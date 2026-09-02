<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class LaboratoryApiService
{
    public function getHasil(string $noOrder): array
    {
        try {
            $baseUrl = rtrim(
                (string) config('api_simrs.base_url'),
                '/'
            );

            $endpoint = '/'
                . ltrim(
                    (string) config(
                        'api_simrs.laboratorium.hasil_endpoint'
                    ),
                    '/'
                );

            $token = (string) config(
                'api_simrs.token'
            );

            $secret = (string) config(
                'api_simrs.secret'
            );

            $timeout = (int) config(
                'api_simrs.timeout',
                20
            );
            /*
            |--------------------------------------------------------------------------
            | Validasi konfigurasi
            |--------------------------------------------------------------------------
            */

            if ($baseUrl === '') {
                throw new RuntimeException(
                    'LAB_API_BASE_URL belum diatur.'
                );
            }

            if ($token === '') {
                throw new RuntimeException(
                    'LAB_API_TOKEN belum diatur.'
                );
            }

            if ($secret === '') {
                throw new RuntimeException(
                    'LAB_API_SECRET belum diatur.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | HTTP Method
            |--------------------------------------------------------------------------
            */

            $method = 'GET';

            /*
            |--------------------------------------------------------------------------
            | Query
            |--------------------------------------------------------------------------
            */

            $query = [
                'noorder' => trim($noOrder),
            ];

            ksort($query);

            $queryString = http_build_query(
                $query,
                '',
                '&',
                PHP_QUERY_RFC3986
            );

            /*
            |--------------------------------------------------------------------------
            | Canonical URI
            |--------------------------------------------------------------------------
            |
            | Yang ditandatangani BUKAN base URL.
            |
            */

            $canonicalPath = $endpoint
                . '?'
                . $queryString;

            /*
            |--------------------------------------------------------------------------
            | Timestamp
            |--------------------------------------------------------------------------
            */

            $timestamp = (string) time();

            /*
            |--------------------------------------------------------------------------
            | Body Hash
            |--------------------------------------------------------------------------
            |
            | Karena GET tidak mempunyai body.
            |
            */

            $bodyHash = hash(
                'sha256',
                ''
            );

            /*
            |--------------------------------------------------------------------------
            | Payload Signature
            |--------------------------------------------------------------------------
            */

            $payload = implode("\n", [
                $method,
                $canonicalPath,
                $timestamp,
                $bodyHash,
            ]);

            /*
            |--------------------------------------------------------------------------
            | HMAC SHA256
            |--------------------------------------------------------------------------
            */

            $signature = hash_hmac(
                'sha256',
                $payload,
                $secret
            );

            /*
            |--------------------------------------------------------------------------
            | URL
            |--------------------------------------------------------------------------
            */

            $url = $baseUrl
                . $canonicalPath;

            /*
            |--------------------------------------------------------------------------
            | Request
            |--------------------------------------------------------------------------
            */

            $response = Http::timeout($timeout)
                ->acceptJson()
                ->withHeaders([
                    'X-Token' => $token,
                    'X-Timestamp' => $timestamp,
                    'X-Signature' => $signature,
                ])
                ->get($url);

            /*
            |--------------------------------------------------------------------------
            | HTTP Error
            |--------------------------------------------------------------------------
            */

            if (! $response->successful()) {
                return [
                    'success' => false,
                    'message' =>
                    data_get(
                        $response->json(),
                        'message'
                    )
                        ?? 'API laboratorium gagal diakses.',
                    'http_status' => $response->status(),
                    'data' => [],
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | Response JSON
            |--------------------------------------------------------------------------
            */

            $body = $response->json();

            if (! is_array($body)) {
                return [
                    'success' => false,
                    'message' =>
                    'Response API laboratorium bukan JSON.',
                    'data' => [],
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | Status API
            |--------------------------------------------------------------------------
            */

            if (
                array_key_exists('status', $body)
                && ! $body['status']
            ) {
                return [
                    'success' => false,
                    'message' =>
                    $body['message']
                        ?? 'Hasil laboratorium tidak ditemukan.',
                    'data' => [],
                ];
            }

            return [
                'success' => true,
                'message' =>
                $body['message']
                    ?? 'Data hasil laboratorium berhasil diambil.',
                'data' =>
                is_array($body['data'] ?? null)
                    ? $body['data']
                    : [],
            ];
        } catch (Throwable $e) {
            report($e);

            return [
                'success' => false,
                'message' =>
                'Terjadi kesalahan saat mengambil hasil laboratorium.',
                'error' => $e->getMessage(),
                'data' => [],
            ];
        }
    }
}
