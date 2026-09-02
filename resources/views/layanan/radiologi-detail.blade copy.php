@extends('layouts.app', ['title' => 'Detail Hasil Radiologi'])

@section('content')
<style>
    .rad-detail-page,.rad-detail-page * { box-sizing:border-box; }
    .rad-detail-page { width:min(980px,100%); margin:0 auto; padding:20px 0 42px; color:#0f172a; }
    .back-link { display:inline-flex; margin-bottom:18px; color:#475569; font-size:13px; font-weight:800; text-decoration:none; }
    .detail-card { overflow:hidden; border:1px solid #e2e8f0; border-radius:22px; background:#fff; box-shadow:0 14px 36px rgba(15,23,42,.06); }
    .detail-header { display:flex; gap:16px; align-items:flex-start; justify-content:space-between; padding:22px; border-bottom:1px solid #e2e8f0; background:#f8fafc; }
    .detail-title { margin:0; color:#0f172a; font-size:24px; font-weight:950; }
    .detail-subtitle { margin-top:7px; color:#64748b; font-size:12px; line-height:1.5; }
    .badge { display:inline-flex; padding:7px 11px; border-radius:999px; font-size:11px; font-weight:900; }
    .badge-ready { background:#dcfce7; color:#166534; }
    .badge-wait { background:#fef3c7; color:#92400e; }
    .badge-critical { background:#fee2e2; color:#b91c1c; }
    .detail-body { padding:22px; }
    .meta-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:18px 22px; margin-bottom:22px; }
    .label { margin-bottom:5px; color:#94a3b8; font-size:10px; font-weight:900; text-transform:uppercase; letter-spacing:.04em; }
    .value { color:#334155; font-size:13px; font-weight:800; line-height:1.55; }
    .expertise-section { margin-top:18px; padding:18px; border:1px solid #e2e8f0; border-radius:16px; }
    .expertise-title { margin-bottom:12px; color:#0f172a; font-size:14px; font-weight:950; }
    .expertise-text { color:#334155; font-size:13px; line-height:1.75; white-space:pre-line; }
    .conclusion { border-color:#bfdbfe; background:#eff6ff; }
    .actions { display:flex; flex-wrap:wrap; gap:9px; margin-top:20px; }
    .btn { display:inline-flex; min-height:40px; align-items:center; justify-content:center; padding:0 15px; border-radius:11px; font-size:12px; font-weight:900; text-decoration:none; }
    .btn-primary { border:1px solid #bfdbfe; background:#2563eb; color:#fff; }
    .btn-secondary { border:1px solid #cbd5e1; background:#fff; color:#475569; }
    @media (max-width:680px) { .detail-header { flex-direction:column; } .meta-grid { grid-template-columns:1fr; } }
</style>

<div class="rad-detail-page">
    <a href="{{ route('radiology.index', ['nrm' => $nrm]) }}" class="back-link">← Kembali ke riwayat radiologi</a>

    <div class="detail-card">
        <div class="detail-header">
            <div>
                <h1 class="detail-title">{{ data_get($item, 'nama_pemeriksaan', 'Hasil Radiologi') }}</h1>
                <div class="detail-subtitle">
                    No. Rontgen: {{ data_get($item, 'no_rontgen', '-') }}
                    · RM: {{ data_get($item, 'no_rm', '-') }}
                    · Registrasi: {{ data_get($item, 'no_register', '-') }}
                </div>
            </div>

            <div>
                @if(data_get($item, 'is_critical'))
                    <span class="badge badge-critical">Hasil Kritis</span>
                @elseif(data_get($item, 'has_expertise'))
                    <span class="badge badge-ready">Sudah Expertise</span>
                @else
                    <span class="badge badge-wait">Belum Expertise</span>
                @endif
            </div>
        </div>

        <div class="detail-body">
            <div class="meta-grid">
                <div><div class="label">Nama Pasien</div><div class="value">{{ data_get($item, 'nama_pasien', '-') }}</div></div>
                <div><div class="label">Radiografer</div><div class="value">{{ data_get($item, 'nama_radiografer', '-') ?: '-' }}</div></div>
                <div><div class="label">Dokter Radiolog</div><div class="value">{{ data_get($item, 'nama_radiolog', '-') ?: '-' }}</div></div>
                <div><div class="label">Mulai Radiografer</div><div class="value">{{ data_get($item, 'radiografer_datetime_start', '-') ?: '-' }}</div></div>
                <div><div class="label">Selesai Radiografer</div><div class="value">{{ data_get($item, 'radiografer_datetime_end', '-') ?: '-' }}</div></div>
                <div>
                    <div class="label">Selesai Expertise</div>
                    <div class="value">
                        @php $radiologEnd = trim((string) data_get($item, 'radiolog_datetime_end', '')); @endphp
                        @if($radiologEnd !== '' && strpos($radiologEnd, '1900-01-01') !== 0)
                            {{ $radiologEnd }}
                        @else
                            -
                        @endif
                    </div>
                </div>
            </div>

            @if(trim((string) data_get($item, 'expertise_text_finding', '')) !== '')
                <div class="expertise-section">
                    <div class="expertise-title">Hasil Pemeriksaan / Finding</div>
                    <div class="expertise-text">{{ data_get($item, 'expertise_text_finding') }}</div>
                </div>
            @endif

            @if(trim((string) data_get($item, 'expertise_text_conclusion', '')) !== '')
                <div class="expertise-section conclusion">
                    <div class="expertise-title">Kesan / Kesimpulan</div>
                    <div class="expertise-text">{{ data_get($item, 'expertise_text_conclusion') }}</div>
                </div>
            @endif

            @if(! data_get($item, 'has_expertise'))
                <div class="expertise-section">
                    <div class="expertise-text">Hasil expertise dokter radiologi belum tersedia.</div>
                </div>
            @endif

            <div class="actions">
                <!-- @if(data_get($item, 'pacs_url'))
                    <a href="{{ data_get($item, 'pacs_url') }}" target="_blank" rel="noopener noreferrer" class="btn btn-primary">Lihat Citra PACS</a>
                @endif -->

                <a href="{{ route('radiology.index', ['nrm' => $nrm]) }}" class="btn btn-secondary">Kembali</a>
            </div>
        </div>
    </div>
</div>
@endsection
