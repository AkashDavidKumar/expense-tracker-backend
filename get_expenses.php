<?php
session_start();
include 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

try {
    $stmt = $conn->prepare("
        SELECT id, amount, category, description, date, recurring, created_at
        FROM expenses
        WHERE user_id = ?
        ORDER BY date DESC, created_at DESC
    ");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    $expenses = [];
    while ($row = $result->fetch_assoc()) {
        $row['amount'] = (float)$row['amount'];
        $row['recurring'] = (bool)$row['recurring'];
        $expenses[] = $row;
    }

    echo json_encode($expenses);
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Failed to load expenses"]);
}
?>
