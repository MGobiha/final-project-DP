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
// HELPER
// ==========================================================

function e($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        "UTF-8"
    );
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

$userStmt = mysqli_prepare(
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

mysqli_stmt_execute(
    $userStmt
);

$userResult =
    mysqli_stmt_get_result(
        $userStmt
    );

$user =
    mysqli_fetch_assoc(
        $userResult
    );

mysqli_stmt_close(
    $userStmt
);


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
        ?
        strtoupper(
            substr(
                $firstName,
                0,
                1
            )
        )
        :
        "U";


// ==========================================================
// VEHICLE SELECTED FROM URL
// maintenance.php?vehicle_id=4
// ==========================================================

$selectedVehicleId =
    isset($_GET["vehicle_id"])
        ?
        (int) $_GET["vehicle_id"]
        :
        0;


// ==========================================================
// LOAD USER VEHICLES
// ==========================================================

$vehicles = [];

$vehicleSql = "
    SELECT
        vehicle_id,
        registration_number,
        make,
        model,
        manufacture_year,
        current_mileage,
        average_km_per_month

    FROM vehicles

    WHERE user_id = ?

    ORDER BY
        make ASC,
        model ASC
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
    "i",
    $userId
);


mysqli_stmt_execute(
    $vehicleStmt
);


$vehicleResult =
    mysqli_stmt_get_result(
        $vehicleStmt
    );


while (
    $vehicle =
    mysqli_fetch_assoc(
        $vehicleResult
    )
) {

    $vehicles[] =
        $vehicle;
}


mysqli_stmt_close(
    $vehicleStmt
);


// ==========================================================
// DEFAULT SELECTED VEHICLE
// ==========================================================

if (
    $selectedVehicleId <= 0
    &&
    count($vehicles) > 0
) {

    $selectedVehicleId =
        (int)
        $vehicles[0]["vehicle_id"];
}


// ==========================================================
// VERIFY SELECTED VEHICLE BELONGS TO USER
// ==========================================================

$selectedVehicle = null;


foreach (
    $vehicles
    as $vehicle
) {

    if (
        (int)
        $vehicle["vehicle_id"]
        ===
        $selectedVehicleId
    ) {

        $selectedVehicle =
            $vehicle;

        break;
    }
}


// ==========================================================
// FORM MESSAGES
// ==========================================================

$successMessage = "";
$errorMessage = "";


// ==========================================================
// CREATE MANUAL MAINTENANCE SCHEDULE
// ==========================================================

if (
    $_SERVER["REQUEST_METHOD"]
    === "POST"
) {

    $vehicleId =
        (int)
        (
            $_POST["vehicle_id"]
            ?? 0
        );


    $maintenanceType =
        trim(
            $_POST[
                "maintenance_type"
            ]
            ?? ""
        );


    $dueDate =
        trim(
            $_POST[
                "due_date"
            ]
            ?? ""
        );


    $dueMileageRaw =
        trim(
            $_POST[
                "due_mileage"
            ]
            ?? ""
        );


    $reminderDays =
        (int)
        (
            $_POST[
                "reminder_days_before"
            ]
            ?? 30
        );


    $description =
        trim(
            $_POST[
                "description"
            ]
            ?? ""
        );


    // ------------------------------------------------------
    // VERIFY VEHICLE OWNERSHIP
    // ------------------------------------------------------

    $checkVehicleSql = "
        SELECT vehicle_id
        FROM vehicles
        WHERE vehicle_id = ?
        AND user_id = ?
        LIMIT 1
    ";


    $checkVehicleStmt =
        mysqli_prepare(
            $conn,
            $checkVehicleSql
        );


    if (!$checkVehicleStmt) {

        $errorMessage =
            "Unable to validate vehicle.";

    } else {

        mysqli_stmt_bind_param(
            $checkVehicleStmt,
            "ii",
            $vehicleId,
            $userId
        );


        mysqli_stmt_execute(
            $checkVehicleStmt
        );


        $checkVehicleResult =
            mysqli_stmt_get_result(
                $checkVehicleStmt
            );


        $vehicleExists =
            mysqli_fetch_assoc(
                $checkVehicleResult
            );


        mysqli_stmt_close(
            $checkVehicleStmt
        );


        if (!$vehicleExists) {

            $errorMessage =
                "Invalid vehicle selected.";
        }
    }


    // ------------------------------------------------------
    // VALIDATION
    // ------------------------------------------------------

    if (
        $errorMessage === ""
        &&
        $maintenanceType === ""
    ) {

        $errorMessage =
            "Please select a service type.";
    }


    if (
        $errorMessage === ""
        &&
        $dueDate === ""
        &&
        $dueMileageRaw === ""
    ) {

        $errorMessage =
            "Please enter either a due date or due mileage.";
    }


    if (
        $errorMessage === ""
        &&
        $dueMileageRaw !== ""
        &&
        (
            !is_numeric(
                $dueMileageRaw
            )
            ||
            (int)
            $dueMileageRaw
            < 0
        )
    ) {

        $errorMessage =
            "Due mileage must be a valid number.";
    }


    // ------------------------------------------------------
    // INSERT
    // ------------------------------------------------------

    if ($errorMessage === "") {


        $dueDateValue =
            $dueDate !== ""
                ?
                $dueDate
                :
                null;


        $dueMileage =
            $dueMileageRaw !== ""
                ?
                (int)
                $dueMileageRaw
                :
                null;


        $scheduleStatus =
            "upcoming";


        $reminderKmBefore =
            500;


        $smsEnabled =
            1;


        $smsSent =
            0;


        $insertSql = "
            INSERT INTO maintenance_schedule
            (
                vehicle_id,
                maintenance_type,
                description,
                due_date,
                due_mileage,
                reminder_days_before,
                reminder_km_before,
                schedule_status,
                sms_enabled,
                sms_sent
            )

            VALUES
            (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )
        ";


        $insertStmt =
            mysqli_prepare(
                $conn,
                $insertSql
            );


        if (!$insertStmt) {

            $errorMessage =
                "Unable to prepare maintenance schedule: "
                .
                mysqli_error(
                    $conn
                );

        } else {


            mysqli_stmt_bind_param(
                $insertStmt,
                "isssiiisii",
                $vehicleId,
                $maintenanceType,
                $description,
                $dueDateValue,
                $dueMileage,
                $reminderDays,
                $reminderKmBefore,
                $scheduleStatus,
                $smsEnabled,
                $smsSent
            );


            if (
                mysqli_stmt_execute(
                    $insertStmt
                )
            ) {

                $successMessage =
                    "Maintenance schedule created successfully.";

                $selectedVehicleId =
                    $vehicleId;


                // Reload selected vehicle
                foreach (
                    $vehicles
                    as $vehicle
                ) {

                    if (
                        (int)
                        $vehicle[
                            "vehicle_id"
                        ]
                        ===
                        $vehicleId
                    ) {

                        $selectedVehicle =
                            $vehicle;

                        break;
                    }
                }

            } else {

                $errorMessage =
                    "Unable to create schedule: "
                    .
                    mysqli_stmt_error(
                        $insertStmt
                    );
            }


            mysqli_stmt_close(
                $insertStmt
            );
        }
    }
}


// ==========================================================
// LOAD CURRENT MAINTENANCE SCHEDULES
// ==========================================================

$schedules = [];


if ($selectedVehicleId > 0) {


    $scheduleSql = "
        SELECT

            schedule_id,

            maintenance_type,

            description,

            due_date,

            due_mileage,

            reminder_days_before,

            reminder_km_before,

            schedule_status,

            sms_sent

        FROM maintenance_schedule

        WHERE vehicle_id = ?

        ORDER BY

            CASE
                WHEN schedule_status = 'overdue'
                    THEN 1

                WHEN schedule_status = 'due'
                    THEN 2

                WHEN schedule_status = 'upcoming'
                    THEN 3

                ELSE 4
            END,

            due_date ASC,

            due_mileage ASC
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
            $selectedVehicleId
        );


        mysqli_stmt_execute(
            $scheduleStmt
        );


        $scheduleResult =
            mysqli_stmt_get_result(
                $scheduleStmt
            );


        while (
            $schedule =
            mysqli_fetch_assoc(
                $scheduleResult
            )
        ) {

            $schedules[] =
                $schedule;
        }


        mysqli_stmt_close(
            $scheduleStmt
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
        Maintenance Scheduler - AutoTrack
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


        .alert-success {

            margin-bottom: 18px;

            padding: 14px 16px;

            border:
                1px solid
                #abefc6;

            border-radius: 10px;

            background: #ecfdf3;

            color: #067647;
        }


        .alert-error {

            margin-bottom: 18px;

            padding: 14px 16px;

            border:
                1px solid
                #fecdca;

            border-radius: 10px;

            background: #fef3f2;

            color: #b42318;
        }


        .recommended-box p {

            margin:
                13px
                0;
        }


        .schedule-section {

            margin-top: 28px;
        }


        .schedule-badge {

            display: inline-block;

            padding:
                5px
                9px;

            border-radius:
                999px;

            font-size:
                12px;

            font-weight:
                700;
        }


        .schedule-upcoming {

            background:
                #eff6ff;

            color:
                #1d4ed8;
        }


        .schedule-due {

            background:
                #fff7ed;

            color:
                #c2410c;
        }


        .schedule-overdue {

            background:
                #fef2f2;

            color:
                #b42318;
        }


        .schedule-completed {

            background:
                #ecfdf3;

            color:
                #067647;
        }


        .empty-message {

            padding:
                35px;

            text-align:
                center;

            color:
                #667085;
        }

    </style>

</head>


<body data-page="maintenance">


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


        <header class="topbar">


            <div class="title">

                <h1>
                    Maintenance Scheduler
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



        <?php if (
            $successMessage !== ""
        ): ?>


            <div class="alert-success">

                <?= e(
                    $successMessage
                ) ?>

            </div>


        <?php endif; ?>



        <?php if (
            $errorMessage !== ""
        ): ?>


            <div class="alert-error">

                <?= e(
                    $errorMessage
                ) ?>

            </div>


        <?php endif; ?>



        <!-- =================================================
             FORM + RECOMMENDATIONS
        ================================================== -->

        <section class="grid grid-2">


            <!-- =============================================
                 SCHEDULE FORM
            ============================================== -->

            <form

                method="POST"

                action="maintenance.php?vehicle_id=<?= (int)$selectedVehicleId ?>"

                class="card form-grid"

            >


                <!-- =========================================
                     VEHICLE
                ========================================== -->

                <div class="field full">


                    <label for="vehicleId">
                        Vehicle
                    </label>


                    <select
                        name="vehicle_id"
                        id="vehicleId"
                        required
                        onchange="changeVehicle(this.value)"
                    >


                        <?php if (
                            count(
                                $vehicles
                            )
                            === 0
                        ): ?>


                            <option value="">
                                No vehicles available
                            </option>


                        <?php else: ?>


                            <?php foreach (
                                $vehicles
                                as $vehicle
                            ): ?>


                                <option

                                    value="<?= (int)$vehicle["vehicle_id"] ?>"

                                    <?= (
                                        (int)$vehicle["vehicle_id"]
                                        ===
                                        (int)$selectedVehicleId
                                    )
                                    ?
                                    "selected"
                                    :
                                    "" ?>

                                >

                                    <?= e(
                                        trim(
                                            $vehicle["make"]
                                            . " "
                                            . $vehicle["model"]
                                            . " "
                                            . $vehicle["manufacture_year"]
                                        )
                                    ) ?>

                                    <?php if (
                                        !empty(
                                            $vehicle[
                                                "registration_number"
                                            ]
                                        )
                                    ): ?>

                                        -
                                        <?= e(
                                            $vehicle[
                                                "registration_number"
                                            ]
                                        ) ?>

                                    <?php endif; ?>

                                </option>


                            <?php endforeach; ?>


                        <?php endif; ?>


                    </select>


                </div>



                <!-- =========================================
                     SERVICE TYPE
                ========================================== -->

                <div class="field">


                    <label for="maintenanceType">
                        Service Type
                    </label>


                    <select
                        name="maintenance_type"
                        id="maintenanceType"
                        required
                    >

                        <option value="">
                            Select service
                        </option>

                        <option value="Engine Oil Change">
                            Engine Oil Change
                        </option>

                        <option value="General Service">
                            General Service
                        </option>

                        <option value="Brake Inspection">
                            Brake Inspection
                        </option>

                        <option value="Tyre Rotation">
                            Tyre Rotation
                        </option>

                        <option value="Coolant Inspection">
                            Coolant Inspection
                        </option>

                        <option value="Battery Inspection">
                            Battery Inspection
                        </option>

                        <option value="Full Service">
                            Full Service
                        </option>

                    </select>


                </div>



                <!-- =========================================
                     DUE DATE
                ========================================== -->

                <div class="field">


                    <label for="dueDate">
                        Due Date
                    </label>


                    <input
                        type="date"
                        name="due_date"
                        id="dueDate"
                    >


                </div>



                <!-- =========================================
                     DUE MILEAGE
                ========================================== -->

                <div class="field">


                    <label for="dueMileage">
                        Due Mileage
                    </label>


                    <input

                        type="number"

                        name="due_mileage"

                        id="dueMileage"

                        min="0"

                        placeholder="Example: 100000"

                    >


                </div>



                <!-- =========================================
                     REMINDER
                ========================================== -->

                <div class="field">


                    <label for="reminderDays">
                        Reminder
                    </label>


                    <select
                        name="reminder_days_before"
                        id="reminderDays"
                    >

                        <option value="7">
                            7 days before
                        </option>

                        <option value="14">
                            14 days before
                        </option>

                        <option
                            value="30"
                            selected
                        >
                            30 days before
                        </option>

                    </select>


                </div>



                <!-- =========================================
                     NOTES
                ========================================== -->

                <div class="field full">


                    <label for="description">
                        Notes
                    </label>


                    <textarea

                        name="description"

                        id="description"

                        placeholder="Optional maintenance notes"

                    ></textarea>


                </div>



                <button

                    type="submit"

                    class="btn btn-primary full"

                    <?= count($vehicles) === 0
                        ?
                        "disabled"
                        :
                        "" ?>

                >

                    Create Schedule

                </button>


            </form>



            <!-- =============================================
                 RECOMMENDED INTERVALS
            ============================================== -->

            <div class="card recommended-box">


                <h2>
                    Recommended Intervals
                </h2>


                <p>

                    <strong>
                        Engine Oil:
                    </strong>

                    approximately every
                    5,000 km

                </p>


                <p>

                    <strong>
                        General Service:
                    </strong>

                    approximately every
                    10,000 km

                </p>


                <p>

                    <strong>
                        Tyre Rotation:
                    </strong>

                    approximately every
                    10,000 km

                </p>


                <p>

                    <strong>
                        Brake Inspection:
                    </strong>

                    approximately every
                    20,000 km

                </p>


                <p>

                    <strong>
                        Coolant:
                    </strong>

                    according to manufacturer guidance

                </p>


                <p class="muted">

                    AutoTrack automatically calculates
                    Engine Oil Change and General Service
                    schedules using the registered vehicle
                    mileage and average monthly usage.

                </p>


                <p class="muted">

                    Manufacturer recommendations should
                    always take priority where they differ.

                </p>


            </div>


        </section>



        <!-- =================================================
             EXISTING SCHEDULES
        ================================================== -->

        <section class="schedule-section">


            <div class="section-head">


                <h2>
                    Current Maintenance Schedules
                </h2>


                <?php if (
                    $selectedVehicle
                ): ?>


                    <span class="muted">

                        <?= e(
                            trim(
                                $selectedVehicle[
                                    "make"
                                ]
                                . " "
                                .
                                $selectedVehicle[
                                    "model"
                                ]
                            )
                        ) ?>

                    </span>


                <?php endif; ?>


            </div>



            <div class="card table-wrap">


                <table class="table">


                    <thead>

                        <tr>

                            <th>
                                Service
                            </th>

                            <th>
                                Due Date
                            </th>

                            <th>
                                Due Mileage
                            </th>

                            <th>
                                Reminder
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                SMS
                            </th>

                        </tr>

                    </thead>



                    <tbody>


                    <?php if (
                        count(
                            $schedules
                        )
                        === 0
                    ): ?>


                        <tr>

                            <td
                                colspan="6"
                                class="empty-message"
                            >

                                No maintenance schedules
                                available for this vehicle.

                            </td>

                        </tr>


                    <?php else: ?>


                        <?php foreach (
                            $schedules
                            as $schedule
                        ): ?>


                            <tr>


                                <td>

                                    <strong>

                                        <?= e(
                                            $schedule[
                                                "maintenance_type"
                                            ]
                                        ) ?>

                                    </strong>


                                    <?php if (
                                        !empty(
                                            $schedule[
                                                "description"
                                            ]
                                        )
                                    ): ?>


                                        <div class="muted">

                                            <?= e(
                                                $schedule[
                                                    "description"
                                                ]
                                            ) ?>

                                        </div>


                                    <?php endif; ?>


                                </td>



                                <td>


                                    <?php

                                    if (
                                        !empty(
                                            $schedule[
                                                "due_date"
                                            ]
                                        )
                                    ) {

                                        $timestamp =
                                            strtotime(
                                                $schedule[
                                                    "due_date"
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
                                                $schedule[
                                                    "due_date"
                                                ]
                                            );

                                    } else {

                                        echo "-";
                                    }

                                    ?>


                                </td>



                                <td>


                                    <?php if (
                                        $schedule[
                                            "due_mileage"
                                        ]
                                        !== null
                                    ): ?>


                                        <?= number_format(
                                            (int)
                                            $schedule[
                                                "due_mileage"
                                            ]
                                        ) ?>

                                        km


                                    <?php else: ?>


                                        -


                                    <?php endif; ?>


                                </td>



                                <td>

                                    <?= (int)
                                        $schedule[
                                            "reminder_days_before"
                                        ] ?>

                                    days before

                                </td>



                                <td>


                                    <?php

                                    $status =
                                        strtolower(
                                            $schedule[
                                                "schedule_status"
                                            ]
                                        );

                                    ?>


                                    <span
                                        class="
                                            schedule-badge
                                            schedule-<?= e(
                                                $status
                                            ) ?>
                                        "
                                    >

                                        <?= e(
                                            ucfirst(
                                                $status
                                            )
                                        ) ?>

                                    </span>


                                </td>



                                <td>


                                    <?php if (
                                        (int)
                                        $schedule[
                                            "sms_sent"
                                        ]
                                        === 1
                                    ): ?>


                                        ✅ Sent


                                    <?php else: ?>


                                        ⏳ Pending


                                    <?php endif; ?>


                                </td>


                            </tr>


                        <?php endforeach; ?>


                    <?php endif; ?>


                    </tbody>


                </table>


            </div>


        </section>



        <div class="footer-note">

            AutoTrack • Automobile Service and Maintenance Tracker

        </div>


    </main>


</div>



<script>

function changeVehicle(
    vehicleId
) {

    if (!vehicleId) {
        return;
    }

    window.location.href =
        "maintenance.php?vehicle_id="
        +
        encodeURIComponent(
            vehicleId
        );
}

</script>


<script src="js/app.js"></script>


</body>

</html>