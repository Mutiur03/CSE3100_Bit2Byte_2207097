<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/content-data.php';
require_once __DIR__ . '/auth.php';

header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_admin_login($pdo);

try {
  $stmt = $pdo->query('SELECT * FROM members ORDER BY created_at DESC');
  $members = $stmt->fetchAll();
} catch (PDOException $e) {
  if (is_missing_table_error($e)) {
    $members = [];
  } else {
    throw $e;
  }
}
$status_order = ['pending' => 1, 'approved' => 2, 'rejected' => 3];
usort($members, fn($a, $b) => ($status_order[strtolower($a['status'] ?? '')] ?? 99) <=> ($status_order[strtolower($b['status'] ?? '')] ?? 99));
$events = all_events($pdo);
$projects = all_projects($pdo);
$committee_members = all_committee_members($pdo);
$admin_accounts = all_admin_accounts($pdo);

$pending_members = count(array_filter($members, fn($member) => $member['status'] === 'pending'));
$approved_members = count(array_filter($members, fn($member) => $member['status'] === 'approved'));
$rejected_members = count(array_filter($members, fn($member) => $member['status'] === 'rejected'));
$total_members = count($members);
$active_admins = count(array_filter($admin_accounts, fn($admin) => (int) ($admin['is_active'] ?? 0) === 1));
$flash_message = trim($_GET['message'] ?? '');
$flash_error = trim($_GET['error'] ?? '');
$debug_mode = env_value('APP_DEBUG', '0') === '1';
$avatar_placeholder = 'https://placehold.net/avatar.svg';

function status_class($status)
{
  $key = strtolower((string) $status);
  if ($key === 'approved') {
    return 'status-pill status-pill-success';
  }
  if ($key === 'rejected') {
    return 'status-pill status-pill-danger';
  }
  if ($key === 'pending') {
    return 'status-pill status-pill-warning';
  }
  return 'status-pill status-pill-info';
}
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Dashboard | Bit2Byte</title>
  <link
    href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600&display=swap"
    rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="assets/css/admin-dashboard.css" />
</head>

<body class="admin-page overflow-auto">
  <!--Visible Section-->
  <main class="container-fluid min-vh-100 py-3 py-md-5 admin-shell">
    <div class="auth-card auth-card-wide admin-dashboard mx-auto w-100">
      <div class="admin-topbar">
        <div class="admin-title-block">
          <h1 class="brand-text mb-2">Admin Dashboard</h1>
          <p class="policy mb-0">Logged in as <?= e($_SESSION['admin_name']) ?></p>
        </div>
        <div class="admin-top-actions">
          <a href="index.php" class="btn btn-primary">
            View Site
          </a>
          <a href="logout.php" class="btn btn-primary">
            Logout
          </a>
        </div>
      </div>
      <?php if ($flash_message !== ''): ?>
        <div class="alert alert-warning mb-3" role="alert">
          <?= e($flash_message) ?>
        </div>
      <?php endif; ?>
      <?php if ($debug_mode && $flash_error !== ''): ?>
        <div class="alert alert-danger mb-3" role="alert">
          <strong>Debug Error:</strong> <code><?= e($flash_error) ?></code>
        </div>
      <?php endif; ?>
      <!-- Summary -->
      <div class="admin-summary-grid">
        <a class="admin-summary-card" href="#members" data-admin-tab-target="members">
          <span>Members</span>
          <strong><?= e($total_members) ?></strong>
          <small><?= e($pending_members) ?> pending, <?= e($approved_members) ?> approved, <?= e($rejected_members) ?>
            rejected</small>
        </a>
        <a class="admin-summary-card" href="#events" data-admin-tab-target="events">
          <span>Events</span>
          <strong><?= e(count($events)) ?></strong>
          <small><?= e(count(array_filter($events, function ($e) {
            return $e['event_date'] > date('Y-m-d', strtotime('today')); }))) ?>
            Upcoming</small>
        </a>
        <a class="admin-summary-card" href="#projects" data-admin-tab-target="projects">
          <span>Projects</span>
          <strong><?= e(count($projects)) ?></strong>
          <small>Total records</small>
        </a>
        <a class="admin-summary-card" href="#committee" data-admin-tab-target="committee">
          <span>Committee</span>
          <strong><?= e(count($committee_members)) ?></strong>
          <small>Total records</small>
        </a>
        <a class="admin-summary-card" href="#admins" data-admin-tab-target="admins">
          <span>Admins</span>
          <strong><?= e(count($admin_accounts)) ?></strong>
          <small><?= e($active_admins) ?> active</small>
        </a>
      </div>
      <!-- Tabs -->
      <div class="tabs dashboard-tabs d-flex flex-wrap mb-4">
        <a href="#members" class="tab-btn active" data-admin-tab-target="members">Members</a>
        <a href="#events" class="tab-btn" data-admin-tab-target="events">Events</a>
        <a href="#projects" class="tab-btn" data-admin-tab-target="projects">Projects</a>
        <a href="#committee" class="tab-btn" data-admin-tab-target="committee">Committee</a>
        <a href="#admins" class="tab-btn" data-admin-tab-target="admins">Admins</a>
      </div>
      <!-- Members Tab -->
      <section id="members" class="admin-section admin-tab-panel is-active" data-admin-panel="members">
        <div class="admin-section-heading">
          <div>
            <h2 class="admin-section-title">Member Applications</h2>
            <p class="admin-section-subtitle">Review student requests and update approval status.</p>
          </div>
          <span class="status-pill"><?= e(count($members)) ?> Total</span>
        </div>
        <?php if (!$members): ?>
          <p class="policy mb-0">No member applications yet.</p>
        <?php else: ?>
          <div class="admin-table-wrap">
            <table class="table table-hover align-middle mb-0 admin-table">
              <thead>
                <tr>
                  <th>Applicant</th>
                  <th>Contact</th>
                  <th>Academic</th>
                  <th>Status</th>
                  <th>Action</th>
                  <th>Submitted</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($members as $member): ?>
                  <tr>
                    <td data-label="Applicant">
                      <div class="applicant-cell">
                        <?php if (!empty($member['photo_path'])): ?>
                          <img class="admin-thumb" src="<?= e($member['photo_path']) ?>"
                            alt="<?= e($member['full_name']) ?>" />
                        <?php else: ?>
                          <div class="admin-thumb admin-thumb-placeholder">
                            <?= e(strtoupper(substr($member['full_name'], 0, 1))) ?></div>
                        <?php endif; ?>
                        <div>
                          <strong><?= e($member['full_name']) ?></strong>
                          <small><?= e($member['email']) ?></small>
                        </div>
                      </div>
                    </td>
                    <td data-label="Contact">
                      <?= e($member['phone'] ?: 'No phone') ?>
                    </td>
                    <td data-label="Academic">
                      <?= e($member['department']) ?><br />
                      <small>ID: <?= e($member['student_id']) ?> | Batch: <?= e($member['batch']) ?></small>
                    </td>
                    <td data-label="Status"><span
                        class="<?= e(status_class($member['status'])) ?>"><?= e($member['status']) ?></span></td>
                    <td data-label="Action">
                      <form class="admin-actions" action="member-status.php" method="post">
                        <input type="hidden" name="member_id" value="<?= e($member['id']) ?>" />
                        <button class="btn btn-sm btn-outline-info" type="button" data-open-member-preview
                          data-name="<?= e($member['full_name']) ?>" data-email="<?= e($member['email']) ?>"
                          data-phone="<?= e($member['phone']) ?>" data-student-id="<?= e($member['student_id']) ?>"
                          data-department="<?= e($member['department']) ?>" data-batch="<?= e($member['batch']) ?>"
                          data-skills="<?= e($member['skills']) ?>" data-reason="<?= e($member['reason_for_joining']) ?>"
                          data-status="<?= e($member['status']) ?>" data-photo="<?= e($member['photo_path'] ?? '') ?>"
                          data-submitted="<?= e(date('F j, Y, g:i a', strtotime($member['created_at']))) ?>">View</button>
                        <?php if ($member['status'] === 'pending'): ?>
                          <button class="btn btn-sm btn-success" name="status" value="approved" type="submit">Approve</button>
                          <button class="btn btn-sm btn-danger" name="status" value="rejected" type="submit">Reject</button>
                        <?php endif; ?>
                      </form>
                    </td>
                    <td data-label="Submitted">
                      <small><?= e(date('F j, Y, g:i a', strtotime($member['created_at']))) ?></small></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </section>
      <!-- Events Tab -->
      <section id="events" class="admin-section admin-tab-panel" data-admin-panel="events">
        <div class="admin-section-heading">
          <div>
            <h2 class="admin-section-title">Events</h2>
            <p class="admin-section-subtitle">Manage homepage event cards, dates, locations, and ordering.</p>
          </div>
          <button class="btn btn-primary" type="button" data-open-event-modal data-mode="add">
            Add Event
          </button>
        </div>
        <div class="management-table-wrap">
          <table class="table table-hover align-middle mb-0 management-table">
            <thead>
              <tr>
                <th>Event</th>
                <th>Status</th>
                <th>Date</th>
                <th>Location</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($events as $event): ?>
                <tr>
                  <td data-label="Event">
                    <strong><?= e($event['title']) ?></strong>
                    <small><?= e($event['description']) ?></small>
                  </td>
                  <td data-label="Status"><?= e($event['event_date']) > date('Y-m-d') ? 'Upcoming' : 'Completed' ?></td>
                  <td data-label="Date">
                    <?= e($event['event_date'] ? date('M j, Y', strtotime($event['event_date'])) : 'No date') ?></td>
                  <td data-label="Location"><?= e($event['location']) ?></td>
                  <td data-label="Actions">
                    <div class="row-actions">
                      <button class="btn btn-sm btn-outline-info" type="button" data-open-preview
                        data-title="<?= e($event['title']) ?>"
                        data-kicker="<?= e($event['event_date'] > date('Y-m-d') ? 'Upcoming' : 'Completed') ?>"
                        data-description="<?= e($event['description']) ?>"
                        data-meta="<?= e(($event['event_date'] ? date('M j, Y', strtotime($event['event_date'])) : 'No date') . ' | ' . ($event['location'] ?: 'No location')) ?>">Preview</button>
                      <button class="btn btn-sm btn-outline-secondary" type="button" data-open-event-modal
                        data-mode="edit" data-id="<?= e($event['id']) ?>" data-title="<?= e($event['title']) ?>"
                        data-event-date="<?= e($event['event_date']) ?>"
                        data-description="<?= e($event['description']) ?>" data-location="<?= e($event['location']) ?>"
                        data-location-icon="<?= e($event['location_icon']) ?>"
                        data-sort-order="<?= e($event['sort_order']) ?>">Edit</button>
                      <button class="btn btn-sm btn-outline-danger" type="button" data-open-delete data-type="event"
                        data-id="<?= e($event['id']) ?>" data-title="<?= e($event['title']) ?>">Delete</button>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </section>
      <!-- Projects Tab -->
      <section id="projects" class="admin-section admin-tab-panel" data-admin-panel="projects">
        <div class="admin-section-heading">
          <div>
            <h2 class="admin-section-title">Projects</h2>
            <p class="admin-section-subtitle">Control project title, description, technology tags, and ordering.</p>
          </div>
          <button class="btn btn-primary" type="button" data-open-project-modal data-mode="add">
            Add Project
          </button>
        </div>
        <div class="management-table-wrap">
          <table class="table table-hover align-middle mb-0 management-table">
            <thead>
              <tr>
                <th>Project</th>
                <th>Tags</th>
                <th>Sort</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($projects as $project): ?>
                <tr>
                  <td data-label="Project">
                    <strong><?= e($project['title']) ?></strong>
                    <small><?= e($project['description']) ?></small>
                  </td>
                  <td data-label="Tags"><?= e($project['tags'] ?: 'No tags') ?></td>
                  <td data-label="Sort"><?= e($project['sort_order']) ?></td>
                  <td data-label="Actions">
                    <div class="row-actions">
                      <button class="btn btn-sm btn-outline-info" type="button" data-open-preview
                        data-title="<?= e($project['title']) ?>" data-kicker="<?= e($project['tags'] ?: 'Project') ?>"
                        data-description="<?= e($project['description']) ?>"
                        data-meta="<?= e('Sort ' . $project['sort_order']) ?>">Preview</button>
                      <button class="btn btn-sm btn-outline-secondary" type="button" data-open-project-modal
                        data-mode="edit" data-id="<?= e($project['id']) ?>" data-title="<?= e($project['title']) ?>"
                        data-description="<?= e($project['description']) ?>" data-tags="<?= e($project['tags']) ?>"
                        data-sort-order="<?= e($project['sort_order']) ?>">Edit</button>
                      <button class="btn btn-sm btn-outline-danger" type="button" data-open-delete data-type="project"
                        data-id="<?= e($project['id']) ?>" data-title="<?= e($project['title']) ?>">Delete</button>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </section>
      <!-- Committee Tab -->
      <section id="committee" class="admin-section admin-tab-panel" data-admin-panel="committee">
        <div class="admin-section-heading">
          <div>
            <h2 class="admin-section-title">Committee</h2>
            <p class="admin-section-subtitle">Update organizers, roles, photos, and display order.</p>
          </div>
          <button class="btn btn-primary" type="button" data-open-committee-modal data-mode="add">
            Add Member
          </button>
        </div>
        <div class="management-table-wrap">
          <table class="table table-hover align-middle mb-0 management-table">
            <thead>
              <tr>
                <th>Member</th>
                <th>Role</th>
                <th>Photo</th>
                <th>Sort</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($committee_members as $committee_member): ?>
                <tr>
                  <td data-label="Member">
                    <strong><?= e($committee_member['name']) ?></strong>
                  </td>
                  <td data-label="Role"><?= e($committee_member['role']) ?></td>
                  <td data-label="Photo">
                    <img class="admin-thumb" src="<?= e($committee_member['photo_path'] ?: $avatar_placeholder) ?>"
                      alt="<?= e($committee_member['name']) ?>" onerror="this.onerror=null;this.src='<?= e($avatar_placeholder) ?>';" />
                  </td>
                  <td data-label="Sort"><?= e($committee_member['sort_order']) ?></td>
                  <td data-label="Actions">
                    <div class="row-actions">
                      <button class="btn btn-sm btn-outline-info" type="button" data-open-preview
                        data-title="<?= e($committee_member['name']) ?>" data-kicker="<?= e($committee_member['role']) ?>"
                        data-description=""
                        data-meta="<?= e($committee_member['photo_path'] ?: $avatar_placeholder) ?>"
                        data-image="<?= e($committee_member['photo_path'] ?: $avatar_placeholder) ?>">Preview</button>
                      <button class="btn btn-sm btn-outline-secondary" type="button" data-open-committee-modal data-mode="edit"
                        data-id="<?= e($committee_member['id']) ?>" data-name="<?= e($committee_member['name']) ?>"
                        data-role="<?= e($committee_member['role']) ?>" data-photo-path="<?= e($committee_member['photo_path']) ?>"
                        data-sort-order="<?= e($committee_member['sort_order']) ?>">Edit</button>
                      <button class="btn btn-sm btn-outline-danger" type="button" data-open-delete data-type="committee"
                        data-id="<?= e($committee_member['id']) ?>" data-title="<?= e($committee_member['name']) ?>">Delete</button>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </section>
      <section id="admins" class="admin-section admin-tab-panel" data-admin-panel="admins">
        <div class="admin-section-heading">
          <div>
            <h2 class="admin-section-title">Admin Access</h2>
            <p class="admin-section-subtitle">Assign committee members as admins and revoke access when needed.</p>
          </div>
          <span class="status-pill"><?= e($active_admins) ?> Active</span>
        </div>

        <form class="admin-modal-form admin-inline-form" action="admin-content.php" method="post">
          <input type="hidden" name="type" value="admin" />
          <input type="hidden" name="action" value="save" />
          <div class="admin-form-grid">
            <label>
              Committee Member
              <select class="form-input" name="committee_id" required>
                <option value="">Select committee member</option>
                <?php foreach ($committee_members as $committee_member): ?>
                  <option value="<?= e($committee_member['id']) ?>">
                    <?= e($committee_member['name']) ?> (<?= e($committee_member['role']) ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </label>
            <label>Email<input class="form-input" name="email" type="email" required /></label>
            <label>
              Password
              <div class="password-generate-row">
                <input class="form-input" name="password" type="password" minlength="6" required />
                <button class="btn btn-outline-secondary btn-sm" type="button" data-generate-admin-password>Auto Generate</button>
              </div>
            </label>
          </div>
          <div class="modal-actions pt-0 border-0">
            <button class="btn btn-primary" type="submit">Grant / Update Access</button>
          </div>
        </form>

        <div class="management-table-wrap">
          <table class="table table-hover align-middle mb-0 management-table">
            <thead>
              <tr>
                <th>Admin</th>
                <th>Linked Committee Role</th>
                <th>Status</th>
                <th>Created</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($admin_accounts as $admin_account): ?>
                <tr>
                  <td data-label="Admin">
                    <strong><?= e($admin_account['name']) ?></strong>
                    <small><?= e($admin_account['email']) ?></small>
                  </td>
                  <td data-label="Linked Committee Role">
                    <?php if (!empty($admin_account['committee_name'])): ?>
                      <?= e($admin_account['committee_name']) ?><br />
                      <small><?= e($admin_account['committee_role']) ?></small>
                    <?php else: ?>
                      <small>Direct admin account</small>
                    <?php endif; ?>
                  </td>
                  <td data-label="Status">
                    <span class="<?= e((int) $admin_account['is_active'] === 1 ? 'status-pill status-pill-success' : 'status-pill status-pill-danger') ?>">
                      <?= e((int) $admin_account['is_active'] === 1 ? 'Active' : 'Revoked') ?>
                    </span>
                  </td>
                  <td data-label="Created">
                    <small><?= e(date('F j, Y, g:i a', strtotime($admin_account['created_at']))) ?></small>
                  </td>
                  <td data-label="Actions">
                    <div class="row-actions">
                      <button class="btn btn-sm btn-outline-info" type="button" data-open-preview
                        data-title="<?= e($admin_account['name']) ?>"
                        data-kicker="<?= e((int) $admin_account['is_active'] === 1 ? 'Active Admin' : 'Revoked Admin') ?>"
                        data-description="<?= e('Email: ' . $admin_account['email']) ?>"
                        data-meta="<?= e(!empty($admin_account['committee_name']) ? ($admin_account['committee_name'] . ' | ' . $admin_account['committee_role']) : 'Direct admin account') ?>">Preview</button>
                      <?php if ((int) $admin_account['id'] === (int) $_SESSION['admin_id']): ?>
                        <span class="status-pill status-pill-info">Current account</span>
                      <?php elseif ((int) $admin_account['is_active'] === 1): ?>
                        <form action="admin-content.php" method="post">
                          <input type="hidden" name="type" value="admin" />
                          <input type="hidden" name="action" value="revoke" />
                          <input type="hidden" name="id" value="<?= e($admin_account['id']) ?>" />
                          <button class="btn btn-sm btn-outline-danger" type="submit">Revoke</button>
                        </form>
                      <?php else: ?>
                        <form action="admin-content.php" method="post">
                          <input type="hidden" name="type" value="admin" />
                          <input type="hidden" name="action" value="restore" />
                          <input type="hidden" name="id" value="<?= e($admin_account['id']) ?>" />
                          <button class="btn btn-sm btn-outline-secondary" type="submit">Restore</button>
                        </form>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </section>
    </div>
  </main>
  <!--Event Edit Popup-->
  <div class="admin-modal" id="event-modal" aria-hidden="true">
    <div class="admin-modal-panel" role="dialog" aria-modal="true" aria-labelledby="event-modal-title">
      <div class="admin-modal-header">
        <h3 id="event-modal-title">Event</h3>
        <button class="modal-close" type="button" data-close-modal>&times;</button>
      </div>
      <form class="admin-modal-form" action="admin-content.php" method="post">
        <input type="hidden" name="type" value="event" />
        <input type="hidden" name="action" value="save" />
        <input type="hidden" name="id" />
        <div class="admin-form-grid">
          <label>Title<input class="form-input" name="title" required /></label>
          <label>Date<input class="form-input" name="event_date" type="date" /></label>
          <label>Location<input class="form-input" name="location" /></label>
          <label>
            Icon
            <select class="form-input" name="location_icon">
              <option value="location_on">Location</option>
              <option value="history">History</option>
              <option value="event">Event</option>
              <option value="school">School</option>
              <option value="groups">Groups</option>
              <option value="code">Code</option>
            </select>
          </label>
          <label>Sort<input class="form-input" name="sort_order" type="number" /></label>
        </div>
        <label>Description<textarea class="form-input form-textarea" name="description" rows="4"></textarea></label>
        <div class="modal-actions">
          <button class="btn btn-outline-secondary" type="button" data-close-modal>Cancel</button>
          <button class="btn btn-primary" type="submit">Save</button>
        </div>
      </form>
    </div>
  </div>
  <!--Project Edit Popup-->
  <div class="admin-modal" id="project-modal" aria-hidden="true">
    <div class="admin-modal-panel" role="dialog" aria-modal="true" aria-labelledby="project-modal-title">
      <div class="admin-modal-header">
        <h3 id="project-modal-title">Project</h3>
        <button class="modal-close" type="button" data-close-modal>&times;</button>
      </div>
      <form class="admin-modal-form" action="admin-content.php" method="post">
        <input type="hidden" name="type" value="project" />
        <input type="hidden" name="action" value="save" />
        <input type="hidden" name="id" />
        <div class="admin-form-grid">
          <label>Title<input class="form-input" name="title" required /></label>
          <label>Tags<input class="form-input" name="tags" /></label>
          <label>Sort<input class="form-input" name="sort_order" type="number" /></label>
        </div>
        <label>Description<textarea class="form-input form-textarea" name="description" rows="4"></textarea></label>
        <div class="modal-actions">
          <button class="btn btn-outline-secondary" type="button" data-close-modal>Cancel</button>
          <button class="btn btn-primary" type="submit">Save</button>
        </div>
      </form>
    </div>
  </div>
  <!--Committee Member Edit Popup-->
  <div class="admin-modal" id="committee-modal" aria-hidden="true">
    <div class="admin-modal-panel" role="dialog" aria-modal="true" aria-labelledby="committee-modal-title">
      <div class="admin-modal-header">
        <h3 id="committee-modal-title">Committee Member</h3>
        <button class="modal-close" type="button" data-close-modal>&times;</button>
      </div>
      <form class="admin-modal-form" action="admin-content.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="type" value="committee" />
        <input type="hidden" name="action" value="save" />
        <input type="hidden" name="id" />
        <div class="admin-form-grid">
          <label>Name<input class="form-input" name="name" required /></label>
          <label>Role<input class="form-input" name="role" required /></label>
              </br>
          <!-- <label>Photo path<input class="form-input" name="photo_path" /></label> -->
          <label>Upload image<input class="form-input" name="committee_image" type="file"
              accept="image/jpeg,image/png,image/webp,image/gif" /></label>
          <label>Sort<input class="form-input" name="sort_order" type="number" /></label>
        </div>
        <div class="current-image-preview" id="committee-current-image-wrap" hidden>
          <span>Current image</span>
          <img id="committee-current-image" alt="Current Committee Member image" onerror="this.onerror=null;this.src='<?= e($avatar_placeholder) ?>';" />
        </div>
        <div class="modal-actions">
          <button class="btn btn-outline-secondary" type="button" data-close-modal>Cancel</button>
          <button class="btn btn-primary" type="submit">Save</button>
        </div>
      </form>
    </div>
  </div>
  <!--Preview Modal-->
  <div class="admin-modal" id="preview-modal" aria-hidden="true">
    <div class="admin-modal-panel" role="dialog" aria-modal="true" aria-labelledby="preview-title">
      <div class="admin-modal-header">
        <h3 id="preview-title">Preview</h3>
        <button class="modal-close" type="button" data-close-modal>&times;</button>
      </div>
      <div class="preview-card">
        <img class="preview-image" id="preview-image" alt="" />
        <span id="preview-kicker"></span>
        <h4 id="preview-heading"></h4>
        <p id="preview-description"></p>
        <small id="preview-meta"></small>
      </div>
    </div>
  </div>
  <!--Member Form Preview Modal-->
  <div class="admin-modal" id="member-preview-modal" aria-hidden="true">
    <div class="admin-modal-panel member-preview-panel" role="dialog" aria-modal="true"
      aria-labelledby="member-preview-title">
      <div class="admin-modal-header">
        <h3 id="member-preview-title">Member Application</h3>
        <button class="modal-close" type="button" data-close-modal>&times;</button>
      </div>
      <div class="member-preview">
        <div class="member-preview-header">
          <img id="member-preview-photo" class="member-preview-photo" alt="" hidden />
          <div class="member-preview-avatar" id="member-preview-avatar"></div>
          <div>
            <h4 id="member-preview-name"></h4>
            <p id="member-preview-email"></p>
            <span class="status-pill" id="member-preview-status"></span>
          </div>
        </div>
        <dl class="member-detail-grid">
          <div>
            <dt>Phone</dt>
            <dd id="member-preview-phone"></dd>
          </div>
          <div>
            <dt>Student ID</dt>
            <dd id="member-preview-student-id"></dd>
          </div>
          <div>
            <dt>Department</dt>
            <dd id="member-preview-department"></dd>
          </div>
          <div>
            <dt>Batch</dt>
            <dd id="member-preview-batch"></dd>
          </div>
          <div>
            <dt>Submitted</dt>
            <dd id="member-preview-submitted"></dd>
          </div>
        </dl>
        <div class="member-detail-block">
          <h5>Skills</h5>
          <p id="member-preview-skills"></p>
        </div>
        <div class="member-detail-block">
          <h5>Reason for joining</h5>
          <p id="member-preview-reason"></p>
        </div>
      </div>
    </div>
  </div>
  <!--Delete Confirmation Modal-->
  <div class="admin-modal" id="delete-modal" aria-hidden="true">
    <div class="admin-modal-panel admin-modal-panel-small" role="dialog" aria-modal="true"
      aria-labelledby="delete-title">
      <div class="admin-modal-header">
        <h3 id="delete-title">Delete Record</h3>
        <button class="modal-close" type="button" data-close-modal>&times;</button>
      </div>
      <p class="admin-section-subtitle" id="delete-copy"></p>
      <form action="admin-content.php" method="post">
        <input type="hidden" name="type" />
        <input type="hidden" name="action" value="delete" />
        <input type="hidden" name="id" />
        <div class="modal-actions">
          <button class="btn btn-outline-secondary" type="button" data-close-modal>Cancel</button>
          <button class="btn btn-danger" type="submit">Delete</button>
        </div>
      </form>
    </div>
  </div>

  <script src="assets/js/admin-dashboard.js"></script>
</body>

</html>


