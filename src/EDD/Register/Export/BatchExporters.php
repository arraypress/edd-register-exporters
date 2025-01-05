<?php
/**
 * Batch Export Helper for Easy Digital Downloads (EDD)
 *
 * @package       ArrayPress/EDD-Utils
 * @copyright     Copyright (c) 2024, ArrayPress Limited
 * @license       GPL2+
 * @since         1.0.0
 * @author        David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\EDD\Register\Export;

/**
 * Class BatchExporters
 *
 * @package ArrayPress\EDD\Register\Export
 * @since   1.0.0
 */
class BatchExporters {

	/**
	 * Instance of this class.
	 *
	 * @since 1.0.0
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * An associative array of batch exports with their configurations.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private array $exports = [];

	/**
	 * The base path for export files.
	 *
	 * @since 1.0.0
	 * @var string|null
	 */
	private ?string $base_path;

	/**
	 * BatchExporters constructor.
	 *
	 * @param string|null $base_path Optional base path for export files.
	 *
	 * @since 1.0.0
	 *
	 */
	private function __construct( ?string $base_path = null ) {
		$this->base_path = $base_path;
	}

	/**
	 * Get instance of this class.
	 *
	 * @return self Instance of this class.
	 * @since 1.0.0
	 *
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Register a single batch export.
	 *
	 * @param string $key    Unique identifier for the export.
	 * @param array  $export Export configuration.
	 *
	 * @return bool True if registered successfully, false otherwise.
	 * @since 1.0.0
	 *
	 */
	public function register_export( string $key, array $export ): bool {
		if ( empty( $key ) || empty( $export['class'] ) || empty( $export['file'] ) ) {
			return false;
		}

		$this->exports[ $key ] = $this->validate_export_config( $export );

		return true;
	}

	/**
	 * Register multiple batch exports.
	 *
	 * @param array $exports Array of export configurations.
	 *
	 * @return void
	 * @since 1.0.0
	 *
	 */
	public function register_exports( array $exports ): void {
		foreach ( $exports as $key => $export ) {
			$this->register_export( $key, $export );
		}
	}

	/**
	 * Remove a registered export.
	 *
	 * @param string $key The export key to remove.
	 *
	 * @return bool True if removed, false if not found.
	 * @since 1.0.0
	 *
	 */
	public function remove_export( string $key ): bool {
		if ( isset( $this->exports[ $key ] ) ) {
			unset( $this->exports[ $key ] );

			return true;
		}

		return false;
	}

	/**
	 * Get all registered exports.
	 *
	 * @return array Array of registered exports.
	 * @since 1.0.0
	 *
	 */
	public function get_exports(): array {
		return $this->exports;
	}

	/**
	 * Get a specific registered export.
	 *
	 * @param string $key Export key.
	 *
	 * @return array|null Export configuration or null if not found.
	 * @since 1.0.0
	 *
	 */
	public function get_export( string $key ): ?array {
		return $this->exports[ $key ] ?? null;
	}

	/**
	 * Set the base path for export files.
	 *
	 * @param string|null $base_path The base path to set.
	 *
	 * @return void
	 * @since 1.0.0
	 *
	 */
	public function set_base_path( ?string $base_path ): void {
		$this->base_path = $base_path;
	}

	/**
	 * Initialize the batch exports system.
	 *
	 * @return void
	 * @since 1.0.0
	 *
	 */
	public function init(): void {
		add_action( 'edd_batch_export_class_include', [ $this, 'include_export_class' ] );
	}

	/**
	 * Include the batch export class file.
	 *
	 * @param string $class The class being requested to run for the batch export.
	 *
	 * @return void
	 * @since 1.0.0
	 *
	 */
	public function include_export_class( string $class ): void {
		foreach ( $this->exports as $export ) {
			if ( $export['class'] === $class && ! empty( $export['file'] ) ) {
				$file = $this->get_full_file_path( $export['file'] );
				if ( file_exists( $file ) ) {
					require_once $file;

					return;
				}
			}
		}
	}

	/**
	 * Validate and normalize export configuration.
	 *
	 * @param array $export Export configuration to validate.
	 *
	 * @return array Validated and normalized export configuration.
	 * @since 1.0.0
	 *
	 */
	private function validate_export_config( array $export ): array {
		return wp_parse_args( $export, [
			'class' => '',
			'file'  => '',
		] );
	}

	/**
	 * Get the full file path.
	 *
	 * @param string $file The file path.
	 *
	 * @return string The full file path.
	 * @since 1.0.0
	 *
	 */
	private function get_full_file_path( string $file ): string {
		return $this->base_path ? trailingslashit( $this->base_path ) . $file : $file;
	}

	/**
	 * Static helper method to create and register batch exports.
	 *
	 * @param array       $exports   An associative array of batch exports with their configurations.
	 * @param string|null $base_path Optional base path for export files.
	 *
	 * @return self
	 * @since 1.0.0
	 *
	 */
	public static function register( array $exports, ?string $base_path = null ): self {
		$instance = self::instance();

		if ( $base_path ) {
			$instance->set_base_path( $base_path );
		}

		$instance->register_exports( $exports );
		$instance->init();

		return $instance;
	}

}