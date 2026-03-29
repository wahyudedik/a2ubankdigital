# Implementation Tasks: Card Management Bugfix

## Task List

### Phase 1: Backend Modifications

- [x] 1.1 Update CardController to eager-load user relationship in index() method
- [x] 1.2 Update CardController to eager-load user relationship in show() method

### Phase 2: Frontend Component Fixes

- [x] 2.1 Fix card number display in DebitCard.jsx
- [x] 2.2 Fix cardholder name display in DebitCard.jsx
- [x] 2.3 Add expiry date formatting function in DebitCard.jsx
- [x] 2.4 Update API endpoint for block/unblock action in CardsPage.jsx
- [x] 2.5 Update API endpoint for limit update action in CardsPage.jsx

### Phase 3: Testing & Verification

- [x] 3.1 Test card number displays correctly with masked format
- [x] 3.2 Test cardholder name displays correctly from user relationship
- [x] 3.3 Test expiry date formats correctly in MM/YY format
- [x] 3.4 Test block/unblock action calls correct endpoint and updates status
- [x] 3.5 Test limit update action calls correct endpoint and updates limit
- [x] 3.6 Verify no regression in card type display
- [x] 3.7 Verify no regression in bank name display
- [x] 3.8 Verify no regression in card request functionality
- [x] 3.9 Verify no regression in card list ordering

## Task Details

### 1.1 Update CardController to eager-load user relationship in index() method

**File:** `app/Http/Controllers/User/CardController.php`

**Changes:**
- Modify the `index()` method to include `with('user')` in the query
- This ensures user data is included when fetching the list of cards

**Before:**
```php
public function index(): JsonResponse
{
    $cards = Card::where('user_id', Auth::id())
        ->orderBy('created_at', 'desc')
        ->get();
    // ...
}
```

**After:**
```php
public function index(): JsonResponse
{
    $cards = Card::where('user_id', Auth::id())
        ->with('user')
        ->orderBy('created_at', 'desc')
        ->get();
    // ...
}
```

### 1.2 Update CardController to eager-load user relationship in show() method

**File:** `app/Http/Controllers/User/CardController.php`

**Changes:**
- Modify the `show()` method to include `with('user')` in the query
- This ensures user data is included when fetching a single card

**Before:**
```php
public function show($id): JsonResponse
{
    $card = Card::where('user_id', Auth::id())
        ->findOrFail($id);
    // ...
}
```

**After:**
```php
public function show($id): JsonResponse
{
    $card = Card::where('user_id', Auth::id())
        ->with('user')
        ->findOrFail($id);
    // ...
}
```

### 2.1 Fix card number display in DebitCard.jsx

**File:** `resources/js/components/customer/DebitCard.jsx`

**Changes:**
- Replace `card.card_number` with `card.card_number_masked`
- This accesses the correct field from the Card model

**Before:**
```javascript
<p className="font-mono text-lg tracking-wider mb-4">
    {card.card_number || '**** **** **** ****'}
</p>
```

**After:**
```javascript
<p className="font-mono text-lg tracking-wider mb-4">
    {card.card_number_masked || '**** **** **** ****'}
</p>
```

### 2.2 Fix cardholder name display in DebitCard.jsx

**File:** `resources/js/components/customer/DebitCard.jsx`

**Changes:**
- Replace `card.holder_name` with `card.user?.full_name`
- Use optional chaining to safely access the user relationship

**Before:**
```javascript
<div>
    <p className="text-xs opacity-60">Pemegang Kartu</p>
    <p className="text-sm font-medium">{card.holder_name || '-'}</p>
</div>
```

**After:**
```javascript
<div>
    <p className="text-xs opacity-60">Pemegang Kartu</p>
    <p className="text-sm font-medium">{card.user?.full_name || '-'}</p>
</div>
```

### 2.3 Add expiry date formatting function in DebitCard.jsx

**File:** `resources/js/components/customer/DebitCard.jsx`

**Changes:**
- Add a utility function to format the expiry date from YYYY-MM-DD to MM/YY
- Apply the formatting when displaying the expiry date

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

**Before:**
```javascript
<div className="text-right">
    <p className="text-xs opacity-60">Berlaku s/d</p>
    <p className="text-sm font-medium">{card.expiry_date || '--/--'}</p>
</div>
```

**After:**
```javascript
<div className="text-right">
    <p className="text-xs opacity-60">Berlaku s/d</p>
    <p className="text-sm font-medium">{formatExpiryDate(card.expiry_date)}</p>
</div>
```

### 2.4 Update API endpoint for block/unblock action in CardsPage.jsx

**File:** `resources/js/Pages/CardsPage.jsx`

**Changes:**
- Update the `handleUpdateStatus()` function to call the correct API endpoint
- Change from `user_update_card_status.php` to `/user/cards/{id}/status`
- Update the payload to use `status` instead of `new_status`

**Before:**
```javascript
const handleUpdateStatus = async (cardId, currentStatus) => {
    const newStatus = currentStatus === 'blocked' ? 'active' : 'blocked';
    const actionText = newStatus === 'active' ? 'membuka blokir' : 'memblokir';
    const confirmed = await modal.showConfirmation({ title: `Konfirmasi ${actionText} kartu`, message: `Apakah Anda yakin ingin ${actionText} kartu ini?`, confirmText: `Ya, ${actionText}` });
    if (confirmed) { const result = await callApi('user_update_card_status.php', 'POST', { card_id: cardId, new_status: newStatus }); if (result && result.status === 'success') { modal.showAlert({ title: 'Berhasil', message: result.message, type: 'success' }); router.reload(); } }
};
```

**After:**
```javascript
const handleUpdateStatus = async (cardId, currentStatus) => {
    const newStatus = currentStatus === 'blocked' ? 'active' : 'blocked';
    const actionText = newStatus === 'active' ? 'membuka blokir' : 'memblokir';
    const confirmed = await modal.showConfirmation({ title: `Konfirmasi ${actionText} kartu`, message: `Apakah Anda yakin ingin ${actionText} kartu ini?`, confirmText: `Ya, ${actionText}` });
    if (confirmed) { const result = await callApi(`/user/cards/${cardId}/status`, 'POST', { status: newStatus }); if (result && result.status === 'success') { modal.showAlert({ title: 'Berhasil', message: result.message, type: 'success' }); router.reload(); } }
};
```

### 2.5 Update API endpoint for limit update action in CardsPage.jsx

**File:** `resources/js/Pages/CardsPage.jsx`

**Changes:**
- Update the `handleSetLimit()` function to call the correct API endpoint
- Change from `user_set_card_limit.php` to `/user/cards/{id}/limit`
- Update the payload to remove `card_id` (it's in the URL)

**Before:**
```javascript
const handleSetLimit = async (e) => {
    e.preventDefault();
    const result = await callApi('user_set_card_limit.php', 'POST', { card_id: selectedCard.id, daily_limit: newLimit });
    if (result && result.status === 'success') { modal.showAlert({ title: 'Berhasil', message: result.message, type: 'success' }); setLimitModalOpen(false); router.reload(); }
};
```

**After:**
```javascript
const handleSetLimit = async (e) => {
    e.preventDefault();
    const result = await callApi(`/user/cards/${selectedCard.id}/limit`, 'POST', { daily_limit: newLimit });
    if (result && result.status === 'success') { modal.showAlert({ title: 'Berhasil', message: result.message, type: 'success' }); setLimitModalOpen(false); router.reload(); }
};
```

### 3.1 - 3.9 Testing & Verification Tasks

These tasks involve manual testing and verification of the fixes:

- **3.1:** Verify card number displays in masked format (e.g., "**** **** **** 1234")
- **3.2:** Verify cardholder name displays the user's full name correctly
- **3.3:** Verify expiry date displays in MM/YY format (e.g., "12/25")
- **3.4:** Verify block/unblock button works and updates card status
- **3.5:** Verify limit update button works and updates daily limit
- **3.6:** Verify card type (DEBIT/CREDIT) still displays correctly
- **3.7:** Verify bank name "A2U Bank Digital" still displays correctly
- **3.8:** Verify card request functionality still works
- **3.9:** Verify cards are displayed in correct order (newest first)
