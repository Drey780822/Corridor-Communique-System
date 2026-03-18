<?php
session_start();
require_once 'include/db_connect.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Check if communique ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: admin_dashboard.php");
    exit();
}
$communique_id = $_GET['id'];

// Fetch communique
$stmt = $conn->prepare("SELECT title, description, category_id, urgency, pinned FROM communiques WHERE id = ?");
$stmt->bind_param("i", $communique_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 0) {
    header("Location: admin_dashboard.php");
    exit();
}
$communique = $result->fetch_assoc();
$stmt->close();

// Fetch categories
$categories = $conn->query("SELECT id, name FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);

// Handle form submission
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category_id = $_POST['category_id'];
    $urgency = $_POST['urgency'];
    $pinned = isset($_POST['pinned']) ? 1 : 0;

    if (empty($title) || empty($description) || empty($category_id)) {
        $error = 'Please fill in all required fields.';
    } else {
        $stmt = $conn->prepare("UPDATE communiques SET title = ?, description = ?, category_id = ?, urgency = ?, pinned = ? WHERE id = ?");
        $stmt->bind_param("ssisii", $title, $description, $category_id, $urgency, $pinned, $communique_id);
        
        if ($stmt->execute()) {
            $success = 'Communique updated successfully!';
        } else {
            $error = 'Failed to update communique. Please try again.';
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
    <title>Edit Communique - Corridor Communique</title>
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
                        <a class="nav-link" href="admin_manage_students.php">Manage Students</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Edit Communique Section -->
    <section class="container my-5">
        <h1 class="text-center mb-4">Edit Communique</h1>
        <div class="row justify-content-center">
            <div class="col-md-6">
                <?php if ($error): ?>
                    <p class="text-error mb-3"><?php echo htmlspecialchars($error); ?></p>
                <?php elseif ($success): ?>
                    <p class="text-success mb-3"><?php echo htmlspecialchars($success); ?></p>
                <?php endif; ?>
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="title" class="form-label">Title</label>
                        <input type="text" class="form-control" id="title" name="title" 
                               value="<?php echo htmlspecialchars($communique['title']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="category_id" class="form-label">Category</label>
                        <select class="form-control" id="category_id" name="category_id" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" 
                                        <?php if ($category['id'] == $communique['category_id']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="5" required>
                            <?php echo htmlspecialchars($communique['description']); ?>
                        </textarea>
                    </div>
                    <div class="mb-3">
                        <label for="urgency" class="form-label">Urgency</label>
                        <select class="form-control" id="urgency" name="urgency" required>
                            <option value="Normal" <?php if ($communique['urgency'] == 'Normal') echo 'selected'; ?>>Normal</option>
                            <option value="Urgent" <?php if ($communique['urgency'] == 'Urgent') echo 'selected'; ?>>Urgent</option>
                        </select>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="pinned" name="pinned" 
                               <?php if ($communique['pinned']) echo 'checked'; ?>>
                        <label class="form-check-label" for="pinned">Pin to Top</label>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Update Communique</button>
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