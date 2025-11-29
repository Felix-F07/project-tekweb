<?php
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/Database.php';

$database = new Database();
$db = $database->getConnection();

$username = isset($_POST['username']) ? $_POST['username'] : '';

if(empty($username)) {
    echo json_encode(["status" => "error", "message" => "Username required."]);
    exit;
}

try {
    // Ambil riwayat dari tabel transactions, join ke paket_billing jika tersedia
    $query = "SELECT t.id, COALESCE(p.name, t.paket_id, '') AS paket_name, COALESCE(p.price, t.billing_amount) AS price, p.description AS paket_description, t.seconds_added, t.created_at "
           . "FROM transactions t "
           . "LEFT JOIN paket_billing p ON t.paket_id = p.id "
           . "LEFT JOIN users u ON t.user_id = u.id "
           . "WHERE u.username = :username "
           . "ORDER BY t.created_at DESC LIMIT 50";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if($rows && count($rows) > 0) {
        echo json_encode(["status" => "success", "data" => $rows]);
    } else {
        // Fallback: jika tidak ada di transactions, coba ambil dari purchases (backward compatibility)
        $query2 = "SELECT paket_name, price, seconds_added, created_at, NULL AS paket_description FROM purchases WHERE username = :username ORDER BY created_at DESC LIMIT 50";
        $stmt2 = $db->prepare($query2);
        $stmt2->bindParam(':username', $username);
        $stmt2->execute();
        $rows2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        if($rows2 && count($rows2) > 0) {
            echo json_encode(["status" => "success", "data" => $rows2]);
        } else {
            echo json_encode(["status" => "error", "message" => "No history found."]);
        }
    }

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Server error."]);
}
?>
