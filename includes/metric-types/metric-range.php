<?php

class Evaluate_Metric_Range {

	const SLUG = 'range';

	public static function init() {
		Evaluate_Metrics::register_type( "Range", self::SLUG );
		add_action( 'evaluate_render_metric_' . self::SLUG, array( __CLASS__, 'render_metric' ), 10, 3 );
		add_filter( 'evaluate_validate_vote_' . self::SLUG, array( __CLASS__, 'validate_vote' ), 10, 2 );
		add_filter( 'evaluate_adjust_score_' . self::SLUG, array( __CLASS__, 'adjust_score' ), 10, 4 );
		add_action( 'evaluate_render_options_' . self::SLUG, array( __CLASS__, 'render_options' ), 10, 2 );
	}

	public static function render_metric( $options, $score, $metric_id, $user_vote = null ) {
		if ( $options['icon'] === 'numeric' ) {
			/*<input type="range" min="0" max="<?php echo $options['max']; ?>"></input>*/
			?>
			<input class="metric-input" type="number" min="0" max="<?php echo $options['max']; ?>" placeholder="0" value="<?php echo $user_vote; ?>" data-value="<?php echo $user_vote; ?>"></input> / <?php echo $options['max']; ?> - 
			<?php
		} else {
			self::render_icons( $options['max'], $options['icon'], $metric_id, $user_vote );
		}
		?>
		<span class="metric-score"><?php echo $score['average']; ?></span>
		<?php
	}

	public static function render_icons( $i, $icon_slug, $metric_id, $user_vote ) {
		if ( $i > 0 ) {
			$icon_state = 'up';
			//$icon_state = ( $i <= $user_vote ) ? 'up' : 'empty';
			?>
			<span class="metric-vote metric-vote-<?php echo $i; ?><">
				<?php self::render_icons( $i - 1, $icon_slug, $metric_id, $user_vote ); ?>
				<label>
					<input name="metric-<?php echo $metric_id; ?>" type="radio" value="<?php echo $i; ?>" <?php checked( $user_vote, $i ); ?>></input>
					<i class="icon-<?php echo $icon_slug; ?>-<?php echo $icon_state; ?>"></i>
				</label>
			</span>
			<?php
		}
	}

	public static function validate_vote( $vote, $options ) {
		if ( $vote <= 0 ) {
			return $options['icon'] == 'numeric' ? 0 : 1;
		} else if ( $vote > $options['max'] ) {
			return intval( $options['max'] );
		} else {
			return $vote;
		}
	}

	public static function adjust_score( $score, $options, $vote, $old_vote = null ) {
		error_log( "adjust_score " . var_export( $score, true ) . ",  " . var_export( $vote, true ) . ", " . var_export( $old_vote, true ) );
		$vote_diff = $vote - $old_vote;
		error_log( "- average: " . $score['average'] );
		$score['average'] = $score['average'] * $score['count'];
		error_log( "- average: " . $score['average'] );

		if ( $vote !== $old_vote ) {
			if ( $vote === null ) {
				error_log("decrement");
				$score['count']--;
			} elseif ( $old_vote === null ) {
				error_log("increment");
				$score['count']++;
			}
		}

		if ( $score['count'] == 0 ) {
			$score['average'] = 0;
			$score['value'] = 0;
		} else {
			error_log( "- average: " . $score['average'] . " + " . $vote_diff . " / " . $score['count'] );
			$score['average'] = ( $score['average'] + $vote_diff ) / $score['count'];
			$score['value'] = self::calculate_bayesian_score( $score['average'], $score['count'], $options['max'] );
		}
		error_log( "- average: " . $score['average'] );

		return $score;
	}

	/**
	 * Assumes score inherently tends towards 50%. ie. the bayesian prior is 50%
	 */
	public static function calculate_bayesian_score( $average, $total, $max ) {
		$prior = ( ( $max - 1 ) / 2 ) + 1;
		$constant = 1;
		return round( ( ( $constant * $prior ) + ( $average * $total ) ) / ( $constant + $total ), 5 );
	}

	public static function render_options( $options, $name = 'options[%s]' ) {
		$options = shortcode_atts( array(
			'icon' => 'stars',
			'max' => 5,
		), $options );

		?>
		<dt>Display Method</dt>
		<dd>
			<select name="<?php printf( $name, 'icon' ); ?>">
				<?php
				// TODO: Add slider display.
				$icons = array(
					'numeric' => "Numeric",
					//'slider' => "Slider",
					'stars' => "Stars",
					'thumbs' => "Thumbs",
					'hearts' => "Hearts",
				);

				foreach ( $icons as $slug => $text ) {
					?>
					<option value="<?php echo $slug; ?>" <?php selected( $slug, $options['icon'] ); ?>>
						<?php echo $text; ?>
					</option>
					<?php
				}
				?>
			</select>
		</dd>
		<dt>Maximum Rating</dt>
		<dd>
			<input type="number" min="2" name="<?php printf( $name, 'max' ); ?>" value="<?php echo $options['max']; ?>"></input>
		</dd>
		<?php
		// TODO: Add minimum rating
	}

}

Evaluate_Metric_Range::init();
