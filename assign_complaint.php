<?php
session_start();
// Ensure only Admin is logged in (using your existing checks)
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin") {
    header("location: admin_login.php");
    exit;
}

require_once 'db_connect.php'; // Your database connection file

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['assign_complaint'])) {
    
    // Sanitize and capture POST data
    $complaint_id = (int)$_POST['complaint_id'];
    $caseworker_id = (int)$_POST['caseworker_id']; // This is the user_id

    // Check if the assignment is to unassign (value 0)
    if ($caseworker_id == 0) {
        // Unassign: Set assigned_to_id to NULL and status to 'Pending'
        $assigned_to_value = NULL;
        $new_status = 'Pending';
        $message_text = "Complaint #{$complaint_id} unassigned successfully. Status set to Pending.";
    } else {
        // Assign: Set assigned_to_id to the selected ID and status to 'In Progress'
        $assigned_to_value = $caseworker_id;
        $new_status = 'In Progress';
        $message_text = "Complaint #{$complaint_id} assigned successfully. Status set to In Progress.";
    }

    // Update the complaint record
    // We update both the assigned_to_id and the status in one go.
    $sql = "UPDATE complaints SET assigned_to_id = ?, status = ? WHERE complaint_id = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        // Binding parameters: 'i' for assigned_to_id (integer or NULL), 's' for status, 'i' for complaint_id
        if ($assigned_to_value === NULL) {
            // Use 's' for the parameter type of assigned_to_id if setting to NULL, and bind NULL
            $stmt->bind_param("ssi", $assigned_to_value, $new_status, $complaint_id);
        } else {
             // Bind integer for assignment
             $stmt->bind_param("isi", $assigned_to_value, $new_status, $complaint_id);
        }
       
        if ($stmt->execute()) {
            $_SESSION['update_message'] = '<div class="alert alert-success">' . $message_text . '</div>';
        } else {
            $_SESSION['update_message'] = '<div class="alert alert-danger">Error assigning complaint: ' . $conn->error . '</div>';
        }
        $stmt->close();
    } else {
        $_SESSION['update_message'] = '<div class="alert alert-danger">Database error: Could not prepare statement.</div>';
    }
    
    $conn->close();
}

// Always redirect back to the dashboard after processing
header("Location: admin_dashboard.php");
exit();
?>