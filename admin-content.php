<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/content-data.php';
require_once __DIR__ . '/upload-utils.php';
require_once __DIR__ . '/auth.php';

header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_admin_login($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin-dashboard.php');
    exit;
}

$type = $_POST['type'] ?? '';
$action = $_POST['action'] ?? '';
$id = (int) ($_POST['id'] ?? 0);
$allowed_types = ['event', 'project', 'committee', 'admin'];
$allowed_actions = ['save', 'delete', 'revoke', 'restore'];

if (!in_array($type, $allowed_types, true) || !in_array($action, $allowed_actions, true)) {
    header('Location: admin-dashboard.php');
    exit;
}

function execute_or_redirect(PDOStatement $stmt, array $payload, $location)
{
    try {
        $stmt->execute($payload);
    } catch (PDOException $e) {
        $error_info = $e->errorInfo ?? [];
        $is_schema_error = is_missing_table_error($e)
            || $e->getCode() === '42S22'
            || (int) ($error_info[1] ?? 0) === 1054;
        $message = $is_schema_error
            ? 'Database schema is outdated. Run setup/schema then try again.'
            : 'Database action failed. Please try again.';

        $parts = explode('#', $location, 2);
        $path = $parts[0];
        $hash = $parts[1] ?? '';
        $query = 'message=' . urlencode($message);

        if (env_value('APP_DEBUG', '0') === '1') {
            $debug_error = '[' . ($e->getCode() ?: 'N/A') . '] ' . $e->getMessage();
            $query .= '&error=' . urlencode($debug_error);
        }

        $target = $path . (str_contains($path, '?') ? '&' : '?') . $query;
        if ($hash !== '') {
            $target .= '#' . $hash;
        }

        header('Location: ' . $target);
        exit;
    }

    header('Location: ' . $location);
    exit;
}

if ($action === 'delete' && $id > 0 && in_array($type, ['event', 'project', 'committee'], true)) {
    $tables = [
        'event' => 'events',
        'project' => 'projects',
        'committee' => 'committee',
    ];
    $redirects = [
        'event' => 'admin-dashboard.php#events',
        'project' => 'admin-dashboard.php#projects',
        'committee' => 'admin-dashboard.php#committee',
    ];

    $stmt = $pdo->prepare("DELETE FROM {$tables[$type]} WHERE id = :id");
    execute_or_redirect($stmt, [':id' => $id], $redirects[$type]);
}

if ($type === 'admin') {
    try {
        if ($action === 'save') {
            $committee_id = (int) ($_POST['committee_id'] ?? 0);
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            if ($committee_id <= 0 || $email === '' || strlen($password) < 6) {
                header('Location: admin-dashboard.php#admins');
                exit;
            }

            $committee_stmt = $pdo->prepare('SELECT id, name FROM committee WHERE id = ? LIMIT 1');
            $committee_stmt->execute([$committee_id]);
            $committee_member = $committee_stmt->fetch();

            if (!$committee_member) {
                header('Location: admin-dashboard.php#admins');
                exit;
            }

            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $existing_stmt = $pdo->prepare('SELECT id FROM admins WHERE committee_id = ? LIMIT 1');
            $existing_stmt->execute([$committee_id]);
            $existing_admin = $existing_stmt->fetch();

            if ($existing_admin) {
                $stmt = $pdo->prepare(
                    'UPDATE admins
                     SET name = :name, email = :email, password_hash = :password_hash, is_active = 1
                     WHERE id = :id'
                );
                execute_or_redirect($stmt, [
                    ':name' => $committee_member['name'],
                    ':email' => $email,
                    ':password_hash' => $password_hash,
                    ':id' => $existing_admin['id'],
                ], 'admin-dashboard.php#admins');
            }

            $stmt = $pdo->prepare(
                'INSERT INTO admins (name, email, password_hash, committee_id, is_active)
                 VALUES (:name, :email, :password_hash, :committee_id, 1)'
            );
            execute_or_redirect($stmt, [
                ':name' => $committee_member['name'],
                ':email' => $email,
                ':password_hash' => $password_hash,
                ':committee_id' => $committee_id,
            ], 'admin-dashboard.php#admins');
        }

        if (in_array($action, ['revoke', 'restore'], true) && $id > 0) {
            if ($action === 'revoke' && $id === (int) ($_SESSION['admin_id'] ?? 0)) {
                header('Location: admin-dashboard.php#admins');
                exit;
            }

            $stmt = $pdo->prepare('UPDATE admins SET is_active = :is_active WHERE id = :id');
            execute_or_redirect($stmt, [
                ':is_active' => $action === 'restore' ? 1 : 0,
                ':id' => $id,
            ], 'admin-dashboard.php#admins');
        }

        header('Location: admin-dashboard.php#admins');
        exit;
    } catch (PDOException $e) {
        $error_info = $e->errorInfo ?? [];
        $is_schema_error = is_missing_table_error($e)
            || $e->getCode() === '42S22'
            || (int) ($error_info[1] ?? 0) === 1054;
        $message = $is_schema_error
            ? 'Database schema is outdated. Run setup/schema then try again.'
            : 'Database action failed. Please try again.';
        $query = 'message=' . urlencode($message);
        if (env_value('APP_DEBUG', '0') === '1') {
            $debug_error = '[' . ($e->getCode() ?: 'N/A') . '] ' . $e->getMessage();
            $query .= '&error=' . urlencode($debug_error);
        }
        header('Location: admin-dashboard.php?' . $query . '#admins');
        exit;
    }
}

if ($type === 'event') {
    $allowed_icons = ['location_on', 'history', 'event', 'school', 'groups', 'code'];
    $location_icon = trim($_POST['location_icon'] ?? 'location_on') ?: 'location_on';
    if (!in_array($location_icon, $allowed_icons, true)) {
        $location_icon = 'location_on';
    }

    $payload = [
        ':title' => trim($_POST['title'] ?? ''),
        ':event_date' => trim($_POST['event_date'] ?? '') ?: null,
        ':description' => trim($_POST['description'] ?? ''),
        ':location' => trim($_POST['location'] ?? ''),
        ':location_icon' => $location_icon,
        ':sort_order' => (int) ($_POST['sort_order'] ?? 0),
    ];

    if ($payload[':title'] !== '') {
        if ($id > 0) {
            $payload[':id'] = $id;
            $stmt = $pdo->prepare(
                'UPDATE events
                 SET title = :title, event_date = :event_date, description = :description,
                     location = :location, location_icon = :location_icon, sort_order = :sort_order
                 WHERE id = :id'
            );
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO events (title, event_date, description, location, location_icon, sort_order)
                 VALUES (:title, :event_date, :description, :location, :location_icon, :sort_order)'
            );
        }
        execute_or_redirect($stmt, $payload, 'admin-dashboard.php#events');
    }

    header('Location: admin-dashboard.php#events');
    exit;
}

if ($type === 'project') {
    $payload = [
        ':title' => trim($_POST['title'] ?? ''),
        ':description' => trim($_POST['description'] ?? ''),
        ':tags' => trim($_POST['tags'] ?? ''),
        ':sort_order' => (int) ($_POST['sort_order'] ?? 0),
    ];

    if ($payload[':title'] !== '') {
        if ($id > 0) {
            $payload[':id'] = $id;
            $stmt = $pdo->prepare(
                'UPDATE projects
                 SET title = :title, description = :description, tags = :tags,
                     sort_order = :sort_order
                 WHERE id = :id'
            );
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO projects (title, description, tags, sort_order)
                 VALUES (:title, :description, :tags, :sort_order)'
            );
        }
        execute_or_redirect($stmt, $payload, 'admin-dashboard.php#projects');
    }

    header('Location: admin-dashboard.php#projects');
    exit;
}

$photo_path = trim($_POST['photo_path'] ?? '');
try {
    $uploaded_photo = save_uploaded_image('committee_image', 'committee');
    if ($uploaded_photo !== null) {
        $photo_path = $uploaded_photo;
    }
} catch (RuntimeException $e) {
    header('Location: admin-dashboard.php#committee');
    exit;
}

$payload = [
    ':name' => trim($_POST['name'] ?? ''),
    ':role' => trim($_POST['role'] ?? ''),
    ':photo_path' => $photo_path,
    ':sort_order' => (int) ($_POST['sort_order'] ?? 0),
];

if ($payload[':name'] !== '' && $payload[':role'] !== '') {
    if ($id > 0) {
        $payload[':id'] = $id;
        $stmt = $pdo->prepare(
            'UPDATE committee
             SET name = :name, role = :role, photo_path = :photo_path,
                 sort_order = :sort_order
             WHERE id = :id'
        );
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO committee (name, role, photo_path, sort_order)
             VALUES (:name, :role, :photo_path, :sort_order)'
        );
    }
    execute_or_redirect($stmt, $payload, 'admin-dashboard.php#committee');
}

header('Location: admin-dashboard.php#committee');
exit;


