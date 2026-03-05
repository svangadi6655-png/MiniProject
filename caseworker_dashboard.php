<?php
session_start();

// 1. Security Check: Ensure a Caseworker is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "caseworker") {
    // If not a caseworker, redirect them out
    header("location: user_login.php"); 
    exit;
}

include 'db_connect.php';
// >>> 1. INCLUDE MAIL CONFIG FILE <<<
include 'mail_config.php';

$caseworker_id = $_SESSION["user_id"]; // Assuming you stored the ID as 'user_id' during login
$update_message = "";

// Check for update message from status change
if (isset($_SESSION['update_message'])) {
    $update_message = $_SESSION['update_message'];
    unset($_SESSION['update_message']);
}

// --- Status Update Logic for Caseworker ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    $complaint_id = (int)$_POST['complaint_id'];
    $new_status = $_POST['new_status'];
    
    // Caseworkers can only set status to 'In Progress' or 'Resolved'
    if (in_array($new_status, ['In Progress', 'Resolved'])) {
        
        // >>> 2. FETCH USER DATA FOR EMAIL (only if resolved) <<<
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

        // 3. Define the SQL based on status
        if ($new_status == 'Resolved') {
            // Set resolution_date (essential for a 'Resolved' status)
            $sql = "UPDATE complaints SET status = ?, resolution_date = NOW(), last_updated_by_id = ? WHERE complaint_id = ? AND assigned_to_id = ?";
            $param_types = "siii";
            $param_values = [$new_status, $caseworker_id, $complaint_id, $caseworker_id];
        } else {
            // For 'In Progress', just update status and last_updated_by_id
            $sql = "UPDATE complaints SET status = ?, last_updated_by_id = ? WHERE complaint_id = ? AND assigned_to_id = ?";
            $param_types = "siii";
            $param_values = [$new_status, $caseworker_id, $complaint_id, $caseworker_id];
        }
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param($param_types, ...$param_values);
            
            if ($stmt->execute()) {
                $_SESSION['update_message'] = '<div class="alert alert-success">Complaint #'.$complaint_id.' status updated to **'.$new_status.'**.</div>';
                
                // >>> 4. EMAIL SENDING LOGIC <<<
                if ($new_status == 'Resolved' && $user_data) {
                    if (send_resolution_notification(
                        $user_data['email'], 
                        $user_data['name'], 
                        $complaint_id, 
                        $user_data['complaint_title']
                    )) {
                        // Email sent successfully 
                    } else {
                        // Email failed to send, update the message
                        $_SESSION['update_message'] .= '<div class="alert alert-warning">Warning: Failed to send email notification to the user.</div>';
                    }
                }
                // >>> END EMAIL SENDING LOGIC <<<

            } else {
                $_SESSION['update_message'] = '<div class="alert alert-danger">Error updating status or complaint not assigned to you.</div>';
            }
            $stmt->close();
        }
    } else {
         $_SESSION['update_message'] = '<div class="alert alert-danger">Invalid status update.</div>';
    }
    header("Location: caseworker_dashboard.php");
    exit;
}
// --- END Status Update Logic ---

// --- Fetch Complaints Assigned to THIS Caseworker ---
$complaints = [];

$sql_fetch = "SELECT 
                c.*, 
                u.name as user_name, 
                u.area,
                u.email as user_email
              FROM complaints c 
              JOIN users u ON c.user_id = u.user_id 
              WHERE c.assigned_to_id = ? 
              ORDER BY 
                FIELD(c.status, 'In Progress', 'Pending', 'Resolved'), 
                c.submitted_at ASC";

if ($stmt_fetch = $conn->prepare($sql_fetch)) {
    $stmt_fetch->bind_param("i", $caseworker_id);
    $stmt_fetch->execute();
    $result = $stmt_fetch->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $complaints[] = $row;
    }
    $stmt_fetch->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Caseworker Dashboard | EcoTrack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .sidebar { min-height: 100vh; }
        .image-thumb { width: 50px; height: 50px; object-fit: cover; border-radius: 5px; cursor: pointer; }
    </style>
</head>
<body>
<div class="d-flex">
    <div class="d-flex flex-column flex-shrink-0 p-3 text-white bg-dark sidebar" style="width: 280px;">
        <a href="#" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
            <i class="bi bi-person-workspace me-2 fs-4 text-info"></i>
            <span class="fs-4">Case Worker Panel</span>
        </a>
        <hr>
        <ul class="nav nav-pills flex-column mb-auto">
            <li class="nav-item">
                <a href="caseworker_dashboard.php" class="nav-link active"><i class="bi bi-clipboard-check me-2"></i>My Assignments</a>
            </li>
        </ul>
        <hr>
        <div>
            <a href="logout.php" class="btn btn-danger w-100"><i class="bi bi-box-arrow-right me-2"></i>Logout</a>
        </div>
    </div>

    <div class="flex-grow-1 p-4">
<h1 class="mb-4 text-info"><i class="bi bi-clipboard-data-fill"></i> 
Welcome, <?php echo htmlspecialchars($_SESSION["name"] ?? "Case Worker"); ?>!
</h1>
        <p class="lead">Complaints assigned to you for resolution.</p>
        
        <?php if (!empty($update_message)) echo $update_message; ?>

        <hr>

        <h3 class="mb-3"><i class="bi bi-list-check me-2"></i>Assigned Complaints</h3>
        
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
                        <th>Submitted</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($complaints)): ?>
                        <tr><td colspan="8" class="text-center">No complaints currently assigned to you.</td></tr> 
                    <?php endif; ?>
                    
                    <?php foreach ($complaints as $complaint): ?>
                    <tr class="<?php echo ($complaint['status'] == 'In Progress') ? 'table-warning' : (($complaint['status'] == 'Resolved') ? 'table-success' : ''); ?>">
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
                        <td><?php echo date('Y-m-d H:i', strtotime($complaint['submitted_at'])); ?></td>

                        <td>
                            <?php if ($complaint['status'] != 'Resolved'): ?>
                                <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#updateModal"
                                        data-id="<?php echo $complaint['complaint_id']; ?>" 
                                        data-title="<?php echo htmlspecialchars($complaint['complaint_title']); ?>"
                                        data-current-status="<?php echo htmlspecialchars($complaint['status']); ?>">
                                    Update Status
                                </button>
                            <?php else: ?>
                                <span class="badge bg-success">Resolved</span>
                            <?php endif; ?>
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
        var newStatusSelect = updateModal.querySelector('#new_status');
        
        modalComplaintIdInput.value = complaintId;
        modalComplaintTitleText.textContent = complaintTitle;
        
        // Optionally set the current status as the default selected option
        var currentStatus = button.getAttribute('data-current-status');
        if (currentStatus) {
            newStatusSelect.value = currentStatus;
        }
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