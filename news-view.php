<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
| GET NEWS ID
|--------------------------------------------------------------------------
*/

$newsId = (int)($_GET['id'] ?? 0);

if ($newsId <= 0) {
    header("Location: /automobile_tracker/news.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| GET NEWS ARTICLE
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        gn.news_id,
        gn.title,
        gn.content,
        gn.image,
        gn.published_at,
        gn.created_at,
        g.garage_name
    FROM garage_news gn

    INNER JOIN garages g
        ON g.garage_id = gn.garage_id

    WHERE gn.news_id = ?
      AND gn.publish_status = 'published'
      AND (
            gn.published_at IS NULL
            OR gn.published_at <= NOW()
          )

    LIMIT 1
");

$stmt->bind_param(
    "i",
    $newsId
);

$stmt->execute();

$result = $stmt->get_result();

$news = $result->fetch_assoc();

$stmt->close();


/*
|--------------------------------------------------------------------------
| ARTICLE NOT FOUND
|--------------------------------------------------------------------------
*/

if (!$news) {
    header("Location: /automobile_tracker/news.php");
    exit;
}


$firstName =
    $_SESSION['first_name']
    ?? 'User';


$newsDate =
    $news['published_at']
    ?: $news['created_at'];

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
        <?= htmlspecialchars($news['title']) ?>
        | AutoTrack
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
        href="css/style.css?v=10"
    >

    <link
        rel="stylesheet"
        href="css/responsive.css?v=10"
    >


    <style>

        .article-wrapper {
            max-width: 1000px;
            margin: 28px auto 0;
        }


        .article-back {
            margin-bottom: 20px;
        }


        .article-back a {
            display: inline-flex;
            align-items: center;
            gap: 7px;

            color: #2563eb;

            font-size: 14px;
            font-weight: 700;

            text-decoration: none;
        }


        .article-card {
            padding: 30px;

            background: #ffffff;

            border: 1px solid #e2e8f0;

            border-radius: 18px;

            box-shadow:
                0 10px 30px
                rgba(15, 23, 42, 0.05);
        }


        .article-meta {
            display: flex;
            align-items: center;
            flex-wrap: wrap;

            gap: 10px;

            margin-bottom: 16px;
        }


        .article-badge {
            display: inline-flex;

            padding: 6px 11px;

            background: #dbeafe;
            color: #1d4ed8;

            border-radius: 999px;

            font-size: 12px;
            font-weight: 700;
        }


        .article-garage {
            color: #64748b;

            font-size: 13px;
            font-weight: 600;
        }


        .article-date {
            color: #94a3b8;

            font-size: 13px;
        }


        .article-title {
            margin: 0 0 22px;

            color: #0f172a;

            font-size: 32px;
            line-height: 1.25;

            font-weight: 800;
        }


        .article-image {
            width: 100%;

            max-height: 460px;

            margin-bottom: 25px;

            overflow: hidden;

            border-radius: 16px;

            background: #eef2f7;
        }


        .article-image img {
            width: 100%;
            height: 100%;

            display: block;

            object-fit: cover;
        }


        .article-placeholder {
            min-height: 250px;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 60px;

            background:
                linear-gradient(
                    135deg,
                    #eff6ff,
                    #f8fafc
                );
        }


        .article-content {
            color: #475569;

            font-size: 16px;

            line-height: 1.8;
        }


        .article-content p {
            margin-top: 0;
        }


        @media (max-width: 700px) {

            .article-card {
                padding: 20px;
            }


            .article-title {
                font-size: 24px;
            }

        }

    </style>

</head>


<body data-page="news">


<div class="app-shell">


    <?php
    require_once __DIR__ . '/includes/sidebar.php';
    ?>


    <main class="main">


        <header class="topbar">


            <div class="title">

                <h1>
                    Automotive News
                </h1>

                <p>
                    Automobile Service and Maintenance Tracker
                </p>

            </div>


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


                <span>

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


        <div class="article-wrapper">


            <div class="article-back">

                <a href="/automobile_tracker/news.php">
                    ← Back to News
                </a>

            </div>


            <article class="article-card">


                <div class="article-meta">


                    <span class="article-badge">
                        Garage News
                    </span>


                    <span class="article-garage">

                        <?= htmlspecialchars(
                            $news['garage_name']
                        ) ?>

                    </span>


                    <span class="article-date">

                        <?= date(
                            'd M Y - h:i A',
                            strtotime($newsDate)
                        ) ?>

                    </span>


                </div>


                <h1 class="article-title">

                    <?= htmlspecialchars(
                        $news['title']
                    ) ?>

                </h1>


                <div class="article-image">


                    <?php if (!empty($news['image'])): ?>


                        <img
                            src="<?= htmlspecialchars(
                                $news['image']
                            ) ?>"
                            alt="<?= htmlspecialchars(
                                $news['title']
                            ) ?>"
                        >


                    <?php else: ?>


                        <div class="article-placeholder">
                            📰
                        </div>


                    <?php endif; ?>


                </div>


                <div class="article-content">

                    <?= nl2br(
                        htmlspecialchars(
                            $news['content']
                        )
                    ) ?>

                </div>


            </article>


        </div>


        <div class="footer-note">

            AutoTrack • Automobile Service and Maintenance Tracker

        </div>


    </main>


</div>


<script src="js/app.js"></script>


</body>

</html>