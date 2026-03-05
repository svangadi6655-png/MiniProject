<?php
session_start();
include 'db_connect.php';
// --- FIX 1: INCLUDE MAIL CONFIG FILE ---
include 'mail_config.php';

// Check if the admin is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin") {
    header("location: admin_login.php");
    exit;
}

// 1. Initialize variables for status updates and messages
$update_message = "";

// Check for update message from redirect (e.g., from assign_complaint.php or status update)
if (isset($_SESSION['update_message'])) {
    $update_message = $_SESSION['update_message'];
    unset($_SESSION['update_message']);
}

// *** FIX 2: Use the correct, standardized session key "user_id" ***
$admin_id = $_SESSION["user_id"]; 
$admin_name = $_SESSION["name"];

// --- Status Update and Email Logic ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    $complaint_id = $_POST['complaint_id'];
    $new_status = $_POST['new_status'];

    // Input validation
    if (!empty($complaint_id) && in_array($new_status, ['Pending', 'In Progress', 'Resolved'])) {
        
        // 1. Fetch user data and complaint details ONLY if status is 'Resolved' for email notification
        $user_data = null;
        if ($new_status == 'Resolved') {
            $sql_fetch_user = "
                SELECT c.complaint_title, u.email, u.name 
                FROM complaints c 
                JOIN users u ON c.user_id = u.user_id 
                WHERE c.complaint_id = ?
            ";
            if ($stmt_fetch = $conn->prepare($sql_fetch_user)) {
                $stmt_fetch->bind_param("i", $complaint_id);
                $stmt_fetch->execute();
                $result = $stmt_fetch->get_result();
                $user_data = $result->fetch_assoc();
                $stmt_fetch->close();
            }
        }

        // 2. Update the status in the database
        if ($new_status != 'Resolved') {
            
            // Smart un-assignment: If status is set back to 'Pending', we clear assignment and feedback
            if ($new_status == 'Pending') {
                $sql = "UPDATE complaints SET status = ?, resolved_by_admin_id = NULL, resolution_date = NULL, resolution_rating = NULL, feedback_text = NULL, assigned_to_id = NULL WHERE complaint_id = ?";
            } else {
                // For 'In Progress', just update the status
                $sql = "UPDATE complaints SET status = ?, resolved_by_admin_id = NULL, resolution_date = NULL, resolution_rating = NULL, feedback_text = NULL WHERE complaint_id = ?";
            }
            
            // Note: resolved_by_admin_id is set to NULL if status is not 'Resolved'
            $param_types = "si"; 
            $param_values = [$new_status, $complaint_id];

        } else {
            // Status is 'Resolved'. Set resolved_by_admin_id and resolution_date.
            $sql = "UPDATE complaints SET status = ?, resolved_by_admin_id = ?, resolution_date = NOW() WHERE complaint_id = ?";
            $param_types = "sii";
            $param_values = [$new_status, $admin_id, $complaint_id];
        }
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param($param_types, ...$param_values);
            
            if ($stmt->execute()) {
                // Success message
                $_SESSION['update_message'] = '<div class="alert alert-success">Complaint #'.$complaint_id.' status successfully updated to **'.$new_status.'**.</div>';
                
                // --- FIX 3: ADD EMAIL FUNCTION CALL ---
                if ($new_status == 'Resolved' && $user_data) {
                    
                    if (send_resolution_notification(
                        $user_data['email'], 
                        $user_data['name'], 
                        $complaint_id, 
                        $user_data['complaint_title']
                    )) {
                        // Email sent successfully (No further message needed)
                    } else {
                        // Email failed
                        $_SESSION['update_message'] .= '<div class="alert alert-warning">Warning: Failed to send email notification to the user. Check SMTP configuration in mail_config.php.</div>';
                    }
                }
                // --- END EMAIL FUNCTION CALL ---

            } else {
                $_SESSION['update_message'] = '<div class="alert alert-danger">Database error: Could not update status.</div>';
            }
            $stmt->close();
            
            header("location: admin_dashboard.php"); // Redirect after POST to prevent form resubmission
            exit;
        } else {
            $_SESSION['update_message'] = '<div class="alert alert-danger">Database error: Could not prepare statement.</div>';
            header("location: admin_dashboard.php");
            exit;
        }
    } else {
        $_SESSION['update_message'] = '<div class="alert alert-danger">Invalid complaint ID or status received.</div>';
        header("location: admin_dashboard.php");
        exit;
    }
}
// --- END: Status Update and Email Logic ---


// --- Fetch Caseworkers for the dropdown ---
$caseworkers = [];
$sql_caseworkers = "SELECT user_id, name FROM users WHERE role = 'caseworker' ORDER BY name ASC";
$cw_result = $conn->query($sql_caseworkers);
if ($cw_result && $cw_result->num_rows > 0) {
    while($cw_row = $cw_result->fetch_assoc()) {
        $caseworkers[] = $cw_row;
    }
}
// --- END Fetch Caseworkers ---

// Fetch all complaints and join with user table to get user details
$complaints = [];

$sql_fetch = "SELECT 
                c.*, 
                u.name as user_name, 
                u.area,
                a.name as assigned_caseworker_name 
             FROM complaints c 
             JOIN users u ON c.user_id = u.user_id 
             LEFT JOIN users a ON c.assigned_to_id = a.user_id
             ORDER BY 
                FIELD(c.status, 'Pending', 'In Progress', 'Resolved'), 
                c.submitted_at ASC"; 

$result = $conn->query($sql_fetch);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $complaints[] = $row;
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | EcoTrack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .sidebar { min-height: 100vh; }
        .image-thumb {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 5px;
            cursor: pointer;
            transition: transform 0.1s;
        }
        .image-thumb:hover { transform: scale(1.05); }
        .table-danger, .table-warning {
            border-left: 5px solid; 
            font-weight: 600;
        }
        .table-danger { border-left-color: #dc3545; }
        .table-warning { border-left-color: #ffc107; }
        .assign-form {
            display: flex;
            min-width: 220px; 
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
            <li class="nav-item">
                <a href="admin_dashboard.php" class="nav-link active" aria-current="page"><i class="bi bi-table me-2"></i>View Complaints</a>
            </li>
            <li>
                <a href="admin_reports.php" class="nav-link text-white"><i class="bi bi-graph-up me-2"></i>Reports</a>
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
        <h1 class="mb-4 text-success"><i class="bi bi-clipboard-data-fill"></i> Welcome, <?php echo htmlspecialchars($admin_name); ?>!</h1>
        <p class="lead">Manage and resolve municipal waste complaints.</p>
        
        <?php if (!empty($update_message)) echo $update_message; ?>

        <hr>

        <h3 class="mb-3"><i class="bi bi-sort-down me-2"></i>All Complaints (Sorted by Priority)</h3>
        
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Image</th> 
                        <th>User Name/Area</th>
                        <th>Issue</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Feedback/Rating</th> 
                        <th>Submitted</th>
                        <th>Assign</th> <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($complaints)): ?>
                        <tr><td colspan="10" class="text-center">No complaints registered yet.</td></tr> 
                    <?php endif; ?>
                    
                    <?php foreach ($complaints as $complaint): ?>
                    <?php 
                        $row_class = '';
                        if ($complaint['status'] == 'Pending') {
                            $row_class = 'table-danger'; 
                        } elseif ($complaint['status'] == 'In Progress') {
                            $row_class = 'table-warning';
                        }
                    ?>
                    <tr class="<?php echo $row_class; ?>">
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
                            <?php if ($complaint['status'] == 'Resolved'): ?>
                                <?php if (!empty($complaint['resolution_rating'])): ?>
                                    <span class="text-warning d-block small mb-1">
                                        <?php 
                                            for ($i = 1; $i <= 5; $i++) {
                                                echo ($i <= $complaint['resolution_rating']) ? '<i class="bi bi-star-fill"></i>' : '<i class="bi bi-star"></i>';
                                            }
                                        ?>
                                          (<?php echo $complaint['resolution_rating']; ?>/5)
                                    </span>
                                    <?php if (!empty($complaint['feedback_text'])): ?>
                                        <p class="small mt-1 mb-0 text-muted fst-italic">"<?php echo htmlspecialchars($complaint['feedback_text']); ?>"</p>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="badge bg-info">Awaiting User Feedback</span>
                                <?php endif; ?>
                            <?php else: ?>
                                N/A
                            <?php endif; ?>
                        </td>

                        <td><?php echo date('Y-m-d H:i', strtotime($complaint['submitted_at'])); ?></td>

                        <td>
                            <?php if ($complaint['assigned_to_id'] !== NULL && $complaint['status'] != 'Resolved'): ?>
                                <small>Assigned:</small>
                                <strong class="d-block text-primary"><?php echo htmlspecialchars($complaint['assigned_caseworker_name']); ?></strong>
                                
                                <form action="assign_complaint.php" method="POST" class="assign-form mt-1">
                                    <input type="hidden" name="complaint_id" value="<?php echo $complaint['complaint_id']; ?>">
                                    <select name="caseworker_id" class="form-select form-select-sm me-1">
                                        <option value="0">Unassign</option>
                                        <?php foreach ($caseworkers as $caseworker): ?>
                                            <option value="<?php echo $caseworker['user_id']; ?>"
                                                <?php echo ($complaint['assigned_to_id'] == $caseworker['user_id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($caseworker['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" name="assign_complaint" class="btn btn-primary btn-sm" title="Re-assign">
                                        <i class="bi bi-arrow-repeat"></i>
                                    </button>
                                </form>

                            <?php elseif ($complaint['status'] == 'Resolved'): ?>
                                <span class="badge bg-light text-dark">Completed</span>

                            <?php else: ?>
                                <form action="assign_complaint.php" method="POST" class="assign-form">
                                    <input type="hidden" name="complaint_id" value="<?php echo $complaint['complaint_id']; ?>">
                                    <select name="caseworker_id" class="form-select form-select-sm me-1" required>
                                        <option value="" disabled selected>Select...</option>
                                        <?php foreach ($caseworkers as $caseworker): ?>
                                            <option value="<?php echo $caseworker['user_id']; ?>">
                                                <?php echo htmlspecialchars($caseworker['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" name="assign_complaint" class="btn btn-primary btn-sm" title="Assign">
                                        <i class="bi bi-send-check"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#updateModal"
                                    data-id="<?php echo $complaint['complaint_id']; ?>" 
                                    data-title="<?php echo htmlspecialchars($complaint['complaint_title']); ?>">
                                Update
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="updateModal" tabindex="-1" aria-labelledby="updateModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-warning">
        <h5 class="modal-title" id="updateModalLabel">Update Complaint Status</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
          <div class="modal-body">
            <p>Complaint: <strong id="modal-complaint-title"></strong></p>
            <input type="hidden" name="complaint_id" id="modal-complaint-id">
            <div class="mb-3">
                <label for="new_status" class="form-label">New Status</label>
                <select class="form-select" name="new_status" id="new_status" required>
                    <option value="Pending">Pending</option>
                    <option value="In Progress">In Progress</option>
                    <option value="Resolved">Resolved</option>
                </select>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="submit" name="update_status" class="btn btn-warning">Save changes</button>
          </div>
      </form>
    </div>
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
    // Script to pass complaint data to the status update modal
    var updateModal = document.getElementById('updateModal');
    updateModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var complaintId = button.getAttribute('data-id');
        var complaintTitle = button.getAttribute('data-title');
        
        var modalComplaintIdInput = updateModal.querySelector('#modal-complaint-id');
        var modalComplaintTitleText = updateModal.querySelector('#modal-complaint-title');
        
        modalComplaintIdInput.value = complaintId;
        modalComplaintTitleText.textContent = complaintTitle;
    });

    // SCRIPT FOR IMAGE MODAL
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