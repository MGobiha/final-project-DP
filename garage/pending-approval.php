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
        Garage Approval Pending - AutoTrack
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

            background:
                linear-gradient(
                    135deg,
                    #eef5ff,
                    #f8fbff
                );

            color: #172033;
        }

        .approval-card {
            width: min(
                620px,
                100%
            );

            padding: 42px;

            border:
                1px solid
                #e3e8ef;

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

        .approval-icon {
            width: 84px;
            height: 84px;

            margin:
                0 auto 22px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 50%;

            background: #fff7e6;

            font-size: 38px;
        }

        .approval-card h1 {
            margin:
                0 0 12px;

            font-size: 30px;
        }

        .approval-card > p {
            margin:
                0 auto 28px;

            max-width: 500px;

            color: #667085;

            line-height: 1.7;
        }

        .garage-box {
            margin-bottom: 26px;

            padding: 20px;

            border:
                1px solid
                #e4e7ec;

            border-radius: 14px;

            background: #f8fafc;

            text-align: left;
        }

        .garage-box h3 {
            margin:
                0 0 14px;

            font-size: 18px;
        }

        .garage-row {
            display: grid;

            grid-template-columns:
                130px
                1fr;

            gap: 12px;

            margin-top: 10px;

            font-size: 14px;
        }

        .garage-row span:first-child {
            color: #667085;

            font-weight: 600;
        }

        .status-badge {
            display: inline-flex;

            padding:
                7px
                12px;

            border-radius: 999px;

            background: #fef3c7;

            color: #92400e;

            font-size: 13px;

            font-weight: 700;
        }

        .actions {
            display: flex;

            gap: 12px;

            justify-content: center;

            flex-wrap: wrap;
        }

        .actions .btn {
            min-width: 140px;
        }

        @media (
            max-width: 560px
        ) {

            .approval-card {
                padding:
                    30px
                    20px;
            }

            .garage-row {
                grid-template-columns:
                    1fr;
            }

            .actions {
                flex-direction: column;
            }

            .actions .btn {
                width: 100%;
            }
        }

    </style>

</head>


<body>


<div class="approval-card">


    <div class="approval-icon">
        ⏳
    </div>


    <h1>
        Garage Approval Pending
    </h1>


    <p>
        Your garage registration has been submitted successfully.
        The AutoTrack System Administrator must approve your garage
        before you can access the full Garage Administration system.
    </p>


    <div class="garage-box">

        <h3>
            Registration Details
        </h3>


        <div class="garage-row">

            <span>
                Garage
            </span>

            <strong>

                <?php
                echo htmlspecialchars(
                    $garage[
                        "garage_name"
                    ]
                );
                ?>

            </strong>

        </div>


        <div class="garage-row">

            <span>
                Owner
            </span>

            <span>

                <?php
                echo htmlspecialchars(
                    $garage[
                        "owner_name"
                    ]
                    ?? (
                        (
                            $_SESSION[
                                "first_name"
                            ]
                            ?? ""
                        )
                        . " "
                        .
                        (
                            $_SESSION[
                                "last_name"
                            ]
                            ?? ""
                        )
                    )
                );
                ?>

            </span>

        </div>


        <div class="garage-row">

            <span>
                District
            </span>

            <span>

                <?php
                echo htmlspecialchars(
                    $garage[
                        "district"
                    ]
                    ?? "-"
                );
                ?>

            </span>

        </div>


        <div class="garage-row">

            <span>
                Status
            </span>

            <span>

                <span class="status-badge">
                    Pending Approval
                </span>

            </span>

        </div>


        <?php if (
            !empty(
                $garage[
                    "created_at"
                ]
            )
        ): ?>

            <div class="garage-row">

                <span>
                    Registered
                </span>

                <span>

                    <?php
                    echo date(
                        "d M Y h:i A",
                        strtotime(
                            $garage[
                                "created_at"
                            ]
                        )
                    );
                    ?>

                </span>

            </div>

        <?php endif; ?>


    </div>


    <div class="actions">


        <a
            href="pending-approval.php"
            class="btn btn-primary"
        >
            Refresh Status
        </a>


        <a
            href="../logout.php"
            class="btn btn-secondary"
        >
            Logout
        </a>


    </div>


</div>


</body>

</html>