<?php
session_start();
// Check if the user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "user") {
    header("location: user_login.php");
    exit;
}

include 'db_connect.php';

// Fetch user's complaints
$user_id = $_SESSION["user_id"];
$user_complaints = [];

// SELECT includes new columns: deadline, is_escalated, resolution_rating, feedback_text
$sql_fetch = "SELECT * FROM complaints WHERE user_id = ? ORDER BY submitted_at DESC";
if ($stmt = $conn->prepare($sql_fetch)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $user_complaints[] = $row;
    }
    $stmt->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>User Dashboard | EcoTrack</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
.image-thumb { width: 50px; height: 50px; object-fit: cover; border-radius: 5px; border: 1px solid #ddd; transition: transform 0.2s; }
.image-thumb:hover { transform: scale(1.1); }
.table-responsive td:nth-child(5) { max-width: 180px; white-space: normal; }
</style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-success sticky-top">
<div class="container">
<a class="navbar-brand fw-bold" href="index.php"><i class="bi bi-recycle"></i> EcoTrack</a>
<span class="navbar-text text-white me-3">Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?></span>
<a href="logout.php" class="btn btn-outline-light"><i class="bi bi-box-arrow-right"></i> Logout</a>
</div>
</nav>

<div class="container my-5">
<h1 class="mb-4 text-success"><i class="bi bi-house-door-fill"></i> Your EcoTrack Dashboard</h1>

<div class="row">
<!-- Register Complaint Card -->
<div class="col-md-4 mb-4">
<div class="card shadow h-100 border-primary">
<div class="card-body text-center">
<i class="bi bi-megaphone-fill display-4 text-primary mb-3"></i>
<h5 class="card-title">Register Complaint</h5>
<p class="card-text">Submit a new waste-related issue in your area.</p>
<a href="user_complain.php" class="btn btn-primary w-100">Lodge Complaint</a>
</div>
</div>
</div>

<!-- Track Complaints Card -->
<div class="col-md-8 mb-4">
<div class="card shadow h-100 border-info">
<div class="card-body">
<h5 class="card-title text-info"><i class="bi bi-geo-alt-fill"></i> Track My Complaints</h5>
<p class="card-text">View the status and history of your submissions.</p>

<div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
<table class="table table-sm table-hover align-middle">
<thead class="table-info">
<tr>
<th>ID</th>
<th>Title</th>
<th>Image</th>
<th>Location</th>
<th>Status / Feedback</th>
<th>Submitted On</th>
</tr>
</thead>
<tbody>
<?php if (empty($user_complaints)): ?>
<tr><td colspan="6" class="text-center">You have not submitted any complaints yet.</td></tr>
<?php endif; ?>

<?php foreach ($user_complaints as $complaint): ?>
<tr>
<td><?php echo $complaint['complaint_id']; ?></td>
<td><?php echo htmlspecialchars($complaint['complaint_title']); ?></td>

<td>
<?php $image_path = $complaint['image_path']; ?>
<?php if (!empty($image_path)): ?>
<a href="<?php echo htmlspecialchars($image_path); ?>" target="_blank" title="View Full Image">
<img src="<?php echo htmlspecialchars($image_path); ?>" alt="Complaint Image" class="image-thumb">
</a>
<?php else: ?>N/A<?php endif; ?>
</td>

<td><?php echo htmlspecialchars($complaint['location']); ?></td>

<td>
<?php
// Status badge
$badge_class = 'bg-secondary';
if ($complaint['status'] == 'Pending') $badge_class = 'bg-danger';
elseif ($complaint['status'] == 'In Progress') $badge_class = 'bg-warning text-dark';
elseif ($complaint['status'] == 'Resolved') $badge_class = 'bg-success';
?>
<span class="badge <?php echo $badge_class; ?>"><?php echo $complaint['status']; ?></span>

<!-- STEP 3: ESCALATION LOGIC -->
<?php

// Convert deadline if exists
$deadline_time = !empty($complaint['deadline']) ? strtotime($complaint['deadline']) : 0;
$now = time();

// If no deadline set → consider deadline not yet passed
$deadline_passed = ($deadline_time != 0 && $now > $deadline_time);

// Show Delayed badge
if ($deadline_passed && $complaint['status'] !== 'Resolved') {
    echo "<span class='badge bg-danger mt-1'>Delayed</span><br>";
}

// ESCALATE BUTTON — always visible
$disabled = "";
$btn_style = "btn-danger";

if ($complaint['status'] === 'Resolved') {
    $disabled = "disabled";
    $btn_style = "btn-secondary";
}
elseif ($complaint['is_escalated'] == 1) {
    $disabled = "disabled";
    $btn_style = "btn-warning text-dark";
}
elseif (!$deadline_passed) {
    $disabled = "disabled";
    $btn_style = "btn-outline-danger";
}

echo '
<a href="escalate_complaint.php?id=' . $complaint['complaint_id'] . '" 
   class="btn btn-sm ' . $btn_style . ' mt-1 w-100 ' . $disabled . '">
   <i class="bi bi-arrow-up-circle"></i> Escalate
</a>';

// Before deadline message
if (!$deadline_passed && $complaint['status'] !== 'Resolved' && $complaint['is_escalated'] == 0) {
    echo "<small class='text-muted small d-block mt-1'>
        Escalation available after deadline
    </small>";
}

// Already escalated badge
if ($complaint['is_escalated'] == 1) {
    echo "<span class='badge bg-warning text-dark mt-1'>Already Escalated</span>";
}
?>


<!-- Feedback / Rating -->
<?php if ($complaint['status'] == 'Resolved'): ?>
<?php if (empty($complaint['resolution_rating'])): ?>
<a href="user_feedback.php?id=<?php echo $complaint['complaint_id']; ?>" 
class="btn btn-sm btn-outline-warning mt-1 w-100"><i class="bi bi-star"></i> Rate!</a>
<?php else: ?>
<span class="text-success small d-block mt-1">Rated: 
<?php for ($i = 1; $i <= 5; $i++) {
    echo ($i <= $complaint['resolution_rating']) ? '<i class="bi bi-star-fill text-warning"></i>' : '<i class="bi bi-star"></i>';
} ?>
</span>
<?php endif; ?>
<?php endif; ?>
</td>

<td><?php echo date('Y-m-d', strtotime($complaint['submitted_at'])); ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
</div>
</div>
</div>

<!-- Tips Section -->
<div class="row mt-5">
<div class="col-12">
<div class="alert alert-success" role="alert">
<h4 class="alert-heading"><i class="bi bi-lightbulb-fill"></i> Recycling & Awareness Tips</h4>
<p>Proper segregation of dry and wet waste at home boosts recycling rates! Check out our FAQ for more tips.</p>
<a href="faq.php" class="btn btn-success btn-sm">Go to FAQ</a>
</div>
</div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
