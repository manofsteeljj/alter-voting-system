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

// Get dashboard stats
$active_elections_query = $conn->query("SELECT COUNT(*) as count FROM elections WHERE status = 'active'");
$active_elections = $active_elections_query->fetch_assoc()['count'];

$active_voters_query = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'voter'");
$active_voters = $active_voters_query->fetch_assoc()['count'];

$total_votes_query = $conn->query("SELECT COUNT(*) as count FROM votes");
$total_votes = $total_votes_query->fetch_assoc()['count'];

// Get current elections
$elections_query = $conn->query("SELECT * FROM elections ORDER BY start_date DESC LIMIT 3");
$elections = [];
while ($row = $elections_query->fetch_assoc()) {
    $elections[] = $row;
}

// Get recent activities
$activities_query = $conn->query("SELECT * FROM activities ORDER BY timestamp DESC LIMIT 4");
$activities = [];
while ($row = $activities_query->fetch_assoc()) {
    $activities[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - VoteSecure</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="dashboard.css">
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
            transition: background 0.3s, color 0.3s, transform 0.2s;
        }
        
        .nav-item:hover {
            background-color: #f0f5ff;
            color: #0066ff;
            transform: translateX(5px) scale(1.03);
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
        
        .notification-icon, .help-icon, .messages-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-left: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            position: relative;
            cursor: pointer;
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: #ff3b30;
            color: white;
            font-size: 10px;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        /* Dashboard cards */
        .stats-container {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            flex: 1;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }
        
        .stat-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        
        .stat-title {
            color: #555;
            font-weight: 500;
        }
        
        .stat-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            justify-content: center;
            align-items: center;
            color: white;
            transition: transform 0.3s cubic-bezier(.4,0,.2,1);
        }
        
        .icon-elections {
            background-color: #6c5ce7;
        }
        
        .icon-voters {
            background-color: #00b894;
        }
        
        .icon-votes {
            background-color: #9c88ff;
        }
        
        .stat-value {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-description {
            color: #777;
            font-size: 14px;
        }
        
        .stat-footer {
            margin-top: 15px;
        }
        
        .view-link {
            color: #0066ff;
            text-decoration: none;
            display: flex;
            align-items: center;
            font-size: 14px;
        }
        
        .view-link i {
            margin-left: 5px;
        }
        
        .growth-indicator {
            color: #00b894;
            display: flex;
            align-items: center;
            font-size: 14px;
        }
        
        .growth-indicator i {
            margin-right: 5px;
        }
        
        /* Elections container */
        .content-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .content-card {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }
        
        .content-card.large {
            flex: 2;
        }
        
        .content-card.small {
            flex: 1;
        }
        
        .card-title {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin-bottom: 20px;
        }
        
        .election-item {
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
        }
        
        .election-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .election-title {
            font-weight: bold;
            font-size: 16px;
            color: #333;
        }
        
        .election-status {
            background-color: #e6f7eb;
            color: #00b894;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .election-status.upcoming {
            background-color: #fff8e6;
            color: #f39c12;
        }
        
        .election-date {
            color: #777;
            font-size: 14px;
            margin-bottom: 15px;
        }
        
        .participation-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            color: #666;
            font-size: 14px;
        }
        
        .progress-bar {
            height: 10px;
            background-color: #e6e6e6;
            border-radius: 5px;
            overflow: hidden;
            margin-bottom: 15px;
        }
        
        .progress {
            height: 100%;
            background-color: #0066ff;
            border-radius: 5px;
            transition: width 1s cubic-bezier(.4,0,.2,1);
        }
        
        .election-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 8px 15px;
            border-radius: 5px;
            font-size: 14px;
            display: flex;
            align-items: center;
            cursor: pointer;
        }
        
        .btn i {
            margin-right: 5px;
        }
        
        .btn-outline {
            background-color: #f0f5ff;
            color: #0066ff;
            border: none;
        }
        
        .btn-outline:hover {
            background-color: #e0ebff;
        }
        
        /* Activity section */
        .activity-list {
            margin-top: 10px;
        }
        
        .activity-item {
            display: flex;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-right: 15px;
        }
        
        .icon-vote {
            background-color: #e6f7eb;
            color: #00b894;
        }
        
        .icon-user {
            background-color: #e0ebff;
            color: #0066ff;
        }
        
        .icon-warning {
            background-color: #fff8e6;
            color: #f39c12;
        }
        
        .icon-update {
            background-color: #f0e7ff;
            color: #9c88ff;
        }
        
        .activity-details {
            flex-grow: 1;
        }
        
        .activity-title {
            font-weight: 500;
            margin-bottom: 3px;
            color: #333;
        }
        
        .activity-time {
            color: #888;
            font-size: 12px;
        }
        
        /* Fade-in animation for cards */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .stats-container .stat-card,
        .content-card,
        .election-item,
        .activity-item {
            animation: fadeInUp 0.7s ease both;
        }
        .stats-container .stat-card { animation-delay: 0.1s; }
        .content-card { animation-delay: 0.2s; }
        .election-item { animation-delay: 0.3s; }
        .activity-item { animation-delay: 0.4s; }
        
        /* Responsive styles */
        @media (max-width: 992px) {
            .stats-container, .content-row {
                flex-direction: column;
            }
            
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
            <div class="nav-item active">
                <i class="fas fa-th-large"></i>
                <span class="nav-text">Dashboard</span>
            </div>
            <a href="elections.php" class="nav-item">
                <i class="fas fa-poll"></i>
                <span class="nav-text">Elections</span>
            </a>
            <a href="create_election.php" class="nav-item">
                <i class="fas fa-plus-circle"></i>
                <span class="nav-text">Create Election</span>
            </a>
            <a href="users.php" class="nav-item">
                <i class="fas fa-users"></i>
                <span class="nav-text">User Management</span>
            </a>
            <!-- 
            <a href="add_candidate.php" class="nav-item">
                <i class="fas fa-user-plus"></i>
                <span class="nav-text">Add Candidate</span>
            </a>
            <a href="add_voter.php" class="nav-item">
                <i class="fas fa-user-check"></i>
                <span class="nav-text">Add Voter</span>
            </a>
          New: Add Voter button -->
         <!-- Logout button-->
            <a href="logout.php" class="nav-item" style="margin-top:30px;color:#ff5252;">
                <i class="fas fa-sign-out-alt"></i>
                <span class="nav-text">Logout</span>
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
            <div class="page-title">Dashboard</div>
            <div class="header-actions">
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search...">
                </div>
                <div class="notification-icon">
                    <i class="fas fa-bell"></i>
                    <div class="notification-badge">2</div>
                </div>
                <div class="help-icon">
                    <i class="fas fa-question-circle"></i>
                </div>
                <div class="messages-icon">
                    <i class="fas fa-comment"></i>
                </div>
            </div>
        </div>
        
        <!-- Stats Section -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-title">Active Elections</div>
                    <div class="stat-icon icon-elections">
                        <i class="fas fa-poll"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo $active_elections; ?></div>
                <div class="stat-description">Elections in progress</div>
                <div class="stat-footer">
                    <a href="elections.php" class="view-link">View details <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-title">Active Voters</div>
                    <div class="stat-icon icon-voters">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo number_format($active_voters); ?></div>
                <div class="stat-description">Registered voters</div>
                <div class="stat-footer">
                    <!-- You can add a growth indicator if you calculate it in PHP -->
                    <div class="growth-indicator">
                        <i class="fas fa-arrow-up"></i> 
                        <!-- Example: 12% from last month, or leave blank if not calculated -->
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-title">Total Votes</div>
                    <div class="stat-icon icon-votes">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
                <div class="stat-value"><?php echo number_format($total_votes); ?></div>
                <div class="stat-description">All time votes</div>
                <div class="stat-footer">
                    <a href="#" class="view-link">View analytics <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
        </div>
        
        <!-- Elections & Activity Section -->
        <div class="content-row">
            <div class="content-card large">
                <div class="card-title">Current Elections</div>
                <?php if (count($elections) > 0): ?>
                    <?php foreach ($elections as $election): ?>
                        <div class="election-item">
                            <div class="election-header">
                                <div class="election-title"><?php echo htmlspecialchars($election['title']); ?></div>
                                <div class="election-status <?php echo $election['status'] == 'upcoming' ? 'upcoming' : ''; ?>">
                                    <?php echo ucfirst($election['status']); ?>
                                </div>
                            </div>
                            <div class="election-date">
                                <?php echo date('M d', strtotime($election['start_date'])); ?> - <?php echo date('M d, Y', strtotime($election['end_date'])); ?>
                            </div>
                            <!-- Participation and progress bar can be calculated if you have vote/candidate data -->
                            <div class="participation-label">
                                <span>Voter Participation</span>
                                <span>
                                    <?php
                                    // Example: Calculate participation if you have votes and eligible voters
                                    // Replace with your own logic if you store these numbers
                                    $election_id = $election['id'];
                                    $votes_q = $conn->query("SELECT COUNT(*) as count FROM votes WHERE election_id = $election_id");
                                    $votes_count = $votes_q ? $votes_q->fetch_assoc()['count'] : 0;
                                    $voters_q = $conn->query("SELECT COUNT(*) as count FROM voters WHERE election_id = $election_id");
                                    $voters_count = $voters_q ? $voters_q->fetch_assoc()['count'] : 0;
                                    $percent = ($voters_count > 0) ? round(($votes_count / $voters_count) * 100) : 0;
                                    echo "$votes_count/$voters_count ($percent%)";
                                    ?>
                                </span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress" style="width: <?php echo $percent; ?>%;"></div>
                            </div>
                            <div class="election-actions">
                                <a href="view_election.php?id=<?php echo $election['id']; ?>" class="btn btn-outline"><i class="fas fa-eye"></i> View Details</a>
                                <a href="results.php?id=<?php echo $election['id']; ?>" class="btn btn-outline"><i class="fas fa-chart-bar"></i> Live Results</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="padding: 30px; text-align: center;">No elections found.</div>
                <?php endif; ?>
            </div>
            
            <div class="content-card small">
                <div class="card-title">Voting Activity</div>
                <div class="activity-list">
                    <?php if (count($activities) > 0): ?>
                        <?php foreach ($activities as $activity): ?>
                            <div class="activity-item">
                                <div class="activity-icon 
                                    <?php
                                        if ($activity['activity_type'] == 'vote') echo 'icon-vote';
                                        elseif ($activity['activity_type'] == 'register') echo 'icon-user';
                                        elseif ($activity['activity_type'] == 'warning') echo 'icon-warning';
                                        else echo 'icon-update';
                                    ?>">
                                    <?php
                                        if ($activity['activity_type'] == 'vote') echo '<i class="fas fa-check-circle"></i>';
                                        elseif ($activity['activity_type'] == 'register') echo '<i class="fas fa-user-plus"></i>';
                                        elseif ($activity['activity_type'] == 'warning') echo '<i class="fas fa-exclamation-triangle"></i>';
                                        else echo '<i class="fas fa-chart-line"></i>';
                                    ?>
                                </div>
                                <div class="activity-details">
                                    <div class="activity-title">
                                        <?php echo htmlspecialchars($activity['description']); ?>
                                    </div>
                                    <div class="activity-time">
                                        <?php
                                        // Show "x minutes ago" or date
                                        $time = strtotime($activity['timestamp']);
                                        $diff = time() - $time;
                                        if ($diff < 0) $diff = 0; // Prevent negative values
                                        if ($diff < 60) echo "just now";
                                        elseif ($diff < 3600) echo floor($diff/60) . " minutes ago";
                                        elseif ($diff < 86400) echo floor($diff/3600) . " hours ago";
                                        else echo date('M d, Y H:i', $time);
                                        ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="padding: 30px; text-align: center;">No recent activity.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Add functionality if needed
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle sidebar on mobile
            document.querySelector('.dark-mode-toggle').addEventListener('click', function() {
                document.body.classList.toggle('dark-mode');
            });
        });
        document.querySelectorAll('.nav-item a').forEach(item => {
            item.addEventListener('click', function(event) {
                // Ensure navigation is not blocked
                window.location.href = this.getAttribute('href');
            });
        });
    </script>
</body>
</html>