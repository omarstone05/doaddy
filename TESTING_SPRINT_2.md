# Sprint 2 Testing Guide

## ✅ Setup Complete

All migrations have been run successfully and seeders have been executed.

## Quick Start Testing

### 1. Start the Application
```bash
php artisan serve
npm run dev
```

### 2. Create Test User
1. Navigate to `/register`
2. Register a new user (this will create an organization automatically)
3. Login with your credentials

### 3. Test Money Accounts
1. Go to `/money/accounts/create`
2. Create a cash account with opening balance K1000
3. Create a bank account
4. Verify accounts appear in the list

### 4. Test Money Movements
1. Go to `/money/movements/create?type=income`
2. Record K500 income to cash account
3. Go to `/money/movements/create?type=expense`
4. Record K100 expense from cash account
5. Verify cash account balance updated to K1400
6. View movements list and verify filters work

### 5. Test Budgets
1. Go to `/money/budgets/create`
2. Create a monthly budget for "Office Supplies" - K500
3. Record an expense in "Office Supplies" category - K150
4. View budget and verify spent percentage shows 30%

### 6. Test Dashboard
1. Go to `/dashboard`
2. Verify stats cards show correct totals
3. Click "Add Card" and add a dashboard card
4. Drag cards to reorder (if you have multiple cards)
5. Remove a card

### 7. Test POS System
1. First, create a product:
   - Go to `/goods-and-services/create` (you'll need to create this route/model)
   - OR manually insert via database:
   ```sql
   INSERT INTO goods_and_services (id, organization_id, name, type, selling_price, is_active, created_at, updated_at)
   VALUES (gen_random_uuid(), 'your-org-id', 'Test Product', 'product', 100.00, true, now(), now());
   ```

2. Go to `/pos`
3. Search for products
4. Add products to cart
5. Complete a sale
6. Verify receipt displays correctly
7. Test "Quick Expense" button

### 8. Test Quick Expense from POS
1. On POS screen, click "Quick Expense"
2. Enter amount: K50
3. Enter description: "Test expense"
4. Submit
5. Verify expense appears in money movements
6. Verify cash account balance decreases

## Expected Results

- ✅ All CRUD operations work without errors
- ✅ Account balances update automatically
- ✅ Dashboard shows correct stats
- ✅ POS creates sales and receipts
- ✅ Quick expense works from POS
- ✅ All forms validate correctly
- ✅ All pages load without errors

## Known Limitations

- Products CRUD interface not yet created (Sprint 3)
- Customers CRUD interface not yet created (Sprint 3)
- Register sessions not yet implemented (Sprint 3)

## Notes

- The system will auto-create a TeamMember record when you first use POS
- Stock movements are created but stock tracking may need products to have `track_stock = true`
- Dashboard cards need to be added via "Add Card" button - they don't auto-populate
