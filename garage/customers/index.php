<?php

require_once '../auth.php';

$activePage = "customers";


// =====================================================
// LOAD ACCEPTED CUSTOMERS FOR THIS GARAGE
// =====================================================

$sql = "
    SELECT
        r.request_id,
        r.vehicle_owner_id,
        r.request_status,

        u.first_name,
        u.last_name,
        u.email,
        u.mobile_number

    FROM garage_customer_requests r

    INNER JOIN users u
        ON r.vehicle_owner_id = u.user_id

    WHERE r.garage_id = ?
    AND r.request_status = 'accepted'

    ORDER BY
        u.first_name,
        u.last_name
";


$stmt =
    mysqli_prepare(
        $conn,
        $sql
    );


if (!$stmt) {

    die(
        "Customer query error: "
        . mysqli_error($conn)
    );
}


mysqli_stmt_bind_param(
    $stmt,
    "i",
    $garageId
);


mysqli_stmt_execute(
    $stmt
);


$customerResult =
    mysqli_stmt_get_result(
        $stmt
    );

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
        Customers - AutoTrack
    </title>

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="../../css/garage-admin.css"
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

        .customer-page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 20px;

            margin-bottom: 28px;
        }

        .customer-page-header h1 {
            margin: 0 0 6px;
        }

        .customer-page-header p {
            margin: 0;
        }

        .customer-name {
            font-weight: 700;
        }

        .customer-contact small {
            display: block;

            margin-top: 4px;

            color: #667085;
        }

        .status-badge {
            display: inline-flex;

            padding: 6px 10px;

            border-radius: 999px;

            background: #ecfdf3;

            color: #027a48;

            font-size: 12px;

            font-weight: 700;
        }

        .empty-row {
            text-align: center;

            padding: 30px !important;

            color: #667085;
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


        <div class="customer-page-header">

            <div>

                <h1>
                    Customers
                </h1>

                <p class="muted">

                    <?php
                    echo htmlspecialchars(
                        $garage["garage_name"]
                    );
                    ?>

                </p>

            </div>

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
                        $customerResult
                    ) > 0
                ): ?>


                    <?php while (
                        $customer =
                        mysqli_fetch_assoc(
                            $customerResult
                        )
                    ): ?>


                        <tr>


                            <td>

                                <span
                                    class="customer-name"
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $customer[
                                            "first_name"
                                        ]
                                        . " "
                                        .
                                        $customer[
                                            "last_name"
                                        ]
                                    );
                                    ?>

                                </span>

                            </td>


                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $customer[
                                        "email"
                                    ]
                                    ?? "-"
                                );
                                ?>

                            </td>


                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $customer[
                                        "mobile_number"
                                    ]
                                    ?? "-"
                                );
                                ?>

                            </td>


                            <td>

                                <span
                                    class="status-badge"
                                >
                                    Connected
                                </span>

                            </td>


                            <td>

                                <a
                                    href="view.php?id=<?php
                                    echo
                                    (int)
                                    $customer[
                                        "vehicle_owner_id"
                                    ];
                                    ?>"
                                    class="
                                        btn
                                        btn-secondary
                                    "
                                >
                                    View Details
                                </a>

                            </td>


                        </tr>


                    <?php endwhile; ?>


                <?php else: ?>


                    <tr>

                        <td
                            colspan="5"
                            class="empty-row"
                        >

                            No approved customers
                            are connected to this garage yet.

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