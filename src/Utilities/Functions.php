<?php
/**
 * Helper functions for EDD Batch Exporters registration
 *
 * @package     ArrayPress\EDD\Register
 * @copyright   Copyright 2024, ArrayPress Limited
 * @license     GPL-2.0-or-later
 * @version     1.0.0
 */

declare( strict_types=1 );

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

use ArrayPress\EDD\Register\Exporters;

if ( ! function_exists( 'edd_register_custom_exporter' ) ):
	/**
	 * Register a single custom batch exporter for EDD.
	 *
	 * Convenience function for registering a single batch exporter with optional metabox form.
	 *
	 * Example usage:
	 * ```php
	 * edd_register_custom_batch_exporter(
	 *     'customer-analytics',
	 *     [
	 *         'class'       => 'EDD_Customer_Analytics_Export',
	 *         'file'        => 'exports/class-customer-analytics.php',
	 *         'title'       => 'Customer Analytics Export',
	 *         'description' => 'Export detailed customer analytics and behavior data.',
	 *         'fields'      => [
	 *             [
	 *                 'type'   => 'date',
	 *                 'id'     => 'analytics_date',
	 *                 'name'   => 'date',
	 *                 'legend' => 'Select date range'
	 *             ]
	 *         ]
	 *     ],
	 *     __DIR__
	 * );
	 * ```
	 *
	 * @param string      $key       Unique identifier for the export.
	 * @param array       $export    Export configuration.
	 * @param string|null $base_path Optional base path for export file.
	 *
	 * @return bool|\WP_Error True on success, WP_Error on failure.
	 * @since 1.0.0
	 */
	function edd_register_custom_exporter( string $key, array $export, ?string $base_path = null ) {
		return edd_register_custom_exporters( [ $key => $export ], $base_path );
	}
endif;

if ( ! function_exists( 'edd_register_custom_exporters' ) ):
	/**
	 * Register multiple custom batch exporters for EDD.
	 *
	 * This is the primary function for registering multiple batch exporters with optional metabox forms.
	 * It creates a new BatchExporters instance, registers the exports, and initializes the system.
	 *
	 * Example usage:
	 * ```php
	 * edd_register_custom_batch_exporters( [
	 *     'customer-analytics' => [
	 *         'class'       => 'EDD_Customer_Analytics_Export',
	 *         'file'        => 'exports/class-customer-analytics.php',
	 *         'title'       => 'Customer Analytics Export',
	 *         'description' => 'Export detailed customer analytics and behavior data.',
	 *         'fields'      => [
	 *             [
	 *                 'type'   => 'date',
	 *                 'id'     => 'analytics_date',
	 *                 'name'   => 'date',
	 *                 'legend' => 'Select date range'
	 *             ]
	 *         ]
	 *     ]
	 * ], __DIR__ );
	 * ```
	 *
	 * @param array       $exports   An associative array of batch exporters.
	 * @param string|null $base_path Optional base path for export files.
	 *
	 * @return bool|\WP_Error True on success, WP_Error on failure.
	 * @since 1.0.0
	 */
	function edd_register_custom_exporters( array $exports, ?string $base_path = null ) {
		$manager = new Exporters( $base_path );
		$result  = $manager->register( $exports );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$manager->init();

		return true;
	}
endif;