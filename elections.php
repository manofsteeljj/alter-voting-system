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

// Check if user has permission to manage elections
if ($user['role'] != 'voter' && $user['role'] != 'election_manager') {
    header('Location: dashboard.php');
    exit();
}

// Handle delete operation
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    $delete_stmt = $conn->prepare("DELETE FROM elections WHERE id = ?");
    $delete_stmt->bind_param("i", $delete_id);
    
    if ($delete_stmt->execute()) {
        // Record activity
        $activity_stmt = $conn->prepare("INSERT INTO activities (activity_type, description, user_id, election_id) VALUES ('delete', 'Election deleted', ?, ?)");
        $activity_stmt->bind_param("ii", $user_id, $delete_id);
        $activity_stmt->execute();
        
        // Redirect to prevent resubmission
        header('Location: elections.php?msg=deleted');
        exit();
    }
}

// Get all elections
$elections_query = $conn->query("SELECT * FROM elections ORDER BY start_date DESC");
$elections = [];
while ($row = $elections_query->fetch_assoc()) {
    $elections[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Elections Management - VoteSecure</title>
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
        
        .nav-item.expandable {
            display: flex;
            justify-content: space-between;
        }
        
        .nav-item.expandable .arrow {
            margin-left: 10px;
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
        
        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
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
        
        .dark-mode-toggle {
            margin-left: 10px;
            color: #777;
            cursor: pointer;
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
        
        .header-actions {
            display: flex;
            align-items: center;
        }
        
        .search-bar {
            background-color: white;
            border-radius: 20px;
            padding: 8px 15px;
            display: flex;
            align-items: center;
            margin-right: 15px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }
        
        .search-bar input {
            border: none;
            outline: none;
            margin-left: 5px;
            width: 200px;
        }
        
        .btn-primary {
            background-color: #0066ff;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
        }
        
        .btn-primary i {
            margin-right: 5px;
        }
        
        .btn-primary:hover {
            background-color: #0052cc;
        }
        
        /* Elections table */
        .elections-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }
        
        .elections-table th, .elections-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .elections-table th {
            background-color: #f9f9f9;
            font-weight: 600;
            color: #555;
        }
        
        .elections-table tr:last-child td {
            border-bottom: none;
        }
        
        .elections-table tr:hover {
            background-color: #f5f5f7;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-active {
            background-color: #e6f7eb;
            color: #00b894;
        }
        
        .status-upcoming {
            background-color: #fff8e6;
            color: #f39c12;
        }
        
        .status-completed {
            background-color: #e0ebff;
            color: #0066ff;
        }
        
        .status-canceled {
            background-color: #ffe6e6;
            color: #ff5252;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .btn-action {
            width: 32px;
            height: 32px;
            border-radius: 5px;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            color: white;
        }
        
        .btn-view {
            background-color: #0066ff;
        }
        
        .btn-edit {
            background-color: #00b894;
        }
        
        .btn-delete {
            background-color: #ff5252;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .alert-success {
            background-color: #e6f7eb;
            color: #00b894;
            border: 1px solid #00b894;
        }
        
        .alert-danger {
            background-color: #ffe6e6;
            color: #ff5252;
            border: 1px solid #ff5252;
        }
        
        /* Responsive styles */
        @media (max-width: 992px) {
            .sidebar {
                width: 80px;
            }
            
            .logo-text, .nav-text, .user-info {
                display: none;
            }
            
            .main-content {
                margin-left: 80px;
            }
            
            .nav-item {
                justify-content: center;
                padding: 12px 0;
            }
            
            .nav-item i {
                margin-right: 0;
            }
            
            .user-profile {
                justify-content: center;
                padding: 15px 0;
            }
            
            .user-avatar {
                margin-right: 0;
            }
            
            .search-bar {
                width: 150px;
            }
            
            .search-bar input {
                width: 100px;
            }
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
            
            <a href="users.php" class="nav-item">
                <i class="fas fa-users"></i>
                <span class="nav-text">User Management</span>
            </a>
            
            <a href="settings.php" class="nav-item">
                <i class="fas fa-cog"></i>
                <span class="nav-text">Settings</span>
            </a>
        </div>
        
        <div class="user-profile">
            <div class="user-avatar">
                <?php
                // Display first letter of username as avatar
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
            <div class="dark-mode-toggle">
                <i class="fas fa-moon"></i>
            </div>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <div class="page-title">Elections Management</div>
            <div class="header-actions">
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search elections...">
                </div>
                <a href="create_election.php" class="btn-primary">
                    <i class="fas fa-plus"></i> Create Election
                </a>
            </div>
        </div>
        
        <?php if (isset($_GET['msg'])) { ?>
            <?php if ($_GET['msg'] == 'created') { ?>
                <div class="alert alert-success">Election was created successfully.</div>
            <?php } else if ($_GET['msg'] == 'updated') { ?>
                <div class="alert alert-success">Election was updated successfully.</div>
            <?php } else if ($_GET['msg'] == 'deleted') { ?>
                <div class="alert alert-success">Election was deleted successfully.</div>
            <?php } ?>
        <?php } ?>
        
        <table class="elections-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Description</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Status</th>
                    <th>Candidates</th>
                    <th>Votes</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($elections) > 0) { ?>
                    <?php foreach ($elections as $election) { ?>
                        <tr>
                            <td><?php echo htmlspecialchars($election['title']); ?></td>
                            <td><?php echo htmlspecialchars(substr($election['description'], 0, 50)) . (strlen($election['description']) > 50 ? '...' : ''); ?></td>
                            <td><?php echo date('M d, Y', strtotime($election['start_date'])); ?></td>
                            <td><?php echo date('M d, Y', strtotime($election['end_date'])); ?></td>
                            <td>
                                <?php
                                $now = new DateTime();
                                $start = new DateTime($election['start_date']);
                                $end = new DateTime($election['end_date']);
                                
                                if ($election['status'] == 'canceled') {
                                    echo '<span class="status-badge status-canceled">Canceled</span>';
                                } else if ($now < $start) {
                                    echo '<span class="status-badge status-upcoming">Upcoming</span>';
                                } else if ($now >= $start && $now <= $end) {
                                    echo '<span class="status-badge status-active">Active</span>';
                                } else {
                                    echo '<span class="status-badge status-completed">Completed</span>';
                                }
                                ?>
                            </td>
                            <td><?php echo $election['candidate_count'] ?? 0; ?></td>
                            <td><?php echo $election['vote_count'] ?? 0; ?></td>
                            <td class="action-buttons">
                                <a href="view_election.php?id=<?php echo $election['id']; ?>" class="btn-action btn-view" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="edit_election.php?id=<?php echo $election['id']; ?>" class="btn-action btn-edit" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="elections.php?delete=<?php echo $election['id']; ?>" class="btn-action btn-delete" title="Delete" onclick="return confirm('Are you sure you want to delete this election?');">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 30px;">No elections found. Create your first election!</td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
    
    <script>
        // Add functionality if needed
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle dark mode
            document.querySelector('.dark-mode-toggle').addEventListener('click', function() {
                document.body.classList.toggle('dark-mode');
            });
            
            // Auto-hide alerts after 5 seconds
            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(function(alert) {
                    alert.style.display = 'none';
                });
            }, 5000);
        });
    </script>
</body>
</html>