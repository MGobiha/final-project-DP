<?php

require_once '../auth.php';

$activePage = "staff";


// =====================================================
// LOAD STAFF
// =====================================================

$sql = "
    SELECT
        staff_id,
        staff_code,
        first_name,
        last_name,
        position,
        mobile_number,
        basic_salary,
        employment_status

    FROM garage_staff

    WHERE garage_id = ?

    ORDER BY first_name, last_name
";

$stmt = mysqli_prepare(
    $conn,
    $sql
);

if (!$stmt) {

    die(
        "Staff query error: "
        . mysqli_error($conn)
    );
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $garageId
);

mysqli_stmt_execute($stmt);

$staffResult =
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
        Staff Management - AutoTrack
    </title>

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="../../css/garage-admin.css"
    >

    <!-- <link
        rel="stylesheet"
        href="../../css/style.css"
    > -->

    <style>

        .staff-page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 28px;
        }

        .staff-page-header h1 {
            margin: 0 0 6px;
        }

        .staff-page-header p {
            margin: 0;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;

            padding: 6px 10px;

            border-radius: 999px;

            font-size: 12px;
            font-weight: 700;
        }

        .status-active {
            background: #ecfdf3;
            color: #027a48;
        }

        .status-inactive {
            background: #f2f4f7;
            color: #475467;
        }

        .status-resigned {
            background: #fef3f2;
            color: #b42318;
        }

        .action-group {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .empty-row {
            text-align: center;
            color: #667085;
            padding: 28px !important;
        }

        @media (max-width: 700px) {

            .staff-page-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .staff-page-header .btn {
                width: 100%;
            }

        }

    </style>

</head>


<body>

<div class="app-shell">


  <?php

$currentPage = "staff";

require_once "../../includes/garage-sidebar.php";

?>

<main class="garage-main">


        <div class="staff-page-header">

            <div>

                <h1>
                    Staff Management
                </h1>

                <p class="muted">

                    <?php
                    echo htmlspecialchars(
                        $garage["garage_name"]
                    );
                    ?>

                </p>

            </div>


            <a
                href="add.php"
                class="btn btn-primary"
            >
                + Add Staff
            </a>

        </div>


        <div class="card table-wrap">

            <table class="table">

                <thead>

                    <tr>

                        <th>Code</th>

                        <th>Name</th>

                        <th>Position</th>

                        <th>Mobile</th>

                        <th>Salary</th>

                        <th>Status</th>

                        <th>Actions</th>

                    </tr>

                </thead>


                <tbody>


                <?php if (
                    mysqli_num_rows(
                        $staffResult
                    ) > 0
                ): ?>


                    <?php while (
                        $staff =
                        mysqli_fetch_assoc(
                            $staffResult
                        )
                    ): ?>


                        <tr>

                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $staff["staff_code"]
                                );
                                ?>

                            </td>


                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $staff["first_name"]
                                    . " "
                                    . $staff["last_name"]
                                );
                                ?>

                            </td>


                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $staff["position"]
                                );
                                ?>

                            </td>


                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $staff[
                                        "mobile_number"
                                    ]
                                );
                                ?>

                            </td>


                            <td>

                                Rs.

                                <?php
                                echo number_format(
                                    (float)
                                    $staff["basic_salary"],
                                    2
                                );
                                ?>

                            </td>


                            <td>

                                <?php

                                $status =
                                    $staff[
                                        "employment_status"
                                    ];

                                $statusClass =
                                    "status-inactive";

                                if (
                                    $status === "active"
                                ) {

                                    $statusClass =
                                        "status-active";

                                } elseif (
                                    $status === "resigned"
                                ) {

                                    $statusClass =
                                        "status-resigned";
                                }

                                ?>

                                <span
                                    class="
                                        status-badge
                                        <?php
                                        echo $statusClass;
                                        ?>
                                    "
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        ucfirst($status)
                                    );
                                    ?>

                                </span>

                            </td>


                            <td>

                                <div
                                    class="action-group"
                                >

                                    <a
                                        href="edit.php?id=<?php
                                        echo
                                        (int)
                                        $staff[
                                            "staff_id"
                                        ];
                                        ?>"
                                        class="btn btn-secondary"
                                    >
                                        Edit
                                    </a>


                                    <?php if (
                                        $staff[
                                            "employment_status"
                                        ] === "active"
                                    ): ?>


                                        <a
                                            href="deactivate.php?id=<?php
                                            echo
                                            (int)
                                            $staff[
                                                "staff_id"
                                            ];
                                            ?>"
                                            class="btn btn-secondary"
                                            onclick="
                                                return confirm(
                                                    'Are you sure you want to deactivate this staff member?'
                                                );
                                            "
                                        >
                                            Deactivate
                                        </a>


                                    <?php endif; ?>


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

                            No staff members
                            have been added yet.

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