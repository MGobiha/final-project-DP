<?php

session_start();

require_once "config/database.php";


// ==========================================================
// LOGIN CHECK
// ==========================================================

if (!isset($_SESSION["user_id"])) {

    header("Location: login.php");
    exit();
}


$userId =
    (int) $_SESSION["user_id"];


$message = "";
$successMessage = "";

/*
|--------------------------------------------------------------------------
| GARAGE DAILY BOOKING CAPACITY
|--------------------------------------------------------------------------
| Each garage can accept a maximum of 4 active vehicle bookings per day.
*/
$dailyBookingCapacity = 4;


// ==========================================================
// URL VALUES
// ==========================================================

$selectedVehicleId =
    isset($_GET["vehicle_id"])
        ? (int) $_GET["vehicle_id"]
        : 0;


$selectedGarageId =
    isset($_GET["garage_id"])
        ? (int) $_GET["garage_id"]
        : 0;


$scheduleId =
    isset($_GET["schedule_id"])
        ? (int) $_GET["schedule_id"]
        : 0;


$preselectedService = "";


// ==========================================================
// LOAD SERVICE FROM MAINTENANCE SCHEDULE
// ==========================================================

if ($scheduleId > 0) {

    $scheduleSql = "
        SELECT
            ms.maintenance_type,
            ms.vehicle_id

        FROM maintenance_schedule ms

        INNER JOIN vehicles v
            ON v.vehicle_id = ms.vehicle_id

        WHERE ms.schedule_id = ?
        AND v.user_id = ?

        LIMIT 1
    ";


    $scheduleStmt =
        mysqli_prepare(
            $conn,
            $scheduleSql
        );


    if ($scheduleStmt) {

        mysqli_stmt_bind_param(
            $scheduleStmt,
            "ii",
            $scheduleId,
            $userId
        );


        mysqli_stmt_execute(
            $scheduleStmt
        );


        $scheduleResult =
            mysqli_stmt_get_result(
                $scheduleStmt
            );


        $schedule =
            mysqli_fetch_assoc(
                $scheduleResult
            );


        mysqli_stmt_close(
            $scheduleStmt
        );


        if ($schedule) {

            $preselectedService =
                $schedule["maintenance_type"];


            if ($selectedVehicleId <= 0) {

                $selectedVehicleId =
                    (int) $schedule["vehicle_id"];
            }
        }
    }
}


// ==========================================================
// LOAD VEHICLES
// ==========================================================

$vehicles = [];


$vehicleSql = "
    SELECT
        vehicle_id,
        registration_number,
        make,
        model,
        manufacture_year

    FROM vehicles

    WHERE user_id = ?

    ORDER BY make, model
";


$vehicleStmt =
    mysqli_prepare(
        $conn,
        $vehicleSql
    );


if ($vehicleStmt) {

    mysqli_stmt_bind_param(
        $vehicleStmt,
        "i",
        $userId
    );


    mysqli_stmt_execute(
        $vehicleStmt
    );


    $vehicleResult =
        mysqli_stmt_get_result(
            $vehicleStmt
        );


    while (
        $vehicle =
        mysqli_fetch_assoc(
            $vehicleResult
        )
    ) {

        $vehicles[] = $vehicle;
    }


    mysqli_stmt_close(
        $vehicleStmt
    );
}


// ==========================================================
// LOAD APPROVED GARAGES
// ==========================================================

$garages = [];


$garageSql = "
    SELECT
        garage_id,
        garage_name,
        address,
        city,
        district

    FROM garages

    WHERE approval_status = 'approved'
    AND active_status = 1

    ORDER BY garage_name
";


$garageResult =
    mysqli_query(
        $conn,
        $garageSql
    );


if ($garageResult) {

    while (
        $garage =
        mysqli_fetch_assoc(
            $garageResult
        )
    ) {

        $garages[] = $garage;
    }
}


// ==========================================================
// SUBMIT APPOINTMENT
// ==========================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $vehicleId =
        (int) (
            $_POST["vehicle_id"]
            ?? 0
        );


    $garageId =
        (int) (
            $_POST["garage_id"]
            ?? 0
        );


    $garageServiceId =
        (int) (
            $_POST["garage_service_id"]
            ?? 0
        );


    $appointmentDate =
        trim(
            $_POST["appointment_date"]
            ?? ""
        );


    $appointmentTime =
        trim(
            $_POST["appointment_time"]
            ?? ""
        );


    $customerNote =
        trim(
            $_POST["customer_note"]
            ?? ""
        );


    // ======================================================
    // BASIC VALIDATION
    // ======================================================

    if (
        $vehicleId <= 0
        ||
        $garageId <= 0
        ||
        $garageServiceId <= 0
        ||
        $appointmentDate === ""
        ||
        $appointmentTime === ""
    ) {

        $message =
            "Please complete all required fields.";

    } else {


        // ==================================================
        // CHECK DATE IS NOT IN PAST
        // ==================================================

        if (
            strtotime($appointmentDate)
            <
            strtotime(date("Y-m-d"))
        ) {

            $message =
                "Please select today or a future appointment date.";

        } else {


            // ==============================================
            // VERIFY VEHICLE BELONGS TO USER
            // ==============================================

            $vehicleCheckSql = "
                SELECT vehicle_id

                FROM vehicles

                WHERE vehicle_id = ?
                AND user_id = ?

                LIMIT 1
            ";


            $vehicleCheckStmt =
                mysqli_prepare(
                    $conn,
                    $vehicleCheckSql
                );


            mysqli_stmt_bind_param(
                $vehicleCheckStmt,
                "ii",
                $vehicleId,
                $userId
            );


            mysqli_stmt_execute(
                $vehicleCheckStmt
            );


            $vehicleCheckResult =
                mysqli_stmt_get_result(
                    $vehicleCheckStmt
                );


            $validVehicle =
                mysqli_fetch_assoc(
                    $vehicleCheckResult
                );


            mysqli_stmt_close(
                $vehicleCheckStmt
            );


            if (!$validVehicle) {

                $message =
                    "Invalid vehicle selected.";

            } else {


                // ==========================================
                // VERIFY GARAGE
                // ==========================================

                $garageCheckSql = "
                    SELECT garage_id

                    FROM garages

                    WHERE garage_id = ?
                    AND approval_status = 'approved'
                    AND active_status = 1

                    LIMIT 1
                ";


                $garageCheckStmt =
                    mysqli_prepare(
                        $conn,
                        $garageCheckSql
                    );


                mysqli_stmt_bind_param(
                    $garageCheckStmt,
                    "i",
                    $garageId
                );


                mysqli_stmt_execute(
                    $garageCheckStmt
                );


                $garageCheckResult =
                    mysqli_stmt_get_result(
                        $garageCheckStmt
                    );


                $validGarage =
                    mysqli_fetch_assoc(
                        $garageCheckResult
                    );


                mysqli_stmt_close(
                    $garageCheckStmt
                );


                if (!$validGarage) {

                    $message =
                        "Invalid garage selected.";

                } else {


                    // ======================================
                    // VERIFY SERVICE BELONGS TO GARAGE
                    // ======================================

                    $serviceSql = "
                        SELECT
                            garage_service_id,
                            service_name

                        FROM garage_services

                        WHERE garage_service_id = ?
                        AND garage_id = ?
                        AND active_status = 1

                        LIMIT 1
                    ";


                    $serviceStmt =
                        mysqli_prepare(
                            $conn,
                            $serviceSql
                        );


                    mysqli_stmt_bind_param(
                        $serviceStmt,
                        "ii",
                        $garageServiceId,
                        $garageId
                    );


                    mysqli_stmt_execute(
                        $serviceStmt
                    );


                    $serviceResult =
                        mysqli_stmt_get_result(
                            $serviceStmt
                        );


                    $service =
                        mysqli_fetch_assoc(
                            $serviceResult
                        );


                    mysqli_stmt_close(
                        $serviceStmt
                    );


                    if (!$service) {

                        $message =
                            "The selected service is not available at this garage.";

                    } else {


                        $serviceType =
                            $service["service_name"];


                        // ==================================
                        // CHECK GARAGE DAILY CAPACITY
                        // ==================================
                        //
                        // Only active bookings count toward the daily limit.
                        // Cancelled / rejected appointments do not use a slot.
                        //

                        $capacitySql = "
                            SELECT COUNT(*) AS booking_count

                            FROM appointments

                            WHERE garage_id = ?
                            AND appointment_date = ?
                            AND appointment_status IN (
                                'pending',
                                'confirmed',
                                'in_progress'
                            )
                        ";


                        $capacityStmt =
                            mysqli_prepare(
                                $conn,
                                $capacitySql
                            );


                        if (!$capacityStmt) {

                            $message =
                                "Unable to check garage availability: "
                                . mysqli_error($conn);

                        } else {

                            mysqli_stmt_bind_param(
                                $capacityStmt,
                                "is",
                                $garageId,
                                $appointmentDate
                            );


                            mysqli_stmt_execute(
                                $capacityStmt
                            );


                            $capacityResult =
                                mysqli_stmt_get_result(
                                    $capacityStmt
                                );


                            $capacityRow =
                                mysqli_fetch_assoc(
                                    $capacityResult
                                );


                            $bookingCount =
                                (int) (
                                    $capacityRow["booking_count"]
                                    ?? 0
                                );


                            mysqli_stmt_close(
                                $capacityStmt
                            );


                            if (
                                $bookingCount
                                >=
                                $dailyBookingCapacity
                            ) {

                                $message =
                                    "This garage is fully booked for "
                                    . $appointmentDate
                                    . ". Please choose another date.";

                            } else {


                        // ==================================
                        // INSERT APPOINTMENT
                        // ==================================

                        $appointmentStatus =
                            "pending";


                        $insertSql = "
                            INSERT INTO appointments
                            (
                                user_id,
                                vehicle_id,
                                garage_id,
                                service_type,
                                appointment_date,
                                appointment_time,
                                customer_note,
                                appointment_status,
                                sms_sent
                            )

                            VALUES
                            (
                                ?, ?, ?, ?, ?, ?, ?, ?, 0
                            )
                        ";


                        $insertStmt =
                            mysqli_prepare(
                                $conn,
                                $insertSql
                            );


                        if (!$insertStmt) {

                            $message =
                                "Unable to prepare appointment request: "
                                . mysqli_error($conn);

                        } else {


                            mysqli_stmt_bind_param(
                                $insertStmt,
                                "iiisssss",
                                $userId,
                                $vehicleId,
                                $garageId,
                                $serviceType,
                                $appointmentDate,
                                $appointmentTime,
                                $customerNote,
                                $appointmentStatus
                            );


                            if (
                                mysqli_stmt_execute(
                                    $insertStmt
                                )
                            ) {

                                mysqli_stmt_close(
                                    $insertStmt
                                );


                                header(
                                    "Location: request-service.php?success=1"
                                );

                                exit();

                            } else {

                                $message =
                                    "Unable to submit service request: "
                                    . mysqli_stmt_error(
                                        $insertStmt
                                    );


                                mysqli_stmt_close(
                                    $insertStmt
                                );
                            }
                        }

                            } // end daily capacity available
                        } // end capacity statement

                    }
                }
            }
        }
    }
}


// ==========================================================
// SUCCESS
// ==========================================================

if (
    isset($_GET["success"])
    &&
    $_GET["success"] == 1
) {

    $successMessage =
        "Your service request has been sent to the garage. The garage can now approve, reschedule or reject it.";
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
        Request Service - AutoTrack
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
        href="/automobile_tracker/css/style.css"
    >


    <link
        rel="stylesheet"
        href="/automobile_tracker/css/dashboard-layout.css"
    >


    <link
        rel="stylesheet"
        href="/automobile_tracker/css/responsive.css"
    >


    <style>

        /* .request-container {
            max-width: 950px;
        } */


        .request-header {
            margin-bottom: 24px;
        }


        .request-header h1 {
            margin: 0 0 7px;
        }


        .request-header p {
            margin: 0;
            color: #667085;
        }


        .request-form {
            display: grid;

            grid-template-columns:
                repeat(
                    2,
                    minmax(0, 1fr)
                );

            gap: 18px;
        }


        .request-form .full {
            grid-column: 1 / -1;
        }


        .request-form label {
            display: block;
            margin-bottom: 7px;

            font-size: 14px;
            font-weight: 600;
        }


        .request-form input,
        .request-form select,
        .request-form textarea {
            width: 100%;
            min-height: 45px;

            padding: 10px 12px;

            border:
                1px solid
                #cbd5e1;

            border-radius: 9px;

            font: inherit;

            background: #ffffff;
        }


        .request-form select:disabled {
            background: #f1f5f9;
            color: #64748b;
            cursor: not-allowed;
        }


        .request-form textarea {
            min-height: 120px;
            resize: vertical;
        }


        .service-info {
            display: none;

            margin-top: 9px;
            padding: 12px;

            border-radius: 9px;

            background: #f8fafc;

            color: #475569;

            font-size: 13px;

            line-height: 1.5;
        }


        .request-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }


        .request-success {
            padding: 15px 18px;
            margin-bottom: 18px;

            border-radius: 10px;

            background: #dcfce7;
            color: #166534;
        }


        .request-error {
            padding: 15px 18px;
            margin-bottom: 18px;

            border-radius: 10px;

            background: #fee2e2;
            color: #991b1b;
        }


        .availability-note {
            display: none;
            margin-top: 8px;
            padding: 10px 12px;
            border-radius: 9px;
            background: #eff6ff;
            color: #1e40af;
            font-size: 13px;
            line-height: 1.5;
        }


        .availability-note.error {
            display: block;
            background: #fee2e2;
            color: #991b1b;
        }


        .availability-note.success {
            display: block;
            background: #dcfce7;
            color: #166534;
        }


        .fully-booked-list {
            display: none;
            margin-top: 8px;
            color: #b45309;
            font-size: 12px;
            line-height: 1.5;
        }


        @media (max-width: 700px) {

            .request-form {
                grid-template-columns: 1fr;
            }


            .request-form .full {
                grid-column: auto;
            }

        }

    </style>

</head>


<body data-page="request-service">


<div class="app-shell">


<?php

require_once "includes/sidebar.php";

?>


<main class="main">


<div class="request-container">


<header class="topbar">


            <div class="title">

                <h1>
                    Request Service
                </h1>

                

            </div>


            <div class="user-chip">
           <div class="avatar">
                <?= htmlspecialchars(
                    strtoupper(
                        substr($user["first_name"] ?? "U", 0, 1)
                    )
                ) ?>
            </div>
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



<?php if ($successMessage !== ""): ?>

    <div class="request-success">

        <?= htmlspecialchars(
            $successMessage
        ) ?>

    </div>

<?php endif; ?>



<?php if ($message !== ""): ?>

    <div class="request-error">

        <?= htmlspecialchars(
            $message
        ) ?>

    </div>

<?php endif; ?>



<form
    method="POST"
    class="card request-form"
>


    <!-- VEHICLE -->

    <div>

        <label for="vehicleId">
            Vehicle
        </label>


        <select
            id="vehicleId"
            name="vehicle_id"
            required
        >

            <option value="">
                Select vehicle
            </option>


            <?php foreach (
                $vehicles
                as $vehicle
            ): ?>


                <option
                    value="<?= (int)$vehicle["vehicle_id"] ?>"

                    <?= (
                        $selectedVehicleId
                        ===
                        (int)$vehicle["vehicle_id"]
                    )
                        ? "selected"
                        : "" ?>
                >

                    <?= htmlspecialchars(
                        trim(
                            $vehicle["make"]
                            . " "
                            . $vehicle["model"]
                            . " "
                            . (
                                $vehicle["manufacture_year"]
                                ?? ""
                            )
                        )
                    ) ?>

                    -

                    <?= htmlspecialchars(
                        $vehicle["registration_number"]
                    ) ?>

                </option>


            <?php endforeach; ?>


        </select>

    </div>



    <!-- GARAGE -->

    <div>

        <label for="garageId">
            Garage
        </label>


        <select
            id="garageId"
            name="garage_id"
            required
        >

            <option value="">
                Select garage
            </option>


            <?php foreach (
                $garages
                as $garage
            ): ?>


                <option
                    value="<?= (int)$garage["garage_id"] ?>"

                    <?= (
                        $selectedGarageId
                        ===
                        (int)$garage["garage_id"]
                    )
                        ? "selected"
                        : "" ?>
                >

                    <?= htmlspecialchars(
                        $garage["garage_name"]
                    ) ?>


                    <?php if (
                        !empty($garage["city"])
                    ): ?>

                        -
                        <?= htmlspecialchars(
                            $garage["city"]
                        ) ?>

                    <?php endif; ?>


                </option>


            <?php endforeach; ?>


        </select>

    </div>



    <!-- SERVICE -->

    <div class="full">

        <label for="serviceType">
            Service Type
        </label>


        <select
            id="serviceType"
            name="garage_service_id"
            required
            disabled
        >

            <option value="">
                First select a garage
            </option>

        </select>


        <div
            id="serviceInfo"
            class="service-info"
        ></div>

    </div>



    <!-- DATE -->

    <div>

        <label for="appointmentDate">
            Preferred Date
        </label>


        <input
            id="appointmentDate"
            type="date"
            name="appointment_date"
            min="<?= date("Y-m-d") ?>"
            required
        >

        <div
            id="availabilityNote"
            class="availability-note"
        ></div>

        <div
            id="fullyBookedList"
            class="fully-booked-list"
        ></div>

    </div>



    <!-- TIME -->

    <div>

        <label for="appointmentTime">
            Preferred Time
        </label>


        <input
            id="appointmentTime"
            type="time"
            name="appointment_time"
            required
        >

    </div>



    <!-- NOTE -->

    <div class="full">

        <label for="customerNote">
            Notes
        </label>


        <textarea
            id="customerNote"
            name="customer_note"
            placeholder="Describe your request or any vehicle issue..."
        ></textarea>

    </div>



    <!-- BUTTONS -->

    <div class="full request-actions">


        <button
            type="submit"
            class="btn btn-primary"
        >
            Send Service Request
        </button>


        <a
            href="garages.php"
            class="btn btn-secondary"
        >
            Cancel
        </a>


    </div>


</form>


</div>


</main>


</div>



<script>

const garageSelect =
    document.getElementById(
        "garageId"
    );


const serviceSelect =
    document.getElementById(
        "serviceType"
    );


const serviceInfo =
    document.getElementById(
        "serviceInfo"
    );


const appointmentDateInput =
    document.getElementById(
        "appointmentDate"
    );


const availabilityNote =
    document.getElementById(
        "availabilityNote"
    );


const fullyBookedList =
    document.getElementById(
        "fullyBookedList"
    );


const dailyBookingCapacity =
    <?= (int)$dailyBookingCapacity ?>;


let fullyBookedDates = [];


const preselectedService =
    <?= json_encode(
        $preselectedService
    ) ?>;


// ==========================================================
// RESET SERVICE
// ==========================================================

function resetServices(
    text = "First select a garage"
) {

    serviceSelect.innerHTML = "";

    const option =
        document.createElement(
            "option"
        );

    option.value = "";

    option.textContent = text;

    serviceSelect.appendChild(
        option
    );

    serviceSelect.disabled = true;

    serviceInfo.style.display =
        "none";

    serviceInfo.textContent =
        "";
}


// ==========================================================
// LOAD GARAGE SERVICES
// ==========================================================

async function loadGarageServices() {

    const garageId =
        garageSelect.value;


    if (!garageId) {

        resetServices();

        return;
    }


    resetServices(
        "Loading services..."
    );


    try {

        const response =
            await fetch(
                "/automobile_tracker/get-garage-services.php?garage_id="
                +
                encodeURIComponent(
                    garageId
                )
            );


        if (!response.ok) {

            throw new Error(
                "HTTP "
                +
                response.status
            );
        }


        const data =
            await response.json();


        serviceSelect.innerHTML =
            "";


        if (
            !data.success
            ||
            !Array.isArray(
                data.services
            )
            ||
            data.services.length === 0
        ) {

            resetServices(
                "No active services available"
            );

            return;
        }


        const defaultOption =
            document.createElement(
                "option"
            );


        defaultOption.value = "";

        defaultOption.textContent =
            "Select service";


        serviceSelect.appendChild(
            defaultOption
        );


        data.services.forEach(
            service => {


                const option =
                    document.createElement(
                        "option"
                    );


                option.value =
                    service.id;


                option.textContent =
                    service.name;


                option.dataset.serviceName =
                    service.name ?? "";


                option.dataset.price =
                    service.price ?? "";


                option.dataset.duration =
                    service.duration ?? "";


                option.dataset.description =
                    service.description ?? "";


                serviceSelect.appendChild(
                    option
                );


                // Maintenance schedule preselection

                if (
                    preselectedService
                    &&
                    service.name
                        .trim()
                        .toLowerCase()
                    ===
                    preselectedService
                        .trim()
                        .toLowerCase()
                ) {

                    option.selected = true;
                }

            }
        );


        serviceSelect.disabled =
            false;


        if (serviceSelect.value) {

            showServiceInformation();
        }

    }
    catch (error) {

        console.error(
            error
        );


        resetServices(
            "Unable to load services"
        );
    }
}


// ==========================================================
// SHOW PRICE / DURATION
// ==========================================================

function showServiceInformation() {

    const selectedOption =
        serviceSelect.options[
            serviceSelect.selectedIndex
        ];


    if (
        !selectedOption
        ||
        !selectedOption.value
    ) {

        serviceInfo.style.display =
            "none";

        serviceInfo.textContent =
            "";

        return;
    }


    const price =
        selectedOption.dataset.price;


    const duration =
        selectedOption.dataset.duration;


    const description =
        selectedOption.dataset.description;


    const information = [];


    if (
        price
        &&
        parseFloat(price) > 0
    ) {

        information.push(
            "Estimated Price: Rs. "
            +
            parseFloat(
                price
            ).toLocaleString(
                undefined,
                {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }
            )
        );
    }


    if (
        duration
        &&
        parseInt(duration) > 0
    ) {

        const totalMinutes =
            parseInt(
                duration
            );


        const hours =
            Math.floor(
                totalMinutes
                /
                60
            );


        const minutes =
            totalMinutes
            %
            60;


        let durationText = "";


        if (hours > 0) {

            durationText +=
                hours
                +
                (
                    hours === 1
                        ? " hr"
                        : " hrs"
                );
        }


        if (minutes > 0) {

            if (
                durationText !== ""
            ) {

                durationText +=
                    " ";
            }


            durationText +=
                minutes
                +
                " mins";
        }


        information.push(
            "Estimated Duration: "
            +
            durationText
        );
    }


    if (description) {

        information.push(
            description
        );
    }


    serviceInfo.textContent =
        information.join(
            " • "
        );


    serviceInfo.style.display =
        information.length > 0
            ? "block"
            : "none";
}


// ==========================================================
// GARAGE DATE AVAILABILITY
// ==========================================================

function resetAvailability() {

    fullyBookedDates = [];

    appointmentDateInput.value = "";

    availabilityNote.className =
        "availability-note";

    availabilityNote.textContent =
        "";

    fullyBookedList.style.display =
        "none";

    fullyBookedList.textContent =
        "";
}


async function loadGarageAvailability() {

    const garageId =
        garageSelect.value;

    resetAvailability();


    if (!garageId) {
        return;
    }


    availabilityNote.className =
        "availability-note";

    availabilityNote.style.display =
        "block";

    availabilityNote.textContent =
        "Checking available booking dates...";


    try {

        const response =
            await fetch(
                "/automobile_tracker/ajax/garage-booked-dates.php?garage_id="
                +
                encodeURIComponent(
                    garageId
                )
            );


        if (!response.ok) {

            throw new Error(
                "HTTP "
                +
                response.status
            );
        }


        const data =
            await response.json();


        if (!data.success) {

            throw new Error(
                data.message
                ||
                "Unable to load garage availability."
            );
        }


        fullyBookedDates =
            Array.isArray(
                data.booked_dates
            )
                ? data.booked_dates
                : [];


        availabilityNote.className =
            "availability-note success";

        availabilityNote.textContent =
            "This garage accepts up to "
            +
            dailyBookingCapacity
            +
            " vehicles per day. Select a date to check availability.";


        if (fullyBookedDates.length > 0) {

            fullyBookedList.style.display =
                "block";

            fullyBookedList.textContent =
                "Fully booked dates: "
                +
                fullyBookedDates.join(
                    ", "
                );
        }

    }
    catch (error) {

        console.error(
            error
        );


        availabilityNote.className =
            "availability-note error";

        availabilityNote.textContent =
            "Unable to load garage booking availability. Please try again.";
    }
}


function validateSelectedDate() {

    const selectedDate =
        appointmentDateInput.value;


    if (!selectedDate) {
        return;
    }


    if (
        fullyBookedDates.includes(
            selectedDate
        )
    ) {

        availabilityNote.className =
            "availability-note error";

        availabilityNote.textContent =
            "This garage already has "
            +
            dailyBookingCapacity
            +
            " vehicle bookings on "
            +
            selectedDate
            +
            ". Please select another date.";

        appointmentDateInput.value =
            "";

        appointmentDateInput.focus();

        return;
    }


    availabilityNote.className =
        "availability-note success";

    availabilityNote.textContent =
        selectedDate
        +
        " is currently available for booking.";
}


// ==========================================================
// EVENTS
// ==========================================================

garageSelect.addEventListener(
    "change",
    function () {

        loadGarageServices();
        loadGarageAvailability();
    }
);


appointmentDateInput.addEventListener(
    "change",
    validateSelectedDate
);


serviceSelect.addEventListener(
    "change",
    showServiceInformation
);


// ==========================================================
// PRESELECT GARAGE FROM URL
// ==========================================================

if (garageSelect.value) {

    loadGarageServices();
    loadGarageAvailability();
}

</script>


</body>

</html>