<?php

namespace App\Http\Controllers;

use App\Services\PoliWaitingApiService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Crypt;
use Throwable;

class PublicQueueApiController extends Controller
{
    private $service;

    public function __construct(PoliWaitingApiService $service)
    {
        $this->service = $service;
    }

    public function home()
    {
        $captcha = $this->generateCaptcha();

        return response()
            ->view('layanan.masuk', [
                'captchaQuestion' => $captcha['question'],
                'captchaToken' => $captcha['token'],
            ])
            ->header(
                'Cache-Control',
                'no-store, no-cache, must-revalidate, max-age=0'
            )
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }
    private function generateCaptcha(): array
    {
        $angka1 = random_int(1, 9);
        $angka2 = random_int(1, 9);

        $answer = $angka1 + $angka2;

        $payload = [
            'answer' => $answer,
            'issued_at' => time(),
        ];

        return [
            'question' => $angka1 . ' + ' . $angka2,
            'token' => Crypt::encryptString(
                json_encode($payload)
            ),
        ];
    }
    public function check(Request $request)
    {
        $validated = $request->validate([
            'rm' => ['required', 'string', 'max:30'],
            'tanggal_lahir' => ['required', 'date'],
        ], [
            'rm.required' => 'Nomor RM wajib diisi.',
            'tanggal_lahir.required' => 'Tanggal lahir wajib diisi.',
        ]);

        $rm = $validated['rm'];
        $tanggalLahir = $validated['tanggal_lahir'];

        try {
            $ringkasanAntrean = $this->getRingkasanAntrean(
                $rm,
                $tanggalLahir
            );

            return view('public.result', [
                'result' => [
                    'found' => true,
                    'response' => $ringkasanAntrean,
                    'metaData' => [
                        'code' => 200,
                        'message' => 'OK',
                    ],
                ],
                'keyword' => $rm,
                'tanggalLahir' => $tanggalLahir,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->withErrors([
                    'validasi' => $e->getMessage(),
                ]);
        }
    }
    public function refresh(Request $request)
    {
        $validated = $request->validate([
            'rm' => ['required', 'string', 'max:30'],
            'tanggal_lahir' => ['required', 'date'],
        ]);

        try {
            $ringkasanAntrean = $this->getRingkasanAntrean(
                $validated['rm'],
                $validated['tanggal_lahir']
            );

            $statusPasien = data_get(
                $ringkasanAntrean,
                'status_pasien',
                '-'
            );

            $statusLower = Str::lower($statusPasien);
            $statusClass = 'is-info';

            if (Str::contains($statusLower, 'menunggu')) {
                $statusClass = 'is-warning';
            } elseif (Str::contains($statusLower, 'sedang')) {
                $statusClass = 'is-primary';
            } elseif (Str::contains($statusLower, 'selesai')) {
                $statusClass = 'is-success';
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'status_pasien' => $statusPasien,
                    'status_class' => $statusClass,
                    'sisa_pasien_di_depan' => (int) data_get(
                        $ringkasanAntrean,
                        'sisa_pasien_di_depan',
                        0
                    ),
                    'antrianloket' => data_get(
                        $ringkasanAntrean,
                        'antrianloket',
                        '-'
                    ),
                    'noantrian' => data_get(
                        $ringkasanAntrean,
                        'noantrian',
                        '-'
                    ),
                ],
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
    private function getRingkasanAntrean(
        string $rm,
        string $tanggalLahir
    ): array {
        /*
    |--------------------------------------------------------------------------
    | Ambil data pasien
    |--------------------------------------------------------------------------
    */

        $resultPasien = $this->curlGet(
            'http://127.0.0.1:8000/service/medifirst2000/reservasionline/get-pasien/'
                . urlencode($rm)
                . '/'
                . urlencode($tanggalLahir)
        );

        if (
            (int) data_get($resultPasien, 'metaData.code') !== 200 ||
            empty(data_get($resultPasien, 'response.data'))
        ) {
            throw new \RuntimeException(
                'Data pasien tidak ditemukan untuk RM dan tanggal lahir tersebut.'
            );
        }

        $pasien = data_get($resultPasien, 'response.data.0');

        /*
    |--------------------------------------------------------------------------
    | Ambil registrasi pasien
    |--------------------------------------------------------------------------
    */

        $resultRegistrasi = $this->curlGet(
            'http://127.0.0.1:8000/service/general/pasien-registrasi?query='
                . urlencode(data_get($pasien, 'nocm'))
        );

        if (
            (int) data_get($resultRegistrasi, 'metaData.code') !== 200 ||
            empty(data_get($resultRegistrasi, 'response.0'))
        ) {
            throw new \RuntimeException(
                'Data registrasi tidak ditemukan untuk pasien tersebut.'
            );
        }

        $dataRegistrasi = data_get($resultRegistrasi, 'response.0');
        $tanggalHariIni = Carbon::today()->format('Y-m-d');

        /*
    |--------------------------------------------------------------------------
    | Ambil dashboard rawat jalan terbaru
    |--------------------------------------------------------------------------
    */

        $queryDashboard = http_build_query([
            'dari' => $tanggalHariIni,
            'sampai' => $tanggalHariIni,
            'kelompokUser' => 'it',
            'ruanganfk' => data_get($dataRegistrasi, 'ruanganfk'),
            'page' => 1,
            'limit' => 200,
        ]);

        $resultDashboard = $this->curlGet(
            'http://127.0.0.1:8000/service/dashboard/rawat-jalan-pasien?'
                . $queryDashboard
        );

        $dashboardData = data_get(
            $resultDashboard,
            'response.data.data'
        );

        if (!is_array($dashboardData)) {
            throw new \RuntimeException(
                'Struktur respons dashboard tidak sesuai. Pesan API: '
                    . data_get(
                        $resultDashboard,
                        'metaData.message',
                        'Tidak ada pesan.'
                    )
            );
        }

        $dataPasienAll = collect($dashboardData)
            ->filter(function ($item) {
                return data_get($item, 'noantrian') !== null
                    && data_get($item, 'noantrian') !== '';
            })
            ->sortBy(function ($item) {
                return (int) data_get($item, 'noantrian');
            })
            ->values();

        /*
    |--------------------------------------------------------------------------
    | Cari pasien yang sedang dicek
    |--------------------------------------------------------------------------
    */

        $pasienAntrean = $dataPasienAll->first(
            function ($item) use ($dataRegistrasi) {
                $targetNorecPd = trim(
                    (string) data_get($dataRegistrasi, 'norec')
                );

                $targetId = trim(
                    (string) data_get($dataRegistrasi, 'id')
                );

                $targetNocm = trim(
                    (string) data_get($dataRegistrasi, 'nocm')
                );

                $norecPd = trim(
                    (string) data_get($item, 'norec_pd')
                );

                $nocmFk = trim(
                    (string) data_get($item, 'nocmfk')
                );

                $norecApd = trim(
                    (string) data_get($item, 'norec_apd')
                );

                $nocm = trim(
                    (string) data_get($item, 'nocm')
                );

                return (
                    $targetNorecPd !== '' &&
                    $norecPd === $targetNorecPd
                ) || (
                    $targetId !== '' &&
                    $nocmFk === $targetId
                ) || (
                    $targetId !== '' &&
                    $norecApd === $targetId
                ) || (
                    $targetNocm !== '' &&
                    $nocm === $targetNocm
                );
            }
        );

        if (!$pasienAntrean) {
            throw new \RuntimeException(
                'Data antrean pasien tidak ditemukan di dashboard rawat jalan hari ini.'
            );
        }

        /*
    |--------------------------------------------------------------------------
    | Fungsi pembaca status
    |--------------------------------------------------------------------------
    */

        $isBelumSelesai = function ($item): bool {
            $label = Str::lower(
                trim((string) data_get($item, 'label_statusperiksa'))
            );

            return Str::contains($label, [
                'menunggu',
                'belum',
            ]);
        };

        $isSelesai = function ($item) use ($isBelumSelesai): bool {
            if ($isBelumSelesai($item)) {
                return false;
            }

            $label = Str::lower(
                trim((string) data_get($item, 'label_statusperiksa'))
            );

            return Str::contains($label, [
                'closing',
                'selesai',
            ]);
        };

        $isSedangDilayani = function ($item) use ($isSelesai): bool {
            if ($isSelesai($item)) {
                return false;
            }

            $status = Str::lower(
                trim((string) data_get($item, 'status'))
            );

            $label = Str::lower(
                trim((string) data_get($item, 'label_statusperiksa'))
            );

            return Str::contains($status, 'sedang')
                || Str::contains($label, 'sedang')
                || !empty(data_get($item, 'exam_started_at'));
        };

        $statusPasien = function (
            $item
        ) use (
            $isSelesai,
            $isSedangDilayani
        ): string {
            if ($isSelesai($item)) {
                return 'Selesai dilayani';
            }

            if ($isSedangDilayani($item)) {
                return 'Sedang dilayani';
            }

            $status = data_get($item, 'status');
            $label = data_get($item, 'label_statusperiksa');

            if (
                Str::contains(
                    Str::lower($label . ' ' . $status),
                    ['menunggu', 'belum']
                )
            ) {
                return 'Menunggu pelayanan';
            }

            return $label ?: $status ?: '-';
        };

        /*
    |--------------------------------------------------------------------------
    | Hitung antrean
    |--------------------------------------------------------------------------
    */

        $noAntreanPasien = (int) data_get(
            $pasienAntrean,
            'noantrian'
        );

        $pasienDiDepan = $dataPasienAll
            ->filter(
                function ($item) use (
                    $noAntreanPasien,
                    $isSelesai
                ) {
                    $nomorAntrean = (int) data_get(
                        $item,
                        'noantrian'
                    );

                    return $nomorAntrean > 0
                        && $nomorAntrean < $noAntreanPasien
                        && !$isSelesai($item);
                }
            )
            ->values();

        $sudahDilayaniSebelumPasien = $dataPasienAll
            ->filter(
                function ($item) use (
                    $noAntreanPasien,
                    $isSelesai
                ) {
                    $nomorAntrean = (int) data_get(
                        $item,
                        'noantrian'
                    );

                    return $nomorAntrean > 0
                        && $nomorAntrean < $noAntreanPasien
                        && $isSelesai($item);
                }
            )
            ->count();

        $sedangDilayani = $dataPasienAll
            ->filter(function ($item) use ($isSedangDilayani) {
                return $isSedangDilayani($item);
            })
            ->sortBy(function ($item) {
                return (int) data_get($item, 'noantrian');
            })
            ->first();

        $terakhirSelesai = $dataPasienAll
            ->filter(function ($item) use ($isSelesai) {
                return $isSelesai($item);
            })
            ->sortByDesc(function ($item) {
                return (int) data_get($item, 'noantrian');
            })
            ->first();

        $pasienBelumSelesai = $dataPasienAll
            ->filter(function ($item) use ($isSelesai) {
                return !$isSelesai($item);
            })
            ->values();

        $totalPasien = $dataPasienAll->count();
        $totalBelumSelesai = $pasienBelumSelesai->count();
        $totalSelesaiDilayani = $totalPasien - $totalBelumSelesai;

        /*
    |--------------------------------------------------------------------------
    | Hasil ringkasan
    |--------------------------------------------------------------------------
    */

        return [
            'namapasien' => data_get(
                $pasienAntrean,
                'namapasien'
            ),
            'nocm' => data_get(
                $pasienAntrean,
                'nocm'
            ),
            'namaruangan' => data_get(
                $pasienAntrean,
                'namaruangan'
            ),
            'dokter' => data_get(
                $pasienAntrean,
                'namalengkap'
            ),
            'noantrian' => $noAntreanPasien,
            'antrianloket' => data_get(
                $pasienAntrean,
                'antrianloket'
            ),

            'status_pasien' => $statusPasien(
                $pasienAntrean
            ),
            'status_asli' => data_get(
                $pasienAntrean,
                'status'
            ),
            'label_statusperiksa' => data_get(
                $pasienAntrean,
                'label_statusperiksa'
            ),

            'total_pasien' => $totalPasien,
            'total_belum_selesai' => $totalBelumSelesai,
            'total_selesai_dilayani' => $totalSelesaiDilayani,

            'sudah_dilayani_sebelum_pasien' =>
            $sudahDilayaniSebelumPasien,

            'sisa_pasien_di_depan' =>
            $pasienDiDepan->count(),

            'sedang_dilayani' => $sedangDilayani
                ? [
                    'noantrian' => data_get(
                        $sedangDilayani,
                        'noantrian'
                    ),
                    'namapasien' => data_get(
                        $sedangDilayani,
                        'namapasien'
                    ),
                    'status' => data_get(
                        $sedangDilayani,
                        'status'
                    ),
                    'label_statusperiksa' => data_get(
                        $sedangDilayani,
                        'label_statusperiksa'
                    ),
                ]
                : null,

            'terakhir_selesai' => $terakhirSelesai
                ? [
                    'noantrian' => data_get(
                        $terakhirSelesai,
                        'noantrian'
                    ),
                    'nocm' => data_get(
                        $terakhirSelesai,
                        'nocm'
                    ),
                    'namapasien' => data_get(
                        $terakhirSelesai,
                        'namapasien'
                    ),
                    'status' => data_get(
                        $terakhirSelesai,
                        'status'
                    ),
                    'label_statusperiksa' => data_get(
                        $terakhirSelesai,
                        'label_statusperiksa'
                    ),
                    'tglclosing' => data_get(
                        $terakhirSelesai,
                        'tglclosing'
                    ),
                ]
                : null,

            'pasien_belum_selesai' => $pasienBelumSelesai
                ->map(function ($item) {
                    return [
                        'noantrian' => data_get(
                            $item,
                            'noantrian'
                        ),
                        'nocm' => data_get(
                            $item,
                            'nocm'
                        ),
                        'namapasien' => data_get(
                            $item,
                            'namapasien'
                        ),
                        'status' => data_get(
                            $item,
                            'status'
                        ),
                        'label_statusperiksa' => data_get(
                            $item,
                            'label_statusperiksa'
                        ),
                    ];
                })
                ->values()
                ->all(),
        ];
    }
    // public function check(Request $request)
    // {
    //     $validated = $request->validate([
    //         'rm' => ['required', 'string', 'max:30'],
    //         'tanggal_lahir' => ['required', 'date'],
    //     ], [
    //         'rm.required' => 'Nomor RM wajib diisi.',
    //         'tanggal_lahir.required' => 'Tanggal lahir wajib diisi.',
    //     ]);

    //     $rm = $validated['rm'];
    //     $tanggalLahir = $validated['tanggal_lahir'];

    //     // Contoh cari data antrean berdasarkan RM dan tanggal lahir

    //     $curl = curl_init();

    //     curl_setopt_array($curl, array(
    //         CURLOPT_URL => 'http://127.0.0.1:8000/service/medifirst2000/reservasionline/get-pasien/' . $rm . '/' . $tanggalLahir,
    //         CURLOPT_RETURNTRANSFER => true,
    //         CURLOPT_ENCODING => '',
    //         CURLOPT_MAXREDIRS => 10,
    //         CURLOPT_TIMEOUT => 0,
    //         CURLOPT_FOLLOWLOCATION => true,
    //         CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    //         CURLOPT_CUSTOMREQUEST => 'GET',
    //         CURLOPT_HTTPHEADER => array(
    //             'token: eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzUxMiJ9.eyJzdWIiOiJzdXBlcmFkbWluIiwic2Vzc2lvbklkIjoiMWY5Njc1ZGQtMWI1Ny00MWJjLWI1MzItNWYyMjcxMGMyMTc0IiwiZXhwIjoxNzcwMjU5NjM0fQ.H6LHgfJaWk1J1VauyQaOYwJb5tVeDwomgbrXzpNVTGBUXU1ZkDNDx3SgQE6tNqLzEYewt8jPEHNj4KJs84k14w.MQ==',
    //             'Cookie: transmedic_session=eyJpdiI6IndEYVFoZmk4Y1c5RlVnTGd2OHB0RVE9PSIsInZhbHVlIjoic1FrSmt5SkxDUFVSRFg4c3pYUXhud3ovc3lCOGdFMEhldU83MG5tYkNVZzRrM0dBRUNWbXBsMVJPZDR4TnR2cVFSWEdZeU1DVWpSaWpseGhwdjFuSm1OSHJQQVpiblRyUEw2SWlQNmN5ZHJwM1NWK0R3WWJDVlJLZk9kcXhHTGoiLCJtYWMiOiJjNWRmYmJiYzIxYmU2M2QyYWY1YTM4OTljNmJjZDRjMjAyZmM3ZWUzM2I2Y2ZmYWMzNzc2OGI2MTBmYWU4MWVlIiwidGFnIjoiIn0%3D'
    //         ),
    //     ));

    //     $response = curl_exec($curl);

    //     curl_close($curl);

    //     $result = json_decode($response, true);

    //     if ($result['metaData']['code'] == 200 && !empty($result['response']['data'])) {
    //         $pasien = $result['response']['data'][0];
    //         $curl = curl_init();
    //         curl_setopt_array($curl, array(
    //             CURLOPT_URL => 'http://127.0.0.1:8000/service/general/pasien-registrasi?query=' . $pasien['nocm'],
    //             CURLOPT_RETURNTRANSFER => true,
    //             CURLOPT_ENCODING => '',
    //             CURLOPT_MAXREDIRS => 10,
    //             CURLOPT_TIMEOUT => 0,
    //             CURLOPT_FOLLOWLOCATION => true,
    //             CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    //             CURLOPT_CUSTOMREQUEST => 'GET',
    //             CURLOPT_HTTPHEADER => array(
    //                 'Token: eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzUxMiJ9.eyJzdWIiOiJzdXBlcmFkbWluIiwic2Vzc2lvbklkIjoiMWY5Njc1ZGQtMWI1Ny00MWJjLWI1MzItNWYyMjcxMGMyMTc0IiwiZXhwIjoxNzcwMjU5NjM0fQ.H6LHgfJaWk1J1VauyQaOYwJb5tVeDwomgbrXzpNVTGBUXU1ZkDNDx3SgQE6tNqLzEYewt8jPEHNj4KJs84k14w.MQ=='
    //             ),
    //         ));

    //         $response2 = curl_exec($curl);

    //         curl_close($curl);

    //         $result2 = json_decode($response2, true);

    //         if (
    //             isset($result2['metaData']['code']) &&
    //             $result2['metaData']['code'] == 200 &&
    //             !empty($result2['response']) &&
    //             isset($result2['response'][0])
    //         ) {
    //             $data = $result2['response'][0];
    //             $tanggalHariIni = Carbon::today()->format('Y-m-d');


    //             $curl = curl_init();

    //             curl_setopt_array($curl, array(
    //                 CURLOPT_URL => 'http://127.0.0.1:8000/service/dashboard/rawat-jalan-pasien?dari=' . $tanggalHariIni . '&sampai=' . $tanggalHariIni . '&kelompokUser=it&ruanganfk=' . $data['ruanganfk'] . '&page=1&limit=200',
    //                 CURLOPT_RETURNTRANSFER => true,
    //                 CURLOPT_ENCODING => '',
    //                 CURLOPT_MAXREDIRS => 10,
    //                 CURLOPT_TIMEOUT => 0,
    //                 CURLOPT_FOLLOWLOCATION => true,
    //                 CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    //                 CURLOPT_CUSTOMREQUEST => 'GET',
    //                 CURLOPT_HTTPHEADER => array(
    //                     'token: eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzUxMiJ9.eyJzdWIiOiJzdXBlcmFkbWluIiwic2Vzc2lvbklkIjoiMWY5Njc1ZGQtMWI1Ny00MWJjLWI1MzItNWYyMjcxMGMyMTc0IiwiZXhwIjoxNzcwMjU5NjM0fQ.H6LHgfJaWk1J1VauyQaOYwJb5tVeDwomgbrXzpNVTGBUXU1ZkDNDx3SgQE6tNqLzEYewt8jPEHNj4KJs84k14w.MQ==',
    //                     'Cookie: transmedic_session=eyJpdiI6IkRjMllIb0s4MlNqSDNodzE4MFc4MXc9PSIsInZhbHVlIjoicTFoeXVoUzcrcm5PL1poSno3VjdIWUltcmhMa3BTSUV5QUZVLzNINUE5Z1RjVFdWTGp2MGNDSFArRnhqQTZRbGdBT0N6RnB2M0ZNQ001a096eHduRS8rbDBwOWpTbXIxYnRTb0dJMHhZQnh6N21Md2VmRUVXV2F4OTA4ODdwTUgiLCJtYWMiOiJjYjgwMTRmNDg4OTM5OWQxOThmOGNlYzhiYTBmOWZjYTUyODI5ZjVhMjhlZjgxYmJhZTNiYTNhYTJiMmM2ODIzIiwidGFnIjoiIn0%3D'
    //                 ),
    //             ));

    //             $response = curl_exec($curl);

    //             curl_close($curl);
    //             $result = json_decode($response, true);

    //             // kalau pakai Laravel HTTP Client:
    //             // $result = $response->json();


    //             $dataPasienAll = collect(data_get($result, 'response.data.data', []))
    //                 ->filter(fn($item) => !empty(data_get($item, 'noantrian')))
    //                 ->sortBy(fn($item) => (int) data_get($item, 'noantrian'))
    //                 ->values();

    //             $pasienAntrean = $dataPasienAll->first(function ($item) use ($data) {
    //                 $targetNorecPd = data_get($data, 'norec');
    //                 $targetId      = data_get($data, 'id');
    //                 $targetNocm    = data_get($data, 'nocm');

    //                 return ($targetNorecPd && data_get($item, 'norec_pd') === $targetNorecPd)
    //                     || ($targetId && data_get($item, 'nocmfk') === $targetId)
    //                     || ($targetId && data_get($item, 'norec_apd') === $targetId)
    //                     || ($targetNocm && data_get($item, 'nocm') === $targetNocm);
    //             });

    //             if (!$pasienAntrean) {
    //                 return back()
    //                     ->withInput()
    //                     ->withErrors([
    //                         'validasi' => 'Data antrean pasien tidak ditemukan di dashboard rawat jalan hari ini.'
    //                     ]);
    //             }

    //             $isBelumSelesai = function ($item): bool {
    //                 $label = Str::lower(trim((string) data_get($item, 'label_statusperiksa')));

    //                 return Str::contains($label, [
    //                     'menunggu',
    //                     'belum',
    //                 ]);
    //             };

    //             $isSelesai = function ($item) use ($isBelumSelesai): bool {
    //                 // Prioritas: kalau label masih menunggu/belum, jangan dihitung selesai
    //                 if ($isBelumSelesai($item)) {
    //                     return false;
    //                 }

    //                 $label = Str::lower(trim((string) data_get($item, 'label_statusperiksa')));

    //                 return Str::contains($label, [
    //                     'closing',
    //                     'selesai',
    //                 ]);
    //             };
    //             $isSedangDilayani = function ($item) use ($isSelesai): bool {
    //                 if ($isSelesai($item)) {
    //                     return false;
    //                 }

    //                 $status = Str::lower(trim((string) data_get($item, 'status')));
    //                 $label  = Str::lower(trim((string) data_get($item, 'label_statusperiksa')));

    //                 return Str::contains($status, 'sedang')
    //                     || Str::contains($label, 'sedang')
    //                     || !empty(data_get($item, 'exam_started_at'));
    //             };

    //             $statusPasien = function ($item) use ($isSelesai, $isSedangDilayani): string {
    //                 if ($isSelesai($item)) {
    //                     return 'Selesai dilayani';
    //                 }

    //                 if ($isSedangDilayani($item)) {
    //                     return 'Sedang dilayani';
    //                 }

    //                 $status = data_get($item, 'status');
    //                 $label  = data_get($item, 'label_statusperiksa');

    //                 if (Str::contains(Str::lower($label . ' ' . $status), ['menunggu', 'belum'])) {
    //                     return 'Menunggu pelayanan';
    //                 }

    //                 return $label ?: $status ?: '-';
    //             };

    //             // TANPA FILTER DOKTER
    //             $dataPasien = $dataPasienAll;

    //             $noAntreanPasien = (int) data_get($pasienAntrean, 'noantrian');

    //             $pasienDiDepan = $dataPasien->filter(function ($item) use ($noAntreanPasien, $isSelesai) {
    //                 $no = (int) data_get($item, 'noantrian');

    //                 return $no > 0
    //                     && $no < $noAntreanPasien
    //                     && !$isSelesai($item);
    //             })->values();

    //             $sudahDilayaniSebelumPasien = $dataPasien->filter(function ($item) use ($noAntreanPasien, $isSelesai) {
    //                 $no = (int) data_get($item, 'noantrian');

    //                 return $no > 0
    //                     && $no < $noAntreanPasien
    //                     && $isSelesai($item);
    //             })->count();

    //             $totalSelesaiDilayani = $dataPasien
    //                 ->filter(fn($item) => $isSelesai($item))
    //                 ->count();

    //             $sedangDilayani = $dataPasien
    //                 ->filter(fn($item) => $isSedangDilayani($item))
    //                 ->sortBy(fn($item) => (int) data_get($item, 'noantrian'))
    //                 ->first();

    //             $terakhirSelesai = $dataPasien
    //                 ->filter(fn($item) => $isSelesai($item))
    //                 ->sortByDesc(fn($item) => (int) data_get($item, 'noantrian'))
    //                 ->first();
    //             $totalPasien = $dataPasien->count();

    //             $pasienBelumSelesai = $dataPasien
    //                 ->filter(fn($item) => !$isSelesai($item))
    //                 ->values();

    //             $totalBelumSelesai = $pasienBelumSelesai->count();

    //             $totalSelesaiDilayani = $totalPasien - $totalBelumSelesai;
    //             $ringkasanAntrean = [
    //                 'namapasien' => data_get($pasienAntrean, 'namapasien'),
    //                 'nocm' => data_get($pasienAntrean, 'nocm'),
    //                 'namaruangan' => data_get($pasienAntrean, 'namaruangan'),
    //                 'dokter' => data_get($pasienAntrean, 'namalengkap'),
    //                 'noantrian' => $noAntreanPasien,
    //                 'antrianloket' => data_get($pasienAntrean, 'antrianloket'),

    //                 'status_pasien' => $statusPasien($pasienAntrean),
    //                 'status_asli' => data_get($pasienAntrean, 'status'),
    //                 'label_statusperiksa' => data_get($pasienAntrean, 'label_statusperiksa'),

    //                 'total_pasien' => $totalPasien,
    //                 'total_belum_selesai' => $totalBelumSelesai,
    //                 'total_selesai_dilayani' => $totalSelesaiDilayani,

    //                 'sudah_dilayani_sebelum_pasien' => $sudahDilayaniSebelumPasien,
    //                 'sisa_pasien_di_depan' => $pasienDiDepan->count(),

    //                 'sedang_dilayani' => $sedangDilayani ? [
    //                     'noantrian' => data_get($sedangDilayani, 'noantrian'),
    //                     'namapasien' => data_get($sedangDilayani, 'namapasien'),
    //                     'status' => data_get($sedangDilayani, 'status'),
    //                     'label_statusperiksa' => data_get($sedangDilayani, 'label_statusperiksa'),
    //                 ] : null,

    //                 'terakhir_selesai' => $terakhirSelesai ? [
    //                     'noantrian' => data_get($terakhirSelesai, 'noantrian'),
    //                     'nocm' => data_get($terakhirSelesai, 'nocm'),
    //                     'namapasien' => data_get($terakhirSelesai, 'namapasien'),
    //                     'status' => data_get($terakhirSelesai, 'status'),
    //                     'label_statusperiksa' => data_get($terakhirSelesai, 'label_statusperiksa'),
    //                     'tglclosing' => data_get($terakhirSelesai, 'tglclosing'),
    //                 ] : null,

    //                 'pasien_belum_selesai' => $pasienBelumSelesai->map(function ($item) {
    //                     return [
    //                         'noantrian' => data_get($item, 'noantrian'),
    //                         'nocm' => data_get($item, 'nocm'),
    //                         'namapasien' => data_get($item, 'namapasien'),
    //                         'status' => data_get($item, 'status'),
    //                         'label_statusperiksa' => data_get($item, 'label_statusperiksa'),
    //                     ];
    //                 })->values(),
    //             ];

    //             // return response()->json([
    //             //     'metaData' => [
    //             //         'code' => 200,
    //             //         'message' => 'OK',
    //             //     ],
    //             //     'response' => $ringkasanAntrean,
    //             // ]);
    //             return view('public.result', [
    //                 'result' => [
    //                     'found' => true,
    //                     'response' => $ringkasanAntrean,
    //                     'metaData' => [
    //                         'code' => 200,
    //                         'message' => 'OK',
    //                     ],
    //                 ],
    //                 'keyword' => $rm,
    //                 'tanggalLahir' => $tanggalLahir,
    //             ]);
    //             // Kalau sudah mau tampil ke blade:
    //             // return view('queue.result', compact('ringkasanAntrean'));


    //             // return view('queue.result', compact('data'));
    //         } else {
    //             return back()
    //                 ->withInput()
    //                 ->withErrors([
    //                     'validasi' => 'Data Registrasi tidak ditemukan untuk RM dan tanggal lahir tersebut.'
    //                 ]);
    //         }
    //     } else {
    //         return back()
    //             ->withInput()
    //             ->withErrors([
    //                 'validasi' => 'Data Pasien tidak ditemukan untuk RM dan tanggal lahir tersebut.'
    //             ]);
    //     }


    //     // if (!$data) {
    //     //     return back()
    //     //         ->withInput()
    //     //         ->with('error', 'Data antrean tidak ditemukan untuk RM dan tanggal lahir tersebut.');
    //     // }

    //     // return view('queue.result', compact('data'));
    // }

    public function checkJson(Request $request)
    {
        $validated = $request->validate([
            'check_type' => ['nullable', 'in:booking,medical_record'],
            'keyword' => ['nullable', 'string', 'max:100'],
            'kodebooking' => ['nullable', 'string', 'max:100'],
            'no_rm' => ['nullable', 'string', 'max:30'],
            'tanggal_lahir' => ['nullable', 'date'],
        ]);

        $payload = $this->buildPayload($validated);

        return response()->json($this->service->check($payload));
    }

    private function buildPayload(array $validated): array
    {
        $checkType = $validated['check_type'] ?? (
            ! empty($validated['no_rm']) ? 'medical_record' : 'booking'
        );

        if ($checkType === 'medical_record') {
            return [
                'check_type' => 'medical_record',
                'keyword' => trim((string) ($validated['no_rm'] ?? '')),
                'no_rm' => trim((string) ($validated['no_rm'] ?? '')),
                'tanggal_lahir' => $validated['tanggal_lahir'] ?? null,
            ];
        }

        $kodeBooking = trim((string) ($validated['kodebooking'] ?? $validated['keyword'] ?? ''));

        return [
            'check_type' => 'booking',
            'keyword' => $kodeBooking,
            'kodebooking' => $kodeBooking,
        ];
    }
    private function curlGet(
        string $url,
        array $additionalHeaders = []
    ): array {
        $token = config('services.transmedika.token');

        if (empty($token)) {
            throw new \RuntimeException(
                'Token Transmedika belum dikonfigurasi.'
            );
        }

        $headers = array_merge([
            'Accept: application/json',
            'token: ' . $token,
        ], $additionalHeaders);

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => $headers,
        ]);

        $response = curl_exec($curl);

        $httpCode = curl_getinfo(
            $curl,
            CURLINFO_HTTP_CODE
        );

        $curlError = curl_error($curl);

        curl_close($curl);

        if ($response === false || $curlError !== '') {
            throw new \RuntimeException(
                'Gagal menghubungi API: ' . $curlError
            );
        }

        $result = json_decode($response, true);

        if (!is_array($result)) {
            throw new \RuntimeException(
                'Respons API bukan JSON yang valid. HTTP: '
                    . $httpCode
                    . '. Respons: '
                    . substr($response, 0, 300)
            );
        }

        if ($httpCode >= 400) {
            throw new \RuntimeException(
                'API mengembalikan HTTP '
                    . $httpCode
                    . ': '
                    . data_get(
                        $result,
                        'metaData.message',
                        data_get($result, 'message', 'Terjadi kesalahan API.')
                    )
            );
        }

        return $result;
    }
}
