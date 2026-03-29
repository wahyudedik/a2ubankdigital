# Units Management System Bugfix Design

## Overview

The units management system has critical bugs preventing proper hierarchical organization of organizational units. The system uses a parent-child relationship where KANTOR_CABANG (branches) can have KANTOR_KAS (sub-units) as children. The bugs prevent parent_id from being saved in UnitModal, cause incorrect grouping logic in AdminPageController using string matching instead of parent_id relationships, allow unauthorized deletion, and lack comprehensive seeding data. This design formalizes the bug conditions and outlines a targeted fix that preserves existing functionality while enabling proper parent-child relationships.

## Glossary

- **Bug_Condition (C)**: The condition that triggers the bug - when creating/updating units with parent relationships, or when grouping units by parent, or when deleting without permission checks
- **Property (P)**: The desired behavior when parent_id operations occur - parent_id should be saved, grouping should use parent_id relationships, deletion should require Super Admin role
- **Preservation**: Existing functionality for unit status toggling, name/address updates, and staff/customer assignments that must remain unchanged
- **parent_id**: The foreign key field in the units table that establishes parent-child relationships between units
- **UnitModal**: The React component in `resources/js/components/modals/UnitModal.jsx` that handles unit creation and editing
- **UnitController**: The controller in `app/Http/Controllers/Admin/UnitController.php` that handles API requests for unit operations
- **AdminPageController.units()**: The method in `app/Http/Controllers/Inertia/AdminPageController.php` that retrieves and groups units for display
- **Unit Model**: The Eloquent model in `app/Models/Unit.php` with parent/children relationships

## Bug Details

### Bug Condition

The bug manifests when:
1. A user creates a KANTOR_KAS (sub-unit) and selects a parent branch in UnitModal - the parent_id is not passed to the backend
2. A user creates a KANTOR_CABANG (branch) through UnitModal - the request fails or returns errors
3. A non-Super Admin user attempts to delete a unit - the system allows deletion without permission verification
4. The AdminUnitsPage loads - the system groups units using string matching on unit_code instead of parent_id relationships
5. The UserSeeder runs - no unit or branch data is created, leaving the units table empty

**Formal Specification:**
```
FUNCTION isBugCondition(input)
  INPUT: input of type {operation, userRole, unitData}
  OUTPUT: boolean
  
  RETURN (input.operation = 'CREATE_UNIT' AND input.unitData.parent_id IS NULL AND input.unitData.unit_type = 'KANTOR_KAS')
         OR (input.operation = 'CREATE_UNIT' AND input.unitData.unit_type = 'KANTOR_CABANG' AND requestFails)
         OR (input.operation = 'DELETE_UNIT' AND input.userRole != 1)
         OR (input.operation = 'GROUP_UNITS' AND groupingUsesStringMatching)
         OR (input.operation = 'SEED_DATA' AND unitsTableIsEmpty)
END FUNCTION
```

### Examples

**Example 1: Parent ID Not Saved**
- User creates KANTOR_KAS "Unit Layanan A" with parent_id = 5 (branch)
- Expected: Unit created with parent_id = 5, appears under branch in UI
- Actual: Unit created with parent_id = NULL, appears as orphaned unit

**Example 2: Incorrect Grouping**
- Branch "Cabang Jakarta" has unit_code "JAK-001"
- Sub-unit "Unit Layanan" has unit_code "JAK-002" but parent_id = NULL
- Expected: Sub-unit grouped under branch using parent_id relationship
- Actual: Sub-unit grouped using string matching (checking if unit_code starts with "JAK-")

**Example 3: Unauthorized Deletion**
- User with role_id = 5 (Teller) attempts to delete a unit
- Expected: System rejects with 403 Forbidden
- Actual: System allows deletion (permission check missing)

**Example 4: Empty Seeding**
- Developer runs `php artisan db:seed --class=UserSeeder`
- Expected: Units table populated with sample branches and sub-units
- Actual: Units table remains empty, only users created

## Expected Behavior

### Preservation Requirements

**Unchanged Behaviors:**
- Updating a unit's name or address must not affect parent_id or unit_type
- Toggling a unit's status between ACTIVE and INACTIVE must not affect relationships
- Assigning customers/staff to units must continue to work with the hierarchical structure
- Viewing staff list must continue to display staff grouped by their assigned units
- Creating customers and assigning them to units must properly associate them

**Scope:**
All inputs that do NOT involve parent_id operations, unauthorized deletion attempts, or seeding should be completely unaffected by this fix. This includes:
- Mouse clicks on status toggle buttons
- Unit name/address updates
- Customer/staff assignments
- Staff list display
- Existing unit queries that don't rely on grouping logic

## Hypothesized Root Cause

Based on the bug description, the most likely issues are:

1. **UnitModal Not Passing parent_id**: The form data in UnitModal includes parent_id but the API call may not be sending it, or the backend validation may be rejecting it
   - The formData state includes parent_id but it may not be included in the API call payload
   - The backend validation may have issues with parent_id handling

2. **AdminPageController Using String Matching**: The units() method uses `str_starts_with($u->unit_code, explode('-', $branch->unit_code)[0] . '-')` instead of checking parent_id
   - This is fragile and breaks if unit codes don't follow the expected pattern
   - The parent_id relationship is available but not being used

3. **Missing Permission Check in Delete**: The destroy() method in UnitController checks for role_id === 1 but this may not be enforced properly
   - The permission check exists but may not be working correctly
   - The frontend may not be preventing the delete button from being shown

4. **UserSeeder Not Creating Units**: The UserSeeder only creates user records and doesn't create any unit data
   - A separate UnitSeeder needs to be created
   - The UserSeeder should call the UnitSeeder or units should be created in a separate seeder

5. **API Endpoint Issues**: The store() and update() methods may not be properly handling parent_id
   - The validation may be too strict or not allowing parent_id for certain unit types
   - The response may not include the parent relationship

## Correctness Properties

Property 1: Bug Condition - Parent ID Persistence

_For any_ unit creation request where a KANTOR_KAS unit type is selected with a valid parent_id, the fixed UnitModal and UnitController SHALL properly pass and save the parent_id field, establishing the parent-child relationship so the unit appears grouped under its parent branch.

**Validates: Requirements 2.1, 2.6**

Property 2: Bug Condition - Proper Grouping

_For any_ AdminUnitsPage load, the fixed AdminPageController.units() method SHALL group units using the parent_id relationship (checking if unit.parent_id === branch.id) instead of string matching on unit_code, ensuring accurate hierarchical display.

**Validates: Requirements 2.4**

Property 3: Bug Condition - Permission Enforcement

_For any_ delete request from a user with role_id != 1 (not Super Admin), the fixed UnitController.destroy() method SHALL reject the request with a 403 Forbidden response and prevent deletion.

**Validates: Requirements 2.3**

Property 4: Bug Condition - Seeding Data

_For any_ database seeding operation, the fixed UnitSeeder SHALL create sample unit and branch data including at least one KANTOR_CABANG and multiple KANTOR_KAS units with proper parent_id relationships.

**Validates: Requirements 2.5**

Property 5: Preservation - Non-Parent Operations

_For any_ input that does NOT involve parent_id operations, unauthorized deletion, or seeding (such as status updates, name/address updates, customer assignments), the fixed code SHALL produce exactly the same behavior as the original code, preserving all existing functionality.

**Validates: Requirements 3.1, 3.2, 3.3, 3.4, 3.5**

## Fix Implementation

### Changes Required

Assuming our root cause analysis is correct:

**File 1**: `resources/js/components/modals/UnitModal.jsx`

**Issue**: The parent_id is in formData but may not be properly included in the API call or may be sent as empty string

**Specific Changes**:
1. **Ensure parent_id is included in API payload**: Verify that parent_id is included in the formData sent to the backend, not just in the form state
2. **Handle empty parent_id for KANTOR_CABANG**: When unit_type is KANTOR_CABANG, ensure parent_id is not sent or is sent as null
3. **Validate parent_id before submission**: Add client-side validation to ensure parent_id is selected when unit_type is KANTOR_KAS

**File 2**: `app/Http/Controllers/Admin/UnitController.php`

**Issue**: The store() and update() methods may not be properly handling parent_id in all cases

**Specific Changes**:
1. **Ensure parent_id is included in response**: The response should include the parent relationship so the frontend can display it correctly
2. **Validate parent_id exists**: The validation already checks `exists:units,id` but ensure it's working correctly
3. **Handle parent_id for different unit types**: Ensure that KANTOR_CABANG and KANTOR_PUSAT have parent_id = null, while KANTOR_KAS and KANTOR_LAYANAN can have parent_id

**File 3**: `app/Http/Controllers/Inertia/AdminPageController.php`

**Issue**: The units() method uses string matching instead of parent_id relationships

**Specific Changes**:
1. **Replace string matching with parent_id check**: Change from `str_starts_with($u->unit_code, ...)` to `$u->parent_id === $branch->id`
2. **Use eager loading**: Load parent relationships to avoid N+1 queries
3. **Simplify grouping logic**: Use the parent_id relationship directly instead of parsing unit_code

**File 4**: `database/seeders/UnitSeeder.php` (NEW FILE)

**Issue**: No unit seeding data exists

**Specific Changes**:
1. **Create KANTOR_PUSAT**: Create one headquarters unit
2. **Create KANTOR_CABANG**: Create 2-3 branch units
3. **Create KANTOR_KAS**: Create 2-3 sub-units under each branch with proper parent_id
4. **Set proper status**: All units should be ACTIVE
5. **Generate unique unit codes**: Use a pattern like "HQ-001", "JAK-001", "JAK-002", "SBY-001", etc.

**File 5**: `database/seeders/DatabaseSeeder.php`

**Issue**: The UnitSeeder needs to be called during seeding

**Specific Changes**:
1. **Call UnitSeeder**: Add `$this->call(UnitSeeder::class);` to ensure units are seeded before users

## Testing Strategy

### Validation Approach

The testing strategy follows a two-phase approach: first, surface counterexamples that demonstrate the bugs on unfixed code, then verify the fixes work correctly and preserve existing behavior.

### Exploratory Bug Condition Checking

**Goal**: Surface counterexamples that demonstrate the bugs BEFORE implementing the fix. Confirm or refute the root cause analysis.

**Test Plan**: Write tests that simulate unit creation with parent_id, unit grouping, deletion attempts, and seeding operations. Run these tests on the UNFIXED code to observe failures and understand the root causes.

**Test Cases**:
1. **Parent ID Persistence Test**: Create a KANTOR_KAS unit with parent_id and verify it's saved (will fail on unfixed code)
2. **Grouping Logic Test**: Create branch and sub-units, verify they're grouped by parent_id not string matching (will fail on unfixed code)
3. **Permission Check Test**: Attempt delete as non-Super Admin, verify 403 response (will fail on unfixed code)
4. **Seeding Test**: Run seeder and verify units table is populated (will fail on unfixed code)
5. **Branch Creation Test**: Create KANTOR_CABANG through API and verify success (may fail on unfixed code)

**Expected Counterexamples**:
- parent_id is NULL after creating KANTOR_KAS with parent_id
- Units are grouped incorrectly or not at all
- Non-Super Admin users can delete units
- Units table is empty after seeding
- KANTOR_CABANG creation fails or returns errors

### Fix Checking

**Goal**: Verify that for all inputs where the bug condition holds, the fixed code produces the expected behavior.

**Pseudocode:**
```
FOR ALL input WHERE isBugCondition(input) DO
  result := fixedFunction(input)
  ASSERT expectedBehavior(result)
END FOR
```

**Test Cases**:
1. **Parent ID Persistence**: Create KANTOR_KAS with parent_id, verify parent_id is saved and returned
2. **Proper Grouping**: Create branch and sub-units, verify they're grouped using parent_id
3. **Permission Enforcement**: Attempt delete as non-Super Admin, verify 403 response
4. **Seeding Data**: Run seeder, verify units table has branches and sub-units with parent_id relationships
5. **Branch Creation**: Create KANTOR_CABANG, verify success and parent_id is null

### Preservation Checking

**Goal**: Verify that for all inputs where the bug condition does NOT hold, the fixed code produces the same result as the original code.

**Pseudocode:**
```
FOR ALL input WHERE NOT isBugCondition(input) DO
  ASSERT originalFunction(input) = fixedFunction(input)
END FOR
```

**Testing Approach**: Property-based testing is recommended for preservation checking because:
- It generates many test cases automatically across the input domain
- It catches edge cases that manual unit tests might miss
- It provides strong guarantees that behavior is unchanged for non-buggy inputs

**Test Plan**: Observe behavior on UNFIXED code first for status updates, name/address updates, and customer assignments, then write property-based tests capturing that behavior.

**Test Cases**:
1. **Status Toggle Preservation**: Verify toggling status between ACTIVE/INACTIVE continues to work
2. **Name/Address Update Preservation**: Verify updating name or address doesn't affect parent_id
3. **Customer Assignment Preservation**: Verify assigning customers to units continues to work
4. **Staff List Display Preservation**: Verify staff list displays correctly with hierarchical grouping
5. **Unit Query Preservation**: Verify existing unit queries continue to work correctly

### Unit Tests

- Test parent_id is saved when creating KANTOR_KAS with parent_id
- Test parent_id is null when creating KANTOR_CABANG
- Test permission check prevents non-Super Admin deletion
- Test grouping logic uses parent_id relationship
- Test seeder creates units with proper parent_id relationships
- Test status toggle doesn't affect parent_id
- Test name/address update doesn't affect parent_id

### Property-Based Tests

- Generate random unit creation requests and verify parent_id is handled correctly
- Generate random unit hierarchies and verify grouping is correct
- Generate random deletion attempts with different user roles and verify permission checks
- Generate random status updates and verify parent_id is preserved
- Generate random customer assignments and verify they work with hierarchical units

### Integration Tests

- Test full unit creation flow through UnitModal and API
- Test unit grouping on AdminUnitsPage with multiple branches and sub-units
- Test deletion workflow with permission checks
- Test seeding creates proper hierarchical structure
- Test staff list displays correctly with hierarchical units
- Test customer assignment works with hierarchical units
