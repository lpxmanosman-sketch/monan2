<?php
/**
 * Admin Logout - MANON
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

session_destroy();
header('Location: ' . BASE_URL . 'admin/login.php');
exit;
