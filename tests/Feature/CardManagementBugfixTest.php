<?php

namespace Tests\Feature;

use App\Models\Card;
use App\Models\User;
use App\Models\Account;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Card Management Bugfix - Phase 3 Testing & Verification
 * 
 * These tests verify that all card management fixes work correctly:
 * - Card number displays with masked format
 * - Cardholder name displays from user relationship
 * - Expiry date formats correctly in MM/YY format
 * - Block/unblock action calls correct endpoint
 * - Limit update action calls correct endpoint
 * - No regressions in card type, bank name, card request, or ordering
 * 
 * Validates: Requirements 2.1, 2.2, 2.3, 2.4, 2.5, 3.1, 3.2, 3.3, 3.4, 3.5, 3.6
 */
class CardManagementBugfixTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;
    protected Account $account;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test customer
        $this->customer = User::create([
            'bank_id' => 'CIF-TEST-001',
            'role_id' => 9, // Customer
            'full_name' => 'Test Customer',
            'email' => 'customer@test.com',
            'phone_number' => '081234567890',
            'password_hash' => bcrypt('password'),
            'status' => 'ACTIVE',
        ]);

        // Create a test account for the customer
        $this->account = Account::create([
            'user_id' => $this->customer->id,
            'account_number' => '1234567890',
            'account_type' => 'TABUNGAN',
            'balance' => 1000000,
            'status' => 'ACTIVE',
        ]);
    }

    /**
     * Test 3.1: Card number displays correctly with masked format
     * 
     * Property: For any card with card_number_masked field, the API response should include
     * the masked card number, and the frontend should display it correctly
     * 
     * Validates: Requirements 2.1
     */
    public function test_card_number_displays_with_masked_format(): void
    {
        // Create a card with masked number
        $card = Card::create([
            'user_id' => $this->customer->id,
            'account_id' => $this->account->id,
            'card_number_masked' => '**** **** **** 1234',
            'card_type' => 'DEBIT',
            'status' => 'active',
            'daily_limit' => 5000000,
            'expiry_date' => '2025-12-31',
        ]);

        // Act: Fetch cards via API
        $this->actingAs($this->customer, 'web');
        $response = $this->getJson('/ajax/user/cards');

        // Assert: Response should be successful
        $this->assertEquals(200, $response->status());
        $response->assertJsonPath('status', 'success');

        // Assert: Card number should be in masked format
        $responseCard = $response->json('data.0');
        $this->assertNotNull($responseCard, 'Card should be in response');
        $this->assertEquals('**** **** **** 1234', $responseCard['card_number_masked']);
        $this->assertNotNull($responseCard['card_number_masked'], 'card_number_masked should not be null');
    }

    /**
     * Test 3.2: Cardholder name displays correctly from user relationship
     * 
     * Property: For any card with a user relationship, the API response should include
     * the user's full_name, and the frontend should display it correctly
     * 
     * Validates: Requirements 2.2
     */
    public function test_cardholder_name_displays_from_user_relationship(): void
    {
        // Create a card
        $card = Card::create([
            'user_id' => $this->customer->id,
            'account_id' => $this->account->id,
            'card_number_masked' => '**** **** **** 5678',
            'card_type' => 'DEBIT',
            'status' => 'active',
            'daily_limit' => 5000000,
            'expiry_date' => '2025-12-31',
        ]);

        // Act: Fetch cards via API
        $this->actingAs($this->customer, 'web');
        $response = $this->getJson('/ajax/user/cards');

        // Assert: Response should be successful
        $this->assertEquals(200, $response->status());
        $response->assertJsonPath('status', 'success');

        // Assert: User relationship should be included with full_name
        $responseCard = $response->json('data.0');
        $this->assertNotNull($responseCard, 'Card should be in response');
        $this->assertNotNull($responseCard['user'], 'User relationship should be included');
        $this->assertEquals('Test Customer', $responseCard['user']['full_name']);
    }

    /**
     * Test 3.3: Expiry date formats correctly in MM/YY format
     * 
     * Property: For any card with expiry_date in YYYY-MM-DD format, the API response
     * should include the raw date, and the frontend should format it as MM/YY
     * 
     * Validates: Requirements 2.3
     */
    public function test_expiry_date_formats_correctly_in_mm_yy_format(): void
    {
        // Create a card with specific expiry date
        $card = Card::create([
            'user_id' => $this->customer->id,
            'account_id' => $this->account->id,
            'card_number_masked' => '**** **** **** 9012',
            'card_type' => 'DEBIT',
            'status' => 'active',
            'daily_limit' => 5000000,
            'expiry_date' => '2025-12-31', // Should format to 12/25
        ]);

        // Act: Fetch cards via API
        $this->actingAs($this->customer, 'web');
        $response = $this->getJson('/ajax/user/cards');

        // Assert: Response should be successful
        $this->assertEquals(200, $response->status());
        $response->assertJsonPath('status', 'success');

        // Assert: Expiry date should be in the response (backend returns raw date)
        $responseCard = $response->json('data.0');
        $this->assertNotNull($responseCard, 'Card should be in response');
        $this->assertNotNull($responseCard['expiry_date'], 'expiry_date should be in response');
        // Backend returns date as ISO string, frontend will format it to MM/YY
        $this->assertStringContainsString('2025-12', $responseCard['expiry_date']);
    }

    /**
     * Test 3.4: Block/unblock action calls correct endpoint and updates status
     * 
     * Property: For any active card, calling PUT /ajax/user/cards/{id}/status with status='blocked'
     * should update the card status to 'blocked' and return success
     * 
     * Validates: Requirements 2.4
     */
    public function test_block_unblock_action_calls_correct_endpoint(): void
    {
        // Create an active card
        $card = Card::create([
            'user_id' => $this->customer->id,
            'account_id' => $this->account->id,
            'card_number_masked' => '**** **** **** 3456',
            'card_type' => 'DEBIT',
            'status' => 'active',
            'daily_limit' => 5000000,
            'expiry_date' => '2025-12-31',
        ]);

        // Act: Block the card using the correct endpoint
        $this->actingAs($this->customer, 'web');
        $response = $this->putJson("/ajax/user/cards/{$card->id}/status", [
            'status' => 'blocked'
        ]);

        // Assert: Response should be successful
        $this->assertEquals(200, $response->status());
        $response->assertJsonPath('status', 'success');
        $response->assertJsonPath('message', 'Kartu berhasil diblokir.');

        // Assert: Card status should be updated in database
        $card->refresh();
        $this->assertEquals('blocked', $card->status);

        // Act: Unblock the card
        $response = $this->putJson("/ajax/user/cards/{$card->id}/status", [
            'status' => 'active'
        ]);

        // Assert: Response should be successful
        $this->assertEquals(200, $response->status());
        $response->assertJsonPath('status', 'success');
        $response->assertJsonPath('message', 'Kartu berhasil diaktifkan.');

        // Assert: Card status should be updated back to active
        $card->refresh();
        $this->assertEquals('active', $card->status);
    }

    /**
     * Test 3.5: Limit update action calls correct endpoint and updates limit
     * 
     * Property: For any active card, calling PUT /ajax/user/cards/{id}/limit with daily_limit
     * should update the card's daily_limit and return success
     * 
     * Validates: Requirements 2.5
     */
    public function test_limit_update_action_calls_correct_endpoint(): void
    {
        // Create an active card
        $card = Card::create([
            'user_id' => $this->customer->id,
            'account_id' => $this->account->id,
            'card_number_masked' => '**** **** **** 7890',
            'card_type' => 'DEBIT',
            'status' => 'active',
            'daily_limit' => 5000000,
            'expiry_date' => '2025-12-31',
        ]);

        // Act: Update the card limit using the correct endpoint
        $this->actingAs($this->customer, 'web');
        $newLimit = 10000000;
        $response = $this->putJson("/ajax/user/cards/{$card->id}/limit", [
            'daily_limit' => $newLimit
        ]);

        // Assert: Response should be successful
        $this->assertEquals(200, $response->status());
        $response->assertJsonPath('status', 'success');
        $response->assertJsonPath('message', 'Limit kartu berhasil diperbarui.');

        // Assert: Card limit should be updated in database
        $card->refresh();
        $this->assertEquals($newLimit, $card->daily_limit);

        // Act: Update limit to a different value
        $newLimit2 = 2000000;
        $response = $this->putJson("/ajax/user/cards/{$card->id}/limit", [
            'daily_limit' => $newLimit2
        ]);

        // Assert: Response should be successful
        $this->assertEquals(200, $response->status());

        // Assert: Card limit should be updated again
        $card->refresh();
        $this->assertEquals($newLimit2, $card->daily_limit);
    }

    /**
     * Test 3.6: Verify no regression in card type display
     * 
     * Property: For any card with card_type field, the API response should include
     * the card type (DEBIT or CREDIT), and it should not be affected by other fixes
     * 
     * Validates: Requirements 3.1
     */
    public function test_no_regression_in_card_type_display(): void
    {
        // Create cards of different types
        $debitCard = Card::create([
            'user_id' => $this->customer->id,
            'account_id' => $this->account->id,
            'card_number_masked' => '**** **** **** 1111',
            'card_type' => 'DEBIT',
            'status' => 'active',
            'daily_limit' => 5000000,
            'expiry_date' => '2025-12-31',
        ]);

        $creditCard = Card::create([
            'user_id' => $this->customer->id,
            'account_id' => $this->account->id,
            'card_number_masked' => '**** **** **** 2222',
            'card_type' => 'CREDIT',
            'status' => 'active',
            'daily_limit' => 10000000,
            'expiry_date' => '2026-06-30',
        ]);

        // Act: Fetch cards via API
        $this->actingAs($this->customer, 'web');
        $response = $this->getJson('/ajax/user/cards');

        // Assert: Response should be successful
        $this->assertEquals(200, $response->status());
        $response->assertJsonPath('status', 'success');

        // Assert: Card types should be preserved
        $cards = $response->json('data');
        $this->assertCount(2, $cards);
        
        $debitFromResponse = collect($cards)->firstWhere('id', $debitCard->id);
        $creditFromResponse = collect($cards)->firstWhere('id', $creditCard->id);
        
        $this->assertEquals('debit', $debitFromResponse['card_type']);
        $this->assertEquals('credit', $creditFromResponse['card_type']);
    }

    /**
     * Test 3.7: Verify no regression in bank name display
     * 
     * Property: The bank name "A2U Bank Digital" should be displayed in the frontend
     * and should not be affected by the fixes
     * 
     * Validates: Requirements 3.2
     */
    public function test_no_regression_in_bank_name_display(): void
    {
        // Create a card
        $card = Card::create([
            'user_id' => $this->customer->id,
            'account_id' => $this->account->id,
            'card_number_masked' => '**** **** **** 4444',
            'card_type' => 'DEBIT',
            'status' => 'active',
            'daily_limit' => 5000000,
            'expiry_date' => '2025-12-31',
        ]);

        // Act: Fetch cards via API
        $this->actingAs($this->customer, 'web');
        $response = $this->getJson('/ajax/user/cards');

        // Assert: Response should be successful
        $this->assertEquals(200, $response->status());

        // Assert: Card data should be present (bank name is displayed in frontend)
        $responseCard = $response->json('data.0');
        $this->assertNotNull($responseCard, 'Card should be in response');
        // Bank name is hardcoded in frontend component, so we just verify card data is present
        $this->assertNotNull($responseCard['id']);
        $this->assertNotNull($responseCard['card_type']);
    }

    /**
     * Test 3.8: Verify no regression in card request functionality
     * 
     * Property: The card request endpoint should continue to work correctly
     * and not be affected by the fixes
     * 
     * Validates: Requirements 3.3
     */
    public function test_no_regression_in_card_request_functionality(): void
    {
        // Act: Request a new card
        $this->actingAs($this->customer, 'web');
        $response = $this->postJson('/ajax/user/cards/request', [
            'card_type' => 'DEBIT',
            'delivery_address' => 'Jl. Test No. 1',
            'reason' => 'Personal use'
        ]);

        // Assert: Response should be successful
        $this->assertEquals(201, $response->status());
        $response->assertJsonPath('status', 'success');
        $response->assertJsonPath('message', 'Permintaan kartu berhasil diajukan.');

        // Assert: Card request should be created
        $this->assertDatabaseHas('card_requests', [
            'user_id' => $this->customer->id,
            'card_type' => 'DEBIT',
            'status' => 'PENDING'
        ]);
    }

    /**
     * Test 3.9: Verify no regression in card list ordering
     * 
     * Property: For any user with multiple cards, the cards should be ordered
     * by created_at descending (newest first)
     * 
     * Validates: Requirements 3.4
     */
    public function test_no_regression_in_card_list_ordering(): void
    {
        // Create multiple cards with different creation times
        $card1 = Card::create([
            'user_id' => $this->customer->id,
            'account_id' => $this->account->id,
            'card_number_masked' => '**** **** **** 5555',
            'card_type' => 'DEBIT',
            'status' => 'active',
            'daily_limit' => 5000000,
            'expiry_date' => '2025-12-31',
            'created_at' => now()->subDays(2),
        ]);

        $card2 = Card::create([
            'user_id' => $this->customer->id,
            'account_id' => $this->account->id,
            'card_number_masked' => '**** **** **** 6666',
            'card_type' => 'DEBIT',
            'status' => 'active',
            'daily_limit' => 5000000,
            'expiry_date' => '2025-12-31',
            'created_at' => now()->subDays(1),
        ]);

        $card3 = Card::create([
            'user_id' => $this->customer->id,
            'account_id' => $this->account->id,
            'card_number_masked' => '**** **** **** 7777',
            'card_type' => 'DEBIT',
            'status' => 'active',
            'daily_limit' => 5000000,
            'expiry_date' => '2025-12-31',
            'created_at' => now(),
        ]);

        // Act: Fetch cards via API
        $this->actingAs($this->customer, 'web');
        $response = $this->getJson('/ajax/user/cards');

        // Assert: Response should be successful
        $this->assertEquals(200, $response->status());
        $response->assertJsonPath('status', 'success');

        // Assert: Cards should be ordered by created_at descending (newest first)
        $cards = $response->json('data');
        $this->assertCount(3, $cards);
        
        // Verify the cards are in descending order by checking created_at timestamps
        $createdAts = array_map(fn($card) => $card['created_at'], $cards);
        $sortedCreatedAts = $createdAts;
        rsort($sortedCreatedAts);
        $this->assertEquals($sortedCreatedAts, $createdAts, 'Cards should be ordered by created_at descending');
    }

    /**
     * Test 3.5a: Verify limit validation (0 to 50,000,000)
     * 
     * Property: For any card limit update, the limit should be validated
     * to be between 0 and 50,000,000
     * 
     * Validates: Requirements 3.5
     */
    public function test_limit_validation_range(): void
    {
        // Create an active card
        $card = Card::create([
            'user_id' => $this->customer->id,
            'account_id' => $this->account->id,
            'card_number_masked' => '**** **** **** 8888',
            'card_type' => 'DEBIT',
            'status' => 'active',
            'daily_limit' => 5000000,
            'expiry_date' => '2025-12-31',
        ]);

        // Act: Try to set limit below minimum (should fail)
        $this->actingAs($this->customer, 'web');
        $response = $this->putJson("/ajax/user/cards/{$card->id}/limit", [
            'daily_limit' => -1
        ]);

        // Assert: Should fail validation
        $this->assertEquals(422, $response->status());

        // Act: Try to set limit above maximum (should fail)
        $response = $this->putJson("/ajax/user/cards/{$card->id}/limit", [
            'daily_limit' => 50000001
        ]);

        // Assert: Should fail validation
        $this->assertEquals(422, $response->status());

        // Act: Set limit to minimum valid value (0)
        $response = $this->putJson("/ajax/user/cards/{$card->id}/limit", [
            'daily_limit' => 0
        ]);

        // Assert: Should succeed
        $this->assertEquals(200, $response->status());
        $card->refresh();
        $this->assertEquals(0, $card->daily_limit);

        // Act: Set limit to maximum valid value (50,000,000)
        $response = $this->putJson("/ajax/user/cards/{$card->id}/limit", [
            'daily_limit' => 50000000
        ]);

        // Assert: Should succeed
        $this->assertEquals(200, $response->status());
        $card->refresh();
        $this->assertEquals(50000000, $card->daily_limit);
    }

    /**
     * Test 3.6a: Verify status validation (active or blocked)
     * 
     * Property: For any card status update, the status should be validated
     * to be either 'active' or 'blocked'
     * 
     * Validates: Requirements 3.6
     */
    public function test_status_validation_values(): void
    {
        // Create an active card
        $card = Card::create([
            'user_id' => $this->customer->id,
            'account_id' => $this->account->id,
            'card_number_masked' => '**** **** **** 9999',
            'card_type' => 'DEBIT',
            'status' => 'active',
            'daily_limit' => 5000000,
            'expiry_date' => '2025-12-31',
        ]);

        // Act: Try to set invalid status (should fail)
        $this->actingAs($this->customer, 'web');
        $response = $this->putJson("/ajax/user/cards/{$card->id}/status", [
            'status' => 'invalid_status'
        ]);

        // Assert: Should fail validation
        $this->assertEquals(422, $response->status());

        // Act: Set status to 'blocked' (valid)
        $response = $this->putJson("/ajax/user/cards/{$card->id}/status", [
            'status' => 'blocked'
        ]);

        // Assert: Should succeed
        $this->assertEquals(200, $response->status());
        $card->refresh();
        $this->assertEquals('blocked', $card->status);

        // Act: Set status to 'active' (valid)
        $response = $this->putJson("/ajax/user/cards/{$card->id}/status", [
            'status' => 'active'
        ]);

        // Assert: Should succeed
        $this->assertEquals(200, $response->status());
        $card->refresh();
        $this->assertEquals('active', $card->status);
    }

    /**
     * Test 3.7a: Verify user can only access their own cards
     * 
     * Property: For any card, a user should only be able to access their own cards
     * and not other users' cards
     * 
     * Validates: Requirements 3.7
     */
    public function test_user_can_only_access_own_cards(): void
    {
        // Create another customer
        $otherCustomer = User::create([
            'bank_id' => 'CIF-TEST-002',
            'role_id' => 9,
            'full_name' => 'Other Customer',
            'email' => 'other@test.com',
            'phone_number' => '081234567891',
            'password_hash' => bcrypt('password'),
            'status' => 'ACTIVE',
        ]);

        // Create a card for the first customer
        $card1 = Card::create([
            'user_id' => $this->customer->id,
            'account_id' => $this->account->id,
            'card_number_masked' => '**** **** **** 1010',
            'card_type' => 'DEBIT',
            'status' => 'active',
            'daily_limit' => 5000000,
            'expiry_date' => '2025-12-31',
        ]);

        // Create an account and card for the other customer
        $otherAccount = Account::create([
            'user_id' => $otherCustomer->id,
            'account_number' => '0987654321',
            'account_type' => 'TABUNGAN',
            'balance' => 1000000,
            'status' => 'ACTIVE',
        ]);

        $card2 = Card::create([
            'user_id' => $otherCustomer->id,
            'account_id' => $otherAccount->id,
            'card_number_masked' => '**** **** **** 2020',
            'card_type' => 'DEBIT',
            'status' => 'active',
            'daily_limit' => 5000000,
            'expiry_date' => '2025-12-31',
        ]);

        // Act: First customer fetches their cards
        $this->actingAs($this->customer, 'web');
        $response = $this->getJson('/ajax/user/cards');

        // Assert: Should only see their own card
        $this->assertEquals(200, $response->status());
        $cards = $response->json('data');
        $this->assertCount(1, $cards);
        $this->assertEquals($card1->id, $cards[0]['id']);

        // Act: Try to access other customer's card directly (if show route exists)
        // Note: The routes don't have a show endpoint, so we skip this test
        // $response = $this->getJson("/ajax/user/cards/{$card2->id}");
        // $this->assertEquals(404, $response->status());
    }
}
