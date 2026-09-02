@extends('layouts.app', ['title' => 'Hasil Cek Waktu Tunggu'])

@section('content')
<style>
    .detail-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
        margin-top: 16px;
    }

    .detail-grid>div {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .table-responsive {
        width: 100%;
        overflow-x: auto;
    }

    .table-simple {
        width: 100%;
        border-collapse: collapse;
        margin-top: 14px;
    }

    .table-simple th,
    .table-simple td {
        padding: 12px 10px;
        text-align: left;
        border-bottom: 1px solid rgba(15, 23, 42, 0.08);
        vertical-align: top;
    }

    .table-simple th {
        font-size: 13px;
        color: var(--muted);
        font-weight: 800;
    }

    @media (max-width: 768px) {
        .detail-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@php
$antrean = data_get($result, 'response', []);
$found = data_get($result, 'found', false);

$statusPasien = data_get($antrean, 'status_pasien', '-');
$statusLower = \Illuminate\Support\Str::lower($statusPasien);

$statusClass = 'is-info';

if (\Illuminate\Support\Str::contains($statusLower, 'menunggu')) {
$statusClass = 'is-warning';
} elseif (\Illuminate\Support\Str::contains($statusLower, 'sedang')) {
$statusClass = 'is-primary';
} elseif (\Illuminate\Support\Str::contains($statusLower, 'selesai')) {
$statusClass = 'is-success';
}

$terakhirSelesai = data_get($antrean, 'terakhir_selesai');
$sedangDilayani = data_get($antrean, 'sedang_dilayani');
$pasienBelumSelesai = collect(data_get($antrean, 'pasien_belum_selesai', []));
@endphp

<section class="stack">
    @if(! $found)
    <div class="panel ticket">
        <div class="eyebrow">{{ !empty($result['is_error']) ? 'API bermasalah' : 'Tidak ditemukan' }}</div>
        <h1>Data belum tersedia</h1>
        <p class="lead">{{ $result['message'] ?? 'Data antrean tidak ditemukan.' }}</p>
        <p class="muted">Kode yang dicek: <strong>{{ $keyword }}</strong></p>
    </div>
    @else
    <div class="panel ticket">
        <div class="eyebrow">{{ data_get($antrean, 'namaruangan', 'Poli') }}</div>

        <div class="queue-number">
            {{ data_get($antrean, 'noantrian', '-') }}
        </div>

        <div class="meta">
            <span class="pill {{ $statusClass }}">
                {{ data_get($antrean, 'status_pasien', '-') }}
            </span>

            @if(data_get($antrean, 'antrianloket'))
            <span class="pill">
                Loket: {{ data_get($antrean, 'antrianloket') }}
            </span>
            @endif

            <!-- @if(data_get($antrean, 'dokter'))
            <span class="pill">
                {{ data_get($antrean, 'dokter') }}
            </span>
            @endif -->
        </div>

        <div style="margin-top: 18px;">
            <h1 style="margin-bottom: 6px;">
                {{ data_get($antrean, 'namapasien', '-') }}
            </h1>
            <p class="muted">
                No. RM: <strong>{{ data_get($antrean, 'nocm', '-') }}</strong>
            </p>
        </div>
    </div>

    <div class="metrics">
        <div class="metric">
            <strong>{{ data_get($antrean, 'sisa_pasien_di_depan', 0) }}</strong>
            <span>pasien di depan</span>
        </div>

        <!-- <div class="metric">
            <strong>{{ data_get($antrean, 'sudah_dilayani_sebelum_pasien', 0) }}</strong>
            <span>sudah dilayani sebelum pasien</span>
        </div>

        <div class="metric">
            <strong>{{ data_get($antrean, 'total_selesai_dilayani', 0) }}</strong>
            <span>total selesai dilayani</span>
        </div>

        <div class="metric">
            <strong>{{ data_get($antrean, 'total_belum_selesai', 0) }}</strong>
            <span>belum selesai</span>
        </div>

        <div class="metric">
            <strong>{{ data_get($antrean, 'total_pasien', 0) }}</strong>
            <span>total pasien poli</span>
        </div> -->
    </div>

    <!-- <div class="panel form-panel">
        <h2>Detail antrean pasien</h2>

        <div class="detail-grid">
            <div>
                <span class="muted">Nama Pasien</span>
                <strong>{{ data_get($antrean, 'namapasien', '-') }}</strong>
            </div>

            <div>
                <span class="muted">No. RM</span>
                <strong>{{ data_get($antrean, 'nocm', '-') }}</strong>
            </div>

            <div>
                <span class="muted">No. Antrean Poli</span>
                <strong>{{ data_get($antrean, 'noantrian', '-') }}</strong>
            </div>

            <div>
                <span class="muted">Antrean Loket</span>
                <strong>{{ data_get($antrean, 'antrianloket', '-') }}</strong>
            </div>

            <div>
                <span class="muted">Status Pasien</span>
                <strong>{{ data_get($antrean, 'status_pasien', '-') }}</strong>
            </div>

            <div>
                <span class="muted">Status Asli API</span>
                <strong>{{ data_get($antrean, 'status_asli', '-') }}</strong>
            </div>

            <div>
                <span class="muted">Label Status Periksa</span>
                <strong>{{ data_get($antrean, 'label_statusperiksa', '-') }}</strong>
            </div>

            <div>
                <span class="muted">Poli</span>
                <strong>{{ data_get($antrean, 'namaruangan', '-') }}</strong>
            </div>
        </div>
    </div>

    <div class="panel form-panel">
        <h2>Pasien sedang dilayani</h2>

        @if($sedangDilayani)
        <p class="lead">
            No. antrean
            <strong>{{ data_get($sedangDilayani, 'noantrian', '-') }}</strong>
            atas nama
            <strong>{{ data_get($sedangDilayani, 'namapasien', '-') }}</strong>
        </p>

        <div class="meta">
            <span class="pill is-primary">
                {{ data_get($sedangDilayani, 'status', '-') }}
            </span>
            <span class="pill">
                {{ data_get($sedangDilayani, 'label_statusperiksa', '-') }}
            </span>
        </div>
        @else
        <p class="lead">Saat ini belum ada pasien yang terdeteksi sedang dilayani.</p>
        @endif
    </div>

    <div class="panel form-panel">
        <h2>Pasien terakhir selesai dilayani</h2>

        @if($terakhirSelesai)
        <p class="lead">
            Pasien terakhir selesai dilayani adalah
            <strong>{{ data_get($terakhirSelesai, 'namapasien', '-') }}</strong>
            dengan no. antrean poli
            <strong>{{ data_get($terakhirSelesai, 'noantrian', '-') }}</strong>.
        </p>

        <div class="detail-grid">
            <div>
                <span class="muted">No. RM</span>
                <strong>{{ data_get($terakhirSelesai, 'nocm', '-') }}</strong>
            </div>

            <div>
                <span class="muted">Status</span>
                <strong>{{ data_get($terakhirSelesai, 'status', '-') }}</strong>
            </div>

            <div>
                <span class="muted">Label Status</span>
                <strong>{{ data_get($terakhirSelesai, 'label_statusperiksa', '-') }}</strong>
            </div>

            <div>
                <span class="muted">Waktu Closing</span>
                <strong>{{ data_get($terakhirSelesai, 'tglclosing', '-') }}</strong>
            </div>
        </div>
        @else
        <p class="lead">Belum ada pasien yang selesai dilayani.</p>
        @endif
    </div>

    <div class="panel form-panel">
        <h2>Pasien belum selesai dilayani</h2>

        @if($pasienBelumSelesai->isEmpty())
        <p class="lead">Tidak ada pasien yang masih menunggu pelayanan.</p>
        @else
        <div class="table-responsive">
            <table class="table-simple">
                <thead>
                    <tr>
                        <th>No Antrean</th>
                        <th>No RM</th>
                        <th>Nama Pasien</th>
                        <th>Status</th>
                        <th>Label Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pasienBelumSelesai as $pasien)
                    <tr>
                        <td>
                            <strong>{{ data_get($pasien, 'noantrian', '-') }}</strong>
                        </td>
                        <td>{{ data_get($pasien, 'nocm', '-') }}</td>
                        <td>{{ data_get($pasien, 'namapasien', '-') }}</td>
                        <td>{{ data_get($pasien, 'status', '-') }}</td>
                        <td>
                            <span class="pill is-warning">
                                {{ data_get($pasien, 'label_statusperiksa', '-') }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div> -->

    <!-- <div class="panel form-panel">
        <h2>Perbarui status</h2>
        <p class="lead">
            Status ini mengikuti data terbaru dari API antrean rawat jalan.
        </p>

        <form method="POST" action="{{ route('queue.check') }}">
            @csrf

            <input type="hidden" name="rm" value="{{ old('rm', data_get($antrean, 'nocm')) }}">
            <input type="hidden" name="tanggal_lahir" value="{{ old('tanggal_lahir') }}">

            <button class="btn full" type="submit">Perbarui Status</button>
        </form>
    </div> -->
    @endif

    <a class="btn secondary full" href="{{ route('queue.home') }}">Cek Kode Lain</a>
</section>
@endsection