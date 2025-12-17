# Sprint 8 Testing Guide - Documents & Compliance Management

## Overview
This guide covers testing for Sprint 8 features: Documents Management, Licenses Tracking, and Certificates Management.

## Prerequisites
- Laravel application running
- Database migrations run (`php artisan migrate`)
- User logged in with organization access
- Frontend assets compiled (`npm run dev` or `npm run build`)

## Test Environment Setup

### 1. Verify Routes
```bash
php artisan route:list | grep -E "(documents|licenses|certificates)"
```

Expected routes:
- GET `/compliance/documents` - Documents index
- POST `/compliance/documents` - Create document
- GET `/compliance/documents/create` - Create form
- GET `/compliance/documents/{id}` - View document
- GET `/compliance/documents/{id}/edit` - Edit form
- PUT `/compliance/documents/{id}` - Update document
- DELETE `/compliance/documents/{id}` - Delete document

Similar routes for licenses and certificates.

### 2. Database Check
```bash
php artisan tinker
```
```php
DB::table('documents')->count();
DB::table('licenses')->count();
DB::table('certificates')->count();
```

---

## Documents Management Testing

### Test 1: Navigate to Documents Page
**Steps:**
1. Log in to the application
2. Navigate to Compliance → Documents
3. Verify the page loads without errors

**Expected:**
- Page displays "Documents" heading
- "New Document" button is visible
- Empty state message if no documents exist
- Filters section visible (Status, Category, Search)

**Test Result:** [ ] Pass [ ] Fail

---

### Test 2: Create a New Document
**Steps:**
1. Click "New Document" button
2. Fill in the form:
   - Name: "Employee Handbook"
   - Description: "Company employee handbook v1.0"
   - Category: "Policy"
   - Type: "PDF"
   - Status: "Active"
3. Click "Create Document"

**Expected:**
- Form submits successfully
- Redirects to document show page
- Success message displayed
- Document appears in documents list

**Test Result:** [ ] Pass [ ] Fail

---

### Test 3: View Document Details
**Steps:**
1. From documents list, click on a document name or eye icon
2. Verify document details page loads

**Expected:**
- Document name displayed
- Description shown
- Category and type displayed
- Status badge visible
- Created by and created at dates shown
- Edit button visible

**Test Result:** [ ] Pass [ ] Fail

---

### Test 4: Edit Document
**Steps:**
1. From document show page, click "Edit Document"
2. Update the document:
   - Change status to "Archived"
   - Update description
3. Click "Update Document"

**Expected:**
- Form pre-populated with existing data
- Updates save successfully
- Redirects to show page
- Success message displayed
- Changes reflected in document details

**Test Result:** [ ] Pass [ ] Fail

---

### Test 5: Filter Documents
**Steps:**
1. Create multiple documents with different statuses (draft, active, archived)
2. Filter by status: "Active"
3. Filter by category
4. Use search to find documents by name

**Expected:**
- Filters work correctly
- Results update immediately
- Multiple filters can be combined
- Search finds documents by name or description

**Test Result:** [ ] Pass [ ] Fail

---

### Test 6: Delete Document
**Steps:**
1. Create a test document
2. From documents list, click delete icon
3. Confirm deletion

**Expected:**
- Confirmation dialog appears
- Document deleted successfully
- Redirects to documents list
- Success message displayed
- Document removed from list

**Test Result:** [ ] Pass [ ] Fail

---

## Licenses Management Testing

### Test 7: Navigate to Licenses Page
**Steps:**
1. Navigate to Compliance → Licenses
2. Verify the page loads

**Expected:**
- Page displays "Licenses" heading
- "New License" button visible
- Filters visible (Status, Category, Expiring, Search)
- Table with columns: License Number, Name, Issuing Authority, Expiry Date, Status, Actions

**Test Result:** [ ] Pass [ ] Fail

---

### Test 8: Create a New License
**Steps:**
1. Click "New License"
2. Fill in the form:
   - License Number: "BIZ-2024-001"
   - Name: "Business License"
   - Description: "Main business operating license"
   - Category: "Business"
   - Issuing Authority: "Ministry of Commerce"
   - Issue Date: Today's date
   - Expiry Date: One year from today
   - Status: "Active"
   - Is Renewable: Checked
   - Renewal Date: 30 days before expiry
3. Click "Create License"

**Expected:**
- Form validates correctly
- License created successfully
- Redirects to licenses list
- Success message displayed
- License appears in list with correct status

**Test Result:** [ ] Pass [ ] Fail

---

### Test 9: License Expiry Alerts
**Steps:**
1. Create a license with expiry date 25 days from now
2. View licenses list
3. Verify expiry alert appears

**Expected:**
- Yellow warning icon appears next to expiry date
- Expiring license is highlighted
- "Expiring Soon" filter shows this license

**Test Result:** [ ] Pass [ ] Fail

---

### Test 10: License Status Auto-Update
**Steps:**
1. Create a license with expiry date in the past
2. Save the license
3. Check the status

**Expected:**
- Status automatically updates to "Expired"
- Model boot method updates status correctly

**Test Result:** [ ] Pass [ ] Fail

---

### Test 11: Edit License
**Steps:**
1. Click edit icon on a license
2. Update:
   - Change expiry date to future date
   - Update status to "Active"
3. Save changes

**Expected:**
- Form pre-populated correctly
- Updates save successfully
- Status updates automatically if dates change
- Success message displayed

**Test Result:** [ ] Fail

---

### Test 12: Filter Licenses
**Steps:**
1. Create licenses with different statuses and categories
2. Test filters:
   - By status (Active, Expired, Pending Renewal)
   - By category
   - Expiring soon filter
   - Search by license number or name

**Expected:**
- All filters work correctly
- Results update appropriately
- Multiple filters can be combined

**Test Result:** [ ] Pass [ ] Fail

---

### Test 13: Delete License
**Steps:**
1. Create a test license
2. Delete it from the list
3. Confirm deletion

**Expected:**
- Confirmation dialog appears
- License deleted successfully
- Removed from list

**Test Result:** [ ] Pass [ ] Fail

---

## Certificates Management Testing

### Test 14: Navigate to Certificates Page
**Steps:**
1. Navigate to Compliance → Certificates
2. Verify page loads

**Expected:**
- Page displays "Certificates" heading
- "New Certificate" button visible
- Filters visible
- Table with correct columns

**Test Result:** [ ] Pass [ ] Fail

---

### Test 15: Create a New Certificate
**Steps:**
1. Click "New Certificate"
2. Fill in the form:
   - Name: "ISO 9001 Certification"
   - Description: "Quality management certification"
   - Certificate Number: "ISO-2024-001"
   - Category: "Quality"
   - Issuing Authority: "ISO Certification Body"
   - Issue Date: Today
   - Expiry Date: Three years from today
   - Status: "Active"
3. Click "Create Certificate"

**Expected:**
- Form validates correctly
- Certificate created successfully
- Redirects to certificates list
- Success message displayed

**Test Result:** [ ] Pass [ ] Fail

---

### Test 16: Certificate Expiry Alerts
**Steps:**
1. Create a certificate expiring in 20 days
2. View certificates list
3. Verify alert appears

**Expected:**
- Warning icon appears next to expiry date
- Expiring certificate visible in "Expiring Soon" filter

**Test Result:** [ ] Pass [ ] Fail

---

### Test 17: Certificate Status Auto-Update
**Steps:**
1. Create a certificate with expiry date in the past
2. Save it
3. Check status

**Expected:**
- Status automatically updates to "Expired"
- Model boot method works correctly

**Test Result:** [ ] Pass [ ] Fail

---

### Test 18: Edit Certificate
**Steps:**
1. Click edit on a certificate
2. Update details
3. Save changes

**Expected:**
- Form pre-populated correctly
- Updates save successfully
- Status updates based on dates

**Test Result:** [ ] Pass [ ] Fail

---

### Test 19: Filter Certificates
**Steps:**
1. Create multiple certificates
2. Test all filters

**Expected:**
- Status filter works
- Category filter works
- Expiring soon filter works
- Search works

**Test Result:** [ ] Pass [ ] Fail

---

### Test 20: Delete Certificate
**Steps:**
1. Create test certificate
2. Delete it
3. Confirm deletion

**Expected:**
- Deletes successfully
- Removed from list

**Test Result:** [ ] Pass [ ] Fail

---

## Integration Testing

### Test 21: Navigation Links
**Steps:**
1. Verify navigation sidebar has:
   - Compliance → Documents
   - Compliance → Licenses
   - Compliance → Certificates

**Expected:**
- Links work correctly
- Active state highlights correctly
- Routes match backend

**Test Result:** [ ] Pass [ ] Fail

---

### Test 22: Pagination
**Steps:**
1. Create more than 20 documents/licenses/certificates
2. Navigate through pages

**Expected:**
- Pagination appears
- Page navigation works
- Correct count displayed

**Test Result:** [ ] Pass [ ] Fail

---

### Test 23: Form Validation
**Steps:**
1. Try to create document/license/certificate with:
   - Missing required fields
   - Invalid date ranges (expiry before issue)
   - Duplicate license numbers

**Expected:**
- Validation errors displayed
- Form does not submit
- Error messages are clear

**Test Result:** [ ] Pass [ ] Fail

---

### Test 24: Back Navigation
**Steps:**
1. Navigate to create/edit pages
2. Click "Back" links
3. Verify navigation works

**Expected:**
- Back links work correctly
- Returns to list page

**Test Result:** [ ] Pass [ ] Fail

---

## Error Handling Testing

### Test 25: Invalid Document ID
**Steps:**
1. Try to access `/compliance/documents/invalid-id`

**Expected:**
- 404 error or appropriate error handling
- User-friendly error message

**Test Result:** [ ] Pass [ ] Fail

---

### Test 26: Duplicate License Number
**Steps:**
1. Create license with number "TEST-001"
2. Try to create another with same number

**Expected:**
- Validation error displayed
- Duplicate number rejected

**Test Result:** [ ] Pass [ ] Fail

---

### Test 27: Organization Isolation
**Steps:**
1. Create documents/licenses/certificates as one user
2. Log in as different organization user
3. Verify only their own items appear

**Expected:**
- Multi-tenancy working correctly
- No cross-organization data visible

**Test Result:** [ ] Pass [ ] Fail

---

## UI/UX Testing

### Test 28: Responsive Design
**Steps:**
1. Test on mobile viewport
2. Test on tablet viewport
3. Test on desktop

**Expected:**
- Layout adapts correctly
- Forms usable on mobile
- Tables scrollable on small screens

**Test Result:** [ ] Pass [ ] Fail

---

### Test 29: Status Badges
**Steps:**
1. View lists with different statuses
2. Verify badge colors

**Expected:**
- Status badges display correctly
- Colors match status types
- Consistent styling

**Test Result:** [ ] Pass [ ] Fail

---

### Test 30: Loading States
**Steps:**
1. Submit forms
2. Verify loading states

**Expected:**
- Buttons show loading state
- Form disabled during submission
- Progress indicators visible

**Test Result:** [ ] Pass [ ] Fail

---

## Regression Testing

### Test 31: Previous Features Still Work
**Steps:**
1. Test features from previous sprints:
   - Dashboard
   - Money accounts
   - Sales/POS
   - Team management

**Expected:**
- All previous features still functional
- No breaking changes

**Test Result:** [ ] Pass [ ] Fail

---

## Performance Testing

### Test 32: Large Lists
**Steps:**
1. Create 50+ documents/licenses/certificates
2. Load lists and navigate

**Expected:**
- Lists load in reasonable time
- Pagination works correctly
- No performance degradation

**Test Result:** [ ] Pass [ ] Fail

---

## Summary

### Test Results Tracking
- **Total Tests:** 32
- **Passed:** ___ / 32
- **Failed:** ___ / 32
- **Skipped:** ___ / 32

### Critical Issues Found
1. _________________________________
2. _________________________________
3. _________________________________

### Minor Issues Found
1. _________________________________
2. _________________________________
3. _________________________________

### Notes
_________________________________

### Tested By: ________________
### Date: ________________
### Test Environment: ________________

---

## Quick Test Checklist

### Documents
- [ ] Create document
- [ ] View document
- [ ] Edit document
- [ ] Delete document
- [ ] Filter documents
- [ ] Search documents

### Licenses
- [ ] Create license
- [ ] View license
- [ ] Edit license
- [ ] Delete license
- [ ] Expiry alerts work
- [ ] Status auto-updates

### Certificates
- [ ] Create certificate
- [ ] View certificate
- [ ] Edit certificate
- [ ] Delete certificate
- [ ] Expiry alerts work
- [ ] Status auto-updates

### Integration
- [ ] Navigation links work
- [ ] Pagination works
- [ ] Form validation works
- [ ] Multi-tenancy works

---

## Next Steps After Testing

1. **Fix Critical Issues**: Address any blocking bugs
2. **Fix Minor Issues**: Address non-blocking issues
3. **Update Documentation**: Update user guides if needed
4. **Deploy**: Deploy to staging/production
5. **User Acceptance Testing**: Get stakeholder approval

---

**Happy Testing! 🚀**

