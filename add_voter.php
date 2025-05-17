<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if (!isset($_GET['election_id']) || !is_numeric($_GET['election_id'])) {
    die("Election ID missing.");
}
$election_id = intval($_GET['election_id']);

// Get all users
$users = [];
$res = $conn->query("SELECT id, username FROM users ORDER BY username ASC");
while ($row = $res->fetch_assoc()) $users[] = $row;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = intval($_POST['user_id']);
    // Prevent duplicate
    $check = $conn->query("SELECT id FROM voters WHERE election_id=$election_id AND user_id=$user_id");
    if ($check->num_rows == 0) {
        $stmt = $conn->prepare("INSERT INTO voters (election_id, user_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $election_id, $user_id);
        $stmt->execute();
        header("Location: add_voter.php?election_id=$election_id&success=1");
        exit();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Voter</title>
</head>
<body>
    <h2>Add Voter to Election #<?php echo $election_id; ?></h2>
    <?php if (isset($_GET['success'])): ?>
        <p style="color:green;">Voter added!</p>
    <?php endif; ?>
    <form method="post">
        <select name="user_id" required>
            <option value="">Select User</option>
            <?php foreach ($users as $u): ?>
                <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['username']); ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Add Voter</button>
    </form>
    <a href="elections.php">Back to Elections</a>
</body>
</html>