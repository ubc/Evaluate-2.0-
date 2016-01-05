<?php

class Evaluate_Settings {

	private static $page_slug = 'evaluate_settings';
	private static $allow_anonymous = 'evaluate_allow_anonymous';
	private static $disable_stylesheet = 'evaluate_disabled_stylesheet';

	public static function init() {
		if ( is_admin() ) {
			add_action( 'admin_menu', array( __CLASS__, 'create_page' ) );
			add_action( 'admin_init', array( __CLASS__, 'register_mysettings' ) );
		}
	}

	public static function create_page() {
		add_submenu_page(
			'evaluate',
			"Evaluate Settings",
			"Settings",
			'manage_options',
			self::$page_slug,
			array( __CLASS__, 'render_page' )
		);
	}

	public static function register_mysettings() {
		register_setting( 'evaluate_options', self::$allow_anonymous );

		add_settings_section( 
			'evaluate_general',
			"General",
			array( __CLASS__, 'render_general' ),
			self::$page_slug
		);

		add_settings_field(
			self::$allow_anonymous,
			"Allow Anonymous Users to Vote",
			array( __CLASS__, 'render_general_allow_anonymous' ),
			self::$page_slug,
			'evaluate_general'
		);

		add_settings_field(
			self::$disable_stylesheet,
			"Disable the default stylesheet",
			array( __CLASS__, 'render_general_disable_stylesheet' ),
			self::$page_slug,
			'evaluate_general'
		);
	}

	public static function render_page() {
		?>
		<div class="wrap">
			<h2>Evaluate Settings</h2>

			<form method="post" action="options.php"> 
				<?php
				settings_fields( 'evaluate_options' );

				do_settings_sections( self::$page_slug );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	public static function render_general() {
		// Do nothing
	}

	public static function render_general_allow_anonymous() {
		$value = self::are_anonymous_votes_allowed();
		?>
		<input name="<?php echo self::$allow_anonymous; ?>" type="checkbox" value="on" <?php checked( $value, true ); ?>></input>
		<?php
	}

	public static function render_general_disable_stylesheet() {
		$value = self::is_stylesheet_enabled();
		?>
		<input name="<?php echo self::$disable_stylesheet; ?>" type="checkbox" value="on" <?php checked( $value, false ); ?>></input>
		<?php
	}

	public static function are_anonymous_votes_allowed() {
		return get_option( self::$allow_anonymous, false ) == 'on';
	}

	public static function is_stylesheet_enabled() {
		return get_option( self::$allow_anonymous, 'on' ) == 'on';
	}

}

Evaluate_Settings::init();
