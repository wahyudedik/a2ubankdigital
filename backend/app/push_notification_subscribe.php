<?php
// File: app/push_notification_subscribe.php
// Penjelasan: Menyimpan endpoint langganan push notification dari PWA/browser.

require_once 'auth_middleware.php';

/*
    CATATAN DATABASE: Memerlukan tabel `push_subscriptions`.
    CREATE TABLE `push_subscriptions` (
      `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
      `user_id` int(10) UNSIGNED NOT NULL,
      `endpoint` text NOT NULL,
      `p256dh` varchar(255) NOT NULL,
      `auth` varchar(255) NOT NULL,
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      PRIMARY KEY (`id`),
      UNIQUE KEY `user_id_endpoint` (`user_id`, `p256dh`), -- Mencegah duplikat per perangkat
      CONSTRAINT `fk_push_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB;
*/

$input = json_decode(file_get_contents('php://input'), true);

if (empty($input['endpoint']) || empty($input['keys']['p256dh']) || empty($input['keys']['auth'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Data langganan tidak valid.']);
    exit();
}

$endpoint = $input['endpoint'];
$p256dh = $input['keys']['p256dh'];
$auth = $input['keys']['auth'];

try {
    $stmt = $pdo->prepare("
        INSERT INTO push_subscriptions (user_id, endpoint, p256dh, auth) 
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE endpoint = VALUES(endpoint), auth = VALUES(auth)
    ");
    $stmt->execute([$authenticated_user_id, $endpoint, $p256dh, $auth]);

    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'Berhasil berlangganan notifikasi.']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan langganan.']);
}
?>
