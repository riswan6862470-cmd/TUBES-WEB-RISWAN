<?php
session_start();
if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard_admin.php');
    exit();
}
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard_user.php');
    exit();
}
header('Location: login.php');
exit();
?>
