<?php
/**
 * Export Registration Helper Functions
 *
 * Provides simplified interfaces for registering EDD exporters, metaboxes and columns.
 *
 * @package     ArrayPress/EDD-Register-Exporters
 * @copyright   Copyright (c) 2024, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\EDD\Register\Export;

use Exception;

if ( ! function_exists( __NAMESPACE__ . '\\batch_exporters' ) ):
	/**
	 * Helper function to get the BatchExporters instance.
	 *
	 * @return BatchExporters
	 */
	function batch_exporters(): BatchExporters {
		return BatchExporters::instance();
	}
endif;

if ( ! function_exists( __NAMESPACE__ . '\\register_batch_exporter' ) ):
	/**
	 * Helper function to register a single batch exporter.
	 *
	 * @param string $key    Unique identifier for the export
	 * @param array  $export Export configuration
	 *
	 * @return bool
	 */
	function register_batch_exporter( string $key, array $export ): bool {
		return batch_exporters()->register_export( $key, $export );
	}
endif;

if ( ! function_exists( __NAMESPACE__ . '\\register_batch_exporters' ) ):
	/**
	 * Helper function to register multiple batch exporters.
	 *
	 * @param array       $exports   Array of export configurations
	 * @param string|null $base_path Optional base path for all exporters
	 *
	 * @return void
	 */
	function register_batch_exporters( array $exports, ?string $base_path = null ): void {
		$exporters = batch_exporters();

		if ( $base_path ) {
			$exporters->set_base_path( $base_path );
		}

		$exporters->register_exports( $exports );
		$exporters->init();
	}
endif;

if ( ! function_exists( __NAMESPACE__ . '\\metaboxes' ) ):
	/**
	 * Helper function to get the Metaboxes instance.
	 *
	 * @return Metaboxes
	 */
	function metaboxes(): Metaboxes {
		return Metaboxes::instance();
	}
endif;

if ( ! function_exists( __NAMESPACE__ . '\\register_metabox' ) ):
	/**
	 * Helper function to register a single export metabox.
	 *
	 * @param array $args Metabox configuration
	 *
	 * @return bool
	 */
	function register_metabox( array $args ): bool {
		return metaboxes()->register_metabox( $args );
	}
endif;

if ( ! function_exists( __NAMESPACE__ . '\\register_metaboxes' ) ):
	/**
	 * Helper function to register multiple export metaboxes.
	 *
	 * @param array       $metaboxes Array of metabox configurations
	 * @param string|null $base_path Optional base path for all metaboxes
	 *
	 * @return void
	 */
	function register_metaboxes( array $metaboxes, ?string $base_path = null ): void {
		$instance = metaboxes();

		if ( $base_path ) {
			$instance->set_base_path( $base_path );
		}

		$instance->register_metaboxes( $metaboxes );
		$instance->init();
	}
endif;

if ( ! function_exists( __NAMESPACE__ . '\\register_exporters' ) ):
	/**
	 * Helper function to register both metaboxes and their associated batch exporters.
	 *
	 * Example usage:
	 * ```php
	 * $metaboxes = [
	 *     [
	 *         'id'           => 'guest-verifications',
	 *         'title'        => 'Export Guest Verifications',
	 *         'description'  => 'Download a CSV of guest verification logs.',
	 *         'export_class' => 'GuestVerificationBatchExport',
	 *         'file'         => 'class-guest-verification-batch-export.php',
	 *         'fields'       => [
	 *             [
	 *                 'type'   => 'date',
	 *                 'id'     => 'guest-verification-dates',
	 *                 'name'   => 'guest-verification-export',
	 *                 'legend' => 'Select Date Range'
	 *             ]
	 *         ]
	 *     ]
	 * ];
	 *
	 * // Will automatically determine base path from plugin root
	 * register_exporters($metaboxes);
	 * ```
	 *
	 * @param array       $metaboxes Array of metabox configurations
	 * @param string|null $base_path Optional base path for exporter files
	 *
	 * @return array Array containing [Metaboxes, BatchExporters] instances
	 */
	function register_exporters( array $metaboxes, ?string $base_path = null ): array {
		// Initialize instances
		$metaboxes_instance = metaboxes();
		$exporters_instance = batch_exporters();

		// Set base paths
		$metaboxes_instance->set_base_path( $base_path );
		$exporters_instance->set_base_path( $base_path );

		// Extract batch exporters from metaboxes
		$exporters = [];
		foreach ( $metaboxes as $metabox ) {
			$metaboxes_instance->register_metabox( $metabox );

			if ( ! empty( $metabox['export_class'] ) && ! empty( $metabox['file'] ) ) {
				$exporters[ $metabox['id'] ] = [
					'class' => $metabox['export_class'],
					'file'  => $metabox['file'],
				];
			}
		}

		// Register and initialize both systems
		$exporters_instance->register_exports( $exporters );

		$metaboxes_instance->init();
		$exporters_instance->init();

		return [ $metaboxes_instance, $exporters_instance ];
	}
endif;


if ( ! function_exists( __NAMESPACE__ . '\\register_export_columns' ) ):
	/**
	 * Helper function to register custom columns for CSV exports.
	 *
	 * Example usage:
	 * ```php
	 * register_export_columns('downloads', [
	 *     'custom_column' => [
	 *         'label'    => 'Custom Field',
	 *         'callback' => function($id) {
	 *             return get_post_meta($id, 'custom_field', true);
	 *         }
	 *     ]
	 * ]);
	 * ```
	 *
	 * @param string $type     The type of CSV export (e.g., 'downloads', 'customers', etc.).
	 * @param array  $columns  An associative array of custom columns with their configurations.
	 * @param string $id_field The field to use as the identifier (default: 'ID').
	 *
	 * @return Columns|null Returns Columns instance or null if registration fails.
	 * @since 1.0.0
	 *
	 */
	function register_export_columns( string $type, array $columns, string $id_field = 'ID' ): ?Columns {
		try {
			return Columns::register( $type, $columns, $id_field );
		} catch ( Exception $e ) {
			return null;
		}
	}
endif;