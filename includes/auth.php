<?php
/**
 * Session-based authentication and role-access guards.
 * Implements FR-AUTH.3 (login/logout) and FR-AUTH.6 (role-based access control).
 */

function current_user() {
    return $_SESSION['user'] ?? null;
}

function is_logged_in() {
    return isset($_SESSION['user']);
}

function login_user(array $user) {
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id' => $user['id'],
        'role' => $user['role'],
        'full_name' => $user['full_name'],
        'email' => $user['email'],
    ];
}

function logout_user() {
    $_SESSION = [];
    session_regenerate_id(true);
}

function require_login() {
    if (!is_logged_in()) {
        flash('error', t('auth.please_login'));
        redirect('/auth/login.php');
    }
}

function require_role($role) {
    require_login();
    $roles = (array) $role;
    if (!in_array(current_user()['role'], $roles, true)) {
        http_response_code(403);
        die(e(t('auth.no_permission')));
    }
}
