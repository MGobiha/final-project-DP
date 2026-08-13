<?php

require_once '../auth.php';

$staffId =
    isset($_GET['id'])
    ? (int) $_GET['id']
    : 0;

if ($staffId <= 0) {

    header(
        "Location: index.php"
    );

    exit();
}


$sql = "
    UPDATE garage_staff
    SET employment_status = 'inactive'
    WHERE staff_id = ?
    AND garage_id = ?
";

$stmt =
    mysqli_prepare(
        $conn,
        $sql
    );

mysqli_stmt_bind_param(
    $stmt,
    "ii",
    $staffId,
    $garageId
);

mysqli_stmt_execute($stmt);

header(
    "Location: index.php"
);

exit();

?>