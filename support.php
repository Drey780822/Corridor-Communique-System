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
    <title>Support - Corridor Communique</title>
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
        .support-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            padding: 20px;
        }
        .btn-primary {
            background-color: var(--primary-green);
            border-color: var(--primary-green);
        }
        .btn-primary:hover {
            background-color: #388E3C;
            border-color: #388E3C;
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
                    <a href="faqs.php"><i class="fas fa-question-circle me-1"></i> FAQs</a>
                    <a href="support.php" class="active"><i class="fas fa-headset me-1"></i> Support</a>
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
        <h2 class="text-center mb-4">Support</h2>
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="support-card">
                    <h4>Contact Us</h4>
                    <p>Our support team is here to assist you with any issues or questions.</p>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-phone-alt me-2"></i> <a href="tel:<?php echo htmlspecialchars($contact_phone); ?>"><?php echo htmlspecialchars($contact_phone); ?></a></li>
                        <li><i class="fas fa-envelope me-2"></i> <a href="mailto:<?php echo htmlspecialchars($contact_email); ?>"><?php echo htmlspecialchars($contact_email); ?></a></li>
                        <li><i class="fas fa-clock me-2"></i> Available: Mon-Fri, 9 AM - 5 PM</li>
                    </ul>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="support-card">
                    <h4>Send a Message</h4>
                    <form>
                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="name" placeholder="Your Name" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" placeholder="Your Email" required>
                        </div>
                        <div class="mb-3">
                            <label for="message" class="form-label">Message</label>
                            <textarea class="form-control" id="message" rows="4" placeholder="Your Message" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Send Message</button>
                    </form>
                    <p class="mt-2 text-muted">Note: This form is a placeholder. Contact support directly via email or phone.</p>
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