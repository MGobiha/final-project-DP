<?php

$currentPage =
    basename($_SERVER["PHP_SELF"]);

?>
<link
    rel="stylesheet"
    href="css/style.css"
>

<link
    rel="stylesheet"
    href="css/dashboard-layout.css"
>
<aside class="sidebar">

    <div class="brand">

        <div class="brand-badge">
            A
        </div>

        <span>
            AutoTrack
        </span>

    </div>


    <nav class="nav">

        <a
            href="/automobile_tracker/dashboard.php"
            class="<?php
            echo $currentPage === 'dashboard.php'
                ? 'active'
                : '';
            ?>"
        >
            🏠
            <span>Dashboard</span>
        </a>


        <a
            href="/automobile_tracker/vehicles.php"
            class="<?php
            echo $currentPage === 'vehicles.php'
                ? 'active'
                : '';
            ?>"
        >
            🚙
            <span>Vehicles</span>
        </a>


        <a
            href="/automobile_tracker/service-history.php"
            class="<?php
            echo $currentPage === 'service-history.php'
                ? 'active'
                : '';
            ?>"
        >
            🧾
            <span>Service History</span>
        </a>


        <a
            href="/automobile_tracker/maintenance.php"
            class="<?php
            echo $currentPage === 'maintenance.php'
                ? 'active'
                : '';
            ?>"
        >
            🗓️
            <span>Maintenance</span>
        </a>

        <a
            data-page="request-service"
            href="/automobile_tracker/request-service.php"
            class="<?php
            echo $currentPage === 'request-service.php'
                ? 'active'
                : '';
            ?>"
        >
            🛠️ <span>Book a Service</span>
        </a>

        <a
            href="/automobile_tracker/reminders.php"
            class="<?php
            echo $currentPage === 'reminders.php'
                ? 'active'
                : '';
            ?>"
        >
            🔔
            <span>Reminders</span>
        </a>


        <a
            href="/automobile_tracker/chatbot.php"
            class="<?php
            echo $currentPage === 'chatbot.php'
                ? 'active'
                : '';
            ?>"
        >
            🤖
            <span>AI Assistant</span>
        </a>


        <a
            href="/automobile_tracker/garages.php"
            class="<?php
            echo $currentPage === 'garages.php'
                ? 'active'
                : '';
            ?>"
        >
            📍
            <span>Garages</span>
        </a>


        <a
            href="/automobile_tracker/news.php"
            class="<?php
            echo $currentPage === 'news.php'
                ? 'active'
                : '';
            ?>"
        >
            📰
            <span>News</span>
        </a>


        <a
            href="/automobile_tracker/profile.php"
            class="<?php
            echo $currentPage === 'profile.php'
                ? 'active'
                : '';
            ?>"
        >
            👤
            <span>Profile</span>
        </a>


        <a
            href="/automobile_tracker/logout.php"
        >
            🚪
            <span>Logout</span>
        </a>

    </nav>

</aside>