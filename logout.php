<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

logout_admin($pdo);

header('Location: login.php?message=' . urlencode('Logged out successfully.'));
exit;
