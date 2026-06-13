<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/content-data.php';
require_once __DIR__ . '/auth.php';
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
bootstrap_admin_session($pdo);
/*
|--------------------------------------------------------------------------
| Optional automatic database setup
|--------------------------------------------------------------------------
| Keep this commented out during normal use. If you want the homepage to
| automatically create missing tables and seed starter data, uncomment this
| block, load the homepage once, then comment it again.
|
| require_once __DIR__ . '/setup-data.php';
| run_schema_file($pdo);
| seed_all_data($pdo);
*/
// require_once __DIR__ . '/setup-data.php';
// run_schema_file($pdo);
// seed_all_data($pdo);
$events = all_events($pdo);
$projects = all_projects($pdo);
$committee_members = array_slice(all_committee_members($pdo), 0, 4);
$avatar_placeholder = 'assets/avatar.jpg';

?>
<!doctype html>

<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Bit2Byte | University Software Development Club</title>
    <link
      href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&amp;family=Inter:wght@300;400;500;600&amp;display=swap"
      rel="stylesheet"
    />
    <link
      href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=block"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="assets/css/common.css">
    <link rel="stylesheet" href="assets/css/style.css">
  </head>
  <body>
    <header class="site-header">
      <nav class="site-nav">
        <a href="#home" class="brand-link">
          <img src="assets/main-logo.png" alt="Bit2Byte Logo" />
          <span>Bit2Byte</span>
        </a>
        <div class="nav-links">
          <a class="nav-link-hover" href="#home">Home</a>
          <a class="nav-link-hover" href="#about">About</a>
          <a class="nav-link-hover" href="#events">Events</a>
          <a class="nav-link-hover" href="#projects">Projects</a>
          <a class="nav-link-hover" href="#committee">Committee</a>
        </div>
        <?php if (empty($_SESSION['admin_id'])): ?>
          <a href="login.php" class="join-link">
            JOIN_SYSTEM
          </a>
        <?php else: ?>
          <a href="admin-dashboard.php" class="join-link">
            ADMIN_DASHBOARD
          </a>
        <?php endif; ?>
      </nav>
    </header>
    <main>
      <section class="hero-section" id="home">
        <div class="hero-bg grid-pattern"></div>
        <div class="hero-bg dot-pattern"></div>
        <div class="hero-glow"></div>
        <div class="hero-content">
          <div class="status-pill">
            <span class="status-dot"></span>
            <span class="status-text">System.Status: Active</span>
          </div>
          <h1 class="hero-title text-glow">
            Bit<span>2</span>Byte
          </h1>
          <p class="hero-copy cursor-blink">
            Architecting the next generation of digital infrastructure.
          </p>
          <div class="hero-actions">
            <button class="hero-button hero-button-primary">
              <span>INITIALIZE_EXPLORATION</span>
            </button>
            <button class="hero-button hero-button-secondary">
              &gt; VIEW_CHANGELOG
            </button>
          </div>
          <div class="stats-grid">
            <div class="stat-item">
              <div class="stat-value">500+</div>
              <div class="stat-label">Active_Nodes</div>
            </div>
            <div class="stat-item">
              <div class="stat-value">20+</div>
              <div class="stat-label">Protocols_Deployed</div>
            </div>
            <div class="stat-item">
              <div class="stat-value">15+</div>
              <div class="stat-label">Cycles_Quarterly</div>
            </div>
          </div>
        </div>
        <div class="hero-scroll">
          <span class="material-symbols-outlined">expand_more</span>
        </div>
      </section>
      <section class="site-section site-section--muted" id="about">
        <div class="section-inner split-layout">
          <div>
            <span class="section-kicker">About the club</span>
            <h2 class="section-title">A practical software community on campus.</h2>
            <p class="section-lede">
              Bit2Byte helps students move from classroom concepts to reliable
              software. Members learn through workshops, guided projects, peer
              review, and events that reflect how modern engineering teams
              actually work.
            </p>
          </div>

          <div>
            <article class="about-panel">
              <h3>What we focus on</h3>
              <p>
                We build useful tools, practice clean engineering habits, and
                create space for students to collaborate across skill levels.
              </p>
            </article>
            <article class="about-panel">
              <h3>How members grow</h3>
              <p>
                New members get mentorship and structured learning paths, while
                experienced members lead sessions, review code, and ship club
                initiatives.
              </p>
            </article>
          </div>
        </div>

        <div class="section-inner capability-grid capability-grid--offset">
          <article class="capability-card">
            <span class="material-symbols-outlined capability-icon">groups</span>
            <h3>Peer learning</h3>
            <p>
              Weekly study circles and review sessions keep progress consistent
              without making learning feel isolated.
            </p>
          </article>
          <article class="capability-card">
            <span class="material-symbols-outlined capability-icon">code_blocks</span>
            <h3>Project practice</h3>
            <p>
              Members work on real applications, documentation, deployment, and
              maintenance rather than only small exercises.
            </p>
          </article>
          <article class="capability-card">
            <span class="material-symbols-outlined capability-icon">school</span>
            <h3>Workshops</h3>
            <p>
              Focused sessions cover frontend, backend, databases, cloud tools,
              security basics, and career-ready engineering habits.
            </p>
          </article>
          <article class="capability-card">
            <span class="material-symbols-outlined capability-icon">emoji_events</span>
            <h3>Competitions</h3>
            <p>
              Hackathons and coding contests give members a clear reason to
              practice problem solving, teamwork, and presentation.
            </p>
          </article>
        </div>
      </section>
      <section class="site-section" id="events">
        <div class="section-inner">
          <div class="section-heading-row">
            <div>
              <span class="section-kicker">Events</span>
              <h2 class="section-title">Learning by building together.</h2>
            </div>
            <a class="section-action" href="#events">
              View all events
              <span class="material-symbols-outlined">arrow_right_alt</span>
            </a>
          </div>

          <div class="event-list">
            <?php foreach ($events as $event): ?>
              <article class="event-card">
                <div class="event-meta">
                  <span class="event-status"><?= e($event['event_date']) > date('Y-m-d') ? 'Upcoming' : 'Completed' ?></span>
                  <?php if ($event['event_date']): ?>
                    <time datetime="<?= e($event['event_date']) ?>"><?= e(date('M d, Y', strtotime($event['event_date']))) ?></time>
                  <?php endif; ?>
                </div>
                <h3><?= e($event['title']) ?></h3>
                <p><?= e($event['description']) ?></p>
                <div class="event-location">
                  <span class="material-symbols-outlined"><?= e($event['location_icon']) ?></span>
                  <?= e($event['location']) ?>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        </div>
      </section>
      <section class="site-section site-section--deep" id="projects">
        <div class="section-inner">
          <span class="section-kicker">Projects</span>
          <h2 class="section-title">Student-built work with a real purpose.</h2>
          <p class="section-lede">
            Club projects are selected for usefulness, maintainability, and
            learning value. Every project has room for designers, developers,
            testers, writers, and coordinators.
          </p>

          <div class="project-grid">
            <?php foreach ($projects as $project): ?>
              <article class="project-card">
                <h3><?= e($project['title']) ?></h3>
                <p><?= e($project['description']) ?></p>
                <?php
                  $tags = array_filter(array_map('trim', explode(',', (string) $project['tags'])));
                ?>
                <?php if ($tags): ?>
                  <ul class="project-tags" aria-label="Project technologies">
                    <?php foreach ($tags as $tag): ?>
                      <li><?= e($tag) ?></li>
                    <?php endforeach; ?>
                  </ul>
                <?php endif; ?>
              </article>
            <?php endforeach; ?>
          </div>
        </div>
      </section>
      <section class="site-section site-section--muted" id="committee">
        <div class="section-inner">
          <span class="section-kicker">Committee</span>
          <h2 class="section-title">People who keep the club moving.</h2>
          <p class="section-lede">
            The organizing committee plans learning programs, reviews member work,
            coordinates events, and keeps the club welcoming for students at
            every stage.
          </p>

          <div class="committee-grid">
            <?php foreach ($committee_members as $committee_member): ?>
              <article class="committee-card">
                <div class="committee-photo">
                  <img src="<?= e($committee_member['photo_path'] ?: $avatar_placeholder) ?>" alt="<?= e($committee_member['name']) ?>" onerror="this.onerror=null;this.src='<?= e($avatar_placeholder) ?>';" />
                </div>
                <h3><?= e($committee_member['name']) ?></h3>
                <div class="committee-role"><?= e($committee_member['role']) ?></div>
              </article>
            <?php endforeach; ?>
          </div>
        </div>
      </section>
    </main>
    <footer class="site-footer">
      <div class="footer-inner">
        <div class="footer-brand">
          <img src="assets/main-logo.png" alt="Bit2Byte Logo" />
          <div>
            <strong>Bit2Byte</strong>
            <span class="footer-copy">&copy; <span id="current-year">2024</span> Bit2Byte Club.</span>
          </div>
        </div>
        <div class="footer-links">
          <a href="#">GitHub</a>
          <a href="#">Discord</a>
          <a href="#">Facebook</a>
          <a href="login.php">Join</a>
        </div>
      </div>
    </footer>
    <script>
      const sections = document.querySelectorAll("section[id]");
      const navLinks = document.querySelectorAll(".nav-links a");

      const handleScroll = () => {
        let current = "";
        sections.forEach((section) => {
          const sectionTop = section.offsetTop;
          const sectionHeight = section.clientHeight;
          if (pageYOffset >= sectionTop - 150) {
            current = section.getAttribute("id");
          }
        });

        navLinks.forEach((link) => {
          link.classList.remove("is-active");
          if (link.getAttribute("href") === `#${current}`) {
            link.classList.add("is-active");
          }
        });
      };

      window.addEventListener("scroll", handleScroll);
      window.addEventListener("load", () => {
        handleScroll();
        document.getElementById("current-year").textContent =
          new Date().getFullYear();
      });
    </script>
  </body>
</html>


