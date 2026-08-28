<?php

require_once '../auth.php';


// ==========================================================
// GET APPOINTMENT ID
// ==========================================================

$appointmentId =
    isset($_GET["id"])
        ? (int) $_GET["id"]
        : 0;


if ($appointmentId <= 0) {

    header(
        "Location: index.php"
    );

    exit();
}


// ==========================================================
// VERIFY APPOINTMENT BELONGS TO THIS GARAGE
// ==========================================================

$checkSql = "
    SELECT
        appointment_id,
        appointment_status

    FROM appointments

    WHERE appointment_id = ?
    AND garage_id = ?

    LIMIT 1
";


$checkStmt =
    mysqli_prepare(
        $conn,
        $checkSql
    );


if (!$checkStmt) {

    die(
        "Unable to verify appointment: "
        . mysqli_error($conn)
    );
}


mysqli_stmt_bind_param(
    $checkStmt,
    "ii",
    $appointmentId,
    $garageId
);


mysqli_stmt_execute(
    $checkStmt
);


$checkResult =
    mysqli_stmt_get_result(
        $checkStmt
    );


$appointment =
    mysqli_fetch_assoc(
        $checkResult
    );


mysqli_stmt_close(
    $checkStmt
);


// ==========================================================
// APPOINTMENT NOT FOUND
// ==========================================================

if (!$appointment) {

    header(
        "Location: index.php?error=notfound"
    );

    exit();
}


// ==========================================================
// ONLY PENDING CAN BE APPROVED
// ==========================================================

if (
    $appointment["appointment_status"]
    !== "pending"
) {

    header(
        "Location: index.php?error=status"
    );

    exit();
}


// ==========================================================
// APPROVE APPOINTMENT
// ==========================================================

$updateSql = "
    UPDATE appointments

    SET
        appointment_status = 'confirmed',
        sms_sent = 0,
        updated_at = CURRENT_TIMESTAMP

    WHERE appointment_id = ?
    AND garage_id = ?
";


$updateStmt =
    mysqli_prepare(
        $conn,
        $updateSql
    );


if (!$updateStmt) {

    die(
        "Unable to prepare appointment approval: "
        . mysqli_error($conn)
    );
}


mysqli_stmt_bind_param(
    $updateStmt,
    "ii",
    $appointmentId,
    $garageId
);


if (
    mysqli_stmt_execute(
        $updateStmt
    )
) {

    mysqli_stmt_close(
        $updateStmt
    );


    header(
        "Location: index.php?approved=1"
    );

    exit();

} else {

    $error =
        mysqli_stmt_error(
            $updateStmt
        );


    mysqli_stmt_close(
        $updateStmt
    );


    die(
        "Unable to approve appointment: "
        . htmlspecialchars(
            $error
        )
    );
}