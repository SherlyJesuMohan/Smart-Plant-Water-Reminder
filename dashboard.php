
<?php
session_start();

// 1. Protect the dashboard
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

// 2. Connect to the database
require_once "config/database.php";

$userId = (int) $_SESSION["user_id"];
$userName = $_SESSION["user_name"] ?? "Plant Lover";

// Escape output before displaying it in HTML
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

// 3. Fetch only the logged-in user's plants
$sql = "SELECT id, plant_name, plant_type,
               watering_interval, last_watered
        FROM plants
        WHERE user_id = ?
        ORDER BY id DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$plants = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// 4. Success messages
$notice = "";

if (isset($_GET["plant_added"])) {
    $notice = "Plant added successfully!";
} elseif (isset($_GET["plant_updated"])) {
    $notice = "Plant updated successfully!";
} elseif (isset($_GET["plant_deleted"])) {
    $notice = "Plant deleted successfully!";
}

// 5. Calculate reminder information for each plant
$today = new DateTimeImmutable("today");

foreach ($plants as &$plant) {
    $interval = (int) $plant["watering_interval"];

    try {
        $lastWatered = new DateTimeImmutable(
            $plant["last_watered"]
        );

        $nextWatering = $lastWatered->modify(
            "+" . $interval . " days"
        );

        $plant["next_watering"] = $nextWatering->format("Y-m-d");

        if ($nextWatering < $today) {
            $daysOverdue = (int) $nextWatering
                ->diff($today)
                ->format("%a");

            $plant["status"] = "Overdue by "
                . $daysOverdue . " day(s)";
            $plant["status_class"] = "overdue";

        } elseif ($nextWatering == $today) {
            $plant["status"] = "Watering due today!";
            $plant["status_class"] = "due";

        } else {
            $daysRemaining = (int) $today
                ->diff($nextWatering)
                ->format("%a");

            $plant["status"] = "Water in "
                . $daysRemaining . " day(s)";
            $plant["status_class"] = "upcoming";
        }

    } catch (Exception $exception) {
        $plant["next_watering"] = "Unavailable";
        $plant["status"] = "Check watering date";
        $plant["status_class"] = "overdue";
    }
}
unset($plant);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard | Smart Plant Water Reminder</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f2f7f2;
            color: #263628;
        }

        header {
            background: #2e7d32;
            color: white;
            padding: 18px 6%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }

        header h1 {
            margin: 0;
            font-size: 23px;
        }

        .logout {
            display: inline-block;
            padding: 10px 16px;
            border-radius: 6px;
            background: #1b5e20;
            color: white;
            text-decoration: none;
        }

        main {
            max-width: 1100px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .panel {
            background: white;
            padding: 25px;
            margin-bottom: 24px;
            border-radius: 12px;
            box-shadow: 0 3px 12px #0000000d;
        }

        .welcome h2 {
            margin-top: 0;
            color: #245d2a;
        }

        .section-heading {
            color: #2e7d32;
            margin-top: 0;
        }

        .notice {
            padding: 13px 16px;
            background: #e3f5e5;
            color: #1b5e20;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .add-button,
        .edit-button,
        .delete-button {
            display: inline-block;
            border: none;
            border-radius: 5px;
            padding: 10px 14px;
            font-size: 14px;
            text-decoration: none;
            cursor: pointer;
        }

        .add-button {
            background: #2e7d32;
            color: white;
            margin-bottom: 20px;
        }

        .edit-button {
            background: #e5f0ff;
            color: #174ea6;
        }

        .delete-button {
            background: #ffebee;
            color: #b71c1c;
        }

        .plant-grid {
            display: grid;
            grid-template-columns: repeat(
                auto-fit, minmax(250px, 1fr)
            );
            gap: 20px;
        }

        .plant-card {
            border: 1px solid #e0e8e0;
            border-radius: 10px;
            padding: 20px;
            background: #fff;
            min-width: 0;
        }

        .plant-card h3 {
            margin-top: 0;
            color: #2e7d32;
            overflow-wrap: anywhere;
        }

        .plant-card p {
            line-height: 1.5;
            overflow-wrap: anywhere;
        }

        .reminder {
            padding: 11px;
            border-radius: 6px;
            background: #f5f5f5;
            font-weight: bold;
        }

        .due {
            color: #9a6700;
            background: #fff4cc;
        }

        .overdue {
            color: #b71c1c;
            background: #ffebee;
        }

        .upcoming {
            color: #1b5e20;
            background: #e8f5e9;
        }

        .plant-actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
            margin-top: 16px;
        }

        .plant-actions form {
            margin: 0;
        }

        .empty-state {
            padding: 25px;
            text-align: center;
            background: #f7faf7;
            border-radius: 8px;
        }

        footer {
            text-align: center;
            padding: 20px;
            color: #667366;
        }

        @media (max-width: 480px) {
            header h1 {
                font-size: 19px;
            }

            .panel {
                padding: 18px;
            }
        }

        
.reminder-button {
    display: inline-block;
    padding: 12px 20px;
    background-color: #2e7d32;
    color: white;
    text-decoration: none;
    font-size: 16px;
    font-weight: bold;
    border-radius: 8px;
    transition: background-color 0.3s ease;
}

.reminder-button:hover {
    background-color: #1b5e20;
}

    </style>
</head>

<body>

<header>
    <h1>🌿 Smart Plant Water Reminder</h1>
    <a class="logout" href="logout.php">Logout</a>
</header>

<main>

    <section class="panel welcome">
        <h2>
            Welcome, <?= e($userName) ?>!
        </h2>

        <p>Welcome to your plant care dashboard.</p>

        <p>
            Keep your plants healthy with timely watering reminders.
        </p>
    </section>

    
<p>
    <a href="reminders.php" class="reminder-button">
        💧 Manage Watering Reminders
    </a>
</p>


    <?php if ($notice !== ""): ?>
        <div class="notice" role="status">
            <?= e($notice) ?>
        </div>
    <?php endif; ?>

    <section class="panel">
        <h2 class="section-heading">🪴 My Plants</h2>

        <a class="add-button" href="add_plant.php">
            + Add New Plant
        </a>

        <?php if (count($plants) > 0): ?>

            <div class="plant-grid">

                <?php foreach ($plants as $plant): ?>

                    <article class="plant-card">

                        <h3>
                            🌱 <?= e($plant["plant_name"]) ?>
                        </h3>

                        <p>
                            <strong>Type:</strong>
                            <?= e($plant["plant_type"]) ?>
                        </p>

                        <p>
                            <strong>Watering interval:</strong>
                            <?= (int) $plant["watering_interval"] ?>
                            day(s)
                        </p>

                        <p>
                            <strong>Last watered:</strong>
                            <?= e($plant["last_watered"]) ?>
                        </p>

                        <p>
                            <strong>Next watering:</strong>
                            <?= e($plant["next_watering"]) ?>
                        </p>

                        <p class="reminder <?= e($plant["status_class"]) ?>">
                            💧 <?= e($plant["status"]) ?>
                        </p>

                        <div class="plant-actions">

                            <a
                                class="edit-button"
                                href="edit_plant.php?id=<?= (int) $plant["id"] ?>"
                            >
                                Edit Plant
                            </a>

                            <form
                                method="POST"
                                action="delete_plant.php"
                                onsubmit="return confirm('Are you sure you want to delete this plant?');"
                            >
                                <input
                                    type="hidden"
                                    name="id"
                                    value="<?= (int) $plant["id"] ?>"
                                >

                                <button
                                    type="submit"
                                    class="delete-button"
                                >
                                    Delete Plant
                                </button>
                            </form>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <div class="empty-state">
                <h3>No plants added yet 🌱</h3>
                <p>Add your first plant to start tracking watering dates.</p>
            </div>

        <?php endif; ?>
    </section>

</main>

<footer>
    Smart Plant Water Reminder &copy; <?= date("Y") ?>
</footer>

</body>
</html>
