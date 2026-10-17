
<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once "config/database.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit("Method not allowed.");
}

$plantId = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
$userId = (int) $_SESSION["user_id"];

if (!$plantId || $plantId < 1) {
    http_response_code(400);
    exit("Invalid plant ID.");
}

$sql = "DELETE FROM plants WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $plantId, $userId);
$stmt->execute();

if ($stmt->affected_rows === 1) {
    $stmt->close();
    header("Location: dashboard.php?plant_deleted=1");
    exit;
}

$stmt->close();
http_response_code(404);
echo "Plant not found or you don't have permission to delete it.";
