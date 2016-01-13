<?php

class Evaluate_Manager {

	public static $nonce_key = 'evaluate_edit_nonce';

	public static function init() {
		add_filter( 'set-screen-option', array( __CLASS__, 'set_screen' ), 10, 3 );
		add_action( 'admin_menu', array( __CLASS__, 'create_pages' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'register_scripts_and_styles' ) );
		
		self::save_post_data();
		//add_action( 'get_header', array( __CLASS__, 'save_post_data' ) );
	}

	public static function create_pages() {
		add_menu_page(
			'Evaluate',
			'Evaluate',
			'manage_options',
			'evaluate'
		);

		$hook = add_submenu_page(
			'evaluate',
			'Evaluate Metrics',
			'All Metrics',
			'manage_options',
			'evaluate',
			array( __CLASS__, 'render_list_page' )
		);

		add_action( "load-" . $hook, array( __CLASS__, 'set_screen_options' ) );

		add_submenu_page(
			'evaluate',
			"Add New Metric",
			"Add New",
			'manage_options',
			'evaluate_edit',
			array( __CLASS__, 'render_new_page' )
		);
	}

	public static function register_scripts_and_styles() {
		wp_register_script( 'evaluate-admin', Evaluate::$directory_url . "js/evaluate-admin.js", array( 'jquery' ) );
	}

	public static function set_screen( $status, $option, $value ) {
		return $value;
	}

	public static function set_screen_options() {
		add_screen_option( 'per_page', array(
			'label'   => 'Metrics',
			'default' => 5,
			'option'  => 'metrics_per_page'
		) );
	}

	public static function render_list_page() {
		?>
		<div class="wrap">
			<h1>
				Evaluate Metrics
				<a href="<?php echo site_url('/wp-admin/admin.php?page=evaluate_edit'); ?>" class="page-title-action">Add New</a>
			</h1>

			<div id="poststuff">
				<div id="post-body" class="metabox-holder columns-2">
					<div id="post-body-content">
						<div class="meta-box-sortables ui-sortable">
							<form method="post">
								<?php
								$table = new Evaluate_Metric_table();
								$table->prepare_items();
								$table->display();
								?>
							</form>
						</div>
					</div>
				</div>
				<br class="clear">
			</div>
		</div>
		<?php
	}

	// TODO: Add a nonce field.
	public static function render_new_page() {
		wp_enqueue_script( 'evaluate-admin' );

		$metric_types = Evaluate_Metrics::get_metric_types();

		if ( isset( $_GET['metric_id'] ) ) {
			$metric_id = $_GET['metric_id'];
			$metric = Evaluate_Metrics::get_metrics( array( $metric_id ) )[0];
		} else {
			$metric_id = "";
			$metric = array();
		}
		
		$metric = shortcode_atts( array(
			'name' => "",
			'type' => "",
			'options' => array(
				'title' => "",
			),
		), $metric );

		var_dump( $metric );
		?>
		<div class="wrap">
			<h2><?php echo empty( $metric_id ) ? "Create New Metric" : "Edit Metric"; ?></h2>

			<form method="POST">
				<input type="hidden" name="action" value="save"></input>
				<input type="hidden" name="metric_id" value="<?php echo $metric_id; ?>"></input>
				<dl>
					<dt><label for="name">Name</label></dt>
					<dd><input name="name" value="<?php echo $metric['name']; ?>" autocomplete="off"></input></dd>
					<dt><label for="type">Type</label></dt>
					<dd>
						<select id="type" class="nav" data-anchor="options" name="type">
							<option value=""> - Choose a Type - </option>
							<?php
							foreach ( $metric_types as $slug => $metric_type ) {
								?>
								<option value="<?php echo $slug; ?>" <?php selected( $slug, $metric['type'] ); ?>>
									<?php echo $metric_type->name; ?>
								</option>
								<?php
							}
							?>
						</select>
					</dd>
					<dt><label for="options[title]">Display Title</label></dt>
					<dd><input name="options[title]" value="<?php echo $metric['options']['title']; ?>" autocomplete="off"></input></dd>
				</dl>
				<?php
				foreach ( $metric_types as $slug => $metric_type ) {
					?>
					<dl class="options-<?php echo $slug; ?> options"<?php echo $slug == $metric['type'] ? '' : ' style="display: none;"'; ?>>
						<?php
						$metric_type->render_options( $metric['options'] );
						?>
					</dl>
					<?php
				}
				?>
				<dl>
					<dt>Usage</dt>
					<dd>
						<?php
						if ( ! isset( $metric['options']['usage'] ) ) {
							$usage = array( 'shortcodes' );
						} else {
							$usage = $metric['options']['usage'];
						}

						$cases = array(
							'shortcodes' => "Available as a shortcode",
							'admins_only' => "Only visible to admins",
							'comments_attached' => "Attached to Comments (i)",
							'comments' => "Available on Comments",
						);

						foreach ( get_post_types( array( 'public' => true, ), 'objects' ) as $slug => $object ) {
							$cases[ $slug ] = "Available on " . $object->labels->name;
						}

						foreach ( $cases as $slug => $text ) {
							?>
							<label>
								<input type="checkbox" name="options[usage][]" value="<?php echo $slug; ?>" <?php checked( in_array( $slug, $usage ) ); ?>></input>
								<?php echo $text; ?>
							</label>
							<br>
							<?php
						}
						?>
					</dd>
				</dl>
				<input class="button button-primary" type="submit" value="Save"></input>
			</form>
		</div>
		<?php
	}

	private static function save_post_data() {
		if ( ! isset( $_POST['action'] ) || $_POST['action'] !== 'save' ) return;
		global $wpdb;

		// TODO: Verify nonce.

		$metric_type = Evaluate_Metrics::get_metric_types()[ $_POST['type'] ];
		$options = $metric_type->filter_options( $_POST['options'] );

		$data = array(
			'name' => sanitize_text_field( $_POST['name'] ),
			'type' => sanitize_text_field( $_POST['type'] ),
			'options' => serialize( $options ),
		);

		if ( empty( $_POST['metric_id'] ) ) {
			$data['created'] = current_time( 'mysql', 1 );

			$wpdb->insert( Evaluate::$metric_table, $data );
			$metric_id = $wpdb->insert_id;
		} else {
			$wpdb->update( Evaluate::$metric_table, $data, array( 'metric_id' => $_POST['metric_id'] ) );
			$metric_id = $_POST['metric_id'];
		}

		wp_redirect( add_query_arg( 'metric_id', $metric_id ) );
		exit;
	}

	public static function delete_metric( $metric_id ) {
		global $wpdb;
		$wpdb->delete( Evaluate::$metric_table, array( 'metric_id' => $metric_id ), array( '%d' ) );
	}

	public static function get_metrics_count() {
		global $wpdb;
		return $wpdb->get_var( "SELECT COUNT(*) FROM " . Evaluate::$metric_table );
	}

}

Evaluate_Manager::init();
