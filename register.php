<?php

require_once "config/database.php";

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];

    // Check empty fields
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {

        $message = "All fields are required.";
        $messageType = "error";

    } 
    // Check email
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email address.";
        $messageType = "error";

    }
    // Check password length
    elseif (strlen($password) < 6) {

        $message = "Password must be at least 6 characters.";
        $messageType = "error";

    }
    // Check password confirmation
    elseif ($password !== $confirm_password) {

        $message = "Passwords do not match.";
        $messageType = "error";

    } 
    else {

        // Check if email already exists
        $checkQuery = "SELECT id FROM users WHERE email = ?";

        $stmt = $conn->prepare($checkQuery);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {

            $message = "Email already registered.";
            $messageType = "error";

        } 
        else {

            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Insert user
            $insertQuery = "INSERT INTO users (name, email, password) VALUES (?, ?, ?)";

            $stmt = $conn->prepare($insertQuery);
            $stmt->bind_param("sss", $name, $email, $hashedPassword);

            if ($stmt->execute()) {

                $message = "Registration successful!";
                $messageType = "success";

            } 
            else {

                $message = "Registration failed. Please try again.";
                $messageType = "error";
            }
        }

        $stmt->close();
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Register - Smart Plant Water Reminder</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f2f7f2;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 400px;
            margin: 60px auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
            margin-bottom: 25px;
        }

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
            border: none;
            border-radius: 5px;
            cursor: pointer;
            background-color: #4caf50;
            color: white;
            font-size: 16px;
        }

        button:hover {
            background-color: #45a049;
        }

        .success {
            color: green;
            text-align: center;
            margin-bottom: 15px;
        }

        .error {
            color: red;
            text-align: center;
            margin-bottom: 15px;
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>

<body>

<div class="container">

    <h2>Create Account</h2>

    <?php if (!empty($message)): ?>

        <div class="<?php echo $messageType; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>

    <?php endif; ?>

    <form method="POST" action="">

        <label for="name">Name</label>

        <input
            type="text"
            id="name"
            name="name"
            placeholder="Enter your name"
            required
        >

        <label for="email">Email</label>

        <input
            type="email"
            id="email"
            name="email"
            placeholder="Enter your email"
            required
        >

        <label for="password">Password</label>

        <input
            type="password"
            id="password"
            name="password"
            placeholder="Enter your password"
            required
        >

        <label for="confirm_password">Confirm Password</label>

        <input
            type="password"
            id="confirm_password"
            name="confirm_password"
            placeholder="Confirm your password"
            required
        >

        <button type="submit">Register</button>

    </form>

    <div class="login-link">
        Already have an account?
        <a href="login.php">Login</a>
    </div>

</div>

</body>
</html>