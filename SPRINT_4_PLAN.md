# Sprint 4 Plan - Inventory & Products Management

## Overview
Sprint 4 focuses on building out the complete inventory and products management system. This includes full CRUD operations for products, stock management, stock movements tracking, and related features.

## Goals
- Complete product management interface
- Stock level tracking and management
- Stock movement history and reporting
- Product categorization
- Low stock alerts
- Stock adjustments

## Features to Implement

### 1. Products/Goods & Services CRUD ✅ Priority
**Status**: Model exists, needs frontend

**Tasks**:
- [ ] Products Index page with search and filters
- [ ] Product Create page with all fields
- [ ] Product Edit page
- [ ] Product Show/View page
- [ ] Product deletion (soft delete or hard delete)
- [ ] Product activation/deactivation toggle
- [ ] Product categories management

**Fields to Include**:
- Basic Info: Name, SKU, Barcode, Description
- Pricing: Cost Price, Selling Price
- Stock: Current Stock, Minimum Stock, Unit, Track Stock flag
- Type: Product vs Service
- Category
- Active status

### 2. Stock Management ✅ Priority
**Status**: Stock movements exist, needs UI

**Tasks**:
- [ ] Stock levels dashboard
- [ ] Stock movement history page
- [ ] Stock movement detail view
- [ ] Filter by product, date range, movement type
- [ ] Stock value calculations

**Movement Types**:
- In (Purchase)
- Out (Sale)
- Adjustment (Manual)
- Return (Sale Return)

### 3. Stock Movements Tracking ✅ Priority
**Status**: Model exists, needs frontend

**Tasks**:
- [ ] Stock movements index page
- [ ] Filter by product, date, type
- [ ] View movement details
- [ ] Link to related transactions (sales, purchases)

### 4. Product Categories ✅ Medium Priority
**Status**: Need to add category management

**Tasks**:
- [ ] Categories CRUD
- [ ] Category assignment to products
- [ ] Category-based filtering
- [ ] Category hierarchy (optional)

### 5. Low Stock Alerts ✅ Medium Priority
**Status**: Need to implement

**Tasks**:
- [ ] Dashboard widget showing low stock items
- [ ] Low stock products list page
- [ ] Email/notification alerts (optional)
- [ ] Configurable alert thresholds

### 6. Product Search Enhancement ✅ Medium Priority
**Status**: Basic search exists in POS

**Tasks**:
- [ ] Advanced search with multiple filters
- [ ] Search by category, price range, stock level
- [ ] Save search filters
- [ ] Export search results

### 7. Stock Adjustments ✅ Medium Priority
**Status**: Need to implement

**Tasks**:
- [ ] Manual stock adjustment form
- [ ] Adjustment reasons (Damage, Loss, Found, etc.)
- [ ] Approval workflow (optional)
- [ ] Stock adjustment history

### 8. Bulk Operations ✅ Low Priority
**Status**: Nice to have

**Tasks**:
- [ ] CSV import for products
- [ ] Bulk update (price, stock, status)
- [ ] Export products to CSV
- [ ] Template download for import

## Technical Implementation

### Models to Enhance
- `GoodsAndService` - Add category relationship, enhance queries
- `StockMovement` - Add more detailed tracking
- `Category` - New model for product categories

### Controllers to Create
- `ProductController` - Full CRUD for products
- `StockMovementController` - Stock movement management
- `CategoryController` - Category management
- `StockAdjustmentController` - Stock adjustments

### Frontend Pages to Create
- `/products` - Products listing
- `/products/create` - Create product
- `/products/{id}` - View product
- `/products/{id}/edit` - Edit product
- `/stock` - Stock levels dashboard
- `/stock/movements` - Stock movements history
- `/stock/adjustments` - Stock adjustments
- `/categories` - Category management

### Routes to Add
```php
// Products
Route::resource('products', ProductController::class);

// Stock Management
Route::get('/stock', [StockController::class, 'index'])->name('stock.index');
Route::get('/stock/movements', [StockMovementController::class, 'index'])->name('stock.movements.index');
Route::get('/stock/movements/{movement}', [StockMovementController::class, 'show'])->name('stock.movements.show');
Route::post('/stock/adjustments', [StockAdjustmentController::class, 'store'])->name('stock.adjustments.store');

// Categories
Route::resource('categories', CategoryController::class);
```

## Dependencies
- GoodsAndService model (exists)
- StockMovement model (exists)
- Stock movements already created by sales/returns

## Integration Points
- POS product selection (already integrated)
- Sales stock deduction (already integrated)
- Sale returns stock restoration (already integrated)

## Success Criteria
- ✅ Full CRUD for products
- ✅ Stock levels visible and manageable
- ✅ Stock movement history tracked
- ✅ Low stock alerts functional
- ✅ Stock adjustments possible
- ✅ Product categories implemented

## Estimated Effort
- Core CRUD: 4-6 hours
- Stock Management: 3-4 hours
- Categories: 2-3 hours
- Low Stock Alerts: 2-3 hours
- Stock Adjustments: 2-3 hours
- **Total**: ~15-20 hours

## Next Sprint Preview
Sprint 5 could focus on:
- Purchasing/Procurement
- Reports & Analytics
- Settings & Configuration
- Team Management (Departments, Leave, Payroll)

