<?php
/**
 * Gopi Travels - Staff Logout
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

logout_user();
session_start();
set_flash('info', 'You have been logged out of the Office Portal.');
header('Location: login.php');
exit;
