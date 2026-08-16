<?php
/**
 * Jayambe Travels - Passenger & Admin Login
 */

$pageTitle = 'Login';
require_once __DIR__ . '/includes/header.php';

if (is_logged_in()) {
    header('Location: my-bookings.php');
    exit;
}

$pdo = get_db_connection();
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_submit'])) {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($email) && !empty($password) && $pdo) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR phone = ? LIMIT 1");
        $stmt->execute([$email, $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            login_user($user);
            set_flash('success', "Welcome back, {$user['name']}!");
            
            $redirect = $_SESSION['redirect_after_login'] ?? 'my-bookings.php';
            unset($_SESSION['redirect_after_login']);
            header("Location: {$redirect}");
            exit;
        } else {
            set_flash('danger', 'Invalid email/phone or password.');
        }
    } else {
        set_flash('danger', 'Please enter your login credentials.');
    }
}
?>

<div class="container" style="padding: 60px 20px 90px;">
    <div style="max-width: 440px; margin: 0 auto;">
        
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 20px; padding: 36px; box-shadow: var(--shadow-xl);">
            
            <div style="text-align: center; margin-bottom: 24px;">
                <div class="logo-symbol" style="margin: 0 auto 12px; width: 50px; height: 50px; font-size: 24px;">
                    <i class="fas fa-bus-simple"></i>
                </div>
                <h1 style="font-size: 24px; font-weight: 800; color: #0f172a;">Passenger Login</h1>
                <p style="color: #64748b; font-size: 13px; margin-top: 4px;">Access your Jayambe Travels tickets & profile</p>
            </div>

            <form action="login.php" method="POST">
                <div class="form-group-custom" style="margin-bottom: 16px;">
                    <label class="form-label-custom"><i class="fas fa-envelope"></i> Email or Mobile Number</label>
                    <input type="text" name="email" class="form-control-custom" placeholder="demo@jayambetravels.com" value="<?= htmlspecialchars($email); ?>" required>
                </div>

                <div class="form-group-custom" style="margin-bottom: 20px;">
                    <label class="form-label-custom"><i class="fas fa-lock"></i> Password</label>
                    <input type="password" name="password" class="form-control-custom" placeholder="••••••••" required>
                </div>

                <button type="submit" name="login_submit" value="1" class="btn-search-bus" style="width: 100%; margin-bottom: 18px;">
                    <i class="fas fa-arrow-right-to-bracket me-2"></i> Secure Login
                </button>
            </form>

            <!-- Quick Demo Credentials Helper -->
            <div style="background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 10px; padding: 12px; font-size: 12px; color: #475569; margin-bottom: 18px;">
                <strong><i class="fas fa-key text-primary me-1"></i> Demo Credentials:</strong><br>
                Email: <code>demo@jayambetravels.com</code><br>
                Password: <code>password123</code>
            </div>

            <div style="text-align: center; font-size: 13px; color: #64748b;">
                Don't have an account? <a href="register.php" style="color: var(--primary); font-weight: 700;">Create Account</a>
            </div>

        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
