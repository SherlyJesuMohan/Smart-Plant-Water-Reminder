
<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once "config/database.php";

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $plantName = trim($_POST["plant_name"] ?? "");
    $plantType = trim($_POST["plant_type"] ?? "");
    $wateringInterval = filter_input(
        INPUT_POST,
        "watering_interval",
        FILTER_VALIDATE_INT
    );
    $lastWatered = $_POST["last_watered"] ?? "";

    if (
        $plantName === "" ||
        $plantType === "" ||
        $wateringInterval === false ||
        $wateringInterval === null ||
        $wateringInterval < 1 ||
        $wateringInterval > 365 ||
        $lastWatered === ""
    ) {
        $message = "Please enter valid details in all fields.";
        $messageType = "error";
    } else {
        $date = DateTime::createFromFormat("!Y-m-d", $lastWatered);
        $dateErrors = DateTime::getLastErrors();

        if (
            !$date ||
            $date->format("Y-m-d") !== $lastWatered ||
            ($dateErrors !== false &&
                ($dateErrors["warning_count"] > 0 ||
                 $dateErrors["error_count"] > 0)) ||
            $lastWatered > date("Y-m-d")
        ) {
            $message = "Please enter a valid date that is not in the future.";
            $messageType = "error";
        } else {
            $userId = (int) $_SESSION["user_id"];

            $sql = "INSERT INTO plants
                    (user_id, plant_name, plant_type,
                     watering_interval, last_watered)
                    VALUES (?, ?, ?, ?, ?)";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "issis",
                $userId,
                $plantName,
                $plantType,
                $wateringInterval,
                $lastWatered
            );

            if ($stmt->execute()) {
                $stmt->close();
                header("Location: dashboard.php?plant_added=1");
                exit;
            }

            $message = "Unable to save the plant. Please try again.";
            $messageType = "error";
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Plant - Smart Plant Water Reminder</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f2f7f2;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 450px;
            margin: 40px auto;
            padding: 28px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px #00000012;
        }

        h2 {
            text-align: center;
            color: #2e7d32;
        }

        label {
            display: block;
            margin-top: 16px;
            margin-bottom: 6px;
        }

        input, select {
            box-sizing: border-box;
            width: 100%;
            padding: 11px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        button {
            width: 100%;
            margin-top: 22px;
            padding: 12px;
            border: 0;
            border-radius: 5px;
            background: #2e7d32;
            color: white;
            cursor: pointer;
            font-size: 16px;
        }

        .error {
            color: #b00020;
            text-align: center;
        }

        .back {
            display: block;
            margin-top: 18px;
            text-align: center;
        }
    </style>
</head>

<body>
<div class="container">
    <h2>🌱 Add a New Plant</h2>

    <?php if ($message !== ""): ?>
        <p class="error">
            <?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?>
        </p>
    <?php endif; ?>

    <form method="POST">
        <label for="plant_name">Plant Name</label>
        <input
            type="text"
            id="plant_name"
            name="plant_name"
            maxlength="100"
            required
            value="<?= htmlspecialchars($_POST["plant_name"] ?? "", ENT_QUOTES, "UTF-8") ?>"
        >

        <label for="plant_type">Plant Type</label>
        <input
            type="text"
            id="plant_type"
            name="plant_type"
            maxlength="100"
            required
            value="<?= htmlspecialchars($_POST["plant_type"] ?? "", ENT_QUOTES, "UTF-8") ?>"
        >

        <label for="watering_interval">Watering Interval (days)</label>
        <input
            type="number"
            id="watering_interval"
            name="watering_interval"
            min="1"
            max="365"
            required
            value="<?= htmlspecialchars($_POST["watering_interval"] ?? "3", ENT_QUOTES, "UTF-8") ?>"
        >

        <label for="last_watered">Last Watered Date</label>
        <input
            type="date"
            id="last_watered"
            name="last_watered"
            max="<?= date("Y-m-d") ?>"
            required
            value="<?= htmlspecialchars($_POST["last_watered"] ?? date("Y-m-d"), ENT_QUOTES, "UTF-8") ?>"
        >

        <button type="submit">Save Plant</button>
    </form>

    <a class="back" href="dashboard.php">Back to Dashboard</a>
</div>
</body>
</html>
