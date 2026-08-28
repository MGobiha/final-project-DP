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

if (
    $_SESSION["role"]
    !== "vehicle_owner"
) {

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
        last_name
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

    header(
        "Location: login.php"
    );

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
        ?
        strtoupper(
            substr(
                $firstName,
                0,
                1
            )
        )
        :
        "U";


// =====================================================
// LOAD APPROVED GARAGES
// =====================================================

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

    ORDER BY garage_name ASC
";


$garageResult =
    mysqli_query(
        $conn,
        $garageSql
    );


if (!$garageResult) {

    die(
        "Garage query error: "
        . mysqli_error($conn)
    );
}


$garages = [];


while (
    $garage =
    mysqli_fetch_assoc(
        $garageResult
    )
) {

    // Skip garages without coordinates
    if (
        $garage["latitude"] === null
        ||
        $garage["longitude"] === null
        ||
        $garage["latitude"] === ""
        ||
        $garage["longitude"] === ""
    ) {

        continue;
    }


    $garages[] = [

        "garage_id" =>
            (int)
            $garage["garage_id"],

        "garage_name" =>
            $garage["garage_name"],

        "owner_name" =>
            $garage["owner_name"],

        "email" =>
            $garage["email"],

        "mobile_number" =>
            $garage["mobile_number"],

        "telephone" =>
            $garage["telephone"],

        "address" =>
            $garage["address"],

        "city" =>
            $garage["city"],

        "district" =>
            $garage["district"],

        "latitude" =>
            (float)
            $garage["latitude"],

        "longitude" =>
            (float)
            $garage["longitude"],

        "opening_time" =>
            $garage["opening_time"],

        "closing_time" =>
            $garage["closing_time"],

        "description" =>
            $garage["description"],

        "image" =>
            $garage["image"]
    ];
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
        Nearby Garages - AutoTrack
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

        .garage-toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 20px;
        }


        .location-status {
            padding: 12px 15px;
            margin-bottom: 18px;

            border: 1px solid #dbe5f1;
            border-radius: 10px;

            background: #ffffff;

            color: #667085;

            font-size: 14px;
        }


        .location-status.success {
            color: #067647;

            border-color: #abefc6;

            background: #ecfdf3;
        }


        .location-status.error {
            color: #b42318;

            border-color: #fecdca;

            background: #fef3f2;
        }


        .garage-layout {

            display: grid;

            grid-template-columns:
                minmax(320px, 0.9fr)
                minmax(420px, 1.1fr);

            gap: 18px;

            align-items: start;
        }


        .map-box {

            min-height: 430px;

            padding: 30px;

            border-radius: 15px;

            display: flex;

            align-items: center;

            justify-content: center;

            text-align: center;

            background:
                linear-gradient(
                    135deg,
                    #dbeafe,
                    #d1fae5
                );

            color: #344054;
        }


        .map-content h3 {
            margin-top: 0;

            color: #172033;
        }


        .map-location {
            margin-top: 15px;

            font-size: 13px;

            color: #475467;
        }


        .garage-list {

            display: grid;

            gap: 16px;
        }


        .garage-card {

            position: relative;

            padding: 18px;

            border:
                1px solid
                #e4e7ec;

            border-radius: 14px;

            background: #ffffff;

            box-shadow:
                0 6px 20px
                rgba(
                    15,
                    23,
                    42,
                    .05
                );
        }


        .garage-card h3 {

            margin:
                0
                0
                8px;

            font-size: 17px;
        }


        .garage-card p {

            margin:
                7px
                0;

            line-height: 1.5;
        }


        .garage-distance {

            font-weight: 700;

            color: #0f62fe;
        }


        .nearest-badge {

            display: inline-block;

            margin-bottom: 10px;

            padding:
                5px
                9px;

            border-radius: 999px;

            background: #ecfdf3;

            color: #067647;

            font-size: 12px;

            font-weight: 700;
        }


        .garage-meta {

            color: #667085;

            font-size: 13px;
        }


        .garage-actions {

            display: flex;

            flex-wrap: wrap;

            gap: 10px;

            margin-top: 14px;
        }


        .btn-outline {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            padding:
                10px
                14px;

            border:
                1px solid
                #d0d5dd;

            border-radius: 9px;

            background: #ffffff;

            color: #344054;

            font-weight: 600;

            text-decoration: none;

            cursor: pointer;
        }


        .empty-garage {

            padding: 30px;

            border-radius: 14px;

            background: #ffffff;

            text-align: center;

            color: #667085;
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


        @media (
            max-width: 900px
        ) {

            .garage-layout {

                grid-template-columns:
                    1fr;
            }


            .map-box {

                min-height:
                    280px;
            }
        }

    </style>

</head>


<body data-page="garages">


<div class="app-shell">


    <!-- =============================================
         SIDEBAR
    ============================================== -->

    <?php
    require_once 'includes/sidebar.php';
    ?>



    <!-- =============================================
         MAIN
    ============================================== -->

    <main class="main">


        <!-- =========================================
             TOPBAR
        ========================================== -->

        <header class="topbar">


            <div class="title">

                <h1>
                    Nearby Garages
                </h1>

                <p>
                    Find approved AutoTrack garages or search all nearby garages.
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



        <!-- =========================================
             SEARCH OPTIONS
        ========================================== -->

        <div class="garage-toolbar">


            <button
                type="button"
                class="btn btn-primary"
                id="findApprovedBtn"
            >
                📍 Find Approved Garages Near Me
            </button>


            <button
                type="button"
                class="btn-outline"
                id="searchAllBtn"
            >
                🔎 Search All Nearby Garages
            </button>


        </div>



        <!-- =========================================
             LOCATION STATUS
        ========================================== -->

        <div
            id="locationStatus"
            class="location-status"
        >

            Click
            <strong>
                Find Approved Garages Near Me
            </strong>
            and allow your browser to access your location.

        </div>



        <!-- =========================================
             GARAGE AREA
        ========================================== -->

        <section class="garage-layout">


            <!-- =====================================
                 MAP PLACEHOLDER
            ====================================== -->

            <div class="card">


                <div class="map-box">


                    <div class="map-content">


                        <h3>
                            📍 Your Location
                        </h3>


                        <p>

                            AutoTrack uses your browser location
                            to calculate the nearest approved
                            garages.

                        </p>


                        <div
                            class="map-location"
                            id="mapLocation"
                        >

                            Location not detected yet.

                        </div>


                        <div
                            style="
                                margin-top:20px;
                            "
                        >


                            <button
                                type="button"
                                class="btn btn-primary"
                                id="openCurrentLocationBtn"
                                disabled
                            >

                                Open My Location in Google Maps

                            </button>


                        </div>


                    </div>


                </div>


            </div>



            <!-- =====================================
                 GARAGE LIST
            ====================================== -->

            <div
                class="garage-list"
                id="garageList"
            >


                <div class="empty-garage">

                    Your nearest approved garages
                    will appear here after location
                    permission is granted.

                </div>


            </div>


        </section>



        <div class="footer-note">

            AutoTrack • Automobile Service and Maintenance Tracker

        </div>


    </main>


</div>



<script>


// =====================================================
// GARAGE DATA FROM PHP / MYSQL
// =====================================================

const garages =
    <?= json_encode(
        $garages,
        JSON_UNESCAPED_UNICODE
        |
        JSON_UNESCAPED_SLASHES
    ) ?>;



// =====================================================
// ELEMENTS
// =====================================================

const findApprovedBtn =
    document.getElementById(
        "findApprovedBtn"
    );


const searchAllBtn =
    document.getElementById(
        "searchAllBtn"
    );


const garageList =
    document.getElementById(
        "garageList"
    );


const locationStatus =
    document.getElementById(
        "locationStatus"
    );


const mapLocation =
    document.getElementById(
        "mapLocation"
    );


const openCurrentLocationBtn =
    document.getElementById(
        "openCurrentLocationBtn"
    );



let userLatitude = null;
let userLongitude = null;



// =====================================================
// HTML ESCAPE
// =====================================================

function escapeHtml(
    value
) {

    if (
        value === null
        ||
        value === undefined
    ) {

        return "";
    }


    return String(value)
        .replace(
            /&/g,
            "&amp;"
        )
        .replace(
            /</g,
            "&lt;"
        )
        .replace(
            />/g,
            "&gt;"
        )
        .replace(
            /"/g,
            "&quot;"
        )
        .replace(
            /'/g,
            "&#039;"
        );
}



// =====================================================
// HAVERSINE DISTANCE
// =====================================================

function calculateDistance(

    lat1,
    lon1,
    lat2,
    lon2

) {


    const earthRadius =
        6371;


    const toRadians =
        value =>
            value
            *
            Math.PI
            /
            180;


    const latitudeDifference =
        toRadians(
            lat2
            -
            lat1
        );


    const longitudeDifference =
        toRadians(
            lon2
            -
            lon1
        );


    const a =

        Math.sin(
            latitudeDifference
            /
            2
        )
        *
        Math.sin(
            latitudeDifference
            /
            2
        )

        +

        Math.cos(
            toRadians(
                lat1
            )
        )

        *

        Math.cos(
            toRadians(
                lat2
            )
        )

        *

        Math.sin(
            longitudeDifference
            /
            2
        )

        *

        Math.sin(
            longitudeDifference
            /
            2
        );


    const c =
        2
        *
        Math.atan2(
            Math.sqrt(a),
            Math.sqrt(
                1 - a
            )
        );


    return earthRadius * c;
}



// =====================================================
// FORMAT TIME
// =====================================================

function formatTime(
    time
) {


    if (!time) {

        return "";
    }


    const parts =
        time.split(":");


    let hour =
        parseInt(
            parts[0],
            10
        );


    const minute =
        parts[1]
        || "00";


    const suffix =
        hour >= 12
        ?
        "PM"
        :
        "AM";


    hour =
        hour % 12;


    if (
        hour === 0
    ) {

        hour = 12;
    }


    return (
        hour
        +
        ":"
        +
        minute
        +
        " "
        +
        suffix
    );
}



// =====================================================
// DISPLAY GARAGES
// =====================================================

function displayGarages() {


    if (
        userLatitude === null
        ||
        userLongitude === null
    ) {

        return;
    }


    if (
        garages.length === 0
    ) {


        garageList.innerHTML = `

            <div class="empty-garage">

                No approved AutoTrack garages
                with location information are
                currently available.

            </div>

        `;


        return;
    }



    const nearbyGarages =
        garages.map(

            function (
                garage
            ) {


                const distance =
                    calculateDistance(

                        userLatitude,

                        userLongitude,

                        parseFloat(
                            garage.latitude
                        ),

                        parseFloat(
                            garage.longitude
                        )

                    );


                return {

                    ...garage,

                    distance:
                        distance

                };

            }

        );


    // Nearest first
    nearbyGarages.sort(

        function (
            a,
            b
        ) {

            return (
                a.distance
                -
                b.distance
            );
        }

    );


    garageList.innerHTML =
        "";


    nearbyGarages.forEach(

        function (
            garage,
            index
        ) {


            const card =
                document.createElement(
                    "div"
                );


            card.className =
                "garage-card";


            const opening =
                formatTime(
                    garage.opening_time
                );


            const closing =
                formatTime(
                    garage.closing_time
                );


            let hoursText =
                "";


            if (
                opening
                &&
                closing
            ) {

                hoursText =
                    opening
                    +
                    " - "
                    +
                    closing;

            } else if (
                closing
            ) {

                hoursText =
                    "Open until "
                    +
                    closing;
            }


            const phone =

                garage.mobile_number
                ||
                garage.telephone
                ||
                "";


            const locationParts =
                [

                    garage.address,

                    garage.city,

                    garage.district

                ]
                .filter(
                    Boolean
                );


            const locationText =
                locationParts.join(
                    ", "
                );


            const directionsUrl =

                "https://www.google.com/maps/dir/?api=1"

                +

                "&destination="

                +

                encodeURIComponent(

                    garage.latitude
                    +
                    ","
                    +
                    garage.longitude

                );


            card.innerHTML = `

                ${
                    index === 0

                    ?

                    `
                    <span class="nearest-badge">
                        ✓ Nearest Approved Garage
                    </span>
                    `

                    :

                    ""
                }


                <h3>

                    ${
                        escapeHtml(
                            garage.garage_name
                        )
                    }

                </h3>


                <p class="garage-distance">

                    📍

                    ${
                        garage.distance
                            .toFixed(1)
                    }

                    km away

                </p>


                ${
                    hoursText

                    ?

                    `
                    <p class="garage-meta">

                        🕐

                        ${
                            escapeHtml(
                                hoursText
                            )
                        }

                    </p>
                    `

                    :

                    ""
                }


                ${
                    locationText

                    ?

                    `
                    <p class="garage-meta">

                        📌

                        ${
                            escapeHtml(
                                locationText
                            )
                        }

                    </p>
                    `

                    :

                    ""
                }


                ${
                    phone

                    ?

                    `
                    <p class="garage-meta">

                        📞

                        ${
                            escapeHtml(
                                phone
                            )
                        }

                    </p>
                    `

                    :

                    ""
                }


                ${
                    garage.description

                    ?

                    `
                    <p>

                        ${
                            escapeHtml(
                                garage.description
                            )
                        }

                    </p>
                    `

                    :

                    ""
                }


                <div class="garage-actions">


                    <a

                        class="btn btn-primary"

                        href="${directionsUrl}"

                        target="_blank"

                        rel="noopener noreferrer"

                    >

                        Get Directions

                    </a>


                    ${
                        phone

                        ?

                        `
                        <a

                            class="btn-outline"

                            href="tel:${
                                escapeHtml(
                                    phone
                                )
                            }"

                        >

                            Call Garage

                        </a>
                        `

                        :

                        ""
                    }


                </div>

            `;


            garageList.appendChild(
                card
            );

        }

    );
}



// =====================================================
// SUCCESSFUL LOCATION
// =====================================================

function locationSuccess(
    position
) {


    userLatitude =
        position
            .coords
            .latitude;


    userLongitude =
        position
            .coords
            .longitude;


    locationStatus
        .classList
        .remove(
            "error"
        );


    locationStatus
        .classList
        .add(
            "success"
        );


    locationStatus.innerHTML =

        "✓ Location detected successfully. "
        +
        "Approved garages are sorted from nearest to farthest.";


    mapLocation.innerHTML =

        "Latitude: "

        +

        userLatitude
            .toFixed(6)

        +

        "<br>Longitude: "

        +

        userLongitude
            .toFixed(6);


    openCurrentLocationBtn.disabled =
        false;


    displayGarages();
}



// =====================================================
// LOCATION ERROR
// =====================================================

function locationError(
    error
) {


    locationStatus
        .classList
        .remove(
            "success"
        );


    locationStatus
        .classList
        .add(
            "error"
        );


    let message =
        "Unable to access your location.";


    if (
        error.code === 1
    ) {

        message =
            "Location permission was denied. Please allow location access in your browser.";

    } else if (
        error.code === 2
    ) {

        message =
            "Your current location is unavailable.";

    } else if (
        error.code === 3
    ) {

        message =
            "Location request timed out.";
    }


    locationStatus.textContent =
        message;
}



// =====================================================
// GET LOCATION
// =====================================================

function getUserLocation() {


    if (
        !navigator.geolocation
    ) {


        locationStatus
            .classList
            .add(
                "error"
            );


        locationStatus.textContent =
            "Geolocation is not supported by this browser.";


        return;
    }


    locationStatus
        .classList
        .remove(
            "error",
            "success"
        );


    locationStatus.textContent =
        "Detecting your current location...";


    navigator.geolocation.getCurrentPosition(

        locationSuccess,

        locationError,

        {

            enableHighAccuracy:
                true,

            timeout:
                10000,

            maximumAge:
                60000
        }

    );
}



// =====================================================
// APPROVED GARAGE BUTTON
// =====================================================

findApprovedBtn.addEventListener(

    "click",

    function () {

        getUserLocation();

    }

);



// =====================================================
// SEARCH ALL NEARBY GARAGES
// =====================================================

searchAllBtn.addEventListener(

    "click",

    function () {


        // If location already detected,
        // search close to current coordinates.

        if (
            userLatitude !== null
            &&
            userLongitude !== null
        ) {


            const query =

                "garages near "

                +

                userLatitude

                +

                ","

                +

                userLongitude;


            const url =

                "https://www.google.com/maps/search/?api=1"

                +

                "&query="

                +

                encodeURIComponent(
                    query
                );


            window.open(

                url,

                "_blank"

            );


            return;
        }


        // Get location first
        navigator.geolocation.getCurrentPosition(

            function (
                position
            ) {


                userLatitude =
                    position
                        .coords
                        .latitude;


                userLongitude =
                    position
                        .coords
                        .longitude;


                const query =

                    "garages near "

                    +

                    userLatitude

                    +

                    ","

                    +

                    userLongitude;


                const url =

                    "https://www.google.com/maps/search/?api=1"

                    +

                    "&query="

                    +

                    encodeURIComponent(
                        query
                    );


                window.open(

                    url,

                    "_blank"

                );

            },

            locationError

        );

    }

);



// =====================================================
// OPEN CURRENT LOCATION
// =====================================================

openCurrentLocationBtn
    .addEventListener(

        "click",

        function () {


            if (
                userLatitude === null
                ||
                userLongitude === null
            ) {

                return;
            }


            const url =

                "https://www.google.com/maps/search/?api=1"

                +

                "&query="

                +

                encodeURIComponent(

                    userLatitude

                    +

                    ","

                    +

                    userLongitude

                );


            window.open(

                url,

                "_blank"

            );

        }

    );



</script>


<script src="js/app.js"></script>


</body>

</html>