<?php
/**
 * Jayambe Travels - Web-based Database Installer & System Verifier
 */

require_once __DIR__ . '/config/db.php';
$installed = false;
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_install'])) {
    ob_start();
    include __DIR__ . '/setup_db.php';
    $output = ob_get_clean();
    $installed = true;
    $message = $output;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup | Jayambe Travels</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #f97316;
            --primary-dark: #ea580c;
            --navy: #0b132b;
            --navy-light: #1c2541;
            --slate: #334155;
            --bg: #0f172a;
            --card-bg: #1e293b;
            --text-light: #f8fafc;
            --text-muted: #94a3b8;
            --emerald: #10b981;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; }
        body {
            background-color: var(--bg);
            color: var(--text-light);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .setup-card {
            background: var(--card-bg);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px;
            max-width: 680px;
            width: 100%;
            padding: 40px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }
        .brand-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .brand-icon {
            font-size: 42px;
            color: var(--primary);
            margin-bottom: 12px;
            display: inline-block;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.08); }
        }
        h1 { font-size: 26px; font-weight: 800; color: var(--text-light); }
        p.subtitle { color: var(--text-muted); font-size: 14px; margin-top: 4px; }
        .specs-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 14px;
            margin: 24px 0;
        }
        .spec-item {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 10px;
            padding: 14px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .spec-item i { font-size: 20px; color: var(--emerald); }
        .spec-item strong { display: block; font-size: 14px; color: var(--text-light); }
        .spec-item span { font-size: 12px; color: var(--text-muted); }
        .btn-install {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: #fff;
            border: none;
            padding: 16px 28px;
            font-size: 16px;
            font-weight: 700;
            border-radius: 10px;
            width: 100%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.25s ease;
            box-shadow: 0 8px 20px -4px rgba(249, 115, 22, 0.4);
        }
        .btn-install:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 25px -4px rgba(249, 115, 22, 0.6);
        }
        .btn-home {
            background: rgba(255,255,255,0.08);
            color: #fff;
            text-decoration: none;
            padding: 14px 24px;
            border-radius: 10px;
            display: inline-block;
            margin-top: 15px;
            font-weight: 600;
            text-align: center;
            width: 100%;
        }
        .btn-home:hover { background: rgba(255,255,255,0.15); }
        .console-output { margin-top: 25px; border-radius: 8px; overflow: hidden; }
    </style>
</head>
<body>
    <div class="setup-card">
        <div class="brand-header">
            <i class="fas fa-bus-simple brand-icon"></i>
            <h1>Jayambe Travels Setup</h1>
            <p class="subtitle">Gujarat State Deluxe Bus Reservation & Fleet Engine</p>
        </div>

        <div class="specs-grid">
            <div class="spec-item">
                <i class="fas fa-database"></i>
                <div>
                    <strong>Database</strong>
                    <span>MySQL: `jayambe_travels`</span>
                </div>
            </div>
            <div class="spec-item">
                <i class="fas fa-map-location-dot"></i>
                <div>
                    <strong>Gujarat Routes</strong>
                    <span>28 Major Highway Routes</span>
                </div>
            </div>
            <div class="spec-item">
                <i class="fas fa-bus"></i>
                <div>
                    <strong>Fleet Assignment</strong>
                    <span>560 Buses (20 per route)</span>
                </div>
            </div>
            <div class="spec-item">
                <i class="fas fa-shield-halved"></i>
                <div>
                    <strong>Bus Classes</strong>
                    <span>Volvo, Electric, Sleeper, Express</span>
                </div>
            </div>
        </div>

        <?php if ($installed): ?>
            <div class="console-output">
                <?= $message; ?>
            </div>
            <a href="index.php" class="btn-home"><i class="fas fa-arrow-right me-2"></i> Launch Jayambe Travels Website</a>
        <?php else: ?>
            <form method="POST">
                <button type="submit" name="run_install" value="1" class="btn-install">
                    <i class="fas fa-bolt"></i> Initialize Database & Fleet (1-Click)
                </button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
