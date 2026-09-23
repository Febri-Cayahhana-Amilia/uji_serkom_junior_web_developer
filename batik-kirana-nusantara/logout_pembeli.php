<?php
if (session_status() === PHP_SESSION_NONE) session_start();
unset($_SESSION['pembeli_id'], $_SESSION['pembeli_nama']);
header('Location: index.php');
exit;
