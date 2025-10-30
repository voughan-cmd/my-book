<?php
// requests.php
session_start();
include 'db.php';

// 1. SECURITY CHECK: Redirect if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];
$message = '';

// 2. PROCESS POST ACTION (ACCEPT or DENY)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['friendship_id'])) {
    $friendship_id = $_POST['friendship_id'];
    $action = $_POST['action'];

    if ($action === 'accept') {
        // Update status to 'accepted' ONLY if the current user is the recipient (user2_id)
        $stmt = $conn->prepare("UPDATE friendships SET status = 'accepted' WHERE id = ? AND user2_id = ? AND status = 'pending'");
        $stmt->bind_param("ii", $friendship_id, $current_user_id);
        $stmt->execute();
        $stmt->close();
        $message = "Request accepted!";

    } elseif ($action === 'deny') {
        // Delete the pending request
        $stmt = $conn->prepare("DELETE FROM friendships WHERE id = ? AND user2_id = ? AND status = 'pending'");
        $stmt->bind_param("ii", $friendship_id, $current_user_id);
        $stmt->execute();
        $stmt->close();
        $message = "Request denied!";
    }
    // Redirect to self to clear POST data and show message
    header('Location: requests.php?msg=' . urlencode($message));
    exit;
}

// 3. FETCH PENDING REQUESTS
// Fetch requests where the current user is user2_id and status is pending
$sql_fetch = "
    SELECT f.id, u.username, u.id as sender_id
    FROM friendships f
    JOIN users u ON u.id = f.user1_id
    WHERE f.user2_id = ? AND f.status = 'pending'
";
$stmt_fetch = $conn->prepare($sql_fetch);
$stmt_fetch->bind_param("i", $current_user_id);
$stmt_fetch->execute();
$pending_requests = $stmt_fetch->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_fetch->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MyBook - Friend Requests</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans">
    <header class="bg-white shadow-md p-4 flex justify-between items-center">
        <h1 class="text-3xl text-[#1877f2] font-bold">mybook</h1>
        <a href="index.php" class="bg-[#1877f2] text-white px-3 py-1 rounded-lg hover:bg-blue-700">Back to Home</a>
    </header>

    <main class="max-w-xl mx-auto p-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Pending Friend Requests</h2>
        
        <?php 
        // Display status message after accept/deny
        if (isset($_GET['msg'])) {
            echo '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">' . htmlspecialchars($_GET['msg']) . '</div>';
        }
        ?>

        <?php if (empty($pending_requests)): ?>
            <div class="p-4 bg-white rounded-lg shadow-lg text-center text-gray-500">
                You have no new friend requests.
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($pending_requests as $request): ?>
                    <div class="flex items-center justify-between bg-white p-4 rounded-lg shadow-md">
                        <p class="font-semibold text-gray-800">
                            **<?php echo htmlspecialchars($request['username']); ?>** wants to be your friend.
                        </p>
                        <div class="flex space-x-2">
                            <form action="requests.php" method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="accept">
                                <input type="hidden" name="friendship_id" value="<?php echo $request['id']; ?>">
                                <button type="submit" class="bg-green-500 text-white px-3 py-1 rounded hover:bg-green-600 text-sm">Accept</button>
                            </form>
                            <form action="requests.php" method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="deny">
                                <input type="hidden" name="friendship_id" value="<?php echo $request['id']; ?>">
                                <button type="submit" class="bg-gray-500 text-white px-3 py-1 rounded hover:bg-gray-600 text-sm">Deny</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

</body>
</html>