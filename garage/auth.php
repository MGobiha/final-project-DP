<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if (
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'garage_admin'
) {
    header("Location: ../login.php");
    exit();
}

$userId = (int) $_SESSION['user_id'];

$sql = "
    SELECT
        garage_id,
        garage_name,
        address,
        district
    FROM garages
    WHERE owner_user_id = ?
    AND active_status = 1
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die(
        "Garage query failed: "
        . mysqli_error($conn)
    );
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $userId
);

mysqli_stmt_execute($stmt);

$result =
    mysqli_stmt_get_result($stmt);

$garage =
    mysqli_fetch_assoc($result);

if (!$garage) {
    die(
        "No active garage is linked to this account."
    );
}

$garageId =
    (int) $garage["garage_id"];

?>