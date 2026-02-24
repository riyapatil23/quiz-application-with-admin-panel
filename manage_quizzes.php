<?php
require_once '../config.php';

// Check if admin is logged in
if (!is_admin_logged_in()) {
    redirect('login.php');
}

$message = '';
$message_type = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $title = sanitize_input($_POST['title']);
                $description = sanitize_input($_POST['description']);
                $category_id = (int)$_POST['category_id'];
                $time_limit = (int)$_POST['time_limit'];
                $difficulty_level = sanitize_input($_POST['difficulty_level']);
                $is_featured = isset($_POST['is_featured']) ? 1 : 0;
                $admin_id = $_SESSION['admin_id'];
                
                if (!empty($title) && !empty($description) && $category_id > 0) {
                    // Check if is_featured column exists
                    $check_column = mysqli_query($conn, "SHOW COLUMNS FROM quizzes LIKE 'is_featured'");
                    $has_is_featured = mysqli_num_rows($check_column) > 0;
                    
                    // Check if created_by column exists
                    $check_created_by = mysqli_query($conn, "SHOW COLUMNS FROM quizzes LIKE 'created_by'");
                    $has_created_by = mysqli_num_rows($check_created_by) > 0;
                    
                    // Build insert query based on available columns
                    if ($has_is_featured && $has_created_by) {
                        $insert_query = "INSERT INTO quizzes (title, description, category_id, time_limit, difficulty_level, is_featured, created_by) 
                                       VALUES ('$title', '$description', $category_id, $time_limit, '$difficulty_level', $is_featured, $admin_id)";
                    } else if ($has_is_featured) {
                        $insert_query = "INSERT INTO quizzes (title, description, category_id, time_limit, difficulty_level, is_featured) 
                                       VALUES ('$title', '$description', $category_id, $time_limit, '$difficulty_level', $is_featured)";
                    } else if ($has_created_by) {
                        $insert_query = "INSERT INTO quizzes (title, description, category_id, time_limit, difficulty_level, created_by) 
                                       VALUES ('$title', '$description', $category_id, $time_limit, '$difficulty_level', $admin_id)";
                    } else {
                        $insert_query = "INSERT INTO quizzes (title, description, category_id, time_limit, difficulty_level) 
                                       VALUES ('$title', '$description', $category_id, $time_limit, '$difficulty_level')";
                    }
                    
                    if (mysqli_query($conn, $insert_query)) {
                        $message = 'Quiz added successfully!';
                        $message_type = 'success';
                    } else {
                        $message = 'Error adding quiz: ' . mysqli_error($conn);
                        $message_type = 'danger';
                    }
                } else {
                    $message = 'Please fill in all required fields.';
                    $message_type = 'warning';
                }
                break;
                
            case 'edit':
                $quiz_id = (int)$_POST['quiz_id'];
                $title = sanitize_input($_POST['title']);
                $description = sanitize_input($_POST['description']);
                $category_id = (int)$_POST['category_id'];
                $time_limit = (int)$_POST['time_limit'];
                $difficulty_level = sanitize_input($_POST['difficulty_level']);
                $is_featured = isset($_POST['is_featured']) ? 1 : 0;
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                
                if (!empty($title) && !empty($description) && $category_id > 0 && $quiz_id > 0) {
                    // Check which columns exist
                    $check_is_featured = mysqli_query($conn, "SHOW COLUMNS FROM quizzes LIKE 'is_featured'");
                    $has_is_featured = mysqli_num_rows($check_is_featured) > 0;
                    
                    $check_is_active = mysqli_query($conn, "SHOW COLUMNS FROM quizzes LIKE 'is_active'");
                    $has_is_active = mysqli_num_rows($check_is_active) > 0;
                    
                    $check_updated_at = mysqli_query($conn, "SHOW COLUMNS FROM quizzes LIKE 'updated_at'");
                    $has_updated_at = mysqli_num_rows($check_updated_at) > 0;
                    
                    // Build update query based on available columns
                    $update_fields = [
                        "title = '$title'",
                        "description = '$description'",
                        "category_id = $category_id",
                        "time_limit = $time_limit",
                        "difficulty_level = '$difficulty_level'"
                    ];
                    
                    if ($has_is_featured) {
                        $update_fields[] = "is_featured = $is_featured";
                    }
                    
                    if ($has_is_active) {
                        $update_fields[] = "is_active = $is_active";
                    }
                    
                    if ($has_updated_at) {
                        $update_fields[] = "updated_at = NOW()";
                    }
                    
                    $update_query = "UPDATE quizzes SET " . implode(', ', $update_fields) . " WHERE quiz_id = $quiz_id";
                    
                    if (mysqli_query($conn, $update_query)) {
                        $message = 'Quiz updated successfully!';
                        $message_type = 'success';
                    } else {
                        $message = 'Error updating quiz: ' . mysqli_error($conn);
                        $message_type = 'danger';
                    }
                } else {
                    $message = 'Please fill in all required fields.';
                    $message_type = 'warning';
                }
                break;
                
            case 'delete':
                $quiz_id = (int)$_POST['quiz_id'];
                if ($quiz_id > 0) {
                    // First delete related questions and results
                    mysqli_query($conn, "DELETE FROM attempts WHERE result_id IN (SELECT result_id FROM results WHERE quiz_id = $quiz_id)");
                    mysqli_query($conn, "DELETE FROM results WHERE quiz_id = $quiz_id");
                    mysqli_query($conn, "DELETE FROM questions WHERE quiz_id = $quiz_id");
                    
                    $delete_query = "DELETE FROM quizzes WHERE quiz_id = $quiz_id";
                    if (mysqli_query($conn, $delete_query)) {
                        $message = 'Quiz deleted successfully!';
                        $message_type = 'success';
                    } else {
                        $message = 'Error deleting quiz: ' . mysqli_error($conn);
                        $message_type = 'danger';
                    }
                }
                break;
        }
    }
}

// Get all quizzes with category information
$quizzes_query = "SELECT q.*, c.category_name, 
                  (SELECT COUNT(*) FROM questions WHERE quiz_id = q.quiz_id) as question_count,
                  (SELECT COUNT(*) FROM results WHERE quiz_id = q.quiz_id) as attempt_count
                  FROM quizzes q 
                  LEFT JOIN categories c ON q.category_id = c.category_id 
                  ORDER BY q.created_at DESC";
$quizzes_result = mysqli_query($conn, $quizzes_query);

// Get categories for dropdown
$categories_query = "SELECT * FROM categories ORDER BY category_name";
$categories_result = mysqli_query($conn, $categories_query);

// Get quiz for editing if edit mode
$edit_quiz = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $edit_query = "SELECT * FROM quizzes WHERE quiz_id = $edit_id";
    $edit_result = mysqli_query($conn, $edit_query);
    if (mysqli_num_rows($edit_result) > 0) {
        $edit_quiz = mysqli_fetch_assoc($edit_result);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Quizzes - <?php echo SITE_NAME; ?></title>
    
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
                            <a class="nav-link active" href="manage_quizzes.php">
                                <i class="fas fa-clipboard-list me-2"></i>Manage Quizzes
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manage_questions.php">
                                <i class="fas fa-question-circle me-2"></i>Manage Questions
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manage_categories.php">
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
                    <h1 class="h2"><i class="fas fa-clipboard-list me-2"></i>Manage Quizzes</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addQuizModal">
                            <i class="fas fa-plus me-1"></i>Add New Quiz
                        </button>
                    </div>
                </div>

                <!-- Messages -->
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Quizzes Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">All Quizzes</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Title</th>
                                        <th>Category</th>
                                        <th>Questions</th>
                                        <th>Time Limit</th>
                                        <th>Difficulty</th>
                                        <th>Status</th>
                                        <th>Attempts</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($quiz = mysqli_fetch_assoc($quizzes_result)): ?>
                                        <tr>
                                            <td><?php echo $quiz['quiz_id']; ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($quiz['title']); ?></strong>
                                                <?php if (isset($quiz['is_featured']) && $quiz['is_featured']): ?>
                                                    <span class="badge bg-warning ms-1">Featured</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($quiz['category_name']); ?></td>
                                            <td>
                                                <span class="badge bg-info"><?php echo $quiz['question_count']; ?></span>
                                            </td>
                                            <td><?php echo $quiz['time_limit']; ?> min</td>
                                            <td>
                                                <span class="badge <?php 
                                                    echo $quiz['difficulty_level'] == 'Easy' ? 'bg-success' : 
                                                        ($quiz['difficulty_level'] == 'Medium' ? 'bg-warning' : 'bg-danger'); 
                                                ?>">
                                                    <?php echo $quiz['difficulty_level']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo $quiz['is_active'] ? 'bg-success' : 'bg-secondary'; ?>">
                                                    <?php echo $quiz['is_active'] ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </td>
                                            <td><?php echo $quiz['attempt_count']; ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="manage_questions.php?quiz_id=<?php echo $quiz['quiz_id']; ?>" 
                                                       class="btn btn-outline-primary" title="Manage Questions">
                                                        <i class="fas fa-question"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-outline-success" 
                                                            onclick="editQuiz(<?php echo htmlspecialchars(json_encode($quiz)); ?>)" 
                                                            title="Edit Quiz">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-danger" 
                                                            onclick="deleteQuiz(<?php echo $quiz['quiz_id']; ?>, '<?php echo htmlspecialchars($quiz['title']); ?>')" 
                                                            title="Delete Quiz">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Add Quiz Modal -->
    <div class="modal fade" id="addQuizModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>Add New Quiz
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label for="title" class="form-label">Quiz Title *</label>
                                <input type="text" class="form-control" id="title" name="title" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="category_id" class="form-label">Category *</label>
                                <select class="form-select" id="category_id" name="category_id" required>
                                    <option value="">Select Category</option>
                                    <?php 
                                    mysqli_data_seek($categories_result, 0);
                                    while ($category = mysqli_fetch_assoc($categories_result)): 
                                    ?>
                                        <option value="<?php echo $category['category_id']; ?>">
                                            <?php echo htmlspecialchars($category['category_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description *</label>
                            <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="time_limit" class="form-label">Time Limit (minutes)</label>
                                <input type="number" class="form-control" id="time_limit" name="time_limit" value="30" min="1" max="180">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="difficulty_level" class="form-label">Difficulty Level</label>
                                <select class="form-select" id="difficulty_level" name="difficulty_level">
                                    <option value="Easy">Easy</option>
                                    <option value="Medium" selected>Medium</option>
                                    <option value="Hard">Hard</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured">
                                <label class="form-check-label" for="is_featured">
                                    Featured Quiz (will appear on homepage)
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Add Quiz
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Quiz Modal -->
    <div class="modal fade" id="editQuizModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Edit Quiz
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editQuizForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="quiz_id" id="edit_quiz_id">
                        
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label for="edit_title" class="form-label">Quiz Title *</label>
                                <input type="text" class="form-control" id="edit_title" name="title" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="edit_category_id" class="form-label">Category *</label>
                                <select class="form-select" id="edit_category_id" name="category_id" required>
                                    <option value="">Select Category</option>
                                    <?php 
                                    mysqli_data_seek($categories_result, 0);
                                    while ($category = mysqli_fetch_assoc($categories_result)): 
                                    ?>
                                        <option value="<?php echo $category['category_id']; ?>">
                                            <?php echo htmlspecialchars($category['category_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_description" class="form-label">Description *</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="3" required></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_time_limit" class="form-label">Time Limit (minutes)</label>
                                <input type="number" class="form-control" id="edit_time_limit" name="time_limit" min="1" max="180">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_difficulty_level" class="form-label">Difficulty Level</label>
                                <select class="form-select" id="edit_difficulty_level" name="difficulty_level">
                                    <option value="Easy">Easy</option>
                                    <option value="Medium">Medium</option>
                                    <option value="Hard">Hard</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="edit_is_featured" name="is_featured">
                                    <label class="form-check-label" for="edit_is_featured">
                                        Featured Quiz
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active">
                                    <label class="form-check-label" for="edit_is_active">
                                        Active Quiz
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save me-1"></i>Update Quiz
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteQuizModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle me-2"></i>Confirm Delete
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the quiz "<strong id="deleteQuizTitle"></strong>"?</p>
                    <div class="alert alert-warning">
                        <i class="fas fa-warning me-2"></i>
                        This will also delete all questions and user results associated with this quiz. This action cannot be undone.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="quiz_id" id="deleteQuizId">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash me-1"></i>Delete Quiz
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function editQuiz(quiz) {
            document.getElementById('edit_quiz_id').value = quiz.quiz_id;
            document.getElementById('edit_title').value = quiz.title;
            document.getElementById('edit_description').value = quiz.description;
            document.getElementById('edit_category_id').value = quiz.category_id;
            document.getElementById('edit_time_limit').value = quiz.time_limit;
            document.getElementById('edit_difficulty_level').value = quiz.difficulty_level;
            document.getElementById('edit_is_featured').checked = quiz.is_featured == 1 || false;
            document.getElementById('edit_is_active').checked = quiz.is_active == 1 || false;
            
            const modal = new bootstrap.Modal(document.getElementById('editQuizModal'));
            modal.show();
        }
        
        function deleteQuiz(quizId, quizTitle) {
            document.getElementById('deleteQuizId').value = quizId;
            document.getElementById('deleteQuizTitle').textContent = quizTitle;
            
            const modal = new bootstrap.Modal(document.getElementById('deleteQuizModal'));
            modal.show();
        }
    </script>
</body>
</html>
