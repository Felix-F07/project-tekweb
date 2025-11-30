# Backend API - project-tekweb

Lokasi folder backend: `backend/`

Ringkasan singkat file dan endpoint (gunakan `http://localhost/project-tekweb/backend/api/...`):

- `backend/configuration/database.php`
  - Menyediakan koneksi PDO `$conn` dan menangani header CORS serta preflight OPTIONS.

- `backend/api/auth.php` (POST)
  - Fungsi: login/authenticate.
  - Request body JSON: `{ "username": "...", "password": "..." }`.
  - Respon sukses: `{ status: "success", role_id: X, user_id: Y, username: "..." }`.
  - Menggunakan `password_verify()` untuk mengecek hash.

- `backend/api/users.php` (GET, POST, PUT, DELETE)
  - GET: mengembalikan daftar user (join roles), hanya yang `deleted_at IS NULL`.
  - POST: tambah user baru. Body JSON: `{ username, password, role_id }`. Password di-hash (BCRYPT).
  - PUT: update user (misal ganti password / role). Body JSON contoh: `{ id, role_id, password }` (password opsional).
  - DELETE: soft delete (`deleted_at = NOW()`), gunakan query param `?id=`.

- `backend/api/pcs.php` (GET, POST, PUT, DELETE)
  - CRUD PC (nama, status). Sudah tersedia; kembalikan JSON array untuk GET.

- `backend/api/packages.php` (GET, POST, DELETE)
  - GET: list paket aktif (`deleted_at IS NULL`).
  - POST: dua mode: `?action=buy` untuk beli paket (mencatat di `transactions` dan menambah `users.billing_seconds`), atau default untuk menambah paket (admin).
  - DELETE: soft delete paket (set `deleted_at = NOW()`)

- `backend/api/transactions.php` (GET)
  - GET: ambil riwayat transaksi user, gunakan query param `?user_id=`.

- `backend/scripts/hash_seed_passwords.php`
  - Skrip CLI untuk meng-hash password plaintext yang ada di tabel `users` (seed).
  - Jalankan dari folder `backend/scripts` atau panggil via browser (lebih aman jalankan CLI).
  - Contoh (PowerShell dari root project):

```powershell
php .\backend\scripts\hash_seed_passwords.php
```

- `backend/scripts/generate_hashed_sql.php`
  - Membaca `sql/warnet.sql`, membuat versi baru `sql/warnet_hashed.sql` di mana password pada INSERT users di-hash otomatis.
  - Cara pakai (dijalankan lokal pada mesin Anda):

```powershell
php .\backend\scripts\generate_hashed_sql.php
```

  - Setelah script dijalankan, import `sql/warnet_hashed.sql` di phpMyAdmin / MySQL untuk membuat database langsung dengan password yang sudah di-hash.

Catatan penting & langkah pengujian:

- Pastikan Apache (XAMPP) dan MySQL berjalan.
- Database: file SQL seed ada di `sql/warnet.sql`. Jika Anda sudah meng-import seed yang berisi password plaintext, jalankan skrip `hash_seed_passwords.php` untuk mengkonversi password menjadi bcrypt.
- Jika `root` MySQL Anda punya password, update `backend/configuration/database.php` variabel `$password` dengan benar.

Mapping ke frontend (saran):
- Halaman `frontend/index.html` (login) -> `backend/api/auth.php` (POST)
- `frontend/admin/pcs.html` -> `backend/api/pcs.php`
- `frontend/admin/users.html` -> `backend/api/users.php`
- `frontend/admin/packages.html` -> `backend/api/packages.php`
- `frontend/user/history.html` -> `backend/api/transactions.php?user_id=...`

Jika Anda mau, saya bisa:
- Menambahkan token-based auth (JWT) untuk melindungi endpoint admin.
- Buat contoh AJAX call lengkap untuk semua halaman frontend.
- Update `sql/warnet.sql` supaya memasukkan hashed password langsung.
