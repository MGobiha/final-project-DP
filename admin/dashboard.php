<?php

session_start();

require_once '../config/database.php';

require_once 'auth.php';
// =====================================================
// SYSTEM ADMIN SECURITY
// =====================================================

if (
    !isset($_SESSION["user_id"])
    ||
    ($_SESSION["role"] ?? "") !== "system_admin"
) {

    header("Location: ../login.php");
    exit();
}


// =====================================================
// DASHBOARD COUNTERS
// =====================================================

$totalGarages = 0;
$pendingGarages = 0;
$approvedGarages = 0;
$totalUsers = 0;


// =====================================================
// TOTAL GARAGES
// =====================================================

$sql = "
    SELECT COUNT(*) AS total
    FROM garages
";

$result = mysqli_query($conn, $sql);

if ($result) {

    $row = mysqli_fetch_assoc($result);

    $totalGarages =
        (int) ($row["total"] ?? 0);
}


// =====================================================
// PENDING GARAGES
// =====================================================

$sql = "
    SELECT COUNT(*) AS total
    FROM garages
    WHERE approval_status = 'pending'
";

$result = mysqli_query($conn, $sql);

if ($result) {

    $row = mysqli_fetch_assoc($result);

    $pendingGarages =
        (int) ($row["total"] ?? 0);
}


// =====================================================
// APPROVED GARAGES
// =====================================================

$sql = "
    SELECT COUNT(*) AS total
    FROM garages
    WHERE approval_status = 'approved'
";

$result = mysqli_query($conn, $sql);

if ($result) {

    $row = mysqli_fetch_assoc($result);

    $approvedGarages =
        (int) ($row["total"] ?? 0);
}


// =====================================================
// TOTAL USERS
// =====================================================

$sql = "
    SELECT COUNT(*) AS total
    FROM users
";

$result = mysqli_query($conn, $sql);

if ($result) {

    $row = mysqli_fetch_assoc($result);

    $totalUsers =
        (int) ($row["total"] ?? 0);
}


// =====================================================
// RECENT PENDING GARAGES
// =====================================================

$sql = "
    SELECT
        garage_id,
        garage_name,
        owner_name,
        email,
        mobile_number,
        city,
        district,
        created_at

    FROM garages

    WHERE approval_status = 'pending'

    ORDER BY created_at DESC

    LIMIT 5
";

$pendingResult =
    mysqli_query($conn, $sql);

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
        System Admin Dashboard - AutoTrack
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
        href="../css/dashboard-layout.css"
    >

    <style>

        .admin-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 28px;
        }

        .admin-header h1 {
            margin: 0 0 5px;
        }

        .admin-header p {
            margin: 0;
            color: #667085;
        }

        .admin-stats {
            display: grid;

            grid-template-columns:
                repeat(4, minmax(0, 1fr));

            gap: 20px;

            margin-bottom: 35px;
        }

        .admin-stat {
            background: white;

            border: 1px solid #e4e7ec;

            border-radius: 18px;

            padding: 25px;

            min-height: 130px;

            display: flex;
            justify-content: space-between;
            align-items: center;

            box-shadow:
                0 10px 30px
                rgba(15, 35, 65, .05);
        }

        .admin-stat span {
            display: block;

            color: #667085;

            margin-bottom: 10px;
        }

        .admin-stat strong {
            font-size: 30px;
        }

        .admin-stat-icon {
            width: 55px;
            height: 55px;

            border-radius: 14px;

            background: #eff6ff;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 25px;
        }

        .pending-stat {
            border-color: #fbbf24;
        }

        .section-header {
            display: flex;

            justify-content: space-between;
            align-items: center;

            margin-bottom: 15px;
        }

        .section-header h2 {
            margin: 0;
        }

        .pending-badge {
            display: inline-block;

            padding: 5px 10px;

            border-radius: 999px;

            background: #fef3c7;

            color: #92400e;

            font-size: 12px;
            font-weight: 700;
        }

        .admin-actions {
            display: flex;
            gap: 8px;
        }

        @media(max-width:1000px) {

            .admin-stats {
                grid-template-columns:
                    repeat(2, 1fr);
            }

        }

        @media(max-width:600px) {

            .admin-stats {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>


<body>

<div class="app-shell">


    <!-- =============================================
         SYSTEM ADMIN SIDEBAR
         ============================================= -->

    <aside class="sidebar">

        <div class="brand">

            <div class="brand-badge">
                A
            </div>

            <span>
                AutoTrack Admin
            </span>

        </div>


        <nav class="nav">


            <a
                href="/automobile_tracker/admin/dashboard.php"
                class="active"
            >
                🏠
                <span>Dashboard</span>
            </a>


            <a
                href="/automobile_tracker/admin/garages/requests.php"
            >
                📥

                <span>
                    Garage Requests

                    <?php if ($pendingGarages > 0): ?>

                        (<?php echo $pendingGarages; ?>)

                    <?php endif; ?>

                </span>

            </a>


            <a
                href="/automobile_tracker/admin/garages.php"
            >
                🏢
                <span>
                    Garages
                </span>
            </a>


            <a
                href="/automobile_tracker/admin/users.php"
            >
                👥
                <span>
                    Users
                </span>
            </a>


            <a
                href="/automobile_tracker/logout.php"
            >
                🚪
                <span>
                    Logout
                </span>
            </a>


        </nav>

    </aside>


    <!-- =============================================
         MAIN
         ============================================= -->

    <main class="main">


        <div class="admin-header">

            <div>

                <h1>
                    System Administration
                </h1>

                <p>
                    Automobile Service and
                    Maintenance Tracker
                </p>

            </div>


            <a
                href="/automobile_tracker/logout.php"
                class="btn btn-secondary"
            >
                Logout
            </a>

        </div>


        <!-- =========================================
             DASHBOARD CARDS
             ========================================= -->

        <section class="admin-stats">


            <div class="admin-stat">

                <div>

                    <span>
                        Total Garages
                    </span>

                    <strong>
                        <?php
                        echo $totalGarages;
                        ?>
                    </strong>

                </div>

                <div class="admin-stat-icon">
                    🏢
                </div>

            </div>


            <div
                class="
                    admin-stat
                    pending-stat
                "
            >

                <div>

                    <span>
                        Pending Garage Requests
                    </span>

                    <strong>
                        <?php
                        echo $pendingGarages;
                        ?>
                    </strong>

                </div>

                <div class="admin-stat-icon">
                    📥
                </div>

            </div>


            <div class="admin-stat">

                <div>

                    <span>
                        Approved Garages
                    </span>

                    <strong>
                        <?php
                        echo $approvedGarages;
                        ?>
                    </strong>

                </div>

                <div class="admin-stat-icon">
                    ✅
                </div>

            </div>


            <div class="admin-stat">

                <div>

                    <span>
                        Total Users
                    </span>

                    <strong>
                        <?php
                        echo $totalUsers;
                        ?>
                    </strong>

                </div>

                <div class="admin-stat-icon">
                    👥
                </div>

            </div>


        </section>


        <!-- =========================================
             PENDING GARAGES
             ========================================= -->

        <div class="section-header">

            <h2>
                Recent Garage Requests
            </h2>


            <a
                href="/automobile_tracker/admin/garages/requests.php"
                class="btn btn-primary"
            >
                View All Requests
            </a>

        </div>


        <div class="card table-wrap">

            <table class="table">

                <thead>

                    <tr>

                        <th>
                            Garage
                        </th>

                        <th>
                            Owner
                        </th>

                        <th>
                            Contact
                        </th>

                        <th>
                            Location
                        </th>

                        <th>
                            Status
                        </th>

                        <th>
                            Action
                        </th>

                    </tr>

                </thead>


                <tbody>


                <?php if (
                    $pendingResult
                    &&
                    mysqli_num_rows(
                        $pendingResult
                    ) > 0
                ): ?>


                    <?php while (
                        $garage =
                        mysqli_fetch_assoc(
                            $pendingResult
                        )
                    ): ?>


                        <tr>

                            <td>

                                <strong>

                                    <?php
                                    echo
                                    htmlspecialchars(
                                        $garage[
                                            "garage_name"
                                        ]
                                    );
                                    ?>

                                </strong>

                            </td>


                            <td>

                                <?php
                                echo
                                htmlspecialchars(
                                    $garage[
                                        "owner_name"
                                    ]
                                    ?? "-"
                                );
                                ?>

                            </td>


                            <td>

                                <?php
                                echo
                                htmlspecialchars(
                                    $garage[
                                        "mobile_number"
                                    ]
                                    ?? "-"
                                );
                                ?>

                                <br>

                                <small>

                                    <?php
                                    echo
                                    htmlspecialchars(
                                        $garage[
                                            "email"
                                        ]
                                        ?? ""
                                    );
                                    ?>

                                </small>

                            </td>


                            <td>

                                <?php
                                echo
                                htmlspecialchars(
                                    $garage[
                                        "city"
                                    ]
                                    ?? "-"
                                );
                                ?>

                                <?php if (
                                    !empty(
                                        $garage[
                                            "district"
                                        ]
                                    )
                                ): ?>

                                    <br>

                                    <small>

                                        <?php
                                        echo
                                        htmlspecialchars(
                                            $garage[
                                                "district"
                                            ]
                                        );
                                        ?>

                                    </small>

                                <?php endif; ?>

                            </td>


                            <td>

                                <span
                                    class="pending-badge"
                                >
                                    Pending
                                </span>

                            </td>


                            <td>

                                <a
                                    href="/automobile_tracker/admin/garages/requests.php"
                                    class="btn btn-primary"
                                >
                                    Review
                                </a>

                            </td>

                        </tr>


                    <?php endwhile; ?>


                <?php else: ?>


                    <tr>

                        <td
                            colspan="6"
                            style="
                                text-align:center;
                                padding:30px;
                            "
                        >
                            No pending garage
                            registration requests.
                        </td>

                    </tr>


                <?php endif; ?>


                </tbody>

            </table>

        </div>


    </main>

</div>

</body>

</html>