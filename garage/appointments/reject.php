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
        a.service_type,
        a.appointment_date,
        a.appointment_time,
        a.appointment_status,

        u.first_name,
        u.last_name,

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

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Unable to prepare appointment query: " . mysqli_error($conn));
}

mysqli_stmt_bind_param(
    $stmt,
    "ii",
    $appointmentId,
    $garageId
);

mysqli_stmt_execute($stmt);

$result =
    mysqli_stmt_get_result($stmt);

$appointment =
    mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


if (!$appointment) {
    header("Location: index.php?error=notfound");
    exit();
}


// ==========================================================
// ONLY PENDING APPOINTMENTS CAN BE REJECTED
// ==========================================================

if (
    $appointment["appointment_status"]
    !== "pending"
) {
    header("Location: index.php?error=status");
    exit();
}


$errorMessage = "";


// ==========================================================
// SUBMIT REJECTION
// ==========================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $garageNote =
        trim(
            $_POST["garage_note"] ?? ""
        );

    if ($garageNote === "") {

        $errorMessage =
            "Please enter a reason for rejecting this appointment.";

    } else {

        /*
         * Your appointments table uses appointment_status.
         *
         * If your enum contains 'cancelled' rather than 'rejected',
         * we use cancelled here.
         */

        $updateSql = "
            UPDATE appointments

            SET
                appointment_status = 'cancelled',
                garage_note = ?,
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
                "Unable to prepare rejection: "
                . mysqli_error($conn);

        } else {

            mysqli_stmt_bind_param(
                $updateStmt,
                "sii",
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

                header(
                    "Location: index.php?rejected=1"
                );

                exit();

            } else {

                $errorMessage =
                    "Unable to reject appointment: "
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
        Reject Appointment - AutoTrack
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
            max-width: 850px;
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
                repeat(2, minmax(0, 1fr));
            gap: 14px;

            margin-bottom: 24px;
        }

        .summary-item {
            padding: 16px;
            border: 1px solid #e2e8f0;
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

        .reject-actions {
            display: flex;
            gap: 12px;
            margin-top: 18px;
        }

        @media (max-width: 700px) {

            .appointment-summary {
                grid-template-columns: 1fr;
            }

            .reject-actions {
                flex-direction: column;
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


        <header class="appointment-action-header">

            <h1>
                Reject Appointment
            </h1>

            <p>
                Enter a reason before rejecting this customer service request.
            </p>

        </header>


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
                        $appointment["registration_number"]
                    ) ?>

                </div>


                <div class="summary-item">

                    <span>
                        Service
                    </span>

                    <strong>
                        <?= htmlspecialchars(
                            $appointment["service_type"]
                        ) ?>
                    </strong>

                </div>


                <div class="summary-item">

                    <span>
                        Requested Appointment
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


            <form method="POST">

                <input
                    type="hidden"
                    name="appointment_id"
                    value="<?= $appointmentId ?>"
                >


                <div class="field">

                    <label for="garageNote">
                        Rejection Reason
                    </label>

                    <textarea
                        id="garageNote"
                        name="garage_note"
                        placeholder="Example: We are fully booked on the requested date."
                        required
                    ><?= htmlspecialchars(
                        $_POST["garage_note"] ?? "",
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?></textarea>

                </div>


                <div class="reject-actions">

                    <button
                        type="submit"
                        class="btn btn-danger"
                    >
                        Confirm Rejection
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