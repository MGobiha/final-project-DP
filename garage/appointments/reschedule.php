<?php

require_once '../auth.php';

$currentPage = "appointments";

$appointmentId =
    isset($_GET["id"])
        ? (int) $_GET["id"]
        : (
            isset($_POST["appointment_id"])
                ? (int) $_POST["appointment_id"]
                : 0
        );

if ($appointmentId <= 0) {

    header("Location: index.php");
    exit();
}


// ==========================================================
// LOAD APPOINTMENT
// ==========================================================

$sql = "
    SELECT
        a.appointment_id,
        a.user_id,
        a.vehicle_id,
        a.service_type,
        a.service_type,
        a.appointment_date,
        a.appointment_time,
        a.customer_note,
        a.garage_note,
        a.appointment_status,

        u.first_name,
        u.last_name,
        u.mobile_number,

        v.make,
        v.model,
        v.registration_number

    FROM appointments a

    INNER JOIN users u
        ON u.user_id = a.user_id

    INNER JOIN vehicles v
        ON v.vehicle_id = a.vehicle_id

    WHERE a.appointment_id = ?
    AND a.garage_id = ?

    LIMIT 1
";


$stmt =
    mysqli_prepare(
        $conn,
        $sql
    );

if (!$stmt) {

    die(
        "Unable to prepare appointment query: "
        . mysqli_error($conn)
    );
}


mysqli_stmt_bind_param(
    $stmt,
    "ii",
    $appointmentId,
    $garageId
);


mysqli_stmt_execute(
    $stmt
);


$result =
    mysqli_stmt_get_result(
        $stmt
    );


$appointment =
    mysqli_fetch_assoc(
        $result
    );


mysqli_stmt_close(
    $stmt
);


if (!$appointment) {

    header(
        "Location: index.php?error=notfound"
    );

    exit();
}


// ==========================================================
// ONLY PENDING APPOINTMENT CAN BE RESCHEDULED
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


$errorMessage = "";


// ==========================================================
// HANDLE RESCHEDULE
// ==========================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $newDate =
        trim(
            $_POST["appointment_date"]
            ?? ""
        );

    $newTime =
        trim(
            $_POST["appointment_time"]
            ?? ""
        );

    $garageNote =
        trim(
            $_POST["garage_note"]
            ?? ""
        );


    // ======================================================
    // VALIDATION
    // ======================================================

    if (
        $newDate === ""
        ||
        $newTime === ""
    ) {

        $errorMessage =
            "Please select a new date and time.";

    } else {


        // ==================================================
        // UPDATE APPOINTMENT
        // ==================================================

        $updateSql = "
            UPDATE appointments

            SET
                appointment_date = ?,
                appointment_time = ?,
                garage_note = ?,
                appointment_status = 'confirmed',
                sms_sent = 0,
                updated_at = CURRENT_TIMESTAMP

            WHERE appointment_id = ?
            AND garage_id = ?
            AND appointment_status = 'pending'
        ";


        $updateStmt =
            mysqli_prepare(
                $conn,
                $updateSql
            );


        if (!$updateStmt) {

            $errorMessage =
                "Unable to prepare reschedule: "
                . mysqli_error($conn);

        } else {


            mysqli_stmt_bind_param(
                $updateStmt,
                "sssii",
                $newDate,
                $newTime,
                $garageNote,
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


    // ==========================================
    // CREATE VEHICLE OWNER NOTIFICATION
    // ==========================================

    $notificationTitle =
        "Appointment Rescheduled";


    $notificationMessage =
        "Your "
        . $appointment["service_type"]
        . " appointment has been rescheduled to "
        . date(
            "d M Y",
            strtotime($newDate)
        )
        . " at "
        . date(
            "h:i A",
            strtotime($newTime)
        )
        . ".";


    // Add garage note if entered

    if ($garageNote !== "") {

        $notificationMessage .=
            " Garage note: "
            . $garageNote;
    }


    $notificationSql = "
        INSERT INTO notifications
        (
            user_id,
            vehicle_id,
            schedule_id,
            notification_type,
            title,
            message,
            channel,
            mobile_number,
            notification_status
        )

        VALUES
        (
            ?,
            ?,
            NULL,
            'appointment',
            ?,
            ?,
            'system',
            ?,
            'pending'
        )
    ";


    $notificationStmt =
        mysqli_prepare(
            $conn,
            $notificationSql
        );


    if ($notificationStmt) {

        mysqli_stmt_bind_param(
            $notificationStmt,
            "iisss",
            $appointment["user_id"],
            $appointment["vehicle_id"],
            $notificationTitle,
            $notificationMessage,
            $appointment["mobile_number"]
        );


        mysqli_stmt_execute(
            $notificationStmt
        );


        mysqli_stmt_close(
            $notificationStmt
        );
    }


    // ==========================================
    // RETURN TO APPOINTMENTS
    // ==========================================

    header(
        "Location: index.php?rescheduled=1"
    );

    exit();
} else {

                $errorMessage =
                    "Unable to reschedule appointment: "
                    . mysqli_stmt_error(
                        $updateStmt
                    );


                mysqli_stmt_close(
                    $updateStmt
                );
            }
        }
    }
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

    <title>
        Reschedule Appointment - AutoTrack
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
        href="/automobile_tracker/css/garage-admin.css"
    >


    <style>

        .appointment-action-page {
            max-width: 900px;
        }


        .appointment-action-header {
            margin-bottom: 25px;
        }


        .appointment-action-header h1 {
            margin: 0 0 7px;
            font-size: 30px;
        }


        .appointment-action-header p {
            margin: 0;
            color: #64748b;
        }


        .appointment-summary {
            display: grid;

            grid-template-columns:
                repeat(
                    2,
                    minmax(0, 1fr)
                );

            gap: 14px;

            margin-bottom: 24px;
        }


        .summary-item {
            padding: 16px;

            border:
                1px solid
                #e2e8f0;

            border-radius: 12px;

            background: #f8fafc;
        }


        .summary-item span {
            display: block;

            margin-bottom: 5px;

            color: #64748b;

            font-size: 13px;
        }


        .summary-item strong {
            color: #0f172a;
        }


        .reschedule-form {
            display: grid;

            grid-template-columns:
                repeat(
                    2,
                    minmax(0, 1fr)
                );

            gap: 18px;
        }


        .reschedule-form .full {
            grid-column: 1 / -1;
        }


        .reschedule-actions {
            display: flex;

            gap: 12px;

            flex-wrap: wrap;

            margin-top: 5px;
        }


        @media (max-width: 700px) {

            .appointment-summary,
            .reschedule-form {
                grid-template-columns: 1fr;
            }


            .reschedule-form .full {
                grid-column: auto;
            }

        }

    </style>

</head>


<body>


<?php

require_once '../../includes/garage-sidebar.php';

?>


<main class="garage-main">


    <div class="appointment-action-page">


        <!-- HEADER -->

        <header class="appointment-action-header">

            <h1>
                Reschedule Appointment
            </h1>

            <p>
                Select a new appointment date and time for this customer.
            </p>

        </header>



        <!-- ERROR -->

        <?php if ($errorMessage !== ""): ?>

            <div class="alert alert-danger">

                <?= htmlspecialchars(
                    $errorMessage,
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>

            </div>

        <?php endif; ?>



        <div class="card">


            <!-- APPOINTMENT SUMMARY -->

            <div class="appointment-summary">


                <div class="summary-item">

                    <span>
                        Customer
                    </span>

                    <strong>

                        <?= htmlspecialchars(
                            $appointment["first_name"]
                            . " "
                            . $appointment["last_name"]
                        ) ?>

                    </strong>

                </div>



                <div class="summary-item">

                    <span>
                        Vehicle
                    </span>

                    <strong>

                        <?= htmlspecialchars(
                            $appointment["make"]
                            . " "
                            . $appointment["model"]
                        ) ?>

                    </strong>

                    <br>

                    <?= htmlspecialchars(
                        $appointment[
                            "registration_number"
                        ]
                    ) ?>

                </div>



                <div class="summary-item">

                    <span>
                        Service
                    </span>

                    <strong>

                        <?= htmlspecialchars(
                            $appointment[
                                "service_type"
                            ]
                        ) ?>

                    </strong>

                </div>



                <div class="summary-item">

                    <span>
                        Current Appointment
                    </span>

                    <strong>

                        <?= date(
                            "d M Y",
                            strtotime(
                                $appointment[
                                    "appointment_date"
                                ]
                            )
                        ) ?>

                        at

                        <?= date(
                            "h:i A",
                            strtotime(
                                $appointment[
                                    "appointment_time"
                                ]
                            )
                        ) ?>

                    </strong>

                </div>


            </div>



            <!-- RESCHEDULE FORM -->

            <form
                method="POST"
                class="reschedule-form"
            >


                <input
                    type="hidden"
                    name="appointment_id"
                    value="<?= $appointmentId ?>"
                >



                <!-- NEW DATE -->

                <div class="field">

                    <label for="appointmentDate">
                        New Date
                    </label>

                    <input
                        id="appointmentDate"
                        type="date"
                        name="appointment_date"
                        min="<?= date("Y-m-d") ?>"
                        value="<?= htmlspecialchars(
                            $_POST["appointment_date"]
                            ??
                            $appointment[
                                "appointment_date"
                            ]
                        ) ?>"
                        required
                    >

                </div>



                <!-- NEW TIME -->

                <div class="field">

                    <label for="appointmentTime">
                        New Time
                    </label>

                    <input
                        id="appointmentTime"
                        type="time"
                        name="appointment_time"
                        value="<?= htmlspecialchars(
                            $_POST["appointment_time"]
                            ??
                            $appointment[
                                "appointment_time"
                            ]
                        ) ?>"
                        required
                    >

                </div>



                <!-- NOTE -->

                <div class="field full">

                    <label for="garageNote">
                        Garage Note
                    </label>

                    <textarea
                        id="garageNote"
                        name="garage_note"
                        placeholder="Example: Requested time is unavailable. We have proposed a new appointment time."
                    ><?= htmlspecialchars(
                        $_POST["garage_note"]
                        ??
                        $appointment[
                            "garage_note"
                        ]
                        ??
                        "",
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?></textarea>

                </div>



                <!-- ACTIONS -->

                <div class="full reschedule-actions">


                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Confirm Reschedule
                    </button>


                    <a
                        href="index.php"
                        class="btn btn-secondary"
                    >
                        Cancel
                    </a>


                </div>


            </form>


        </div>


    </div>


</main>


</body>

</html>