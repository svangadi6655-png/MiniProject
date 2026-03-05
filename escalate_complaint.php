<?php
session_start();
include 'db_connect.php';

// Security check: only logged-in users can escalate
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "user") {
    header("location: user_login.php");
    exit;
}

// Check if complaint ID is set
if (!isset($_GET['id'])) {
    die("Invalid request.");
}

$complaint_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reason = trim($_POST['reason']);

    if (!empty($reason)) {
        // Update complaint: set is_escalated = 1, save reason and escalation date
        $sql = "UPDATE complaints 
                SET is_escalated = 1, escalation_reason = ?, escalation_date = NOW() 
                WHERE complaint_id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sii", $reason, $complaint_id, $user_id);

        if ($stmt->execute()) {
            // Redirect back with success message
            header("location: user_dashboard.php?escalated=1");
            exit;
        } else {
            $error = "Error escalating complaint. Please try again.";
        }

        $stmt->close();
    } else {
        $error = "Please provide a reason for escalation.";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Escalate Complaint</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="p-4">

<div class="container">
    <h2>Escalate Complaint</h2>

    <?php if(isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label for="reason" class="form-label">Reason for escalation:</label>
            <textarea id="reason" name="reason" class="form-control" rows="4" required></textarea>
        </div>
        <button type="submit" class="btn btn-danger">Submit Escalation</button>
        <a href="user_dashboard.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>

</body>
</html>
