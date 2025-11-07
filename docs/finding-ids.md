# Finding Form & Field IDs

Step-by-step guide to finding form IDs, field IDs, and workflow step IDs in Gravity Forms.

---

## Finding Form IDs

### Method 1: Forms List (Easiest)

1. Go to **WordPress Admin → Forms**
2. Hover your mouse over any form name
3. Look at the bottom left of your browser (or the URL preview)
4. You'll see: `...admin.php?page=gf_edit_forms&id=5`
5. The number after `id=` is your form ID

**Example:**
- URL shows: `id=5` → Form ID is **5**
- URL shows: `id=12` → Form ID is **12**

### Method 2: Edit Form

1. Go to **Forms** and click on a form to edit it
2. Look at the URL in your browser address bar
3. Find the `id=` parameter
4. Example: `admin.php?page=gf_edit_forms&id=5` → Form ID is **5**

### Method 3: Using WP-CLI

```bash
wp gf form list
```

Output:
```
+----+------------------+
| ID | Title            |
+----+------------------+
| 5  | Contact Form     |
| 12 | Application Form |
| 18 | Order Form       |
+----+------------------+
```

---

## Finding Field IDs

### Simple Fields (Text, Email, Phone, etc.)

1. Go to **Forms** and click to edit your form
2. Click on any field to select it
3. Look at the **Field Settings** panel on the right
4. At the very top, you'll see **"Field ID: X"**
5. That number is your field ID

**Example:**
- Field Settings shows: **Field ID: 2** → Use `'2'` in your property map

### Name Fields (First Name, Last Name)

Name fields have **sub-fields** with decimal notation.

1. Click on the Name field
2. In Field Settings, note the main Field ID (e.g., **1**)
3. Sub-field IDs are:
   - **Prefix**: `1.2` (if enabled)
   - **First**: `1.3`
   - **Middle**: `1.4` (if enabled)
   - **Last**: `1.6`
   - **Suffix**: `1.8` (if enabled)

**Standard Name Field Mapping:**
```php
protected function get_property_map(): array {
    return [
        'nameFirst' => '1.3',
        'nameLast' => '1.6',
    ];
}
```

**Full Name Field with All Parts:**
```php
protected function get_property_map(): array {
    return [
        'namePrefix' => '1.2',
        'nameFirst' => '1.3',
        'nameMiddle' => '1.4',
        'nameLast' => '1.6',
        'nameSuffix' => '1.8',
    ];
}
```

### Address Fields

Address fields also use sub-field notation.

1. Click on the Address field
2. Note the main Field ID (e.g., **3**)
3. Sub-field IDs are:
   - **Street Address**: `3.1`
   - **Address Line 2**: `3.2`
   - **City**: `3.3`
   - **State/Province**: `3.4`
   - **ZIP/Postal**: `3.5`
   - **Country**: `3.6`

**Address Field Mapping:**
```php
protected function get_property_map(): array {
    return [
        'street' => '3.1',
        'streetLine2' => '3.2',
        'city' => '3.3',
        'state' => '3.4',
        'zip' => '3.5',
        'country' => '3.6',
    ];
}
```

### Other Complex Fields

**Email Field**: Use main ID (e.g., `'2'`)
**Phone Field**: Use main ID (e.g., `'3'`)
**Date Field**: Use main ID (e.g., `'7'`)
**Time Field**: Use main ID (e.g., `'8'`)
**File Upload Field**: Use main ID (e.g., `'9'`)
**List Field**: Use main ID - returns array of rows

**Checkbox Field** (Multiple values):
```php
// Individual checkboxes
'checkbox1' => '10.1',
'checkbox2' => '10.2',
'checkbox3' => '10.3',

// Or get all as array using main ID
'checkboxes' => '10',
```

**Multi-select Dropdown**:
```php
// Returns array of selected values
'categories' => '11',
```

---

## Quick Reference: Common Sub-field IDs

| Field Type | Sub-field | ID Pattern | Example |
|------------|-----------|------------|---------|
| Name | Prefix | X.2 | 1.2 |
| Name | First | X.3 | 1.3 |
| Name | Middle | X.4 | 1.4 |
| Name | Last | X.6 | 1.6 |
| Name | Suffix | X.8 | 1.8 |
| Address | Street | X.1 | 3.1 |
| Address | Line 2 | X.2 | 3.2 |
| Address | City | X.3 | 3.3 |
| Address | State | X.4 | 3.4 |
| Address | ZIP | X.5 | 3.5 |
| Address | Country | X.6 | 3.6 |
| Checkbox | Choice 1 | X.1 | 10.1 |
| Checkbox | Choice 2 | X.2 | 10.2 |

**Where X is the main field ID.**

---

## Testing Field IDs

### Method 1: Check an Existing Entry

1. Go to **Forms → Entries**
2. Click on any entry
3. View the entry details
4. Match field labels to their values
5. Hover over field values - some show field IDs

### Method 2: Use WP-CLI

```bash
# Get form structure
wp gf form get 5

# Get an entry
wp gf entry get 123
```

The entry output shows field IDs as keys:
```
{
    "1.3": "John",
    "1.6": "Doe",
    "2": "john@example.com",
    "3": "555-0123"
}
```

### Method 3: Debug in Code

```php
// In your use case
public function handle_submission($entry) {
    error_log("Full entry: " . print_r($entry, true));
    
    // You'll see output like:
    // Array (
    //     [1.3] => John
    //     [1.6] => Doe
    //     [2] => john@example.com
    // )
}
```

---

## Finding Workflow Step IDs

If using Gravity Flow:

### Method 1: Workflow Settings

1. Go to **Forms → [Your Form] → Workflow**
2. Click on any workflow step
3. Look at the URL in your address bar
4. Find `step_id=` parameter
5. Example: `step_id=10` → Step ID is **10**

### Method 2: Step List

1. Go to **Forms → [Your Form] → Workflow**
2. Each step shows its configuration
3. Click "Edit" on a step
4. The step ID appears in the URL

### Method 3: Create Constants

Document step IDs in a constants file:

Create `/public/php/types/WorkflowStepIds.php`:

```php
<?php
namespace SMPLFY\ClientName;

class WorkflowStepIds {
    // Application Form Workflow
    const PENDING_REVIEW = '10';
    const MANAGER_APPROVAL = '15';
    const APPROVED = '20';
    const REJECTED = '25';
    const PROCESSING = '30';
    const COMPLETE = '35';
    
    // Order Form Workflow
    const PAYMENT_PENDING = '40';
    const PAYMENT_COMPLETE = '45';
    const FULFILLMENT = '50';
    const SHIPPED = '55';
}
```

Then use in code:
```php
WorkflowStep::send(WorkflowStepIds::APPROVED, $entity->formEntry);
```

---

## Creating a Mapping Document

For each client site, create a mapping document for reference.

### Example: forms-mapping.md

```markdown
# Form & Field Mappings - Client Name

## Forms

| Form Name | Form ID | Entity | Repository |
|-----------|---------|--------|------------|
| Contact Form | 5 | ContactFormEntity | ContactFormRepository |
| Application Form | 12 | ApplicationFormEntity | ApplicationFormRepository |
| Order Form | 18 | OrderFormEntity | OrderFormRepository |

## Contact Form (ID: 5)

| Field Label | Field ID | Property Name | Type | Notes |
|-------------|----------|---------------|------|-------|
| First Name | 1.3 | nameFirst | text | Name field sub-field |
| Last Name | 1.6 | nameLast | text | Name field sub-field |
| Email | 2 | email | email | |
| Phone | 3 | phone | phone | |
| Company | 4 | company | text | |
| Message | 5 | message | textarea | |

## Application Form (ID: 12)

| Field Label | Field ID | Property Name | Type | Notes |
|-------------|----------|---------------|------|-------|
| First Name | 1.3 | nameFirst | text | |
| Last Name | 1.6 | nameLast | text | |
| Email | 2 | email | email | |
| Street Address | 3.1 | street | text | Address sub-field |
| City | 3.3 | city | text | Address sub-field |
| State | 3.4 | state | dropdown | Address sub-field |
| ZIP | 3.5 | zip | text | Address sub-field |
| Resume | 4 | resume | fileupload | |

## Workflow Steps - Application Form

| Step Name | Step ID | Constant |
|-----------|---------|----------|
| Pending Review | 10 | WorkflowStepIds::PENDING_REVIEW |
| Manager Approval | 15 | WorkflowStepIds::MANAGER_APPROVAL |
| Approved | 20 | WorkflowStepIds::APPROVED |
| Rejected | 25 | WorkflowStepIds::REJECTED |
```

---

## Common Mistakes

### Using Numbers Instead of Strings

❌ **Wrong:**
```php
'email' => 2,  // Numeric
```

✅ **Correct:**
```php
'email' => '2',  // String
```

### Using Parent Field ID for Sub-fields

❌ **Wrong:**
```php
'nameFirst' => '1',  // Parent Name field ID
```

✅ **Correct:**
```php
'nameFirst' => '1.3',  // Sub-field ID
```

### Forgetting Quotes in Constants

❌ **Wrong:**
```php
const CONTACT_FORM_ID = '5';  // String
```

✅ **Correct:**
```php
const CONTACT_FORM_ID = 5;  // Integer (constants don't need quotes)
```

But in property map:
```php
'email' => '2',  // String (field IDs need quotes)
```