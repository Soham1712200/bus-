<?php
/**
 * Jayambe Travels - Admin & Branch Office Portal Header
 */

require_once __DIR__ . '/auth_guard.php';

$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' | Office Portal - Jayambe Travels' : 'Office Portal - Jayambe Travels'; ?></title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Master Admin CSS -->
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body style="background-color: #f1f5f9;">

    <!-- Top Admin Bar -->
    <div class="top-bar" style="background: #060d1f; border-bottom: 1px solid rgba(255,255,255,0.06);">
        <div class="container-fluid" style="max-width: 1440px; margin: 0 auto;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div style="display: flex; align-items: center; gap: 14px;">
                    <span class="branch-badge">
                        <i class="fas fa-building text-primary"></i> 
                        <?= htmlspecialchars($staffUser['branch_office'] ?? 'Ahmedabad Head Office'); ?>
                    </span>
                    <span class="sync-status-indicator">
                        <span class="sync-dot"></span> Live DB Sync Active
                    </span>
                </div>
                <div style="display: flex; align-items: center; gap: 16px; font-size: 13px; color: #94a3b8;">
                    <span><i class="fas fa-user-tie text-primary me-1"></i> Logged as: <strong style="color:#ffffff;"><?= htmlspecialchars($staffUser['name']); ?></strong> (<?= strtoupper($staffUser['role']); ?>)</span>
                    <a href="../index.php" target="_blank" style="color: #cbd5e1; font-weight: 600;">
                        <i class="fas fa-external-link-alt me-1 text-primary"></i> Customer Site
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Admin Main Navigation -->
    <header class="admin-navbar">
        <div class="admin-navbar-inner">
            <!-- Brand Logo -->
            <a href="index.php" class="brand-logo" style="gap: 12px;">
                <img src="../assets/images/logo.svg" alt="Jayambe Travels" style="height: 42px; width: auto; max-width: 200px;" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <div style="display:none; align-items:center; gap:8px;">
                    <div class="logo-symbol" style="width:36px; height:36px; font-size:18px;"><i class="fas fa-bus-simple"></i></div>
                    <div class="brand-name" style="font-size:18px;">Jayambe <span>Travels</span></div>
                </div>
                <span style="background: #ea580c; color: #fff; font-size: 10px; font-weight: 800; padding: 2px 6px; border-radius: 4px; text-transform: uppercase;">
                    OFFICE PORTAL
                </span>
            </a>

            <!-- Navigation Links -->
            <ul class="nav-menu" style="display: flex; gap: 6px;">
                <li>
                    <a href="index.php" class="nav-link <?= $currentPage === 'index.php' ? 'active' : ''; ?>">
                        <i class="fas fa-chart-pie"></i> Dashboard
                    </a>
                </li>
                <li>
                    <a href="counter-booking.php" class="nav-link <?= $currentPage === 'counter-booking.php' ? 'active' : ''; ?>" style="background: rgba(2, 132, 199, 0.15); border: 1px solid rgba(2, 132, 199, 0.4); color: #38bdf8;">
                        <i class="fas fa-cash-register text-info"></i> Counter Booking
                    </a>
                </li>
                <li>
                    <a href="bookings.php" class="nav-link <?= $currentPage === 'bookings.php' ? 'active' : ''; ?>">
                        <i class="fas fa-list-check"></i> Master Bookings
                    </a>
                </li>
                <li>
                    <a href="manifest.php" class="nav-link <?= $currentPage === 'manifest.php' ? 'active' : ''; ?>">
                        <i class="fas fa-clipboard-user"></i> Trip Manifest
                    </a>
                </li>
                <li>
                    <a href="buses.php" class="nav-link <?= $currentPage === 'buses.php' ? 'active' : ''; ?>">
                        <i class="fas fa-bus"></i> Fleet Schedules
                    </a>
                </li>
            </ul>

            <!-- Staff Profile & Logout -->
            <div style="display: flex; align-items: center; gap: 10px;">
                <div class="user-avatar" style="background: var(--primary);">
                    <?= strtoupper(substr($staffUser['name'], 0, 1)); ?>
                </div>
                <a href="logout.php" class="btn-nav-primary" style="background: #334155; padding: 8px 14px; box-shadow: none; font-size: 13px;" title="Logout Staff">
                    <i class="fas fa-arrow-right-from-bracket me-1"></i> Logout
                </a>
            </div>
        </div>
    </header>

    <!-- Flash Messages Container -->
    <div style="max-width: 1400px; margin: 20px auto 0; padding: 0 20px;">
        <?= display_flash(); ?>
    </div>
