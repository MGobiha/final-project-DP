<?php

session_start();

require_once 'config/database.php';
require_once 'includes/reminder-checker.php';

// =====================================================
// CHECK LOGIN
// =====================================================

if (
    !isset($_SESSION["user_id"])
    ||
    !isset($_SESSION["role"])
) {

    header(
        "Location: login.php"
    );

    exit();
}


// =====================================================
// VEHICLE OWNER ONLY
// =====================================================

if (
    $_SESSION["role"]
    !== "vehicle_owner"
) {

    switch (
        $_SESSION["role"]
    ) {

        case "system_admin":

            header(
                "Location: admin/dashboard.php"
            );

            exit();


        case "garage_admin":

            header(
                "Location: garage/dashboard.php"
            );

            exit();


        case "garage_staff":

            header(
                "Location: garage/staff/dashboard.php"
            );

            exit();


        default:

            header(
                "Location: logout.php"
            );

            exit();
    }
}


// after role check

$userId = (int) $_SESSION["user_id"];


// Run automatic reminder check
checkAutomaticReminders(
    $conn,
    $userId
);

// =====================================================
// LOAD REMINDERS
// =====================================================

$sql = "
    SELECT

        ms.schedule_id,
        ms.maintenance_type,
        ms.description,
        ms.last_service_date,
        ms.last_service_mileage,
        ms.due_date,
        ms.due_mileage,
        ms.reminder_days_before,
        ms.reminder_km_before,
        ms.schedule_status,
        ms.sms_enabled,
        ms.sms_sent,

        v.vehicle_id,
        v.registration_number,
        v.make,
        v.model,
        v.current_mileage

    FROM maintenance_schedule ms

    INNER JOIN vehicles v
        ON ms.vehicle_id =
           v.vehicle_id

    WHERE v.user_id = ?

    AND ms.schedule_status
        != 'completed'

    ORDER BY
        ms.due_date IS NULL,
        ms.due_date ASC,
        ms.due_mileage ASC
";


$stmt =
    mysqli_prepare(
        $conn,
        $sql
    );


if (!$stmt) {

    die(
        "Reminder query error: "
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


$reminderResult =
    mysqli_stmt_get_result(
        $stmt
    );


// =====================================================
// HELPER FUNCTION
// =====================================================

function getReminderStatus(
    array $row
): array {

    $status = "Upcoming";

    $class = "status-upcoming";

    $message = "";


    // =================================================
    // MILEAGE-BASED
    // =================================================

    if (
        !empty(
            $row["due_mileage"]
        )
    ) {

        $currentMileage =
            (int)
            ($row["current_mileage"] ?? 0);

        $dueMileage =
            (int)
            $row["due_mileage"];

        $reminderKm =
            (int)
            ($row["reminder_km_before"] ?? 500);


        $difference =
            $dueMileage
            -
            $currentMileage;


        if (
            $difference < 0
        ) {

            $status =
                "Overdue";

            $class =
                "status-overdue";

            $message =
                number_format(
                    abs($difference)
                )
                . " km overdue";

        } elseif (
            $difference === 0
        ) {

            $status =
                "Due Now";

            $class =
                "status-due";

            $message =
                "Service is due now";

        } elseif (
            $difference
            <=
            $reminderKm
        ) {

            $status =
                "Due Soon";

            $class =
                "status-due";

            $message =
                number_format(
                    $difference
                )
                . " km remaining";

        } else {

            $message =
                number_format(
                    $difference
                )
                . " km remaining";
        }
    }


    // =================================================
    // DATE-BASED
    // =================================================

    elseif (
        !empty(
            $row["due_date"]
        )
    ) {

        $today =
            new DateTime(
                date("Y-m-d")
            );

        $dueDate =
            new DateTime(
                $row[
                    "due_date"
                ]
            );


        $difference =
            (int)
            $today
                ->diff(
                    $dueDate
                )
                ->format(
                    "%r%a"
                );


        $reminderDays =
            (int)
            (
                $row[
                    "reminder_days_before"
                ]
                ?? 7
            );


        if (
            $difference < 0
        ) {

            $status =
                "Overdue";

            $class =
                "status-overdue";

            $message =
                abs($difference)
                . " day(s) overdue";

        } elseif (
            $difference === 0
        ) {

            $status =
                "Due Today";

            $class =
                "status-due";

            $message =
                "Due today";

        } elseif (
            $difference
            <=
            $reminderDays
        ) {

            $status =
                "Due Soon";

            $class =
                "status-due";

            $message =
                $difference
                . " day(s) remaining";

        } else {

            $message =
                $difference
                . " day(s) remaining";
        }
    }


    return [
        "status" => $status,
        "class" => $class,
        "message" => $message
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

    <title>
        Reminders - AutoTrack
    </title>


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
        href="css/dashboard-layout.css"
    >


    <style>

        .reminder-header {
            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 20px;

            margin-bottom: 28px;
        }

        .reminder-header h1 {
            margin: 0 0 6px;
        }

        .reminder-header p {
            margin: 0;

            color: #667085;
        }


        .reminder-grid {
            display: grid;

            grid-template-columns:
                repeat(
                    2,
                    minmax(0, 1fr)
                );

            gap: 18px;
        }


        .reminder-card {
            padding: 22px;

            border:
                1px solid
                #e4e7ec;

            border-radius: 16px;

            background: #ffffff;

            box-shadow:
                0 10px 30px
                rgba(
                    15,
                    35,
                    65,
                    .05
                );
        }


        .reminder-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;

            gap: 15px;

            margin-bottom: 16px;
        }


        .reminder-title {
            margin: 0 0 5px;

            font-size: 18px;
        }


        .vehicle-name {
            color: #667085;

            font-size: 14px;
        }


        .status-badge {
            display: inline-flex;

            padding:
                6px
                10px;

            border-radius: 999px;

            font-size: 12px;

            font-weight: 700;
        }


        .status-upcoming {
            background: #eff6ff;

            color: #1d4ed8;
        }


        .status-due {
            background: #fef3c7;

            color: #92400e;
        }


        .status-overdue {
            background: #fef2f2;

            color: #b91c1c;
        }


        .reminder-details {
            display: grid;

            gap: 10px;

            margin-top: 14px;
        }


        .detail-row {
            display: flex;

            justify-content: space-between;

            gap: 20px;

            padding-bottom: 10px;

            border-bottom:
                1px solid
                #eef1f5;

            font-size: 14px;
        }


        .detail-row span:first-child {
            color: #667085;
        }


        .remaining {
            margin-top: 14px;

            padding: 12px;

            border-radius: 10px;

            background: #f8fafc;

            font-weight: 600;
        }


        .description {
            margin-top: 14px;

            color: #667085;

            line-height: 1.6;

            font-size: 14px;
        }


        .empty-state {
            padding: 40px;

            text-align: center;

            color: #667085;
        }


        @media (
            max-width: 850px
        ) {

            .reminder-grid {
                grid-template-columns: 1fr;
            }
        }

    </style>

</head>


<body>


<div class="app-shell">


    <?php
    require_once
        'includes/sidebar.php';
    ?>


    <main class="main">


        <div class="reminder-header">

            <div>

                <h1>
                    Maintenance Reminders
                </h1>

                <p>
                    Automatically generated
                    from your vehicle information.
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

        </div>


        <?php if (
            mysqli_num_rows(
                $reminderResult
            ) > 0
        ): ?>


            <div class="reminder-grid">


                <?php while (
                    $reminder =
                    mysqli_fetch_assoc(
                        $reminderResult
                    )
                ): ?>


                    <?php

                    $reminderStatus =
                        getReminderStatus(
                            $reminder
                        );

                    ?>


                    <div class="reminder-card">


                        <div class="reminder-top">

                            <div>

                                <h3
                                    class="reminder-title"
                                >

                                    <?php
                                    echo
                                    htmlspecialchars(
                                        $reminder[
                                            "maintenance_type"
                                        ]
                                    );
                                    ?>

                                </h3>


                                <div
                                    class="vehicle-name"
                                >

                                    <?php

                                    echo
                                    htmlspecialchars(
                                        $reminder["make"]
                                        . " "
                                        .
                                        $reminder["model"]
                                        . " • "
                                        .
                                        $reminder[
                                            "registration_number"
                                        ]
                                    );

                                    ?>

                                </div>

                            </div>


                            <span
                                class="
                                    status-badge
                                    <?php
                                    echo
                                    $reminderStatus[
                                        "class"
                                    ];
                                    ?>
                                "
                            >

                                <?php
                                echo
                                htmlspecialchars(
                                    $reminderStatus[
                                        "status"
                                    ]
                                );
                                ?>

                            </span>

                        </div>


                        <div
                            class="reminder-details"
                        >


                            <?php if (
                                !empty(
                                    $reminder[
                                        "due_mileage"
                                    ]
                                )
                            ): ?>


                                <div
                                    class="detail-row"
                                >

                                    <span>
                                        Current Mileage
                                    </span>

                                    <strong>

                                        <?php
                                        echo
                                        number_format(
                                            $reminder[
                                                "current_mileage"
                                            ]
                                        );
                                        ?>

                                        km

                                    </strong>

                                </div>


                                <div
                                    class="detail-row"
                                >

                                    <span>
                                        Due Mileage
                                    </span>

                                    <strong>

                                        <?php
                                        echo
                                        number_format(
                                            $reminder[
                                                "due_mileage"
                                            ]
                                        );
                                        ?>

                                        km

                                    </strong>

                                </div>


                            <?php endif; ?>


                            <?php if (
                                !empty(
                                    $reminder[
                                        "due_date"
                                    ]
                                )
                            ): ?>


                                <div
                                    class="detail-row"
                                >

                                    <span>
                                        Due Date
                                    </span>

                                    <strong>

                                        <?php
                                        echo
                                        date(
                                            "d M Y",
                                            strtotime(
                                                $reminder[
                                                    "due_date"
                                                ]
                                            )
                                        );
                                        ?>

                                    </strong>

                                </div>


                            <?php endif; ?>


                        </div>


                        <div class="remaining">

                            <?php
                            echo
                            htmlspecialchars(
                                $reminderStatus[
                                    "message"
                                ]
                            );
                            ?>

                        </div>


                        <?php if (
                            !empty(
                                $reminder[
                                    "description"
                                ]
                            )
                        ): ?>

                            <div
                                class="description"
                            >

                                <?php
                                echo
                                htmlspecialchars(
                                    $reminder[
                                        "description"
                                    ]
                                );
                                ?>

                            </div>

                        <?php endif; ?>


                    </div>


                <?php endwhile; ?>


            </div>


        <?php else: ?>


            <div
                class="
                    card
                    empty-state
                "
            >

                No maintenance reminders
                are available yet.

            </div>


        <?php endif; ?>


    </main>


</div>


</body>

</html>