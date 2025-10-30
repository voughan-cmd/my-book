<?php
// index.php
session_start();
include 'db.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];
$current_username = $_SESSION['username'];
$posts = []; // Initialize posts array

// 1. Fetch posts from the user AND their accepted friends (COMPLEX SQL)
$sql = "
    SELECT 
        p.*, u.username
    FROM 
        posts p
    JOIN 
        users u ON p.user_id = u.id
    WHERE
        p.user_id = ? /* Posts from the current user */
        OR p.user_id IN (
            /* Subqueries find ALL user IDs accepted by the current user */
            SELECT user2_id FROM friendships WHERE user1_id = ? AND status = 'accepted'
            UNION
            SELECT user1_id FROM friendships WHERE user2_id = ? AND status = 'accepted'
        )
    ORDER BY 
        p.created_at DESC
    LIMIT 20
";

// Use prepared statement for security and to bind the same ID three times
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $current_user_id, $current_user_id, $current_user_id);
$stmt->execute();
$result = $stmt->get_result();

// Store results in an array for cleaner looping
while ($row = $result->fetch_assoc()) {
    $posts[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MyBook - Connect and Share</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans">

<header class="bg-white shadow-md p-4 flex justify-between items-center">
    <h1 class="text-3xl text-[#1877f2] font-bold">mybook</h1>
    <nav class="flex items-center">
        <a href="requests.php" class="mr-4 text-[#1877f2] font-semibold hover:underline">
            Friend Requests
        </a>
        <span class="mr-4 font-semibold text-gray-700">
            <?php echo htmlspecialchars($current_username); ?>
        </span>
        <a href="logout.php" class="bg-red-500 text-white px-3 py-1 rounded-lg hover:bg-red-600">Logout</a>
    </nav>
</header>

<main class="max-w-3xl mx-auto p-6">
    <div class="bg-white p-5 rounded-xl shadow-lg mb-6">
        <h3 class="font-bold text-lg mb-3">Create Post</h3>
        <form action="post.php" method="POST">
            <textarea name="content" rows="3" placeholder="What's on your mind? ...
                      class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-[#1877f2]"></textarea>
            <button type="submit" class="mt-3 bg-[#42b72a] text-white px-6 py-2 rounded-lg hover:bg-green-600">Post</button>
        </form>
    </div>

    <?php if (empty($posts)): ?>
        <div class="p-5 text-center text-gray-600">Your feed is empty. Post something, or add friends!</div>
    <?php else: ?>
        <?php foreach ($posts as $row): ?>
            <div class="bg-white p-5 rounded-xl shadow-lg mb-4">
                <div class="flex items-center mb-3">
                    <div class="w-10 h-10 bg-[#1877f2] text-white rounded-full flex items-center justify-center font-bold text-lg">
                        <?php echo strtoupper(substr($row['username'], 0, 1)); ?>
                    </div>
                    <div class="ml-3">
                        <p class="font-bold text-gray-800"><?php echo htmlspecialchars($row['username']); ?></p>
                        <p class="text-xs text-gray-500"><?php echo $row['created_at']; ?></p>
                    </div>
                </div>
                <p class="text-gray-700 whitespace-pre-wrap"><?php echo nl2br(htmlspecialchars($row['post_content'])); ?></p>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</main>

</body>
</html>