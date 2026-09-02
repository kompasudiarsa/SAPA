@extends('layouts.app', ['title' => 'Riwayat Radiologi'])

@section('content')
<style>
    .rad-page,
    .rad-page * { box-sizing: border-box; }

    .rad-page {
        position: relative;
        left: 50%;
        width: 100vw;
        min-height: 100vh;
        margin-left: -50vw;
        padding: 32px clamp(16px, 4vw, 48px) 60px;
        overflow-x: hidden;
        color: #0f172a;
        background:
            radial-gradient(circle at top right, rgba(59,130,246,.10), transparent 34%),
            linear-gradient(180deg, #f8fbff 0%, #f8fafc 48%, #f1f5f9 100%);
    }

    .rad-container { width: min(1180px, 100%); margin: 0 auto; }
    .rad-back { display:inline-flex; margin-bottom:18px; color:#475569; font-size:13px; font-weight:800; text-decoration:none; }
    .rad-eyebrow { display:inline-flex; padding:7px 13px; border:1px solid #bfdbfe; border-radius:999px; background:#dbeafe; color:#1d4ed8; font-size:12px; font-weight:900; }
    .rad-title { margin:14px 0 8px; color:#0f172a; font-size:clamp(32px,5vw,48px); font-weight:950; line-height:1.05; letter-spacing:-.04em; }
    .rad-description { margin:0 0 24px; color:#64748b; font-size:14px; line-height:1.7; }

    .patient-card,.filter-card,.rad-card,.summary-box,.empty-card,.error-card {
        border:1px solid rgba(203,213,225,.85);
        background:rgba(255,255,255,.97);
        box-shadow:0 12px 32px rgba(15,23,42,.05);
    }

    .patient-card { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); margin-bottom:16px; overflow:hidden; border-radius:20px; }
    .patient-card > div { padding:18px 20px; }
    .patient-card > div + div { border-left:1px solid #e2e8f0; }
    .label { margin-bottom:5px; color:#94a3b8; font-size:10px; font-weight:900; text-transform:uppercase; letter-spacing:.04em; }
    .value { color:#0f172a; font-size:14px; font-weight:900; line-height:1.45; }

    .summary-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:12px; margin-bottom:16px; }
    .summary-box { padding:17px 18px; border-radius:18px; }
    .summary-number { margin-top:5px; color:#0f172a; font-size:28px; font-weight:950; }

    .filter-card { margin-bottom:18px; padding:18px; border-radius:20px; }
    .filter-grid { display:grid; grid-template-columns:minmax(240px,1.4fr) minmax(150px,.6fr) minmax(120px,.45fr) auto; gap:12px; align-items:end; }
    .form-group { display:grid; gap:6px; min-width:0; }
    .form-label { color:#334155; font-size:11px; font-weight:900; }
    .form-control { width:100%; height:44px; padding:0 13px; border:1px solid #cbd5e1; border-radius:12px; outline:none; background:#fff; color:#0f172a; font-size:12px; }
    .form-control:focus { border-color:#3b82f6; box-shadow:0 0 0 4px rgba(59,130,246,.10); }

    .btn-primary,.btn-detail,.btn-pacs,.page-btn { display:inline-flex; align-items:center; justify-content:center; border-radius:11px; font-size:11px; font-weight:900; text-decoration:none; white-space:nowrap; }
    .btn-primary { height:44px; padding:0 17px; border:0; background:#2563eb; color:#fff; cursor:pointer; }

    .rad-card { margin-bottom:14px; overflow:hidden; border-radius:20px; }
    .rad-card-header { display:flex; gap:14px; align-items:flex-start; justify-content:space-between; padding:18px 20px; border-bottom:1px solid #e2e8f0; background:linear-gradient(90deg,rgba(239,246,255,.85),rgba(255,255,255,0)); }
    .rad-exam { color:#0f172a; font-size:17px; font-weight:950; }
    .rad-number { margin-top:5px; color:#64748b; font-size:11px; font-weight:750; }
    .badge-row { display:flex; flex-wrap:wrap; gap:7px; justify-content:flex-end; }
    .badge { display:inline-flex; align-items:center; padding:6px 10px; border-radius:999px; font-size:10px; font-weight:900; }
    .badge-ready { background:#dcfce7; color:#166534; }
    .badge-wait { background:#fef3c7; color:#92400e; }
    .badge-critical { background:#fee2e2; color:#b91c1c; }

    .rad-card-body { padding:18px 20px; }
    .rad-info { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:16px 22px; }
    .conclusion { margin-top:16px; padding:14px 15px; border:1px solid #dbeafe; border-radius:14px; background:#eff6ff; }
    .conclusion-text { color:#334155; font-size:12px; font-weight:700; line-height:1.65; white-space:pre-line; }
    .rad-actions { display:flex; flex-wrap:wrap; gap:8px; margin-top:16px; }
    .btn-detail,.btn-pacs { min-height:38px; padding:0 14px; }
    .btn-detail { border:1px solid #bfdbfe; background:#eff6ff; color:#1d4ed8; }
    .btn-pacs { border:1px solid #cbd5e1; background:#fff; color:#475569; }

    .pagination-simple { display:flex; gap:10px; align-items:center; justify-content:space-between; margin-top:22px; padding:13px 14px; border:1px solid #e2e8f0; border-radius:16px; background:#fff; }
    .page-info { color:#64748b; font-size:11px; font-weight:800; }
    .page-actions { display:flex; gap:8px; }
    .page-btn { min-height:36px; padding:0 12px; border:1px solid #cbd5e1; background:#fff; color:#475569; }
    .page-btn.disabled { color:#cbd5e1; pointer-events:none; background:#f8fafc; }

    .empty-card,.error-card { padding:28px 20px; border-radius:18px; text-align:center; font-size:13px; font-weight:750; line-height:1.6; }
    .error-card { border-color:#fecaca; background:#fef2f2; color:#991b1b; }

    @media (max-width:820px) {
        .summary-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }
        .filter-grid { grid-template-columns:1fr 1fr; }
        .filter-grid .form-group:first-child { grid-column:1 / -1; }
        .rad-info { grid-template-columns:1fr 1fr; }
    }

    @media (max-width:640px) {
        .rad-page { padding:18px 14px 42px; }
        .patient-card,.filter-grid,.rad-info { grid-template-columns:1fr; }
        .patient-card > div + div { border-top:1px solid #e2e8f0; border-left:0; }
        .filter-grid .form-group:first-child { grid-column:auto; }
        .rad-card-header { flex-direction:column; }
        .badge-row { justify-content:flex-start; }
        .pagination-simple { display:grid; justify-content:stretch; }
        .page-actions { justify-content:space-between; }
    }
</style>

<div class="rad-page">
    <div class="rad-container">
        <a href="{{ route('layanan.menu') }}" class="rad-back">← Kembali ke menu</a>

        <div><span class="rad-eyebrow">Radiologi</span></div>
        <h1 class="rad-title">Riwayat pemeriksaan radiologi</h1>
        <p class="rad-description">Daftar pemeriksaan radiologi dan hasil expertise berdasarkan nomor rekam medis pasien.</p>

        <div class="patient-card">
            <div>
                <div class="label">Nama Pasien</div>
                <div class="value">{{ data_get($patient, 'name', '-') }}</div>
            </div>
            <div>
                <div class="label">Nomor Rekam Medis</div>
                <div class="value">{{ data_get($patient, 'medical_record', '-') }}</div>
            </div>
        </div>

        @if(! data_get($result, 'success', false))
            <div class="error-card">{{ data_get($result, 'message', 'API radiologi belum berhasil diakses.') }}</div>
        @else
            <div class="summary-grid">
                <div class="summary-box"><div class="label">Total Pemeriksaan</div><div class="summary-number">{{ $summary['total'] ?? 0 }}</div></div>
                <div class="summary-box"><div class="label">Sudah Expertise</div><div class="summary-number">{{ $summary['with_expertise'] ?? 0 }}</div></div>
                <div class="summary-box"><div class="label">Belum Expertise</div><div class="summary-number">{{ $summary['without_expertise'] ?? 0 }}</div></div>
                <div class="summary-box"><div class="label">Hasil Kritis</div><div class="summary-number">{{ $summary['critical'] ?? 0 }}</div></div>
            </div>

            <div class="filter-card">
                <form method="GET" action="{{ route('radiology.index') }}">
                    <input type="hidden" name="nrm" value="{{ $nrm }}">
                    <div class="filter-grid">
                        <div class="form-group">
                            <label class="form-label">Cari Pemeriksaan</label>
                            <input type="text" name="keyword" class="form-control" value="{{ $keyword }}" placeholder="No rontgen, pemeriksaan, radiolog...">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Expertise</label>
                            <select name="expertise" class="form-control">
                                <option value="ALL" {{ $expertiseFilter === 'ALL' ? 'selected' : '' }}>Semua</option>
                                <option value="ADA" {{ $expertiseFilter === 'ADA' ? 'selected' : '' }}>Sudah Ada</option>
                                <option value="BELUM" {{ $expertiseFilter === 'BELUM' ? 'selected' : '' }}>Belum Ada</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Per halaman</label>
                            <select name="per_page" class="form-control">
                                @foreach([10, 20, 50] as $size)
                                    <option value="{{ $size }}" {{ $perPage == $size ? 'selected' : '' }}>{{ $size }}</option>
                                @endforeach
                            </select>
                        </div>

                        <button type="submit" class="btn-primary">Terapkan</button>
                    </div>
                </form>
            </div>

            @forelse($radiologyItems as $item)
                <article class="rad-card">
                    <div class="rad-card-header">
                        <div>
                            <div class="rad-exam">{{ data_get($item, 'nama_pemeriksaan', '-') }}</div>
                            <div class="rad-number">
                                No. Rontgen: {{ data_get($item, 'no_rontgen', '-') }}
                                · Registrasi: {{ data_get($item, 'no_register', '-') }}
                            </div>
                        </div>

                        <div class="badge-row">
                            @if(data_get($item, 'is_critical'))
                                <span class="badge badge-critical">Kritis</span>
                            @endif

                            @if(data_get($item, 'has_expertise'))
                                <span class="badge badge-ready">Sudah Expertise</span>
                            @else
                                <span class="badge badge-wait">Belum Expertise</span>
                            @endif
                        </div>
                    </div>

                    <div class="rad-card-body">
                        <div class="rad-info">
                            <div>
                                <div class="label">Tanggal</div>
                                <div class="value">
                                    @if(data_get($item, 'display_date'))
                                        {{ data_get($item, 'display_date')->format('d-m-Y H:i') }}
                                    @else
                                        -
                                    @endif
                                </div>
                            </div>

                            <div>
                                <div class="label">Radiografer</div>
                                <div class="value">{{ data_get($item, 'nama_radiografer', '-') ?: '-' }}</div>
                            </div>

                            <div>
                                <div class="label">Dokter Radiolog</div>
                                <div class="value">{{ data_get($item, 'nama_radiolog', '-') ?: '-' }}</div>
                            </div>
                        </div>

                        <!-- @if(trim((string) data_get($item, 'expertise_text_conclusion', '')) !== '')
                            <div class="conclusion">
                                <div class="label">Kesan / Kesimpulan</div>
                                <div class="conclusion-text">{{ data_get($item, 'expertise_text_conclusion') }}</div>
                            </div>
                        @endif -->

                        <div class="rad-actions">
                            <a href="{{ route('radiology.detail', ['id' => data_get($item, 'id'), 'nrm' => $nrm]) }}" class="btn-detail">Lihat Detail</a>

                            <!-- @if(data_get($item, 'pacs_url'))
                                <a href="{{ data_get($item, 'pacs_url') }}" target="_blank" rel="noopener noreferrer" class="btn-pacs">Lihat Citra PACS</a>
                            @endif -->
                        </div>
                    </div>
                </article>
            @empty
                <div class="empty-card">Belum ada riwayat radiologi yang sesuai dengan filter.</div>
            @endforelse

            @if($radiologyItems->hasPages())
                <div class="pagination-simple">
                    <div class="page-info">
                        Menampilkan {{ $radiologyItems->firstItem() }} - {{ $radiologyItems->lastItem() }} dari {{ $radiologyItems->total() }} data
                    </div>

                    <div class="page-actions">
                        @if($radiologyItems->onFirstPage())
                            <span class="page-btn disabled">← Sebelumnya</span>
                        @else
                            <a href="{{ $radiologyItems->previousPageUrl() }}" class="page-btn">← Sebelumnya</a>
                        @endif

                        @if($radiologyItems->hasMorePages())
                            <a href="{{ $radiologyItems->nextPageUrl() }}" class="page-btn">Berikutnya →</a>
                        @else
                            <span class="page-btn disabled">Berikutnya →</span>
                        @endif
                    </div>
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
