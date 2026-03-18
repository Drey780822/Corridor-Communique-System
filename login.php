<?php
session_start();
require_once 'include/db_connect.php';

// Redirect if logged in
if (isset($_SESSION['student_id'])) {
    header("Location: view_communiques.php");
    exit();
}

// Handle login
$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_number = trim($_POST['student_number']);
    $password = trim($_POST['password']);
    
    if (empty($student_number) || empty($password)) {
        $error = 'Please enter both student number and password.';
    } else {
        $stmt = $conn->prepare("SELECT id, student_number, password FROM students WHERE student_number = ?");
        $stmt->bind_param("s", $student_number);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $student = $result->fetch_assoc();
            if ($password === $student['password']) {
                $_SESSION['student_id'] = $student['id'];
                $_SESSION['student_number'] = $student['student_number'];
                header("Location: view_communiques.php");
                exit();
            } else {
                $error = 'Invalid student number or password.';
            }
        } else {
            $error = 'Invalid student number or password.';
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
    <title>Student Login - Corridor Communique</title>
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
                        <a class="nav-link active" href="login.php">Student Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="register.php">Register</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_login.php">Admin Login</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Login Section -->
    <section class="container my-5">
        <h1 class="text-center mb-4">Student Login</h1>
        <div class="row justify-content-center">
            <div class="col-md-6">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="studentNumber" class="form-label">Student Number</label>
                        <input type="text" class="form-control" id="studentNumber" name="student_number" 
                               placeholder="e.g., 223174256" required pattern="[0-9]{9}">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Log In</button>
                    <?php if ($error): ?>
                        <p class="text-error mt-2"><?php echo htmlspecialchars($error); ?></p>
                    <?php endif; ?>
                </form>
                <p class="text-center mt-3">
                    New here? <a href="register.php">Register now</a>.
                </p>
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