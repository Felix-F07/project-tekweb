<?php
// Jalankan skrip ini dari CLI atau browser (sebaiknya CLI) untuk meng-hash password plaintext hasil seed
// Lokasi : backend/scripts/hash_seed_passwords.php

include_once __DIR__ . '/../configuration/database.php';

try {
    $q = $conn->prepare("SELECT id, password FROM users");
    $q->execute();
    $users = $q->fetchAll(PDO::FETCH_ASSOC);

    $updated = 0;
    foreach ($users as $u) {
        $pwd = $u['password'];
        // Deteksi sederhana apakah sudah hash bcrypt (mulai dengan $2y$ atau $2b$)
        if (!preg_match('/^\$2[yb]\$/', $pwd)) {
            $hash = password_hash($pwd, PASSWORD_BCRYPT);
            $up = $conn->prepare("UPDATE users SET password = :pw WHERE id = :id");
            $up->bindParam(':pw', $hash);
            $up->bindParam(':id', $u['id']);
            if ($up->execute()) $updated++;
        }
    }

    echo "Selesai. Password ter-update: " . $updated . " user(s)\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

?>