<?php

error_reporting(E_ALL);

ini_set(
    "display_errors",
    1
);


require_once
    'includes/sms-functions.php';


$mobile =
    "0767041996"; // CHANGE THIS TO YOUR TEST NUMBER


$message =
    "AutoTrack test SMS. "
    .
    "Your SMS integration is working.";


$result =
    sendSms(
        $mobile,
        $message
    );

?>

<!doctype html>

<html>

<head>

    <title>
        AutoTrack SMS Test
    </title>

</head>

<body>

    <h2>
        SMS Test Result
    </h2>


    <pre>
<?php
print_r(
    $result
);
?>
    </pre>

</body>

</html>