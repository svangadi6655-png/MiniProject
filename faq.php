<?php 
session_start();
include 'db_connect.php'; 

$is_logged_in = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
$role = $is_logged_in ? $_SESSION['role'] : '';

// Function to generate the navigation bar (reused from about.php)
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
                    <li class="nav-item"><a class="nav-link" href="about.php">About Us</a></li>
                    <li class="nav-item"><a class="nav-link active" href="faq.php">FAQ</a></li>';
    
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
    <title>FAQ | EcoTrack - Frequently Asked Questions</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="d-flex flex-column min-vh-100">

<?php echo generate_navbar($is_logged_in, $role); ?>

<main class="flex-grow-1">
    <div class="container my-5">
        <h1 class="text-center mb-5 text-primary border-bottom pb-2"><i class="bi bi-question-circle-fill me-3"></i> Frequently Asked Questions</h1>
        
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="accordion" id="faqAccordion">

                    <h4 class="mt-4 mb-3 text-secondary">EcoTrack System Usage</h4>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingOne">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                1. How do I register a complaint?
                            </button>
                        </h2>
                        <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                To register a complaint, you must first Sign Up (if you're a new user) and then Login to the User Dashboard. From the dashboard, click the "Lodge Complaint" button, fill in the details (Title, Description, Location), and submit.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingTwo">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                2. How can I track the status of my submitted complaint?
                            </button>
                        </h2>
                        <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                After logging into your User Dashboard, scroll down to the "Track My Complaints" section. You will see a table listing all your submitted issues along with their current status: Pending, In Progress, or Resolved.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingThree">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                3. What should I do if my complaint is marked 'Resolved' but the issue remains?
                            </button>
                        </h2>
                        <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                If the issue persists, please submit a **new complaint** referencing the ID of the previous (resolved) complaint in the description. The Municipal Admin team will re-evaluate the status.
                            </div>
                        </div>
                    </div>

                    <h4 class="mt-4 mb-3 text-secondary">Waste Management & Recycling Tips</h4>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingFour">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                                4. What is the difference between dry waste and wet waste?
                            </button>
                        </h2>
                        <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                **Wet waste** includes all food scraps, vegetable and fruit peels, tea leaves, and garden waste. **Dry waste** includes paper, plastics, metals, glass, and rubber. **Proper segregation** is the first and most important step in effective waste management.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingFive">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive">
                                5. How can I reduce plastic waste at home?
                            </button>
                        </h2>
                        <div id="collapseFive" class="accordion-collapse collapse" aria-labelledby="headingFive" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Adopt the 'Five R' principle: **Refuse** single-use plastics (bags, straws), **Reduce** consumption, **Reuse** containers, **Repurpose** old items, and finally, **Recycle** what cannot be reused.
                            </div>
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