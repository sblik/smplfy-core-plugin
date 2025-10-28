# Best Practices

Code standards, naming conventions, and architectural patterns for SMPLFY Core Plugin development.

---

## Naming Conventions

### Classes

**Use PascalCase with descriptive names**:

✅ **Good**:
```php
ContactFormEntity
ContactFormRepository
ContactFormSubmissionUsecase
GravityFormsAdapter
```

❌ **Bad**:
```php
contactForm          // Wrong case
Contact              // Too vague
FormEntity           // Too generic
Processor            // Not descriptive
```

**Include purpose in name**:
- Entities: `{FormName}Entity`
- Repositories: `{FormName}Repository`
- Use Cases: `{Action}Usecase`
- Adapters: `{System}Adapter`

### Properties

**Use camelCase**:

✅ **Good**:
```php
$nameFirst
$emailAddress
$phoneNumber
$billingAddress
$submissionDate
```

❌ **Bad**:
```php
$name_first          // Snake case
$EmailAddress        // Pascal case
$phonenumber         // No separation
$email               // Too vague (if ambiguous)
```

**Be descriptive but concise**:
```php
✅ $approvalDate
❌ $dateWhenApplicationWasApproved

✅ $customerEmail
❌ $email  // If multiple email types exist

✅ $totalAmount
❌ $tot    // Too abbreviated
```

### Constants

**Use SCREAMING_SNAKE_CASE**:

✅ **Good**:
```php
class FormIds {
    const CONTACT_FORM_ID = 5;
    const APPLICATION_FORM_ID = 12;
    const ORDER_FORM_ID = 18;
}

class WorkflowStepIds {
    const PENDING_REVIEW = '10';
    const APPROVED = '15';
    const REJECTED = '20';
}
```

❌ **Bad**:
```php
const contactFormId = 5;      // Wrong case
const FORM_5 = 5;             // Not descriptive
const form_id_contact = 5;    // Wrong format
```

### Methods

**Use verb phrases in camelCase**:

✅ **Good**:
```php
handle_submission()
process_payment()
send_notification()
get_customer_email()
is_valid()
```

❌ **Bad**:
```php
submission()         // Not a verb
HandleSubmission()   // Wrong case
processPaymentAndSendEmailAndUpdateDatabase()  // Too long
doStuff()           // Not descriptive
```

### Namespaces

**Use client name**:

```php
namespace SMPLFY\ClientName;
namespace SMPLFY\ClientName\Usecases;
namespace SMPLFY\ClientName\Adapters;
```

---

## File Organization

### Directory Structure

```
client-plugin/
├── public/
│   └── php/
│       ├── types/
│       │   ├── FormIds.php
│       │   ├── FieldIds.php
│       │   └── WorkflowStepIds.php
│       ├── entities/
│       │   ├── ContactFormEntity.php
│       │   ├── ApplicationFormEntity.php
│       │   └── CustomerEntity.php
│       ├── repositories/
│       │   ├── ContactFormRepository.php
│       │   ├── ApplicationFormRepository.php
│       │   └── CustomerRepository.php
│       ├── usecases/
│       │   ├── ContactFormSubmissionUsecase.php
│       │   ├── ApplicationApprovalUsecase.php
│       │   └── OrderProcessingUsecase.php
│       ├── adapters/
│       │   ├── GravityFormsAdapter.php
│       │   ├── GravityFlowAdapter.php
│       │   └── WordPressAdapter.php
│       └── services/
│           ├── CrmService.php
│           └── EmailService.php
```

### File Names

**Match class names exactly**:
```
ContactFormEntity.php     → class ContactFormEntity
ContactFormRepository.php → class ContactFormRepository
```

---

## Code Structure

### Entity Design

**Keep entities simple - data containers only**:

✅ **Good**:
```php
class ContactFormEntity extends SMPLFY_BaseEntity {
    protected function get_property_map(): array {
        return [
            'nameFirst' => '1.3',
            'nameLast' => '1.6',
            'email' => '2'
        ];
    }
    
    // Simple helper methods are OK
    public function get_full_name() {
        return trim($this->nameFirst . ' ' . $this->nameLast);
    }
}
```

❌ **Bad**:
```php
class ContactFormEntity extends SMPLFY_BaseEntity {
    // Don't do this in entities!
    public function save_to_database() {
        GFAPI::update_entry($this->formEntry);
    }
    
    public function send_to_crm() {
        wp_remote_post(...);
    }
    
    public function get_related_orders() {
        return GFAPI::get_entries(...);
    }
}
```

### Repository Design

**Repositories handle CRUD only**:

✅ **Good**:
```php
class ContactFormRepository extends SMPLFY_BaseRepository {
    // CRUD methods from base
    // Custom query methods are OK
    public function get_recent_contacts($days = 7) {
        $all = $this->get_all();
        $cutoff = date('Y-m-d', strtotime("-{$days} days"));
        
        return array_filter($all, function($entity) use ($cutoff) {
            return $entity->submissionDate >= $cutoff;
        });
    }
}
```

❌ **Bad**:
```php
class ContactFormRepository extends SMPLFY_BaseRepository {
    // Don't put business logic in repositories!
    public function process_contact_and_send_emails($entity) {
        $this->update($entity);
        $this->send_welcome_email($entity);
        $this->notify_sales_team($entity);
        $this->update_crm($entity);
    }
}
```

### Use Case Design

**One use case = one action**:

✅ **Good**:
```php
class ContactFormSubmissionUsecase {
    public function handle_submission($entry) {
        // Focused on form submission only
    }
}

class CrmSyncUsecase {
    public function sync_to_crm($entity) {
        // Focused on CRM sync only
    }
}
```

❌ **Bad**:
```php
class FormProcessingUsecase {
    public function handle_contact_form($entry) { }
    public function handle_application_form($entry) { }
    public function sync_to_crm($entry) { }
    public function send_emails($entry) { }
    public function process_payments($entry) { }
    // Too many responsibilities!
}
```

### Adapter Design

**Adapters only register hooks and delegate**:

✅ **Good**:
```php
class GravityFormsAdapter {
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

❌ **Bad**:
```php
class GravityFormsAdapter {
    public function register_hooks() {
        add_action('gform_after_submission_5', function($entry) {
            // 50 lines of business logic here!
            $entity = new ContactFormEntity($entry);
            // ... more logic ...
        });
    }
}
```

---

## Dependency Injection

**Always inject dependencies via constructor**:

✅ **Good**:
```php
class ContactFormSubmissionUsecase {
    private ContactFormRepository $contactRepository;
    private CrmService $crmService;
    
    public function __construct(
        ContactFormRepository $contactRepository,
        CrmService $crmService
    ) {
        $this->contactRepository = $contactRepository;
        $this->crmService = $crmService;
    }
}
```

❌ **Bad**:
```php
class ContactFormSubmissionUsecase {
    public function handle_submission($entry) {
        // Don't instantiate inside methods!
        $repo = new ContactFormRepository(...);
        $service = new CrmService();
    }
}
```

---

## Error Handling

### Always Check WP_Error

```php
✅ // Good
$entry_id = $repository->add($entity);
if (is_wp_error($entry_id)) {
    SMPLFY_Log::error('Failed to create entry', [
        'error' => $entry_id->get_error_message()
    ]);
    return false;
}

❌ // Bad
$entry_id = $repository->add($entity);
// Assuming success - dangerous!
```

### Use Try-Catch for External APIs

```php
✅ // Good
try {
    $response = $this->crmService->create_contact($entity);
    SMPLFY_Log::info('Contact created in CRM');
} catch (\Exception $e) {
    SMPLFY_Log::error('CRM sync failed', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    // Don't throw - log and continue
}

❌ // Bad
// No error handling - will crash on failure
$response = $this->crmService->create_contact($entity);
```

### Don't Throw in Use Cases

```php
✅ // Good - Log and handle gracefully
public function handle_submission($entry) {
    try {
        $this->process($entry);
    } catch (\Exception $e) {
        SMPLFY_Log::error('Processing failed', [
            'error' => $e->getMessage()
        ]);
        // Continue execution
    }
}

❌ // Bad - Throws will break form submission
public function handle_submission($entry) {
    if (!$entity->email) {
        throw new \Exception('Email required'); // Don't throw!
    }
}
```

---

## Logging

### What to Log

✅ **Do log**:
- Important business events
- Errors and exceptions
- External API calls
- Workflow transitions
- Critical operations

```php
SMPLFY_Log::info('Order created', [
    'order_id' => $entity->get_entry_id(),
    'total' => $entity->total,
    'customer' => $entity->customerEmail
]);

SMPLFY_Log::error('Payment failed', [
    'order_id' => $entity->get_entry_id(),
    'error' => $e->getMessage(),
    'payment_method' => $entity->paymentMethod
]);
```

❌ **Don't log**:
- Sensitive data (passwords, API keys, credit cards)
- Excessive debug data in production
- Every line of code execution

```php
// Bad - Sensitive data
SMPLFY_Log::info('User login', [
    'password' => $password,  // Never log passwords!
    'api_key' => $api_key     // Never log API keys!
]);

// Bad - Too verbose
SMPLFY_Log::debug('Variable x = ' . $x);
SMPLFY_Log::debug('Entering function');
SMPLFY_Log::debug('Exiting function');
```

### Log with Context

```php
✅ // Good - Includes context
SMPLFY_Log::error('Failed to update customer', [
    'customer_id' => $customer->get_entry_id(),
    'email' => $customer->email,
    'error' => $e->getMessage(),
    'attempted_action' => 'update_contact_date'
]);

❌ // Bad - No context
SMPLFY_Log::error('Update failed');
```

---

## Comments

### When to Comment

✅ **Do comment**:
- Complex business logic
- Why decisions were made
- Workarounds for known issues
- Important assumptions

```php
// Calculate prorated amount based on signup date
// Using 30-day month for consistency with accounting system
$prorated = ($monthly_rate / 30) * $remaining_days;

// WORKAROUND: GF doesn't support negative values in number fields
// so we store as positive and track sign in separate field
$amount = abs($entity->transactionAmount);
$sign = $entity->transactionAmount < 0 ? 'credit' : 'debit';
```

❌ **Don't comment**:
- Obvious code
- Commented-out code (delete it)
- What the code does (code should be self-documenting)

```php
// Bad - Obvious
// Get the customer email
$email = $entity->email;

// Bad - Commented code
// $old_method($entity);
// return $old_value;

// Bad - Describes what (should be obvious from code)
// Loop through all entities
foreach ($entities as $entity) {
```

### PHPDoc

**Always use for classes and public methods**:

```php
/**
 * Contact Form Entity
 * 
 * Represents a submission from the contact form.
 * 
 * @property string $nameFirst
 * @property string $nameLast  
 * @property string $email
 */
class ContactFormEntity extends SMPLFY_BaseEntity {
    
    /**
     * Get customer's full name
     * 
     * @return string Full name (first + last)
     */
    public function get_full_name() {
        return trim($this->nameFirst . ' ' . $this->nameLast);
    }
}
```

---

## Testing

### Manual Testing Checklist

Before deploying:

- [ ] Test form submission
- [ ] Verify entry created in GF
- [ ] Check Datadog logs
- [ ] Verify workflow transitions (if applicable)
- [ ] Test with invalid data
- [ ] Test error scenarios
- [ ] Check email notifications sent
- [ ] Verify external API calls (CRM, etc.)

### Test in Stages

1. **Local development** - Test all functionality
2. **Staging environment** - Test with real data
3. **Production** - Deploy incrementally, monitor logs

---

## Security

### Always Include ABSPATH Check

```php
<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Rest of file...
```

### Sanitize User Input

```php
✅ // Good
$email = sanitize_email($_POST['email']);
$name = sanitize_text_field($_POST['name']);

❌ // Bad
$email = $_POST['email'];  // Unsanitized!
```

### Validate Data

```php
✅ // Good
if (!is_email($email)) {
    return new WP_Error('invalid_email', 'Invalid email address');
}

if (empty($entity->nameFirst)) {
    SMPLFY_Log::warning('Missing required field', ['field' => 'nameFirst']);
}
```

---

## Performance

### Don't Load All Entries Unless Necessary

```php
✅ // Good - Load only what you need
$pending = $repository->get_all('status', 'Pending');

❌ // Bad - Loads everything
$all = $repository->get_all();
$pending = array_filter($all, fn($e) => $e->status === 'Pending');
```

### Process in Batches

```php
✅ // Good - Batch processing
$page = 1;
do {
    $entries = GFAPI::get_entries($form_id, [], null, [
        'page_size' => 50,
        'offset' => ($page - 1) * 50
    ]);
    
    foreach ($entries as $entry) {
        $this->process(new Entity($entry));
    }
    
    $page++;
} while (count($entries) > 0);

❌ // Bad - Load all at once
$all = $repository->get_all();  // Could be thousands!
foreach ($all as $entity) {
    $this->process($entity);
}
```

---

## Documentation

### Keep a Mapping Document

For each client site, maintain:

**forms-mapping.md**:
```markdown
# Form Mappings

| Form Name | Form ID | Entity | Repository |
|-----------|---------|--------|------------|
| Contact Form | 5 | ContactFormEntity | ContactFormRepository |
| Application | 12 | ApplicationEntity | ApplicationRepository |

## Contact Form (ID: 5)

| Field | ID | Property | Type |
|-------|-----|----------|------|
| First Name | 1.3 | nameFirst | text |
| Last Name | 1.6 | nameLast | text |
| Email | 2 | email | email |
```

### Document Business Logic

Add README to complex use cases:

```php
/**
 * Order Processing Use Case
 * 
 * Handles the complete order workflow:
 * 1. Validate order data
 * 2. Check inventory availability
 * 3. Process payment via Stripe
 * 4. Reserve inventory
 * 5. Create/update customer record
 * 6. Generate shipping label
 * 7. Send confirmation email
 * 8. Move to fulfillment workflow step
 * 
 * Dependencies:
 * - OrderRepository
 * - CustomerRepository
 * - InventoryService (checks stock levels)
 * - PaymentService (Stripe integration)
 * - ShippingService (ShipStation API)
 * - NotificationService (email/SMS)
 */
class OrderProcessingUsecase {
```

---

## See Also

- [Getting Started](getting-started.md) - Setup guide
- [Entities](entities.md) - Entity patterns
- [Repositories](repositories.md) - Repository patterns
- [Use Cases](use-cases.md) - Use case patterns
- [Debugging](debugging.md) - Troubleshooting guide