
<?php
session_start();
require_once "config/database.php";

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === "") {
        $message = "Please enter a valid email and password.";
        $messageType = "error";
    } else {
        $sql = "SELECT id, name, email, password FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();

        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user["password"])) {
            session_regenerate_id(true);

            $_SESSION["user_id"] = $user["id"];
            $_SESSION["user_name"] = $user["name"];
            $_SESSION["user_email"] = $user["email"];

            header("Location: dashboard.php");
            exit;
        } else {
            $message = "Invalid email or password.";
            $messageType = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Smart Plant Water Reminder</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f2f7f2;
            margin: 0;
            padding: 0;
        }
        .container {
            width: min(360px, calc(100% - 60px));
            margin: 60px auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px #0000001a;
        }
        h2 { text-align: center; }
        label {
            display: block;
            margin-top: 15px;
            margin-bottom: 5px;
        }
        input {
            width: 100%;
            padding: 10px;
            box-sizing: border-box;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        button {
            width: 100%;
            padding: 12px;
            margin-top: 20px;
            border: 0;
            border-radius: 5px;
            background: #388e3c;
            color: white;
            font-size: 16px;
            cursor: pointer;
        }
        .error {
            color: #b00020;
            text-align: center;
            margin: 15px 0;
        }
        .register-link {
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Login</h2>

        <?php if ($message !== ""): ?>
            <p class="error">
                <?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?>
            </p>
        <?php endif; ?>

        <form method="POST" action="">
            <label for="email">Email</label>
            <input type="email" id="email" name="email"
                   placeholder="Enter your email" required>

            <label for="password">Password</label>
            <input type="password" id="password" name="password"
                   placeholder="Enter your password" required>

            <button type="submit">Login</button>
        </form>

        <p class="register-link">
            Don't have an account?
            <a href="register.php">Register</a>
        </p>
    </div>
</body>
</html>
