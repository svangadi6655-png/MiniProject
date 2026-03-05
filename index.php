<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoTrack | Municipal Waste Complaint & Resolution System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        /* 1. Hero Section Styling */
        .hero-section {
            /* Changed background image to one that is more representative and potentially higher quality (assuming URL is valid) */
            background: url('https://www.banyannation.com/wp-content/uploads/2024/10/Sustainable-Waste-Management.jpg') no-repeat center center;
            background-size: cover;
            color: white;
            padding: 10rem 0;
            position: relative;
        }
        .hero-section::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            /* Darker overlay for better text contrast */
            background: rgba(50, 142, 42, 0.2); 
            z-index: 1;
        }
        .hero-content {
            position: relative;
            z-index: 2;
            color : black;
        }

        /* 2. Headline Animation */
        .animated-headline {
            /* Initial state for animation */
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInDown 1.5s ease-out forwards;
        }
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* 3. Get Started Button Pop-up on Hover */
        .btn-warning {
            transition: transform 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .btn-warning:hover {
            transform: scale(1.05); /* Scales up 5% */
            box-shadow: 0 8px 15px rgba(255, 193, 7, 0.5); /* Glowing effect */
        }

        /* 4. Feature Card Hover Effect */
        .card {
            transition: transform 0.4s ease-out, box-shadow 0.4s ease-out;
            border: none !important; /* Ensure border is removed */
        }
        .card:hover {
            transform: translateY(-10px) scale(1.02); /* Lifts and slightly scales the card */
            box-shadow: 0 1rem 3rem rgba(0, 0, 0, 0.175) !important; /* Stronger shadow */
            cursor: pointer;
        }
        
        /* 5. Footer Enhancement */
        .footer {
            background-color: #62e6a8ff ; /* Light Gray for a cleaner look */
            padding: 20px 0;
            border-top: 5px solid #198754; /* Green border for theme continuity */
        }
        .footer a {
            color: #198754; /* Make social icons match success color */
        }
    </style>
</head>
<body>

<header>
    <nav class="navbar navbar-expand-lg navbar-dark bg-success sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="bi bi-recycle"></i> EcoTrack : Municipal  Waste Complaint & Resolution System
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link active" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="about.php">About Us</a></li>
                    <li class="nav-item"><a class="nav-link" href="faq.php">FAQ</a></li>
                    <li class="nav-item"><a class="nav-link" href="user_login.php">login/signup</a></li>
                    <?php 
                    // PHP check for logged-in user or admin session
                    // if (isset($_SESSION['user_id']) || isset($_SESSION['admin_id'])) { 
                    ?>
                        <?php 
                    // } 
                    ?>
                </ul>
            </div>
        </div>
    </nav>
</header>

<main>
    <section class="hero-section text-center">
        <div class="container hero-content">
            <h1 class="display-3 fw-bold animated-headline">Track Waste. Resolve Fast.</h1>
            <p class="lead my-4 animated-headline" style="animation-delay: 0.5s;"><b>
                EcoTrack is your digital platform to report waste-related problems and track their resolution in real-time.</b>
            </p>
            
            <button class="btn btn-warning btn-lg fw-bold px-5" 
                    data-bs-toggle="modal" data-bs-target="#getStartedModal">
                Get Started
            </button>
        </div>
    </section>

    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="text-center mb-5 text-success">Why EcoTrack?</h2>
            <div class="row row-cols-1 row-cols-md-3 g-4 text-center">
                <div class="col">
                    <div class="card h-100 p-4 shadow-sm">
                        <i class="bi bi-megaphone-fill display-4 text-primary mb-3"></i>
                        <h5 class="card-title">Digital Reporting</h5>
                        <p class="card-text">Easily submit complaints with location details and optional photos from any device.</p>
                    </div>
                </div>
                <div class="col">
                    <div class="card h-100 p-4 shadow-sm">
                        <i class="bi bi-geo-alt-fill display-4 text-warning mb-3"></i>
                        <h5 class="card-title">Real-Time Tracking</h5>
                        <p class="card-text">Monitor the status of your issue from 'Pending' to 'Resolved', ensuring transparency.</p>
                    </div>
                </div>
                <div class="col">
                    <div class="card h-100 p-4 shadow-sm">
                        <i class="bi bi-lightbulb-fill display-4 text-success mb-3"></i>
                        <h5 class="card-title">Awareness Tips</h5>
                        <p class="card-text">Access recycling ideas and waste reduction tips to promote environmental consciousness.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <section class="py-5">
        <div class="container">
             </div>
    </section>
</main>

<footer class="footer">
    <div class="container text-center">
        <p class="mb-0">&copy; <?php echo date("Y"); ?> EcoTrack: Municipal Waste Complaint & Resolution System. All Rights Reserved.</p>
        <div class="mt-2">
            <a href="#" class="text-success me-3"><i class="bi bi-facebook"></i></a>
            <a href="#" class="text-success me-3"><i class="bi bi-twitter"></i></a>
            <a href="#" class="text-success"><i class="bi bi-instagram"></i></a>
        </div>
    </div>
</footer>

<div class="modal fade" id="getStartedModal" tabindex="-1" aria-labelledby="getStartedModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="getStartedModalLabel">Select Your Role</h5>
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


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>