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
        <div class="icon-wrap" style="background: none; margin-bottom: 1.5rem;">
            <!-- Animated Robot SVG -->
            <svg id="robot" width="64" height="64" viewBox="0 0 64 64" style="display:block;margin:auto;" xmlns="http://www.w3.org/2000/svg">
                <g>
                    <rect x="16" y="24" width="32" height="24" rx="8" fill="#e0e7ff" stroke="#3730a3" stroke-width="2"/>
                    <rect x="26" y="40" width="12" height="8" rx="3" fill="#a5b4fc"/>
                    <circle cx="24" cy="36" r="4" fill="#fff" stroke="#3730a3" stroke-width="2"/>
                    <circle cx="40" cy="36" r="4" fill="#fff" stroke="#3730a3" stroke-width="2"/>
                    <circle id="eye-left" cx="24" cy="36" r="1.5" fill="#3730a3"/>
                    <circle id="eye-right" cx="40" cy="36" r="1.5" fill="#3730a3"/>
                    <rect x="28" y="20" width="8" height="6" rx="2" fill="#a5b4fc" stroke="#3730a3" stroke-width="2"/>
                    <rect x="30" y="10" width="4" height="10" rx="2" fill="#3730a3"/>
                    <rect x="12" y="28" width="4" height="12" rx="2" fill="#a5b4fc"/>
                    <rect x="48" y="28" width="4" height="12" rx="2" fill="#a5b4fc"/>
                </g>
            </svg>
        </div>

        <span class="status-badge">503 — Service Unavailable</span>

        <h1>Database Unreachable</h1>

        <p>
            The application cannot reach the database server right now.
            This is usually a temporary issue. Please wait a moment and try again.
        </p>


        <div class="detail">
            The service is temporarily unavailable. Please try again later.
        </div>

        <div id="robot-tip" style="margin-bottom:1.5rem; font-size:0.95rem; color:var(--muted);">
            <strong>🤖 Did you know?</strong>
            <span id="robot-tip-text">Our CRM robots never sleep—they just recharge!</span>
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

        <script>
            // Animate robot eyes (blink)
            setInterval(() => {
                document.getElementById('eye-left').setAttribute('r', '0.5');
                document.getElementById('eye-right').setAttribute('r', '0.5');
                setTimeout(() => {
                    document.getElementById('eye-left').setAttribute('r', '1.5');
                    document.getElementById('eye-right').setAttribute('r', '1.5');
                }, 180);
            }, 3200);

            // Rotating tips/jokes
            const tips = [
                "Our CRM robots never sleep—they just recharge!",
                "Tip: You can use keyboard shortcuts to speed up your workflow.",
                "Joke: Why did the robot go on vacation? To recharge its batteries!",
                "Tip: Remember to back up your data regularly.",
                "Joke: What do you call a robot who likes to take the scenic route? R2-Detour.",
                "Tip: If you see this page often, check your database connection settings.",
                "Joke: Why was the robot so bad at soccer? It kept kicking up sparks!"
            ];
            let tipIndex = 0;
            setInterval(() => {
                tipIndex = (tipIndex + 1) % tips.length;
                document.getElementById('robot-tip-text').textContent = tips[tipIndex];
            }, 10000);
        </script>
    </div>
</body>
</html>
