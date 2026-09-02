<?php

namespace App\Http\Controllers;

use App\Services\ReservationApiService;
use Illuminate\Http\Request;
use Throwable;

class ReservationController extends Controller
{
    public function index(
        Request $request,
        ReservationApiService $reservationApi
    ) {
       $sessionPatient = session('pasien', []);

        $request->merge([
            'rm' => $request->input(
                'rm',
                data_get($sessionPatient, 'medical_record')
            ),

            'tanggal_lahir' => $request->input(
                'tanggal_lahir',
                data_get($sessionPatient, 'birth_date')
            ),
        ]);

        $validated = $request->validate([
            'rm' => [
                'required',
                'string',
                'max:30',
            ],

            'tanggal_lahir' => [
                'required',
                'date_format:Y-m-d',
            ],
        ], [
            'rm.required' =>
            'Nomor RM wajib tersedia.',

            'tanggal_lahir.required' =>
            'Tanggal lahir wajib tersedia.',

            'tanggal_lahir.date_format' =>
            'Format tanggal lahir harus YYYY-MM-DD.',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Validasi session
        |--------------------------------------------------------------------------
        */

        if (
            $validated['rm']=== '' ||
            $validated['tanggal_lahir'] === ''
        ) {
            return redirect()
                ->route('layanan.menu')
                ->withErrors([
                    'validasi' =>
                        'Data pasien tidak ditemukan. '
                        . 'Silakan masuk kembali ke SAPA RSBM.',
                ]);
        }

        try {
            /*
            |--------------------------------------------------------------------------
            | Ambil reservasi dari API
            |--------------------------------------------------------------------------
            */

            $result = $reservationApi->getHistory(
                $validated['rm'],
                $validated['tanggal_lahir']
            );

            /*
            |--------------------------------------------------------------------------
            | Normalisasi data
            |--------------------------------------------------------------------------
            */

            $reservations = collect(
                data_get($result, 'data', [])
            )
                ->sortByDesc(function ($item) {
                    return data_get(
                        $item,
                        'tanggalreservasi',
                        ''
                    );
                })
                ->values();

            $total = $reservations->count();

            /*
            |--------------------------------------------------------------------------
            | Data pasien
            |--------------------------------------------------------------------------
            |
            | Nama pasien dapat diambil dari hasil reservasi pertama.
            |
            */

            $firstReservation = $reservations->first();

            $patientInfo = [
                'medical_record' => $validated['rm'],

                'tanggal_lahir' => $validated['tanggal_lahir'],

                'nama' => data_get(
                    $firstReservation,
                    'namapasien',
                    data_get(
                        $sessionPatient,
                        'name',
                        ''
                    )
                ),

                'no_bpjs' => data_get(
                    $firstReservation,
                    'nobpjs',
                    ''
                ),

                'jenis_kelamin' => data_get(
                    $firstReservation,
                    'jeniskelamin',
                    ''
                ),
            ];

            return view(
                'reservation.index',
                compact(
                    'reservations',
                    'total',
                    'patientInfo'
                )
            );
        } catch (Throwable $e) {
            report($e);

            return redirect()
                ->route('layanan.menu')
                ->withErrors([
                    'validasi' => $e->getMessage(),
                ]);
        }
    }
}