<?php
session_start();
require_once 'include/db_connect.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Handle add student number
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_student_number'])) {
    $student_number = trim($_POST['student_number']);
    if (empty($student_number) || !preg_match('/^[0-9]{9}$/', $student_number)) {
        $error = 'Student number must be 9 digits.';
    } else {
        $stmt = $conn->prepare("INSERT INTO student_numbers (student_number) VALUES (?)");
        $stmt->bind_param("s", $student_number);
        if ($stmt->execute()) {
            $success = 'Student number added successfully!';
        } else {
            $error = 'Failed to add student number. It may already exist.';
        }
        $stmt->close();
    }
}

// Handle delete student number
if (isset($_GET['delete_number_id']) && is_numeric($_GET['delete_number_id'])) {
    $delete_id = $_GET['delete_number_id'];
    $stmt = $conn->prepare("DELETE FROM student_numbers WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        $success = 'Student number deleted successfully!';
    } else {
        $error = 'Failed to delete student number. It may be in use.';
    }
    $stmt->close();
}

// Fetch student numbers and students
$student_numbers = $conn->query("SELECT id, student_number FROM student_numbers ORDER BY student_number")->fetch_all(MYSQLI_ASSOC);
$students = $conn->query("SELECT id, student_number, name, surname, email, room_number FROM students ORDER BY student_number")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students - Corridor Communique</title>
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
                        <a class="nav-link" href="admin_dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_add_communique.php">Add Communique</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_manage_categories.php">Manage Categories</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="admin_manage_students.php">Manage Students</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Manage Students Section -->
    <section class="container my-5">
        <h1 class="text-center mb-4">Manage Students</h1>
        <div class="row justify-content-center">
            <div class="col-md-6">
                <?php if ($error): ?>
                    <p class="text-error mb-3"><?php echo htmlspecialchars($error); ?></p>
                <?php elseif ($success): ?>
                    <p class="text-success mb-3"><?php echo htmlspecialchars($success); ?></p>
                <?php endif; ?>
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="student_number" class="form-label">Add Student Number</label>
                        <input type="text" class="form-control" id="student_number" name="student_number" 
                               placeholder="e.g., 223174256" required pattern="[0-9]{9}">
                    </div>
                    <button type="submit" name="add_student_number" class="btn btn-primary w-100">Add Student Number</button>
                </form>
            </div>
        </div>
        <div class="row mt-5">
            <div class="col-12">
                <h3>Student Numbers</h3>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Student Number</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($student_numbers)): ?>
                            <tr><td colspan="2">No student numbers found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($student_numbers as $number): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($number['student_number']); ?></td>
                                    <td>
                                        <a href="?delete_number_id=<?php echo $number['id']; ?>" 
                                           class="btn btn-danger btn-sm" 
                                           onclick="return confirm('Are you sure you want to delete this student number?');">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="row mt-5">
            <div class="col-12">
                <h3>Registered Students</h3>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Student Number</th>
                            <th>Name</th>
                            <th>Surname</th>
                            <th>Email</th>
                            <th>Room</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($students)): ?>
                            <tr><td colspan="5">No students registered.</td></tr>
                        <?php else: ?>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['student_number']); ?></td>
                                    <td><?php echo htmlspecialchars($student['name']); ?></td>
                                    <td><?php echo htmlspecialchars($student['surname']); ?></td>
                                    <td><?php echo htmlspecialchars($student['email']); ?></td>
                                    <td><?php echo htmlspecialchars($student['room_number'] ?: 'N/A'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
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