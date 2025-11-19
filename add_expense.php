<?php
session_start();
include 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true) ?? [];

$amount = isset($data['amount']) ? (float)$data['amount'] : null;
$category = trim($data['category'] ?? '');
$date = $data['date'] ?? null;
$description = trim($data['description'] ?? '');
$recurring = !empty($data['recurring']) ? 1 : 0;

if ($amount === null || $amount <= 0 || !$category || !$date) {
    http_response_code(422);
    echo json_encode(["status" => "error", "message" => "Amount, category, and date are required."]);
    exit;
}

try {
    $stmt = $conn->prepare("
        INSERT INTO expenses (user_id, amount, category, description, date, recurring)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("idsssi", $_SESSION['user_id'], $amount, $category, $description, $date, $recurring);
    $stmt->execute();

    echo json_encode(["status" => "success", "message" => "Expense added"]);
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Failed to add expense"]);
}
?>
