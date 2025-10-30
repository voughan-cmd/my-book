<?php
// send_request.php
session_start();
include 'db.php';

// 1. SECURITY CHECK: Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$sender_id = $_SESSION['user_id'];
$recipient_id = $_GET['recipient_id'] ?? null;

// Ensure recipient ID is valid
if (!is_numeric($recipient_id) || $sender_id == $recipient_id) {
    // Redirect to a safe place if parameters are bad
    header('Location: index.php?error=invalid_recipient');
    exit;
}

// 2. Check if friendship already exists (pending or accepted)
// The user who has the smaller ID is always user1_id in our table logic
$user1_id_check = min($sender_id, $recipient_id);
$user2_id_check = max($sender_id, $recipient_id);

$stmt_check = $conn->prepare("
    SELECT status FROM friendships
    WHERE user1_id = ? AND user2_id = ?
");
$stmt_check->bind_param("ii", $user1_id_check, $user2_id_check);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows > 0) {
    // Friendship exists (either pending or accepted), prevent duplicate request
    header('Location: index.php?info=already_connected'); 
    exit;
}
$stmt_check->close();

// 3. Insert the new friendship request (status defaults to 'pending')
$stmt_insert = $conn->prepare("
    INSERT INTO friendships (user1_id, user2_id, status) VALUES (?, ?, 'pending')
");

if ($stmt_insert->bind_param("ii", $user1_id_check, $user2_id_check)) {
    $stmt_insert->execute();
    $stmt_insert->close();
    header('Location: index.php?success=request_sent'); // Redirect with success
} else {
    // Error handling
    header('Location: index.php?error=db_insert_fail');
}

exit;
?>