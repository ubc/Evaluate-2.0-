<?php

abstract class Evaluate_Metric_Type {

	public $name;
	public $slug;

	public function __construct( $name, $slug ) {
		$this->name = $name;
		$this->slug = $slug;
	}

	// ===== FRONT FACING CODE ==== //
	abstract public function render_display( $metric, $score, $user_vote );

	// ===== VOTE / SCORE HANDLING ==== //

	/**
	 * We want a no-vote to be null, and any non-valid votes to be approximated or converted to null.
	 */
	public function validate_vote( $vote, $old_vote, $options ) {
		$vote = floatval( $vote );
		if ( $vote === $old_vote || is_nan( $vote ) ) {
			return null;
		} else {
			return $vote;
		}
	}

	public function set_vote( $vote, $metric, $context_id, $user_id ) {
		global $wpdb;

		if ( $vote === null ) {
			$wpdb->delete( Evaluate::$voting_table, array(
				'metric_id'  => $metric['metric_id'],
				'context_id' => $context_id,
				'user_id'    => $user_id,
			), array( '%d', '%s', '%s' ) );
		} else {
			$wpdb->replace( Evaluate::$voting_table, array(
				'metric_id'  => $metric['metric_id'],
				'context_id' => $context_id,
				'user_id'    => $user_id,
				'vote'       => $vote,
			), array( '%d', '%s', '%s', '%d' ) );
		}
	}

	public function get_vote( $metric, $context_id, $user_id ) {
		global $wpdb;

		$query = "SELECT vote FROM " . Evaluate::$voting_table . " WHERE metric_id=%d AND context_id=%s AND user_id=%s";
		$query = $wpdb->prepare( $query, $metric['metric_id'], $context_id, $user_id );
		$result = $wpdb->get_row( $query, 'ARRAY_A' );

		return isset( $result['vote'] ) ? $result['vote'] : null;
	}

	public function modify_score( $score, $vote, $old_vote, $metric, $context_id ) {
		global $wpdb;

		$wpdb->replace( Evaluate::$scores_table, array(
			'metric_id'  => $metric['metric_id'],
			'context_id' => $context_id,
			'count'      => $score['count'],
			'value'      => $score['value'],
			'average'    => $score['average'],
			'data'       => serialize( $score['data'] ),
		), array( '%d', '%s', '%d', '%f', '%f', '%s' ) );

		return $score;
	}

	public function get_score( $metric_id, $context_id ) {
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

	// ===== ADMIN FACING CODE ==== //
	public function render_options( $options, $name ) {
		// Do Nothing
	}

	public function filter_options( $options ) {
		return $options;
	}

	/*public function get_options() {

	}*/

}
