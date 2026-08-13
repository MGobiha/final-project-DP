<?php

require_once 'auth.php';

$totalStaff = 0;
$totalParts = 0;
$lowStock = 0;


// TOTAL STAFF

$sql = "
    SELECT COUNT(*) AS total
    FROM garage_staff
    WHERE garage_id = ?
    AND employment_status = 'active'
";

$stmt = mysqli_prepare($conn, $sql);

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


// TOTAL PARTS

$sql = "
    SELECT COUNT(*) AS total
    FROM parts
    WHERE garage_id = ?
";

$stmt = mysqli_prepare($conn, $sql);

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


// LOW STOCK

$sql = "
    SELECT COUNT(*) AS total
    FROM parts
    WHERE garage_id = ?
    AND quantity <= minimum_stock
";

$stmt = mysqli_prepare($conn, $sql);

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
<html>
<head>

    <meta charset="utf-8">

    <title>
        Garage Dashboard
    </title>
<link
    rel="stylesheet"
    href="../css/garage-admin.css"
>
    <link
        rel="stylesheet"
        href="../css/style.css"
    >

</head>

<body>

<div class="app-shell">

    <aside class="sidebar">

        <div class="brand">
            AutoTrack Garage
        </div>

        <nav class="nav">

            <a href="dashboard.php">
                🏠 Dashboard
            </a>

            <a href="staff/index.php">
                👥 Staff
            </a>

            <a href="attendance/index.php">
                🕒 Attendance
            </a>

            <a href="leave/index.php">
                🏖 Leave
            </a>

            <a href="salary/index.php">
                💰 Salary
            </a>

            <a href="parts/index.php">
                📦 Parts
            </a>

            <a href="orders/index.php">
                🚚 Orders
            </a>

            <a href="accounts/index.php">
                💳 Accounts
            </a>

            <a href="services/index.php">
                🔧 Services
            </a>

            <a href="news/index.php">
                📰 News
            </a>

            <a href="../logout.php">
                🚪 Logout
            </a>

        </nav>

    </aside>


    <main class="main">

        <header class="topbar">

            <div>

                <h1>
                    <?php
                    echo htmlspecialchars(
                        $garage["garage_name"]
                    );
                    ?>
                </h1>

                <p>
                    Garage Administration
                </p>

            </div>

        </header>


        <section class="grid grid-3">

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

    </main>

</div>

</body>
</html>