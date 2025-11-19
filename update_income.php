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
$income = isset($data['income']) ? (float)$data['income'] : null;

if ($income === null || $income < 0) {
    http_response_code(422);
    echo json_encode(["status" => "error", "message" => "Invalid income amount"]);
    exit;
}

try {
    $stmt = $conn->prepare("UPDATE users SET income = ? WHERE id = ?");
    $stmt->bind_param("di", $income, $_SESSION['user_id']);
    $stmt->execute();
    
    if ($stmt->affected_rows === 0) {
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "User not found"]);
        exit;
    }
    
    echo json_encode(["status" => "success", "message" => "Income updated", "income" => $income]);
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Failed to update income"]);
}
?>
