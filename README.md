# SMPLFY Core Plugin

**Core abstraction layer for Gravity Forms business automation solutions.**

This plugin provides reusable base classes, field mapping utilities, Datadog logging, and Gravity Flow integration for building maintainable, readable business automation plugins on top of Gravity Forms.

---

## 🎯 Purpose

When building complex Gravity Forms solutions, you typically work with numeric form and field IDs throughout your code:

```php
// Hard to read and maintain
$email = rgar($entry, '2');
$first_name = rgar($entry, '1.3');
GFAPI::update_entry_property($entry_id, '2', $new_email);
```

This plugin transforms that into:

```php
// Self-documenting and maintainable
$entity = $repository->get_one(2, $entry_id);
$email = $entity->email;
$first_name = $entity->nameFirst;
$entity->email = $new_email;
$repository->update($entity);
```

**This plugin eliminates:**
- Form/field ID hardcoding throughout your codebase
- Guesswork when troubleshooting ("What is field 2? What is field 1.3?")
- Scattered logging across multiple plugins
- Repetitive CRUD code for every form

---

## 🏗️ Architecture

This plugin uses the **Repository and Entity patterns** alongside the **Use Case pattern** to provide:

- **Form Entities**: Object representations of form entries with named properties
- **Form Repositories**: CRUD operations and entry management
- **Use Cases**: Business logic containers triggered by events
- **Property Mapping System**: Convert GF field IDs to readable names
- **Datadog Integration**: Centralized logging
- **Gravity Flow Integration**: Programmatic workflow step transitions
- **WordPress Heartbeat Integration**: Real-time data updates
- **Security Utilities**: Prevent direct script execution
- **Require Utilities**: Recursive file loading

---

## 📋 Requirements

### Required
- **WordPress**: 6.0+
- **PHP**: 7.3+
- **Gravity Forms**: 2.8.4+
- **Gravity Flow**: 2.x+ (for workflow features)

### Optional
- Datadog account (for logging features)
- WP-CLI (recommended for debugging)

---

## 🚀 Quick Start

1. Install this plugin in `/wp-content/plugins/smplfy-core-plugin/`
2. Activate via WordPress Admin → Plugins
3. Configure Datadog (optional): WordPress Admin → SMPLFY Settings
4. Clone the [SMPLFY Boilerplate Plugin](https://github.com/sblik/smplfy-boilerplate-plugin) to create your client plugin

**Note**: This plugin provides no functionality on its own. It requires a companion client-specific plugin.

---

## 📚 Documentation

### Core Concepts
- **[Entities](docs/entities.md)** - Working with form entities and property mapping
- **[Repositories](docs/repositories.md)** - CRUD operations and data management
- **[Use Cases](docs/use-cases.md)** - Organizing business logic
- **[Adapters](docs/adapters.md)** - Connecting use cases to WordPress/GF hooks

### Features
- **[Gravity Flow Integration](docs/gravity-flow.md)** - Workflow step transitions
- **[Datadog Logging](docs/datadog-logging.md)** - Centralized logging configuration
- **[WordPress Heartbeat](docs/heartbeat.md)** - Real-time data updates
- **[Security](docs/security.md)** - Security features and best practices

### Development
- **[Getting Started](docs/getting-started.md)** - Setting up a new client site
- **[Best Practices](docs/best-practices.md)** - Naming conventions and patterns
- **[Debugging Guide](docs/debugging.md)** - Troubleshooting common issues
- **[API Reference](docs/api-reference.md)** - Complete method reference

### Guides
- **[Finding Form & Field IDs](docs/finding-ids.md)** - Locating IDs in Gravity Forms
- **[WP-CLI Commands](docs/wp-cli.md)** - Using WP-CLI for debugging

---

## 🔧 Client Plugin Structure

```
client-plugin/
├── public/
│   └── php/
│       ├── entities/          # Extend SMPLFY_BaseEntity
│       ├── repositories/      # Extend SMPLFY_BaseRepository
│       ├── usecases/         # Business logic
│       ├── adapters/         # Hook registration
│       └── types/            # Constants (FormIds, etc.)
```

See the [Getting Started Guide](docs/getting-started.md) for detailed setup instructions.

---

## 💡 Quick Examples

### Creating an Entity
```php
class ContactFormEntity extends SMPLFY_BaseEntity {
    public function __construct($formEntry = array()) {
        parent::__construct($formEntry);
        $this->formId = FormIds::CONTACT_FORM_ID;
    }
    
    protected function get_property_map(): array {
        return [
            'nameFirst' => '1.3',
            'nameLast'  => '1.6',
            'email'     => '2'
        ];
    }
}
```

### Using a Repository
```php
// Get entry by email
$entity = $repository->get_one('email', 'john@example.com');

// Update entry
$entity->phone = '555-1234';
$repository->update($entity);

// Log to Datadog
SMPLFY_Log::info("Entry updated", ['entry_id' => $entity->get_entry_id()]);
```

### Moving Workflow Steps
```php
WorkflowStep::send(WorkflowStepIds::APPROVED, $entity->formEntry);
```

---

## 🐛 Common Issues

**Entry not updating?** → Check [Debugging Guide](docs/debugging.md#entry-not-updating)

**Property not working?** → Verify field IDs are strings: `'2'` not `2`

**Workflow not transitioning?** → Use `$entity->formEntry`, not `$entity`

See the [Debugging Guide](docs/debugging.md) for more troubleshooting help.

---

## 📚 Additional Resources

- [SMPLFY Boilerplate Plugin](https://github.com/sblik/smplfy-boilerplate-plugin) - Starting template
- [Gravity Forms Documentation](https://docs.gravityforms.com/)
- [Gravity Flow Documentation](https://docs.gravityflow.io/)

---

## 📄 License

GPL v2 or later

---

## 🤝 Contributing

This is an internal tool for our development team. When making updates:

1. Test locally with an existing client plugin
2. Update relevant documentation in `/docs`
3. Add entry to CHANGELOG.md
4. Communicate changes to team before deployment

---

**Questions?** Check the [documentation](docs/) or ask the team.
