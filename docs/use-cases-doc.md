# Use Cases

Use cases contain your business logic and represent specific actions or workflows triggered by user interactions or system events.

---

## Overview

A **use case** represents a single, specific business operation such as:
- "Submit Contact Form"
- "Approve Application"
- "Process Payment"
- "Sync Customer to CRM"

**Philosophy**: Use cases keep your business logic separate from WordPress/Gravity Forms hooks, making it easier to test, maintain, and understand.

---

## Structure of a Use Case

### Basic Template

```php
<?php
namespace SMPLFY\ClientName;

use SmplfyCore\SMPLFY_Log;
use SmplfyCore\WorkflowStep;

class ContactFormSubmissionUsecase {
    
    // Dependencies (repositories, services)
    private ContactFormRepository $contactRepository;
    private CustomerRepository $customerRepository;
    
    // Constructor with dependency injection
    public function __construct(
        ContactFormRepository $contactRepository,
        CustomerRepository $customerRepository
    ) {
        $this->contactRepository = $contactRepository;
        $this->customerRepository = $customerRepository;
    }
    
    // Main handler method
    public function handle_submission($entry) {
        // 1. Create entity from GF entry
        $entity = new ContactFormEntity($entry);
        
        // 2. Log the action
        SMPLFY_Log::info("Contact form submitted", [
            'email' => $entity->email,
            'entry_id' => $entry['id']
        ]);
        
        // 3. Execute business logic
        $this->process_contact($entity);
        
        // 4. Transition workflow (if needed)
        WorkflowStep::send('10', $entity->formEntry);
    }
    
    // Private helper methods
    private function process_contact(ContactFormEntity $entity) {
        // Business logic here
    }
}
```

### Key Components

**Dependencies**
- Repositories for data access
- Other use cases for related operations
- Service classes for external integrations

**Constructor**
- Use dependency injection (don't instantiate inside)
- Type-hint all dependencies

**Handler Methods**
- Public methods called by adapters
- Accept GF entry array or other trigger data
- Coordinate between repositories and services

**Private Methods**
- Break complex logic into smaller, testable pieces
- Keep handler methods focused and readable

---

## Common Use Case Patterns

### Form Submission Use Case

Handles what happens when a form is submitted.

```php
<?php
namespace SMPLFY\ClientName;

use SmplfyCore\SMPLFY_Log;

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
        $contactEntity = new ContactFormEntity($entry);
        
        SMPLFY_Log::info("Contact form submitted", [
            'email' => $contactEntity->email,
            'name' => $contactEntity->nameFirst . ' ' . $contactEntity->nameLast,
            'entry_id' => $entry['id']
        ]);
        
        // Check if customer already exists
        $existingCustomer = $this->customerRepository->get_one('email', $contactEntity->email);
        
        if ($existingCustomer) {
            $this->update_existing_customer($existingCustomer, $contactEntity);
        } else {
            $this->create_new_customer($contactEntity);
        }
        
        // Send notification
        $this->send_notification($contactEntity);
    }
    
    private function update_existing_customer($customer, ContactFormEntity $contact) {
        $customer->lastContactDate = current_time('mysql');
        $customer->contactCount = ($customer->contactCount ?? 0) + 1;
        
        $result = $this->customerRepository->update($customer);
        
        if (is_wp_error($result)) {
            SMPLFY_Log::error('Failed to update customer', [
                'customer_id' => $customer->get_entry_id(),
                'error' => $result->get_error_message()
            ]);
        } else {
            SMPLFY_Log::info('Updated existing customer', [
                'customer_id' => $customer->get_entry_id()
            ]);
        }
    }
    
    private function create_new_customer(ContactFormEntity $contact) {
        $customer = new CustomerEntity();
        $customer->email = $contact->email;
        $customer->firstName = $contact->nameFirst;
        $customer->lastName = $contact->nameLast;
        $customer->phone = $contact->phone;
        $customer->createdDate = current_time('mysql');
        $customer->lastContactDate = current_time('mysql');
        $customer->contactCount = 1;
        
        $customer_id = $this->customerRepository->add($customer);
        
        if (is_wp_error($customer_id)) {
            SMPLFY_Log::error('Failed to create customer', [
                'error' => $customer_id->get_error_message()
            ]);
        } else {
            SMPLFY_Log::info('Created new customer', [
                'customer_id' => $customer_id
            ]);
        }
    }
    
    private function send_notification(ContactFormEntity $contact) {
        wp_mail(
            get_option('admin_email'),
            'New Contact Form Submission',
            sprintf(
                "New contact from %s %s (%s)\n\nMessage: %s",
                $contact->nameFirst,
                $contact->nameLast,
                $contact->email,
                $contact->message
            )
        );
    }
}
```

### Workflow Step Completion Use Case

Handles what happens when a Gravity Flow step is completed.

```php
<?php
namespace SMPLFY\ClientName;

use SmplfyCore\SMPLFY_Log;
use SmplfyCore\WorkflowStep;

class ApplicationApprovalUsecase {
    
    private ApplicationRepository $applicationRepository;
    private CrmService $crmService;
    
    public function __construct(
        ApplicationRepository $applicationRepository,
        CrmService $crmService
    ) {
        $this->applicationRepository = $applicationRepository;
        $this->crmService = $crmService;
    }
    
    public function handle_approval($entry, $step_id) {
        $application = $this->applicationRepository->get_one('id', $entry['id']);
        
        if (!$application) {
            SMPLFY_Log::error('Application not found for approval', [
                'entry_id' => $entry['id']
            ]);
            return;
        }
        
        // Update application status
        $application->status = 'Approved';
        $application->approvalDate = current_time('mysql');
        $application->approvedBy = wp_get_current_user()->display_name;
        
        $result = $this->applicationRepository->update($application);
        
        if (is_wp_error($result)) {
            SMPLFY_Log::error('Failed to update application', [
                'error' => $result->get_error_message()
            ]);
            return;
        }
        
        SMPLFY_Log::info('Application approved', [
            'entry_id' => $application->get_entry_id(),
            'approver' => $application->approvedBy
        ]);
        
        // Send to CRM
        $this->crmService->create_opportunity($application);
        
        // Move to next workflow step
        WorkflowStep::send(WorkflowStepIds::PROCESSING, $application->formEntry);
        
        // Send confirmation email
        $this->send_approval_email($application);
    }
    
    public function handle_rejection($entry, $step_id) {
        $application = $this->applicationRepository->get_one('id', $entry['id']);
        
        if (!$application) {
            return;
        }
        
        $application->status = 'Rejected';
        $application->rejectionDate = current_time('mysql');
        $application->rejectedBy = wp_get_current_user()->display_name;
        
        $this->applicationRepository->update($application);
        
        SMPLFY_Log::info('Application rejected', [
            'entry_id' => $application->get_entry_id(),
            'rejector' => $application->rejectedBy
        ]);
        
        WorkflowStep::send(WorkflowStepIds::REJECTED, $application->formEntry);
        
        $this->send_rejection_email($application);
    }
    
    private function send_approval_email(ApplicationEntity $application) {
        wp_mail(
            $application->email,
            'Your Application Has Been Approved',
            sprintf(
                "Dear %s,\n\nYour application has been approved...",
                $application->nameFirst
            )
        );
    }
    
    private function send_rejection_email(ApplicationEntity $application) {
        wp_mail(
            $application->email,
            'Application Status Update',
            sprintf(
                "Dear %s,\n\nThank you for your application...",
                $application->nameFirst
            )
        );
    }
}
```

### Data Synchronization Use Case

Syncs data between Gravity Forms and external systems.

```php
<?php
namespace SMPLFY\ClientName;

use SmplfyCore\SMPLFY_Log;

class CrmSyncUsecase {
    
    private ContactFormRepository $contactRepository;
    private CrmApiService $crmApi;
    
    public function __construct(
        ContactFormRepository $contactRepository,
        CrmApiService $crmApi
    ) {
        $this->contactRepository = $contactRepository;
        $this->crmApi = $crmApi;
    }
    
    public function sync_contact_to_crm($entry) {
        $contact = new ContactFormEntity($entry);
        
        try {
            // Check if contact exists in CRM
            $crmContact = $this->crmApi->find_contact_by_email($contact->email);
            
            if ($crmContact) {
                // Update existing
                $this->crmApi->update_contact($crmContact['id'], [
                    'first_name' => $contact->nameFirst,
                    'last_name' => $contact->nameLast,
                    'phone' => $contact->phone,
                    'company' => $contact->company,
                    'last_contact_date' => current_time('mysql')
                ]);
                
                SMPLFY_Log::info('Updated contact in CRM', [
                    'entry_id' => $entry['id'],
                    'crm_id' => $crmContact['id']
                ]);
            } else {
                // Create new
                $crmId = $this->crmApi->create_contact([
                    'first_name' => $contact->nameFirst,
                    'last_name' => $contact->nameLast,
                    'email' => $contact->email,
                    'phone' => $contact->phone,
                    'company' => $contact->company,
                    'source' => 'Website Contact Form'
                ]);
                
                // Store CRM ID in GF entry
                $contact->crmId = $crmId;
                $this->contactRepository->update($contact);
                
                SMPLFY_Log::info('Created contact in CRM', [
                    'entry_id' => $entry['id'],
                    'crm_id' => $crmId
                ]);
            }
            
        } catch (\Exception $e) {
            SMPLFY_Log::error('CRM sync failed', [
                'entry_id' => $entry['id'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    public function sync_all_pending() {
        $pending = $this->contactRepository->get_all('crmSyncStatus', 'Pending');
        
        SMPLFY_Log::info('Starting bulk CRM sync', [
            'count' => count($pending)
        ]);
        
        foreach ($pending as $contact) {
            $this->sync_contact_to_crm($contact->formEntry);
            
            // Rate limiting
            sleep(1);
        }
        
        SMPLFY_Log::info('Completed bulk CRM sync');
    }
}
```

### Scheduled Task Use Case

Handles recurring tasks like daily reports or cleanups.

```php
<?php
namespace SMPLFY\ClientName;

use SmplfyCore\SMPLFY_Log;

class DailyReportUsecase {
    
    private OrderRepository $orderRepository;
    private CustomerRepository $customerRepository;
    
    public function __construct(
        OrderRepository $orderRepository,
        CustomerRepository $customerRepository
    ) {
        $this->orderRepository = $orderRepository;
        $this->customerRepository = $customerRepository;
    }
    
    public function generate_daily_report() {
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $orders = $this->orderRepository->get_orders_by_date($yesterday);
        
        $totalRevenue = 0;
        $orderCount = count($orders);
        
        foreach ($orders as $order) {
            $totalRevenue += (float) $order->total;
        }
        
        $newCustomers = $this->customerRepository->get_customers_by_date($yesterday);
        
        $report = sprintf(
            "Daily Report - %s\n\n" .
            "Orders: %d\n" .
            "Revenue: $%s\n" .
            "New Customers: %d\n",
            $yesterday,
            $orderCount,
            number_format($totalRevenue, 2),
            count($newCustomers)
        );
        
        // Send report email
        wp_mail(
            get_option('admin_email'),
            'Daily Sales Report - ' . $yesterday,
            $report
        );
        
        SMPLFY_Log::info('Daily report generated', [
            'date' => $yesterday,
            'orders' => $orderCount,
            'revenue' => $totalRevenue
        ]);
    }
}
```

---

## Connecting Use Cases to Hooks

Use cases are triggered by adapters. See [Adapters](adapters.md) for details on connecting use cases to WordPress/GF hooks.

---

## Use Case Guidelines

### Single Responsibility

Each use case should handle **one specific action**.

**✅ Good - Focused use cases:**
```php
class ContactFormSubmissionUsecase {
    public function handle_submission($entry) { }
}

class ApplicationApprovalUsecase {
    public function handle_approval($entry, $step_id) { }
}

class CrmSyncUsecase {
    public function sync_contact_to_crm($entry) { }
}
```

**❌ Bad - God object:**
```php
class FormProcessingUsecase {
    public function handle_contact_form($entry) { }
    public function handle_application_form($entry) { }
    public function handle_order_form($entry) { }
    public function sync_to_crm($entry) { }
    public function send_emails($entry) { }
    public function update_workflow($entry) { }
}
```

### Naming Conventions

**Use descriptive names:**
- ✅ `ContactFormSubmissionUsecase`
- ✅ `ApplicationApprovalUsecase`
- ✅ `OrderPaymentProcessingUsecase`
- ❌ `ContactUsecase` (too vague)
- ❌ `FormHandler` (not descriptive)
- ❌ `ProcessStuff` (meaningless)

**Handler method names:**
- ✅ `handle_submission()`
- ✅ `handle_approval()`
- ✅ `process_payment()`
- ❌ `execute()`
- ❌ `run()`
- ❌ `do_stuff()`

### Dependency Injection

Always inject dependencies via constructor.

**✅ Good:**
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

**❌ Bad:**
```php
class ContactFormSubmissionUsecase {
    public function handle_submission($entry) {
        // Don't instantiate inside methods
        $repository = new ContactFormRepository(...);
        $service = new CrmService();
    }
}
```

### Error Handling

Always handle and log errors.

**✅ Good:**
```php
public function handle_submission($entry) {
    try {
        $entity = new ContactFormEntity($entry);
        
        $result = $this->repository->add($entity);
        
        if (is_wp_error($result)) {
            throw new \Exception($result->get_error_message());
        }
        
        $this->crmService->sync($entity);
        
    } catch (\Exception $e) {
        SMPLFY_Log::error('Submission handling failed', [
            'entry_id' => $entry['id'],
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        // Don't throw - log and continue
    }
}
```

**❌ Bad:**
```php
public function handle_submission($entry) {
    // No error handling
    $entity = new ContactFormEntity($entry);
    $this->repository->add($entity);
    $this->crmService->sync($entity);
}
```

### Logging

Log important events and errors.

**What to log:**
- ✅ When use case starts
- ✅ Major operations (create, update, external API calls)
- ✅ Errors and exceptions
- ✅ Workflow transitions
- ✅ Business rule decisions

**What NOT to log:**
- ❌ Sensitive data (passwords, API keys, credit cards)
- ❌ Every single line of code
- ❌ Debug data in production (use debug level)

**Example:**
```php
public function handle_submission($entry) {
    SMPLFY_Log::info("Contact form submitted", [
        'entry_id' => $entry['id'],
        'email' => $entity->email
    ]);
    
    $customer = $this->customerRepository->get_one('email', $entity->email);
    
    if ($customer) {
        SMPLFY_Log::info("Existing customer found", [
            'customer_id' => $customer->get_entry_id()
        ]);
    } else {
        SMPLFY_Log::info("Creating new customer");
    }
    
    // ... business logic
}
```

---

## Testing Use Cases

### Manual Testing

Add temporary debug output:

```php
public function handle_submission($entry) {
    error_log("=== CONTACT FORM SUBMISSION ===");
    error_log("Entry ID: " . $entry['id']);
    error_log("Email: " . $entry['2']);
    
    $entity = new ContactFormEntity($entry);
    error_log("Entity created: " . print_r($entity->to_array(), true));
    
    $customer = $this->customerRepository->get_one('email', $entity->email);
    error_log("Existing customer: " . ($customer ? 'Yes' : 'No'));
    
    // ... rest of logic
}
```

### Using WP-CLI

Test use cases directly:

```bash
# Trigger use case manually
wp eval "
\$gravityFormsApi = new SmplfyCore\SMPLFY_GravityFormsApiWrapper();
\$contactRepo = new SMPLFY\ClientName\ContactFormRepository(\$gravityFormsApi);
\$customerRepo = new SMPLFY\ClientName\CustomerRepository(\$gravityFormsApi);

\$usecase = new SMPLFY\ClientName\ContactFormSubmissionUsecase(
    \$contactRepo,
    \$customerRepo
);

// Get a test entry
\$entry = GFAPI::get_entry(123);
\$usecase->handle_submission(\$entry);

echo 'Use case executed';
"
```

---

## Complex Use Case Example

Here's a complete, production-ready use case:

```php
<?php
namespace SMPLFY\ClientName;

use SmplfyCore\SMPLFY_Log;
use SmplfyCore\WorkflowStep;

class OrderProcessingUsecase {
    
    private OrderRepository $orderRepository;
    private CustomerRepository $customerRepository;
    private InventoryService $inventoryService;
    private PaymentService $paymentService;
    private ShippingService $shippingService;
    private NotificationService $notificationService;
    
    public function __construct(
        OrderRepository $orderRepository,
        CustomerRepository $customerRepository,
        InventoryService $inventoryService,
        PaymentService $paymentService,
        ShippingService $shippingService,
        NotificationService $notificationService
    ) {
        $this->orderRepository = $orderRepository;
        $this->customerRepository = $customerRepository;
        $this->inventoryService = $inventoryService;
        $this->paymentService = $paymentService;
        $this->shippingService = $shippingService;
        $this->notificationService = $notificationService;
    }
    
    public function handle_order_submission($entry) {
        $order = new OrderEntity($entry);
        
        SMPLFY_Log::info("Order submitted", [
            'entry_id' => $entry['id'],
            'customer_email' => $order->customerEmail,
            'total' => $order->total
        ]);
        
        try {
            // 1. Validate order
            $this->validate_order($order);
            
            // 2. Check inventory
            if (!$this->check_inventory($order)) {
                $this->handle_out_of_stock($order);
                return;
            }
            
            // 3. Process payment
            $paymentResult = $this->process_payment($order);
            
            if (!$paymentResult['success']) {
                $this->handle_payment_failure($order, $paymentResult['error']);
                return;
            }
            
            // 4. Reserve inventory
            $this->inventoryService->reserve_items($order->items);
            
            // 5. Create/update customer record
            $this->process_customer($order);
            
            // 6. Update order status
            $order->status = 'Processing';
            $order->paymentStatus = 'Paid';
            $order->paidDate = current_time('mysql');
            $this->orderRepository->update($order);
            
            // 7. Create shipping label
            $this->create_shipping_label($order);
            
            // 8. Move to next workflow step
            WorkflowStep::send(WorkflowStepIds::FULFILLMENT, $order->formEntry);
            
            // 9. Send notifications
            $this->send_order_confirmation($order);
            
            SMPLFY_Log::info("Order processed successfully", [
                'entry_id' => $order->get_entry_id(),
                'order_number' => $order->orderNumber
            ]);
            
        } catch (\Exception $e) {
            $this->handle_processing_error($order, $e);
        }
    }
    
    private function validate_order(OrderEntity $order) {
        if (empty($order->customerEmail)) {
            throw new \Exception('Customer email is required');
        }
        
        if ($order->total <= 0) {
            throw new \Exception('Order total must be greater than zero');
        }
        
        if (empty($order->items)) {
            throw new \Exception('Order must contain at least one item');
        }
    }
    
    private function check_inventory(OrderEntity $order): bool {
        return $this->inventoryService->check_availability($order->items);
    }
    
    private function process_payment(OrderEntity $order): array {
        try {
            $result = $this->paymentService->charge(
                $order->paymentMethod,
                $order->total,
                [
                    'order_id' => $order->get_entry_id(),
                    'customer_email' => $order->customerEmail
                ]
            );
            
            SMPLFY_Log::info("Payment processed", [
                'order_id' => $order->get_entry_id(),
                'amount' => $order->total,
                'transaction_id' => $result['transaction_id']
            ]);
            
            return ['success' => true, 'transaction_id' => $result['transaction_id']];
            
        } catch (\Exception $e) {
            SMPLFY_Log::error("Payment failed", [
                'order_id' => $order->get_entry_id(),
                'error' => $e->getMessage()
            ]);
            
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    private function process_customer(OrderEntity $order) {
        $customer = $this->customerRepository->get_one('email', $order->customerEmail);
        
        if ($customer) {
            $customer->lastOrderDate = current_time('mysql');
            $customer->totalOrders = ($customer->totalOrders ?? 0) + 1;
            $customer->lifetimeValue = ($customer->lifetimeValue ?? 0) + $order->total;
            $this->customerRepository->update($customer);
        } else {
            $customer = new CustomerEntity();
            $customer->email = $order->customerEmail;
            $customer->firstName = $order->billingFirstName;
            $customer->lastName = $order->billingLastName;
            $customer->phone = $order->billingPhone;
            $customer->firstOrderDate = current_time('mysql');
            $customer->lastOrderDate = current_time('mysql');
            $customer->totalOrders = 1;
            $customer->lifetimeValue = $order->total;
            $this->customerRepository->add($customer);
        }
    }
    
    private function create_shipping_label(OrderEntity $order) {
        try {
            $label = $this->shippingService->create_label([
                'name' => $order->shippingName,
                'address' => $order->shippingAddress,
                'city' => $order->shippingCity,
                'state' => $order->shippingState,
                'zip' => $order->shippingZip,
                'weight' => $order->totalWeight
            ]);
            
            $order->trackingNumber = $label['tracking_number'];
            $this->orderRepository->update($order);
            
        } catch (\Exception $e) {
            SMPLFY_Log::error("Failed to create shipping label", [
                'order_id' => $order->get_entry_id(),
                'error' => $e->getMessage()
            ]);
        }
    }
    
    private function send_order_confirmation(OrderEntity $order) {
        $this->notificationService->send_email(
            $order->customerEmail,
            'Order Confirmation',
            'order-confirmation',
            ['order' => $order]
        );
        
        $this->notificationService->send_admin_notification(
            'New Order',
            sprintf(
                "Order #%s from %s\nTotal: $%s",
                $order->orderNumber,
                $order->customerEmail,
                number_format($order->total, 2)
            )
        );
    }
    
    private function handle_out_of_stock(OrderEntity $order) {
        $order->status = 'On Hold';
        $order->statusReason = 'Out of Stock';
        $this->orderRepository->update($order);
        
        SMPLFY_Log::warning("Order on hold - out of stock", [
            'order_id' => $order->get_entry_id()
        ]);
        
        $this->notificationService->send_email(
            $order->customerEmail,
            'Order On Hold',
            'order-out-of-stock',
            ['order' => $order]
        );
    }
    
    private function handle_payment_failure(OrderEntity $order, string $error) {
        $order->status = 'Payment Failed';
        $order->paymentStatus = 'Failed';
        $order->paymentError = $error;
        $this->orderRepository->update($order);
        
        SMPLFY_Log::error("Order payment failed", [
            'order_id' => $order->get_entry_id(),
            'error' => $error
        ]);
        
        $this->notificationService->send_email(
            $order->customerEmail,
            'Payment Failed',
            'order-payment-failed',
            ['order' => $order, 'error' => $error]
        );
    }
    
    private function handle_processing_error(OrderEntity $order, \Exception $e) {
        $order->status = 'Error';
        $order->statusReason = $e->getMessage();
        $this->orderRepository->update($order);
        
        SMPLFY_Log::error("Order processing error", [
            'order_id' => $order->get_entry_id(),
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        // Notify admins of critical error
        $this->notificationService->send_admin_notification(
            'Order Processing Error',
            sprintf(
                "Error processing order #%s\n\nError: %s",
                $order->orderNumber,
                $e->getMessage()
            )
        );
    }
}
```

---

## Best Practices

✅ **Do:**
- Keep use cases focused on one action
- Use dependency injection
- Log important events
- Handle all errors gracefully
- Break complex logic into private methods
- Use descriptive names
- Return early on failures

❌ **Don't:**
- Put multiple unrelated actions in one use case
- Instantiate dependencies inside methods
- Ignore errors or exceptions
- Put database queries directly in use case (use repositories)
- Make use cases too large (split them up)
- Use generic names like "ProcessUsecase"

---

## See Also

- [Adapters](adapters.md) - Connecting use cases to hooks
- [Repositories](repositories.md) - Data access in use cases
- [Entities](entities.md) - Working with entity objects
- [Gravity Flow](gravity-flow.md) - Workflow transitions
- [Datadog Logging](datadog-logging.md) - Logging from use cases
            