# Bugfix Requirements Document: Card Management Issues

## Introduction

The card management page at `/profile/cards` in the A2U Bank Digital application has multiple critical issues preventing users from viewing card information and managing their cards. The component is attempting to access incorrect database fields and API endpoints, causing card details to not display properly and card management actions (block/unblock, limit updates) to fail. This bugfix addresses all four issues: card number display, expiry date formatting, block/unblock functionality, and limit update functionality.

## Bug Analysis

### Current Behavior (Defect)

1.1 WHEN the DebitCard component renders a card THEN the card number field displays nothing instead of the masked card number because the component tries to access `card.card_number` which doesn't exist in the Card model

1.2 WHEN the DebitCard component renders a card THEN the cardholder name displays as "-" because the component tries to access `card.holder_name` which doesn't exist in the Card model

1.3 WHEN the DebitCard component renders a card THEN the expiry date displays in raw date format (YYYY-MM-DD) instead of the expected MM/YY format because the date is not formatted for display

1.4 WHEN the user clicks the block/unblock button on CardsPage THEN the action fails because the frontend calls the non-existent endpoint `user_update_card_status.php` instead of the correct API endpoint `/user/cards/{id}/status`

1.5 WHEN the user clicks the limit update button on CardsPage THEN the action fails because the frontend calls the non-existent endpoint `user_set_card_limit.php` instead of the correct API endpoint `/user/cards/{id}/limit`

### Expected Behavior (Correct)

2.1 WHEN the DebitCard component renders a card THEN the card number field SHALL display the masked card number from `card.card_number_masked` in the format "**** **** **** 1234"

2.2 WHEN the DebitCard component renders a card THEN the cardholder name SHALL display the user's full name from the related user relationship

2.3 WHEN the DebitCard component renders a card THEN the expiry date SHALL display in MM/YY format (e.g., "12/25") derived from the `card.expiry_date` field

2.4 WHEN the user clicks the block/unblock button on CardsPage THEN the action SHALL succeed by calling the correct API endpoint `/user/cards/{id}/status` with the new status

2.5 WHEN the user clicks the limit update button on CardsPage THEN the action SHALL succeed by calling the correct API endpoint `/user/cards/{id}/limit` with the new daily limit

### Unchanged Behavior (Regression Prevention)

3.1 WHEN a card is displayed with valid data THEN the system SHALL CONTINUE TO show the card type (DEBIT/CREDIT) correctly

3.2 WHEN a card is displayed with valid data THEN the system SHALL CONTINUE TO show the bank name "A2U Bank Digital" in the card header

3.3 WHEN the user requests a new card THEN the system SHALL CONTINUE TO work correctly with the existing `user_request_card.php` endpoint

3.4 WHEN the user views the cards list THEN the system SHALL CONTINUE TO display all user's cards in the correct order (newest first)

3.5 WHEN the user updates a card limit THEN the system SHALL CONTINUE TO validate that the limit is between 0 and 50,000,000 as defined in the backend

3.6 WHEN the user blocks or unblocks a card THEN the system SHALL CONTINUE TO validate that the status is either 'active' or 'blocked' as defined in the backend
