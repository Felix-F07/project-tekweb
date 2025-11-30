<?php
session_start();
include_once '../configuration/database.php';

$data = json_decode(file_get_contents("php://input"));

// --- DEBUG LOG (temporary) ---
// Write a small debug entry for incoming login attempts. Remove when done.
try {
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
    $logFile = $logDir . '/auth_debug.log';
    $body = file_get_contents('php://input');
    $now = date('Y-m-d H:i:s');
    $entry = "[$now] LOGIN ATTEMPT - raw_body=" . str_replace("\n", '', $body);
    file_put_contents($logFile, $entry . PHP_EOL, FILE_APPEND);
} catch (Exception $e) {
    // ignore logging errors
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Try selecting user excluding soft-deleted rows. If the DB schema doesn't
    // have `deleted_at` column we catch the exception and retry without it.
    $user = false;
    $query = "SELECT * FROM users WHERE username = :username AND deleted_at IS NULL LIMIT 1";
    try {
        $stmt = $conn->prepare($query);
        $stmt->bindParam(":username", $data->username);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // If error is 'column not found' (SQLSTATE 42S22) then retry without deleted_at
        if ($e->getCode() === '42S22') {
            try {
                $query2 = "SELECT * FROM users WHERE username = :username LIMIT 1";
                $stmt2 = $conn->prepare($query2);
                $stmt2->bindParam(":username", $data->username);
                $stmt2->execute();
                $user = $stmt2->fetch(PDO::FETCH_ASSOC);
            } catch (PDOException $e2) {
                // fallback: set user false and continue to error handling below
                $user = false;
            }
        } else {
            // Re-throw unexpected DB errors to surface them during development
            throw $e;
        }
    }
    // Verifikasi Hash Password
    $ok = false;

    if ($user && password_verify($data->password, $user['password'])) {
        // Password already hashed and verified
        $ok = true;
    } elseif ($user && isset($user['password']) && $data->password === $user['password']) {
        // Fallback: DB contains plaintext password (seed). Re-hash it and update DB.
        $newHash = password_hash($data->password, PASSWORD_BCRYPT);
        $up = $conn->prepare("UPDATE users SET password = :pw WHERE id = :id");
        $up->bindParam(':pw', $newHash);
        $up->bindParam(':id', $user['id']);
        $up->execute();
        $ok = true;
    }

    if ($ok) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role_id'] = $user['role_id'];
        echo json_encode([
            "status" => "success",
            "role_id" => $user['role_id'],
            "user_id" => $user['id'],
            "username" => $user['username']
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Username atau Password salah"]);
    }
}
?>