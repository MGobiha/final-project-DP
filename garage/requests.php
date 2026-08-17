<?php

session_start();

require_once '../../config/database.php';


// =====================================================
// CHECK SYSTEM ADMIN LOGIN
// =====================================================

if (
    !isset($_SESSION["user_id"])
    ||
    ($_SESSION["role"] ?? "")
    !== "system_admin"
) {

    header(
        "Location: ../../login.php"
    );

    exit();
}


$adminUserId =
    (int) $_SESSION["user_id"];


// =====================================================
// SESSION MESSAGES
// =====================================================

$successMessage =
    $_SESSION["admin_success"]
    ?? "";

$errorMessage =
    $_SESSION["admin_error"]
    ?? "";

unset(
    $_SESSION["admin_success"],
    $_SESSION["admin_error"]
);


// =====================================================
// APPROVE / REJECT GARAGE
// =====================================================

if (
    $_SERVER["REQUEST_METHOD"]
    === "POST"
) {

    $garageId =
        (int)
        ($_POST["garage_id"] ?? 0);

    $action =
        $_POST["action"] ?? "";


    // -------------------------------------------------
    // Validate garage
    // -------------------------------------------------

    if ($garageId <= 0) {

        $_SESSION["admin_error"] =
            "Invalid garage request.";

        header(
            "Location: requests.php"
        );

        exit();
    }


    // -------------------------------------------------
    // Validate action
    // -------------------------------------------------

    if (
        !in_array(
            $action,
            [
                "approve",
                "reject"
            ],
            true
        )
    ) {

        $_SESSION["admin_error"] =
            "Invalid approval action.";

        header(
            "Location: requests.php"
        );

        exit();
    }


    // =====================================================
    // APPROVE GARAGE
    // =====================================================

    if (
        $action === "approve"
    ) {

        $sql = "
            UPDATE garages

            SET
                approval_status = 'approved',
                active_status = 1,
                updated_at = CURRENT_TIMESTAMP

            WHERE garage_id = ?
            AND approval_status = 'pending'
        ";

    }


    // =====================================================
    // REJECT GARAGE
    // =====================================================

    else {

        $sql = "
            UPDATE garages

            SET
                approval_status = 'rejected',
                active_status = 0,
                updated_at = CURRENT_TIMESTAMP

            WHERE garage_id = ?
            AND approval_status = 'pending'
        ";
    }


    $stmt =
        mysqli_prepare(
            $conn,
            $sql
        );


    if (!$stmt) {

        $_SESSION["admin_error"] =
            "Unable to prepare garage approval: "
            . mysqli_error($conn);

        header(
            "Location: requests.php"
        );

        exit();
    }


    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $garageId
    );


    if (
        mysqli_stmt_execute(
            $stmt
        )
    ) {

        if (
            mysqli_stmt_affected_rows(
                $stmt
            ) > 0
        ) {

            if (
                $action === "approve"
            ) {

                $_SESSION["admin_success"] =
                    "Garage approved successfully.";

            } else {

                $_SESSION["admin_success"] =
                    "Garage rejected successfully.";
            }

        } else {

            $_SESSION["admin_error"] =
                "Garage request was not found or has already been reviewed.";
        }

    } else {

        $_SESSION["admin_error"] =
            "Unable to update garage: "
            . mysqli_stmt_error($stmt);
    }


    mysqli_stmt_close($stmt);


    header(
        "Location: requests.php"
    );

    exit();
}


// =====================================================
// LOAD PENDING GARAGES
// =====================================================

$sql = "
    SELECT

        g.garage_id,
        g.owner_user_id,
        g.garage_name,
        g.owner_name,
        g.email,
        g.mobile_number,
        g.telephone,
        g.address,
        g.city,
        g.district,
        g.latitude,
        g.longitude,
        g.opening_time,
        g.closing_time,
        g.description,
        g.image,
        g.approval_status,
        g.active_status,
        g.created_at,

        u.first_name,
        u.last_name

    FROM garages g

    LEFT JOIN users u
        ON g.owner_user_id =
           u.user_id

    WHERE g.approval_status =
        'pending'

    ORDER BY
        g.created_at ASC
";


$garageResult =
    mysqli_query(
        $conn,
        $sql
    );


if (!$garageResult) {

    die(
        "Garage query error: "
        . mysqli_error($conn)
    );
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
        Garage Requests - AutoTrack
    </title>


    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >


    <link
        rel="stylesheet"
        href="../../css/style.css"
    >


    <link
        rel="stylesheet"
        href="../../css/dashboard-layout.css"
    >


    <style>

        .garage-request-info {
            min-width: 190px;
        }

        .garage-request-info strong {
            display: block;
            margin-bottom: 5px;
        }

        .garage-request-info small {
            color: #667085;
        }


        .request-address {
            max-width: 280px;
            line-height: 1.5;
        }


        .request-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }


        .request-actions form {
            margin: 0;
        }


        .btn-approve {
            background: #16a34a;
            color: #ffffff;
        }


        .btn-approve:hover {
            background: #15803d;
        }


        .btn-reject {
            background: #fee2e2;
            color: #991b1b;
        }


        .btn-reject:hover {
            background: #fecaca;
        }


        .status-pending {
            display: inline-flex;

            padding:
                5px
                9px;

            border-radius: 999px;

            background: #fef3c7;

            color: #92400e;

            font-size: 12px;

            font-weight: 700;
        }


        .garage-description {
            max-width: 280px;

            color: #667085;

            font-size: 13px;

            line-height: 1.5;
        }


        .empty-row {
            text-align: center;

            padding:
                35px !important;

            color: #667085;
        }


        @media (max-width: 900px) {

            .request-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .request-actions .btn {
                width: 100%;
            }

        }

    </style>

</head>


<body>

<div class="app-shell">


    <!-- =====================================================
         ADMIN SIDEBAR
         ===================================================== -->

    <aside class="sidebar">

        <div class="brand">

            <div class="brand-badge">
                A
            </div>

            <span>
                AutoTrack Admin
            </span>

        </div>


        <nav class="nav">


            <a
                href="../dashboard.php"
            >
                🏠
                <span>
                    Dashboard
                </span>
            </a>


            <a
                href="requests.php"
                class="active"
            >
                🏢
                <span>
                    Garage Requests
                </span>
            </a>


            <a
                href="../garages.php"
            >
                🏬
                <span>
                    All Garages
                </span>
            </a>


            <a
                href="../users.php"
            >
                👥
                <span>
                    Users
                </span>
            </a>


            <a
                href="../../logout.php"
            >
                🚪
                <span>
                    Logout
                </span>
            </a>


        </nav>

    </aside>


    <!-- =====================================================
         MAIN CONTENT
         ===================================================== -->

    <main class="main">


        <div class="page-header">

            <div>

                <h1>
                    Garage Approval Requests
                </h1>

                <p>
                    Review newly registered
                    garages before they become
                    available to vehicle owners.
                </p>

            </div>

        </div>


        <!-- =================================================
             SUCCESS MESSAGE
             ================================================= -->

        <?php if (
            !empty(
                $successMessage
            )
        ): ?>

            <div
                class="
                    alert
                    alert-success
                "
            >

                <?php
                echo htmlspecialchars(
                    $successMessage
                );
                ?>

            </div>

        <?php endif; ?>


        <!-- =================================================
             ERROR MESSAGE
             ================================================= -->

        <?php if (
            !empty(
                $errorMessage
            )
        ): ?>

            <div
                class="
                    alert
                    alert-danger
                "
            >

                <?php
                echo htmlspecialchars(
                    $errorMessage
                );
                ?>

            </div>

        <?php endif; ?>


        <!-- =================================================
             GARAGE REQUEST TABLE
             ================================================= -->

        <div class="card table-wrap">

            <table class="table">

                <thead>

                    <tr>

                        <th>
                            Garage
                        </th>

                        <th>
                            Owner
                        </th>

                        <th>
                            Contact
                        </th>

                        <th>
                            Location
                        </th>

                        <th>
                            Registered
                        </th>

                        <th>
                            Status
                        </th>

                        <th>
                            Actions
                        </th>

                    </tr>

                </thead>


                <tbody>


                <?php if (
                    mysqli_num_rows(
                        $garageResult
                    ) > 0
                ): ?>


                    <?php while (
                        $garageRequest =
                        mysqli_fetch_assoc(
                            $garageResult
                        )
                    ): ?>


                        <tr>


                            <!-- GARAGE -->

                            <td>

                                <div
                                    class="
                                        garage-request-info
                                    "
                                >

                                    <strong>

                                        <?php
                                        echo
                                        htmlspecialchars(
                                            $garageRequest[
                                                "garage_name"
                                            ]
                                        );
                                        ?>

                                    </strong>


                                    <?php if (
                                        !empty(
                                            $garageRequest[
                                                "description"
                                            ]
                                        )
                                    ): ?>

                                        <small>

                                            <?php
                                            echo
                                            htmlspecialchars(
                                                mb_strimwidth(
                                                    $garageRequest[
                                                        "description"
                                                    ],
                                                    0,
                                                    80,
                                                    "..."
                                                )
                                            );
                                            ?>

                                        </small>

                                    <?php endif; ?>

                                </div>

                            </td>


                            <!-- OWNER -->

                            <td>

                                <?php

                                $ownerName =
                                    trim(
                                        $garageRequest[
                                            "owner_name"
                                        ]
                                        ?? ""
                                    );


                                if (
                                    $ownerName === ""
                                ) {

                                    $ownerName =
                                        trim(
                                            (
                                                $garageRequest[
                                                    "first_name"
                                                ]
                                                ?? ""
                                            )
                                            . " "
                                            .
                                            (
                                                $garageRequest[
                                                    "last_name"
                                                ]
                                                ?? ""
                                            )
                                        );
                                }


                                echo
                                htmlspecialchars(
                                    $ownerName
                                );

                                ?>

                            </td>


                            <!-- CONTACT -->

                            <td>

                                <?php
                                echo
                                htmlspecialchars(
                                    $garageRequest[
                                        "mobile_number"
                                    ]
                                    ?? ""
                                );
                                ?>


                                <?php if (
                                    !empty(
                                        $garageRequest[
                                            "telephone"
                                        ]
                                    )
                                ): ?>

                                    <br>

                                    <small>
                                        Tel:
                                        <?php
                                        echo
                                        htmlspecialchars(
                                            $garageRequest[
                                                "telephone"
                                            ]
                                        );
                                        ?>
                                    </small>

                                <?php endif; ?>


                                <br>


                                <small>

                                    <?php
                                    echo
                                    htmlspecialchars(
                                        $garageRequest[
                                            "email"
                                        ]
                                        ?? ""
                                    );
                                    ?>

                                </small>

                            </td>


                            <!-- LOCATION -->

                            <td>

                                <div
                                    class="
                                        request-address
                                    "
                                >

                                    <?php
                                    echo
                                    htmlspecialchars(
                                        $garageRequest[
                                            "address"
                                        ]
                                        ?? ""
                                    );
                                    ?>


                                    <?php if (
                                        !empty(
                                            $garageRequest[
                                                "city"
                                            ]
                                        )
                                    ): ?>

                                        <br>

                                        <?php
                                        echo
                                        htmlspecialchars(
                                            $garageRequest[
                                                "city"
                                            ]
                                        );
                                        ?>

                                    <?php endif; ?>


                                    <?php if (
                                        !empty(
                                            $garageRequest[
                                                "district"
                                            ]
                                        )
                                    ): ?>

                                        <br>

                                        <strong>

                                            <?php
                                            echo
                                            htmlspecialchars(
                                                $garageRequest[
                                                    "district"
                                                ]
                                            );
                                            ?>

                                        </strong>

                                    <?php endif; ?>

                                </div>

                            </td>


                            <!-- CREATED DATE -->

                            <td>

                                <?php

                                if (
                                    !empty(
                                        $garageRequest[
                                            "created_at"
                                        ]
                                    )
                                ) {

                                    echo
                                    date(
                                        "d M Y",
                                        strtotime(
                                            $garageRequest[
                                                "created_at"
                                            ]
                                        )
                                    );

                                } else {

                                    echo "-";
                                }

                                ?>

                            </td>


                            <!-- STATUS -->

                            <td>

                                <span
                                    class="
                                        status-pending
                                    "
                                >
                                    Pending
                                </span>

                            </td>


                            <!-- ACTIONS -->

                            <td>

                                <div
                                    class="
                                        request-actions
                                    "
                                >


                                    <!-- APPROVE -->

                                    <form
                                        method="POST"
                                        onsubmit="
                                            return confirm(
                                                'Approve this garage?'
                                            );
                                        "
                                    >

                                        <input
                                            type="hidden"
                                            name="garage_id"
                                            value="<?php
                                            echo
                                            (int)
                                            $garageRequest[
                                                "garage_id"
                                            ];
                                            ?>"
                                        >


                                        <button
                                            type="submit"
                                            name="action"
                                            value="approve"
                                            class="
                                                btn
                                                btn-approve
                                            "
                                        >
                                            Approve
                                        </button>

                                    </form>


                                    <!-- REJECT -->

                                    <form
                                        method="POST"
                                        onsubmit="
                                            return confirm(
                                                'Reject this garage registration?'
                                            );
                                        "
                                    >

                                        <input
                                            type="hidden"
                                            name="garage_id"
                                            value="<?php
                                            echo
                                            (int)
                                            $garageRequest[
                                                "garage_id"
                                            ];
                                            ?>"
                                        >


                                        <button
                                            type="submit"
                                            name="action"
                                            value="reject"
                                            class="
                                                btn
                                                btn-reject
                                            "
                                        >
                                            Reject
                                        </button>

                                    </form>


                                </div>

                            </td>


                        </tr>


                    <?php endwhile; ?>


                <?php else: ?>


                    <tr>

                        <td
                            colspan="7"
                            class="empty-row"
                        >

                            No pending garage
                            registration requests.

                        </td>

                    </tr>


                <?php endif; ?>


                </tbody>

            </table>

        </div>


    </main>

</div>


</body>

</html>