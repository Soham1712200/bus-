<?php
/**
 * Gopi Travels - Passenger Registration
 */

$pageTitle = 'Create Account';
require_once __DIR__ . '/includes/header.php';

if (is_logged_in()) {
    header('Location: my-bookings.php');
    exit;
}

$pdo = get_db_connection();
$name = '';
$email = '';
$phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_submit'])) {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($name) || empty($email) || empty($phone) || empty($password)) {
        set_flash('danger', 'Please fill all required fields.');
    } elseif ($password !== $confirmPassword) {
        set_flash('danger', 'Passwords do not match.');
    } elseif (strlen($password) < 6) {
        set_flash('danger', 'Password must be at least 6 characters long.');
    } elseif ($pdo) {
        // Check if email or phone already registered
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR phone = ? LIMIT 1");
        $stmt->execute([$email, $phone]);
        if ($stmt->fetch()) {
            set_flash('danger', 'An account with this email or phone number already exists.');
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $insStmt = $pdo->prepare("INSERT INTO users (name, email, phone, password_hash, role) VALUES (?, ?, ?, ?, 'passenger')");
            $insStmt->execute([$name, $email, $phone, $hash]);

            $newUserId = $pdo->lastInsertId();
            login_user([
                'id' => $newUserId,
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'role' => 'passenger'
            ]);

            set_flash('success', 'Account created successfully! Welcome to Gopi Travels.');
            header('Location: my-bookings.php');
            exit;
        }
    }
}
?>

<div class="container" style="padding: 50px 20px 80px;">
    <div style="max-width: 480px; margin: 0 auto;">
        
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 20px; padding: 36px; box-shadow: var(--shadow-xl);">
            
            <div style="text-align: center; margin-bottom: 24px;">
                <div class="logo-symbol" style="margin: 0 auto 12px; width: 50px; height: 50px; font-size: 24px;">
                    <i class="fas fa-user-plus"></i>
                </div>
                <h1 style="font-size: 24px; font-weight: 800; color: #0f172a;">Create Passenger Account</h1>
                <p style="color: #64748b; font-size: 13px; margin-top: 4px;">Book buses faster and track all your Gujarat trips</p>
            </div>

            <form action="register.php" method="POST">
                <div class="form-group-custom" style="margin-bottom: 14px;">
                    <label class="form-label-custom"><i class="fas fa-user"></i> Full Name</label>
                    <input type="text" name="name" class="form-control-custom" placeholder="e.g. Ramesh Patel" value="<?= htmlspecialchars($name); ?>" required>
                </div>

                <div class="form-group-custom" style="margin-bottom: 14px;">
                    <label class="form-label-custom"><i class="fas fa-envelope"></i> Email Address</label>
                    <input type="email" name="email" class="form-control-custom" placeholder="name@example.com" value="<?= htmlspecialchars($email); ?>" required>
                </div>

                <div class="form-group-custom" style="margin-bottom: 14px;">
                    <label class="form-label-custom"><i class="fas fa-phone"></i> Mobile Number (10 Digits)</label>
                    <input type="tel" name="phone" class="form-control-custom" placeholder="98XXXXXXXX" pattern="[0-9]{10}" value="<?= htmlspecialchars($phone); ?>" required>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 20px;">
                    <div class="form-group-custom">
                        <label class="form-label-custom"><i class="fas fa-lock"></i> Password</label>
                        <input type="password" name="password" class="form-control-custom" placeholder="Min 6 chars" required>
                    </div>
                    <div class="form-group-custom">
                        <label class="form-label-custom"><i class="fas fa-check-double"></i> Confirm</label>
                        <input type="password" name="confirm_password" class="form-control-custom" placeholder="Re-type" required>
                    </div>
                </div>

                <button type="submit" name="register_submit" value="1" class="btn-search-bus" style="width: 100%; margin-bottom: 18px;">
                    <i class="fas fa-user-check me-2"></i> Register Account
                </button>
            </form>

            <div style="text-align: center; font-size: 13px; color: #64748b;">
                Already have an account? <a href="login.php" style="color: var(--primary); font-weight: 700;">Login Here</a>
            </div>

        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
