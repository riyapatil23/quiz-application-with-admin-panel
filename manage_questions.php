<?php
require_once '../config.php';

if (!is_admin_logged_in()) {
    redirect('login.php');
}

$message = '';
$message_type = '';
$quiz_id = isset($_GET['quiz_id']) ? (int)$_GET['quiz_id'] : 0;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $quiz_id = (int)$_POST['quiz_id'];
                $question_text = sanitize_input($_POST['question_text']);
                $option_a = sanitize_input($_POST['option_a']);
                $option_b = sanitize_input($_POST['option_b']);
                $option_c = sanitize_input($_POST['option_c']);
                $option_d = sanitize_input($_POST['option_d']);
                $correct_option = sanitize_input($_POST['correct_option']);
                $explanation = sanitize_input($_POST['explanation']);
                $question_order = (int)$_POST['question_order'];
                
                if (!empty($question_text) && !empty($option_a) && !empty($option_b) && !empty($option_c) && !empty($option_d) && !empty($correct_option)) {
                    $insert_query = "INSERT INTO questions (quiz_id, question_text, option_a, option_b, option_c, option_d, correct_option, explanation, question_order) 
                                   VALUES ($quiz_id, '$question_text', '$option_a', '$option_b', '$option_c', '$option_d', '$correct_option', '$explanation', $question_order)";
                    
                    if (mysqli_query($conn, $insert_query)) {
                        // Update quiz total_questions count
                        mysqli_query($conn, "UPDATE quizzes SET total_questions = (SELECT COUNT(*) FROM questions WHERE quiz_id = $quiz_id) WHERE quiz_id = $quiz_id");
                        $message = 'Question added successfully!';
                        $message_type = 'success';
                    } else {
                        $message = 'Error adding question: ' . mysqli_error($conn);
                        $message_type = 'danger';
                    }
                } else {
                    $message = 'Please fill in all required fields.';
                    $message_type = 'warning';
                }
                break;
                
            case 'edit':
                $question_id = (int)$_POST['question_id'];
                $question_text = sanitize_input($_POST['question_text']);
                $option_a = sanitize_input($_POST['option_a']);
                $option_b = sanitize_input($_POST['option_b']);
                $option_c = sanitize_input($_POST['option_c']);
                $option_d = sanitize_input($_POST['option_d']);
                $correct_option = sanitize_input($_POST['correct_option']);
                $explanation = sanitize_input($_POST['explanation']);
                $question_order = (int)$_POST['question_order'];
                
                if (!empty($question_text) && $question_id > 0) {
                    $update_query = "UPDATE questions SET 
                                   question_text = '$question_text', 
                                   option_a = '$option_a', 
                                   option_b = '$option_b', 
                                   option_c = '$option_c', 
                                   option_d = '$option_d', 
                                   correct_option = '$correct_option', 
                                   explanation = '$explanation',
                                   question_order = $question_order
                                   WHERE question_id = $question_id";
                    
                    if (mysqli_query($conn, $update_query)) {
                        $message = 'Question updated successfully!';
                        $message_type = 'success';
                    } else {
                        $message = 'Error updating question: ' . mysqli_error($conn);
                        $message_type = 'danger';
                    }
                }
                break;
                
            case 'delete':
                $question_id = (int)$_POST['question_id'];
                $quiz_id = (int)$_POST['quiz_id'];
                if ($question_id > 0) {
                    mysqli_query($conn, "DELETE FROM attempts WHERE question_id = $question_id");
                    $delete_query = "DELETE FROM questions WHERE question_id = $question_id";
                    if (mysqli_query($conn, $delete_query)) {
                        // Update quiz total_questions count
                        mysqli_query($conn, "UPDATE quizzes SET total_questions = (SELECT COUNT(*) FROM questions WHERE quiz_id = $quiz_id) WHERE quiz_id = $quiz_id");
                        $message = 'Question deleted successfully!';
                        $message_type = 'success';
                    } else {
                        $message = 'Error deleting question: ' . mysqli_error($conn);
                        $message_type = 'danger';
                    }
                }
                break;
        }
    }
}

// Get quiz information
$quiz_info = null;
if ($quiz_id > 0) {
    $quiz_query = "SELECT q.*, c.category_name FROM quizzes q LEFT JOIN categories c ON q.category_id = c.category_id WHERE q.quiz_id = $quiz_id";
    $quiz_result = mysqli_query($conn, $quiz_query);
    if (mysqli_num_rows($quiz_result) > 0) {
        $quiz_info = mysqli_fetch_assoc($quiz_result);
    }
}

// Get all quizzes for dropdown
$quizzes_query = "SELECT quiz_id, title FROM quizzes ORDER BY title";
$quizzes_result = mysqli_query($conn, $quizzes_query);

// Get questions for selected quiz
$questions_result = null;
if ($quiz_id > 0) {
    $questions_query = "SELECT * FROM questions WHERE quiz_id = $quiz_id ORDER BY question_order, question_id";
    $questions_result = mysqli_query($conn, $questions_query);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Questions - <?php echo SITE_NAME; ?></title>
    
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
                            <a class="nav-link active" href="manage_questions.php">
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
                    <h1 class="h2"><i class="fas fa-question-circle me-2"></i>Manage Questions</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <?php if ($quiz_id > 0): ?>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addQuestionModal">
                                <i class="fas fa-plus me-1"></i>Add Question
                            </button>
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

                <!-- Quiz Selection -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3 align-items-end">
                            <div class="col-md-8">
                                <label for="quiz_id" class="form-label">Select Quiz</label>
                                <select class="form-select" id="quiz_id" name="quiz_id" onchange="this.form.submit()">
                                    <option value="">Choose a quiz to manage questions...</option>
                                    <?php while ($quiz = mysqli_fetch_assoc($quizzes_result)): ?>
                                        <option value="<?php echo $quiz['quiz_id']; ?>" <?php echo $quiz_id == $quiz['quiz_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($quiz['title']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-1"></i>Load Questions
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <?php if ($quiz_info): ?>
                    <!-- Quiz Info -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Quiz: <?php echo htmlspecialchars($quiz_info['title']); ?></h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Category:</strong> <?php echo htmlspecialchars($quiz_info['category_name']); ?></p>
                                    <p><strong>Description:</strong> <?php echo htmlspecialchars($quiz_info['description']); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Total Questions:</strong> <?php echo $quiz_info['total_questions']; ?></p>
                                    <p><strong>Time Limit:</strong> <?php echo $quiz_info['time_limit']; ?> minutes</p>
                                    <p><strong>Difficulty:</strong> <?php echo $quiz_info['difficulty_level']; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Questions List -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Questions (<?php echo mysqli_num_rows($questions_result); ?>)</h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if (mysqli_num_rows($questions_result) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-dark">
                                            <tr>
                                                <th width="5%">Order</th>
                                                <th width="50%">Question</th>
                                                <th width="15%">Correct Answer</th>
                                                <th width="20%">Options</th>
                                                <th width="10%">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($question = mysqli_fetch_assoc($questions_result)): ?>
                                                <tr>
                                                    <td><?php echo $question['question_order']; ?></td>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($question['question_text']); ?></strong>
                                                        <?php if ($question['explanation']): ?>
                                                            <br><small class="text-muted">Explanation: <?php echo htmlspecialchars($question['explanation']); ?></small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-success"><?php echo $question['correct_option']; ?></span>
                                                    </td>
                                                    <td>
                                                        <small>
                                                            <strong>A:</strong> <?php echo htmlspecialchars(substr($question['option_a'], 0, 30)); ?>...<br>
                                                            <strong>B:</strong> <?php echo htmlspecialchars(substr($question['option_b'], 0, 30)); ?>...<br>
                                                            <strong>C:</strong> <?php echo htmlspecialchars(substr($question['option_c'], 0, 30)); ?>...<br>
                                                            <strong>D:</strong> <?php echo htmlspecialchars(substr($question['option_d'], 0, 30)); ?>...
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group btn-group-sm">
                                                            <button type="button" class="btn btn-outline-success" 
                                                                    onclick="editQuestion(<?php echo htmlspecialchars(json_encode($question)); ?>)" 
                                                                    title="Edit Question">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-outline-danger" 
                                                                    onclick="deleteQuestion(<?php echo $question['question_id']; ?>, <?php echo $quiz_id; ?>)" 
                                                                    title="Delete Question">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-question-circle fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">No Questions Added Yet</h5>
                                    <p class="text-muted">Start by adding your first question to this quiz.</p>
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addQuestionModal">
                                        <i class="fas fa-plus me-1"></i>Add First Question
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Select a Quiz</h5>
                        <p class="text-muted">Choose a quiz from the dropdown above to manage its questions.</p>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <!-- Add Question Modal -->
    <div class="modal fade" id="addQuestionModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>Add New Question
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="quiz_id" value="<?php echo $quiz_id; ?>">
                        
                        <div class="mb-3">
                            <label for="question_text" class="form-label">Question Text *</label>
                            <textarea class="form-control" id="question_text" name="question_text" rows="3" required></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="option_a" class="form-label">Option A *</label>
                                <input type="text" class="form-control" id="option_a" name="option_a" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="option_b" class="form-label">Option B *</label>
                                <input type="text" class="form-control" id="option_b" name="option_b" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="option_c" class="form-label">Option C *</label>
                                <input type="text" class="form-control" id="option_c" name="option_c" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="option_d" class="form-label">Option D *</label>
                                <input type="text" class="form-control" id="option_d" name="option_d" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="correct_option" class="form-label">Correct Answer *</label>
                                <select class="form-select" id="correct_option" name="correct_option" required>
                                    <option value="">Select Correct Option</option>
                                    <option value="A">A</option>
                                    <option value="B">B</option>
                                    <option value="C">C</option>
                                    <option value="D">D</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="question_order" class="form-label">Question Order</label>
                                <input type="number" class="form-control" id="question_order" name="question_order" value="1" min="1">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="explanation" class="form-label">Explanation (Optional)</label>
                            <textarea class="form-control" id="explanation" name="explanation" rows="2" placeholder="Provide an explanation for the correct answer..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Add Question
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Question Modal -->
    <div class="modal fade" id="editQuestionModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Edit Question
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="question_id" id="edit_question_id">
                        
                        <div class="mb-3">
                            <label for="edit_question_text" class="form-label">Question Text *</label>
                            <textarea class="form-control" id="edit_question_text" name="question_text" rows="3" required></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_option_a" class="form-label">Option A *</label>
                                <input type="text" class="form-control" id="edit_option_a" name="option_a" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_option_b" class="form-label">Option B *</label>
                                <input type="text" class="form-control" id="edit_option_b" name="option_b" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_option_c" class="form-label">Option C *</label>
                                <input type="text" class="form-control" id="edit_option_c" name="option_c" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_option_d" class="form-label">Option D *</label>
                                <input type="text" class="form-control" id="edit_option_d" name="option_d" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_correct_option" class="form-label">Correct Answer *</label>
                                <select class="form-select" id="edit_correct_option" name="correct_option" required>
                                    <option value="A">A</option>
                                    <option value="B">B</option>
                                    <option value="C">C</option>
                                    <option value="D">D</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_question_order" class="form-label">Question Order</label>
                                <input type="number" class="form-control" id="edit_question_order" name="question_order" min="1">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_explanation" class="form-label">Explanation (Optional)</label>
                            <textarea class="form-control" id="edit_explanation" name="explanation" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save me-1"></i>Update Question
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Question Modal -->
    <div class="modal fade" id="deleteQuestionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle me-2"></i>Confirm Delete
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this question?</p>
                    <div class="alert alert-warning">
                        <i class="fas fa-warning me-2"></i>
                        This will also delete any user answers associated with this question. This action cannot be undone.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="question_id" id="delete_question_id">
                        <input type="hidden" name="quiz_id" id="delete_quiz_id">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash me-1"></i>Delete Question
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function editQuestion(question) {
            document.getElementById('edit_question_id').value = question.question_id;
            document.getElementById('edit_question_text').value = question.question_text;
            document.getElementById('edit_option_a').value = question.option_a;
            document.getElementById('edit_option_b').value = question.option_b;
            document.getElementById('edit_option_c').value = question.option_c;
            document.getElementById('edit_option_d').value = question.option_d;
            document.getElementById('edit_correct_option').value = question.correct_option;
            document.getElementById('edit_question_order').value = question.question_order;
            document.getElementById('edit_explanation').value = question.explanation || '';
            
            const modal = new bootstrap.Modal(document.getElementById('editQuestionModal'));
            modal.show();
        }
        
        function deleteQuestion(questionId, quizId) {
            document.getElementById('delete_question_id').value = questionId;
            document.getElementById('delete_quiz_id').value = quizId;
            
            const modal = new bootstrap.Modal(document.getElementById('deleteQuestionModal'));
            modal.show();
        }
    </script>
</body>
</html>
