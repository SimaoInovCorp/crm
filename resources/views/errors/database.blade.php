<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Service Unavailable — CRM</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg: oklch(0.98 0 0);
            --card: oklch(1 0 0);
            --border: oklch(0.9 0 0);
            --text: oklch(0.145 0 0);
            --muted: oklch(0.45 0 0);
            --accent: oklch(0.55 0.18 250);
            --accent-light: oklch(0.95 0.04 250);
            --red: oklch(0.55 0.22 25);
            --red-light: oklch(0.96 0.04 25);
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --bg: oklch(0.145 0 0);
                --card: oklch(0.18 0 0);
                --border: oklch(0.26 0 0);
                --text: oklch(0.96 0 0);
                --muted: oklch(0.65 0 0);
                --accent: oklch(0.65 0.18 250);
                --accent-light: oklch(0.22 0.04 250);
                --red: oklch(0.68 0.22 25);
                --red-light: oklch(0.22 0.05 25);
            }
        }

        body {
            font-family: ui-sans-serif, system-ui, -apple-system, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100dvh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 1rem;
            padding: 2.5rem 3rem;
            max-width: 480px;
            width: 100%;
            text-align: center;
            box-shadow: 0 4px 24px oklch(0 0 0 / 0.06);
        }

        .icon-wrap {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 4rem;
            height: 4rem;
            border-radius: 9999px;
            background: var(--red-light);
            margin-bottom: 1.5rem;
        }

        .icon-wrap svg {
            width: 2rem;
            height: 2rem;
            stroke: var(--red);
            stroke-width: 1.75;
            fill: none;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .status-badge {
            display: inline-block;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            background: var(--red-light);
            color: var(--red);
            border-radius: 9999px;
            padding: 0.2rem 0.7rem;
            margin-bottom: 1rem;
        }

        h1 {
            font-size: 1.4rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            margin-bottom: 0.5rem;
        }

        p {
            font-size: 0.9rem;
            color: var(--muted);
            line-height: 1.65;
            margin-bottom: 1.5rem;
        }

        .detail {
            background: var(--accent-light);
            border: 1px solid var(--border);
            border-radius: 0.6rem;
            padding: 0.8rem 1rem;
            font-size: 0.78rem;
            color: var(--accent);
            font-family: ui-monospace, monospace;
            text-align: left;
            word-break: break-all;
            margin-bottom: 2rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.875rem;
            font-weight: 600;
            padding: 0.6rem 1.4rem;
            border-radius: 0.5rem;
            background: var(--accent);
            color: oklch(1 0 0);
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: opacity 0.15s;
        }
        .btn:hover { opacity: 0.85; }

        .footer {
            margin-top: 2rem;
            font-size: 0.75rem;
            color: var(--muted);
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon-wrap">
            <svg viewBox="0 0 24 24">
                <path d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
        </div>

        <span class="status-badge">503 — Service Unavailable</span>

        <h1>Database Unreachable</h1>

        <p>
            The application cannot reach the database server right now.
            This is usually a temporary issue. Please wait a moment and try again.
        </p>

        <div class="detail">
            MySQL · 127.0.0.1:3306 · SQLSTATE HY000 [2002]<br>
            Connection was actively refused by the target host.
        </div>

        <a href="javascript:location.reload()" class="btn">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/>
                <path d="M21 3v5h-5"/>
                <path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/>
                <path d="M8 16H3v5"/>
            </svg>
            Retry
        </a>

        <div class="footer">
            If the problem persists, contact your system administrator.
        </div>
    </div>
</body>
</html>
