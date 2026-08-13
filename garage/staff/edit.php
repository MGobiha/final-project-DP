<?php

require_once '../auth.php';

$staffId =
    isset($_GET['id'])
    ? (int) $_GET['id']
    : 0;

if ($staffId <= 0) {
    die("Invalid staff ID.");
}


// Load staff

$sql = "
    SELECT *
    FROM garage_staff
    WHERE staff_id = ?
    AND garage_id = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "ii",
    $staffId,
    $garageId
);

mysqli_stmt_execute($stmt);

$result =
    mysqli_stmt_get_result($stmt);

$staff =
    mysqli_fetch_assoc($result);

if (!$staff) {
    die("Staff member not found.");
}


$message = "";


// Update

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $firstName =
        trim($_POST["first_name"]);

    $lastName =
        trim($_POST["last_name"]);

    $email =
        trim($_POST["email"]);

    $mobile =
        trim($_POST["mobile_number"]);

    $nic =
        trim($_POST["nic_number"]);

    $address =
        trim($_POST["address"]);

    $position =
        trim($_POST["position"]);

    $joinedDate =
        $_POST["joined_date"];

    $salary =
        (float) $_POST["basic_salary"];

    $status =
        $_POST["employment_status"];


    $sql = "
        UPDATE garage_staff
        SET
            first_name = ?,
            last_name = ?,
            email = ?,
            mobile_number = ?,
            nic_number = ?,
            address = ?,
            position = ?,
            joined_date = ?,
            basic_salary = ?,
            employment_status = ?
        WHERE staff_id = ?
        AND garage_id = ?
    ";

    $stmt =
        mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "ssssssssd sii",
        $firstName,
        $lastName,
        $email,
        $mobile,
        $nic,
        $address,
        $position,
        $joinedDate,
        $salary,
        $status,
        $staffId,
        $garageId
    );

    if (mysqli_stmt_execute($stmt)) {

        header(
            "Location: index.php"
        );

        exit();

    } else {

        $message =
            "Unable to update staff information.";
    }
}

?>

<!doctype html>
<html>
<head>

    <meta charset="utf-8">

    <title>Edit Staff</title>

    <link
        rel="stylesheet"
        href="../../css/garage-admin.css"
    >

</head>

<body>

<div class="app-shell">

    <main class="main">

        <div class="section-head">

            <div>

                <h1>Edit Staff</h1>

                <p class="muted">
                    <?php
                    echo htmlspecialchars(
                        $garage["garage_name"]
                    );
                    ?>
                </p>

            </div>

            <a
                href="index.php"
                class="btn btn-secondary"
            >
                Back
            </a>

        </div>


        <?php if ($message): ?>

            <div class="alert alert-danger">

                <?php
                echo htmlspecialchars(
                    $message
                );
                ?>

            </div>

        <?php endif; ?>


        <form
            method="POST"
            class="card form-grid"
        >

            <div class="field">

                <label>Staff Code</label>

                <input
                    type="text"
                    value="<?php
                    echo htmlspecialchars(
                        $staff["staff_code"]
                    );
                    ?>"
                    disabled
                >

            </div>


            <div class="field">

                <label>First Name</label>

                <input
                    type="text"
                    name="first_name"
                    value="<?php
                    echo htmlspecialchars(
                        $staff["first_name"]
                    );
                    ?>"
                    required
                >

            </div>


            <div class="field">

                <label>Last Name</label>

                <input
                    type="text"
                    name="last_name"
                    value="<?php
                    echo htmlspecialchars(
                        $staff["last_name"]
                    );
                    ?>"
                    required
                >

            </div>


            <div class="field">

                <label>Email</label>

                <input
                    type="email"
                    name="email"
                    value="<?php
                    echo htmlspecialchars(
                        $staff["email"]
                    );
                    ?>"
                >

            </div>


            <div class="field">

                <label>Mobile Number</label>

                <input
                    type="text"
                    name="mobile_number"
                    value="<?php
                    echo htmlspecialchars(
                        $staff["mobile_number"]
                    );
                    ?>"
                >

            </div>


            <div class="field">

                <label>NIC Number</label>

                <input
                    type="text"
                    name="nic_number"
                    value="<?php
                    echo htmlspecialchars(
                        $staff["nic_number"]
                    );
                    ?>"
                >

            </div>


            <div class="field">

                <label>Address</label>

                <textarea
                    name="address"
                ><?php
                echo htmlspecialchars(
                    $staff["address"]
                );
                ?></textarea>

            </div>


            <div class="field">

                <label>Position</label>

                <select name="position">

                    <?php

                    $positions = [
                        "Mechanic",
                        "Technician",
                        "Receptionist",
                        "Supervisor",
                        "Store Keeper",
                        "Accountant"
                    ];

                    foreach (
                        $positions
                        as $position
                    ):

                    ?>

                        <option
                            value="<?php
                            echo $position;
                            ?>"
                            <?php
                            echo
                            $staff["position"]
                            === $position
                            ? "selected"
                            : "";
                            ?>
                        >
                            <?php
                            echo $position;
                            ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <div class="field">

                <label>Joined Date</label>

                <input
                    type="date"
                    name="joined_date"
                    value="<?php
                    echo htmlspecialchars(
                        $staff["joined_date"]
                    );
                    ?>"
                    required
                >

            </div>


            <div class="field">

                <label>Basic Salary</label>

                <input
                    type="number"
                    name="basic_salary"
                    step="0.01"
                    min="0"
                    value="<?php
                    echo htmlspecialchars(
                        $staff["basic_salary"]
                    );
                    ?>"
                    required
                >

            </div>


            <div class="field">

                <label>
                    Employment Status
                </label>

                <select
                    name="employment_status"
                >

                    <option
                        value="active"
                        <?php
                        echo
                        $staff[
                            "employment_status"
                        ]
                        === "active"
                        ? "selected"
                        : "";
                        ?>
                    >
                        Active
                    </option>

                    <option
                        value="inactive"
                        <?php
                        echo
                        $staff[
                            "employment_status"
                        ]
                        === "inactive"
                        ? "selected"
                        : "";
                        ?>
                    >
                        Inactive
                    </option>

                    <option
                        value="resigned"
                        <?php
                        echo
                        $staff[
                            "employment_status"
                        ]
                        === "resigned"
                        ? "selected"
                        : "";
                        ?>
                    >
                        Resigned
                    </option>

                </select>

            </div>


            <div>

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Update Staff
                </button>

            </div>

        </form>

    </main>

</div>

</body>
</html>