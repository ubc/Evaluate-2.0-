<?php

class Evaluate_Metric_Two_Way {

	const SLUG = 'two-way';

	public static function init() {
		Evaluate_Metrics::register_type( "Two Way", self::SLUG );
		add_action( 'evaluate_render_metric_' . self::SLUG, array( __CLASS__, 'render_metric' ), 10, 3 );
		add_filter( 'evaluate_validate_vote_' . self::SLUG, array( __CLASS__, 'validate_vote' ), 10, 2 );
		add_filter( 'evaluate_adjust_score_' . self::SLUG, array( __CLASS__, 'adjust_score' ), 10, 4 );
		add_action( 'evaluate_render_options_' . self::SLUG, array( __CLASS__, 'render_options' ), 10, 2 );
	}

	// TODO: Prevent people from voting with non-valid quantities, like +3 or -10 for example.
	public static function render_metric( $options, $score, $metric_id, $user_vote = null ) {
		?>
		<label class="metric-vote metric-vote-1">
			<input name="metric-<?php echo $metric_id; ?>" type="radio" value="1" <?php checked( $user_vote, 1 );?>></input>
			<i class="icon-<?php echo $options['icon']; ?>-up"></i>
			<span><?php echo $options['text_up']; ?></span>
		</label>
		<label class="metric-vote metric-vote--1">
			<input name="metric-<?php echo $metric_id; ?>" type="radio" value="-1" <?php checked( $user_vote, -1 );?>></input>
			<i class="icon-<?php echo $options['icon']; ?>-down"></i>
			<span><?php echo $options['text_down']; ?></span>
		</label>
		<span class="metric-score"><?php echo $score['value']; ?></span>
		<?php
	}

	public static function validate_vote( $vote, $options ) {
		if ( $vote > 0 ) {
			return 1;
		} else if ( $vote < 0 ) {
			return -1;
		} else {
			return null;
		}
	}

	public static function adjust_score( $score, $options, $vote, $old_vote = null ) {
		error_log( "- adjusting score: " . var_export( $score, true ) . ", " . var_export( $vote, true ) . ", " . var_export( $old_vote, true ) );
		$vote_diff = $vote - $old_vote;
		error_log( "- vote_diff: " . $vote_diff );

		if ( ! isset( $score['data']['positive'] ) ) {
			$score['data']['positive_votes'] = 0;
		}

		if ( $vote !== $old_vote ) {
			if ( $vote === null ) {
				$score['count']--;
				if ( $old_vote > 0 ) $score['data']['positive_votes']--;
			} elseif ( $old_vote === null ) {
				$score['count']++;
				if ( $vote > 0 ) $score['data']['positive_votes']++;
			}
		}

		$score['average'] += $vote_diff;
		$score['value'] = self::calculate_wilson_score( $score['data']['positive_votes'], $score['count'] );
		
		return $score;
	}
  
	/**
	 * Taken from https://gist.github.com/mbadolato/8253004
	 * calculates the wilson score: a lower bound on the "true" value of
	 * the ratio of positive votes and total votes, given a confidence level
	 *
	 * $z = 1.959964 = 95.0% confidence
	 * $z = 2.241403 = 97.5% confidence
	 */
	public static function calculate_wilson_score( $positive, $count, $z = 1.959964, $base_votes = 10 ) {
		error_log( "-- get wilson: " . var_export( func_get_args(), true ) );
		
		// This means that every metric will be treated as if it has 10 (or user defined) votes to start with.
		// This is used to make unrated content appear higher than negatively rated content.
		// TODO: There may exist better solutions to this, such as using a negative wilson score.
		$positive += $base_votes / 2;
		$count += $base_votes;

		$p = 1.0 * $positive / $count;
		$numerator = $p + $z * $z / (2 * $count) - $z * sqrt(($p * (1 - $p) + $z * $z / (4 * $count)) / $count);
		error_log( "-- numerator: " . var_export( $numerator, true ) );
		$denominator = 1 + $z * $z / $count;
		error_log( "-- denominator: " . var_export( $denominator, true ) );
		
		error_log( "-- wilson: " . var_export( $numerator / $denominator, true ) );
		return $numerator / $denominator;
	}

	public static function render_options( $options, $name = 'options[%s]' ) {
		$options = shortcode_atts( array(
			'icon' => 'arrows',
			'text_up' => "",
			'text_down' => "",
		), $options );

		?>
		<dt>Icon</dt>
		<dd>
			<select name="<?php printf( $name, 'icon' ); ?>">
				<?php
				$icons = array(
					'thumbs' => "Thumbs",
					'arrows' => "Arrows",
					'marks' => "Checkmark",
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
		<dt>Text</dt>
		<dd>
			<input type="text" name="<?php printf( $name, 'text_up' ); ?>" placeholder="Up" value="<?php echo $options['text_up']; ?>"></input>
			<br>
			<input type="text" name="<?php printf( $name, 'text_down' ); ?>" placeholder="Down" value="<?php echo $options['text_down']; ?>"></input>
		</dd>
		<?php
	}

}

Evaluate_Metric_Two_Way::init();
