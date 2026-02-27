<?php
// File: app/utility_get_faq.php
// Penjelasan: Mengambil daftar FAQ dari database.

require_once 'config.php';


try {
    $stmt = $pdo->query("SELECT question, answer, category FROM faqs WHERE is_active = 1 ORDER BY category, id");
    $faqs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $grouped_faqs = [];
    foreach ($faqs as $faq) {
        $grouped_faqs[$faq['category']][] = $faq;
    }

    http_response_code(200);
    echo json_encode(['status' => 'success', 'data' => $grouped_faqs]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil data FAQ.']);
}
?>
