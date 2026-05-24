<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/content-data.php';
require_once __DIR__ . '/upload-utils.php';

header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (empty($_COOKIE[session_name()])) {
    header('Location: login.php?message=' . urlencode('Please login as admin.'));
    exit;
}

session_start();

if (empty($_SESSION['admin_id'])) {
    header('Location: login.php?message=' . urlencode('Please login as admin.'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin-dashboard.php');
    exit;
}

$type = $_POST['type'] ?? '';
$action = $_POST['action'] ?? '';
$id = (int) ($_POST['id'] ?? 0);
$allowed_types = ['event', 'project', 'committee'];
$allowed_actions = ['save', 'delete'];

if (!in_array($type, $allowed_types, true) || !in_array($action, $allowed_actions, true)) {
    header('Location: admin-dashboard.php');
    exit;
}

function execute_or_redirect(PDOStatement $stmt, array $payload, $location)
{
    try {
        $stmt->execute($payload);
    } catch (PDOException $e) {
        if (!is_missing_table_error($e)) {
            throw $e;
        }
    }

    header('Location: ' . $location);
    exit;
}

if ($action === 'delete' && $id > 0) {
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


