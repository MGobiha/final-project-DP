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

    header("Location: login.php");
    exit();
}


// =====================================================
// VEHICLE OWNER ONLY
// =====================================================

if ($_SESSION["role"] !== "vehicle_owner") {

    header("Location: login.php");
    exit();
}


$userId =
    (int) $_SESSION["user_id"];


// =====================================================
// LOAD LOGGED-IN USER
// =====================================================

$userSql = "
    SELECT
        first_name,
        last_name,
        email,
        mobile_number
    FROM users
    WHERE user_id = ?
    LIMIT 1
";

$userStmt =
    mysqli_prepare(
        $conn,
        $userSql
    );

if (!$userStmt) {

    die(
        "User query error: "
        . mysqli_error($conn)
    );
}


mysqli_stmt_bind_param(
    $userStmt,
    "i",
    $userId
);


mysqli_stmt_execute(
    $userStmt
);


$userResult =
    mysqli_stmt_get_result(
        $userStmt
    );


$user =
    mysqli_fetch_assoc(
        $userResult
    );


mysqli_stmt_close(
    $userStmt
);


if (!$user) {

    session_destroy();

    header("Location: login.php");
    exit();
}


$firstName =
    trim(
        $user["first_name"]
        ?? ""
    );


$lastName =
    trim(
        $user["last_name"]
        ?? ""
    );


$fullName =
    trim(
        $firstName
        . " "
        . $lastName
    );


$avatarLetter =
    $firstName !== ""
        ? strtoupper(
            substr(
                $firstName,
                0,
                1
            )
        )
        : "U";


// =====================================================
// LOAD OWNER'S FIRST VEHICLE
// =====================================================

$vehicleSql = "
    SELECT
        vehicle_id,
        registration_number,
        make,
        model,
        manufacture_year,
        fuel_type,
        current_mileage,
        average_km_per_month,
        last_service_type,
        last_service_date,
        last_service_mileage

    FROM vehicles

    WHERE user_id = ?

    ORDER BY vehicle_id ASC

    LIMIT 1
";


$vehicleStmt =
    mysqli_prepare(
        $conn,
        $vehicleSql
    );


$vehicle = null;


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

    $vehicle =
        mysqli_fetch_assoc(
            $vehicleResult
        );

    mysqli_stmt_close(
        $vehicleStmt
    );
}


// =====================================================
// HELPER: JSON RESPONSE
// =====================================================

function chatbotJson(
    array $data
): void {

    header(
        "Content-Type: application/json; charset=UTF-8"
    );

    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE
        |
        JSON_UNESCAPED_SLASHES
    );

    exit();
}


// =====================================================
// HELPER: NORMALIZE USER MESSAGE
// =====================================================

function normalizeMessage(
    string $message
): string {

    $message =
        strtolower(
            trim($message)
        );

    $message =
        preg_replace(
            '/[^a-z0-9\s]/',
            ' ',
            $message
        );

    $message =
        preg_replace(
            '/\s+/',
            ' ',
            $message
        );

    return trim(
        $message
    );
}


// =====================================================
// HELPER: CHECK KEYWORDS
// =====================================================

function containsAny(
    string $message,
    array $keywords
): bool {

    foreach (
        $keywords
        as $keyword
    ) {

        if (
            strpos(
                $message,
                strtolower($keyword)
            )
            !== false
        ) {

            return true;
        }
    }

    return false;
}


// =====================================================
// VEHICLE PROBLEM CATALOGUE
// =====================================================
//
// Based on the types of records in the uploaded
// Sri Lanka Vehicle Problems synthetic dataset.
//
// We use user-friendly keywords so the customer does
// not have to type the exact dataset wording.
// =====================================================

$problemCatalog = [

    "Engine overheating" => [

        "keywords" => [
            "overheating",
            "engine hot",
            "engine getting hot",
            "car getting hot",
            "too hot",
            "temperature high",
            "temperature rising",
            "heating up",
            "temperature warning"
        ],

        "parts_involved" =>
            "Radiator, coolant system, thermostat",

        "parts_to_check" =>
            "Coolant level, radiator fan, radiator hoses and thermostat",

        "recommendation" =>
            "Avoid continuing to drive if the temperature is unusually high. Have the cooling system inspected by a qualified garage."
    ],


    "Battery not charging" => [

        "keywords" => [
            "battery not charging",
            "battery dead",
            "battery dying",
            "battery keeps dying",
            "charging problem",
            "alternator problem",
            "battery warning",
            "battery light"
        ],

        "parts_involved" =>
            "Battery, alternator",

        "parts_to_check" =>
            "Battery voltage, battery terminals and alternator output",

        "recommendation" =>
            "Have the battery and charging system tested."
    ],


    "Brake noise" => [

        "keywords" => [
            "brake noise",
            "brakes noisy",
            "brakes squeak",
            "brake squeak",
            "brakes squeaking",
            "brake squeaking",
            "brake grinding",
            "grinding brakes"
        ],

        "parts_involved" =>
            "Brake pads, brake rotors",

        "parts_to_check" =>
            "Brake-pad thickness and rotor wear",

        "recommendation" =>
            "Brake problems can affect vehicle safety. Have the braking system inspected promptly."
    ],


    "Engine misfire" => [

        "keywords" => [
            "engine misfire",
            "misfiring",
            "engine shaking",
            "engine jerking",
            "rough idle",
            "engine running rough"
        ],

        "parts_involved" =>
            "Spark plugs, ignition coils",

        "parts_to_check" =>
            "Spark plugs and ignition coils",

        "recommendation" =>
            "A diagnostic scan and ignition-system inspection can help identify the cause."
    ],


    "Steering vibration" => [

        "keywords" => [
            "steering vibration",
            "steering vibrating",
            "steering shaking",
            "wheel vibration",
            "steering wheel shakes"
        ],

        "parts_involved" =>
            "Tyres, wheels and alignment components",

        "parts_to_check" =>
            "Tyre balance, wheel alignment and suspension",

        "recommendation" =>
            "Have the wheels, tyres, alignment and suspension checked."
    ],


    "AC not cooling" => [

        "keywords" => [
            "ac not cooling",
            "air conditioner not cooling",
            "aircon not cooling",
            "ac warm",
            "ac hot",
            "no cold air",
            "air conditioning problem"
        ],

        "parts_involved" =>
            "AC compressor, condenser",

        "parts_to_check" =>
            "Refrigerant level, compressor and condenser",

        "recommendation" =>
            "Have the air-conditioning system pressure and components inspected."
    ],


    "Oil leak" => [

        "keywords" => [
            "oil leak",
            "oil leaking",
            "engine oil leak",
            "oil under car",
            "oil dripping"
        ],

        "parts_involved" =>
            "Engine gaskets, oil filter",

        "parts_to_check" =>
            "Engine gaskets, oil filter and visible oil-leak points",

        "recommendation" =>
            "Check the engine-oil level and arrange an inspection to locate the leak."
    ],


    "Poor fuel economy" => [

        "keywords" => [
            "poor fuel economy",
            "high fuel consumption",
            "using too much fuel",
            "fuel consumption high",
            "bad mileage",
            "low mileage per litre"
        ],

        "parts_involved" =>
            "Air filter, fuel injectors",

        "parts_to_check" =>
            "Air filter condition and fuel injectors",

        "recommendation" =>
            "A service inspection can check the intake, injectors, tyre pressure and related causes."
    ],


    "Transmission slipping" => [

        "keywords" => [
            "transmission slipping",
            "gear slipping",
            "gearbox slipping",
            "automatic gear slipping",
            "rpm increases but car slow"
        ],

        "parts_involved" =>
            "Transmission, automatic transmission fluid",

        "parts_to_check" =>
            "Transmission-fluid level and transmission condition",

        "recommendation" =>
            "Have the transmission inspected before the problem becomes more severe."
    ],


    "Suspension noise" => [

        "keywords" => [
            "suspension noise",
            "noise over bumps",
            "clunk over bumps",
            "knocking suspension",
            "suspension knocking"
        ],

        "parts_involved" =>
            "Shock absorbers, suspension bushings",

        "parts_to_check" =>
            "Suspension bushings and shock absorbers",

        "recommendation" =>
            "Have the suspension inspected, particularly if handling has also changed."
    ],


    "Starter failure" => [

        "keywords" => [
            "starter failure",
            "car not starting",
            "engine not cranking",
            "starter not working",
            "click when starting"
        ],

        "parts_involved" =>
            "Starter motor, battery",

        "parts_to_check" =>
            "Battery condition, battery connections and starter motor",

        "recommendation" =>
            "Check the battery first, then have the starter system inspected."
    ],


    "Headlight failure" => [

        "keywords" => [
            "headlight not working",
            "headlight failure",
            "headlight dead",
            "headlamp not working",
            "light not working"
        ],

        "parts_involved" =>
            "Headlight bulb, fuse",

        "parts_to_check" =>
            "Headlight bulb and related fuse",

        "recommendation" =>
            "Inspect the bulb and fuse, and have the electrical circuit checked if the problem continues."
    ],


    "Check engine light" => [

        "keywords" => [
            "check engine light",
            "engine warning light",
            "engine light",
            "dashboard engine light",
            "check engine"
        ],

        "parts_involved" =>
            "Vehicle sensors, ECU",

        "parts_to_check" =>
            "OBD diagnostic codes and related sensors",

        "recommendation" =>
            "An OBD diagnostic scan is recommended to identify the stored fault code."
    ],


    "Excessive smoke" => [

        "keywords" => [
            "excessive smoke",
            "too much smoke",
            "black smoke",
            "white smoke",
            "blue smoke",
            "smoke from exhaust"
        ],

        "parts_involved" =>
            "Fuel injectors, turbo system",

        "parts_to_check" =>
            "Injectors, turbo and engine condition",

        "recommendation" =>
            "Have the engine and fuel system inspected to determine the source of the smoke."
    ],


    "Clutch slipping" => [

        "keywords" => [
            "clutch slipping",
            "clutch slip",
            "rpm rises but car slow",
            "clutch problem"
        ],

        "parts_involved" =>
            "Clutch plate",

        "parts_to_check" =>
            "Clutch wear and clutch operation",

        "recommendation" =>
            "Have the clutch system inspected."
    ],


    "Wheel bearing noise" => [

        "keywords" => [
            "wheel bearing noise",
            "humming from wheel",
            "wheel humming",
            "bearing noise",
            "wheel roaring"
        ],

        "parts_involved" =>
            "Wheel bearing",

        "parts_to_check" =>
            "Wheel-bearing play and condition",

        "recommendation" =>
            "Have the affected wheel bearing inspected."
    ],


    "Power window failure" => [

        "keywords" => [
            "power window not working",
            "window not working",
            "electric window problem",
            "window stuck"
        ],

        "parts_involved" =>
            "Window motor, window switch",

        "parts_to_check" =>
            "Window motor and control switch",

        "recommendation" =>
            "Have the window electrical circuit, switch and motor checked."
    ],


    "Wiper not working" => [

        "keywords" => [
            "wiper not working",
            "wipers not working",
            "windscreen wiper problem",
            "wiper stopped"
        ],

        "parts_involved" =>
            "Wiper motor, fuse",

        "parts_to_check" =>
            "Wiper motor and fuse",

        "recommendation" =>
            "Check the fuse and wiper motor."
    ],


    "Coolant leak" => [

        "keywords" => [
            "coolant leak",
            "coolant leaking",
            "water leak radiator",
            "losing coolant",
            "radiator water leak"
        ],

        "parts_involved" =>
            "Radiator hoses and cooling-system connections",

        "parts_to_check" =>
            "Radiator hoses, hose clamps and coolant level",

        "recommendation" =>
            "Avoid running the engine with insufficient coolant. Have the cooling system inspected."
    ],


    "Engine knocking" => [

        "keywords" => [
            "engine knocking",
            "knocking engine",
            "engine knock",
            "metal knocking engine"
        ],

        "parts_involved" =>
            "Engine oil system, engine bearings",

        "parts_to_check" =>
            "Engine-oil level and engine bearings",

        "recommendation" =>
            "Check the engine-oil level and arrange an engine inspection."
    ]
];


// =====================================================
// AJAX CHAT REQUEST
// =====================================================

if (
    $_SERVER["REQUEST_METHOD"]
    === "POST"
) {

    $rawInput =
        file_get_contents(
            "php://input"
        );


    $jsonInput =
        json_decode(
            $rawInput,
            true
        );


    $message =
        trim(
            $jsonInput["message"]
            ??
            $_POST["message"]
            ??
            ""
        );


    if ($message === "") {

        chatbotJson([
            "success" => false,
            "message" => "Please enter a question or describe the vehicle problem."
        ]);
    }


    $normalized =
        normalizeMessage(
            $message
        );


    // =================================================
    // NO VEHICLE
    // =================================================

    if (!$vehicle) {

        chatbotJson([
            "success" => true,
            "reply" =>
                "I could not find a registered vehicle for your account. Please add your vehicle first."
        ]);
    }


    $vehicleId =
        (int)
        $vehicle["vehicle_id"];


    $vehicleName =
        trim(
            ($vehicle["make"] ?? "")
            . " "
            . ($vehicle["model"] ?? "")
        );


    // =================================================
    // GREETING
    // =================================================

    if (
        containsAny(
            $normalized,
            [
                "hello",
                "hi",
                "hey",
                "good morning",
                "good afternoon"
            ]
        )
    ) {

        chatbotJson([
            "success" => true,
            "reply" =>
                "Hello "
                . $firstName
                . ". I can help you with "
                . $vehicleName
                . ". You can describe a problem or ask about your next maintenance service."
        ]);
    }


    // =================================================
    // VEHICLE DETAILS QUESTION
    // =================================================

    if (
        containsAny(
            $normalized,
            [
                "my vehicle",
                "vehicle details",
                "car details",
                "what vehicle",
                "my car",
                "vehicle information"
            ]
        )
    ) {

        $details =
            $vehicleName;

        if (
            !empty(
                $vehicle[
                    "registration_number"
                ]
            )
        ) {

            $details .=
                " ("
                . $vehicle[
                    "registration_number"
                ]
                . ")";
        }


        $details .=
            ". Current mileage: "
            . number_format(
                (int)
                (
                    $vehicle[
                        "current_mileage"
                    ]
                    ?? 0
                )
            )
            . " km";


        if (
            !empty(
                $vehicle[
                    "fuel_type"
                ]
            )
        ) {

            $details .=
                ". Fuel type: "
                . $vehicle[
                    "fuel_type"
                ];
        }


        chatbotJson([
            "success" => true,
            "reply" => $details,
            "vehicle" => $vehicleName
        ]);
    }


    // =================================================
    // NEXT SERVICE / MAINTENANCE QUESTION
    // =================================================

    if (
        containsAny(
            $normalized,
            [
                "next service",
                "when service",
                "service due",
                "maintenance due",
                "next maintenance",
                "how many km",
                "km remaining",
                "service remaining",
                "oil change due",
                "when oil"
            ]
        )
    ) {

        $scheduleSql = "
            SELECT
                maintenance_type,
                due_date,
                due_mileage,
                schedule_status

            FROM maintenance_schedule

            WHERE vehicle_id = ?

            AND schedule_status IN (
                'upcoming',
                'due',
                'overdue'
            )

            ORDER BY

                CASE
                    WHEN schedule_status = 'overdue' THEN 1
                    WHEN schedule_status = 'due' THEN 2
                    ELSE 3
                END,

                due_date ASC,

                due_mileage ASC

            LIMIT 1
        ";


        $scheduleStmt =
            mysqli_prepare(
                $conn,
                $scheduleSql
            );


        $schedule = null;


        if ($scheduleStmt) {

            mysqli_stmt_bind_param(
                $scheduleStmt,
                "i",
                $vehicleId
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
        }


        if (!$schedule) {

            chatbotJson([
                "success" => true,
                "reply" =>
                    "I could not find an active maintenance schedule for your "
                    . $vehicleName
                    . "."
            ]);
        }


        $reply =
            "Your next maintenance item is "
            . $schedule[
                "maintenance_type"
            ]
            . ".";


        if (
            !empty(
                $schedule[
                    "due_mileage"
                ]
            )
        ) {

            $currentMileage =
                (int)
                (
                    $vehicle[
                        "current_mileage"
                    ]
                    ?? 0
                );


            $dueMileage =
                (int)
                $schedule[
                    "due_mileage"
                ];


            $remaining =
                $dueMileage
                -
                $currentMileage;


            $reply .=
                " It is scheduled at "
                . number_format(
                    $dueMileage
                )
                . " km.";


            if ($remaining > 0) {

                $reply .=
                    " Approximately "
                    . number_format(
                        $remaining
                    )
                    . " km remain.";

            } elseif ($remaining === 0) {

                $reply .=
                    " The mileage target has been reached.";

            } else {

                $reply .=
                    " The mileage target has been exceeded by "
                    . number_format(
                        abs(
                            $remaining
                        )
                    )
                    . " km.";
            }
        }


        if (
            !empty(
                $schedule[
                    "due_date"
                ]
            )
        ) {

            $reply .=
                " Estimated due date: "
                . date(
                    "d M Y",
                    strtotime(
                        $schedule[
                            "due_date"
                        ]
                    )
                )
                . ".";
        }


        $reply .=
            " Status: "
            . ucfirst(
                $schedule[
                    "schedule_status"
                ]
            )
            . ".";


        chatbotJson([
            "success" => true,
            "reply" => $reply,
            "vehicle" => $vehicleName
        ]);
    }


    // =================================================
    // GARAGE QUESTION
    // =================================================

    if (
        containsAny(
            $normalized,
            [
                "garage",
                "nearest garage",
                "find garage",
                "mechanic",
                "service center",
                "service centre"
            ]
        )
    ) {

        chatbotJson([
            "success" => true,
            "reply" =>
                "You can use the Garages page to find approved AutoTrack garages near your current location or search all nearby garages using Google Maps.",
            "recommendation" =>
                "Open the Garages section from the left menu."
        ]);
    }


    // =================================================
    // PROBLEM / SYMPTOM MATCHING
    // =================================================

    $matchedProblem = null;


    foreach (
        $problemCatalog
        as $problemName => $problemData
    ) {

        if (
            containsAny(
                $normalized,
                $problemData[
                    "keywords"
                ]
            )
        ) {

            $matchedProblem = [
                "name" =>
                    $problemName,

                "data" =>
                    $problemData
            ];

            break;
        }
    }


    if ($matchedProblem) {

        chatbotJson([

            "success" => true,

            "reply" =>
                "I found a possible problem match based on the symptoms you described. This is guidance only; a mechanic should confirm the actual fault.",

            "vehicle" =>
                $vehicleName,

            "problem" =>
                $matchedProblem[
                    "name"
                ],

            "parts_involved" =>
                $matchedProblem[
                    "data"
                ][
                    "parts_involved"
                ],

            "parts_to_check" =>
                $matchedProblem[
                    "data"
                ][
                    "parts_to_check"
                ],

            "recommendation" =>
                $matchedProblem[
                    "data"
                ][
                    "recommendation"
                ]
        ]);
    }


    // =================================================
    // FALLBACK
    // =================================================

    chatbotJson([

        "success" => true,

        "reply" =>
            "I could not confidently match that description to one of the maintenance problems in my current problem dataset.",

        "vehicle" =>
            $vehicleName,

        "recommendation" =>
            "Try describing the main symptom, for example: engine overheating, brake noise, battery not charging, AC not cooling, oil leak, steering vibration, check-engine light, transmission slipping or coolant leak."
    ]);
}

?>

<!doctype html>

<html lang="en">

<head>

    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width,initial-scale=1"
    >

    <title>
        AI Maintenance Assistant - AutoTrack
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
        href="css/style.css"
    >

    <link
        rel="stylesheet"
        href="css/responsive.css"
    >


    <style>

        .chat-layout {
            display: grid;
            grid-template-columns: 280px minmax(0, 1fr);
            gap: 18px;
            align-items: start;
        }


        .chat-sidebar h3 {
            margin-top: 0;
        }


        .suggestion-btn {
            width: 100%;
            margin-bottom: 10px;
            text-align: left;
        }


        .chat-window {
            min-height: 610px;
            display: flex;
            flex-direction: column;
        }


        .messages {
            height: 500px;
            overflow-y: auto;
            padding: 6px;
        }


        .message {
            max-width: 82%;
            margin-bottom: 12px;
            padding: 12px 14px;
            border-radius: 14px;
            line-height: 1.55;
            font-size: 14px;
            white-space: normal;
        }


        .message.bot {
            margin-right: auto;
            background: #f2f4f7;
            color: #172033;
        }


        .message.user {
            margin-left: auto;
            background: #0f62fe;
            color: #ffffff;
        }


        .diagnostic-result {
            max-width: 90%;
        }


        .diagnostic-section {
            margin-top: 12px;
            padding-top: 10px;
            border-top: 1px solid #dfe3e8;
        }


        .diagnostic-section strong {
            color: #101828;
        }


        .chat-input {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 10px;
            margin-top: auto;
            padding-top: 14px;
            border-top: 1px solid #e4e7ec;
        }


        .chat-input input {
            width: 100%;
            border: 1px solid #d0d5dd;
            border-radius: 10px;
            padding: 12px 13px;
            font: inherit;
            outline: none;
        }


        .chat-input input:focus {
            border-color: #0f62fe;
            box-shadow: 0 0 0 3px rgba(15, 98, 254, .12);
        }


        .brand-text {
            display: flex;
            flex-direction: column;
        }


        .brand-name {
            color: #ffffff;
            font-size: 19px;
            font-weight: 800;
        }


        .brand-subtitle {
            margin-top: 3px;
            color: #94a3b8;
            font-size: 11px;
        }


        .vehicle-context {
            margin-bottom: 18px;
            padding: 12px;
            border-radius: 10px;
            background: #f8fafc;
            border: 1px solid #e4e7ec;
            font-size: 13px;
            line-height: 1.5;
        }


        @media (
            max-width: 850px
        ) {

            .chat-layout {
                grid-template-columns: 1fr;
            }


            .messages {
                height: 420px;
            }
        }

    </style>

</head>


<body data-page="chatbot">


<div class="app-shell">


    <!-- =================================================
         SIDEBAR
    ================================================== -->

<?php
    require_once 'includes/sidebar.php';
    ?>



    <!-- =================================================
         MAIN CONTENT
    ================================================== -->

    <main class="main">


        <header class="topbar">


            <div class="title">

                <h1>
                    AI Maintenance Assistant
                </h1>

                <p>
                    Vehicle problem guidance and maintenance information
                </p>

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



        <section class="chat-layout">


            <!-- =========================================
                 LEFT SIDE
            ========================================== -->

            <aside class="card chat-sidebar">


                <h3>
                    Suggested questions
                </h3>


                <?php if ($vehicle): ?>

                    <div class="vehicle-context">

                        <strong>
                            Your Vehicle
                        </strong>

                        <br>

                        <?= htmlspecialchars(
                            trim(
                                ($vehicle["make"] ?? "")
                                . " "
                                . ($vehicle["model"] ?? "")
                            )
                        ) ?>

                        <?php if (
                            !empty(
                                $vehicle[
                                    "registration_number"
                                ]
                            )
                        ): ?>

                            <br>

                            <?= htmlspecialchars(
                                $vehicle[
                                    "registration_number"
                                ]
                            ) ?>

                        <?php endif; ?>

                    </div>

                <?php endif; ?>


                <button
                    type="button"
                    class="btn btn-secondary suggestion-btn"
                    data-question="When is my next service?"
                >
                    🗓️ When is my next service?
                </button>


                <button
                    type="button"
                    class="btn btn-secondary suggestion-btn"
                    data-question="My engine is overheating"
                >
                    🌡️ Engine overheating
                </button>


                <button
                    type="button"
                    class="btn btn-secondary suggestion-btn"
                    data-question="My brakes are squeaking"
                >
                    🛑 Brake noise
                </button>


                <button
                    type="button"
                    class="btn btn-secondary suggestion-btn"
                    data-question="My battery is not charging"
                >
                    🔋 Battery problem
                </button>


                <button
                    type="button"
                    class="btn btn-secondary suggestion-btn"
                    data-question="My AC is not cooling"
                >
                    ❄️ AC not cooling
                </button>


                <button
                    type="button"
                    class="btn btn-secondary suggestion-btn"
                    data-question="My check engine light is on"
                >
                    ⚠️ Check-engine light
                </button>


                <button
                    type="button"
                    class="btn btn-secondary suggestion-btn"
                    data-question="Find a garage near me"
                >
                    📍 Find a garage
                </button>


                <p
                    class="muted"
                    style="
                        margin-top:16px;
                    "
                >

                    The assistant provides possible problem matches and maintenance guidance. A mechanic should confirm the actual vehicle fault.

                </p>


            </aside>



            <!-- =========================================
                 CHAT WINDOW
            ========================================== -->

            <div class="card chat-window">


                <div
                    class="messages"
                    id="chatMessages"
                >


                    <div class="message bot">

                        Hello
                        <?= htmlspecialchars(
                            $firstName
                        ) ?>.
                        👋

                        <?php if ($vehicle): ?>

                            I can help with your
                            <?= htmlspecialchars(
                                trim(
                                    ($vehicle["make"] ?? "")
                                    . " "
                                    . ($vehicle["model"] ?? "")
                                )
                            ) ?>.

                        <?php endif; ?>

                        Describe the problem you are experiencing, or ask about your next service.

                    </div>


                </div>



                <form
                    id="chatForm"
                    class="chat-input"
                >


                    <input
                        id="chatMessage"
                        type="text"
                        autocomplete="off"
                        placeholder="Example: My engine is getting very hot..."
                        required
                    >


                    <button
                        class="btn btn-primary"
                        type="submit"
                    >
                        Send
                    </button>


                </form>


            </div>


        </section>



        <div class="footer-note">
            AutoTrack • Automobile Service and Maintenance Tracker
        </div>


    </main>


</div>



<script>


document.addEventListener(
    "DOMContentLoaded",
    function () {


        const chatForm =
            document.getElementById(
                "chatForm"
            );


        const chatInput =
            document.getElementById(
                "chatMessage"
            );


        const messages =
            document.getElementById(
                "chatMessages"
            );


        const suggestionButtons =
            document.querySelectorAll(
                "[data-question]"
            );


        // =============================================
        // SEND QUESTION
        // =============================================

        async function sendQuestion(
            message
        ) {


            message =
                message.trim();


            if (!message) {
                return;
            }


            addTextMessage(
                message,
                "user"
            );


            chatInput.value =
                "";


            chatInput.focus();


            const loading =
                document.createElement(
                    "div"
                );


            loading.className =
                "message bot";


            loading.textContent =
                "Checking your vehicle information and problem data...";


            messages.appendChild(
                loading
            );


            scrollBottom();


            try {


                const response =
                    await fetch(
                        "chatbot.php",
                        {

                            method:
                                "POST",

                            headers: {

                                "Content-Type":
                                    "application/json"
                            },

                            body:
                                JSON.stringify(
                                    {
                                        message:
                                            message
                                    }
                                )
                        }
                    );


                if (!response.ok) {

                    throw new Error(
                        "Server error "
                        +
                        response.status
                    );
                }


                const data =
                    await response.json();


                loading.remove();


                if (
                    data.success
                ) {

                    addBotResponse(
                        data
                    );

                } else {

                    addTextMessage(

                        data.message
                        ||
                        "I could not process that question.",

                        "bot"
                    );
                }


            } catch (
                error
            ) {


                console.error(
                    error
                );


                loading.remove();


                addTextMessage(

                    "Sorry, I could not process the request. Please try again.",

                    "bot"
                );
            }
        }



        // =============================================
        // FORM SUBMIT
        // =============================================

        chatForm.addEventListener(

            "submit",

            function (
                event
            ) {


                event.preventDefault();


                sendQuestion(
                    chatInput.value
                );
            }

        );



        // =============================================
        // SUGGESTED QUESTION BUTTONS
        // =============================================

        suggestionButtons.forEach(

            function (
                button
            ) {


                button.addEventListener(

                    "click",

                    function () {


                        const question =
                            button.getAttribute(
                                "data-question"
                            );


                        sendQuestion(
                            question
                        );
                    }

                );

            }

        );



        // =============================================
        // SIMPLE MESSAGE
        // =============================================

        function addTextMessage(

            text,
            type

        ) {


            const div =
                document.createElement(
                    "div"
                );


            div.className =
                "message "
                +
                type;


            div.textContent =
                text;


            messages.appendChild(
                div
            );


            scrollBottom();
        }



        // =============================================
        // DIAGNOSTIC RESPONSE
        // =============================================

        function addBotResponse(
            data
        ) {


            const div =
                document.createElement(
                    "div"
                );


            div.className =
                "message bot diagnostic-result";


            let html =
                "";


            if (
                data.reply
            ) {

                html += `

                    <div>

                        ${
                            escapeHtml(
                                data.reply
                            )
                        }

                    </div>
                `;
            }


            if (
                data.vehicle
            ) {

                html += `

                    <div class="diagnostic-section">

                        <strong>
                            🚗 Vehicle
                        </strong>

                        <br>

                        ${
                            escapeHtml(
                                data.vehicle
                            )
                        }

                    </div>
                `;
            }


            if (
                data.problem
            ) {

                html += `

                    <div class="diagnostic-section">

                        <strong>
                            ⚠️ Possible Problem
                        </strong>

                        <br>

                        ${
                            escapeHtml(
                                data.problem
                            )
                        }

                    </div>
                `;
            }


            if (
                data.parts_involved
            ) {

                html += `

                    <div class="diagnostic-section">

                        <strong>
                            🔧 Possible Components
                        </strong>

                        <br>

                        ${
                            escapeHtml(
                                data.parts_involved
                            )
                        }

                    </div>
                `;
            }


            if (
                data.parts_to_check
            ) {

                html += `

                    <div class="diagnostic-section">

                        <strong>
                            🔍 Parts to Check
                        </strong>

                        <br>

                        ${
                            escapeHtml(
                                data.parts_to_check
                            )
                        }

                    </div>
                `;
            }


            if (
                data.recommendation
            ) {

                html += `

                    <div class="diagnostic-section">

                        <strong>
                            💡 Recommendation
                        </strong>

                        <br>

                        ${
                            escapeHtml(
                                data.recommendation
                            )
                        }

                    </div>
                `;
            }


            div.innerHTML =
                html;


            messages.appendChild(
                div
            );


            scrollBottom();
        }



        // =============================================
        // ESCAPE HTML
        // =============================================

        function escapeHtml(
            value
        ) {


            const div =
                document.createElement(
                    "div"
                );


            div.textContent =
                String(
                    value
                    ??
                    ""
                );


            return div.innerHTML;
        }



        // =============================================
        // SCROLL
        // =============================================

        function scrollBottom() {

            messages.scrollTop =
                messages.scrollHeight;
        }

    }
);


</script>


<script src="js/app.js"></script>


</body>

</html>