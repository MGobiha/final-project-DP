<?php


function checkAutomaticReminders(
    mysqli $conn,
    int $userId
): void {


    // =====================================================
    // GET VEHICLE MAINTENANCE SCHEDULES
    // =====================================================

    $sql = "
        SELECT
            ms.schedule_id,
            ms.vehicle_id,
            ms.maintenance_type,
            ms.description,
            ms.due_date,
            ms.due_mileage,
            ms.reminder_days_before,
            ms.reminder_km_before,
            ms.schedule_status,

            v.registration_number,
            v.make,
            v.model,
            v.current_mileage,

            u.mobile_number,
            u.maintenance_sms

        FROM maintenance_schedule ms

        INNER JOIN vehicles v
            ON ms.vehicle_id =
               v.vehicle_id

        INNER JOIN users u
            ON v.user_id =
               u.user_id

        WHERE v.user_id = ?

        AND ms.schedule_status
            != 'completed'
    ";


    $stmt =
        mysqli_prepare(
            $conn,
            $sql
        );


    if (!$stmt) {

        throw new Exception(
            "Reminder checker error: "
            . mysqli_error($conn)
        );
    }


    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $userId
    );


    mysqli_stmt_execute($stmt);


    $result =
        mysqli_stmt_get_result($stmt);


    // =====================================================
    // CHECK EVERY SCHEDULE
    // =====================================================

    while (
        $schedule =
        mysqli_fetch_assoc($result)
    ) {


        $newStatus =
            "upcoming";


        // =================================================
        // MILEAGE REMINDER
        // =================================================

        if (
            !empty(
                $schedule[
                    "due_mileage"
                ]
            )
        ) {

            $currentMileage =
                (int)
                $schedule[
                    "current_mileage"
                ];


            $dueMileage =
                (int)
                $schedule[
                    "due_mileage"
                ];


            $reminderKm =
                (int)
                (
                    $schedule[
                        "reminder_km_before"
                    ]
                    ?? 500
                );


            $remainingKm =
                $dueMileage
                -
                $currentMileage;


            if (
                $remainingKm < 0
            ) {

                $newStatus =
                    "overdue";

            } elseif (
                $remainingKm
                <=
                $reminderKm
            ) {

                $newStatus =
                    "due";
            }
        }


        // =================================================
        // DATE REMINDER
        // =================================================

        if (
            !empty(
                $schedule[
                    "due_date"
                ]
            )
        ) {

            $today =
                new DateTime(
                    date("Y-m-d")
                );


            $dueDate =
                new DateTime(
                    $schedule[
                        "due_date"
                    ]
                );


            $daysRemaining =
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
                    $schedule[
                        "reminder_days_before"
                    ]
                    ?? 7
                );


            if (
                $daysRemaining < 0
            ) {

                $newStatus =
                    "overdue";

            } elseif (
                $daysRemaining
                <=
                $reminderDays
                &&
                $newStatus
                !== "overdue"
            ) {

                $newStatus =
                    "due";
            }
        }


        // =================================================
        // UPDATE DATABASE STATUS
        // =================================================

        if (
            $schedule[
                "schedule_status"
            ]
            !==
            $newStatus
        ) {

            $updateSql = "
                UPDATE maintenance_schedule

                SET schedule_status = ?

                WHERE schedule_id = ?
            ";


            $updateStmt =
                mysqli_prepare(
                    $conn,
                    $updateSql
                );


            mysqli_stmt_bind_param(
                $updateStmt,
                "si",
                $newStatus,
                $schedule[
                    "schedule_id"
                ]
            );


            mysqli_stmt_execute(
                $updateStmt
            );


            mysqli_stmt_close(
                $updateStmt
            );
        }


        // =================================================
        // CREATE / UPDATE NOTIFICATION
        // ONLY WHEN DUE OR OVERDUE
        // =================================================

        if (
            $newStatus === "due"
            ||
            $newStatus === "overdue"
        ) {

            createDueNotification(
                $conn,
                $userId,
                $schedule,
                $newStatus
            );
        }

    }


    mysqli_stmt_close($stmt);
}


// =====================================================
// CREATE OR UPDATE NOTIFICATION
// =====================================================

function createDueNotification(
    mysqli $conn,
    int $userId,
    array $schedule,
    string $status
): void {


    $scheduleId =
        (int)
        $schedule[
            "schedule_id"
        ];


    $vehicleId =
        (int)
        $schedule[
            "vehicle_id"
        ];


    $title =
        $schedule[
            "maintenance_type"
        ];


    // =================================================
    // MESSAGE
    // =================================================

    if (
        !empty(
            $schedule[
                "due_mileage"
            ]
        )
    ) {

        $remaining =
            (int)
            $schedule[
                "due_mileage"
            ]
            -
            (int)
            $schedule[
                "current_mileage"
            ];


        if (
            $status === "overdue"
        ) {

            $message =
                $title
                . " is overdue by "
                . number_format(
                    abs($remaining)
                )
                . " km.";

        } else {

            $message =
                $title
                . " is due soon. "
                . number_format(
                    max(
                        $remaining,
                        0
                    )
                )
                . " km remaining.";
        }

    } else {

        if (
            $status === "overdue"
        ) {

            $message =
                $title
                . " is overdue.";

        } else {

            $message =
                $title
                . " is due soon.";
        }
    }


    // =================================================
    // CHECK EXISTING NOTIFICATION
    // =================================================

    $checkSql = "
        SELECT notification_id

        FROM notifications

        WHERE user_id = ?
        AND schedule_id = ?
        AND notification_type =
            'maintenance'

        LIMIT 1
    ";


    $checkStmt =
        mysqli_prepare(
            $conn,
            $checkSql
        );


    mysqli_stmt_bind_param(
        $checkStmt,
        "ii",
        $userId,
        $scheduleId
    );


    mysqli_stmt_execute(
        $checkStmt
    );


    $checkResult =
        mysqli_stmt_get_result(
            $checkStmt
        );


    $existing =
        mysqli_fetch_assoc(
            $checkResult
        );


    mysqli_stmt_close(
        $checkStmt
    );


    // =================================================
    // UPDATE EXISTING
    // =================================================

    if ($existing) {

        $updateSql = "
            UPDATE notifications

            SET
                title = ?,
                message = ?,
                notification_status =
                    'pending'

            WHERE notification_id = ?
        ";


        $updateStmt =
            mysqli_prepare(
                $conn,
                $updateSql
            );


        mysqli_stmt_bind_param(
            $updateStmt,
            "ssi",
            $title,
            $message,
            $existing[
                "notification_id"
            ]
        );


        mysqli_stmt_execute(
            $updateStmt
        );


        mysqli_stmt_close(
            $updateStmt
        );


        return;
    }


    // =================================================
    // CREATE NEW
    // =================================================

    $notificationType =
        "maintenance";


    $channel =
        "system";


    $notificationStatus =
        "pending";


    $mobileNumber =
        $schedule[
            "mobile_number"
        ]
        ?? null;


    $insertSql = "
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
            ?, ?, ?, ?, ?, ?, ?, ?, ?
        )
    ";


    $insertStmt =
        mysqli_prepare(
            $conn,
            $insertSql
        );


    mysqli_stmt_bind_param(
        $insertStmt,
        "iiissssss",
        $userId,
        $vehicleId,
        $scheduleId,
        $notificationType,
        $title,
        $message,
        $channel,
        $mobileNumber,
        $notificationStatus
    );


    mysqli_stmt_execute(
        $insertStmt
    );


    mysqli_stmt_close(
        $insertStmt
    );
}