<?php
session_start();
$_SESSION = array(); // Unset all session variables
session_destroy();
header("location: index.php"); // Redirect to home page
exit;
?>