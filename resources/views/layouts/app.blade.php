<!doctype html>
<html lang="id">
<link rel="manifest" href="/manifest.json">

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f97316">
    <title>{{ $title ?? 'Cek Waktu Tunggu Poli' }}</title>
    <style>
        :root {
            --bg: #fff7ed;
            --surface: rgba(255, 255, 255, .9);
            --text: #1f2937;
            --muted: #6b7280;
            --line: #fed7aa;
            --accent: #f97316;
            --accent-dark: #c2410c;
            --green: #16a34a;
            --blue: #2563eb;
            --red: #dc2626;
            --shadow: 0 18px 50px rgba(154, 52, 18, .12);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            color: var(--text);
            background:
                radial-gradient(circle at top left, rgba(249, 115, 22, .18), transparent 28rem),
                linear-gradient(180deg, #fff7ed 0%, #fff 48%, #fff7ed 100%);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .shell {
            width: min(100%, 760px);
            margin: 0 auto;
            padding: 18px 16px 48px;
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 24px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 850;
        }

        .brand-mark {
            width: 38px;
            height: 38px;
            display: grid;
            place-items: center;
            border-radius: 14px;
            color: white;
            background: var(--accent);
            box-shadow: 0 12px 28px rgba(249, 115, 22, .28);
            font-weight: 950;
        }

        .api-badge {
            min-height: 36px;
            display: inline-flex;
            align-items: center;
            padding: 8px 12px;
            border-radius: 999px;
            color: var(--accent-dark);
            background: #ffedd5;
            font-size: 13px;
            font-weight: 800;
        }

        .hero {
            padding: 10px 0 24px;
        }

        .eyebrow {
            color: var(--accent-dark);
            font-size: 13px;
            font-weight: 850;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        h1, h2, p {
            margin-top: 0;
        }

        h1 {
            margin-bottom: 12px;
            font-size: clamp(34px, 10vw, 64px);
            line-height: .96;
            letter-spacing: 0;
        }

        h2 {
            margin-bottom: 8px;
            font-size: 24px;
            line-height: 1.1;
        }

        .lead {
            max-width: 620px;
            color: var(--muted);
            font-size: 17px;
            line-height: 1.55;
        }

        .panel {
            background: var(--surface);
            border: 1px solid rgba(254, 215, 170, .82);
            border-radius: 28px;
            box-shadow: var(--shadow);
            backdrop-filter: blur(16px);
        }

        .form-panel {
            padding: 18px;
        }

        .field {
            display: grid;
            gap: 8px;
        }

        label {
            color: #374151;
            font-size: 13px;
            font-weight: 800;
        }

        input {
            width: 100%;
            min-height: 54px;
            border: 1px solid var(--line);
            border-radius: 17px;
            background: white;
            color: var(--text);
            padding: 14px;
            font: inherit;
            outline: none;
            transition: border-color .16s ease, box-shadow .16s ease;
        }

        input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 4px rgba(249, 115, 22, .14);
        }

        .grid {
            display: grid;
            gap: 14px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 50px;
            border: 0;
            border-radius: 17px;
            padding: 13px 16px;
            color: white;
            background: var(--accent);
            font: inherit;
            font-weight: 850;
            cursor: pointer;
            box-shadow: 0 12px 24px rgba(249, 115, 22, .22);
        }

        .btn.secondary {
            color: var(--accent-dark);
            background: #ffedd5;
            box-shadow: none;
        }

        .btn.full {
            width: 100%;
        }

        .error {
            color: var(--red);
            font-size: 13px;
            font-weight: 750;
        }

        .ticket {
            position: relative;
            overflow: hidden;
            padding: 22px;
        }

        .ticket::after {
            content: "";
            position: absolute;
            right: -54px;
            bottom: -84px;
            width: 190px;
            height: 190px;
            border-radius: 999px;
            background: rgba(249, 115, 22, .12);
        }

        .queue-number {
            margin: 6px 0;
            font-size: clamp(58px, 22vw, 126px);
            font-weight: 950;
            line-height: .9;
            letter-spacing: -.02em;
        }

        .meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 14px;
        }

        .pill {
            display: inline-flex;
            align-items: center;
            min-height: 34px;
            padding: 8px 11px;
            border-radius: 999px;
            background: #f3f4f6;
            color: #374151;
            font-size: 13px;
            font-weight: 800;
        }

        .status-waiting { background: #fef3c7; color: #92400e; }
        .status-called { background: #dbeafe; color: #1d4ed8; }
        .status-serving { background: #dcfce7; color: #166534; }
        .status-done { background: #e5e7eb; color: #374151; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }

        .metrics {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
        }

        .metric {
            padding: 14px;
            border-radius: 20px;
            background: rgba(255, 255, 255, .78);
            border: 1px solid rgba(254, 215, 170, .72);
        }

        .metric strong {
            display: block;
            font-size: 28px;
            line-height: 1;
        }

        .metric span {
            display: block;
            margin-top: 6px;
            color: var(--muted);
            font-size: 12px;
            font-weight: 750;
        }

        .stack {
            display: grid;
            gap: 14px;
        }

        .muted {
            color: var(--muted);
        }

        @media (min-width: 760px) {
            .shell {
                padding-top: 30px;
            }

            .hero-layout {
                display: grid;
                grid-template-columns: 1fr .82fr;
                align-items: end;
                gap: 28px;
            }

            .form-panel {
                padding: 24px;
            }
        }

        @media (max-width: 520px) {
            .metrics {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <main class="shell">
        <!-- <header class="topbar">
            <a href="{{ route('queue.home') }}" class="brand">
                <span class="brand-mark">P</span>
                <span>PoliQueue</span>
            </a>
            <span class="api-badge">via API</span>
        </header> -->

        @yield('content')
    </main>
    <script>
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/service-worker.js')
            .then(() => console.log('Service Worker Berhasil Terdaftar'))
            .catch((error) => console.log('Gagal Daftar Service Worker:', error));
    }
</script>

</body>
</html>

