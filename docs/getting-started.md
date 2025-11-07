# Getting Started

Complete guide to setting up a new client plugin using the SMPLFY Core Plugin.

---

## Prerequisites

- SMPLFY Core Plugin installed and activated
- Access to Gravity Forms admin
- Basic understanding of PHP and WordPress
- Code editor with PHP support

---

## Step-by-Step Setup

### Step 1: Clone the Boilerplate

```bash
cd /path/to/wp-content/plugins/
git clone https://github.com/sblik/smplfy-boilerplate-plugin.git client-name-plugin
cd client-name-plugin
```

### Step 2: Rename and Configure

**Rename plugin folder and main file:**
```bash
mv smplfy-boilerplate-plugin.php client-name-plugin.php
```

**Update plugin header** in `client-name-plugin.php`:
```php
/**
 * Plugin Name: Client Name Custom Plugin
 * Description: Custom business automation for Client Name
 * Version: 1.0.0
 * Author: Your Team Name
 */
```

**Update namespace** throughout all files:
- Find: `SMPLFY\boilerplate`
- Replace: `SMPLFY\ClientName`

**Files to update:**
- All PHP files in `/public/php/`
- Main plugin file

### Step 3: Identify Forms

1. Go to **WordPress Admin → Forms**
2. Note the forms you'll be working with
3. Hover over each form to see its ID in the URL

Example:
- Contact Form → ID: 5
- Application Form → ID: 12
- Order Form → ID: 18

### Step 4: Update FormIds.php

Edit `/public/php/types/FormIds.php`:

```php
<?php
namespace SMPLFY\ClientName;

class FormIds {
    const CONTACT_FORM_ID = 5;
    const APPLICATION_FORM_ID = 12;
    const ORDER_FORM_ID = 18;
}
```

### Step 5: Find Field IDs

For each form:

1. Open form in **Form Editor**
2. Click on each field
3. Note the **Field ID** in the settings panel
4. For name fields, note sub-field IDs (1.3, 1.6, etc.)

Create a mapping document:

| Form | Field Label | Field ID | Property Name |
|------|-------------|----------|---------------|
| Contact (5) | First Name | 1.3 | nameFirst |
| Contact (5) | Last Name | 1.6 | nameLast |
| Contact (5) | Email | 2 | email |
| Contact (5) | Phone | 3 | phone |
| Contact (5) | Company | 4 | company |

See [Finding Form & Field IDs](finding-ids.md) for detailed instructions.

### Step 6: Create Entities

For each form, create an entity class.

**Example: Contact Form Entity**

Create `/public/php/entities/ContactFormEntity.php`:

```php
<?php
namespace SMPLFY\ClientName;

use SmplfyCore\SMPLFY_BaseEntity;

/**
 * @property string $nameFirst
 * @property string $nameLast
 * @property string $email
 * @property string $phone
 * @property string $company
 */
class ContactFormEntity extends SMPLFY_BaseEntity {
    
    public function __construct($formEntry = array()) {
        parent::__construct($formEntry);
        $this->formId = FormIds::CONTACT_FORM_ID;
    }
    
    protected function get_property_map(): array {
        return [
            'nameFirst' => '1.3',
            'nameLast' => '1.6',
            'email' => '2',
            'phone' => '3',
            'company' => '4',
        ];
    }
}
```

Repeat for each form.

### Step 7: Create Repositories

For each entity, create a repository class.

**Example: Contact Form Repository**

Create `/public/php/repositories/ContactFormRepository.php`:

```php
<?php
namespace SMPLFY\ClientName;

use SmplfyCore\SMPLFY_BaseRepository;
use SmplfyCore\SMPLFY_GravityFormsApiWrapper;
use WP_Error;

/**
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

Repeat for each form.

### Step 8: Create Use Cases

Create use cases for your business logic.

**Example: Contact Form Submission Use Case**

Create `/public/php/usecases/ContactFormSubmissionUsecase.php`:

```php
<?php
namespace SMPLFY\ClientName;

use SmplfyCore\SMPLFY_Log;

class ContactFormSubmissionUsecase {
    
    private ContactFormRepository $contactRepository;
    
    public function __construct(ContactFormRepository $contactRepository) {
        $this->contactRepository = $contactRepository;
    }
    
    public function handle_submission($entry) {
        $entity = new ContactFormEntity($entry);
        
        // Log submission
        SMPLFY_Log::info("Contact form submitted", [
            'email' => $entity->email,
            'name' => $entity->nameFirst . ' ' . $entity->nameLast
        ]);
        
        // Add your business logic here
        // - Send to CRM
        // - Send email notification
        // - Update other records
        // - etc.
    }
}
```

### Step 9: Create Adapter

Create an adapter to hook your use cases into Gravity Forms.

**Example: Gravity Forms Adapter**

Create `/public/php/adapters/GravityFormsAdapter.php`:

```php
<?php
namespace SMPLFY\ClientName;

class GravityFormsAdapter {
    
    private ContactFormSubmissionUsecase $contactSubmissionUsecase;
    
    public function __construct(ContactFormSubmissionUsecase $contactSubmissionUsecase) {
        $this->contactSubmissionUsecase = $contactSubmissionUsecase;
    }
    
    public function register_hooks() {
        add_action(
            'gform_after_submission_' . FormIds::CONTACT_FORM_ID,
            [$this->contactSubmissionUsecase, 'handle_submission'],
            10,
            2
        );
    }
}
```

### Step 10: Wire Everything Together

In your main plugin file, instantiate and wire up dependencies:

```php
<?php
// After the plugin header comment

// Security check
if (!defined('ABSPATH')) {
    exit;
}

// Load utilities
require_once plugin_dir_path(__FILE__) . '../smplfy-core-plugin/includes/utilities.php';

// Load all classes
require_utilities(__DIR__ . '/public/php/types');
require_utilities(__DIR__ . '/public/php/entities');
require_utilities(__DIR__ . '/public/php/repositories');
require_utilities(__DIR__ . '/public/php/usecases');
require_utilities(__DIR__ . '/public/php/adapters');

// Initialize plugin
add_action('plugins_loaded', function() {
    $gravityFormsApi = new SmplfyCore\SMPLFY_GravityFormsApiWrapper();
    
    // Repositories
    $contactRepository = new SMPLFY\ClientName\ContactFormRepository($gravityFormsApi);
    
    // Use Cases
    $contactSubmissionUsecase = new SMPLFY\ClientName\ContactFormSubmissionUsecase(
        $contactRepository
    );
    
    // Adapters
    $gfAdapter = new SMPLFY\ClientName\GravityFormsAdapter(
        $contactSubmissionUsecase
    );
    
    // Register hooks
    $gfAdapter->register_hooks();
}, 20);
```

### Step 11: Test

1. **Submit a test form** in WordPress
2. **Check Datadog logs** (if enabled)
3. **Verify entry was created** in Gravity Forms
4. **Check that use case logic executed** correctly

---

## Quick Testing

### Test Entity Creation

Add this to your use case temporarily:

```php
public function handle_submission($entry) {
    $entity = new ContactFormEntity($entry);
    
    // Debug output
    error_log("Email: " . $entity->email);
    error_log("Name: " . $entity->nameFirst . " " . $entity->nameLast);
    error_log("Full entry: " . print_r($entity->to_array(), true));
}
```

### Test Repository

Use WP-CLI to test repository methods:

```bash
# Get all entries
wp eval "print_r((new SMPLFY\ClientName\ContactFormRepository(new SmplfyCore\SMPLFY_GravityFormsApiWrapper()))->get_all());"

# Get specific entry
wp eval "\$repo = new SMPLFY\ClientName\ContactFormRepository(new SmplfyCore\SMPLFY_GravityFormsApiWrapper()); \$entity = \$repo->get_one('email', 'test@example.com'); print_r(\$entity->to_array());"
```

---

## Common Setup Issues

### Namespace Errors

**Error**: `Class 'SMPLFY\boilerplate\FormIds' not found`

**Solution**: You missed updating a namespace. Search all files for `SMPLFY\boilerplate` and replace with `SMPLFY\ClientName`.

### Property Returns Null

**Error**: `$entity->email` returns `null` but field has data

**Solution**: 
1. Check field ID is correct in GF admin
2. Ensure field ID is a string: `'2'` not `2`
3. For name fields, use sub-field ID: `'1.3'`

### Hook Not Firing

**Error**: Use case doesn't execute on form submission

**Solution**:
1. Verify form ID is correct in `FormIds.php`
2. Check adapter's `register_hooks()` is called
3. Verify hook name: `gform_after_submission_5` (with form ID)
4. Check plugin is activated

### Class Not Found

**Error**: `Class 'SMPLFY\ClientName\ContactFormEntity' not found`

**Solution**:
1. Verify `require_utilities()` is loading the entity directory
2. Check file name matches class name: `ContactFormEntity.php`
3. Verify namespace in file matches directory structure

---

## Next Steps

Once your plugin is working:

1. **Add more forms** - Create entities/repositories for remaining forms
2. **Add business logic** - Implement use cases for form submissions
3. **Integrate Gravity Flow** - Add workflow step transitions
4. **Add external integrations** - Connect to CRMs, email services, etc.
5. **Enable Datadog** - Configure logging for production monitoring

---

## Checklist

Use this checklist for each new client site:

- [ ] Cloned boilerplate plugin
- [ ] Renamed plugin folder and main file
- [ ] Updated plugin header
- [ ] Updated all namespaces
- [ ] Created FormIds constants
- [ ] Documented all field IDs
- [ ] Created entity for each form
- [ ] Created repository for each form
- [ ] Created use cases for business logic
- [ ] Created adapters for hook registration
- [ ] Wired dependencies in main file
- [ ] Tested form submission
- [ ] Verified logging works
- [ ] Deployed to staging
- [ ] Tested on staging
- [ ] Deployed to production

---

## See Also

- [Entities](entities.md) - Creating and using entities
- [Repositories](repositories.md) - Working with repositories
- [Use Cases](use-cases.md) - Organizing business logic
- [Finding IDs](finding-ids.md) - How to find form and field IDs
- [Debugging](debugging.md) - Troubleshooting guide