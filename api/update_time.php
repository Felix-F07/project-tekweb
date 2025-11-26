<?php
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/Database.php';
include_once '../classes/User.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

$username = isset($_POST['username']) ? $_POST['username'] : '';
$seconds = isset($_POST['seconds']) ? $_POST['seconds'] : 60; // Default lapor 60 detik

if(!empty($username)) {
    if($user->decreaseBilling($username, $seconds)) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Gagal update"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Username kosong"]);
}
?>