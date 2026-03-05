<?php
session_start();
include 'db_connect.php';

// Initialize variables to prevent the 'Undefined variable' warning on page load (GET request)
$login_identifier = ""; 
$password = $error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // We use 'username' in the form, which maps to either admin.username or user.email
    $login_identifier = trim($_POST["username"]); 
    $password = trim($_POST["password"]);

    if (empty($login_identifier) || empty($password)) {
        $error = "Please enter both username/email and password.";
    } else {
        $found_user = false;

        // ===========================================
        // 1. ATTEMPT LOGIN AS PRIMARY ADMIN (FROM 'admin' table)
        // ===========================================
        // NOTE: We assume 'username' is the login field for the 'admin' table.
        $sql_admin = "SELECT admin_id, name, username, password FROM admin WHERE username = ?";
        
        if ($stmt_admin = $conn->prepare($sql_admin)) {
            $stmt_admin->bind_param("s", $login_identifier);
            
            if ($stmt_admin->execute()) {
                $stmt_admin->store_result();

                if ($stmt_admin->num_rows == 1) {
                    $stmt_admin->bind_result($admin_id, $name, $db_username, $hashed_password);
                    if ($stmt_admin->fetch()) {
                        
                        // Check password (using password_verify and keeping your 'admin123' fallback for legacy testing)
                        if (password_verify($password, $hashed_password) || $password == 'admin123') { 
                            
                            $_SESSION["loggedin"] = true;
                            $_SESSION["user_id"] = $admin_id; 
                            $_SESSION["name"] = $name;
                            $_SESSION["role"] = "admin"; // Set role explicitly
                            $found_user = true;
                            
                            header("location: admin_dashboard.php");
                            exit;
                        }
                    }
                }
                $stmt_admin->close();
            }
        }

        // ====================================================
        // 2. ATTEMPT LOGIN AS CASE WORKER (FROM 'users' table)
        // ====================================================
        // Only run this if the Admin login failed.
        if (!$found_user) { 
            // NOTE: We assume 'email' is the login field for the 'users' table.
            $sql_user = "SELECT user_id, name, email, password, role FROM users WHERE email = ? AND role IN ('caseworker', 'user')";
            
            if ($stmt_user = $conn->prepare($sql_user)) {
                $stmt_user->bind_param("s", $login_identifier);

                if ($stmt_user->execute()) {
                    $stmt_user->store_result();

                    if ($stmt_user->num_rows == 1) {
                        $stmt_user->bind_result($user_id, $name, $db_email, $hashed_password, $role);
                        if ($stmt_user->fetch()) {
                            
                            if (password_verify($password, $hashed_password)) {
                                $found_user = true;
                                
                                $_SESSION["loggedin"] = true;
                                $_SESSION["user_id"] = $user_id;
                                $_SESSION["name"] = $name;
                                $_SESSION["role"] = $role; // Will be 'caseworker' or 'user'

                                if ($role == 'caseworker') {
                                    header("location: caseworker_dashboard.php");
                                } else {
                                    // Handle standard user redirect if applicable
                                    header("location: user_dashboard.php"); 
                                }
                                exit;
                            }
                        }
                    }
                    $stmt_user->close();
                }
            }
        }

        // ===========================================
        // 3. FINAL ERROR CHECK
        // ===========================================
        if (!$found_user) {
            $error = "Invalid username/email or password.";
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
    <title>System Login | EcoTrack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-dark">
    <div class="container d-flex justify-content-center align-items-center" style="min-height: 100vh;">
        <div class="card shadow" style="max-width: 400px; width: 100%;">
            <div class="card-header bg-warning text-dark text-center">
                <h4 class="mb-0"><i class="bi bi-shield-lock-fill"></i> System Login</h4>
            </div>
            <div class="card-body p-4">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <?php if (!empty($error)) echo '<div class="alert alert-danger" role="alert">' . $error . '</div>'; ?>
                    
                    <div class="mb-3">
                        <label for="username" class="form-label">Username (Admin) / Email (CW)</label>
                        <input type="text" name="username" id="username" class="form-control" value="<?php echo htmlspecialchars($login_identifier); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" name="password" id="password" class="form-control" required>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-warning text-dark fw-bold">Login</button>
                    </div>
                </form>
                <p class="text-center mt-3"><a href="index.php"><i class="bi bi-arrow-left-circle"></i> Back to Home</a></p>
            </div>
        </div>
    </div>
</body>
</html>