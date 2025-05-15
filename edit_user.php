<?php
session_start();
include 'db_connect.php';

// Check if user is logged in and is admin
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

if (!$current_user || $current_user['role'] !== 'voter') {
    header('Location: users.php?error=unauthorized');
    exit();
}

// Get user to edit
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: users.php?error=invalid_id');
    exit();
}
$edit_id = intval($_GET['id']);
$stmt = $conn->prepare("SELECT id, username, email, role FROM users WHERE id = ?");
$stmt->bind_param("i", $edit_id);
$stmt->execute();
$result = $stmt->get_result();
$edit_user = $result->fetch_assoc();

if (!$edit_user) {
    header('Location: users.php?error=user_not_found');
    exit();
}

// Handle form submission
$success = '';
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $role = trim($_POST['role']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email address.";
    }
    if (!in_array($role, ['admin', 'voter', 'election_manager'])) {
        $errors[] = "Invalid role.";
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE users SET email = ?, role = ? WHERE id = ?");
        $stmt->bind_param("ssi", $email, $role, $edit_id);
        if ($stmt->execute()) {
            $success = "User updated successfully.";
            $edit_user['email'] = $email;
            $edit_user['role'] = $role;
        } else {
            $errors[] = "Failed to update user.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit User - VoteSecure</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f5f5f7; font-family: Arial, sans-serif; }
        .main-content { max-width: 500px; margin: 40px auto; background: #fff; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); padding: 30px; }
        .header { font-size: 24px; font-weight: bold; color: #333; margin-bottom: 20px; }
        .form-group { margin-bottom: 20px; }
        .form-label { font-weight: bold; margin-bottom: 5px; display: block; color: #333; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; }
        .form-actions { margin-top: 20px; }
        .btn { padding: 10px 20px; border-radius: 5px; font-size: 14px; display: inline-flex; align-items: center; cursor: pointer; text-decoration: none; color: #fff; background: #0066ff; border: none; transition: background 0.3s; }
        .btn:hover { background: #0056d1; }
        .btn-secondary { background: #555; }
        .btn-secondary:hover { background: #444; }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .alert-success { background: #e6ffe6; color: #009900; border: 1px solid #009900; }
        .alert-danger { background: #ffe6e6; color: #ff5252; border: 1px solid #ff5252; }
    </style>
</head>
<body>
    <div class="main-content">
        <div class="header"><i class="fas fa-user-edit"></i> Edit User</div>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error) echo "<div>$error</div>"; ?>
            </div>
        <?php endif; ?>
        <form method="post" action="edit_user.php?id=<?php echo $edit_id; ?>">
            <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($edit_user['username']); ?>" disabled>
            </div>
            <div class="form-group">
                <label class="form-label" for="email">Email</label>
                <input type="email" class="form-control" name="email" id="email" value="<?php echo htmlspecialchars($edit_user['email']); ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="role">Role</label>
                <select name="role" id="role" class="form-control" required>
                    <option value="admin" <?php if($edit_user['role']=='admin') echo 'selected'; ?>>Admin</option>
                    <option value="voter" <?php if($edit_user['role']=='voter') echo 'selected'; ?>>Voter</option>
                    <option value="election_manager" <?php if($edit_user['role']=='election_manager') echo 'selected'; ?>>Election Manager</option>
                </select>
            </div>
            <div class="form-actions">
                <a href="users.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Cancel</a>
                <button type="submit" class="btn"><i class="fas fa-save"></i> Save Changes</button>
            </div>
        </form>
    </div>
</body>
</html>