<?php

require_once 'auth.php';

$activePage = "dashboard";


// =====================================================
// DEFAULT VALUES
// =====================================================

$totalStaff = 0;
$totalParts = 0;
$lowStock = 0;


// =====================================================
// TOTAL STAFF
// =====================================================

$sql = "
    SELECT COUNT(*) AS total
    FROM garage_staff
    WHERE garage_id = ?
    AND employment_status = 'active'
";

$stmt = mysqli_prepare(
    $conn,
    $sql
);

if (!$stmt) {

    die(
        "Staff count query error: "
        . mysqli_error($conn)
    );
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $garageId
);

mysqli_stmt_execute($stmt);

$result =
    mysqli_stmt_get_result($stmt);

$row =
    mysqli_fetch_assoc($result);

$totalStaff =
    (int) ($row["total"] ?? 0);


// =====================================================
// TOTAL PARTS
// =====================================================

$sql = "
    SELECT COUNT(*) AS total
    FROM parts
    WHERE garage_id = ?
";

$stmt = mysqli_prepare(
    $conn,
    $sql
);

if (!$stmt) {

    die(
        "Parts query error: "
        . mysqli_error($conn)
    );
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $garageId
);

mysqli_stmt_execute($stmt);

$result =
    mysqli_stmt_get_result($stmt);

$row =
    mysqli_fetch_assoc($result);

$totalParts =
    (int) ($row["total"] ?? 0);


// =====================================================
// LOW STOCK
// =====================================================

$sql = "
    SELECT COUNT(*) AS total
    FROM parts
    WHERE garage_id = ?
    AND quantity <= minimum_stock
";

$stmt = mysqli_prepare(
    $conn,
    $sql
);

if (!$stmt) {

    die(
        "Low stock query error: "
        . mysqli_error($conn)
    );
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $garageId
);

mysqli_stmt_execute($stmt);

$result =
    mysqli_stmt_get_result($stmt);

$row =
    mysqli_fetch_assoc($result);

$lowStock =
    (int) ($row["total"] ?? 0);

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
        Garage Dashboard - AutoTrack
    </title>


    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >


    <link
        rel="stylesheet"
        href="../css/style.css"
    >


    <link
        rel="stylesheet"
        href="../css/garage-admin.css"
    >


    <link
        rel="stylesheet"
        href="../css/dashboard-layout.css"
    >


    <style>

        .garage-dashboard-header {
            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 20px;

            margin-bottom: 28px;
        }

        .garage-dashboard-header h1 {
            margin: 0 0 6px;
        }

        .garage-dashboard-header p {
            margin: 0;
        }

        .dashboard-stats {
            margin-bottom: 30px;
        }

        .dashboard-stats .stat {
            min-height: 145px;
        }

        .dashboard-section {
            margin-top: 30px;
        }

        .dashboard-section h2 {
            margin-bottom: 16px;
        }

        .quick-links {
            display: grid;

            grid-template-columns:
                repeat(
                    3,
                    minmax(0, 1fr)
                );

            gap: 18px;
        }

        .quick-link-card {
            display: block;

            padding: 22px;

            border: 1px solid #e4e7ec;
            border-radius: 16px;

            background: #ffffff;

            color: inherit;

            text-decoration: none;

            transition:
                transform .2s ease,
                box-shadow .2s ease,
                border-color .2s ease;
        }

        .quick-link-card:hover {
            transform: translateY(-3px);

            border-color:
                rgba(15, 98, 254, .35);

            box-shadow:
                0 12px 28px
                rgba(15, 35, 65, .08);
        }

        .quick-link-card .quick-icon {
            font-size: 28px;

            margin-bottom: 12px;
        }

        .quick-link-card h3 {
            margin: 0 0 7px;
        }

        .quick-link-card p {
            margin: 0;

            color: #667085;

            line-height: 1.6;
        }

        @media (max-width: 900px) {

            .quick-links {
                grid-template-columns:
                    repeat(
                        2,
                        minmax(0, 1fr)
                    );
            }

        }

        @media (max-width: 600px) {

            .garage-dashboard-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .quick-links {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>


<body>

<div class="app-shell">


    <?php
    require_once '../includes/garage-sidebar.php';
    ?>


    <main class="main">


        <!-- =====================================================
             HEADER
             ===================================================== -->

        <div class="garage-dashboard-header">

            <div>

                <h1>

                    <?php
                    echo htmlspecialchars(
                        $garage["garage_name"]
                    );
                    ?>

                </h1>

                <p class="muted">
                    Garage Administration Dashboard
                </p>

            </div>

        </div>


        <!-- =====================================================
             STATISTICS
             ===================================================== -->

        <section
            class="
                grid
                grid-3
                dashboard-stats
            "
        >


            <div class="card stat">

                <div>

                    <span class="muted">
                        Total Staff
                    </span>

                    <strong>

                        <?php
                        echo $totalStaff;
                        ?>

                    </strong>

                </div>

                <div class="icon">
                    👥
                </div>

            </div>


            <div class="card stat">

                <div>

                    <span class="muted">
                        Parts
                    </span>

                    <strong>

                        <?php
                        echo $totalParts;
                        ?>

                    </strong>

                </div>

                <div class="icon">
                    📦
                </div>

            </div>


            <div class="card stat">

                <div>

                    <span class="muted">
                        Low Stock
                    </span>

                    <strong>

                        <?php
                        echo $lowStock;
                        ?>

                    </strong>

                </div>

                <div class="icon">
                    ⚠️
                </div>

            </div>


        </section>


        <!-- =====================================================
             QUICK ACCESS
             ===================================================== -->

        <section class="dashboard-section">

            <h2>
                Quick Access
            </h2>


            <div class="quick-links">


                <a
                    href="staff/index.php"
                    class="quick-link-card"
                >

                    <div class="quick-icon">
                        👥
                    </div>

                    <h3>
                        Staff Management
                    </h3>

                    <p>
                        Add, edit and manage
                        garage staff members.
                    </p>

                </a>


                <a
                    href="requests/index.php"
                    class="quick-link-card"
                >

                    <div class="quick-icon">
                        📥
                    </div>

                    <h3>
                        Customer Requests
                    </h3>

                    <p>
                        Review vehicle-owner
                        connection requests.
                    </p>

                </a>


                <a
                    href="customers/index.php"
                    class="quick-link-card"
                >

                    <div class="quick-icon">
                        🚗
                    </div>

                    <h3>
                        Customers
                    </h3>

                    <p>
                        View customers connected
                        to this garage.
                    </p>

                </a>


                <a
                    href="appointments/index.php"
                    class="quick-link-card"
                >

                    <div class="quick-icon">
                        📅
                    </div>

                    <h3>
                        Appointments
                    </h3>

                    <p>
                        Manage upcoming customer
                        service bookings.
                    </p>

                </a>


                <a
                    href="services/index.php"
                    class="quick-link-card"
                >

                    <div class="quick-icon">
                        🔧
                    </div>

                    <h3>
                        Garage Services
                    </h3>

                    <p>
                        Maintain services,
                        pricing and duration.
                    </p>

                </a>


                <a
                    href="parts/index.php"
                    class="quick-link-card"
                >

                    <div class="quick-icon">
                        📦
                    </div>

                    <h3>
                        Parts Inventory
                    </h3>

                    <p>
                        View stock quantities
                        and low-stock items.
                    </p>

                </a>


            </div>

        </section>


    </main>

</div>


</body>

</html>