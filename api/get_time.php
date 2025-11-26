<?php
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/Database.php';
include_once '../classes/User.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

$username = isset($_POST['username']) ? $_POST['username'] : '';

if(!empty($username)) {
    // Ambil waktu terbaru dari Database
    $waktu_terbaru = $user->getBilling($username);
    
    echo json_encode([
        "status" => "success",
        "billing_seconds" => $waktu_terbaru
    ]);
} else {
    echo json_encode(["status" => "error", "message" => "Username kosong"]);
}
?>