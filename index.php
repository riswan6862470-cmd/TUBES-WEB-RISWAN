<?php
session_start();
if (isset($_SESSION['admin_id'])) {
    header('Location: /TUBES WEB RISWAN/dashboard_admin.php');
    exit();
}
if (isset($_SESSION['user_id'])) {
    header('Location: /TUBES WEB RISWAN/dashboard_user.php');
    exit();
}
header('Location: /TUBES WEB RISWAN/login.php');
exit();
?>
