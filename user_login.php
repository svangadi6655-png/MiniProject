<?php
session_start();
include 'db_connect.php';

$email = $password = $error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        $sql = "SELECT user_id, name, password, role FROM users WHERE email = ?";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $param_email);
            $param_email = $email;

            if ($stmt->execute()) {
                $stmt->store_result();

                if ($stmt->num_rows == 1) {
                    $stmt->bind_result($user_id, $name, $hashed_password, $db_role);
                    if ($stmt->fetch()) {
                        if (password_verify($password, $hashed_password)) {

                            $_SESSION["loggedin"] = true;
                            $_SESSION["user_id"] = $user_id;
                            $_SESSION["username"] = $name;
                            $_SESSION["role"] = $db_role;

                            // Redirect based on role
                            if ($db_role === "user") {
                                header("location: user_dashboard.php");
                            } elseif ($db_role === "case_worker") {
                                header("location: caseworker_dashboard.php");
                            } elseif ($db_role === "higher_authority") {
                                header("location: higher_authority_dashboard.php");
                            } else {
                                $error = "Invalid role assigned.";
                            }
                            exit;

                        } else {
                            $error = "The password you entered was not valid.";
                        }
                    }
                } else {
                    $error = "No account found with that email.";
                }
            } else {
                $error = "Oops! Something went wrong. Please try again later.";
            }
            $stmt->close();
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
    <title>User Login | EcoTrack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-light">
    <div class="container d-flex justify-content-center align-items-center" style="min-height: 100vh;">
        <div class="card shadow" style="max-width: 400px; width: 100%;">
            <div class="card-header bg-success text-white text-center">
                <h4 class="mb-0"><i class="bi bi-person-circle"></i> User Login</h4>
            </div>
            <div class="card-body p-4">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <?php if (!empty($error)) echo '<div class="alert alert-danger" role="alert">' . $error . '</div>'; ?>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email ID</label>
                        <input type="email" name="email" id="email" class="form-control" value="<?php echo $email; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" name="password" id="password" class="form-control" required>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-success">Login</button>
                    </div>
                </form>
                <hr>
                <p class="text-center mb-0">Don't have an account? <a href="user_signup.php">Sign Up here</a></p>
                <p class="text-center mt-2"><a href="index.php"><i class="bi bi-arrow-left-circle"></i> Back to Home</a></p>
            </div>
        </div>
    </div>
</body>
</html>