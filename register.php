<?php
session_start();
require_once 'include/db_connect.php';

// Redirect if logged in
if (isset($_SESSION['student_id'])) {
    header("Location: view_communiques.php");
    exit();
}

// Handle registration
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_number = trim($_POST['student_number']);
    $name = trim($_POST['name']);
    $surname = trim($_POST['surname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $room_number = trim($_POST['room_number']);
    $password = trim($_POST['password']);
    $notification_opt_in = isset($_POST['notification_opt_in']) ? 1 : 0;

    // Validate inputs
    if (empty($student_number) || empty($name) || empty($surname) || empty($email) || empty($password)) {
        $error = 'Please fill in all required fields.';
    } elseif (!preg_match('/^[0-9]{9}$/', $student_number)) {
        $error = 'Student number must be 9 digits.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } else {
        // Check if student number exists in student_numbers
        $stmt = $conn->prepare("SELECT student_number FROM student_numbers WHERE student_number = ?");
        $stmt->bind_param("s", $student_number);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 0) {
            $error = 'Invalid student number. Contact the residence manager.';
        } else {
            // Check if already registered
            $stmt = $conn->prepare("SELECT id FROM students WHERE student_number = ?");
            $stmt->bind_param("s", $student_number);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = 'This student number is already registered.';
            } else {
                // Insert new student
                $stmt = $conn->prepare("INSERT INTO students (student_number, name, surname, email, phone, room_number, password, notification_opt_in) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssssi", $student_number, $name, $surname, $email, $phone, $room_number, $password, $notification_opt_in);
                
                if ($stmt->execute()) {
                    $success = 'Registration successful! Please log in.';
                } else {
                    $error = 'Registration failed. Please try again.';
                }
            }
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
    <title>Register - Corridor Communique</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- Navbar -->
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
                        <a class="nav-link" href="login.php">Student Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="register.php">Register</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_login.php">Admin Login</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Registration Section -->
    <section class="container my-5">
        <h1 class="text-center mb-4">Create Your Profile</h1>
        <div class="row justify-content-center">
            <div class="col-md-6">
                <?php if ($error): ?>
                    <p class="text-error mb-3"><?php echo htmlspecialchars($error); ?></p>
                <?php elseif ($success): ?>
                    <p class="text-success mb-3"><?php echo htmlspecialchars($success); ?> <a href="login.php">Log in now</a>.</p>
                <?php endif; ?>
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="studentNumber" class="form-label">Student Number</label>
                        <input type="text" class="form-control" id="studentNumber" name="student_number" 
                               placeholder="e.g., 223174256" required pattern="[0-9]{9}">
                    </div>
                    <div class="mb-3">
                        <label for="name" class="form-label">First Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="surname" class="form-label">Surname</label>
                        <input type="text" class="form-control" id="surname" name="surname" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number (Optional)</label>
                        <input type="tel" class="form-control" id="phone" name="phone">
                    </div>
                    <div class="mb-3">
                        <label for="roomNumber" class="form-label">Room Number (Optional)</label>
                        <input type="text" class="form-control" id="roomNumber" name="room_number">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password (min 8 characters)</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="notificationOptIn" name="notification_opt_in" checked>
                        <label class="form-check-label" for="notificationOptIn">Receive notifications</label>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Register</button>
                </form>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="text-white text-center py-3">
        <p>© 2025 Corridor Communique. Created by Thabang for Corridor Hills Residence.</p>
        <p>Saving <span class="text-accent">500 sheets</span> of paper and counting!</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/script.js"></script>
</body>
</html>
<?php $conn->close(); ?>