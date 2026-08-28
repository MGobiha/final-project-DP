<?php

session_start();

require_once "config/database.php";

header("Content-Type: application/json; charset=UTF-8");


// ==========================================================
// LOGIN CHECK
// ==========================================================

if (!isset($_SESSION["user_id"])) {

    echo json_encode([
        "success" => false,
        "message" => "Not logged in.",
        "services" => []
    ]);

    exit();
}


// ==========================================================
// GET GARAGE ID
// ==========================================================

$garageId =
    isset($_GET["garage_id"])
        ? (int) $_GET["garage_id"]
        : 0;


if ($garageId <= 0) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid garage.",
        "services" => []
    ]);

    exit();
}


// ==========================================================
// VERIFY GARAGE
// ==========================================================

$garageSql = "
    SELECT garage_id
    FROM garages
    WHERE garage_id = ?
    AND approval_status = 'approved'
    AND active_status = 1
    LIMIT 1
";


$garageStmt =
    mysqli_prepare(
        $conn,
        $garageSql
    );


if (!$garageStmt) {

    echo json_encode([
        "success" => false,
        "message" => "Unable to verify garage.",
        "services" => []
    ]);

    exit();
}


mysqli_stmt_bind_param(
    $garageStmt,
    "i",
    $garageId
);


mysqli_stmt_execute(
    $garageStmt
);


$garageResult =
    mysqli_stmt_get_result(
        $garageStmt
    );


$garage =
    mysqli_fetch_assoc(
        $garageResult
    );


mysqli_stmt_close(
    $garageStmt
);


if (!$garage) {

    echo json_encode([
        "success" => false,
        "message" => "Garage not available.",
        "services" => []
    ]);

    exit();
}


// ==========================================================
// LOAD ACTIVE SERVICES
// ==========================================================

$sql = "
    SELECT
        garage_service_id,
        service_name,
        description,
        estimated_price,
        estimated_duration_minutes

    FROM garage_services

    WHERE garage_id = ?
    AND active_status = 1

    ORDER BY service_name ASC
";


$stmt =
    mysqli_prepare(
        $conn,
        $sql
    );


if (!$stmt) {

    echo json_encode([
        "success" => false,
        "message" => "Unable to load services.",
        "services" => []
    ]);

    exit();
}


mysqli_stmt_bind_param(
    $stmt,
    "i",
    $garageId
);


mysqli_stmt_execute(
    $stmt
);


$result =
    mysqli_stmt_get_result(
        $stmt
    );


$services = [];


while (
    $row =
    mysqli_fetch_assoc(
        $result
    )
) {

    $services[] = [

        "id" =>
            (int) $row["garage_service_id"],

        "name" =>
            $row["service_name"],

        "description" =>
            $row["description"] ?? "",

        "price" =>
            $row["estimated_price"],

        "duration" =>
            $row["estimated_duration_minutes"]
    ];
}


mysqli_stmt_close(
    $stmt
);


echo json_encode([
    "success" => true,
    "services" => $services
]);

exit();