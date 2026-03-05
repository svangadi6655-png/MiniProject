<?php 
// Start session for dynamic content (e.g., login status)
session_start();
// Include the database connection for potential future use or consistency
include 'db_connect.php'; 

// Simple PHP logic for navigation if needed, though mostly static page
$is_logged_in = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
$role = $is_logged_in ? $_SESSION['role'] : '';

// Function to generate the navigation bar (to keep the pages consistent)
function generate_navbar($is_logged_in, $role) {
    $nav_html = '
    <nav class="navbar navbar-expand-lg navbar-dark bg-success sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="bi bi-recycle"></i> EcoTrack : 
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link active" href="about.php">About Us</a></li>
                    <li class="nav-item"><a class="nav-link" href="faq.php">FAQ</a></li>';
    
    if ($is_logged_in) {
        // Logged in: show Dashboard and Logout
        $dashboard_link = ($role === 'admin') ? 'admin_dashboard.php' : 'user_dashboard.php';
        $nav_html .= '
                    <li class="nav-item"><a class="nav-link btn btn-outline-light btn-sm ms-lg-2" href="' . $dashboard_link . '"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link btn btn-danger btn-sm ms-lg-2" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>';
    } else {
        // Not logged in: show Login/Sign Up
        $nav_html .= '
                    <li class="nav-item">
                        <button class="btn btn-outline-light ms-lg-2" 
                                data-bs-toggle="modal" data-bs-target="#loginModal">
                            Login / Sign Up
                        </button>
                    </li>';
    }
    
    $nav_html .= '
                </ul>
            </div>
        </div>
    </nav>';
    return $nav_html;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About EcoTrack | Municipal Waste Resolution System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="d-flex flex-column min-vh-100">

<?php echo generate_navbar($is_logged_in, $role); ?>

<main class="flex-grow-1">
    <div class="container my-5">
        <h1 class="text-center mb-5 text-success border-bottom pb-2"><i class="bi bi-info-circle-fill me-3"></i> About EcoTrack</h1>
        
        <div class="row g-5">
            <div class="col-lg-8">
                <section class="mb-5">
                    <h3 class="text-primary mb-3">Our Mission</h3>
                    <p class="lead">
                        <b> EcoTrack </b> is a Municipal Waste Complaint & Resolution System dedicated to bridging the gap between citizens and local authorities. Our mission is to provide a transparent, efficient, and accessible platform for reporting waste and sanitation issues, ensuring quicker resolution and promoting a cleaner, healthier community.
                    </p>
                </section>

                <section class="mb-5">
                    <h3 class="text-primary mb-3">System Objectives </h3>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item"><i class="bi bi-check-circle-fill text-success me-2"></i> <b> Easy Reporting :</b> Allow citizens to easily report waste or garbage problems using the User Dashboard.</li>
                        <li class="list-group-item"><i class="bi bi-check-circle-fill text-success me-2"></i> <b>Transparency: </b> Enable users to monitor the status of their complaints (Pending, In Progress, Resolved) in real-time.</li>
                        <li class="list-group-item"><i class="bi bi-check-circle-fill text-success me-2"></i> <b>Efficient Resolution: </b> Provide the Municipal Admin with a centralized dashboard to track, prioritize, and update the status of complaints.</li>
                        <li class="list-group-item"><i class="bi bi-check-circle-fill text-success me-2"></i> <b>Awareness: </b> Include a section (like FAQ) to provide information and ideas on waste recycling and better waste management.</li>
                    </ul>
                </section>
                
                <section>
                    <h3 class="text-primary mb-3">Technology & Security</h3>
                    <p>
                        The platform is built using HTML, CSS, and JavaScript for a responsive frontend design, powered by the Bootstrap framework. The robust backend utilizes PHP for server-side logic and MySQL (via XAMPP/phpMyAdmin) for secure database storage. We ensure that all user passwords are securely hashed upon registration.
                    </p>
                </section>
            </div>

            <div class="col-lg-4">
                <div class="card bg-light shadow-sm">
                    <div class="card-body">
                        <h4 class="card-title text-center text-secondary mb-4">Quick Links</h4>
                        <div class="d-grid gap-2">
                            <a href="user_login.php" class="btn btn-outline-success"><i class="bi bi-person-fill"></i> User Login</a>
                            <a href="admin_login.php" class="btn btn-outline-warning"><i class="bi bi-shield-lock-fill"></i> Admin Login</a>
                            <a href="user_signup.php" class="btn btn-outline-primary"><i class="bi bi-person-plus-fill"></i> Sign Up</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<footer class="footer mt-auto bg-light">
    <div class="container text-center">
        <p class="mb-0">&copy; <?php echo date("Y"); ?> EcoTrack: Municipal Waste Complaint & Resolution System. All Rights Reserved.</p>
        <div class="mt-2 pb-3">
            <a href="#" class="text-success me-3"><i class="bi bi-facebook"></i></a>
            <a href="#" class="text-success me-3"><i class="bi bi-twitter"></i></a>
            <a href="#" class="text-success"><i class="bi bi-instagram"></i></a>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php if (!$is_logged_in): ?>
<div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="loginModalLabel">Select Your Role</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-5">
                <p class="lead mb-4">Please choose how you would like to log in:</p>
                <div class="d-grid gap-3">
                    <a href="user_login.php" class="btn btn-primary btn-lg fw-bold">
                        <i class="bi bi-person-fill"></i> Login as User
                    </a>
                    <a href="admin_login.php" class="btn btn-warning btn-lg fw-bold">
                        <i class="bi bi-people-fill"></i> Login as Municipal Admin
                    </a>
                </div>
                <hr class="my-4">
                <p>New User? <a href="user_signup.php">Create an Account (Sign Up)</a></p>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
</body>
</html>