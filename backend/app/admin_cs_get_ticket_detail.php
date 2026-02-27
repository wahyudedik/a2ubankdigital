<?php
// File: app/admin_cs_get_ticket_detail.php
// Penjelasan: CS melihat detail tiket.
// REVISI: Memperbaiki query untuk data scoping yang benar dan nama kolom.

require_once 'auth_middleware.php';

$allowed_roles = [1, 2, 3, 6];
if (!in_array($authenticated_user_role_id, $allowed_roles)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

$ticket_id = $_GET['id'] ?? null;
if (!$ticket_id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID Tiket wajib diisi.']);
    exit();
}

try {
    // Validasi kepemilikan tiket melalui unit nasabah
    $stmt_check = $pdo->prepare("
        SELECT cp.unit_id 
        FROM support_tickets st
        JOIN customer_profiles cp ON st.customer_user_id = cp.user_id
        WHERE st.id = ?
    ");
    $stmt_check->execute([$ticket_id]);
    $customer_unit_id = $stmt_check->fetchColumn();

    if ($authenticated_user_role_id !== 1 && (!$customer_unit_id || !in_array($customer_unit_id, $accessible_unit_ids))) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Tiket tidak ditemukan atau Anda tidak memiliki akses ke unit nasabah ini.']);
        exit();
    }

    // Ambil detail tiket utama
    $stmt_ticket = $pdo->prepare("SELECT * FROM support_tickets WHERE id = ?");
    $stmt_ticket->execute([$ticket_id]);
    $ticket_details = $stmt_ticket->fetch(PDO::FETCH_ASSOC);

    if (!$ticket_details) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Tiket tidak ditemukan.']);
        exit();
    }

    // Ambil riwayat balasan
    $stmt_replies = $pdo->prepare("
        SELECT str.message, str.created_at, u.full_name, r.role_name
        FROM support_ticket_replies str
        JOIN users u ON str.user_id = u.id
        JOIN roles r ON u.role_id = r.id
        WHERE str.ticket_id = ?
        ORDER BY str.created_at ASC
    ");
    $stmt_replies->execute([$ticket_id]);
    $replies = $stmt_replies->fetchAll(PDO::FETCH_ASSOC);

    $response = [
        'details' => $ticket_details,
        'replies' => $replies
    ];

    http_response_code(200);
    echo json_encode(['status' => 'success', 'data' => $response]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil detail tiket.']);
}
