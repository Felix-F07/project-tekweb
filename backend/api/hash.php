<?php


header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data || !isset($data['password'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing password']);
    exit;
}

$pw = $data['password'];
$hash = password_hash($pw, PASSWORD_BCRYPT);

echo json_encode(['status' => 'success', 'hash' => $hash]);

?>
