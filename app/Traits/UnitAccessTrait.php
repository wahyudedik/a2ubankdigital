<?php

namespace App\Traits;

use App\Models\Role;
use Illuminate\Support\Facades\DB;

/**
 * Trait untuk mengontrol akses data berdasarkan unit/cabang.
 *
 * Digunakan oleh controller admin untuk memastikan staf hanya bisa
 * mengakses data dari unit/cabang yang sesuai dengan unit mereka.
 *
 * Super Admin (role_id=1) bypass semua filter.
 */
trait UnitAccessTrait
{
    /**
     * Mendapatkan semua unit ID yang bisa diakses oleh user.
     *
     * - Super Admin (role_id=1): mengembalikan null (bypass filter)
     * - Kepala Cabang (role_id=2): cabangnya + semua sub-unit
     * - Lainnya (role_id=3-8): hanya unit mereka sendiri
     * - Tanpa unit_id: mengembalikan array kosong (tidak bisa akses apa pun)
     *
     * @param \App\Models\User $user
     * @return array|null Array unit IDs, atau null jika Super Admin (bypass)
     */
    protected function getAccessibleUnitIds($user): ?array
    {
        // Super Admin bypass semua filter
        if ($user->role_id === Role::SUPER_ADMIN) {
            return null; // null = bypass, tidak perlu filter
        }

        // Staff tanpa unit_id tidak bisa akses data apa pun
        if (!$user->unit_id) {
            return [];
        }

        // Start dengan unit user sendiri
        $unitIds = [$user->unit_id];

        // Kepala Cabang: ambil semua child unit secara rekursif
        if ($user->role_id === Role::ADMIN) { // Kepala Cabang
            $this->collectChildUnitIds($user->unit_id, $unitIds);
        }

        return $unitIds;
    }

    /**
     * Rekursif mengambil semua child unit ID dari parent tertentu.
     */
    protected function collectChildUnitIds(int $parentUnitId, array &$unitIds): void
    {
        $childIds = DB::table('units')
            ->where('parent_id', $parentUnitId)
            ->pluck('id')
            ->toArray();

        foreach ($childIds as $childId) {
            if (!in_array($childId, $unitIds)) {
                $unitIds[] = $childId;
                $this->collectChildUnitIds($childId, $unitIds);
            }
        }
    }

    /**
     * Apply unit scope ke query Eloquent yang memiliki relasi ke customerProfile.
     *
     * Contoh penggunaan:
     *   $this->applyUnitScope($query, $user, 'customerProfile');
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \App\Models\User $user
     * @param string $relationName Nama relasi ke customerProfile (default: 'customerProfile')
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyUnitScope($query, $user, string $relationName = 'customerProfile')
    {
        $accessibleUnitIds = $this->getAccessibleUnitIds($user);

        // Super Admin: bypass
        if ($accessibleUnitIds === null) {
            return $query;
        }

        // Tidak ada unit yang bisa diakses
        if (empty($accessibleUnitIds)) {
            return $query->whereRaw('1 = 0'); // Return empty result
        }

        return $query->whereHas($relationName, function ($q) use ($accessibleUnitIds) {
            $q->whereIn('unit_id', $accessibleUnitIds);
        });
    }

    /**
     * Check apakah user bisa mengakses customer tertentu.
     *
     * @param \App\Models\User $user Staff yang ingin akses
     * @param int $customerId ID customer yang ingin diakses
     * @return bool
     */
    protected function canAccessCustomer($user, int $customerId): bool
    {
        $accessibleUnitIds = $this->getAccessibleUnitIds($user);

        // Super Admin: bypass
        if ($accessibleUnitIds === null) {
            return true;
        }

        if (empty($accessibleUnitIds)) {
            return false;
        }

        return \App\Models\CustomerProfile::where('user_id', $customerId)
            ->whereIn('unit_id', $accessibleUnitIds)
            ->exists();
    }
}
