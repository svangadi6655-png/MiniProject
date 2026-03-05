<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["role"] !== "user") {
    header("location: user_login.php");
    exit;
}

include 'db_connect.php';

$complaint_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Mark escalated
$sql = "UPDATE complaints SET escalated = 1 WHERE complaint_id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $complaint_id, $user_id);

if ($stmt->execute()) {
    header("location: user_dashboard.php?escalated=1");
} else {
    echo "Error escalating complaint.";
}
?>
