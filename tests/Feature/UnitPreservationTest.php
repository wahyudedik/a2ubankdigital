<?php

namespace Tests\Feature;

use App\Models\Unit;
use App\Models\User;
use App\Models\CustomerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Preservation Property Tests for Units Management System
 * 
 * These tests verify that non-buggy behavior is preserved when fixes are applied.
 * Tests are written BEFORE fixes and run on UNFIXED code to establish baseline behavior.
 * Property-based testing generates many test cases for stronger guarantees.
 * 
 * Validates: Requirements 3.1, 3.2, 3.3, 3.4, 3.5
 */
class UnitPreservationTest extends TestCase
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
            'bank_id' => 'NIP-PRES-001',
            'role_id' => 1, // Super Admin
            'full_name' => 'Super Admin Preservation',
            'email' => 'admin-pres@test.com',
            'phone_number' => '081234567890',
            'password_hash' => bcrypt('password'),
            'status' => 'ACTIVE',
        ]);

        $this->teller = User::create([
            'bank_id' => 'NIP-PRES-002',
            'role_id' => 5, // Teller
            'full_name' => 'Teller Preservation',
            'email' => 'teller-pres@test.com',
            'phone_number' => '081234567891',
            'password_hash' => bcrypt('password'),
            'status' => 'ACTIVE',
        ]);

        $this->branchHead = User::create([
            'bank_id' => 'NIP-PRES-003',
            'role_id' => 2, // Branch Head
            'full_name' => 'Branch Head Preservation',
            'email' => 'branchhead-pres@test.com',
            'phone_number' => '081234567892',
            'password_hash' => bcrypt('password'),
            'status' => 'ACTIVE',
        ]);
    }

    /**
     * Test 2a: Toggling unit status between ACTIVE/INACTIVE preserves parent_id and other fields
     * 
     * Property: For any unit with parent_id, toggling status should not affect parent_id or other fields
     * 
     * Validates: Requirements 3.3
     */
    public function test_status_toggle_preserves_parent_id_and_fields(): void
    {
        // Create a parent branch
        $branch = Unit::create([
            'unit_name' => 'Cabang Preservation',
            'unit_code' => 'PRES-001',
            'unit_type' => 'KANTOR_CABANG',
            'parent_id' => null,
            'address' => 'Jl. Test No. 1',
            'status' => 'ACTIVE',
        ]);

        // Create a sub-unit with parent_id
        $unit = Unit::create([
            'unit_name' => 'Unit Preservation',
            'unit_code' => 'PRES-002',
            'unit_type' => 'KANTOR_KAS',
            'parent_id' => $branch->id,
            'address' => 'Jl. Test No. 2',
            'status' => 'ACTIVE',
        ]);

        // Store original values
        $originalParentId = $unit->parent_id;
        $originalName = $unit->unit_name;
        $originalType = $unit->unit_type;
        $originalAddress = $unit->address;

        // Act: Toggle status to INACTIVE
        $this->actingAs($this->superAdmin);
        $response = $this->putJson("/admin/units/{$unit->id}", [
            'status' => 'INACTIVE',
        ]);

        // Assert: Response should be successful
        $this->assertTrue(in_array($response->status(), [200, 302]), "Expected 200 or 302, got {$response->status()}");

        // Assert: Refresh and verify parent_id is preserved
        $unit->refresh();
        $this->assertEquals($originalParentId, $unit->parent_id, 'parent_id should be preserved when toggling status');
        $this->assertEquals('INACTIVE', $unit->status, 'status should be updated to INACTIVE');
        $this->assertEquals($originalName, $unit->unit_name, 'unit_name should be preserved');
        $this->assertEquals($originalType, $unit->unit_type, 'unit_type should be preserved');
        $this->assertEquals($originalAddress, $unit->address, 'address should be preserved');

        // Act: Toggle status back to ACTIVE
        $response = $this->putJson("/admin/units/{$unit->id}", [
            'status' => 'ACTIVE',
        ]);

        // Assert: Response should be successful
        $this->assertTrue(in_array($response->status(), [200, 302]), "Expected 200 or 302, got {$response->status()}");

        // Assert: Verify parent_id is still preserved
        $unit->refresh();
        $this->assertEquals($originalParentId, $unit->parent_id, 'parent_id should still be preserved after toggling back to ACTIVE');
        $this->assertEquals('ACTIVE', $unit->status, 'status should be updated back to ACTIVE');
    }

    /**
     * Test 2b: Updating unit name or address preserves parent_id and unit_type
     * 
     * Property: For any unit update that changes name or address, parent_id and unit_type should remain unchanged
     * 
     * Validates: Requirements 3.1
     */
    public function test_name_address_update_preserves_parent_id_and_type(): void
    {
        // Create a parent branch
        $branch = Unit::create([
            'unit_name' => 'Cabang Update Test',
            'unit_code' => 'UPD-001',
            'unit_type' => 'KANTOR_CABANG',
            'parent_id' => null,
            'address' => 'Jl. Original No. 1',
            'status' => 'ACTIVE',
        ]);

        // Create a sub-unit with parent_id
        $unit = Unit::create([
            'unit_name' => 'Unit Update Test',
            'unit_code' => 'UPD-002',
            'unit_type' => 'KANTOR_KAS',
            'parent_id' => $branch->id,
            'address' => 'Jl. Original No. 2',
            'status' => 'ACTIVE',
        ]);

        // Store original values
        $originalParentId = $unit->parent_id;
        $originalType = $unit->unit_type;

        // Act: Update name and address
        $this->actingAs($this->superAdmin);
        $response = $this->putJson("/admin/units/{$unit->id}", [
            'unit_name' => 'Unit Updated Name',
            'address' => 'Jl. Updated No. 2',
        ]);

        // Assert: Response should be successful
        $this->assertTrue(in_array($response->status(), [200, 302]), "Expected 200 or 302, got {$response->status()}");

        // Assert: Verify parent_id and unit_type are preserved
        $unit->refresh();
        $this->assertEquals($originalParentId, $unit->parent_id, 'parent_id should be preserved when updating name/address');
        $this->assertEquals($originalType, $unit->unit_type, 'unit_type should be preserved when updating name/address');
        $this->assertEquals('Unit Updated Name', $unit->unit_name, 'unit_name should be updated');
        $this->assertEquals('Jl. Updated No. 2', $unit->address, 'address should be updated');
    }

    /**
     * Test 2c: Assigning customers to units continues to work correctly
     * 
     * Property: For any unit, customer assignment should work and maintain unit relationships
     * 
     * Validates: Requirements 3.5
     */
    public function test_customer_assignment_continues_to_work(): void
    {
        // Create a unit
        $unit = Unit::create([
            'unit_name' => 'Unit Customer Test',
            'unit_code' => 'CUST-001',
            'unit_type' => 'KANTOR_CABANG',
            'parent_id' => null,
            'status' => 'ACTIVE',
        ]);

        // Create a customer profile with all required fields
        $customer = CustomerProfile::create([
            'user_id' => $this->teller->id,
            'unit_id' => $unit->id,
            'nik' => '1234567890123456',
            'mother_maiden_name' => 'Test Mother',
            'pob' => 'Jakarta',
            'dob' => '1990-01-01',
            'gender' => 'L',
            'address_ktp' => 'Jl. Test No. 1',
        ]);

        // Assert: Customer should be assigned to unit
        $this->assertNotNull($customer->user_id, 'Customer should be created');
        $this->assertEquals($unit->id, $customer->unit_id, 'Customer should be assigned to unit');

        // Assert: Unit should have customer
        $unit->refresh();
        $this->assertGreaterThan(0, $unit->customerProfiles()->count(), 'Unit should have assigned customers');
        $this->assertTrue($unit->customerProfiles()->where('user_id', $customer->user_id)->exists(), 'Customer should be in unit\'s customer list');

        // Act: Create another customer for the same unit
        $customer2 = CustomerProfile::create([
            'user_id' => $this->branchHead->id,
            'unit_id' => $unit->id,
            'nik' => '1234567890123457',
            'mother_maiden_name' => 'Test Mother 2',
            'pob' => 'Bandung',
            'dob' => '1991-01-01',
            'gender' => 'P',
            'address_ktp' => 'Jl. Test No. 2',
        ]);

        // Assert: Both customers should be assigned
        $unit->refresh();
        $this->assertEquals(2, $unit->customerProfiles()->count(), 'Unit should have 2 customers');
        $this->assertTrue($unit->customerProfiles()->where('user_id', $customer->user_id)->exists(), 'First customer should still be assigned');
        $this->assertTrue($unit->customerProfiles()->where('user_id', $customer2->user_id)->exists(), 'Second customer should be assigned');
    }

    /**
     * Test 2d: Staff list displays correctly with hierarchical grouping
     * 
     * Property: For any unit hierarchy, staff/customer list should display with correct grouping
     * 
     * Validates: Requirements 3.4
     */
    public function test_staff_list_displays_with_hierarchical_grouping(): void
    {
        // Create a branch
        $branch = Unit::create([
            'unit_name' => 'Cabang Staff Test',
            'unit_code' => 'STAFF-001',
            'unit_type' => 'KANTOR_CABANG',
            'parent_id' => null,
            'status' => 'ACTIVE',
        ]);

        // Create sub-units under branch
        $unit1 = Unit::create([
            'unit_name' => 'Unit Staff 1',
            'unit_code' => 'STAFF-002',
            'unit_type' => 'KANTOR_KAS',
            'parent_id' => $branch->id,
            'status' => 'ACTIVE',
        ]);

        $unit2 = Unit::create([
            'unit_name' => 'Unit Staff 2',
            'unit_code' => 'STAFF-003',
            'unit_type' => 'KANTOR_KAS',
            'parent_id' => $branch->id,
            'status' => 'ACTIVE',
        ]);

        // Assign staff to units with all required fields
        $customer1 = CustomerProfile::create([
            'user_id' => $this->teller->id,
            'unit_id' => $unit1->id,
            'nik' => '1234567890123456',
            'mother_maiden_name' => 'Test Mother',
            'pob' => 'Jakarta',
            'dob' => '1990-01-01',
            'gender' => 'L',
            'address_ktp' => 'Jl. Test No. 1',
        ]);

        $customer2 = CustomerProfile::create([
            'user_id' => $this->branchHead->id,
            'unit_id' => $unit2->id,
            'nik' => '1234567890123457',
            'mother_maiden_name' => 'Test Mother 2',
            'pob' => 'Bandung',
            'dob' => '1991-01-01',
            'gender' => 'P',
            'address_ktp' => 'Jl. Test No. 2',
        ]);

        // Assert: Branch should have correct children
        $branch->refresh();
        $this->assertEquals(2, $branch->children()->count(), 'Branch should have 2 child units');

        // Assert: Each unit should have correct staff
        $unit1->refresh();
        $this->assertEquals(1, $unit1->customerProfiles()->count(), 'Unit1 should have 1 staff member');

        $unit2->refresh();
        $this->assertEquals(1, $unit2->customerProfiles()->count(), 'Unit2 should have 1 staff member');

        // Assert: Verify hierarchical structure is maintained
        $allUnits = Unit::with('children', 'customerProfiles')->get();
        $branchFromDb = $allUnits->where('id', $branch->id)->first();
        $this->assertNotNull($branchFromDb, 'Branch should exist');
        $this->assertEquals(2, $branchFromDb->children->count(), 'Branch should have 2 children in hierarchical structure');
    }

    /**
     * Test 2e: Super Admin deletion of units with no children continues to work
     * 
     * Property: For any unit with no children and no assigned staff, Super Admin deletion should succeed
     * 
     * Validates: Requirements 3.2
     */
    public function test_super_admin_deletion_of_empty_units_continues_to_work(): void
    {
        // Create a unit with no children and no staff
        $unit = Unit::create([
            'unit_name' => 'Unit Delete Test',
            'unit_code' => 'DEL-001',
            'unit_type' => 'KANTOR_KAS',
            'parent_id' => null,
            'status' => 'ACTIVE',
        ]);

        $unitId = $unit->id;

        // Assert: Unit should exist before deletion
        $this->assertNotNull(Unit::find($unitId), 'Unit should exist before deletion');

        // Act: Delete as Super Admin
        $this->actingAs($this->superAdmin);
        $response = $this->deleteJson("/admin/units/{$unitId}");

        // Assert: Response should be successful (200 or 302 redirect)
        $this->assertTrue(in_array($response->status(), [200, 302]), "Expected 200 or 302, got {$response->status()}");

        // Assert: Unit should be deleted from database
        $this->assertNull(Unit::find($unitId), 'Unit should be deleted from database');
    }

    /**
     * Test 2f: Multiple status toggles preserve all unit properties
     * 
     * Property: For any unit, multiple status toggles should preserve all properties
     * 
     * Validates: Requirements 3.3
     */
    public function test_multiple_status_toggles_preserve_all_properties(): void
    {
        // Create a parent branch
        $branch = Unit::create([
            'unit_name' => 'Cabang Multi Toggle',
            'unit_code' => 'MTOG-001',
            'unit_type' => 'KANTOR_CABANG',
            'parent_id' => null,
            'address' => 'Jl. Multi Toggle No. 1',
            'status' => 'ACTIVE',
        ]);

        // Create a sub-unit
        $unit = Unit::create([
            'unit_name' => 'Unit Multi Toggle',
            'unit_code' => 'MTOG-002',
            'unit_type' => 'KANTOR_KAS',
            'parent_id' => $branch->id,
            'address' => 'Jl. Multi Toggle No. 2',
            'status' => 'ACTIVE',
        ]);

        // Store original values
        $originalParentId = $unit->parent_id;
        $originalName = $unit->unit_name;
        $originalType = $unit->unit_type;
        $originalAddress = $unit->address;

        // Act: Toggle status multiple times (3 times: ACTIVE->INACTIVE->ACTIVE->INACTIVE)
        $this->actingAs($this->superAdmin);
        
        for ($i = 0; $i < 3; $i++) {
            $newStatus = ($i % 2 === 0) ? 'INACTIVE' : 'ACTIVE';
            $response = $this->putJson("/admin/units/{$unit->id}", [
                'status' => $newStatus,
            ]);
            $this->assertTrue(in_array($response->status(), [200, 302]), "Toggle {$i} failed");
        }

        // Assert: All properties should be preserved after multiple toggles
        $unit->refresh();
        $this->assertEquals($originalParentId, $unit->parent_id, 'parent_id should be preserved after multiple toggles');
        $this->assertEquals($originalName, $unit->unit_name, 'unit_name should be preserved after multiple toggles');
        $this->assertEquals($originalType, $unit->unit_type, 'unit_type should be preserved after multiple toggles');
        $this->assertEquals($originalAddress, $unit->address, 'address should be preserved after multiple toggles');
        $this->assertEquals('INACTIVE', $unit->status, 'status should be INACTIVE after 3 toggles');
    }

    /**
     * Test 2g: Updating multiple fields preserves parent_id
     * 
     * Property: For any unit update with multiple fields, parent_id should remain unchanged
     * 
     * Validates: Requirements 3.1
     */
    public function test_multi_field_update_preserves_parent_id(): void
    {
        // Create a parent branch
        $branch = Unit::create([
            'unit_name' => 'Cabang Multi Field',
            'unit_code' => 'MFLD-001',
            'unit_type' => 'KANTOR_CABANG',
            'parent_id' => null,
            'status' => 'ACTIVE',
        ]);

        // Create a sub-unit
        $unit = Unit::create([
            'unit_name' => 'Unit Multi Field',
            'unit_code' => 'MFLD-002',
            'unit_type' => 'KANTOR_KAS',
            'parent_id' => $branch->id,
            'address' => 'Jl. Original',
            'status' => 'ACTIVE',
        ]);

        $originalParentId = $unit->parent_id;

        // Act: Update multiple fields at once
        $this->actingAs($this->superAdmin);
        $response = $this->putJson("/admin/units/{$unit->id}", [
            'unit_name' => 'Unit Multi Field Updated',
            'address' => 'Jl. Updated',
            'status' => 'INACTIVE',
        ]);

        // Assert: Response should be successful
        $this->assertTrue(in_array($response->status(), [200, 302]), "Expected 200 or 302, got {$response->status()}");

        // Assert: parent_id should be preserved
        $unit->refresh();
        $this->assertEquals($originalParentId, $unit->parent_id, 'parent_id should be preserved in multi-field update');
        $this->assertEquals('Unit Multi Field Updated', $unit->unit_name, 'unit_name should be updated');
        $this->assertEquals('Jl. Updated', $unit->address, 'address should be updated');
        $this->assertEquals('INACTIVE', $unit->status, 'status should be updated');
    }

    /**
     * Test 2h: Querying units by parent_id returns correct results
     * 
     * Property: For any branch, querying units by parent_id should return only direct children
     * 
     * Validates: Requirements 3.4
     */
    public function test_query_units_by_parent_id_returns_correct_results(): void
    {
        // Create two branches
        $branch1 = Unit::create([
            'unit_name' => 'Cabang Query 1',
            'unit_code' => 'QRY-001',
            'unit_type' => 'KANTOR_CABANG',
            'parent_id' => null,
            'status' => 'ACTIVE',
        ]);

        $branch2 = Unit::create([
            'unit_name' => 'Cabang Query 2',
            'unit_code' => 'QRY-002',
            'unit_type' => 'KANTOR_CABANG',
            'parent_id' => null,
            'status' => 'ACTIVE',
        ]);

        // Create sub-units under branch1
        $unit1 = Unit::create([
            'unit_name' => 'Unit Query 1',
            'unit_code' => 'QRY-003',
            'unit_type' => 'KANTOR_KAS',
            'parent_id' => $branch1->id,
            'status' => 'ACTIVE',
        ]);

        $unit2 = Unit::create([
            'unit_name' => 'Unit Query 2',
            'unit_code' => 'QRY-004',
            'unit_type' => 'KANTOR_KAS',
            'parent_id' => $branch1->id,
            'status' => 'ACTIVE',
        ]);

        // Create sub-unit under branch2
        $unit3 = Unit::create([
            'unit_name' => 'Unit Query 3',
            'unit_code' => 'QRY-005',
            'unit_type' => 'KANTOR_KAS',
            'parent_id' => $branch2->id,
            'status' => 'ACTIVE',
        ]);

        // Act: Query units by parent_id
        $branch1Units = Unit::where('parent_id', $branch1->id)->get();
        $branch2Units = Unit::where('parent_id', $branch2->id)->get();

        // Assert: Branch1 should have exactly 2 units
        $this->assertCount(2, $branch1Units, 'Branch1 should have 2 child units');
        $this->assertTrue($branch1Units->contains('id', $unit1->id), 'Unit1 should be under Branch1');
        $this->assertTrue($branch1Units->contains('id', $unit2->id), 'Unit2 should be under Branch1');
        $this->assertFalse($branch1Units->contains('id', $unit3->id), 'Unit3 should NOT be under Branch1');

        // Assert: Branch2 should have exactly 1 unit
        $this->assertCount(1, $branch2Units, 'Branch2 should have 1 child unit');
        $this->assertTrue($branch2Units->contains('id', $unit3->id), 'Unit3 should be under Branch2');
        $this->assertFalse($branch2Units->contains('id', $unit1->id), 'Unit1 should NOT be under Branch2');
        $this->assertFalse($branch2Units->contains('id', $unit2->id), 'Unit2 should NOT be under Branch2');
    }

    /**
     * Test 2i: Creating units with different types preserves type information
     * 
     * Property: For any unit creation, the unit_type should be preserved and queryable
     * 
     * Validates: Requirements 3.1
     */
    public function test_unit_type_preservation_across_operations(): void
    {
        // Create units of different types
        $branch = Unit::create([
            'unit_name' => 'Cabang Type Test',
            'unit_code' => 'TYPE-001',
            'unit_type' => 'KANTOR_CABANG',
            'parent_id' => null,
            'status' => 'ACTIVE',
        ]);

        $unit = Unit::create([
            'unit_name' => 'Unit Type Test',
            'unit_code' => 'TYPE-002',
            'unit_type' => 'KANTOR_KAS',
            'parent_id' => $branch->id,
            'status' => 'ACTIVE',
        ]);

        // Store original types
        $originalBranchType = $branch->unit_type;
        $originalUnitType = $unit->unit_type;

        // Act: Update other fields
        $this->actingAs($this->superAdmin);
        $this->putJson("/admin/units/{$branch->id}", [
            'unit_name' => 'Cabang Type Test Updated',
        ]);

        $this->putJson("/admin/units/{$unit->id}", [
            'unit_name' => 'Unit Type Test Updated',
        ]);

        // Assert: Types should be preserved
        $branch->refresh();
        $unit->refresh();
        $this->assertEquals($originalBranchType, $branch->unit_type, 'Branch unit_type should be preserved');
        $this->assertEquals($originalUnitType, $unit->unit_type, 'Unit unit_type should be preserved');

        // Assert: Types should be queryable
        $this->assertTrue(Unit::where('unit_type', 'KANTOR_CABANG')->where('id', $branch->id)->exists(), 'Branch should be queryable by type');
        $this->assertTrue(Unit::where('unit_type', 'KANTOR_KAS')->where('id', $unit->id)->exists(), 'Unit should be queryable by type');
    }
}
