<?php

require_once '../auth.php';

$currentPage = "services";

$message = "";


// ==========================================================
// HANDLE FORM SUBMISSION
// ==========================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $serviceName =
        trim(
            $_POST["service_name"]
            ?? ""
        );

    $description =
        trim(
            $_POST["description"]
            ?? ""
        );

    $estimatedPrice =
        trim(
            $_POST["estimated_price"]
            ?? ""
        );

    $duration =
        isset($_POST["estimated_duration_minutes"])
            ? (int) $_POST["estimated_duration_minutes"]
            : 0;

    $activeStatus =
        isset($_POST["active_status"])
            ? 1
            : 0;


    // ======================================================
    // VALIDATION
    // ======================================================

    if ($serviceName === "") {

        $message =
            "Please enter the service name.";

    } else {

        $priceValue =
            $estimatedPrice === ""
                ? null
                : (float) $estimatedPrice;


        // ==================================================
        // INSERT SERVICE
        // ==================================================

        $sql = "
            INSERT INTO garage_services
            (
                garage_id,
                service_name,
                description,
                estimated_price,
                estimated_duration_minutes,
                active_status
            )

            VALUES
            (
                ?, ?, ?, ?, ?, ?
            )
        ";


        $stmt =
            mysqli_prepare(
                $conn,
                $sql
            );


        if (!$stmt) {

            $message =
                "Unable to prepare service: "
                . mysqli_error($conn);

        } else {


            mysqli_stmt_bind_param(
                $stmt,
                "issdii",
                $garageId,
                $serviceName,
                $description,
                $priceValue,
                $duration,
                $activeStatus
            );


            if (
                mysqli_stmt_execute(
                    $stmt
                )
            ) {

                mysqli_stmt_close(
                    $stmt
                );


                header(
                    "Location: index.php?added=1"
                );

                exit();

            } else {

                $message =
                    "Unable to add service: "
                    . mysqli_stmt_error(
                        $stmt
                    );


                mysqli_stmt_close(
                    $stmt
                );
            }
        }
    }
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
        Add Garage Service - AutoTrack
    </title>


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


    <link
        rel="stylesheet"
        href="/automobile_tracker/css/garage-admin.css"
    >


    <style>

        .service-form-page {
            max-width: 950px;
        }

        .service-form-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 25px;
        }

        .service-form-header h1 {
            margin: 0 0 6px;
            font-size: 30px;
        }

        .service-form-header p {
            margin: 0;
            color: #64748b;
        }

        .service-form {
            display: grid;
            grid-template-columns:
                repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .service-form .full {
            grid-column: 1 / -1;
        }

        .service-status {
            display: flex;
            align-items: center;
            gap: 10px;
            min-height: 44px;
        }

        .service-status input {
            width: auto;
            min-height: auto;
        }

        .service-actions {
            grid-column: 1 / -1;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 4px;
        }

        @media (max-width: 700px) {

            .service-form {
                grid-template-columns: 1fr;
            }

            .service-form .full,
            .service-actions {
                grid-column: auto;
            }

            .service-form-header {
                align-items: flex-start;
                flex-direction: column;
            }

        }

    </style>

</head>


<body>


<?php

require_once '../../includes/garage-sidebar.php';

?>


<main class="garage-main">


    <div class="service-form-page">


        <header class="service-form-header">

            <div>

                <h1>
                    Add Garage Service
                </h1>

                <p>
                    Add a service that customers can book from your garage.
                </p>

            </div>


            <a
                href="index.php"
                class="btn btn-secondary"
            >
                ← Back to Services
            </a>

        </header>



        <?php if ($message !== ""): ?>

            <div class="alert alert-danger">

                <?= htmlspecialchars(
                    $message,
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>

            </div>

        <?php endif; ?>



        <form
            method="POST"
            class="card service-form"
        >


            <div class="field">

                <label for="serviceName">
                    Service Name
                </label>

                <input
                    id="serviceName"
                    type="text"
                    name="service_name"
                    placeholder="Example: Full Service"
                    value="<?= htmlspecialchars(
                        $_POST["service_name"] ?? "",
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>"
                    required
                >

            </div>



            <div class="field">

                <label for="estimatedPrice">
                    Estimated Price (Rs.)
                </label>

                <input
                    id="estimatedPrice"
                    type="number"
                    name="estimated_price"
                    min="0"
                    step="0.01"
                    placeholder="Example: 25000"
                    value="<?= htmlspecialchars(
                        $_POST["estimated_price"] ?? "",
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>"
                >

            </div>



            <div class="field">

                <label for="duration">
                    Estimated Duration (Minutes)
                </label>

                <input
                    id="duration"
                    type="number"
                    name="estimated_duration_minutes"
                    min="0"
                    placeholder="Example: 120"
                    value="<?= htmlspecialchars(
                        $_POST["estimated_duration_minutes"] ?? "",
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>"
                >

            </div>



            <div class="field">

                <label>
                    Status
                </label>

                <label class="service-status">

                    <input
                        type="checkbox"
                        name="active_status"
                        value="1"
                        <?= !isset($_POST["active_status"])
                            && $_SERVER["REQUEST_METHOD"] !== "POST"
                            ? "checked"
                            : (
                                isset($_POST["active_status"])
                                    ? "checked"
                                    : ""
                            ) ?>
                    >

                    Active

                </label>

            </div>



            <div class="field full">

                <label for="description">
                    Description
                </label>

                <textarea
                    id="description"
                    name="description"
                    placeholder="Describe what is included in this service..."
                ><?= htmlspecialchars(
                    $_POST["description"] ?? "",
                    ENT_QUOTES,
                    "UTF-8"
                ) ?></textarea>

            </div>



            <div class="service-actions">

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Save Service
                </button>


                <a
                    href="index.php"
                    class="btn btn-secondary"
                >
                    Cancel
                </a>

            </div>


        </form>


    </div>


</main>


</body>

</html>