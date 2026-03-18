<?php
session_start();
require_once 'include/db_connect.php';

// Check if logged in
if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

// Handle suggestion submission
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $suggestion = trim($_POST['']);}