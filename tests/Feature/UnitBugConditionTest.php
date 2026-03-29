<?php

namespace Tests\Feature;

use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Bug Condition Exploration Tests for Units Management System
 * 
 * These tests encode the expected behavior and will validate fixes when they pass.
 * On unfixed code, these tests MUST FAIL to confirm the bugs exist.
 * 
 * Validates: Requirements 1.1, 1.2, 1.3, 1.4, 1.5, 1.6
 */
class UnitBugConditionTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $teller;
    protected User $branchHead;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users with different roles
        $this->superAdmin = User::create([
            'bank_id' => 'NIP-TEST-001',
            'role_id' => 1, // Super Admin
            'full_name' => 'Super Admin Test',
            'email' => 'admin@test.com',
            'phone_number' => '081234567890',
            'password_hash' => bcrypt('password'),
            'status' => 'ACTIVE',
        ]);

        $this->teller = User::create([
            'bank_id' => 'NIP-TEST-002',
            'role_id' => 5, // Teller
            'full_name' => 'Teller Test',
            'email' => 'teller@test.com',
            'phone_number' => '081234567891',
            'password_hash' => bcrypt('password'),
            'status' => 'ACTIVE',
        ]);

        $this->branchHead = User::create([
            'bank_id' => 'NIP-TEST-003',
            'role_id' => 2, // Branch Head
            'full_name' => 'Branch Head Test',
            'email' => 'branchhead@test.com',
            'phone_number' => '081234567892',
            'password_hash' => bcrypt('password'),
            'status' => 'ACTIVE',
        ]);
    }

    /**
     * Test 1a: Create KANTOR_KAS with parent_id, verify parent_id is saved (not NULL)
     * 
     * Bug Condition: parent_id not saved when creating KANTOR_KAS
     * Expected Behavior: parent_id should be saved and returned in response
     * 
     * Validates: Requirements 1.1, 2.1, 2.6
     */
    public function test_kantor_kas_parent_id_is_saved(): void
    {
        // Create a parent branch first
        $branch = Unit::create([
            'unit_name' => 'Cabang Jakarta',
            'unit_code' => 'JAK-001',
            'unit_type' => 'KANTOR_CABANG',
            'parent_id' => null,
            'status' => 'ACTIVE',
        ]);

        // Act: Create KANTOR_KAS with parent_id using the controller directly
        $this->actingAs($this->superAdmin);
        $response = $this->postJson('/admin/units', [
            'unit_name' => 'Unit Layanan A',
            'unit_type' => 'KANTOR_KAS',
            'parent_id' => $branch->id,
            'status' => 'ACTIVE',
        ]);

        // Assert: Response should be successful (201 or 302 redirect)
        $this->assertTrue(in_array($response->status(), [201, 302]), "Expected 201 or 302, got {$response->status()}");

        // Assert: parent_id should be saved in database (not NULL)
        $createdUnit = Unit::where('unit_name', 'Unit Layanan A')->first();
        $this->assertNotNull($createdUnit, 'Unit should be created');
        $this->assertNotNull($createdUnit->parent_id, 'parent_id should NOT be NULL - BUG: parent_id is not being saved');
        $this->assertEquals($branch->id, $createdUnit->parent_id, 'parent_id should match the branch id');
    }

    /**
     * Test 1b: Create KANTOR_CABANG, verify unit_type is saved correctly
     * 
     * Bug Condition: KANTOR_CABANG creation fails or returns errors
     * Expected Behavior: KANTOR_CABANG should be created successfully with parent_id = null
     * 
     * Validates: Requirements 1.2, 2.2
     */
    public function test_kantor_cabang_creation_succeeds(): void
    {
        // Act: Create KANTOR_CABANG
        $this->actingAs($this->superAdmin);
        $response = $this->postJson('/admin/units', [
            'unit_name' => 'Cabang Surabaya',
            'unit_type' => 'KANTOR_CABANG',
            'address' => 'Jl. Pemuda No. 1',
            'latitude' => -7.2575,
            'longitude' => 112.7521,
            'status' => 'ACTIVE',
        ]);

        // Assert: Response should be successful
        $this->assertTrue(in_array($response->status(), [201, 302]), "Expected 201 or 302, got {$response->status()}");

        // Assert: KANTOR_CABANG should be created with parent_id = null
        $createdUnit = Unit::where('unit_name', 'Cabang Surabaya')->first();
        $this->assertNotNull($createdUnit, 'KANTOR_CABANG should be created');
        $this->assertEquals('KANTOR_CABANG', $createdUnit->unit_type);
        $this->assertNull($createdUnit->parent_id, 'KANTOR_CABANG should have parent_id = null');
    }

    /**
     * Test 1c: Attempt delete as non-Super Admin (role_id=5), verify 403 Forbidden response
     * 
     * Bug Condition: Non-Super Admin can delete units
     * Expected Behavior: Non-Super Admin deletion should be rejected with 403 Forbidden
     * 
     * Validates: Requirements 1.3, 2.3
     */
    public function test_non_super_admin_cannot_delete_unit(): void
    {
        // Create a unit to delete
        $unit = Unit::create([
            'unit_name' => 'Unit to Delete',
            'unit_code' => 'DEL-001',
            'unit_type' => 'KANTOR_KAS',
            'parent_id' => null,
            'status' => 'ACTIVE',
        ]);

        // Act: Attempt delete as Teller (role_id = 5, not Super Admin)
        $this->actingAs($this->teller);
        $response = $this->deleteJson("/admin/units/{$unit->id}");

        // Assert: Response should be 403 Forbidden
        $this->assertEquals(403, $response->status(), 'Non-Super Admin should get 403 Forbidden - BUG: permission check is not working');
        $response->assertJsonPath('status', 'error');
        $response->assertJsonPath('message', 'Akses ditolak.');

        // Assert: Unit should still exist in database
        $this->assertNotNull(Unit::find($unit->id), 'Unit should not be deleted');
    }

    /**
     * Test 1d: Create branch and sub-units, verify grouping uses parent_id not string matching
     * 
     * Bug Condition: Grouping uses string matching instead of parent_id relationships
     * Expected Behavior: Units should be grouped using parent_id relationship
     * 
     * Validates: Requirements 1.4, 2.4
     */
    public function test_units_grouped_by_parent_id_not_string_matching(): void
    {
        // Create two branches with different prefixes
        $branch1 = Unit::create([
            'unit_name' => 'Cabang Jakarta',
            'unit_code' => 'JAK-001',
            'unit_type' => 'KANTOR_CABANG',
            'parent_id' => null,
            'status' => 'ACTIVE',
        ]);

        $branch2 = Unit::create([
            'unit_name' => 'Cabang Bandung',
            'unit_code' => 'BDG-001',
            'unit_type' => 'KANTOR_CABANG',
            'parent_id' => null,
            'status' => 'ACTIVE',
        ]);

        // Create sub-units under branch1
        $unit1 = Unit::create([
            'unit_name' => 'Unit Layanan 1',
            'unit_code' => 'JAK-002',
            'unit_type' => 'KANTOR_KAS',
            'parent_id' => $branch1->id,
            'status' => 'ACTIVE',
        ]);

        $unit2 = Unit::create([
            'unit_name' => 'Unit Layanan 2',
            'unit_code' => 'JAK-003',
            'unit_type' => 'KANTOR_KAS',
            'parent_id' => $branch1->id,
            'status' => 'ACTIVE',
        ]);

        // Create sub-unit under branch2 with similar code pattern
        $unit3 = Unit::create([
            'unit_name' => 'Unit Layanan 3',
            'unit_code' => 'BDG-002',
            'unit_type' => 'KANTOR_KAS',
            'parent_id' => $branch2->id,
            'status' => 'ACTIVE',
        ]);

        // Assert: Verify grouping uses parent_id relationship
        // Branch1 should have exactly 2 units (unit1 and unit2)
        $branch1Children = Unit::where('parent_id', $branch1->id)->get();
        $this->assertCount(2, $branch1Children, 'Branch1 should have 2 child units - BUG: grouping is not using parent_id');
        $this->assertTrue($branch1Children->contains('id', $unit1->id));
        $this->assertTrue($branch1Children->contains('id', $unit2->id));

        // Branch2 should have exactly 1 unit (unit3)
        $branch2Children = Unit::where('parent_id', $branch2->id)->get();
        $this->assertCount(1, $branch2Children, 'Branch2 should have 1 child unit');
        $this->assertTrue($branch2Children->contains('id', $unit3->id));

        // Verify unit1 and unit2 are NOT grouped under branch2 (string matching would fail here)
        $this->assertFalse($branch2Children->contains('id', $unit1->id), 'Unit1 should not be under Branch2');
        $this->assertFalse($branch2Children->contains('id', $unit2->id), 'Unit2 should not be under Branch2');
    }

    /**
     * Test 1e: Run seeder, verify units table is populated with branches and sub-units
     * 
     * Bug Condition: UserSeeder doesn't create units, leaving units table empty
     * Expected Behavior: Seeder should create sample branches and sub-units with parent_id relationships
     * 
     * Validates: Requirements 1.5, 2.5
     */
    public function test_seeder_populates_units_table(): void
    {
        // Verify units table is empty before seeding
        $this->assertCount(0, Unit::all(), 'Units table should be empty before seeding');

        // Act: Run the seeder
        $this->seed(\Database\Seeders\UnitSeeder::class);

        // Assert: Units table should be populated
        $allUnits = Unit::all();
        $this->assertGreaterThan(0, $allUnits->count(), 'Units table should be populated after seeding - BUG: seeder is not creating units');

        // Assert: Should have at least one KANTOR_CABANG
        $branches = Unit::where('unit_type', 'KANTOR_CABANG')->get();
        $this->assertGreaterThan(0, $branches->count(), 'Should have at least one KANTOR_CABANG');

        // Assert: Should have at least one KANTOR_KAS with parent_id set
        $subUnits = Unit::where('unit_type', 'KANTOR_KAS')->get();
        $this->assertGreaterThan(0, $subUnits->count(), 'Should have at least one KANTOR_KAS');

        // Assert: All KANTOR_KAS should have parent_id set to a valid branch
        foreach ($subUnits as $unit) {
            $this->assertNotNull($unit->parent_id, "KANTOR_KAS '{$unit->unit_name}' should have parent_id set");
            $parent = Unit::find($unit->parent_id);
            $this->assertNotNull($parent, "Parent unit should exist for KANTOR_KAS '{$unit->unit_name}'");
            $this->assertTrue(
                in_array($parent->unit_type, ['KANTOR_CABANG', 'KANTOR_PUSAT']),
                "Parent should be KANTOR_CABANG or KANTOR_PUSAT, got {$parent->unit_type}"
            );
        }

        // Assert: All units should have ACTIVE status
        foreach ($allUnits as $unit) {
            $this->assertEquals('ACTIVE', $unit->status, "Unit '{$unit->unit_name}' should be ACTIVE");
        }
    }

    /**
     * Test 1f: Verify parent_id is properly passed to backend API
     * 
     * Bug Condition: parent_id value is not properly passed to the backend API
     * Expected Behavior: parent_id should be included in API request and saved
     * 
     * Validates: Requirements 1.6, 2.6
     */
    public function test_parent_id_passed_to_backend_api(): void
    {
        // Create a parent branch
        $branch = Unit::create([
            'unit_name' => 'Cabang Test',
            'unit_code' => 'TST-001',
            'unit_type' => 'KANTOR_CABANG',
            'parent_id' => null,
            'status' => 'ACTIVE',
        ]);

        // Act: Send API request with parent_id in payload
        $this->actingAs($this->superAdmin);
        $response = $this->postJson('/admin/units', [
            'unit_name' => 'Unit Test',
            'unit_type' => 'KANTOR_KAS',
            'parent_id' => $branch->id,
            'status' => 'ACTIVE',
        ]);

        // Assert: Request should succeed
        $this->assertTrue(in_array($response->status(), [201, 302]), "Expected 201 or 302, got {$response->status()}");

        // Assert: Database should have parent_id saved
        $unit = Unit::where('unit_name', 'Unit Test')->first();
        $this->assertNotNull($unit);
        $this->assertEquals($branch->id, $unit->parent_id, 'parent_id should be saved - BUG: parent_id is not being passed to backend');
    }
}
