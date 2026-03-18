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
$student_query = "SELECT name, surname, student_number, email, phone, room_number, last_login 
                 FROM students 
                 WHERE id = ?";
$stmt = $conn->prepare($student_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student_result = $stmt->get_result();
$student = $student_result->fetch_assoc();
$stmt->close();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $surname = trim($_POST['surname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $room_number = trim($_POST['room_number']);

    // Basic validation
    if (empty($name) || empty($surname) || empty($email)) {
        $error_message = "Name, surname, and email are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format.";
    } else {
        // Check if email is already used by another student
        $email_check_query = "SELECT id FROM students WHERE email = ? AND id != ?";
        $stmt = $conn->prepare($email_check_query);
        $stmt->bind_param("si", $email, $student_id);
        $stmt->execute();
        $email_result = $stmt->get_result();
        if ($email_result->num_rows > 0) {
            $error_message = "This email is already in use.";
        } else {
            // Update profile
            $update_query = "UPDATE students SET name = ?, surname = ?, email = ?, phone = ?, room_number = ? WHERE id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("sssssi", $name, $surname, $email, $phone, $room_number, $student_id);
            if ($stmt->execute()) {
                $success_message = "Profile updated successfully.";
                // Refresh student data
                $student['name'] = $name;
                $student['surname'] = $surname;
                $student['email'] = $email;
                $student['phone'] = $phone;
                $student['room_number'] = $room_number;
            } else {
                $error_message = "Failed to update profile. Please try again.";
            }
        }
        $stmt->close();
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate inputs
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_message = "All password fields are required.";
    } elseif ($new_password !== $confirm_password) {
        $error_message = "New password and confirmation do not match.";
    } elseif (strlen($new_password) < 6) {
        $error_message = "New password must be at least 6 characters long.";
    } else {
        // Verify current password
        $password_query = "SELECT password FROM students WHERE id = ?";
        $stmt = $conn->prepare($password_query);
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $password_result = $stmt->get_result();
        $stored_password = $password_result->fetch_assoc()['password'];

        if ($current_password === $stored_password) {
            // Update password
            $update_password_query = "UPDATE students SET password = ? WHERE id = ?";
            $stmt = $conn->prepare($update_password_query);
            $stmt->bind_param("si", $new_password, $student_id);
            if ($stmt->execute()) {
                $success_message = "Password changed successfully.";
            } else {
                $error_message = "Failed to change password. Please try again.";
            }
        } else {
            $error_message = "Current password is incorrect.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profile - Corridor Communique</title>
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
        .profile-card, .form-card {
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
                        <a class="nav-link active" href="profile.php">
                            <i class="fas fa-user me-1"></i> Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="settings.php">
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
                <!-- Profile Information -->
                <div class="profile-card">
                    <h4 class="mb-4"><i class="fas fa-user-circle me-2"></i>My Profile</h4>
                    <?php if ($success_message): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
                    <?php endif; ?>
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
                    <?php endif; ?>
                    <div class="profile-img">
                        <?php echo strtoupper(substr($student['name'], 0, 1) . substr($student['surname'], 0, 1)); ?>
                    </div>
                    <div class="text-center mb-4">
                        <h5><?php echo htmlspecialchars($student['name'] . ' ' . $student['surname']); ?></h5>
                        <p class="text-muted"><?php echo htmlspecialchars($student['student_number']); ?></p>
                    </div>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item"><strong>Email:</strong> <?php echo htmlspecialchars($student['email']); ?></li>
                        <li class="list-group-item"><strong>Phone:</strong> <?php echo htmlspecialchars($student['phone'] ?: 'Not set'); ?></li>
                        <li class="list-group-item"><strong>Room Number:</strong> <?php echo htmlspecialchars($student['room_number'] ?: 'Not assigned'); ?></li>
                        <li class="list-group-item"><strong>Last Login:</strong> <?php echo $student['last_login'] ? date('M j, Y, g:i A', strtotime($student['last_login'])) : 'Never'; ?></li>
                    </ul>
                </div>

                <!-- Update Profile Form -->
                <div class="form-card">
                    <h4 class="mb-4"><i class="fas fa-edit me-2"></i>Update Profile</h4>
                    <form method="POST" action="">
                        <input type="hidden" name="update_profile" value="1">
                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($student['name']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="surname" class="form-label">Surname</label>
                            <input type="text" class="form-control" id="surname" name="surname" value="<?php echo htmlspecialchars($student['surname']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($student['email']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($student['phone']); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="room_number" class="form-label">Room Number</label>
                            <input type="text" class="form-control" id="room_number" name="room_number" value="<?php echo htmlspecialchars($student['room_number']); ?>">
                        </div>
                        <button type="submit" class="btn btn-primary" style="background-color: var(--primary-green); border-color: var(--primary-green);">Update Profile</button>
                    </form>
                </div>

                <!-- Change Password Form -->
                <div class="form-card">
                    <h4 class="mb-4"><i class="fas fa-lock me-2"></i>Change Password</h4>
                    <form method="POST" action="">
                        <input type="hidden" name="change_password" value="1">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        <button type="submit" class="btn btn-primary" style="background-color: var(--primary-green); border-color: var(--primary-green);">Change Password</button>
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