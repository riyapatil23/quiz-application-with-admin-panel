<?php
require_once '../config.php';

// Destroy admin session and redirect to admin login
unset($_SESSION['admin_id']);
unset($_SESSION['admin_username']);
redirect('login.php');
?>
