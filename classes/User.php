<?php
class User {
    private $conn;
    private $table_name = "users";

    public function __construct($db) {
        $this->conn = $db;
    }

    // 1. LOGIN
    public function login($username, $password) {
        $query = "SELECT u.id, u.username, u.password, u.billing_seconds, r.name as role_name 
                  FROM " . $this->table_name . " u
                  JOIN roles r ON u.role_id = r.id
                  WHERE u.username = :username LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if($password == $row['password']) {
                unset($row['password']);
                return $row;
            }
        }
        return false;
    }

    // 2. TOP UP (BELI)
    public function topUpBilling($username, $seconds) {
        $query = "UPDATE " . $this->table_name . " 
                  SET billing_seconds = billing_seconds + :seconds 
                  WHERE username = :username";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':seconds', $seconds);
        $stmt->bindParam(':username', $username);
        return $stmt->execute();
    }

    // 3. GET INFO TERBARU
    public function getBilling($username) {
        $query = "SELECT billing_seconds FROM " . $this->table_name . " WHERE username = :username LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['billing_seconds'];
    }

    // 4. AUTO SAVE (KURANGI WAKTU) - BARU!
    public function decreaseBilling($username, $seconds) {
        // GREATEST(0, ...) biar waktunya gak minus
        $query = "UPDATE " . $this->table_name . " 
                  SET billing_seconds = GREATEST(0, billing_seconds - :seconds) 
                  WHERE username = :username";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':seconds', $seconds);
        $stmt->bindParam(':username', $username);
        return $stmt->execute();
    }
}
?>