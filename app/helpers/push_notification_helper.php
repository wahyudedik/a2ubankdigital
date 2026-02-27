<?php
// File: app/helpers/push_notification_helper.php
// REVISI TOTAL: Menambahkan logging yang sangat detail untuk diagnosis.

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

require_once __DIR__ . '/log_helper.php';

/**
 * Fungsi terpusat untuk mengirim notifikasi push ke perangkat pengguna.
 *
 * @param PDO $pdo Objek koneksi database.
 * @param int $user_id ID pengguna yang akan dikirimi notifikasi.
 * @param string $title Judul notifikasi.
 * @param string $body Isi pesan notifikasi.
 * @param string $tag Opsional, untuk mengelompokkan notifikasi (misal: 'transaksi', 'promo').
 * @return void
 */
function sendPushNotification($pdo, $user_id, $title, $body, $tag = 'general') {
    
    // --- LANGKAH DIAGNOSIS 1 ---
    // Log bahwa fungsi ini dipanggil dan untuk user ID siapa.
    log_system_event($pdo, 'INFO', 'sendPushNotification function triggered', ['for_user_id' => $user_id, 'title' => $title]);

    // 1. Ambil semua langganan (subscriptions) aktif untuk user ini dari database.
    $stmt = $pdo->prepare("SELECT * FROM push_subscriptions WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- LANGKAH DIAGNOSIS 2 ---
    // Log jumlah subscription yang ditemukan di database untuk user ini.
    // Ini akan memberitahu kita apakah query berhasil atau tidak.
    log_system_event($pdo, 'INFO', 'Subscription check result', ['for_user_id' => $user_id, 'subscriptions_found' => count($subscriptions)]);

    if (empty($subscriptions)) {
        // Log ini sekarang hanya akan muncul jika memang tidak ada data, bukan karena error lain.
        log_system_event($pdo, 'INFO', 'Push notification skipped', ['user_id' => $user_id, 'reason' => 'No active subscriptions found in DB.']);
        return; 
    }
    
    // 2. Siapkan otentikasi VAPID menggunakan kunci dari file .env
    $auth = [
        'VAPID' => [
            'subject' => 'mailto:' . $_ENV['MAIL_FROM_ADDRESS'],
            'publicKey' => $_ENV['VAPID_PUBLIC_KEY'],
            'privateKey' => $_ENV['VAPID_PRIVATE_KEY'],
        ],
    ];

    try {
        $webPush = new WebPush($auth);
        
        $payload = json_encode([
            'title' => $title,
            'body' => $body,
            'icon' => '/a2u-icon.png',
            'tag' => $tag,
        ]);

        foreach ($subscriptions as $sub) {
            try {
                // --- LANGKAH DIAGNOSIS 3 ---
                // Log setiap endpoint yang akan dikirimi notifikasi.
                log_system_event($pdo, 'INFO', 'Queueing notification for endpoint', ['user_id' => $user_id, 'endpoint' => $sub['endpoint']]);

                $subscriptionObject = Subscription::create([
                    'endpoint' => $sub['endpoint'],
                    'publicKey' => $sub['p256dh'],
                    'authToken' => $sub['auth'],
                ]);
                $webPush->queueNotification($subscriptionObject, $payload);
            } catch (Exception $e) {
                log_system_event($pdo, 'WARNING', 'Invalid subscription format in DB', ['user_id' => $user_id, 'subscription_id' => $sub['id'], 'error' => $e->getMessage()]);
            }
        }
        
        foreach ($webPush->flush() as $report) {
            $endpoint = $report->getRequest()->getUri()->__toString();
            
            if ($report->isSuccess()) {
                log_system_event($pdo, 'INFO', 'Push notification sent successfully', ['endpoint' => $endpoint]);
            } else {
                $statusCode = $report->getResponse() ? $report->getResponse()->getStatusCode() : null;
                log_system_event($pdo, 'ERROR', 'Push notification failed', [
                    'endpoint' => $endpoint, 
                    'reason' => $report->getReason(),
                    'statusCode' => $statusCode
                ]);
                
                if ($statusCode === 404 || $statusCode === 410) {
                     log_system_event($pdo, 'INFO', 'Deleting stale push subscription', ['endpoint' => $endpoint]);
                     $stmt_delete = $pdo->prepare("DELETE FROM push_subscriptions WHERE endpoint = ?");
                     $stmt_delete->execute([$endpoint]);
                }
            }
        }
    } catch (Exception $e) {
        log_system_event($pdo, 'CRITICAL', 'WebPush library error', ['error_message' => $e->getMessage()]);
    }
}
