<?php
session_start();
require_once 'include/db_connect.php';

// Check if already logged in
if (isset($_SESSION['student_id'])) {
    header("Location: view_communiques.php");
    exit();
}

// Handle login form submission
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $student_number = trim($_POST['student_number']);
    $password = trim($_POST['password']);
    
    if (empty($student_number) || empty($password)) {
        $error = 'Please enter both student number and password.';
    } else {
        $stmt = $conn->prepare("SELECT id, student_number, password FROM students WHERE student_number = ?");
        $stmt->bind_param("s", $student_number);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $student = $result->fetch_assoc();
            if ($password === $student['password']) { // Plain text comparison (insecure, to be upgraded)
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

// Fetch recent communiques (limit to 3 for preview)
$query = "SELECT c.id, c.title, c.description, c.urgency, c.created_at, a.name, a.surname 
          FROM communiques c 
          JOIN admins a ON c.admin_id = a.id 
          WHERE c.pinned = 1 OR c.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
          ORDER BY c.pinned DESC, c.created_at DESC 
          LIMIT 3";
$result = $conn->query($query);
$communiques = $result->fetch_all(MYSQLI_ASSOC);

// Fetch total announcements count for display in the header
$total_announcements = 0;
$query_count = "SELECT COUNT(*) as total FROM communiques WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
$result_count = $conn->query($query_count);
if ($result_count && $row_count = $result_count->fetch_assoc()) {
    $total_announcements = $row_count['total'];
}

// Static contact details (since settings table does not exist)
$contact_phone = '+27 12 345 6789';
$contact_email = 'info@corridorcommunique.co.za';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Corridor Communique - Home</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts: Roboto -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
    <style>
        :root {
            --primary-green: #4CAF50;
            --error-red: #DC3545;
            --accent-yellow: #FFC107;
            --text-dark: #212529;
        }
        .urgency-normal { color: #4CAF50; }
        .urgency-urgent { color: white; }
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
        .announcements-ticker {
            background-color: rgba(255, 255, 255, 0.15);
            padding: 10px 0;
            margin-bottom: 20px;
        }
        .ticker-content {
            white-space: nowrap;
            overflow: hidden;
            animation: ticker 20s linear infinite;
        }
        @keyframes ticker {
            0% { transform: translateX(100%); }
            100% { transform: translateX(-100%); }
        }
        .main-header {
            background: linear-gradient(135deg, var(--primary-green), #1B5E20);
            padding: 4rem 0 2rem;
        }
        .notification-badge {
            position: absolute;
            top: 2px;
            right: 2px;
            background-color: var(--accent-yellow);
            color: var(--text-dark);
            font-size: 0.7rem;
            font-weight: bold;
            border-radius: 50%;
            padding: 3px 6px;
            line-height: 1;
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
    <!-- Top Mini Header with Contact Info and Quick Links -->
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
                    <a href="support.php"><i class="fas fa-headset me-1"></i> Support</a>
                    <a href="news.php">
                        <i class="fas fa-newspaper me-1"></i> News
                        <?php if ($total_announcements > 0): ?>
                            <span class="badge bg-warning text-dark ms-1"><?php echo $total_announcements; ?></span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <!-- Brand/logo on the left -->
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-mountain me-2"></i>Corridor Communique
            </a>
            
            <!-- Responsive toggle button -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <!-- Nav items -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php"><i class="fas fa-home me-1"></i> Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#about"><i class="fas fa-info-circle me-1"></i> About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#login"><i class="fas fa-sign-in-alt me-1"></i> Student Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="register.php"><i class="fas fa-user-plus me-1"></i> Register</a>
                    </li>
                    <li class="nav-item position-relative">
                        <a class="nav-link" href="admin_login.php"><i class="fas fa-user-shield me-1"></i> Admin</a>
                        <?php if ($total_announcements > 0): ?>
                            <span class="notification-badge"><?php echo ($total_announcements > 99) ? '99+' : $total_announcements; ?></span>
                        <?php endif; ?>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link quick-access-btn" href="#login">Quick Access</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Enhanced Hero Header Section -->
    <header class="main-header text-white text-center">
        <div class="container">
            <h1>Welcome to Corridor Communique</h1>
            <p class="lead">Stay updated with Corridor Hills Residence news and announcements!</p>
            
            <!-- Announcement Ticker -->
            <div class="announcements-ticker mt-4">
                <div class="ticker-content">
                    <?php if (!empty($communiques)): ?>
                        <?php foreach ($communiques as $communique): ?>
                            <i class="fas fa-bullhorn me-2"></i> 
                            <span class="fw-bold"><?php echo htmlspecialchars($communique['urgency']); ?>:</span> 
                            <?php echo htmlspecialchars($communique['title']); ?> | 
                        <?php endforeach; ?>
                    <?php else: ?>
                        <i class="fas fa-info-circle me-2"></i> No recent announcements. Please log in to view all updates.
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="mt-4">
                <a href="#login" class="btn btn-primary me-2"><i class="fas fa-clipboard-list me-1"></i> View Updates</a>
                <a href="register.php" class="btn btn-outline-light"><i class="fas fa-user-plus me-1"></i> Register Now</a>
            </div>
        </div>
    </header>

    <!-- Communique Previews -->
    <section class="container my-5">
        <h2 class="text-center mb-4">Recent Communiques</h2>
        <div class="row">
            <?php if (empty($communiques)): ?>
                <p class="text-center">No recent communiques available. Log in to view all updates.</p>
            <?php else: ?>
                <?php foreach ($communiques as $communique): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <span class="urgency-<?php echo strtolower($communique['urgency']); ?>">
                                    <?php echo htmlspecialchars($communique['urgency']); ?>
                                </span>
                                <h5 class="card-title">
                                    <?php echo htmlspecialchars($communique['title']); ?>
                                </h5>
                                <p class="card-text">
                                    <?php
                                    $desc = strip_tags($communique['description']);
                                    echo strlen($desc) > 100 ? substr($desc, 0, 97) . '...' : $desc;
                                    ?>
                                </p>
                                <p class="card-text">
                                    <small class="text-muted">
                                        Posted by <?php echo htmlspecialchars($communique['name'] . ' ' . $communique['surname']); ?>
                                        on <?php echo date('M j, Y', strtotime($communique['created_at'])); ?>
                                    </small>
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <!-- Login Section -->
    <section id="login" class="container my-5">
        <h2 class="text-center mb-4">Student Login</h2>
        <div class="row justify-content-center">
            <div class="col-md-6">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="studentNumber" class="form-label">Student Number</label>
                        <input type="text" class="form-control" id="studentNumber" name="student_number" 
                               placeholder="e.g., 223174256" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" name="login" class="btn btn-primary w-100">Log In</button>
                    <?php if ($error): ?>
                        <p class="text-danger mt-2"><?php echo htmlspecialchars($error); ?></p>
                    <?php endif; ?>
                </form>
                <p class="text-center mt-3">
                    New here? <a href="register.php">Register now</a>.
                </p>
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