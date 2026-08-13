<?php

require_once '../auth.php';

$message = "";

if (
    $_SERVER[
        "REQUEST_METHOD"
    ] === "POST"
) {

    $staffCode =
        trim(
            $_POST[
                "staff_code"
            ]
        );

    $firstName =
        trim(
            $_POST[
                "first_name"
            ]
        );

    $lastName =
        trim(
            $_POST[
                "last_name"
            ]
        );

    $email =
        trim(
            $_POST["email"]
        );

    $mobile =
        trim(
            $_POST[
                "mobile_number"
            ]
        );

    $position =
        trim(
            $_POST[
                "position"
            ]
        );

    $joinedDate =
        $_POST[
            "joined_date"
        ];

    $salary =
        (float)
        $_POST[
            "basic_salary"
        ];


    $sql = "
        INSERT INTO garage_staff
        (
            garage_id,
            staff_code,
            first_name,
            last_name,
            email,
            mobile_number,
            position,
            joined_date,
            basic_salary
        )

        VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?
        )
    ";


    $stmt =
        mysqli_prepare(
            $conn,
            $sql
        );


    mysqli_stmt_bind_param(
        $stmt,
        "isssssssd",
        $garageId,
        $staffCode,
        $firstName,
        $lastName,
        $email,
        $mobile,
        $position,
        $joinedDate,
        $salary
    );


    if (
        mysqli_stmt_execute(
            $stmt
        )
    ) {

        header(
            "Location: index.php"
        );

        exit();

    } else {

        $message =
            "Unable to add staff.";
    }

}

?>
<h1>Add Staff</h1>

<?php if ($message): ?>

    <div class="alert alert-danger">

        <?php
        echo htmlspecialchars(
            $message
        );
        ?>

    </div>

<?php endif; ?>


<form method="POST" class="card form-grid">

    <div class="field">

        <label>
            Staff Code
        </label>

        <input
            type="text"
            name="staff_code"
            placeholder="STF001"
            required
        >

    </div>


    <div class="field">

        <label>
            First Name
        </label>

        <input
            type="text"
            name="first_name"
            required
        >

    </div>


    <div class="field">

        <label>
            Last Name
        </label>

        <input
            type="text"
            name="last_name"
            required
        >

    </div>


    <div class="field">

        <label>
            Email
        </label>

        <input
            type="email"
            name="email"
        >

    </div>


    <div class="field">

        <label>
            Mobile Number
        </label>

        <input
            type="tel"
            name="mobile_number"
        >

    </div>


    <div class="field">

        <label>
            Position
        </label>

        <select name="position">

            <option value="Mechanic">
                Mechanic
            </option>

            <option value="Technician">
                Technician
            </option>

            <option value="Receptionist">
                Receptionist
            </option>

            <option value="Supervisor">
                Supervisor
            </option>

            <option value="Store Keeper">
                Store Keeper
            </option>

            <option value="Accountant">
                Accountant
            </option>

        </select>

    </div>


    <div class="field">

        <label>
            Joined Date
        </label>

        <input
            type="date"
            name="joined_date"
            required
        >

    </div>


    <div class="field">

        <label>
            Basic Salary
        </label>

        <input
            type="number"
            name="basic_salary"
            min="0"
            step="0.01"
            required
        >

    </div>


    <div>

        <button
            type="submit"
            class="btn btn-primary"
        >
            Save Staff
        </button>

        <a
            href="index.php"
            class="btn btn-secondary"
        >
            Cancel
        </a>

    </div>

</form>