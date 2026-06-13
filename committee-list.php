<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/content-data.php';
require_once __DIR__ . '/auth.php';

header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

bootstrap_admin_session($pdo);

$committee_members = all_committee_members($pdo);
$avatar_placeholder = 'assets/avatar.jpg';
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Bit2Byte | Full Committee</title>
    <link
      href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600&display=swap"
      rel="stylesheet"
    />
    <link
      href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=block"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="assets/css/common.css">
    <link rel="stylesheet" href="assets/css/style.css">
  </head>
  <body>
    <header class="site-header">
      <nav class="site-nav">
        <a href="index.php#home" class="brand-link">
          <img src="assets/main-logo.png" alt="Bit2Byte Logo" />
          <span>Bit2Byte</span>
        </a>
        <div class="nav-links">
          <a class="nav-link-hover" href="index.php#home">Home</a>
          <a class="nav-link-hover is-active" href="committee-list.php">Committee</a>
        </div>
        <?php if (empty($_SESSION['admin_id'])): ?>
          <a href="login.php" class="join-link">JOIN_SYSTEM</a>
        <?php else: ?>
          <a href="admin-dashboard.php" class="join-link">ADMIN_DASHBOARD</a>
        <?php endif; ?>
      </nav>
    </header>

    <main>
      <section class="site-section committee-list-page">
        <div class="section-inner">
          <div class="section-heading-row">
            <div>
              <span class="section-kicker">Committee Directory</span>
              <h1 class="section-title">Complete committee list</h1>
              <p class="section-lede">All committee members and their current roles.</p>
            </div>
            <a class="section-action" href="index.php#committee">
              Back to home
              <span class="material-symbols-outlined">arrow_back</span>
            </a>
          </div>

          <div class="committee-grid committee-grid--full">
            <?php foreach ($committee_members as $committee_member): ?>
              <article class="committee-card">
                <div class="committee-photo">
                  <img src="<?= e($committee_member['photo_path'] ?: $avatar_placeholder) ?>"
                       alt="<?= e($committee_member['name']) ?>"
                       onerror="this.onerror=null;this.src='<?= e($avatar_placeholder) ?>';" />
                </div>
                <h3><?= e($committee_member['name']) ?></h3>
                <div class="committee-role"><?= e($committee_member['role']) ?></div>
              </article>
            <?php endforeach; ?>
          </div>
        </div>
      </section>
    </main>
  </body>
</html>
