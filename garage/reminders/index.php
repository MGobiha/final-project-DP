<?php

require_once '../auth.php';

$currentPage = "reminders";


// =====================================================
// GET CUSTOMER REMINDERS FOR THIS GARAGE
// =====================================================

$sql = "
    SELECT

        ms.schedule_id,
        ms.maintenance_type,
        ms.description,
        ms.due_date,
        ms.due_mileage,
        ms.schedule_status,

        v.vehicle_id,
        v.registration_number,
        v.make,
        v.model,
        v.current_mileage,

        u.user_id,
        u.first_name,
        u.last_name,
        u.email,
        u.mobile_number

    FROM garage_customer_requests gcr

    INNER JOIN users u
        ON gcr.vehicle_owner_id = u.user_id

    INNER JOIN vehicles v
        ON v.user_id = u.user_id

    INNER JOIN maintenance_schedule ms
        ON ms.vehicle_id = v.vehicle_id

    WHERE gcr.garage_id = ?

    AND gcr.request_status = 'approved'

    AND ms.schedule_status IN (
        'due',
        'overdue'
    )

    ORDER BY

        FIELD(
            ms.schedule_status,
            'overdue',
            'due'
        ),

        ms.due_date ASC,
        ms.due_mileage ASC
";


$stmt =
    mysqli_prepare(
        $conn,
        $sql
    );


if (!$stmt) {

    die(
        "Customer reminder query failed: "
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


$reminderResult =
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
        Customer Reminders - AutoTrack
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
        href="../../css/dashboard-layout.css"
    >


    <style>

        .page-head {
            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 20px;

            margin-bottom: 26px;
        }

        .page-head h1 {
            margin: 0 0 6px;
        }

        .page-head p {
            margin: 0;
            color: #667085;
        }


        .reminder-table-wrap {
            overflow-x: auto;
        }


        .status-badge {
            display: inline-flex;

            align-items: center;

            padding: 6px 10px;

            border-radius: 999px;

            font-size: 12px;

            font-weight: 700;
        }


        .status-due {
            background: #fef3c7;
            color: #92400e;
        }


        .status-overdue {
            background: #fef2f2;
            color: #b91c1c;
        }


        .customer-name {
            font-weight: 700;
        }


        .customer-sub {
            margin-top: 4px;

            color: #667085;

            font-size: 12px;
        }


        .vehicle-name {
            font-weight: 600;
        }


        .empty-state {
            padding: 35px;

            text-align: center;

            color: #667085;
        }

    </style>

</head>


<body>


<div class="app-shell">


    <?php
    require_once '../../includes/garage-sidebar.php';
    ?>


    <main class="garage-main">


        <div class="page-head">

            <div>

                <h1>
                    Customer Reminders
                </h1>

                <p>
                    Maintenance reminders for customers
                    connected to
                    <?php
                    echo htmlspecialchars(
                        $garage["garage_name"]
                    );
                    ?>.
                </p>

            </div>

        </div>


        <div class="card reminder-table-wrap">


            <table class="table">


                <thead>

                    <tr>

                        <th>
                            Customer
                        </th>

                        <th>
                            Vehicle
                        </th>

                        <th>
                            Reminder
                        </th>

                        <th>
                            Current
                        </th>

                        <th>
                            Due
                        </th>

                        <th>
                            Status
                        </th>

                        <th>
                            Action
                        </th>

                    </tr>

                </thead>


                <tbody>


                <?php if (
                    mysqli_num_rows(
                        $reminderResult
                    ) > 0
                ): ?>


                    <?php while (
                        $row =
                        mysqli_fetch_assoc(
                            $reminderResult
                        )
                    ): ?>


                        <tr>


                            <td>

                                <div class="customer-name">

                                    <?php

                                    echo htmlspecialchars(
                                        $row["first_name"]
                                        . " "
                                        . $row["last_name"]
                                    );

                                    ?>

                                </div>


                                <div class="customer-sub">

                                    <?php
                                    echo htmlspecialchars(
                                        $row["mobile_number"]
                                    );
                                    ?>

                                </div>

                            </td>


                            <td>

                                <div class="vehicle-name">

                                    <?php

                                    echo htmlspecialchars(
                                        $row["make"]
                                        . " "
                                        . $row["model"]
                                    );

                                    ?>

                                </div>


                                <div class="customer-sub">

                                    <?php

                                    echo htmlspecialchars(
                                        $row[
                                            "registration_number"
                                        ]
                                    );

                                    ?>

                                </div>

                            </td>


                            <td>

                                <?php

                                echo htmlspecialchars(
                                    $row[
                                        "maintenance_type"
                                    ]
                                );

                                ?>

                            </td>


                            <td>

                                <?php

                                if (
                                    $row[
                                        "current_mileage"
                                    ]
                                    !== null
                                ) {

                                    echo number_format(
                                        $row[
                                            "current_mileage"
                                        ]
                                    )
                                    . " km";

                                } else {

                                    echo "-";
                                }

                                ?>

                            </td>


                            <td>

                                <?php

                                if (
                                    !empty(
                                        $row[
                                            "due_mileage"
                                        ]
                                    )
                                ) {

                                    echo number_format(
                                        $row[
                                            "due_mileage"
                                        ]
                                    )
                                    . " km";

                                } elseif (
                                    !empty(
                                        $row[
                                            "due_date"
                                        ]
                                    )
                                ) {

                                    echo date(
                                        "d M Y",
                                        strtotime(
                                            $row[
                                                "due_date"
                                            ]
                                        )
                                    );

                                } else {

                                    echo "-";
                                }

                                ?>

                            </td>


                            <td>

                                <?php if (
                                    $row[
                                        "schedule_status"
                                    ]
                                    === "overdue"
                                ): ?>

                                    <span
                                        class="
                                            status-badge
                                            status-overdue
                                        "
                                    >
                                        Overdue
                                    </span>

                                <?php else: ?>

                                    <span
                                        class="
                                            status-badge
                                            status-due
                                        "
                                    >
                                        Due Soon
                                    </span>

                                <?php endif; ?>

                            </td>


                            <td>

                                <a
                                    href="send.php?schedule_id=<?php
                                    echo
                                    (int)
                                    $row[
                                        "schedule_id"
                                    ];
                                    ?>"
                                    class="btn btn-primary"
                                >
                                    Send Reminder
                                </a>

                            </td>


                        </tr>


                    <?php endwhile; ?>


                <?php else: ?>


                    <tr>

                        <td
                            colspan="7"
                            class="empty-state"
                        >

                            No due or overdue
                            customer maintenance reminders.

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