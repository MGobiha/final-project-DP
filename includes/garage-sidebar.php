<aside class="sidebar">

    <div class="garage-brand">

        <?php if (
            !empty($garage["image"])
        ): ?>

            <img
                src="/automobile_tracker/uploads/garages/<?php
                echo htmlspecialchars(
                    $garage["image"]
                );
                ?>"
                class="garage-sidebar-logo"
                alt="Garage Logo"
            >

        <?php else: ?>

            <div class="brand-badge">

                <?php
                echo strtoupper(
                    substr(
                        $garage["garage_name"]
                        ?? "A",
                        0,
                        1
                    )
                );
                ?>

            </div>

        <?php endif; ?>


        <div class="garage-brand-text">

            <strong>

                <?php
                echo htmlspecialchars(
                    $garage["garage_name"]
                    ?? "AutoTrack Garage"
                );
                ?>

            </strong>

            <small>
                Garage Admin
            </small>

        </div>

    </div>


    <nav class="nav">

        <a
            href="/automobile_tracker/garage/dashboard.php"
            class="<?php
            echo ($activePage ?? '') === 'dashboard'
                ? 'active'
                : '';
            ?>"
        >
            🏠
            <span>Dashboard</span>
        </a>


        <a
            href="/automobile_tracker/garage/staff/index.php"
            class="<?php
            echo ($activePage ?? '') === 'staff'
                ? 'active'
                : '';
            ?>"
        >
            👥
            <span>Staff Management</span>
        </a>


        <a
            href="/automobile_tracker/garage/customers/index.php"
            class="<?php
            echo ($activePage ?? '') === 'customers'
                ? 'active'
                : '';
            ?>"
        >
            🚗
            <span>Customers</span>
        </a>


        <a
            href="/automobile_tracker/garage/requests/index.php"
            class="<?php
            echo ($activePage ?? '') === 'requests'
                ? 'active'
                : '';
            ?>"
        >
            📥
            <span>Customer Requests</span>
        </a>


        <a
            href="/automobile_tracker/garage/appointments/index.php"
            class="<?php
            echo ($activePage ?? '') === 'appointments'
                ? 'active'
                : '';
            ?>"
        >
            📅
            <span>Appointments</span>
        </a>


        <a
            href="/automobile_tracker/garage/services/index.php"
            class="<?php
            echo ($activePage ?? '') === 'services'
                ? 'active'
                : '';
            ?>"
        >
            🔧
            <span>Garage Services</span>
        </a>


        <a
            href="/automobile_tracker/garage/reminders/index.php"
            class="<?php
            echo ($activePage ?? '') === 'reminders'
                ? 'active'
                : '';
            ?>"
        >
            🔔
            <span>Customer Reminders</span>
        </a>


        <a
            href="/automobile_tracker/garage/news/index.php"
            class="<?php
            echo ($activePage ?? '') === 'news'
                ? 'active'
                : '';
            ?>"
        >
            📰
            <span>Garage News</span>
        </a>


        <a
            href="/automobile_tracker/garage/service-records/index.php"
            class="<?php
            echo ($activePage ?? '') === 'records'
                ? 'active'
                : '';
            ?>"
        >
            🧾
            <span>Service Records</span>
        </a>


        <a
            href="/automobile_tracker/garage/repair-costs/index.php"
            class="<?php
            echo ($activePage ?? '') === 'costs'
                ? 'active'
                : '';
            ?>"
        >
            💰
            <span>Repair Costs</span>
        </a>


        <a
            href="/automobile_tracker/garage/profile.php"
            class="<?php
            echo ($activePage ?? '') === 'profile'
                ? 'active'
                : '';
            ?>"
        >
            ⚙️
            <span>Garage Profile</span>
        </a>


        <a
            href="/automobile_tracker/logout.php"
        >
            🚪
            <span>Logout</span>
        </a>

    </nav>

</aside>