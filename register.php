<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $sql = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $username, $email, $password);

    if ($stmt->execute()) {
        header("Location: login.php");
        exit();
    } else {
        $error = "Error: Username or email already exists.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register | MyBook</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#f0f2f5] flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-lg shadow-lg w-96">
        <h1 class="text-4xl text-[#1877f2] font-bold text-center mb-8">mybook</h1>
        <?php if (!empty($error)) echo "<p class='text-red-500 text-center mb-3'>$error</p>"; ?>
        <form method="POST">
            <input name="username" type="text" placeholder="Username" required class="w-full mb-3 p-3 border rounded-lg">
            <input name="email" type="email" placeholder="Email" required class="w-full mb-3 p-3 border rounded-lg">
            <input name="password" type="password" placeholder="Password" required class="w-full mb-4 p-3 border rounded-lg">
            <button class="w-full bg-[#42b72a] text-white font-bold py-2 rounded-lg hover:bg-green-600">Sign Up</button>
        </form>
        <p class="text-center text-sm text-gray-600 mt-4">Already have an account? <a href="login.php" class="text-[#1877f2]">Log in</a></p>
    </div>
</body>
</html>
