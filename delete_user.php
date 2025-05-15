<?php
session_start();
include 'db_connect.php';

// Check if user is logged in and is admin (or your desired role)
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$current_user = $result->fetch_assoc();

// Only allow admin to delete users (change as needed)
if (!$current_user || $current_user['role'] !== 'voter') {
    header('Location: users.php?error=unauthorized');
    exit();
}

// Get user to delete
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $delete_id = intval($_GET['id']);

    // Prevent admin from deleting themselves
    if ($delete_id == $user_id) {
        header('Location: users.php?error=cannot_delete_self');
        exit();
    }

    // Delete user
    $del_stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $del_stmt->bind_param("i", $delete_id);
    if ($del_stmt->execute()) {
        header('Location: users.php?msg=deleted');
        exit();
    } else {
        header('Location: users.php?error=delete_failed');
        exit();
    }
} else {
    header('Location: users.php?error=invalid_id');
    exit();
}
?>