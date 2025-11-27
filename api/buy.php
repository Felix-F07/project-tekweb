<?php
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/Database.php';
include_once '../classes/User.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

// Terima data dari Script.js
$username = isset($_POST['username']) ? $_POST['username'] : '';
$seconds = isset($_POST['seconds']) ? intval($_POST['seconds']) : 0;
$paket_id = isset($_POST['paket_id']) ? intval($_POST['paket_id']) : 0;

// Require paket_id (DB-only). Fetch paket details first to get seconds and price
$paket_name = '';
$price = 0;
if(!$paket_id) {
    echo json_encode(["status" => "error", "message" => "paket_id is required."]);
    exit;
}

try {
    $q = "SELECT id, name, price, seconds FROM paket_billing WHERE id = :id LIMIT 1";
    $st = $db->prepare($q);
    $st->bindParam(':id', $paket_id);
    $st->execute();
    if($st->rowCount() > 0) {
        $r = $st->fetch(PDO::FETCH_ASSOC);
        $paket_name = $r['name'];
        $price = intval($r['price']);
        $seconds = intval($r['seconds']);
    } else {
        echo json_encode(["status" => "error", "message" => "Paket tidak ditemukan."]);
        exit;
    }
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Server error saat membaca paket."]);
    exit;
}

// Now we have paket data from DB; require username as well
if(!empty($username) && !empty($paket_id)) {
    // Jalankan Top Up
    if($user->topUpBilling($username, $seconds)) {
        // Ambil data waktu terbaru setelah ditambah
        $sisa_baru = $user->getBilling($username);
        // Simpan ke tabel purchases (username-based)
        try {
            $queryIns = "INSERT INTO purchases (username, paket_name, price, seconds_added, created_at) VALUES (:username, :paket, :price, :seconds, NOW())";
            $stmtIns = $db->prepare($queryIns);
            $stmtIns->bindParam(':username', $username);
            $stmtIns->bindParam(':paket', $paket_name);
            $stmtIns->bindParam(':price', $price);
            $stmtIns->bindParam(':seconds', $seconds);
            $stmtIns->execute();
        } catch (Exception $e) {
            // Jika gagal menyimpan ke purchases, lanjutkan supaya top-up tetap berhasil.
        }

        // Simpan juga ke tabel `transactions` (dinormalisasi) jika memungkinkan
        try {
            // Cari user_id dari username
            $user_id = null;
            $qUser = "SELECT id FROM users WHERE username = :username LIMIT 1";
            $stUser = $db->prepare($qUser);
            $stUser->bindParam(':username', $username);
            $stUser->execute();
            if($stUser->rowCount() > 0) {
                $ur = $stUser->fetch(PDO::FETCH_ASSOC);
                $user_id = $ur['id'];
            }

            if($user_id) {
                $qTrans = "INSERT INTO transactions (user_id, pc_id, paket_id, billing_amount, seconds_added, created_at) VALUES (:user_id, NULL, :paket_id, :billing_amount, :seconds_added, NOW())";
                $stTrans = $db->prepare($qTrans);
                $stTrans->bindParam(':user_id', $user_id);
                $stTrans->bindParam(':paket_id', $paket_id);
                $stTrans->bindParam(':billing_amount', $price);
                $stTrans->bindParam(':seconds_added', $seconds);
                $stTrans->execute();
            }
        } catch (Exception $e) {
            // Jika gagal menyimpan transaksi, kita tetap anggap top-up sukses.
        }

        echo json_encode([
            "status" => "success",
            "message" => "Pembelian Berhasil!",
            "new_time" => $sisa_baru
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Gagal update database."]);
    }

} else {
    echo json_encode(["status" => "error", "message" => "Data tidak lengkap."]);
}
?>