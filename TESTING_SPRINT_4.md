# Sprint 4 Testing Guide

## Pre-Testing Setup

### 1. Ensure Database is Migrated
```bash
php artisan migrate
```

### 2. Start Development Servers
```bash
# Terminal 1: Laravel server
php artisan serve

# Terminal 2: Vite dev server
npm run dev
```

### 3. Login/Register
- Navigate to `http://localhost:8000`
- Login or register a new account
- Ensure you have an organization set up

---

## Test 1: Products CRUD

### 1.1 Create Product
1. Navigate to `/products` (via sidebar → Inventory → Products)
2. Click "New Product" button
3. Fill in the form:
   - Name: "Test Product"
   - Type: Product
   - SKU: "TEST-001"
   - Barcode: "123456789"
   - Cost Price: 10.00
   - Selling Price: 15.00
   - Enable "Track Stock"
   - Current Stock: 100
   - Minimum Stock: 20
   - Unit: "pcs"
   - Category: "Electronics"
   - Check "Active"
4. Click "Create Product"
5. ✅ Should redirect to product detail page
6. ✅ Should see product information displayed

### 1.2 View Products List
1. Navigate to `/products`
2. ✅ Should see "Test Product" in the list
3. ✅ Should see stock information (100 pcs)
4. ✅ Should see prices displayed
5. Test filters:
   - Filter by Type: Select "Product" → Should show only products
   - Filter by Category: Select "Electronics" → Should show only Electronics
   - Search: Type "Test" → Should show Test Product
   - Filter by Status: Select "Active" → Should show active products

### 1.3 View Product Details
1. Click on "Test Product" or eye icon
2. ✅ Should see full product details
3. ✅ Should see stock information with visual indicators
4. ✅ Should see pricing information
5. ✅ Should see profit margin calculation

### 1.4 Edit Product
1. From product detail page, click "Edit"
2. Change selling price to 18.00
3. Change current stock to 50
4. Click "Update Product"
5. ✅ Should redirect to product detail page
6. ✅ Should see updated values

### 1.5 Delete Product (Should Fail)
1. Navigate to `/products`
2. Click delete icon on Test Product
3. ✅ Should show confirmation dialog
4. Confirm deletion
5. ✅ Should show error: "Cannot delete product that has been used in sales"
6. ✅ Product should still exist

### 1.6 Create Service
1. Navigate to `/products/create`
2. Fill in:
   - Name: "Consultation Service"
   - Type: Service
   - Description: "Hourly consultation"
   - Selling Price: 50.00
   - Category: "Services"
3. Click "Create Product"
4. ✅ Should create successfully
5. ✅ Should NOT show stock tracking options (services don't track stock)

---

## Test 2: Stock Management

### 2.1 View Stock Dashboard
1. Navigate to `/stock` (via sidebar → Inventory → Stock)
2. ✅ Should see statistics cards:
   - Total Products
   - Low Stock Items
   - Total Stock Value
3. ✅ Should see "Test Product" in the list
4. ✅ Should see current stock: 50 pcs
5. ✅ Should see stock value (cost price × current stock)

### 2.2 Filter Stock by Low Stock
1. On stock page, click "Low Stock Only" filter
2. ✅ Should only show products at or below minimum stock
3. Update Test Product minimum stock to 60
4. Refresh page and filter again
5. ✅ Should now show Test Product in low stock

### 2.3 Stock Value Calculation
1. Verify stock value = current_stock × cost_price
2. For Test Product: 50 × 10.00 = 500.00
3. ✅ Should display correctly

---

## Test 3: Stock Movements

### 3.1 View Stock Movements
1. Navigate to `/stock/movements` (via sidebar → Inventory → Stock Movements)
2. ✅ Should see movements list (may be empty initially)
3. Create a sale from POS to generate movements:
   - Go to `/pos`
   - Add Test Product to cart
   - Complete sale
4. Return to `/stock/movements`
5. ✅ Should see new movement with type "Out"
6. ✅ Should see quantity decreased
7. ✅ Should see reference number (sale number)

### 3.2 Filter Movements
1. Select Test Product from filter dropdown
2. ✅ Should show only movements for that product
3. Select movement type "Out"
4. ✅ Should show only outgoing movements
5. Set date range
6. ✅ Should filter by date

### 3.3 View Movement Details
1. Click on any movement
2. ✅ Should see full movement details
3. ✅ Should see product name
4. ✅ Should see movement type (In/Out)
5. ✅ Should see quantity and unit
6. ✅ Should see reference number
7. ✅ Should see notes/description
8. ✅ Should see created by user

---

## Test 4: Stock Adjustments

### 4.1 Create Stock Increase Adjustment
1. Navigate to `/stock/adjustments/create` (via Stock page → "Stock Adjustment" button)
2. Select "Test Product" from dropdown
3. ✅ Should see current stock displayed
4. Select adjustment type: "Increase Stock"
5. Enter quantity: 25
6. Select reason: "Found"
7. Add notes: "Found in warehouse"
8. ✅ Should see preview: Current (50) → New (75)
9. Click "Record Adjustment"
10. ✅ Should redirect to movement detail page
11. ✅ Should see new movement created
12. Go back to product detail
13. ✅ Should see stock increased to 75

### 4.2 Create Stock Decrease Adjustment
1. Navigate to `/stock/adjustments/create`
2. Select "Test Product"
3. Select adjustment type: "Decrease Stock"
4. Enter quantity: 10
5. Select reason: "Damage"
6. Add notes: "Damaged during handling"
7. ✅ Should see preview: Current (75) → New (65)
8. Click "Record Adjustment"
9. ✅ Should see stock decreased to 65
10. ✅ Should see movement with type "Out"

### 4.3 Verify Adjustment Creates Movement
1. Go to `/stock/movements`
2. Filter by product: Test Product
3. ✅ Should see adjustment movements
4. ✅ Should see reference numbers starting with "ADJ-"
5. ✅ Should see notes with reason

---

## Test 5: Integration Tests

### 5.1 Sale Creates Stock Movement
1. Ensure Test Product has stock > 0
2. Go to `/pos`
3. Add Test Product to cart (quantity: 5)
4. Complete sale
5. Go to `/products/{id}` (Test Product)
6. ✅ Should see stock decreased by 5
7. Go to `/stock/movements`
8. ✅ Should see new movement with type "Out"
9. ✅ Should see quantity: 5
10. ✅ Should see reference to sale number

### 5.2 Return Restores Stock
1. Go to `/sale-returns/create`
2. Search for the sale you just created
3. Select items to return (quantity: 2)
4. Complete return
5. Go to `/products/{id}` (Test Product)
6. ✅ Should see stock increased by 2
7. Go to `/stock/movements`
8. ✅ Should see new movement with type "In"
9. ✅ Should see quantity: 2

### 5.3 Low Stock Detection
1. Set Test Product minimum stock to 70
2. Current stock is 65 (from previous tests)
3. Go to `/products`
4. ✅ Should see warning icon next to Test Product
5. ✅ Should see red text for stock quantity
6. Go to `/stock`
7. ✅ Should see Test Product in low stock filter
8. ✅ Should see low stock count in statistics

---

## Quick Test Commands

```bash
# Check database
php artisan tinker
>>> \App\Models\GoodsAndService::count()
>>> \App\Models\StockMovement::count()
>>> \App\Models\GoodsAndService::where('track_stock', true)->count()

# Check routes
php artisan route:list --path=products
php artisan route:list --path=stock

# Clear cache if needed
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

---

## Troubleshooting

### If products don't appear:
- Check organization_id is set
- Verify user is logged in
- Check database has products

### If stock movements don't show:
- Verify sales/returns were completed
- Check StockMovement model relationships
- Check organization_id matches

### If navigation doesn't work:
- Clear browser cache
- Restart dev server
- Check routes are registered

### If filters don't work:
- Check URL parameters
- Verify controller filters logic
- Check frontend router implementation

