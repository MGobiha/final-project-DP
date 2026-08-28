<?php

session_start();

require_once 'config/database.php';

$message = "";


// =====================================================
// HANDLE LOGIN
// =====================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email =
        trim(
            $_POST["email"] ?? ""
        );

    $password =
        $_POST["password"] ?? "";


    // -------------------------------------------------
    // Basic validation
    // -------------------------------------------------

    if (
        $email === ""
        ||
        $password === ""
    ) {

        $message =
            "Please enter your email and password.";

    } else {


        // =====================================================
        // LOAD USER
        // =====================================================

        $sql = "
            SELECT
                user_id,
                first_name,
                last_name,
                email,
                mobile_number,
                password,
                role,
                account_status

            FROM users

            WHERE email = ?

            LIMIT 1
        ";


        $stmt =
            mysqli_prepare(
                $conn,
                $sql
            );


        if (!$stmt) {

            $message =
                "Login query error: "
                . mysqli_error($conn);

        } else {


            mysqli_stmt_bind_param(
                $stmt,
                "s",
                $email
            );


            mysqli_stmt_execute(
                $stmt
            );


            $result =
                mysqli_stmt_get_result(
                    $stmt
                );


            $user =
                mysqli_fetch_assoc(
                    $result
                );


            // =================================================
            // CHECK USER
            // =================================================

            if (!$user) {

                $message =
                    "Invalid email or password.";

            } elseif (
                !password_verify(
                    $password,
                    $user["password"]
                )
            ) {

                $message =
                    "Invalid email or password.";

            } elseif (
                $user["account_status"]
                !== "active"
            ) {

                $message =
                    "Your account is not active.";

            } else {


                // =================================================
                // LOGIN SUCCESS
                // =================================================

                session_regenerate_id(
                    true
                );


                $_SESSION["user_id"] =
                    (int)
                    $user["user_id"];


                $_SESSION["first_name"] =
                    $user["first_name"];


                $_SESSION["last_name"] =
                    $user["last_name"];


                $_SESSION["email"] =
                    $user["email"];


                $_SESSION["mobile_number"] =
                    $user["mobile_number"];


                $_SESSION["role"] =
                    $user["role"];


                // =================================================
                // ROLE BASED REDIRECTION
                // =================================================

                switch (
                    $user["role"]
                ) {


                    // =============================================
                    // SYSTEM ADMIN
                    // =============================================

                    case "system_admin":

                        header(
                            "Location: admin/dashboard.php"
                        );

                        exit();


                    // =============================================
                    // GARAGE ADMIN
                    // =============================================

                    case "garage_admin":


                        // -----------------------------------------
                        // Find garage connected to this admin
                        // -----------------------------------------

                        $garageSql = "
                            SELECT
                                garage_id,
                                approval_status,
                                active_status

                            FROM garages

                            WHERE owner_user_id = ?

                            LIMIT 1
                        ";


                        $garageStmt =
                            mysqli_prepare(
                                $conn,
                                $garageSql
                            );


                        if (!$garageStmt) {

                            session_destroy();

                            $message =
                                "Unable to check garage account.";

                            break;
                        }


                        mysqli_stmt_bind_param(
                            $garageStmt,
                            "i",
                            $user["user_id"]
                        );


                        mysqli_stmt_execute(
                            $garageStmt
                        );


                        $garageResult =
                            mysqli_stmt_get_result(
                                $garageStmt
                            );


                        $garage =
                            mysqli_fetch_assoc(
                                $garageResult
                            );


                        if (!$garage) {

                            session_destroy();

                            $message =
                                "No garage is linked to this account.";

                            break;
                        }


                        $_SESSION["garage_id"] =
                            (int)
                            $garage["garage_id"];


                        // -----------------------------------------
                        // Pending Garage
                        // -----------------------------------------

                        if (
                            $garage[
                                "approval_status"
                            ] === "pending"
                        ) {

                            header(
                                "Location: garage/pending-approval.php"
                            );

                            exit();
                        }


                        // -----------------------------------------
                        // Rejected Garage
                        // -----------------------------------------

                        if (
                            $garage[
                                "approval_status"
                            ] === "rejected"
                        ) {

                            header(
                                "Location: garage/rejected.php"
                            );

                            exit();
                        }


                        // -----------------------------------------
                        // Approved but inactive
                        // -----------------------------------------

                        if (
                            $garage[
                                "approval_status"
                            ] === "approved"
                            &&
                            (int)
                            $garage[
                                "active_status"
                            ] !== 1
                        ) {

                            session_destroy();

                            $message =
                                "Your garage account is currently inactive.";

                            break;
                        }


                        // -----------------------------------------
                        // Approved Garage
                        // -----------------------------------------

                        if (
                            $garage[
                                "approval_status"
                            ] === "approved"
                        ) {

                            header(
                                "Location: garage/dashboard.php"
                            );

                            exit();
                        }


                        session_destroy();

                        $message =
                            "Unable to determine garage approval status.";

                        break;


                    // =============================================
                    // GARAGE STAFF
                    // =============================================

                    case "garage_staff":

                        header(
                            "Location: garage/staff/dashboard.php"
                        );

                        exit();


                    // =============================================
                    // VEHICLE OWNER
                    // =============================================

                    case "vehicle_owner":

                        header(
                            "Location: dashboard.php"
                        );

                        exit();


                    // =============================================
                    // UNKNOWN ROLE
                    // =============================================

                    default:

                        session_destroy();

                        $message =
                            "Your account role is not valid.";

                        break;
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
        Login - AutoTrack
    </title>


    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap"
        rel="stylesheet"
    >


    <link
        rel="stylesheet"
        href="css/style.css"
    >

</head>


<body>


<div class="auth-page">


    <!-- =====================================================
         LEFT SIDE
         ===================================================== -->

    <section class="auth-visual">

        <h1>
            Keep every journey running smoothly.
        </h1>

        <p>
            Track service history,
            plan maintenance,
            receive reminders,
            find garages and ask
            the AI assistant for general
            vehicle-care guidance.
        </p>

    </section>


    <!-- =====================================================
         LOGIN PANEL
         ===================================================== -->

    <section class="auth-panel">


        <form
            class="auth-card"
            method="POST"
        >


            <h2>
                Welcome back
            </h2>


            <p class="muted">
                Sign in to your AutoTrack account.
            </p>


            <!-- =================================================
                 SUCCESS MESSAGE
                 ================================================= -->

            <?php if (
                isset(
                    $_SESSION[
                        "success_message"
                    ]
                )
            ): ?>

                <div
                    class="
                        alert
                        alert-success
                    "
                >

                    <?php

                    echo
                    htmlspecialchars(
                        $_SESSION[
                            "success_message"
                        ]
                    );

                    unset(
                        $_SESSION[
                            "success_message"
                        ]
                    );

                    ?>

                </div>

            <?php endif; ?>


            <!-- =================================================
                 ERROR MESSAGE
                 ================================================= -->

            <?php if (
                !empty(
                    $message
                )
            ): ?>

                <div
                    class="
                        alert
                        alert-danger
                    "
                >

                    <?php

                    echo
                    htmlspecialchars(
                        $message
                    );

                    ?>

                </div>

            <?php endif; ?>


            <!-- EMAIL -->

            <div class="field">

                <label for="email">
                    Email
                </label>

                <input
                    type="email"
                    name="email"
                    id="email"
                    value="<?php
                    echo htmlspecialchars(
                        $_POST[
                            "email"
                        ] ?? ""
                    );
                    ?>"
                    placeholder="example@email.com"
                    required
                >

            </div>


            <!-- PASSWORD -->

            <div class="field">

                <label for="password">
                    Password
                </label>

                <input
                    type="password"
                    name="password"
                    id="password"
                    placeholder="Enter your password"
                    required
                >

            </div>


            <!-- LOGIN BUTTON -->

            <button
                type="submit"
                class="
                    btn
                    btn-primary
                "
            >
                Login
            </button>


            <p class="muted">

                New user?

                <a
                    href="register.php"
                    style="
                        color:#1565c0;
                    "
                >
                    Create an account
                </a>

            </p>


        </form>


    </section>


</div>


</body>

</html>