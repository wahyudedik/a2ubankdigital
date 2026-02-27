<?php
// File: app/admin_get_customers.php
// Penjelasan: Menyediakan daftar nasabah dengan pencarian, paginasi, dan data scoping.
// REVISI: Menerapkan filter unit/cabang berdasarkan hak akses staf yang login.

require_once 'auth_middleware.php';

// Hanya peran staf yang bisa mengakses
if ($authenticated_user_role_id == 9) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit();
}

// --- Parameter untuk Paginasi dan Pencarian ---
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$offset = ($page - 1) * $limit;

try {
    // --- Membangun Query secara Dinamis ---
    // Kita butuh JOIN ke customer_profiles karena di sanalah unit_id nasabah disimpan.
    $base_sql = "FROM users u JOIN customer_profiles cp ON u.id = cp.user_id";
    $where_clauses = ["u.role_id = 9"]; // Filter dasar: hanya tampilkan nasabah
    $params = [];

    // --- LOGIKA DATA SCOPING ---
    // Terapkan filter unit/cabang jika yang login BUKAN Super Admin.
    // Variabel $authenticated_user_role_id dan $accessible_unit_ids berasal dari middleware.
    if ($authenticated_user_role_id !== 1) {
        
        // Pengaman: Jika staf (selain super admin) tidak punya akses ke unit manapun,
        // jangan tampilkan data apa-apa. Kirim array kosong.
        if (empty($accessible_unit_ids)) {
            echo json_encode(['status' => 'success', 'data' => [], 'pagination' => ['current_page' => 1, 'total_pages' => 0, 'total_records' => 0]]);
            exit();
        }
        
        // Buat placeholder sejumlah ID unit yang bisa diakses untuk query yang aman.
        $placeholders = implode(',', array_fill(0, count($accessible_unit_ids), '?'));
        $where_clauses[] = "cp.unit_id IN ($placeholders)";
        $params = array_merge($params, $accessible_unit_ids);
    }
    // --- AKHIR LOGIKA DATA SCOPING ---

    if (!empty($search)) {
        $where_clauses[] = "(u.full_name LIKE ? OR u.email LIKE ? OR u.bank_id LIKE ?)";
        $search_param = "%" . $search . "%";
        array_push($params, $search_param, $search_param, $search_param);
    }

    $where_sql = " WHERE " . implode(" AND ", $where_clauses);

    // --- Query untuk menghitung total data (untuk paginasi) ---
    $sql_total = "SELECT COUNT(u.id) " . $base_sql . $where_sql;
    $stmt_total = $pdo->prepare($sql_total);
    $stmt_total->execute($params);
    $total_records = $stmt_total->fetchColumn();
    $total_pages = ceil($total_records / $limit);

    // --- Query untuk mengambil data nasabah ---
    $sql_data = "SELECT u.id, u.bank_id, u.full_name, u.email, u.phone_number, u.status, u.created_at " 
              . $base_sql 
              . $where_sql
              . " ORDER BY u.created_at DESC LIMIT ? OFFSET ?";
    
    // Tambahkan parameter limit dan offset untuk paginasi
    $params_for_data = $params;
    $params_for_data[] = $limit;
    $params_for_data[] = $offset;

    $stmt_data = $pdo->prepare($sql_data);
    
    // Bind parameter secara aman untuk mencegah SQL injection dan error tipe data
    foreach ($params_for_data as $key => $value) {
        $param_type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
        $stmt_data->bindValue($key + 1, $value, $param_type);
    }
    
    $stmt_data->execute();
    $customers = $stmt_data->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'data' => $customers,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => (int)$total_pages,
            'total_records' => (int)$total_records
        ]
    ]);

} catch (PDOException $e) {
    error_log("Admin Get Customers Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan pada server saat mengambil data nasabah.']);
}
