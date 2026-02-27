<?php
// File: app/helpers/hierarchy_helper.php
// Penjelasan: Helper baru untuk fungsi-fungsi terkait hierarki organisasi (unit, cabang, atasan).

/**
 * Mencari ID atasan langsung (Kepala Unit dan Kepala Cabang) dari seorang staf atau nasabah.
 *
 * @param PDO $pdo Objek koneksi database.
 * @param int $user_id ID pengguna (staf atau nasabah) yang ingin dicari atasannya.
 * @return array Array berisi 'unit_head_id' dan 'branch_head_id'. Keduanya bisa null.
 */
function get_supervisor_ids($pdo, $user_id) {
    $supervisors = ['unit_head_id' => null, 'branch_head_id' => null];

    // 1. Cari tahu di unit mana pengguna ini berada (baik staf maupun nasabah).
    $stmt_unit = $pdo->prepare("
        SELECT u.unit_id, un.parent_id as branch_id
        FROM users u
        LEFT JOIN units un ON u.unit_id = un.id
        WHERE u.id = ?
    ");
    $stmt_unit->execute([$user_id]);
    $location = $stmt_unit->fetch();

    if (!$location || !$location['unit_id']) {
        return $supervisors; // Pengguna tidak punya unit, tidak punya atasan.
    }

    $unit_id = $location['unit_id'];
    $branch_id = $location['branch_id'];

    // 2. Cari Kepala Unit (role_id = 3) di unit tersebut.
    $stmt_unit_head = $pdo->prepare("SELECT id FROM users WHERE unit_id = ? AND role_id = 3 AND status = 'ACTIVE' LIMIT 1");
    $stmt_unit_head->execute([$unit_id]);
    $supervisors['unit_head_id'] = $stmt_unit_head->fetchColumn() ?: null;

    // 3. Jika unit tersebut berada di bawah cabang, cari Kepala Cabang (role_id = 2).
    if ($branch_id) {
        $stmt_branch_head = $pdo->prepare("SELECT id FROM users WHERE unit_id = ? AND role_id = 2 AND status = 'ACTIVE' LIMIT 1");
        $stmt_branch_head->execute([$branch_id]);
        $supervisors['branch_head_id'] = $stmt_branch_head->fetchColumn() ?: null;
    }

    return $supervisors;
}
