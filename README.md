# EDD Register Custom Exporters

Register custom batch export classes with metabox forms for Easy Digital Downloads 3.0+ export system.

## Installation

```bash
composer require arraypress/edd-register-exporters
```

## Basic Usage

### Register Multiple Exporters

```php
// Register multiple batch exporters with optional metabox forms
edd_register_custom_batch_exporters( [
    'customer-analytics' => [
        'class'       => 'EDD_Customer_Analytics_Export',
        'file'        => 'exports/class-customer-analytics.php',
        'title'       => 'Customer Analytics Export',
        'description' => 'Export detailed customer analytics and behavior data.',
        'fields'      => [
            [
                'type'   => 'date',
                'id'     => 'analytics_date',
                'name'   => 'date',
                'legend' => 'Select date range'
            ],
            [
                'type' => 'customer',
                'id'   => 'analytics_customer',
                'name' => 'customer_id'
            ]
        ]
    ],
    'product-performance' => [
        'class'       => 'EDD_Product_Performance_Export', 
        'file'        => 'exports/product-performance.php',
        'title'       => 'Product Performance Report',
        'description' => 'Analyze product sales performance and metrics.',
        'fields'      => [
            [
                'type' => 'product',
                'id'   => 'performance_product',
                'name' => 'product'
            ],
            [
                'type' => 'date',
                'id'   => 'performance_date',
                'name' => 'date'
            ]
        ]
    ],
    'financial-summary' => [
        'class'       => 'EDD_Financial_Summary_Export',
        'file'        => 'exports/financial-summary.php',
        'title'       => 'Financial Summary Export',
        'description' => 'Export complete financial data for accounting.'
        // No fields = metabox with just title, description, and export button
    ]
], __DIR__ ); // Base path for relative file paths
```

### Register Single Exporter

```php
// Register a single batch exporter
edd_register_custom_batch_exporter(
    'all-orders',
    [
        'class'       => 'EDD_All_Orders_Export',
        'file'        => 'exports/all-orders.php',
        'title'       => 'Export All Orders',
        'description' => 'Download complete order history.',
        'fields'      => [
            [
                'type'   => 'date',
                'id'     => 'orders_date',
                'name'   => 'date',
                'legend' => 'Select date range'
            ]
        ]
    ],
    __DIR__
);
```

## Exports Without Form Fields

If you don't specify `fields`, the metabox will still render with:
- Title
- Description (if provided)
- Export button

This is perfect for simple "export all" functionality that doesn't need filtering.

```php
edd_register_custom_batch_exporter(
    'simple-export',
    [
        'class'       => 'EDD_Simple_Export',
        'file'        => 'exports/simple-export.php',
        'title'       => 'Export All Data',
        'description' => 'Download complete dataset with no filters.'
        // No fields - just shows a button to trigger export
    ],
    __DIR__
);
```

## Real-World Examples

### Product Performance with Advanced Filtering

```php
edd_register_custom_batch_exporters( [
    'advanced-product-analysis' => [
        'class'       => 'EDD_Advanced_Product_Export',
        'file'        => 'exports/advanced-product.php',
        'title'       => 'Advanced Product Analysis',
        'description' => 'Comprehensive product performance analysis with multiple filters.',
        'fields'      => [
            [
                'type'     => 'product',
                'id'       => 'analysis_product',
                'name'     => 'product',
                'multiple' => true
            ],
            [
                'type'   => 'date',
                'id'     => 'analysis_date',
                'name'   => 'date',
                'legend' => 'Select date range'
            ],
            [
                'type' => 'country',
                'id'   => 'analysis_country',
                'name' => 'country'
            ],
            [
                'type' => 'order_statuses',
                'id'   => 'analysis_status',
                'name' => 'status'
            ]
        ]
    ]
], __DIR__ );

// Export class that uses all the form data
class EDD_Advanced_Product_Export extends EDD_Batch_Export {

    public $export_type = 'advanced_product_analysis';

    public function csv_cols() {
        return [
            'product_id'        => __( 'Product ID', 'textdomain' ),
            'product_name'      => __( 'Product Name', 'textdomain' ),
            'filtered_sales'    => __( 'Sales', 'textdomain' ),
            'filtered_earnings' => __( 'Earnings', 'textdomain' ),
            'avg_sale_value'    => __( 'Avg Sale', 'textdomain' ),
            'conversion_rate'   => __( 'Conversion', 'textdomain' ),
            'total_views'       => __( 'Views', 'textdomain' ),
            'price'             => __( 'Price', 'textdomain' ),
            'created_date'      => __( 'Created', 'textdomain' ),
        ];
    }

    public function get_data() {
        $downloads = get_posts( [
            'post_type'      => 'download',
            'post_status'    => 'publish',
            'posts_per_page' => 15,
            'offset'         => ( $this->step - 1 ) * 15,
            'post__in'       => $this->get_filtered_products()
        ] );

        if ( empty( $downloads ) ) {
            return false;
        }

        $data = [];

        foreach ( $downloads as $download ) {
            // Get filtered sales data
            $payment_args = [
                'download' => $download->ID,
                'number'   => -1
            ];

            // Apply date filters
            if ( ! empty( $this->start ) ) {
                $payment_args['start_date'] = $this->start;
            }

            if ( ! empty( $this->end ) ) {
                $payment_args['end_date'] = $this->end;
            }

            // Apply status filter
            if ( ! empty( $_REQUEST['status'] ) ) {
                $payment_args['status'] = sanitize_text_field( $_REQUEST['status'] );
            }

            $payments = edd_get_payments( $payment_args );

            // Filter by country if specified
            if ( ! empty( $_REQUEST['country'] ) ) {
                $country = sanitize_text_field( $_REQUEST['country'] );
                $payments = array_filter( $payments, function( $payment ) use ( $country ) {
                    $address = $payment->address;
                    return isset( $address['country'] ) && $address['country'] === $country;
                } );
            }

            $filtered_sales = count( $payments );
            $filtered_earnings = array_sum( array_map( function( $payment ) {
                return $payment->total;
            }, $payments ) );

            // Get conversion data
            $views = get_post_meta( $download->ID, '_edd_download_views', true ) ?: 1;
            $conversion_rate = round( ( $filtered_sales / $views ) * 100, 2 );

            $data[] = [
                'product_id'         => $download->ID,
                'product_name'       => $download->post_title,
                'filtered_sales'     => $filtered_sales,
                'filtered_earnings'  => $filtered_earnings,
                'avg_sale_value'     => $filtered_sales > 0 ? round( $filtered_earnings / $filtered_sales, 2 ) : 0,
                'conversion_rate'    => $conversion_rate . '%',
                'total_views'        => $views,
                'price'              => edd_get_download_price( $download->ID ),
                'created_date'       => $download->post_date
            ];
        }

        return $data;
    }

    public function get_percentage_complete() {
        $total = wp_count_posts( 'download' )->publish;
        $percentage = ( $total > 0 ) ? ( ( 15 * $this->step ) / $total ) * 100 : 100;
        return min( $percentage, 100 );
    }

    public function set_properties( $request ) {
        $this->start = isset( $request['date-start'] ) ? sanitize_text_field( $request['date-start'] ) : '';
        $this->end   = isset( $request['date-end'] ) ? sanitize_text_field( $request['date-end'] ) : '';
    }

    private function get_filtered_products() {
        if ( ! empty( $_REQUEST['product'] ) ) {
            $products = (array) $_REQUEST['product'];
            return array_map( 'absint', $products );
        }

        return null; // No filter applied
    }
}
```

### Customer Segmentation Export

```php
edd_register_custom_batch_exporters( [
    'customer-segments' => [
        'class'       => 'EDD_Customer_Segments_Export',
        'file'        => 'exports/customer-segments.php',
        'title'       => 'Customer Segmentation Export',
        'description' => 'Export customers grouped by spending behavior and engagement.',
        'fields'      => [
            [
                'type'    => 'select',
                'id'      => 'segment_type',
                'name'    => 'segment',
                'options' => [
                    ''             => 'All Segments',
                    'high_value'   => 'High Value ($500+)',
                    'medium_value' => 'Medium Value ($100-$499)',
                    'low_value'    => 'Low Value ($1-$99)',
                    'single_buyer' => 'Single Purchase Only',
                    'repeat_buyer' => 'Repeat Buyers (2+)',
                    'inactive'     => 'Inactive (6+ months)'
                ]
            ],
            [
                'type'   => 'date',
                'id'     => 'segment_date',
                'name'   => 'date',
                'legend' => 'Select date range'
            ]
        ]
    ]
], __DIR__ );
```

### Simple Export (No Filters)

```php
edd_register_custom_batch_exporter(
    'complete-database',
    [
        'class'       => 'EDD_Complete_Database_Export',
        'file'        => 'exports/complete-database.php',
        'title'       => 'Complete Database Export',
        'description' => 'Export entire EDD database for backup or migration.'
        // No fields needed - one-click export
    ],
    __DIR__
);
```

## Available Field Types

| Type | Description | Form Control |
|------|-------------|--------------|
| `customer` | Customer dropdown | Single or multiple customer selection |
| `product` | Product dropdown | Single or multiple product selection |
| `country` | Country selector | Country dropdown |
| `region` | Region selector | Region dropdown |
| `order_statuses` | Payment status | Order status dropdown |
| `date` | Date range picker | Start and end date fields |
| `month_year` | Month/year dropdowns | Month and year selectors |
| `select` | Custom dropdown | Custom options dropdown |
| `text` | Text input | Text input field |
| `separator` | Visual separator | Styling element |

## Configuration Options

| Option | Required | Description |
|--------|----------|-------------|
| `class` | **Yes** | PHP class name that extends `EDD_Batch_Export` |
| `file` | **Yes** | Path to file containing the class |
| `title` | No | Display title for metabox (auto-generated from key if omitted) |
| `description` | No | Description shown in metabox |
| `fields` | No | Array of form fields (metabox renders with or without fields) |
| `label` | No | Alternative to title |

## Form Data Access

In your export class, access form data through:

- `$this->start` and `$this->end` - Date range fields (set via `set_properties()`)
- `$_REQUEST['field_name']` - Other form fields
- `set_properties( $request )` - Method to process and store form data

Example:
```php
public function set_properties( $request ) {
    $this->start  = isset( $request['date-start'] ) ? sanitize_text_field( $request['date-start'] ) : '';
    $this->end    = isset( $request['date-end'] ) ? sanitize_text_field( $request['date-end'] ) : '';
    $this->status = isset( $request['status'] ) ? sanitize_text_field( $request['status'] ) : '';
}
```

## API Functions

### `edd_register_custom_batch_exporter()`

Register a single batch exporter.

```php
edd_register_custom_batch_exporter( string $key, array $export, ?string $base_path = null )
```

**Parameters:**
- `$key` (string) - Unique identifier for the export
- `$export` (array) - Export configuration
- `$base_path` (string|null) - Optional base path for the export file

**Returns:** `bool|WP_Error` - True on success, WP_Error on failure

### `edd_register_custom_batch_exporters()`

Register multiple batch exporters at once.

```php
edd_register_custom_batch_exporters( array $exports, ?string $base_path = null )
```

**Parameters:**
- `$exports` (array) - Associative array of export configurations
- `$base_path` (string|null) - Optional base path for export files

**Returns:** `bool|WP_Error` - True on success, WP_Error on failure

## Requirements

- PHP 8.0+
- WordPress 5.0+
- Easy Digital Downloads 3.0+

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This project is licensed under the GPL-2.0-or-later License.

## Support

- [Documentation](https://github.com/arraypress/edd-register-batch-exporters)
- [Issue Tracker](https://github.com/arraypress/edd-register-batch-exporters/issues)