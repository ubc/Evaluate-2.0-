<?php
/**
 * @wordpress-plugin
 * Plugin Name:       Evaluate
 * Plugin URI:        https://github.com/ubc/evaluate
 * Description:       A simple plugin which adds display logic for fields created by CMB2
 * Version:           2.0
 * Author:            Devindra Payment
 * Text Domain:       cmb2l
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 * GitHub Plugin URI: https://github.com/ubc/evaluate
 */

class Evaluate {
	public static $directory_path = '';
	public static $directory_url = '';
	public static $namespace = 'eval';

	public static $metric_table = 'evaluate_metrics';
	public static $voting_table = 'evaluate_votes';
	public static $scores_table = 'evaluate_scores';
	
	public static function init() {
		global $wpdb;
		self::$metric_table = $wpdb->prefix . self::$metric_table;
		self::$voting_table = $wpdb->prefix . self::$voting_table;
		self::$scores_table = $wpdb->prefix . self::$scores_table;

		self::$directory_path = plugin_dir_path( __FILE__ );
		self::$directory_url = plugin_dir_url( __FILE__ );
		
		register_activation_hook( __FILE__, array( __CLASS__, 'activate' ) );
		add_action( 'plugins_loaded', array( __CLASS__, 'load' ), 11 );
	}

	/**
	 * Load the plugin, if we meet requirements.
	 * @filter plugins_loaded
	 */
	public static function load() {
		require_once( self::$directory_path . 'includes/class-evaluate-api.php' );
		require_once( self::$directory_path . 'includes/class-evaluate-metrics.php' );
		require_once( self::$directory_path . 'includes/class-evaluate-voting.php' );

		if ( is_admin() ) {
			require_once( self::$directory_path . 'admin/class-evaluate-manager.php' );
			require_once( self::$directory_path . 'admin/class-evaluate-shortcodes.php' );
			require_once( self::$directory_path . 'admin/class-evaluate-metric-table.php' );
		}
		
		require_once( self::$directory_path . 'includes/class-evaluate-settings.php' );
	}

	public static function activate() {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		
		$sql = "CREATE TABLE " . self::$metric_table . " (
			metric_id bigint(11) NOT NULL AUTO_INCREMENT,
			name varchar(64) NOT NULL,
			type varchar(10) NOT NULL DEFAULT 'one-way',
			options blob NOT NULL,
			restrictions tinyint(1) NOT NULL DEFAULT '1',
			created timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
			modified timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (metric_id) );";
		
		dbDelta( $sql );
		
		// TODO: We need a way to store metric votes. Maybe they should be individually stored. Maybe add a 'field_id' column
		$sql = "CREATE TABLE " . self::$voting_table . " (
			metric_id bigint(11) NOT NULL,
			context_id varchar(40) NOT NULL,
			user_id varchar(40) NOT NULL,
			vote float NOT NULL,
			date timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (metric_id, context_id, user_id) );";
		
		dbDelta( $sql );
		
		$sql = "CREATE TABLE " . self::$scores_table . " (
			metric_id bigint(11) NOT NULL,
			context_id varchar(40) NOT NULL,
			count int(11) NOT NULL,
			value float NOT NULL,
			average float NOT NULL,
			data blob NOT NULL,
			PRIMARY KEY  (metric_id, context_id) );";
		
		dbDelta( $sql );
	}
}

Evaluate::init();
