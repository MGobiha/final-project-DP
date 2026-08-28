<?php

require_once __DIR__ . '/sms-functions.php';


// =====================================================
// CHECK AUTOMATIC MAINTENANCE REMINDERS
// =====================================================

function checkAutomaticReminders(
    mysqli $conn,
    int $userId
): void {


    // =====================================================
    // LOAD MAINTENANCE SCHEDULES FOR THIS VEHICLE OWNER
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
            ms.sms_enabled,
            ms.sms_sent,

            v.registration_number,
            v.make,
            v.model,
            v.current_mileage,

            u.mobile_number,
            u.maintenance_sms

        FROM maintenance_schedule ms

        INNER JOIN vehicles v
            ON ms.vehicle_id = v.vehicle_id

        INNER JOIN users u
            ON v.user_id = u.user_id

        WHERE v.user_id = ?

        AND ms.schedule_status != 'completed'
    ";


    $stmt = mysqli_prepare(
        $conn,
        $sql
    );


    if (!$stmt) {

        throw new Exception(
            "Reminder checker query error: "
            . mysqli_error($conn)
        );
    }


    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $userId
    );


    if (!mysqli_stmt_execute($stmt)) {

        throw new Exception(
            "Reminder checker execution error: "
            . mysqli_stmt_error($stmt)
        );
    }


    $result =
        mysqli_stmt_get_result($stmt);


    // =====================================================
    // CHECK EACH MAINTENANCE SCHEDULE
    // =====================================================

    while (
        $schedule =
        mysqli_fetch_assoc($result)
    ) {


        $newStatus = "upcoming";


        // =================================================
        // MILEAGE BASED REMINDER
        // =================================================

        if (
            $schedule["due_mileage"] !== null
            &&
            $schedule["due_mileage"] !== ""
        ) {

            $currentMileage =
                (int) (
                    $schedule[
                        "current_mileage"
                    ]
                    ?? 0
                );


            $dueMileage =
                (int)
                $schedule[
                    "due_mileage"
                ];


            $reminderKm =
                (int) (
                    $schedule[
                        "reminder_km_before"
                    ]
                    ?? 500
                );


            $remainingKm =
                $dueMileage
                -
                $currentMileage;


            // Already passed due mileage
            if (
                $remainingKm < 0
            ) {

                $newStatus =
                    "overdue";

            }

            // Inside reminder range
            elseif (
                $remainingKm
                <=
                $reminderKm
            ) {

                $newStatus =
                    "due";
            }
        }


        // =================================================
        // DATE BASED REMINDER
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
                (int) (
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
        // UPDATE MAINTENANCE STATUS
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


            if (!$updateStmt) {

                throw new Exception(
                    "Unable to prepare maintenance status update: "
                    . mysqli_error($conn)
                );
            }


            $scheduleId =
                (int)
                $schedule[
                    "schedule_id"
                ];


            mysqli_stmt_bind_param(
                $updateStmt,
                "si",
                $newStatus,
                $scheduleId
            );


            if (
                !mysqli_stmt_execute(
                    $updateStmt
                )
            ) {

                throw new Exception(
                    "Unable to update maintenance status: "
                    . mysqli_stmt_error(
                        $updateStmt
                    )
                );
            }


            mysqli_stmt_close(
                $updateStmt
            );
        }


        // =================================================
        // CREATE NOTIFICATION + SEND SMS
        // ONLY IF DUE / OVERDUE
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


    mysqli_stmt_close(
        $stmt
    );
}


// =====================================================
// CREATE OR UPDATE SYSTEM NOTIFICATION
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


    $maintenanceType =
        $schedule[
            "maintenance_type"
        ];


    $vehicleName =
        trim(
            (
                $schedule[
                    "make"
                ]
                ?? ""
            )
            .
            " "
            .
            (
                $schedule[
                    "model"
                ]
                ?? ""
            )
        );


    $registrationNumber =
        $schedule[
            "registration_number"
        ]
        ?? "";


    $title =
        $maintenanceType;


    // =====================================================
    // CREATE MESSAGE
    // =====================================================

    if (
        $schedule["due_mileage"] !== null
        &&
        $schedule["due_mileage"] !== ""
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
                $maintenanceType
                . " for "
                . $vehicleName
                . " ("
                . $registrationNumber
                . ") is overdue by "
                . number_format(
                    abs($remaining)
                )
                . " km.";

        } else {

            $message =
                $maintenanceType
                . " for "
                . $vehicleName
                . " ("
                . $registrationNumber
                . ") is due soon. "
                . number_format(
                    max(
                        $remaining,
                        0
                    )
                )
                . " km remaining.";
        }

    } elseif (
        !empty(
            $schedule[
                "due_date"
            ]
        )
    ) {

        $formattedDate =
            date(
                "d M Y",
                strtotime(
                    $schedule[
                        "due_date"
                    ]
                )
            );


        if (
            $status === "overdue"
        ) {

            $message =
                $maintenanceType
                . " for "
                . $vehicleName
                . " ("
                . $registrationNumber
                . ") was due on "
                . $formattedDate
                . ".";

        } else {

            $message =
                $maintenanceType
                . " for "
                . $vehicleName
                . " ("
                . $registrationNumber
                . ") is due on "
                . $formattedDate
                . ".";
        }

    } else {

        if (
            $status === "overdue"
        ) {

            $message =
                $maintenanceType
                . " for "
                . $vehicleName
                . " is overdue.";

        } else {

            $message =
                $maintenanceType
                . " for "
                . $vehicleName
                . " is due soon.";
        }
    }


    // =====================================================
    // CHECK WHETHER NOTIFICATION ALREADY EXISTS
    // =====================================================

    $checkSql = "
        SELECT notification_id

        FROM notifications

        WHERE user_id = ?
        AND schedule_id = ?
        AND notification_type = 'maintenance'

        LIMIT 1
    ";


    $checkStmt =
        mysqli_prepare(
            $conn,
            $checkSql
        );


    if (!$checkStmt) {

        throw new Exception(
            "Notification check query error: "
            . mysqli_error($conn)
        );
    }


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


    // =====================================================
    // UPDATE EXISTING NOTIFICATION
    // =====================================================

    if ($existing) {

        $notificationId =
            (int)
            $existing[
                "notification_id"
            ];


        $updateSql = "
            UPDATE notifications

            SET
                title = ?,
                message = ?,
                mobile_number = ?,
                notification_status = 'pending'

            WHERE notification_id = ?
        ";


        $updateStmt =
            mysqli_prepare(
                $conn,
                $updateSql
            );


        if (!$updateStmt) {

            throw new Exception(
                "Notification update query error: "
                . mysqli_error($conn)
            );
        }


        $mobileNumber =
            $schedule[
                "mobile_number"
            ]
            ?? null;


        mysqli_stmt_bind_param(
            $updateStmt,
            "sssi",
            $title,
            $message,
            $mobileNumber,
            $notificationId
        );


        mysqli_stmt_execute(
            $updateStmt
        );


        mysqli_stmt_close(
            $updateStmt
        );

    }

    // =====================================================
    // CREATE NEW NOTIFICATION
    // =====================================================

    else {


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


        if (!$insertStmt) {

            throw new Exception(
                "Notification insert query error: "
                . mysqli_error($conn)
            );
        }


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


        if (
            !mysqli_stmt_execute(
                $insertStmt
            )
        ) {

            throw new Exception(
                "Unable to create notification: "
                . mysqli_stmt_error(
                    $insertStmt
                )
            );
        }


        mysqli_stmt_close(
            $insertStmt
        );
    }


    // =====================================================
    // SEND SMS
    //
    // IMPORTANT:
    // This runs whether the notification was newly created
    // OR already existed.
    // =====================================================

    sendMaintenanceSmsIfNeeded(
        $conn,
        $schedule,
        $message
    );
}


// =====================================================
// AUTOMATIC MAINTENANCE SMS
// =====================================================

function sendMaintenanceSmsIfNeeded(
    mysqli $conn,
    array $schedule,
    string $message
): void {


    // Customer chose maintenance SMS at registration/profile
    $customerSmsEnabled =
        (int) (
            $schedule[
                "maintenance_sms"
            ]
            ?? 0
        );


    // This maintenance schedule allows SMS
    $scheduleSmsEnabled =
        (int) (
            $schedule[
                "sms_enabled"
            ]
            ?? 0
        );


    // Prevent duplicate SMS
    $smsAlreadySent =
        (int) (
            $schedule[
                "sms_sent"
            ]
            ?? 0
        );


    $mobileNumber =
        trim(
            $schedule[
                "mobile_number"
            ]
            ?? ""
        );


    // =====================================================
    // DO NOT SEND IF ANY CONDITION FAILS
    // =====================================================

    if (
        $customerSmsEnabled !== 1
        ||
        $scheduleSmsEnabled !== 1
        ||
        $smsAlreadySent === 1
        ||
        $mobileNumber === ""
    ) {

        return;
    }


    // =====================================================
    // SMS MESSAGE
    // =====================================================

    $smsMessage =
        "AutoTrack: "
        .
        $message;


    // =====================================================
    // SEND USING NOTIFY.LK
    // =====================================================

    $smsResult =
        sendSms(
            $mobileNumber,
            $smsMessage
        );


    // =====================================================
    // IF SMS FAILED
    // =====================================================

    if (
        empty(
            $smsResult[
                "success"
            ]
        )
    ) {

        return;
    }


    // =====================================================
    // SMS SUCCESSFUL
    // MARK THIS SCHEDULE AS SENT
    // =====================================================

    $scheduleId =
        (int)
        $schedule[
            "schedule_id"
        ];


    $updateSql = "
        UPDATE maintenance_schedule

        SET
            sms_sent = 1

        WHERE schedule_id = ?
    ";


    $updateStmt =
        mysqli_prepare(
            $conn,
            $updateSql
        );


    if (!$updateStmt) {

        throw new Exception(
            "Unable to update SMS status: "
            . mysqli_error($conn)
        );
    }


    mysqli_stmt_bind_param(
        $updateStmt,
        "i",
        $scheduleId
    );


    if (
        !mysqli_stmt_execute(
            $updateStmt
        )
    ) {

        throw new Exception(
            "Unable to update SMS sent status: "
            . mysqli_stmt_error(
                $updateStmt
            )
        );
    }


    mysqli_stmt_close(
        $updateStmt
    );
}