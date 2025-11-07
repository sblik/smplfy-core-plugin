# Debugging Guide

Complete troubleshooting guide for common issues when working with the SMPLFY Core Plugin.

---

## General Debugging Tips

### Automatic Error Logging

**Good news**: The SMPLFY Core Plugin automatically logs all PHP errors and exceptions to Datadog!

- Fatal errors → `error` level
- Warnings → `warning` level  
- Notices → `info` level
- Uncaught exceptions → `error` level with full stack trace

You don't need to add logging code for PHP errors - they're captured automatically.

### Enable WordPress Debugging

**IMPORTANT**: `SMPLFY_Log` requires `WP_DEBUG` to be enabled. Add to `wp-config.php`:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
@ini_set('display_errors', 0);
```

**Note**: Even in production, you should enable `WP_DEBUG` and `WP_DEBUG_LOG` but keep `WP_DEBUG_DISPLAY` false. This allows SMPLFY_Log to write to both `/wp-content/debug.log` and Datadog without displaying errors to users.

Logs will be written to `/wp-content/debug.log`

### Enable Gravity Forms Logging

1. Go to **Forms → Settings → Logging**
2. Check **"Enable Logging"**
3. Select **"Log all messages"** (or specific categories)
4. View logs at **Forms → System Status → Logs**

### Check Datadog Logs

If Datadog is enabled:
1. Go to your Datadog dashboard
2. Filter by `source:your-site-url` (uses your WordPress site URL)
3. Filter by `service:your-plugin-name` (automatically detected from plugin folder)
4. Search for your entry ID or email
5. Look for errors or warnings

**Log Levels in Datadog**:
- `error` - Critical errors and exceptions
- `warning` - Warnings and potential issues
- `info` - Informational messages and events

---

## Entity Issues

### Property Returns Null

**Problem**: `$entity->email` returns `null` but field has data in GF admin

**Causes & Solutions**:

1. **Field ID mismatch**
   ```php
   // Check property map
   protected function get_property_map(): array {
       return [
           'email' => '2',  // Must match GF field ID exactly
       ];
   }
   ```
   
   **Fix**: Verify field ID in Gravity Forms admin

2. **Field ID is not a string**
   ```php
   // WRONG
   'email' => 2,
   
   // CORRECT
   'email' => '2',
   ```
   
   **Fix**: Always use string values for field IDs

3. **Using parent field ID for sub-fields**
   ```php
   // WRONG - for Name (First) field
   'nameFirst' => '1',
   
   // CORRECT
   'nameFirst' => '1.3',
   ```
   
   **Fix**: Use sub-field notation (1.3, 1.6, etc.)

4. **Property not in entity map**
   ```php
   // Add missing property to map
   protected function get_property_map(): array {
       return [
           'email' => '2',
           'phone' => '3',  // Was missing
       ];
   }
   ```

**Debug Steps**:
```php
// In your use case
$entity = new ContactFormEntity($entry);

// Check what's in the entry
error_log("GF Entry: " . print_r($entry, true));

// Check what's in the entity
error_log("Entity array: " . print_r($entity->to_array(), true));

// Check specific field
error_log("Email from entry: " . rgar($entry, '2'));
error_log("Email from entity: " . $entity->email);
```

### IDE Autocomplete Not Working

**Problem**: IDE doesn't suggest properties when typing `$entity->`

**Solution**: Add PHPDoc `@property` declarations

```php
/**
 * @property string $nameFirst
 * @property string $nameLast
 * @property string $email
 * @property string $phone
 */
class ContactFormEntity extends SMPLFY_BaseEntity {
```

---

## Repository Issues

### Repository Returns Null

**Problem**: `$repository->get_one()` returns `null` but entry exists

**Causes & Solutions**:

1. **Field value doesn't match exactly**
   ```php
   // Case-sensitive, whitespace matters
   $entity = $repository->get_one('email', 'john@example.com');
   // Won't find: 'John@example.com' or ' john@example.com '
   ```
   
   **Fix**: Ensure exact match or trim/normalize values

2. **Wrong form ID**
   ```php
   // Check repository constructor
   public function __construct(...) {
       $this->formId = FormIds::CONTACT_FORM_ID;  // Verify this is correct
       // ...
   }
   ```
   
   **Debug**: Check if form ID matches
   ```php
   error_log("Looking for form: " . FormIds::CONTACT_FORM_ID);
   error_log("Entry form ID: " . $entry['form_id']);
   ```

3. **Entry is in trash**
   
   **Fix**: Check GF admin → Trash

4. **Using property name instead of field ID**
   ```php
   // Try both
   $entity = $repository->get_one('email', 'john@example.com');  // Property name
   $entity = $repository->get_one(2, 'john@example.com');        // Field ID
   ```

**Debug with GFAPI**:
```php
// Check if entry exists in GF
$entries = GFAPI::get_entries(FormIds::CONTACT_FORM_ID, [
    'field_filters' => [
        ['key' => '2', 'value' => 'john@example.com']
    ]
]);
error_log("GFAPI found: " . count($entries) . " entries");
error_log("Entries: " . print_r($entries, true));
```

### Update Not Saving

**Problem**: `$repository->update($entity)` succeeds but changes don't persist

**Causes & Solutions**:

1. **Not calling update**
   ```php
   // WRONG - forgot to save
   $entity->phone = '555-1234';
   
   // CORRECT
   $entity->phone = '555-1234';
   $repository->update($entity);
   ```

2. **Property not mapped**
   ```php
   // Verify property is in get_property_map()
   protected function get_property_map(): array {
       return [
           'phone' => '3',  // Must be here
       ];
   }
   ```

3. **Field validation fails**
   
   **Debug**: Check GF field settings for validation rules

4. **Permissions issue**
   
   **Debug**: Check if current user can edit entries
   ```php
   error_log("Current user: " . wp_get_current_user()->user_login);
   error_log("Can edit: " . (current_user_can('edit_posts') ? 'Yes' : 'No'));
   ```

**Debug**:
```php
$result = $repository->update($entity);

if (is_wp_error($result)) {
    error_log("Update error: " . $result->get_error_message());
} else {
    // Reload and verify
    $reloaded = $repository->get_one('id', $entity->get_entry_id());
    error_log("Phone after update: " . $reloaded->phone);
}
```

### Get All Returns Empty Array

**Problem**: `$repository->get_all()` returns `[]` but entries exist

**Causes & Solutions**:

1. **Wrong form ID**
   ```php
   // Verify form ID in constructor
   error_log("Repository form ID: " . FormIds::CONTACT_FORM_ID);
   
   // Check actual form
   $form = GFAPI::get_form(FormIds::CONTACT_FORM_ID);
   error_log("Form exists: " . ($form ? 'Yes' : 'No'));
   ```

2. **Entries in trash**
   
   **Fix**: Restore from trash in GF admin

3. **No entries yet**
   
   **Verify**: Check GF admin entries list

**Debug**:
```php
// Check with GFAPI directly
$entries = GFAPI::get_entries(FormIds::CONTACT_FORM_ID);
error_log("GFAPI get_entries: " . count($entries));
error_log("Repository get_all: " . count($repository->get_all()));
```

---

## Use Case Issues

### Use Case Not Executing

**Problem**: Form submitted but use case never runs

**Causes & Solutions**:

1. **Hook not registered**
   ```php
   // Verify in main plugin file
   $gfAdapter->register_hooks();  // Must be called!
   ```

2. **Wrong form ID in hook**
   ```php
   // Check adapter
   add_action(
       'gform_after_submission_' . FormIds::CONTACT_FORM_ID,
       // ^^ Verify this form ID is correct
       [$this->contactSubmissionUsecase, 'handle_submission'],
       10,
       2
   );
   ```

3. **Method doesn't exist**
   ```php
   // Verify method name matches
   public function handle_submission($entry) {  // Must match exactly
   ```

4. **PHP error in use case**
   
   **Check**: `/wp-content/debug.log` for errors

**Debug**:
```php
// Add to adapter
add_action('gform_after_submission_' . FormIds::CONTACT_FORM_ID, function($entry, $form) {
    error_log("=== HOOK FIRED ===");
    error_log("Entry ID: " . $entry['id']);
    error_log("Form ID: " . $form['id']);
}, 5, 2); // Priority 5 = before your use case

// Add to use case
public function handle_submission($entry) {
    error_log("=== USE CASE STARTED ===");
    error_log("Entry: " . print_r($entry, true));
    // ...
}
```

### Errors in Use Case

**Problem**: Use case starts but throws errors

**Debug Strategy**:

```php
public function handle_submission($entry) {
    try {
        error_log("Step 1: Creating entity");
        $entity = new ContactFormEntity($entry);
        error_log("Entity created successfully");
        
        error_log("Step 2: Checking for existing customer");
        $customer = $this->customerRepository->get_one('email', $entity->email);
        error_log("Customer found: " . ($customer ? 'Yes' : 'No'));
        
        error_log("Step 3: Processing logic");
        // ... business logic
        
        error_log("Use case completed successfully");
        
    } catch (\Exception $e) {
        error_log("USE CASE ERROR: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
    }
}
```

---

## Workflow Issues

### Workflow Step Not Transitioning

**Problem**: `WorkflowStep::send()` doesn't move entry to next step

**Causes & Solutions**:

1. **Using entity instead of formEntry**
   ```php
   // WRONG
   WorkflowStep::send('10', $entity);
   
   // CORRECT
   WorkflowStep::send('10', $entity->formEntry);
   ```

2. **Wrong step ID**
   ```php
   // Verify step ID in Gravity Flow admin
   error_log("Trying to move to step: " . WorkflowStepIds::APPROVED);
   ```

3. **Entry not in workflow**
   ```php
   // Check if entry has workflow
   $workflow_status = gform_get_meta($entity->get_entry_id(), 'workflow_final_status');
   error_log("Workflow status: " . print_r($workflow_status, true));
   ```

4. **Step conditions not met**
   
   **Check**: Gravity Flow step settings for conditions

**Debug**:
```php
error_log("Before transition - Entry: " . print_r($entity->formEntry, true));

WorkflowStep::send(WorkflowStepIds::APPROVED, $entity->formEntry);

error_log("After transition");

// Reload entry and check current step
$entry = GFAPI::get_entry($entity->get_entry_id());
$current_step = gform_get_meta($entity->get_entry_id(), 'workflow_step');
error_log("Current step after transition: " . $current_step);
```

---

## Hook Issues

### Hook Not Firing

**Problem**: WordPress/GF hook never fires

**Debug Steps**:

1. **Verify hook exists**
   ```php
   // List all actions
   global $wp_filter;
   error_log("Registered actions: " . print_r(array_keys($wp_filter), true));
   ```

2. **Check hook name**
   ```php
   // Verify exact hook name
   $hook = 'gform_after_submission_' . FormIds::CONTACT_FORM_ID;
   error_log("Looking for hook: " . $hook);
   error_log("Form ID: " . FormIds::CONTACT_FORM_ID);
   ```

3. **Test with simple callback**
   ```php
   add_action('gform_after_submission_5', function($entry, $form) {
       error_log("SIMPLE HOOK FIRED!");
       error_log("Entry ID: " . $entry['id']);
   }, 10, 2);
   ```

4. **Check plugin load order**
   ```php
   // In main plugin file - increase priority
   add_action('plugins_loaded', function() {
       // Register adapters
   }, 20); // Higher = later
   ```

---

## Common Error Messages

### "Class not found"

**Error**: `Fatal error: Class 'SMPLFY\ClientName\ContactFormEntity' not found`

**Solutions**:
1. Verify namespace matches file: `namespace SMPLFY\ClientName;`
2. Check `require_utilities()` is loading the directory
3. Verify file name matches class name: `ContactFormEntity.php`
4. Check for typos in class name

### "Call to undefined method"

**Error**: `Fatal error: Call to undefined method... ::handle_submission()`

**Solutions**:
1. Verify method exists and is public
2. Check method name spelling
3. Verify use case is instantiated correctly
4. Check for PHP syntax errors in use case class

### "Cannot access private property"

**Error**: `Fatal error: Cannot access private property $email`

**Solution**: Properties accessed via magic methods must be in `get_property_map()`

```php
// Make sure property is mapped
protected function get_property_map(): array {
    return [
        'email' => '2',  // Must be here to access $entity->email
    ];
}
```

---

## Using WP-CLI for Debugging

### Check Forms

```bash
# List all forms
wp gf form list

# Get form details
wp gf form get 5

# Export form
wp gf form export 5
```

### Check Entries

```bash
# List entries for a form
wp gf entry list 5

# Get specific entry
wp gf entry get 123

# Search entries
wp gf entry list 5 --field-id=2 --field-value=john@example.com
```

### Test Repository

```bash
# Get all entries via repository
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
print_r(\$entity ? \$entity->to_array() : 'Not found');
"
```

### Trigger Hooks Manually

```bash
# Trigger use case
wp eval "
\$entry = GFAPI::get_entry(123);
\$form = GFAPI::get_form(5);
do_action('gform_after_submission_5', \$entry, \$form);
echo 'Hook triggered';
"
```

---

## Performance Debugging

### Slow Queries

**Check**: How many entries are being loaded

```php
$start = microtime(true);
$entities = $repository->get_all();
$time = microtime(true) - $start;

error_log("Loaded " . count($entities) . " entries in " . $time . " seconds");
```

**Solution**: Add filtering to reduce data

```php
// Instead of loading all
$all = $repository->get_all();

// Load only what you need
$pending = $repository->get_all('status', 'Pending');
```

### Memory Issues

**Check memory usage**:

```php
error_log("Memory used: " . memory_get_usage(true) / 1024 / 1024 . " MB");
error_log("Memory peak: " . memory_get_peak_usage(true) / 1024 / 1024 . " MB");
```

**Solution**: Process in batches

```php
// Instead of loading all at once
$all = $repository->get_all();
foreach ($all as $entity) {
    $this->process($entity);
}

// Process in batches
$entries = GFAPI::get_entries(FormIds::CONTACT_FORM_ID, [], null, ['page_size' => 50]);
foreach ($entries as $entry) {
    $entity = new ContactFormEntity($entry);
    $this->process($entity);
}
```

---

## Debugging Checklist

When something isn't working, go through this checklist:

### Entity Issues
- [ ] Property is in `get_property_map()`
- [ ] Field ID is a string (e.g., `'2'` not `2`)
- [ ] Using correct sub-field notation (e.g., `'1.3'`)
- [ ] Property has `@property` PHPDoc declaration
- [ ] `formId` is set in entity constructor

### Repository Issues
- [ ] `entityType` is set correctly in constructor
- [ ] `formId` is set correctly in constructor
- [ ] Form exists in Gravity Forms
- [ ] Entries not in trash
- [ ] Field values match exactly (case-sensitive)
- [ ] Checking for `WP_Error` after add/update/delete

### Use Case Issues
- [ ] Use case method is public
- [ ] Method name matches adapter callback
- [ ] Dependencies injected via constructor
- [ ] Error handling with try-catch
- [ ] Logging important events
- [ ] Checking for `WP_Error` from repositories

### Adapter Issues
- [ ] `register_hooks()` is called in main plugin file
- [ ] Hook name is correct (including form ID)
- [ ] Form ID constant matches actual form
- [ ] Use case is instantiated before adapter
- [ ] Adapter callback points to correct method
- [ ] Plugin loads after Gravity Forms (priority 20+)

### Workflow Issues
- [ ] Using `$entity->formEntry` not `$entity`
- [ ] Step ID is correct (check Gravity Flow admin)
- [ ] Entry is actually in a workflow
- [ ] Step conditions are met
- [ ] Gravity Flow is active

---

## Advanced Debugging

### Enable Query Monitor Plugin

Install Query Monitor plugin for detailed debugging:
- PHP errors, warnings, notices
- Database queries
- HTTP requests
- Hook execution order
- Memory usage
- Template usage

### Database Queries

Check Gravity Forms meta directly:

```sql
-- Check entry meta
SELECT * FROM wp_gf_entry_meta 
WHERE entry_id = 123;

-- Check workflow status
SELECT * FROM wp_gf_entry_meta 
WHERE meta_key = 'workflow_final_status' 
AND entry_id = 123;

-- Find entries by email
SELECT e.* FROM wp_gf_entry e
INNER JOIN wp_gf_entry_meta m ON e.id = m.entry_id
WHERE m.meta_key = '2'  -- Email field
AND m.meta_value = 'john@example.com';
```

### Xdebug Breakpoints

If using Xdebug:

```php
// Set breakpoint here
$entity = new ContactFormEntity($entry);

// Inspect variables
$email = $entity->email;
$all_data = $entity->to_array();

// Step through code
$result = $repository->update($entity);
```

### Logging Best Practices

**Log context, not just messages**:

```php
// BAD
error_log("Error occurred");

// GOOD
error_log(json_encode([
    'message' => 'Failed to update customer',
    'entry_id' => $entry['id'],
    'customer_email' => $entity->email,
    'error' => $e->getMessage(),
    'timestamp' => current_time('mysql')
]));
```

**Use log levels**:

```php
// Production - errors and warnings only
if (is_wp_error($result)) {
    SMPLFY_Log::error("CRITICAL: Payment failed - " . $result->get_error_message());
}

// Important events
SMPLFY_Log::info("Testing payment flow");

// Potential issues
SMPLFY_Log::warn("API response slow", ['duration' => $duration]);
```

**Note**: There is no `debug()` method in SMPLFY_Log. Use `info()` for informational logging.

---

## Getting Help

If you're still stuck:

1. **Check Datadog logs** - Look for errors around the time of the issue
2. **Review debug.log** - Check `/wp-content/debug.log` for PHP errors
3. **Check Gravity Forms logs** - Forms → System Status → Logs
4. **Ask the team** - Share your debug output and error messages
5. **Simplify the problem** - Remove complexity until it works, then add back

### What to Include When Asking for Help

- **Error message** (exact text from logs)
- **What you're trying to do** (expected behavior)
- **What's actually happening** (actual behavior)
- **Code snippet** (relevant entity/repository/use case)
- **Debug output** (error_log or Datadog logs)
- **Environment** (WordPress version, PHP version, GF version)

---

## Quick Reference

### Common Debug Snippets

**Check if property is set**:
```php
error_log("Email set: " . (isset($entity->email) ? 'Yes' : 'No'));
error_log("Email value: " . ($entity->email ?? 'NULL'));
```

**Dump entity data**:
```php
error_log("Entity: " . json_encode($entity->to_array(), JSON_PRETTY_PRINT));
```

**Check repository result**:
```php
$result = $repository->update($entity);
error_log("Update result: " . (is_wp_error($result) ? $result->get_error_message() : 'Success'));
```

**Verify hook registration**:
```php
global $wp_filter;
$hook = 'gform_after_submission_5';
error_log("Hook registered: " . (isset($wp_filter[$hook]) ? 'Yes' : 'No'));
if (isset($wp_filter[$hook])) {
    error_log("Callbacks: " . print_r($wp_filter[$hook]->callbacks, true));
}
```

**Check form exists**:
```php
$form = GFAPI::get_form(FormIds::CONTACT_FORM_ID);
error_log("Form exists: " . ($form ? 'Yes' : 'No'));
if ($form) {
    error_log("Form title: " . $form['title']);
}
```

**Check entry exists**:
```php
$entry = GFAPI::get_entry(123);
error_log("Entry exists: " . (is_array($entry) && !is_wp_error($entry) ? 'Yes' : 'No'));
if (is_wp_error($entry)) {
    error_log("Entry error: " . $entry->get_error_message());
}
```

---

## See Also

- [Entities](entities.md) - Entity troubleshooting
- [Repositories](repositories.md) - Repository troubleshooting  
- [Use Cases](use-cases.md) - Use case debugging
- [Finding IDs](finding-ids.md) - How to find correct IDs
- [WP-CLI](wp-cli.md) - Command line debugging