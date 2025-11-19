<?php
session_start();
include 'db.php';
header('Content-Type: application/json');

function respond(string $status, string $message, array $extra = []): void {
    echo json_encode(array_merge([
        "status" => $status,
        "message" => $message
    ], $extra));
    exit;
}

$action = $_GET['action'] ?? null;
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if (!$action || $method !== 'POST') {
    http_response_code(400);
    respond("error", "Invalid request");
}

$payload = json_decode(file_get_contents("php://input"), true) ?? [];

try {
    if ($action === 'register') {
        $name = trim($payload['name'] ?? '');
        $email = filter_var(trim($payload['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $password = $payload['password'] ?? '';

        if ($name === '' || !$email || strlen($password) < 6) {
            http_response_code(422);
            respond("error", "Please provide name, valid email, and a password (6+ chars).");
        }

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $email, $hashedPassword);
        $stmt->execute();

        respond("success", "User registered");
    }

    if ($action === 'login') {
        $email = filter_var(trim($payload['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $password = $payload['password'] ?? '';

        if (!$email || $password === '') {
            http_response_code(422);
            respond("error", "Please provide email and password.");
        }

        $stmt = $conn->prepare("SELECT id, name, password, income FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            http_response_code(401);
            respond("error", "User not found.");
        }

        $user = $result->fetch_assoc();
        if (!password_verify($password, $user['password'])) {
            http_response_code(401);
            respond("error", "Invalid password.");
        }

        $_SESSION['user_id'] = $user['id'];

        respond("success", "Login successful", [
            "user" => [
                "id" => (int)$user['id'],
                "name" => $user['name'],
                "income" => (float)$user['income']
            ]
        ]);
    }

    http_response_code(400);
    respond("error", "Unsupported action.");
} catch (mysqli_sql_exception $e) {
    if ($e->getCode() === 1062) {
        http_response_code(409);
        respond("error", "Email already exists.");
    }

    http_response_code(500);
    respond("error", "Server error. Please try again later.");
}
?>
