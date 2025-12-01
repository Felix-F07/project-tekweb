<?php
include_once '../configuration/database.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $query = "SELECT * FROM pc ORDER BY id ASC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
}

if ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    $query = "INSERT INTO pc (name, status) VALUES (:name, 'available')";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(":name", $data->name);
    
    if($stmt->execute()) echo json_encode(["message" => "PC Ditambahkan"]);
    else echo json_encode(["message" => "Gagal"]);
}


if ($method === 'PUT') {
    $data = json_decode(file_get_contents("php://input"));
    
    if(isset($data->status)) {
        $query = "UPDATE pc SET status = :status WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(":status", $data->status);
    } else {
        $query = "UPDATE pc SET name = :name WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(":name", $data->name);
    }
    $stmt->bindParam(":id", $data->id);

    if($stmt->execute()) echo json_encode(["message" => "Update Berhasil"]);
}

if ($method === 'DELETE') {
    $id = $_GET['id'];
    $query = "DELETE FROM pc WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(":id", $id);
    if($stmt->execute()) echo json_encode(["message" => "PC Dihapus"]);
}
?>