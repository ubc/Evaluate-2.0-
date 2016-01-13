<?php

class Evaluate_Voting {

	public static function init() {
		if ( is_admin() ) {
			add_action( 'wp_ajax_vote', array( __CLASS__, 'do_ajax_vote' ) );
			add_action( 'wp_ajax_nopriv_vote', array( __CLASS__, 'do_ajax_vote' ) );
		}
	}

	private static function get_nonce_key( $metric_id, $context ) {
		return "evaluate_" . $metric_id . "_" . $context;
	}

	public static function get_nonce( $metric_id, $context ) {
		return wp_create_nonce( self::get_nonce_key( $metric_id, $context ) );
	}

	private static function get_client_ip() {
		$ipaddress = '';

		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) )
			$ipaddress = $_SERVER['HTTP_CLIENT_IP'];
		else if ( ! empty($_SERVER['HTTP_X_FORWARDED_FOR'] ) )
			$ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
		else if ( ! empty($_SERVER['HTTP_X_FORWARDED'] ) )
			$ipaddress = $_SERVER['HTTP_X_FORWARDED'];
		else if ( ! empty($_SERVER['HTTP_FORWARDED_FOR'] ) )
			$ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
		else if ( ! empty($_SERVER['HTTP_FORWARDED'] ) )
			$ipaddress = $_SERVER['HTTP_FORWARDED'];
		else if ( ! empty($_SERVER['REMOTE_ADDR'] ) )
			$ipaddress = $_SERVER['REMOTE_ADDR'];
		else
			$ipaddress = 'UNKNOWN';

		return $ipaddress;
	}

	public static function do_ajax_vote() {
		error_log( "AJAX VOTE, " . var_export( $_POST, true ) );

		$args = shortcode_atts( array(
			'metric_id'  => null,
			'context_id' => null,
			'vote'       => null,
		), $_REQUEST );

		$nonce_key = self::get_nonce_key( $args['metric_id'], $args['context_id'] );
		check_ajax_referer( $nonce_key, 'nonce' );

		if ( $args['metric_id'] != null && $args['context_id'] != null ) {
			$metric = Evaluate_Metrics::get_metrics( array( $args['metric_id'] ) )[0];
			$score = self::set_vote( $args['vote'], $metric, $args['context_id'], self::get_user_key() );
			echo json_encode( $score );
		}

		wp_die();
	}

	public static function get_user_key() {
		if ( is_user_logged_in() ) {
			return get_current_user_id();
		} else {
			return self::get_client_ip();
		}
	}

	public static function set_vote( $vote, $metric, $context_id, $user_id ) {
		$metric_type = Evaluate_Metrics::get_metric_types()[ $metric['type'] ];

		$old_vote = $metric_type->get_vote( $metric, $context_id, $user_id );
		$vote = $metric_type->validate_vote( $vote, $old_vote, $metric['options'] );

		$metric_type->set_vote( $vote, $metric, $context_id, $user_id );

		$score = $metric_type->get_score( $metric['metric_id'], $context_id );
		$score = $metric_type->modify_score( $score, $vote, $old_vote, $metric, $context_id );

		return array(
			'count'   => $score['count'],
			//'value'   => $score['value'],
			'average' => $score['average'],
			'data'    => $score['data'],
			'vote'    => $vote,
		);
	}

	public static function clear_votes( $metric_id = null, $context = null ) {
		global $wpdb;

		$where = array();
		if ( $metric_id != null ) $where['metric_id']  = $metric_id;
		if ( $context != null )   $where['context_id'] = $context;

		$wpdb->delete( Evaluate::$voting_table, $where );
	}

}

Evaluate_Voting::init();