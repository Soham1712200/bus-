<?php
/**
 * Jayambe Travels - Admin & Staff Portal Login
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Redirect if already logged in as staff
if (is_staff()) {
    header('Location: index.php');
    exit;
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['staff_login_submit'])) {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter both staff email and password.';
    } else {
        $pdo = get_db_connection();
        if ($pdo) {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role IN ('admin', 'counter_agent') LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                login_user($user);
                $dest = $_SESSION['redirect_after_staff_login'] ?? 'index.php';
                unset($_SESSION['redirect_after_staff_login']);
                set_flash('success', "Welcome back, {$user['name']}! Connected to {$user['branch_office']}.");
                header("Location: {$dest}");
                exit;
            } else {
                $error = 'Invalid credentials or user account does not have staff permissions.';
            }
        } else {
            $error = 'Database connection failed. Please ensure MySQL is active.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff & Counter Agent Login | Jayambe Travels</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body style="background: linear-gradient(135deg, #0b132b 0%, #1c2541 60%, #0f172a 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px;">

    <div style="width: 100%; max-width: 480px; background: #ffffff; border-radius: var(--radius-xl); box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.5); padding: 40px; position: relative; overflow: hidden;">
        
        <div style="position: absolute; top: 0; left: 0; right: 0; height: 5px; background: linear-gradient(90deg, #f97316, #0284c7);"></div>

        <div style="text-align: center; margin-bottom: 28px;">
            <img src="../assets/images/logo.svg" alt="Jayambe Travels" style="height: 48px; width: auto; margin-bottom: 12px;" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
            <div style="display:none; font-size:24px; font-weight:800; color:#0b132b;">Jayambe <span style="color:#f97316;">Travels</span></div>
            <div style="display:inline-block; background: #e0f2fe; color: #0369a1; font-size: 11px; font-weight: 800; padding: 3px 10px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 4px;">
                <i class="fas fa-shield-halved me-1"></i> Admin & Branch Office Portal
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" style="margin-bottom: 20px; font-size: 13px;">
                <i class="fas fa-circle-exclamation me-2"></i> <?= htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="form-group-custom" style="margin-bottom: 16px;">
                <label class="form-label-custom"><i class="fas fa-id-badge"></i> Staff Email</label>
                <input type="email" name="email" id="staffEmailInput" class="form-control-custom" placeholder="staff@jayambetravels.com" value="<?= htmlspecialchars($email); ?>" required>
            </div>

            <div class="form-group-custom" style="margin-bottom: 20px;">
                <label class="form-label-custom"><i class="fas fa-lock"></i> Password</label>
                <input type="password" name="password" id="staffPasswordInput" class="form-control-custom" placeholder="••••••••" required>
            </div>

            <button type="submit" name="staff_login_submit" value="1" class="btn-search-bus" style="width: 100%; margin-bottom: 20px;">
                <i class="fas fa-arrow-right-to-bracket me-2"></i> Enter Office Terminal
            </button>
        </form>

        <div style="text-align: center; margin-top: 18px; font-size: 12px; color: #94a3b8;">
            <a href="../index.php" style="color: var(--primary); font-weight: 700;">
                <i class="fas fa-arrow-left me-1"></i> Back to Public Website
            </a>
        </div>

    </div>
</body>
</html>
