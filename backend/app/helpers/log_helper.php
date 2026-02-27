<?php
// File: app/helpers/log_helper.php
// Penjelasan: Fungsi helper untuk mencatat log ke database.

function log_system_event($pdo, $level, $message, $context = []) {
    try {
        $sql = "INSERT INTO system_logs (level, message, context) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$level, $message, json_encode($context)]);
    } catch (PDOException $e) {
        // Gagal mencatat log, jangan hentikan aplikasi, cukup catat di error log PHP
        error_log("Failed to write to system_logs table: " . $e->getMessage());
    }
}
?>
