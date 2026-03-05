<?php
session_start();
include 'db_connect.php';

$name = $email = $area = $password = $confirm_password = $error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Validate inputs
    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $area = trim($_POST["area"]);
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];

    if (empty($name) || empty($email) || empty($area) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        // 2. Check if email already exists
        $sql = "SELECT user_id FROM users WHERE email = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $param_email);
            $param_email = $email;
            $stmt->execute();
            $stmt->store_result();
            
            if ($stmt->num_rows > 0) {
                $error = "This email is already registered.";
            } else {
                // 3. Insert new user
                // CORRECTED SQL: Added 'role' column to the field list
                $sql_insert = "INSERT INTO users (name, email, area, password, role) VALUES (?, ?, ?, ?, ?)";
                if ($stmt_insert = $conn->prepare($sql_insert)) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $role = 'user'; // Define the default role
                    
                    // CORRECTED bind_param: Changed "ssss" to "sssss" for the 5 parameters
                    $stmt_insert->bind_param("sssss", $name, $email, $area, $hashed_password, $role);
                    
                    if ($stmt_insert->execute()) {
                        // Registration successful, redirect to login
                        header("location: user_login.php?success=1");
                        exit;
                    } else {
                        $error = "Error during registration. Please try again. " . $stmt_insert->error;
                    }
                    $stmt_insert->close();
                } else {
                    $error = "Database error preparing insert statement.";
                }
            }
            $stmt->close();
        } else {
            $error = "Database error preparing select statement.";
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
    <title>User Sign Up | EcoTrack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-light">
    <div class="container d-flex justify-content-center align-items-center" style="min-height: 100vh;">
        <div class="card shadow" style="max-width: 500px; width: 100%;">
            <div class="card-header bg-primary text-white text-center">
                <h4 class="mb-0"><i class="bi bi-person-plus-fill"></i> New User Sign Up</h4>
            </div>
            <div class="card-body p-4">
                <form id="registrationForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <?php if (!empty($error)) echo '<div class="alert alert-danger" role="alert">' . $error . '</div>'; ?>
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Full Name</label>
                        <input type="text" name="name" id="name" class="form-control" value="<?php echo htmlspecialchars($name); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email ID</label>
                        <input type="email" name="email" id="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="area" class="form-label">Your Area/Locality</label>
                        <input type="text" name="area" id="area" class="form-control" value="<?php echo htmlspecialchars($area); ?>" required>
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
                        <button type="submit" id="submitButton" class="btn btn-primary">Sign Up</button>
                    </div>
                </form>
                <hr>
                <p class="text-center mb-0">Already have an account? <a href="user_login.php">Login here</a></p>
            </div>
        </div>
    </div>

    <div class="modal fade" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="confirmationModalLabel"><i class="bi bi-exclamation-triangle-fill me-2"></i>Confirm Registration</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Please ensure all details are correct. By clicking **Confirm**, you agree to register this user account:</p>
                    <ul class="list-unstyled fw-bold">
                        <li>Name: <span id="modal-name" class="fw-normal"></span></li>
                        <li>Email: <span id="modal-email" class="fw-normal"></span></li>
                        <li>Area: <span id="modal-area" class="fw-normal"></span></li>
                    </ul>
                    <div class="alert alert-info mt-3 small">
                        **Note:** You will be redirected to the login page after successful registration.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="confirmSubmission" class="btn btn-warning fw-bold"><i class="bi bi-check-circle-fill me-2"></i>Confirm & Register</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('registrationForm');
            const confirmBtn = document.getElementById('confirmSubmission');
            const modal = new bootstrap.Modal(document.getElementById('confirmationModal'));
            
            let isConfirmed = false; // Flag to control form submission

            // 1. Intercept the form submission
            form.addEventListener('submit', function(event) {
                if (isConfirmed) {
                    return; // Allow submission to proceed to PHP if confirmed
                }
                
                // Use browser's built-in validation to check 'required' fields
                if (!form.checkValidity()) {
                    // If validation fails (e.g., empty field), let the browser show errors
                    return; 
                }

                event.preventDefault(); // STOP the form from submitting
                
                // Populate the modal with form data
                document.getElementById('modal-name').textContent = form.elements['name'].value;
                document.getElementById('modal-email').textContent = form.elements['email'].value;
                document.getElementById('modal-area').textContent = form.elements['area'].value;
                
                modal.show(); // Show the confirmation modal
            });

            // 2. Handle the confirmation click inside the modal
            confirmBtn.addEventListener('click', function() {
                isConfirmed = true; // Set flag to allow form submission
                modal.hide();      // Hide the modal
                form.submit();     // Manually submit the form to the PHP script
            });
        });
    </script>
</body>
</html>