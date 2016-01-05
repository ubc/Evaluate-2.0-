<?php

class Evaluate_Metric_Two_Way {

	public static function init() {
		Evaluate_Metrics::register_type( array(
			'title'    => "Two Way",
			'slug'     => 'two-way',
			'render'   => array( __CLASS__, 'render_metric' ),
			'validate' => array( __CLASS__, 'validate_vote' ),
			'score'    => array( __CLASS__, 'adjust_score' ),
			'options'  => array( __CLASS__, 'render_options' ),
		) );
	}

	// TODO: Prevent people from voting with non-valid quantities, like +3 or -10 for example.
	public static function render_metric( $options, $score, $user_vote = null ) {
		?>
		<a class="metric-vote metric-vote-1<?php echo $user_vote == 1 ? ' active' : '';?>" data-value="1">
			<i class="icon-<?php echo $options['icon']; ?>-up"></i>
			<?php echo $options['text_up']; ?>
		</a>
		<a class="metric-vote metric-vote--1<?php echo $user_vote == -1 ? ' active' : '';?>" data-value="-1">
			<i class="icon-<?php echo $options['icon']; ?>-down"></i>
			<?php echo $options['text_down']; ?>
		</a>
		<span class="metric-score"><?php echo round( $score['average'] ); ?></span>
		<?php
	}

	public static function validate_vote( $options, $vote ) {
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
