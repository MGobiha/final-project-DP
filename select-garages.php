<?php

session_start();

require_once 'config/database.php';


// =====================================================
// CHECK LOGIN
// =====================================================

if (!isset($_SESSION["user_id"])) {

    header("Location: login.php");
    exit();
}


// =====================================================
// CHECK ROLE
// =====================================================

if (
    !isset($_SESSION["role"])
    ||
    $_SESSION["role"] !== "vehicle_owner"
) {

    header("Location: dashboard.php");
    exit();
}


$userId = (int) $_SESSION["user_id"];

$message = "";
$successMessage = "";


// =====================================================
// PROCESS GARAGE REQUESTS
// =====================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $selectedGarages =
        $_POST["garages"] ?? [];

    if (
        !is_array($selectedGarages)
        ||
        count($selectedGarages) === 0
    ) {

        $message =
            "Please select at least one garage.";

    } else {

        $requestCreated = false;

        foreach ($selectedGarages as $garageId) {

            $garageId =
                (int) $garageId;

            if ($garageId <= 0) {
                continue;
            }


            // =====================================================
            // CHECK GARAGE EXISTS AND IS APPROVED
            // =====================================================

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

            $garageExists =
                mysqli_fetch_assoc(
                    $garageCheckResult
                );

            if (!$garageExists) {
                continue;
            }


            // =====================================================
            // CHECK EXISTING REQUEST
            // =====================================================

            $existingSql = "
                SELECT
                    request_id,
                    request_status
                FROM garage_customer_requests
                WHERE garage_id = ?
                AND vehicle_owner_id = ?
                LIMIT 1
            ";

            $existingStmt =
                mysqli_prepare(
                    $conn,
                    $existingSql
                );

            mysqli_stmt_bind_param(
                $existingStmt,
                "ii",
                $garageId,
                $userId
            );

            mysqli_stmt_execute(
                $existingStmt
            );

            $existingResult =
                mysqli_stmt_get_result(
                    $existingStmt
                );

            $existingRequest =
                mysqli_fetch_assoc(
                    $existingResult
                );


            // =====================================================
            // IF REQUEST EXISTS
            // =====================================================

            if ($existingRequest) {

                $currentStatus =
                    $existingRequest[
                        "request_status"
                    ];

                if (
                    $currentStatus === "accepted"
                    ||
                    $currentStatus === "pending"
                ) {

                    // Do nothing
                    continue;
                }


                // Re-send rejected/cancelled request

                $updateSql = "
                    UPDATE garage_customer_requests
                    SET
                        request_status = 'pending',
                        requested_by = 'vehicle_owner',
                        requested_at = CURRENT_TIMESTAMP,
                        reviewed_by = NULL,
                        reviewed_at = NULL,
                        rejection_reason = NULL
                    WHERE request_id = ?
                ";

                $updateStmt =
                    mysqli_prepare(
                        $conn,
                        $updateSql
                    );

                mysqli_stmt_bind_param(
                    $updateStmt,
                    "i",
                    $existingRequest[
                        "request_id"
                    ]
                );

                if (
                    mysqli_stmt_execute(
                        $updateStmt
                    )
                ) {

                    $requestCreated = true;
                }

            } else {


                // =====================================================
                // CREATE NEW REQUEST
                // =====================================================

                $insertSql = "
                    INSERT INTO garage_customer_requests
                    (
                        garage_id,
                        vehicle_owner_id,
                        request_status,
                        requested_by
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        'pending',
                        'vehicle_owner'
                    )
                ";

                $insertStmt =
                    mysqli_prepare(
                        $conn,
                        $insertSql
                    );

                mysqli_stmt_bind_param(
                    $insertStmt,
                    "ii",
                    $garageId,
                    $userId
                );

                if (
                    mysqli_stmt_execute(
                        $insertStmt
                    )
                ) {

                    $requestCreated = true;
                }
            }
        }


        // =====================================================
        // REDIRECT AFTER SUCCESS
        // =====================================================

        if ($requestCreated) {

            $_SESSION[
                "success_message"
            ] =
                "Garage request(s) sent successfully.";

            header(
                "Location: dashboard.php"
            );

            exit();

        } else {

            $message =
                "No new garage requests were created.";
        }
    }
}


// =====================================================
// LOAD APPROVED GARAGES
// =====================================================

$garageSql = "
    SELECT
        g.garage_id,
        g.garage_name,
        g.address,
        g.city,
        g.district,
        g.mobile_number,
        g.opening_time,
        g.closing_time,
        g.description,

        r.request_status

    FROM garages g

    LEFT JOIN garage_customer_requests r
        ON g.garage_id = r.garage_id
        AND r.vehicle_owner_id = ?

    WHERE g.approval_status = 'approved'
    AND g.active_status = 1

    ORDER BY
        g.district,
        g.garage_name
";

$stmt =
    mysqli_prepare(
        $conn,
        $garageSql
    );

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $userId
);

mysqli_stmt_execute($stmt);

$garageResult =
    mysqli_stmt_get_result($stmt);

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
        Select Garages - AutoTrack
    </title>

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="css/style.css"
    >

    <style>

        body {
            background: #f5f8fc;
        }

        .garage-selection-page {
            min-height: 100vh;
            padding: 50px 20px;
        }

        .garage-selection-container {
            width: min(1100px, 100%);
            margin: 0 auto;
        }

        .garage-selection-header {
            margin-bottom: 30px;
        }

        .garage-selection-header h1 {
            margin: 0 0 10px;
            font-size: 34px;
        }

        .garage-selection-header p {
            margin: 0;
            color: #667085;
            line-height: 1.7;
        }

        .garage-select-grid {
            display: grid;
            grid-template-columns:
                repeat(
                    3,
                    minmax(0, 1fr)
                );
            gap: 20px;
        }

        .garage-select-card {
            position: relative;

            padding: 22px;

            border: 1px solid #e5e7eb;
            border-radius: 16px;

            background: #ffffff;

            transition:
                border-color .2s ease,
                box-shadow .2s ease,
                transform .2s ease;
        }

        .garage-select-card:hover {
            transform: translateY(-3px);

            border-color:
                rgba(
                    15,
                    98,
                    254,
                    .35
                );

            box-shadow:
                0 12px 28px
                rgba(
                    15,
                    35,
                    65,
                    .08
                );
        }

        .garage-select-card.disabled {
            opacity: .7;
        }

        .garage-select-top {
            display: flex;
            gap: 14px;
            align-items: flex-start;
        }

        .garage-select-checkbox {
            margin-top: 6px;
        }

        .garage-select-info {
            flex: 1;
        }

        .garage-select-info h3 {
            margin: 0 0 8px;
            font-size: 19px;
        }

        .garage-select-info p {
            margin: 6px 0;
            color: #667085;
            font-size: 14px;
            line-height: 1.5;
        }

        .request-status {
            display: inline-flex;

            margin-top: 12px;

            padding: 6px 10px;

            border-radius: 999px;

            font-size: 12px;
            font-weight: 700;
        }

        .status-pending {
            background: #fff7e6;
            color: #b54708;
        }

        .status-accepted {
            background: #ecfdf3;
            color: #027a48;
        }

        .status-rejected {
            background: #fef3f2;
            color: #b42318;
        }

        .status-cancelled {
            background: #f2f4f7;
            color: #475467;
        }

        .garage-selection-actions {
            margin-top: 30px;

            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }

        .alert {
            padding: 14px 16px;

            margin-bottom: 20px;

            border-radius: 10px;
        }

        .alert-danger {
            background: #fef3f2;
            color: #b42318;
            border: 1px solid #fecdca;
        }

        .empty-garages {
            grid-column: 1 / -1;

            padding: 40px;

            text-align: center;

            border: 1px dashed #d0d5dd;
            border-radius: 16px;

            background: #ffffff;

            color: #667085;
        }

        @media (max-width: 900px) {

            .garage-select-grid {
                grid-template-columns:
                    repeat(
                        2,
                        minmax(0, 1fr)
                    );
            }

        }

        @media (max-width: 600px) {

            .garage-select-grid {
                grid-template-columns:
                    1fr;
            }

            .garage-selection-actions {
                flex-direction: column;
            }

            .garage-selection-actions .btn {
                width: 100%;
            }

        }

    </style>

</head>

<body>

<div class="garage-selection-page">

    <div class="garage-selection-container">

        <div class="garage-selection-header">

            <h1>
                Select Your Garages
            </h1>

            <p>
                Choose one or more garages you would
                like to connect with. Your request
                will be sent to the Garage Admin
                for approval.
            </p>

        </div>


        <?php if (!empty($message)): ?>

            <div
                class="alert alert-danger"
            >
                <?php
                echo htmlspecialchars(
                    $message
                );
                ?>
            </div>

        <?php endif; ?>


        <form method="POST">

            <div class="garage-select-grid">


                <?php if (
                    mysqli_num_rows(
                        $garageResult
                    ) > 0
                ): ?>


                    <?php while (
                        $garage =
                        mysqli_fetch_assoc(
                            $garageResult
                        )
                    ): ?>


                        <?php

                        $status =
                            $garage[
                                "request_status"
                            ];

                        $disabled =
                            $status === "pending"
                            ||
                            $status === "accepted";

                        ?>


                        <label
                            class="
                                garage-select-card
                                <?php
                                echo $disabled
                                    ? "disabled"
                                    : "";
                                ?>
                            "
                        >

                            <div
                                class="garage-select-top"
                            >

                                <div
                                    class="garage-select-checkbox"
                                >

                                    <input
                                        type="checkbox"
                                        name="garages[]"
                                        value="<?php
                                        echo
                                        (int)
                                        $garage[
                                            "garage_id"
                                        ];
                                        ?>"
                                        <?php
                                        echo
                                        $disabled
                                            ? "disabled"
                                            : "";
                                        ?>
                                    >

                                </div>


                                <div
                                    class="garage-select-info"
                                >

                                    <h3>

                                        <?php
                                        echo htmlspecialchars(
                                            $garage[
                                                "garage_name"
                                            ]
                                        );
                                        ?>

                                    </h3>


                                    <p>

                                        📍

                                        <?php
                                        echo htmlspecialchars(
                                            $garage[
                                                "address"
                                            ]
                                        );
                                        ?>

                                    </p>


                                    <?php if (
                                        !empty(
                                            $garage[
                                                "district"
                                            ]
                                        )
                                    ): ?>

                                        <p>

                                            District:

                                            <?php
                                            echo htmlspecialchars(
                                                $garage[
                                                    "district"
                                                ]
                                            );
                                            ?>

                                        </p>

                                    <?php endif; ?>


                                    <?php if (
                                        !empty(
                                            $garage[
                                                "mobile_number"
                                            ]
                                        )
                                    ): ?>

                                        <p>

                                            📞

                                            <?php
                                            echo htmlspecialchars(
                                                $garage[
                                                    "mobile_number"
                                                ]
                                            );
                                            ?>

                                        </p>

                                    <?php endif; ?>


                                    <?php if ($status): ?>

                                        <span
                                            class="
                                                request-status
                                                status-<?php
                                                echo
                                                htmlspecialchars(
                                                    $status
                                                );
                                                ?>
                                            "
                                        >

                                            <?php

                                            if (
                                                $status
                                                ===
                                                "pending"
                                            ) {

                                                echo
                                                    "Request Pending";

                                            } elseif (
                                                $status
                                                ===
                                                "accepted"
                                            ) {

                                                echo
                                                    "Connected";

                                            } elseif (
                                                $status
                                                ===
                                                "rejected"
                                            ) {

                                                echo
                                                    "Rejected - Request Again";

                                            } elseif (
                                                $status
                                                ===
                                                "cancelled"
                                            ) {

                                                echo
                                                    "Cancelled - Request Again";

                                            }

                                            ?>

                                        </span>

                                    <?php endif; ?>


                                </div>

                            </div>

                        </label>


                    <?php endwhile; ?>


                <?php else: ?>


                    <div
                        class="empty-garages"
                    >

                        No approved garages
                        are currently available.

                    </div>


                <?php endif; ?>


            </div>


            <div
                class="garage-selection-actions"
            >

                <a
                    href="dashboard.php"
                    class="btn btn-secondary"
                >
                    Skip for Now
                </a>


                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Send Garage Requests
                </button>

            </div>

        </form>

    </div>

</div>

</body>

</html>