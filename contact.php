<?php
/**
 * Gopi Travels - Gujarat Offices Directory & Contact Us
 */

$pageTitle = 'Contact Us & Gujarat Branch Offices';
require_once __DIR__ . '/includes/header.php';

$pdo = get_db_connection();
$inquirySent = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_inquiry'])) {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $subject = sanitize($_POST['subject'] ?? '');
    $message = sanitize($_POST['message'] ?? '');

    if (!empty($name) && !empty($email) && !empty($message) && $pdo) {
        try {
            $stmt = $pdo->prepare("INSERT INTO contact_inquiries (name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $email, $phone, $subject, $message]);
            $inquirySent = true;
            set_flash('success', 'Thank you! Your message has been received. Our Gujarat office team will contact you shortly.');
        } catch (Exception $e) {
            set_flash('danger', 'Unable to submit inquiry: ' . $e->getMessage());
        }
    }
}

$offices = [
    [
        'city' => 'Ahmedabad Head Office (Geeta Mandir)',
        'address' => 'Geeta Mandir Road, Near Astodia Darwaja, Ahmedabad - 380022',
        'phone' => '079-25450123 / 1800-233-6666',
        'email' => 'ahmedabad.office@gopitravels.com'
    ],
    [
        'city' => 'Surat Central Branch Office (Ring Road)',
        'address' => 'Central Bus Terminal Complex, Ring Road, Varachha, Surat - 395003',
        'phone' => '0261-2425000 / 1800-233-6666',
        'email' => 'surat.office@gopitravels.com'
    ],
    [
        'city' => 'Vadodara Central Branch Office',
        'address' => 'Opposite Railway Station, Sayajigunj, Vadodara - 390005',
        'phone' => '0265-2794000 / 1800-233-6666',
        'email' => 'vadodara.office@gopitravels.com'
    ],
    [
        'city' => 'Rajkot Central Branch Office',
        'address' => 'Shastri Maidan, Dhebar Road, Rajkot - 360001',
        'phone' => '0281-2223000 / 1800-233-6666',
        'email' => 'rajkot.office@gopitravels.com'
    ],
    [
        'city' => 'Bhavnagar Branch Office',
        'address' => 'Navapara, Near High Court Road, Bhavnagar - 364001',
        'phone' => '0278-2512000',
        'email' => 'bhavnagar.office@gopitravels.com'
    ],
    [
        'city' => 'Bhuj Branch Office (Kutch)',
        'address' => 'Station Road, Near Jubilee Ground, Bhuj - 370001',
        'phone' => '02832-220000',
        'email' => 'bhuj.office@gopitravels.com'
    ]
];
?>

<div class="container" style="padding: 40px 20px 80px;">
    
    <div style="text-align: center; max-width: 700px; margin: 0 auto 40px;">
        <span class="section-tag">24x7 Passenger Support</span>
        <h1 style="font-size: 32px; font-weight: 800; color: #0f172a;">Branch Offices & Contact Us</h1>
        <p style="color: #64748b; font-size: 15px; margin-top: 6px;">
            Get in touch with our central customer service desk or visit your nearest Gujarat branch office.
        </p>
    </div>

    <!-- Helpline Banner Cards -->
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 40px;">
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; text-align: center; box-shadow: var(--shadow-sm);">
            <div style="width: 50px; height: 50px; background: #ffedd5; color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 22px; margin: 0 auto 12px;">
                <i class="fas fa-phone-volume"></i>
            </div>
            <h3 style="font-size: 16px; font-weight: 800; color: #0f172a;">Toll-Free Helpline</h3>
            <p style="font-size: 18px; font-weight: 800; color: var(--primary); margin: 6px 0;"><?= HELPLINE_NUMBER; ?></p>
            <span style="font-size: 12px; color: #94a3b8;">24 Hours / 7 Days Live Support</span>
        </div>

        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; text-align: center; box-shadow: var(--shadow-sm);">
            <div style="width: 50px; height: 50px; background: #e0f2fe; color: #0284c7; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 22px; margin: 0 auto 12px;">
                <i class="fas fa-envelope-open-text"></i>
            </div>
            <h3 style="font-size: 16px; font-weight: 800; color: #0f172a;">Email Helpdesk</h3>
            <p style="font-size: 15px; font-weight: 700; color: #0f172a; margin: 6px 0;"><?= SUPPORT_EMAIL; ?></p>
            <span style="font-size: 12px; color: #94a3b8;">Response within 2 hours</span>
        </div>

        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; text-align: center; box-shadow: var(--shadow-sm);">
            <div style="width: 50px; height: 50px; background: #d1fae5; color: #059669; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 22px; margin: 0 auto 12px;">
                <i class="fas fa-building"></i>
            </div>
            <h3 style="font-size: 16px; font-weight: 800; color: #0f172a;">Corporate Head Office</h3>
            <p style="font-size: 15px; font-weight: 700; color: #0f172a; margin: 6px 0;">Geeta Mandir Central, Ahmedabad</p>
            <span style="font-size: 12px; color: #94a3b8;">Gujarat State Main Operations Desk</span>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 36px; align-items: start;">
        
        <!-- Gujarat Branch Offices Directory -->
        <div>
            <h2 style="font-size: 20px; font-weight: 800; color: #0f172a; margin-bottom: 18px;">
                <i class="fas fa-building-flag text-primary me-2"></i> Major Gujarat Branch Offices
            </h2>

            <div style="display: flex; flex-direction: column; gap: 14px;">
                <?php foreach ($offices as $office): ?>
                    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px; box-shadow: var(--shadow-sm);">
                        <h4 style="font-size: 16px; font-weight: 800; color: #0f172a; margin-bottom: 6px;">
                            <i class="fas fa-building text-primary me-2"></i> <?= htmlspecialchars($office['city']); ?>
                        </h4>
                        <p style="font-size: 13px; color: #64748b; margin-bottom: 8px;">
                            <i class="fas fa-location-dot me-1 text-muted"></i> <?= htmlspecialchars($office['address']); ?>
                        </p>
                        <div style="display: flex; gap: 16px; font-size: 12px; color: #334155; font-weight: 600;">
                            <span><i class="fas fa-phone text-primary me-1"></i> <?= htmlspecialchars($office['phone']); ?></span>
                            <span><i class="fas fa-envelope text-primary me-1"></i> <?= htmlspecialchars($office['email']); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Contact / Feedback Form -->
        <div>
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 30px; box-shadow: var(--shadow-md);">
                <h2 style="font-size: 20px; font-weight: 800; color: #0f172a; margin-bottom: 6px;">
                    <i class="fas fa-paper-plane text-primary me-2"></i> Send Inquiry / Feedback
                </h2>
                <p style="color: #64748b; font-size: 13px; margin-bottom: 20px;">
                    Have queries regarding your journey, lost luggage, or corporate bus bookings? Send our office team a message.
                </p>

                <form action="contact.php" method="POST">
                    <div class="form-group-custom" style="margin-bottom: 14px;">
                        <label class="form-label-custom">Full Name</label>
                        <input type="text" name="name" class="form-control-custom" placeholder="Your Name" required>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                        <div class="form-group-custom">
                            <label class="form-label-custom">Email Address</label>
                            <input type="email" name="email" class="form-control-custom" placeholder="name@email.com" required>
                        </div>
                        <div class="form-group-custom">
                            <label class="form-label-custom">Mobile Number</label>
                            <input type="tel" name="phone" class="form-control-custom" placeholder="10-digit mobile" required>
                        </div>
                    </div>

                    <div class="form-group-custom" style="margin-bottom: 14px;">
                        <label class="form-label-custom">Subject</label>
                        <input type="text" name="subject" class="form-control-custom" placeholder="e.g. Booking Assistance, Lost Item, Luggage Query" required>
                    </div>

                    <div class="form-group-custom" style="margin-bottom: 20px;">
                        <label class="form-label-custom">Message</label>
                        <textarea name="message" class="form-control-custom" style="height: 100px; padding: 10px 14px;" placeholder="Type your message here..." required></textarea>
                    </div>

                    <button type="submit" name="send_inquiry" value="1" class="btn-search-bus" style="width: 100%;">
                        <i class="fas fa-paper-plane me-2"></i> Send Message
                    </button>
                </form>
            </div>
        </div>

    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
