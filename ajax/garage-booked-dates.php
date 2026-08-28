<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');


if (!isset($_SESSION['user_id'])) {

    http_response_code(401);

    echo json_encode([
        'success' => false,
        'message' => 'Please log in again.',
        'booked_dates' => []
    ]);

    exit;
}


$garageId =
    (int) (
        $_GET['garage_id']
        ?? 0
    );


if ($garageId <= 0) {

    echo json_encode([
        'success' => false,
        'message' => 'Invalid garage.',
        'booked_dates' => []
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Each garage can handle 4 active vehicle bookings per day
|--------------------------------------------------------------------------
*/
$dailyBookingCapacity = 4;


/*
|--------------------------------------------------------------------------
| Confirm garage is approved and active
|--------------------------------------------------------------------------
*/
$garageStmt =
    mysqli_prepare(
        $conn,
        "
        SELECT garage_id

        FROM garages

        WHERE garage_id = ?
        AND approval_status = 'approved'
        AND active_status = 1

        LIMIT 1
        "
    );


if (!$garageStmt) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Unable to check garage.',
        'booked_dates' => []
    ]);

    exit;
}


mysqli_stmt_bind_param(
    $garageStmt,
    'i',
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
        'success' => false,
        'message' => 'Garage is not available.',
        'booked_dates' => []
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Find dates that already have 4 or more active bookings
|--------------------------------------------------------------------------
|
| Cancelled and rejected requests are intentionally excluded.
|--------------------------------------------------------------------------
*/
$sql = "
    SELECT
        appointment_date,
        COUNT(*) AS booking_count

    FROM appointments

    WHERE garage_id = ?
    AND appointment_date >= CURDATE()
    AND appointment_status IN (
        'pending',
        'confirmed',
        'in_progress'
    )

    GROUP BY appointment_date

    HAVING COUNT(*) >= ?

    ORDER BY appointment_date
";


$stmt =
    mysqli_prepare(
        $conn,
        $sql
    );


if (!$stmt) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Unable to check booking availability.',
        'booked_dates' => []
    ]);

    exit;
}


mysqli_stmt_bind_param(
    $stmt,
    'ii',
    $garageId,
    $dailyBookingCapacity
);


mysqli_stmt_execute(
    $stmt
);


$result =
    mysqli_stmt_get_result(
        $stmt
    );


$bookedDates = [];


while (
    $row =
        mysqli_fetch_assoc(
            $result
        )
) {

    $bookedDates[] =
        $row['appointment_date'];
}


mysqli_stmt_close(
    $stmt
);


echo json_encode([
    'success' => true,
    'capacity' => $dailyBookingCapacity,
    'booked_dates' => $bookedDates
]);