<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/upload-utils.php';
require_once __DIR__ . '/content-data.php';
function redirect_with_message($url, $message)
{
  header('Location: ' . $url . '?message=' . urlencode($message));
  exit;
}

$page_message = trim($_GET['message'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $full_name = trim($_POST['full_name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $phone = trim($_POST['phone'] ?? '');
  $student_id = trim($_POST['student_id'] ?? '');
  $department = trim($_POST['department'] ?? '');
  $batch = trim($_POST['batch'] ?? '');
  $skills = trim($_POST['skills'] ?? '');
  $reason_for_joining = trim($_POST['reason_for_joining'] ?? '');
  $photo_path = null;

  if (
    $full_name === '' ||
    $email === '' ||
    $phone === '' ||
    $student_id === '' ||
    $department === '' ||
    $batch === '' ||
    empty($_FILES['member_image']) ||
    ($_FILES['member_image']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE
  ) {
    redirect_with_message('signup.php', 'All fields are required.');
  }

  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirect_with_message('signup.php', 'Please enter a valid email address.');
  }

  try {
    $member_image_name = $student_id !== ''
      ? $student_id
      : trim($full_name . '-' . $batch);
    $photo_path = save_uploaded_image('member_image', 'members', $member_image_name);

    $stmt = $pdo->prepare(
      'INSERT INTO members
                (full_name, email, phone, student_id, department, batch, photo_path, skills, reason_for_joining)
             VALUES
                (:full_name, :email, :phone, :student_id, :department, :batch, :photo_path, :skills, :reason_for_joining)'
    );
    $stmt->execute([
      ':full_name' => $full_name,
      ':email' => $email,
      ':phone' => $phone ?: null,
      ':student_id' => $student_id ?: null,
      ':department' => $department ?: null,
      ':batch' => $batch ?: null,
      ':photo_path' => $photo_path,
      ':skills' => $skills ?: null,
      ':reason_for_joining' => $reason_for_joining ?: null,
    ]);
  } catch (RuntimeException $e) {
    redirect_with_message('signup.php', $e->getMessage());
  } catch (PDOException $e) {
    if (is_missing_table_error($e)) {
      redirect_with_message('signup.php', 'Database tables are missing. Run setup first.');
    }

    if ($e->getCode() === '23000') {
      redirect_with_message('signup.php', 'Email or student ID already registered.');
    }

    redirect_with_message('signup.php', 'Registration failed. Please try again.');
  }

  redirect_with_message('signup.php', 'Registration successful. Admin will review your application.');
}
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta content="width=device-width, initial-scale=1.0" name="viewport" />
  <title>Bit2Byte | Sign Up</title>
  <link rel="preload" as="style"
    href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=block" />
  <link
    href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600&display=swap"
    rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=block"
    rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous" />
  <link rel="stylesheet" href="assets/css/common.css" />
  <link rel="stylesheet" href="assets/css/auth.css" />
</head>

<body class="overflow-auto">
  <div class="bg-overlay grid-pattern"></div>
  <div class="bg-overlay dot-pattern"></div>
  <div class="glow-spot"></div>

  <main class="container-fluid min-vh-100 d-flex flex-column justify-content-center align-items-center py-4 py-md-5">
    <a href="index.php" class="brand mb-4 mb-md-5" title="Return to Core System">
      <img src="assets/main-logo.png" alt="Bit2Byte Logo" class="brand-logo" />
      <h1 class="brand-text">Bit<span>2</span>Byte</h1>
    </a>

    <div class="row w-100 justify-content-center m-0">
      <div class="col-12 col-sm-11 col-md-9 col-lg-7 col-xl-5 px-0">
        <div class="auth-card mx-auto w-100">
          <div class="corner corner-tl"></div>
          <div class="corner corner-tr"></div>
          <div class="corner corner-bl"></div>
          <div class="corner corner-br"></div>

          <div class="tabs d-flex justify-content-between justify-content-sm-start">
            <a href="login.php" class="tab-btn">Admin Login</a>
            <a href="signup.php" class="tab-btn active">Member Form</a>
          </div>
          <?php if ($page_message !== ''): ?>
            <div class="form-message" role="status">
              <?= e($page_message) ?>
            </div>
          <?php endif; ?>

          <form id="registerForm" class="auth-form needs-validation" action="signup.php" method="post"
            enctype="multipart/form-data">
            <div class="form-group">
              <label class="form-label">Full Name</label>
              <div class="input-wrapper">
                <i class="material-symbols-outlined input-icon">face</i>
                <input class="form-input" name="full_name" placeholder="Enter your full name" type="text"
                  autocomplete="name" required />
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Email Address</label>
              <div class="input-wrapper">
                <i class="material-symbols-outlined input-icon">alternate_email</i>
                <input class="form-input" name="email" placeholder="Enter your email" type="email" autocomplete="email"
                  required />
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Phone</label>
              <div class="input-wrapper">
                <i class="material-symbols-outlined input-icon">call</i>
                <input class="form-input" name="phone" placeholder="Enter your phone" type="tel" autocomplete="tel"
                  required />
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Student ID</label>
              <div class="input-wrapper">
                <i class="material-symbols-outlined input-icon">badge</i>
                <input class="form-input" name="student_id" placeholder="Enter student ID" type="text" required />
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Department</label>
              <div class="input-wrapper">
                <i class="material-symbols-outlined input-icon">school</i>
                <input class="form-input" name="department" placeholder="Enter department" type="text" required />
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Batch</label>
              <div class="input-wrapper">
                <i class="material-symbols-outlined input-icon">groups</i>
                <input class="form-input" name="batch" placeholder="Enter batch" type="text" required />
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Profile Image</label>
              <div class="input-wrapper">
                <i class="material-symbols-outlined input-icon">image</i>
                <input class="form-input" id="member-image-input" name="member_image" type="file"
                  accept="image/jpeg,image/png,image/webp,image/gif" required />
              </div>
              <div class="upload-preview" id="member-image-preview-wrap" hidden>
                <img id="member-image-preview" alt="Selected profile image preview" />
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Skills</label>
              <div class="input-wrapper">
                <i class="material-symbols-outlined input-icon">code</i>
                <textarea class="form-input form-textarea" name="skills" placeholder="Your skills" rows="3"></textarea>
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Reason For Joining</label>
              <div class="input-wrapper">
                <i class="material-symbols-outlined input-icon">edit_note</i>
                <textarea class="form-input form-textarea" name="reason_for_joining" placeholder="Why join Bit2Byte?"
                  rows="3"></textarea>
              </div>
            </div>
            <p class="policy">
              Member application only. Admin will review and contact you.
            </p>
            <button type="submit" class="btn-submit">
              <span class="btn-text">Submit</span>
              <div class="btn-overlay"></div>
            </button>
          </form>
        </div>
      </div>
    </div>

    <p class="auth-footer">© <span id="current-year">2026</span> Bit2Byte</p>
  </main>

  <script>
    window.addEventListener("load", () => {
      document.getElementById("current-year").textContent =
        new Date().getFullYear();
    });

    document
      .getElementById("member-image-input")
      .addEventListener("change", (event) => {
        const file = event.target.files[0];
        const previewWrap = document.getElementById(
          "member-image-preview-wrap"
        );
        const preview = document.getElementById("member-image-preview");

        if (!file) {
          previewWrap.hidden = true;
          preview.removeAttribute("src");
          return;
        }

        preview.src = URL.createObjectURL(file);
        previewWrap.hidden = false;
      });
    document.addEventListener("DOMContentLoaded", function () {
      const params = new URLSearchParams(window.location.search);
      const message = params.get("message");
      if (message) {
        alert(message);
      }
    });

  </script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
    crossorigin="anonymous"></script>
</body>

</html>