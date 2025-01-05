<?php
/**
 * Export Columns Class
 *
 * @package       ArrayPress/EDD-Register-Exporters
 * @copyright     Copyright (c) 2024, ArrayPress Limited
 * @license       GPL2+
 * @version       1.0.0
 * @author        David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\EDD\Register\Export;

use Exception;

/**
 * Class Columns
 *
 * Manages custom columns for EDD CSV exports.
 *
 * @package ArrayPress\EDD\Register\Export
 * @since   1.0.0
 */
class Columns {

	/**
	 * Instance of this class.
	 *
	 * @since 1.0.0
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * The type of CSV export (e.g., 'downloads', 'customers', etc.).
	 *
	 * This determines which EDD export filters to hook into.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private string $type;

	/**
	 * An associative array of custom columns with their configurations.
	 *
	 * Each element should be in the format:
	 * 'column_key' => [
	 *     'label' => 'Column Label',
	 *     'callback' => callable
	 * ]
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private array $columns;

	/**
	 * The field to use as the identifier for each row in the export data.
	 *
	 * This is typically 'ID' or 'id', but can be customized if needed.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private string $id_field;

	/**
	 * Columns constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param string $type     The type of CSV export (e.g., 'downloads', 'customers', etc.).
	 * @param array  $columns  An associative array of custom columns with their configurations.
	 * @param string $id_field The field to use as the identifier (default: 'ID').
	 *
	 * @throws Exception If an invalid or empty array is passed.
	 */
	private function __construct( string $type, array $columns, string $id_field = 'ID' ) {
		if ( empty( $type ) || empty( $columns ) ) {
			throw new Exception( 'Invalid type or empty columns array provided.' );
		}

		$this->type     = $type;
		$this->columns  = $columns;
		$this->id_field = $id_field;
	}

	/**
	 * Get instance of this class.
	 *
	 * @since 1.0.0
	 *
	 * @return self Instance of this class.
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initialize the columns registration.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function init(): void {
		$this->setup_hooks();
	}

	/**
	 * Register custom columns for the CSV export.
	 *
	 * @since 1.0.0
	 *
	 * @param array $cols An array of existing columns for the CSV export.
	 *
	 * @return array An updated array of columns with the custom columns added.
	 */
	public function register_csv_columns( array $cols ): array {
		foreach ( $this->columns as $key => $column ) {
			$cols[ $key ] = $column['label'] ?? '';
		}

		return $cols;
	}

	/**
	 * Filter the CSV data to add custom columns.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data The data for the CSV export.
	 *
	 * @return array The modified data for the CSV export.
	 */
	public function filter_csv_data( array $data ): array {
		foreach ( $data as $key => $row ) {
			$id = $this->get_row_id( $row );
			foreach ( $this->columns as $column_key => $column ) {
				if ( isset( $column['callback'] ) && is_callable( $column['callback'] ) ) {
					$data[ $key ][ $column_key ] = call_user_func( $column['callback'], $id );
				}
			}
		}

		return $data;
	}

	/**
	 * Get the ID value from a row.
	 *
	 * @since 1.0.0
	 *
	 * @param array $row The row data.
	 *
	 * @return mixed The ID value.
	 */
	private function get_row_id( array $row ) {
		if ( isset( $row[ $this->id_field ] ) ) {
			return $row[ $this->id_field ];
		}

		// Check for 'ID' if the specified field doesn't exist
		if ( $this->id_field !== 'ID' && isset( $row['ID'] ) ) {
			return $row['ID'];
		}

		// Check for 'id' if neither specified field nor 'ID' exist
		if ( $this->id_field !== 'id' && isset( $row['id'] ) ) {
			return $row['id'];
		}

		// If no ID field is found, return null
		return null;
	}

	/**
	 * Setup the necessary hooks for custom CSV columns.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function setup_hooks(): void {
		add_filter( "edd_export_csv_cols_{$this->type}", [ $this, 'register_csv_columns' ] );
		add_filter( "edd_export_get_data_{$this->type}", [ $this, 'filter_csv_data' ] );
	}

	/**
	 * Register custom columns for CSV export.
	 *
	 * @since 1.0.0
	 *
	 * @param string $type     The type of CSV export (e.g., 'downloads', 'customers', etc.).
	 * @param array  $columns  An associative array of custom columns with their configurations.
	 * @param string $id_field The field to use as the identifier (default: 'ID').
	 *
	 * @return self
	 * @throws Exception If invalid parameters are provided.
	 */
	public static function register( string $type, array $columns, string $id_field = 'ID' ): self {
		$instance = new self( $type, $columns, $id_field );
		$instance->init();

		return $instance;
	}
}