<?php
require_once __DIR__ . '/includes/config.php';
unset($_SESSION['admin_id'], $_SESSION['admin_name']);
session_destroy();
header("Location: login.php");
exit;
?>