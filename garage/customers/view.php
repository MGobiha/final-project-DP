<?php

error_reporting(E_ALL);
ini_set("display_errors", 1);

session_start();

require_once "../../config/database.php";


// ==========================================================
// GARAGE LOGIN CHECK
// ==========================================================

require_once "../auth.php";


// ==========================================================
// HELPER
// ==========================================================

function e($value): string
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        "UTF-8"
    );
}


// ==========================================================
// CUSTOMER ID FROM URL
// Example:
// view.php?id=13
// ==========================================================

$customerId =
    isset($_GET["id"])
        ? (int)$_GET["id"]
        : 0;


if ($customerId <= 0) {

    header(
        "Location: index.php"
    );

    exit();
}


// ==========================================================
// LOAD CUSTOMER
// ==========================================================

$customerSql = "
    SELECT
        user_id,
        first_name,
        last_name,
        email,
        mobile_number,
        role,
        maintenance_sms,
        appointment_sms,
        news_sms,
        account_status,
        created_at

    FROM users

    WHERE user_id = ?

    LIMIT 1
";


$customerStmt =
    mysqli_prepare(
        $conn,
        $customerSql
    );


if (!$customerStmt) {

    die(
        "Unable to prepare customer query: "
        . mysqli_error($conn)
    );
}


mysqli_stmt_bind_param(
    $customerStmt,
    "i",
    $customerId
);


mysqli_stmt_execute(
    $customerStmt
);


$customerResult =
    mysqli_stmt_get_result(
        $customerStmt
    );


$customer =
    mysqli_fetch_assoc(
        $customerResult
    );


mysqli_stmt_close(
    $customerStmt
);


if (!$customer) {

    http_response_code(404);

    die(
        "Customer not found."
    );
}


// ==========================================================
// CUSTOMER NAME
// ==========================================================

$customerName =
    trim(
        ($customer["first_name"] ?? "")
        . " "
        . ($customer["last_name"] ?? "")
    );


if ($customerName === "") {

    $customerName =
        "Vehicle Owner";
}


// ==========================================================
// LOAD CUSTOMER VEHICLES
// ==========================================================

$vehicles = [];


$vehicleSql = "
    SELECT

        vehicle_id,

        registration_number,

        make,

        model,

        manufacture_year,

        fuel_type,

        transmission,

        engine_capacity,

        current_mileage,

        average_km_per_month,

        last_service_type,

        last_service_date,

        last_service_mileage,

        color,

        chassis_number,

        purchase_date,

        insurance_expiry,

        revenue_license_expiry,

        emission_test_expiry,

        vehicle_image,

        vehicle_image_1,

        vehicle_image_2,

        vehicle_image_3,

        created_at

    FROM vehicles

    WHERE user_id = ?

    ORDER BY vehicle_id DESC
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
    $customerId
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
// LOAD ACTIVE MAINTENANCE FOR ALL CUSTOMER VEHICLES
// ==========================================================

$maintenanceSchedules = [];


$maintenanceSql = "
    SELECT

        ms.schedule_id,

        ms.vehicle_id,

        ms.maintenance_type,

        ms.description,

        ms.due_date,

        ms.due_mileage,

        ms.schedule_status,

        ms.sms_sent,

        v.make,

        v.model,

        v.registration_number

    FROM maintenance_schedule ms

    INNER JOIN vehicles v
        ON v.vehicle_id = ms.vehicle_id

    WHERE v.user_id = ?

    AND ms.schedule_status
        IN (
            'upcoming',
            'due',
            'overdue'
        )

    ORDER BY

        CASE

            WHEN ms.schedule_status = 'overdue'
                THEN 1

            WHEN ms.schedule_status = 'due'
                THEN 2

            ELSE 3

        END,

        ms.due_date ASC,

        ms.due_mileage ASC
";


$maintenanceStmt =
    mysqli_prepare(
        $conn,
        $maintenanceSql
    );


if ($maintenanceStmt) {

    mysqli_stmt_bind_param(
        $maintenanceStmt,
        "i",
        $customerId
    );


    mysqli_stmt_execute(
        $maintenanceStmt
    );


    $maintenanceResult =
        mysqli_stmt_get_result(
            $maintenanceStmt
        );


    while (
        $row =
        mysqli_fetch_assoc(
            $maintenanceResult
        )
    ) {

        $maintenanceSchedules[] =
            $row;
    }


    mysqli_stmt_close(
        $maintenanceStmt
    );
}


// ==========================================================
// COUNTS
// ==========================================================

$totalVehicles =
    count(
        $vehicles
    );


$totalMaintenance =
    count(
        $maintenanceSchedules
    );

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
        Customer Details - AutoTrack Garage
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
        href="../../css/style.css"
    >

    <link
        rel="stylesheet"
        href="../../css/garage-admin.css"
    >

    <link
        rel="stylesheet"
        href="../../css/responsive.css"
    >


    <style>


    /* =====================================================
       GLOBAL
    ====================================================== */

    * {
        box-sizing: border-box;
    }

    body {
        margin: 0;
        font-family: "Inter", sans-serif;
        background: #f4f7fb;
        color: #101828;
    }


    /* =====================================================
       GARAGE PAGE LAYOUT
    ====================================================== */

    .garage-shell {
        min-height: 100vh;
        width: 100%;
    }


    /*
     * Your garage-sidebar.php is fixed on the left.
     * Therefore the main content must move to the right.
     */

    /* .garage-main {
        margin-left: 280px;
        width: calc(100% - 280px);
        min-height: 100vh;
        padding: 32px;
        background: #f4f7fb;
    } */


    /* =====================================================
       PAGE HEADER
    ====================================================== */

    .customer-page-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 20px;
        margin-bottom: 24px;
    }

    .customer-page-header h1 {
        margin: 0 0 8px;
        font-size: 30px;
        line-height: 1.2;
        color: #101828;
    }

    .customer-page-header p {
        margin: 0;
        color: #667085;
    }

    .page-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        justify-content: flex-end;
    }


    /* =====================================================
       CUSTOMER INFORMATION
    ====================================================== */

    .customer-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 20px;
        margin-bottom: 28px;
    }

    .customer-card {
        background: #ffffff;
        border: 1px solid #e4e7ec;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04);
    }

    .customer-card h2 {
        margin-top: 0;
        margin-bottom: 18px;
        font-size: 22px;
    }


    /* =====================================================
       INFORMATION BOXES
    ====================================================== */

    .info-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
        margin-top: 18px;
    }

    .info-item {
        min-height: 82px;
        padding: 14px 16px;
        border: 1px solid #e4e7ec;
        border-radius: 12px;
        background: #f8fafc;
        overflow-wrap: anywhere;
    }

    .info-label {
        display: block;
        margin-bottom: 7px;
        color: #667085;
        font-size: 12px;
        font-weight: 500;
    }

    .info-value {
        display: block;
        color: #101828;
        font-size: 15px;
        font-weight: 700;
    }


    /* =====================================================
       STATUS BADGES
    ====================================================== */

    .status-badge {
        display: inline-flex;
        align-items: center;
        padding: 6px 10px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
    }

    .status-active {
        background: #ecfdf3;
        color: #067647;
    }

    .status-upcoming {
        background: #eff6ff;
        color: #1d4ed8;
    }

    .status-due {
        background: #fff7ed;
        color: #c2410c;
    }

    .status-overdue {
        background: #fef2f2;
        color: #b42318;
    }


    /* =====================================================
       SECTION HEADERS
    ====================================================== */

    .customer-section-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 15px;
        margin: 30px 0 16px;
    }

    .customer-section-head h2 {
        margin: 0;
        font-size: 22px;
    }


    /* =====================================================
       VEHICLES
    ====================================================== */

    .vehicle-list {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 20px;
    }

    .vehicle-box {
        padding: 20px;
        background: #ffffff;
        border: 1px solid #e4e7ec;
        border-radius: 16px;
        box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04);
    }

    .vehicle-box h3 {
        margin: 0 0 6px;
        font-size: 19px;
    }

    .vehicle-image {
        display: block;
        width: 100%;
        height: 210px;
        margin-bottom: 16px;
        object-fit: cover;
        border-radius: 12px;
        background: #eef2f6;
    }

    .vehicle-placeholder {
        width: 100%;
        height: 210px;
        margin-bottom: 16px;
        border-radius: 12px;
        background: #eef4ff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 54px;
    }


    /* =====================================================
       MAINTENANCE TABLE
    ====================================================== */

    .maintenance-card {
        width: 100%;
        overflow-x: auto;
        background: #ffffff;
        border: 1px solid #e4e7ec;
        border-radius: 16px;
        padding: 8px 18px 18px;
        box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04);
    }

    .maintenance-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 850px;
    }

    .maintenance-table th {
        padding: 15px 12px;
        text-align: left;
        color: #667085;
        font-size: 12px;
        border-bottom: 1px solid #e4e7ec;
    }

    .maintenance-table td {
        padding: 15px 12px;
        border-bottom: 1px solid #e4e7ec;
        vertical-align: middle;
        font-size: 14px;
    }

    .maintenance-table tbody tr:last-child td {
        border-bottom: none;
    }


    /* =====================================================
       EMPTY MESSAGE
    ====================================================== */

    .empty-message {
        padding: 35px;
        text-align: center;
        color: #667085;
    }


    /* =====================================================
       BUTTONS
    ====================================================== */

    .customer-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 11px 16px;
        border: 0;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 700;
        text-decoration: none;
        cursor: pointer;
    }

    .customer-btn-primary {
        color: #ffffff;
        background: #1769ff;
    }

    .customer-btn-primary:hover {
        background: #0d56df;
    }

    .customer-btn-secondary {
        color: #101828;
        background: #eaf0f7;
    }

    .customer-btn-secondary:hover {
        background: #dde6f0;
    }


    /* =====================================================
       MOBILE / TABLET
    ====================================================== */

    @media (max-width: 1100px) {

        .customer-grid {
            grid-template-columns: 1fr;
        }

        .vehicle-list {
            grid-template-columns: 1fr;
        }

    }


    @media (max-width: 800px) {

        .garage-main {
            margin-left: 0;
            width: 100%;
            padding: 20px;
        }

        .customer-page-header {
            flex-direction: column;
        }

        .page-actions {
            justify-content: flex-start;
        }

        .info-grid {
            grid-template-columns: 1fr;
        }

    }


    </style>

</head>


<body>


<div class="garage-shell">


    <?php

    /*
    |--------------------------------------------------------------------------
    | GARAGE SIDEBAR
    |--------------------------------------------------------------------------
    */

    require_once "../../includes/garage-sidebar.php";

    ?>


    <main class="garage-main">


        <!-- =================================================
             PAGE HEADER
        ================================================== -->

        <div
            class="section-head"
            style="
                margin-bottom:20px;
            "
        >


            <div>

                <h1>
                    Customer Details
                </h1>

                <p class="muted">

                    View vehicle owner and maintenance information

                </p>

            </div>


            <div class="page-actions">


                <a
                    href="index.php"
                    class="btn btn-secondary"
                >
                    ← Back to Customers
                </a>


                <a
                    href="../requests/"
                    class="btn btn-primary"
                >
                    Service Requests
                </a>


            </div>


        </div>



        <!-- =================================================
             CUSTOMER INFORMATION
        ================================================== -->

        <section class="customer-grid">


            <div class="card">


                <h2>

                    <?= e(
                        $customerName
                    ) ?>

                </h2>


                <span
                    class="
                        status-badge
                        status-active
                    "
                >

                    <?= e(
                        ucfirst(
                            $customer[
                                "account_status"
                            ]
                        )
                    ) ?>

                </span>



                <div class="info-grid">


                    <div class="info-item">

                        <span class="info-label">
                            Customer ID
                        </span>

                        <span class="info-value">

                            #<?= (int)
                                $customer[
                                    "user_id"
                                ] ?>

                        </span>

                    </div>



                    <div class="info-item">

                        <span class="info-label">
                            Role
                        </span>

                        <span class="info-value">

                            <?= e(
                                str_replace(
                                    "_",
                                    " ",
                                    ucwords(
                                        $customer[
                                            "role"
                                        ]
                                    )
                                )
                            ) ?>

                        </span>

                    </div>



                    <div class="info-item">

                        <span class="info-label">
                            Email
                        </span>

                        <span class="info-value">

                            <?= e(
                                $customer[
                                    "email"
                                ]
                            ) ?>

                        </span>

                    </div>



                    <div class="info-item">

                        <span class="info-label">
                            Mobile Number
                        </span>

                        <span class="info-value">

                            <?= e(
                                $customer[
                                    "mobile_number"
                                ]
                            ) ?>

                        </span>

                    </div>



                    <div class="info-item">

                        <span class="info-label">
                            Registered Vehicles
                        </span>

                        <span class="info-value">

                            <?= number_format(
                                $totalVehicles
                            ) ?>

                        </span>

                    </div>



                    <div class="info-item">

                        <span class="info-label">
                            Active Maintenance
                        </span>

                        <span class="info-value">

                            <?= number_format(
                                $totalMaintenance
                            ) ?>

                        </span>

                    </div>


                </div>


            </div>



            <!-- =============================================
                 NOTIFICATION SETTINGS
            ============================================== -->

            <div class="card">


                <h2>
                    Notification Preferences
                </h2>


                <div class="info-grid">


                    <div class="info-item">

                        <span class="info-label">
                            Maintenance SMS
                        </span>

                        <span class="info-value">

                            <?= (int)
                                $customer[
                                    "maintenance_sms"
                                ]
                                === 1
                                ?
                                "✅ Enabled"
                                :
                                "❌ Disabled" ?>

                        </span>

                    </div>



                    <div class="info-item">

                        <span class="info-label">
                            Appointment SMS
                        </span>

                        <span class="info-value">

                            <?= (int)
                                $customer[
                                    "appointment_sms"
                                ]
                                === 1
                                ?
                                "✅ Enabled"
                                :
                                "❌ Disabled" ?>

                        </span>

                    </div>



                    <div class="info-item">

                        <span class="info-label">
                            News SMS
                        </span>

                        <span class="info-value">

                            <?= (int)
                                $customer[
                                    "news_sms"
                                ]
                                === 1
                                ?
                                "✅ Enabled"
                                :
                                "❌ Disabled" ?>

                        </span>

                    </div>



                    <div class="info-item">

                        <span class="info-label">
                            Member Since
                        </span>

                        <span class="info-value">

                            <?php

                            $created =
                                strtotime(
                                    $customer[
                                        "created_at"
                                    ]
                                );

                            echo $created
                                ?
                                e(
                                    date(
                                        "d M Y",
                                        $created
                                    )
                                )
                                :
                                "-";

                            ?>

                        </span>

                    </div>


                </div>


            </div>


        </section>



        <!-- =================================================
             CUSTOMER VEHICLES
        ================================================== -->

        <div class="section-head">

            <h2>
                Registered Vehicles
            </h2>

        </div>


        <section class="vehicle-list">


        <?php if (
            count(
                $vehicles
            )
            === 0
        ): ?>


            <div class="card empty-message">

                This customer has no registered vehicles.

            </div>


        <?php else: ?>


            <?php foreach (
                $vehicles
                as $vehicle
            ): ?>


                <?php

                $image =
                    $vehicle[
                        "vehicle_image"
                    ]
                    ?:
                    $vehicle[
                        "vehicle_image_1"
                    ]
                    ?:
                    $vehicle[
                        "vehicle_image_2"
                    ]
                    ?:
                    $vehicle[
                        "vehicle_image_3"
                    ]
                    ?:
                    "";

                ?>


                <article class="vehicle-box">


                    <?php if (
                        $image !== ""
                    ): ?>


                        <img

                            src="<?= e(
                                "../../"
                                .
                                ltrim(
                                    $image,
                                    "/"
                                )
                            ) ?>"

                            class="vehicle-image"

                            alt="Vehicle"

                        >


                    <?php else: ?>


                        <div class="vehicle-placeholder">

                            🚗

                        </div>


                    <?php endif; ?>



                    <h3>

                        <?= e(
                            trim(
                                $vehicle[
                                    "make"
                                ]
                                . " "
                                .
                                $vehicle[
                                    "model"
                                ]
                            )
                        ) ?>

                    </h3>



                    <p class="muted">

                        <?= e(
                            $vehicle[
                                "registration_number"
                            ]
                        ) ?>

                    </p>



                    <div class="info-grid">


                        <div class="info-item">

                            <span class="info-label">
                                Year
                            </span>

                            <span class="info-value">

                                <?= e(
                                    $vehicle[
                                        "manufacture_year"
                                    ]
                                    ??
                                    "-"
                                ) ?>

                            </span>

                        </div>



                        <div class="info-item">

                            <span class="info-label">
                                Current Mileage
                            </span>

                            <span class="info-value">

                                <?= number_format(
                                    (int)
                                    $vehicle[
                                        "current_mileage"
                                    ]
                                ) ?>

                                km

                            </span>

                        </div>



                        <div class="info-item">

                            <span class="info-label">
                                Fuel Type
                            </span>

                            <span class="info-value">

                                <?= e(
                                    $vehicle[
                                        "fuel_type"
                                    ]
                                ) ?>

                            </span>

                        </div>



                        <div class="info-item">

                            <span class="info-label">
                                Transmission
                            </span>

                            <span class="info-value">

                                <?= e(
                                    $vehicle[
                                        "transmission"
                                    ]
                                    ??
                                    "-"
                                ) ?>

                            </span>

                        </div>



                        <div class="info-item">

                            <span class="info-label">
                                Average KM / Month
                            </span>

                            <span class="info-value">

                                <?php if (
                                    $vehicle[
                                        "average_km_per_month"
                                    ]
                                    !== null
                                ): ?>

                                    <?= number_format(
                                        (int)
                                        $vehicle[
                                            "average_km_per_month"
                                        ]
                                    ) ?>

                                    km

                                <?php else: ?>

                                    -

                                <?php endif; ?>

                            </span>

                        </div>



                        <div class="info-item">

                            <span class="info-label">
                                Last Service
                            </span>

                            <span class="info-value">

                                <?= e(
                                    $vehicle[
                                        "last_service_type"
                                    ]
                                    ??
                                    "-"
                                ) ?>

                            </span>

                        </div>


                    </div>


                </article>


            <?php endforeach; ?>


        <?php endif; ?>


        </section>



        <!-- =================================================
             ACTIVE MAINTENANCE
        ================================================== -->

        <div
            class="section-head"
            style="
                margin-top:28px;
            "
        >

            <h2>
                Maintenance Status
            </h2>

        </div>



        <div class="card table-wrap">


            <table class="maintenance-table">


                <thead>

                    <tr>

                        <th>
                            Vehicle
                        </th>

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
                        $maintenanceSchedules
                    )
                    === 0
                ): ?>


                    <tr>

                        <td
                            colspan="6"
                            class="empty-message"
                        >

                            No active maintenance schedules.

                        </td>

                    </tr>


                <?php else: ?>


                    <?php foreach (
                        $maintenanceSchedules
                        as $schedule
                    ): ?>


                        <tr>


                            <td>

                                <?= e(
                                    trim(
                                        $schedule[
                                            "make"
                                        ]
                                        . " "
                                        .
                                        $schedule[
                                            "model"
                                        ]
                                    )
                                ) ?>

                                <br>

                                <small class="muted">

                                    <?= e(
                                        $schedule[
                                            "registration_number"
                                        ]
                                    ) ?>

                                </small>

                            </td>



                            <td>

                                <?= e(
                                    $schedule[
                                        "maintenance_type"
                                    ]
                                ) ?>

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

                                    $time =
                                        strtotime(
                                            $schedule[
                                                "due_date"
                                            ]
                                        );

                                    echo $time
                                        ?
                                        e(
                                            date(
                                                "d M Y",
                                                $time
                                            )
                                        )
                                        :
                                        "-";

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
                                        status-badge
                                        status-<?= e(
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

                                <?= (int)
                                    $schedule[
                                        "sms_sent"
                                    ]
                                    === 1
                                    ?
                                    "✅ Sent"
                                    :
                                    "⏳ Pending" ?>

                            </td>


                        </tr>


                    <?php endforeach; ?>


                <?php endif; ?>


                </tbody>


            </table>


        </div>


    </main>


</div>


</body>

</html>