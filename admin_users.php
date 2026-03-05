<?php
session_start();
// Check if the admin is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== "admin") {
    header("location: admin_login.php");
    exit;
}

include 'db_connect.php';

// --- Placeholder for Success/Error Messages ---
$message = '';
$message_type = '';
if (isset($_SESSION['setup_message'])) {
    $message = $_SESSION['setup_message'];
    $message_type = $_SESSION['setup_type'];
    unset($_SESSION['setup_message']);
    unset($_SESSION['setup_type']);
}
// --- END Placeholder ---

// --- NEW/MODIFIED: Logic to Handle User Creation ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_user'])) {
    $name = trim($_POST['name']);
    // --- CORRECTED: Using 'email' instead of 'username' ---
    $email = trim($_POST['email']); 
    $password = $_POST['password'];
    $role = $_POST['role']; 
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // SQL to insert into the 'users' table. NOTE: COLUMN 'email' USED HERE.
    // Assuming 'area' is not required for system users (Admins/Caseworkers)
    $sql_insert = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)";
    
    if ($stmt_insert = $conn->prepare($sql_insert)) {
        // Binding parameters: ssss for string, string, string, string
        $stmt_insert->bind_param("ssss", $name, $email, $hashed_password, $role);
        if ($stmt_insert->execute()) {
            $_SESSION['setup_message'] = 'User (Role: ' . htmlspecialchars($role) . ') added successfully!';
            $_SESSION['setup_type'] = 'success';
        } else {
            // Check for duplicate entry error (e.g., duplicate email)
            $error_msg = strpos($conn->error, 'Duplicate entry') !== false ? 'Error: Email/Username already exists.' : 'Error adding user: ' . $conn->error;
            $_SESSION['setup_message'] = $error_msg;
            $_SESSION['setup_type'] = 'danger';
        }
        $stmt_insert->close();
    } else {
        $_SESSION['setup_message'] = 'Database error: Could not prepare statement.';
        $_SESSION['setup_type'] = 'danger';
    }
    // Redirect to clear POST data and show message
    header("Location: admin_users.php"); 
    exit;
}
// --- END NEW/MODIFIED POST LOGIC ---

// --- Fetch ALL Admins/Case Workers for Display ---
$all_users = [];
// --- CORRECTED: Selecting 'email' instead of 'username' (Line 59 in original error) ---
$sql_fetch_all = "SELECT user_id, name, email, role FROM users WHERE role IN ('admin', 'caseworker') ORDER BY role DESC, name ASC";
$result_all = $conn->query($sql_fetch_all);
if ($result_all && $result_all->num_rows > 0) {
    while($row = $result_all->fetch_assoc()) {
        $all_users[] = $row;
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Admins & Case Workers | EcoTrack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
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
                <a href="admin_dashboard.php" class="nav-link text-white" aria-current="page"><i class="bi bi-table me-2"></i>View Complaints</a>
            </li>
            <li>
                <a href="admin_reports.php" class="nav-link text-white"><i class="bi bi-graph-up me-2"></i>Reports</a>
            </li>
            <li>
                <a href="admin_users.php" class="nav-link active"><i class="bi bi-people-fill me-2"></i>Manage Admins</a>
            </li>
        </ul>
        <hr>
        <div>
            <a href="logout.php" class="btn btn-danger w-100"><i class="bi bi-box-arrow-right me-2"></i>Logout</a>
        </div>
    </div>
    
    <div class="flex-grow-1 p-4">
        <h1 class="mb-4"><i class="bi bi-people-fill"></i> Manage System Users</h1>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="card mb-5">
            <div class="card-header bg-success text-white">
                <h4>Add New System User (Admin or Case Worker)</h4>
            </div>
            <div class="card-body">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="col-md-4">
                            <label for="email" class="form-label">Email (for login)</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="col-md-4">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <div class="col-md-4">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="admin">Primary Admin</option> 
                                <option value="caseworker">Case Worker</option>
                            </select>
                        </div>
                        
                        <div class="col-12 mt-4">
                            <button type="submit" name="add_user" class="btn btn-success"><i class="bi bi-plus-circle me-2"></i>Create User</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <h3>Existing System Users</h3>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($all_users)): ?>
                        <tr><td colspan="4" class="text-center">No additional users found.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($all_users as $user): ?>
                    <tr>
                        <td><?php echo $user['user_id']; ?></td>
                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td>
                            <span class="badge <?php echo ($user['role'] == 'admin') ? 'bg-danger' : 'bg-primary'; ?>">
                                <?php echo ucfirst($user['role']); ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>