<?php

require_once 'auth.php';
require_once '../config/database.php';

/*
|--------------------------------------------------------------------------
| Active sidebar page
|--------------------------------------------------------------------------
*/
$currentPage = "dashboard";


/*
|--------------------------------------------------------------------------
| Default dashboard values
|--------------------------------------------------------------------------
*/
$totalStaff = 0;
$totalParts = 0;
$lowStock = 0;


/*
|--------------------------------------------------------------------------
| Total Active Staff
|--------------------------------------------------------------------------
*/
$sql = "
    SELECT COUNT(*) AS total
    FROM garage_staff
    WHERE garage_id = ?
    AND employment_status = 'active'
";

$stmt = mysqli_prepare($conn, $sql);

if ($stmt) {

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $garageId
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $row = mysqli_fetch_assoc($result);

    $totalStaff = (int)($row["total"] ?? 0);

    mysqli_stmt_close($stmt);
}


/*
|--------------------------------------------------------------------------
| Total Parts
|--------------------------------------------------------------------------
*/
$sql = "
    SELECT COUNT(*) AS total
    FROM parts
    WHERE garage_id = ?
";

$stmt = mysqli_prepare($conn, $sql);

if ($stmt) {

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $garageId
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $row = mysqli_fetch_assoc($result);

    $totalParts = (int)($row["total"] ?? 0);

    mysqli_stmt_close($stmt);
}


/*
|--------------------------------------------------------------------------
| Low Stock Parts
|--------------------------------------------------------------------------
*/
$sql = "
    SELECT COUNT(*) AS total
    FROM parts
    WHERE garage_id = ?
    AND quantity <= minimum_stock
";

$stmt = mysqli_prepare($conn, $sql);

if ($stmt) {

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $garageId
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $row = mysqli_fetch_assoc($result);

    $lowStock = (int)($row["total"] ?? 0);

    mysqli_stmt_close($stmt);
}


/*
|--------------------------------------------------------------------------
| Garage Name
|--------------------------------------------------------------------------
*/
$garageName = "Garage";

if (
    isset($garage["garage_name"]) &&
    trim($garage["garage_name"]) !== ""
) {

    $garageName = trim($garage["garage_name"]);

} elseif (
    isset($_SESSION["garage_name"]) &&
    trim($_SESSION["garage_name"]) !== ""
) {

    $garageName = trim($_SESSION["garage_name"]);
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
        Garage Dashboard - AutoTrack
    </title>


    <!-- Google Font -->

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


    <!-- IMPORTANT: Garage global CSS only -->

    <link
        rel="stylesheet"
        href="/automobile_tracker/css/garage-admin.css"
    >


    <style>

        /* =========================================================
           DASHBOARD HEADER
        ========================================================= */

        .garage-dashboard-header {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;

            margin-bottom: 28px;

        }


        .garage-dashboard-header h1 {

            margin: 0 0 7px;

            font-size: 32px;

            font-weight: 800;

            color: #101828;

        }


        .garage-dashboard-header p {

            margin: 0;

            font-size: 16px;

            color: #667085;

        }



        /* =========================================================
           STATISTICS
        ========================================================= */

        .dashboard-stats {

            display: grid;

            grid-template-columns:
                repeat(3, minmax(0, 1fr));

            gap: 22px;

            margin-bottom: 36px;

        }


        .dashboard-stat-card {

            min-height: 145px;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;

            padding: 26px;

            background: #ffffff;

            border: 1px solid #e2e8f0;

            border-radius: 16px;

            box-shadow:
                0 10px 30px rgba(15, 23, 42, 0.04);

        }


        .dashboard-stat-label {

            display: block;

            margin-bottom: 10px;

            color: #64748b;

            font-size: 16px;

            font-weight: 500;

        }


        .dashboard-stat-number {

            display: block;

            color: #0f172a;

            font-size: 32px;

            font-weight: 800;

        }


        .dashboard-stat-icon {

            width: 58px;

            height: 58px;

            flex: 0 0 58px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 15px;

            background: #eff6ff;

            font-size: 27px;

        }



        /* =========================================================
           SECTION
        ========================================================= */

        .dashboard-section {

            margin-top: 10px;

        }


        .dashboard-section-title {

            margin: 0 0 20px;

            color: #101828;

            font-size: 26px;

            font-weight: 800;

        }



        /* =========================================================
           QUICK ACCESS GRID
        ========================================================= */

        .quick-links {

            display: grid;

            grid-template-columns:
                repeat(3, minmax(0, 1fr));

            gap: 22px;

        }


        .quick-link-card {

            min-height: 190px;

            display: flex;

            flex-direction: column;

            justify-content: flex-start;

            padding: 26px;

            background: #ffffff;

            border: 1px solid #e2e8f0;

            border-radius: 16px;

            color: #101828;

            text-decoration: none;

            box-shadow:
                0 8px 24px rgba(15, 23, 42, 0.03);

            transition:
                transform .2s ease,
                box-shadow .2s ease,
                border-color .2s ease;

        }


        .quick-link-card:hover {

            transform: translateY(-4px);

            border-color: #93c5fd;

            box-shadow:
                0 15px 35px rgba(15, 23, 42, 0.08);

        }


        .quick-icon {

            margin-bottom: 15px;

            font-size: 29px;

        }


        .quick-link-card h3 {

            margin: 0 0 8px;

            color: #101828;

            font-size: 20px;

            font-weight: 750;

        }


        .quick-link-card p {

            margin: 0;

            color: #64748b;

            font-size: 15px;

            line-height: 1.6;

        }



        /* =========================================================
           RESPONSIVE
        ========================================================= */

        @media (max-width: 1100px) {

            .dashboard-stats {

                grid-template-columns:
                    repeat(2, minmax(0, 1fr));

            }


            .quick-links {

                grid-template-columns:
                    repeat(2, minmax(0, 1fr));

            }

        }


        @media (max-width: 700px) {

            .garage-dashboard-header {

                flex-direction: column;

                align-items: flex-start;

            }


            .dashboard-stats {

                grid-template-columns: 1fr;

            }


            .quick-links {

                grid-template-columns: 1fr;

            }

        }

    </style>

</head>


<body>


<!-- =========================================================
     SHARED GARAGE SIDEBAR
========================================================= -->

<?php

require_once '../includes/garage-sidebar.php';

?>


<!-- =========================================================
     MAIN CONTENT
========================================================= -->

<main class="garage-main">


    <!-- =====================================================
         HEADER
    ====================================================== -->

    <header class="garage-dashboard-header">

        <div>

            <h1>
                <?= htmlspecialchars($garageName) ?>
            </h1>

            <p>
                Garage Administration Dashboard
            </p>

        </div>

    </header>



    <!-- =====================================================
         DASHBOARD STATISTICS
    ====================================================== -->

    <section class="dashboard-stats">


        <!-- TOTAL STAFF -->

        <div class="dashboard-stat-card">

            <div>

                <span class="dashboard-stat-label">
                    Total Staff
                </span>

                <strong class="dashboard-stat-number">
                    <?= $totalStaff ?>
                </strong>

            </div>

            <div class="dashboard-stat-icon">
                👥
            </div>

        </div>



        <!-- TOTAL PARTS -->

        <div class="dashboard-stat-card">

            <div>

                <span class="dashboard-stat-label">
                    Parts
                </span>

                <strong class="dashboard-stat-number">
                    <?= $totalParts ?>
                </strong>

            </div>

            <div class="dashboard-stat-icon">
                📦
            </div>

        </div>



        <!-- LOW STOCK -->

        <div class="dashboard-stat-card">

            <div>

                <span class="dashboard-stat-label">
                    Low Stock
                </span>

                <strong class="dashboard-stat-number">
                    <?= $lowStock ?>
                </strong>

            </div>

            <div class="dashboard-stat-icon">
                ⚠️
            </div>

        </div>


    </section>



    <!-- =====================================================
         QUICK ACCESS
    ====================================================== -->

    <section class="dashboard-section">

        <h2 class="dashboard-section-title">
            Quick Access
        </h2>


        <div class="quick-links">


            <!-- STAFF -->

            <a
                href="/automobile_tracker/garage/staff/index.php"
                class="quick-link-card"
            >

                <div class="quick-icon">
                    👥
                </div>

                <h3>
                    Staff Management
                </h3>

                <p>
                    Add, edit and manage garage staff members.
                </p>

            </a>



            <!-- CUSTOMER REQUESTS -->

            <a
                href="/automobile_tracker/garage/requests/index.php"
                class="quick-link-card"
            >

                <div class="quick-icon">
                    📥
                </div>

                <h3>
                    Customer Requests
                </h3>

                <p>
                    Review vehicle-owner connection requests.
                </p>

            </a>



            <!-- CUSTOMERS -->

            <a
                href="/automobile_tracker/garage/customers/index.php"
                class="quick-link-card"
            >

                <div class="quick-icon">
                    🚗
                </div>

                <h3>
                    Customers
                </h3>

                <p>
                    View customers connected to this garage.
                </p>

            </a>



            <!-- APPOINTMENTS -->

            <a
                href="/automobile_tracker/garage/appointments/index.php"
                class="quick-link-card"
            >

                <div class="quick-icon">
                    📅
                </div>

                <h3>
                    Appointments
                </h3>

                <p>
                    Manage upcoming customer service bookings.
                </p>

            </a>



            <!-- SERVICES -->

            <a
                href="/automobile_tracker/garage/services/index.php"
                class="quick-link-card"
            >

                <div class="quick-icon">
                    🔧
                </div>

                <h3>
                    Garage Services
                </h3>

                <p>
                    Maintain services, pricing and duration.
                </p>

            </a>



            <!-- PARTS -->

            <a
                href="/automobile_tracker/garage/parts/index.php"
                class="quick-link-card"
            >

                <div class="quick-icon">
                    📦
                </div>

                <h3>
                    Parts Inventory
                </h3>

                <p>
                    View stock quantities and low-stock items.
                </p>

            </a>


        </div>

    </section>


</main>


</body>

</html>