<?php
/**
 * Jayambe Travels - Admin & Staff Authentication Guard
 */

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

if (!is_staff()) {
    $_SESSION['redirect_after_staff_login'] = $_SERVER['REQUEST_URI'];
    set_flash('warning', 'Staff / Counter Agent credentials required to access Office Portal.');
    header('Location: login.php');
    exit;
}

$staffUser = current_user();
