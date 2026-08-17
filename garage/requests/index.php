<?php

require_once '../auth.php';

$activePage = "requests";


// =====================================================
// HANDLE ACCEPT / REJECT
// =====================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $requestId =
        (int) ($_POST["request_id"] ?? 0);

    $action =
        $_POST["action"] ?? "";


    if (
        $requestId > 0
        &&
        in_array(
            $action,
            ["accept", "reject"],
            true
        )
    ) {

        $newStatus =
            $action === "accept"
            ? "accepted"
            : "rejected";


        $sql = "
            UPDATE garage_customer_requests

            SET
                request_status = ?,
                reviewed_by = ?,
                reviewed_at = NOW()

            WHERE request_id = ?
            AND garage_id = ?
            AND request_status = 'pending'
        ";

        $stmt =
            mysqli_prepare(
                $conn,
                $sql
            );

        if ($stmt) {

            $adminUserId =
                (int) $_SESSION["user_id"];

            mysqli_stmt_bind_param(
                $stmt,
                "siii",
                $newStatus,
                $adminUserId,
                $requestId,
                $garageId
            );

            mysqli_stmt_execute($stmt);
        }
    }


    header(
        "Location: index.php"
    );

    exit();
}


// =====================================================
// LOAD PENDING REQUESTS
// =====================================================

$sql = "
    SELECT
        r.request_id,
        r.requested_at,

        u.user_id,
        u.first_name,
        u.last_name,
        u.email,
        u.mobile_number

    FROM garage_customer_requests r

    INNER JOIN users u
        ON r.vehicle_owner_id =
           u.user_id

    WHERE r.garage_id = ?

    AND r.request_status =
        'pending'

    ORDER BY
        r.requested_at ASC
";

$stmt =
    mysqli_prepare(
        $conn,
        $sql
    );

if (!$stmt) {

    die(
        "Request query error: "
        .
        mysqli_error($conn)
    );
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $garageId
);

mysqli_stmt_execute($stmt);

$requestResult =
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
        Customer Requests - AutoTrack
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
        href="../../css/garage-admin.css"
    >

    <link
        rel="stylesheet"
        href="../../css/dashboard-layout.css"
    >

    <style>

        .request-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn-accept {
            background: #ecfdf3;
            color: #027a48;
        }

        .btn-accept:hover {
            background: #d1fadf;
        }

        .btn-reject {
            background: #fef3f2;
            color: #b42318;
        }

        .btn-reject:hover {
            background: #fee4e2;
        }

    </style>

</head>


<body>

<div class="app-shell">


    <?php
    require_once
        '../../includes/garage-sidebar.php';
    ?>


    <main class="main">


        <div class="request-header">

            <h1>
                Customer Requests
            </h1>

            <p class="muted">

                <?php
                echo htmlspecialchars(
                    $garage[
                        "garage_name"
                    ]
                );
                ?>

            </p>

        </div>


        <div class="card table-wrap">

            <table class="table">

                <thead>

                    <tr>

                        <th>
                            Customer
                        </th>

                        <th>
                            Email
                        </th>

                        <th>
                            Mobile
                        </th>

                        <th>
                            Requested
                        </th>

                        <th>
                            Actions
                        </th>

                    </tr>

                </thead>


                <tbody>


                <?php if (
                    mysqli_num_rows(
                        $requestResult
                    ) > 0
                ): ?>


                    <?php while (
                        $request =
                        mysqli_fetch_assoc(
                            $requestResult
                        )
                    ): ?>


                        <tr>

                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $request[
                                        "first_name"
                                    ]
                                    . " "
                                    .
                                    $request[
                                        "last_name"
                                    ]
                                );
                                ?>

                            </td>


                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $request[
                                        "email"
                                    ]
                                );
                                ?>

                            </td>


                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $request[
                                        "mobile_number"
                                    ]
                                );
                                ?>

                            </td>


                            <td>

                                <?php
                                echo date(
                                    "d M Y h:i A",
                                    strtotime(
                                        $request[
                                            "requested_at"
                                        ]
                                    )
                                );
                                ?>

                            </td>


                            <td>

                                <div
                                    class="request-actions"
                                >


                                    <form
                                        method="POST"
                                    >

                                        <input
                                            type="hidden"
                                            name="request_id"
                                            value="<?php
                                            echo
                                            (int)
                                            $request[
                                                "request_id"
                                            ];
                                            ?>"
                                        >

                                        <button
                                            type="submit"
                                            name="action"
                                            value="accept"
                                            class="
                                                btn
                                                btn-accept
                                            "
                                        >
                                            Accept
                                        </button>

                                    </form>


                                    <form
                                        method="POST"
                                    >

                                        <input
                                            type="hidden"
                                            name="request_id"
                                            value="<?php
                                            echo
                                            (int)
                                            $request[
                                                "request_id"
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
                            colspan="5"
                            class="empty-row"
                        >

                            No pending customer
                            requests.

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