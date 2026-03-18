<?php
session_start();
require_once 'include/db_connect.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];

// CSRF token for form security
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fetch stats
$communique_count = $conn->query("SELECT COUNT(*) FROM communiques")->fetch_row()[0];
$urgent_communique_count = $conn->query("SELECT COUNT(*) FROM communiques WHERE urgency = 'Urgent'")->fetch_row()[0];
$student_count = $conn->query("SELECT COUNT(*) FROM students")->fetch_row()[0];
$recent_login_count = $conn->query("SELECT COUNT(*) FROM students WHERE last_login >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch_row()[0];
$suggestion_count = $conn->query("SELECT COUNT(*) FROM suggestions")->fetch_row()[0];

// Handle AJAX requests for CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit();
    }

    // Communique CRUD
    if ($_POST['action'] === 'create_communique') {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $urgency = $_POST['urgency'];
        $pinned = isset($_POST['pinned']) ? 1 : 0;

        if (empty($title) || empty($description)) {
            echo json_encode(['success' => false, 'message' => 'Title and description are required']);
            exit();
        }

        $stmt = $conn->prepare("INSERT INTO communiques (admin_id, title, description, urgency, pinned, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("isssi", $admin_id, $title, $description, $urgency, $pinned);
        $success = $stmt->execute();
        $stmt->close();

        echo json_encode(['success' => $success, 'message' => $success ? 'Communique created' : 'Failed to create communique']);
        exit();
    }

    if ($_POST['action'] === 'update_communique') {
        $id = (int)$_POST['id'];
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $urgency = $_POST['urgency'];
        $pinned = isset($_POST['pinned']) ? 1 : 0;

        if (empty($title) || empty($description)) {
            echo json_encode(['success' => false, 'message' => 'Title and description are required']);
            exit();
        }

        $stmt = $conn->prepare("UPDATE communiques SET title = ?, description = ?, urgency = ?, pinned = ? WHERE id = ? AND admin_id = ?");
        $stmt->bind_param("sssiii", $title, $description, $urgency, $pinned, $id, $admin_id);
        $success = $stmt->execute();
        $stmt->close();

        echo json_encode(['success' => $success, 'message' => $success ? 'Communique updated' : 'Failed to update communique']);
        exit();
    }

    if ($_POST['action'] === 'delete_communique') {
        $id = (int)$_POST['id'];
        $stmt = $conn->prepare("DELETE FROM communiques WHERE id = ? AND admin_id = ?");
        $stmt->bind_param("ii", $id, $admin_id);
        $success = $stmt->execute();
        $stmt->close();

        echo json_encode(['success' => $success, 'message' => $success ? 'Communique deleted' : 'Failed to delete communique']);
        exit();
    }

    // Student CRUD
    if ($_POST['action'] === 'create_student') {
        $student_number = trim($_POST['student_number']);
        $name = trim($_POST['name']);
        $surname = trim($_POST['surname']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $room_number = trim($_POST['room_number']);
        $password = trim($_POST['password']);
        $notification_opt_in = isset($_POST['notification_opt_in']) ? 1 : 0;

        if (empty($student_number) || empty($name) || empty($surname) || empty($email) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'Required fields are missing']);
            exit();
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Invalid email format']);
            exit();
        }

        $stmt = $conn->prepare("SELECT id FROM students WHERE student_number = ? OR email = ?");
        $stmt->bind_param("ss", $student_number, $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Student number or email already exists']);
            $stmt->close();
            exit();
        }
        $stmt->close();

        $stmt = $conn->prepare("INSERT INTO students (student_number, name, surname, email, phone, room_number, password, notification_opt_in, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("sssssssi", $student_number, $name, $surname, $email, $phone, $room_number, $password, $notification_opt_in);
        $success = $stmt->execute();
        $stmt->close();

        echo json_encode(['success' => $success, 'message' => $success ? 'Student created' : 'Failed to create student']);
        exit();
    }

    if ($_POST['action'] === 'update_student') {
        $id = (int)$_POST['id'];
        $student_number = trim($_POST['student_number']);
        $name = trim($_POST['name']);
        $surname = trim($_POST['surname']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $room_number = trim($_POST['room_number']);
        $password = trim($_POST['password']);
        $notification_opt_in = isset($_POST['notification_opt_in']) ? 1 : 0;

        if (empty($student_number) || empty($name) || empty($surname) || empty($email)) {
            echo json_encode(['success' => false, 'message' => 'Required fields are missing']);
            exit();
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Invalid email format']);
            exit();
        }

        $stmt = $conn->prepare("SELECT id FROM students WHERE (student_number = ? OR email = ?) AND id != ?");
        $stmt->bind_param("ssi", $student_number, $email, $id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            echo json_encode(['success' => false, 'message Student number or email already exists']);
            $stmt->close();
            exit();
        }
        $stmt->close();

        if (empty($password)) {
            $stmt = $conn->prepare("UPDATE students SET student_number = ?, name = ?, surname = ?, email = ?, phone = ?, room_number = ?, notification_opt_in = ? WHERE id = ?");
            $stmt->bind_param("ssssssii", $student_number, $name, $surname, $email, $phone, $room_number, $notification_opt_in, $id);
        } else {
            $stmt = $conn->prepare("UPDATE students SET student_number = ?, name = ?, surname = ?, email = ?, phone = ?, room_number = ?, password = ?, notification_opt_in = ? WHERE id = ?");
            $stmt->bind_param("ssssssisi", $student_number, $name, $surname, $email, $phone, $room_number, $password, $notification_opt_in, $id);
        }
        $success = $stmt->execute();
        $stmt->close();

        echo json_encode(['success' => $success, 'message' => $success ? 'Student updated' : 'Failed to update student']);
        exit();
    }

    if ($_POST['action'] === 'delete_student') {
        $id = (int)$_POST['id'];
        $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
        $stmt->bind_param("i", $id);
        $success = $stmt->execute();
        $stmt->close();

        // Delete related suggestions
        $stmt = $conn->prepare("DELETE FROM suggestions WHERE student_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        echo json_encode(['success' => $success, 'message' => $success ? 'Student deleted' : 'Failed to delete student']);
        exit();
    }

    // Suggestion CRUD (Delete only)
    if ($_POST['action'] === 'delete_suggestion') {
        $id = (int)$_POST['id'];
        $stmt = $conn->prepare("DELETE FROM suggestions WHERE id = ?");
        $stmt->bind_param("i", $id);
        $success = $stmt->execute();
        $stmt->close();

        echo json_encode(['success' => $success, 'message' => $success ? 'Suggestion deleted' : 'Failed to delete suggestion']);
        exit();
    }

    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit();
}

// Fetch data for tables
$communique_search = isset($_GET['communique_search']) ? trim($_GET['communique_search']) : '';
$communique_urgency = isset($_GET['communique_urgency']) ? $_GET['communique_urgency'] : '';
$communique_query = "SELECT c.id, c.title, c.description, c.urgency, c.pinned, c.created_at 
                     FROM communiques c 
                     WHERE c.admin_id = ?";
$params = [$admin_id];
$types = "i";

if (!empty($communique_search)) {
    $communique_query .= " AND (c.title LIKE ? OR c.description LIKE ?)";
    $search_param = "%$communique_search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}
if (!empty($communique_urgency)) {
    $communique_query .= " AND c.urgency = ?";
    $params[] = $communique_urgency;
    $types .= "s";
}
$communique_query .= " ORDER BY c.created_at DESC LIMIT 5";
$stmt = $conn->prepare($communique_query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$communiques = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$student_search = isset($_GET['student_search']) ? trim($_GET['student_search']) : '';
$student_query = "SELECT id, student_number, name, surname, email, room_number, last_login 
                 FROM students 
                 WHERE 1=1";
$params = [];
$types = "";
if (!empty($student_search)) {
    $student_query .= " AND (student_number LIKE ? OR name LIKE ? OR surname LIKE ?)";
    $search_param = "%$student_search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}
$student_query .= " ORDER BY created_at DESC LIMIT 5";
$stmt = $conn->prepare($student_query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$suggestion_search = isset($_GET['suggestion_search']) ? trim($_GET['suggestion_search']) : '';
$suggestion_query = "SELECT s.id, s.suggestion, s.created_at, st.student_number, st.name, st.surname 
                    FROM suggestions s 
                    JOIN students st ON s.student_id = st.id 
                    WHERE 1=1";
$params = [];
$types = "";
if (!empty($suggestion_search)) {
    $suggestion_query .= " AND s.message LIKE ?";
    $search_param = "%$suggestion_search%";
    $params[] = $search_param;
    $types .= "s";
}
$suggestion_query .= " ORDER BY s.created_at DESC LIMIT 5";
$stmt = $conn->prepare($suggestion_query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$suggestions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<?php
// Handle student number submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_student_number') {
    header('Content-Type: application/json');

    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit();
    }

    $student_number = trim($_POST['student_number']);

    // Validate student number
    if (empty($student_number)) {
        echo json_encode(['success' => false, 'message' => 'Student number is required']);
        exit();
    }

    // Check if student number already exists
    $stmt = $conn->prepare("SELECT id FROM student_numbers WHERE student_number = ?");
    $stmt->bind_param("s", $student_number);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Student number already exists']);
        $stmt->close();
        exit();
    }
    $stmt->close();

    // Insert student number
    $stmt = $conn->prepare("INSERT INTO student_numbers (student_number) VALUES (?)");
    $stmt->bind_param("s", $student_number);
    $success = $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => $success, 'message' => $success ? 'Student number added successfully' : 'Failed to add student number']);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Corridor Communique</title>
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

        .text-accent {
            color: #FFD700;
        }

        .navbar {
            padding: 15px 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            background-color: var(--primary-green);
        }

        .navbar-brand {
            font-size: 1.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
        }

        .error-message {
            color: var(--error-red);
            font-size: 0.9rem;
        }

        .success-message {
            color: var(--primary-green);
            font-size: 0.9rem;
        }

        .navbar-brand i {
            font-size: 1.8rem;
        }

        .nav-link {
            font-weight: 500;
            color: white !important;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            color: var(--accent-yellow) !important;
        }

        .card {
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }

        .btn-primary {
            background-color: var(--primary-green);
            border-color: var(--primary-green);
        }

        .btn-primary:hover {
            background-color: #388E3C;
            border-color: #388E3C;
        }

        .urgency-normal {
            color: #4CAF50;
        }

        .urgency-urgent {
            color: #DC3545;
        }

        .table-responsive {
            margin-top: 20px;
        }

        .search-form {
            max-width: 300px;
        }

        .filter-btn {
            background-color: var(--primary-green);
            color: white;
            border-radius: 20px;
            padding: 5px 15px;
            margin-right: 5px;
        }

        .filter-btn.active {
            background-color: var(--accent-yellow);
            color: var(--text-dark);
        }

        .modal-content {
            border-radius: 10px;
        }

        .error-message {
            color: var(--error-red);
        }

        .success-message {
            color: var(--primary-green);
        }
    </style>
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
                        <a class="nav-link active" href="admin_dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Dashboard Section -->
    <section class="container my-5">
        <h1 class="text-center mb-4">Admin Dashboard</h1>
        <!-- Add Student Number -->
        <div class="card mb-5">
            <div class="card-header">
                <h4>Add Student Number</h4>
            </div>
            <div class="card-body">
                <form id="addStudentNumberForm">
                    <input type="hidden" name="action" value="add_student_number">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <div class="mb-3">
                        <label for="student_number" class="form-label">Student Number</label>
                        <input type="text" class="form-control" id="student_number" name="student_number" placeholder="Enter student number" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Student Number</button>
                    <div id="student_number_message" class="mt-2"></div>
                </form>
            </div>
        </div>
        <!-- Statistics -->
        <div class="row mb-5">
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Communiques</h5>
                        <p class="card-text"><?php echo $communique_count; ?> posted</p>
                        <p class="card-text text-muted"><?php echo $urgent_communique_count; ?> urgent</p>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createCommuniqueModal">Add New</button>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Students</h5>
                        <p class="card-text"><?php echo $student_count; ?> registered</p>
                        <p class="card-text text-muted"><?php echo $recent_login_count; ?> recent logins</p>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createStudentModal">Add New</button>
                    </div>
                </div>
            </div>

            <div class="col-md-3 col-sm-6 mb-4">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">Suggestions</h5>
                        <p class="card-text"><?php echo $suggestion_count; ?> received</p>
                        <a href="#suggestions" class="btn btn-primary">View</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="card text-center">
                    <div class="card-body">
                        <h5 class="card-title">System Status</h5>
                        <p class="card-text">Active</p>
                        <a href="index.php" class="btn btn-primary">View Public Page</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Communiques Section -->
        <div class="card mb-5">
            <div class="card-header">
                <h4>Recent Communiques</h4>
            </div>
            <div class="card-body">
                <form method="GET" class="mb-3">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="input-group search-form">
                                <input type="text" class="form-control" name="communique_search" placeholder="Search communiques..." value="<?php echo htmlspecialchars($communique_search); ?>">
                                <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <a href="?communique_urgency=Urgent" class="filter-btn <?php echo $communique_urgency == 'Urgent' ? 'active' : ''; ?>">Urgent</a>
                            <a href="?communique_urgency=Normal" class="filter-btn <?php echo $communique_urgency == 'Normal' ? 'active' : ''; ?>">Normal</a>
                            <?php if (!empty($communique_search) || !empty($communique_urgency)): ?>
                                <a href="admin_dashboard.php" class="text-danger ms-2">Clear Filters</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Urgency</th>
                                <th>Pinned</th>
                                <th>Posted</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="communiqueTable">
                            <?php foreach ($communiques as $communique): ?>
                                <tr data-id="<?php echo $communique['id']; ?>">
                                    <td><?php echo htmlspecialchars($communique['title']); ?></td>
                                    <td class="urgency-<?php echo strtolower($communique['urgency']); ?>">
                                        <?php echo htmlspecialchars($communique['urgency']); ?>
                                    </td>
                                    <td><?php echo $communique['pinned'] ? '<i class="fas fa-thumbtack"></i>' : ''; ?></td>
                                    <td><?php echo date('M j, Y', strtotime($communique['created_at'])); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary edit-communique"
                                            data-id="<?php echo $communique['id']; ?>"
                                            data-title="<?php echo htmlspecialchars($communique['title']); ?>"
                                            data-description="<?php echo htmlspecialchars($communique['description']); ?>"
                                            data-urgency="<?php echo htmlspecialchars($communique['urgency']); ?>"
                                            data-pinned="<?php echo $communique['pinned']; ?>"
                                            data-bs-toggle="modal" data-bs-target="#editCommuniqueModal">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger delete-communique"
                                            data-id="<?php echo $communique['id']; ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Students Section -->
        <div class="card mb-5">
            <div class="card-header">
                <h4>Registered Students</h4>
            </div>
            <div class="card-body">
                <form method="GET" class="mb-3">
                    <div class="input-group search-form">
                        <input type="text" class="form-control" name="student_search" placeholder="Search students..." value="<?php echo htmlspecialchars($student_search); ?>">
                        <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                    </div>
                </form>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Student Number</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Room</th>
                                <th>Last Login</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="studentTable">
                            <?php foreach ($students as $student): ?>
                                <tr data-id="<?php echo $student['id']; ?>">
                                    <td><?php echo htmlspecialchars($student['student_number']); ?></td>
                                    <td><?php echo htmlspecialchars($student['name'] . ' ' . $student['surname']); ?></td>
                                    <td><?php echo htmlspecialchars($student['email']); ?></td>
                                    <td><?php echo htmlspecialchars($student['room_number'] ?: 'N/A'); ?></td>
                                    <td><?php echo $student['last_login'] ? date('M j, Y', strtotime($student['last_login'])) : 'Never'; ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary edit-student"
                                            data-id="<?php echo $student['id']; ?>"
                                            data-student_number="<?php echo htmlspecialchars($student['student_number']); ?>"
                                            data-name="<?php echo htmlspecialchars($student['name']); ?>"
                                            data-surname="<?php echo htmlspecialchars($student['surname']); ?>"
                                            data-email="<?php echo htmlspecialchars($student['email']); ?>"
                                            data-phone="<?php echo htmlspecialchars($student['phone'] ?? ''); ?>"
                                            data-room_number="<?php echo htmlspecialchars($student['room_number'] ?? ''); ?>"
                                            data-bs-toggle="modal" data-bs-target="#editStudentModal">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger delete-student"
                                            data-id="<?php echo $student['id']; ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Suggestions Section -->
        <div class="card mb-5" id="suggestions">
            <div class="card-header">
                <h4>Student Suggestions</h4>
            </div>
            <div class="card-body">
                <form method="GET" class="mb-3">
                    <div class="input-group search-form">
                        <input type="text" class="form-control" name="suggestion_search" placeholder="Search suggestions..." value="<?php echo htmlspecialchars($suggestion_search); ?>">
                        <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                    </div>
                </form>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Message</th>
                                <th>Received</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="suggestionTable">
                            <?php foreach ($suggestions as $suggestion): ?>
                                <tr data-id="<?php echo $suggestion['id']; ?>">
                                    <td><?php echo htmlspecialchars($suggestion['student_number'] . ' - ' . $suggestion['name'] . ' ' . $suggestion['surname']); ?></td>
                                    <td><?php echo htmlspecialchars(strlen($suggestion['suggestion']) > 50 ? substr($suggestion['suggestion'], 0, 47) . '...' : $suggestion['suggestion']); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($suggestion['created_at'])); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary view-suggestion"
                                            data-id="<?php echo $suggestion['id']; ?>"
                                            data-message="<?php echo htmlspecialchars($suggestion['suggestion']); ?>"
                                            data-bs-toggle="modal" data-bs-target="#viewSuggestionModal">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger delete-suggestion"
                                            data-id="<?php echo $suggestion['id']; ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <!-- Communique Modals -->
    <div class="modal fade" id="createCommuniqueModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create Communique</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="createCommuniqueForm">
                        <input type="hidden" name="action" value="create_communique">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <div class="mb-3">
                            <label for="create_title" class="form-label">Title</label>
                            <input type="text" class="form-control" id="create_title" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label for="create_description" class="form-label">Description</label>
                            <textarea class="form-control" id="create_description" name="description" rows="4" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="create_urgency" class="form-label">Urgency</label>
                            <select class="form-control" id="create_urgency" name="urgency" required>
                                <option value="Normal">Normal</option>
                                <option value="Urgent">Urgent</option>
                            </select>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="create_pinned" name="pinned">
                            <label class="form-check-label" for="create_pinned">Pin this communique</label>
                        </div>
                        <div id="create_communique_error" class="error-message"></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="createCommuniqueSubmit">Create</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editCommuniqueModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Communique</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editCommuniqueForm">
                        <input type="hidden" name="action" value="update_communique">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="mb-3">
                            <label for="edit_title" class="form-label">Title</label>
                            <input type="text" class="form-control" id="edit_title" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_description" class="form-label">Description</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="4" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="edit_urgency" class="form-label">Urgency</label>
                            <select class="form-control" id="edit_urgency" name="urgency" required>
                                <option value="Normal">Normal</option>
                                <option value="Urgent">Urgent</option>
                            </select>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="edit_pinned" name="pinned">
                            <label class="form-check-label" for="edit_pinned">Pin this communique</label>
                        </div>
                        <div id="edit_communique_error" class="error-message"></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="editCommuniqueSubmit">Update</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Student Modals -->
    <div class="modal fade" id="createStudentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="createStudentForm">
                        <input type="hidden" name="action" value="create_student">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="create_student_number" class="form-label">Student Number</label>
                                <input type="text" class="form-control" id="create_student_number" name="student_number" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="create_name" class="form-label">Name</label>
                                <input type="text" class="form-control" id="create_name" name="name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="create_surname" class="form-label">Surname</label>
                                <input type="text" class="form-control" id="create_surname" name="surname" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="create_email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="create_email" name="email" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="create_phone" class="form-label">Phone</label>
                                <input type="text" class="form-control" id="create_phone" name="phone">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="create_room_number" class="form-label">Room Number</label>
                                <input type="text" class="form-control" id="create_room_number" name="room_number">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="create_password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="create_password" name="password" required>
                            </div>
                            <div class="col-md-6 mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="create_notification_opt_in" name="notification_opt_in" checked>
                                <label class="form-check-label" for="create_notification_opt_in">Receive notifications</label>
                            </div>
                        </div>
                        <div id="create_student_error" class="error-message"></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="createStudentSubmit">Create</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editStudentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editStudentForm">
                        <input type="hidden" name="action" value="update_student">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="id" id="edit_student_id">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_student_number" class="form-label">Student Number</label>
                                <input type="text" class="form-control" id="edit_student_number" name="student_number" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_name" class="form-label">Name</label>
                                <input type="text" class="form-control" id="edit_name" name="name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_surname" class="form-label">Surname</label>
                                <input type="text" class="form-control" id="edit_surname" name="surname" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="edit_email" name="email" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_phone" class="form-label">Phone</label>
                                <input type="text" class="form-control" id="edit_phone" name="phone">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_room_number" class="form-label">Room Number</label>
                                <input type="text" class="form-control" id="edit_room_number" name="room_number">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_password" class="form-label">Password (leave blank to keep current)</label>
                                <input type="password" class="form-control" id="edit_password" name="password">
                            </div>
                            <div class="col-md-6 mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="edit_notification_opt_in" name="notification_opt_in">
                                <label class="form-check-label" for="edit_notification_opt_in">Receive notifications</label>
                            </div>
                        </div>
                        <div id="edit_student_error" class="error-message"></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="editStudentSubmit">Update</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Suggestion Modal -->
    <div class="modal fade" id="viewSuggestionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">View Suggestion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p id="view_suggestion_message"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="text-white text-center py-3" style="background-color: var(--primary-green);">
        <p>© 2025 Corridor Communique. Created by Thabang for Corridor Hills Residence.</p>
        <p>Saving <span class="text-accent">500 sheets</span> of paper and counting!</p>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/script.js"></script>
    <script>
        $(document).ready(function() {
            $('#addStudentNumberForm').submit(function(e) {
                e.preventDefault();
                $.ajax({
                    url: 'admin_dashboard.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            $('#addStudentNumberForm')[0].reset();
                            $('#student_number_message').removeClass('error-message').addClass('success-message').text(response.message);
                            setTimeout(function() {
                                $('#student_number_message').text('');
                            }, 3000);
                        } else {
                            $('#student_number_message').removeClass('success-message').addClass('error-message').text(response.message);
                        }
                    },
                    error: function() {
                        $('#student_number_message').removeClass('success-message').addClass('error-message').text('An error occurred. Please try again.');
                    }
                });
            });
        });




        $(document).ready(function() {
            // Create Communique
            $('#createCommuniqueSubmit').click(function() {
                $.ajax({
                    url: 'admin_dashboard.php',
                    type: 'POST',
                    data: $('#createCommuniqueForm').serialize(),
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            $('#createCommuniqueModal').modal('hide');
                            $('#createCommuniqueForm')[0].reset();
                            $('#create_communique_error').text('');
                            location.reload(); // Refresh to show new communique
                        } else {
                            $('#create_communique_error').text(response.message);
                        }
                    },
                    error: function() {
                        $('#create_communique_error').text('An error occurred. Please try again.');
                    }
                });
            });

            // Edit Communique
            $('.edit-communique').click(function() {
                const id = $(this).data('id');
                $('#edit_id').val(id);
                $('#edit_title').val($(this).data('title'));
                $('#edit_description').val($(this).data('description'));
                $('#edit_urgency').val($(this).data('urgency'));
                $('#edit_pinned').prop('checked', $(this).data('pinned') == 1);
                $('#edit_communique_error').text('');
            });

            $('#editCommuniqueSubmit').click(function() {
                $.ajax({
                    url: 'admin_dashboard.php',
                    type: 'POST',
                    data: $('#editCommuniqueForm').serialize(),
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            $('#editCommuniqueModal').modal('hide');
                            $('#edit_communique_error').text('');
                            location.reload();
                        } else {
                            $('#edit_communique_error').text(response.message);
                        }
                    },
                    error: function() {
                        $('#edit_communique_error').text('An error occurred. Please try again.');
                    }
                });
            });

            // Delete Communique
            $('.delete-communique').click(function() {
                if (confirm('Are you sure you want to delete this communique?')) {
                    const id = $(this).data('id');
                    $.ajax({
                        url: 'admin_dashboard.php',
                        type: 'POST',
                        data: {
                            action: 'delete_communique',
                            id: id,
                            csrf_token: '<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>'
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                $(`#communiqueTable tr[data-id="${id}"]`).remove();
                            } else {
                                alert(response.message);
                            }
                        },
                        error: function() {
                            alert('An error occurred. Please try again.');
                        }
                    });
                }
            });

            // Create Student
            $('#createStudentSubmit').click(function() {
                $.ajax({
                    url: 'admin_dashboard.php',
                    type: 'POST',
                    data: $('#createStudentForm').serialize(),
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            $('#createStudentModal').modal('hide');
                            $('#createStudentForm')[0].reset();
                            $('#create_student_error').text('');
                            location.reload();
                        } else {
                            $('#create_student_error').text(response.message);
                        }
                    },
                    error: function() {
                        $('#create_student_error').text('An error occurred. Please try again.');
                    }
                });
            });

            // Edit Student
            $('.edit-student').click(function() {
                const id = $(this).data('id');
                $('#edit_student_id').val(id);
                $('#edit_student_number').val($(this).data('student_number'));
                $('#edit_name').val($(this).data('name'));
                $('#edit_surname').val($(this).data('surname'));
                $('#edit_email').val($(this).data('email'));
                $('#edit_phone').val($(this).data('phone'));
                $('#edit_room_number').val($(this).data('room_number'));
                $('#edit_notification_opt_in').prop('checked', $(this).data('notification_opt_in') == 1);
                $('#edit_student_error').text('');
            });

            $('#editStudentSubmit').click(function() {
                $.ajax({
                    url: 'admin_dashboard.php',
                    type: 'POST',
                    data: $('#editStudentForm').serialize(),
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            $('#editStudentModal').modal('hide');
                            $('#edit_student_error').text('');
                            location.reload();
                        } else {
                            $('#edit_student_error').text(response.message);
                        }
                    },
                    error: function() {
                        $('#edit_student_error').text('An error occurred. Please try again.');
                    }
                });
            });

            // Delete Student
            $('.delete-student').click(function() {
                if (confirm('Are you sure you want to delete this student? This will also delete their suggestions.')) {
                    const id = $(this).data('id');
                    $.ajax({
                        url: 'admin_dashboard.php',
                        type: 'POST',
                        data: {
                            action: 'delete_student',
                            id: id,
                            csrf_token: '<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>'
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                $(`#studentTable tr[data-id="${id}"]`).remove();
                            } else {
                                alert(response.message);
                            }
                        },
                        error: function() {
                            alert('An error occurred. Please try again.');
                        }
                    });
                }
            });

            // View Suggestion
            $('.view-suggestion').click(function() {
                $('#view_suggestion_message').text($(this).data('message'));
            });

            // Delete Suggestion
            $('.delete-suggestion').click(function() {
                if (confirm('Are you sure you want to delete this suggestion?')) {
                    const id = $(this).data('id');
                    $.ajax({
                        url: 'admin_dashboard.php',
                        type: 'POST',
                        data: {
                            action: 'delete_suggestion',
                            id: id,
                            csrf_token: '<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>'
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                $(`#suggestionTable tr[data-id="${id}"]`).remove();
                            } else {
                                alert(response.message);
                            }
                        },
                        error: function() {
                            alert('An error occurred. Please try again.');
                        }
                    });
                }
            });
        });
    </script>
</body>

</html>
<?php $conn->close(); ?>