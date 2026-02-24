<?php
require_once '../config.php';

if (!is_admin_logged_in()) {
    redirect('login.php');
}

$message = '';
$message_type = '';

// Check if enhanced database structure exists
$check_columns = mysqli_query($conn, "SHOW COLUMNS FROM categories LIKE 'icon'");
$has_enhanced_structure = mysqli_num_rows($check_columns) > 0;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $category_name = sanitize_input($_POST['category_name']);
                $description = sanitize_input($_POST['description']);
                $icon = $has_enhanced_structure ? sanitize_input($_POST['icon']) : 'fas fa-question-circle';
                $color = $has_enhanced_structure ? sanitize_input($_POST['color']) : '#007bff';
                $is_featured = $has_enhanced_structure && isset($_POST['is_featured']) ? 1 : 0;
                
                if (!empty($category_name)) {
                    if ($has_enhanced_structure) {
                        $insert_query = "INSERT INTO categories (category_name, description, icon, color, is_featured) 
                                       VALUES ('$category_name', '$description', '$icon', '$color', $is_featured)";
                    } else {
                        $insert_query = "INSERT INTO categories (category_name, description) 
                                       VALUES ('$category_name', '$description')";
                    }
                    
                    if (mysqli_query($conn, $insert_query)) {
                        $message = 'Category added successfully!';
                        $message_type = 'success';
                    } else {
                        $message = 'Error adding category: ' . mysqli_error($conn);
                        $message_type = 'danger';
                    }
                } else {
                    $message = 'Category name is required.';
                    $message_type = 'warning';
                }
                break;
                
            case 'edit':
                $category_id = (int)$_POST['category_id'];
                $category_name = sanitize_input($_POST['category_name']);
                $description = sanitize_input($_POST['description']);
                
                if (!empty($category_name) && $category_id > 0) {
                    if ($has_enhanced_structure) {
                        $icon = sanitize_input($_POST['icon']);
                        $color = sanitize_input($_POST['color']);
                        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
                        
                        $update_query = "UPDATE categories SET 
                                       category_name = '$category_name', 
                                       description = '$description', 
                                       icon = '$icon', 
                                       color = '$color', 
                                       is_featured = $is_featured
                                       WHERE category_id = $category_id";
                    } else {
                        $update_query = "UPDATE categories SET 
                                       category_name = '$category_name', 
                                       description = '$description'
                                       WHERE category_id = $category_id";
                    }
                    
                    if (mysqli_query($conn, $update_query)) {
                        $message = 'Category updated successfully!';
                        $message_type = 'success';
                    } else {
                        $message = 'Error updating category: ' . mysqli_error($conn);
                        $message_type = 'danger';
                    }
                }
                break;
                
            case 'delete':
                $category_id = (int)$_POST['category_id'];
                if ($category_id > 0) {
                    // Check if category has quizzes
                    $check_query = "SELECT COUNT(*) as count FROM quizzes WHERE category_id = $category_id";
                    $check_result = mysqli_query($conn, $check_query);
                    $quiz_count = mysqli_fetch_assoc($check_result)['count'];
                    
                    if ($quiz_count > 0) {
                        $message = 'Cannot delete category. It has ' . $quiz_count . ' quiz(es) associated with it.';
                        $message_type = 'warning';
                    } else {
                        $delete_query = "DELETE FROM categories WHERE category_id = $category_id";
                        if (mysqli_query($conn, $delete_query)) {
                            $message = 'Category deleted successfully!';
                            $message_type = 'success';
                        } else {
                            $message = 'Error deleting category: ' . mysqli_error($conn);
                            $message_type = 'danger';
                        }
                    }
                }
                break;
        }
    }
}

// Get all categories with quiz count
if ($has_enhanced_structure) {
    $categories_query = "SELECT c.*, COUNT(q.quiz_id) as quiz_count 
                        FROM categories c 
                        LEFT JOIN quizzes q ON c.category_id = q.category_id 
                        GROUP BY c.category_id 
                        ORDER BY c.category_name";
} else {
    $categories_query = "SELECT c.*, COUNT(q.quiz_id) as quiz_count 
                        FROM categories c 
                        LEFT JOIN quizzes q ON c.category_id = q.category_id 
                        GROUP BY c.category_id 
                        ORDER BY c.category_name";
}
$categories_result = mysqli_query($conn, $categories_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories - <?php echo SITE_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block admin-sidebar collapse">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <h5 class="text-light">
                            <i class="fas fa-shield-alt me-2"></i>Admin Panel
                        </h5>
                        <small class="text-muted">Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?></small>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manage_quizzes.php">
                                <i class="fas fa-clipboard-list me-2"></i>Manage Quizzes
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manage_questions.php">
                                <i class="fas fa-question-circle me-2"></i>Manage Questions
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="manage_categories.php">
                                <i class="fas fa-tags me-2"></i>Categories
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="view_results.php">
                                <i class="fas fa-chart-bar me-2"></i>View Results
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manage_users.php">
                                <i class="fas fa-users me-2"></i>Manage Users
                            </a>
                        </li>
                        <li class="nav-item mt-3">
                            <a class="nav-link" href="../index.php" target="_blank">
                                <i class="fas fa-external-link-alt me-2"></i>View Site
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-tags me-2"></i>Manage Categories</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                            <i class="fas fa-plus me-1"></i>Add Category
                        </button>
                        <?php if (!$has_enhanced_structure): ?>
                            <a href="../upgrade_database.php" class="btn btn-warning ms-2">
                                <i class="fas fa-rocket me-1"></i>Upgrade Database
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Messages -->
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Database Status -->
                <?php if (!$has_enhanced_structure): ?>
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle me-2"></i>Basic Database Structure</h6>
                        <p class="mb-2">You're using the basic database structure. Upgrade to get:</p>
                        <ul class="mb-2">
                            <li>Category icons and colors</li>
                            <li>Featured categories</li>
                            <li>Enhanced visual design</li>
                        </ul>
                        <a href="../upgrade_database.php" class="btn btn-primary btn-sm">Upgrade Now</a>
                    </div>
                <?php endif; ?>

                <!-- Categories Grid -->
                <div class="row">
                    <?php while ($category = mysqli_fetch_assoc($categories_result)): ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card h-100" <?php if ($has_enhanced_structure): ?>style="border-left: 4px solid <?php echo $category['color'] ?? '#007bff'; ?>"<?php endif; ?>>
                                <div class="card-body">
                                    <div class="d-flex align-items-start justify-content-between">
                                        <div class="flex-grow-1">
                                            <?php if ($has_enhanced_structure): ?>
                                                <div class="category-icon mb-2" style="color: <?php echo $category['color'] ?? '#007bff'; ?>">
                                                    <i class="<?php echo $category['icon'] ?? 'fas fa-question-circle'; ?> fa-2x"></i>
                                                </div>
                                            <?php endif; ?>
                                            <h5 class="card-title">
                                                <?php echo htmlspecialchars($category['category_name']); ?>
                                                <?php if ($has_enhanced_structure && $category['is_featured']): ?>
                                                    <span class="badge bg-warning ms-1">Featured</span>
                                                <?php endif; ?>
                                            </h5>
                                            <p class="card-text text-muted">
                                                <?php echo htmlspecialchars($category['description']); ?>
                                            </p>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="badge bg-primary">
                                                    <?php echo $category['quiz_count']; ?> Quiz<?php echo $category['quiz_count'] != 1 ? 'es' : ''; ?>
                                                </span>
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-outline-success" 
                                                            onclick="editCategory(<?php echo htmlspecialchars(json_encode($category)); ?>)" 
                                                            title="Edit Category">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-danger" 
                                                            onclick="deleteCategory(<?php echo $category['category_id']; ?>, '<?php echo htmlspecialchars($category['category_name']); ?>', <?php echo $category['quiz_count']; ?>)" 
                                                            title="Delete Category">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- Add Category Modal -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>Add New Category
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="mb-3">
                            <label for="category_name" class="form-label">Category Name *</label>
                            <input type="text" class="form-control" id="category_name" name="category_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        
                        <?php if ($has_enhanced_structure): ?>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="icon" class="form-label">Icon Class</label>
                                    <input type="text" class="form-control" id="icon" name="icon" value="fas fa-question-circle" placeholder="fas fa-icon-name">
                                    <div class="form-text">Font Awesome icon class</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="color" class="form-label">Color</label>
                                    <input type="color" class="form-control form-control-color" id="color" name="color" value="#007bff">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured">
                                    <label class="form-check-label" for="is_featured">
                                        Featured Category (appears on homepage)
                                    </label>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Add Category
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Category Modal -->
    <div class="modal fade" id="editCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Edit Category
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="category_id" id="edit_category_id">
                        
                        <div class="mb-3">
                            <label for="edit_category_name" class="form-label">Category Name *</label>
                            <input type="text" class="form-control" id="edit_category_name" name="category_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_description" class="form-label">Description</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                        </div>
                        
                        <?php if ($has_enhanced_structure): ?>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="edit_icon" class="form-label">Icon Class</label>
                                    <input type="text" class="form-control" id="edit_icon" name="icon" placeholder="fas fa-icon-name">
                                    <div class="form-text">Font Awesome icon class</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="edit_color" class="form-label">Color</label>
                                    <input type="color" class="form-control form-control-color" id="edit_color" name="color">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="edit_is_featured" name="is_featured">
                                    <label class="form-check-label" for="edit_is_featured">
                                        Featured Category
                                    </label>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save me-1"></i>Update Category
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Category Modal -->
    <div class="modal fade" id="deleteCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle me-2"></i>Confirm Delete
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the category "<strong id="deleteCategoryName"></strong>"?</p>
                    <div id="deleteWarning" class="alert alert-warning" style="display: none;">
                        <i class="fas fa-warning me-2"></i>
                        This category has <span id="quizCount"></span> quiz(es) associated with it. Please move or delete those quizzes first.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" style="display: inline;" id="deleteCategoryForm">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="category_id" id="deleteCategoryId">
                        <button type="submit" class="btn btn-danger" id="deleteButton">
                            <i class="fas fa-trash me-1"></i>Delete Category
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function editCategory(category) {
            document.getElementById('edit_category_id').value = category.category_id;
            document.getElementById('edit_category_name').value = category.category_name;
            document.getElementById('edit_description').value = category.description || '';
            
            <?php if ($has_enhanced_structure): ?>
                document.getElementById('edit_icon').value = category.icon || 'fas fa-question-circle';
                document.getElementById('edit_color').value = category.color || '#007bff';
                document.getElementById('edit_is_featured').checked = category.is_featured == 1;
            <?php endif; ?>
            
            const modal = new bootstrap.Modal(document.getElementById('editCategoryModal'));
            modal.show();
        }
        
        function deleteCategory(categoryId, categoryName, quizCount) {
            document.getElementById('deleteCategoryId').value = categoryId;
            document.getElementById('deleteCategoryName').textContent = categoryName;
            
            const warning = document.getElementById('deleteWarning');
            const deleteButton = document.getElementById('deleteButton');
            
            if (quizCount > 0) {
                document.getElementById('quizCount').textContent = quizCount;
                warning.style.display = 'block';
                deleteButton.disabled = true;
                deleteButton.innerHTML = '<i class="fas fa-ban me-1"></i>Cannot Delete';
            } else {
                warning.style.display = 'none';
                deleteButton.disabled = false;
                deleteButton.innerHTML = '<i class="fas fa-trash me-1"></i>Delete Category';
            }
            
            const modal = new bootstrap.Modal(document.getElementById('deleteCategoryModal'));
            modal.show();
        }
    </script>
</body>
</html>
