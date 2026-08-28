<?php

error_reporting(E_ALL);
ini_set("display_errors", 1);

session_start();

require_once "config/database.php";


// ==========================================================
// LOGIN CHECK
// ==========================================================

if (
    !isset($_SESSION["user_id"])
    ||
    !isset($_SESSION["role"])
) {
    header("Location: login.php");
    exit();
}


if ($_SESSION["role"] !== "vehicle_owner") {
    header("Location: login.php");
    exit();
}


$userId = (int) $_SESSION["user_id"];


// ==========================================================
// HELPER - CHECK COLUMN EXISTS
// ==========================================================

function columnExists(
    mysqli $conn,
    string $table,
    string $column
): bool {

    $sql = "
        SELECT COUNT(*) AS total
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
        AND table_name = ?
        AND column_name = ?
    ";

    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param(
        $stmt,
        "ss",
        $table,
        $column
    );

    mysqli_stmt_execute($stmt);

    $result =
        mysqli_stmt_get_result($stmt);

    $row =
        mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    return ((int)($row["total"] ?? 0)) > 0;
}


// ==========================================================
// HELPER - FIND FIRST AVAILABLE COLUMN
// ==========================================================

function findColumn(
    mysqli $conn,
    string $table,
    array $possibleColumns
): ?string {

    foreach ($possibleColumns as $column) {

        if (
            columnExists(
                $conn,
                $table,
                $column
            )
        ) {
            return $column;
        }
    }

    return null;
}


// ==========================================================
// LOAD LOGGED IN USER
// ==========================================================

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
        "Unable to load user: "
        . mysqli_error($conn)
    );
}


mysqli_stmt_bind_param(
    $userStmt,
    "i",
    $userId
);

mysqli_stmt_execute($userStmt);

$userResult =
    mysqli_stmt_get_result($userStmt);

$user =
    mysqli_fetch_assoc($userResult);

mysqli_stmt_close($userStmt);


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


// ==========================================================
// DETECT SERVICE_RECORDS COLUMNS
// ==========================================================

$vehicleIdColumn =
    findColumn(
        $conn,
        "service_records",
        [
            "vehicle_id",
            "vehicleId"
        ]
    );


$dateColumn =
    findColumn(
        $conn,
        "service_records",
        [
            "service_date",
            "date",
            "completed_date",
            "created_at"
        ]
    );


$typeColumn =
    findColumn(
        $conn,
        "service_records",
        [
            "service_type",
            "maintenance_type",
            "service_name",
            "description"
        ]
    );


$mileageColumn =
    findColumn(
        $conn,
        "service_records",
        [
            "mileage",
            "service_mileage",
            "current_mileage"
        ]
    );


$costColumn =
    findColumn(
        $conn,
        "service_records",
        [
            "cost",
            "total_cost",
            "service_cost",
            "amount"
        ]
    );


$garageIdColumn =
    findColumn(
        $conn,
        "service_records",
        [
            "garage_id"
        ]
    );


// ==========================================================
// LOAD SERVICE HISTORY
// ==========================================================

$serviceRecords = [];

$errorMessage = "";


if (!$vehicleIdColumn) {

    $errorMessage =
        "The service_records table does not contain a vehicle_id column.";

} else {

    // ------------------------------------------------------
    // BUILD OPTIONAL SELECTS
    // ------------------------------------------------------

    $dateSelect =
        $dateColumn
            ? "sr.`$dateColumn`"
            : "NULL";


    $typeSelect =
        $typeColumn
            ? "sr.`$typeColumn`"
            : "'Service'";


    $mileageSelect =
        $mileageColumn
            ? "sr.`$mileageColumn`"
            : "NULL";


    $costSelect =
        $costColumn
            ? "sr.`$costColumn`"
            : "NULL";


    // ------------------------------------------------------
    // GARAGE SUPPORT
    // ------------------------------------------------------

    if ($garageIdColumn) {

        $garageSelect =
            "COALESCE(g.garage_name, 'Unknown Garage')";

        $garageJoin = "
            LEFT JOIN garages g
            ON g.garage_id = sr.`$garageIdColumn`
        ";

    } else {

        $garageSelect =
            "'Not specified'";

        $garageJoin =
            "";
    }


    // ------------------------------------------------------
    // SERVICE RECORD QUERY
    // ------------------------------------------------------

    $historySql = "

        SELECT

            $dateSelect AS service_date,

            CONCAT(
                COALESCE(v.make, ''),
                ' ',
                COALESCE(v.model, '')
            ) AS vehicle_name,

            v.registration_number,

            $typeSelect AS service_type,

            $garageSelect AS garage_name,

            $mileageSelect AS service_mileage,

            $costSelect AS service_cost

        FROM service_records sr

        INNER JOIN vehicles v
        ON v.vehicle_id =
            sr.`$vehicleIdColumn`

        $garageJoin

        WHERE v.user_id = ?

    ";


    if ($dateColumn) {

        $historySql .= "
            ORDER BY
                sr.`$dateColumn`
            DESC
        ";

    } else {

        $historySql .= "
            ORDER BY
                sr.`$vehicleIdColumn`
            DESC
        ";
    }


    $historyStmt =
        mysqli_prepare(
            $conn,
            $historySql
        );


    if (!$historyStmt) {

        $errorMessage =
            "Unable to prepare service history query: "
            . mysqli_error($conn);

    } else {

        mysqli_stmt_bind_param(
            $historyStmt,
            "i",
            $userId
        );


        if (
            !mysqli_stmt_execute(
                $historyStmt
            )
        ) {

            $errorMessage =
                "Unable to load service history: "
                . mysqli_stmt_error(
                    $historyStmt
                );

        } else {

            $historyResult =
                mysqli_stmt_get_result(
                    $historyStmt
                );


            while (
                $row =
                mysqli_fetch_assoc(
                    $historyResult
                )
            ) {

                $serviceRecords[] =
                    $row;
            }
        }


        mysqli_stmt_close(
            $historyStmt
        );
    }
}


// ==========================================================
// TOTALS
// ==========================================================

$totalRecords =
    count(
        $serviceRecords
    );


$totalCost = 0;


foreach (
    $serviceRecords
    as $record
) {

    if (
        isset(
            $record[
                "service_cost"
            ]
        )
        &&
        is_numeric(
            $record[
                "service_cost"
            ]
        )
    ) {

        $totalCost +=
            (float)
            $record[
                "service_cost"
            ];
    }
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
        Service History - AutoTrack
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


        .history-stats {

            display: grid;

            grid-template-columns:
                repeat(
                    2,
                    minmax(
                        200px,
                        300px
                    )
                );

            gap: 16px;

            margin-bottom: 25px;
        }


        .history-stat {

            padding: 20px;

            border: 1px solid #e4e7ec;

            border-radius: 14px;

            background: #ffffff;
        }


        .history-stat-label {

            color: #667085;

            font-size: 14px;
        }


        .history-stat-value {

            margin-top: 8px;

            color: #101828;

            font-size: 25px;

            font-weight: 800;
        }


        .service-empty {

            padding: 45px 20px;

            text-align: center;

            color: #667085;
        }


        .error-box {

            margin-bottom: 20px;

            padding: 14px 16px;

            border: 1px solid #fecdca;

            border-radius: 10px;

            color: #b42318;

            background: #fef3f2;
        }


        .vehicle-registration {

            display: block;

            margin-top: 3px;

            color: #667085;

            font-size: 12px;
        }


        .status-pill {

            display: inline-block;

            padding: 5px 9px;

            border-radius: 999px;

            background: #ecfdf3;

            color: #067647;

            font-size: 12px;

            font-weight: 700;
        }


        @media (
            max-width: 700px
        ) {

            .history-stats {

                grid-template-columns:
                    1fr;
            }
        }

    </style>

</head>


<body data-page="service">


<div class="app-shell">


    <!-- ==================================================
         SIDEBAR
    =================================================== -->
<?php
    require_once 'includes/sidebar.php';
    ?>


    <!-- ==================================================
         MAIN
    =================================================== -->

    <main class="main">


        <!-- ==============================================
             HEADER
        =============================================== -->

        <header class="topbar">


            <div class="title">

                <h1>
                    Service History
                </h1>

                <p>
                    View all completed vehicle service records
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



        <!-- ==============================================
             STATISTICS
        =============================================== -->

        <section class="history-stats">


            <div class="history-stat">

                <div class="history-stat-label">

                    Total Service Records

                </div>

                <div class="history-stat-value">

                    <?= number_format(
                        $totalRecords
                    ) ?>

                </div>

            </div>



            <div class="history-stat">

                <div class="history-stat-label">

                    Total Service Cost

                </div>

                <div class="history-stat-value">

                    Rs.
                    <?= number_format(
                        $totalCost,
                        2
                    ) ?>

                </div>

            </div>


        </section>



        <?php if ($errorMessage !== ""): ?>


            <div class="error-box">

                <?= htmlspecialchars(
                    $errorMessage
                ) ?>

            </div>


        <?php endif; ?>



        <!-- ==============================================
             SECTION HEADING
        =============================================== -->

        <div class="section-head">


            <h2>
                All Service Records
            </h2>


            <a
                href="maintenance.php"
                class="btn btn-primary"
            >
                Schedule Service
            </a>


        </div>



        <!-- ==============================================
             TABLE
        =============================================== -->

        <div class="card table-wrap">


            <table class="table">


                <thead>

                    <tr>

                        <th>
                            Date
                        </th>

                        <th>
                            Vehicle
                        </th>

                        <th>
                            Service
                        </th>

                        <th>
                            Garage
                        </th>

                        <th>
                            Mileage
                        </th>

                        <th>
                            Cost
                        </th>

                        <th>
                            Status
                        </th>

                    </tr>

                </thead>



                <tbody>


                <?php if (
                    count(
                        $serviceRecords
                    ) === 0
                ): ?>


                    <tr>

                        <td
                            colspan="7"
                            class="service-empty"
                        >

                            <strong>
                                No service history available yet.
                            </strong>

                            <br><br>

                            Completed vehicle services
                            will appear here.

                        </td>

                    </tr>


                <?php else: ?>


                    <?php foreach (
                        $serviceRecords
                        as $record
                    ): ?>


                        <tr>


                            <!-- DATE -->

                            <td>

                                <?php

                                if (
                                    !empty(
                                        $record[
                                            "service_date"
                                        ]
                                    )
                                ) {

                                    $timestamp =
                                        strtotime(
                                            $record[
                                                "service_date"
                                            ]
                                        );

                                    if ($timestamp) {

                                        echo htmlspecialchars(
                                            date(
                                                "d M Y",
                                                $timestamp
                                            )
                                        );

                                    } else {

                                        echo htmlspecialchars(
                                            $record[
                                                "service_date"
                                            ]
                                        );
                                    }

                                } else {

                                    echo "-";
                                }

                                ?>

                            </td>



                            <!-- VEHICLE -->

                            <td>

                                <strong>

                                    <?= htmlspecialchars(
                                        trim(
                                            $record[
                                                "vehicle_name"
                                            ]
                                        )
                                    ) ?>

                                </strong>


                                <?php if (
                                    !empty(
                                        $record[
                                            "registration_number"
                                        ]
                                    )
                                ): ?>


                                    <span
                                        class="vehicle-registration"
                                    >

                                        <?= htmlspecialchars(
                                            $record[
                                                "registration_number"
                                            ]
                                        ) ?>

                                    </span>


                                <?php endif; ?>


                            </td>



                            <!-- SERVICE -->

                            <td>

                                <?= htmlspecialchars(
                                    $record[
                                        "service_type"
                                    ]
                                    ??
                                    "Service"
                                ) ?>

                            </td>



                            <!-- GARAGE -->

                            <td>

                                <?= htmlspecialchars(
                                    $record[
                                        "garage_name"
                                    ]
                                    ??
                                    "Not specified"
                                ) ?>

                            </td>



                            <!-- MILEAGE -->

                            <td>


                                <?php

                                if (
                                    isset(
                                        $record[
                                            "service_mileage"
                                        ]
                                    )
                                    &&
                                    is_numeric(
                                        $record[
                                            "service_mileage"
                                        ]
                                    )
                                ) {

                                    echo number_format(
                                        (int)
                                        $record[
                                            "service_mileage"
                                        ]
                                    )
                                    . " km";

                                } else {

                                    echo "-";
                                }

                                ?>


                            </td>



                            <!-- COST -->

                            <td>


                                <?php

                                if (
                                    isset(
                                        $record[
                                            "service_cost"
                                        ]
                                    )
                                    &&
                                    is_numeric(
                                        $record[
                                            "service_cost"
                                        ]
                                    )
                                ) {

                                    echo "Rs. "
                                    . number_format(
                                        (float)
                                        $record[
                                            "service_cost"
                                        ],
                                        2
                                    );

                                } else {

                                    echo "-";
                                }

                                ?>


                            </td>



                            <!-- STATUS -->

                            <td>

                                <span class="status-pill">

                                    Completed

                                </span>

                            </td>


                        </tr>


                    <?php endforeach; ?>


                <?php endif; ?>


                </tbody>


            </table>


        </div>



        <div class="footer-note">

            AutoTrack • Automobile Service and Maintenance Tracker

        </div>


    </main>


</div>


<script src="js/app.js"></script>


</body>

</html>