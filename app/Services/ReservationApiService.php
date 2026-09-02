<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class ReservationApiService
{
    private string $baseUrl;

    private string $endpoint;

    private string $token;

    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim(
            (string) config('services.ereservasi.base_url'),
            '/'
        );

        $this->endpoint = (string) config(
            'services.ereservasi.endpoint'
        );

        $this->token = (string) config(
            'services.ereservasi.token'
        );

        $this->timeout = (int) config(
            'services.ereservasi.timeout',
            15
        );
    }

    /**
     * Mengambil riwayat reservasi pasien.
     */
    public function getHistory(
        string $medicalRecord,
        string $tanggalLahir
    ): array {
        if ($this->baseUrl === '') {
            throw new RuntimeException(
                'Konfigurasi URL API reservasi belum tersedia.'
            );
        }

        if ($this->token === '') {
            throw new RuntimeException(
                'Konfigurasi token API reservasi belum tersedia.'
            );
        }

        $url = $this->baseUrl . '/' . ltrim(
            $this->endpoint,
            '/'
        );

        $response = Http::timeout($this->timeout)
            ->acceptJson()
            ->withHeaders([
                'token' => $this->token,
            ])
            ->get($url, [
                'nocmNama' => $medicalRecord,
                'tgllahir' => $tanggalLahir,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException(
                'Layanan reservasi belum dapat diakses. HTTP '
                . $response->status()
                . '.'
            );
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            throw new RuntimeException(
                'Format respons layanan reservasi tidak sesuai.'
            );
        }

        $metaCode = (int) data_get(
            $payload,
            'metaData.code',
            0
        );

        $metaMessage = trim(
            (string) data_get(
                $payload,
                'metaData.message',
                ''
            )
        );

        if ($metaCode !== 200) {
            throw new RuntimeException(
                $metaMessage !== ''
                    ? $metaMessage
                    : 'Data reservasi tidak berhasil diambil.'
            );
        }

        $data = data_get(
            $payload,
            'response.data',
            []
        );

        if (! is_array($data)) {
            $data = [];
        }

        return [
            'success' => true,

            'message' => $metaMessage !== ''
                ? $metaMessage
                : 'OK',

            'total' => (int) data_get(
                $payload,
                'response.total',
                count($data)
            ),

            'data' => $data,
        ];
    }
}