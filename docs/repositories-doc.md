# Repositories

Repositories handle all CRUD (Create, Read, Update, Delete) operations for Gravity Forms entries, providing a clean interface to work with entities.

---

## Overview

A repository acts as a data access layer between your business logic and Gravity Forms. Each repository is responsible for one specific form and returns properly typed entities.

**Benefits:**
- Centralized data access logic
- Type-safe entity returns
- Eliminates repetitive GFAPI calls
- Easier to test and maintain

---

## Creating a Repository

### Basic Repository Structure

```php
<?php
namespace SMPLFY\ClientName;

use SmplfyCore\SMPLFY_BaseRepository;
use SmplfyCore\SMPLFY_GravityFormsApiWrapper;
use WP_Error;

/**
 * Contact Form Repository
 * 
 * @method static ContactFormEntity|null get_one($fieldId, $value)
 * @method static ContactFormEntity|null get_one_for_current_user()
 * @method static ContactFormEntity|null get_one_for_user($userId)
 * @method static ContactFormEntity[] get_all($fieldId = null, $value = null, string $direction = 'ASC')
 * @method static int|WP_Error add(ContactFormEntity $entity)
 */
class ContactFormRepository extends SMPLFY_BaseRepository {
    
    public function __construct(SMPLFY_GravityFormsApiWrapper $gravityFormsApi) {
        $this->entityType = ContactFormEntity::class;
        $this->formId     = FormIds::CONTACT_FORM_ID;
        parent::__construct($gravityFormsApi);
    }
}
```

### Key Components

**PHPDoc @method Declarations**
- Enable IDE autocomplete with proper types
- Document available methods
- Specify return types for your specific entity

**Constructor Requirements**
- Accept `SMPLFY_GravityFormsApiWrapper` dependency
- Set `$this->entityType` to your entity class
- Set `$this->formId` to the form ID constant
- Call parent constructor

---

## Core Methods

### Retrieving Entries

#### get_one()

Get a single entry by field value.

```php
// By field ID
$entity = $repository->get_one(2, 'john@example.com');

// By property name (recommended)
$entity = $repository->get_one('email', 'john@example.com');

// Returns null if not found
if ($entity === null) {
    // Entry not found
}
```

#### get_one_for_current_user()

Get entry created by the currently logged-in user.

```php
$entity = $repository->get_one_for_current_user();

if ($entity === null) {
    // Current user has no entry
}
```

**Use case**: User dashboard showing their own form submission.

#### get_one_for_user()

Get entry created by a specific user ID.

```php
$user_id = 123;
$entity = $repository->get_one_for_user($user_id);

if ($entity === null) {
    // User has no entry
}
```

**Use case**: Admin viewing a specific user's submission.

#### get_all()

Get all entries, optionally filtered and sorted.

```php
// Get all entries
$all_entities = $repository->get_all();

// Get entries filtered by field value
$filtered = $repository->get_all('email', 'john@example.com');
$filtered = $repository->get_all(2, 'john@example.com'); // By field ID

// Get all entries sorted descending (newest first)
$newest_first = $repository->get_all(null, null, 'DESC');

// Get filtered entries sorted ascending
$filtered_sorted = $repository->get_all('status', 'Pending', 'ASC');
```

**Returns**: Array of entities (empty array if none found).

---

### Creating Entries

#### add()

Create a new entry in Gravity Forms.

```php
// Create new entity
$entity = new ContactFormEntity();
$entity->nameFirst = 'John';
$entity->nameLast = 'Doe';
$entity->email = 'john@example.com';
$entity->phone = '555-0123';

// Save to Gravity Forms
$entry_id = $repository->add($entity);

// Check for errors
if (is_wp_error($entry_id)) {
    SMPLFY_Log::error('Failed to create entry', [
        'error' => $entry_id->get_error_message()
    ]);
    return false;
} else {
    SMPLFY_Log::info('Entry created', ['entry_id' => $entry_id]);
    return $entry_id;
}
```

**Returns**: `int` entry ID on success, `WP_Error` on failure.

---

### Updating Entries

#### update()

Update an existing entry.

```php
// Load existing entry
$entity = $repository->get_one('email', 'john@example.com');

if ($entity) {
    // Modify properties
    $entity->phone = '555-9999';
    $entity->company = 'New Company';
    
    // Save changes
    $result = $repository->update($entity);
    
    if (is_wp_error($result)) {
        SMPLFY_Log::error('Failed to update entry', [
            'error' => $result->get_error_message(),
            'entry_id' => $entity->get_entry_id()
        ]);
    } else {
        SMPLFY_Log::info('Entry updated', [
            'entry_id' => $entity->get_entry_id()
        ]);
    }
}
```

**Returns**: `true` on success, `WP_Error` on failure.

---

### Deleting Entries

#### delete()

Delete an entry from Gravity Forms.

```php
$entity = $repository->get_one('email', 'john@example.com');

if ($entity) {
    $result = $repository->delete($entity);
    
    if (is_wp_error($result)) {
        SMPLFY_Log::error('Failed to delete entry', [
            'error' => $result->get_error_message(),
            'entry_id' => $entity->get_entry_id()
        ]);
    } else {
        SMPLFY_Log::info('Entry deleted', [
            'entry_id' => $entity->get_entry_id()
        ]);
    }
}
```

**Returns**: `true` on success, `WP_Error` on failure.

**⚠️ Warning**: This permanently deletes the entry. Consider using entry status or a "deleted" flag instead if you need soft deletes.

---

## Common Patterns

### Check if Entry Exists

```php
$entity = $repository->get_one('email', $email);

if ($entity !== null) {
    // Entry exists - update it
    $entity->lastContactDate = current_time('mysql');
    $repository->update($entity);
} else {
    // Entry doesn't exist - create it
    $newEntity = new ContactFormEntity();
    $newEntity->email = $email;
    $newEntity->nameFirst = $firstName;
    $repository->add($newEntity);
}
```

### Get or Create Pattern

```php
public function get_or_create_customer($email, $firstName, $lastName) {
    $entity = $this->customerRepository->get_one('email', $email);
    
    if ($entity === null) {
        // Create new customer
        $entity = new CustomerEntity();
        $entity->email = $email;
        $entity->firstName = $firstName;
        $entity->lastName = $lastName;
        $entity->createdDate = current_time('mysql');
        
        $entry_id = $this->customerRepository->add($entity);
        
        if (is_wp_error($entry_id)) {
            SMPLFY_Log::error('Failed to create customer', [
                'error' => $entry_id->get_error_message()
            ]);
            return null;
        }
        
        // Reload to get full entity with ID
        $entity = $this->customerRepository->get_one('email', $email);
    }
    
    return $entity;
}
```

### Bulk Operations

```php
public function update_all_pending_to_processed() {
    $pending = $this->repository->get_all('status', 'Pending');
    
    foreach ($pending as $entity) {
        $entity->status = 'Processed';
        $entity->processedDate = current_time('mysql');
        
        $result = $this->repository->update($entity);
        
        if (is_wp_error($result)) {
            SMPLFY_Log::error('Failed to update entity', [
                'entry_id' => $entity->get_entry_id(),
                'error' => $result->get_error_message()
            ]);
        }
    }
}
```

### Finding Related Entries

```php
public function get_user_orders($user_id) {
    // Get customer entity
    $customer = $this->customerRepository->get_one_for_user($user_id);
    
    if ($customer === null) {
        return [];
    }
    
    // Get all orders for this customer's email
    $orders = $this->orderRepository->get_all('customerEmail', $customer->email);
    
    return $orders;
}
```

---

## Custom Repository Methods

You can add custom methods to your repositories for complex queries.

### Example: Find by Date Range

```php
class OrderRepository extends SMPLFY_BaseRepository {
    
    public function __construct(SMPLFY_GravityFormsApiWrapper $gravityFormsApi) {
        $this->entityType = OrderEntity::class;
        $this->formId     = FormIds::ORDER_FORM_ID;
        parent::__construct($gravityFormsApi);
    }
    
    /**
     * Get orders within a date range
     * 
     * @param string $start_date Y-m-d format
     * @param string $end_date Y-m-d format
     * @return OrderEntity[]
     */
    public function get_orders_by_date_range($start_date, $end_date) {
        $all_orders = $this->get_all();
        
        return array_filter($all_orders, function($order) use ($start_date, $end_date) {
            $order_date = date('Y-m-d', strtotime($order->orderDate));
            return $order_date >= $start_date && $order_date <= $end_date;
        });
    }
}
```

### Example: Find by Multiple Criteria

```php
public function get_pending_high_priority_orders() {
    $all_orders = $this->get_all();
    
    return array_filter($all_orders, function($order) {
        return $order->status === 'Pending' && $order->priority === 'High';
    });
}
```

### Example: Get Count

```php
public function get_pending_count() {
    $pending = $this->get_all('status', 'Pending');
    return count($pending);
}
```

---

## Dependency Injection

Repositories should be injected into use cases, not instantiated inside them.

### ✅ Good: Dependency Injection

```php
class ContactFormSubmissionUsecase {
    
    private ContactFormRepository $contactRepository;
    private CustomerRepository $customerRepository;
    
    public function __construct(
        ContactFormRepository $contactRepository,
        CustomerRepository $customerRepository
    ) {
        $this->contactRepository = $contactRepository;
        $this->customerRepository = $customerRepository;
    }
    
    public function handle_submission($entry) {
        $entity = new ContactFormEntity($entry);
        
        $customer = $this->customerRepository->get_one('email', $entity->email);
        // ... business logic
    }
}
```

### ❌ Bad: Creating Inside Use Case

```php
class ContactFormSubmissionUsecase {
    
    public function handle_submission($entry) {
        // Don't do this!
        $repository = new ContactFormRepository(
            new SMPLFY_GravityFormsApiWrapper()
        );
        
        $entity = new ContactFormEntity($entry);
        // ... business logic
    }
}
```

---

## Error Handling

Always check for `WP_Error` returns.

```php
// Creating
$entry_id = $repository->add($entity);
if (is_wp_error($entry_id)) {
    // Handle error
    SMPLFY_Log::error('Create failed', [
        'error' => $entry_id->get_error_message(),
        'entity' => $entity->to_array()
    ]);
    return false;
}

// Updating
$result = $repository->update($entity);
if (is_wp_error($result)) {
    // Handle error
    SMPLFY_Log::error('Update failed', [
        'error' => $result->get_error_message(),
        'entry_id' => $entity->get_entry_id()
    ]);
    return false;
}

// Deleting
$result = $repository->delete($entity);
if (is_wp_error($result)) {
    // Handle error
    SMPLFY_Log::error('Delete failed', [
        'error' => $result->get_error_message(),
        'entry_id' => $entity->get_entry_id()
    ]);
    return false;
}
```

---

## Testing Repositories

### Using WP-CLI

```bash
# Get all entries
wp eval "
\$repo = new SMPLFY\ClientName\ContactFormRepository(
    new SmplfyCore\SMPLFY_GravityFormsApiWrapper()
);
print_r(\$repo->get_all());
"

# Get specific entry
wp eval "
\$repo = new SMPLFY\ClientName\ContactFormRepository(
    new SmplfyCore\SMPLFY_GravityFormsApiWrapper()
);
\$entity = \$repo->get_one('email', 'test@example.com');
if (\$entity) {
    print_r(\$entity->to_array());
} else {
    echo 'Not found';
}
"

# Create test entry
wp eval "
\$repo = new SMPLFY\ClientName\ContactFormRepository(
    new SmplfyCore\SMPLFY_GravityFormsApiWrapper()
);
\$entity = new SMPLFY\ClientName\ContactFormEntity();
\$entity->nameFirst = 'Test';
\$entity->nameLast = 'User';
\$entity->email = 'test@example.com';
\$entry_id = \$repo->add(\$entity);
echo 'Created entry ID: ' . \$entry_id;
"
```

### Manual Testing

Add temporary debug code to your use cases:

```php
public function handle_submission($entry) {
    // Test repository
    $all = $this->repository->get_all();
    error_log("Total entries: " . count($all));
    
    $entity = $this->repository->get_one('email', 'test@example.com');
    error_log("Found entity: " . ($entity ? 'Yes' : 'No'));
    
    if ($entity) {
        error_log("Entity data: " . print_r($entity->to_array(), true));
    }
}
```

---

## Troubleshooting

### Repository Returns Null When Entry Exists

**Problem**: `get_one()` returns `null` but entry exists in GF admin

**Solutions**:
1. Verify field ID/name is correct
2. Check field value matches exactly (case-sensitive)
3. Ensure form ID is correct in repository constructor
4. Test with numeric field ID instead of property name

```php
// Try both approaches
$entity = $repository->get_one('email', 'john@example.com'); // Property name
$entity = $repository->get_one(2, 'john@example.com');       // Field ID
```

### Update Not Saving

**Problem**: `update()` returns success but changes don't persist

**Solutions**:
1. Verify you're calling `$repository->update($entity)` after modifying
2. Check property names are correct in entity
3. Ensure field IDs are mapped correctly
4. Check GF admin for validation errors
5. Verify WordPress user has permission to edit entries

### Get All Returns Empty Array

**Problem**: `get_all()` returns `[]` but entries exist

**Solutions**:
1. Verify form ID is correct in repository
2. Check entries aren't in trash
3. Verify entries are in the correct form
4. Test with GFAPI directly:

```php
$entries = GFAPI::get_entries(FormIds::CONTACT_FORM_ID);
error_log("Direct GFAPI result: " . print_r($entries, true));
```

---

## Best Practices

✅ **Do:**
- Always check for `WP_Error` after create/update/delete
- Use dependency injection for repositories
- Log errors with full context
- Use PHPDoc `@method` annotations for IDE support
- Add custom methods for complex queries
- Use property names in `get_one()` instead of field IDs

❌ **Don't:**
- Instantiate repositories inside use cases
- Ignore `WP_Error` returns
- Query entries directly with GFAPI in use cases
- Forget to set `entityType` and `formId` in constructor
- Put business logic in repository methods
- Use repositories in entity classes

---

## See Also

- [Entities](entities.md) - Working with entity objects
- [Use Cases](use-cases.md) - Using repositories in business logic
- [API Reference](api-reference.md) - Complete method documentation
- [Debugging](debugging.md) - Troubleshooting repository issues