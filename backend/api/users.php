<?php
include_once '../configuration/database.php';

$method = $_SERVER['REQUEST_METHOD'];

// PUT: Edit User (Ganti Password / Role)
if ($method === 'PUT') {
    $data = json_decode(file_get_contents("php://input"));
    
    // Siapkan query dinamis
    $query = "UPDATE users SET role_id = :role_id";
    if (!empty($data->password)) {
        $query .= ", password = :password";
    }
    // allow updating billing_seconds if provided
    if (isset($data->billing_seconds)) {
        $query .= ", billing_seconds = :billing_seconds";
    }
    $query .= " WHERE id = :id";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(":role_id", $data->role_id);
    $stmt->bindParam(":id", $data->id);
    
    if (!empty($data->password)) {
        $hashed_password = password_hash($data->password, PASSWORD_BCRYPT);
        $stmt->bindParam(":password", $hashed_password);
    }
    if (isset($data->billing_seconds)) {
        $stmt->bindParam(":billing_seconds", $data->billing_seconds);
    }

    if($stmt->execute()) {
        echo json_encode(["message" => "User berhasil diupdate"]);
    } else {
        echo json_encode(["message" => "Gagal update"]);
    }
}
// GET: Ambil semua user
if ($method === 'GET') {
    // Join dengan table roles untuk mengambil nama role
    $users = [];
    $query = "SELECT u.id, u.username, r.name as role_name, u.billing_seconds 
              FROM users u 
              JOIN roles r ON u.role_id = r.id 
              WHERE u.deleted_at IS NULL";
    try {
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // jika kolom deleted_at tidak ada, retry tanpa kondisi tersebut
        if ($e->getCode() === '42S22') {
            $query2 = "SELECT u.id, u.username, r.name as role_name, u.billing_seconds 
                       FROM users u 
                       JOIN roles r ON u.role_id = r.id";
            $stmt2 = $conn->prepare($query2);
            $stmt2->execute();
            $users = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        } else {
            throw $e;
        }
    }
    echo json_encode($users);
}

// POST: Tambah User Baru (Dengan Hashing)
if ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"));

    // Hash Password
    $hashed_password = password_hash($data->password, PASSWORD_BCRYPT);
    // allow initial billing_seconds if provided (default 0)
    $query = "INSERT INTO users (username, password, role_id, billing_seconds) VALUES (:username, :password, :role_id, :billing_seconds)";
    $stmt = $conn->prepare($query);
    
    $stmt->bindParam(":username", $data->username);
    $stmt->bindParam(":password", $hashed_password);
    $stmt->bindParam(":role_id", $data->role_id);
    $billing = isset($data->billing_seconds) ? $data->billing_seconds : 0;
    $stmt->bindParam(":billing_seconds", $billing);

    if ($stmt->execute()) {
        echo json_encode(["message" => "User berhasil ditambahkan"]);
    } else {
        echo json_encode(["message" => "Gagal menambah user"]);
    }
}

// DELETE: Soft Delete User (Request DELETE tidak support body di beberapa server, pakai POST dengan param action atau method override, tapi disini pakai simple Logic)
if ($method === 'DELETE') {
    $id = $_GET['id'];
    // Try soft delete first; if column missing, do hard delete
    $query = "UPDATE users SET deleted_at = NOW() WHERE id = :id";
    try {
        $stmt = $conn->prepare($query);
        $stmt->bindParam(":id", $id);
        if($stmt->execute()){
            echo json_encode(["message" => "User dihapus"]);
        }
    } catch (PDOException $e) {
        if ($e->getCode() === '42S22') {
            // fallback: hard delete
            $q2 = "DELETE FROM users WHERE id = :id";
            $s2 = $conn->prepare($q2);
            $s2->bindParam(":id", $id);
            if($s2->execute()) {
                echo json_encode(["message" => "User dihapus (hard delete)"]);
            }
        } else {
            throw $e;
        }
    }
}
?>