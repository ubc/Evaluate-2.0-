<?php

class Evaluate_Metrics {

	private static $metric_types = array();

	public static function init() {
		require_once( Evaluate::$directory_path . '/includes/metric-types/metric-one-way.php' );
		require_once( Evaluate::$directory_path . '/includes/metric-types/metric-two-way.php' );
		require_once( Evaluate::$directory_path . '/includes/metric-types/metric-range.php' );
		require_once( Evaluate::$directory_path . '/includes/metric-types/metric-poll.php' );
		require_once( Evaluate::$directory_path . '/includes/metric-types/metric-rubric.php' );

		add_filter( 'the_content', array( __CLASS__, 'render_metrics' ), 100 );
		add_filter( 'comment_text', array( __CLASS__, 'render_metrics' ), 100 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts_and_styles' ) );
	}

	public static function enqueue_scripts_and_styles() {
		wp_register_script( 'evaluate-metrics', Evaluate::$directory_url . 'js/evaluate-metrics.js' );
		wp_register_style( 'evaluate-metrics', Evaluate::$directory_url . 'css/evaluate-metrics.css' );
		wp_register_style( 'evaluate-icons', Evaluate::$directory_url . 'fontello/css/evaluate.css' );

		wp_localize_script( 'evaluate-metrics', 'ajaxurl', admin_url( 'admin-ajax.php' ) );
	}

	public static function register_type( $title, $slug ) {
		self::$metric_types[ $slug ] = $title;
	}

	public static function get_metric_types() {
		return self::$metric_types;
	}

	public static function render_metrics( $content, $context = null ) {
		$metrics = self::get_metrics( array(), null );

		if ( empty( $context ) ) {
			if ( 'comment_text' === current_filter() ) {
				$context = 'comments';
				$context_id = get_comment_ID();
			} else {
				$context = get_post_type();
				$context_id = get_the_ID();
			}
		}

		$user_id = Evaluate_Voting::get_user_id();

		foreach ( $metrics as $key => $metric ) {
			error_log( $metric['name'] );
			if ( in_array( $context, $metric['options']['usage'] ) ) {
				wp_enqueue_script( 'evaluate-metrics' );
				wp_enqueue_style( 'evaluate-metrics' );
				wp_enqueue_style( 'evaluate-icons' );

				ob_start();
				?>
				<span class="metric metric-<?php echo $metric['type']; ?>"
					data-id="<?php echo $metric['metric_id']; ?>"
					data-context="<?php echo $context_id; ?>"
					data-nonce="<?php echo Evaluate_Voting::get_nonce( $metric['metric_id'], $context_id ); ?>">
					<?php
					if ( ! empty( $metric['options']['title'] ) ) {
						?>
						<strong class="metric-title"><?php echo $metric['options']['title']; ?></strong>
						<?php
					}

					$score = Evaluate_Voting::get_score( $metric['metric_id'], $context_id );
					$user_vote = Evaluate_Voting::get_vote( $metric['metric_id'], $context_id, $user_id );
					do_action( 'evaluate_render_metric_' . $metric['type'], $metric['options'], $score, $user_vote );
					?>
				</span>
				<?php
				$content .= ob_get_clean();
			}
		}

		return $content;
	}

	public static function get_metrics( $ids = array(), $per_page = 5, $page_number = 1 ) {
		global $wpdb;

		$sql = "SELECT * FROM " . Evaluate::$metric_table;

		if ( ! empty( $ids ) ) {
			$sql .= ' WHERE metric_id IN (' . implode( ',', array_map( 'intval', $ids ) ) . ')';
		}

		if ( ! empty( $_REQUEST['orderby'] ) ) {
			$sql .= ' ORDER BY ' . esc_sql( $_REQUEST['orderby'] );
			$sql .= ! empty( $_REQUEST['order'] ) ? ' ' . esc_sql( $_REQUEST['order'] ) : ' ASC';
		}

		if ( ! empty( $per_page ) ) {
			$sql .= " LIMIT " . $per_page;
			$sql .= " OFFSET " . max( $page_number - 1, 0 ) * $per_page;
		}

		$results = $wpdb->get_results( $sql, 'ARRAY_A' );

		foreach ( $results as $key => $result ) {
			if ( ! empty( $result['options'] ) ) {
				$results[ $key ]['options'] = unserialize( $result['options'] );
			}
		}

		return apply_filters( 'evaluate_get_metrics', $results, $ids );
	}

	public static function get_metric_contexts() {
		// Run a filter that retrieves all contexts in which a metric can be posted.
	}

}

Evaluate_Metrics::init();
