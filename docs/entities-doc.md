# Entities

Entities are object representations of Gravity Forms entries with strongly-typed, named properties.

---

## Overview

Instead of working with numeric field IDs like `rgar($entry, '2')`, entities let you use readable property names like `$entity->email`. This makes your code self-documenting and easier to maintain.

---

## Creating an Entity

### Step 1: Extend SMPLFY_BaseEntity

```php
<?php
namespace SMPLFY\ClientName;

use SmplfyCore\SMPLFY_BaseEntity;

/**
 * Contact Form Entity
 * 
 * @property string $nameFirst
 * @property string $nameLast
 * @property string $email
 * @property string $phone
 * @property string $company
 * @property string $message
 */
class ContactFormEntity extends SMPLFY_BaseEntity {
    
    public function __construct($formEntry = array()) {
        parent::__construct($formEntry);
        $this->formId = FormIds::CONTACT_FORM_ID;
    }
    
    protected function get_property_map(): array {
        return array(
            'nameFirst' => '1.3',  // Name (First) field
            'nameLast'  => '1.6',  // Name (Last) field
            'email'     => '2',    // Email field
            'phone'     => '3',    // Phone field
            'company'   => '4',    // Single line text field
            'message'   => '5',    // Paragraph text field
        );
    }
}
```

### Key Components

**PHPDoc @property Declarations**
- Enable IDE autocomplete
- Document available properties
- Specify property types

**Constructor**
- Call parent constructor with GF entry array
- Set the form ID using your constants

**get_property_map() Method**
- Maps property names to GF field IDs
- Field IDs must be strings (e.g., `'2'` not `2`)
- Use sub-field notation for complex fields (e.g., `'1.3'` for First Name)

---

## Property Mapping

### Simple Fields

```php
protected function get_property_map(): array {
    return [
        'email' => '2',           // Email field
        'phone' => '3',           // Phone field
        'company' => '4',         // Single line text
        'message' => '5',         // Paragraph text
        'website' => '6',         // Website field
        'date' => '7',            // Date field
        'dropdown' => '8',        // Dropdown field
        'checkbox' => '9',        // Checkbox field
    ];
}
```

### Name Fields (Sub-fields)

```php
protected function get_property_map(): array {
    return [
        'nameFirst' => '1.3',     // Name - First
        'nameLast' => '1.6',      // Name - Last
        'nameMiddle' => '1.4',    // Name - Middle (if enabled)
        'namePrefix' => '1.2',    // Name - Prefix (if enabled)
        'nameSuffix' => '1.8',    // Name - Suffix (if enabled)
    ];
}
```

### Address Fields (Sub-fields)

```php
protected function get_property_map(): array {
    return [
        'street' => '3.1',        // Street Address
        'streetLine2' => '3.2',   // Address Line 2
        'city' => '3.3',          // City
        'state' => '3.4',         // State/Province
        'zip' => '3.5',           // ZIP/Postal Code
        'country' => '3.6',       // Country
    ];
}
```

---

## Using Entities

### Accessing Properties

```php
// Get property values (uses magic __get)
$email = $entity->email;
$firstName = $entity->nameFirst;
$city = $entity->city;
```

### Setting Properties

```php
// Set property values (uses magic __set)
$entity->email = 'newemail@example.com';
$entity->phone = '555-9999';
$entity->company = 'Acme Corp';
```

### Getting Gravity Forms Data

```php
// Get the GF entry ID
$entry_id = $entity->get_entry_id();

// Get the full GF entry array
$gf_entry = $entity->get_entry();

// Access formEntry directly (needed for WorkflowStep)
$entity->formEntry; // Raw GF entry array
```

### Converting to Array

```php
// Convert entity to array
$data = $entity->to_array();
```

---

## Helper Methods

You can add simple helper methods to your entities:

```php
class ContactFormEntity extends SMPLFY_BaseEntity {
    // ... property map ...
    
    /**
     * Get full name
     */
    public function get_full_name() {
        return trim($this->nameFirst . ' ' . $this->nameLast);
    }
    
    /**
     * Check if customer has phone number
     */
    public function has_phone() {
        return !empty($this->phone);
    }
    
    /**
     * Get formatted address
     */
    public function get_formatted_address() {
        return sprintf(
            "%s\n%s, %s %s",
            $this->street,
            $this->city,
            $this->state,
            $this->zip
        );
    }
}
```

**Important**: Keep helper methods simple. Don't add:
- Database queries (belongs in repositories)
- External API calls (belongs in use cases)
- Complex business logic (belongs in use cases)

---

## Property Naming Conventions

**Use camelCase:**
- ✅ `nameFirst`, `emailAddress`, `phoneNumber`
- ❌ `name_first`, `EmailAddress`, `phone_number`

**Be descriptive:**
- ✅ `customerEmail`, `billingAddress`, `submissionDate`
- ❌ `email`, `address`, `date` (too vague if ambiguous)

**Keep concise:**
- ✅ `approvalDate`
- ❌ `dateWhenApplicationWasApproved`

---

## Common Patterns

### Creating from GF Entry

```php
// In a use case after form submission
public function handle_submission($entry) {
    $entity = new ContactFormEntity($entry);
    
    // Work with entity properties
    SMPLFY_Log::info("Form submitted", [
        'email' => $entity->email,
        'name' => $entity->get_full_name()
    ]);
}
```

### Creating New Entity

```php
// Create empty entity
$entity = new ContactFormEntity();
$entity->nameFirst = 'John';
$entity->nameLast = 'Doe';
$entity->email = 'john@example.com';

// Save via repository
$entry_id = $repository->add($entity);
```

### Updating Existing Entity

```php
// Load from repository
$entity = $repository->get_one('email', 'john@example.com');

// Modify properties
$entity->phone = '555-1234';
$entity->company = 'New Company';

// Save changes
$repository->update($entity);
```

---

## Troubleshooting

### Property Returns Null

**Problem**: `$entity->email` returns `null` even though field has data

**Solutions**:
1. Verify property is mapped in `get_property_map()`
2. Check field ID is a string: `'2'` not `2`
3. For name fields, use sub-field ID: `'1.3'` not `'1'`
4. Verify field ID matches GF admin

### IDE Autocomplete Not Working

**Problem**: IDE doesn't suggest properties when typing `$entity->`

**Solution**: Add `@property` PHPDoc declarations:
```php
/**
 * @property string $email
 * @property string $nameFirst
 */
class ContactFormEntity extends SMPLFY_BaseEntity {
```

### Property Name Collision

**Problem**: Property name conflicts with method or parent class property

**Solution**: Prefix with descriptive context:
- Instead of `$entity->status`, use `$entity->applicationStatus`
- Instead of `$entity->date`, use `$entity->submissionDate`

---

## Best Practices

✅ **Do:**
- Use `@property` PHPDoc for all mapped properties
- Map all fields you'll access in your code
- Use descriptive property names
- Keep entities simple (data containers only)
- Add simple helper methods for formatting/display

❌ **Don't:**
- Add database queries to entities
- Add external API calls to entities
- Put complex business logic in entities
- Use numeric keys in property map
- Forget to set `$this->formId` in constructor

---

## See Also

- [Repositories](repositories.md) - Using entities with repositories
- [Use Cases](use-cases.md) - Business logic with entities
- [Finding IDs](finding-ids.md) - How to find field IDs
- [API Reference](api-reference.md) - Complete entity methods