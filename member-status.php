<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_admin_login($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin-dashboard.php');
    exit;
}

$member_id = (int) ($_POST['member_id'] ?? 0);
$status = $_POST['status'] ?? '';
$allowed_statuses = ['approved', 'rejected'];

if ($member_id < 1 || !in_array($status, $allowed_statuses, true)) {
    header('Location: admin-dashboard.php');
    exit;
}

try {
    $stmt = $pdo->prepare('UPDATE members SET status = :status WHERE id = :id');
    $stmt->execute([
        ':status' => $status,
        ':id' => $member_id,
    ]);
} catch (PDOException $e) {
    if (!is_missing_table_error($e)) {
        throw $e;
    }
}

header('Location: admin-dashboard.php');
exit;
