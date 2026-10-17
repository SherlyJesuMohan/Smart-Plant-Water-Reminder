
<?php
session_start();

// 1. Protect the page
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

// 2. Database connection
require_once "config/database.php";

$userId = (int) $_SESSION["user_id"];
$message = "";
$messageType = "success";

// Escape output safely
function e($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        "UTF-8"
    );
}

// 3. Handle form submissions
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    // Mark a reminder as completed
    if ($action === "complete") {
        $reminderId = filter_input(
            INPUT_POST,
            "reminder_id",
            FILTER_VALIDATE_INT
        );

        if (!$reminderId || $reminderId < 1) {
            $message = "Invalid reminder selected.";
            $messageType = "error";
        } else {
            $sql = "UPDATE watering_reminders AS wr
                    INNER JOIN plants AS p
                        ON p.id = wr.plant_id
                    SET wr.status = 'completed'
                    WHERE wr.id = ?
                      AND p.user_id = ?
                      AND wr.status = 'pending'";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $reminderId, $userId);
            $stmt->execute();

            if ($stmt->affected_rows === 1) {
                $stmt->close();
                header("Location: reminders.php?completed=1");
                exit;
            }

            $stmt->close();
            $message = "Reminder could not be completed. It may already be completed.";
            $messageType = "error";
        }
    }

    // Create a new reminder
    elseif ($action === "create") {
        $plantId = filter_input(
            INPUT_POST,
            "plant_id",
            FILTER_VALIDATE_INT
        );

        $reminderDate = trim(
            $_POST["reminder_date"] ?? ""
        );

        $date = DateTimeImmutable::createFromFormat(
            "!Y-m-d",
            $reminderDate
        );

        $dateErrors = DateTimeImmutable::getLastErrors();

        $validDate = $date !== false
            && $date->format("Y-m-d") === $reminderDate
            && (
                $dateErrors === false ||
                (
                    $dateErrors["warning_count"] === 0 &&
                    $dateErrors["error_count"] === 0
                )
            );

        if (
            !$plantId ||
            $plantId < 1 ||
            !$validDate ||
            $reminderDate < date("Y-m-d")
        ) {
            $message = "Please select a plant and a valid date today or later.";
            $messageType = "error";
        } else {
            // Ensure the plant belongs to this user
            $check = $conn->prepare(
                "SELECT id FROM plants
                 WHERE id = ? AND user_id = ?"
            );

            $check->bind_param("ii", $plantId, $userId);
            $check->execute();

            $ownedPlant = $check->get_result()->fetch_assoc();
            $check->close();

            if (!$ownedPlant) {
                $message = "Plant not found or access denied.";
                $messageType = "error";
            } else {
                // Check for a duplicate reminder
                $check = $conn->prepare(
                    "SELECT id FROM watering_reminders
                     WHERE plant_id = ? AND reminder_date = ?"
                );

                $check->bind_param(
                    "is",
                    $plantId,
                    $reminderDate
                );

                $check->execute();
                $duplicate = $check->get_result()->fetch_assoc();
                $check->close();

                if ($duplicate) {
                    $message = "A reminder already exists for this plant and date.";
                    $messageType = "error";
                } else {
                    $insert = $conn->prepare(
                        "INSERT INTO watering_reminders
                         (plant_id, reminder_date, status)
                         VALUES (?, ?, 'pending')"
                    );

                    $insert->bind_param(
                        "is",
                        $plantId,
                        $reminderDate
                    );

                    if ($insert->execute()) {
                        $insert->close();
                        header("Location: reminders.php?created=1");
                        exit;
                    }

                    $insert->close();
                    $message = "Unable to create the reminder.";
                    $messageType = "error";
                }
            }
        }
    } else {
        $message = "Invalid request.";
        $messageType = "error";
    }
}

// 4. Success messages after redirect
if (isset($_GET["created"])) {
    $message = "Watering reminder created successfully!";
    $messageType = "success";
} elseif (isset($_GET["completed"])) {
    $message = "Reminder marked as completed!";
    $messageType = "success";
}

// 5. Fetch plants belonging to this user
$stmt = $conn->prepare(
    "SELECT id, plant_name
     FROM plants
     WHERE user_id = ?
     ORDER BY plant_name"
);

$stmt->bind_param("i", $userId);
$stmt->execute();

$plants = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// 6. Fetch this user's reminders only
$sql = "SELECT
            wr.id,
            wr.plant_id,
            wr.reminder_date,
            wr.status,
            p.plant_name
        FROM watering_reminders AS wr
        INNER JOIN plants AS p
            ON p.id = wr.plant_id
        WHERE p.user_id = ?
        ORDER BY
            CASE WHEN wr.status = 'pending' THEN 0 ELSE 1 END,
            wr.reminder_date ASC,
            wr.id DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();

$reminders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Watering Reminders | Smart Plant Water Reminder</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 20px;
            font-family: Arial, sans-serif;
            background: #f2f7f2;
            color: #263628;
        }

        main {
            max-width: 900px;
            margin: 25px auto;
        }

        .panel {
            background: white;
            padding: 25px;
            margin-bottom: 22px;
            border-radius: 12px;
            box-shadow: 0 3px 12px #0000000d;
        }

        h1, h2 {
            color: #2e7d32;
        }

        label {
            display: block;
            margin: 16px 0 7px;
            font-weight: bold;
        }

        select, input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 15px;
        }

        button {
            padding: 12px 18px;
            border: none;
            border-radius: 6px;
            font-size: 15px;
            cursor: pointer;
        }

        .primary-button {
            width: 100%;
            margin-top: 20px;
            background: #2e7d32;
            color: white;
        }

        .primary-button:hover {
            background: #1b5e20;
        }

        .complete-button {
            background: #2e7d32;
            color: white;
        }

        .complete-button:hover {
            background: #1b5e20;
        }

        .back-button {
            display: inline-block;
            padding: 10px 16px;
            background: #e8f5e9;
            color: #1b5e20;
            text-decoration: none;
            border-radius: 6px;
        }

        .message {
            padding: 13px;
            border-radius: 6px;
            margin-bottom: 18px;
        }

        .success {
            background: #e8f5e9;
            color: #1b5e20;
        }

        .error {
            background: #ffebee;
            color: #b71c1c;
        }

        .reminder-card {
            padding: 20px 0;
            border-bottom: 1px solid #e0e8e0;
        }

        .reminder-card:last-child {
            border-bottom: none;
        }

        .reminder-card h3 {
            margin-top: 0;
            color: #2e7d32;
        }

        .status {
            display: inline-block;
            padding: 7px 12px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 13px;
        }

        .pending {
            color: #946200;
            background: #fff4cc;
        }

        .completed {
            color: #1b5e20;
            background: #e8f5e9;
        }

        .overdue {
            color: #b71c1c;
            background: #ffebee;
        }

        .empty-state {
            padding: 25px;
            text-align: center;
            background: #f7faf7;
            border-radius: 8px;
        }

        a {
            color: #246b2b;
        }

        @media (max-width: 480px) {
            .panel {
                padding: 18px;
            }
        }
    </style>
</head>

<body>
<main>

    <section class="panel">
        <h1>💧 Watering Reminders</h1>

        <p>Keep track of when your plants need water.</p>

        <a class="back-button" href="dashboard.php">
            ← Back to Dashboard
        </a>
    </section>

    <?php if ($message !== ""): ?>
        <div class="message <?= e($messageType) ?>" role="status">
            <?= e($message) ?>
        </div>
    <?php endif; ?>

    <section class="panel">
        <h2>➕ Create a Reminder</h2>

        <?php if (count($plants) > 0): ?>

            <form method="POST" action="reminders.php">
                <input type="hidden" name="action" value="create">

                <label for="plant_id">Select Plant</label>

                <select id="plant_id" name="plant_id" required>
                    <option value="">Choose a plant</option>

                    <?php foreach ($plants as $plant): ?>
                        <option value="<?= (int) $plant["id"] ?>">
                            <?= e($plant["plant_name"]) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="reminder_date">Reminder Date</label>

                <input
                    type="date"
                    id="reminder_date"
                    name="reminder_date"
                    min="<?= date("Y-m-d") ?>"
                    required
                >

                <button class="primary-button" type="submit">
                    Create Reminder
                </button>
            </form>

        <?php else: ?>

            <div class="empty-state">
                <p>You haven't added any plants yet.</p>
                <a href="add_plant.php">Add your first plant</a>
            </div>

        <?php endif; ?>
    </section>

    <section class="panel">
        <h2>📅 My Reminders</h2>

        <?php if (count($reminders) > 0): ?>

            <?php foreach ($reminders as $reminder): ?>

                <?php
                $status = strtolower(
                    $reminder["status"] ?? "pending"
                );

                $date = $reminder["reminder_date"];
                $isCompleted = ($status === "completed");

                if ($isCompleted) {
                    $displayStatus = "Completed";
                    $statusClass = "completed";
                } elseif (
                    $status === "overdue" ||
                    ($status === "pending" && $date < date("Y-m-d"))
                ) {
                    $displayStatus = "Overdue";
                    $statusClass = "overdue";
                } else {
                    $displayStatus = "Pending";
                    $statusClass = "pending";
                }
                ?>

                <article class="reminder-card">
                    <h3>
                        🌱 <?= e($reminder["plant_name"]) ?>
                    </h3>

                    <p>
                        <strong>Reminder date:</strong>
                        <?= e($date) ?>
                    </p>

                    <p>
                        <strong>Status:</strong>
                        <span class="status <?= e($statusClass) ?>">
                            <?= e($displayStatus) ?>
                        </span>
                    </p>

                    <?php if (
                        $status === "pending" &&
                        $date >= date("Y-m-d")
                    ): ?>

                        <form method="POST" action="reminders.php">
                            <input
                                type="hidden"
                                name="action"
                                value="complete"
                            >

                            <input
                                type="hidden"
                                name="reminder_id"
                                value="<?= (int) $reminder["id"] ?>"
                            >

                            <button
                                class="complete-button"
                                type="submit"
                            >
                                ✓ Mark as Completed
                            </button>
                        </form>

                    <?php endif; ?>
                </article>

            <?php endforeach; ?>

        <?php else: ?>

            <div class="empty-state">
                <p>No watering reminders yet.</p>
                <p>Create a reminder above to get started!</p>
            </div>

        <?php endif; ?>
    </section>

</main>
</body>
</html>
