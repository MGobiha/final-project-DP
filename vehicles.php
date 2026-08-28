<?php

session_start();

/*
|--------------------------------------------------------------------------
| DATABASE CONNECTION
|--------------------------------------------------------------------------
| Change this include only if your connection file has another name.
*/
require_once "config/database.php";


/*
|--------------------------------------------------------------------------
| CHECK LOGIN
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = (int) $_SESSION['user_id'];


/*
|--------------------------------------------------------------------------
| LOAD USER
|--------------------------------------------------------------------------
*/

$userSql = "
    SELECT *
    FROM users
    WHERE user_id = ?
    LIMIT 1
";

$userStmt = mysqli_prepare($conn, $userSql);

if (!$userStmt) {
    die("Unable to prepare user query: " . mysqli_error($conn));
}

mysqli_stmt_bind_param(
    $userStmt,
    "i",
    $userId
);

mysqli_stmt_execute($userStmt);

$userResult = mysqli_stmt_get_result($userStmt);

$user = mysqli_fetch_assoc($userResult);

mysqli_stmt_close($userStmt);


/*
|--------------------------------------------------------------------------
| USER NAME
|--------------------------------------------------------------------------
|
| Supports common column names.
| Change these if your users table uses different column names.
|
*/

$firstName = trim(
    $user['first_name']
    ?? $user['firstname']
    ?? ''
);

$lastName = trim(
    $user['last_name']
    ?? $user['lastname']
    ?? ''
);


/*
|--------------------------------------------------------------------------
| FALLBACK NAME
|--------------------------------------------------------------------------
*/

if ($firstName === '' && isset($user['name'])) {

    $parts = preg_split(
        '/\s+/',
        trim($user['name'])
    );

    $firstName = $parts[0] ?? '';

    if (count($parts) > 1) {
        $lastName = $parts[count($parts) - 1];
    }
}


$displayName = trim(
    $firstName . ' ' . $lastName
);

if ($displayName === '') {
    $displayName = "Vehicle Owner";
}


/*
|--------------------------------------------------------------------------
| AVATAR INITIALS
|--------------------------------------------------------------------------
*/

$initials = '';

if ($firstName !== '') {
    $initials .= strtoupper(
        substr($firstName, 0, 1)
    );
}

if ($lastName !== '') {
    $initials .= strtoupper(
        substr($lastName, 0, 1)
    );
}

if ($initials === '') {
    $initials = "VO";
}


/*
|--------------------------------------------------------------------------
| LOAD LOGGED-IN OWNER'S VEHICLES
|--------------------------------------------------------------------------
*/

$vehicleSql = "
    SELECT *
    FROM vehicles
    WHERE user_id = ?
    ORDER BY vehicle_id DESC
";

$vehicleStmt = mysqli_prepare(
    $conn,
    $vehicleSql
);

if (!$vehicleStmt) {
    die(
        "Unable to prepare vehicle query: "
        . mysqli_error($conn)
    );
}

mysqli_stmt_bind_param(
    $vehicleStmt,
    "i",
    $userId
);

mysqli_stmt_execute($vehicleStmt);

$vehicleResult =
    mysqli_stmt_get_result($vehicleStmt);


/*
|--------------------------------------------------------------------------
| HELPER FUNCTION
|--------------------------------------------------------------------------
*/

function e($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}


/*
|--------------------------------------------------------------------------
| VEHICLE IMAGE HELPER
|--------------------------------------------------------------------------
|
| Your vehicle image column name may be:
|
| image
| vehicle_image
| image1
| vehicle_image_1
|
| This checks several possibilities.
|
*/

function getVehicleImage($vehicle)
{
    $possibleColumns = [
        'vehicle_image',
        'image',
        'image1',
        'vehicle_image_1',
        'photo'
    ];

    foreach ($possibleColumns as $column) {

        if (
            isset($vehicle[$column]) &&
            trim((string) $vehicle[$column]) !== ''
        ) {
            return trim(
                (string) $vehicle[$column]
            );
        }
    }

    return '';
}


/*
|--------------------------------------------------------------------------
| GET VEHICLE MAINTENANCE STATUS
|--------------------------------------------------------------------------
*/

function getMaintenanceStatus(
    mysqli $conn,
    int $vehicleId,
    int $currentMileage
): array {

    $sql = "
        SELECT
            schedule_id,
            maintenance_type,
            due_date,
            due_mileage,
            schedule_status

        FROM maintenance_schedule

        WHERE vehicle_id = ?
        AND schedule_status <> 'completed'

        ORDER BY
            CASE
                WHEN due_date IS NOT NULL THEN due_date
                ELSE '9999-12-31'
            END ASC,

            CASE
                WHEN due_mileage IS NOT NULL THEN due_mileage
                ELSE 2147483647
            END ASC

        LIMIT 1
    ";

    $stmt = mysqli_prepare(
        $conn,
        $sql
    );

    if (!$stmt) {
        return [
            'text' => 'No schedule',
            'class' => 'success'
        ];
    }

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $vehicleId
    );

    mysqli_stmt_execute($stmt);

    $result =
        mysqli_stmt_get_result($stmt);

    $schedule =
        mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);


    /*
    |--------------------------------------------------------------------------
    | NO MAINTENANCE SCHEDULE
    |--------------------------------------------------------------------------
    */

    if (!$schedule) {

        return [
            'text' => 'No maintenance scheduled',
            'class' => 'success'
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | DATE-BASED STATUS
    |--------------------------------------------------------------------------
    */

    if (!empty($schedule['due_date'])) {

        try {

            $today = new DateTime();
            $today->setTime(0, 0, 0);

            $dueDate =
                new DateTime(
                    $schedule['due_date']
                );

            $dueDate->setTime(0, 0, 0);


            $difference =
                (int) $today
                    ->diff($dueDate)
                    ->format('%r%a');


            if ($difference < 0) {

                return [
                    'text' => 'Service overdue',
                    'class' => 'warning'
                ];
            }


            if ($difference === 0) {

                return [
                    'text' => 'Service due today',
                    'class' => 'warning'
                ];
            }


            if ($difference <= 30) {

                return [
                    'text' =>
                        'Service due in '
                        . $difference
                        . ' day'
                        . ($difference == 1 ? '' : 's'),

                    'class' => 'warning'
                ];
            }

        } catch (Exception $e) {
            // Ignore invalid date and continue.
        }
    }


    /*
    |--------------------------------------------------------------------------
    | MILEAGE-BASED STATUS
    |--------------------------------------------------------------------------
    */

    if ($schedule['due_mileage'] !== null) {

        $dueMileage =
            (int) $schedule['due_mileage'];

        $remaining =
            $dueMileage - $currentMileage;


        if ($remaining <= 0) {

            return [
                'text' => 'Service due',
                'class' => 'warning'
            ];
        }


        if ($remaining <= 500) {

            return [
                'text' =>
                    number_format($remaining)
                    . ' km until service',

                'class' => 'warning'
            ];
        }
    }


    return [
        'text' => 'Up to date',
        'class' => 'success'
    ];
}

?>
<!doctype html>

<html lang="en">

<head>

    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>My Vehicles - AutoTrack</title>


    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >


    <link
        rel="stylesheet"
        href="css/style.css"
    >

    <link
        rel="stylesheet"
        href="css/responsive.css"
    >

</head>


<body data-page="vehicles">


<div class="app-shell">


    <!-- =====================================================
         SIDEBAR
    ====================================================== -->

<?php
    require_once 'includes/sidebar.php';
    ?>


    <!-- =====================================================
         MAIN
    ====================================================== -->

    <main class="main">


        <!-- =================================================
             TOP BAR
        ================================================== -->

        <header class="topbar">


            <div class="title">

                <h1>
                    My Vehicles
                </h1>

                <p>
                    Automobile Service and Maintenance Tracker
                </p>

            </div>


            <div class="user-chip">
           <div class="avatar">
                <?= htmlspecialchars(
                    strtoupper(
                        substr($user["first_name"] ?? "U", 0, 1)
                    )
                ) ?>
            </div>
            <span data-user-name>
              <?php
              echo htmlspecialchars(
                  $_SESSION["first_name"]
              );
              ?>
            </span>
            <span>
              
            <a
                      href="logout.php"
                      class="btn btn-secondary"
                  >
                      Logout
                  </a>
                </span>
          </div>


        </header>


        <!-- =================================================
             SECTION HEADER
        ================================================== -->

        <div class="section-head">


            <h2>
                Registered vehicles
            </h2>


            <a
                href="register.php"
                class="btn btn-primary"
            >
                + Add Vehicle
            </a>


        </div>


        <!-- =================================================
             VEHICLES
        ================================================== -->

        <section class="grid grid-3">


            <?php if (
                mysqli_num_rows($vehicleResult) > 0
            ): ?>


                <?php while (
                    $vehicle =
                    mysqli_fetch_assoc($vehicleResult)
                ): ?>


                    <?php

                    $vehicleId =
                        (int) $vehicle['vehicle_id'];


                    $currentMileage =
                        (int) (
                            $vehicle['current_mileage']
                            ?? 0
                        );


                    $status =
                        getMaintenanceStatus(
                            $conn,
                            $vehicleId,
                            $currentMileage
                        );


                    $vehicleImage =
                        getVehicleImage($vehicle);


                    $make =
                        $vehicle['make']
                        ?? 'Vehicle';


                    $model =
                        $vehicle['model']
                        ?? '';


                    $year =
                        $vehicle['manufacture_year']
                        ?? '';


                    $registration =
                        $vehicle['registration_number']
                        ?? '';

                    ?>


                    <article
                        class="card vehicle-card"
                    >


                        <!-- =================================
                             VEHICLE IMAGE
                        ================================== -->

                        <?php if (
                            $vehicleImage !== ''
                        ): ?>


                            <div
                                class="vehicle-hero"
                                style="
                                    overflow:hidden;
                                    padding:0;
                                "
                            >

                                <img
                                    src="<?= e($vehicleImage) ?>"
                                    alt="<?= e(
                                        $make . ' ' . $model
                                    ) ?>"
                                    style="
                                        width:100%;
                                        height:100%;
                                        object-fit:cover;
                                        display:block;
                                    "
                                >

                            </div>


                        <?php else: ?>


                            <div class="vehicle-hero">

                                🚗

                            </div>


                        <?php endif; ?>


                        <!-- =================================
                             VEHICLE NAME
                        ================================== -->

                        <h3>

                            <?= e($make) ?>

                            <?= e($model) ?>

                            <?= e($year) ?>

                        </h3>


                        <!-- =================================
                             REGISTRATION + MILEAGE
                        ================================== -->

                        <p>

                            <?= e($registration) ?>

                            •

                            <?= number_format(
                                $currentMileage
                            ) ?> km

                        </p>


                        <!-- =================================
                             FUEL
                        ================================== -->

                        <?php if (
                            !empty($vehicle['fuel_type'])
                        ): ?>

                            <p class="muted">

                                <?= e(
                                    $vehicle['fuel_type']
                                ) ?>

                            </p>

                        <?php endif; ?>


                        <!-- =================================
                             MAINTENANCE STATUS
                        ================================== -->

                        <p>

                            <span
                                class="badge <?= e(
                                    $status['class']
                                ) ?>"
                            >

                                <?= e(
                                    $status['text']
                                ) ?>

                            </span>

                        </p>


                        <!-- =================================
                             DETAILS BUTTON
                        ================================== -->

                        <a
                            class="btn btn-secondary"
                            href="vehicle-details.php?id=<?= $vehicleId ?>"
                        >
                            View Details
                        </a>


                    </article>


                <?php endwhile; ?>


            <?php else: ?>


                <div
                    class="card"
                    style="
                        grid-column:1/-1;
                        text-align:center;
                        padding:40px;
                    "
                >

                    <div
                        style="
                            font-size:48px;
                            margin-bottom:15px;
                        "
                    >
                        🚙
                    </div>


                    <h3>
                        No vehicles registered
                    </h3>


                    <p class="muted">

                        Add your first vehicle to start
                        tracking maintenance and service
                        reminders.

                    </p>


                    <p style="margin-top:20px;">

                        <a
                            href="register.php"
                            class="btn btn-primary"
                        >
                            Add Vehicle
                        </a>

                    </p>


                </div>


            <?php endif; ?>


        </section>


        <!-- =================================================
             FOOTER
        ================================================== -->

        <div class="footer-note">

            AutoTrack • Automobile Service and Maintenance Tracker

        </div>


    </main>


</div>


<script src="js/app.js"></script>


</body>

</html>

<?php

mysqli_stmt_close(
    $vehicleStmt
);

?>