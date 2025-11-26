<?php
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/Database.php';
include_once '../classes/User.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

// Terima data dari Script.js
$username = isset($_POST['username']) ? $_POST['username'] : '';
$seconds = isset($_POST['seconds']) ? $_POST['seconds'] : 0;

if(!empty($username) && !empty($seconds)) {
    
    // Jalankan Top Up
    if($user->topUpBilling($username, $seconds)) {
        
        // Ambil data waktu terbaru setelah ditambah
        $sisa_baru = $user->getBilling($username);
        
        echo json_encode([
            "status" => "success", 
            "message" => "Pembelian Berhasil!",
            "new_time" => $sisa_baru
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Gagal update database."]);
    }

} else {
    echo json_encode(["status" => "error", "message" => "Data tidak lengkap."]);
}
?>