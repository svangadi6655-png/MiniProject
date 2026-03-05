<?php
session_start();
// Security check: Ensure user is logged in and has the "user" role
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "user") {
    header("location: user_login.php");
    exit;
}

include 'db_connect.php';

$title = $description = $location = $message = "";
$user_id = $_SESSION["user_id"];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST["title"]);
    $description = trim($_POST["description"]);
    $location = trim($_POST["location"]);
    $image_path = null; // Initialize to null

    // Simple validation
    if (empty($title) || empty($description) || empty($location)) {
        $message = '<div class="alert alert-danger">Please fill in all required fields.</div>';
    } else {

        // --- 1. Image Upload Logic Start ---
        if (isset($_FILES["image"]) && $_FILES["image"]["error"] == 0) {
            
            $allowed = array("jpg" => "image/jpg", "jpeg" => "image/jpeg", "gif" => "image/gif", "png" => "image/png");
            $maxsize = 5 * 1024 * 1024; // 5MB
            $upload_dir = "uploads/";
            
            $filename = $_FILES["image"]["name"];
            $filetype = $_FILES["image"]["type"];
            $filesize = $_FILES["image"]["size"];
            
            $ext = pathinfo($filename, PATHINFO_EXTENSION);

            if(!array_key_exists(strtolower($ext), $allowed)) {
                $message = '<div class="alert alert-danger">Error: Please select a valid file format (JPG, JPEG, PNG, GIF).</div>';
            }

            if($filesize > $maxsize) {
                $message = '<div class="alert alert-danger">Error: File size must be less than 5MB.</div>';
            }

            if(empty($message) && !in_array($filetype, $allowed)){
                 $message = '<div class="alert alert-danger">Error: Invalid file type detected.</div>';
            }

            if (empty($message)) {
                $new_filename = uniqid('comp_' . $user_id . '_', true) . "." . strtolower($ext);
                $target_file = $upload_dir . $new_filename;

                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                    $image_path = $target_file;
                } else {
                    $message = '<div class="alert alert-danger">Error: File upload failed (Check permissions).</div>';
                }
            }
        }
        // --- 1. Image Upload Logic End ---

        // Only proceed if no upload errors
        if (empty($message)) {

            // -------------------------------------
            // Add 48-hour deadline for complaint
            // -------------------------------------
            $deadline = date('Y-m-d H:i:s', strtotime('+48 hours'));

            // Updated SQL statement: added image_path + deadline
            $sql = "INSERT INTO complaints (user_id, complaint_title, description, location, image_path, deadline) 
                    VALUES (?, ?, ?, ?, ?, ?)";

            if ($stmt = $conn->prepare($sql)) {

                // i = user_id, s = title, s = desc, s = location, s = image_path, s = deadline
                $stmt->bind_param("isssss", $user_id, $title, $description, $location, $image_path, $deadline);

                if ($stmt->execute()) {
                    $message = '<div class="alert alert-success">Complaint registered successfully! You can track its status on your dashboard.</div>';
                    $title = $description = $location = "";
                } else {
                    $message = '<div class="alert alert-danger">Error submitting complaint to database.</div>';
                }

                $stmt->close();
            } else {
                $message = '<div class="alert alert-danger">Database preparation error.</div>';
            }
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Complaint | EcoTrack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-success sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="user_dashboard.php"><i class="bi bi-arrow-left-circle-fill me-2"></i> Back to Dashboard</a>
        <a href="logout.php" class="btn btn-outline-light"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </div>
</nav>

<div class="container my-5">
    <div class="card shadow p-4">
        <h2 class="card-title text-primary mb-4"><i class="bi bi-exclamation-octagon-fill"></i> New Complaint Form</h2>

        <?php echo $message; ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="title" class="form-label">Complaint Title (e.g., Overflowing Bin)</label>
                <input type="text" name="title" id="title" class="form-control" value="<?php echo htmlspecialchars($title); ?>" required>
            </div>

            <div class="mb-3">
                <label for="location" class="form-label">Location / Landmark (Crucial for Admin)</label>
                <input type="text" name="location" id="location" class="form-control" value="<?php echo htmlspecialchars($location); ?>" required>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Detailed Description of the Issue</label>
                <textarea name="description" id="description" class="form-control" rows="4" required><?php echo htmlspecialchars($description); ?></textarea>
            </div>

            <div class="mb-4">
                <label for="image" class="form-label">Upload Photo (Optional)</label>
                <input type="file" name="image" id="image" class="form-control" accept="image/*">
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary btn-lg">Submit Complaint</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
