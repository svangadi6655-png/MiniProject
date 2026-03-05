<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["role"] !== "higher_authority") {
    header("location: login.php");
    exit;
}

// Handle action from POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complaint_id'], $_POST['action'])) {
    $complaint_id = intval($_POST['complaint_id']);
    $action = $_POST['action']; // "Resolve" or "Reject"
    $notes = trim($_POST['notes']);

    if(in_array($action, ['Resolve','Reject'])) {
        $stmt = $conn->prepare("UPDATE complaints SET status = ?, resolution_notes = ? WHERE complaint_id = ?");
        $stmt->bind_param("ssi", $action, $notes, $complaint_id);
        $stmt->execute();
        $stmt->close();
    }
}

// Fetch escalated complaints
$sql = "SELECT c.complaint_id, c.user_id, u.name AS user_name, c.description, c.escalation_reason, c.escalation_date, c.status
        FROM complaints c
        JOIN users u ON c.user_id = u.user_id
        WHERE c.is_escalated = 1
        ORDER BY c.escalation_date DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Escalated Complaints - Higher Authority</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="p-4">
<div class="container">
    <h2 class="mb-4">Escalated Complaints</h2>

    <?php if($result->num_rows > 0): ?>
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>Complaint ID</th>
                    <th>User</th>
                    <th>Description</th>
                    <th>Reason for Escalation</th>
                    <th>Escalation Date</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['complaint_id']; ?></td>
                        <td><?php echo htmlspecialchars($row['user_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['description']); ?></td>
                        <td><?php echo htmlspecialchars($row['escalation_reason']); ?></td>
                        <td><?php echo date("d M Y, H:i", strtotime($row['escalation_date'])); ?></td>
                        <td><?php echo $row['status']; ?></td>
                        <td>
                            <?php if($row['status'] === 'Pending'): ?>
                                <form method="POST">
                                    <input type="hidden" name="complaint_id" value="<?php echo $row['complaint_id']; ?>">
                                    <textarea name="notes" class="form-control mb-1" placeholder="Optional notes"></textarea>
                                    <button type="submit" name="action" value="Resolve" class="btn btn-success btn-sm mb-1">Resolve</button>
                                    <button type="submit" name="action" value="Reject" class="btn btn-danger btn-sm">Reject</button>
                                </form>
                            <?php else: ?>
                                <span><?php echo htmlspecialchars($row['status']); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="alert alert-info">No escalated complaints at the moment.</div>
    <?php endif; ?>

</div>
</body>
</html>
