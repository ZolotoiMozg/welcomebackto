<?php
require_once "db_config.php";
require_once "rsvp_whitelist.php";

// ---------------- CONFIGURATION ----------------

// Where should RSVPs be emailed?
$to   = "stefen.s@gmail.com";
$from = "stefen@welcomebackto.com";

// --------------- HELPER FUNCTIONS ---------------

function in_array_case_insensitive(string $needle, array $haystack): bool {
    $needle = mb_strtolower(trim($needle));
    foreach ($haystack as $item) {
        if ($needle === mb_strtolower(trim($item))) {
            return true;
        }
    }
    return false;
}

// Simple check to avoid header injection via email field
function is_safe_email(string $email): bool {
    return !preg_match("/[\r\n]/", $email);
}

/**
 * Fetch overall guest stats for display.
 *
 * - Ignores rows with guests = 0 (treated as "no longer attending")
 * - Groups by name and sums guests per name
 *
 * @return array{totalGuests:int, guestList:array<int,array{name:string,total_guests:int,first_rsvp:string}>}
 */
function fetch_guest_stats(PDO $pdo): array {
    $results = [
        "totalGuests" => 0,
        "guestList"   => [],
    ];

    // Total guests (sum of all guests across RSVPs where guests > 0)
    $stmt = $pdo->query("SELECT COALESCE(SUM(guests), 0) AS total FROM rsvps WHERE guests > 0");
    $row  = $stmt->fetch();
    $results["totalGuests"] = $row ? (int)$row["total"] : 0;

    // Guest list grouped by name with total guests per name
    $stmt = $pdo->query(
        "SELECT name,
                SUM(guests) AS total_guests,
                MIN(created_at) AS first_rsvp
         FROM rsvps
         WHERE guests > 0
         GROUP BY name
         ORDER BY first_rsvp ASC"
    );
    $results["guestList"] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return $results;
}

// --------------- MAIN FORM HANDLING --------------

$errors    = [];
$success   = false;
$mailError = false;

$isUpdate   = false;
$wasRemoved = false;

$totalGuests = null;
$guestList   = [];

// Default values for redisplay / success page
$name    = "";
$email   = "";
$guests  = "";
$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name    = trim($_POST["name"]    ?? "");
    $email   = trim($_POST["email"]   ?? "");
    $guests  = trim($_POST["guests"]  ?? "");
    $message = trim($_POST["message"] ?? "");

    // Basic validation
    if ($name === "") {
        $errors[] = "Please enter your name.";
    }

    if ($email === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    } elseif (!is_safe_email($email)) {
        $errors[] = "Invalid email format.";
    }

    if ($guests === "" || !ctype_digit($guests) || (int)$guests < 0) {
        $errors[] = "Please enter the number of guests as 0 or a whole number (1 or more).";
    }

    if (empty($errors)) {
        $nameAllowed  = in_array_case_insensitive($name, $whitelistNames);
        $emailAllowed = in_array_case_insensitive($email, $whitelistEmails);

        if ($nameAllowed || $emailAllowed) {
            try {
                $pdo = get_pdo();

                // Check if there's already an RSVP for this email
                $stmt = $pdo->prepare(
                    "SELECT name, email, guests
                     FROM rsvps
                     WHERE email = :email
                     LIMIT 1"
                );
                $stmt->execute([":email" => $email]);
                $existing = $stmt->fetch();

                $newGuestsInt    = (int)$guests;
                $previousGuests  = null;

                if ($existing) {
                    // Update existing RSVP
                    $isUpdate       = true;
                    $previousGuests = (int)$existing["guests"];

                    $stmt = $pdo->prepare(
                        "UPDATE rsvps
                         SET name = :name,
                             guests = :guests,
                             created_at = NOW()
                         WHERE email = :email
                         LIMIT 1"
                    );
                    $stmt->execute([
                        ":name"   => $name,
                        ":guests" => $newGuestsInt,
                        ":email"  => $email,
                    ]);
                } else {
                    // New RSVP (including the case where guests = 0; row is still stored)
                    $stmt = $pdo->prepare(
                        "INSERT INTO rsvps (name, email, guests)
                         VALUES (:name, :email, :guests)"
                    );
                    $stmt->execute([
                        ":name"   => $name,
                        ":email"  => $email,
                        ":guests" => $newGuestsInt,
                    ]);
                }

                // Fetch total guests and guest list for display
                $stats       = fetch_guest_stats($pdo);
                $totalGuests = $stats["totalGuests"];
                $guestList   = $stats["guestList"];

                $success    = true;
                $wasRemoved = ($newGuestsInt === 0);

                // ----- Build and send email -----

                if ($wasRemoved) {
                    // Special subject when guest is no longer able to come
                    $subject = $name . " is no longer able to attend";
                } elseif ($isUpdate && $existing) {
                    $subject = "Updated RSVP from " . $name;
                } else {
                    $subject = "New RSVP from " . $name;
                }

                // Email body
                if ($wasRemoved) {
                    $body = "This guest has indicated they are no longer able to attend.\n\n";
                } elseif ($isUpdate && $existing) {
                    $body = "This is an updated RSVP.\n\n";
                } else {
                    $body = "A new RSVP has been submitted:\n\n";
                }

                $body .= "Name(s):   " . $name   . "\n";
                $body .= "Email:     " . $email  . "\n";
                $body .= "Guests:    " . $guests . "\n";

                if ($isUpdate && $previousGuests !== null && $previousGuests !== $newGuestsInt) {
                    $body .= "Previous number of guests: " . $previousGuests . "\n";
                }

                if ($message !== "") {
                    $body .= "\nMessage from guest:\n" . $message . "\n";
                }

                $body .= "\nSubmitted: " . date("Y-m-d H:i:s") . "\n";

                $headers = "From: " . $from . "\r\n"
                         . "Reply-To: " . $email . "\r\n"
                         . "Content-Type: text/plain; charset=UTF-8\r\n";

                if (!mail($to, $subject, $body, $headers)) {
                    $mailError = true;
                }

            } catch (Exception $e) {
                $errors[] = "Your RSVP couldn't be saved due to a server error. Please contact us directly.";
            }
        } else {
            // Name/email not recognized on whitelist
            $errors[] = "We couldn't find your name or email on the guest list. "
                      . "Please contact us directly so we can update it.";

            // Log failed attempt (best-effort only)
            try {
                // Reuse existing PDO connection if available, otherwise create one
                if (!isset($pdo) || !$pdo) {
                    $pdo = get_pdo();
                }

                if ($pdo) {
                    $log = $pdo->prepare("
                        INSERT INTO failed_rsvps (name, email, guests, message, reason)
                        VALUES (:name, :email, :guests, :message, :reason)
                    ");
                    $log->execute([
                        ':name'    => $name,
                        ':email'   => $email,
                        ':guests'  => $guests,
                        ':message' => $message,
                        ':reason'  => 'Not on whitelist',
                    ]);
                }
            } catch (Exception $e) {
                // Don't break the page if logging fails
                error_log('Failed to log RSVP failure: ' . $e->getMessage());
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>RSVP Status</title>
  <style>
    body {
      font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      margin: 0;
      padding: 0;
      background: #f5f5f7;
      color: #222;
    }
    .container {
      max-width: 800px;
      margin: 0 auto;
      padding: 2rem 1.5rem;
    }
    .card {
      background: #ffffff;
      padding: 1.5rem;
      border-radius: 1rem;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.06);
    }
    h1 {
      margin-top: 0;
    }
    .success {
      border-left: 4px solid #16a34a;
      padding-left: 1rem;
    }
    .error {
      border-left: 4px solid #dc2626;
      padding-left: 1rem;
    }
    ul {
      margin-top: 0.5rem;
    }
    a.button-link {
      display: inline-block;
      margin-top: 1rem;
      padding: 0.7rem 1.3rem;
      border-radius: 999px;
      background: #111827;
      color: #ffffff;
      text-decoration: none;
      font-weight: 600;
      box-shadow: 0 10px 20px rgba(15, 23, 42, 0.25);
    }
    a.button-link:hover {
      background: #020617;
    }
    .details {
      margin-top: 1rem;
      font-size: 0.95rem;
      color: #444;
    }
    .note {
      margin-top: 0.75rem;
      font-size: 0.9rem;
      color: #b45309;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="card">
      <?php if ($success && empty($errors)): ?>
        <div class="success">
          <h1>Thank you! 🤗</h1>
          <p>
            <?php if ($wasRemoved): ?>
              We've recorded that you're no longer able to attend. 🥺
            <?php elseif ($isUpdate): ?>
              We've updated your RSVP with the new guest count. 🫡
            <?php else: ?>
              Your RSVP has been received. 🙌
            <?php endif; ?>
          </p>
        </div>
        <div class="details">
          <p><strong>Name(s):</strong>
            <?php echo htmlspecialchars($name, ENT_QUOTES, "UTF-8"); ?>
          </p>
          <p><strong>Email:</strong>
            <?php echo htmlspecialchars($email, ENT_QUOTES, "UTF-8"); ?>
          </p>
          <p><strong>Number of guests:</strong>
            <?php echo htmlspecialchars($guests, ENT_QUOTES, "UTF-8"); ?>
          </p>

          <?php if ($message !== ""): ?>
            <p><strong>Your message:</strong><br>
              <?php echo nl2br(htmlspecialchars($message, ENT_QUOTES, "UTF-8")); ?>
            </p>
          <?php endif; ?>

          <?php if ($mailError): ?>
            <p class="note">
              Your RSVP was recorded, but we had trouble sending a notification email.
              Don't worry — you're on the list (or marked as not attending, if you set guests to 0).
            </p>
          <?php endif; ?>

          <?php if ($totalGuests !== null): ?>
            <p><strong>Total guests attending so far:</strong> <?php echo $totalGuests; ?></p>
          <?php endif; ?>

          <?php if (!empty($guestList)): ?>
            <h2>Guest List (so far)</h2>
            <ul>
              <?php foreach ($guestList as $guestRow): ?>
                <?php
                  $guestName    = $guestRow["name"];
                  $totalForName = (int)$guestRow["total_guests"];
                  $extra        = max(0, $totalForName - 1);
                  $label        = $guestName;
                  if ($extra > 0) {
                      $label .= " (+" . $extra . ")";
                  }
                ?>
                <li><?php echo htmlspecialchars($label, ENT_QUOTES, "UTF-8"); ?></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <div class="error">
          <h1>There was a problem with your RSVP</h1>
          <?php if (!empty($errors)): ?>
            <ul>
              <?php foreach ($errors as $e): ?>
                <li><?php echo htmlspecialchars($e, ENT_QUOTES, "UTF-8"); ?></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <a class="button-link" href="index.php#rsvp">Back to Event</a>
    </div>
  </div>
</body>
</html>
