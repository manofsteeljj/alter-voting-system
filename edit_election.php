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
if ($user['role'] != 'voter') {
    header('Location: dashboard.php');
    exit();
}

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

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $status = $_POST['status'];

    $errors = [];

    if (empty($title)) {
        $errors[] = "Title is required.";
    }

    if (empty($description)) {
        $errors[] = "Description is required.";
    }

    if (empty($start_date)) {
        $errors[] = "Start date is required.";
    }

    if (empty($end_date)) {
        $errors[] = "End date is required.";
    }

    if (strtotime($end_date) <= strtotime($start_date)) {
        $errors[] = "End date must be after start date.";
    }

    if (empty($errors)) {
        $update_stmt = $conn->prepare("UPDATE elections SET title = ?, description = ?, start_date = ?, end_date = ?, status = ? WHERE id = ?");
        $update_stmt->bind_param("sssssi", $title, $description, $start_date, $end_date, $status, $election_id);

        if ($update_stmt->execute()) {
            header('Location: elections.php?msg=updated');
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
    <title>Edit Election - VoteSecure</title>
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

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            font-weight: bold;
            margin-bottom: 5px;
            display: block;
            color: #333;
        }

        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
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

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        .alert-danger {
            background-color: #ffe6e6;
            color: #ff5252;
            border: 1px solid #ff5252;
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
            <div class="page-title">Edit Election</div>
        </div>

        <?php if (!empty($errors)) { ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error) { echo "<p>$error</p>"; } ?>
            </div>
        <?php } ?>

        <form action="edit_election.php?id=<?php echo $election_id; ?>" method="post">
            <div class="form-group">
                <label for="title" class="form-label">Election Title</label>
                <input type="text" id="title" name="title" class="form-control" value="<?php echo htmlspecialchars($election['title']); ?>" required>
            </div>

            <div class="form-group">
                <label for="description" class="form-label">Description</label>
                <textarea id="description" name="description" class="form-control" required><?php echo htmlspecialchars($election['description']); ?></textarea>
            </div>

            <div class="form-group">
                <label for="start_date" class="form-label">Start Date</label>
                <input type="datetime-local" id="start_date" name="start_date" class="form-control" value="<?php echo date('Y-m-d\TH:i', strtotime($election['start_date'])); ?>" required>
            </div>

            <div class="form-group">
                <label for="end_date" class="form-label">End Date</label>
                <input type="datetime-local" id="end_date" name="end_date" class="form-control" value="<?php echo date('Y-m-d\TH:i', strtotime($election['end_date'])); ?>" required>
            </div>

            <div class="form-group">
                <label for="status" class="form-label">Status</label>
                <select id="status" name="status" class="form-control">
                    <option value="active" <?php echo ($election['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo ($election['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                    <option value="upcoming" <?php echo ($election['status'] == 'upcoming') ? 'selected' : ''; ?>>Upcoming</option>
                </select>
            </div>

            <div class="form-actions">
                <a href="elections.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</body>
</html>