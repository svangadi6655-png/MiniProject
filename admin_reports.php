<?php
session_start();
include 'db_connect.php';

// Check if the admin is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin") {
    header("location: admin_login.php");
    exit;
}

$admin_name = $_SESSION["name"];
$selected_report = isset($_GET['report']) ? $_GET['report'] : null;
$report_title = "";
$report_data = [];
$report_sql = "";

// --- 1. Fetch Counts for Cards ---
$counts = [
    'total' => 0,
    'pending' => 0,
    'in_progress' => 0,
    'resolved' => 0,
    'assigned' => 0 // NEW COUNT
];

// Total Registered Complaints
$counts['total'] = $conn->query("SELECT COUNT(*) FROM complaints")->fetch_row()[0];

// Pending Action (Status = 'Pending')
$counts['pending'] = $conn->query("SELECT COUNT(*) FROM complaints WHERE status = 'Pending'")->fetch_row()[0];

// In Progress (Status = 'In Progress')
$counts['in_progress'] = $conn->query("SELECT COUNT(*) FROM complaints WHERE status = 'In Progress'")->fetch_row()[0];

// Successfully Resolved (Status = 'Resolved')
$counts['resolved'] = $conn->query("SELECT COUNT(*) FROM complaints WHERE status = 'Resolved'")->fetch_row()[0];

// Total Assigned Complaints (assigned_to_id IS NOT NULL and status NOT 'Resolved')
// We exclude 'Resolved' since those are technically complete assignments.
$counts['assigned'] = $conn->query("SELECT COUNT(*) FROM complaints WHERE assigned_to_id IS NOT NULL AND status != 'Resolved'")->fetch_row()[0];


// --- 2. Handle Detailed Report Fetch ---
if ($selected_report) {
    switch ($selected_report) {
        case 'total':
            $report_title = "Total Registered Complaints";
            $report_sql = "SELECT c.*, u.name as user_name, u.area, a.name as assigned_caseworker_name FROM complaints c JOIN users u ON c.user_id = u.user_id LEFT JOIN users a ON c.assigned_to_id = a.user_id";
            break;
        case 'pending':
            $report_title = "Pending Action Complaints";
            $report_sql = "SELECT c.*, u.name as user_name, u.area, a.name as assigned_caseworker_name FROM complaints c JOIN users u ON c.user_id = u.user_id LEFT JOIN users a ON c.assigned_to_id = a.user_id WHERE c.status = 'Pending'";
            break;
        case 'in_progress':
            $report_title = "In Progress Complaints";
            $report_sql = "SELECT c.*, u.name as user_name, u.area, a.name as assigned_caseworker_name FROM complaints c JOIN users u ON c.user_id = u.user_id LEFT JOIN users a ON c.assigned_to_id = a.user_id WHERE c.status = 'In Progress'";
            break;
        case 'resolved':
            $report_title = "Successfully Resolved Complaints";
            $report_sql = "SELECT c.*, u.name as user_name, u.area, a.name as assigned_caseworker_name FROM complaints c JOIN users u ON c.user_id = u.user_id LEFT JOIN users a ON c.assigned_to_id = a.user_id WHERE c.status = 'Resolved'";
            break;
        case 'assigned':
            $report_title = "Currently Assigned Complaints";
            // Filter: assigned to someone AND not yet resolved
            $report_sql = "SELECT c.*, u.name as user_name, u.area, a.name as assigned_caseworker_name FROM complaints c JOIN users u ON c.user_id = u.user_id LEFT JOIN users a ON c.assigned_to_id = a.user_id WHERE c.assigned_to_id IS NOT NULL AND c.status != 'Resolved'";
            break;
    }

    if ($report_sql) {
        $result = $conn->query($report_sql . " ORDER BY c.submitted_at DESC");
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $report_data[] = $row;
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
    <title>Admin Reports | EcoTrack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .sidebar { min-height: 100vh; }
        .card-metric {
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .card-metric:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .image-thumb {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 5px;
            cursor: pointer;
        }
        /* Ensure the total width is handled by 12 columns in both rows */
        .card-row-2 { 
            margin-top: 1rem; /* Add some vertical space between rows */
        }
    </style>
</head>
<body>
<div class="d-flex">
    <div class="d-flex flex-column flex-shrink-0 p-3 text-white bg-dark sidebar" style="width: 280px;">
        <a href="#" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
            <i class="bi bi-gear-fill me-2 fs-4 text-warning"></i>
            <span class="fs-4">Admin Panel</span>
        </a>
        <hr>
        <ul class="nav nav-pills flex-column mb-auto">
            <li>
                <a href="admin_dashboard.php" class="nav-link text-white"><i class="bi bi-table me-2"></i>View Complaints</a>
            </li>
            <li class="nav-item">
                <a href="admin_reports.php" class="nav-link active" aria-current="page"><i class="bi bi-graph-up me-2"></i>Reports</a>
            </li>
            <li>
                <a href="admin_setup.php" class="nav-link text-white"><i class="bi bi-people-fill me-2"></i>Manage Admins</a>
            </li>
        </ul>
        <hr>
        <div>
            <a href="logout.php" class="btn btn-danger w-100"><i class="bi bi-box-arrow-right me-2"></i>Logout</a>
        </div>
    </div>

    <div class="flex-grow-1 p-4">
        <h1 class="mb-4 text-primary"><i class="bi bi-graph-up-arrow"></i> System Reports & Analytics</h1>
        <p class="lead">Click any card below to view the detailed list of complaints.</p>
        
        <div class="row g-4 mb-5 text-center">
            
            <div class="col-md-3">
                <a href="?report=total" class="text-decoration-none">
                    <div class="card bg-dark text-white card-metric">
                        <div class="card-body">
                            <h2 class="card-title"><?php echo $counts['total']; ?></h2>
                            <p class="card-text"><i class="bi bi-journal-check me-1"></i>Total Registered</p>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-md-3">
                <a href="?report=pending" class="text-decoration-none">
                    <div class="card bg-danger text-white card-metric">
                        <div class="card-body">
                            <h2 class="card-title"><?php echo $counts['pending']; ?></h2>
                            <p class="card-text"><i class="bi bi-clock-history me-1"></i>Pending Action</p>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-md-3">
                <a href="?report=in_progress" class="text-decoration-none">
                    <div class="card bg-warning text-dark card-metric">
                        <div class="card-body">
                            <h2 class="card-title"><?php echo $counts['in_progress']; ?></h2>
                            <p class="card-text"><i class="bi bi-gear-fill me-1"></i>In Progress</p>
                        </div>
                    </div>
                </a>
            </div>
            
            <div class="col-md-3">
                <a href="?report=resolved" class="text-decoration-none">
                    <div class="card bg-success text-white card-metric">
                        <div class="card-body">
                            <h2 class="card-title"><?php echo $counts['resolved']; ?></h2>
                            <p class="card-text"><i class="bi bi-check-circle-fill me-1"></i>Successfully Resolved</p>
                        </div>
                    </div>
                </a>
            </div>
            
            <div class="col-12 card-row-2">
                <div class="row justify-content-center g-4">
                    <div class="col-md-3">
                        <a href="?report=assigned" class="text-decoration-none">
                            <div class="card bg-info text-dark card-metric">
                                <div class="card-body">
                                    <h2 class="card-title"><?php echo $counts['assigned']; ?></h2>
                                    <p class="card-text"><i class="bi bi-person-check-fill me-1"></i>Total Assigned</p>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

        </div>

        <hr>

        <?php if ($selected_report): ?>
            <h3 class="mb-3 text-secondary"><i class="bi bi-list-ul me-2"></i><?php echo htmlspecialchars($report_title); ?> (<?php echo count($report_data); ?>)</h3>

            <?php if (empty($report_data)): ?>
                <div class="alert alert-info">No complaints found in this category.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Image</th> 
                                <th>User/Area</th>
                                <th>Issue</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Assigned To</th> 
                                <th>Submitted</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report_data as $complaint): ?>
                            <tr>
                                <td><?php echo $complaint['complaint_id']; ?></td>
                                
                                <td>
                                    <?php if (!empty($complaint['image_path'])): ?>
                                        <img src="<?php echo htmlspecialchars($complaint['image_path']); ?>" 
                                            alt="Complaint Image" 
                                            class="image-thumb" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#imageModal" 
                                            data-image-path="<?php echo htmlspecialchars($complaint['image_path']); ?>">
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($complaint['user_name']) . " <br><small>(" . htmlspecialchars($complaint['area']) . ")</small>"; ?></td>
                                <td><?php echo htmlspecialchars($complaint['complaint_title']); ?></td>
                                <td><?php echo substr(htmlspecialchars($complaint['description']), 0, 50) . '...'; ?></td>
                                <td>
                                    <?php 
                                        $badge_class = 'bg-secondary';
                                        if ($complaint['status'] == 'Pending') $badge_class = 'bg-danger';
                                        elseif ($complaint['status'] == 'In Progress') $badge_class = 'bg-warning text-dark';
                                        elseif ($complaint['status'] == 'Resolved') $badge_class = 'bg-success';
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>"><?php echo $complaint['status']; ?></span>
                                </td>
                                
                                <td>
                                    <?php 
                                        echo $complaint['assigned_caseworker_name'] ? htmlspecialchars($complaint['assigned_caseworker_name']) : '<span class="text-muted">N/A</span>'; 
                                    ?>
                                </td>
                                
                                <td><?php echo date('Y-m-d H:i', strtotime($complaint['submitted_at'])); ?></td>

                                <td>
                                    <a href="admin_dashboard.php#complaint-<?php echo $complaint['complaint_id']; ?>" class="btn btn-sm btn-info">
                                        View/Action
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="alert alert-secondary text-center">
                <i class="bi bi-arrow-up-circle-fill fs-3 mb-2"></i>
                <h4 class="m-0">Select a Statistic Above</h4>
                <p class="m-0">Click on any of the metric cards to view the detailed list of complaints in that category.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-dark text-white">
        <h5 class="modal-title" id="imageModalLabel">Complaint Image Preview</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <img id="fullImage" src="" class="img-fluid rounded" alt="Complaint Image">
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // SCRIPT FOR IMAGE MODAL (copied from dashboard for consistency)
    var imageModal = document.getElementById('imageModal');
    imageModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var imagePath = button.getAttribute('data-image-path');
        var modalImage = imageModal.querySelector('#fullImage');
        
        modalImage.src = imagePath;
    });
</script>
</body>
</html>