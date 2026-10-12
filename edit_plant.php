
<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once "config/database.php";

$userId = (int) $_SESSION["user_id"];
$message = "";

$plantId = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $plantId = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
    $plantName = trim($_POST["plant_name"] ?? "");
    $plantType = trim($_POST["plant_type"] ?? "");
    $wateringInterval = filter_input(
        INPUT_POST,
        "watering_interval",
        FILTER_VALIDATE_INT
    );
    $lastWatered = $_POST["last_watered"] ?? "";

    if (
        !$plantId ||
        $plantName === "" ||
        $plantType === "" ||
        strlen($plantName) > 100 ||
        strlen($plantType) > 100 ||
        $wateringInterval === false ||
        $wateringInterval === null ||
        $wateringInterval < 1 ||
        $wateringInterval > 365
    ) {
        $message = "Please enter valid details in all fields.";
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
            $message = "Please enter a valid last-watered date.";
        } else {
            $sql = "UPDATE plants
                    SET plant_name = ?, plant_type = ?,
                        watering_interval = ?, last_watered = ?
                    WHERE id = ? AND user_id = ?";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "ssisii",
                $plantName,
                $plantType,
                $wateringInterval,
                $lastWatered,
                $plantId,
                $userId
            );

            if ($stmt->execute() && $stmt->affected_rows >= 0) {
                $stmt->close();
                header("Location: dashboard.php?plant_updated=1");
                exit;
            }

            $message = "Unable to update the plant. Please try again.";
            $stmt->close();
        }
    }
}

// Load the selected plant, restricted to its owner.
if ($_SERVER["REQUEST_METHOD"] !== "POST" || $message === "") {
    if (!$plantId) {
        http_response_code(400);
        exit("Invalid plant ID.");
    }

    $stmt = $conn->prepare(
        "SELECT id, plant_name, plant_type, watering_interval, last_watered
         FROM plants WHERE id = ? AND user_id = ?"
    );
    $stmt->bind_param("ii", $plantId, $userId);
    $stmt->execute();
    $plant = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$plant) {
        http_response_code(404);
        exit("Plant not found.");
    }
} else {
    // Preserve submitted values when validation fails.
    $plant = [
        "id" => $plantId,
        "plant_name" => $plantName,
        "plant_type" => $plantType,
        "watering_interval" => $wateringInterval,
        "last_watered" => $lastWatered
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Plant</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f2f7f2;
            padding: 20px;
        }
        .container {
            max-width: 450px;
            margin: 40px auto;
            padding: 28px;
            background: white;
            border-radius: 10px;
        }
        label {
            display: block;
            margin: 16px 0 6px;
        }
        input {
            box-sizing: border-box;
            width: 100%;
            padding: 11px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        button {
            width: 100%;
            padding: 12px;
            margin-top: 22px;
            border: 0;
            border-radius: 5px;
            background: #2e7d32;
            color: white;
            cursor: pointer;
        }
        .error { color: #b00020; }
    </style>
</head>
<body>
<div class="container">
    <h2>🌿 Edit Plant</h2>

    <?php if ($message !== ""): ?>
        <p class="error">
            <?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?>
        </p>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="id"
               value="<?= (int) $plant["id"] ?>">

        <label for="plant_name">Plant Name</label>
        <input type="text" id="plant_name" name="plant_name"
               maxlength="100" required
               value="<?= htmlspecialchars($plant["plant_name"], ENT_QUOTES, "UTF-8") ?>">

        <label for="plant_type">Plant Type</label>
        <input type="text" id="plant_type" name="plant_type"
               maxlength="100" required
               value="<?= htmlspecialchars($plant["plant_type"], ENT_QUOTES, "UTF-8") ?>">

        <label for="watering_interval">Watering Interval (days)</label>
        <input type="number" id="watering_interval"
               name="watering_interval" min="1" max="365" required
               value="<?= (int) $plant["watering_interval"] ?>">

        <label for="last_watered">Last Watered</label>
        <input type="date" id="last_watered" name="last_watered"
               max="<?= date("Y-m-d") ?>" required
               value="<?= htmlspecialchars($plant["last_watered"], ENT_QUOTES, "UTF-8") ?>">

        <button type="submit">Update Plant</button>
    </form>

    <p><a href="dashboard.php">Back to Dashboard</a></p>
</div>
</body>
</html>
