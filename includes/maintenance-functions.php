<?php

function generateVehicleReminders(
    mysqli $conn,
    int $vehicleId,
    int $userId
): void {

    // =====================================================
    // LOAD VEHICLE
    // =====================================================

    $sql = "
        SELECT
            vehicle_id,
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

    mysqli_stmt_execute($stmt);

    $result =
        mysqli_stmt_get_result($stmt);

    $vehicle =
        mysqli_fetch_assoc($result);

    if (!$vehicle) {
        return;
    }


    // =====================================================
    // 1. ENGINE OIL REMINDER
    // every 5,000 km
    // =====================================================

    $currentMileage =
        (int) (
            $vehicle[
                "current_mileage"
            ] ?? 0
        );

    $nextOilMileage =
        (
            floor(
                $currentMileage / 5000
            ) + 1
        ) * 5000;


    createMaintenanceReminder(
        $conn,
        $vehicleId,
        $userId,
        "Engine Oil Change",
        null,
        $nextOilMileage
    );


    // =====================================================
    // 2. GENERAL SERVICE
    // every 10,000 km
    // =====================================================

    $nextGeneralService =
        (
            floor(
                $currentMileage / 10000
            ) + 1
        ) * 10000;


    createMaintenanceReminder(
        $conn,
        $vehicleId,
        $userId,
        "General Service",
        null,
        $nextGeneralService
    );


    // =====================================================
    // 3. INSURANCE EXPIRY
    // =====================================================

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
            $userId,
            "Insurance Renewal",
            $vehicle[
                "insurance_expiry"
            ],
            null
        );
    }


    // =====================================================
    // 4. REVENUE LICENCE EXPIRY
    // =====================================================

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
            $userId,
            "Revenue Licence Renewal",
            $vehicle[
                "revenue_license_expiry"
            ],
            null
        );
    }


    // =====================================================
    // 5. EMISSION TEST EXPIRY
    // =====================================================

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
            $userId,
            "Emission Test",
            $vehicle[
                "emission_test_expiry"
            ],
            null
        );
    }
}


// =====================================================
// CREATE MAINTENANCE REMINDER
// =====================================================

function createMaintenanceReminder(
    mysqli $conn,
    int $vehicleId,
    int $userId,
    string $maintenanceType,
    ?string $dueDate,
    ?int $dueMileage
): void {

    // -----------------------------------------------------
    // prevent duplicates
    // -----------------------------------------------------

    $checkSql = "
        SELECT schedule_id

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

    mysqli_stmt_execute(
        $checkStmt
    );

    $checkResult =
        mysqli_stmt_get_result(
            $checkStmt
        );


    if (
        mysqli_num_rows(
            $checkResult
        ) > 0
    ) {
        return;
    }


    // -----------------------------------------------------
    // create maintenance_schedule row
    // -----------------------------------------------------

    $status = "upcoming";


    $sql = "
        INSERT INTO maintenance_schedule
        (
            vehicle_id,
            maintenance_type,
            due_date,
            due_mileage,
            schedule_status
        )
        VALUES
        (
            ?, ?, ?, ?, ?
        )
    ";

    $stmt =
        mysqli_prepare(
            $conn,
            $sql
        );

    if (!$stmt) {
        throw new Exception(
            "Unable to create maintenance schedule: "
            . mysqli_error($conn)
        );
    }


    mysqli_stmt_bind_param(
        $stmt,
        "issis",
        $vehicleId,
        $maintenanceType,
        $dueDate,
        $dueMileage,
        $status
    );

    mysqli_stmt_execute(
        $stmt
    );


    // -----------------------------------------------------
    // create notification
    // -----------------------------------------------------

    $message =
        $maintenanceType
        . " reminder has been created.";

    $notificationType =
        "maintenance";

    $notificationStatus =
        "pending";


    $notificationSql = "
        INSERT INTO notifications
        (
            user_id,
            notification_type,
            message,
            notification_status
        )
        VALUES
        (
            ?, ?, ?, ?
        )
    ";


    $notificationStmt =
        mysqli_prepare(
            $conn,
            $notificationSql
        );

    if (!$notificationStmt) {
        throw new Exception(
            "Unable to create notification: "
            . mysqli_error($conn)
        );
    }


    mysqli_stmt_bind_param(
        $notificationStmt,
        "isss",
        $userId,
        $notificationType,
        $message,
        $notificationStatus
    );

    mysqli_stmt_execute(
        $notificationStmt
    );
}