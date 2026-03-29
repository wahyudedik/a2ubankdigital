# Bug Condition Exploration Results

## Overview
Bug condition exploration tests have been written and executed on the unfixed code. The tests encode the expected behavior and will validate fixes when they pass after implementation.

## Test Execution Summary

**Total Tests: 6**
- Passed: 4 ✓
- Failed: 2 ⨯

## Detailed Results

### Test 1a: Create KANTOR_KAS with parent_id, verify parent_id is saved (not NULL)
**Status:** ⨯ FAILED

**Counterexample Found:**
- Input: Create KANTOR_KAS with unit_name='Unit Layanan A', unit_type='KANTOR_KAS', parent_id=20
- Expected: Unit saved with parent_id=20
- Actual: Unit saved with parent_id=NULL

**Bug Confirmed:** Bug 1.1 - parent_id not saved when creating KANTOR_KAS

**Root Cause Analysis:**
The UnitController.store() method has logic to handle parent_id:
```php
'parent_id' => in_array($request->unit_type, ['KANTOR_PUSAT', 'KANTOR_CABANG']) ? null : $request->parent_id,
```

This logic appears correct (should save parent_id for KANTOR_KAS), but the parent_id is still being saved as NULL. Possible causes:
1. The parent_id is not being passed from the frontend/API request
2. The validation is failing silently
3. The request data is not being properly captured

---

### Test 1b: Create KANTOR_CABANG, verify unit_type is saved correctly
**Status:** ✓ PASSED

**Result:** KANTOR_CABANG creation is working correctly. The unit is created with:
- unit_type = 'KANTOR_CABANG'
- parent_id = NULL (as expected)

**Bug Status:** Bug 1.2 - NOT REPRODUCED (appears to be already fixed or not present)

---

### Test 1c: Attempt delete as non-Super Admin (role_id=5), verify 403 Forbidden response
**Status:** ✓ PASSED

**Result:** Non-Super Admin deletion is properly rejected with 403 Forbidden response. The permission check is working correctly.

**Bug Status:** Bug 1.3 - NOT REPRODUCED (permission check is working)

---

### Test 1d: Create branch and sub-units, verify grouping uses parent_id not string matching
**Status:** ✓ PASSED

**Result:** Units are correctly grouped using parent_id relationships:
- Branch1 (JAK-001) has 2 child units (JAK-002, JAK-003) via parent_id relationship
- Branch2 (BDG-001) has 1 child unit (BDG-002) via parent_id relationship
- Units are NOT incorrectly grouped by string matching

**Bug Status:** Bug 1.4 - NOT REPRODUCED (grouping logic is correct)

---

### Test 1e: Run seeder, verify units table is populated with branches and sub-units
**Status:** ✓ PASSED

**Result:** UnitSeeder successfully creates:
- 1 KANTOR_PUSAT (headquarters)
- 3 KANTOR_CABANG (branches)
- 6 KANTOR_KAS (sub-units) with proper parent_id relationships
- All units have ACTIVE status

**Bug Status:** Bug 1.5 - NOT REPRODUCED (seeder is working correctly)

---

### Test 1f: Verify parent_id is properly passed to backend API
**Status:** ⨯ FAILED

**Counterexample Found:**
- Input: API request with parent_id=20 in payload
- Expected: Unit saved with parent_id=20
- Actual: Unit saved with parent_id=NULL

**Bug Confirmed:** Bug 1.6 - parent_id not passed to backend API (or not being saved)

**Root Cause Analysis:**
Same as Test 1a - the parent_id is not being saved even though it's being passed in the API request.

---

## Summary of Bugs

### Confirmed Bugs (2)
1. **Bug 1.1 & 1.6: parent_id not saved when creating KANTOR_KAS**
   - When creating a KANTOR_KAS unit with a parent_id, the parent_id is saved as NULL instead of the provided value
   - This affects both direct creation and API requests
   - Impact: Sub-units cannot be properly associated with their parent branches

### Not Reproduced / Already Fixed (4)
2. **Bug 1.2: KANTOR_CABANG creation fails** - NOT REPRODUCED (working correctly)
3. **Bug 1.3: Non-Super Admin can delete units** - NOT REPRODUCED (permission check working)
4. **Bug 1.4: Grouping uses string matching** - NOT REPRODUCED (grouping uses parent_id correctly)
5. **Bug 1.5: UserSeeder doesn't create units** - NOT REPRODUCED (UnitSeeder creates units correctly)

---

## Next Steps

The bug condition exploration tests have successfully identified the primary issue:
- **parent_id is not being saved when creating KANTOR_KAS units**

The fix should focus on:
1. Ensuring parent_id is properly passed from the frontend to the backend
2. Verifying the UnitController.store() method is correctly handling parent_id
3. Checking if there are any validation issues preventing parent_id from being saved

The tests are now ready to validate the fixes when they are implemented. All tests should pass after the fixes are applied.
