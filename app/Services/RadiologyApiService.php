<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

class RadiologyApiService
{
    protected $baseUrl;
    protected $token;
    protected $secret;
    protected $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('api_simrs.base_url'), '/');
        $this->token = (string) config('api_simrs.token');
        $this->secret = (string) config('api_simrs.secret');
        $this->timeout = (int) config('api_simrs.timeout', 20);
    }

    public function getByNrm($nrm)
    {
        $nrm = trim((string) $nrm);

        if ($nrm === '') {
            return $this->errorResult('Nomor rekam medis belum tersedia.');
        }

        if ($this->baseUrl === '' || $this->token === '' || $this->secret === '') {
            return $this->errorResult('Konfigurasi API radiologi belum lengkap.');
        }

        $method = 'GET';
        $path = '/api/service/getpemeriksaanbynrm';
        $query = ['nrm' => $nrm];

        try {
            $timestamp = (string) time();
            $signature = $this->generateSignature($method, $path, $query, '', $timestamp);

            $response = Http::withHeaders([
                'X-Token' => $this->token,
                'X-Timestamp' => $timestamp,
                'X-Signature' => $signature,
                'Accept' => 'application/json',
            ])->timeout($this->timeout)
              ->get($this->baseUrl . $path, $query);

            if (! $response->successful()) {
                return $this->errorResult('API radiologi belum berhasil diakses.', [
                    'http_status' => $response->status(),
                ]);
            }

            $json = $response->json();

            if (! is_array($json)) {
                return $this->errorResult('Response API radiologi tidak valid.');
            }

            if (! data_get($json, 'status', false)) {
                return $this->errorResult(data_get($json, 'message', 'Data radiologi tidak ditemukan.'));
            }

            return [
                'success' => true,
                'message' => data_get($json, 'message', 'Data berhasil diambil.'),
                'data' => is_array(data_get($json, 'data')) ? data_get($json, 'data') : [],
            ];
        } catch (Throwable $e) {
            report($e);

            return $this->errorResult('Terjadi kesalahan saat mengakses API radiologi.', [
                'exception' => $e->getMessage(),
            ]);
        }
    }

    protected function generateSignature($method, $path, array $query, $rawBody, $timestamp)
    {
        ksort($query);

        $queryString = http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        $requestTarget = '/' . ltrim($path, '/');

        if ($queryString !== '') {
            $requestTarget .= '?' . $queryString;
        }

        $bodyHash = hash('sha256', (string) $rawBody);

        $payload = strtoupper($method)
            . "\n" . $requestTarget
            . "\n" . $timestamp
            . "\n" . $bodyHash;

        return hash_hmac('sha256', $payload, $this->secret);
    }

    protected function errorResult($message, array $extra = [])
    {
        return array_merge([
            'success' => false,
            'message' => $message,
            'data' => [],
        ], $extra);
    }
}
