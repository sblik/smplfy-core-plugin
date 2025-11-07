# Datadog Logging

Complete guide to configuring and using Datadog logging with the SMPLFY Core Plugin.

---

## Overview

The SMPLFY Core Plugin provides centralized logging to Datadog, allowing you to monitor all plugin operations, errors, and events in real-time across all client sites.

**Features:**
- Automatic log forwarding to Datadog
- Three log levels: `error`, `warning`, `info`
- Dual logging: WordPress debug.log AND Datadog
- Automatic PHP error/exception handling
- Automatic plugin name detection
- Site URL as source for filtering

---

## Prerequisites

### 1. Enable WordPress Debugging

**CRITICAL**: Logging requires `WP_DEBUG` to be enabled.

Add to `wp-config.php`:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);  // Don't show errors to visitors
@ini_set('display_errors', 0);
```

**Why this matters:**
- `SMPLFY_Log` checks `WP_DEBUG` before logging
- Even in production, keep `WP_DEBUG` and `WP_DEBUG_LOG` enabled
- Keep `WP_DEBUG_DISPLAY` false to hide errors from users
- Logs go to both `/wp-content/debug.log` AND Datadog

### 2. Get Datadog Credentials

1. Log in to your Datadog account
2. Go to **Organization Settings → API Keys**
3. Copy your API key
4. Note your Datadog intake URL:
   - **US1**: `https://http-intake.logs.datadoghq.com/v1/input`
   - **EU**: `https://http-intake.logs.datadoghq.eu/v1/input`
   - **US3**: `https://http-intake.logs.us3.datadoghq.com/v1/input`
   - **US5**: `https://http-intake.logs.us5.datadoghq.com/v1/input`
   - Check [Datadog docs](https://docs.datadoghq.com/logs/log_collection/) for other regions

---

## Configuration

### Enable Datadog in WordPress

1. Go to **WordPress Admin → SMPLFY Settings**
2. Check **"Send logs to Datadog"**
3. Enter your **Datadog API URL** (from above)
4. Enter your **Datadog API Key**
5. Click **Save**

**Verification:**
After saving, trigger a test log:

```php
SMPLFY_Log::info("Test log from " . site_url());
```

Check Datadog within 1-2 minutes to see if the log appears.

---

## Using SMPLFY_Log

### Available Methods

The `SMPLFY_Log` class provides three logging methods:

```php
use SmplfyCore\SMPLFY_Log;

// Error level - for critical errors and exceptions
SMPLFY_Log::error($message, $data = null, $log_to_file = true);

// Warning level - for warnings and potential issues
SMPLFY_Log::warn($message, $data = null, $log_to_file = true);

// Info level - for informational messages and events
SMPLFY_Log::info($message, $data = null, $log_to_file = true);
```

**Note**: There is no `debug()` method. Use `info()` for general logging.

### Parameters

**`$message`** (string|array|object) - Required
- The log message
- Can be a string, array, or object
- Arrays/objects are automatically converted to readable format

**`$data`** (mixed) - Optional
- Additional context data
- Can be array, object, or any value
- Automatically formatted for readability

**`$log_to_file`** (bool) - Optional, default: `true`
- `true`: Log to both debug.log and Datadog
- `false`: Log only to Datadog (skip debug.log)

---

## Usage Examples

### Basic Logging

```php
use SmplfyCore\SMPLFY_Log;

// Simple message
SMPLFY_Log::info("Contact form submitted");

// Message with data
SMPLFY_Log::info("Contact form submitted", [
    'entry_id' => $entry['id'],
    'email' => $entity->email
]);

// Error with exception
try {
    $result = $this->apiService->call();
} catch (\Exception $e) {
    SMPLFY_Log::error("API call failed", [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
```

### Logging in Use Cases

```php
class ContactFormSubmissionUsecase {
    
    public function handle_submission($entry) {
        $entity = new ContactFormEntity($entry);
        
        // Log submission
        SMPLFY_Log::info("Contact form submitted", [
            'entry_id' => $entry['id'],
            'email' => $entity->email,
            'name' => $entity->nameFirst . ' ' . $entity->nameLast
        ]);
        
        // Business logic
        $customer = $this->customerRepository->get_one('email', $entity->email);
        
        if ($customer) {
            SMPLFY_Log::info("Existing customer found", [
                'customer_id' => $customer->get_entry_id()
            ]);
        } else {
            SMPLFY_Log::info("Creating new customer");
            
            $customer_id = $this->customerRepository->add($newCustomer);
            
            if (is_wp_error($customer_id)) {
                SMPLFY_Log::error("Failed to create customer", [
                    'error' => $customer_id->get_error_message(),
                    'email' => $entity->email
                ]);
            }
        }
    }
}
```

### Repository Error Logging

```php
$result = $repository->update($entity);

if (is_wp_error($result)) {
    SMPLFY_Log::error("Failed to update entry", [
        'entry_id' => $entity->get_entry_id(),
        'error' => $result->get_error_message(),
        'form_id' => FormIds::CONTACT_FORM_ID
    ]);
}
```

### External API Integration

```php
try {
    $response = wp_remote_post($crm_url, [
        'body' => json_encode($data),
        'headers' => ['Content-Type' => 'application/json']
    ]);
    
    if (is_wp_error($response)) {
        throw new \Exception($response->get_error_message());
    }
    
    SMPLFY_Log::info("CRM sync successful", [
        'entry_id' => $entity->get_entry_id(),
        'crm_response' => wp_remote_retrieve_body($response)
    ]);
    
} catch (\Exception $e) {
    SMPLFY_Log::error("CRM sync failed", [
        'entry_id' => $entity->get_entry_id(),
        'error' => $e->getMessage(),
        'url' => $crm_url
    ]);
}
```

### Workflow Transitions

```php
SMPLFY_Log::info("Moving to workflow step", [
    'entry_id' => $entity->get_entry_id(),
    'from_step' => $current_step,
    'to_step' => WorkflowStepIds::APPROVED
]);

WorkflowStep::send(WorkflowStepIds::APPROVED, $entity->formEntry);

SMPLFY_Log::info("Workflow step transition complete");
```

---

## Automatic Error Handling

The SMPLFY Core Plugin **automatically** captures and logs all PHP errors and exceptions to Datadog.

### What Gets Logged Automatically

**PHP Errors:**
- Fatal errors → `error` level
- Parse errors → `error` level
- Warnings → `warning` level
- Notices → `info` level
- Strict standards → `info` level

**Uncaught Exceptions:**
- All uncaught exceptions → `error` level
- Includes full stack trace

### Example Automatic Log

When a PHP error occurs:

```
PHP Warning: Undefined array key "email" in /path/to/file.php on line 123
PHP Stack trace:
#0 /path/to/ContactFormEntity.php(45): get_property_map()
#1 /path/to/ContactFormSubmissionUsecase.php(23): __construct()
...
```

This is automatically sent to Datadog without any code on your part.

### Benefits

- **Zero-effort error monitoring** - Errors are logged even if you forget to add logging
- **Full stack traces** - Every error includes complete context
- **Production visibility** - Catch errors in production you'd otherwise miss
- **Debugging aid** - Stack traces show exact error path

---

## Viewing Logs in Datadog

### Filters

Logs are tagged with:

**Source**: Your WordPress site URL
```
source:https://clientsite.com
```

**Service**: Automatically detected plugin name
```
service:client-name-plugin
```

**Level**: Log severity
```
level:error
level:warning
level:info
```

### Common Queries

**All errors for a site:**
```
source:https://clientsite.com level:error
```

**Errors from specific plugin:**
```
service:client-name-plugin level:error
```

**Logs for specific entry:**
```
"entry_id:123"
```

**Logs containing email:**
```
@email:john@example.com
```

**Recent errors (last hour):**
```
level:error @timestamp:>now-1h
```

**CRM sync issues:**
```
"CRM sync failed"
```

### Dashboard Setup

Create a Datadog dashboard with:

1. **Error Rate Panel**
   - Metric: Count of `level:error`
   - Group by: `service`
   - Time: Last 24 hours

2. **Top Errors Panel**
   - Query: `level:error`
   - Group by: Message pattern
   - Show: Top 10

3. **Activity Timeline**
   - All logs from your sites
   - Filter by service/source

4. **Form Submissions Panel**
   - Query: `"form submitted"`
   - Count by form name

---

## Best Practices

### What to Log

✅ **Do log:**
- Form submissions
- Entity create/update/delete operations
- External API calls (success and failure)
- Workflow step transitions
- Business rule decisions
- Errors and exceptions
- Authentication/authorization events

```php
// Good logging
SMPLFY_Log::info("Order created", [
    'order_id' => $entity->get_entry_id(),
    'customer' => $entity->customerEmail,
    'total' => $entity->total
]);
```

❌ **Don't log:**
- Sensitive data (passwords, API keys, credit card numbers)
- Excessive debug information in production
- Personal identifying information (if subject to GDPR/privacy laws)

```php
// Bad - sensitive data
SMPLFY_Log::info("User login", [
    'password' => $password,  // NEVER!
    'api_key' => $api_key     // NEVER!
]);
```

### Log with Context

Always include context to make logs useful:

✅ **Good:**
```php
SMPLFY_Log::error("Payment processing failed", [
    'order_id' => $entity->get_entry_id(),
    'customer_email' => $entity->customerEmail,
    'amount' => $entity->total,
    'payment_method' => $entity->paymentMethod,
    'error' => $e->getMessage(),
    'stripe_error_code' => $stripe_error->getCode()
]);
```

❌ **Bad:**
```php
SMPLFY_Log::error("Payment failed");
```

### Use Appropriate Log Levels

**Error** - Critical failures:
```php
SMPLFY_Log::error("Database update failed", [...]);
SMPLFY_Log::error("API returned 500 error", [...]);
SMPLFY_Log::error("Required field missing", [...]);
```

**Warning** - Potential issues:
```php
SMPLFY_Log::warn("Duplicate email detected", [...]);
SMPLFY_Log::warn("API response slow (>5s)", [...]);
SMPLFY_Log::warn("Inventory low", [...]);
```

**Info** - Normal operations:
```php
SMPLFY_Log::info("Form submitted", [...]);
SMPLFY_Log::info("Email sent successfully", [...]);
SMPLFY_Log::info("Workflow step completed", [...]);
```

### Avoid Log Spam

```php
// Bad - logs in loop
foreach ($entities as $entity) {
    SMPLFY_Log::info("Processing entity", ['id' => $entity->get_entry_id()]);
    $this->process($entity);
}

// Good - summary log
SMPLFY_Log::info("Batch processing started", ['count' => count($entities)]);
foreach ($entities as $entity) {
    $this->process($entity);
}
SMPLFY_Log::info("Batch processing complete");
```

### Skip debug.log When Needed

Use `$log_to_file = false` when:
- Logging is very frequent
- You only need Datadog (not local file)
- Avoiding duplicate logs (like in error handler)

```php
// Only log to Datadog, skip debug.log
SMPLFY_Log::info("High frequency event", $data, false);
```

---

## Troubleshooting

### Logs Not Appearing in Datadog

**Check 1: WP_DEBUG enabled?**
```php
// In wp-config.php
var_dump(WP_DEBUG); // Should be true
```

**Check 2: Datadog settings configured?**
```
WordPress Admin → SMPLFY Settings
- [ ] Send logs to Datadog is checked
- [ ] API URL is filled in
- [ ] API Key is filled in
```

**Check 3: Test manually**
```php
SMPLFY_Log::info("Manual test at " . current_time('mysql'));
```

Check `/wp-content/debug.log` - should see the log there even if Datadog fails.

**Check 4: Datadog API reachable?**
```php
$response = wp_remote_post('https://http-intake.logs.datadoghq.com/v1/input', [
    'body' => json_encode(['message' => 'test']),
    'headers' => ['DD-API-KEY' => 'your-key', 'Content-Type' => 'application/json']
]);

error_log("Datadog test response: " . print_r($response, true));
```

**Check 5: Wrong API URL or Key?**
- Verify URL matches your Datadog region
- Regenerate API key if needed

### Logs in debug.log but Not Datadog

This indicates Datadog configuration issue:

1. Verify API key is correct
2. Check API URL matches your region
3. Check "Send logs to Datadog" checkbox is enabled
4. Try creating a new API key in Datadog

### Too Many Logs

**Problem**: Flooding Datadog with logs

**Solutions:**
1. Remove debug logging from loops
2. Use summary logs instead of per-item logs
3. Use `$log_to_file = false` for high-frequency logs
4. Add conditional logging:

```php
// Only log errors, not every operation
if (is_wp_error($result)) {
    SMPLFY_Log::error("Operation failed", [...]);
}
// Don't log success for every operation
```

---

## Log Format

### Structure Sent to Datadog

```json
{
  "ddsource": "https://clientsite.com",
  "ddtags": "",
  "message": "Your log message\nYour data (formatted)",
  "service": "client-name-plugin",
  "level": "error|warning|info"
}
```

### Message Format

When you call:
```php
SMPLFY_Log::info("Order created", ['order_id' => 123, 'total' => 99.99]);
```

The message in Datadog looks like:
```
Order created
Array
(
    [order_id] => 123
    [total] => 99.99
)
```

---

## Advanced Usage

### Logging Arrays and Objects

```php
// Arrays are automatically formatted
SMPLFY_Log::info("Entry data", $entry);

// Objects are converted to arrays
SMPLFY_Log::info("Entity data", $entity->to_array());

// Complex nested data
SMPLFY_Log::error("API response", [
    'status' => 'failed',
    'response' => $api_response,
    'request' => $original_request
]);
```

### Conditional Logging

```php
// Only log in specific environments
if (defined('WP_ENVIRONMENT_TYPE') && WP_ENVIRONMENT_TYPE === 'staging') {
    SMPLFY_Log::info("Staging test", $data);
}

// Only log errors, skip info
if ($level === 'error') {
    SMPLFY_Log::error($message, $data);
}
```

### Performance Monitoring

```php
$start = microtime(true);

// ... your code ...

$duration = microtime(true) - $start;

if ($duration > 5) {
    SMPLFY_Log::warn("Slow operation detected", [
        'duration' => $duration,
        'operation' => 'process_large_dataset'
    ]);
}
```

---

## See Also

- [Debugging Guide](debugging.md) - General debugging tips
- [Best Practices](best-practices.md) - Logging best practices
- [Use Cases](use-cases.md) - Logging in use cases
- [Repositories](repositories.md) - Logging repository operations