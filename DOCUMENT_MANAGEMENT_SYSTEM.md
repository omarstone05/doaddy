# Document Management System

## Overview

The document management system provides comprehensive support for handling various document types, storing them with proper linking to business entities, and using historical context to enhance Addy's responses.

## Features

### 1. Document Type Support

The system now supports the following document types:

- **Invoices**: Outgoing invoices to create for customers
- **Receipts**: Expense receipts for recording transactions
- **Income Documents**: Documents showing money received
- **Quotations/Quotes**: Price quotes for customers
- **Client Lists**: Lists of customers/clients to import
- **Notes/Memos**: Written notes with key information
- **Contracts**: Contracts and agreements
- **Images**: Photos of receipts, invoices, etc. (using AI vision)

### 2. Document Processing

**DocumentProcessorService** (`app/Services/Addy/DocumentProcessorService.php`):
- Extracts text from images using AI Vision (OpenAI/Anthropic)
- Extracts text from PDFs (placeholder for library integration)
- Extracts text from Office documents (Word, Excel)
- Uses AI to extract structured data from documents
- Identifies document type automatically
- Extracts relevant business information (amounts, dates, customers, items, etc.)

### 3. Document Storage

**DocumentStorageService** (`app/Services/Document/DocumentStorageService.php`):
- Stores documents in organized folder structure: `documents/{organization_id}/{year}/{month}`
- Creates `Attachment` records with polymorphic relationships
- Links documents to entities (Customer, Invoice, Quote, ChatMessage, etc.)
- Optionally creates `Document` records for better organization
- Handles file deletion

### 4. Historical Context

**DocumentContextService** (`app/Services/Document/DocumentContextService.php`):
- Retrieves historical documents for context
- Searches by customer name, document type, or date
- Provides context from:
  - Past chat messages with attachments
  - Customer invoices and quotes
  - Linked documents
- Enables Addy to reference past information in conversations

### 5. Upload Locations

Documents can be uploaded in multiple places:

#### A. Addy Chat
- **Location**: Chat interface
- **Use Case**: Quick document processing and information extraction
- **Storage**: Files stored in `chat-attachments/{organization_id}/`
- **Features**: 
  - Automatic data extraction
  - Action creation (invoice, transaction, etc.)
  - Historical context linking

#### B. Customer Records
- **Location**: Customer create/edit forms
- **Use Case**: Attach contracts, agreements, or customer documents
- **API**: `POST /api/addy/attachments` with `attachable_type=App\Models\Customer`
- **Storage**: Files stored in `documents/{organization_id}/{year}/{month}/`

#### C. Invoice Records
- **Location**: Invoice create/edit forms
- **Use Case**: Attach supporting documents, purchase orders, etc.
- **API**: `POST /api/addy/attachments` with `attachable_type=App\Models\Invoice`

#### D. Quote Records
- **Location**: Quote create/edit forms
- **Use Case**: Attach reference documents, specifications, etc.
- **API**: `POST /api/addy/attachments` with `attachable_type=App\Models\Quote`

#### E. Expense/Transaction Records
- **Location**: Money movement forms
- **Use Case**: Attach receipts, bills, etc.
- **API**: `POST /api/addy/attachments` with `attachable_type=App\Models\MoneyMovement`

## API Endpoints

### Upload Attachment
```
POST /api/addy/attachments
Content-Type: multipart/form-data

Parameters:
- file: File (required)
- attachable_type: String (required) - e.g., "App\Models\Customer"
- attachable_id: UUID (required)
- category: String (optional) - e.g., "contract", "receipt"
```

### List Attachments
```
GET /api/addy/attachments?attachable_type=App\Models\Customer&attachable_id={uuid}
```

### Download Attachment
```
GET /api/addy/attachments/{id}/download
```

### Delete Attachment
```
DELETE /api/addy/attachments/{id}
```

## Database Structure

### Attachments Table
- Polymorphic relationship to any entity
- Stores file metadata (path, name, size, MIME type)
- Links to organization and uploader

### Documents Table
- General document management
- Categories and types for organization
- Status tracking (draft, active, archived)

### Addy Chat Messages
- Stores attachments as JSON in `attachments` column
- Stores extracted data in `metadata->extracted_data`
- Links to `Attachment` records for historical context

## Historical Context Usage

When Addy responds to queries, the system:

1. **Extracts Context**: Identifies customer names, document types, or keywords from the user's message
2. **Retrieves History**: Fetches relevant historical documents using `DocumentContextService`
3. **Provides Context**: Includes historical information in the AI system message
4. **Enhances Responses**: Addy can reference past invoices, quotes, or transactions

### Example
User: "What invoices do we have for ABC Company?"
- System extracts "ABC Company" as customer name
- Retrieves all invoices and quotes for that customer
- Provides context to Addy
- Addy responds with relevant information from history

## Document Type Detection

The AI prompt in `DocumentProcessorService` is designed to identify:
- Document type (invoice, receipt, quote, etc.)
- Financial information (amounts, currencies, dates)
- Business entities (customers, merchants, vendors)
- Line items and details
- Special information (contract terms, client lists, notes)

## Future Enhancements

1. **PDF Parsing**: Integrate library like `Smalot\PdfParser` for better PDF text extraction
2. **Office Document Parsing**: Integrate `PhpOffice\PhpWord` and `PhpOffice\PhpSpreadsheet`
3. **Bulk Import**: Create actions for importing client lists from documents
4. **Document Search**: Full-text search across all documents
5. **Document Versioning**: Track document changes over time
6. **OCR Enhancement**: Better OCR for scanned documents
7. **Document Templates**: Store and use document templates

## Usage Examples

### Upload Document in Chat
```javascript
const formData = new FormData();
formData.append('files[]', file);
formData.append('message', 'Process this invoice');

axios.post('/api/addy/chat', formData, {
    headers: { 'Content-Type': 'multipart/form-data' }
});
```

### Upload Document to Customer
```javascript
const formData = new FormData();
formData.append('file', file);
formData.append('attachable_type', 'App\\Models\\Customer');
formData.append('attachable_id', customerId);
formData.append('category', 'contract');

axios.post('/api/addy/attachments', formData, {
    headers: { 'Content-Type': 'multipart/form-data' }
});
```

### Get Historical Context
```php
$contextService = new DocumentContextService();
$context = $contextService->getHistoricalContext(
    $organizationId,
    $customerName = 'ABC Company',
    $documentType = 'invoice',
    $limit = 10
);
```

## Integration Points

1. **Addy Chat**: Automatic document processing and action creation
2. **Customer Management**: Attach documents to customer records
3. **Invoice Management**: Link supporting documents to invoices
4. **Quote Management**: Attach reference documents to quotes
5. **Expense Management**: Attach receipts to transactions
6. **Historical Queries**: Use past documents to answer questions

