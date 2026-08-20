<?php

/**
 * ==========================================================
 * AutoTrack - Maintenance Functions
 * ==========================================================
 *
 * Purpose:
 * 1. Generate automatic maintenance schedules
 * 2. Prevent duplicate schedules
 * 3. Create mileage-based and date-based maintenance items
 *
 * IMPORTANT:
 * Notifications are NOT created here.
 * Notifications should be created later by reminder-checker.php
 * only when a schedule becomes due or overdue.
 */


/**
 * ==========================================================
 * GENERATE VEHICLE REMINDERS
 * ==========================================================
 *
 * This function creates the initial automatic maintenance
 * schedule for one vehicle.
 */
function generateVehicleReminders(
    mysqli $conn,
    int $vehicleId,
    int $userId
): void {

    // ======================================================
    // LOAD VEHICLE INFORMATION
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
            insurance_expiry,
            revenue_license_expiry,
            emission_test_expiry

        FROM vehicles

        WHERE vehicle_id = ?
        AND user_id = ?

        LIMIT 1
    ";


    $stmt = mysqli_prepare(
        $conn,
        $sql
    );


    if (!$stmt) {

        throw new Exception(
            "Vehicle reminder query error: "
            . mysqli_error($conn)
        );
    }


    mysqli_stmt_bind_param(
        $stmt,
        "ii",
        $vehicleId,
        $userId
    );


    if (!mysqli_stmt_execute($stmt)) {

        throw new Exception(
            "Unable to load vehicle information: "
            . mysqli_stmt_error($stmt)
        );
    }


    $result =
        mysqli_stmt_get_result($stmt);


    $vehicle =
        mysqli_fetch_assoc($result);


    mysqli_stmt_close($stmt);


    if (!$vehicle) {

        return;
    }


    // ======================================================
    // CURRENT MILEAGE
    // ======================================================

    $currentMileage =
        (int) (
            $vehicle["current_mileage"]
            ?? 0
        );


    // ======================================================
    // 1. ENGINE OIL CHANGE
    // Every 5,000 km
    // ======================================================

    $nextOilMileage =
        (
            (int) floor(
                $currentMileage / 5000
            )
            + 1
        )
        * 5000;


    createMaintenanceReminder(
        $conn,
        $vehicleId,
        "Engine Oil Change",
        "Engine oil change is recommended based on vehicle mileage.",
        null,
        $nextOilMileage
    );


    // ======================================================
    // 2. GENERAL SERVICE
    // Every 10,000 km
    // ======================================================

    $nextGeneralServiceMileage =
        (
            (int) floor(
                $currentMileage / 10000
            )
            + 1
        )
        * 10000;


    createMaintenanceReminder(
        $conn,
        $vehicleId,
        "General Service",
        "General vehicle service is recommended based on vehicle mileage.",
        null,
        $nextGeneralServiceMileage
    );


    // ======================================================
    // 3. INSURANCE RENEWAL
    // Date-based reminder
    // ======================================================

    if (
        !empty(
            $vehicle[
                "insurance_expiry"
            ]
        )
    ) {

        createMaintenanceReminder(
            $conn,
            $vehicleId,
            "Insurance Renewal",
            "Your vehicle insurance is approaching its expiry date.",
            $vehicle[
                "insurance_expiry"
            ],
            null
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

        createMaintenanceReminder(
            $conn,
            $vehicleId,
            "Revenue Licence Renewal",
            "Your vehicle revenue licence is approaching its expiry date.",
            $vehicle[
                "revenue_license_expiry"
            ],
            null
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

        createMaintenanceReminder(
            $conn,
            $vehicleId,
            "Emission Test",
            "Your vehicle emission test is approaching its expiry date.",
            $vehicle[
                "emission_test_expiry"
            ],
            null
        );
    }
}


/**
 * ==========================================================
 * CREATE MAINTENANCE REMINDER
 * ==========================================================
 *
 * Creates one row in maintenance_schedule.
 *
 * This function also checks duplicates first.
 */
function createMaintenanceReminder(
    mysqli $conn,
    int $vehicleId,
    string $maintenanceType,
    string $description,
    ?string $dueDate,
    ?int $dueMileage
): void {

    // ======================================================
    // CHECK DUPLICATE
    // ======================================================

    $checkSql = "
        SELECT
            schedule_id

        FROM maintenance_schedule

        WHERE vehicle_id = ?
        AND maintenance_type = ?

        AND (
            (
                due_date IS NULL
                AND ? IS NULL
            )
            OR due_date = ?
        )

        AND (
            (
                due_mileage IS NULL
                AND ? IS NULL
            )
            OR due_mileage = ?
        )

        LIMIT 1
    ";


    $checkStmt =
        mysqli_prepare(
            $conn,
            $checkSql
        );


    if (!$checkStmt) {

        throw new Exception(
            "Reminder duplicate check error: "
            . mysqli_error($conn)
        );
    }


    mysqli_stmt_bind_param(
        $checkStmt,
        "isssii",
        $vehicleId,
        $maintenanceType,
        $dueDate,
        $dueDate,
        $dueMileage,
        $dueMileage
    );


    if (
        !mysqli_stmt_execute(
            $checkStmt
        )
    ) {

        throw new Exception(
            "Unable to check maintenance reminder: "
            . mysqli_stmt_error(
                $checkStmt
            )
        );
    }


    $checkResult =
        mysqli_stmt_get_result(
            $checkStmt
        );


    if (
        mysqli_num_rows(
            $checkResult
        ) > 0
    ) {

        mysqli_stmt_close(
            $checkStmt
        );

        return;
    }


    mysqli_stmt_close(
        $checkStmt
    );


    // ======================================================
    // DEFAULT VALUES
    // ======================================================

    $scheduleStatus =
        "upcoming";


    /*
     * Database defaults already handle:
     *
     * reminder_days_before = 7
     * reminder_km_before   = 500
     * sms_enabled          = 1
     * sms_sent             = 0
     */


    // ======================================================
    // INSERT MAINTENANCE SCHEDULE
    // ======================================================

    $scheduleSql = "
        INSERT INTO maintenance_schedule
        (
            vehicle_id,
            maintenance_type,
            description,
            due_date,
            due_mileage,
            schedule_status
        )

        VALUES
        (
            ?, ?, ?, ?, ?, ?
        )
    ";


    $scheduleStmt =
        mysqli_prepare(
            $conn,
            $scheduleSql
        );


    if (!$scheduleStmt) {

        throw new Exception(
            "Unable to prepare maintenance schedule: "
            . mysqli_error($conn)
        );
    }


    mysqli_stmt_bind_param(
        $scheduleStmt,
        "isssis",
        $vehicleId,
        $maintenanceType,
        $description,
        $dueDate,
        $dueMileage,
        $scheduleStatus
    );


    if (
        !mysqli_stmt_execute(
            $scheduleStmt
        )
    ) {

        throw new Exception(
            "Unable to create maintenance schedule: "
            . mysqli_stmt_error(
                $scheduleStmt
            )
        );
    }


    mysqli_stmt_close(
        $scheduleStmt
    );
}


/**
 * ==========================================================
 * REGENERATE REMINDERS AFTER VEHICLE UPDATE
 * ==========================================================
 *
 * You can call this later when:
 * - mileage changes
 * - vehicle details change
 *
 * It calls the same generator and duplicate checks protect
 * existing schedules.
 */
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