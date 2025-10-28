# Gravity Flow Integration

Complete guide to integrating Gravity Flow workflows with the SMPLFY Core Plugin.

---

## Overview

Gravity Flow adds powerful workflow capabilities to Gravity Forms. The SMPLFY Core Plugin provides the `WorkflowStep` class to programmatically move entries between workflow steps.

**Use Cases:**
- Automatically approve/reject applications based on criteria
- Move entries to next step after payment confirmation
- Route entries based on form field values
- Trigger external actions when steps complete

---

## Prerequisites

- Gravity Flow plugin installed and activated
- At least one form with a configured workflow
- Understanding of your workflow structure

---

## WorkflowStep Class

The core plugin provides a simple class for workflow transitions:

```php
use SmplfyCore\WorkflowStep;

WorkflowStep::send($step_id, $entry);
```

### Parameters

**`$step_id`** (string) - Required
- The ID of the workflow step to move to
- Must be a string (e.g., `'10'` not `10`)
- See [Finding Step IDs](finding-ids.md#finding-workflow-step-ids)

**`$entry`** (array) - Required
- The raw Gravity Forms entry array
- **Must use `$entity->formEntry`**, not the entity itself
- This is critical - passing the entity will fail

---

## Basic Usage

### Moving to a Step

```php
use SmplfyCore\WorkflowStep;
use SmplfyCore\SMPLFY_Log;

public function handle_approval($entry) {
    $entity = $this->applicationRepository->get_one('id', $entry['id']);
    
    // Update entity
    $entity->status = 'Approved';
    $entity->approvalDate = current_time('mysql');
    $this->applicationRepository->update($entity);
    
    // Log the transition
    SMPLFY_Log::info("Moving to approved step", [
        'entry_id' => $entity->get_entry_id(),
        'step_id' => '15'
    ]);
    
    // Move to approved step
    WorkflowStep::send('15', $entity->formEntry);  // Note: formEntry, not entity!
}
```

### Using Constants for Step IDs

**Recommended**: Define step IDs as constants for better maintainability.

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

## Common Patterns

### Conditional Step Routing

Route to different steps based on form data:

```php
public function handle_submission($entry) {
    $entity = new ApplicationFormEntity($entry);
    
    // Route based on application type
    if ($entity->applicationType === 'Express') {
        WorkflowStep::send(WorkflowStepIds::EXPRESS_REVIEW, $entity->formEntry);
        SMPLFY_Log::info("Routed to express review");
    } elseif ($entity->applicationType === 'Standard') {
        WorkflowStep::send(WorkflowStepIds::STANDARD_REVIEW, $entity->formEntry);
        SMPLFY_Log::info("Routed to standard review");
    } else {
        WorkflowStep::send(WorkflowStepIds::MANUAL_REVIEW, $entity->formEntry);
        SMPLFY_Log::info("Routed to manual review");
    }
}
```

### Approval/Rejection Flow

```php
public function handle_manager_decision($entry, $approved) {
    $entity = $this->applicationRepository->get_one('id', $entry['id']);
    
    if ($approved) {
        // Approve
        $entity->status = 'Approved';
        $entity->approvalDate = current_time('mysql');
        $entity->approvedBy = wp_get_current_user()->display_name;
        
        $this->applicationRepository->update($entity);
        
        SMPLFY_Log::info("Application approved", [
            'entry_id' => $entity->get_entry_id(),
            'approver' => $entity->approvedBy
        ]);
        
        // Move to processing
        WorkflowStep::send(WorkflowStepIds::PROCESSING, $entity->formEntry);
        
        // Send approval email
        $this->send_approval_email($entity);
        
    } else {
        // Reject
        $entity->status = 'Rejected';
        $entity->rejectionDate = current_time('mysql');
        $entity->rejectedBy = wp_get_current_user()->display_name;
        
        $this->applicationRepository->update($entity);
        
        SMPLFY_Log::info("Application rejected", [
            'entry_id' => $entity->get_entry_id(),
            'rejector' => $entity->rejectedBy
        ]);
        
        // Move to rejected step
        WorkflowStep::send(WorkflowStepIds::REJECTED, $entity->formEntry);
        
        // Send rejection email
        $this->send_rejection_email($entity);
    }
}
```

### Multi-Step Process

```php
public function handle_payment_complete($entry) {
    $entity = $this->orderRepository->get_one('id', $entry['id']);
    
    // Update order
    $entity->paymentStatus = 'Paid';
    $entity->paidDate = current_time('mysql');
    $this->orderRepository->update($entity);
    
    SMPLFY_Log::info("Payment completed, moving to fulfillment");
    
    // Move to fulfillment
    WorkflowStep::send(WorkflowStepIds::FULFILLMENT, $entity->formEntry);
    
    // After fulfillment processes, move to shipping
    // (This would be called by another use case when fulfillment completes)
}

public function handle_fulfillment_complete($entry) {
    $entity = $this->orderRepository->get_one('id', $entry['id']);
    
    // Create shipping label
    $tracking = $this->shippingService->create_label($entity);
    
    $entity->trackingNumber = $tracking['number'];
    $entity->shippedDate = current_time('mysql');
    $this->orderRepository->update($entity);
    
    SMPLFY_Log::info("Order fulfilled, moving to shipped");
    
    // Move to shipped step
    WorkflowStep::send(WorkflowStepIds::SHIPPED, $entity->formEntry);
    
    // Send shipping notification
    $this->send_shipping_email($entity);
}
```

---

## Hooking into Gravity Flow Events

Use adapters to connect workflow events to your use cases.

### Gravity Flow Adapter

Create `/public/php/adapters/GravityFlowAdapter.php`:

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
        
        // Approval events
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
        
        // Step start
        add_action(
            'gravityflow_step_start',
            [$this, 'handle_step_start'],
            10,
            3
        );
    }
    
    public function handle_step_complete($entry_id, $step, $form_id, $status) {
        $entry = GFAPI::get_entry($entry_id);
        $step_id = $step->get_id();
        
        // Route based on form and step
        if ($form_id == FormIds::APPLICATION_FORM_ID) {
            if ($step_id == WorkflowStepIds::MANAGER_APPROVAL) {
                // Manager approval completed
                $this->approvalUsecase->handle_approval_complete($entry);
            }
        } elseif ($form_id == FormIds::ORDER_FORM_ID) {
            if ($step_id == WorkflowStepIds::PAYMENT_COMPLETE) {
                $this->orderProcessingUsecase->handle_payment_verified($entry);
            }
        }
    }
    
    public function handle_step_start($step, $entry_id, $form_id) {
        // Called when a step begins
        SMPLFY_Log::info("Workflow step started", [
            'entry_id' => $entry_id,
            'form_id' => $form_id,
            'step_id' => $step->get_id(),
            'step_type' => $step->get_type()
        ]);
    }
}
```

### Registering the Adapter

In your main plugin file:

```php
add_action('plugins_loaded', function() {
    // ... repositories and use cases ...
    
    // Gravity Flow Adapter
    if (class_exists('Gravity_Flow')) {
        $gflowAdapter = new SMPLFY\ClientName\GravityFlowAdapter(
            $approvalUsecase,
            $orderProcessingUsecase
        );
        
        $gflowAdapter->register_hooks();
    }
}, 20);
```

---

## Available Gravity Flow Hooks

### Workflow Events

```php
// When workflow starts
add_action('gravityflow_workflow_started', $callback, 10, 3);
// Params: $entry_id, $form_id, $workflow_timestamp

// When workflow completes
add_action('gravityflow_workflow_complete', $callback, 10, 3);
// Params: $entry_id, $form_id, $workflow_timestamp

// When workflow is cancelled
add_action('gravityflow_workflow_cancelled', $callback, 10, 2);
// Params: $entry_id, $form_id
```

### Step Events

```php
// When step starts
add_action('gravityflow_step_start', $callback, 10, 3);
// Params: $step, $entry_id, $form_id

// When step completes
add_action('gravityflow_step_complete', $callback, 10, 4);
// Params: $entry_id, $step, $form_id, $status

// When step is processed
add_action('gravityflow_post_process_workflow', $callback, 10, 4);
// Params: $form, $entry_id, $step, $current_step_id
```

### Approval Events

```php
// When approved
add_action('gravityflow_approval_status_approved', $callback, 10, 4);
// Params: $form, $entry_id, $step, $assignee

// When rejected
add_action('gravityflow_approval_status_rejected', $callback, 10, 4);
// Params: $form, $entry_id, $step, $assignee

// When status changes
add_action('gravityflow_status_updated', $callback, 10, 4);
// Params: $entry_id, $new_status, $old_status, $assignee
```

---

## Workflow Information

### Getting Current Step

```php
$current_step_id = gform_get_meta($entry_id, 'workflow_step');

SMPLFY_Log::info("Current workflow step", [
    'entry_id' => $entry_id,
    'step_id' => $current_step_id
]);
```

### Getting Workflow Status

```php
$status = gform_get_meta($entry_id, 'workflow_final_status');

// Status values:
// - 'pending' - In progress
// - 'complete' - Workflow finished
// - 'cancelled' - Workflow cancelled
```

### Checking if Entry is in Workflow

```php
public function is_in_workflow($entry_id) {
    $status = gform_get_meta($entry_id, 'workflow_final_status');
    return $status === 'pending';
}
```

---

## Troubleshooting

### Step Transition Not Working

**Problem**: `WorkflowStep::send()` called but entry doesn't move

**Causes & Solutions**:

1. **Using entity instead of formEntry**
   ```php
   // WRONG
   WorkflowStep::send('10', $entity);
   
   // CORRECT
   WorkflowStep::send('10', $entity->formEntry);
   ```

2. **Wrong step ID**
   - Verify step ID in Gravity Flow admin
   - Check step ID is a string: `'10'` not `10`
   - Use constants to avoid typos

3. **Entry not in workflow**
   ```php
   $workflow_status = gform_get_meta($entity->get_entry_id(), 'workflow_final_status');
   if ($workflow_status !== 'pending') {
       SMPLFY_Log::warn("Entry not in active workflow", [
           'entry_id' => $entity->get_entry_id(),
           'status' => $workflow_status
       ]);
   }
   ```

4. **Step conditions not met**
   - Check step settings in Gravity Flow
   - Verify conditional logic is satisfied
   - Check required fields are filled

**Debug**:
```php
SMPLFY_Log::info("Before step transition", [
    'entry_id' => $entity->get_entry_id(),
    'current_step' => gform_get_meta($entity->get_entry_id(), 'workflow_step'),
    'target_step' => WorkflowStepIds::APPROVED
]);

WorkflowStep::send(WorkflowStepIds::APPROVED, $entity->formEntry);

// Wait a moment, then check
sleep(1);
$new_step = gform_get_meta($entity->get_entry_id(), 'workflow_step');

SMPLFY_Log::info("After step transition", [
    'entry_id' => $entity->get_entry_id(),
    'new_step' => $new_step,
    'success' => ($new_step == WorkflowStepIds::APPROVED)
]);
```

### Hook Not Firing

**Problem**: Gravity Flow hook never executes

**Solutions**:
1. Verify Gravity Flow is active: `class_exists('Gravity_Flow')`
2. Check adapter's `register_hooks()` is called
3. Verify hook name is correct
4. Check form has an active workflow
5. Enable Gravity Flow logging: Forms → Settings → Logging

### Can't Find Step ID

See [Finding Step IDs](finding-ids.md#finding-workflow-step-ids)

---

## Best Practices

### Always Use formEntry

✅ **Correct**:
```php
WorkflowStep::send(WorkflowStepIds::APPROVED, $entity->formEntry);
```

❌ **Wrong**:
```php
WorkflowStep::send(WorkflowStepIds::APPROVED, $entity);
WorkflowStep::send(WorkflowStepIds::APPROVED, $entry);  // If $entry is not raw GF entry
```

### Use Constants for Step IDs

✅ **Good**:
```php
WorkflowStep::send(WorkflowStepIds::APPROVED, $entity->formEntry);
```

❌ **Bad**:
```php
WorkflowStep::send('15', $entity->formEntry);  // Magic number
```

### Log Workflow Transitions

```php
SMPLFY_Log::info("Moving to approval step", [
    'entry_id' => $entity->get_entry_id(),
    'from_step' => $current_step,
    'to_step' => WorkflowStepIds::APPROVED,
    'user' => wp_get_current_user()->display_name
]);

WorkflowStep::send(WorkflowStepIds::APPROVED, $entity->formEntry);
```

### Update Entity Before Transition

```php
// Update entity first
$entity->status = 'Approved';
$entity->approvalDate = current_time('mysql');
$this->repository->update($entity);

// Then transition
WorkflowStep::send(WorkflowStepIds::APPROVED, $entity->formEntry);
```

### Handle Errors Gracefully

```php
try {
    WorkflowStep::send(WorkflowStepIds::APPROVED, $entity->formEntry);
    
    SMPLFY_Log::info("Workflow transition successful");
    
} catch (\Exception $e) {
    SMPLFY_Log::error("Workflow transition failed", [
        'error' => $e->getMessage(),
        'entry_id' => $entity->get_entry_id()
    ]);
}
```

---

## Complete Example

Here's a full approval workflow implementation:

```php
<?php
namespace SMPLFY\ClientName;

use SmplfyCore\SMPLFY_Log;
use SmplfyCore\WorkflowStep;

class ApplicationApprovalUsecase {
    
    private ApplicationRepository $applicationRepository;
    private NotificationService $notificationService;
    
    public function __construct(
        ApplicationRepository $applicationRepository,
        NotificationService $notificationService
    ) {
        $this->applicationRepository = $applicationRepository;
        $this->notificationService = $notificationService;
    }
    
    /**
     * Called when manager approves application
     */
    public function handle_approval($form, $entry_id, $step, $assignee) {
        $entity = $this->applicationRepository->get_one('id', $entry_id);
        
        if (!$entity) {
            SMPLFY_Log::error("Application not found for approval", [
                'entry_id' => $entry_id
            ]);
            return;
        }
        
        // Update application
        $entity->status = 'Approved';
        $entity->approvalDate = current_time('mysql');
        $entity->approvedBy = $assignee->get_display_name();
        
        $result = $this->applicationRepository->update($entity);
        
        if (is_wp_error($result)) {
            SMPLFY_Log::error("Failed to update application", [
                'entry_id' => $entry_id,
                'error' => $result->get_error_message()
            ]);
            return;
        }
        
        SMPLFY_Log::info("Application approved", [
            'entry_id' => $entry_id,
            'approver' => $entity->approvedBy
        ]);
        
        // Send to external system
        $this->sync_to_crm($entity);
        
        // Move to processing step
        WorkflowStep::send(WorkflowStepIds::PROCESSING, $entity->formEntry);
        
        // Send approval email
        $this->notificationService->send_email(
            $entity->email,
            'Application Approved',
            'approval-template',
            ['entity' => $entity]
        );
    }
    
    /**
     * Called when manager rejects application
     */
    public function handle_rejection($form, $entry_id, $step, $assignee) {
        $entity = $this->applicationRepository->get_one('id', $entry_id);
        
        if (!$entity) {
            return;
        }
        
        // Update application
        $entity->status = 'Rejected';
        $entity->rejectionDate = current_time('mysql');
        $entity->rejectedBy = $assignee->get_display_name();
        
        $this->applicationRepository->update($entity);
        
        SMPLFY_Log::info("Application rejected", [
            'entry_id' => $entry_id,
            'rejector' => $entity->rejectedBy
        ]);
        
        // Move to rejected step
        WorkflowStep::send(WorkflowStepIds::REJECTED, $entity->formEntry);
        
        // Send rejection email
        $this->notificationService->send_email(
            $entity->email,
            'Application Status Update',
            'rejection-template',
            ['entity' => $entity]
        );
    }
    
    private function sync_to_crm($entity) {
        try {
            $this->crmService->create_opportunity([
                'name' => $entity->get_full_name(),
                'email' => $entity->email,
                'status' => 'Approved Application'
            ]);
            
            SMPLFY_Log::info("Synced to CRM", [
                'entry_id' => $entity->get_entry_id()
            ]);
            
        } catch (\Exception $e) {
            SMPLFY_Log::error("CRM sync failed", [
                'entry_id' => $entity->get_entry_id(),
                'error' => $e->getMessage()
            ]);
        }
    }
}
```

---

## See Also

- [Use Cases](use-cases.md) - Organizing workflow logic
- [Adapters](adapters.md) - Hooking into Gravity Flow
- [Finding IDs](finding-ids.md) - Finding workflow step IDs
- [Datadog Logging](datadog-logging.md) - Logging workflow events