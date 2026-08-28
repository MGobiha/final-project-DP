<?php

session_start();

require_once 'config/database.php';

$userId = (int)($_SESSION['user_id'] ?? 0);

if ($userId <= 0) {
    header("Location: login.php");
    exit;
}


$stmt = $conn->prepare("
    SELECT
        notification_id,
        notification_type,
        title,
        message,
        channel,
        notification_status,
        sent_at,
        created_at
    FROM notifications
    WHERE user_id = ?
    ORDER BY created_at DESC
");

$stmt->bind_param("i", $userId);

$stmt->execute();

$notifications = $stmt->get_result();

?>