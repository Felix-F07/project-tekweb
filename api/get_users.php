<?php
header("Content-Type: application/json; charset=UTF-8");
include_once '../config/Database.php';

$database = new Database();
$db = $database->getConnection();

// Ambil semua user yang BUKAN Admin
$query = "SELECT u.id, u.username, u.billing_seconds, r.name as role_name 
          FROM users u 
          JOIN roles r ON u.role_id = r.id 
          WHERE r.name != 'Admin' 
          ORDER BY u.username ASC";

$stmt = $db->prepare($query);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(["status" => "success", "data" => $users]);
?>