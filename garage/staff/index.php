<?php

require_once '../auth.php';

$sql = "
    SELECT *
    FROM garage_staff
    WHERE garage_id = ?
    ORDER BY first_name, last_name
";

$stmt =
    mysqli_prepare(
        $conn,
        $sql
    );

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
<html>

<head>

    <title>
        Staff Management
    </title>
<link
    rel="stylesheet"
    href="../../css/garage-admin.css"
>
    <link
        rel="stylesheet"
        href="../../css/style.css"
    >

</head>

<body>

<main class="main">

    <div class="section-head">

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

                    <th>
                        Code
                    </th>

                    <th>
                        Name
                    </th>

                    <th>
                        Position
                    </th>

                    <th>
                        Mobile
                    </th>

                    <th>
                        Salary
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
                                $staff[
                                    "staff_code"
                                ]
                            );
                            ?>
                        </td>


                        <td>
                            <?php
                            echo htmlspecialchars(
                                $staff[
                                    "first_name"
                                ]
                                . " "
                                .
                                $staff[
                                    "last_name"
                                ]
                            );
                            ?>
                        </td>


                        <td>
                            <?php
                            echo htmlspecialchars(
                                $staff[
                                    "position"
                                ]
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
                                $staff[
                                    "basic_salary"
                                ],
                                2
                            );
                            ?>

                        </td>


                        <td>

                            <?php
                            echo ucfirst(
                                $staff[
                                    "employment_status"
                                ]
                            );
                            ?>

                        </td>


                        <td>

                            <a
                                href="edit.php?id=<?php
                                echo
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
                                ]
                                ===
                                "active"
                            ): ?>

                                <a
                                    href="deactivate.php?id=<?php
                                    echo
                                    $staff[
                                        "staff_id"
                                    ];
                                    ?>"
                                    class="btn btn-secondary"
                                >
                                    Deactivate
                                </a>

                            <?php endif; ?>

                        </td>

                    </tr>


                <?php endwhile; ?>


            <?php else: ?>

                <tr>

                    <td colspan="7">

                        No staff members
                        have been added.

                    </td>

                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

</main>

</body>
</html>