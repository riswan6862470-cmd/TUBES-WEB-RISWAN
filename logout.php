<?php
session_start();
session_destroy();
header('Location: /TUBES WEB RISWAN/login.php?logout=1');
exit();
?>
