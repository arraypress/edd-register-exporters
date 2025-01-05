<?php
/**
 * Export Metaboxes Class
 *
 * @package       ArrayPress/EDD-Utils
 * @copyright     Copyright 2024, ArrayPress Limited
 * @license       GPL-2.0-or-later
 * @version       1.0.0
 * @author        David Sherlock
 */

namespace ArrayPress\EDD\Register\Export;

/**
 * Class Metaboxes
 *
 * @package ArrayPress\EDD\Register\Export
 * @since   1.0.0
 */
class Metaboxes {

	/**
	 * Instance of this class.
	 *
	 * @since 1.0.0
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Array of registered metaboxes.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private array $metaboxes = [];

	/**
	 * Base path for export class files.
	 *
	 * @since 1.0.0
	 * @var string|null
	 */
	private ?string $base_path;

	/**
	 * Constructor
	 *
	 * @param string|null $base_path Optional base path for export class files.
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
	 * Initialize the metaboxes system.
	 *
	 * @return void
	 * @since 1.0.0
	 *
	 */
	public function init(): void {
		add_action( 'edd_reports_tab_export_content_bottom', [ $this, 'render_metaboxes' ] );
	}

	/**
	 * Set the base path for export class files.
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
	 * Register a new export metabox.
	 *
	 * @param array $args Metabox arguments.
	 *
	 * @return bool True if registered successfully, false otherwise.
	 * @since 1.0.0
	 *
	 */
	public function register_metabox( array $args ): bool {
		$metabox = $this->validate_metabox( $args );

		if ( empty( $metabox['id'] ) || empty( $metabox['export_class'] ) ) {
			return false;
		}

		// Verify export class file exists if specified
		if ( ! empty( $metabox['file'] ) ) {
			$file = $this->get_full_file_path( $metabox['file'] );
			if ( ! file_exists( $file ) ) {
				return false;
			}
		}

		$this->metaboxes[ $metabox['id'] ] = $metabox;

		return true;
	}

	/**
	 * Register multiple metaboxes at once.
	 *
	 * @param array $metaboxes Array of metabox configurations.
	 *
	 * @return void
	 * @since 1.0.0
	 *
	 */
	public function register_metaboxes( array $metaboxes ): void {
		foreach ( $metaboxes as $metabox ) {
			$this->register_metabox( $metabox );
		}
	}

	/**
	 * Remove a registered metabox.
	 *
	 * @param string $id The metabox ID to remove.
	 *
	 * @return bool True if removed, false if not found.
	 * @since 1.0.0
	 *
	 */
	public function remove_metabox( string $id ): bool {
		if ( isset( $this->metaboxes[ $id ] ) ) {
			unset( $this->metaboxes[ $id ] );

			return true;
		}

		return false;
	}

	/**
	 * Get all registered metaboxes.
	 *
	 * @return array Array of registered metaboxes.
	 * @since 1.0.0
	 *
	 */
	public function get_metaboxes(): array {
		return $this->metaboxes;
	}

	/**
	 * Get a specific registered metabox.
	 *
	 * @param string $id Metabox ID.
	 *
	 * @return array|null Metabox configuration or null if not found.
	 * @since 1.0.0
	 *
	 */
	public function get_metabox( string $id ): ?array {
		return $this->metaboxes[ $id ] ?? null;
	}

	/**
	 * Validate and normalize metabox configuration.
	 *
	 * @param array $args Metabox configuration to validate.
	 *
	 * @return array Validated and normalized metabox configuration.
	 * @since 1.0.0
	 *
	 */
	private function validate_metabox( array $args ): array {
		return wp_parse_args( $args, [
			'id'           => '',
			'title'        => '',
			'description'  => '',
			'fields'       => [],
			'export_class' => '',
			'file'         => ''
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
	 * Render all registered metaboxes.
	 *
	 * @return void
	 * @since 1.0.0
	 *
	 */
	public function render_metaboxes(): void {
		foreach ( $this->metaboxes as $metabox ) {
			$this->render_single_metabox( $metabox );
		}
	}

	/**
	 * Render a single metabox.
	 *
	 * @param array $metabox Metabox configuration.
	 *
	 * @return void
	 * @since 1.0.0
	 *
	 */
	private function render_single_metabox( array $metabox ): void {
		?>
        <div class="postbox edd-export-<?php echo esc_attr( $metabox['id'] ); ?>">
            <h2 class="hndle"><span><?php echo esc_html( $metabox['title'] ); ?></span></h2>
            <div class="inside">
                <p><?php echo esc_html( $metabox['description'] ); ?></p>
                <form id="edd-export-<?php echo esc_attr( $metabox['id'] ); ?>"
                      class="edd-export-form edd-import-export-form" method="post">
					<?php
					foreach ( $metabox['fields'] as $field ) {
						$this->render_field( $field );
					}
					wp_nonce_field( 'edd_ajax_export', 'edd_ajax_export' );
					?>
                    <input type="hidden" name="edd-export-class"
                           value="<?php echo esc_attr( $metabox['export_class'] ); ?>"/>
                    <button type="submit"
                            class="button button-secondary"><?php esc_html_e( 'Generate CSV', 'edd-register-exporters' ); ?></button>
                </form>
            </div>
        </div>
		<?php
	}

	/**
	 * Render a single field.
	 *
	 * @param array $field Field configuration.
	 *
	 * @return void
	 * @since 1.0.0
	 *
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

		unset( $field['desc'], $field['class'] );

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
	 * Render a product field.
	 *
	 * @param array $field Field configuration.
	 *
	 * @return void
	 * @since 1.0.0
	 *
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
	 * Render a customer field.
	 *
	 * @param array $field Field configuration.
	 *
	 * @return void
	 * @since 1.0.0
	 *
	 */
	private function render_customer_field( array $field ): void {
		$defaults = [
			'id'            => 'edd_export_customer',
			'name'          => 'customer_id',
			'chosen'        => true,
			'multiple'      => false,
			'none_selected' => '',
			'placeholder'   => __( 'All Customers', 'edd-register-exporters' ),
		];

		$args = wp_parse_args( $field, $defaults );

		echo EDD()->html->customer_dropdown( $args );
	}

	/**
	 * Render a country field.
	 *
	 * @param array $field Field configuration.
	 *
	 * @return void
	 * @since 1.0.0
	 *
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
	 * Render a region field.
	 *
	 * @param array $field Field configuration.
	 *
	 * @return void
	 * @since 1.0.0
	 *
	 */
	private function render_region_field( array $field ): void {
		$defaults = [
			'id'          => 'edd_reports_filter_regions',
			'placeholder' => __( 'All Regions', 'edd-register-exporters' ),
		];

		$args = wp_parse_args( $field, $defaults );

		echo EDD()->html->region_select( $args );
	}

	/**
	 * Render an order statuses field.
	 *
	 * @param array $field Field configuration.
	 *
	 * @return void
	 * @since 1.0.0
	 *
	 */
	private function render_order_statuses_field( array $field ): void {
		$defaults = [
			'id'               => 'edd_export_status',
			'name'             => 'status',
			'show_option_all'  => __( 'All Statuses', 'edd-register-exporters' ),
			'show_option_none' => false,
			'selected'         => false,
			'options'          => edd_get_payment_statuses(),
		];

		$args = wp_parse_args( $field, $defaults );

		echo EDD()->html->select( $args );
	}

	/**
	 * Render a date field.
	 *
	 * @param array $field Field configuration.
	 *
	 * @return void
	 * @since 1.0.0
	 *
	 */
	private function render_date_field( array $field ): void {
		?>
        <fieldset class="edd-from-to-wrapper">
            <legend class="screen-reader-text"><?php echo esc_html( $field['legend'] ?? '' ); ?></legend>
            <label for="<?php echo esc_attr( $field['id'] ); ?>-start"
                   class="screen-reader-text"><?php esc_html_e( 'Set start date', 'edd-register-exporters' ); ?></label>
            <span id="edd-<?php echo esc_attr( $field['id'] ); ?>-start-wrap">
            <?php
            echo EDD()->html->date_field( [
	            'id'          => $field['id'] . '-start',
	            'class'       => 'edd-export-start',
	            'name'        => $field['name'] . '-start',
	            'placeholder' => _x( 'From', 'date filter', 'edd-register-exporters' ),
            ] );
            ?>
        </span>
            <label for="<?php echo esc_attr( $field['id'] ); ?>-end"
                   class="screen-reader-text"><?php esc_html_e( 'Set end date', 'edd-register-exporters' ); ?></label>
            <span id="edd-<?php echo esc_attr( $field['id'] ); ?>-end-wrap">
            <?php
            echo EDD()->html->date_field( [
	            'id'          => $field['id'] . '-end',
	            'class'       => 'edd-export-end',
	            'name'        => $field['name'] . '-end',
	            'placeholder' => _x( 'To', 'date filter', 'edd-register-exporters' ),
            ] );
            ?>
        </span>
        </fieldset>
		<?php
	}

	/**
	 * Render a month and year dropdown field.
	 *
	 * @param array $field Field configuration.
	 *
	 * @return void
	 * @since 1.0.0
	 *
	 */
	private function render_month_year_field( array $field ): void {
		?>
        <fieldset class="edd-to-and-from-container">
            <legend class="screen-reader-text"><?php echo esc_html( $field['legend'] ?? '' ); ?></legend>
            <label for="<?php echo esc_attr( $field['id'] ); ?>_month"
                   class="screen-reader-text"><?php echo esc_html( $field['month_label'] ?? __( 'Select month', 'edd-register-exporters' ) ); ?></label>
			<?php echo EDD()->html->month_dropdown( $field['name'] . '_month', 0, $field['id'], true ); ?>
            <label for="<?php echo esc_attr( $field['id'] ); ?>_year"
                   class="screen-reader-text"><?php echo esc_html( $field['year_label'] ?? __( 'Select year', 'edd-register-exporters' ) ); ?></label>
			<?php echo EDD()->html->year_dropdown( $field['name'] . '_year', 0, 5, 0, $field['id'] ); ?>
        </fieldset>
		<?php
	}

	/**
	 * Render a separator.
	 *
	 * @param array $field Field configuration.
	 *
	 * @return void
	 * @since 1.0.0
	 *
	 */
	private function render_separator( array $field ): void {
		?>
        <span class="edd-to-and-from--separator"><?php echo esc_html( $field['text'] ?? _x( '&mdash; to &mdash;', 'Date one to date two', 'edd-register-exporters' ) ); ?></span>
		<?php
	}

	/**
	 * Static helper method to create and register metaboxes.
	 *
	 * @param array       $metaboxes Array of metabox configurations.
	 * @param string|null $base_path Optional base path for export class files.
	 *
	 * @return self
	 * @since 1.0.0
	 *
	 */
	public static function register( array $metaboxes, ?string $base_path = null ): self {
		$instance = self::instance();

		if ( $base_path ) {
			$instance->set_base_path( $base_path );
		}

		$instance->register_metaboxes( $metaboxes );
		$instance->init();

		return $instance;
	}

}