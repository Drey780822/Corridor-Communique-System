<?php
session_start();
require_once 'include/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['student_id'])) {
    header("Location: index.php");
    exit();
}

$student_id = $_SESSION['student_id'];
$success_message = '';
$error_message = '';

// Get student information
$student_query = "SELECT name, surname, student_number, notification_opt_in 
                 FROM students 
                 WHERE id = ?";
$stmt = $conn->prepare($student_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student_result = $stmt->get_result();
$student = $student_result->fetch_assoc();
$stmt->close();

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    $notification_opt_in = isset($_POST['notification_opt_in']) ? 1 : 0;

    $update_query = "UPDATE students SET notification_opt_in = ? WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("ii", $notification_opt_in, $student_id);
    if ($stmt->execute()) {
        $success_message = "Settings updated successfully.";
        $student['notification_opt_in'] = $notification_opt_in;
    } else {
        $error_message = "Failed to update settings. Please try again.";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Settings - Corridor Communique</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        :root {
            --primary-green: #4CAF50;
            --error-red: #DC3545;
            --accent-yellow: #FFC107;
            --text-dark: #212529;
        }
        .settings-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        .profile-img {
            width: 100px;
            height: 100px;
            background-color: var(--primary-green);
            color: white;
            font-size: 2.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            margin: 0 auto 15px;
        }
        .sidebar {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            padding: 20px;
            height: 100%;
        }
        .quick-links a {
            display: block;
            padding: 10px;
            margin-bottom: 5px;
            border-radius: 5px;
            color: var(--text-dark);
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .quick-links a:hover {
            background-color: #f8f9fa;
            color: var(--primary-green);
        }
        .quick-links a i {
            margin-right: 10px;
            color: var(--primary-green);
        }
        .back-to-top {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: var(--primary-green);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: all 0.3s ease;
            cursor: pointer;
            z-index: 1000;
        }
        .back-to-top.visible {
            opacity: 1;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top" style="background-color: var(--primary-green);">
        <div class="container">
            <a class="navbar-brand" href="view_communiques.php">
                <i class="fas fa-mountain me-2"></i>Corridor Communique
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="view_communiques.php">
                            <i class="fas fa-bullhorn me-1"></i> Communiques
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">
                            <i class="fas fa-user me-1"></i> Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="settings.php">
                            <i class="fas fa-cog me-1"></i> Settings
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt me-1"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container my-4">
        <div class="row">
            <!-- Main Content Area -->
            <div class="col-lg-8 mb-4">
                <!-- Settings -->
                <div class="settings-card">
                    <h4 class="mb-4"><i class="fas fa-cog me-2"></i>Settings</h4>
                    <?php if ($success_message): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
                    <?php endif; ?>
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
                    <?php endif; ?>
                    <h5>Notification Preferences</h5>
                    <p><strong>Current Status:</strong> <?php echo $student['notification_opt_in'] ? 'Opted In' : 'Opted Out'; ?></p>
                    <form method="POST" action="">
                        <input type="hidden" name="update_settings" value="1">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="notification_opt_in" name="notification_opt_in" 
                                   <?php echo $student['notification_opt_in'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="notification_opt_in">
                                Receive notifications for new communiques
                            </label>
                        </div>
                        <button type="submit" class="btn btn-primary" style="background-color: var(--primary-green); border-color: var(--primary-green);">Save Settings</button>
                    </form>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <div class="sidebar">
                    <!-- Profile Section -->
                    <div class="profile-section text-center mb-4 pb-4 border-bottom">
                        <div class="profile-img">
                            <?php echo strtoupper(substr($student['name'], 0, 1) . substr($student['surname'], 0, 1)); ?>
                        </div>
                        <h5><?php echo htmlspecialchars($student['name'] . ' ' . $student['surname']); ?></h5>
                        <p class="text-muted"><?php echo htmlspecialchars($student['student_number']); ?></p>
                    </div>

                    <!-- Quick Links -->
                    <div class="quick-links">
                        <h5>Quick Links</h5>
                        <a href="view_communiques.php"><i class="fas fa-bullhorn"></i> Communiques</a>
                        <a href="profile.php"><i class="fas fa-user-circle"></i> My Profile</a>
                        <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
                        <a href="https://tutportal.tut.ac.za" target="_blank"><i class="fas fa-external-link-alt"></i> TUT Portal</a>
                        <a href="https://mytutor.tut.ac.za" target="_blank"><i class="fas fa-graduation-cap"></i> MyTUTor</a>
                        <a href="mailto:support@corridorcommunique.co.za"><i class="fas fa-envelope"></i> Contact Support</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Back to Top Button -->
    <div class="back-to-top" id="backToTop">
        <i class="fas fa-arrow-up"></i>
    </div>

    <!-- Footer -->
    <footer class="text-white text-center py-3" style="background-color: var(--primary-green);">
        <p>© 2025 Corridor Communique. Created by Thabang for Corridor Hills Residence.</p>
        <p>Saving <span class="text-accent">500 sheets</span> of paper and counting!</p>
    </footer>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Custom JS -->
    <script src="js/script.js"></script>
    <script>
        // Back to top button functionality
        $(window).scroll(function() {
            if ($(this).scrollTop() > 300) {
                $('#backToTop').addClass('visible');
            } else {
                $('#backToTop').removeClass('visible');
            }
        });
        
        $('#backToTop').click(function() {
            $('html, body').animate({scrollTop : 0}, 800);
            return false;
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>