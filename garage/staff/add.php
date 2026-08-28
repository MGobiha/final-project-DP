<?php

require_once '../auth.php';

$currentPage = "staff";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $staffCode =
        trim($_POST["staff_code"] ?? "");

    $firstName =
        trim($_POST["first_name"] ?? "");

    $lastName =
        trim($_POST["last_name"] ?? "");

    $email =
        trim($_POST["email"] ?? "");

    $mobile =
        trim($_POST["mobile_number"] ?? "");

    $position =
        trim($_POST["position"] ?? "");

    $joinedDate =
        trim($_POST["joined_date"] ?? "");

    $salary =
        isset($_POST["basic_salary"])
            ? (float) $_POST["basic_salary"]
            : 0;


    // ======================================================
    // VALIDATION
    // ======================================================

    if (
        $staffCode === "" ||
        $firstName === "" ||
        $lastName === "" ||
        $position === "" ||
        $joinedDate === ""
    ) {

        $message =
            "Please complete all required fields.";

    } else {


        // ==================================================
        // CHECK DUPLICATE STAFF CODE
        // ==================================================

        $checkSql = "
            SELECT staff_id
            FROM garage_staff
            WHERE staff_code = ?
            LIMIT 1
        ";

        $checkStmt =
            mysqli_prepare(
                $conn,
                $checkSql
            );

        if (!$checkStmt) {

            $message =
                "Unable to check staff code: "
                . mysqli_error($conn);

        } else {

            mysqli_stmt_bind_param(
                $checkStmt,
                "s",
                $staffCode
            );

            mysqli_stmt_execute(
                $checkStmt
            );

            $checkResult =
                mysqli_stmt_get_result(
                    $checkStmt
                );

            $existingStaff =
                mysqli_fetch_assoc(
                    $checkResult
                );

            mysqli_stmt_close(
                $checkStmt
            );


            // ==============================================
            // DUPLICATE FOUND
            // ==============================================

            if ($existingStaff) {

                $message =
                    "Staff Code "
                    . htmlspecialchars($staffCode)
                    . " already exists. Please use another staff code.";

            } else {


                // ==========================================
                // INSERT STAFF
                // ==========================================

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

                    VALUES
                    (
                        ?, ?, ?, ?, ?, ?, ?, ?, ?
                    )
                ";


                $stmt =
                    mysqli_prepare(
                        $conn,
                        $sql
                    );


                if (!$stmt) {

                    $message =
                        "Unable to prepare staff record: "
                        . mysqli_error($conn);

                } else {


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


                    try {

                        if (
                            mysqli_stmt_execute(
                                $stmt
                            )
                        ) {

                            mysqli_stmt_close(
                                $stmt
                            );

                            header(
                                "Location: index.php?added=1"
                            );

                            exit();

                        } else {

                            $message =
                                "Unable to add staff.";
                        }

                    } catch (
                        mysqli_sql_exception $e
                    ) {

                        if ($e->getCode() == 1062) {

                            $message =
                                "This Staff Code already exists. Please use another code.";

                        } else {

                            $message =
                                "Database error: "
                                . $e->getMessage();
                        }
                    }


                    if ($stmt) {

                        mysqli_stmt_close(
                            $stmt
                        );
                    }

                }

            }

        }

    }

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
        Add Staff - AutoTrack Garage
    </title>


    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >


    <link
        rel="stylesheet"
        href="/automobile_tracker/css/garage-admin.css"
    >


    <style>

        .staff-page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 25px;
        }

        .staff-page-header h1 {
            margin: 0 0 6px;
            font-size: 30px;
            font-weight: 800;
        }

        .staff-page-header p {
            margin: 0;
            color: #667085;
        }

        .staff-form-card {
            max-width: 1000px;
        }

        .form-actions {
            grid-column: 1 / -1;
            display: flex;
            gap: 12px;
            margin-top: 5px;
        }

        @media (max-width: 700px) {

            .staff-page-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .form-actions {
                flex-direction: column;
            }

        }

    </style>

</head>


<body>


<?php

require_once '../../includes/garage-sidebar.php';

?>


<main class="garage-main">


    <!-- =====================================================
         HEADER
    ====================================================== -->

    <header class="staff-page-header">

        <div>

            <h1>
                Add Staff
            </h1>

            <p>
                Add a new employee to your garage.
            </p>

        </div>


        <a
            href="index.php"
            class="btn btn-secondary"
        >
            ← Back to Staff
        </a>

    </header>



    <!-- =====================================================
         ERROR MESSAGE
    ====================================================== -->

    <?php if ($message !== ""): ?>

        <div class="alert alert-danger">

            <?= htmlspecialchars(
                $message,
                ENT_QUOTES,
                "UTF-8"
            ) ?>

        </div>

    <?php endif; ?>



    <!-- =====================================================
         ADD STAFF FORM
    ====================================================== -->

    <form
        method="POST"
        class="card form-grid staff-form-card"
    >


        <!-- STAFF CODE -->

        <div class="field">

            <label for="staffCode">
                Staff Code
            </label>

            <input
                id="staffCode"
                type="text"
                name="staff_code"
                placeholder="Example: STF001"
                value="<?= htmlspecialchars(
                    $_POST["staff_code"] ?? "",
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>"
                required
            >

        </div>



        <!-- POSITION -->

        <div class="field">

            <label for="position">
                Position
            </label>

            <select
                id="position"
                name="position"
                required
            >

                <?php

                $positions = [
                    "Mechanic",
                    "Technician",
                    "Receptionist",
                    "Supervisor",
                    "Store Keeper",
                    "Accountant"
                ];

                $selectedPosition =
                    $_POST["position"] ?? "Mechanic";

                ?>

                <?php foreach ($positions as $item): ?>

                    <option
                        value="<?= htmlspecialchars($item) ?>"
                        <?= $selectedPosition === $item
                            ? "selected"
                            : "" ?>
                    >
                        <?= htmlspecialchars($item) ?>
                    </option>

                <?php endforeach; ?>

            </select>

        </div>



        <!-- FIRST NAME -->

        <div class="field">

            <label for="firstName">
                First Name
            </label>

            <input
                id="firstName"
                type="text"
                name="first_name"
                value="<?= htmlspecialchars(
                    $_POST["first_name"] ?? "",
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>"
                required
            >

        </div>



        <!-- LAST NAME -->

        <div class="field">

            <label for="lastName">
                Last Name
            </label>

            <input
                id="lastName"
                type="text"
                name="last_name"
                value="<?= htmlspecialchars(
                    $_POST["last_name"] ?? "",
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>"
                required
            >

        </div>



        <!-- EMAIL -->

        <div class="field">

            <label for="email">
                Email
            </label>

            <input
                id="email"
                type="email"
                name="email"
                placeholder="example@email.com"
                value="<?= htmlspecialchars(
                    $_POST["email"] ?? "",
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>"
            >

        </div>



        <!-- MOBILE -->

        <div class="field">

            <label for="mobileNumber">
                Mobile Number
            </label>

            <input
                id="mobileNumber"
                type="tel"
                name="mobile_number"
                placeholder="07XXXXXXXX"
                value="<?= htmlspecialchars(
                    $_POST["mobile_number"] ?? "",
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>"
            >

        </div>



        <!-- JOINED DATE -->

        <div class="field">

            <label for="joinedDate">
                Joined Date
            </label>

            <input
                id="joinedDate"
                type="date"
                name="joined_date"
                value="<?= htmlspecialchars(
                    $_POST["joined_date"] ?? "",
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>"
                required
            >

        </div>



        <!-- BASIC SALARY -->

        <div class="field">

            <label for="basicSalary">
                Basic Salary
            </label>

            <input
                id="basicSalary"
                type="number"
                name="basic_salary"
                min="0"
                step="0.01"
                placeholder="Example: 75000"
                value="<?= htmlspecialchars(
                    $_POST["basic_salary"] ?? "",
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>"
                required
            >

        </div>



        <!-- ACTION BUTTONS -->

        <div class="form-actions">

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


</main>


</body>

</html>