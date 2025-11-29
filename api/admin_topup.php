<?php
header("Content-Type: application/json; charset=UTF-8");
include_once '../config/Database.php';
include_once '../classes/User.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

$username = isset($_POST['username']) ? $_POST['username'] : '';
$minutes = isset($_POST['minutes']) ? intval($_POST['minutes']) : 0;

if(!empty($username) && $minutes > 0) {
    $seconds = $minutes * 60; // Ubah menit ke detik
    
    // Pakai fungsi topUpBilling yang sudah ada
    if($user->topUpBilling($username, $seconds)) {
        echo json_encode(["status" => "success", "message" => "Berhasil tambah $minutes menit ke $username"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Gagal update database."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Data tidak lengkap."]);
}
?>