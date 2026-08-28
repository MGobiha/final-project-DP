<?php

$currentPage = $currentPage ?? "";


/*
|--------------------------------------------------------------------------
| GARAGE NAME
|--------------------------------------------------------------------------
*/

$sidebarGarageName = "Garage";


if (
    isset($garage["garage_name"])
    &&
    trim($garage["garage_name"]) !== ""
) {

    $sidebarGarageName =
        trim(
            $garage["garage_name"]
        );

} elseif (
    isset($_SESSION["garage_name"])
    &&
    trim($_SESSION["garage_name"]) !== ""
) {

    $sidebarGarageName =
        trim(
            $_SESSION["garage_name"]
        );
}


/*
|--------------------------------------------------------------------------
| GARAGE INITIAL
|--------------------------------------------------------------------------
*/

$garageInitial = "G";

if ($sidebarGarageName !== "") {

    $garageInitial =
        strtoupper(
            substr(
                $sidebarGarageName,
                0,
                1
            )
        );
}

?>

<aside class="garage-sidebar">


    <div class="garage-brand">


        <div class="garage-brand-badge">

            <?= htmlspecialchars(
                $garageInitial,
                ENT_QUOTES,
                "UTF-8"
            ) ?>

        </div>


        <div class="garage-brand-text">


            <strong>

                <?= htmlspecialchars(
                    $sidebarGarageName,
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>

            </strong>


            <span>
                Garage Admin
            </span>


        </div>


    </div>



    <nav class="garage-nav">


        <a
            href="/automobile_tracker/garage/dashboard.php"
            class="<?= $currentPage === "dashboard" ? "active" : "" ?>"
        >
            <span class="nav-icon">🏠</span>
            <span>Dashboard</span>
        </a>


        <a
            href="/automobile_tracker/garage/staff/index.php"
            class="<?= $currentPage === "staff" ? "active" : "" ?>"
        >
            <span class="nav-icon">👥</span>
            <span>Staff Management</span>
        </a>


        <a
            href="/automobile_tracker/garage/customers/index.php"
            class="<?= $currentPage === "customers" ? "active" : "" ?>"
        >
            <span class="nav-icon">🚗</span>
            <span>Customers</span>
        </a>


        <a
            href="/automobile_tracker/garage/requests/index.php"
            class="<?= $currentPage === "requests" ? "active" : "" ?>"
        >
            <span class="nav-icon">📥</span>
            <span>Customer Requests</span>
        </a>


        <a
            href="/automobile_tracker/garage/appointments/index.php"
            class="<?= $currentPage === "appointments" ? "active" : "" ?>"
        >
            <span class="nav-icon">🗓️</span>
            <span>Appointments</span>
        </a>


        <a
            href="/automobile_tracker/garage/services/index.php"
            class="<?= $currentPage === "services" ? "active" : "" ?>"
        >
            <span class="nav-icon">🔧</span>
            <span>Garage Services</span>
        </a>


        <a
            href="/automobile_tracker/garage/reminders/index.php"
            class="<?= $currentPage === "reminders" ? "active" : "" ?>"
        >
            <span class="nav-icon">🔔</span>
            <span>Customer Reminders</span>
        </a>


        <a
            href="/automobile_tracker/garage/news/index.php"
            class="<?= $currentPage === "news" ? "active" : "" ?>"
        >
            <span class="nav-icon">📰</span>
            <span>Garage News</span>
        </a>


        <a
            href="/automobile_tracker/garage/service-records/index.php"
            class="<?= $currentPage === "records" ? "active" : "" ?>"
        >
            <span class="nav-icon">🧾</span>
            <span>Service Records</span>
        </a>


        <a
            href="/automobile_tracker/garage/repair-costs/index.php"
            class="<?= $currentPage === "costs" ? "active" : "" ?>"
        >
            <span class="nav-icon">💰</span>
            <span>Repair Costs</span>
        </a>


        <a
            href="/automobile_tracker/garage/profile.php"
            class="<?= $currentPage === "profile" ? "active" : "" ?>"
        >
            <span class="nav-icon">⚙️</span>
            <span>Garage Profile</span>
        </a>


        <a
            href="/automobile_tracker/garage/logout.php"
            class="garage-logout"
        >
            <span class="nav-icon">🚪</span>
            <span>Logout</span>
        </a>


    </nav>


</aside>