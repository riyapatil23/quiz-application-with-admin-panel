<?php
require_once '../config.php';

if (!is_admin_logged_in()) {
    redirect('login.php');
}

$message = '';
$message_type = '';

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'delete_user':
                $user_id = (int)$_POST['user_id'];
                if ($user_id > 0) {
                    // Delete user and all related data
                    mysqli_query($conn, "DELETE FROM attempts WHERE result_id IN (SELECT result_id FROM results WHERE user_id = $user_id)");
                    mysqli_query($conn, "DELETE FROM results WHERE user_id = $user_id");
                    $delete_query = "DELETE FROM users WHERE user_id = $user_id";
                    
                    if (mysqli_query($conn, $delete_query)) {
                        $message = 'User deleted successfully!';
                        $message_type = 'success';
                    } else {
                        $message = 'Error deleting user: ' . mysqli_error($conn);
                        $message_type = 'danger';
                    }
                }
                break;
                
            case 'reset_password':
                $user_id = (int)$_POST['user_id'];
                $new_password = 'password123'; // Default password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                if ($user_id > 0) {
                    $update_query = "UPDATE users SET password = '$hashed_password' WHERE user_id = $user_id";
                    if (mysqli_query($conn, $update_query)) {
                        $message = 'Password reset successfully! New password: ' . $new_password;
                        $message_type = 'success';
                    } else {
                        $message = 'Error resetting password: ' . mysqli_error($conn);
                        $message_type = 'danger';
                    }
                }
                break;
        }
    }
}

// Get search and filter parameters
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'created_at_desc';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 15;
$offset = ($page - 1) * $per_page;

// Build WHERE clause for search
$where_clause = '';
if (!empty($search)) {
    $where_clause = "WHERE u.username LIKE '%$search%' OR u.full_name LIKE '%$search%' OR u.email LIKE '%$search%'";
}

// Build ORDER BY clause
$order_clause = '';
switch ($sort_by) {
    case 'name_asc':
        $order_clause = 'ORDER BY u.full_name ASC';
        break;
    case 'name_desc':
        $order_clause = 'ORDER BY u.full_name DESC';
        break;
    case 'username_asc':
        $order_clause = 'ORDER BY u.username ASC';
        break;
    case 'username_desc':
        $order_clause = 'ORDER BY u.username DESC';
        break;
    case 'created_at_asc':
        $order_clause = 'ORDER BY u.created_at ASC';
        break;
    case 'created_at_desc':
        $order_clause = 'ORDER BY u.created_at DESC';
        break;
    case 'quiz_count_desc':
        $order_clause = 'ORDER BY quiz_attempts DESC';
        break;
    case 'avg_score_desc':
        $order_clause = 'ORDER BY avg_score DESC';
        break;
    default:
        $order_clause = 'ORDER BY u.created_at DESC';
}

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM users u $where_clause";
$count_result = mysqli_query($conn, $count_query);
$total_users = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_users / $per_page);

// Get users with statistics
$users_query = "SELECT u.*, 
                COUNT(r.result_id) as quiz_attempts,
                COALESCE(AVG(r.percentage), 0) as avg_score,
                COALESCE(MAX(r.percentage), 0) as best_score,
                COUNT(DISTINCT r.quiz_id) as unique_quizzes
                FROM users u
                LEFT JOIN results r ON u.user_id = r.user_id
                $where_clause
                GROUP BY u.user_id
                $order_clause
                LIMIT $per_page OFFSET $offset";
$users_result = mysqli_query($conn, $users_query);

// Get overall statistics
$stats_query = "SELECT 
                COUNT(*) as total_users,
                COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as new_users_30d,
                COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as new_users_7d
                FROM users";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - <?php echo SITE_NAME; ?></title>
    
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
                            <a class="nav-link" href="view_results.php">
                                <i class="fas fa-chart-bar me-2"></i>View Results
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="manage_users.php">
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
                    <h1 class="h2"><i class="fas fa-users me-2"></i>Manage Users</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-outline-secondary" onclick="exportUsers()">
                            <i class="fas fa-download me-1"></i>Export CSV
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

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-lg-4 col-md-6 mb-3">
                        <div class="card border-left-primary">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Users</div>
                                        <div class="h5 mb-0 font-weight-bold"><?php echo number_format($stats['total_users']); ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-users fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 mb-3">
                        <div class="card border-left-success">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">New Users (30 days)</div>
                                        <div class="h5 mb-0 font-weight-bold"><?php echo number_format($stats['new_users_30d']); ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-user-plus fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 mb-3">
                        <div class="card border-left-info">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">New Users (7 days)</div>
                                        <div class="h5 mb-0 font-weight-bold"><?php echo number_format($stats['new_users_7d']); ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-user-clock fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Search and Filter -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label for="search" class="form-label">Search Users</label>
                                <input type="text" class="form-control" id="search" name="search" 
                                       value="<?php echo htmlspecialchars($search); ?>" 
                                       placeholder="Name, username, or email...">
                            </div>
                            <div class="col-md-3">
                                <label for="sort" class="form-label">Sort By</label>
                                <select class="form-select" id="sort" name="sort">
                                    <option value="created_at_desc" <?php echo $sort_by == 'created_at_desc' ? 'selected' : ''; ?>>Newest First</option>
                                    <option value="created_at_asc" <?php echo $sort_by == 'created_at_asc' ? 'selected' : ''; ?>>Oldest First</option>
                                    <option value="name_asc" <?php echo $sort_by == 'name_asc' ? 'selected' : ''; ?>>Name A-Z</option>
                                    <option value="name_desc" <?php echo $sort_by == 'name_desc' ? 'selected' : ''; ?>>Name Z-A</option>
                                    <option value="username_asc" <?php echo $sort_by == 'username_asc' ? 'selected' : ''; ?>>Username A-Z</option>
                                    <option value="quiz_count_desc" <?php echo $sort_by == 'quiz_count_desc' ? 'selected' : ''; ?>>Most Active</option>
                                    <option value="avg_score_desc" <?php echo $sort_by == 'avg_score_desc' ? 'selected' : ''; ?>>Highest Score</option>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="fas fa-search me-1"></i>Search
                                </button>
                                <a href="manage_users.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-1"></i>Clear
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Users Table -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Users (<?php echo number_format($total_users); ?> total)</h6>
                    </div>
                    <div class="card-body p-0">
                        <?php if (mysqli_num_rows($users_result) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>ID</th>
                                            <th>User</th>
                                            <th>Email</th>
                                            <th>Quiz Attempts</th>
                                            <th>Avg Score</th>
                                            <th>Best Score</th>
                                            <th>Joined</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($user = mysqli_fetch_assoc($users_result)): ?>
                                            <tr>
                                                <td><?php echo $user['user_id']; ?></td>
                                                <td>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($user['full_name']); ?></strong><br>
                                                        <small class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></small>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                <td>
                                                    <span class="badge bg-info"><?php echo $user['quiz_attempts']; ?></span>
                                                    <?php if ($user['unique_quizzes'] > 0): ?>
                                                        <br><small class="text-muted"><?php echo $user['unique_quizzes']; ?> unique</small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($user['quiz_attempts'] > 0): ?>
                                                        <span class="badge <?php 
                                                            echo $user['avg_score'] >= 80 ? 'bg-success' : 
                                                                ($user['avg_score'] >= 60 ? 'bg-warning' : 'bg-danger'); 
                                                        ?>">
                                                            <?php echo number_format($user['avg_score'], 1); ?>%
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($user['quiz_attempts'] > 0): ?>
                                                        <span class="badge bg-primary">
                                                            <?php echo number_format($user['best_score'], 1); ?>%
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                                                    <?php if (strtotime($user['created_at']) > strtotime('-7 days')): ?>
                                                        <br><span class="badge bg-success">New</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="view_results.php?user_id=<?php echo $user['user_id']; ?>" 
                                                           class="btn btn-outline-primary" title="View Results">
                                                            <i class="fas fa-chart-bar"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-outline-warning" 
                                                                onclick="resetPassword(<?php echo $user['user_id']; ?>, '<?php echo htmlspecialchars($user['full_name']); ?>')" 
                                                                title="Reset Password">
                                                            <i class="fas fa-key"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-outline-danger" 
                                                                onclick="deleteUser(<?php echo $user['user_id']; ?>, '<?php echo htmlspecialchars($user['full_name']); ?>', <?php echo $user['quiz_attempts']; ?>)" 
                                                                title="Delete User">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <?php if ($total_pages > 1): ?>
                                <div class="card-footer">
                                    <nav aria-label="Users pagination">
                                        <ul class="pagination justify-content-center mb-0">
                                            <?php if ($page > 1): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo $sort_by; ?>">
                                                        <i class="fas fa-chevron-left"></i>
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                            
                                            <?php for ($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?>
                                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo $sort_by; ?>">
                                                        <?php echo $i; ?>
                                                    </a>
                                                </li>
                                            <?php endfor; ?>
                                            
                                            <?php if ($page < $total_pages): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo $sort_by; ?>">
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
                                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No Users Found</h5>
                                <p class="text-muted">No users match your search criteria.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Reset Password Modal -->
    <div class="modal fade" id="resetPasswordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-key me-2"></i>Reset Password
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Reset password for user "<strong id="resetUserName"></strong>"?</p>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        The password will be reset to: <strong>password123</strong>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="reset_password">
                        <input type="hidden" name="user_id" id="resetUserId">
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-key me-1"></i>Reset Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete User Modal -->
    <div class="modal fade" id="deleteUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle me-2"></i>Confirm Delete
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete user "<strong id="deleteUserName"></strong>"?</p>
                    <div class="alert alert-danger">
                        <i class="fas fa-warning me-2"></i>
                        This will permanently delete the user and all their quiz results (<span id="deleteQuizCount"></span> attempts). This action cannot be undone.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="delete_user">
                        <input type="hidden" name="user_id" id="deleteUserId">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash me-1"></i>Delete User
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function resetPassword(userId, userName) {
            document.getElementById('resetUserId').value = userId;
            document.getElementById('resetUserName').textContent = userName;
            
            const modal = new bootstrap.Modal(document.getElementById('resetPasswordModal'));
            modal.show();
        }
        
        function deleteUser(userId, userName, quizCount) {
            document.getElementById('deleteUserId').value = userId;
            document.getElementById('deleteUserName').textContent = userName;
            document.getElementById('deleteQuizCount').textContent = quizCount;
            
            const modal = new bootstrap.Modal(document.getElementById('deleteUserModal'));
            modal.show();
        }
        
        function exportUsers() {
            const params = new URLSearchParams(window.location.search);
            params.set('export', 'csv');
            
            const link = document.createElement('a');
            link.href = 'export_users.php?' + params.toString();
            link.download = 'users.csv';
            link.click();
        }
    </script>
</body>
</html>
