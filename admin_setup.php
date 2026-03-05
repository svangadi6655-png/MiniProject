<?php
// Note: This file is intentionally NOT linked to the main website.
// It should be run only once to create the initial admin user.
// For security, you should delete or rename this file after setup is complete.

session_start();
include 'db_connect.php';

$message = "";
$username = $name = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $name = trim($_POST["name"]);
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];

    // 1. Basic Validation
    if (empty($username) || empty($password) || empty($confirm_password) || empty($name)) {
        $message = '<div class="alert alert-danger">All fields are required.</div>';
    } elseif ($password !== $confirm_password) {
        $message = '<div class="alert alert-danger">Passwords do not match.</div>';
    } elseif (strlen($password) < 6) {
        $message = '<div class="alert alert-danger">Password must be at least 6 characters long.</div>';
    } else {
        // 2. Check if username already exists
        $sql = "SELECT admin_id FROM admin WHERE username = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $param_username);
            $param_username = $username;
            $stmt->execute();
            $stmt->store_result();
            
            $action = $stmt->num_rows > 0 ? "UPDATE" : "INSERT";
            $stmt->close();

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            if ($action === "INSERT") {
                // 3. Insert new Admin
                $sql_insert = "INSERT INTO admin (username, name, password) VALUES (?, ?, ?)";
                if ($stmt_insert = $conn->prepare($sql_insert)) {
                    $stmt_insert->bind_param("sss", $username, $name, $hashed_password);
                    if ($stmt_insert->execute()) {
                        $message = '<div class="alert alert-success">New Admin account created successfully! You can now delete this file and login.</div>';
                        $username = $name = ""; // Clear form
                    } else {
                        $message = '<div class="alert alert-danger">Error creating admin account: ' . $conn->error . '</div>';
                    }
                    $stmt_insert->close();
                }
            } else {
                // 3. Update existing Admin (e.g., password reset)
                $sql_update = "UPDATE admin SET name = ?, password = ? WHERE username = ?";
                if ($stmt_update = $conn->prepare($sql_update)) {
                    $stmt_update->bind_param("sss", $name, $hashed_password, $username);
                    if ($stmt_update->execute()) {
                        $message = '<div class="alert alert-warning">Admin account **' . htmlspecialchars($username) . '** updated successfully (Name/Password changed)!</div>';
                    } else {
                        $message = '<div class="alert alert-danger">Error updating admin account.</div>';
                    }
                    $stmt_update->close();
                }
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
    <title>SECURE ADMIN SETUP | EcoTrack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-warning bg-opacity-10">
    <div class="container d-flex justify-content-center align-items-center" style="min-height: 100vh;">
        <div class="card shadow border-danger" style="max-width: 500px; width: 100%;">
            <div class="card-header bg-danger text-white text-center">
                <h4 class="mb-0"><i class="bi bi-person-fill-lock"></i> EcoTrack Admin Setup (HIGH SECURITY)</h4>
            </div>
            <div class="card-body p-4">
                <p class="text-danger fw-bold">**SECURITY WARNING:** This file should only be run by the Chief Developer/Manager. Rename or delete it after use.</p>
                <?php echo $message; ?>
                
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    
                    <div class="mb-3">
                        <label for="username" class="form-label">Admin Username (Unique Identifier)</label>
                        <input type="text" name="username" id="username" class="form-control" value="<?php echo htmlspecialchars($username); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="name" class="form-label">Full Name of Admin</label>
                        <input type="text" name="name" id="name" class="form-control" value="<?php echo htmlspecialchars($name); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" name="password" id="password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-danger">Create/Update Admin</button>
                    </div>
                </form>
                <hr>
                <p class="text-center mb-0">Once complete, <a href="admin_login.php">click here to test the login.</a></p>
            </div>
        </div>
    </div>
</body>
</html>