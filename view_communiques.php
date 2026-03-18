<?php
session_start();
require_once 'include/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['student_id'])) {
    header("Location: index.php");
    exit();
}

// Get student information
$student_id = $_SESSION['student_id'];
$student_query = "SELECT name, surname, room_number, student_number 
                 FROM students 
                 WHERE id = ?";
$stmt = $conn->prepare($student_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student_result = $stmt->get_result();
$student = $student_result->fetch_assoc();
$stmt->close();

// Get last login time
$last_login_query = "SELECT last_login FROM students WHERE id = ?";
$stmt = $conn->prepare($last_login_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$last_login_result = $stmt->get_result();
$last_login = $last_login_result->fetch_assoc()['last_login'] ?? '2000-01-01 00:00:00'; // Default to old date if null
$stmt->close();

// Update last login time
$update_login = "UPDATE students SET last_login = NOW() WHERE id = ?";
$stmt = $conn->prepare($update_login);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$stmt->close();

// Fetch all communiques with filter options
$filter_urgency = isset($_GET['urgency']) ? $_GET['urgency'] : '';
$filter_pinned = isset($_GET['pinned']) ? (int)$_GET['pinned'] : -1;
$search_term = isset($_GET['search']) ? $_GET['search'] : '';

$query = "SELECT c.id, c.title, c.description, c.urgency, c.pinned, c.created_at, a.name, a.surname 
          FROM communiques c 
          JOIN admins a ON c.admin_id = a.id 
          WHERE 1=1";

// Apply filters
if (!empty($filter_urgency)) {
    $query .= " AND c.urgency = ?";
}
if ($filter_pinned >= 0) {
    $query .= " AND c.pinned = ?";
}
if (!empty($search_term)) {
    $query .= " AND (c.title LIKE ? OR c.description LIKE ?)";
}

$query .= " ORDER BY c.pinned DESC, c.created_at DESC";

$stmt = $conn->prepare($query);

// Bind parameters based on filters
$types = "";
$params = [];

if (!empty($filter_urgency)) {
    $types .= "s";
    $params[] = $filter_urgency;
}
if ($filter_pinned >= 0) {
    $types .= "i";
    $params[] = $filter_pinned;
}
if (!empty($search_term)) {
    $types .= "ss";
    $search_param = "%" . $search_term . "%";
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$communiques = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Count urgent announcements
$urgent_query = "SELECT COUNT(*) as count FROM communiques WHERE urgency = 'Urgent' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
$urgent_result = $conn->query($urgent_query);
$urgent_count = $urgent_result->fetch_assoc()['count'];

// Count pinned announcements
$pinned_query = "SELECT COUNT(*) as count FROM communiques WHERE pinned = 1";
$pinned_result = $conn->query($pinned_query);
$pinned_count = $pinned_result->fetch_assoc()['count'];

// Count today's announcements
$today_query = "SELECT COUNT(*) as count FROM communiques WHERE DATE(created_at) = CURDATE()";
$today_result = $conn->query($today_query);
$today_count = $today_result->fetch_assoc()['count'];

// Check for unread announcements (posted after last login)
$unread_query = "SELECT COUNT(*) as count FROM communiques WHERE created_at > ?";
$stmt = $conn->prepare($unread_query);
$stmt->bind_param("s", $last_login);
$stmt->execute();
$unread_result = $stmt->get_result();
$unread_count = $unread_result->fetch_assoc()['count'];
$stmt->close();

// CSRF token for suggestion form
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle AJAX suggestion submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_suggestion') {
    header('Content-Type: application/json');

    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit();
    }

    $suggestion = trim($_POST['suggestion']);
    $anonymous = isset($_POST['anonymous']) ? 1 : 0;

    if (empty($suggestion)) {
        echo json_encode(['success' => false, 'message' => 'Suggestion cannot be empty']);
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO suggestions (student_id, suggestion, anonymous, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("isi", $student_id, $suggestion, $anonymous);
    $success = $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => $success, 'message' => $success ? 'Suggestion submitted successfully' : 'Failed to submit suggestion']);
    exit();
}

// Fetch student's suggestions
$suggestion_filter = isset($_GET['suggestion_filter']) ? (int)$_GET['suggestion_filter'] : -1; // -1: All, 0: Non-Anonymous, 1: Anonymous
$suggestion_query = "SELECT id, suggestion, anonymous, created_at 
                    FROM suggestions 
                    WHERE student_id = ?";
$params = [$student_id];
$types = "i";

if ($suggestion_filter >= 0) {
    $suggestion_query .= " AND anonymous = ?";
    $params[] = $suggestion_filter;
    $types .= "i";
}

$suggestion_query .= " ORDER BY created_at DESC LIMIT 5";
$stmt = $conn->prepare($suggestion_query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$suggestions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Corridor Communique</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Additional styles for student dashboard */
        .dashboard-header {
            background: linear-gradient(135deg, var(--primary-green), #1B5E20);
            color: white;
            padding: 20px 0;
            margin-bottom: 30px;
        }
        .dashboard-stats {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        .stat-card {
            background-color: #f8f9fa;
            border-left: 4px solid var(--primary-green);
            padding: 15px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .stat-card.urgent {
            border-left-color: var(--error-red);
        }
        .stat-card.pinned {
            border-left-color: var(--accent-yellow);
        }
        .stat-card.today {
            border-left-color: #3F51B5;
        }
        .stat-card.unread {
            border-left-color: #FF9800;
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--text-dark);
        }
        .stat-title {
            color: #6c757d;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .filter-section {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        .communique-card {
            transition: all 0.3s ease;
        }
        .communique-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .filter-badge {
            background-color: var(--primary-green);
            color: white;
            font-size: 0.8rem;
            border-radius: 20px;
            padding: 5px 10px;
            margin-right: 5px;
            cursor: pointer;
        }
        .filter-badge:hover {
            background-color: #1B5E20;
        }
        .filter-badge.active {
            background-color: var(--accent-yellow);
            color: var(--text-dark);
        }
        .clear-filters {
            color: var(--error-red);
            cursor: pointer;
            text-decoration: none;
        }
        .clear-filters:hover {
            text-decoration: underline;
        }
        .sidebar {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            padding: 20px;
            height: 100%;
        }
        .profile-section {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        .profile-img {
            width: 80px;
            height: 80px;
            background-color: var(--primary-green);
            color: white;
            font-size: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            margin: 0 auto 15px;
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
        .unread-indicator {
            background-color: var(--accent-yellow);
            color: var(--text-dark);
            font-size: 0.7rem;
            font-weight: bold;
            border-radius: 50%;
            padding: 3px 6px;
            margin-left: 5px;
        }
        .breadcrumb {
            margin-bottom: 0;
        }
        .urgency-filter, .pinned-filter {
            cursor: pointer;
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
        .news-ticker {
            background-color: var(--primary-green);
            color: white;
            padding: 8px 0;
            overflow: hidden;
            position: relative;
        }
        .ticker-content {
            white-space: nowrap;
            overflow: hidden;
            position: absolute;
            animation: tickerAnimation 20s linear infinite;
        }
        @keyframes tickerAnimation {
            0% { transform: translateX(100%); }
            100% { transform: translateX(-100%); }
        }
        /* New styles for suggestions */
        .suggestion-section {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .suggestion-form {
            margin-bottom: 20px;
        }
        .suggestion-table {
            font-size: 0.9rem;
        }
        .suggestion-table th, .suggestion-table td {
            padding: 8px;
        }
        .error-message {
            color: var(--error-red);
            font-size: 0.9rem;
        }
        .success-message {
            color: var(--primary-green);
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="view_communiques.php">
                <i class="fas fa-mountain me-2"></i>Corridor Communique
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <!-- Search in navbar -->
                <form class="d-flex ms-auto me-2" action="" method="GET">
                    <div class="input-group">
                        <input class="form-control" type="search" name="search" placeholder="Search communiques..." 
                               value="<?php echo htmlspecialchars($search_term); ?>">
                        <button class="btn btn-outline-light" type="submit"><i class="fas fa-search"></i></button>
                    </div>
                </form>
                
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link active" href="view_communiques.php">
                            <i class="fas fa-bullhorn me-1"></i> Communiques
                            <?php if ($unread_count > 0): ?>
                                <span class="unread-indicator"><?php echo $unread_count; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">
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

    <!-- News Ticker -->
    <?php if (!empty($communiques) && isset($communiques[0])): ?>
    <div class="news-ticker">
        <div class="container position-relative">
            <div class="ticker-content">
                <?php foreach (array_slice($communiques, 0, 3) as $comm): ?>
                    <i class="fas fa-bullhorn me-2"></i> 
                    <strong><?php echo htmlspecialchars($comm['urgency']); ?>:</strong> 
                    <?php echo htmlspecialchars($comm['title']); ?>      
                <?php endforeach; ?>
            </div>
        </div><br>
<br>
    </div>
    <?php endif; ?>
    <!-- Dashboard Header -->
    <section class="dashboard-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1><i class="fas fa-tachometer-alt me-2"></i>Student Dashboard</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="view_communiques.php" class="text-white">Home</a></li>
                            <li class="breadcrumb-item active text-white" aria-current="page">Dashboard</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">Welcome, <?php echo htmlspecialchars($student['name'] . ' ' . $student['surname']); ?></p>
                    <p class="mb-0"><?php echo isset($student['room_number']) ? 
                        htmlspecialchars('Room ' . $student['room_number']) : 'Room not assigned'; ?></p>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container my-4">
        <div class="row">
            <!-- Main Content Area -->
            <div class="col-lg-8 mb-4">
                <!-- Stats Cards -->
                <div class="dashboard-stats mb-4">
                    <h4 class="mb-3">Communication Stats</h4>
                    <div class="row">
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="stat-card urgent">
                                <div class="stat-number"><?php echo $urgent_count; ?></div>
                                <div class="stat-title">Urgent</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="stat-card pinned">
                                <div class="stat-number"><?php echo $pinned_count; ?></div>
                                <div class="stat-title">Pinned</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="stat-card today">
                                <div class="stat-number"><?php echo $today_count; ?></div>
                                <div class="stat-title">Today</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="stat-card unread">
                                <div class="stat-number"><?php echo $unread_count; ?></div>
                                <div class="stat-title">Unread</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="filter-section mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="mb-0">Filters</h4>
                        <?php if (!empty($filter_urgency) || $filter_pinned >= 0 || !empty($search_term)): ?>
                            <a href="view_communiques.php" class="clear-filters">Clear all filters</a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <strong>Urgency:</strong>
                        <a href="view_communiques.php?urgency=Urgent<?php echo $filter_pinned >= 0 ? '&pinned='.$filter_pinned : ''; ?><?php echo !empty($search_term) ? '&search='.$search_term : ''; ?>" 
                           class="filter-badge urgency-filter <?php echo $filter_urgency == 'Urgent' ? 'active' : ''; ?>">
                            Urgent
                        </a>
                        <a href="view_communiques.php?urgency=Normal<?php echo $filter_pinned >= 0 ? '&pinned='.$filter_pinned : ''; ?><?php echo !empty($search_term) ? '&search='.$search_term : ''; ?>" 
                           class="filter-badge urgency-filter <?php echo $filter_urgency == 'Normal' ? 'active' : ''; ?>">
                            Normal
                        </a>
                    </div>
                    
                    <div>
                        <strong>Status:</strong>
                        <a href="view_communiques.php?pinned=1<?php echo !empty($filter_urgency) ? '&urgency='.$filter_urgency : ''; ?><?php echo !empty($search_term) ? '&search='.$search_term : ''; ?>" 
                           class="filter-badge pinned-filter <?php echo $filter_pinned == 1 ? 'active' : ''; ?>">
                            Pinned
                        </a>
                        <a href="view_communiques.php?pinned=0<?php echo !empty($filter_urgency) ? '&urgency='.$filter_urgency : ''; ?><?php echo !empty($search_term) ? '&search='.$search_term : ''; ?>" 
                           class="filter-badge pinned-filter <?php echo $filter_pinned === 0 ? 'active' : ''; ?>">
                            Regular
                        </a>
                    </div>
                </div>

                <!-- Communiques List -->
                <h4 class="mb-3">Communiques</h4>
                <div class="row">
                    <?php if (empty($communiques)): ?>
                        <div class="col-12">
                            <div class="alert alert-info">
                                No communiques found matching your filters. <a href="view_communiques.php">Clear filters</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($communiques as $communique): ?>
                            <div class="col-md-6 mb-4">
                                <div class="card communique-card h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <span class="urgency-<?php echo strtolower($communique['urgency']); ?>">
                                                <?php echo htmlspecialchars($communique['urgency']); ?>
                                            </span>
                                            <?php if ($communique['pinned']): ?>
                                                <span class="badge bg-warning text-dark ms-2">
                                                    <i class="fas fa-thumbtack me-1"></i> Pinned
                                                </span>
                                            <?php endif; ?>
                                            
                                            <?php if (strtotime($communique['created_at']) > strtotime($last_login) && strtotime($last_login) > 0): ?>
                                                <span class="badge bg-danger ms-auto">New</span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <h5 class="card-title">
                                            <?php echo htmlspecialchars($communique['title']); ?>
                                        </h5>
                                        
                                        <div class="card-text">
                                            <?php echo nl2br(htmlspecialchars($communique['description'])); ?>
                                        </div>
                                        
                                        <p class="card-text mt-3">
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
            </div>
            
            <!-- Sidebar -->
            <div class="col-lg-4">
                <div class="sidebar">
                    <!-- Profile Section -->
                    <div class="profile-section">
                        <div class="profile-img">
                            <?php echo strtoupper(substr($student['name'], 0, 1) . substr($student['surname'], 0, 1)); ?>
                        </div>
                        <h5><?php echo htmlspecialchars($student['name'] . ' ' . $student['surname']); ?></h5>
                        <p class="text-muted"><?php echo htmlspecialchars($student['student_number']); ?></p>
                    </div>
                    
                    <!-- Quick Links -->
                    <div class="quick-links mb-4">
                        <h5>Quick Links</h5>
                        <a href="profile.php"><i class="fas fa-user-circle"></i> My Profile</a>
                        <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
                        <a href="https://tutportal.tut.ac.za" target="_blank"><i class="fas fa-external-link-alt"></i> TUT Portal</a>
                        <a href="https://mytutor.tut.ac.za" target="_blank"><i class="fas fa-graduation-cap"></i> MyTUTor</a>
                        <a href="mailto:support@corridorcommunique.co.za"><i class="fas fa-envelope"></i> Contact Support</a>
                    </div>
                    
                    <!-- Suggestions Section -->
                    <div class="suggestion-section">
                        <h5>Submit a Suggestion</h5>
                        <form id="suggestionForm" class="suggestion-form">
                            <input type="hidden" name="action" value="submit_suggestion">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            <div class="mb-3">
                                <textarea class="form-control" name="suggestion" rows="3" placeholder="Enter your suggestion..." required></textarea>
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="anonymous" name="anonymous">
                                <label class="form-check-label" for="anonymous">Submit anonymously</label>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Submit</button>
                            <div id="suggestion_message" class="mt-2"></div>
                        </form>

                        <h5 class="mt-4">My Suggestions</h5>
                        <div class="mb-3">
                            <strong>Filter:</strong>
                            <a href="view_communiques.php?suggestion_filter=-1<?php echo !empty($filter_urgency) ? '&urgency='.$filter_urgency : ''; ?><?php echo $filter_pinned >= 0 ? '&pinned='.$filter_pinned : ''; ?><?php echo !empty($search_term) ? '&search='.urlencode($search_term) : ''; ?>" 
                               class="filter-badge <?php echo $suggestion_filter == -1 ? 'active' : ''; ?>">All</a>
                            <a href="view_communiques.php?suggestion_filter=1<?php echo !empty($filter_urgency) ? '&urgency='.$filter_urgency : ''; ?><?php echo $filter_pinned >= 0 ? '&pinned='.$filter_pinned : ''; ?><?php echo !empty($search_term) ? '&search='.urlencode($search_term) : ''; ?>" 
                               class="filter-badge <?php echo $suggestion_filter == 1 ? 'active' : ''; ?>">Anonymous</a>
                            <a href="view_communiques.php?suggestion_filter=0<?php echo !empty($filter_urgency) ? '&urgency='.$filter_urgency : ''; ?><?php echo $filter_pinned >= 0 ? '&pinned='.$filter_pinned : ''; ?><?php echo !empty($search_term) ? '&search='.urlencode($search_term) : ''; ?>" 
                               class="filter-badge <?php echo $suggestion_filter == 0 ? 'active' : ''; ?>">Non-Anonymous</a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover suggestion-table">
                                <thead>
                                    <tr>
                                        <th>Suggestion</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($suggestions)): ?>
                                        <tr>
                                            <td colspan="3" class="text-center">No suggestions submitted yet.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($suggestions as $suggestion): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars(strlen($suggestion['suggestion']) > 30 ? substr($suggestion['suggestion'], 0, 27) . '...' : $suggestion['suggestion']); ?></td>
                                                <td><?php echo $suggestion['anonymous'] ? 'Anonymous' : 'Non-Anonymous'; ?></td>
                                                <td><?php echo date('M j, Y', strtotime($suggestion['created_at'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
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
    <footer class="text-white text-center py-3">
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
        
        // If we have unread messages, highlight them
        $(document).ready(function() {
            setTimeout(function() {
                $('.badge.bg-danger').addClass('animate__animated animate__heartBeat');
            }, 1000);

            // Handle suggestion form submission
            $('#suggestionForm').submit(function(e) {
                e.preventDefault();
                $.ajax({
                    url: 'view_communiques.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            $('#suggestionForm')[0].reset();
                            $('#suggestion_message').removeClass('error-message').addClass('success-message').text(response.message);
                            setTimeout(function() {
                                location.reload(); // Refresh to show new suggestion
                            }, 1000);
                        } else {
                            $('#suggestion_message').removeClass('success-message').addClass('error-message').text(response.message);
                        }
                    },
                    error: function() {
                        $('#suggestion_message').removeClass('success-message').addClass('error-message').text('An error occurred. Please try again.');
                    }
                });
            });
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>