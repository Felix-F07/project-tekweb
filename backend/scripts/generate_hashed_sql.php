<?php
// generate_hashed_sql.php
// Baca sql/warnet.sql, hash semua password plaintext pada INSERT INTO users, tulis sql/warnet_hashed.sql

$input = __DIR__ . '/../../sql/warnet.sql';
$output = __DIR__ . '/../../sql/warnet_hashed.sql';

if (!file_exists($input)) {
    echo "File input not found: $input\n";
    exit(1);
}

$content = file_get_contents($input);

// Cari bagian INSERT INTO users ... VALUES (...) bisa berisi beberapa baris
$pattern = '/INSERT\s+INTO\s+users\s*\([^\)]*\)\s*VALUES\s*(\([^;]+?\));/is';

$new = preg_replace_callback($pattern, function($m) {
    $values_block = $m[1]; // e.g. ( 'admin','admin123',1,0 ), ( 'Kenji','123',2,3600 )

    // Replace each password in single-quoted pattern: 'username', 'password', role, billing
    $row_pattern = '/\(\s*' .
        "'([^']*)'\s*,\s*'([^']*)'\s*,\s*([0-9]+)\s*,\s*([0-9]*)\s*\)/" .
        '/i';

    $replaced = preg_replace_callback($row_pattern, function($r) {
        $username = $r[1];
        $plain = $r[2];
        $role = $r[3];
        $billing = $r[4];

        // Hash password with bcrypt
        $hash = password_hash($plain, PASSWORD_BCRYPT);

        // Escape single quotes in hash just in case (shouldn't contain ')
        $hash_esc = str_replace("'", "''", $hash);

        return "( '$username', '$hash_esc', $role, $billing )";
    }, $values_block);

    return 'INSERT INTO users (username, password, role_id, billing_seconds) VALUES ' . $replaced . ';';

}, $content);

if ($new === null) {
    echo "Tidak ada perubahan (regex error).\n";
    exit(1);
}

file_put_contents($output, $new);
echo "Generated hashed SQL: $output\n";
echo "Import file ini di phpMyAdmin atau MySQL untuk membuat database dengan password yang sudah di-hash.\n";

?>
