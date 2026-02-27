<?php
// File: app/auth_middleware.php
// Penjelasan: "Penjaga" untuk semua endpoint yang memerlukan login.
// REVISI FINAL: Menambahkan "freshness check" untuk memastikan peran & status pengguna selalu terbaru.

require_once 'config.php';
// vendor/autoload.php sudah di-include oleh config.php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// --- Variabel Global untuk Otentikasi & Otorisasi ---
$authenticated_user_id = null;
$authenticated_user_role_id = null;
$accessible_unit_ids = []; // Menyimpan daftar ID unit yang boleh diakses oleh staf

$auth_header = $_SERVER['HTTP_AUTHORIZATION'] ?? null;

if (!$auth_header) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Token otentikasi tidak ditemukan.']);
    exit();
}

if (!preg_match('/Bearer\s(\S+)/', $auth_header, $matches)) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Format token tidak valid.']);
    exit();
}

$token = $matches[1];
if (!$token) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Token kosong.']);
    exit();
}

try {
    $secret_key = $_ENV['JWT_SECRET'];
    $decoded_token = JWT::decode($token, new Key($secret_key, 'HS256'));
    $user_id_from_token = $decoded_token->data->user_id;

    // --- PERBAIKAN KRUSIAL: Pemeriksaan Kesegaran (Freshness Check) ---
    // Daripada memercayai data di token, kita ambil data terbaru dari database.
    // Ini mencegah penggunaan token lama jika hak akses atau status pengguna telah berubah.
    $stmt_fresh_check = $pdo->prepare("SELECT role_id, unit_id, status FROM users WHERE id = ?");
    $stmt_fresh_check->execute([$user_id_from_token]);
    $fresh_user_data = $stmt_fresh_check->fetch(PDO::FETCH_ASSOC);

    if (!$fresh_user_data || $fresh_user_data['status'] !== 'ACTIVE') {
        http_response_code(401);
        throw new Exception('Pengguna tidak ditemukan, tidak aktif, atau telah diblokir.');
    }
    
    // Gunakan data terbaru dari database, bukan dari token
    $authenticated_user_id = $user_id_from_token;
    $authenticated_user_role_id = (int)$fresh_user_data['role_id'];
    $user_assigned_unit_id = $fresh_user_data['unit_id'] ? (int)$fresh_user_data['unit_id'] : null;
    // --- AKHIR PERBAIKAN ---


    // --- LOGIKA DATA SCOPING (Tidak Berubah) ---
    if ($authenticated_user_role_id !== 9) { 
        if ($authenticated_user_role_id === 1) {
            // Super Admin memiliki akses tak terbatas
        } else {
            if ($user_assigned_unit_id) {
                $stmt_unit_type = $pdo->prepare("SELECT unit_type, parent_id FROM units WHERE id = ?");
                $stmt_unit_type->execute([$user_assigned_unit_id]);
                $unit_info = $stmt_unit_type->fetch(PDO::FETCH_ASSOC);

                if ($unit_info && $unit_info['unit_type'] === 'CABANG') {
                    $accessible_unit_ids[] = $user_assigned_unit_id;
                    $stmt_child_units = $pdo->prepare("SELECT id FROM units WHERE parent_id = ?");
                    $stmt_child_units->execute([$user_assigned_unit_id]);
                    $child_ids = $stmt_child_units->fetchAll(PDO::FETCH_COLUMN);
                    if (!empty($child_ids)) {
                        $accessible_unit_ids = array_merge($accessible_unit_ids, array_map('intval', $child_ids));
                    }
                } elseif ($unit_info && $unit_info['unit_type'] === 'UNIT') {
                    $accessible_unit_ids[] = $user_assigned_unit_id;
                }
            }
        }
    }

} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Token tidak valid atau sesi Anda telah berakhir. Silakan login kembali.']);
    exit();
}
