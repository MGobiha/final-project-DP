<?php

require_once 'auth.php';

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
        Garage Registration Rejected - AutoTrack
    </title>

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="../css/style.css"
    >

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;

            display: flex;
            align-items: center;
            justify-content: center;

            padding: 30px;

            font-family: "Inter", sans-serif;

            background: #f8fafc;

            color: #172033;
        }

        .status-card {
            width: min(
                600px,
                100%
            );

            padding: 42px;

            border:
                1px solid
                #e4e7ec;

            border-radius: 22px;

            background: #ffffff;

            text-align: center;

            box-shadow:
                0 18px 45px
                rgba(
                    15,
                    35,
                    65,
                    .10
                );
        }

        .status-icon {
            width: 84px;
            height: 84px;

            margin:
                0 auto 22px;

            display: grid;
            place-items: center;

            border-radius: 50%;

            background: #fef3f2;

            font-size: 38px;
        }

        .status-card h1 {
            margin:
                0 0 12px;
        }

        .status-card p {
            color: #667085;

            line-height: 1.7;
        }

        .garage-name {
            margin:
                25px
                0;

            padding: 18px;

            border-radius: 12px;

            background: #f8fafc;

            font-weight: 700;
        }

    </style>

</head>


<body>


<div class="status-card">


    <div class="status-icon">
        ❌
    </div>


    <h1>
        Garage Registration Rejected
    </h1>


    <p>
        Your garage registration was not approved by the
        AutoTrack System Administrator. You cannot access
        the Garage Administration area while the registration
        remains rejected.
    </p>


    <div class="garage-name">

        <?php
        echo htmlspecialchars(
            $garage[
                "garage_name"
            ]
        );
        ?>

    </div>


    <a
        href="../logout.php"
        class="btn btn-primary"
    >
        Logout
    </a>


</div>


</body>

</html>