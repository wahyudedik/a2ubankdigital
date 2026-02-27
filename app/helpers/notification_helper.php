<?php
// File: app/helpers/notification_helper.php
// Penjelasan: Kumpulan fungsi untuk mengirim notifikasi internal kepada staf.
// REVISI: Menambahkan fungsi-fungsi yang hilang (notify_staff_by_role, notify_staff_hierarchically).

require_once __DIR__ . '/hierarchy_helper.php';

/**
 * Mengirim notifikasi ke semua staf aktif yang memiliki peran tertentu.
 *
 * @param PDO $pdo Objek koneksi database.
 * @param array $role_ids Array berisi ID peran target (misal: [1, 2, 3] untuk manajer).
 * @param string $title Judul notifikasi.
 * @param string $message Isi pesan notifikasi.
 * @return void
 */
function notify_staff_by_role($pdo, $role_ids, $title, $message) {
    if (empty($role_ids)) {
        return;
    }
    
    $placeholders = implode(',', array_fill(0, count($role_ids), '?'));
    
    try {
        $sql_users = "SELECT id FROM users WHERE role_id IN ($placeholders) AND status = 'ACTIVE'";
        $stmt_users = $pdo->prepare($sql_users);
        $stmt_users->execute($role_ids);
        $staff_ids = $stmt_users->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($staff_ids)) {
            $stmt_notify = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
            foreach ($staff_ids as $staff_id) {
                $stmt_notify->execute([$staff_id, $title, $message]);
            }
        }
    } catch (Exception $e) {
        // Log error jika pengiriman notifikasi gagal, tapi jangan hentikan proses utama.
        error_log("Failed to send notification by role: " . $e->getMessage());
    }
}

/**
 * Mengirim notifikasi secara berjenjang dari staf pelaksana ke atasannya.
 *
 * @param PDO $pdo Objek koneksi database.
 * @param int $originator_user_id ID pengguna (nasabah/staf) yang memicu notifikasi.
 * @param mixed $target_role_id ID peran atau array ID peran pelaksana awal.
 * @param string $title Judul notifikasi.
 * @param string $message Isi pesan notifikasi.
 * @return void
 */
function notify_staff_hierarchically($pdo, $originator_user_id, $target_role_id, $title, $message) {
    $notified_users = [];
    $target_roles = is_array($target_role_id) ? $target_role_id : [$target_role_id];

    try {
        // 1. Notifikasi ke pelaksana di unit yang sama
        $stmt_unit_id = $pdo->prepare("SELECT unit_id FROM users WHERE id = ?");
        $stmt_unit_id->execute([$originator_user_id]);
        $unit_id = $stmt_unit_id->fetchColumn();

        if ($unit_id) {
            $placeholders = implode(',', array_fill(0, count($target_roles), '?'));
            $sql_implementers = "SELECT id FROM users WHERE unit_id = ? AND role_id IN ($placeholders) AND status = 'ACTIVE'";
            $stmt_implementers = $pdo->prepare($sql_implementers);
            $stmt_implementers->execute(array_merge([$unit_id], $target_roles));
            $implementer_ids = $stmt_implementers->fetchAll(PDO::FETCH_COLUMN);

            foreach ($implementer_ids as $id) {
                $notified_users[$id] = true;
            }
        }

        // 2. Notifikasi ke atasan
        $supervisors = get_supervisor_ids($pdo, $originator_user_id);
        if ($supervisors['unit_head_id']) $notified_users[$supervisors['unit_head_id']] = true;
        if ($supervisors['branch_head_id']) $notified_users[$supervisors['branch_head_id']] = true;

        // 3. Notifikasi ke Super Admin (selalu dapat)
        $notified_users[1] = true;

        // 4. Kirim notifikasi
        if (!empty($notified_users)) {
            $stmt_notify = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
            foreach (array_keys($notified_users) as $staff_id) {
                 $stmt_notify->execute([$staff_id, $title, $message]);
            }
        }

    } catch (Exception $e) {
        error_log("Failed to send hierarchical notification: " . $e->getMessage());
    }
}
