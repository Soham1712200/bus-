<?php
/**
 * Jayambe Travels - Global Navigation Header
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

$currentUser = current_user();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' | ' . SITE_NAME : SITE_NAME . ' - ' . SITE_TAGLINE; ?></title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Core & Print Stylesheets -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/print.css" media="print">
</head>
<body>

    <!-- Top Helpline Notification Bar -->
    <div class="top-bar">
        <div class="container">
            <div class="top-bar-left">
                <span><i class="fas fa-headset me-1 text-primary"></i> 24x7 ST Helpline: <strong><?= HELPLINE_NUMBER; ?></strong></span>
                <span><i class="fas fa-envelope me-1 text-primary"></i> <?= SUPPORT_EMAIL; ?></span>
            </div>
            <div class="top-bar-right">
                <span class="live-badge"><span class="live-dot"></span> Live Fleet Tracking Active</span>
                <a href="routes.php"><i class="fas fa-clock me-1"></i> Today's Schedule</a>
                <a href="print-ticket.php"><i class="fas fa-print me-1"></i> Quick Print</a>
                <a href="admin/index.php" style="color: #38bdf8; font-weight:700; background: rgba(2, 132, 199, 0.2); padding: 2px 8px; border-radius: 4px; border: 1px solid rgba(56, 189, 248, 0.3);"><i class="fas fa-building text-info me-1"></i> Branch Office Portal</a>
            </div>
        </div>
    </div>

    <!-- Main Navigation Header -->
    <header class="main-header">
        <div class="container">
            <div class="header-wrapper">
                <!-- Brand Logo Section -->
                <a href="index.php" class="brand-logo" title="Jayambe Travels - Home">
                    <img src="assets/images/logo.svg" alt="Jayambe Travels" style="height: 46px; width: auto; max-width: 220px;" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div style="display:none; align-items:center; gap:10px;">
                        <div class="logo-symbol">
                            <i class="fas fa-bus-simple"></i>
                        </div>
                        <div class="brand-text">
                            <div class="brand-name">Jayambe <span>Travels</span></div>
                            <div class="brand-tagline">Gujarat Deluxe Service</div>
                        </div>
                    </div>
                </a>

                <!-- Navigation Links -->
                <ul class="nav-menu">
                    <li><a href="index.php" class="nav-link <?= $currentPage === 'index.php' ? 'active' : ''; ?>"><i class="fas fa-house"></i> Home</a></li>
                    <li><a href="search.php" class="nav-link <?= $currentPage === 'search.php' ? 'active' : ''; ?>"><i class="fas fa-magnifying-glass"></i> Search Bus</a></li>
                    <li><a href="print-ticket.php" class="nav-link <?= $currentPage === 'print-ticket.php' ? 'active' : ''; ?>"><i class="fas fa-ticket"></i> Print E-Ticket</a></li>
                    <li><a href="cancel-ticket.php" class="nav-link <?= $currentPage === 'cancel-ticket.php' ? 'active' : ''; ?>"><i class="fas fa-ban"></i> Cancel Ticket</a></li>
                    <li><a href="routes.php" class="nav-link <?= $currentPage === 'routes.php' ? 'active' : ''; ?>"><i class="fas fa-route"></i> Route Schedule</a></li>
                    <li><a href="contact.php" class="nav-link <?= $currentPage === 'contact.php' ? 'active' : ''; ?>"><i class="fas fa-phone"></i> Contact Us</a></li>
                </ul>

                <!-- Header Actions (Auth / User Profile) -->
                <div class="header-actions">
                    <?php if ($currentUser): ?>
                        <?php if ($currentUser['role'] === 'admin' || $currentUser['role'] === 'counter_agent'): ?>
                            <a href="admin/index.php" class="btn-nav-primary" style="background:#0284c7; box-shadow:none; padding:8px 14px;" title="Branch Office Operations Portal">
                                <i class="fas fa-cash-register me-1"></i> Office Portal
                            </a>
                        <?php endif; ?>
                        <div class="user-profile-menu">
                            <a href="my-bookings.php" class="btn-user-badge">
                                <span class="user-avatar"><?= strtoupper(substr($currentUser['name'], 0, 1)); ?></span>
                                <span><?= htmlspecialchars(explode(' ', $currentUser['name'])[0]); ?></span>
                                <i class="fas fa-chevron-down ms-1 text-muted" style="font-size:11px;"></i>
                            </a>
                        </div>
                        <a href="logout.php" class="btn-nav-primary" style="background:#334155; box-shadow:none; padding:8px 14px;" title="Logout">
                            <i class="fas fa-arrow-right-from-bracket"></i>
                        </a>
                    <?php else: ?>
                        <a href="admin/login.php" class="nav-link" style="font-weight:700; color: #0284c7;"><i class="fas fa-shield-halved"></i> Staff Portal</a>
                        <a href="login.php" class="nav-link" style="font-weight:700;"><i class="fas fa-user-circle"></i> Login</a>
                        <a href="search.php" class="btn-nav-primary">
                            <i class="fas fa-ticket-simple"></i> Book Ticket
                        </a>
                    <?php endif; ?>

                    <button class="mobile-toggle" aria-label="Toggle Navigation">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Flash Messages Output -->
    <?= display_flash(); ?>
