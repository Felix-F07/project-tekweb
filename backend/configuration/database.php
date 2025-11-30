<?php
// Basic CORS + JSON headers for API responses
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight requests quickly
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$host = "localhost";
$db_name = "warnet_db";
$username = "root";
$password = ""; // Sesuaikan dengan password database kamu

try {
    $conn = new PDO("mysql:host=" . $host . ";dbname=" . $db_name, $username, $password);
    $conn->exec("set names utf8");
} catch(PDOException $exception) {
    echo json_encode(["message" => "Connection error: " . $exception->getMessage()]);
    exit;
}
?>