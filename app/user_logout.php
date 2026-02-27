<?php
// File: app/user_logout.php
// Penjelasan: Endpoint untuk proses logout.

require_once 'auth_middleware.php';

// Untuk JWT stateless, logout sebenarnya terjadi di sisi client dengan menghapus token.
// Endpoint ini berguna jika di masa depan Anda ingin mengimplementasikan
// daftar hitam (blacklist) token untuk keamanan tambahan.

// Contoh:
// $token = get_bearer_token();
// add_token_to_blacklist($token);

http_response_code(200);
echo json_encode(['status' => 'success', 'message' => 'Logout berhasil.']);
?>
