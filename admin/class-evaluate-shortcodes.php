<?php

class Evaluate_Shortcodes {

	public static $metric_table;

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'create_pages' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'register_scripts_and_styles' ) );
	}

	public static function create_pages() {
		add_submenu_page(
			'evaluate',
			"Evaluate Shortcodes",
			"Shortcodes",
			'manage_options',
			'evaluate_shortcode',
			array( __CLASS__, 'render_page' )
		);
	}

	public static function register_scripts_and_styles() {
		//wp_register_script( 'evaluate-admin', Evaluate::$directory_url . "js/evaluate-admin.js", array( 'jquery' ) );
	}

	public static function render_page() {
		?>
		<div class="wrap">
			<h2>Evaluate Shortcodes</h2>

			This plugin provides a shortcode so that you can embed your metric in locations that are not naturally supported (such as widgets, other plugins, or inline in a post).
			<dl>
				<dt>ID</dt>
				<dd>
					<select>
						<option value=""> - Choose a Metric - </option>
						<?php
						$metrics = Evaluate_Metrics::get_metrics();

						foreach ( $metrics as $key => $metric ) {
							?>
							<option value="<?php echo $metric['metric_id']; ?>"><?php echo $metric['name']; ?></option>
							<?php
						}
						?>
					</select>
					<br>
					<small>Indicate which metric you want to embed using this shortcode</small>
				</dd>
				<dt>Key</dt>
				<dd>
					<input type="text"></input>
					<p><small>This parameter defines a unique key for the shortcode. A user will only be able two vote once for every unique key, even if it is embedded multiple times. <u>For example</u>: if you are embedding on two different posts, you would want to use two different keys, so that the user can vote differently for each post. On the other hand, if you are creating a feedback rating for the website, you would want to use the same key in all locations, so that the user only has 1 rating for the website.</small></p>
					<p><small>
						You can create your own key, or use a special option:
						<br><strong>%post_id%</strong>  inserts the id of whichever post is currently being viewed.
						<br><strong>%date%</strong>  inserts the current day. Meaning the user will be able to make a new vote on the next day, and will no longer be able to change their old vote.
						<br><strong>%url%</strong>  inserts a unique id for the current url.
						<br>When creating your own key, use a meaningful word, so that you can recognize it when viewing the metric data.
					</small></p>
				</dd>
			</dl>

			<div>Your shortcode is</div>
			<code>[metric id="" key=""]</code>
		</div>
		<?php
	}

}

Evaluate_Shortcodes::init();
