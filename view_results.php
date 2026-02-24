<?php
require_once '../config.php';

if (!is_admin_logged_in()) {
    redirect('login.php');
}

// Get filter parameters
$quiz_filter = isset($_GET['quiz_id']) ? (int)$_GET['quiz_id'] : 0;
$user_filter = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build WHERE clause
$where_conditions = [];
if ($quiz_filter > 0) {
    $where_conditions[] = "r.quiz_id = $quiz_filter";
}
if ($user_filter > 0) {
    $where_conditions[] = "r.user_id = $user_filter";
}
if (!empty($date_from)) {
    $where_conditions[] = "DATE(r.completed_at) >= '$date_from'";
}
if (!empty($date_to)) {
    $where_conditions[] = "DATE(r.completed_at) <= '$date_to'";
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM results r $where_clause";
$count_result = mysqli_query($conn, $count_query);
$total_results = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_results / $per_page);

// Get results with filters
$results_query = "SELECT r.*, u.username, u.full_name, q.title as quiz_title, c.category_name
                  FROM results r
                  JOIN users u ON r.user_id = u.user_id
                  JOIN quizzes q ON r.quiz_id = q.quiz_id
                  LEFT JOIN categories c ON q.category_id = c.category_id
                  $where_clause
                  ORDER BY r.completed_at DESC
                  LIMIT $per_page OFFSET $offset";
$results_result = mysqli_query($conn, $results_query);

// Get quizzes for filter dropdown
$quizzes_query = "SELECT quiz_id, title FROM quizzes ORDER BY title";
$quizzes_result = mysqli_query($conn, $quizzes_query);

// Get users for filter dropdown
$users_query = "SELECT user_id, username, full_name FROM users ORDER BY full_name";
$users_result = mysqli_query($conn, $users_query);

// Get statistics
$stats_query = "SELECT 
                COUNT(*) as total_attempts,
                AVG(percentage) as avg_score,
                MAX(percentage) as highest_score,
                MIN(percentage) as lowest_score,
                COUNT(DISTINCT user_id) as unique_users,
                COUNT(DISTINCT quiz_id) as quizzes_attempted
                FROM results r $where_clause";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Results - <?php echo SITE_NAME; ?></title>
    
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
                            <a class="nav-link" href="manage_categories.php">
                                <i class="fas fa-tags me-2"></i>Categories
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="view_results.php">
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
                    <h1 class="h2"><i class="fas fa-chart-bar me-2"></i>Quiz Results</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-outline-secondary" onclick="exportResults()">
                            <i class="fas fa-download me-1"></i>Export CSV
                        </button>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <div class="card text-center border-left-primary">
                            <div class="card-body">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Attempts</div>
                                <div class="h5 mb-0 font-weight-bold"><?php echo number_format($stats['total_attempts']); ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <div class="card text-center border-left-success">
                            <div class="card-body">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Average Score</div>
                                <div class="h5 mb-0 font-weight-bold"><?php echo number_format($stats['avg_score'], 1); ?>%</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <div class="card text-center border-left-info">
                            <div class="card-body">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Highest Score</div>
                                <div class="h5 mb-0 font-weight-bold"><?php echo number_format($stats['highest_score'], 1); ?>%</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <div class="card text-center border-left-warning">
                            <div class="card-body">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Lowest Score</div>
                                <div class="h5 mb-0 font-weight-bold"><?php echo number_format($stats['lowest_score'], 1); ?>%</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <div class="card text-center border-left-secondary">
                            <div class="card-body">
                                <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">Unique Users</div>
                                <div class="h5 mb-0 font-weight-bold"><?php echo number_format($stats['unique_users']); ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <div class="card text-center border-left-dark">
                            <div class="card-body">
                                <div class="text-xs font-weight-bold text-dark text-uppercase mb-1">Quizzes Attempted</div>
                                <div class="h5 mb-0 font-weight-bold"><?php echo number_format($stats['quizzes_attempted']); ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-filter me-2"></i>Filters</h6>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label for="quiz_id" class="form-label">Quiz</label>
                                <select class="form-select" id="quiz_id" name="quiz_id">
                                    <option value="">All Quizzes</option>
                                    <?php while ($quiz = mysqli_fetch_assoc($quizzes_result)): ?>
                                        <option value="<?php echo $quiz['quiz_id']; ?>" <?php echo $quiz_filter == $quiz['quiz_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($quiz['title']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="user_id" class="form-label">User</label>
                                <select class="form-select" id="user_id" name="user_id">
                                    <option value="">All Users</option>
                                    <?php while ($user = mysqli_fetch_assoc($users_result)): ?>
                                        <option value="<?php echo $user['user_id']; ?>" <?php echo $user_filter == $user['user_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($user['full_name']); ?> (@<?php echo htmlspecialchars($user['username']); ?>)
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="date_from" class="form-label">From Date</label>
                                <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $date_from; ?>">
                            </div>
                            <div class="col-md-2">
                                <label for="date_to" class="form-label">To Date</label>
                                <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $date_to; ?>">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="fas fa-search me-1"></i>Filter
                                </button>
                                <a href="view_results.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times"></i>
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Results Table -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Results (<?php echo number_format($total_results); ?> total)</h6>
                    </div>
                    <div class="card-body p-0">
                        <?php if (mysqli_num_rows($results_result) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>ID</th>
                                            <th>User</th>
                                            <th>Quiz</th>
                                            <th>Category</th>
                                            <th>Score</th>
                                            <th>Percentage</th>
                                            <th>Time Taken</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($result = mysqli_fetch_assoc($results_result)): ?>
                                            <tr>
                                                <td><?php echo $result['result_id']; ?></td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($result['full_name']); ?></strong><br>
                                                    <small class="text-muted">@<?php echo htmlspecialchars($result['username']); ?></small>
                                                </td>
                                                <td><?php echo htmlspecialchars($result['quiz_title']); ?></td>
                                                <td>
                                                    <span class="badge bg-light text-dark">
                                                        <?php echo htmlspecialchars($result['category_name']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $result['score']; ?>/<?php echo $result['total_questions']; ?></td>
                                                <td>
                                                    <span class="badge <?php 
                                                        echo $result['percentage'] >= 80 ? 'bg-success' : 
                                                            ($result['percentage'] >= 60 ? 'bg-warning' : 'bg-danger'); 
                                                    ?>">
                                                        <?php echo number_format($result['percentage'], 1); ?>%
                                                    </span>
                                                </td>
                                                <td><?php echo formatTime($result['time_taken']); ?></td>
                                                <td>
                                                    <?php echo date('M j, Y H:i', strtotime($result['completed_at'])); ?>
                                                </td>
                                                <td>
                                                    <a href="view_detailed_result.php?id=<?php echo $result['result_id']; ?>" 
                                                       class="btn btn-outline-primary btn-sm" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <?php if ($total_pages > 1): ?>
                                <div class="card-footer">
                                    <nav aria-label="Results pagination">
                                        <ul class="pagination justify-content-center mb-0">
                                            <?php if ($page > 1): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?page=<?php echo $page-1; ?>&quiz_id=<?php echo $quiz_filter; ?>&user_id=<?php echo $user_filter; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>">
                                                        <i class="fas fa-chevron-left"></i>
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                            
                                            <?php for ($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?>
                                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                                    <a class="page-link" href="?page=<?php echo $i; ?>&quiz_id=<?php echo $quiz_filter; ?>&user_id=<?php echo $user_filter; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>">
                                                        <?php echo $i; ?>
                                                    </a>
                                                </li>
                                            <?php endfor; ?>
                                            
                                            <?php if ($page < $total_pages): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?page=<?php echo $page+1; ?>&quiz_id=<?php echo $quiz_filter; ?>&user_id=<?php echo $user_filter; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>">
                                                        <i class="fas fa-chevron-right"></i>
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                        </ul>
                                    </nav>
                                </div>
                            <?php endif; ?>

                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No Results Found</h5>
                                <p class="text-muted">No quiz results match your current filters.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function exportResults() {
            // Get current filter parameters
            const params = new URLSearchParams(window.location.search);
            params.set('export', 'csv');
            
            // Create download link
            const link = document.createElement('a');
            link.href = 'export_results.php?' + params.toString();
            link.download = 'quiz_results.csv';
            link.click();
        }
    </script>
</body>
</html>

<?php
function formatTime($seconds) {
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $secs = $seconds % 60;
    
    if ($hours > 0) {
        return sprintf('%dh %dm %ds', $hours, $minutes, $secs);
    } elseif ($minutes > 0) {
        return sprintf('%dm %ds', $minutes, $secs);
    } else {
        return sprintf('%ds', $secs);
    }
}
?>
