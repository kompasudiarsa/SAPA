@extends('layouts.app', ['title' => 'Hasil Cek Waktu Tunggu'])

@section('content')
    @php
        $antrean = data_get($result, 'response', []);
        $found = (bool) data_get($result, 'found', false);

        $statusPasien = (string) data_get($antrean, 'status_pasien', '-');
        $statusLower = \Illuminate\Support\Str::lower($statusPasien);

        /*
         * Prioritaskan class status yang dikirim oleh API.
         * API dapat mengirim:
         * - class_statusperiksa: "is-warning tag"
         * - status_class: "is-warning"
         *
         * Hanya class status yang diizinkan yang digunakan pada kartu.
         */
        $statusClassApi = trim((string) data_get(
            $antrean,
            'class_statusperiksa',
            data_get($antrean, 'status_class', '')
        ));

        $allowedStatusClasses = [
            'is-info',
            'is-warning',
            'is-primary',
            'is-success',
            'is-danger',
        ];

        $statusClass = collect(
            preg_split('/\\s+/', $statusClassApi) ?: []
        )->first(function ($class) use ($allowedStatusClasses) {
            return in_array($class, $allowedStatusClasses, true);
        });

        /*
         * Fallback hanya digunakan jika API belum mengirim status class.
         */
        if (!$statusClass) {
            $statusClass = 'is-info';

            if (\Illuminate\Support\Str::contains(
                $statusLower,
                ['menunggu', 'belum']
            )) {
                $statusClass = 'is-warning';
            } elseif (\Illuminate\Support\Str::contains(
                $statusLower,
                ['sedang', 'dilayani', 'diperiksa']
            )) {
                $statusClass = 'is-primary';
            } elseif (\Illuminate\Support\Str::contains(
                $statusLower,
                ['selesai', 'closing']
            )) {
                $statusClass = 'is-success';
            }
        }

        /*
         * Semua nilai antrean berada di dalam response API.
         * Jangan mengambil langsung dari $result karena nilainya akan selalu 0/null.
         */
        /*
         * Total pasien reservasi = seluruh pasien yang terdaftar/reservasi.
         * Total pasien check-in = seluruh pasien yang sudah teregistrasi.
         *
         * Key baru diprioritaskan. Fallback key lama dipertahankan agar
         * tampilan tetap berjalan selama endpoint refresh belum diperbarui.
         */
        $totalPasienReservasi = (int) data_get(
            $antrean,
            'total_pasien_reservasi',
            data_get(
                $antrean,
                'total_reservasi',
                data_get(
                    $antrean,
                    'rincian_antrean.total_reservasi',
                    data_get(
                        $antrean,
                        'rincian_antrean_di_depan.total_reservasi',
                        0
                    )
                )
            )
        );

        $totalPasienCheckin = (int) data_get(
            $antrean,
            'total_pasien_checkin',
            data_get(
                $antrean,
                'total_teregistrasi',
                data_get(
                    $antrean,
                    'rincian_antrean.total_teregistrasi',
                    data_get(
                        $antrean,
                        'rincian_antrean_di_depan.total_teregistrasi',
                        0
                    )
                )
            )
        );

        $asalRegistrasi = data_get($antrean, 'asal_registrasi');
        $statusRegistrasi = data_get($antrean, 'status_registrasi');

        $jenisKelamin = trim((string) data_get($antrean, 'jeniskelamin', ''));
        $jenisKelaminLower = \Illuminate\Support\Str::lower($jenisKelamin);

        $isPerempuan = in_array($jenisKelaminLower, [
            'perempuan',
            'wanita',
            'female',
            'p',
        ], true);

        $isLakiLaki = in_array($jenisKelaminLower, [
            'laki-laki',
            'laki laki',
            'pria',
            'male',
            'l',
        ], true);


        /* Navigasi SAPA RSBM */
        $mainMenuUrl = Route::has('layanan.menu')
            ? route('layanan.menu')
            : url('/layanan/menu');

        $logoutRouteName = Route::has('layanan.logout')
            ? 'layanan.logout'
            : (Route::has('logout') ? 'logout' : null);
    @endphp

    <style>
        .queue-result-page {
            position: relative;
            min-height: calc(100vh - 120px);
            overflow: hidden;
            padding: clamp(18px, 4vw, 46px) 0;
        }

        .queue-result-page::before,
        .queue-result-page::after {
            position: absolute;
            z-index: 0;
            border-radius: 999px;
            content: "";
            pointer-events: none;
        }

        .queue-result-page::before {
            top: -150px;
            right: -120px;
            width: 330px;
            height: 330px;
            background: rgba(249, 115, 22, 0.08);
        }

        .queue-result-page::after {
            bottom: -180px;
            left: -150px;
            width: 360px;
            height: 360px;
            background: rgba(251, 146, 60, 0.06);
        }

        .queue-result {
            position: relative;
            z-index: 1;
            width: min(100%, 920px);
            margin-inline: auto;
        }

        .queue-result-stack {
            display: grid;
            gap: 16px;
        }

        .queue-ticket,
        .queue-metric,
        .queue-empty,
        .queue-footer-card {
            border: 1px solid rgba(226, 232, 240, 0.85);
            background: rgba(255, 255, 255, 0.96);
            box-shadow: none !important;
        }

        .queue-ticket {
            position: relative;
            overflow: hidden;
            padding: clamp(22px, 4vw, 38px);
            border-radius: clamp(24px, 4vw, 34px);
        }

        .queue-ticket::before {
            position: absolute;
            top: 0;
            right: 42px;
            left: 42px;
            height: 4px;
            border-radius: 0 0 999px 999px;
            background: linear-gradient(90deg, #fb923c, #ea580c);
            content: "";
        }

        .queue-ticket::after {
            position: absolute;
            top: -95px;
            right: -70px;
            width: 220px;
            height: 220px;
            border-radius: 999px;
            background: rgba(249, 115, 22, 0.07);
            content: "";
            pointer-events: none;
        }

        .queue-ticket-content {
            position: relative;
            z-index: 1;
        }

        .queue-topbar {
            display: flex;
            gap: 18px;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: clamp(22px, 4vw, 32px);
        }

        .queue-heading {
            min-width: 0;
        }

        .queue-heading .eyebrow {
            margin-bottom: 7px;
        }

        .queue-poli-name {
            margin: 0;
            color: var(--text, #0f172a);
            font-size: clamp(21px, 3vw, 30px);
            font-weight: 900;
            line-height: 1.15;
            letter-spacing: -0.03em;
            overflow-wrap: anywhere;
        }

        .refresh-state {
            display: inline-flex;
            flex: 0 0 auto;
            gap: 7px;
            align-items: center;
            padding: 8px 11px;
            border-radius: 999px;
            background: #f8fafc;
            color: #64748b;
            font-size: 11px;
            font-weight: 800;
            white-space: nowrap;
        }

        .refresh-state-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #22c55e;
        }

        .refresh-state.is-loading .refresh-state-dot {
            background: #f97316;
            animation: queue-pulse 1s infinite;
        }

        .refresh-state.is-error .refresh-state-dot {
            background: #ef4444;
        }

        @keyframes queue-pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: .45; transform: scale(.75); }
        }

        .queue-info-grid {
            display: grid;
            grid-template-columns: 1.35fr .75fr .75fr;
            gap: 10px;
            align-items: stretch;
        }

        .queue-info-card {
            display: flex;
            min-width: 0;
            min-height: 112px;
            flex-direction: column;
            justify-content: space-between;
            padding: 16px 17px;
            border: 1px solid rgba(217, 224, 234, .92);
            border-radius: 17px;
            background: #f8fafc;
        }

        .queue-info-card.is-status {
            color: #0f172a;
        }

        .queue-info-card.is-status.is-warning {
            background: #fff7ed;
        }

        .queue-info-card.is-status.is-primary {
            background: #eff6ff;
        }

        .queue-info-card.is-status.is-success {
            background: #f0fdf4;
        }

        .queue-info-card.is-status.is-info {
            border-color: #bae6fd;
            background: #f0f9ff;
        }

        .queue-info-card.is-status.is-danger {
            border-color: #fecaca;
            background: #fef2f2;
        }

        .queue-info-label-row {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .queue-info-icon {
            display: grid;
            width: 30px;
            height: 30px;
            flex: 0 0 auto;
            place-items: center;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.8);
            color: #ea580c;
        }

        .is-primary .queue-info-icon {
            color: #2563eb;
        }

        .is-success .queue-info-icon {
            color: #16a34a;
        }

        .is-info .queue-info-icon {
            color: #0284c7;
        }

        .is-danger .queue-info-icon {
            color: #dc2626;
        }

        .queue-info-label {
            color: #64748b;
            font-size: 11px;
            font-weight: 850;
            letter-spacing: 0.08em;
            line-height: 1.2;
            text-transform: uppercase;
        }

        .queue-info-value {
            color: var(--rsbm-text);
            font-size: clamp(27px, 4vw, 40px);
            font-weight: 950;
            line-height: 1;
            letter-spacing: -.04em;
            overflow-wrap: anywhere;
        }

        .queue-info-value.is-text {
            font-size: clamp(18px, 2.7vw, 25px);
            line-height: 1.18;
            letter-spacing: -.02em;
        }

        .queue-info-value.is-loket {
            font-size: clamp(27px, 4vw, 38px);
        }

        .queue-patient {
            display: flex;
            gap: 20px;
            align-items: center;
            justify-content: space-between;
            margin-top: clamp(22px, 4vw, 32px);
            padding-top: clamp(20px, 3vw, 26px);
            border-top: 1px solid rgba(15, 23, 42, 0.08);
        }

        .queue-patient-main {
            min-width: 0;
        }

        .patient-name-row {
            display: flex;
            min-width: 0;
            gap: 10px;
            align-items: center;
            margin-bottom: 10px;
        }

        .queue-patient h1 {
            min-width: 0;
            max-width: 100%;
            margin: 0;
            color: var(--text, #0f172a);
            font-size: clamp(22px, 4vw, 34px);
            font-weight: 900;
            line-height: 1.13;
            letter-spacing: -0.03em;
            overflow-wrap: anywhere;
        }

        .patient-gender-symbol {
            display: inline-grid;
            width: 31px;
            height: 31px;
            flex: 0 0 auto;
            place-items: center;
            border-radius: 999px;
            background: #eff6ff;
            color: #2563eb;
            font-size: 20px;
            font-weight: 900;
            line-height: 1;
        }

        .patient-gender-symbol.is-female {
            background: #fdf2f8;
            color: #db2777;
        }

        .patient-gender-symbol.is-neutral {
            background: #f8fafc;
            color: #64748b;
        }

        .patient-detail-row {
            display: flex;
            min-width: 0;
            gap: 8px;
            align-items: center;
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
            line-height: 1.5;
        }

        .patient-detail-row strong {
            color: #334155;
        }

        .patient-detail-separator {
            flex: 0 0 auto;
            color: #cbd5e1;
        }

        .patient-doctor {
            min-width: 0;
            overflow-wrap: anywhere;
        }

        .registration-badge {
            display: inline-flex;
            flex: 0 0 auto;
            align-items: center;
            padding: 9px 12px;
            border-radius: 999px;
            background: #fff7ed;
            color: #c2410c;
            font-size: 12px;
            font-weight: 850;
            text-align: center;
        }

        .queue-overview {
            display: grid;
            grid-template-columns: minmax(210px, .8fr) repeat(2, minmax(0, 1fr));
            gap: 10px;
            align-items: stretch;
            padding: 12px;
            border: 1px solid rgba(217, 224, 234, .95);
            border-radius: 19px;
            background: rgba(255, 255, 255, .94);
            box-shadow: 0 10px 26px rgba(28, 39, 90, .045);
        }

        .queue-section-heading {
            display: flex;
            min-width: 0;
            flex-direction: column;
            justify-content: center;
            padding: 14px 13px;
            border-radius: 15px;
            background: linear-gradient(
                145deg,
                var(--rsbm-blue-soft),
                #f8faff
            );
        }

        .queue-section-heading h2 {
            margin: 0;
            color: var(--rsbm-blue-dark);
            font-size: clamp(18px, 2.7vw, 23px);
            font-weight: 950;
            line-height: 1.18;
            letter-spacing: -.025em;
        }

        .queue-section-heading p {
            margin: 6px 0 0;
            color: #748094;
            font-size: 10.5px;
            font-weight: 700;
            line-height: 1.5;
        }

        .queue-metric {
            position: relative;
            display: flex;
            min-width: 0;
            min-height: 118px;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
            padding: 15px 16px;
            border: 1px solid rgba(217, 224, 234, .92);
            border-radius: 15px;
            background: #fff;
            box-shadow: none;
        }

        .queue-metric::after {
            position: absolute;
            right: -28px;
            bottom: -34px;
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: rgba(249, 115, 22, 0.06);
            content: "";
        }

        .queue-metric-icon {
            display: grid;
            width: 39px;
            height: 39px;
            place-items: center;
            border-radius: 13px;
            background: #fff7ed;
            color: #ea580c;
        }

        .queue-metric.is-registered .queue-metric-icon {
            background: #eff6ff;
            color: #2563eb;
        }

        .queue-metric-bottom {
            position: relative;
            z-index: 1;
            display: flex;
            gap: 10px;
            align-items: flex-end;
            justify-content: space-between;
            margin-top: 13px;
        }

        .queue-metric-copy {
            min-width: 0;
        }

        .queue-metric-copy strong {
            display: block;
            margin-bottom: 4px;
            color: #344052;
            font-size: 12px;
            font-weight: 900;
            line-height: 1.3;
        }

        .queue-metric-copy span {
            display: block;
            color: #98a1af;
            font-size: 9.5px;
            line-height: 1.4;
        }

        .queue-metric-value {
            flex: 0 0 auto;
            color: var(--rsbm-blue);
            font-size: clamp(34px, 5vw, 46px);
            font-weight: 950;
            line-height: .9;
            letter-spacing: -.05em;
        }

        .queue-metric.is-registered .queue-metric-value {
            color: var(--rsbm-green-dark);
        }

        .queue-footer-card {
            display: flex;
            gap: 14px;
            align-items: center;
            justify-content: space-between;
            padding: 14px;
            border-radius: 22px;
        }

        .queue-footer-note {
            display: flex;
            gap: 9px;
            align-items: center;
            padding-left: 5px;
            color: #64748b;
            font-size: 12px;
            line-height: 1.45;
        }

        .queue-footer-note svg {
            flex: 0 0 auto;
            color: #f97316;
        }

        .queue-action {
            flex: 0 0 auto;
            min-width: 180px;
            margin: 0;
        }

        .queue-empty {
            padding: clamp(28px, 5vw, 44px);
            border-radius: clamp(24px, 4vw, 32px);
            text-align: center;
        }

        .queue-empty-icon {
            display: grid;
            width: 66px;
            height: 66px;
            margin: 0 auto 18px;
            place-items: center;
            border-radius: 22px;
            background: #fff7ed;
            color: #ea580c;
        }

        .queue-empty h1 {
            margin: 8px 0 10px;
            color: var(--text, #0f172a);
            font-size: clamp(27px, 5vw, 40px);
            font-weight: 900;
            line-height: 1.14;
            letter-spacing: -0.035em;
        }

        .queue-empty .lead {
            max-width: 560px;
            margin-inline: auto;
        }

        @media (max-width: 780px) {
            .queue-info-grid {
                grid-template-columns: 1fr 1fr;
            }

            .queue-info-card.is-status {
                grid-column: 1 / -1;
            }

            .queue-overview {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .queue-section-heading {
                grid-column: 1 / -1;
            }
        }

        @media (max-width: 560px) {
            .queue-result-page {
                padding-top: 8px;
            }

            .queue-ticket,
            .queue-empty {
                padding: 19px;
                border-radius: 23px;
            }

            .queue-topbar {
                gap: 12px;
            }

            .refresh-state {
                padding: 7px 9px;
            }

            .refresh-state span:last-child {
                display: none;
            }

            .queue-info-grid {
                grid-template-columns: 1fr;
            }

            .queue-info-card.is-status {
                grid-column: auto;
            }

            .queue-info-card {
                min-height: 98px;
                padding: 14px 15px;
                border-radius: 15px;
            }


            .queue-overview {
                grid-template-columns: 1fr;
                padding: 9px;
            }

            .queue-section-heading {
                grid-column: auto;
                padding: 13px;
            }

            .queue-patient {
                align-items: flex-start;
                flex-direction: column;
            }

            .patient-detail-row {
                flex-wrap: wrap;
            }

            .registration-badge {
                align-self: flex-start;
            }

            .queue-footer-card {
                align-items: stretch;
                flex-direction: column;
            }

            .queue-footer-note {
                padding: 4px 4px 0;
            }

            .queue-action {
                width: 100%;
            }
        }


        /* =========================================================
           SAPA RSBM - IDENTITAS VISUAL RSUD BALI MANDARA
           ========================================================= */
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

        .queue-result-page,
        .queue-result-page * {
            box-sizing: border-box;
        }

        .queue-result-page {
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

        .queue-result-page::before {
            top: -170px;
            right: -150px;
            width: 350px;
            height: 350px;
            border: 46px solid rgba(38, 53, 143, .022);
            background: transparent;
        }

        .queue-result-page::after {
            bottom: -190px;
            left: -160px;
            width: 360px;
            height: 360px;
            border: 52px solid rgba(25, 200, 61, .022);
            background: transparent;
        }

        .queue-result {
            width: min(100%, 1040px);
        }

        .queue-brandbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 16px;
            padding: 11px 12px;
            border: 1px solid rgba(217, 224, 234, .95);
            border-radius: 18px;
            background: rgba(255, 255, 255, .96);
            box-shadow: 0 10px 28px rgba(28, 39, 90, .07);
            backdrop-filter: blur(10px);
        }

        .queue-brand {
            display: flex;
            min-width: 0;
            align-items: center;
            gap: 10px;
        }

        .queue-brand-logo-shell {
            display: grid;
            width: 43px;
            height: 43px;
            flex: 0 0 43px;
            place-items: center;
            overflow: hidden;
            border: 1px solid rgba(38, 53, 143, .10);
            border-radius: 11px;
            background: #fff;
        }

        .queue-brand-logo {
            width: 37px;
            height: 37px;
            object-fit: contain;
        }

        .queue-brand-logo-fallback {
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

        .queue-brand-government {
            color: #818b9a;
            font-size: 8px;
            font-weight: 900;
            letter-spacing: .10em;
            text-transform: uppercase;
        }

        .queue-brand-hospital {
            margin-top: 1px;
            color: var(--rsbm-blue-dark);
            font-size: 14px;
            font-weight: 950;
            letter-spacing: -.01em;
        }

        .queue-brand-location {
            margin-top: 1px;
            color: #98a1af;
            font-size: 9px;
            font-weight: 700;
        }

        .queue-brand-actions,
        .queue-footer-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
            flex-wrap: wrap;
        }

        .queue-logout-form {
            margin: 0;
        }

        .queue-nav-button {
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
            transition: transform .18s ease, border-color .18s ease, background .18s ease;
        }

        .queue-nav-button:hover {
            transform: translateY(-1px);
            text-decoration: none;
        }

        .queue-nav-button.is-menu {
            border-color: #c7d2fe;
            background: var(--rsbm-blue-soft);
            color: var(--rsbm-blue);
        }

        .queue-nav-button.is-menu:hover {
            border-color: #a5b4fc;
            background: #e5e9ff;
            color: var(--rsbm-blue-dark);
        }

        .queue-nav-button.is-logout {
            border-color: #fecaca;
            background: #fff5f5;
            color: #b91c1c;
        }

        .queue-nav-button.is-logout:hover {
            border-color: #fca5a5;
            background: #fee2e2;
            color: #991b1b;
        }

        .queue-result-page .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            margin-bottom: 7px;
            padding: 6px 10px;
            border: 1px solid rgba(38, 53, 143, .09);
            border-radius: 999px;
            background: rgba(255, 255, 255, .86);
            color: var(--rsbm-blue-dark);
            font-size: 9px;
            font-weight: 900;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .queue-result-page .eyebrow::before {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--rsbm-green);
            box-shadow: 0 0 0 4px rgba(25, 200, 61, .11);
            content: "";
        }

        .queue-ticket,
        .queue-metric,
        .queue-empty,
        .queue-footer-card {
            border-color: rgba(217, 224, 234, .95);
            background: rgba(255, 255, 255, .97);
            box-shadow: 0 12px 30px rgba(28, 39, 90, .055) !important;
        }

        .queue-ticket {
            padding: clamp(19px, 3.2vw, 30px);
            border-radius: clamp(20px, 3vw, 26px);
        }

        .queue-ticket::before {
            right: 0;
            left: 0;
            height: 4px;
            border-radius: 0;
            background: linear-gradient(90deg, var(--rsbm-blue) 0 78%, var(--rsbm-green) 78% 100%);
        }

        .queue-ticket::after {
            background: rgba(38, 53, 143, .055);
        }

        .queue-poli-name,
        .queue-patient h1,
        .queue-section-heading h2,
        .queue-info-value,
        .queue-metric-value,
        .queue-empty h1 {
            color: var(--rsbm-text);
        }

        .refresh-state-dot {
            background: var(--rsbm-green);
        }

        .refresh-state.is-loading .refresh-state-dot {
            background: var(--rsbm-blue);
        }

        .queue-info-card {
            background: var(--rsbm-blue-soft);
        }

        .queue-info-card.is-status.is-warning {
            background: #fff7e6;
        }

        .queue-info-card.is-status.is-primary,
        .queue-info-card.is-status.is-info {
            background: var(--rsbm-blue-soft);
        }

        .queue-info-card.is-status.is-success {
            border-color: #bbf7d0;
            background: var(--rsbm-green-soft);
        }

        .queue-info-icon,
        .is-primary .queue-info-icon,
        .is-info .queue-info-icon {
            color: var(--rsbm-blue);
        }

        .is-success .queue-info-icon {
            color: var(--rsbm-green-dark);
        }

        .patient-gender-symbol {
            background: var(--rsbm-blue-soft);
            color: var(--rsbm-blue);
        }

        .registration-badge {
            background: var(--rsbm-green-soft);
            color: var(--rsbm-green-dark);
        }

        .queue-metric::after {
            background: rgba(38, 53, 143, .055);
        }

        .queue-metric-icon,
        .queue-metric.is-registered .queue-metric-icon {
            background: var(--rsbm-blue-soft);
            color: var(--rsbm-blue);
        }

        .queue-metric.is-registered .queue-metric-icon {
            background: var(--rsbm-green-soft);
            color: var(--rsbm-green-dark);
        }

        .queue-footer-note svg {
            color: var(--rsbm-blue);
        }

        .queue-empty-icon {
            background: var(--rsbm-blue-soft);
            color: var(--rsbm-blue);
        }

        @media (max-width: 560px) {
            .queue-result-page {
                padding: 10px 10px 38px;
            }

            .queue-brandbar {
                align-items: stretch;
                flex-direction: column;
                padding: 10px;
            }

            .queue-brand-actions {
                display: grid;
                grid-template-columns: 1fr 1fr;
                width: 100%;
            }

            .queue-brand-actions .queue-nav-button,
            .queue-brand-actions .queue-logout-form,
            .queue-brand-actions .queue-logout-form .queue-nav-button {
                width: 100%;
            }

            .queue-footer-actions {
                display: grid;
                grid-template-columns: 1fr;
                width: 100%;
            }

            .queue-footer-actions .queue-nav-button,
            .queue-footer-actions .queue-logout-form,
            .queue-footer-actions .queue-logout-form .queue-nav-button {
                width: 100%;
            }
        }
    </style>

    <section class="queue-result-page">
        <div class="queue-result">
            <div class="queue-brandbar">
                <div class="queue-brand">
                    <div class="queue-brand-logo-shell">
                        <img
                            class="queue-brand-logo"
                            src="{{ asset('images/logo-rsbm.png') }}"
                            alt="Logo RSUD Bali Mandara"
                            onerror="this.style.display='none'; this.nextElementSibling.style.display='grid';"
                        >
                        <span class="queue-brand-logo-fallback" aria-hidden="true">+</span>
                    </div>

                    <div>
                        <div class="queue-brand-government">Pemerintah Provinsi Bali</div>
                        <div class="queue-brand-hospital">RSUD Bali Mandara</div>
                        <div class="queue-brand-location">Sanur · Denpasar · Bali</div>
                    </div>
                </div>

                <div class="queue-brand-actions">
                    <a href="{{ $mainMenuUrl }}" class="queue-nav-button is-menu">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M3 11L12 4L21 11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M5 10V20H19V10M9 20V14H15V20" stroke="currentColor" stroke-width="2" stroke-linejoin="round" />
                        </svg>
                        <span>Menu Utama</span>
                    </a>

                    @if($logoutRouteName)
                        <form method="POST" action="{{ route($logoutRouteName) }}" class="queue-logout-form">
                            @csrf
                            <button type="submit" class="queue-nav-button is-logout">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M10 5H6C4.9 5 4 5.9 4 7V17C4 18.1 4.9 19 6 19H10" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                    <path d="M14 8L18 12L14 16M18 12H9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <span>Logout</span>
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="queue-result-stack">
                @if(! $found)
                    <div class="panel queue-empty">
                        <div class="queue-empty-icon">
                            <svg width="30" height="30" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2" />
                                <path d="M16.5 16.5L21 21" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                <path d="M8.5 11H13.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                            </svg>
                        </div>

                        <div class="eyebrow">
                            SAPA RSBM · {{ !empty($result['is_error']) ? 'Layanan bermasalah' : 'Data tidak ditemukan' }}
                        </div>

                        <h1>Antrean belum tersedia</h1>

                        <p class="lead">
                            {{ $result['message'] ?? 'Data antrean pasien tidak ditemukan untuk hari ini.' }}
                        </p>

                        <p class="muted">
                            Nomor RM yang diperiksa:
                            <strong>{{ $keyword }}</strong>
                        </p>
                    </div>
                @else
                    <div class="panel queue-ticket">
                        <div class="queue-ticket-content">
                            <div class="queue-topbar">
                                <div class="queue-heading">
                                    <div class="eyebrow">SAPA RSBM · Waktu Tunggu</div>
                                    <h2 class="queue-poli-name">
                                        {{ data_get($antrean, 'namaruangan', 'Poli') ?: 'Poli' }}
                                    </h2>
                                </div>

                                <div id="refresh-state" class="refresh-state" aria-live="polite">
                                    <span class="refresh-state-dot"></span>
                                    <span id="refresh-state-text">Data terbaru</span>
                                </div>
                            </div>

                            <div class="queue-info-grid">
                                <div
                                    id="queue-status-card"
                                    class="queue-info-card is-status {{ $statusClass }}"
                                >
                                    <div class="queue-info-label-row">
                                        <span class="queue-info-icon">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2" />
                                                <path d="M12 7V12L15 14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                        </span>
                                        <span class="queue-info-label">Status Antrean</span>
                                    </div>

                                    <div id="queue-status-value" class="queue-info-value is-text">
                                        {{ $statusPasien }}
                                    </div>
                                </div>

                                <div class="queue-info-card">
                                    <div class="queue-info-label-row">
                                        <span class="queue-info-icon">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                <path d="M6 4H18V20H6V4Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round" />
                                                <path d="M9 8H15M9 12H15M9 16H12" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                            </svg>
                                        </span>
                                        <span class="queue-info-label">Nomor loket</span>
                                    </div>

                                    <div id="queue-loket-value" class="queue-info-value is-loket">
                                        {{ data_get($antrean, 'antrianloket', '-') ?: '-' }}
                                    </div>
                                </div>

                                <div class="queue-info-card">
                                    <div class="queue-info-label-row">
                                        <span class="queue-info-icon">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                <path d="M7 4H17C18.1 4 19 4.9 19 6V18C19 19.1 18.1 20 17 20H7C5.9 20 5 19.1 5 18V6C5 4.9 5.9 4 7 4Z" stroke="currentColor" stroke-width="2" />
                                                <path d="M9 9H15M9 13H15" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                            </svg>
                                        </span>
                                        <span class="queue-info-label">Nomor antrean</span>
                                    </div>

                                    <div id="queue-number-value" class="queue-info-value">
                                        {{ data_get($antrean, 'noantrian', '-') }}
                                    </div>
                                </div>
                            </div>

                            <div class="queue-patient">
                                <div class="queue-patient-main">
                                    <div class="patient-name-row">
                                        <h1>{{ data_get($antrean, 'namapasien', '-') }}</h1>

                                        @if($jenisKelamin)
                                            <span
                                                class="patient-gender-symbol {{ $isPerempuan ? 'is-female' : ($isLakiLaki ? 'is-male' : 'is-neutral') }}"
                                                title="{{ $jenisKelamin }}"
                                                aria-label="Jenis kelamin: {{ $jenisKelamin }}"
                                            >
                                                {{ $isPerempuan ? '♀' : ($isLakiLaki ? '♂' : '⚥') }}
                                            </span>
                                        @endif
                                    </div>

                                    <div class="patient-detail-row">
                                        <span>
                                            No. RM
                                            <strong>{{ data_get($antrean, 'nocm', '-') }}</strong>
                                        </span>

                                        @if(data_get($antrean, 'dokter'))
                                            <span class="patient-detail-separator" aria-hidden="true">•</span>
                                            <span class="patient-doctor">
                                                Dokter:
                                                <strong>{{ data_get($antrean, 'dokter') }}</strong>
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                @if($statusRegistrasi || $asalRegistrasi)
                                    <div class="registration-badge">
                                        {{ $asalRegistrasi ?: $statusRegistrasi }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="queue-overview">
                        <div class="queue-section-heading">
                            <h2>Informasi Antrean</h2>
                            <p>Ringkasan antrean poli diperbarui otomatis setiap 10 detik.</p>
                        </div>

                        <div class="queue-metric">
                            <div class="queue-metric-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M7 4H17C18.1 4 19 4.9 19 6V20L12 16.5L5 20V6C5 4.9 5.9 4 7 4Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round" />
                                </svg>
                            </div>

                            <div class="queue-metric-bottom">
                                <div class="queue-metric-copy">
                                    <strong>Total Reservasi</strong>
                                    <span>Pasien yang terdaftar pada poli</span>
                                </div>

                                <div id="queue-reservation-value" class="queue-metric-value">
                                    {{ $totalPasienReservasi }}
                                </div>
                            </div>
                        </div>

                        <div class="queue-metric is-registered">
                            <div class="queue-metric-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M8 7V5C8 3.9 8.9 3 10 3H14C15.1 3 16 3.9 16 5V7" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                    <rect x="4" y="7" width="16" height="14" rx="3" stroke="currentColor" stroke-width="2" />
                                    <path d="M9 12H15M12 9V15" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                </svg>
                            </div>

                            <div class="queue-metric-bottom">
                                <div class="queue-metric-copy">
                                    <strong>Sudah Check-in</strong>
                                    <span>Pasien yang sudah registrasi/check-in</span>
                                </div>

                                <div id="queue-checkin-value" class="queue-metric-value">
                                    {{ $totalPasienCheckin }}
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="queue-footer-card">
                    <div class="queue-footer-note">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2" />
                            <path d="M12 11V16M12 8H12.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                        </svg>
                        <span>Nomor antrean dapat berubah mengikuti proses pelayanan di poli.</span>
                    </div>

                    <div class="queue-footer-actions">
                        <a class="queue-nav-button is-menu" href="{{ route('queue.home') }}">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <span>Cek Pasien Lain</span>
                        </a>

                        <a class="queue-nav-button is-menu" href="{{ $mainMenuUrl }}">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M3 11L12 4L21 11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M5 10V20H19V10M9 20V14H15V20" stroke="currentColor" stroke-width="2" stroke-linejoin="round" />
                            </svg>
                            <span>Menu Utama</span>
                        </a>

                        @if($logoutRouteName)
                            <form method="POST" action="{{ route($logoutRouteName) }}" class="queue-logout-form">
                                @csrf
                                <button type="submit" class="queue-nav-button is-logout">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M10 5H6C4.9 5 4 5.9 4 7V17C4 18.1 4.9 19 6 19H10" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                        <path d="M14 8L18 12L14 16M18 12H9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <span>Logout</span>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    @if($found)
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const statusCard = document.getElementById('queue-status-card');
                const statusValue = document.getElementById('queue-status-value');
                const loketValue = document.getElementById('queue-loket-value');
                const queueNumberValue = document.getElementById('queue-number-value');
                const reservationValue = document.getElementById('queue-reservation-value');
                const checkinValue = document.getElementById('queue-checkin-value');
                const refreshState = document.getElementById('refresh-state');
                const refreshStateText = document.getElementById('refresh-state-text');

                const statusClasses = [
                    'is-info',
                    'is-warning',
                    'is-primary',
                    'is-success',
                    'is-danger',
                ];

                let sedangRefresh = false;

                function setRefreshState(state, text) {
                    if (!refreshState || !refreshStateText) {
                        return;
                    }

                    refreshState.classList.remove('is-loading', 'is-error');

                    if (state) {
                        refreshState.classList.add(state);
                    }

                    refreshStateText.textContent = text;
                }

                function setText(element, value, fallback = '-') {
                    if (!element) {
                        return;
                    }

                    const normalized = value === null || value === undefined || value === ''
                        ? fallback
                        : value;

                    element.textContent = normalized;
                }

                async function refreshAntrean() {
                    if (sedangRefresh || document.hidden) {
                        return;
                    }

                    sedangRefresh = true;
                    setRefreshState('is-loading', 'Memperbarui');

                    try {
                        const response = await fetch(
                            "{{ route('queue.refresh') }}",
                            {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'Content-Type': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'X-CSRF-TOKEN': "{{ csrf_token() }}",
                                },
                                body: JSON.stringify({
                                    rm: "{{ $keyword }}",
                                    tanggal_lahir: "{{ $tanggalLahir }}",
                                }),
                                cache: 'no-store',
                            }
                        );

                        const result = await response.json();

                        if (!response.ok || !result.success) {
                            throw new Error(
                                result.message || 'Gagal memperbarui antrean.'
                            );
                        }

                        const data = result.data ?? {};

                        setText(statusValue, data.status_pasien);
                        setText(loketValue, data.antrianloket);
                        setText(queueNumberValue, data.noantrian);
                        setText(
                            reservationValue,
                            data.total_pasien_reservasi
                                ?? data.total_reservasi
                                ?? data.sisa_reservasi_di_depan,
                            0
                        );

                        setText(
                            checkinValue,
                            data.total_pasien_checkin
                                ?? data.total_teregistrasi
                                ?? data.sisa_teregistrasi_di_depan,
                            0
                        );

                        if (statusCard) {
                            statusCard.classList.remove(...statusClasses);

                            /*
                             * Prioritaskan class yang dikirim API.
                             * Mendukung nilai seperti "is-warning tag"
                             * tanpa menyebabkan error pada classList.add().
                             */
                            const apiStatusClass = String(
                                data.class_statusperiksa
                                    ?? data.status_class
                                    ?? ''
                            )
                                .trim()
                                .split(/\s+/)
                                .find(className =>
                                    statusClasses.includes(className)
                                );

                            statusCard.classList.add(
                                apiStatusClass ?? 'is-info'
                            );
                        }

                        setRefreshState('', 'Data terbaru');
                    } catch (error) {
                        setRefreshState('is-error', 'Gagal diperbarui');
                        console.error('Refresh antrean gagal:', error);
                    } finally {
                        sedangRefresh = false;
                    }
                }

                setInterval(refreshAntrean, 10000);

                document.addEventListener('visibilitychange', function () {
                    if (!document.hidden) {
                        refreshAntrean();
                    }
                });
            });
        </script>
    @endif
@endsection