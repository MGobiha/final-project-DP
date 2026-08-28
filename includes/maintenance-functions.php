<?php

/**
 * ==========================================================
 * AutoTrack - Maintenance Functions
 * ==========================================================
 *
 * Automatic maintenance schedule generation.
 *
 * Uses:
 * - Current mileage
 * - Average KM per month
 * - Last service type
 * - Last service date
 * - Last service mileage
 *
 * IMPORTANT:
 * This file creates/updates maintenance schedules only.
 *
 * SMS / notifications are handled by:
 * includes/reminder-checker.php
 */


// ==========================================================
// CALCULATE ESTIMATED SERVICE DATE
// ==========================================================

function calculateEstimatedServiceDate(
    int $currentMileage,
    int $dueMileage,
    int $averageKmPerMonth
): ?string {

    // Cannot estimate a date without average monthly mileage.
    if ($averageKmPerMonth <= 0) {
        return null;
    }


    $remainingKm =
        $dueMileage
        -
        $currentMileage;


    // Already due / overdue.
    if ($remainingKm <= 0) {

        return date(
            "Y-m-d"
        );
    }


    /*
     * Example:
     *
     * Remaining = 5,000 km
     * Average    = 2,500 km/month
     *
     * 5000 / 2500 = 2 months
     *
     * Approximately:
     * 2 * 30 = 60 days
     */

    $estimatedMonths =
        $remainingKm
        /
        $averageKmPerMonth;


    $estimatedDays =
        (int) ceil(
            $estimatedMonths
            *
            30
        );


    $date =
        new DateTime();


    $date->modify(
        "+"
        .
        $estimatedDays
        .
        " days"
    );


    return $date->format(
        "Y-m-d"
    );
}


// ==========================================================
// GENERATE VEHICLE REMINDERS
// ==========================================================

function generateVehicleReminders(
    mysqli $conn,
    int $vehicleId,
    int $userId
): void {


    // ======================================================
    // LOAD VEHICLE
    // ======================================================

    $sql = "
        SELECT

            vehicle_id,
            user_id,

            registration_number,

            make,
            model,
            manufacture_year,

            fuel_type,

            current_mileage,
            average_km_per_month,

            last_service_type,
            last_service_date,
            last_service_mileage,

            insurance_expiry,
            revenue_license_expiry,
            emission_test_expiry

        FROM vehicles

        WHERE vehicle_id = ?
        AND user_id = ?

        LIMIT 1
    ";


    $stmt =
        mysqli_prepare(
            $conn,
            $sql
        );


    if (!$stmt) {

        throw new Exception(
            "Vehicle reminder query error: "
            .
            mysqli_error(
                $conn
            )
        );
    }


    mysqli_stmt_bind_param(
        $stmt,
        "ii",
        $vehicleId,
        $userId
    );


    if (
        !mysqli_stmt_execute(
            $stmt
        )
    ) {

        throw new Exception(
            "Unable to load vehicle information: "
            .
            mysqli_stmt_error(
                $stmt
            )
        );
    }


    $result =
        mysqli_stmt_get_result(
            $stmt
        );


    $vehicle =
        mysqli_fetch_assoc(
            $result
        );


    mysqli_stmt_close(
        $stmt
    );


    if (!$vehicle) {
        return;
    }


    // ======================================================
    // VEHICLE VALUES
    // ======================================================

    $currentMileage =
        (int) (
            $vehicle[
                "current_mileage"
            ]
            ??
            0
        );


    $averageKmPerMonth =
        (int) (
            $vehicle[
                "average_km_per_month"
            ]
            ??
            0
        );


    $lastServiceType =
        trim(
            $vehicle[
                "last_service_type"
            ]
            ??
            ""
        );


    $lastServiceDate =
        !empty(
            $vehicle[
                "last_service_date"
            ]
        )
        ?
        $vehicle[
            "last_service_date"
        ]
        :
        null;


    $lastServiceMileage =
        $vehicle[
            "last_service_mileage"
        ]
        !== null

        ?

        (int)
        $vehicle[
            "last_service_mileage"
        ]

        :

        null;


    // ======================================================
    // 1. ENGINE OIL CHANGE
    // Every 5,000 KM
    // ======================================================

    /*
     * Use last service mileage when the last service could
     * reasonably include an oil change.
     */

    $oilServiceTypes = [

        "Engine Oil Change",

        "General Service",

        "Full Service"
    ];


    if (
        $lastServiceMileage !== null
        &&
        in_array(
            $lastServiceType,
            $oilServiceTypes,
            true
        )
    ) {

        $nextOilMileage =
            $lastServiceMileage
            +
            5000;

    } else {

        /*
         * No reliable previous oil service mileage.
         *
         * Fall back to next 5,000 KM point.
         */

        $nextOilMileage =
            (
                (
                    (int) floor(
                        $currentMileage
                        /
                        5000
                    )
                )
                +
                1
            )
            *
            5000;
    }


    $estimatedOilDate =
        calculateEstimatedServiceDate(
            $currentMileage,
            $nextOilMileage,
            $averageKmPerMonth
        );


    createOrUpdateMaintenanceReminder(

        $conn,

        $vehicleId,

        "Engine Oil Change",

        "Engine oil change is recommended every 5,000 km.",

        $lastServiceDate,

        $lastServiceMileage,

        $estimatedOilDate,

        $nextOilMileage,

        30,

        500
    );


    // ======================================================
    // 2. GENERAL SERVICE
    // Every 10,000 KM
    // ======================================================

    /*
     * Only use last service mileage directly for General
     * Service if the previous service was a General/Full
     * Service.
     */

    $generalServiceTypes = [

        "General Service",

        "Full Service"
    ];


    if (
        $lastServiceMileage !== null
        &&
        in_array(
            $lastServiceType,
            $generalServiceTypes,
            true
        )
    ) {

        $nextGeneralMileage =
            $lastServiceMileage
            +
            10000;

    } else {

        /*
         * Example:
         *
         * Current = 95,000
         *
         * Next general service
         * = 100,000
         */

        $nextGeneralMileage =
            (
                (
                    (int) floor(
                        $currentMileage
                        /
                        10000
                    )
                )
                +
                1
            )
            *
            10000;
    }


    $estimatedGeneralDate =
        calculateEstimatedServiceDate(
            $currentMileage,
            $nextGeneralMileage,
            $averageKmPerMonth
        );


    createOrUpdateMaintenanceReminder(

        $conn,

        $vehicleId,

        "General Service",

        "General vehicle service is recommended every 10,000 km.",

        (
            in_array(
                $lastServiceType,
                $generalServiceTypes,
                true
            )
            ?
            $lastServiceDate
            :
            null
        ),

        (
            in_array(
                $lastServiceType,
                $generalServiceTypes,
                true
            )
            ?
            $lastServiceMileage
            :
            null
        ),

        $estimatedGeneralDate,

        $nextGeneralMileage,

        30,

        500
    );


    // ======================================================
    // 3. INSURANCE RENEWAL
    // ======================================================

    if (
        !empty(
            $vehicle[
                "insurance_expiry"
            ]
        )
    ) {

        createOrUpdateMaintenanceReminder(

            $conn,

            $vehicleId,

            "Insurance Renewal",

            "Your vehicle insurance is approaching its expiry date.",

            null,

            null,

            $vehicle[
                "insurance_expiry"
            ],

            null,

            30,

            0
        );
    }


    // ======================================================
    // 4. REVENUE LICENCE RENEWAL
    // ======================================================

    if (
        !empty(
            $vehicle[
                "revenue_license_expiry"
            ]
        )
    ) {

        createOrUpdateMaintenanceReminder(

            $conn,

            $vehicleId,

            "Revenue Licence Renewal",

            "Your vehicle revenue licence is approaching its expiry date.",

            null,

            null,

            $vehicle[
                "revenue_license_expiry"
            ],

            null,

            30,

            0
        );
    }


    // ======================================================
    // 5. EMISSION TEST
    // ======================================================

    if (
        !empty(
            $vehicle[
                "emission_test_expiry"
            ]
        )
    ) {

        createOrUpdateMaintenanceReminder(

            $conn,

            $vehicleId,

            "Emission Test",

            "Your vehicle emission test is approaching its expiry date.",

            null,

            null,

            $vehicle[
                "emission_test_expiry"
            ],

            null,

            30,

            0
        );
    }
}


// ==========================================================
// CREATE OR UPDATE MAINTENANCE SCHEDULE
// ==========================================================

function createOrUpdateMaintenanceReminder(

    mysqli $conn,

    int $vehicleId,

    string $maintenanceType,

    string $description,

    ?string $lastServiceDate,

    ?int $lastServiceMileage,

    ?string $dueDate,

    ?int $dueMileage,

    int $reminderDaysBefore = 30,

    int $reminderKmBefore = 500

): void {


    // ======================================================
    // LOOK FOR ACTIVE EXISTING SCHEDULE
    // ======================================================

    $checkSql = "
        SELECT

            schedule_id,
            due_date,
            due_mileage,
            sms_sent

        FROM maintenance_schedule

        WHERE vehicle_id = ?

        AND maintenance_type = ?

        AND schedule_status != 'completed'

        ORDER BY schedule_id DESC

        LIMIT 1
    ";


    $checkStmt =
        mysqli_prepare(
            $conn,
            $checkSql
        );


    if (!$checkStmt) {

        throw new Exception(
            "Maintenance schedule check error: "
            .
            mysqli_error(
                $conn
            )
        );
    }


    mysqli_stmt_bind_param(
        $checkStmt,
        "is",
        $vehicleId,
        $maintenanceType
    );


    if (
        !mysqli_stmt_execute(
            $checkStmt
        )
    ) {

        throw new Exception(
            "Unable to check maintenance schedule: "
            .
            mysqli_stmt_error(
                $checkStmt
            )
        );
    }


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


    // ======================================================
    // EXISTING ACTIVE SCHEDULE
    // UPDATE INSTEAD OF INSERTING DUPLICATES
    // ======================================================

    if ($existing) {


        $scheduleId =
            (int)
            $existing[
                "schedule_id"
            ];


        /*
         * Determine whether service target changed.
         *
         * If the target changes, SMS can be sent again for
         * the new maintenance cycle.
         */

        $oldDueDate =
            $existing[
                "due_date"
            ];


        $oldDueMileage =
            $existing[
                "due_mileage"
            ]
            !== null

            ?

            (int)
            $existing[
                "due_mileage"
            ]

            :

            null;


        $targetChanged =
            (
                $oldDueDate
                !==
                $dueDate
            )
            ||
            (
                $oldDueMileage
                !==
                $dueMileage
            );


        $smsSent =
            $targetChanged
            ?
            0
            :
            (int)
            $existing[
                "sms_sent"
            ];


        $updateSql = "
            UPDATE maintenance_schedule

            SET

                description = ?,

                last_service_date = ?,

                last_service_mileage = ?,

                due_date = ?,

                due_mileage = ?,

                reminder_days_before = ?,

                reminder_km_before = ?,

                sms_enabled = 1,

                sms_sent = ?

            WHERE schedule_id = ?
        ";


        $updateStmt =
            mysqli_prepare(
                $conn,
                $updateSql
            );


        if (!$updateStmt) {

            throw new Exception(
                "Unable to prepare maintenance schedule update: "
                .
                mysqli_error(
                    $conn
                )
            );
        }


        mysqli_stmt_bind_param(

            $updateStmt,

            "ssisiiiii",

            $description,

            $lastServiceDate,

            $lastServiceMileage,

            $dueDate,

            $dueMileage,

            $reminderDaysBefore,

            $reminderKmBefore,

            $smsSent,

            $scheduleId
        );


        if (
            !mysqli_stmt_execute(
                $updateStmt
            )
        ) {

            throw new Exception(
                "Unable to update maintenance schedule: "
                .
                mysqli_stmt_error(
                    $updateStmt
                )
            );
        }


        mysqli_stmt_close(
            $updateStmt
        );


        return;
    }


    // ======================================================
    // CREATE NEW SCHEDULE
    // ======================================================

    $scheduleStatus =
        "upcoming";


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

            last_service_date,

            last_service_mileage,

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
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
        )
    ";


    $insertStmt =
        mysqli_prepare(
            $conn,
            $insertSql
        );


    if (!$insertStmt) {

        throw new Exception(
            "Unable to prepare maintenance schedule: "
            .
            mysqli_error(
                $conn
            )
        );
    }


    mysqli_stmt_bind_param(

        $insertStmt,

        "isssisiiisii",

        $vehicleId,

        $maintenanceType,

        $description,

        $lastServiceDate,

        $lastServiceMileage,

        $dueDate,

        $dueMileage,

        $reminderDaysBefore,

        $reminderKmBefore,

        $scheduleStatus,

        $smsEnabled,

        $smsSent
    );


    if (
        !mysqli_stmt_execute(
            $insertStmt
        )
    ) {

        throw new Exception(
            "Unable to create maintenance schedule: "
            .
            mysqli_stmt_error(
                $insertStmt
            )
        );
    }


    mysqli_stmt_close(
        $insertStmt
    );
}


// ==========================================================
// REFRESH VEHICLE MAINTENANCE
// ==========================================================

function refreshVehicleMaintenanceSchedules(

    mysqli $conn,

    int $vehicleId,

    int $userId

): void {


    generateVehicleReminders(

        $conn,

        $vehicleId,

        $userId
    );
}