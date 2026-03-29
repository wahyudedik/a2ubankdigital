# Implementation Plan: Units Management System Bugfix

## Overview

This implementation plan follows the exploratory bugfix workflow:
1. **Explore** - Write tests BEFORE fix to understand the bugs (Bug Condition)
2. **Preserve** - Write tests for non-buggy behavior (Preservation Requirements)
3. **Implement** - Apply the fixes with understanding (Expected Behavior)
4. **Validate** - Verify fixes work and don't break anything

---

## Phase 1: Bug Exploration

- [x] 1. Write bug condition exploration tests
  - **Property 1: Bug Condition** - Parent ID Not Saved in Unit Creation
  - **CRITICAL**: This test MUST FAIL on unfixed code - failure confirms the bugs exist
  - **DO NOT attempt to fix the test or the code when it fails**
  - **NOTE**: This test encodes the expected behavior - it will validate the fixes when it passes after implementation
  - **GOAL**: Surface counterexamples that demonstrate the bugs exist
  - **Scoped PBT Approach**: For deterministic bugs, scope the property to concrete failing cases to ensure reproducibility
  - Test implementation details from Bug Condition in design:
    - Test 1a: Create KANTOR_KAS with parent_id=5, verify parent_id is saved (not NULL)
    - Test 1b: Create KANTOR_CABANG, verify unit_type is saved correctly
    - Test 1c: Attempt delete as non-Super Admin (role_id=5), verify 403 Forbidden response
    - Test 1d: Create branch and sub-units, verify grouping uses parent_id not string matching
    - Test 1e: Run seeder, verify units table is populated with branches and sub-units
  - The test assertions should match the Expected Behavior Properties from design
  - Run test on UNFIXED code
  - **EXPECTED OUTCOME**: Tests FAIL (this is correct - it proves the bugs exist)
  - Document counterexamples found to understand root causes:
    - parent_id is NULL after creating KANTOR_KAS with parent_id
    - Units are grouped incorrectly or not at all
    - Non-Super Admin users can delete units
    - Units table is empty after seeding
  - Mark task complete when tests are written, run, and failures are documented
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.6_

---

## Phase 2: Preservation Testing

- [x] 2. Write preservation property tests (BEFORE implementing fixes)
  - **Property 2: Preservation** - Non-Buggy Unit Operations Behavior
  - **IMPORTANT**: Follow observation-first methodology
  - Observe behavior on UNFIXED code for non-buggy inputs (operations that don't involve parent_id bugs, unauthorized deletion, or seeding)
  - Write property-based tests capturing observed behavior patterns from Preservation Requirements:
    - Test 2a: Toggling unit status between ACTIVE/INACTIVE preserves parent_id and other fields
    - Test 2b: Updating unit name or address preserves parent_id and unit_type
    - Test 2c: Assigning customers to units continues to work correctly
    - Test 2d: Staff list displays correctly with hierarchical grouping
    - Test 2e: Super Admin deletion of units with no children continues to work
  - Property-based testing generates many test cases for stronger guarantees
  - Run tests on UNFIXED code
  - **EXPECTED OUTCOME**: Tests PASS (this confirms baseline behavior to preserve)
  - Mark task complete when tests are written, run, and passing on unfixed code
  - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5_

---

## Phase 3: Implementation

- [x] 3. Fix units management system

  - [x] 3.1 Fix UnitModal to properly pass parent_id
    - File: `resources/js/components/modals/UnitModal.jsx`
    - Ensure parent_id is included in API payload when creating KANTOR_KAS
    - Handle empty parent_id for KANTOR_CABANG (send as null)
    - Add client-side validation to ensure parent_id is selected when unit_type is KANTOR_KAS
    - Verify formData includes parent_id in the API call
    - _Bug_Condition: isBugCondition(input) where input.operation = 'CREATE_UNIT' AND input.unitData.parent_id IS NULL AND input.unitData.unit_type = 'KANTOR_KAS'_
    - _Expected_Behavior: expectedBehavior(result) - parent_id is saved and returned in response_
    - _Preservation: Status updates, name/address updates, customer assignments continue to work_
    - _Requirements: 2.1, 2.6_

  - [x] 3.2 Fix UnitController store/update methods to handle parent_id correctly
    - File: `app/Http/Controllers/Admin/UnitController.php`
    - Ensure parent_id is included in validation and response
    - Validate parent_id exists when provided
    - Handle parent_id for different unit types (null for KANTOR_CABANG/KANTOR_PUSAT, required for KANTOR_KAS)
    - Include parent relationship in response
    - _Bug_Condition: isBugCondition(input) where input.operation = 'CREATE_UNIT' AND parent_id is not properly handled_
    - _Expected_Behavior: expectedBehavior(result) - parent_id is saved and returned correctly_
    - _Preservation: Existing unit updates continue to work_
    - _Requirements: 2.1, 2.2, 2.6_

  - [x] 3.3 Fix AdminPageController.units() to use parent_id instead of string matching
    - File: `app/Http/Controllers/Inertia/AdminPageController.php`
    - Replace string matching logic with parent_id relationship check
    - Change from `str_starts_with($u->unit_code, ...)` to `$u->parent_id === $branch->id`
    - Use eager loading to avoid N+1 queries
    - Simplify grouping logic to use parent_id directly
    - _Bug_Condition: isBugCondition(input) where input.operation = 'GROUP_UNITS' AND groupingUsesStringMatching_
    - _Expected_Behavior: expectedBehavior(result) - units are grouped using parent_id relationships_
    - _Preservation: Staff list display and other queries continue to work_
    - _Requirements: 2.4_

  - [x] 3.4 Add permission check to UnitController.destroy() method
    - File: `app/Http/Controllers/Admin/UnitController.php`
    - Verify user has role_id === 1 (Super Admin) before allowing deletion
    - Return 403 Forbidden for non-Super Admin users
    - Ensure permission check is enforced on all delete requests
    - _Bug_Condition: isBugCondition(input) where input.operation = 'DELETE_UNIT' AND input.userRole != 1_
    - _Expected_Behavior: expectedBehavior(result) - deletion is rejected with 403 Forbidden_
    - _Preservation: Super Admin deletion continues to work_
    - _Requirements: 2.3_

  - [x] 3.5 Create UnitSeeder with sample branches and sub-units
    - File: `database/seeders/UnitSeeder.php` (NEW FILE)
    - Create KANTOR_PUSAT (headquarters) unit
    - Create 2-3 KANTOR_CABANG (branch) units
    - Create 2-3 KANTOR_KAS (sub-units) under each branch with proper parent_id
    - Set all units to ACTIVE status
    - Generate unique unit codes following pattern: "HQ-001", "JAK-001", "JAK-002", "SBY-001", etc.
    - _Bug_Condition: isBugCondition(input) where input.operation = 'SEED_DATA' AND unitsTableIsEmpty_
    - _Expected_Behavior: expectedBehavior(result) - units table is populated with branches and sub-units_
    - _Preservation: Existing seeding behavior continues to work_
    - _Requirements: 2.5_

  - [x] 3.6 Update DatabaseSeeder to call UnitSeeder
    - File: `database/seeders/DatabaseSeeder.php`
    - Add call to UnitSeeder before or after UserSeeder
    - Ensure units are seeded before users if there are foreign key dependencies
    - _Requirements: 2.5_

---

## Phase 4: Validation

- [x] 4. Verify bug condition exploration test now passes
  - **Property 1: Expected Behavior** - Parent ID Persistence and Bug Fixes
  - **IMPORTANT**: Re-run the SAME tests from task 1 - do NOT write new tests
  - The tests from task 1 encode the expected behavior
  - When these tests pass, it confirms the expected behavior is satisfied
  - Run bug condition exploration tests from step 1
  - **EXPECTED OUTCOME**: Tests PASS (confirms bugs are fixed)
  - Verify all counterexamples from step 1 are now resolved:
    - parent_id is saved correctly for KANTOR_KAS
    - KANTOR_CABANG creation succeeds
    - Non-Super Admin deletion is rejected with 403
    - Units are grouped using parent_id relationships
    - Units table is populated with seeded data
  - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6_

- [x] 5. Verify preservation tests still pass
  - **Property 2: Preservation** - Non-Buggy Operations Unchanged
  - **IMPORTANT**: Re-run the SAME tests from task 2 - do NOT write new tests
  - Run preservation property tests from step 2
  - **EXPECTED OUTCOME**: Tests PASS (confirms no regressions)
  - Confirm all tests still pass after fixes (no regressions):
    - Status toggling continues to work
    - Name/address updates continue to work
    - Customer assignments continue to work
    - Staff list display continues to work
    - Super Admin deletion continues to work
  - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5_

- [x] 6. Checkpoint - Ensure all tests pass
  - Verify all bug condition tests pass (fixes are working)
  - Verify all preservation tests pass (no regressions)
  - Verify AdminUnitsPage displays units correctly with hierarchical grouping
  - Verify UnitModal properly saves parent_id for sub-units
  - Verify seeded data is present in units table
  - Ensure all tests pass, ask the user if questions arise
  - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 3.1, 3.2, 3.3, 3.4, 3.5_
