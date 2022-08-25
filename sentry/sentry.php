<?php
$nse_sentry_options = NSE_Sentry::get_instance()->options;

define( 'WP_SENTRY_PHP_DSN', $nse_sentry_options['dsn_php_key'] );
define( 'WP_SENTRY_SEND_DEFAULT_PII', true );
define( 'WP_SENTRY_BROWSER_DSN', $nse_sentry_options['dsn_browser_key'] );
define( 'WP_SENTRY_BROWSER_TRACES_SAMPLE_RATE', 0.3 );

if ( file_exists( ABSPATH . 'wp-content/plugins/wp-sentry-integration/wp-sentry.php' ) ) {
	require_once ABSPATH . 'wp-content/plugins/wp-sentry-integration/wp-sentry.php';
}

add_action( 'wp_error_added', 'sentry_error_logging', PHP_INT_MAX, 4 );
function sentry_error_logging( $code, $message, $data, $error ) {
	if ( 'rest_cookie_invalid_nonce' === $code ) {
		$ex = new \Exception( $message, $data['status'] );
		if ( function_exists( 'wp_sentry_safe' ) ) {
			wp_sentry_safe(
				function ( \Sentry\State\HubInterface $client ) use ( $ex ) {
					$client->captureException( $ex );
				}
			);
		}
	}
}


add_action(
	'wp_enqueue_scripts',
	function () {
		wp_add_inline_script(
			'wp-sentry-browser',
			'function wp_sentry_hook(options) {
		options.integrations
        console.log({options})
    }',
			'before'
		);
	}
);

// add_filter('wp_sentry_public_options', function($options){
// $options['integrations'] = [
// 'breadcrumbs' => [
// 'fetch' => true,
// 'xhr' => true
// ]];
// $options['maxBreadcrumbs'] = 50;
// return $options;
// }, 10, 1);

// Setting For Sentry

final class NSE_Sentry {
	protected static $instance = null;
	public $setting_prefix     = null;
	public $options            = array();

	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
			self::$instance->do_hooks();
		}
		return self::$instance;
	}

	public function do_hooks() {
		add_action( 'admin_init', array( $this, 'setting_init' ) );
		add_action( 'admin_menu', array( $this, 'setting_page' ) );
		$this->options = wp_parse_args(
			get_option( 'nse_sentry_options', array() ),
			array(
				'dsn_php_key'     => '',
				'dsn_browser_key' => '',
			)
		);
	}

	public function setting_init() {
		add_settings_section(
			'nse_sentry_key_section',
			__( 'Sentry Client Keys.', 'nse_sentry' ),
			array( $this, 'key_section_callback' ),
			'nse_sentry'
		);

		register_setting( 'nse_sentry', 'nse_sentry_options' );

		add_settings_field(
			'dsn_php_key',
			__( 'DSN PHP Key:', 'nse_sentry' ),
			array( $this, 'dsn_php_html' ),
			'nse_sentry',
			'nse_sentry_key_section',
			array(
				'label_for' => 'dsn_php_key',
				'class'     => 'regular-text',
			)
		);

		add_settings_field(
			'dsn_browser_key',
			__( 'DSN Browser Key:', 'nse_sentry' ),
			array( $this, 'dsn_php_html' ),
			'nse_sentry',
			'nse_sentry_key_section',
			array(
				'label_for' => 'dsn_browser_key',
				'class'     => 'regular-text',
			)
		);
	}

	public function setting_page() {
		$this->setting_prefix = add_menu_page(
			'Sentry.io Setting',
			'Sentry.io',
			'manage_options',
			'sentry',
			array( $this, 'setting_html' )
		);
	}


	public function setting_html() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( isset( $_GET['settings-updated'] ) ) {
			add_settings_error( 'nse_sentry_messages', 'nse_sentry_messages', __( 'Settings Saved', 'nse_sentry' ), 'updated' );
		}

		settings_errors( 'nse_sentry_messages' );
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( 'nse_sentry' );
				do_settings_sections( 'nse_sentry' );
				submit_button( 'Save Settings' );
				?>
			</form>
		</div>
		<?php
	}

	public function key_section_callback( $args ) {
		?>
		<p id="<?php echo esc_attr( $args['id'] ); ?>"><?php esc_html_e( 'Sentry Client Keys.', 'nse_sentry' ); ?></p>
		<?php
	}

	public function dsn_php_html( $args ) {
		?>
		<input 
			id="<?php esc_attr_e( $args['label_for'] ); ?>"
			name="nse_sentry_options[<?php esc_attr_e( $args['label_for'] ); ?>]" 
			type="text" 
			value="<?php esc_attr_e( $this->options[ $args['label_for'] ] ); ?>" 
			class="<?php esc_attr_e( $args['class'] ); ?>"
		>
		<?php
	}
}
// Setting For Sentry

