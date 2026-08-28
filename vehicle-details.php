<?php

error_reporting(E_ALL);
ini_set("display_errors", 1);

session_start();

require_once "config/database.php";


// ==========================================================
// LOGIN CHECK
// ==========================================================

if (
    !isset($_SESSION["user_id"])
    ||
    !isset($_SESSION["role"])
) {
    header("Location: login.php");
    exit();
}

if ($_SESSION["role"] !== "vehicle_owner") {
    header("Location: login.php");
    exit();
}

$userId = (int) $_SESSION["user_id"];


// ==========================================================
// VEHICLE ID
// ==========================================================

$vehicleId =
    isset($_GET["id"])
    ? (int) $_GET["id"]
    : 0;

if ($vehicleId <= 0) {
    header("Location: vehicles.php");
    exit();
}


// ==========================================================
// HELPERS
// ==========================================================

function e($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        "UTF-8"
    );
}


function columnExists(
    mysqli $conn,
    string $table,
    string $column
): bool {

    $sql = "
        SELECT COUNT(*) AS total
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
        AND table_name = ?
        AND column_name = ?
    ";

    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param(
        $stmt,
        "ss",
        $table,
        $column
    );

    mysqli_stmt_execute($stmt);

    $result =
        mysqli_stmt_get_result($stmt);

    $row =
        mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    return ((int)($row["total"] ?? 0)) > 0;
}


function findColumn(
    mysqli $conn,
    string $table,
    array $columns
): ?string {

    foreach ($columns as $column) {

        if (
            columnExists(
                $conn,
                $table,
                $column
            )
        ) {
            return $column;
        }
    }

    return null;
}


// ==========================================================
// LOAD USER
// ==========================================================

$userSql = "
    SELECT
        first_name,
        last_name
    FROM users
    WHERE user_id = ?
    LIMIT 1
";

$userStmt =
    mysqli_prepare(
        $conn,
        $userSql
    );

if (!$userStmt) {
    die(
        "Unable to load user: "
        . mysqli_error($conn)
    );
}

mysqli_stmt_bind_param(
    $userStmt,
    "i",
    $userId
);

mysqli_stmt_execute($userStmt);

$userResult =
    mysqli_stmt_get_result($userStmt);

$user =
    mysqli_fetch_assoc($userResult);

mysqli_stmt_close($userStmt);

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit();
}


$firstName =
    trim(
        $user["first_name"]
        ?? ""
    );

$lastName =
    trim(
        $user["last_name"]
        ?? ""
    );

$fullName =
    trim(
        $firstName
        . " "
        . $lastName
    );

if ($fullName === "") {
    $fullName = "Vehicle Owner";
}

$avatarLetter =
    $firstName !== ""
    ? strtoupper(
        substr(
            $firstName,
            0,
            1
        )
    )
    : "U";


// ==========================================================
// LOAD VEHICLE
// IMPORTANT:
// user_id check prevents another owner from opening
// vehicle-details.php?id=another_vehicle
// ==========================================================

$vehicleSql = "
    SELECT *
    FROM vehicles
    WHERE vehicle_id = ?
    AND user_id = ?
    LIMIT 1
";

$vehicleStmt =
    mysqli_prepare(
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
    "ii",
    $vehicleId,
    $userId
);

mysqli_stmt_execute($vehicleStmt);

$vehicleResult =
    mysqli_stmt_get_result($vehicleStmt);

$vehicle =
    mysqli_fetch_assoc($vehicleResult);

mysqli_stmt_close($vehicleStmt);


if (!$vehicle) {
    http_response_code(404);

    die(
        "Vehicle not found or you do not have permission to view it."
    );
}


// ==========================================================
// VEHICLE DATA
// ==========================================================

$make =
    trim(
        $vehicle["make"]
        ?? ""
    );

$model =
    trim(
        $vehicle["model"]
        ?? ""
    );

$year =
    $vehicle["manufacture_year"]
    ?? "";

$registration =
    $vehicle["registration_number"]
    ?? "";

$currentMileage =
    (int) (
        $vehicle["current_mileage"]
        ?? 0
    );

$fuelType =
    $vehicle["fuel_type"]
    ?? "-";

$transmission =
    $vehicle["transmission"]
    ?? "-";

$colour =
    $vehicle["color"]
    ??
    $vehicle["colour"]
    ??
    "-";


// ==========================================================
// VEHICLE IMAGE
// ==========================================================

$vehicleImage = "";

$imageColumns = [
    "vehicle_image",
    "vehicle_image_1",
    "image",
    "image1",
    "photo"
];

foreach ($imageColumns as $column) {

    if (
        isset($vehicle[$column])
        &&
        trim((string)$vehicle[$column]) !== ""
    ) {

        $vehicleImage =
            trim(
                (string)
                $vehicle[$column]
            );

        break;
    }
}


// ==========================================================
// NEXT MAINTENANCE
// ==========================================================

$nextSchedule = null;

$scheduleSql = "
    SELECT
        schedule_id,
        maintenance_type,
        description,
        due_date,
        due_mileage,
        schedule_status,
        reminder_days_before,
        reminder_km_before

    FROM maintenance_schedule

    WHERE vehicle_id = ?

    AND schedule_status IN (
        'upcoming',
        'due',
        'overdue'
    )

    ORDER BY

        CASE
            WHEN schedule_status = 'overdue' THEN 1
            WHEN schedule_status = 'due' THEN 2
            ELSE 3
        END,

        CASE
            WHEN due_date IS NULL
            THEN '9999-12-31'
            ELSE due_date
        END ASC,

        CASE
            WHEN due_mileage IS NULL
            THEN 2147483647
            ELSE due_mileage
        END ASC

    LIMIT 1
";

$scheduleStmt =
    mysqli_prepare(
        $conn,
        $scheduleSql
    );

if ($scheduleStmt) {

    mysqli_stmt_bind_param(
        $scheduleStmt,
        "i",
        $vehicleId
    );

    mysqli_stmt_execute(
        $scheduleStmt
    );

    $scheduleResult =
        mysqli_stmt_get_result(
            $scheduleStmt
        );

    $nextSchedule =
        mysqli_fetch_assoc(
            $scheduleResult
        );

    mysqli_stmt_close(
        $scheduleStmt
    );
}


// ==========================================================
// CALCULATE PROGRESS
// ==========================================================

$remainingKm = null;
$progressPercent = 0;

if (
    $nextSchedule
    &&
    $nextSchedule["due_mileage"] !== null
) {

    $dueMileage =
        (int)
        $nextSchedule["due_mileage"];

    $remainingKm =
        $dueMileage
        -
        $currentMileage;


    if ($dueMileage > 0) {

        $progressPercent =
            ($currentMileage / $dueMileage)
            *
            100;

        if ($progressPercent < 0) {
            $progressPercent = 0;
        }

        if ($progressPercent > 100) {
            $progressPercent = 100;
        }
    }
}


// ==========================================================
// SERVICE RECORD COLUMNS
// ==========================================================

$serviceRecords = [];

$serviceVehicleColumn =
    findColumn(
        $conn,
        "service_records",
        [
            "vehicle_id",
            "vehicleId"
        ]
    );

$serviceDateColumn =
    findColumn(
        $conn,
        "service_records",
        [
            "service_date",
            "date",
            "completed_date",
            "created_at"
        ]
    );

$serviceTypeColumn =
    findColumn(
        $conn,
        "service_records",
        [
            "service_type",
            "maintenance_type",
            "service_name",
            "description"
        ]
    );

$serviceCostColumn =
    findColumn(
        $conn,
        "service_records",
        [
            "cost",
            "total_cost",
            "service_cost",
            "amount"
        ]
    );

$serviceGarageColumn =
    findColumn(
        $conn,
        "service_records",
        [
            "garage_id"
        ]
    );


// ==========================================================
// LOAD RECENT SERVICE RECORDS
// ==========================================================

if ($serviceVehicleColumn) {

    $dateSelect =
        $serviceDateColumn
        ? "sr.`$serviceDateColumn`"
        : "NULL";

    $typeSelect =
        $serviceTypeColumn
        ? "sr.`$serviceTypeColumn`"
        : "'Service'";

    $costSelect =
        $serviceCostColumn
        ? "sr.`$serviceCostColumn`"
        : "NULL";


    if ($serviceGarageColumn) {

        $garageSelect =
            "COALESCE(g.garage_name, 'Unknown Garage')";

        $garageJoin = "
            LEFT JOIN garages g
            ON g.garage_id = sr.`$serviceGarageColumn`
        ";

    } else {

        $garageSelect =
            "'Not specified'";

        $garageJoin =
            "";
    }


    $serviceSql = "

        SELECT

            $dateSelect
                AS service_date,

            $typeSelect
                AS service_type,

            $garageSelect
                AS garage_name,

            $costSelect
                AS service_cost

        FROM service_records sr

        $garageJoin

        WHERE
            sr.`$serviceVehicleColumn`
            = ?

    ";


    if ($serviceDateColumn) {

        $serviceSql .= "
            ORDER BY
                sr.`$serviceDateColumn`
            DESC
        ";
    }


    $serviceSql .= "
        LIMIT 5
    ";


    $serviceStmt =
        mysqli_prepare(
            $conn,
            $serviceSql
        );


    if ($serviceStmt) {

        mysqli_stmt_bind_param(
            $serviceStmt,
            "i",
            $vehicleId
        );

        mysqli_stmt_execute(
            $serviceStmt
        );

        $serviceResult =
            mysqli_stmt_get_result(
                $serviceStmt
            );


        while (
            $row =
            mysqli_fetch_assoc(
                $serviceResult
            )
        ) {

            $serviceRecords[] =
                $row;
        }


        mysqli_stmt_close(
            $serviceStmt
        );
    }
}

?>

<!doctype html>

<html lang="en">

<head>

    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width,initial-scale=1"
    >

    <title>
        Vehicle Details - AutoTrack
    </title>


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


    <style>

        .brand-text {
            display: flex;
            flex-direction: column;
        }


        .brand-name {
            color: #ffffff;
            font-size: 19px;
            font-weight: 800;
        }


        .brand-subtitle {
            margin-top: 3px;
            color: #94a3b8;
            font-size: 11px;
        }


        .detail-grid {

            display: grid;

            grid-template-columns:
                repeat(
                    2,
                    minmax(0, 1fr)
                );

            gap: 20px;

            margin-top: 20px;
        }


        .detail-item strong {

            display: block;

            margin-bottom: 6px;

            font-size: 15px;
        }


        .vehicle-image {

            width: 100%;

            height: 220px;

            object-fit: cover;

            border-radius: 12px;
        }


        .next-service-status {

            display: inline-block;

            margin-bottom: 12px;

            padding: 5px 10px;

            border-radius: 999px;

            background: #eff6ff;

            color: #1d4ed8;

            font-size: 12px;

            font-weight: 700;
        }


        .next-service-status.due {

            background: #fff7ed;

            color: #c2410c;
        }


        .next-service-status.overdue {

            background: #fef2f2;

            color: #b42318;
        }


        .empty-records {

            padding: 30px;

            text-align: center;

            color: #667085;
        }


        @media (
            max-width: 800px
        ) {

            .detail-grid {

                grid-template-columns:
                    1fr;
            }
        }

    </style>

</head>


<body data-page="vehicles">


<div class="app-shell">


    <!-- ==================================================
         SIDEBAR
    =================================================== -->

    <aside class="sidebar">


        <div class="brand">


            <div class="brand-badge">
                A
            </div>


            <div class="brand-text">

                <div class="brand-name">
                    AutoTrack
                </div>

                <div class="brand-subtitle">
                    Vehicle Dashboard
                </div>

            </div>


        </div>



        <nav class="nav">


            <a
                data-page="dashboard"
                href="dashboard.php"
            >
                🏠 <span>Dashboard</span>
            </a>


            <a
                data-page="vehicles"
                href="vehicles.php"
            >
                🚙 <span>Vehicles</span>
            </a>


            <a
                data-page="service"
                href="service-history.php"
            >
                🧾 <span>Service History</span>
            </a>


            <a
                data-page="maintenance"
                href="maintenance.php"
            >
                🗓️ <span>Maintenance</span>
            </a>


            <a
                data-page="reminders"
                href="reminders.php"
            >
                🔔 <span>Reminders</span>
            </a>


            <a
                data-page="chatbot"
                href="chatbot.php"
            >
                🤖 <span>AI Assistant</span>
            </a>


            <a
                data-page="garages"
                href="garages.php"
            >
                📍 <span>Garages</span>
            </a>


            <a
                data-page="news"
                href="news.php"
            >
                📰 <span>News</span>
            </a>


            <a
                data-page="profile"
                href="profile.php"
            >
                👤 <span>Profile</span>
            </a>


            <a href="logout.php">
                🚪 <span>Logout</span>
            </a>


        </nav>


    </aside>



    <!-- ==================================================
         MAIN
    =================================================== -->

    <main class="main">


        <header class="topbar">


            <div class="title">

                <h1>
                    Vehicle Details
                </h1>

                <p>
                    Automobile Service and Maintenance Tracker
                </p>

            </div>


            <div class="user-chip">


                <div class="avatar">

                    <?= e(
                        $avatarLetter
                    ) ?>

                </div>


                <span>

                    <?= e(
                        $fullName
                    ) ?>

                </span>


            </div>


        </header>



        <!-- =================================================
             VEHICLE + NEXT SERVICE
        ================================================== -->

        <section class="grid grid-2">


            <!-- =============================================
                 VEHICLE DETAILS
            ============================================== -->

            <div class="card">


                <?php if (
                    $vehicleImage !== ""
                ): ?>


                    <img

                        src="<?= e(
                            $vehicleImage
                        ) ?>"

                        alt="<?= e(
                            $make
                            . " "
                            . $model
                        ) ?>"

                        class="vehicle-image"

                    >


                <?php else: ?>


                    <div class="vehicle-hero">
                        🚗
                    </div>


                <?php endif; ?>



                <h2>

                    <?= e($make) ?>

                    <?= e($model) ?>

                    <?= e($year) ?>

                </h2>



                <p class="muted">

                    Registration:

                    <?= e(
                        $registration
                    ) ?>

                </p>



                <div class="detail-grid">


                    <div class="detail-item">

                        <strong>

                            <?= number_format(
                                $currentMileage
                            ) ?> km

                        </strong>

                        <p class="muted">
                            Mileage
                        </p>

                    </div>


                    <div class="detail-item">

                        <strong>

                            <?= e(
                                $fuelType
                            ) ?>

                        </strong>

                        <p class="muted">
                            Fuel Type
                        </p>

                    </div>


                    <div class="detail-item">

                        <strong>

                            <?= e(
                                $transmission
                            ) ?>

                        </strong>

                        <p class="muted">
                            Transmission
                        </p>

                    </div>


                    <div class="detail-item">

                        <strong>

                            <?= e(
                                $colour
                            ) ?>

                        </strong>

                        <p class="muted">
                            Colour
                        </p>

                    </div>


                    <?php if (
                        isset(
                            $vehicle[
                                "average_km_per_month"
                            ]
                        )
                    ): ?>


                        <div class="detail-item">

                            <strong>

                                <?= number_format(
                                    (int)
                                    $vehicle[
                                        "average_km_per_month"
                                    ]
                                ) ?> km

                            </strong>

                            <p class="muted">
                                Average KM / Month
                            </p>

                        </div>


                    <?php endif; ?>


                    <?php if (
                        !empty(
                            $vehicle[
                                "last_service_type"
                            ]
                        )
                    ): ?>


                        <div class="detail-item">

                            <strong>

                                <?= e(
                                    $vehicle[
                                        "last_service_type"
                                    ]
                                ) ?>

                            </strong>

                            <p class="muted">
                                Last Service
                            </p>

                        </div>


                    <?php endif; ?>


                </div>


            </div>



            <!-- =============================================
                 NEXT SERVICE
            ============================================== -->

            <div class="card">


                <h2>
                    Next Service
                </h2>


                <?php if (
                    $nextSchedule
                ): ?>


                    <span
                        class="
                            next-service-status
                            <?= e(
                                $nextSchedule[
                                    "schedule_status"
                                ]
                            ) ?>
                        "
                    >

                        <?= e(
                            ucfirst(
                                $nextSchedule[
                                    "schedule_status"
                                ]
                            )
                        ) ?>

                    </span>



                    <p>

                        <strong>

                            <?= e(
                                $nextSchedule[
                                    "maintenance_type"
                                ]
                            ) ?>

                        </strong>

                    </p>



                    <?php if (
                        !empty(
                            $nextSchedule[
                                "description"
                            ]
                        )
                    ): ?>


                        <p class="muted">

                            <?= e(
                                $nextSchedule[
                                    "description"
                                ]
                            ) ?>

                        </p>


                    <?php endif; ?>



                    <p class="muted">


                        <?php

                        $dueParts = [];


                        if (
                            !empty(
                                $nextSchedule[
                                    "due_date"
                                ]
                            )
                        ) {

                            $timestamp =
                                strtotime(
                                    $nextSchedule[
                                        "due_date"
                                    ]
                                );


                            if ($timestamp) {

                                $dueParts[] =
                                    "Due "
                                    .
                                    date(
                                        "d M Y",
                                        $timestamp
                                    );
                            }
                        }


                        if (
                            $nextSchedule[
                                "due_mileage"
                            ]
                            !== null
                        ) {

                            $dueParts[] =
                                "at "
                                .
                                number_format(
                                    (int)
                                    $nextSchedule[
                                        "due_mileage"
                                    ]
                                )
                                .
                                " km";
                        }


                        echo e(
                            implode(
                                " or ",
                                $dueParts
                            )
                        );

                        ?>


                    </p>



                    <?php if (
                        $nextSchedule[
                            "due_mileage"
                        ]
                        !== null
                    ): ?>


                        <div class="progress">

                            <span
                                style="
                                    width:
                                    <?= e(
                                        round(
                                            $progressPercent,
                                            1
                                        )
                                    ) ?>%;
                                "
                            ></span>

                        </div>


                        <p class="muted">


                            <?php if (
                                $remainingKm > 0
                            ): ?>


                                <?= number_format(
                                    $remainingKm
                                ) ?>
                                km remaining


                            <?php elseif (
                                $remainingKm === 0
                            ): ?>


                                Service mileage reached


                            <?php else: ?>


                                <?= number_format(
                                    abs(
                                        $remainingKm
                                    )
                                ) ?>
                                km overdue


                            <?php endif; ?>


                        </p>


                    <?php endif; ?>



                    <a
                        class="btn btn-primary"
                        href="maintenance.php?vehicle_id=<?= $vehicleId ?>"
                    >
                        View Maintenance
                    </a>


                <?php else: ?>


                    <p class="muted">

                        No active maintenance schedule
                        is available for this vehicle.

                    </p>


                    <a
                        class="btn btn-primary"
                        href="maintenance.php?vehicle_id=<?= $vehicleId ?>"
                    >
                        Maintenance
                    </a>


                <?php endif; ?>


            </div>


        </section>



        <!-- =================================================
             RECENT SERVICE RECORDS
        ================================================== -->

        <div class="section-head">

            <h2>
                Recent Service Records
            </h2>

            <a
                href="service-history.php"
                class="btn btn-secondary"
            >
                View All
            </a>

        </div>



        <div class="card table-wrap">


            <table class="table">


                <thead>

                    <tr>

                        <th>Date</th>

                        <th>Service</th>

                        <th>Garage</th>

                        <th>Cost</th>

                    </tr>

                </thead>


                <tbody>


                <?php if (
                    count(
                        $serviceRecords
                    )
                    === 0
                ): ?>


                    <tr>

                        <td
                            colspan="4"
                            class="empty-records"
                        >

                            No completed service records
                            are available for this vehicle yet.

                        </td>

                    </tr>


                <?php else: ?>


                    <?php foreach (
                        $serviceRecords
                        as $record
                    ): ?>


                        <tr>


                            <td>


                                <?php

                                if (
                                    !empty(
                                        $record[
                                            "service_date"
                                        ]
                                    )
                                ) {

                                    $timestamp =
                                        strtotime(
                                            $record[
                                                "service_date"
                                            ]
                                        );

                                    echo $timestamp
                                        ?
                                        e(
                                            date(
                                                "d M Y",
                                                $timestamp
                                            )
                                        )
                                        :
                                        e(
                                            $record[
                                                "service_date"
                                            ]
                                        );

                                } else {

                                    echo "-";
                                }

                                ?>


                            </td>



                            <td>

                                <?= e(
                                    $record[
                                        "service_type"
                                    ]
                                    ??
                                    "Service"
                                ) ?>

                            </td>



                            <td>

                                <?= e(
                                    $record[
                                        "garage_name"
                                    ]
                                    ??
                                    "Not specified"
                                ) ?>

                            </td>



                            <td>


                                <?php

                                if (
                                    isset(
                                        $record[
                                            "service_cost"
                                        ]
                                    )
                                    &&
                                    is_numeric(
                                        $record[
                                            "service_cost"
                                        ]
                                    )
                                ) {

                                    echo "Rs. "
                                    .
                                    number_format(
                                        (float)
                                        $record[
                                            "service_cost"
                                        ],
                                        2
                                    );

                                } else {

                                    echo "-";
                                }

                                ?>


                            </td>


                        </tr>


                    <?php endforeach; ?>


                <?php endif; ?>


                </tbody>


            </table>


        </div>



        <div class="footer-note">

            AutoTrack • Automobile Service and Maintenance Tracker

        </div>


    </main>


</div>


<script src="js/app.js"></script>


</body>

</html>