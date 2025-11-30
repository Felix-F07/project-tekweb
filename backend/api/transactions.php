<?php
include_once '../configuration/database.php';
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $user_id = $_GET['user_id'];
    $query = "SELECT t.*, p.name as paket_name 
              FROM transactions t 
              LEFT JOIN paket_billing p ON t.paket_id = p.id 
              WHERE t.user_id = :uid 
              ORDER BY t.created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(":uid", $user_id);
    $stmt->execute();
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
}
?>