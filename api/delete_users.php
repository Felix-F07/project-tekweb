<?php
header("Content-Type: application/json; charset=UTF-8");
include_once '../config/Database.php';

$database = new Database();
$db = $database->getConnection();

$username = isset($_POST['username']) ? $_POST['username'] : '';

if(!empty($username)) {
    $query = "DELETE FROM users WHERE username = :username";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':username', $username);
    
    if($stmt->execute()) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Gagal hapus user."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Username kosong."]);
}
?>