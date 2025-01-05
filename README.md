# EDD Exporters Registration Library

A comprehensive PHP library for registering custom exporters in Easy Digital Downloads. This library provides a robust solution for programmatically creating and managing export functionality with metaboxes, batch processing, and file organization.

## Features

- 🚀 Easy registration of custom exporters
- 📊 Export metabox management
- 🔄 Batch processing support
- 📁 Automatic file path management
- 🛠️ Helper functions for quick implementation
- 🔄 Singleton pattern for consistent state
- ✅ Type safety with strict typing

## Requirements

- PHP 7.4 or higher
- WordPress 6.7.1 or higher
- Easy Digital Downloads 3.0 or higher

## Installation

You can install the package via composer:

```bash
composer require arraypress/edd-register-exporters
```

## Basic Usage

Here's a simple example of how to register an exporter with its metabox:

```php
use function ArrayPress\EDD\Register\Export\register_exporters;

$metaboxes = [
    [
        'id'           => 'guest-verifications',
        'title'        => 'Export Guest Verifications',
        'description'  => 'Download a CSV of guest verification logs.',
        'export_class' => 'GuestVerificationBatchExport',
        'file'         => 'admin/exporters/class-guest-verification-batch-export.php',
        'fields'       => [
            [
                'type'   => 'date',
                'id'     => 'guest-verification-dates',
                'name'   => 'guest-verification-export',
                'legend' => 'Select Date Range'
            ]
        ]
    ]
];

// Base path defaults to plugin directory
register_exporters($metaboxes);
```

## Separate Registration

You can register metaboxes and batch exporters separately:

```php
use function ArrayPress\EDD\Register\Export\{register_metaboxes, register_batch_exporters};

// Register metaboxes
register_metaboxes($metaboxes);

// Register batch exporters
$exporters = [
    'guest-verifications' => [
        'class' => 'GuestVerificationBatchExport',
        'file'  => 'class-guest-verification-batch-export.php'
    ]
];
register_batch_exporters($exporters, dirname(__FILE__) . '/exporters');
```

## Configuration Options

### Metabox Configuration

| Option | Type | Required | Description |
|--------|------|----------|-------------|
| id | string | Yes | Unique identifier for the exporter |
| title | string | Yes | Title displayed in the exports tab |
| description | string | No | Description of the export functionality |
| export_class | string | Yes | Class name for the batch exporter |
| file | string | Yes | Path to the exporter class file |
| fields | array | No | Form fields for the export options |

### Field Types
- `date` - Date range selector
- `customer` - Customer dropdown
- `product` - Product selector
- `country` - Country selector
- `region` - Region selector
- `order_statuses` - Order status selector
- `month_year` - Month and year selector

## Direct Class Usage

For more control, you can use the classes directly:

```php
use ArrayPress\EDD\Register\Export\{BatchExporters, Metaboxes};

// Get the exporters instance
$exporters = BatchExporters::instance();
$exporters->register_export('guest-logs', [
    'class' => 'GuestLogsBatchExport',
    'file'  => 'class-guest-logs-batch-export.php'
]);

// Get the metaboxes instance
$metaboxes = Metaboxes::instance();
$metaboxes->register_metabox([
    'id'           => 'guest-logs',
    'title'        => 'Export Guest Logs',
    'export_class' => 'GuestLogsBatchExport',
    'file'         => 'class-guest-logs-batch-export.php'
]);
```

## File Organization

By default, the library looks for exporter classes relative to your plugin's root directory:

```
your-plugin/
├── admin/
│   └── exporters/
│       └── class-guest-verification-batch-export.php
├── includes/
└── your-plugin.php
```

You can customize this by:
1. Providing a custom base_path in the exporter configuration
2. Passing a base_path to register functions

## Error Handling

The library uses type declarations and strict typing for error prevention:

```php
try {
    register_exporters([
        'id'    => 'example',
        'title' => 'Example Export'
    ]);
} catch (TypeError $e) {
    error_log($e->getMessage());
}
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request. For major changes, please open an issue first to discuss what you would like to change.

## License

This project is licensed under the GPL2+ License. See the LICENSE file for details.

## Support

For support, please use the [issue tracker](https://github.com/arraypress/edd-register-exporters/issues).