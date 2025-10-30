<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$content = trim($_POST['content']);
if ($content !== '') {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("INSERT INTO posts (user_id, content) VALUES (?, ?)");
    $stmt->bind_param("is", $user_id, $content);
    $stmt->execute();
}
header("Location: index.php");
exit();
?>
