<?php
session_start();
require_once "db_config.php";

if (empty($_SESSION['is_admin'])) {
    header('Location: admin_login.php');
    exit;
}

$pdo = get_pdo();

// Handle add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if ($email !== '') {
        $stmt = $pdo->prepare("
            INSERT INTO invited_guests (name, email, active)
            VALUES (:name, :email, 1)
            ON DUPLICATE KEY UPDATE name = VALUES(name), active = 1
        ");
        $stmt->execute([
            ':name'  => $name,
            ':email' => $email,
        ]);
    }
}

// Fetch list
$guests = $pdo->query("SELECT id, name, email, active FROM invited_guests ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Admin – Invited Guests</title>
</head>
<body>
  <h1>Invited Guests</h1>
  <p><a href="index.php">Back to site</a></p>

  <h2>Add / Update Guest</h2>
  <form method="post">
    <input type="hidden" name="action" value="add" />
    <label>
      Name:
      <input type="text" name="name" />
    </label>
    <label>
      Email:
      <input type="email" name="email" required />
    </label>
    <button type="submit">Save</button>
  </form>

  <h2>Current Whitelist</h2>
  <table border="1" cellpadding="4" cellspacing="0">
    <tr>
      <th>Name</th>
      <th>Email</th>
      <th>Active</th>
    </tr>
    <?php foreach ($guests as $g): ?>
      <tr>
        <td><?php echo htmlspecialchars($g['name'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($g['email'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo $g['active'] ? 'Yes' : 'No'; ?></td>
      </tr>
    <?php endforeach; ?>
  </table>
</body>
</html>
