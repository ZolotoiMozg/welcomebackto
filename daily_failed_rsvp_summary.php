<?php
require_once "db_config.php";

const ADMIN_EMAIL = 'stefen.s@gmail.com';

$pdo = get_pdo();

// last 24 hours
$stmt = $pdo->query("
    SELECT name, email, guests, message, reason, created_at
    FROM failed_rsvps
    WHERE created_at >= (NOW() - INTERVAL 1 DAY)
    ORDER BY created_at DESC
");

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$rows) {
    // Nothing to send; you can just exit quietly
    exit;
}

$lines = [];
foreach ($rows as $r) {
    $lines[] = sprintf(
        "[%s] name=%s | email=%s | guests=%d | reason=%s | message=%s",
        $r['created_at'],
        $r['name'],
        $r['email'],
        $r['guests'],
        $r['reason'],
        $r['message']
    );
}

$body = "Failed RSVP attempts in the last 24 hours:\n\n" . implode("\n", $lines);

$subject = "Daily failed RSVP summary";
@mail(ADMIN_EMAIL, $subject, $body);
