<?php
session_start();
require_once 'include/db_connect.php';

// Static contact details
$contact_phone = '+27 12 345 6789';
$contact_email = 'info@corridorcommunique.co.za';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQs - Corridor Communique</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        :root {
            --primary-green: #4CAF50;
            --error-red: #DC3545;
            --accent-yellow: #FFC107;
            --text-dark: #212529;
        }
        .text-accent { color: #FFD700; }
        .navbar {
            padding: 15px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            background-color: var(--primary-green);
        }
        .navbar-brand {
            font-size: 1.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
        }
        .navbar-brand i {
            font-size: 1.8rem;
        }
        .header-top {
            background-color: var(--text-dark);
            padding: 8px 0;
            font-size: 0.9rem;
        }
        .header-top a {
            color: white;
            text-decoration: none;
            margin-right: 15px;
        }
        .header-top a:hover {
            color: var(--accent-yellow);
        }
        .nav-link {
            font-weight: 500;
            padding: 8px 15px !important;
            transition: all 0.3s ease;
            color: white !important;
        }
        .nav-link:hover {
            color: var(--accent-yellow) !important;
        }
        .quick-access-btn {
            background-color: var(--accent-yellow);
            color: var(--text-dark) !important;
            border-radius: 20px;
            padding: 5px 15px !important;
            font-weight: 600;
            margin-left: 10px;
        }
        .quick-access-btn:hover {
            background-color: #FFB300;
            transform: translateY(-2px);
        }
        .faq-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .accordion-button:not(.collapsed) {
            background-color: var(--primary-green);
            color: white;
        }
        .accordion-button:focus {
            box-shadow: none;
            border-color: var(--primary-green);
        }
    </style>
</head>
<body>
    <!-- Top Mini Header -->
    <div class="header-top d-none d-md-block">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <a href="tel:<?php echo htmlspecialchars($contact_phone); ?>">
                        <i class="fas fa-phone-alt me-1"></i> <?php echo htmlspecialchars($contact_phone); ?>
                    </a>
                    <a href="mailto:<?php echo htmlspecialchars($contact_email); ?>">
                        <i class="fas fa-envelope me-1"></i> <?php echo htmlspecialchars($contact_email); ?>
                    </a>
                </div>
                <div class="col-md-6 text-end">
                    <a href="faqs.php" class="active"><i class="fas fa-question-circle me-1"></i> FAQs</a>
                    <a href="support.php"><i class="fas fa-headset me-1"></i> Support</a>
                    <a href="news.php"><i class="fas fa-newspaper me-1"></i> News</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-mountain me-2"></i>Corridor Communique
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php"><i class="fas fa-home me-1"></i> Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php#about"><i class="fas fa-info-circle me-1"></i> About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php#login"><i class="fas fa-sign-in-alt me-1"></i> Student Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="register.php"><i class="fas fa-user-plus me-1"></i> Register</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_login.php"><i class="fas fa-user-shield me-1"></i> Admin</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link quick-access-btn" href="index.php#login">Quick Access</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <section class="container my-5">
        <h2 class="text-center mb-4">Frequently Asked Questions</h2>
        <div class="faq-card">
            <div class="accordion" id="faqAccordion">
                <div class="accordion-item">
                    <h2 class="accordion-header" id="faq1">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse1" aria-expanded="true" aria-controls="collapse1">
                            What is Corridor Communique?
                        </button>
                    </h2>
                    <div id="collapse1" class="accordion-collapse collapse show" aria-labelledby="faq1" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Corridor Communique is a digital platform for Corridor Hills Residence students to receive important announcements, updates, and residence news from administrators.
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="faq2">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse2" aria-expanded="false" aria-controls="collapse2">
                            How do I log in as a student?
                        </button>
                    </h2>
                    <div id="collapse2" class="accordion-collapse collapse" aria-labelledby="faq2" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Use your student number (e.g., 223174256) and password to log in on the <a href="index.php#login">Student Login</a> page. If you don’t have an account, contact your residence administrator.
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="faq3">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse3" aria-expanded="false" aria-controls="collapse3">
                            How do I reset my password?
                        </button>
                    </h2>
                    <div id="collapse3" class="accordion-collapse collapse" aria-labelledby="faq3" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            After logging in, go to the <a href="profile.php">Profile</a> page to change your password. If you’ve forgotten your password, contact support at <a href="mailto:<?php echo htmlspecialchars($contact_email); ?>"><?php echo htmlspecialchars($contact_email); ?></a>.
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="faq4">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse4" aria-expanded="false" aria-controls="collapse4">
                            Can I receive notifications for new communiques?
                        </button>
                    </h2>
                    <div id="collapse4" class="accordion-collapse collapse" aria-labelledby="faq4" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Yes, log in and visit the <a href="settings.php">Settings</a> page to opt in for notifications about new communiques.
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="faq5">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse5" aria-expanded="false" aria-controls="collapse5">
                            Who can I contact for support?
                        </button>
                    </h2>
                    <div id="collapse5" class="accordion-collapse collapse" aria-labelledby="faq5" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Reach out to our support team at <a href="mailto:<?php echo htmlspecialchars($contact_email); ?>"><?php echo htmlspecialchars($contact_email); ?></a> or call <a href="tel:<?php echo htmlspecialchars($contact_phone); ?>"><?php echo htmlspecialchars($contact_phone); ?></a>.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="text-white text-center py-3" style="background-color: var(--primary-green);">
        <p>© 2025 Corridor Communique. Created by Thabang for Corridor Hills Residence.</p>
        <p>Saving <span class="text-accent">500 sheets</span> of paper and counting!</p>
    </footer>

    <!-- Bootstrap 5 JS and Dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Custom JS -->
    <script src="js/script.js"></script>
</body>
</html>
<?php $conn->close(); ?>