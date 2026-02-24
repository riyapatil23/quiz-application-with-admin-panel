<?php
require_once '../config.php';

// Check if admin is logged in
if (!is_admin_logged_in()) {
    redirect('login.php');
}

// Get statistics
$stats = [];

// Total users
$user_query = "SELECT COUNT(*) as count FROM users";
$user_result = mysqli_query($conn, $user_query);
$stats['total_users'] = mysqli_fetch_assoc($user_result)['count'];

// Total quizzes
$quiz_query = "SELECT COUNT(*) as count FROM quizzes";
$quiz_result = mysqli_query($conn, $quiz_query);
$stats['total_quizzes'] = mysqli_fetch_assoc($quiz_result)['count'];

// Total questions
$question_query = "SELECT COUNT(*) as count FROM questions";
$question_result = mysqli_query($conn, $question_query);
$stats['total_questions'] = mysqli_fetch_assoc($question_result)['count'];

// Total attempts
$attempt_query = "SELECT COUNT(*) as count FROM results";
$attempt_result = mysqli_query($conn, $attempt_query);
$stats['total_attempts'] = mysqli_fetch_assoc($attempt_result)['count'];

// Recent quiz attempts
$recent_attempts_query = "SELECT r.*, u.username, u.full_name, q.title 
                         FROM results r
                         JOIN users u ON r.user_id = u.user_id
                         JOIN quizzes q ON r.quiz_id = q.quiz_id
                         ORDER BY r.completed_at DESC
                         LIMIT 10";
$recent_attempts_result = mysqli_query($conn, $recent_attempts_query);

// Popular quizzes
$popular_quizzes_query = "SELECT q.title, COUNT(r.result_id) as attempt_count,
                         AVG(r.percentage) as avg_score
                         FROM quizzes q
                         LEFT JOIN results r ON q.quiz_id = r.quiz_id
                         GROUP BY q.quiz_id, q.title
                         ORDER BY attempt_count DESC
                         LIMIT 5";
$popular_quizzes_result = mysqli_query($conn, $popular_quizzes_query);

// Top performers
$top_performers_query = "SELECT u.username, u.full_name, 
                        COUNT(r.result_id) as total_attempts,
                        AVG(r.percentage) as avg_score
                        FROM users u
                        JOIN results r ON u.user_id = r.user_id
                        GROUP BY u.user_id
                        ORDER BY avg_score DESC
                        LIMIT 5";
$top_performers_result = mysqli_query($conn, $top_performers_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo SITE_NAME; ?></title>
    
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
                            <a class="nav-link active" href="dashboard.php">
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
                    <h1 class="h2"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-download me-1"></i>Export
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Total Users
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $stats['total_users']; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-users fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Total Quizzes
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $stats['total_quizzes']; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Total Questions
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $stats['total_questions']; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-question-circle fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Total Attempts
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $stats['total_attempts']; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Recent Quiz Attempts -->
                    <div class="col-lg-8 mb-4">
                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-clock me-2"></i>Recent Quiz Attempts
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered" width="100%" cellspacing="0">
                                        <thead>
                                            <tr>
                                                <th>User</th>
                                                <th>Quiz</th>
                                                <th>Score</th>
                                                <th>Percentage</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($attempt = mysqli_fetch_assoc($recent_attempts_result)): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($attempt['full_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($attempt['title']); ?></td>
                                                    <td><?php echo $attempt['score']; ?>/<?php echo $attempt['total_questions']; ?></td>
                                                    <td>
                                                        <span class="badge <?php 
                                                            echo $attempt['percentage'] >= 80 ? 'bg-success' : 
                                                                ($attempt['percentage'] >= 60 ? 'bg-warning' : 'bg-danger'); 
                                                        ?>">
                                                            <?php echo number_format($attempt['percentage'], 1); ?>%
                                                        </span>
                                                    </td>
                                                    <td><?php echo date('M j, Y H:i', strtotime($attempt['completed_at'])); ?></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions & Stats -->
                    <div class="col-lg-4">
                        <!-- Quick Actions -->
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-bolt me-2"></i>Quick Actions
                                </h6>
                            </div>
                            <div class="card-body">
                                <a href="manage_quizzes.php?action=add" class="btn btn-primary btn-sm w-100 mb-2">
                                    <i class="fas fa-plus me-1"></i>Add New Quiz
                                </a>
                                <a href="manage_questions.php" class="btn btn-success btn-sm w-100 mb-2">
                                    <i class="fas fa-question me-1"></i>Add Questions
                                </a>
                                <a href="view_results.php" class="btn btn-info btn-sm w-100 mb-2">
                                    <i class="fas fa-chart-bar me-1"></i>View All Results
                                </a>
                                <a href="manage_users.php" class="btn btn-warning btn-sm w-100">
                                    <i class="fas fa-users me-1"></i>Manage Users
                                </a>
                            </div>
                        </div>

                        <!-- Popular Quizzes -->
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-fire me-2"></i>Popular Quizzes
                                </h6>
                            </div>
                            <div class="card-body">
                                <?php while ($quiz = mysqli_fetch_assoc($popular_quizzes_result)): ?>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div>
                                            <h6 class="mb-0"><?php echo htmlspecialchars($quiz['title']); ?></h6>
                                            <small class="text-muted">
                                                <?php echo $quiz['attempt_count']; ?> attempts
                                            </small>
                                        </div>
                                        <span class="badge bg-primary">
                                            <?php echo number_format($quiz['avg_score'], 1); ?>%
                                        </span>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
