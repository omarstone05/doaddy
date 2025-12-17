# Complete Document Management System Documentation

## Table of Contents
1. [System Overview](#system-overview)
2. [Architecture](#architecture)
3. [File Flow](#file-flow)
4. [OCR Services](#ocr-services)
5. [Data Extraction](#data-extraction)
6. [Storage System](#storage-system)
7. [Import Logic](#import-logic)
8. [Frontend Components](#frontend-components)
9. [Routes & Endpoints](#routes--endpoints)
10. [Database Models](#database-models)
11. [Known Issues](#known-issues)
12. [Rebuild Recommendations](#rebuild-recommendations)

---

## System Overview

The document management system handles:
- **Receipts** (expense transactions)
- **Invoices** (outgoing invoices to customers)
- **Bank Statements** (multiple transactions)
- **Mobile Money** transactions
- **Income Documents**
- **Quotes/Quotations**
- **Client Lists**
- **Notes/Memos**
- **Contracts**

### Key Features
- AI-powered OCR (OpenAI Vision / Anthropic Claude)
- Context-aware processing
- Uncertainty analysis and question generation
- Historical data support
- Batch processing
- Review interface for low-confidence extractions
- Automatic import for high-confidence documents

---

## Architecture

### Service Layer Hierarchy

```
DocumentProcessorService (Base)
    ↓
ImprovedOcrService (OCR Processing)
    ↓
ContextAwareOcrService (Context + Uncertainty)
```

### Key Services

1. **DocumentProcessorService** (`app/Services/Addy/DocumentProcessorService.php`)
   - Text extraction from images, PDFs, Office docs
   - Structured data extraction using AI
   - Document type identification

2. **ImprovedOcrService** (`app/Services/ImprovedOcrService.php`)
   - Wraps DocumentProcessorService
   - Calculates confidence scores
   - Handles file path resolution

3. **ContextAwareOcrService** (`app/Services/ContextAwareOcrService.php`)
   - Extends ImprovedOcrService
   - Analyzes uncertainty
   - Generates clarifying questions
   - Enriches with historical context

4. **DocumentStorageService** (`app/Services/Document/DocumentStorageService.php`)
   - File storage management
   - Attachment record creation
   - Document record creation

5. **DocumentContextService** (`app/Services/Document/DocumentContextService.php`)
   - Historical document retrieval
   - Customer context
   - Document search

### Controllers

1. **EnhancedDataUploadController** (`app/Http/Controllers/EnhancedDataUploadController.php`)
   - Upload center endpoints
   - OCR analysis
   - Import reviewed data
   - Batch processing

2. **AddyChatController** (`app/Http/Controllers/AddyChatController.php`)
   - Chat-based document uploads
   - File processing in chat context

3. **AttachmentController** (`app/Http/Controllers/AttachmentController.php`)
   - Entity attachments (Customer, Invoice, etc.)

---

## File Flow

### Upload Center Flow

```
User uploads file
    ↓
EnhancedDataUploadController::analyze()
    ↓
Store file temporarily: temp/uploads/{filename}
    ↓
ContextAwareOcrService::processDocumentWithContext()
    ↓
ImprovedOcrService::processDocument()
    ↓
DocumentProcessorService::processFile()
    ↓
Extract text (AI Vision for images, PDF parser for PDFs)
    ↓
Extract structured data (AI prompt)
    ↓
Analyze uncertainty (ContextAwareOcrService)
    ↓
Generate questions if needed
    ↓
Return analysis result
    ↓
Frontend: OcrReviewInterface (if needs review)
    ↓
User reviews/answers questions
    ↓
EnhancedDataUploadController::importOcrReviewed()
    ↓
Import based on document type
    ↓
Create MoneyMovement records
    ↓
Clean up temp file
```

### Chat Upload Flow

```
User uploads file in chat
    ↓
AddyChatController::sendMessage()
    ↓
DocumentProcessorService::processFile()
    ↓
Extract text and structured data
    ↓
Store attachment record
    ↓
Add extracted data to message metadata
    ↓
AddyResponseGenerator processes intent
    ↓
Create action if needed (create_transaction, etc.)
```

---

## OCR Services

### DocumentProcessorService

**Location:** `app/Services/Addy/DocumentProcessorService.php`

**Key Methods:**

1. **`processFile(UploadedFile $file, string $organizationId): array`**
   - Stores file: `chat-attachments/{organizationId}/{filename}`
   - Extracts text based on file type
   - Extracts structured data using AI
   - Returns: `file_path`, `file_name`, `file_size`, `mime_type`, `extracted_text`, `extracted_data`

2. **`extractText(UploadedFile $file, string $filePath): string`**
   - Routes to appropriate extraction method based on MIME type

3. **`extractTextFromImage(UploadedFile $file): string`**
   - Uses OpenAI Vision API or Anthropic Claude
   - Converts image to base64
   - Sends to AI with prompt: "Extract ALL text and information..."

4. **`extractTextFromPdf(UploadedFile $file): string`**
   - Tries multiple methods:
     1. `pdftotext` command-line tool
     2. `qpdf` for password-protected PDFs
     3. PHP PDF Parser (`Smalot\PdfParser`)
     4. AI Vision (as fallback)

5. **`extractStructuredData(string $text, string $mimeType): array`**
   - Uses AI to extract structured JSON
   - Identifies document type
   - Extracts fields based on document type

### ImprovedOcrService

**Location:** `app/Services/ImprovedOcrService.php`

**Key Methods:**

1. **`processDocument(string $filePath): array`**
   - Handles file path resolution (storage vs. system paths)
   - Wraps DocumentProcessorService
   - Calculates confidence score
   - Returns: `success`, `data`, `document_type`, `raw_text`, `confidence`, `file_path`

2. **`calculateConfidence(array $data, string $documentType): float`**
   - Checks required fields for document type
   - Returns average confidence (0.0 - 1.0)

3. **`getRequiredFields(string $documentType): array`**
   - Returns required fields per document type:
     - `receipt`: `['date', 'total', 'merchant']`
     - `invoice`: `['date', 'total', 'invoice_number']`
     - `mobile_money`: `['date', 'amount', 'transaction_id']`
     - `bank_statement`: `['date', 'amount']`

### ContextAwareOcrService

**Location:** `app/Services/ContextAwareOcrService.php`

**Key Methods:**

1. **`processDocumentWithContext(string $filePath, array $userContext, bool $isHistorical): array`**
   - Processes document with standard OCR
   - Analyzes uncertainty
   - Enriches with historical context if applicable
   - Generates clarifying questions
   - Returns: `success`, `data`, `document_type`, `confidence`, `uncertainty_analysis`, `questions`, `requires_review`, `auto_importable`

2. **`analyzeUncertainty(array $data, string $documentType): array`**
   - Checks critical fields for confidence < 0.7
   - Returns: `has_uncertainty`, `uncertain_fields`, `confidence_scores`, `average_confidence`, `needs_review`

3. **`assessFieldConfidence(string $field, $value, array $allData): float`**
   - Validates field values:
     - **Date**: Checks if future, too old, format
     - **Amount**: Checks if zero/negative, too large, decimal format
     - **Merchant/Vendor**: Checks length, numeric content
     - **Phone**: Validates Zambian phone format
     - **Provider**: Validates mobile money provider

4. **`generateClarifyingQuestions(array $data, array $uncertainFields, string $documentType, array $userContext): array`**
   - Generates questions for uncertain fields
   - Skips inappropriate fields (e.g., "total amount" for bank statements)
   - Adds contextual questions

5. **`generateFieldQuestion(array $fieldInfo, array $data, string $type, array $context): ?array`**
   - Creates question objects with:
     - `field`: Field name
     - `type`: Question type (`text_input`, `number_input`, `date_picker`, `select`, `text_with_suggestions`, `confirmation`)
     - `question`: Question text
     - `reason`: Why question is needed
     - `current_value`: Extracted value
     - `suggestions`: Suggested values (if applicable)
     - `options`: Options for select questions

---

## Data Extraction

### AI Prompt Structure

The system uses a detailed prompt to extract structured data:

```php
"Extract structured information from this document text. Focus on identifying:
1. Document type: 'invoice', 'receipt', 'income', 'quote', 'bank_statement', 'client_list', 'note', 'contract', or 'unknown'

2. For BANK STATEMENTS specifically:
   - Extract account number, account name, bank name
   - Extract statement period (start and end dates)
   - Extract opening balance and closing balance
   - Extract ALL transactions - this is critical! Each transaction must have:
     * date: in format like 'Sep 23' or 'Sep 23 2025' or '2025-09-23'
     * amount: numeric value (positive number)
     * description: full transaction description/merchant name
     * type: 'debit' or 'credit' (or 'expense'/'income')
     * flow_type: 'expense' for debits/withdrawals, 'income' for credits/deposits
     * balance: running balance after transaction (if available)

3. Amount(s) and currency
4. Date(s) including due_date if it's an invoice or quote
5. Customer/client name (if invoice/quote) or merchant/vendor name (if receipt/expense)
6. Description/items
7. Category (if identifiable)
8. Any other relevant business transaction details

Return the information in JSON format..."
```

### Expected JSON Structure

**Receipt:**
```json
{
  "document_type": "receipt",
  "type": "expense",
  "amount": 150.00,
  "currency": "ZMW",
  "date": "2025-01-15",
  "merchant": "Shoprite",
  "category": "groceries",
  "description": "Purchase from Shoprite"
}
```

**Bank Statement:**
```json
{
  "document_type": "bank_statement",
  "type": "bank_statement",
  "currency": "ZMW",
  "account_number": "63025208679",
  "account_name": "Account Name",
  "bank_name": "First National Bank Zambia",
  "statement_period_start": "2024-09-29",
  "statement_period_end": "2024-10-17",
  "opening_balance": null,
  "closing_balance": 11846.97,
  "transactions": [
    {
      "date": "Sep 29",
      "amount": 3000.00,
      "description": "Bank To Wallet Payment B2W",
      "type": "debit",
      "flow_type": "expense",
      "balance": 19240.97
    },
    {
      "date": "Oct 01",
      "amount": 10000.00,
      "description": "FNB OB Pmt Payos",
      "type": "credit",
      "flow_type": "income",
      "balance": 29240.97
    }
  ]
}
```

**Invoice:**
```json
{
  "document_type": "invoice",
  "type": "invoice",
  "total_amount": 5000.00,
  "currency": "ZMW",
  "date": "2025-01-15",
  "due_date": "2025-02-15",
  "invoice_number": "INV-001",
  "customer_name": "ABC Company",
  "items": [
    {
      "description": "Product A",
      "quantity": 2,
      "unit_price": 2500.00
    }
  ]
}
```

---

## Storage System

### File Storage Paths

1. **Chat Attachments:** `chat-attachments/{organizationId}/{filename}`
2. **Entity Attachments:** `documents/{organizationId}/{year}/{month}/{filename}`
3. **Temp Uploads:** `temp/uploads/{filename}`

### Storage Service

**DocumentStorageService** (`app/Services/Document/DocumentStorageService.php`)

**Methods:**

1. **`storeDocument(UploadedFile $file, string $organizationId, ?string $attachableType, ?string $attachableId, ?string $category, ?int $uploadedById): Attachment`**
   - Stores file in `documents/{organizationId}/{year}/{month}/`
   - Creates `Attachment` record
   - Optionally creates `Document` record if category provided

2. **`storeFromChat(array $fileData, string $organizationId, int $chatMessageId, $uploadedById): Attachment`**
   - Creates attachment record for chat message
   - File already stored, just creates record

3. **`deleteDocument(Attachment $attachment): bool`**
   - Deletes file from storage
   - Deletes attachment record

---

## Import Logic

### EnhancedDataUploadController Import Methods

**Location:** `app/Http/Controllers/EnhancedDataUploadController.php`

#### 1. `importReceipt($user, $organizationId, array $data)`

- Gets or creates default expense account
- Creates `MoneyMovement` with:
  - `flow_type`: `expense`
  - `amount`: `$data['total'] ?? $data['amount']`
  - `from_account_id`: Expense account ID
  - `category`: `$data['category'] ?? 'Uncategorized'`
  - `description`: Generated from merchant/vendor/items

#### 2. `importIncome($user, $organizationId, array $data)`

- Gets or creates default income account
- Creates `MoneyMovement` with:
  - `flow_type`: `income`
  - `amount`: `$data['amount'] ?? $data['total']`
  - `to_account_id`: Income account ID
  - `category`: `$data['category'] ?? 'Income'`

#### 3. `importInvoice($user, $organizationId, array $data)`

- Currently treats as expense (calls `importReceipt`)
- **TODO:** Should create Invoice record

#### 4. `importMobileMoneyTransaction($user, $organizationId, array $data)`

- Determines type (income/expense) based on context
- Gets or creates Mobile Money account
- Creates `MoneyMovement` with appropriate flow type

#### 5. `importBankTransaction($user, $organizationId, array $data)` ⚠️ **CRITICAL**

**Current Implementation:**
- Checks for `$data['transactions']` array
- If empty: imports single transaction
- If present: loops through all transactions
- Creates `MoneyMovement` for each transaction
- Returns summary: `"Bank statement imported: X transactions imported, Y failed"`

**Issues:**
- Document type detection fails (returns "unknown")
- Transaction date parsing may fail for formats like "Sep 29"
- Account matching logic may not work correctly

---

## Frontend Components

### Enhanced Data Upload Page

**Location:** `resources/js/Pages/DataUpload/Enhanced.jsx`

**Features:**
- File selection (PDF, JPG, PNG)
- Historical data checkbox
- Analyze button
- Results display
- Auto-import for high confidence
- Review interface for low confidence

**State:**
- `selectedFile`: Selected file object
- `isHistorical`: Boolean flag
- `uploading`: Analysis in progress
- `analysisResult`: OCR analysis result
- `needsReview`: Whether review is required
- `importing`: Import in progress

**Methods:**
- `handleAnalyze()`: POSTs to `/data-upload/analyze`
- `handleReviewSubmit()`: POSTs reviewed data to `/data-upload/import-ocr-reviewed`
- `handleAutoImport()`: Auto-imports high-confidence documents

### OCR Review Interface

**Location:** `resources/js/Components/OcrReviewInterface.jsx`

**Features:**
- Question-by-question review
- Progress indicator
- Question types:
  - `text_input`: Text field
  - `number_input`: Number field with ZMW prefix
  - `date_picker`: Date picker
  - `select`: Multiple choice
  - `text_with_suggestions`: Text with suggestions
  - `confirmation`: Yes/No buttons
- Question navigation
- Answer tracking
- Submit final reviewed data

**Props:**
- `ocrResult`: Analysis result with `data`, `questions`, `document_type`
- `onSubmit`: Callback with reviewed data
- `onCancel`: Cancel callback

---

## Routes & Endpoints

### Data Upload Routes

```php
Route::prefix('data-upload')->name('data-upload.')->group(function () {
    Route::get('/', [EnhancedDataUploadController::class, 'index'])->name('index');
    Route::post('/analyze', [EnhancedDataUploadController::class, 'analyze'])->name('analyze');
    Route::post('/import-ocr-reviewed', [EnhancedDataUploadController::class, 'importOcrReviewed'])->name('import-ocr-reviewed');
    Route::post('/batch-historical', [EnhancedDataUploadController::class, 'batchHistorical'])->name('batch-historical');
    Route::post('/auto-import-batch', [EnhancedDataUploadController::class, 'autoImportBatch'])->name('auto-import-batch');
    Route::get('/context', [EnhancedDataUploadController::class, 'getContext'])->name('context');
});
```

### Endpoints

#### POST `/data-upload/analyze`

**Request:**
- `file`: File (PDF, JPG, PNG, max 10MB)
- `is_historical`: Boolean (nullable)

**Response:**
```json
{
  "success": true,
  "method": "ocr",
  "file_path": "temp/uploads/...",
  "analysis": {
    "success": true,
    "data": {...},
    "document_type": "bank_statement",
    "confidence": 0.85,
    "uncertainty_analysis": {...},
    "questions": [...],
    "requires_review": false,
    "auto_importable": true
  }
}
```

#### POST `/data-upload/import-ocr-reviewed`

**Request:**
- `file_path`: String (temp file path)
- `document_type`: String
- `data`: Object (reviewed data)
- `reviewed`: Boolean

**Response:**
```json
{
  "success": true,
  "message": "Bank statement imported: 5 transactions imported",
  "imported": 5,
  "failed": 0,
  "errors": []
}
```

---

## Database Models

### Attachment Model

**Location:** `app/Models/Attachment.php`

**Fields:**
- `id`: UUID
- `organization_id`: UUID
- `attachable_type`: String (polymorphic)
- `attachable_id`: UUID (polymorphic)
- `name`: String
- `file_path`: String
- `file_name`: String
- `file_size`: Integer
- `mime_type`: String
- `uploaded_by_id`: Integer (nullable)

**Relationships:**
- `attachable()`: MorphTo (polymorphic)
- `uploadedBy()`: BelongsTo User

### Document Model

**Location:** `app/Models/Document.php`

**Fields:**
- `id`: UUID
- `organization_id`: UUID
- `name`: String
- `description`: Text (nullable)
- `category`: String (nullable)
- `type`: String (nullable)
- `status`: Enum (`draft`, `active`, `archived`)
- `file_path`: String (nullable)
- `file_name`: String (nullable)
- `file_size`: Integer (nullable)
- `mime_type`: String (nullable)
- `created_by_id`: UUID (nullable)

**Relationships:**
- `createdBy()`: BelongsTo User
- `versions()`: HasMany DocumentVersion
- `attachments()`: MorphMany Attachment

---

## Known Issues

### 1. Document Type Detection Failure ⚠️ **CRITICAL**

**Problem:**
- Bank statements correctly extracted with `document_type: "bank_statement"` in `$data`
- But import fails with "Unknown document type: unknown"

**Root Cause:**
- `importOcrReviewed()` checks: `$data['document_type'] ?? $data['type'] ?? $request->document_type`
- Frontend sends: `document_type: analysisResult.document_type`
- But `analysisResult.document_type` may be "unknown" if not properly set

**Fix Applied:**
- Added logging to debug
- Check `$data['document_type']` first
- Use `strtolower()` for case-insensitive matching
- Still failing - needs investigation

### 2. Bank Statement Transaction Date Parsing

**Problem:**
- Bank statements have dates like "Sep 29", "Oct 01"
- `Carbon::parse()` may fail or parse incorrectly

**Solution Needed:**
- Custom date parser for bank statement formats
- Handle year inference (current year if not specified)

### 3. Inappropriate Questions for Bank Statements

**Problem:**
- System asks "The total amount appears to be ZMW X. Is this correct?"
- Bank statements have multiple transactions, not a single total

**Fix Applied:**
- Skip `amount` and `total` fields for bank statements in question generation
- Skip contextual questions for bank statements

### 4. Account Matching Logic

**Problem:**
- Bank statement import tries to match account by `account_number`
- May not find existing account
- Creates new account each time

**Solution Needed:**
- Better account matching logic
- Allow user to select account during review

### 5. PDF Processing Issues

**Problem:**
- Password-protected PDFs fail
- Image-based PDFs may not extract text correctly

**Current Handling:**
- Tries multiple methods: `pdftotext`, `qpdf`, PHP parser, AI vision
- Falls back to error message if all fail

### 6. OCR Confidence Calculation

**Problem:**
- Confidence calculation is simplistic (field presence check)
- Doesn't account for field quality

**Solution Needed:**
- More sophisticated confidence scoring
- Consider field validation results

---

## Rebuild Recommendations

### 1. Unified Document Type Detection

**Current:** Multiple checks in different places
**Recommended:** Single source of truth

```php
class DocumentTypeDetector {
    public function detect(array $data, string $extractedText): string {
        // Check data['document_type'] first
        // Check data['type']
        // Analyze extracted text if needed
        // Return normalized type
    }
}
```

### 2. Improved Bank Statement Processing

**Issues to Address:**
- Transaction date parsing (handle "Sep 29" format)
- Account matching (user selection during review)
- Transaction deduplication (check for existing transactions)
- Balance validation (verify running balances)

**Recommended Flow:**
```
1. Extract all transactions
2. Parse dates (with year inference)
3. Show account selection in review
4. Check for duplicates
5. Validate balances
6. Import transactions
```

### 3. Better Error Handling

**Current:** Generic error messages
**Recommended:** Specific error messages with recovery suggestions

### 4. Transaction Deduplication

**Current:** No duplicate checking
**Recommended:** Hash-based duplicate detection

```php
$hash = md5($accountId . $date . $amount . $description);
// Check if hash exists
```

### 5. Review Interface Improvements

**Current:** Question-by-question
**Recommended:**
- Show all extracted data in editable table
- Allow bulk editing
- Show confidence scores
- Preview before import

### 6. Batch Processing

**Current:** Basic batch processing
**Recommended:**
- Progress tracking
- Error recovery
- Resume capability
- Summary report

### 7. Storage Optimization

**Current:** Files stored in multiple locations
**Recommended:**
- Unified storage service
- File deduplication
- Automatic cleanup of temp files
- Archive old documents

### 8. Testing

**Recommended:**
- Unit tests for OCR services
- Integration tests for import logic
- Test with various document formats
- Test with edge cases (missing fields, invalid dates, etc.)

### 9. Performance Optimization

**Current:** Sequential processing
**Recommended:**
- Queue-based processing for batch uploads
- Caching of OCR results
- Parallel processing where possible

### 10. Documentation

**Recommended:**
- API documentation
- User guide
- Developer guide
- Troubleshooting guide

---

## File Structure

```
app/
├── Http/
│   └── Controllers/
│       ├── EnhancedDataUploadController.php
│       ├── AddyChatController.php
│       └── AttachmentController.php
├── Services/
│   ├── Addy/
│   │   └── DocumentProcessorService.php
│   ├── Document/
│   │   ├── DocumentStorageService.php
│   │   └── DocumentContextService.php
│   ├── ImprovedOcrService.php
│   └── ContextAwareOcrService.php
└── Models/
    ├── Attachment.php
    └── Document.php

resources/js/
├── Pages/
│   └── DataUpload/
│       └── Enhanced.jsx
└── Components/
    └── OcrReviewInterface.jsx

database/migrations/
├── create_attachments_table.php
└── create_documents_table.php
```

---

## Configuration

### AI Provider Settings

Stored in `platform_settings` table:
- `ai_provider`: `openai` or `anthropic`
- `openai_api_key`: OpenAI API key
- `openai_model`: Model name (default: `gpt-4o`)
- `anthropic_api_key`: Anthropic API key
- `anthropic_model`: Model name (default: `claude-sonnet-4-20250514`)

### File Upload Limits

- Max file size: 10MB
- Allowed types: `pdf`, `jpg`, `jpeg`, `png`, `csv`, `txt`
- Storage disk: `public`

---

## Conclusion

The document management system is comprehensive but has several critical issues that need to be addressed:

1. **Document type detection** - Needs unified detection logic
2. **Bank statement processing** - Needs better date parsing and account matching
3. **Error handling** - Needs more specific error messages
4. **Testing** - Needs comprehensive test coverage
5. **Performance** - Needs optimization for batch processing

The rebuild should focus on:
- Simplifying the architecture
- Improving error handling
- Better user experience in review interface
- Comprehensive testing
- Performance optimization

