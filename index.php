<?php

require_once 'config/database.php';

$garageSql = "
    SELECT
        garage_id,
        garage_name,
        address,
        city,
        district,
        mobile_number,
        opening_time,
        closing_time,
        description

    FROM garages

    WHERE approval_status = 'approved'
    AND active_status = 1

    AND district IN (
        'Jaffna',
        'Kilinochchi',
        'Mullaitivu',
        'Mannar',
        'Vavuniya'
    )

    ORDER BY district, garage_name

    LIMIT 6
";

$garageResult = mysqli_query(
    $conn,
    $garageSql
);



require_once 'config/database.php';

$garageSql = "
    SELECT
        garage_id,
        garage_name,
        owner_name,
        email,
        mobile_number,
        telephone,
        address,
        city,
        district,
        latitude,
        longitude,
        opening_time,
        closing_time,
        description,
        image

    FROM garages

    WHERE approval_status = 'approved'
    AND active_status = 1

    ORDER BY district, garage_name
";

$garageResult = mysqli_query($conn, $garageSql);

if (!$garageResult) {
    die(
        "Garage query failed: "
        . mysqli_error($conn)
    );
}

?>

<!doctype html>
<html>
  <head>
    <meta charset="utf-8" />
    <!-- <meta http-equiv="refresh" content="0; url=login.html" /> -->
     <link
  href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
  rel="stylesheet"/>
    <title>AutoTrack</title>
    <link rel="stylesheet" href="css/style.css" />
    <script src="js/common.js"></script>
    <script src="js/garages.js"></script>
  </head>
  
  <body>
  <header class="public-header">

    <div class="container nav-wrapper">

        <a href="index.php" class="brand">
            AutoTrack
        </a>

        <nav class="public-nav">

            <a href="#home">
                Home
            </a>

            <a href="#services">
                Services
            </a>

            <a href="#garages">
                Garages
            </a>

            <a href="#about">
                About
            </a>

            <a href="login.php">
                Login
            </a>

            <a
                href="register.php"
                class="btn btn-primary"
            >
                Register
            </a>

        </nav>

    </div>

</header>

<section
  id="home"
  class="hero"
>

  <div class="container hero-grid">

    <div>

      <span class="hero-tag">
        Smart Vehicle Maintenance
      </span>

      <h1>
        Keep Your Vehicle Safe,
        Serviced and Road-Ready
      </h1>

      <p>
        AutoTrack helps vehicle owners manage
        service history, maintenance reminders,
        repair costs, nearby garages and
        appointments in one place.
      </p>

      <div class="hero-actions">

        <a
          href="register.php"
          class="btn btn-primary"
        >
          Get Started
        </a>

        <a
          href="#garages"
          class="btn btn-secondary"
        >
          Find a Garage
        </a>

      </div>

    </div>


    <div class="hero-card">

      <h3>
        Smart Vehicle Maintenance
      </h3>

      <p>
        Manage vehicle service schedules,
        expenses, garages and reminders
        from one simple platform.
      </p>

    </div>

  </div>

</section>

<section
    id="services"
    class="section"
>

    <div class="container">

        <div class="section-title">

            <h2>
                Our Services
            </h2>

            <p>
                Everything you need to manage
                vehicle maintenance.
            </p>

        </div>


        <div class="service-grid">

            <div class="service-card">

                <div class="service-icon">
                    🚗
                </div>

                <h3>
                    Vehicle Management
                </h3>

                <p>
                    Store vehicle details,
                    mileage and important
                    vehicle information.
                </p>

            </div>


            <div class="service-card">

                <div class="service-icon">
                    🔧
                </div>

                <h3>
                    Service History
                </h3>

                <p>
                    Keep records of completed
                    services, repairs and
                    replaced parts.
                </p>

            </div>


            <div class="service-card">

                <div class="service-icon">
                    🗓️
                </div>

                <h3>
                    Maintenance Tracking
                </h3>

                <p>
                    Track upcoming oil changes,
                    brake inspections and
                    other maintenance.
                </p>

            </div>


            <div class="service-card">

                <div class="service-icon">
                    🔔
                </div>

                <h3>
                    SMS Alerts
                </h3>

                <p>
                    Receive maintenance and
                    appointment reminders on
                    your registered mobile number.
                </p>

            </div>


            <div class="service-card">

                <div class="service-icon">
                    📍
                </div>

                <h3>
                    Garage Locator
                </h3>

                <p>
                    Find registered automobile
                    garages in the Northern
                    Province.
                </p>

            </div>


            <div class="service-card">

                <div class="service-icon">
                    💰
                </div>

                <h3>
                    Repair Cost Estimator
                </h3>

                <p>
                    Get approximate service
                    and repair costs using
                    previous garage records.
                </p>

            </div>


            <div class="service-card">

                <div class="service-icon">
                    📅
                </div>

                <h3>
                    Appointment Booking
                </h3>

                <p>
                    Schedule service appointments
                    with registered garages.
                </p>

            </div>


            <div class="service-card">

                <div class="service-icon">
                    🤖
                </div>

                <h3>
                    AI Vehicle Assistant
                </h3>

                <p>
                    Ask vehicle-maintenance
                    questions and get help
                    navigating AutoTrack.
                </p>

            </div>

        </div>

    </div>

</section>

<div class="district-list">

    <span>📍 Jaffna</span>

    <span>📍 Kilinochchi</span>

    <span>📍 Mullaitivu</span>

    <span>📍 Mannar</span>

    <span>📍 Vavuniya</span>

</div>

<section
    id="garages"
    class="section garage-section"
>

    <div class="container">

        <div class="section-title">

            <h2>
                Registered Garages
            </h2>

            <p>
                Browse approved automobile garages
                registered with AutoTrack.
            </p>

        </div>


        <div class="garage-grid">

        <?php if (
            mysqli_num_rows($garageResult) > 0
        ): ?>


            <?php while (
                $garage =
                mysqli_fetch_assoc($garageResult)
            ): ?>


                <div class="garage-card">

                    <?php if (
                        !empty($garage["image"])
                    ): ?>

                        <img
                            src="uploads/garages/<?php
                            echo htmlspecialchars(
                                $garage["image"]
                            );
                            ?>"
                            alt="<?php
                            echo htmlspecialchars(
                                $garage["garage_name"]
                            );
                            ?>"
                            class="garage-image"
                        >

                    <?php else: ?>

                        <div class="garage-icon">
                            📍
                        </div>

                    <?php endif; ?>


                    <h3>
                        <?php
                        echo htmlspecialchars(
                            $garage["garage_name"]
                        );
                        ?>
                    </h3>


                    <p>
                        <?php
                        echo htmlspecialchars(
                            $garage["address"]
                        );
                        ?>
                    </p>


                    <?php if (
                        !empty($garage["city"])
                    ): ?>

                        <p>
                            <strong>City:</strong>

                            <?php
                            echo htmlspecialchars(
                                $garage["city"]
                            );
                            ?>
                        </p>

                    <?php endif; ?>


                    <?php if (
                        !empty($garage["district"])
                    ): ?>

                        <p>
                            <strong>District:</strong>

                            <?php
                            echo htmlspecialchars(
                                $garage["district"]
                            );
                            ?>
                        </p>

                    <?php endif; ?>


                    <?php if (
                        !empty($garage["mobile_number"])
                    ): ?>

                        <p>
                            📞

                            <?php
                            echo htmlspecialchars(
                                $garage["mobile_number"]
                            );
                            ?>
                        </p>

                    <?php endif; ?>


                    <?php if (
                        !empty($garage["opening_time"])
                        &&
                        !empty($garage["closing_time"])
                    ): ?>

                        <p>
                            🕒

                            <?php
                            echo date(
                                "h:i A",
                                strtotime(
                                    $garage["opening_time"]
                                )
                            );
                            ?>

                            -

                            <?php
                            echo date(
                                "h:i A",
                                strtotime(
                                    $garage["closing_time"]
                                )
                            );
                            ?>

                        </p>

                    <?php endif; ?>


                    <?php if (
                        !empty($garage["description"])
                    ): ?>

                        <p class="garage-description">

                            <?php
                            echo htmlspecialchars(
                                $garage["description"]
                            );
                            ?>

                        </p>

                    <?php endif; ?>


                    <div class="garage-actions">

                        <button type="button" class="btn btn-secondary view-garage-btn" 
                          data-garage-id="<?php echo (int) $garage["garage_id"];?>">
                           View Details
                        </button>

                        <a
                            href="login.php?garage_id=<?php
                            echo (int)
                            $garage["garage_id"];
                            ?>"
                            class="btn btn-primary"
                        >
                            Book Service
                        </a>

                    </div>

                </div>


            <?php endwhile; ?>


        <?php else: ?>


            <div class="empty-state">

                <h3>
                    No registered garages available
                </h3>

                <p>
                    Approved garages will appear here
                    after registration and admin approval.
                </p>

            </div>


        <?php endif; ?>

        </div>

    </div>

</section>
    <!-- <a href="login.html">Open AutoTrack</a> -->

    <!-- popup body -->
     <div
    id="garageModal"
    class="garage-modal"
    aria-hidden="true"
>

    <div class="garage-modal-overlay"></div>

    <div
        class="garage-modal-content"
        role="dialog"
        aria-modal="true"
        aria-labelledby="garageModalTitle"
    >

        <div class="garage-modal-header">

            <div>

                <span class="garage-modal-label">
                    Registered Garage
                </span>

                <h2 id="garageModalTitle">
                    Garage Details
                </h2>

            </div>

            <button
                type="button"
                class="garage-modal-close"
                data-modal-close="garageModal"
            >
                ×
            </button>

        </div>


       <div id="garageModalBody">
        </div>

    </div>

</div>

  </body>
</html>
