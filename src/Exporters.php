<?php
/**
 * Batch Exporters Registry for Easy Digital Downloads
 *
 * @package     ArrayPress\EDD\Register
 * @copyright   Copyright 2024, ArrayPress Limited
 * @license     GPL-2.0-or-later
 * @version     1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\EDD\Register;

use WP_Error;

/**
 * Class Exporters
 *
 * Handles registration of custom batch export classes with optional metabox forms
 * for Easy Digital Downloads export system.
 */
class Exporters {

    /**
     * An associative array of batch exports with their configurations.
     *
     * @var array
     */
    private array $exports = [];

    /**
     * The base path for export files.
     *
     * @var string|null
     */
    private ?string $base_path;

    /**
     * Constructor.
     *
     * @param string|null $base_path Optional base path for export files.
     */
    public function __construct( ?string $base_path = null ) {
        $this->base_path = $base_path;
    }

    /**
     * Register batch exporters.
     *
     * @param array $exports An associative array of batch exports.
     *
     * @return WP_Error|true WP_Error on failure, true on success.
     */
    public function register( array $exports ) {
        if ( empty( $exports ) ) {
            return new WP_Error(
                    'empty_exports',
                    __( 'Exports array cannot be empty.', 'arraypress' )
            );
        }

        $validated_exports = [];

        foreach ( $exports as $key => $export ) {
            if ( empty( $key ) ) {
                return new WP_Error(
                        'invalid_export_key',
                        __( 'Export key cannot be empty.', 'arraypress' )
                );
            }

            if ( empty( $export['class'] ) ) {
                return new WP_Error(
                        'missing_export_class',
                        sprintf( __( 'Export "%s" must have a class.', 'arraypress' ), $key )
                );
            }

            if ( empty( $export['file'] ) ) {
                return new WP_Error(
                        'missing_export_file',
                        sprintf( __( 'Export "%s" must have a file path.', 'arraypress' ), $key )
                );
            }

            // Verify export class file exists
            $file = $this->get_full_file_path( $export['file'] );
            if ( ! file_exists( $file ) ) {
                return new WP_Error(
                        'export_file_not_found',
                        sprintf( __( 'Export file not found: %s', 'arraypress' ), $file )
                );
            }

            $validated_export = [
                    'id'          => $key,
                    'class'       => $export['class'],
                    'file'        => $export['file'],
                    'title'       => $export['title'] ?? ucwords( str_replace( '-', ' ', $key ) ),
                    'label'       => $export['label'] ?? ucwords( str_replace( '-', ' ', $key ) ),
                    'description' => $export['description'] ?? '',
                    'priority'    => $export['priority'] ?? 60,
                    'button'      => $export['button'] ?? __( 'Generate CSV', 'arraypress' ),
            ];

            // Optional metabox fields
            if ( isset( $export['fields'] ) && is_array( $export['fields'] ) ) {
                $validated_export['fields'] = $export['fields'];
            }

            $validated_exports[ $key ] = $validated_export;
        }

        $this->exports = array_merge( $this->exports, $validated_exports );

        if ( function_exists( 'edd_debug_log' ) ) {
            edd_debug_log( sprintf( '[EDD Batch Exporters] Registered %d batch exporters', count( $validated_exports ) ) );
        }

        return true;
    }

    /**
     * Initialize the batch exporters registration.
     *
     * Sets up WordPress hooks for handling export registration and field rendering.
     *
     * @return void
     */
    public function init(): void {
        if ( empty( $this->exports ) ) {
            return;
        }

        add_action( 'edd_export_init', [ $this, 'register_exporters' ] );
        add_action( 'edd_export_form', [ $this, 'render_fields' ], 10, 2 );

        if ( function_exists( 'edd_debug_log' ) ) {
            edd_debug_log( sprintf( '[EDD Batch Exporters] Initialized %d batch exporters', count( $this->exports ) ) );
        }
    }

    /**
     * Register exporters with the EDD Registry.
     *
     * Called by EDD during export initialization to register all exporters
     * with the central registry system.
     *
     * @param \EDD\Admin\Exports\Registry $registry The EDD export registry instance.
     *
     * @return void
     */
    public function register_exporters( $registry ): void {
        foreach ( $this->exports as $key => $export ) {
            try {
                $registry->register_exporter( $key, [
                        'label'       => $export['label'] ?? $export['title'],
                        'description' => $export['description'] ?? '',
                        'class'       => $export['class'],
                        'class_path'  => $this->get_full_file_path( $export['file'] ),
                        'priority'    => $export['priority'] ?? 60,
                        'button'      => $export['button'] ?? __( 'Generate CSV', 'arraypress' ),
                ] );
            } catch ( \Exception $e ) {
                if ( function_exists( 'edd_debug_log' ) ) {
                    edd_debug_log( sprintf( '[EDD Batch Exporters] Failed to register exporter "%s": %s', $key, $e->getMessage() ), true );
                }
            }
        }
    }

    /**
     * Render custom fields for an exporter.
     *
     * Called via the edd_export_form action to render any custom fields
     * defined for the exporter.
     *
     * @param string $exporter_id The ID of the exporter being rendered.
     * @param array  $exporter    The exporter configuration array.
     *
     * @return void
     */
    public function render_fields( string $exporter_id, array $exporter ): void {
        if ( isset( $this->exports[ $exporter_id ]['fields'] ) && is_array( $this->exports[ $exporter_id ]['fields'] ) ) {
            foreach ( $this->exports[ $exporter_id ]['fields'] as $field ) {
                $this->render_field( $field );
            }
        }
    }

    /**
     * Render a single form field.
     *
     * Renders different types of form fields based on the field type.
     * Supports customer, product, country, date, and other field types.
     *
     * @param array $field Field configuration array containing type, id, name, etc.
     *
     * @return void
     */
    private function render_field( array $field ): void {
        $field = wp_parse_args( $field, [
                'type'  => '',
                'id'    => '',
                'name'  => '',
                'label' => '',
                'class' => '',
                'desc'  => '',
        ] );

        if ( ! empty( $field['label'] ) ) {
            echo '<label for="' . esc_attr( $field['id'] ) . '" class="screen-reader-text">' . esc_html( $field['label'] ) . '</label>';
        }

        switch ( strtolower( $field['type'] ) ) {
            case 'customer':
                $this->render_customer_field( $field );
                break;
            case 'product':
                $this->render_product_field( $field );
                break;
            case 'country':
                $this->render_country_field( $field );
                break;
            case 'order_statuses':
                $this->render_order_statuses_field( $field );
                break;
            case 'region':
                $this->render_region_field( $field );
                break;
            case 'date':
                $this->render_date_field( $field );
                break;
            case 'month_year':
                $this->render_month_year_field( $field );
                break;
            case 'separator':
                $this->render_separator( $field );
                break;
            default:
                if ( method_exists( EDD()->html, $field['type'] ) ) {
                    echo call_user_func( [ EDD()->html, $field['type'] ], $field );
                }
                break;
        }
    }

    /**
     * Get the full file path.
     *
     * Resolves relative file paths using the base path if provided,
     * otherwise returns the path as-is.
     *
     * @param string $file The file path (relative or absolute).
     *
     * @return string The full file path.
     */
    private function get_full_file_path( string $file ): string {
        return $this->base_path ? trailingslashit( $this->base_path ) . $file : $file;
    }

    /**
     * Render product dropdown field.
     *
     * Renders a product selection dropdown using EDD's built-in product dropdown.
     *
     * @param array $field Field configuration array.
     *
     * @return void
     */
    private function render_product_field( array $field ): void {
        $defaults = [
                'id'       => 'edd_export_product',
                'name'     => 'product',
                'chosen'   => true,
                'multiple' => false,
        ];

        $args = wp_parse_args( $field, $defaults );
        echo EDD()->html->product_dropdown( $args );
    }

    /**
     * Render customer dropdown field.
     *
     * Renders a customer selection dropdown using EDD's built-in customer dropdown.
     *
     * @param array $field Field configuration array.
     *
     * @return void
     */
    private function render_customer_field( array $field ): void {
        $defaults = [
                'id'            => 'edd_export_customer',
                'name'          => 'customer_id',
                'chosen'        => true,
                'multiple'      => false,
                'none_selected' => '',
                'placeholder'   => __( 'All Customers', 'arraypress' ),
        ];

        $args = wp_parse_args( $field, $defaults );
        echo EDD()->html->customer_dropdown( $args );
    }

    /**
     * Render country selection field.
     *
     * Renders a country selection dropdown using EDD's built-in country selector.
     *
     * @param array $field Field configuration array.
     *
     * @return void
     */
    private function render_country_field( array $field ): void {
        $defaults = [
                'id'              => 'edd_export_country',
                'name'            => 'country',
                'selected'        => false,
                'show_option_all' => false,
        ];

        $args = wp_parse_args( $field, $defaults );
        echo EDD()->html->country_select( $args );
    }

    /**
     * Render region selection field.
     *
     * Renders a region selection dropdown using EDD's built-in region selector.
     *
     * @param array $field Field configuration array.
     *
     * @return void
     */
    private function render_region_field( array $field ): void {
        $defaults = [
                'id'          => 'edd_reports_filter_regions',
                'placeholder' => __( 'All Regions', 'arraypress' ),
        ];

        $args = wp_parse_args( $field, $defaults );
        echo EDD()->html->region_select( $args );
    }

    /**
     * Render order statuses selection field.
     *
     * Renders a dropdown for selecting payment/order statuses using EDD's
     * available payment statuses.
     *
     * @param array $field Field configuration array.
     *
     * @return void
     */
    private function render_order_statuses_field( array $field ): void {
        $defaults = [
                'id'               => 'edd_export_status',
                'name'             => 'status',
                'show_option_all'  => __( 'All Statuses', 'arraypress' ),
                'show_option_none' => false,
                'selected'         => false,
                'options'          => edd_get_payment_statuses(),
        ];

        $args = wp_parse_args( $field, $defaults );
        echo EDD()->html->select( $args );
    }

    /**
     * Render date range field.
     *
     * Renders a date range picker with start and end date fields using
     * EDD's built-in date field functionality.
     *
     * @param array $field Field configuration array containing id, name, and optional legend.
     *
     * @return void
     */
    private function render_date_field( array $field ): void {
        ?>
        <fieldset class="edd-from-to-wrapper">
            <legend class="screen-reader-text"><?php echo esc_html( $field['legend'] ?? '' ); ?></legend>
            <label for="<?php echo esc_attr( $field['id'] ); ?>-start"
                   class="screen-reader-text"><?php esc_html_e( 'Set start date', 'arraypress' ); ?></label>
            <span id="edd-<?php echo esc_attr( $field['id'] ); ?>-start-wrap">
				<?php
                echo EDD()->html->date_field( [
                        'id'          => $field['id'] . '-start',
                        'class'       => 'edd-export-start',
                        'name'        => $field['name'] . '-start',
                        'placeholder' => _x( 'From', 'date filter', 'arraypress' ),
                ] );
                ?>
			</span>
            <label for="<?php echo esc_attr( $field['id'] ); ?>-end"
                   class="screen-reader-text"><?php esc_html_e( 'Set end date', 'arraypress' ); ?></label>
            <span id="edd-<?php echo esc_attr( $field['id'] ); ?>-end-wrap">
				<?php
                echo EDD()->html->date_field( [
                        'id'          => $field['id'] . '-end',
                        'class'       => 'edd-export-end',
                        'name'        => $field['name'] . '-end',
                        'placeholder' => _x( 'To', 'date filter', 'arraypress' ),
                ] );
                ?>
			</span>
        </fieldset>
        <?php
    }

    /**
     * Render month and year selection field.
     *
     * Renders month and year dropdown fields using EDD's built-in
     * month and year dropdown functionality.
     *
     * @param array $field Field configuration array containing id, name, and optional labels.
     *
     * @return void
     */
    private function render_month_year_field( array $field ): void {
        ?>
        <fieldset class="edd-to-and-from-container">
            <legend class="screen-reader-text"><?php echo esc_html( $field['legend'] ?? '' ); ?></legend>
            <label for="<?php echo esc_attr( $field['id'] ); ?>_month"
                   class="screen-reader-text"><?php echo esc_html( $field['month_label'] ?? __( 'Select month', 'arraypress' ) ); ?></label>
            <?php echo EDD()->html->month_dropdown( $field['name'] . '_month', 0, $field['id'], true ); ?>
            <label for="<?php echo esc_attr( $field['id'] ); ?>_year"
                   class="screen-reader-text"><?php echo esc_html( $field['year_label'] ?? __( 'Select year', 'arraypress' ) ); ?></label>
            <?php echo EDD()->html->year_dropdown( $field['name'] . '_year', 0, 5, 0, $field['id'] ); ?>
        </fieldset>
        <?php
    }

    /**
     * Render separator element.
     *
     * Renders a visual separator element for organizing form fields.
     * Typically used between related field groups.
     *
     * @param array $field Field configuration array containing optional text.
     *
     * @return void
     */
    private function render_separator( array $field ): void {
        ?>
        <span class="edd-to-and-from--separator"><?php echo esc_html( $field['text'] ?? _x( '&mdash; to &mdash;', 'Date one to date two', 'arraypress' ) ); ?></span>
        <?php
    }

}