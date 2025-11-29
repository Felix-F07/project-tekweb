<?php
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/Database.php';

$database = new Database();
$db = $database->getConnection();

try {
    $query = "SELECT id, name as paket_name, price, seconds, description FROM paket_billing ORDER BY seconds ASC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["status" => "success", "data" => $rows]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Gagal memuat paket."]);
}

?>
