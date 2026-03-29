# Design Document: Card Management Bugfix

## Overview

This document outlines the technical design for fixing the card management issues in the A2U Bank Digital application. The fixes involve correcting field references in the frontend component, implementing proper date formatting, and updating API endpoint calls to match the actual backend routes.

## Technical Context

### Current Architecture

**Frontend Components:**
- `resources/js/components/customer/DebitCard.jsx` - Displays individual card information
- `resources/js/Pages/CardsPage.jsx` - Manages card list and card actions

**Backend:**
- `app/Http/Controllers/User/CardController.php` - Provides API endpoints for card operations
- `app/Models/Card.php` - Card model with database field definitions
- Database table: `cards` with fields: `card_number_masked`, `expiry_date`, `status`, `daily_limit`

### Database Schema

The `cards` table contains:
- `card_number_masked` (string) - Masked card number (e.g., "**** **** **** 1234")
- `expiry_date` (date) - Card expiry date stored as YYYY-MM-DD
- `status` (enum) - Card status: 'requested', 'active', 'blocked', 'expired'
- `daily_limit` (decimal) - Daily transaction limit
- `user_id` (foreign key) - Reference to user

### Existing API Endpoints

The CardController provides these endpoints:
- `GET /user/cards` - List user's cards (via `index()`)
- `GET /user/cards/{id}` - Get card details (via `show()`)
- `POST /user/cards/{id}/status` - Update card status (via `updateStatus()`)
- `POST /user/cards/{id}/limit` - Set card limit (via `setLimit()`)
- `POST /user/cards/request` - Request new card (via `requestCard()`)

## Implementation Design

### Issue 1: Card Number Display

**Problem:** DebitCard component accesses `card.card_number` which doesn't exist; should use `card.card_number_masked`

**Solution:**
- Update DebitCard.jsx to use `card.card_number_masked` instead of `card.card_number`
- The field already contains the masked format from the database

**Code Change:**
```javascript
// Before
{card.card_number || '**** **** **** ****'}

// After
{card.card_number_masked || '**** **** **** ****'}
```

### Issue 2: Cardholder Name Display

**Problem:** DebitCard component accesses `card.holder_name` which doesn't exist in the Card model

**Solution:**
- The Card model has a relationship to User model via `user_id`
- Access the cardholder name through the user relationship: `card.user.full_name`
- Update the backend to include the user relationship in the card response
- Update DebitCard.jsx to display the user's full name

**Backend Changes:**
- Modify CardController's `index()` and `show()` methods to eager-load the user relationship
- Use `with('user')` to include user data in the response

**Frontend Changes:**
- Update DebitCard.jsx to use `card.user?.full_name` with optional chaining

### Issue 3: Expiry Date Formatting

**Problem:** Expiry date displays in raw YYYY-MM-DD format instead of MM/YY

**Solution:**
- Create a utility function to format the date from YYYY-MM-DD to MM/YY format
- Apply the formatting in DebitCard.jsx when displaying the expiry date

**Implementation:**
```javascript
const formatExpiryDate = (dateString) => {
  if (!dateString) return '--/--';
  const date = new Date(dateString);
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const year = String(date.getFullYear()).slice(-2);
  return `${month}/${year}`;
};
```

### Issue 4: Block/Unblock Action Fails

**Problem:** CardsPage calls non-existent endpoint `user_update_card_status.php` instead of the correct API endpoint

**Solution:**
- Update the `handleUpdateStatus` function in CardsPage.jsx to call the correct endpoint
- The endpoint should be `/user/cards/{id}/status` with method POST
- Send the correct payload format expected by the backend

**Current Call:**
```javascript
callApi('user_update_card_status.php', 'POST', { card_id: cardId, new_status: newStatus })
```

**Corrected Call:**
```javascript
callApi(`/user/cards/${cardId}/status`, 'POST', { status: newStatus })
```

### Issue 5: Limit Update Fails

**Problem:** CardsPage calls non-existent endpoint `user_set_card_limit.php` instead of the correct API endpoint

**Solution:**
- Update the `handleSetLimit` function in CardsPage.jsx to call the correct endpoint
- The endpoint should be `/user/cards/{id}/limit` with method POST
- Send the correct payload format expected by the backend

**Current Call:**
```javascript
callApi('user_set_card_limit.php', 'POST', { card_id: selectedCard.id, daily_limit: newLimit })
```

**Corrected Call:**
```javascript
callApi(`/user/cards/${selectedCard.id}/limit`, 'POST', { daily_limit: newLimit })
```

## Backend Modifications

### CardController Changes

**Modify `index()` method:**
- Add `with('user')` to eager-load user relationship
- Ensures user data is included in the response

**Modify `show()` method:**
- Add `with('user')` to eager-load user relationship
- Ensures user data is included in the response

## Frontend Modifications

### DebitCard.jsx Changes

1. Replace `card.card_number` with `card.card_number_masked`
2. Replace `card.holder_name` with `card.user?.full_name`
3. Add date formatting function and apply it to `card.expiry_date`

### CardsPage.jsx Changes

1. Update `handleUpdateStatus()` to call `/user/cards/{id}/status` endpoint
2. Update `handleSetLimit()` to call `/user/cards/{id}/limit` endpoint
3. Update payload field names to match backend expectations

## Testing Strategy

### Fix Checking (Buggy Inputs)

For each bug condition, verify the fix works:

1. **Card Number Display:**
   - Render a card with `card_number_masked` = "**** **** **** 1234"
   - Verify the card displays the masked number correctly

2. **Cardholder Name:**
   - Render a card with user relationship populated
   - Verify the cardholder name displays the user's full name

3. **Expiry Date Formatting:**
   - Render a card with `expiry_date` = "2025-12-31"
   - Verify the expiry date displays as "12/25"

4. **Block/Unblock Action:**
   - Click block button on a card
   - Verify the API call goes to `/user/cards/{id}/status`
   - Verify the card status updates correctly

5. **Limit Update Action:**
   - Click limit button and update the limit
   - Verify the API call goes to `/user/cards/{id}/limit`
   - Verify the card limit updates correctly

### Preservation Checking (Non-Buggy Inputs)

Verify existing behavior is unchanged:

1. Card type display continues to work
2. Bank name display continues to work
3. Card request functionality continues to work
4. Card list ordering continues to work
5. Validation rules continue to work

## Risk Assessment

**Low Risk Changes:**
- Field name corrections (card_number_masked, user relationship)
- Date formatting utility function
- API endpoint URL corrections

**Mitigation:**
- All changes are isolated to specific components
- Backend endpoints already exist and are properly implemented
- No database schema changes required
- No breaking changes to existing functionality
