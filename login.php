<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

function redirect_with_message($url, $message) {
    header('Location: ' . $url . '?message=' . urlencode($message));
    exit;
}

function redirect_logged_in_admin(PDO $pdo) {
    if (bootstrap_admin_session($pdo)) {
        header('Location: admin-dashboard.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        redirect_with_message('login.php', 'Email and password required.');
    }

    try {
        $stmt = $pdo->prepare('SELECT id, name, email, password_hash FROM admins WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $admin = $stmt->fetch();
    } catch (PDOException $e) {
        if (!is_missing_table_error($e)) {
            throw $e;
        }
        redirect_with_message('login.php', 'Database tables are missing. Run setup first.');
    }

    if ($admin && password_verify($password, $admin['password_hash'])) {
        $remember_me = !empty($_POST['remember_me']);
        login_admin($pdo, $admin, $remember_me);

        header('Location: admin-dashboard.php');
        exit;
    }

    redirect_with_message('login.php', 'Invalid credentials.');
}

redirect_logged_in_admin($pdo);
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Bit2Byte | Login</title>
    <link
      rel="preload"
      as="style"
      href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=block"
    />
    <link
      href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600&display=swap"
      rel="stylesheet"
    />
    <link
      href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=block"
      rel="stylesheet"
    />
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
      rel="stylesheet"
      integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
      crossorigin="anonymous"
    />
    <link rel="stylesheet" href="assets/css/common.css" />
    <link rel="stylesheet" href="assets/css/auth.css" />
  </head>
  <body class="overflow-auto">
    <div class="bg-overlay grid-pattern"></div>
    <div class="bg-overlay dot-pattern"></div>
    <div class="glow-spot"></div>

    <main
      class="container-fluid min-vh-100 d-flex flex-column justify-content-center align-items-center py-4 py-md-5"
    >
      <a
        href="index.php"
        class="brand mb-4 mb-md-5"
        title="Return to Core System"
      >
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

            <div
              class="tabs d-flex justify-content-between justify-content-sm-start"
            >
              <a href="login.php" class="tab-btn active">Admin Login</a>
              <a href="signup.php" class="tab-btn">Member Form</a>
            </div>

            <form
              id="loginForm"
              class="auth-form needs-validation"
              action="login.php"
              method="post"
              novalidate
            >
              <div class="form-group">
                <label class="form-label">Email Address</label>
                <div class="input-wrapper">
                  <i class="material-symbols-outlined input-icon"
                    >alternate_email</i
                  >
                  <input
                    class="form-input"
                    name="email"
                    placeholder="Enter your email"
                    type="email"
                    required
                  />
                </div>
              </div>
              <div class="form-group">
                <label class="form-label">Password</label>
                <div class="input-wrapper position-relative">
                  <i class="material-symbols-outlined input-icon">lock</i>
                  <input
                    class="form-input"
                    id="login-password"
                    name="password"
                    placeholder="Enter your password"
                    type="password"
                    required
                  />
                  <button
                    type="button"
                    id="toggle-login-password"
                    onclick="show_password()"
                    class="btn btn-sm btn-link p-0 position-absolute end-0 top-50 translate-middle-y me-2"
                    tabindex="-1"
                    style="
                      color: #888;
                      background: none;
                      border: none;
                      outline: none;
                    "
                  >
                    <span class="material-symbols-outlined" id="login-eye-icon"
                      >visibility_off</span
                    >
                  </button>
                </div>
              </div>
              <div
                class="form-options d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-2 gap-sm-0"
              >
                <label class="checkbox-group">
                  <input type="checkbox" name="remember_me" value="1" />
                  Remember me
                </label>
                <span class="checkbox-group">Admin access only</span>
              </div>
              <button type="submit" class="btn-submit">
                <span class="btn-text">Sign In</span>
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
    </script>
    <script
      src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
      integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
      crossorigin="anonymous"
    ></script>
    <script src="assets/js/login.js"></script>
  </body>
</html>
