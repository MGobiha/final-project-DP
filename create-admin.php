<?php

require_once 'config/database.php';

$firstName = "System";
$lastName = "Admin";

$email = "admin@autotrack.lk";

$mobileNumber =
    "0771234567";

$password =
    "Admin123";

$hashedPassword =
    password_hash(
        $password,
        PASSWORD_DEFAULT
    );

$role =
    "system_admin";


$sql = "
    INSERT INTO users
    (
        first_name,
        last_name,
        email,
        mobile_number,
        password,
        maintenance_sms,
        appointment_sms,
        news_sms,
        role
    )
    VALUES
    (
        ?, ?, ?, ?, ?,
        0, 0, 0,
        ?
    )
";


$stmt =
    mysqli_prepare(
        $conn,
        $sql
    );


mysqli_stmt_bind_param(
    $stmt,
    "ssssss",
    $firstName,
    $lastName,
    $email,
    $mobileNumber,
    $hashedPassword,
    $role
);


if (
    mysqli_stmt_execute(
        $stmt
    )
) {

    echo
        "System Admin created successfully.";

} else {

    echo
        "Error: "
        . mysqli_stmt_error(
            $stmt
        );
}