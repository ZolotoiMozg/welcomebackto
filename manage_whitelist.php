<?php
session_start();

// Simple admin gate based on login code
if (empty($_SESSION['admin_logged_in'])) {
    header('Location: admin_login.php');
    exit;
}

require_once __DIR__ . '/db_config.php';

$whitelistFile = __DIR__ . '/rsvp_whitelist.php';

$errorMessage = '';
$successMessage = '';

// ----------------------
// Load RSVP list from DB
// ----------------------
$rsvps = [];
$rsvpLoadError = '';

try {
    $pdo = get_pdo();
    // Adjust ORDER BY if you prefer a different sort
    $stmt = $pdo->query("SELECT name, email, guests FROM rsvps ORDER BY name ASC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $rsvps[] = $row;
    }
} catch (Exception $e) {
    $rsvpLoadError = 'Could not load RSVP list.';
}

// ----------------------
// Whitelist management
// ----------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newEmail    = isset($_POST['newEmail']) ? trim($_POST['newEmail']) : '';
    $newName     = isset($_POST['newName']) ? trim($_POST['newName']) : '';
    $deleteEmail = isset($_POST['delete_email']) ? trim($_POST['delete_email']) : '';
    $deleteName  = isset($_POST['delete_name']) ? trim($_POST['delete_name']) : '';

    // Load current whitelist arrays
    $whitelistEmails = [];
    $whitelistNames  = [];

    if (file_exists($whitelistFile)) {
        include $whitelistFile;
        if (!isset($whitelistEmails) || !is_array($whitelistEmails)) {
            $whitelistEmails = [];
        }
        if (!isset($whitelistNames) || !is_array($whitelistNames)) {
            $whitelistNames = [];
        }
    } else {
        $whitelistEmails = [];
        $whitelistNames  = [];
    }

    // Deletions take precedence (each form only submits one of these)
    if ($deleteEmail !== '') {
        $beforeCount = count($whitelistEmails);
        $whitelistEmails = array_values(array_filter(
            $whitelistEmails,
            function ($email) use ($deleteEmail) {
                return strcasecmp($email, $deleteEmail) !== 0;
            }
        ));
        if (count($whitelistEmails) < $beforeCount) {
            $successMessage = 'Email removed from whitelist.';
        } else {
            $errorMessage = 'That email was not found in the whitelist.';
        }
    } elseif ($deleteName !== '') {
        $beforeCount = count($whitelistNames);
        $whitelistNames = array_values(array_filter(
            $whitelistNames,
            function ($name) use ($deleteName) {
                return strcasecmp($name, $deleteName) !== 0;
            }
        ));
        if (count($whitelistNames) < $beforeCount) {
            $successMessage = 'Name removed from whitelist.';
        } else {
            $errorMessage = 'That name was not found in the whitelist.';
        }
    } else {
        // Handle additions
        if ($newEmail !== '') {
            if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                $errorMessage = 'Invalid email address.';
            } elseif (in_array(strtolower($newEmail), array_map('strtolower', $whitelistEmails))) {
                $errorMessage = 'That email is already in the whitelist.';
            } else {
                $whitelistEmails[] = $newEmail;
                $successMessage = 'Email added to whitelist.';
            }
        }

        if ($newName !== '') {
            if (in_array(strtolower($newName), array_map('strtolower', $whitelistNames))) {
                $errorMessage = 'That name is already in the whitelist.';
            } else {
                $whitelistNames[] = $newName;
                $successMessage = $successMessage
                    ? $successMessage . ' Name added to whitelist.'
                    : 'Name added to whitelist.';
            }
        }
    }

    // If we had no errors, write back updated arrays
    if (empty($errorMessage)) {
        $output  = "<?php\n";
        $output .= '$whitelistEmails = ' . var_export($whitelistEmails, true) . ";\n";
        $output .= '$whitelistNames = ' . var_export($whitelistNames, true) . ";\n";
        $output .= "?>\n";

        if (false === file_put_contents($whitelistFile, $output)) {
            $errorMessage   = 'Failed to update whitelist file.';
            $successMessage = '';
        }
    }

    // Re-include to get the latest arrays on screen
    if (file_exists($whitelistFile)) {
        include $whitelistFile;
    }
} else {
    if (file_exists($whitelistFile)) {
        include $whitelistFile;
    } else {
        $whitelistEmails = [];
        $whitelistNames  = [];
    }
}

function h($str)
{
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Whitelist</title>
    <style>
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: #f5f5f7;
            margin: 0;
            padding: 1.5rem;
        }
        main {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 10px 25px rgba(0,0,0,0.06);
        }
        h1 {
            margin-top: 0;
        }
        form {
            margin-bottom: 1rem;
        }
        label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.3rem;
        }
        input[type="text"],
        input[type="email"] {
            width: 100%;
            max-width: 320px;
            padding: 0.4rem 0.6rem;
            border-radius: 0.4rem;
            border: 1px solid #ccc;
        }
        button {
            margin-top: 0.4rem;
            padding: 0.4rem 0.8rem;
            border-radius: 999px;
            border: none;
            cursor: pointer;
            background: #111827;
            color: #fff;
            font-size: 0.9rem;
        }
        button.remove {
            background: #b91c1c;
            margin-left: 0.5rem;
        }
        .message {
            padding: 0.6rem 0.8rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
        .message.error {
            background: #fee2e2;
            color: #b91c1c;
        }
        .message.success {
            background: #dcfce7;
            color: #166534;
        }
        ul {
            list-style: none;
            padding-left: 0;
        }
        li {
            margin-bottom: 0.3rem;
        }
        .whitelist-item {
            display: inline-flex;
            align-items: center;
        }
        .whitelist-item span {
            margin-right: 0.25rem;
        }
        a {
            color: #1d4ed8;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
        .nav-links {
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
        .nav-links a + a {
            margin-left: 1rem;
        }
        /* New: side-by-side columns for whitelist and RSVP list */
        .columns {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            margin-top: 1.5rem;
        }
        .column {
            flex: 1 1 0;
            min-width: 250px;
        }
        .rsvp-meta {
            font-size: 0.85rem;
            color: #555;
        }
    </style>
</head>
<body>
<main>
    <div class="nav-links">
        <a href="index.php">&larr; Back to main site</a>
        <a href="admin_login.php">Back to admin login</a>
    </div>

    <h1>Manage whitelist</h1>

    <?php if (!empty($errorMessage)): ?>
        <div class="message error"><?php echo h($errorMessage); ?></div>
    <?php endif; ?>

    <?php if (!empty($successMessage)): ?>
        <div class="message success"><?php echo h($successMessage); ?></div>
    <?php endif; ?>

    <section>
        <h2>Add email</h2>
        <form method="post">
            <label for="newEmail">Email to whitelist</label>
            <input type="email" name="newEmail" id="newEmail" placeholder="friend@example.com">
            <button type="submit">Add email</button>
        </form>
    </section>

    <section>
        <h2>Add name</h2>
        <form method="post">
            <label for="newName">Name to whitelist</label>
            <input type="text" name="newName" id="newName" placeholder="Edge Master">
            <button type="submit">Add name</button>
        </form>
    </section>

    <section class="columns">
        <!-- Left column: existing whitelist UI -->
        <div class="column">
            <h2>Current whitelist</h2>
            <p>
                Emails: <?php echo count($whitelistEmails); ?>,
                Names: <?php echo count($whitelistNames); ?>
            </p>

            <h3>Emails</h3>
            <?php if (!empty($whitelistEmails)): ?>
                <ul>
                    <?php foreach ($whitelistEmails as $email): ?>
                        <li>
                            <span class="whitelist-item">
                                <span><?php echo h($email); ?></span>
                                <form method="post" style="display:inline" onsubmit="return confirm('Remove this email from the whitelist?');">
                                    <input type="hidden" name="delete_email" value="<?php echo h($email); ?>">
                                    <button type="submit" class="remove">Remove</button>
                                </form>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>No emails currently whitelisted.</p>
            <?php endif; ?>

            <h3>Names</h3>
            <?php if (!empty($whitelistNames)): ?>
                <ul>
                    <?php foreach ($whitelistNames as $name): ?>
                        <li>
                            <span class="whitelist-item">
                                <span><?php echo h($name); ?></span>
                                <form method="post" style="display:inline" onsubmit="return confirm('Remove this name from the whitelist?');">
                                    <input type="hidden" name="delete_name" value="<?php echo h($name); ?>">
                                    <button type="submit" class="remove">Remove</button>
                                </form>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>No names currently whitelisted.</p>
            <?php endif; ?>
        </div>

        <!-- Right column: list of RSVPs -->
        <div class="column">
            <h2>RSVPs received</h2>

            <?php if (!empty($rsvpLoadError)): ?>
                <div class="message error"><?php echo h($rsvpLoadError); ?></div>
            <?php elseif (!empty($rsvps)): ?>
                <p class="rsvp-meta">
                    Total entries: <?php echo count($rsvps); ?>
                </p>
                <ul>
                    <?php foreach ($rsvps as $r): ?>
                        <?php
                            $name   = isset($r['name']) ? trim($r['name']) : '';
                            $email  = isset($r['email']) ? trim($r['email']) : '';
                            $guests = isset($r['guests']) ? (int)$r['guests'] : 0;
                            $guestLabel = $guests === 1
                                ? '1 guest'
                                : $guests . ' guests';
                        ?>
                        <li>
                            <?php echo h($name !== '' ? $name : '(No name)'); ?>
                            <?php if ($email !== ''): ?>
                                (<?php echo h($email); ?>)
                            <?php endif; ?>
                            <?php if ($guests > 0): ?>
                                – <?php echo h($guestLabel); ?>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>No RSVPs recorded yet.</p>
            <?php endif; ?>
        </div>
    </section>
</main>
</body>
</html>
