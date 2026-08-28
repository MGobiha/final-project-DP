<?php

session_start();

// Remove all session variables
$_SESSION = [];

// Destroy the session
session_destroy();

// Return user to public home page
header("Location: index.php");
exit();

?>

<a
    href="logout.php"
    class="btn btn-secondary"
>
    Logout
</a>