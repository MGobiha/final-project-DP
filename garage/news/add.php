<?php

require_once '../auth.php';

require_once __DIR__ . '/../../includes/sms-functions.php';

$currentPage = "news";

$error = "";

/*
|--------------------------------------------------------------------------
| Logged-in user
|--------------------------------------------------------------------------
*/

$userId = (int)($_SESSION['user_id'] ?? 0);

if ($userId <= 0) {
    header("Location: /automobile_tracker/login.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Get Garage ID
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
    die("Garage not found for this account.");
}


/*
|--------------------------------------------------------------------------
| Save News
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $publishStatus = trim($_POST['publish_status'] ?? 'draft');
    $publishedAtInput = trim($_POST['published_at'] ?? '');

    /*
    |--------------------------------------------------------------------------
    | Validate status
    |--------------------------------------------------------------------------
    */

    $allowedStatuses = [
        'draft',
        'published',
        'archived'
    ];

    if (!in_array($publishStatus, $allowedStatuses, true)) {
        $publishStatus = 'draft';
    }


    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if ($title === '') {

        $error = "Please enter the news title.";

    } elseif ($content === '') {

        $error = "Please enter the news content.";

    } else {

        /*
        |--------------------------------------------------------------------------
        | Published Date
        |--------------------------------------------------------------------------
        */

        $publishedAt = null;

        if ($publishStatus === 'published') {

            if ($publishedAtInput !== '') {

                $publishedAt =
                    date(
                        'Y-m-d H:i:s',
                        strtotime($publishedAtInput)
                    );

            } else {

                $publishedAt = date('Y-m-d H:i:s');
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Insert
        |--------------------------------------------------------------------------
        */

        $stmt = $conn->prepare("
            INSERT INTO garage_news
            (
                garage_id,
                title,
                content,
                publish_status,
                published_at,
                created_by
            )
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "issssi",
            $garageId,
            $title,
            $content,
            $publishStatus,
            $publishedAt,
            $userId
        );


        if ($stmt->execute()) {

    // ID of the newly created news
    $newsId = $conn->insert_id;

    $stmt->close();


    /*
    |--------------------------------------------------------------------------
    | SEND NOTIFICATIONS ONLY FOR PUBLISHED NEWS
    |--------------------------------------------------------------------------
    */

    if ($publishStatus === 'published') {

        /*
        |--------------------------------------------------------------------------
        | Find customers connected to this garage
        |--------------------------------------------------------------------------
        */

        /*
        |--------------------------------------------------------------------------
        | Detect the real customer/status columns in garage_customer_requests
        |--------------------------------------------------------------------------
        */

        $columnsResult = $conn->query("SHOW COLUMNS FROM garage_customer_requests");
        $requestColumns = [];

        while ($column = $columnsResult->fetch_assoc()) {
            $requestColumns[] = $column['Field'];
        }

        $customerIdColumn = null;

        foreach (
            [
                'user_id',
                'customer_user_id',
                'customer_id',
                'vehicle_owner_id',
                'owner_user_id'
            ] as $candidate
        ) {
            if (in_array($candidate, $requestColumns, true)) {
                $customerIdColumn = $candidate;
                break;
            }
        }

        $statusColumn = null;

        foreach (
            [
                'request_status',
                'status',
                'approval_status'
            ] as $candidate
        ) {
            if (in_array($candidate, $requestColumns, true)) {
                $statusColumn = $candidate;
                break;
            }
        }

        if ($customerIdColumn === null) {
            die(
                "Customer ID column was not found in garage_customer_requests. "
                . "Please check that table structure."
            );
        }

        $customerSql = "
            SELECT DISTINCT
                u.user_id,
                u.first_name,
                u.mobile_number,
                u.news_sms
            FROM garage_customer_requests gcr
            INNER JOIN users u
                ON u.user_id = gcr.`{$customerIdColumn}`
            WHERE gcr.garage_id = ?
              AND u.role = 'vehicle_owner'
        ";

        if ($statusColumn !== null) {
            $customerSql .= "
                AND LOWER(gcr.`{$statusColumn}`) IN
                    ('approved', 'accepted', 'active')
            ";
        }

        $customerStmt = $conn->prepare($customerSql);

        $customerStmt->bind_param("i", $garageId);
        $customerStmt->execute();

        $customers = $customerStmt->get_result();


        /*
        |--------------------------------------------------------------------------
        | Notification text
        |--------------------------------------------------------------------------
        */

        $notificationTitle = "New Garage News: " . $title;

        $notificationMessage = $content;


        /*
        |--------------------------------------------------------------------------
        | Loop through connected customers
        |--------------------------------------------------------------------------
        */

        while ($customer = $customers->fetch_assoc()) {

            $customerId = (int)$customer['user_id'];


            /*
            |--------------------------------------------------------------------------
            | WEBSITE NOTIFICATION
            |--------------------------------------------------------------------------
            */

            $channel = "system";

            $notificationStatus = "sent";

            $notificationType = "news";

            $insertNotification = $conn->prepare("
                INSERT INTO notifications
                (
                    user_id,
                    notification_type,
                    title,
                    message,
                    channel,
                    notification_status,
                    sent_at
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    NOW()
                )
            ");

            $insertNotification->bind_param(
                "isssss",
                $customerId,
                $notificationType,
                $notificationTitle,
                $notificationMessage,
                $channel,
                $notificationStatus
            );

            $insertNotification->execute();

            $insertNotification->close();




            /*
|--------------------------------------------------------------------------
| SMS NOTIFICATION
|--------------------------------------------------------------------------
*/

if (
    (int)$customer['news_sms'] === 1
    &&
    !empty($customer['mobile_number'])
) {

    $mobile = trim(
        $customer['mobile_number']
    );


    /*
    |--------------------------------------------------------------------------
    | Keep SMS short
    |--------------------------------------------------------------------------
    */

    $smsMessage =
        "AutoTrack News: "
        . mb_strimwidth(
            $title,
            0,
            75,
            "..."
        )
        . ". Login to AutoTrack to read more.";


    /*
    |--------------------------------------------------------------------------
    | Send using Notify.lk
    |--------------------------------------------------------------------------
    */

    $smsResult = sendSms(
        $mobile,
        $smsMessage
    );


    /*
    |--------------------------------------------------------------------------
    | SMS LOG
    |--------------------------------------------------------------------------
    |
    | Your database already has sms_logs.
    | We can connect this separately after confirming SMS works.
    |--------------------------------------------------------------------------
    */

}

        }

        $customerStmt->close();
    }


    /*
    |--------------------------------------------------------------------------
    | Redirect
    |--------------------------------------------------------------------------
    */

    header(
        "Location: /automobile_tracker/garage/news/index.php?added=1"
    );

    exit;
} else {

            $error = "Unable to save news: " . $stmt->error;
        }

        $stmt->close();
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
        Add News | AutoTrack
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


        <!-- PAGE HEADER -->

        <div class="page-header">

            <div>

                <h1>
                    Add News
                </h1>

                <p>
                    Create a new announcement for your customers.
                </p>

            </div>


            <a
                href="/automobile_tracker/garage/news/index.php"
                class="btn-secondary"
            >
                ← Back
            </a>

        </div>


        <!-- ERROR -->

        <?php if ($error !== ''): ?>

            <div class="alert alert-danger">

                <?= htmlspecialchars($error) ?>

            </div>

        <?php endif; ?>


        <!-- FORM -->

        <div class="card news-form-card">

            <form method="POST">


                <!-- TITLE -->

                <div class="form-group">

                    <label>
                        News Title
                    </label>

                    <input
                        type="text"
                        name="title"
                        value="<?= htmlspecialchars($_POST['title'] ?? '') ?>"
                        placeholder="Example: Vehicle maintenance announcement"
                        required
                    >

                </div>


                <!-- CONTENT -->

                <div class="form-group">

                    <label>
                        News Content
                    </label>

                    <textarea
                        name="content"
                        rows="8"
                        placeholder="Enter the news or announcement..."
                        required
                    ><?= htmlspecialchars($_POST['content'] ?? '') ?></textarea>

                </div>


                <!-- STATUS -->

                <div class="form-group">

                    <label>
                        Publish Status
                    </label>

                    <select
                        name="publish_status"
                        id="publish_status"
                        required
                    >

                        <option
                            value="draft"
                            <?= ($_POST['publish_status'] ?? '') === 'draft'
                                ? 'selected'
                                : '' ?>
                        >
                            Draft
                        </option>

                        <option
                            value="published"
                            <?= ($_POST['publish_status'] ?? 'published') === 'published'
                                ? 'selected'
                                : '' ?>
                        >
                            Published
                        </option>

                        <option
                            value="archived"
                            <?= ($_POST['publish_status'] ?? '') === 'archived'
                                ? 'selected'
                                : '' ?>
                        >
                            Archived
                        </option>

                    </select>

                </div>


                <!-- PUBLISH DATE -->

                <div
                    class="form-group"
                    id="publishDateGroup"
                >

                    <label>
                        Publish Date & Time
                    </label>

                    <input
                        type="datetime-local"
                        name="published_at"
                        value="<?= htmlspecialchars(
                            $_POST['published_at']
                            ?? date('Y-m-d\TH:i')
                        ) ?>"
                    >

                </div>


                <!-- BUTTON -->

                <button
                    type="submit"
                    class="btn-primary"
                >
                    Save News
                </button>


            </form>

        </div>

    </main>

</div>


<script>

const statusSelect =
    document.getElementById('publish_status');

const publishDateGroup =
    document.getElementById('publishDateGroup');


function togglePublishDate() {

    if (statusSelect.value === 'published') {

        publishDateGroup.style.display = 'flex';

    } else {

        publishDateGroup.style.display = 'none';

    }
}


statusSelect.addEventListener(
    'change',
    togglePublishDate
);

togglePublishDate();

</script>


</body>

</html>