<?php

// =====================================================
// START SESSION
// =====================================================

if (
    session_status()
    === PHP_SESSION_NONE
) {

    session_start();
}


// =====================================================
// DATABASE
// =====================================================

require_once
    __DIR__
    . '/../config/database.php';


// =====================================================
// CHECK LOGIN
// =====================================================

if (
    !isset(
        $_SESSION['user_id']
    )
) {

    header(
        "Location: ../login.php"
    );

    exit();
}


// =====================================================
// CHECK ROLE
// =====================================================

if (
    !isset(
        $_SESSION['role']
    )
    ||
    $_SESSION['role']
    !== 'garage_admin'
) {

    header(
        "Location: ../login.php"
    );

    exit();
}


$userId =
    (int)
    $_SESSION['user_id'];


// =====================================================
// LOAD GARAGE
// =====================================================

$sql = "
    SELECT
        garage_id,
        owner_user_id,
        garage_name,
        owner_name,
        email,
        mobile_number,
        telephone,
        address,
        city,
        district,
        latitude,
        longitude,
        opening_time,
        closing_time,
        description,
        image,
        approval_status,
        active_status,
        created_at,
        updated_at

    FROM garages

    WHERE owner_user_id = ?

    LIMIT 1
";


$stmt =
    mysqli_prepare(
        $conn,
        $sql
    );


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


mysqli_stmt_execute(
    $stmt
);


$result =
    mysqli_stmt_get_result(
        $stmt
    );


$garage =
    mysqli_fetch_assoc(
        $result
    );


// =====================================================
// NO GARAGE LINKED
// =====================================================

if (!$garage) {

    session_destroy();

    header(
        "Location: ../login.php"
    );

    exit();
}


// =====================================================
// SAVE GARAGE ID
// =====================================================

$garageId =
    (int)
    $garage["garage_id"];


$_SESSION["garage_id"] =
    $garageId;


// =====================================================
// CHECK APPROVAL STATUS
// =====================================================

$currentPage =
    basename(
        $_SERVER[
            "PHP_SELF"
        ]
    );


// =====================================================
// PENDING GARAGE
// =====================================================

if (
    $garage[
        "approval_status"
    ]
    === "pending"
) {

    // Avoid redirect loop
    if (
        $currentPage
        !==
        "pending-approval.php"
    ) {

        header(
            "Location: /automobile_tracker/garage/pending-approval.php"
        );

        exit();
    }
}


// =====================================================
// REJECTED GARAGE
// =====================================================

if (
    $garage[
        "approval_status"
    ]
    === "rejected"
) {

    // Avoid redirect loop
    if (
        $currentPage
        !==
        "rejected.php"
    ) {

        header(
            "Location: /automobile_tracker/garage/rejected.php"
        );

        exit();
    }
}


// =====================================================
// APPROVED BUT INACTIVE
// =====================================================

if (
    $garage[
        "approval_status"
    ]
    === "approved"
    &&
    (int)
    $garage[
        "active_status"
    ]
    !== 1
) {

    session_destroy();

    header(
        "Location: ../login.php"
    );

    exit();
}


// =====================================================
// ONLY APPROVED GARAGE CAN CONTINUE
// =====================================================

if (
    $garage[
        "approval_status"
    ]
    === "approved"
    &&
    (int)
    $garage[
        "active_status"
    ]
    === 1
) {

    // Continue normally.
    // dashboard.php, staff pages, etc.
}

?>