<?php
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/Database.php';

$database = new Database();
$db = $database->getConnection();

try {
    // Ambil semua paket yang tersedia dari database
    // Kita ganti nama kolom biar sesuai sama javascript temanmu (id, name, price, description)
    $query = "SELECT id, name AS paket_name, price, seconds, description FROM paket_billing ORDER BY price ASC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $num = $stmt->rowCount();

    if($num > 0) {
        $products_arr = array();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
            array_push($products_arr, $row);
        }
        // Kirim data JSON
        echo json_encode(["status" => "success", "data" => $products_arr]);
    } else {
        echo json_encode(["status" => "error", "message" => "Tidak ada paket ditemukan."]);
    }
} catch(PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}
?>