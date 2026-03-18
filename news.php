<?php
session_start();
require_once 'include/db_connect.php';

// Static contact details
$contact_phone = '+27 12 345 6789';
$contact_email = 'info@corridorcommunique.co.za';

// Handle search and filters
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_urgency = isset($_GET['urgency']) ? $_GET['urgency'] : '';

$query = "SELECT c.id, c.title, c.description, c.urgency, c.pinned, c.created_at, a.name, a.surname 
          FROM communiques c 
          JOIN admins a ON c.admin_id = a.id 
          WHERE 1=1";
$params = [];
$types = "";

if (!empty($search_term)) {
    $query .= " AND (c.title LIKE ? OR c.description LIKE ?)";
    $search_param = "%$search_term%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}
if (!empty($filter_urgency)) {
    $query .= " AND c.urgency = ?";
    $params[] = $filter_urgency;
    $types .= "s";
}

$query .= " ORDER BY c.pinned DESC, c.created_at DESC LIMIT 10";
$stmt = $conn->prepare($query);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$communiques = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch total announcements count
$total_announcements = 0;
$query_count = "SELECT COUNT(*) as total FROM communiques WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
$result_count = $conn->query($query_count);
if ($result_count && $row_count = $result_count->fetch_assoc()) {
    $total_announcements = $row_count['total'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>News - Corridor Communique</title>
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
        .urgency-normal { color: white; }
        .urgency-urgent { color: white; }
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
        .news-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        .news-card:hover {
            transform: translateY(-5px);
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
            background-color: #388E3C;
        }
        .filter-badge.active {
            background-color: var(--accent-yellow);
            color: var(--text-dark);
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
                    <a href="support.php"><i class="fas fa-headset me-1"></i> Support</a>
                    <a href="news.php" class="active"><i class="fas fa-newspaper me-1"></i> News
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
        <h2 class="text-center mb-4">Latest News</h2>
        <!-- Search and Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="input-group">
                                <input class="form-control" type="search" name="search" placeholder="Search news..." 
                                       value="<?php echo htmlspecialchars($search_term); ?>">
                                <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Filter by Urgency:</strong>
                            <a href="?urgency=Urgent<?php echo !empty($search_term) ? '&search='.urlencode($search_term) : ''; ?>" 
                               class="filter-badge <?php echo $filter_urgency == 'Urgent' ? 'active' : ''; ?>">Urgent</a>
                            <a href="?urgency=Normal<?php echo !empty($search_term) ? '&search='.urlencode($search_term) : ''; ?>" 
                               class="filter-badge <?php echo $filter_urgency == 'Normal' ? 'active' : ''; ?>">Normal</a>
                            <?php if (!empty($filter_urgency) || !empty($search_term)): ?>
                                <a href="news.php" class="text-danger ms-2">Clear Filters</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <!-- News List -->
        <div class="row">
            <?php if (empty($communiques)): ?>
                <p class="text-center">No news items found. <a href="index.php#login">Log in</a> to view all updates.</p>
            <?php else: ?>
                <?php foreach ($communiques as $communique): ?>
                    <div class="col-md-6 mb-4">
                        <div class="news-card p-3">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="urgency-<?php echo strtolower($communique['urgency']); ?>">
                                    <?php echo htmlspecialchars($communique['urgency']); ?>
                                </span>
                                <?php if ($communique['pinned']): ?>
                                    <span class="badge bg-warning text-dark"><i class="fas fa-thumbtack me-1"></i> Pinned</span>
                                <?php endif; ?>
                            </div>
                            <h5><?php echo htmlspecialchars($communique['title']); ?></h5>
                            <p>
                                <?php
                                $desc = strip_tags($communique['description']);
                                echo strlen($desc) > 150 ? substr($desc, 0, 147) . '...' : $desc;
                                ?>
                            </p>
                            <p class="text-muted">
                                <small>
                                    Posted by <?php echo htmlspecialchars($communique['name'] . ' ' . $communique['surname']); ?>
                                    on <?php echo date('M j, Y', strtotime($communique['created_at'])); ?>
                                </small>
                            </p>
                            <a href="index.php#login" class="btn btn-outline-primary btn-sm">Log in to read more</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
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