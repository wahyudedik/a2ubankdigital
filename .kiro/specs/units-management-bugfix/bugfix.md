# Bugfix Requirements Document: Units Management System

## Introduction

The units management system at `/admin/units` has multiple critical bugs preventing proper creation, organization, and deletion of organizational units and branches. The system uses a hierarchical parent-child relationship where KANTOR_CABANG (branches) can have KANTOR_KAS (sub-units) as children. The bugs prevent this hierarchy from functioning correctly, block unit creation and deletion operations, and cause incorrect data display on the frontend.

## Bug Analysis

### Current Behavior (Defect)

1.1 WHEN a user attempts to create a KANTOR_KAS (sub-unit) through the UnitModal THEN the system does not save the parent_id field, resulting in orphaned units with no parent relationship

1.2 WHEN a user attempts to create a KANTOR_CABANG (branch) through the UnitModal THEN the system does not properly handle the unit creation request, causing the modal to fail silently or return errors

1.3 WHEN a user attempts to delete a unit through the delete button THEN the system allows deletion without verifying user permissions, allowing non-Super Admin users to delete units

1.4 WHEN the AdminUnitsPage loads THEN the system groups units using incorrect string matching on unit_code (checking if unit_code starts with branch code prefix) instead of using the parent_id relationship, resulting in incorrect or missing unit groupings

1.5 WHEN the UserSeeder runs THEN the system only creates user records and does not create any unit or branch data, leaving the units table empty for testing and development

1.6 WHEN a user selects a parent branch in the UnitModal for creating a sub-unit THEN the parent_id value is not properly passed to the backend API, preventing parent-child relationships from being established

### Expected Behavior (Correct)

2.1 WHEN a user creates a KANTOR_KAS (sub-unit) with a valid parent_id THEN the system SHALL save the parent_id field correctly, establishing the parent-child relationship

2.2 WHEN a user creates a KANTOR_CABANG (branch) THEN the system SHALL successfully create the unit and return a success response with the created unit data

2.3 WHEN a non-Super Admin user attempts to delete a unit THEN the system SHALL reject the deletion request with a 403 Forbidden response and display an error message

2.4 WHEN the AdminUnitsPage loads THEN the system SHALL group units using the parent_id relationship, displaying branches at the top level with their child units nested underneath

2.5 WHEN the UserSeeder runs THEN the system SHALL create sample unit and branch data including at least one KANTOR_CABANG and multiple KANTOR_KAS units under it

2.6 WHEN a user selects a parent branch in the UnitModal THEN the system SHALL properly pass the parent_id value to the backend API and establish the parent-child relationship

### Unchanged Behavior (Regression Prevention)

3.1 WHEN a user updates an existing unit's name or address THEN the system SHALL CONTINUE TO update only those fields without affecting the parent_id or unit_type

3.2 WHEN a Super Admin user deletes a unit with no children or assigned staff THEN the system SHALL CONTINUE TO delete the unit successfully

3.3 WHEN a user toggles a unit's status between ACTIVE and INACTIVE THEN the system SHALL CONTINUE TO update the status without affecting other unit properties

3.4 WHEN a user views the staff list page THEN the system SHALL CONTINUE TO display staff grouped by their assigned units using the same hierarchical structure

3.5 WHEN a user creates a customer and assigns them to a unit THEN the system SHALL CONTINUE TO properly associate the customer with the selected unit
