<?php
// user_profile.php
session_start();
include 'db.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];
$profile_user_id = $_GET['id'] ?? null;
$profile_user = null;
$friendship_status = 'none'; // none, pending_sent, pending_received, accepted

// 1. Validate the User ID in the URL
if (!is_numeric($profile_user_id)) {
    header("Location: index.php?error=invalid_user");
    exit();
}

// 2. Fetch the target user's details
$stmt_user = $conn->prepare("SELECT id, username, email, created_at FROM users WHERE id = ?");
$stmt_user->bind_param("i", $profile_user_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
$profile_user = $result_user->fetch_assoc();
$stmt_user->close();

if (!$profile_user) {
    header("Location: index.php?error=user_not_found");
    exit();
}

// Check if the user is viewing their own profile
$is_own_profile = ($current_user_id == $profile_user_id);

// 3. Check Friendship Status (only if it's NOT the user's own profile)
if (!$is_own_profile) {
    // Determine the user1_id (smaller ID) and user2_id (larger ID) for the query
    $user1_id_check = min($current_user_id, $profile_user_id);
    $user2_id_check = max($current_user_id, $profile_user_id);

    $stmt_friendship = $conn->prepare("
        SELECT status, user1_id 
        FROM friendships 
        WHERE user1_id = ? AND user2_id = ?
    ");
    $stmt_friendship->bind_param("ii", $user1_id_check, $user2_id_check);
    $stmt_friendship->execute();
    $result_friendship = $stmt_friendship->get_result();
    $friendship_row = $result_friendship->fetch_assoc();
    $stmt_friendship->close();

    if ($friendship_row) {
        if ($friendship_row['status'] == 'accepted') {
            $friendship_status = 'accepted';
        } elseif ($friendship_row['status'] == 'pending') {
            // Check who sent the request to determine button text
            if ($friendship_row['user1_id'] == $current_user_id) {
                $friendship_status = 'pending_sent'; // Current user sent the request
            } else {
                $friendship_status = 'pending_received'; // Current user needs to accept
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($profile_user['username']); ?>'s Profile | MyBook</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans">

<header class="bg-white shadow-md p-4 flex justify-between items-center">
    <h1 class="text-3xl text-[#1877f2] font-bold">mybook</h1>
    <nav class="flex items-center space-x-4">
        <a href="index.php" class="text-gray-700 hover:text-[#1877f2]">Home</a>
        <a href="logout.php" class="bg-red-500 text-white px-3 py-1 rounded-lg hover:bg-red-600">Logout</a>
    </nav>
</header>

<main class="max-w-3xl mx-auto p-6">
    <div class="bg-white p-6 rounded-xl shadow-lg">
        <div class="flex items-center justify-between border-b pb-4 mb-4">
            <div class="flex items-center">
                <div class="w-20 h-20 bg-[#1877f2] text-white rounded-full flex items-center justify-center font-bold text-3xl">
                    <?php echo strtoupper(substr($profile_user['username'], 0, 1)); ?>
                </div>
                <div class="ml-4">
                    <h2 class="text-3xl font-bold text-gray-800">
                        <?php echo htmlspecialchars($profile_user['username']); ?>
                    </h2>
                    <p class="text-gray-500">Joined: <?php echo date('F Y', strtotime($profile_user['created_at'])); ?></p>
                </div>
            </div>

            <div>
                <?php if ($is_own_profile): ?>
                    <button class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg cursor-default">Your Profile</button>
                <?php elseif ($friendship_status == 'none'): ?>
                    <a href="send_request.php?recipient_id=<?php echo $profile_user_id; ?>" 
                       class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600">
                        + Add Friend
                    </a>
                <?php elseif ($friendship_status == 'pending_sent'): ?>
                    <button class="bg-yellow-500 text-white px-4 py-2 rounded-lg cursor-default">Request Sent</button>
                <?php elseif ($friendship_status == 'pending_received'): ?>
                    <a href="requests.php" class="bg-orange-500 text-white px-4 py-2 rounded-lg hover:bg-orange-600">
                        Accept Request
                    </a>
                <?php elseif ($friendship_status == 'accepted'): ?>
                    <button class="bg-[#1877f2] text-white px-4 py-2 rounded-lg cursor-default">Friends</button>
                <?php endif; ?>
            </div>
        </div>

        <h3 class="text-xl font-bold mt-6 mb-4">Recent Activity</h3>
        <div class="p-4 bg-gray-50 rounded-lg">
            <p class="text-gray-600">This section would show the user's recent posts.</p>
        </div>
    </div>
</main>

</body>
</html>