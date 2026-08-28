<?php

require_once '../auth.php';

$currentPage = "appointments";

/*
|--------------------------------------------------------------------------
| Get appointments for THIS garage only
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        a.appointment_id,
        a.user_id,
        a.vehicle_id,
        a.service_type,
        a.appointment_date,
        a.appointment_time,
        a.customer_note,
        a.garage_note,
        a.appointment_status,
        a.sms_sent,
        a.created_at,

        u.first_name,
        u.last_name,
        u.email,
        u.mobile_number,

        v.make,
        v.model,
        v.registration_number

    FROM appointments a

    INNER JOIN users u
        ON u.user_id = a.user_id

    INNER JOIN vehicles v
        ON v.vehicle_id = a.vehicle_id

    WHERE a.garage_id = ?

    ORDER BY
        CASE
            WHEN a.appointment_status = 'pending' THEN 1
            WHEN a.appointment_status = 'confirmed' THEN 2
            WHEN a.appointment_status = 'in_progress' THEN 3
            ELSE 4
        END,
        a.appointment_date ASC,
        a.appointment_time ASC
";


$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $garageId
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Appointments | Garage Admin</title>

    <link
        rel="stylesheet"
        href="/automobile_tracker/css/garage-admin.css"
    >

</head>

<body>

<div class="app-shell">

<?php

$currentPage = "appointments";

require_once '../../includes/garage-sidebar.php';

?>

<main class="garage-main">

        <div class="topbar">

            <div>

                <h1>Appointments</h1>

                <p>
                    <?= htmlspecialchars($garage["garage_name"] ?? "Garage") ?>
                </p>

            </div>

        </div>


        <?php if (isset($_GET["approved"])): ?>

            <div class="alert alert-success">
                Appointment approved successfully.
            </div>

        <?php endif; ?>


        <?php if (isset($_GET["rescheduled"])): ?>

            <div class="alert alert-success">
                Appointment rescheduled successfully.
            </div>

        <?php endif; ?>


        <?php if (isset($_GET["rejected"])): ?>

            <div class="alert alert-success">
                Appointment rejected successfully.
            </div>

        <?php endif; ?>


        <div class="card">

            <div class="section-head">

                <div>

                    <h2>
                        Customer Service Requests
                    </h2>

                    <p class="muted">
                        Review and manage service appointments requested by customers.
                    </p>

                </div>

            </div>


            <div class="table-wrap">

                <table class="table">

                    <thead>

                    <tr>

                        <th>Customer</th>

                        <th>Vehicle</th>

                        <th>Service</th>

                        <th>Date</th>

                        <th>Time</th>

                        <th>Customer Note</th>

                        <th>Status</th>

                        <th>SMS</th>

                        <th>Actions</th>

                    </tr>

                    </thead>


                    <tbody>

                    <?php if (mysqli_num_rows($result) > 0): ?>


                        <?php while ($row = mysqli_fetch_assoc($result)): ?>

                            <tr>

                                <!-- CUSTOMER -->

                                <td>

                                    <strong>

                                        <?= htmlspecialchars(
                                            $row["first_name"] . " " .
                                            $row["last_name"]
                                        ) ?>

                                    </strong>

                                    <br>

                                    <span class="muted">

                                        <?= htmlspecialchars(
                                            $row["mobile_number"] ?? ""
                                        ) ?>

                                    </span>

                                </td>


                                <!-- VEHICLE -->

                                <td>

                                    <strong>

                                        <?= htmlspecialchars(
                                            $row["make"] . " " .
                                            $row["model"]
                                        ) ?>

                                    </strong>

                                    <br>

                                    <span class="muted">

                                        <?= htmlspecialchars(
                                            $row["registration_number"]
                                        ) ?>

                                    </span>

                                </td>


                                <!-- SERVICE -->

                                <td>

                                    <?= htmlspecialchars(
                                        $row["service_type"]
                                    ) ?>

                                </td>


                                <!-- DATE -->

                                <td>

                                    <?= date(
                                        "d M Y",
                                        strtotime(
                                            $row["appointment_date"]
                                        )
                                    ) ?>

                                </td>


                                <!-- TIME -->

                                <td>

                                    <?= date(
                                        "h:i A",
                                        strtotime(
                                            $row["appointment_time"]
                                        )
                                    ) ?>

                                </td>


                                <!-- CUSTOMER NOTE -->

                                <td>

                                    <?php

                                    if (
                                        !empty(
                                            $row["customer_note"]
                                        )
                                    ) {

                                        echo htmlspecialchars(
                                            $row["customer_note"]
                                        );

                                    } else {

                                        echo '<span class="muted">—</span>';

                                    }

                                    ?>

                                </td>


                                <!-- STATUS -->

                                <td>

                                    <?php

                                    $status =
                                        $row[
                                            "appointment_status"
                                        ];

                                    if (
                                        $status === "pending"
                                    ) {

                                        echo '
                                            <span class="badge badge-warning">
                                                Pending
                                            </span>
                                        ';

                                    }
                                    elseif (
                                        $status === "confirmed"
                                    ) {

                                        echo '
                                            <span class="badge badge-success">
                                                Confirmed
                                            </span>
                                        ';

                                    }
                                    elseif (
                                        $status === "in_progress"
                                    ) {

                                        echo '
                                            <span class="badge badge-warning">
                                                In Progress
                                            </span>
                                        ';

                                    }
                                    elseif (
                                        $status === "completed"
                                    ) {

                                        echo '
                                            <span class="badge badge-success">
                                                Completed
                                            </span>
                                        ';

                                    }
                                    else {

                                        echo '
                                            <span class="badge badge-danger">
                                                ' .
                                                htmlspecialchars(
                                                    ucfirst($status)
                                                ) .
                                            '
                                            </span>
                                        ';

                                    }

                                    ?>

                                </td>


                                <!-- SMS -->

                                <td>

                                    <?php if ((int)$row["sms_sent"] === 1): ?>

                                        <span class="badge badge-success">
                                            ✓ Sent
                                        </span>

                                    <?php else: ?>

                                        <span class="muted">
                                            Pending
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- ACTIONS -->

                                <td>

                                    <?php
                                    if (
                                        $row["appointment_status"]
                                        === "pending"
                                    ):
                                    ?>

                                        <div
                                            style="
                                                display:flex;
                                                gap:7px;
                                                flex-wrap:wrap;
                                            "
                                        >

                                            <a
                                                href="approve.php?id=<?= (int)$row["appointment_id"] ?>"
                                                class="btn btn-primary"
                                            >
                                                Approve
                                            </a>


                                            <a
                                                href="reschedule.php?id=<?= (int)$row["appointment_id"] ?>"
                                                class="btn btn-secondary"
                                            >
                                                Reschedule
                                            </a>


                                            <a
                                                href="reject.php?id=<?= (int)$row["appointment_id"] ?>"
                                                class="btn btn-danger"
                                            >
                                                Reject
                                            </a>

                                        </div>


                                    <?php else: ?>


                                        <span class="muted">

                                            <?= htmlspecialchars(
                                                $row["garage_note"]
                                                ?: "No action required"
                                            ) ?>

                                        </span>


                                    <?php endif; ?>

                                </td>

                            </tr>

                        <?php endwhile; ?>


                    <?php else: ?>


                        <tr>

                            <td
                                colspan="9"
                                style="
                                    text-align:center;
                                    padding:40px;
                                "
                            >

                                <span class="muted">

                                    No service appointments found.

                                </span>

                            </td>

                        </tr>


                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </main>

</div>

</body>

</html>