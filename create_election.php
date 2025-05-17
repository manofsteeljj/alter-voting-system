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

// Fetch all users to select as candidates
$candidate_users = [];
$user_query = $conn->query("SELECT id, username FROM users ORDER BY username ASC");
while ($row = $user_query->fetch_assoc()) {
    $candidate_users[] = $row;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $status = $_POST['status'];
    
    $errors = [];
    
    if (empty($title)) {
        $errors[] = "Title is required";
    }
    
    if (empty($description)) {
        $errors[] = "Description is required";
    }
    
    if (empty($start_date)) {
        $errors[] = "Start date is required";
    }
    
    if (empty($end_date)) {
        $errors[] = "End date is required";
    }
    
    if (strtotime($end_date) <= strtotime($start_date)) {
        $errors[] = "End date must be after start date";
    }
    
    // Get selected candidate user IDs (array)
    $selected_candidates = isset($_POST['candidates']) ? $_POST['candidates'] : [];
    
    // If no errors, save to database
    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO elections (title, description, start_date, end_date, status, created_by) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssi", $title, $description, $start_date, $end_date, $status, $user_id);
        
        if ($stmt->execute()) {
            $election_id = $conn->insert_id;
            
            // Insert selected candidates into candidates table
            if (!empty($selected_candidates)) {
                $cand_stmt = $conn->prepare("INSERT INTO candidates (name, election_id, user_id) VALUES (?, ?, ?)");
                foreach ($selected_candidates as $candidate_id) {
                    // Get username for candidate name
                    $uname_stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
                    $uname_stmt->bind_param("i", $candidate_id);
                    $uname_stmt->execute();
                    $uname_result = $uname_stmt->get_result();
                    $uname_row = $uname_result->fetch_assoc();
                    $candidate_name = $uname_row ? $uname_row['username'] : 'Candidate';
                    
                    $cand_stmt->bind_param("sii", $candidate_name, $election_id, $candidate_id);
                    $cand_stmt->execute();
                }
            }

            // Record activity
            $activity_stmt = $conn->prepare("INSERT INTO activities (activity_type, description, user_id, election_id) VALUES ('create', 'New election created', ?, ?)");
            $activity_stmt->bind_param("ii", $user_id, $election_id);
            $activity_stmt->execute();
            
            // Redirect to elections page
            header('Location: elections.php?msg=created');
            exit();
        } else {
            $errors[] = "Database error: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Election - VoteSecure</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
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
            text-decoration: none;
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
        
        .form-card {
            background-color: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }
        
        .form-control {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #0066ff;
            box-shadow: 0 0 0 2px rgba(0, 102, 255, 0.1);
        }
        
        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            border: none;
        }
        
        .btn i {
            margin-right: 8px;
        }
        
        .btn-primary {
            background-color: #0066ff;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #0052cc;
        }
        
        .btn-secondary {
            background-color: #f0f0f0;
            color: #333;
        }
        
        .btn-secondary:hover {
            background-color: #e0e0e0;
        }
        
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 30px;
        }
        
        .errors {
            background-color: #ffe6e6;
            color: #ff5252;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #ff5252;
        }
        
        .errors ul {
            margin: 0;
            padding-left: 20px;
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
            <div class="page-title">Create New Election</div>
        </div>
        
        <div class="form-card">
            <?php if (!empty($errors)) { ?>
                <div class="errors">
                    <ul>
                        <?php foreach ($errors as $error) { ?>
                            <li><?php echo $error; ?></li>
                        <?php } ?>
                    </ul>
                </div>
<?php } ?>
            <form action="create_election.php" method="post">
                <div class="form-group">
                    <label for="title" class="form-label">Election Title</label>
                    <input type="text" id="title" name="title" class="form-control" 
                           value="<?php echo isset($title) ? htmlspecialchars($title) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="description" class="form-label">Description</label>
                    <textarea id="description" name="description" class="form-control"><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="datetime-local" id="start_date" name="start_date" class="form-control"
                           value="<?php echo isset($start_date) ? htmlspecialchars($start_date) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="datetime-local" id="end_date" name="end_date" class="form-control"
                           value="<?php echo isset($end_date) ? htmlspecialchars($end_date) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="status" class="form-label">Status</label>
                    <select id="status" name="status" class="form-control">
                        <option value="active" <?php echo (isset($status) && $status == 'active') ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo (isset($status) && $status == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                        <option value="upcoming" <?php echo (isset($status) && $status == 'upcoming') ? 'selected' : ''; ?>>Upcoming</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="candidates" class="form-label">Select Candidates</label>
                    <select id="candidates" name="candidates[]" class="form-control" multiple required>
                        <?php foreach ($candidate_users as $cu): ?>
                            <option value="<?php echo $cu['id']; ?>"
                                <?php echo (isset($selected_candidates) && in_array($cu['id'], $selected_candidates)) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cu['username']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small>Select one or more users as candidates for this election.</small>
                </div>
                
                <div class="form-actions">
                    <a href="elections.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Create Election
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        // Toggle dark mode
        document.querySelector('.dark-mode-toggle').addEventListener('click', function() {
            document.body.classList.toggle('dark-mode');
            // Here you would also save the preference to local storage or database
        });
        
        // Form validation on submit
        document.querySelector('form').addEventListener('submit', function(e) {
            const startDate = new Date(document.getElementById('start_date').value);
            const endDate = new Date(document.getElementById('end_date').value);
            
            if (endDate <= startDate) {
                e.preventDefault();
                alert('End date must be after start date');
            }
        });
        
        // Initialize select2 for candidate selection
        $(document).ready(function() {
            $('#candidates').select2({
                placeholder: "Choose candidates...",
                width: '100%',
                allowClear: true
            });
        });
    </script>
</body>
</html>