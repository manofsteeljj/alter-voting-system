<?php
session_start();
include 'db_connect.php';

// Only allow admins or election managers
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if (!isset($_GET['election_id']) || !is_numeric($_GET['election_id'])) {
    die("Election ID missing.");
}
$election_id = intval($_GET['election_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    if ($name != '') {
        $stmt = $conn->prepare("INSERT INTO candidates (name, election_id) VALUES (?, ?)");
        $stmt->bind_param("si", $name, $election_id);
        $stmt->execute();
        header("Location: add_candidate.php?election_id=$election_id&success=1");
        exit();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Candidate</title>
</head>
<body>
    <h2>Add Candidate to Election #<?php echo $election_id; ?></h2>
    <?php if (isset($_GET['success'])): ?>
        <p style="color:green;">Candidate added!</p>
    <?php endif; ?>
    <form method="post">
        <input type="text" name="name" placeholder="Candidate Name" required>
        <button type="submit">Add Candidate</button>
    </form>
    <a href="elections.php">Back to Elections</a>
</body>
</html>