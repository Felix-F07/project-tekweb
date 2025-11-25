<?php
class User {
    private $conn;
    private $table_name = "users";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function login($username, $password) {
        // Query Gabungan (JOIN) antara Users dan Roles
        $query = "SELECT 
                    u.id, u.username, u.password, u.billing_seconds, 
                    r.name as role_name 
                  FROM " . $this->table_name . " u
                  JOIN roles r ON u.role_id = r.id
                  WHERE u.username = :username 
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            // Cek Password
            if($password == $row['password']) {
                unset($row['password']); // Hapus password biar aman
                return $row;
            }
        }
        return false;
    }
}
?>