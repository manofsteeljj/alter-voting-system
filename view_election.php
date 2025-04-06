<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Include database connection
include 'db_connect.php';

// Fetch user data
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT username, role FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Get election details
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: elections.php');
    exit();
}

$election_id = $_GET['id'];
$election_stmt = $conn->prepare("SELECT * FROM elections WHERE id = ?");
$election_stmt->bind_param("i", $election_id);
$election_stmt->execute();
$election = $election_stmt->get_result()->fetch_assoc();

if (!$election) {
    header('Location: elections.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Election - VoteSecure</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background-color: #f5f5f7;
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar styles */
        .sidebar {
            width: 220px;
            background-color: white;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            padding: 15px;
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
        }

        .logo-container {
            display: flex;
            align-items: center;
            padding: 10px 0 20px 0;
        }

        .logo {
            background-color: #0066ff;
            color: white;
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-right: 10px;
        }

        .logo-text {
            font-weight: bold;
            font-size: 18px;
            color: #333;
        }

        .nav-menu {
            margin-top: 20px;
            flex-grow: 1;
        }

        .nav-item {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            margin-bottom: 5px;
            border-radius: 8px;
            cursor: pointer;
            color: #555;
            transition: all 0.3s ease;
        }

        .nav-item:hover {
            background-color: #f0f5ff;
            color: #0066ff;
        }

        .nav-item.active {
            background-color: #0066ff;
            color: white;
        }

        .nav-item i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        .user-profile {
            display: flex;
            align-items: center;
            padding: 15px 10px;
            border-top: 1px solid #eee;
            margin-top: auto;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #ddd;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-right: 10px;
            overflow: hidden;
        }

        .user-info {
            flex-grow: 1;
        }

        .user-name {
            font-weight: bold;
            font-size: 14px;
        }

        .user-role {
            color: #777;
            font-size: 12px;
        }

        /* Main content styles */
        .main-content {
            flex: 1;
            margin-left: 220px;
            padding: 20px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0 20px 0;
        }

        .page-title {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }

        .election-details {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .election-details h2 {
            font-size: 22px;
            font-weight: bold;
            color: #333;
            margin-bottom: 15px;
        }

        .election-details p {
            font-size: 16px;
            color: #555;
            margin-bottom: 10px;
        }

        .form-actions {
            margin-top: 20px;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            cursor: pointer;
            text-decoration: none;
            color: white;
            background-color: #0066ff;
            border: none;
            transition: background-color 0.3s ease;
        }

        .btn:hover {
            background-color: #0056d1;
        }

        .btn i {
            margin-right: 5px;
        }

        .btn-secondary {
            background-color: #555;
        }

        .btn-secondary:hover {
            background-color: #444;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo-container">
            <div class="logo">
                <i class="fas fa-vote-yea"></i>
            </div>
            <div class="logo-text">VoteSecure</div>
        </div>

        <div class="nav-menu">
            <a href="dashboard.php" class="nav-item">
                <i class="fas fa-th-large"></i>
                <span class="nav-text">Dashboard</span>
            </a>
            <a href="elections.php" class="nav-item active">
                <i class="fas fa-poll"></i>
                <span class="nav-text">Elections</span>
            </a>
            <a href="create_election.php" class="nav-item">
                <i class="fas fa-plus-circle"></i>
                <span class="nav-text">Create Election</span>
            </a>
        </div>

        <div class="user-profile">
            <div class="user-avatar">
                <?php
                if ($user) {
                    echo '<div style="width:100%;height:100%;display:flex;justify-content:center;align-items:center;background-color:#f0f5ff;color:#0066ff;font-weight:bold;">' . substr($user['username'], 0, 1) . '</div>';
                } else {
                    echo '<i class="fas fa-user"></i>';
                }
                ?>
            </div>
            <div class="user-info">
                <div class="user-name"><?php echo $user ? $user['username'] : 'User'; ?></div>
                <div class="user-role"><?php echo $user ? ucfirst($user['role']) : 'Role'; ?></div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <div class="page-title">View Election</div>
        </div>

        <div class="election-details">
            <h2><?php echo htmlspecialchars($election['title']); ?></h2>
            <p><strong>Description:</strong> <?php echo htmlspecialchars($election['description']); ?></p>
            <p><strong>Start Date:</strong> <?php echo date('M d, Y H:i', strtotime($election['start_date'])); ?></p>
            <p><strong>End Date:</strong> <?php echo date('M d, Y H:i', strtotime($election['end_date'])); ?></p>
            <p><strong>Status:</strong> <?php echo ucfirst($election['status']); ?></p>
        </div>

        <div class="form-actions">
            <a href="elections.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Elections
            </a>
        </div>
    </div>
</body>
</html>