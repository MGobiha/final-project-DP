<?php

if (
    session_status()
    === PHP_SESSION_NONE
) {
    session_start();
}


require_once
    __DIR__
    . '/../config/database.php';


// =====================================================
// NOT LOGGED IN
// =====================================================

if (
    !isset($_SESSION["user_id"])
    ||
    !isset($_SESSION["role"])
) {

    header(
        "Location: ../login.php"
    );

    exit();
}


// =====================================================
// WRONG ROLE
// =====================================================

if (
    $_SESSION["role"]
    !== "system_admin"
) {

    switch (
        $_SESSION["role"]
    ) {


        case "garage_admin":

            header(
                "Location: ../garage/dashboard.php"
            );

            exit();


        case "garage_staff":

            header(
                "Location: ../garage/staff/dashboard.php"
            );

            exit();


        case "vehicle_owner":

            header(
                "Location: ../dashboard.php"
            );

            exit();


        default:

            header(
                "Location: ../logout.php"
            );

            exit();
    }
}


// =====================================================
// SYSTEM ADMIN
// =====================================================

$adminUserId =
    (int)
    $_SESSION["user_id"];