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
			$score = self::set_vote( $args['metric_id'], $args['context_id'], $args['vote'], self::get_user_id() );
			echo json_encode( $score );
		}

		wp_die();
	}

	public static function get_user_id() {
		if ( is_user_logged_in() ) {
			return get_current_user_id();
		} else {
			return self::get_client_ip();
		}
	}

	public static function set_vote( $metric_id, $context_id, $vote, $user_id ) {
		global $wpdb;
		error_log('setting vote, ' . var_export( $metric_id, true ) . ', ' . var_export( $context_id, true ) . ', ' . var_export( $vote, true ) . ', ' . var_export( $user_id, true ) );

		error_log('get_metric');
		$metric = Evaluate_Metrics::get_metrics( array( $metric_id ) )[0];

		$old_vote = self::get_vote( $metric_id, $context_id, $user_id );
		error_log('get_vote ' . var_export($old_vote, true));
		$vote = self::validate_vote( $metric['type'], $metric['options'], $vote, $old_vote );

		if ( $vote === null ) {
			error_log('delete');
			$wpdb->delete( Evaluate::$voting_table, array(
				'metric_id'  => $metric_id,
				'context_id' => $context_id,
				'user_id'    => $user_id,
			), array( '%d', '%s', '%s' ) );
		} else {
			error_log('replace for ' . var_export( $user_id, true ) );
			$wpdb->replace( Evaluate::$voting_table, array(
				'metric_id'  => $metric_id,
				'context_id' => $context_id,
				'user_id'    => $user_id,
				'vote'       => $vote,
			), array( '%d', '%s', '%s', '%d' ) );
		}

		error_log('get_score');
		$score = self::get_score( $metric_id, $context_id );
		error_log("old_vote is " . var_export($old_vote, true));
		$score = apply_filters( 'evaluate_adjust_score_' . $metric['type'], $score, $metric['options'], $vote, $old_vote );
		
		error_log('replace_score');
		$wpdb->replace( Evaluate::$scores_table, array(
			'metric_id'  => $metric_id,
			'context_id' => $context_id,
			'count'      => $score['count'],
			'value'      => $score['value'],
			'average'    => $score['average'],
			'data'       => serialize( $score['data'] ),
		), array( '%d', '%s', '%d', '%f', '%f', '%s' ) );

		$score['vote'] = $vote;

		error_log('return, ' . var_export( $score, true ) );
		error_log('----');
		return array(
			'count'   => $score['count'],
			//'value'   => $score['value'],
			'average' => $score['average'],
			'data'    => $score['data'],
			'vote'    => $vote,
		);
	}

	public static function validate_vote( $metric_type, $options, $vote, $old_vote ) {
		error_log('check for null ' . var_export( $vote, true ) . ", " . var_export( is_numeric( $vote ), true ) );
		// TODO: Maybe we should allow non-numeric votes through for extensibility or for Rubrics.
		/*if ( ! is_numeric( $vote ) ) return null;

		$vote = floatval( $vote );
		if ( $vote === $old_vote || is_nan( $vote ) ) {
			return null;
		} else {*/
			return apply_filters( 'evaluate_validate_vote_' . $metric_type, $vote, $options );
		//}
	}

	public static function force_numeric_value( $value ) {
		$value = floatval( $value );
		if ( is_nan( $value ) ) {
			return null;
		} else {
			return $value;
		}
	}

	public static function get_score( $metric_id, $context_id ) {
		global $wpdb;

		$query = "SELECT count, value, average, data FROM " . Evaluate::$scores_table . " WHERE metric_id=%d AND context_id=%s";
		$query = $wpdb->prepare( $query, $metric_id, $context_id );

		$results = $wpdb->get_row( $query, 'ARRAY_A' );
		$results['data'] = unserialize( $results['data'] );

		return shortcode_atts( array(
			'count'   => 0,
			'value'   => 0,
			'average' => 0,
			'data'    => array(),
		), $results );
	}

	public static function get_vote( $metric_id, $context_id, $user_id ) {
		global $wpdb;

		$query = "SELECT vote FROM " . Evaluate::$voting_table . " WHERE metric_id=%d AND context_id=%s AND user_id=%s";
		$query = $wpdb->prepare( $query, $metric_id, $context_id, $user_id );
		$result = $wpdb->get_row( $query, 'ARRAY_A' );

		if ( isset( $result['vote'] ) ) {
			return $result['vote'];
		} else {
			return null;
		}
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