<?php

const ADMIN_REMEMBER_COOKIE = 'user';
const ADMIN_REMEMBER_DAYS = 7;

function auth_cookie_path() {
    $path = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
    if ($path === '/' || $path === '\\' || $path === '.') {
        return '/';
    }

    return rtrim(str_replace('\\', '/', $path), '/') . '/';
}

function set_admin_remember_cookie($email) {
    setcookie(
        ADMIN_REMEMBER_COOKIE,
        $email,
        time() + (ADMIN_REMEMBER_DAYS * 86400),
        auth_cookie_path(),
        '',
        !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        true
    );
}

function clear_admin_remember_cookie() {
    setcookie(
        ADMIN_REMEMBER_COOKIE,
        '',
        time() - 3600,
        auth_cookie_path(),
        '',
        !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        true
    );
}

function populate_admin_session(array $admin) {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $_SESSION['admin_id'] = $admin['id'];
    $_SESSION['admin_name'] = $admin['name'];
    $_SESSION['admin_email'] = $admin['email'];
}

function login_admin(PDO $pdo, array $admin, $remember_me) {
    populate_admin_session($admin);

    if ($remember_me) {
        set_admin_remember_cookie($admin['email']);
        return;
    }

    clear_admin_remember_cookie();
}

function restore_admin_from_remember_cookie(PDO $pdo) {
    if (!isset($_COOKIE[ADMIN_REMEMBER_COOKIE])) {
        return false;
    }

    $email = trim($_COOKIE[ADMIN_REMEMBER_COOKIE]);
    if ($email === '') {
        clear_admin_remember_cookie();
        return false;
    }

    try {
        $stmt = $pdo->prepare('SELECT id, name, email FROM admins WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $admin = $stmt->fetch();
    } catch (PDOException $e) {
        if (is_missing_table_error($e)) {
            clear_admin_remember_cookie();
            return false;
        }
        throw $e;
    }

    if (!$admin) {
        clear_admin_remember_cookie();
        return false;
    }

    populate_admin_session($admin);

    return true;
}

function bootstrap_admin_session(PDO $pdo) {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (!empty($_SESSION['admin_id'])) {
        return true;
    }

    if (isset($_COOKIE[ADMIN_REMEMBER_COOKIE])) {
        return restore_admin_from_remember_cookie($pdo);
    }

    return false;
}

function require_admin_login(PDO $pdo) {
    if (bootstrap_admin_session($pdo)) {
        return;
    }

    header('Location: login.php?message=' . urlencode('Please login as admin.'));
    exit;
}

function logout_admin(PDO $pdo) {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $_SESSION = [];
    session_destroy();
    clear_admin_remember_cookie();
}
