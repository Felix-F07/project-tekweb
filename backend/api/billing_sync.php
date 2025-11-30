<?php
// billing_sync.php - Sinkronisasi billing ke database
header('Content-Type: application/json');

include_once '../configuration/database.php';

// Ambil input JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Invalid JSON payload"]);
    exit;
}

$user_id = isset($input['user_id']) ? intval($input['user_id']) : 0;
$billing_seconds = isset($input['billing_seconds']) ? intval($input['billing_seconds']) : null;

if ($user_id <= 0 || $billing_seconds === null || $billing_seconds < 0) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Missing or invalid user_id or billing_seconds"]);
    exit;
}

try {
    // Update billing_seconds di database (gunakan $conn, bukan $db)
    $sql = "UPDATE users SET billing_seconds = :billing_seconds WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':billing_seconds', $billing_seconds, PDO::PARAM_INT);
    $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
    $result = $stmt->execute();

    if ($result) {
        echo json_encode([
            "success" => true, 
            "message" => "Billing synced successfully",
            "user_id" => $user_id,
            "billing_seconds" => $billing_seconds
        ]);
    } else {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Failed to update billing"]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Server error",
        "error" => $e->getMessage()
    ]);
}
?>

