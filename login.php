<?php
session_start();
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $identifier = $_POST['identifier'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE email=? OR username=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $identifier, $identifier);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        header("Location: index.php");
        exit();
    } else {
        $error = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login | MyBook</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#f0f2f5] flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-lg shadow-lg w-96">
        <h1 class="text-4xl text-[#1877f2] font-bold text-center mb-8">mybook</h1>
        <?php if (!empty($error)) echo "<p class='text-red-500 text-center mb-3'>$error</p>"; ?>
        <form method="POST">
            <input name="identifier" type="text" placeholder="Email or Username" required class="w-full mb-3 p-3 border rounded-lg">
            <input name="password" type="password" placeholder="Password" required class="w-full mb-4 p-3 border rounded-lg">
            <button class="w-full bg-[#1877f2] text-white font-bold py-2 rounded-lg hover:bg-blue-700">Log In</button>
        </form>
        <p class="text-center text-sm text-gray-600 mt-4">Don’t have an account? <a href="register.php" class="text-[#1877f2]">Sign up</a></p>
    </div>
</body>
</html>
