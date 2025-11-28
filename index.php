<?php
require_once "db_config.php";

$totalGuests = 0;

try {
    $pdo = get_pdo();
    $stmt = $pdo->query("SELECT COALESCE(SUM(guests), 0) AS total FROM rsvps");
    $row = $stmt->fetch();
    if ($row) {
        $totalGuests = (int)$row["total"];
    }
} catch (Exception $e) {
    // Optional: log the error, but don't break the page
    // error_log("RSVP counter error: " . $e->getMessage());
    $totalGuests = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Welcome Back To The Stage Of History</title>
  <!-- Site icon -->
  <link rel="icon" type="image/jpeg" href="/soul_edge_transparent.png" />
  <link rel="shortcut icon" type="image/jpeg" href="/soul_edge_transparent.png" />
  <style>
    body {
      font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      margin: 0;
      padding: 0;
      background: #f5f5f7;
      color: #222;
    }

    /* Navigation bar */
    nav {
      background: #000;
      color: #fff;
      padding: 0.5rem 1rem;
    }

    .nav-inner {
      max-width: 800px;
      margin: 0 auto;
      display: flex;
      align-items: center;
      gap: 1rem;
    }

    .nav-left {
      display: flex;
      gap: 1rem;
    }

    nav a {
      color: #ddd;
      text-decoration: none;
      font-size: 0.9rem;
    }

    nav a:hover {
      color: #fff;
      text-decoration: underline;
    }

    .nav-admin-link {
      margin-left: auto;
      font-size: 0.8rem;
      color: #4b5563; /* subtle dark gray/blue */
    }

    .nav-admin-link:hover {
      color: #e5e7eb;
    }

    header {
      background: #111;
      color: #fff;
      text-align: center;
    }

    header img {
      max-width: 100%;
      height: auto;
      display: block;
      margin: 0 auto;
    }

    .container {
      max-width: 800px;
      margin: 0 auto;
      padding: 1.5rem;
    }

    .content {
      background: #ffffff;
      padding: 1.5rem;
      border-radius: 1rem;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.06);
      margin-bottom: 1.5rem;
    }

    h1, h2 {
      margin-top: 0;
      letter-spacing: 0.03em;
    }

    .rsvp-card {
      background: #ffffff;
      padding: 1.5rem;
      border-radius: 1rem;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.06);
    }

    form {
      display: grid;
      gap: 1rem;
    }

    label {
      display: block;
      font-weight: 600;
      margin-bottom: 0.3rem;
    }

    input[type="text"],
    input[type="email"],
    input[type="number"] {
      width: 100%;
      padding: 0.6rem 0.8rem;
      border-radius: 0.6rem;
      border: 1px solid #ccc;
      font-size: 1rem;
      box-sizing: border-box;
    }

    textarea {
      width: 100%;
      padding: 0.6rem 0.8rem;
      border-radius: 0.6rem;
      border: 1px solid #ccc;
      font-size: 1rem;
      box-sizing: border-box;
      min-height: 80px;
      resize: vertical;
    }

    input[type="number"] {
      max-width: 150px;
    }

    button[type="submit"] {
      padding: 0.8rem 1.4rem;
      border-radius: 999px;
      border: none;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      background: #111827;
      color: #ffffff;
      align-self: start;
      transition: transform 0.05s ease-out, box-shadow 0.05s ease-out, background 0.15s ease-out;
      box-shadow: 0 10px 20px rgba(15, 23, 42, 0.25);
    }

    button[type="submit"]:hover {
      background: #020617;
      transform: translateY(-1px);
      box-shadow: 0 14px 24px rgba(15, 23, 42, 0.3);
    }

    button[type="submit"]:active {
      transform: translateY(0);
      box-shadow: 0 6px 14px rgba(15, 23, 42, 0.2);
    }

    .help-text {
      font-size: 0.9rem;
      color: #555;
    }

    footer {
      background: #111;
      text-align: center;
      font-size: 0.8rem;
      color: #777;
      padding: 1.5rem 0;
    }

    .lower-bg {
      background: url('/soulcalibur_big.jpg') no-repeat center top;
      background-size: cover;
      /* padding here makes sure the background shows above/below the cards */
      padding: 2rem 0;
    }

    .section-bg {
      /*background: url('/metallic-background-with-grunge-scratched-effect.jpg') no-repeat center top;
      background-size: cover;*/
    }

  </style>
</head>
<body>
  <!-- Navigation bar -->
  <nav>
    <div class="nav-inner">
      <div class="nav-left">
        <a href="/index.php">Home</a>
        <a href="/thestageofhistory.php">The Stage Of History</a>
      </div>
      <a href="/admin_login.php" class="nav-admin-link">Admin</a>
    </div>
  </nav>

  <header>
    <img src="wbttsohX.png" alt="Event banner" />
  </header>

  <div class="lower-bg">
    <main class="container">
      <section class="content">
        <div class="section-bg">
          <h1>Welcome Back To The Stage Of History</h1>
          <p>
            As another year is nearing its close, another Soul Calibur tournament appears against the dark horizon of winter. This will be our 10th(!) annual event, and we invite you to gather with us in celebration of this monument to competition and costumery we've been building together all this time.
          </p>
          <p>
            <b>Saturday December 13th. 4PM to 10PM (or whenever).</b>
          </p>
          <p>
            We will provide food (pizza!) and various alcoholic (beer!) and non-alcoholic drinks.
          </p>

          <p>
            <i>Note to other parents: Harlan will be sleeping over at his grandparents' house that night, so we're not planning any kid-specific activities (no, videogames are very grown up).</i>
          </p>

          <p>
            <strong>Guests confirmed so far:</strong>
            <?php echo $totalGuests; ?>
          </p>
        </div>
      </section>
      <section class="rsvp-card" id="rsvp">
        <div class="section-bg">
          <h2>RSVP</h2>
          <p class="help-text">
            Please enter your name and email address so we can confirm you're coming.
          </p>

          <form action="rsvp.php" method="post" novalidate>
            <div>
              <label for="name">First and Last Name (just one person)</label>
              <input
                type="text"
                id="name"
                name="name"
                required
                autocomplete="name"
                placeholder="e.g., Edge Master"
              />
            </div>

            <div>
              <label for="email">Email</label>
              <input
                type="email"
                id="email"
                name="email"
                required
                autocomplete="email"
                placeholder="you@example.com"
              />
            </div>

            <div>
              <label for="guests">Number of guests (including you)</label>
              <input
                type="number"
                id="guests"
                name="guests"
                min="1"
                step="1"
                required
                placeholder="1"
                title="Hint: If you can't come, update your RSVP with 0 entered here!"
              />
            </div>

            <div>
              <label for="message">Message (optional)</label>
              <textarea
                id="message"
                name="message"
                rows="3"
                placeholder="Anything you'd like us to know (timing, etc.)"
              ></textarea>
            </div>

            <button type="submit">Submit/Update RSVP</button>
          </form>
        </div>
      </section>
    </main>
  </div>

<!--   <footer>
    &copy; <span id="year"></span> Welcome Back To
  </footer>

  <script>
    document.getElementById('year').textContent = new Date().getFullYear();
  </script> -->
</body>
</html>
