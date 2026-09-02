@extends('layouts.app', ['title' => 'Cek Reservasi | SAPA RSBM'])

@section('content')

@php
    $mainMenuUrl = Route::has('layanan.menu')
        ? route('layanan.menu')
        : url('/layanan/menu');

    $logoutRouteName = Route::has('layanan.logout')
        ? 'layanan.logout'
        : (Route::has('logout') ? 'logout' : null);
@endphp

<style>
    :root {
        --rsbm-blue: #26358f;
        --rsbm-blue-dark: #1d286d;
        --rsbm-blue-soft: #eef1ff;
        --rsbm-green: #19c83d;
        --rsbm-green-dark: #0f9f2e;
        --rsbm-green-soft: #eafbee;
        --rsbm-text: #182033;
        --rsbm-muted: #64748b;
        --rsbm-line: #e3e8ef;
        --rsbm-bg: #f7f9fc;
    }

    .reservation-page,
    .reservation-page * {
        box-sizing: border-box;
    }

    .reservation-page {
        position: relative;
        left: 50%;
        width: 100vw;
        min-height: 100vh;
        margin-left: -50vw;
        padding: 22px clamp(14px, 3vw, 38px) 48px;
        overflow-x: hidden;
        color: var(--rsbm-text);
        background:
            radial-gradient(circle at 92% 8%, rgba(38, 53, 143, .08), transparent 27%),
            radial-gradient(circle at 6% 92%, rgba(25, 200, 61, .07), transparent 25%),
            linear-gradient(180deg, #fff 0%, var(--rsbm-bg) 100%);
    }

    .reservation-page::before,
    .reservation-page::after {
        position: absolute;
        border-radius: 999px;
        content: "";
        pointer-events: none;
    }

    .reservation-page::before {
        top: -170px;
        right: -150px;
        width: 350px;
        height: 350px;
        border: 46px solid rgba(38, 53, 143, .022);
    }

    .reservation-page::after {
        bottom: -190px;
        left: -160px;
        width: 360px;
        height: 360px;
        border: 52px solid rgba(25, 200, 61, .022);
    }

    .reservation-container {
        position: relative;
        z-index: 1;
        width: min(1180px, 100%);
        margin: 0 auto;
    }

    /* =========================================================
       TOPBAR RSBM
       ========================================================= */

    .reservation-topbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 18px;
        padding: 11px 12px;
        border: 1px solid rgba(217, 224, 234, .95);
        border-radius: 18px;
        background: rgba(255, 255, 255, .96);
        box-shadow: 0 10px 28px rgba(28, 39, 90, .07);
        backdrop-filter: blur(10px);
    }

    .reservation-brand {
        display: flex;
        min-width: 0;
        align-items: center;
        gap: 10px;
    }

    .reservation-logo-shell {
        display: grid;
        width: 43px;
        height: 43px;
        flex: 0 0 43px;
        place-items: center;
        overflow: hidden;
        border: 1px solid rgba(38, 53, 143, .1);
        border-radius: 11px;
        background: #fff;
    }

    .reservation-logo {
        width: 37px;
        height: 37px;
        object-fit: contain;
    }

    .reservation-logo-fallback {
        display: none;
        width: 34px;
        height: 34px;
        place-items: center;
        border-radius: 9px;
        background: linear-gradient(145deg, var(--rsbm-blue), var(--rsbm-blue-dark));
        color: #fff;
        font-size: 17px;
        font-weight: 950;
    }

    .reservation-government {
        color: #818b9a;
        font-size: 8px;
        font-weight: 900;
        letter-spacing: .1em;
        text-transform: uppercase;
    }

    .reservation-hospital {
        margin-top: 1px;
        color: var(--rsbm-blue-dark);
        font-size: 14px;
        font-weight: 950;
        letter-spacing: -.01em;
    }

    .reservation-location {
        margin-top: 1px;
        color: #98a1af;
        font-size: 9px;
        font-weight: 700;
    }

    .reservation-top-actions,
    .reservation-bottom-actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 8px;
        flex-wrap: wrap;
    }

    .reservation-logout-form {
        margin: 0;
    }

    .reservation-nav-button {
        display: inline-flex;
        min-height: 38px;
        align-items: center;
        justify-content: center;
        gap: 7px;
        padding: 0 12px;
        border: 1px solid transparent;
        border-radius: 11px;
        font: inherit;
        font-size: 10.5px;
        font-weight: 900;
        line-height: 1;
        text-decoration: none;
        white-space: nowrap;
        cursor: pointer;
        transition:
            transform .18s ease,
            background .18s ease,
            border-color .18s ease;
    }

    .reservation-nav-button:hover {
        transform: translateY(-1px);
        text-decoration: none;
    }

    .reservation-nav-button.is-menu {
        border-color: #c7d2fe;
        background: var(--rsbm-blue-soft);
        color: var(--rsbm-blue);
    }

    .reservation-nav-button.is-menu:hover {
        border-color: #a5b4fc;
        background: #e5e9ff;
        color: var(--rsbm-blue-dark);
    }

    .reservation-nav-button.is-logout {
        border-color: #fecaca;
        background: #fff5f5;
        color: #b91c1c;
    }

    .reservation-nav-button.is-logout:hover {
        border-color: #fca5a5;
        background: #fee2e2;
        color: #991b1b;
    }

    /* =========================================================
       HEADER
       ========================================================= */

    .reservation-header {
        max-width: 780px;
        margin-bottom: 20px;
    }

    .reservation-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 6px 10px;
        border: 1px solid rgba(38, 53, 143, .09);
        border-radius: 999px;
        background: rgba(255,255,255,.85);
        color: var(--rsbm-blue-dark);
        font-size: 9px;
        font-weight: 900;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .reservation-eyebrow::before {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: var(--rsbm-green);
        box-shadow: 0 0 0 4px rgba(25, 200, 61, .11);
        content: "";
    }

    .reservation-title {
        margin: 10px 0 7px;
        color: var(--rsbm-text);
        font-size: clamp(29px, 4.3vw, 43px);
        font-weight: 950;
        line-height: 1.05;
        letter-spacing: -.04em;
    }

    .reservation-title span {
        color: var(--rsbm-blue);
    }

    .reservation-description {
        max-width: 720px;
        margin: 0;
        color: var(--rsbm-muted);
        font-size: 13px;
        line-height: 1.6;
    }

    /* =========================================================
       PATIENT CARD
       ========================================================= */

    .patient-card {
        display: grid;
        grid-template-columns: minmax(0, 1.4fr) repeat(3, minmax(120px, .72fr));
        margin-bottom: 16px;
        overflow: hidden;
        border: 1px solid rgba(217, 224, 234, .96);
        border-radius: 18px;
        background: rgba(255, 255, 255, .97);
        box-shadow: 0 12px 30px rgba(28, 39, 90, .055);
    }

    .patient-info-item {
        min-width: 0;
        padding: 15px 18px;
        background: rgba(255,255,255,.98);
    }

    .patient-info-item + .patient-info-item {
        border-left: 1px solid var(--rsbm-line);
    }

    .patient-info-label {
        display: block;
        margin-bottom: 5px;
        color: #98a1af;
        font-size: 9px;
        font-weight: 900;
        letter-spacing: .04em;
        text-transform: uppercase;
    }

    .patient-info-value {
        display: block;
        overflow: hidden;
        color: var(--rsbm-text);
        font-size: 13px;
        font-weight: 900;
        line-height: 1.45;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    /* =========================================================
       SUMMARY
       ========================================================= */

    .reservation-summary {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 12px;
    }

    .reservation-total {
        color: #7b8797;
        font-size: 11px;
        font-weight: 750;
    }

    .reservation-total strong {
        color: var(--rsbm-blue-dark);
        font-size: 13px;
        font-weight: 950;
    }

    /* =========================================================
       RESERVATION LIST
       ========================================================= */

    .reservation-list {
        display: grid;
        gap: 13px;
    }

    .reservation-card {
        position: relative;
        overflow: hidden;
        border: 1px solid rgba(217, 224, 234, .96);
        border-radius: 18px;
        background: rgba(255,255,255,.98);
        box-shadow: 0 12px 30px rgba(28, 39, 90, .055);
    }

    .reservation-card::before {
        display: block;
        height: 3px;
        background: linear-gradient(
            90deg,
            var(--rsbm-blue) 0 78%,
            var(--rsbm-green) 78% 100%
        );
        content: "";
    }

    .reservation-card-main {
        display: grid;
        grid-template-columns:
            minmax(180px, .7fr)
            minmax(0, 1.35fr)
            minmax(145px, .55fr);
        gap: 18px;
        align-items: center;
        padding: 16px 18px;
        border-bottom: 1px solid var(--rsbm-line);
        background:
            linear-gradient(
                90deg,
                rgba(238, 241, 255, .55),
                rgba(255, 255, 255, 0)
            );
    }

    .reservation-date {
        display: flex;
        align-items: center;
        gap: 11px;
    }

    .date-icon {
        display: grid;
        width: 42px;
        height: 42px;
        flex: 0 0 42px;
        place-items: center;
        border-radius: 11px;
        background: var(--rsbm-blue-soft);
        color: var(--rsbm-blue);
    }

    .date-content strong {
        display: block;
        margin-bottom: 3px;
        color: var(--rsbm-text);
        font-size: 13px;
        font-weight: 950;
    }

    .date-content span {
        color: #7b8797;
        font-size: 10px;
        font-weight: 750;
    }

    .reservation-service {
        min-width: 0;
    }

    .reservation-badges {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
        margin-bottom: 7px;
    }

    .reservation-badge {
        display: inline-flex;
        min-height: 22px;
        align-items: center;
        padding: 3px 8px;
        border-radius: 999px;
        font-size: 9px;
        font-weight: 900;
        line-height: 1;
    }

    .reservation-badge.is-status {
        background: var(--rsbm-green-soft);
        color: var(--rsbm-green-dark);
    }

    .reservation-badge.is-bpjs {
        background: var(--rsbm-blue-soft);
        color: var(--rsbm-blue);
    }

    .reservation-badge.is-type {
        background: #f1f5f9;
        color: #64748b;
    }

    .reservation-service h2 {
        margin: 0 0 5px;
        color: var(--rsbm-text);
        font-size: 16px;
        font-weight: 950;
        line-height: 1.35;
        letter-spacing: -.02em;
    }

    .reservation-doctor {
        display: flex;
        align-items: center;
        gap: 6px;
        color: #64748b;
        font-size: 11px;
        font-weight: 750;
    }

    .reservation-queue {
        text-align: right;
    }

    .queue-label {
        display: block;
        margin-bottom: 4px;
        color: #98a1af;
        font-size: 8.5px;
        font-weight: 900;
        letter-spacing: .04em;
        text-transform: uppercase;
    }

    .queue-number {
        color: var(--rsbm-blue);
        font-size: 29px;
        font-weight: 950;
        line-height: 1;
        letter-spacing: -.03em;
    }

    .reservation-details {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        background: #fbfcfe;
    }

    .detail-item {
        min-width: 0;
        padding: 12px 15px;
    }

    .detail-item + .detail-item {
        border-left: 1px solid var(--rsbm-line);
    }

    .detail-label {
        display: block;
        margin-bottom: 4px;
        color: #98a1af;
        font-size: 8px;
        font-weight: 900;
        letter-spacing: .04em;
        text-transform: uppercase;
    }

    .detail-value {
        display: block;
        overflow-wrap: anywhere;
        color: #4b5565;
        font-size: 10.5px;
        font-weight: 800;
        line-height: 1.45;
    }

    /* =========================================================
       EMPTY
       ========================================================= */

    .empty-state {
        padding: 42px 20px;
        border: 1px solid rgba(217, 224, 234, .96);
        border-radius: 18px;
        background: rgba(255,255,255,.97);
        text-align: center;
        box-shadow: 0 12px 30px rgba(28, 39, 90, .055);
    }

    .empty-icon {
        display: grid;
        width: 56px;
        height: 56px;
        margin: 0 auto 14px;
        place-items: center;
        border-radius: 16px;
        background: var(--rsbm-blue-soft);
        color: var(--rsbm-blue);
    }

    .empty-state h2 {
        margin: 0;
        color: var(--rsbm-text);
        font-size: 18px;
        font-weight: 950;
    }

    .empty-state p {
        max-width: 440px;
        margin: 7px auto 0;
        color: var(--rsbm-muted);
        font-size: 12px;
        line-height: 1.6;
    }

    /* =========================================================
       FOOTER NAV
       ========================================================= */

    .reservation-bottom-actions {
        justify-content: center;
        margin-top: 18px;
        padding-top: 16px;
        border-top: 1px solid rgba(227, 232, 239, .9);
    }

    /* =========================================================
       RESPONSIVE
       ========================================================= */

    @media (max-width: 900px) {
        .patient-card {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .patient-info-item:nth-child(odd) {
            border-left: 0;
        }

        .patient-info-item:nth-child(n + 3) {
            border-top: 1px solid var(--rsbm-line);
        }

        .reservation-card-main {
            grid-template-columns: 1fr 1fr;
        }

        .reservation-queue {
            grid-column: 1 / -1;
            text-align: left;
        }

        .reservation-details {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .detail-item:nth-child(odd) {
            border-left: 0;
        }

        .detail-item:nth-child(n + 3) {
            border-top: 1px solid var(--rsbm-line);
        }
    }

    @media (max-width: 640px) {
        .reservation-page {
            padding: 10px 10px 38px;
        }

        .reservation-topbar {
            align-items: stretch;
            flex-direction: column;
            padding: 10px;
        }

        .reservation-top-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            width: 100%;
        }

        .reservation-top-actions .reservation-nav-button,
        .reservation-top-actions .reservation-logout-form,
        .reservation-top-actions .reservation-logout-form .reservation-nav-button {
            width: 100%;
        }

        .reservation-title {
            font-size: 30px;
        }

        .reservation-description {
            font-size: 12px;
        }

        .patient-card {
            grid-template-columns: 1fr;
        }

        .patient-info-item,
        .patient-info-item:nth-child(odd) {
            border-left: 0;
        }

        .patient-info-item + .patient-info-item {
            border-top: 1px solid var(--rsbm-line);
        }

        .reservation-card-main {
            grid-template-columns: 1fr;
            gap: 14px;
            padding: 15px;
        }

        .reservation-queue {
            grid-column: auto;
        }

        .queue-number {
            font-size: 27px;
        }

        .reservation-details {
            grid-template-columns: 1fr;
        }

        .detail-item,
        .detail-item:nth-child(odd) {
            border-left: 0;
        }

        .detail-item + .detail-item {
            border-top: 1px solid var(--rsbm-line);
        }

        .reservation-bottom-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
        }

        .reservation-bottom-actions .reservation-nav-button,
        .reservation-bottom-actions .reservation-logout-form,
        .reservation-bottom-actions .reservation-logout-form .reservation-nav-button {
            width: 100%;
        }
    }
</style>

<section class="reservation-page">
    <div class="reservation-container">

        <div class="reservation-topbar">
            <div class="reservation-brand">
                <div class="reservation-logo-shell">
                    <img
                        class="reservation-logo"
                        src="{{ asset('images/logo-rsbm.png') }}"
                        alt="Logo RSUD Bali Mandara"
                        onerror="this.style.display='none'; this.nextElementSibling.style.display='grid';"
                    >
                    <span
                        class="reservation-logo-fallback"
                        aria-hidden="true"
                    >
                        +
                    </span>
                </div>

                <div>
                    <div class="reservation-government">
                        Pemerintah Provinsi Bali
                    </div>
                    <div class="reservation-hospital">
                        RSUD Bali Mandara
                    </div>
                    <div class="reservation-location">
                        Sanur · Denpasar · Bali
                    </div>
                </div>
            </div>

            <div class="reservation-top-actions">
                <a
                    href="{{ $mainMenuUrl }}"
                    class="reservation-nav-button is-menu"
                >
                    <svg
                        width="14"
                        height="14"
                        viewBox="0 0 24 24"
                        fill="none"
                        aria-hidden="true"
                    >
                        <path
                            d="M3 11L12 4L21 11"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                        <path
                            d="M5 10V20H19V10M9 20V14H15V20"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linejoin="round"
                        />
                    </svg>
                    <span>Menu Utama</span>
                </a>

                @if($logoutRouteName)
                    <form
                        method="POST"
                        action="{{ route($logoutRouteName) }}"
                        class="reservation-logout-form"
                    >
                        @csrf

                        <button
                            type="submit"
                            class="reservation-nav-button is-logout"
                        >
                            <svg
                                width="14"
                                height="14"
                                viewBox="0 0 24 24"
                                fill="none"
                                aria-hidden="true"
                            >
                                <path
                                    d="M10 5H6C4.9 5 4 5.9 4 7V17C4 18.1 4.9 19 6 19H10"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                />
                                <path
                                    d="M14 8L18 12L14 16M18 12H9"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                            </svg>
                            <span>Logout</span>
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <header class="reservation-header">
            <span class="reservation-eyebrow">
                SAPA RSBM · Reservasi
            </span>

           
        </header>

        <div class="patient-card">
            <div class="patient-info-item">
                <span class="patient-info-label">
                    Nama Pasien
                </span>
                <span class="patient-info-value">
                    {{ $patientInfo['nama'] ?: '-' }}
                </span>
            </div>

            <div class="patient-info-item">
                <span class="patient-info-label">
                    Nomor RM
                </span>
                <span class="patient-info-value">
                    {{ $patientInfo['medical_record'] ?: '-' }}
                </span>
            </div>

            <div class="patient-info-item">
                <span class="patient-info-label">
                    Nomor BPJS
                </span>
                <span class="patient-info-value">
                    {{ $patientInfo['no_bpjs'] ?: '-' }}
                </span>
            </div>

            <div class="patient-info-item">
                <span class="patient-info-label">
                    Jenis Kelamin
                </span>
                <span class="patient-info-value">
                    {{ $patientInfo['jenis_kelamin'] ?: '-' }}
                </span>
            </div>
        </div>

        <div class="reservation-summary">
            <div class="reservation-total">
                Ditemukan
                <strong>{{ number_format($total) }}</strong>
                reservasi
            </div>
        </div>

        @if($reservations->isEmpty())
            <div class="empty-state">
                <div class="empty-icon">
                    <svg
                        width="28"
                        height="28"
                        viewBox="0 0 24 24"
                        fill="none"
                        aria-hidden="true"
                    >
                        <rect
                            x="3"
                            y="5"
                            width="18"
                            height="16"
                            rx="3"
                            stroke="currentColor"
                            stroke-width="2"
                        />
                        <path
                            d="M8 3V7M16 3V7M3 10H21"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                        />
                    </svg>
                </div>

                <h2>Belum ada reservasi</h2>

                <p>
                    Tidak ditemukan data reservasi pasien
                    pada sistem e-Reservasi RSUD Bali Mandara.
                </p>
            </div>
        @else
            <div class="reservation-list">
                @foreach($reservations as $reservation)
                    @php
                        $tanggalReservasi = data_get(
                            $reservation,
                            'tanggalreservasi'
                        );

                        $tanggalDisplay = '-';

                        if ($tanggalReservasi) {
                            try {
                                $tanggalDisplay =
                                    \Carbon\Carbon::parse(
                                        $tanggalReservasi
                                    )->format('d-m-Y');
                            } catch (\Throwable $e) {
                                $tanggalDisplay =
                                    $tanggalReservasi;
                            }
                        }

                        $noAntrian = data_get(
                            $reservation,
                            'noantrianpoli'
                        );

                        $status = data_get(
                            $reservation,
                            'status',
                            'Reservasi'
                        );
                    @endphp

                    <article class="reservation-card">

                        <div class="reservation-card-main">

                            <div class="reservation-date">
                                <div class="date-icon">
                                    <svg
                                        width="21"
                                        height="21"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        aria-hidden="true"
                                    >
                                        <rect
                                            x="3"
                                            y="5"
                                            width="18"
                                            height="16"
                                            rx="3"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        />
                                        <path
                                            d="M8 3V7M16 3V7M3 10H21"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                        />
                                    </svg>
                                </div>

                                <div class="date-content">
                                    <strong>
                                        {{ $tanggalDisplay }}
                                    </strong>
                                    <span>
                                        {{ data_get(
                                            $reservation,
                                            'jamreservasi',
                                            '-'
                                        ) }}
                                    </span>
                                </div>
                            </div>

                            <div class="reservation-service">
                                <div class="reservation-badges">

                                    <span class="reservation-badge is-status">
                                        {{ $status }}
                                    </span>

                                    @if(data_get($reservation, 'kelompokpasien'))
                                        <span class="reservation-badge is-bpjs">
                                            {{ data_get(
                                                $reservation,
                                                'kelompokpasien'
                                            ) }}
                                        </span>
                                    @endif

                                    @if(data_get($reservation, 'type'))
                                        <span class="reservation-badge is-type">
                                            Pasien
                                            {{ data_get(
                                                $reservation,
                                                'type'
                                            ) }}
                                        </span>
                                    @endif
                                </div>

                                <h2>
                                    {{ data_get(
                                        $reservation,
                                        'namaruangan',
                                        '-'
                                    ) }}
                                </h2>

                                <div class="reservation-doctor">
                                    <svg
                                        width="14"
                                        height="14"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        aria-hidden="true"
                                    >
                                        <circle
                                            cx="12"
                                            cy="8"
                                            r="4"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        />
                                        <path
                                            d="M5 21C5.8 16.9 8.2 15 12 15C15.8 15 18.2 16.9 19 21"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                        />
                                    </svg>

                                    {{ data_get(
                                        $reservation,
                                        'dokter',
                                        '-'
                                    ) }}
                                </div>
                            </div>

                            <div class="reservation-queue">
                                <span class="queue-label">
                                    No. Antrean Poli
                                </span>

                                <div class="queue-number">
                                    {{ $noAntrian !== null
                                        ? $noAntrian
                                        : '-' }}
                                </div>
                            </div>

                        </div>

                        <div class="reservation-details">

                            <div class="detail-item">
                                <span class="detail-label">
                                    Kode Reservasi
                                </span>
                                <span class="detail-value">
                                    {{ data_get(
                                        $reservation,
                                        'noreservasi',
                                        '-'
                                    ) }}
                                </span>
                            </div>

                            <div class="detail-item">
                                <span class="detail-label">
                                    No. Surat Kontrol
                                </span>
                                <span class="detail-value">
                                    {{ data_get(
                                        $reservation,
                                        'nosuratkontrol',
                                        '-'
                                    ) }}
                                </span>
                            </div>

                            <div class="detail-item">
                                <span class="detail-label">
                                    No. Rujukan
                                </span>
                                <span class="detail-value">
                                    {{ data_get(
                                        $reservation,
                                        'norujukan',
                                        '-'
                                    ) }}
                                </span>
                            </div>

                            <div class="detail-item">
                                <span class="detail-label">
                                    Loket
                                </span>
                                <span class="detail-value">
                                    {{ data_get(
                                        $reservation,
                                        'loketkiosk',
                                        '-'
                                    ) }}
                                </span>
                            </div>

                        </div>

                    </article>
                @endforeach
            </div>
        @endif

        <div class="reservation-bottom-actions">
            <a
                href="{{ $mainMenuUrl }}"
                class="reservation-nav-button is-menu"
            >
                <svg
                    width="14"
                    height="14"
                    viewBox="0 0 24 24"
                    fill="none"
                    aria-hidden="true"
                >
                    <path
                        d="M3 11L12 4L21 11"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                    <path
                        d="M5 10V20H19V10M9 20V14H15V20"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linejoin="round"
                    />
                </svg>
                <span>Kembali ke Menu Utama</span>
            </a>

            @if($logoutRouteName)
                <form
                    method="POST"
                    action="{{ route($logoutRouteName) }}"
                    class="reservation-logout-form"
                >
                    @csrf

                    <button
                        type="submit"
                        class="reservation-nav-button is-logout"
                    >
                        <svg
                            width="14"
                            height="14"
                            viewBox="0 0 24 24"
                            fill="none"
                            aria-hidden="true"
                        >
                            <path
                                d="M10 5H6C4.9 5 4 5.9 4 7V17C4 18.1 4.9 19 6 19H10"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                            />
                            <path
                                d="M14 8L18 12L14 16M18 12H9"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                        <span>Logout</span>
                    </button>
                </form>
            @endif
        </div>

    </div>
</section>

@endsection