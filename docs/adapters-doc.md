# Adapters

Adapters connect your use cases to WordPress, Gravity Forms, and Gravity Flow hooks. They serve as the bridge between the WordPress ecosystem and your business logic.

---

## Overview

**Purpose**: Adapters register WordPress/GF hooks and delegate to use cases when those hooks fire.

**Why adapters?**
- Separates hook registration from business logic
- Makes use cases testable without WordPress
- Centralizes all hook configuration
- Clear separation of concerns

---

## Adapter Types

### Gravity Forms Adapter

Handles Gravity Forms hooks.

```php
<?php
namespace SMPLFY\ClientName;

class GravityFormsAdapter {
    
    private ContactFormSubmissionUsecase $contactSubmissionUsecase;
    private ApplicationSubmissionUsecase $applicationSubmissionUsecase;
    
    public function __construct(
        ContactFormSubmissionUsecase $contactSubmissionUsecase,
        ApplicationSubmissionUsecase $applicationSubmissionUsecase
    ) {
        $this->contactSubmissionUsecase = $contactSubmissionUsecase;
        $this->applicationSubmissionUsecase = $applicationSubmissionUsecase;
    }
    
    public function register_hooks() {
        // Form submissions
        add_action(
            'gform_after_submission_' . FormIds::CONTACT_FORM_ID,
            [$this->contactSubmissionUsecase, 'handle_submission'],
            10,
            2
        );
        
        add_action(
            'gform_after_submission_' . FormIds::APPLICATION_FORM_ID,
            [$this->applicationSubmissionUsecase, 'handle_submission'],
            10,
            2
        );
        
        // Form validation
        add_filter(
            'gform_validation_' . FormIds::CONTACT_FORM_ID,
            [$this, 'validate_contact_form']
        );
        
        // Entry update
        add_action(
            'gform_post_update_entry',
            [$this, 'handle_entry_update'],
            10,
            2
        );
    }
    
    public function validate_contact_form($validation_result) {
        $form = $validation_result['form'];
        
        // Custom validation logic
        foreach ($form['fields'] as &$field) {
            if ($field->id == '2') { // Email field
                $email = rgpost('input_2');
                
                // Example: Check if email domain is blacklisted
                if ($this->is_blacklisted_domain($email)) {
                    $validation_result['is_valid'] = false;
                    $field->failed_validation = true;
                    $field->validation_message = 'This email domain is not allowed.';
                }
            }
        }
        
        return $validation_result;
    }
    
    public function handle_entry_update($entry, $original_entry) {
        // Determine which form was updated
        $form_id = $entry['form_id'];
        
        if ($form_id == FormIds::CONTACT_FORM_ID) {
            // Handle contact form update
        }
    }
    
    private function is_blacklisted_domain($email) {
        $domain = substr(strrchr($email, "@"), 1);
        $blacklist = ['spam.com', 'fake.com'];
        return in_array($domain, $blacklist);
    }
}
```

### Gravity Flow Adapter

Handles Gravity Flow workflow hooks.

```php
<?php
namespace SMPLFY\ClientName;

class GravityFlowAdapter {
    
    private ApplicationApprovalUsecase $approvalUsecase;
    private OrderProcessingUsecase $orderProcessingUsecase;
    
    public function __construct(
        ApplicationApprovalUsecase $approvalUsecase,
        OrderProcessingUsecase $orderProcessingUsecase
    ) {
        $this->approvalUsecase = $approvalUsecase;
        $this->orderProcessingUsecase = $orderProcessingUsecase;
    }
    
    public function register_hooks() {
        // Step completion
        add_action(
            'gravityflow_step_complete',
            [$this, 'handle_step_complete'],
            10,
            4
        );
        
        // Specific step types
        add_action(
            'gravityflow_approval_status_approved',
            [$this->approvalUsecase, 'handle_approval'],
            10,
            4
        );
        
        add_action(
            'gravityflow_approval_status_rejected',
            [$this->approvalUsecase, 'handle_rejection'],
            10,
            4
        );
    }
    
    public function handle_step_complete($entry_id, $step, $form_id, $status) {
        $entry = GFAPI::get_entry($entry_id);
        
        // Route to appropriate use case based on form
        if ($form_id == FormIds::APPLICATION_FORM_ID) {
            if ($step->get_type() === 'approval') {
                // Handled by specific approval hooks above
            }
        } elseif ($form_id == FormIds::ORDER_FORM_ID) {
            if ($step->get_id() === WorkflowStepIds::PAYMENT_STEP) {
                $this->orderProcessingUsecase->handle_payment_complete($entry);
            }
        }
    }
}
```

### WordPress Adapter

Handles WordPress core hooks.

```php
<?php
namespace SMPLFY\ClientName;

class WordPressAdapter {
    
    private UserRegistrationUsecase $userRegistrationUsecase;
    private ContactFormHeartbeat $heartbeatHandler;
    private DailyReportUsecase $dailyReportUsecase;
    
    public function __construct(
        UserRegistrationUsecase $userRegistrationUsecase,
        ContactFormHeartbeat $heartbeatHandler,
        DailyReportUsecase $dailyReportUsecase
    ) {
        $this->userRegistrationUsecase = $userRegistrationUsecase;
        $this->heartbeatHandler = $heartbeatHandler;
        $this->dailyReportUsecase = $dailyReportUsecase;
    }
    
    public function register_hooks() {
        // User registration
        add_action(
            'user_register',
            [$this->userRegistrationUsecase, 'handle_registration'],
            10,
            1
        );
        
        // Heartbeat API
        add_filter(
            'heartbeat_received',
            [$this->heartbeatHandler, 'receive_heartbeat'],
            10,
            2
        );
        
        // Scheduled tasks
        add_action(
            'client_name_daily_report',
            [$this->dailyReportUsecase, 'generate_daily_report']
        );
        
        // Custom REST API endpoints
        add_action('rest_api_init', [$this, 'register_rest_routes']);
    }
    
    public function register_rest_routes() {
        register_rest_route('client-name/v1', '/webhook', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_webhook'],
            'permission_callback' => [$this, 'verify_webhook_permission']
        ]);
    }
    
    public function handle_webhook(\WP_REST_Request $request) {
        // Webhook handling logic
        return new \WP_REST_Response(['success' => true], 200);
    }
    
    public function verify_webhook_permission(\WP_REST_Request $request) {
        $api_key = $request->get_header('X-API-Key');
        return $api_key === get_option('webhook_api_key');
    }
}
```

---

## Registering Adapters

In your main plugin file, instantiate adapters and register their hooks:

```php
<?php
// Main plugin file

if (!defined('ABSPATH')) {
    exit;
}

// Load classes
require_utilities(__DIR__ . '/public/php');

// Initialize on plugins_loaded
add_action('plugins_loaded', function() {
    
    // Core dependencies
    $gravityFormsApi = new SmplfyCore\SMPLFY_GravityFormsApiWrapper();
    
    // Repositories
    $contactRepo = new SMPLFY\ClientName\ContactFormRepository($gravityFormsApi);
    $applicationRepo = new SMPLFY\ClientName\ApplicationRepository($gravityFormsApi);
    $customerRepo = new SMPLFY\ClientName\CustomerRepository($gravityFormsApi);
    
    // Use cases
    $contactSubmissionUsecase = new SMPLFY\ClientName\ContactFormSubmissionUsecase(
        $contactRepo,
        $customerRepo
    );
    
    $applicationSubmissionUsecase = new SMPLFY\ClientName\ApplicationSubmissionUsecase(
        $applicationRepo
    );
    
    $approvalUsecase = new SMPLFY\ClientName\ApplicationApprovalUsecase(
        $applicationRepo
    );
    
    // Adapters
    $gfAdapter = new SMPLFY\ClientName\GravityFormsAdapter(
        $contactSubmissionUsecase,
        $applicationSubmissionUsecase
    );
    
    $gflowAdapter = new SMPLFY\ClientName\GravityFlowAdapter(
        $approvalUsecase
    );
    
    $wpAdapter = new SMPLFY\ClientName\WordPressAdapter(
        $userRegistrationUsecase,
        $heartbeatHandler,
        $dailyReportUsecase
    );
    
    // Register all hooks
    $gfAdapter->register_hooks();
    $gflowAdapter->register_hooks();
    $wpAdapter->register_hooks();
    
}, 20); // Priority 20 ensures GF is loaded
```

---

## Common Gravity Forms Hooks

### Form Submission

```php
// After form submission
add_action('gform_after_submission_' . $form_id, $callback, 10, 2);

// Before form submission
add_action('gform_pre_submission_' . $form_id, $callback, 10, 2);

// Confirmation page
add_filter('gform_confirmation_' . $form_id, $callback, 10, 4);
```

### Form Validation

```php
// Validate entire form
add_filter('gform_validation_' . $form_id, $callback);

// Validate specific field
add_filter('gform_field_validation_' . $form_id . '_' . $field_id, $callback, 10, 4);
```

### Entry Management

```php
// After entry created
add_action('gform_post_add_entry', $callback, 10, 2);

// After entry updated
add_action('gform_post_update_entry', $callback, 10, 2);

// Before entry deleted
add_action('gform_delete_entry', $callback, 10, 2);
```

### Field Rendering

```php
// Before field rendered
add_filter('gform_field_content_' . $form_id . '_' . $field_id, $callback, 10, 5);

// Field value
add_filter('gform_field_value_' . $parameter_name, $callback);
```

---

## Common Gravity Flow Hooks

### Workflow Events

```php
// Step starts
add_action('gravityflow_step_start', $callback, 10, 3);

// Step completes
add_action('gravityflow_step_complete', $callback, 10, 4);

// Workflow complete
add_action('gravityflow_workflow_complete', $callback, 10, 3);
```

### Approval Events

```php
// Approved
add_action('gravityflow_approval_status_approved', $callback, 10, 4);

// Rejected
add_action('gravityflow_approval_status_rejected', $callback, 10, 4);
```

---

## Common WordPress Hooks

### User Events

```php
// User registered
add_action('user_register', $callback, 10, 1);

// User login
add_action('wp_login', $callback, 10, 2);

// Profile updated
add_action('profile_update', $callback, 10, 2);
```

### Post Events

```php
// Post published
add_action('publish_post', $callback, 10, 2);

// Post updated
add_action('post_updated', $callback, 10, 3);
```

### Scheduled Events

```php
// Register cron event
if (!wp_next_scheduled('my_daily_event')) {
    wp_schedule_event(time(), 'daily', 'my_daily_event');
}

// Hook into cron event
add_action('my_daily_event', $callback);
```

---

## Best Practices

### One Adapter Per System

✅ **Good - Separate adapters:**
```
GravityFormsAdapter.php
GravityFlowAdapter.php
WordPressAdapter.php
```

❌ **Bad - God adapter:**
```
HooksAdapter.php (everything in one file)
```

### Thin Adapters

Adapters should only register hooks and delegate to use cases.

✅ **Good:**
```php
public function register_hooks() {
    add_action(
        'gform_after_submission_' . FormIds::CONTACT_FORM_ID,
        [$this->contactSubmissionUsecase, 'handle_submission'],
        10,
        2
    );
}
```

❌ **Bad:**
```php
public function register_hooks() {
    add_action('gform_after_submission_' . FormIds::CONTACT_FORM_ID, function($entry, $form) {
        // Don't put business logic in adapters!
        $entity = new ContactFormEntity($entry);
        $customer = $this->repo->get_one('email', $entity->email);
        // ... 100 lines of business logic
    });
}
```

### Use Constants for Form IDs

✅ **Good:**
```php
add_action(
    'gform_after_submission_' . FormIds::CONTACT_FORM_ID,
    $callback
);
```

❌ **Bad:**
```php
add_action('gform_after_submission_5', $callback); // Magic number
```

### Group Related Hooks

```php
public function register_hooks() {
    // Contact form hooks
    add_action('gform_after_submission_' . FormIds::CONTACT_FORM_ID, ...);
    add_filter('gform_validation_' . FormIds::CONTACT_FORM_ID, ...);
    
    // Application form hooks
    add_action('gform_after_submission_' . FormIds::APPLICATION_FORM_ID, ...);
    add_filter('gform_validation_' . FormIds::APPLICATION_FORM_ID, ...);
    
    // Entry management hooks
    add_action('gform_post_update_entry', ...);
    add_action('gform_delete_entry', ...);
}
```

---

## Advanced Patterns

### Conditional Hook Registration

Only register hooks when needed:

```php
public function register_hooks() {
    // Only register if Gravity Flow is active
    if (class_exists('Gravity_Flow')) {
        add_action('gravityflow_step_complete', [$this, 'handle_step_complete'], 10, 4);
    }
    
    // Only register if specific form exists
    if (GFAPI::get_form(FormIds::APPLICATION_FORM_ID)) {
        add_action('gform_after_submission_' . FormIds::APPLICATION_FORM_ID, ...);
    }
}
```

### Dynamic Hook Registration

Register hooks based on configuration:

```php
public function register_hooks() {
    $forms_config = [
        FormIds::CONTACT_FORM_ID => $this->contactSubmissionUsecase,
        FormIds::APPLICATION_FORM_ID => $this->applicationSubmissionUsecase,
        FormIds::ORDER_FORM_ID => $this->orderSubmissionUsecase,
    ];
    
    foreach ($forms_config as $form_id => $usecase) {
        add_action(
            'gform_after_submission_' . $form_id,
            [$usecase, 'handle_submission'],
            10,
            2
        );
    }
}
```

### Routing Pattern

Route to different use cases based on form/step:

```php
public function handle_step_complete($entry_id, $step, $form_id, $status) {
    $entry = GFAPI::get_entry($entry_id);
    $step_id = $step->get_id();
    
    // Route based on form and step
    $route_key = $form_id . '_' . $step_id;
    
    $routes = [
        FormIds::APPLICATION_FORM_ID . '_' . WorkflowStepIds::APPROVAL => 
            [$this->approvalUsecase, 'handle_approval'],
            
        FormIds::APPLICATION_FORM_ID . '_' . WorkflowStepIds::REVIEW => 
            [$this->reviewUsecase, 'handle_review'],
            
        FormIds::ORDER_FORM_ID . '_' . WorkflowStepIds::PAYMENT => 
            [$this->paymentUsecase, 'handle_payment_complete'],
    ];
    
    if (isset($routes[$route_key])) {
        call_user_func($routes[$route_key], $entry, $step_id);
    }
}
```

---

## Testing Adapters

### Manual Testing

Trigger hooks manually for testing:

```php
// In a temporary test file or WP-CLI
$entry = GFAPI::get_entry(123);
$form = GFAPI::get_form(FormIds::CONTACT_FORM_ID);

// Trigger the hook
do_action('gform_after_submission_' . FormIds::CONTACT_FORM_ID, $entry, $form);
```

### Using WP-CLI

```bash
# List all registered hooks for an action
wp action list gform_after_submission_5

# Trigger a cron event
wp cron event run client_name_daily_report

# Test webhook endpoint
curl -X POST https://yoursite.com/wp-json/client-name/v1/webhook \
  -H "X-API-Key: your-key" \
  -H "Content-Type: application/json" \
  -d '{"test": "data"}'
```

### Debug Hook Registration

Add temporary logging to verify hooks are registered:

```php
public function register_hooks() {
    error_log("Registering Gravity Forms hooks");
    
    add_action(
        'gform_after_submission_' . FormIds::CONTACT_FORM_ID,
        [$this->contactSubmissionUsecase, 'handle_submission'],
        10,
        2
    );
    
    error_log("Registered: gform_after_submission_" . FormIds::CONTACT_FORM_ID);
}
```

---

## Troubleshooting

### Hooks Not Firing

**Problem**: Adapter registered but use case never executes

**Solutions**:
1. Verify adapter's `register_hooks()` is called in main plugin file
2. Check hook name is correct (including form ID)
3. Verify form ID constant matches actual form
4. Ensure plugin loads after Gravity Forms (use priority 20+)
5. Check for PHP errors in error log

**Debug:**
```php
// Add to adapter
public function register_hooks() {
    $hook_name = 'gform_after_submission_' . FormIds::CONTACT_FORM_ID;
    error_log("Registering hook: " . $hook_name);
    
    add_action($hook_name, function($entry, $form) {
        error_log("Hook fired! Entry ID: " . $entry['id']);
    }, 10, 2);
}
```

### Wrong Form Triggering

**Problem**: Hook fires for wrong form

**Solution**: Check form ID in hook name
```php
// Make sure form ID is correct
add_action('gform_after_submission_' . FormIds::CONTACT_FORM_ID, ...);

// Can also check inside callback
public function handle_submission($entry, $form) {
    if ($form['id'] != FormIds::CONTACT_FORM_ID) {
        return; // Wrong form
    }
    // Process
}
```

### Priority Issues

**Problem**: Hook fires but dependencies not ready

**Solution**: Adjust hook priority
```php
// In main plugin file
add_action('plugins_loaded', function() {
    // ... register adapters
}, 20); // Higher priority = later execution
```

### Callback Not Found

**Problem**: "Call to undefined method" error

**Solutions**:
1. Verify method exists in use case
2. Check method is public
3. Verify use case is instantiated
4. Check for typos in method name

---

## Complete Example

Here's a full adapter implementation:

```php
<?php
namespace SMPLFY\ClientName;

use SmplfyCore\SMPLFY_Log;

class GravityFormsAdapter {
    
    private ContactFormSubmissionUsecase $contactSubmissionUsecase;
    private ApplicationSubmissionUsecase $applicationSubmissionUsecase;
    private OrderSubmissionUsecase $orderSubmissionUsecase;
    
    public function __construct(
        ContactFormSubmissionUsecase $contactSubmissionUsecase,
        ApplicationSubmissionUsecase $applicationSubmissionUsecase,
        OrderSubmissionUsecase $orderSubmissionUsecase
    ) {
        $this->contactSubmissionUsecase = $contactSubmissionUsecase;
        $this->applicationSubmissionUsecase = $applicationSubmissionUsecase;
        $this->orderSubmissionUsecase = $orderSubmissionUsecase;
    }
    
    /**
     * Register all Gravity Forms hooks
     */
    public function register_hooks() {
        // Form submissions
        $this->register_submission_hooks();
        
        // Form validation
        $this->register_validation_hooks();
        
        // Entry management
        $this->register_entry_hooks();
        
        // Confirmations
        $this->register_confirmation_hooks();
    }
    
    private function register_submission_hooks() {
        add_action(
            'gform_after_submission_' . FormIds::CONTACT_FORM_ID,
            [$this->contactSubmissionUsecase, 'handle_submission'],
            10,
            2
        );
        
        add_action(
            'gform_after_submission_' . FormIds::APPLICATION_FORM_ID,
            [$this->applicationSubmissionUsecase, 'handle_submission'],
            10,
            2
        );
        
        add_action(
            'gform_after_submission_' . FormIds::ORDER_FORM_ID,
            [$this->orderSubmissionUsecase, 'handle_submission'],
            10,
            2
        );
    }
    
    private function register_validation_hooks() {
        add_filter(
            'gform_validation_' . FormIds::CONTACT_FORM_ID,
            [$this, 'validate_contact_form']
        );
        
        add_filter(
            'gform_validation_' . FormIds::APPLICATION_FORM_ID,
            [$this, 'validate_application_form']
        );
    }
    
    private function register_entry_hooks() {
        add_action(
            'gform_post_update_entry',
            [$this, 'handle_entry_update'],
            10,
            2
        );
        
        add_action(
            'gform_delete_entry',
            [$this, 'handle_entry_delete'],
            10,
            2
        );
    }
    
    private function register_confirmation_hooks() {
        add_filter(
            'gform_confirmation_' . FormIds::ORDER_FORM_ID,
            [$this, 'customize_order_confirmation'],
            10,
            4
        );
    }
    
    public function validate_contact_form($validation_result) {
        $form = $validation_result['form'];
        
        foreach ($form['fields'] as &$field) {
            // Email field validation
            if ($field->id == '2') {
                $email = rgpost('input_2');
                
                if ($this->is_blacklisted_email($email)) {
                    $validation_result['is_valid'] = false;
                    $field->failed_validation = true;
                    $field->validation_message = 'This email address is not allowed.';
                    
                    SMPLFY_Log::warning('Blacklisted email attempted', [
                        'email' => $email
                    ]);
                }
            }
            
            // Phone field validation
            if ($field->id == '3') {
                $phone = rgpost('input_3');
                
                if (!$this->is_valid_phone($phone)) {
                    $validation_result['is_valid'] = false;
                    $field->failed_validation = true;
                    $field->validation_message = 'Please enter a valid phone number.';
                }
            }
        }
        
        return $validation_result;
    }
    
    public function validate_application_form($validation_result) {
        // Application-specific validation
        return $validation_result;
    }
    
    public function handle_entry_update($entry, $original_entry) {
        $form_id = $entry['form_id'];
        
        SMPLFY_Log::info('Entry updated', [
            'entry_id' => $entry['id'],
            'form_id' => $form_id
        ]);
        
        // Route to appropriate handler
        if ($form_id == FormIds::ORDER_FORM_ID) {
            $this->orderSubmissionUsecase->handle_entry_update($entry, $original_entry);
        }
    }
    
    public function handle_entry_delete($entry_id, $entry) {
        SMPLFY_Log::info('Entry deleted', [
            'entry_id' => $entry_id,
            'form_id' => $entry['form_id']
        ]);
    }
    
    public function customize_order_confirmation($confirmation, $form, $entry, $ajax) {
        // Customize confirmation message
        $order = new OrderEntity($entry);
        
        $confirmation = sprintf(
            '<div class="gform_confirmation_message">
                <h3>Thank you for your order!</h3>
                <p>Order Number: <strong>%s</strong></p>
                <p>Total: <strong>$%s</strong></p>
                <p>A confirmation email has been sent to %s</p>
            </div>',
            $order->orderNumber,
            number_format($order->total, 2),
            $order->customerEmail
        );
        
        return $confirmation;
    }
    
    private function is_blacklisted_email($email) {
        $blacklist = get_option('email_blacklist', []);
        $domain = substr(strrchr($email, "@"), 1);
        
        return in_array($email, $blacklist) || 
               in_array($domain, $blacklist);
    }
    
    private function is_valid_phone($phone) {
        // Remove non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Check if 10 or 11 digits (US/Canada)
        return strlen($phone) >= 10 && strlen($phone) <= 11;
    }
}
```

---

## Best Practices Summary

✅ **Do:**
- Create separate adapters for different systems (GF, GFlow, WP)
- Keep adapters thin - only hook registration and delegation
- Use dependency injection for use cases
- Use constants for form/field IDs
- Group related hooks together
- Log when hooks fire (in development)
- Verify hooks are registered in main plugin file

❌ **Don't:**
- Put business logic in adapters
- Create one giant adapter for everything
- Use magic numbers for form IDs
- Instantiate dependencies inside adapters
- Register hooks in multiple places
- Forget to call `register_hooks()`

---

## See Also

- [Use Cases](use-cases.md) - Business logic called by adapters
- [Getting Started](getting-started.md) - Wiring adapters in main plugin file
- [Gravity Flow](gravity-flow.md) - Gravity Flow hooks reference
- [Best Practices](best-practices.md) - Code organization patterns