<?php
session_start();
include 'db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Fetch user data
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT username, role FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Get election ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: elections.php');
    exit();
}
$election_id = intval($_GET['id']);

// Fetch election info
$election_stmt = $conn->prepare("SELECT * FROM elections WHERE id = ?");
$election_stmt->bind_param("i", $election_id);
$election_stmt->execute();
$election = $election_stmt->get_result()->fetch_assoc();
if (!$election) {
    header('Location: elections.php');
    exit();
}

// Fetch candidates and their vote counts
$candidates = [];
$cand_query = $conn->prepare("SELECT c.id, c.name, 
    (SELECT COUNT(*) FROM votes v WHERE v.candidate_id = c.id) as vote_count
    FROM candidates c WHERE c.election_id = ?");
$cand_query->bind_param("i", $election_id);
$cand_query->execute();
$cand_result = $cand_query->get_result();
$total_votes = 0;
while ($row = $cand_result->fetch_assoc()) {
    $candidates[] = $row;
    $total_votes += $row['vote_count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Live Results - <?php echo htmlspecialchars($election['title']); ?> | VoteSecure</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f5f5f7; display: flex; min-height: 100vh; font-family: Arial, sans-serif; }
        .sidebar {
            width: 220px; background: #fff; box-shadow: 0 0 10px rgba(0,0,0,0.1);
            padding: 15px; display: flex; flex-direction: column; position: fixed; height: 100vh;
        }
        .logo-container { display: flex; align-items: center; padding: 10px 0 20px 0; }
        .logo { background: #0066ff; color: #fff; width: 36px; height: 36px; border-radius: 8px;
            display: flex; justify-content: center; align-items: center; margin-right: 10px; }
        .logo-text { font-weight: bold; font-size: 18px; color: #333; }
        .nav-menu { margin-top: 20px; flex-grow: 1; }
        .nav-item {
            display: flex; align-items: center; padding: 12px 15px; margin-bottom: 5px;
            border-radius: 8px; cursor: pointer; color: #555; transition: all 0.3s; text-decoration: none;
        }
        .nav-item:hover { background: #f0f5ff; color: #0066ff; }
        .nav-item.active { background: #0066ff; color: #fff; }
        .nav-item i { margin-right: 10px; width: 20px; text-align: center; }
        .user-profile {
            display: flex; align-items: center; padding: 15px 10px; border-top: 1px solid #eee; margin-top: auto;
        }
        .user-avatar {
            width: 40px; height: 40px; border-radius: 50%; background: #ddd;
            display: flex; justify-content: center; align-items: center; margin-right: 10px; overflow: hidden;
        }
        .user-info { flex-grow: 1; }
        .user-name { font-weight: bold; font-size: 14px; }
        .user-role { color: #777; font-size: 12px; }
        .dark-mode-toggle { margin-left: 10px; color: #777; cursor: pointer; }
        .main-content { flex: 1; margin-left: 220px; padding: 20px; }
        .header { display: flex; justify-content: space-between; align-items: center; padding: 10px 0 20px 0; }
        .page-title { font-size: 24px; font-weight: bold; color: #333; }
        .results-card {
            background: #fff; border-radius: 10px; padding: 30px 30px 20px 30px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05); max-width: 600px; margin: 0 auto;
        }
        .results-title { font-size: 20px; font-weight: bold; color: #333; margin-bottom: 20px; }
        .candidate-row {
            display: flex; align-items: center; justify-content: space-between;
            padding: 15px 0; border-bottom: 1px solid #f0f0f0;
        }
        .candidate-name { font-size: 16px; color: #333; }
        .vote-bar-bg {
            background: #f0f0f0; border-radius: 5px; height: 18px; flex: 1; margin: 0 20px;
            position: relative; overflow: hidden;
        }
        .vote-bar {
            background: #0066ff; height: 100%; border-radius: 5px 0 0 5px; transition: width 0.5s;
        }
        .vote-count { font-weight: bold; color: #0066ff; min-width: 50px; text-align: right; }
        .total-votes { margin-top: 25px; color: #555; font-size: 15px; text-align: right; }
        @media (max-width: 992px) {
            .sidebar { width: 80px; }
            .logo-text, .nav-text, .user-info { display: none; }
            .main-content { margin-left: 80px; }
            .nav-item { justify-content: center; padding: 12px 0; }
            .nav-item i { margin-right: 0; }
            .user-profile { justify-content: center; padding: 15px 0; }
            .user-avatar { margin-right: 0; }
            .results-card { padding: 20px 10px; }
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
            <a href="elections.php" class="nav-item">
                <i class="fas fa-poll"></i>
                <span class="nav-text">Elections</span>
            </a>
            <a href="users.php" class="nav-item">
                <i class="fas fa-users"></i>
                <span class="nav-text">User Management</span>
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
            <div class="dark-mode-toggle">
                <i class="fas fa-moon"></i>
            </div>
        </div>
    </div>
    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <div class="page-title">Live Results: <?php echo htmlspecialchars($election['title']); ?></div>
        </div>
        <div class="results-card">
            <div class="results-title"><i class="fas fa-chart-bar"></i> Candidate Results</div>
            <?php if (count($candidates) > 0): ?>
                <?php foreach ($candidates as $cand): 
                    $percent = ($total_votes > 0) ? round(($cand['vote_count'] / $total_votes) * 100) : 0;
                ?>
                <div class="candidate-row">
                    <div class="candidate-name"><?php echo htmlspecialchars($cand['name']); ?></div>
                    <div class="vote-bar-bg">
                        <div class="vote-bar" style="width: <?php echo $percent; ?>%;"></div>
                    </div>
                    <div class="vote-count"><?php echo $cand['vote_count']; ?> (<?php echo $percent; ?>%)</div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="padding: 30px; text-align: center;">No candidates found for this election.</div>
            <?php endif; ?>
            <div class="total-votes">
                <strong>Total Votes:</strong> <?php echo $total_votes; ?>
            </div>
        </div>
    </div>
    <script>
        document.querySelector('.dark-mode-toggle').addEventListener('click', function() {
            document.body.classList.toggle('dark-mode');
        });
    </script>
</body>
</html>