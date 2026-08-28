<?php

require_once '../auth.php';

$currentPage = "news";

$userId = (int)($_SESSION['user_id'] ?? 0);

if ($userId <= 0) {

    header("Location: /automobile_tracker/login.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Get Garage
|--------------------------------------------------------------------------
*/

$garageId = 0;

$stmt = $conn->prepare("
    SELECT garage_id
    FROM garages
    WHERE owner_user_id = ?
    LIMIT 1
");

$stmt->bind_param("i", $userId);

$stmt->execute();

$result = $stmt->get_result();

if ($garage = $result->fetch_assoc()) {

    $garageId = (int)$garage['garage_id'];
}

$stmt->close();


if ($garageId <= 0) {

    die("Garage not found.");
}


/*
|--------------------------------------------------------------------------
| Get Garage News
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        news_id,
        title,
        content,
        image,
        publish_status,
        published_at,
        created_at
    FROM garage_news
    WHERE garage_id = ?
    ORDER BY created_at DESC
");

$stmt->bind_param("i", $garageId);

$stmt->execute();

$newsResult = $stmt->get_result();

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
        Garage News | AutoTrack
    </title>

    <link
        rel="stylesheet"
        href="../../css/garage-admin.css?v=10"
    >

</head>

<body>


<div class="app-shell">


    <?php

    require_once '../../includes/garage-sidebar.php';

    ?>


    <main class="garage-main">


        <!-- HEADER -->

        <div class="page-header">


            <div>

                <h1>
                    Garage News
                </h1>

                <p>
                    Manage news and announcements for your customers.
                </p>

            </div>


            <a
                href="/automobile_tracker/garage/news/add.php"
                class="btn-primary"
            >
                + Add News
            </a>


        </div>


        <!-- SUCCESS -->

        <?php if (isset($_GET['added'])): ?>

            <div class="alert alert-success">

                News added successfully.

            </div>

        <?php endif; ?>


        <!-- NEWS CARD -->

        <div class="card">


            <h2 style="margin-top:0;">
                News & Announcements
            </h2>


            <?php if ($newsResult->num_rows === 0): ?>


                <p style="color:#667085;">

                    No news has been added yet.

                </p>


            <?php else: ?>


                <div class="table-wrap">


                    <table class="table">


                        <thead>

                        <tr>

                            <th>
                                Title
                            </th>

                            <th>
                                Content
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Published
                            </th>

                            <th>
                                Created
                            </th>

                            <th>
                                Action
                            </th>

                        </tr>

                        </thead>


                        <tbody>


                        <?php while ($news = $newsResult->fetch_assoc()): ?>


                            <tr>


                                <!-- TITLE -->

                                <td>

                                    <strong>

                                        <?= htmlspecialchars(
                                            $news['title']
                                        ) ?>

                                    </strong>

                                </td>


                                <!-- CONTENT -->

                                <td>

                                    <?php

                                    $shortContent =
                                        mb_strimwidth(
                                            $news['content'],
                                            0,
                                            90,
                                            '...'
                                        );

                                    ?>

                                    <?= htmlspecialchars(
                                        $shortContent
                                    ) ?>

                                </td>


                                <!-- STATUS -->

                                <td>

                                    <?php

                                    $status =
                                        $news['publish_status'];

                                    $badgeClass =
                                        'badge-warning';

                                    if ($status === 'published') {

                                        $badgeClass =
                                            'badge-success';

                                    } elseif ($status === 'archived') {

                                        $badgeClass =
                                            'badge-danger';
                                    }

                                    ?>


                                    <span
                                        class="badge
                                        <?= $badgeClass ?>"
                                    >

                                        <?= ucfirst(
                                            htmlspecialchars($status)
                                        ) ?>

                                    </span>

                                </td>


                                <!-- PUBLISHED -->

                                <td>

                                    <?php if (!empty($news['published_at'])): ?>

                                        <?= date(
                                            'd M Y',
                                            strtotime(
                                                $news['published_at']
                                            )
                                        ) ?>

                                    <?php else: ?>

                                        —

                                    <?php endif; ?>

                                </td>


                                <!-- CREATED -->

                                <td>

                                    <?= date(
                                        'd M Y',
                                        strtotime(
                                            $news['created_at']
                                        )
                                    ) ?>

                                </td>


                                <!-- ACTION -->

                                <td>

                                    <a
                                        href="edit.php?id=<?= (int)$news['news_id'] ?>"
                                        class="btn btn-secondary"
                                    >
                                        Edit
                                    </a>

                                </td>


                            </tr>


                        <?php endwhile; ?>


                        </tbody>


                    </table>


                </div>


            <?php endif; ?>


        </div>


    </main>


</div>


</body>

</html>

<?php

$stmt->close();

?>