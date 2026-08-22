<?php
/**
 * Gopi Travels - Authentication Helpers (Customer + Admin Portal)
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';

/**
 * Check if a user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if the logged-in user is an administrator
 */
function is_admin() {
    return is_logged_in() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Check if the logged-in user is a counter agent
 */
function is_counter_agent() {
    return is_logged_in() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'counter_agent';
}

/**
 * Check if logged in as any staff (Admin or Counter Agent)
 */
function is_staff() {
    return is_admin() || is_counter_agent();
}

/**
 * Return currently logged in user info
 */
function current_user() {
    if (!is_logged_in()) {
        return null;
    }
    return [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'] ?? 'Passenger',
        'email' => $_SESSION['user_email'] ?? '',
        'phone' => $_SESSION['user_phone'] ?? '',
        'role' => $_SESSION['user_role'] ?? 'passenger',
        'branch_office' => $_SESSION['user_branch_office'] ?? 'Ahmedabad Head Office'
    ];
}

/**
 * Require login for passenger pages
 */
function require_login($redirectUrl = 'login.php') {
    if (!is_logged_in()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        set_flash('warning', 'Please login to continue.');
        header("Location: {$redirectUrl}");
        exit;
    }
}

/**
 * Require Staff (Admin or Counter Agent) for Office portal
 */
function require_staff($redirectUrl = 'login.php') {
    if (!is_staff()) {
        $_SESSION['redirect_after_staff_login'] = $_SERVER['REQUEST_URI'];
        set_flash('warning', 'Staff / Counter Agent credentials required to access Office Portal.');
        header("Location: {$redirectUrl}");
        exit;
    }
}

/**
 * Require SuperAdmin role
 */
function require_admin($redirectUrl = 'login.php') {
    if (!is_admin()) {
        set_flash('danger', 'Unauthorized: SuperAdmin access required.');
        header("Location: {$redirectUrl}");
        exit;
    }
}

/**
 * Log in a user by setting session
 */
function login_user($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_phone'] = $user['phone'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_branch_office'] = $user['branch_office'] ?? 'Ahmedabad Head Office';
}

/**
 * Log out the current user
 */
function logout_user() {
    unset(
        $_SESSION['user_id'], 
        $_SESSION['user_name'], 
        $_SESSION['user_email'], 
        $_SESSION['user_phone'], 
        $_SESSION['user_role'],
        $_SESSION['user_branch_office']
    );
    session_destroy();
}
