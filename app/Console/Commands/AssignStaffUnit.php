<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Console\Command;

class AssignStaffUnit extends Command
{
    protected $signature = 'staff:assign-unit
                            {--user= : ID atau email staf yang akan di-assign}
                            {--unit= : ID unit yang akan di-assign}
                            {--all=false : Assign unit ke SEMUA staf yang belum punya unit}
                            {--reset=false : Reset unit_id staf menjadi NULL}';

    protected $description = 'Interaktif assign unit ke staff. Tanpa parameter = mode interaktif penuh.';

    public function handle(): int
    {
        $userId = $this->option('user');
        $unitId = $this->option('unit');
        $assignAll = $this->option('all');
        $reset = $this->option('reset');

        // Mode: Reset unit
        if ($reset) {
            return $this->handleReset();
        }

        // Mode: Assign all staff without unit
        if ($assignAll) {
            return $this->handleAssignAll();
        }

        // Mode: Specific user + unit (non-interactive)
        if ($userId && $unitId) {
            return $this->handleSpecific($userId, (int) $unitId);
        }

        // Mode: Interactive (no options or partial options)
        return $this->handleInteractive();
    }

    /**
     * Interactive mode - show all staff and units, let user choose.
     */
    private function handleInteractive(): int
    {
        $this->newLine();
        $this->info('╔══════════════════════════════════════════════════╗');
        $this->info('║   🏦 ASSIGN UNIT KE STAFF - A2U Bank Digital   ║');
        $this->info('╚══════════════════════════════════════════════════╝');
        $this->newLine();

        // Show all units
        $units = Unit::with('parent')->orderBy('id')->get();
        $this->info('📋 Daftar Unit/Cabang:');
        $this->line('─────────────────────────────────────────────');
        foreach ($units as $unit) {
            $prefix = $unit->parent_id ? '  └─ ' : '';
            $type = $unit->unit_type === 'KANTOR_CABANG' ? '🏢' : '🏪';
            $this->line("  {$unit->id}. {$prefix}{$type} {$unit->name} ({$unit->code})");
        }
        $this->newLine();

        // Show all staff (role 1-8, not customer)
        $staff = User::with(['role', 'unit'])
            ->whereIn('role_id', range(1, 8))
            ->orderBy('role_id')
            ->orderBy('full_name')
            ->get();

        $this->info('👥 Daftar Staf:');
        $this->line('─────────────────────────────────────────────');
        foreach ($staff as $s) {
            $currentUnit = $s->unit ? $s->unit->name . ' (' . $s->unit->code . ')' : '❌ BELUM DIPASSING';
            $roleName = $s->role->role_name ?? 'Unknown';
            $this->line("  {$s->id}. {$s->full_name} | Role: {$roleName} | Unit: {$currentUnit}");
        }
        $this->newLine();

        // Ask which staff to assign
        $staffId = $this->ask('Masukkan ID staf yang ingin di-assign unit');
        $staffUser = User::find($staffId);

        if (!$staffUser) {
            $this->error("Staf dengan ID {$staffId} tidak ditemukan.");
            return 1;
        }

        if ($staffUser->role_id == Role::SUPER_ADMIN) {
            $this->warn('Super Admin tidak perlu di-assign unit (bypass semua filter).');
            if (!$this->confirm('Tetap lanjutkan?', false)) {
                return 0;
            }
        }

        if ($staffUser->role_id == Role::CUSTOMER) {
            $this->error('User ini adalah Nasabah, bukan Staf. Nasabah di-assign via customer_profiles.unit_id.');
            return 1;
        }

        $this->info("Assigning unit untuk: {$staffUser->full_name} ({$staffUser->role->role_name})");

        // Ask which unit
        $unitInput = $this->ask('Masukkan ID unit yang akan di-assign');

        // Validate unit input
        if (!is_numeric($unitInput) || $unitInput <= 0) {
            $this->error('ID unit harus berupa angka positif.');
            return 1;
        }

        $unit = Unit::find((int) $unitInput);
        if (!$unit) {
            $this->error("Unit dengan ID {$unitInput} tidak ditemukan.");
            return 1;
        }

        // Confirm
        $this->newLine();
        $this->line("  Staf: {$staffUser->full_name} ({$staffUser->role->role_name})");
        $this->line("  Unit: {$unit->name} ({$unit->code})");
        $this->newLine();

        if ($this->confirm('Konfirmasi assign unit ini?', true)) {
            $staffUser->update(['unit_id' => $unit->id]);
            $this->info("✅ Berhasil assign unit ke {$staffUser->full_name}!");
        } else {
            $this->warn('Dibatalkan.');
        }

        return 0;
    }

    /**
     * Assign all staff without unit to the selected unit.
     */
    private function handleAssignAll(): int
    {
        $this->newLine();
        $this->info('📋 Semua staf tanpa unit:');
        $this->line('─────────────────────────────────────────────');

        $staffWithoutUnit = User::with('role')
            ->whereIn('role_id', range(1, 8))
            ->whereNull('unit_id')
            ->get();

        if ($staffWithoutUnit->isEmpty()) {
            $this->info('✅ Semua staf sudah punya unit. Tidak ada yang perlu di-assign.');
            return 0;
        }

        foreach ($staffWithoutUnit as $s) {
            $roleName = $s->role->role_name ?? 'Unknown';
            $this->line("  ID: {$s->id} | {$s->full_name} | Role: {$roleName}");
        }

        $this->newLine();

        // Show units
        $units = Unit::orderBy('id')->get();
        $this->info('📋 Daftar Unit:');
        foreach ($units as $unit) {
            $this->line("  {$unit->id}. {$unit->name} ({$unit->code})");
        }

        $unitId = $this->ask('Masukkan ID unit untuk semua staf di atas');
        $unit = Unit::find((int) $unitId);

        if (!$unit) {
            $this->error("Unit dengan ID {$unitId} tidak ditemukan.");
            return 1;
        }

        if (!$this->confirm("Assign {$staffWithoutUnit->count()} staf ke {$unit->name}?", true)) {
            $this->warn('Dibatalkan.');
            return 0;
        }

        $count = 0;
        foreach ($staffWithoutUnit as $s) {
            // Kepala Cabang harus di-assign ke level cabang (KANTOR_CABANG)
            // Kepala Unit, Marketing, Teller, CS, Analis, DC harus di-assign ke level unit (KANTOR_KAS)
            $s->update(['unit_id' => $unit->id]);
            $this->line("  ✅ {$s->full_name} → {$unit->name}");
            $count++;
        }

        $this->newLine();
        $this->info("✅ Berhasil assign unit ke {$count} staf!");
        return 0;
    }

    /**
     * Assign specific user to specific unit (non-interactive).
     */
    private function handleSpecific(string $userId, int $unitId): int
    {
        // Find user by ID or email
        $user = is_numeric($userId)
            ? User::find((int) $userId)
            : User::where('email', $userId)->first();

        if (!$user) {
            $this->error("Staf dengan ID/email '{$userId}' tidak ditemukan.");
            return 1;
        }

        if ($user->role_id == Role::CUSTOMER) {
            $this->error('User ini adalah Nasabah, bukan Staf.');
            return 1;
        }

        $unit = Unit::find($unitId);
        if (!$unit) {
            $this->error("Unit dengan ID {$unitId} tidak ditemukan.");
            return 1;
        }

        $user->update(['unit_id' => $unitId]);
        $this->info("✅ {$user->full_name} → {$unit->name} ({$unit->code})");
        return 0;
    }

    /**
     * Reset all staff unit_id to NULL.
     */
    private function handleReset(): int
    {
        $this->warn('⚠️  Ini akan menghapus SEMUA unit assignment dari staf!');

        $count = User::whereIn('role_id', range(1, 8))
            ->whereNotNull('unit_id')
            ->count();

        $this->info("Staf yang akan di-reset: {$count}");

        if ($count === 0) {
            $this->info('Tidak ada staf yang perlu di-reset.');
            return 0;
        }

        if (!$this->confirm('Konfirmasi reset semua unit assignment?', false)) {
            $this->warn('Dibatalkan.');
            return 0;
        }

        User::whereIn('role_id', range(1, 8))
            ->whereNotNull('unit_id')
            ->update(['unit_id' => null]);

        $this->info("✅ Berhasil reset unit_id {$count} staf.");
        return 0;
    }
}
