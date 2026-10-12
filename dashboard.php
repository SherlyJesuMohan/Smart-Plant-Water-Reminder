
<?php
session_start();

// Protect the dashboard: only logged-in users can access it.
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$userName = $_SESSION["user_name"] ?? "Plant Lover";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Smart Plant Water Reminder</title>

    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f2f7f2;
            color: #263628;
        }

        header {
            background: #2e7d32;
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        header h1 {
            margin: 0;
            font-size: 22px;
        }

        .logout {
            color: white;
            text-decoration: none;
            background: #1b5e20;
            padding: 10px 15px;
            border-radius: 5px;
        }

        main {
            max-width: 900px;
            margin: 35px auto;
            padding: 0 20px;
        }

        .welcome, .feature {
            background: white;
            padding: 25px;
            margin-bottom: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px #0000000d;
        }

        .feature h3 {
            color: #2e7d32;
        }
    </style>
</head>

<body>
    <header>
        <h1>🌿 Smart Plant Water Reminder</h1>
        <a class="logout" href="logout.php">Logout</a>
    </header>

    <main>
        <section class="welcome">
            <h2>
                Welcome, <?= htmlspecialchars($userName, ENT_QUOTES, "UTF-8") ?>!
            </h2>
            <p>Welcome to your plant care dashboard.</p>
            <p>Keep your plants healthy with timely watering reminders.</p>
        </section>

        <section class="feature">
            <h3>🪴 My Plants</h3>
            <p>Your plants will appear here once plant management is implemented.</p>
        </section>

        <section class="feature">
            <h3>💧 Watering Reminders</h3>
            <p>Your upcoming watering reminders will appear here.</p>
        </section>
    </main>
</body>
</html>
