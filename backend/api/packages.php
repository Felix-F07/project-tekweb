<?php
include_once '../configuration/database.php';
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        $query = "SELECT * FROM paket_billing WHERE deleted_at IS NULL";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (PDOException $e) {
        if ($e->getCode() === '42S22') {
            $q2 = "SELECT * FROM paket_billing";
            $s2 = $conn->prepare($q2);
            $s2->execute();
            echo json_encode($s2->fetchAll(PDO::FETCH_ASSOC));
        } else {
            throw $e;
        }
    }
}

if ($method === 'POST') {
    if(isset($_GET['action']) && $_GET['action'] == 'buy') {
        $data = json_decode(file_get_contents("php://input"));
        $qTrans = "INSERT INTO transactions (user_id, paket_id, billing_amount, seconds_added) VALUES (:uid, :pid, :amount, :sec)";
        $stmt = $conn->prepare($qTrans);
        $stmt->bindParam(":uid", $data->user_id);
        $stmt->bindParam(":pid", $data->paket_id);
        $stmt->bindParam(":amount", $data->price);
        $stmt->bindParam(":sec", $data->seconds);
        $stmt->execute();
        
        $qUser = "UPDATE users SET billing_seconds = billing_seconds + :sec WHERE id = :uid";
        $stmtU = $conn->prepare($qUser);
        $stmtU->bindParam(":sec", $data->seconds);
        $stmtU->bindParam(":uid", $data->user_id);
        $stmtU->execute();
        echo json_encode(["status" => "success", "message" => "Pembelian berhasil"]);
    } else {
        $data = json_decode(file_get_contents("php://input"));
        $query = "INSERT INTO paket_billing (name, price, seconds, description) VALUES (:name, :price, :seconds, :desc)";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(":name", $data->name);
        $stmt->bindParam(":price", $data->price);
        $stmt->bindParam(":seconds", $data->seconds);
        $stmt->bindParam(":desc", $data->description);
        if($stmt->execute()) echo json_encode(["message" => "Paket dibuat"]);
    }
}

if ($method === 'DELETE') {
    $id = $_GET['id'];
    try {
        $query = "UPDATE paket_billing SET deleted_at = NOW() WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(":id", $id);
        if($stmt->execute()) echo json_encode(["message" => "Paket dihapus (soft delete)"]);
    } catch (PDOException $e) {
        if ($e->getCode() === '42S22') {
            $q2 = "DELETE FROM paket_billing WHERE id = :id";
            $s2 = $conn->prepare($q2);
            $s2->bindParam(":id", $id);
            if($s2->execute()) echo json_encode(["message" => "Paket dihapus (hard delete)"]);
        } else {
            throw $e;
        }
    }
}
?>