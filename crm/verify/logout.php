<?php
session_start();
unset($_SESSION['upload_authenticated'], $_SESSION['username'], $_SESSION['user_role']);
session_destroy();
header('Location: index.php');
exit;
