<?php

require_once '../auth.php';

$currentPage = "services";


// ============================================================
// GET SERVICES BELONGING TO THIS GARAGE
// ============================================================

$sql = "
    SELECT
        garage_service_id,
        service_name,
        description,
        estimated_price,
        estimated_duration_minutes,
        active_status,
        created_at
    FROM garage_services
    WHERE garage_id = ?
    ORDER BY created_at DESC
";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $garageId
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Garage Services | AutoTrack</title>

    <link
        rel="stylesheet"
        href="/automobile_tracker/css/garage-admin.css"
    >

    <style>

        .services-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 25px;
        }

        .services-header h1 {
            margin: 0 0 6px;
        }

        .services-header p {
            margin: 0;
            color: #64748b;
        }

        .service-name {
            font-weight: 700;
            color: #0f172a;
        }

        .service-description {
            margin-top: 5px;
            color: #64748b;
            font-size: 14px;
            line-height: 1.5;
        }

        .price {
            font-weight: 700;
        }

        .service-actions {
            display: flex;
            gap: 7px;
            flex-wrap: wrap;
        }

        .empty-services {
            padding: 55px 20px;
            text-align: center;
        }

        .empty-services-icon {
            font-size: 45px;
            margin-bottom: 15px;
        }

        .empty-services h3 {
            margin: 0 0 8px;
        }

        .empty-services p {
            color: #64748b;
            margin-bottom: 20px;
        }

        @media (max-width: 700px) {

            .services-header {
                align-items: flex-start;
                flex-direction: column;
            }

        }

    </style>

</head>


<body>

<div class="app-shell">


<?php

require_once '../../includes/garage-sidebar.php';

?>


<main class="garage-main">


    <!-- PAGE HEADER -->

    <div class="services-header">

        <div>

            <h1>
                Garage Services
            </h1>

            <p>
                Manage services offered by your garage.
            </p>

        </div>


        <a
            href="add.php"
            class="btn btn-primary"
        >
            + Add Service
        </a>

    </div>



    <!-- SUCCESS MESSAGES -->

    <?php if (isset($_GET["added"])): ?>

        <div class="alert alert-success">

            Service added successfully.

        </div>

    <?php endif; ?>


    <?php if (isset($_GET["updated"])): ?>

        <div class="alert alert-success">

            Service updated successfully.

        </div>

    <?php endif; ?>


    <!-- SERVICES -->

    <div class="card">

        <div class="section-head">

            <div>

                <h2>
                    Your Services
                </h2>

                <p class="muted">

                    These services will be available to
                    customers when booking with your garage.

                </p>

            </div>

        </div>


        <?php if (mysqli_num_rows($result) > 0): ?>


            <div class="table-wrap">

                <table class="table">

                    <thead>

                    <tr>

                        <th>Service</th>

                        <th>Price</th>

                        <th>Duration</th>

                        <th>Status</th>

                        <th>Actions</th>

                    </tr>

                    </thead>


                    <tbody>


                    <?php while ($service = mysqli_fetch_assoc($result)): ?>


                        <tr>


                            <!-- SERVICE -->

                            <td>

                                <div class="service-name">

                                    <?= htmlspecialchars(
                                        $service["service_name"]
                                    ) ?>

                                </div>


                                <?php if (!empty($service["description"])): ?>

                                    <div class="service-description">

                                        <?= htmlspecialchars(
                                            $service["description"]
                                        ) ?>

                                    </div>

                                <?php endif; ?>

                            </td>



                            <!-- PRICE -->

                            <td>

                                <?php if ($service["estimated_price"] !== null): ?>

                                    <span class="price">

                                        Rs.
                                        <?= number_format(
                                            (float)$service["estimated_price"],
                                            2
                                        ) ?>

                                    </span>

                                <?php else: ?>

                                    <span class="muted">
                                        Not specified
                                    </span>

                                <?php endif; ?>

                            </td>



                            <!-- DURATION -->

                            <td>

                                <?php

                                $duration =
                                    (int)$service[
                                        "estimated_duration_minutes"
                                    ];

                                if ($duration > 0) {

                                    if ($duration >= 60) {

                                        $hours =
                                            floor($duration / 60);

                                        $minutes =
                                            $duration % 60;

                                        echo $hours;

                                        echo $hours == 1
                                            ? " hr"
                                            : " hrs";

                                        if ($minutes > 0) {

                                            echo " "
                                                . $minutes
                                                . " mins";
                                        }

                                    } else {

                                        echo $duration
                                            . " mins";
                                    }

                                } else {

                                    echo '<span class="muted">—</span>';
                                }

                                ?>

                            </td>



                            <!-- STATUS -->

                            <td>

                                <?php if ((int)$service["active_status"] === 1): ?>

                                    <span class="badge badge-success">

                                        Active

                                    </span>

                                <?php else: ?>

                                    <span class="badge badge-danger">

                                        Inactive

                                    </span>

                                <?php endif; ?>

                            </td>



                            <!-- ACTIONS -->

                            <td>

                                <div class="service-actions">

                                    <a
                                        href="edit.php?id=<?= (int)$service["garage_service_id"] ?>"
                                        class="btn btn-secondary"
                                    >
                                        Edit
                                    </a>

                                </div>

                            </td>


                        </tr>


                    <?php endwhile; ?>


                    </tbody>

                </table>

            </div>


        <?php else: ?>


            <div class="empty-services">

                <div class="empty-services-icon">
                    🔧
                </div>

                <h3>
                    No Services Added Yet
                </h3>

                <p>

                    Add the services your garage provides
                    so customers can choose them when
                    booking an appointment.

                </p>


                <a
                    href="add.php"
                    class="btn btn-primary"
                >
                    + Add Your First Service
                </a>

            </div>


        <?php endif; ?>


    </div>


</main>

</div>

</body>

</html>