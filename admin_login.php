<?php
session_start();
require_once "db_config.php";

$adminEmail = 'stefen.s@gmail.com';
$message    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['send_code'])) {
        // Generate a 6-digit code
        $code = (string) random_int(100000, 999999);

        $_SESSION['admin_login_code_hash'] = password_hash($code, PASSWORD_DEFAULT);
        $_SESSION['admin_login_expires']   = time() + 600; // 10 minutes

        $subject = "WelcomeBackTo admin login code";
        $body    = "Your admin login code is: {$code}\n\nThis code will expire in 10 minutes.";
        $headers = "From: noreply@welcomebackto.com\r\n" .
                   "X-Mailer: PHP/" . phpversion();

        if (mail($adminEmail, $subject, $body, $headers)) {
            $message = "Code sent. Check your email and enter it below.";
        } else {
            $message = "Failed to send code email.";
        }
    } elseif (isset($_POST['verify_code'])) {
        $inputCode = trim($_POST['code'] ?? '');

        if (
            !empty($_SESSION['admin_login_code_hash']) &&
            !empty($_SESSION['admin_login_expires']) &&
            time() <= $_SESSION['admin_login_expires'] &&
            password_verify($inputCode, $_SESSION['admin_login_code_hash'])
        ) {
            $_SESSION['admin_logged_in'] = true;
            // Clear one-time code
            unset($_SESSION['admin_login_code_hash'], $_SESSION['admin_login_expires']);
            header("Location: manage_whitelist.php");
            exit;
        } else {
            $message = "Invalid or expired code.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Admin Login - Welcome Back To</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
</head>
<body>
  <h1>Admin Login</h1>

  <?php if ($message): ?>
    <p><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
  <?php endif; ?>

  <form method="post">
    <button type="submit" name="send_code">Send login code to <?php echo htmlspecialchars($adminEmail); ?></button>
  </form>

  <hr />

  <form method="post">
    <label for="code">Enter code from email:</label>
    <input type="text" id="code" name="code" required />
    <button type="submit" name="verify_code">Verify &amp; Login</button>
  </form>
</body>
</html>
