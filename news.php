<?php

/*
|--------------------------------------------------------------------------
| START SESSION
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| DATABASE CONNECTION
|--------------------------------------------------------------------------
|
| Change this path only if your database file is in another location.
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/config/database.php';


/*
|--------------------------------------------------------------------------
| CHECK LOGIN
|--------------------------------------------------------------------------
*/

$userId = (int)($_SESSION['user_id'] ?? 0);

if ($userId <= 0) {

    header("Location: /automobile_tracker/login.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| USER NAME
|--------------------------------------------------------------------------
*/

$firstName = $_SESSION['first_name'] ?? 'User';


/*
|--------------------------------------------------------------------------
| GET PUBLISHED GARAGE NEWS
|--------------------------------------------------------------------------
*/

$newsStmt = $conn->prepare("
    SELECT
        gn.news_id,
        gn.garage_id,
        gn.title,
        gn.content,
        gn.image,
        gn.publish_status,
        gn.published_at,
        gn.created_at,
        g.garage_name

    FROM garage_news gn

    INNER JOIN garages g
        ON g.garage_id = gn.garage_id

    WHERE gn.publish_status = 'published'

      AND (
            gn.published_at IS NULL
            OR gn.published_at <= NOW()
          )

    ORDER BY
        COALESCE(
            gn.published_at,
            gn.created_at
        ) DESC
");


$newsStmt->execute();

$newsResult = $newsStmt->get_result();

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
        Automotive News | AutoTrack
    </title>


    <!-- Google Font -->

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


    <!-- Your existing CSS -->

    <link
        rel="stylesheet"
        href="css/style.css?v=10"
    >

    <link
        rel="stylesheet"
        href="css/responsive.css?v=10"
    >


    <!-- News page CSS -->

    <style>

        .news-page-section {
            margin-top: 28px;
        }


        .news-page-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;

            margin-bottom: 18px;
        }


        .news-page-heading h2 {
            margin: 0;

            font-size: 21px;
            font-weight: 800;

            color: #101828;
        }


        /*
        |--------------------------------------------------------------------------
        | NEWS LIST
        |--------------------------------------------------------------------------
        */

        .news-list {
            display: flex;
            flex-direction: column;

            gap: 18px;
        }


        /*
        |--------------------------------------------------------------------------
        | NEWS CARD
        |--------------------------------------------------------------------------
        */

        .news-card {
            display: grid;

            grid-template-columns: 125px 1fr;

            gap: 20px;

            padding: 18px;

            background: #ffffff;

            border: 1px solid #e2e8f0;

            border-radius: 16px;

            box-shadow:
                0 8px 24px
                rgba(15, 23, 42, 0.04);
        }


        /*
        |--------------------------------------------------------------------------
        | IMAGE
        |--------------------------------------------------------------------------
        */

        .news-image {
            width: 125px;
            height: 125px;

            overflow: hidden;

            border-radius: 13px;

            background: #eef2f7;
        }


        .news-image img {
            width: 100%;
            height: 100%;

            display: block;

            object-fit: cover;
        }


        .news-placeholder {
            width: 100%;
            height: 100%;

            display: flex;
            align-items: center;
            justify-content: center;

            background:
                linear-gradient(
                    135deg,
                    #eff6ff,
                    #f8fafc
                );

            font-size: 34px;
        }


        /*
        |--------------------------------------------------------------------------
        | NEWS DETAILS
        |--------------------------------------------------------------------------
        */

        .news-details {
            min-width: 0;

            display: flex;
            flex-direction: column;
        }


        .news-meta {
            display: flex;
            align-items: center;
            flex-wrap: wrap;

            gap: 10px;

            margin-bottom: 9px;
        }


        .news-badge {
            display: inline-flex;

            align-items: center;
            justify-content: center;

            padding: 5px 10px;

            border-radius: 999px;

            background: #dbeafe;
            color: #1d4ed8;

            font-size: 12px;
            font-weight: 700;
        }


        .garage-name {
            color: #64748b;

            font-size: 13px;
            font-weight: 600;
        }


        .news-details h3 {
            margin: 0 0 8px 0;

            color: #0f172a;

            font-size: 18px;
            font-weight: 800;

            line-height: 1.35;
        }


        .news-details p {
            margin: 0 0 16px 0;

            color: #64748b;

            font-size: 14px;

            line-height: 1.6;
        }


        /*
        |--------------------------------------------------------------------------
        | FOOTER
        |--------------------------------------------------------------------------
        */

        .news-footer {
            display: flex;

            align-items: center;
            justify-content: space-between;

            gap: 16px;

            margin-top: auto;
        }


        .read-btn {
            display: inline-flex;

            align-items: center;
            justify-content: center;

            min-height: 40px;

            padding: 9px 16px;

            border-radius: 8px;

            background: #2563eb;

            color: #ffffff !important;

            text-decoration: none;

            font-size: 13px;
            font-weight: 700;

            transition: 0.2s ease;
        }


        .read-btn:hover {
            background: #1d4ed8;

            transform: translateY(-1px);
        }


        .news-date {
            color: #94a3b8;

            font-size: 13px;
            font-weight: 500;
        }


        /*
        |--------------------------------------------------------------------------
        | EMPTY STATE
        |--------------------------------------------------------------------------
        */

        .empty-news {
            padding: 45px 25px;

            text-align: center;

            background: #ffffff;

            border: 1px solid #e2e8f0;

            border-radius: 16px;
        }


        .empty-news-icon {
            margin-bottom: 10px;

            font-size: 38px;
        }


        .empty-news h3 {
            margin: 0 0 8px 0;

            color: #101828;

            font-size: 18px;
        }


        .empty-news p {
            margin: 0;

            color: #667085;

            font-size: 14px;
        }


        /*
        |--------------------------------------------------------------------------
        | RESPONSIVE
        |--------------------------------------------------------------------------
        */

        @media (max-width: 700px) {

            .news-card {
                grid-template-columns: 1fr;
            }


            .news-image {
                width: 100%;
                height: 190px;
            }


            .news-footer {
                align-items: flex-start;
                flex-direction: column;
            }

        }

    </style>

</head>


<body data-page="news">


<div class="app-shell">


    <!-- SIDEBAR -->

    <?php
    require_once __DIR__ . '/includes/sidebar.php';
    ?>


    <!-- MAIN CONTENT -->

    <main class="main">


        <!-- TOP BAR -->

        <header class="topbar">


            <div class="title">

                <h1>
                    Automotive News
                </h1>

                <p>
                    Automobile Service and Maintenance Tracker
                </p>

            </div>


            <!-- USER -->

            <div class="user-chip">


                <div class="avatar">

                    <?= htmlspecialchars(
                        strtoupper(
                            substr(
                                $firstName,
                                0,
                                1
                            )
                        )
                    ) ?>

                </div>


                <span data-user-name>

                    <?= htmlspecialchars(
                        $firstName
                    ) ?>

                </span>


                <a
                    href="/automobile_tracker/logout.php"
                    class="btn btn-secondary"
                >
                    Logout
                </a>


            </div>


        </header>


        <!-- NEWS SECTION -->

        <section class="news-page-section">


            <div class="news-page-heading">

                <h2>
                    Latest Articles
                </h2>

            </div>


            <?php if ($newsResult->num_rows === 0): ?>


                <!-- EMPTY STATE -->

                <div class="empty-news">


                    <div class="empty-news-icon">
                        📰
                    </div>


                    <h3>
                        No news available
                    </h3>


                    <p>
                        New automotive news and garage announcements
                        will appear here.
                    </p>


                </div>


            <?php else: ?>


                <div class="news-list">


                    <?php while ($news = $newsResult->fetch_assoc()): ?>


                        <article class="news-card">


                            <!-- IMAGE -->

                            <div class="news-image">


                                <?php if (!empty($news['image'])): ?>


                                    <?php

                                    $imagePath =
                                        trim(
                                            $news['image']
                                        );

                                    ?>


                                    <img
                                        src="<?= htmlspecialchars($imagePath) ?>"
                                        alt="<?= htmlspecialchars($news['title']) ?>"
                                        loading="lazy"
                                    >


                                <?php else: ?>


                                    <div class="news-placeholder">
                                        📰
                                    </div>


                                <?php endif; ?>


                            </div>


                            <!-- DETAILS -->

                            <div class="news-details">


                                <div class="news-meta">


                                    <span class="news-badge">
                                        Garage News
                                    </span>


                                    <span class="garage-name">

                                        <?= htmlspecialchars(
                                            $news['garage_name']
                                        ) ?>

                                    </span>


                                </div>


                                <!-- TITLE -->

                                <h3>

                                    <?= htmlspecialchars(
                                        $news['title']
                                    ) ?>

                                </h3>


                                <!-- CONTENT PREVIEW -->

                                <p>

                                    <?= htmlspecialchars(
                                        mb_strimwidth(
                                            strip_tags(
                                                $news['content']
                                            ),
                                            0,
                                            190,
                                            '...'
                                        )
                                    ) ?>

                                </p>


                                <!-- FOOTER -->

                                <div class="news-footer">


                                    <a
                                        href="/automobile_tracker/news-view.php?id=<?= (int)$news['news_id'] ?>"
                                        class="read-btn"
                                    >
                                        Read Article
                                    </a>


                                    <span class="news-date">


                                        <?php

                                        $newsDate =
                                            $news['published_at']
                                            ?: $news['created_at'];


                                        echo date(
                                            'd M Y',
                                            strtotime(
                                                $newsDate
                                            )
                                        );

                                        ?>


                                    </span>


                                </div>


                            </div>


                        </article>


                    <?php endwhile; ?>


                </div>


            <?php endif; ?>


        </section>


        <!-- FOOTER -->

        <div class="footer-note">

            AutoTrack • Automobile Service and Maintenance Tracker

        </div>


    </main>


</div>


<script src="js/app.js"></script>


</body>

</html>

<?php

$newsStmt->close();

?>