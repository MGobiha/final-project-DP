<?php

session_start();

require_once '../../config/database.php';


// =====================================================
// CHECK SYSTEM ADMIN
// =====================================================

if (
    !isset($_SESSION["user_id"])
    ||
    !isset($_SESSION["role"])
    ||
    $_SESSION["role"] !== "system_admin"
) {

    header(
        "Location: ../../login.php"
    );

    exit();
}


// =====================================================
// MESSAGES
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
// APPROVE / REJECT
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


    if ($garageId <= 0) {

        $_SESSION["admin_error"] =
            "Invalid garage request.";

        header(
            "Location: requests.php"
        );

        exit();
    }


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
            "Invalid action.";

        header(
            "Location: requests.php"
        );

        exit();
    }


    // =================================================
    // APPROVE
    // =================================================

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


    // =================================================
    // REJECT
    // =================================================

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
            "Unable to prepare approval query: "
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
                "Garage request was not found or already reviewed.";
        }

    } else {

        $_SESSION["admin_error"] =
            "Unable to update garage: "
            . mysqli_stmt_error($stmt);
    }


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
        g.garage_name,
        g.owner_name,
        g.email,
        g.mobile_number,
        g.telephone,
        g.address,
        g.city,
        g.district,
        g.description,
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
        Garage Approval Requests - AutoTrack
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

        .request-actions {
            display: flex;
            gap: 8px;
            flex-wrap: nowrap;
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

            padding: 6px 10px;

            border-radius: 999px;

            background: #fef3c7;

            color: #92400e;

            font-size: 12px;
            font-weight: 700;
        }

        .empty-row {
            text-align: center;
            padding: 30px !important;
            color: #667085;
        }

        .description-cell {
            max-width: 250px;
            white-space: normal !important;
            line-height: 1.5;
        }

    </style>

</head>


<body>

<div class="app-shell">


    <!-- =====================================================
         SIDEBAR
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
                📥
                <span>
                    Garage Requests
                </span>
            </a>


            <a
                href="../garages.php"
            >
                🏢
                <span>
                    Garages
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
         MAIN
         ===================================================== -->

    <main class="main">


        <div class="page-header">

            <div>

                <h1>
                    Garage Approval Requests
                </h1>

                <p>
                    Review newly registered garages.
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
             TABLE
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
                            Description
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
                        $garage =
                        mysqli_fetch_assoc(
                            $garageResult
                        )
                    ): ?>


                        <tr>


                            <!-- GARAGE -->

                            <td>

                                <strong>

                                    <?php
                                    echo htmlspecialchars(
                                        $garage[
                                            "garage_name"
                                        ]
                                    );
                                    ?>

                                </strong>

                            </td>


                            <!-- OWNER -->

                            <td>

                                <?php

                                $ownerName =
                                    trim(
                                        $garage[
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
                                                $garage[
                                                    "first_name"
                                                ]
                                                ?? ""
                                            )
                                            . " "
                                            .
                                            (
                                                $garage[
                                                    "last_name"
                                                ]
                                                ?? ""
                                            )
                                        );
                                }


                                echo htmlspecialchars(
                                    $ownerName
                                );

                                ?>

                            </td>


                            <!-- CONTACT -->

                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $garage[
                                        "mobile_number"
                                    ]
                                    ?? "-"
                                );
                                ?>


                                <?php if (
                                    !empty(
                                        $garage[
                                            "email"
                                        ]
                                    )
                                ): ?>

                                    <br>

                                    <small>

                                        <?php
                                        echo htmlspecialchars(
                                            $garage[
                                                "email"
                                            ]
                                        );
                                        ?>

                                    </small>

                                <?php endif; ?>

                            </td>


                            <!-- LOCATION -->

                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $garage[
                                        "address"
                                    ]
                                    ?? "-"
                                );
                                ?>


                                <?php if (
                                    !empty(
                                        $garage[
                                            "city"
                                        ]
                                    )
                                ): ?>

                                    <br>

                                    <?php
                                    echo htmlspecialchars(
                                        $garage[
                                            "city"
                                        ]
                                    );
                                    ?>

                                <?php endif; ?>


                                <?php if (
                                    !empty(
                                        $garage[
                                            "district"
                                        ]
                                    )
                                ): ?>

                                    <br>

                                    <strong>

                                        <?php
                                        echo htmlspecialchars(
                                            $garage[
                                                "district"
                                            ]
                                        );
                                        ?>

                                    </strong>

                                <?php endif; ?>

                            </td>


                            <!-- DESCRIPTION -->

                            <td class="description-cell">

                                <?php
                                echo htmlspecialchars(
                                    $garage[
                                        "description"
                                    ]
                                    ?? "-"
                                );
                                ?>

                            </td>


                            <!-- DATE -->

                            <td>

                                <?php

                                if (
                                    !empty(
                                        $garage[
                                            "created_at"
                                        ]
                                    )
                                ) {

                                    echo date(
                                        "d M Y",
                                        strtotime(
                                            $garage[
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
                                    class="status-pending"
                                >
                                    Pending
                                </span>

                            </td>


                            <!-- ACTIONS -->

                            <td>

                                <div class="request-actions">


                                    <!-- APPROVE -->

                                    <form
                                        method="POST"
                                        onsubmit="
                                            return confirm(
                                                'Approve this garage registration?'
                                            );
                                        "
                                    >

                                        <input
                                            type="hidden"
                                            name="garage_id"
                                            value="<?php
                                            echo
                                            (int)
                                            $garage[
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
                                            $garage[
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
                            colspan="8"
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