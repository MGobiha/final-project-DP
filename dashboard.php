<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

require_once 'config/database.php';


// =====================================================
// CHECK LOGIN
// =====================================================

if (
    !isset($_SESSION["user_id"])
    ||
    !isset($_SESSION["role"])
) {

    header(
        "Location: login.php"
    );

    exit();
}


// =====================================================
// CHECK ROLE
// THIS DASHBOARD IS ONLY FOR VEHICLE OWNERS
// =====================================================

if (
    $_SESSION["role"]
    !== "vehicle_owner"
) {

    switch (
        $_SESSION["role"]
    ) {


        // SYSTEM ADMIN

        case "system_admin":

            header(
                "Location: admin/dashboard.php"
            );

            exit();


        // GARAGE ADMIN

        case "garage_admin":

            header(
                "Location: garage/dashboard.php"
            );

            exit();


        // GARAGE STAFF

        case "garage_staff":

        header(
            "Location: garage/staff/dashboard.php"
        );

        exit();


        // INVALID ROLE

        default:

            header(
                "Location: logout.php"
            );

            exit();
    }
}


// =====================================================
// VEHICLE OWNER USER ID
// =====================================================

$userId =
    (int)
    $_SESSION["user_id"];


// =====================================================
// LOAD LOGGED-IN USER
// =====================================================

$sql = "
    SELECT
        first_name,
        last_name,
        email,
        mobile_number,
        role
    FROM users
    WHERE user_id = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("User query error: " . mysqli_error($conn));
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $userId
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$user = mysqli_fetch_assoc($result);

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit();
}

if (
    $user["role"] !== "vehicle_owner"
) {

    $_SESSION["role"] =
        $user["role"];

    switch (
        $user["role"]
    ) {

        case "system_admin":

            header(
                "Location: admin/dashboard.php"
            );

            exit();


        case "garage_admin":

            header(
                "Location: garage/dashboard.php"
            );

            exit();


        case "garage_staff":

            header(
                "Location: garage/staff/dashboard.php"
            );

            exit();


        default:

            header(
                "Location: logout.php"
            );

            exit();
    }
}
// =====================================================
// VEHICLE COUNT
// =====================================================

$totalVehicles = 0;

$sql = "
    SELECT COUNT(*) AS total
    FROM vehicles
    WHERE user_id = ?
";

$stmt = mysqli_prepare($conn, $sql);

if ($stmt) {

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $userId
    );

    mysqli_stmt_execute($stmt);

    $result =
        mysqli_stmt_get_result($stmt);

    $row =
        mysqli_fetch_assoc($result);

    $totalVehicles =
        $row['total'] ?? 0;
}


// =====================================================
// TOTAL SERVICES
// =====================================================

$totalServices = 0;

$sql = "
    SELECT COUNT(*) AS total

    FROM service_records sr

    INNER JOIN vehicles v
        ON sr.vehicle_id = v.vehicle_id

    WHERE v.user_id = ?
";

$stmt = mysqli_prepare($conn, $sql);

if ($stmt) {

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $userId
    );

    mysqli_stmt_execute($stmt);

    $result =
        mysqli_stmt_get_result($stmt);

    $row =
        mysqli_fetch_assoc($result);

    $totalServices =
        $row['total'] ?? 0;
}


// =====================================================
// TOTAL COST
// =====================================================

$totalExpenses = 0;

$sql = "
    SELECT
        COALESCE(
            SUM(sr.total_cost),
            0
        ) AS total

    FROM service_records sr

    INNER JOIN vehicles v
        ON sr.vehicle_id = v.vehicle_id

    WHERE v.user_id = ?
";

$stmt = mysqli_prepare($conn, $sql);

if ($stmt) {

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $userId
    );

    mysqli_stmt_execute($stmt);

    $result =
        mysqli_stmt_get_result($stmt);

    $row =
        mysqli_fetch_assoc($result);

    $totalExpenses =
        $row['total'] ?? 0;
}


// =====================================================
// OPEN REMINDERS
// =====================================================

$totalReminders = 0;

$sql = "
    SELECT COUNT(*) AS total
    FROM notifications
    WHERE user_id = ?
    AND notification_type = 'maintenance'
    AND notification_status IN (
        'pending',
        'sent'
    )
";

$stmt = mysqli_prepare($conn, $sql);

if ($stmt) {

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $userId
    );

    mysqli_stmt_execute($stmt);

    $result =
        mysqli_stmt_get_result($stmt);

    $row =
        mysqli_fetch_assoc($result);

    $totalReminders =
        $row['total'] ?? 0;
}


// =====================================================
// UPCOMING MAINTENANCE LIST
// =====================================================

$upcomingResult = false;

$upcomingSql = "
    SELECT
        ms.schedule_id,
        ms.maintenance_type,
        ms.due_date,
        ms.due_mileage,
        ms.schedule_status,
        v.make,
        v.model,
        v.registration_number

    FROM maintenance_schedule ms

    INNER JOIN vehicles v
        ON ms.vehicle_id = v.vehicle_id

    WHERE v.user_id = ?

    AND ms.schedule_status IN (
        'upcoming',
        'due',
        'overdue'
    )

    ORDER BY ms.due_date ASC

    LIMIT 5
";

$stmt = mysqli_prepare(
    $conn,
    $upcomingSql
);

if (!$stmt) {
    die(
        "Upcoming maintenance query error: "
        . mysqli_error($conn)
    );
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $userId
);

mysqli_stmt_execute($stmt);

$upcomingResult =
    mysqli_stmt_get_result($stmt);

    // =====================================================
// UPCOMING MAINTENANCE COUNT
// =====================================================

$maintenanceCountSql = "
    SELECT COUNT(*) AS total
    FROM maintenance_schedule ms

    INNER JOIN vehicles v
        ON ms.vehicle_id = v.vehicle_id

    WHERE v.user_id = ?

    AND ms.schedule_status IN (
        'upcoming',
        'due',
        'overdue'
    )
";

$stmt = mysqli_prepare(
    $conn,
    $maintenanceCountSql
);

if (!$stmt) {
    die(
        "Maintenance count query error: "
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

$row =
    mysqli_fetch_assoc($result);

$upcomingMaintenance =
    (int) ($row["total"] ?? 0);
?>

<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="css/style.css" />
    <link rel="stylesheet" href="css/responsive.css" />
  </head>
  <body data-page="dashboard">
    <div class="app-shell">
      <aside class="sidebar">
        <div class="brand">
          <div class="brand-badge">A</div>
          <span>AutoTrack</span>
        </div>
        <nav class="nav">

    <a
        data-page="dashboard"
        href="dashboard.php"
    >
        🏠 <span>Dashboard</span>
    </a>

    <a
        data-page="vehicles"
        href="vehicles.php"
    >
        🚙 <span>Vehicles</span>
    </a>

    <a
        data-page="service"
        href="service-history.php"
    >
        🧾 <span>Service History</span>
    </a>

    <a
        data-page="maintenance"
        href="maintenance.php"
    >
        🗓️ <span>Maintenance</span>
    </a>

    <a
        data-page="reminders"
        href="reminders.php"
    >
        🔔 <span>Reminders</span>
    </a>

    <a
        data-page="chatbot"
        href="chatbot.php"
    >
        🤖 <span>AI Assistant</span>
    </a>

    <a
        data-page="garages"
        href="garages.php"
    >
        📍 <span>Garages</span>
    </a>

    <a
        data-page="news"
        href="news.php"
    >
        📰 <span>News</span>
    </a>

    <a
        data-page="profile"
        href="profile.php"
    >
        👤 <span>Profile</span>
    </a>

</nav>
      </aside>
      <main class="main">
        <header class="topbar">
          <div class="title">
            <h1>Dashboard</h1>
            <p>Automobile Service and Maintenance Tracker</p>
          </div>
          <div class="user-chip">
            <div class="avatar">AM</div>
            <span data-user-name>
              <?php
              echo htmlspecialchars(
                  $_SESSION["first_name"]
              );
              ?>
            </span>
            <span>
              
            <a
                      href="logout.php"
                      class="btn btn-secondary"
                  >
                      Logout
                  </a>
                </span>
          </div>
        </header>
        <section class="grid grid-4">
          <div class="card stat">

              <p>My Vehicles</p>

              <h2>
                  <?php echo $totalVehicles; ?>
              </h2>

          </div>
          
          
          <div class="card stat">

            <p>Total Services</p>

            <h2>
                <?php echo $totalServices; ?>
            </h2>

        </div>
        <div class="card stat">

            <p>Total Maintenance Cost</p>

            <h2>
                Rs.
                <?php
                echo number_format(
                    $totalExpenses,
                    2
                );
                ?>
            </h2>

        </div>
        
          
            <div class="card stat">

              <p>Upcoming Maintenance</p>

              <h2>
                  <?php
                  echo $upcomingMaintenance;
                  ?>
              </h2>

              <div class="icon">🗓️</div>

          </div>
          <div class="card stat">
            <div>
              <span class="muted">Open Reminders</span>
              <strong><?php echo $totalReminders; ?></strong>
            </div>
            <div class="icon">🔔</div>
          </div>

          <div class="card stat">
            <div>
              <span class="muted">Total Records</span>
              
              <h2>
                  <?php echo $totalServices; ?>
              </h2>
            </div>
            <div class="icon">🧾</div>
          </div>

        </section>
        <div class="section-head">
          <h2>Upcoming maintenance</h2>
          <a class="btn btn-primary" href="maintenance.php"
            >Schedule Service</a
          >
        </div>
        <div class="card table-wrap">
          <table class="table">
            <thead>
              <tr>
                <th>Vehicle</th>
                <th>Service</th>
                <th>Due</th>
                <th>Status</th>
              </tr>
            </thead>
           <tbody>

              <?php if (
                            $upcomingResult
                            &&
                            mysqli_num_rows($upcomingResult) > 0
                        ): ?>

                  <?php while (
                      $maintenance =
                      mysqli_fetch_assoc($upcomingResult)
                  ): ?>

                      <tr>

                          <td>
                              <?php
                              echo htmlspecialchars(
                                  $maintenance["make"]
                                  . " "
                                  . $maintenance["model"]
                              );
                              ?>
                          </td>

                          <td>
                              <?php
                              echo htmlspecialchars(
                                  $maintenance[
                                      "maintenance_type"
                                  ]
                              );
                              ?>
                          </td>

                          <td>

                              <?php if (
                                  !empty(
                                      $maintenance["due_date"]
                                  )
                              ): ?>

                                  <?php
                                  echo date(
                                      "d M Y",
                                      strtotime(
                                          $maintenance["due_date"]
                                      )
                                  );
                                  ?>

                              <?php elseif (
                                  !empty(
                                      $maintenance["due_mileage"]
                                  )
                              ): ?>

                                  <?php
                                  echo number_format(
                                      $maintenance["due_mileage"]
                                  );
                                  ?>
                                  km

                              <?php else: ?>

                                  -

                              <?php endif; ?>

                          </td>

                          <td>

                              <?php
                              $status =
                                  $maintenance[
                                      "schedule_status"
                                  ];
                              ?>

                              <span class="badge
                                  <?php
                                  echo $status === "overdue"
                                      ? "danger"
                                      : (
                                          $status === "due"
                                          ? "warning"
                                          : "info"
                                      );
                                  ?>
                              ">

                                  <?php
                                  echo ucfirst($status);
                                  ?>

                              </span>

                          </td>

                      </tr>

                  <?php endwhile; ?>

              <?php else: ?>

                  <tr>
                      <td colspan="4">
                          No upcoming maintenance found.
                      </td>
                  </tr>

              <?php endif; ?>

              </tbody>
          </table>
        </div>
        <!-- <div class="section-head">
          <h2>Vehicle health</h2>
        </div>
        <section class="grid grid-3">
          <div class="card">
            <strong>Toyota Corolla</strong>
            <p class="muted">82,450 km</p>
            <div class="progress">
              <span style="width: 78%"></span>
            </div>
            <p class="muted">Maintenance score: 78%</p>
          </div>
          <div class="card">
            <strong>Honda Civic</strong>
            <p class="muted">56,120 km</p>
            <div class="progress">
              <span style="width: 91%"></span>
            </div>
            <p class="muted">Maintenance score: 91%</p>
          </div>
          <div class="card">
            <strong>Suzuki Swift</strong>
            <p class="muted">31,800 km</p>
            <div class="progress">
              <span style="width: 86%"></span>
            </div>
            <p class="muted">Maintenance score: 86%</p>
          </div> -->
        </section>
        <div class="footer-note">
          AutoTrack UI prototype • Static frontend demo
        </div>
      </main>
    </div>
    <script src="js/app.js"></script>
  </body>
</html>
