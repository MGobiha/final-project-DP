<?php

session_start();

include 'config/database.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if (empty($email) || empty($password)) {

        $message = "Please enter your email and password.";

    } else {

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

        $stmt = mysqli_prepare($conn, $sql);

        mysqli_stmt_bind_param(
            $stmt,
            "s",
            $email
        );

        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        $user = mysqli_fetch_assoc($result);

        if (!$user) {

            $message = "Invalid email or password.";

        } elseif (
            !password_verify(
                $password,
                $user["password"]
            )
        ) {

            $message = "Invalid email or password.";

        } elseif (
            $user["account_status"] !== "active"
        ) {

            $message = "Your account is not active.";

        } else {

            // Security: generate a new session ID
            session_regenerate_id(true);

            $_SESSION["user_id"] =
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

            // Different dashboard based on role
            if ($user["role"] === "admin") {

                        header("Location: admin.php");

                    } elseif ($user["role"] === "garage_admin") {

                        header("Location: garage/dashboard.php");

                    } elseif ($user["role"] === "garage_staff") {

                        header("Location: staff/dashboard.php");

                    } else {

                        header("Location: dashboard.php");
                    }

                    exit();
            
        }
    }
}

?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Login - AutoTrack</title>
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="css/style.css" />
  </head>
  <body>
    <div class="auth-page">
      <section class="auth-visual">
        <h1>Keep every journey running smoothly.</h1>
        <p>
          Track service history, plan maintenance, receive reminders, find
          garages and ask the AI assistant for general vehicle-care guidance.
        </p>
      </section>
      <section class="auth-panel">
        <?php if (!empty($message)): ?>

            <div class="alert alert-danger">

                <?php
                echo htmlspecialchars($message);
                ?>

            </div>

        <?php endif; ?>

        <form class="auth-card" method="POST">
          <h2>Welcome back</h2>
          <p class="muted">Sign in to manage your vehicles.</p>
          <?php if (
                  isset($_SESSION["success_message"])
              ): ?>

                  <div class="alert alert-success">

                      <?php
                      echo htmlspecialchars(
                          $_SESSION["success_message"]
                      );

                      unset(
                          $_SESSION["success_message"]
                      );
                      ?>

                  </div>

              <?php endif; ?>
          
          <div class="field">
            <label>Email</label>
            <input type="email" name="email"  placeholder="alex@example.com" required />
          </div>

          <div class="field">
           
              <input
                  type="password"
                  name="password"
                  placeholder="Enter your password"
                  required
              >

          </div>


          <button
              type="submit"
              class="btn btn-primary">Login</button>

          <p class="muted">
            New user?
            <a href="register.html" style="color: #1565c0">Create an account</a>
          </p>
          
        </form>
      </section>
    </div>
    <!-- <script src="js/login.js"></script> -->
     
  </body>
</html>
