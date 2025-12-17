# Import Functions Mapping Audit
Generated: 2025-12-11

## Current Import Mappings

### AgentDataUploadController
- ✅ `bank_statement` → `importBankStatement()` → Creates MoneyMovement records
- ✅ `receipt` / `expense` → `importReceipt()` → Creates MoneyMovement (expense)
- ✅ `income` → `importIncome()` → Creates MoneyMovement (income)
- ✅ `mobile_money` → `importMobileMoney()` → Creates MoneyMovement
- ⚠️ `invoice` → `importInvoice()` → Currently calls `importReceipt()` (treats as expense)

### EnhancedDataUploadController
- ✅ `receipt` / `expense` → `importReceipt()` → Creates MoneyMovement (expense)
- ✅ `income` → `importIncome()` → Creates MoneyMovement (income)
- ✅ `mobile_money` → `importMobileMoneyTransaction()` → Creates MoneyMovement
- ✅ `bank_statement` → `importBankTransaction()` → Creates MoneyMovement records
- ⚠️ `invoice` → `importInvoice()` → Currently calls `importReceipt()` (treats as expense)

### DocumentProcessingAgent (Auto-import)
- ✅ `bank_statement` → `importBankStatement()`
- ✅ `receipt` / `expense` → `importReceipt()`
- ✅ `income` → `importIncome()`
- ✅ `mobile_money` → `importMobileMoney()`
- ❌ `invoice` → **NOT MAPPED** (missing from switch statement)

## Issues Found

### 1. Invoice Import Logic
**Issue**: `importInvoice()` in both controllers currently just calls `importReceipt()`, treating all invoices as expenses.

**Analysis**:
- Incoming invoices (bills from vendors) → Should be expenses (current behavior is correct)
- Outgoing invoices (to customers) → Should create Invoice records (not currently handled)

**Recommendation**: 
- Keep current behavior for incoming invoices (bills)
- If data indicates it's an outgoing invoice (has customer info, items, etc.), create Invoice record instead

### 2. Missing Invoice Mapping in DocumentProcessingAgent
**Issue**: The `autoImport()` method in `DocumentProcessingAgent` doesn't handle `invoice` document type.

**Fix Required**: Add `invoice` case to the switch statement.

## Verification Status

✅ All document types are properly mapped in controllers
✅ Receipt/expense imports correctly create MoneyMovement records
✅ Income imports correctly create MoneyMovement records
✅ Bank statement imports correctly create MoneyMovement records
✅ Mobile money imports correctly create MoneyMovement records
⚠️ Invoice imports need improvement (currently works but could be better)
❌ Invoice not mapped in DocumentProcessingAgent auto-import

