<?php
session_start();
include 'db_connect.php';

// Check if the user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "user") {
    header("location: user_login.php");
    exit;
}

$user_id = $_SESSION["user_id"];
$complaint = null;
$error = '';
$success_message = '';

// 1. Handle POST Request (Form Submission)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_feedback'])) {
    $complaint_id_post = $_POST['complaint_id'] ?? '';
    $rating = $_POST['rating'] ?? '';
    $feedback_text = trim($_POST['feedback_text'] ?? '');

    // Basic Validation
    if (empty($complaint_id_post) || empty($rating) || !is_numeric($rating) || $rating < 1 || $rating > 5) {
        $error = "Invalid complaint ID or rating submitted.";
    } else {
        // Sanitize feedback text
        $safe_feedback_text = htmlspecialchars($feedback_text);

        // Prepare UPDATE statement
        $sql_update = "UPDATE complaints SET resolution_rating = ?, feedback_text = ? WHERE complaint_id = ? AND user_id = ? AND status = 'Resolved'";
        
        if ($stmt = $conn->prepare($sql_update)) {
            $stmt->bind_param("isii", $rating, $safe_feedback_text, $complaint_id_post, $user_id);

            if ($stmt->execute()) {
                // Success: Redirect back to the dashboard with a success message
                header("location: user_dashboard.php?feedback_submitted=success");
                exit;
            } else {
                $error = "Error updating record: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $error = "Database error preparing update statement.";
        }
    }
}

// 2. Handle GET Request (Initial Page Load)
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $complaint_id = $_GET['id'];
    
    // Fetch complaint details (Security: ensure it belongs to the logged-in user and is Resolved)
    $sql_fetch = "SELECT complaint_id, complaint_title, status FROM complaints WHERE complaint_id = ? AND user_id = ?";
    
    if ($stmt = $conn->prepare($sql_fetch)) {
        $stmt->bind_param("ii", $complaint_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $complaint = $result->fetch_assoc();
            
            // Critical check: Only allow rating if the status is 'Resolved'
            if ($complaint['status'] !== 'Resolved') {
                $error = "This complaint is not yet marked as resolved and cannot be rated.";
                $complaint = null; // Prevent the form from showing
            }
        } else {
            $error = "Complaint not found or you do not have permission to view it.";
        }
        $stmt->close();
    }
} else {
    $error = "Invalid complaint ID specified.";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Feedback | EcoTrack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .star-rating {
            display: inline-block;
            font-size: 2rem;
        }
        .star-rating input[type="radio"] {
            display: none;
        }
        .star-rating label {
            float: right;
            cursor: pointer;
            color: #ccc;
            transition: color 0.3s;
        }
        .star-rating label:before {
            content: '\2605'; /* Unicode star */
        }
        .star-rating input[type="radio"]:checked ~ label {
            color: orange;
        }
        .star-rating label:hover,
        .star-rating label:hover ~ label {
            color: #ffda6a;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-success sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="user_dashboard.php"><i class="bi bi-arrow-left-circle-fill"></i> Back to Dashboard</a>
        <span class="navbar-text text-white me-3">Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?></span>
        <a href="logout.php" class="btn btn-outline-light"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </div>
</nav>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header bg-warning text-dark">
                    <h3 class="mb-0"><i class="bi bi-star-fill"></i> Resolution Feedback</h3>
                </div>
                <div class="card-body">

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger" role="alert"><?php echo $error; ?></div>
                        <a href="user_dashboard.php" class="btn btn-secondary mt-3">Return to Dashboard</a>
                    <?php endif; ?>

                    <?php if ($complaint): ?>
                        <h4 class="card-title mb-4">Complaint ID: #<?php echo htmlspecialchars($complaint['complaint_id']); ?></h4>
                        <p class="lead">**Title:** <?php echo htmlspecialchars($complaint['complaint_title']); ?></p>
                        
                        <hr>

                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                            <input type="hidden" name="complaint_id" value="<?php echo htmlspecialchars($complaint['complaint_id']); ?>">

                            <div class="mb-4">
                                <label class="form-label d-block fw-bold">1. Rate the Resolution:</label>
                                
                                <div class="star-rating d-flex flex-row-reverse justify-content-start">
                                    <input type="radio" id="star5" name="rating" value="5" required><label for="star5" title="Excellent"></label>
                                    <input type="radio" id="star4" name="rating" value="4"><label for="star4" title="Good"></label>
                                    <input type="radio" id="star3" name="rating" value="3"><label for="star3" title="Average"></label>
                                    <input type="radio" id="star2" name="rating" value="2"><label for="star2" title="Poor"></label>
                                    <input type="radio" id="star1" name="rating" value="1"><label for="star1" title="Very Poor"></label>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="feedback_text" class="form-label fw-bold">2. Optional Feedback Comments:</label>
                                <textarea name="feedback_text" id="feedback_text" class="form-control" rows="4" placeholder="Share any comments on the resolution process (e.g., timeliness, quality of work)."></textarea>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" name="submit_feedback" class="btn btn-warning btn-lg fw-bold"><i class="bi bi-send-fill"></i> Submit Feedback</button>
                            </div>
                        </form>

                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>