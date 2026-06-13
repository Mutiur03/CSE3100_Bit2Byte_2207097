<?php
function load_env($path) {
    if (!is_file($path)) return;

    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim(trim($value), "\"'");
    }
}

function env_value($key, $default = '') {
    return $_ENV[$key] ?? getenv($key) ?: $default;
}

function is_missing_table_error(Throwable $e) {
    if (!$e instanceof PDOException) {
        return false;
    }

    $error_info = $e->errorInfo ?? [];
    return $e->getCode() === '42S02' || (int) ($error_info[1] ?? 0) === 1146;
}

load_env(__DIR__ . '/.env');

$db_host = env_value('DB_HOST', 'localhost');
$db_name = env_value('DB_NAME', 'bit2byte');
$db_user = env_value('DB_USER', 'root');
$db_pass = env_value('DB_PASS', '');

try {
    $pdo = new PDO(
        "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4",
        $db_user,
        $db_pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    $error_info = $e->errorInfo ?? [];
    $is_missing_database = (int) ($error_info[1] ?? 0) === 1049;

    if (!$is_missing_database) {
        http_response_code(500);
        exit('Database connection failed.');
    }

    try {
        $server_pdo = new PDO(
            "mysql:host={$db_host};charset=utf8mb4",
            $db_user,
            $db_pass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
        $quoted_db_name = str_replace('`', '``', $db_name);
        $server_pdo->exec("CREATE DATABASE IF NOT EXISTS `{$quoted_db_name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");

        $pdo = new PDO(
            "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4",
            $db_user,
            $db_pass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    } catch (PDOException $setup_error) {
        http_response_code(500);
        exit('Database connection failed.');
    }
}
