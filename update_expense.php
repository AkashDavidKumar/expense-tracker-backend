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

$id = isset($data['id']) ? (int)$data['id'] : 0;
$amount = isset($data['amount']) ? (float)$data['amount'] : null;
$category = trim($data['category'] ?? '');
$date = $data['date'] ?? null;
$description = trim($data['description'] ?? '');
$recurring = !empty($data['recurring']) ? 1 : 0;

if ($id <= 0 || $amount === null || $amount <= 0 || !$category || !$date) {
    http_response_code(422);
    echo json_encode(["status" => "error", "message" => "All fields are required."]);
    exit;
}

try {
    $stmt = $conn->prepare("
        UPDATE expenses
        SET amount = ?, category = ?, date = ?, description = ?, recurring = ?
        WHERE id = ? AND user_id = ?
    ");
    $stmt->bind_param("dsssiii", $amount, $category, $date, $description, $recurring, $id, $_SESSION['user_id']);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "Expense not found"]);
        exit;
    }

    echo json_encode(["status" => "success", "message" => "Expense updated"]);
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Update failed"]);
}
?>
