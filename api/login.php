<?php
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/Database.php';
include_once '../classes/User.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

$username = isset($_POST['username']) ? $_POST['username'] : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

$result = $user->login($username, $password);

if($result) {
    echo json_encode(["status" => "success", "data" => $result]);
} else {
    echo json_encode(["status" => "error", "message" => "Username atau Password Salah!"]);
}
?>