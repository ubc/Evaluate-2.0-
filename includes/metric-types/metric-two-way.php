<?php

class Evaluate_Metric_Two_Way extends Evaluate_Metric_Type {

	const SLUG = 'two-way';

	public function __construct() {
		parent::__construct( "Two Way", self::SLUG );
	}

	// ===== FRONT FACING CODE ==== //
	public function render_display( $metric, $score, $user_vote = null ) {
		$metric_id = $metric['metric_id'];
		$icon = $metric['options']['icon'];
		$text_up = $metric['options']['text_up'];
		$text_down = $metric['options']['text_down'];
		$value = $score['value'];

		?>
		<label class="metric-vote metric-vote-1">
			<input name="metric-<?php echo $metric_id; ?>" type="radio" value="1" <?php checked( $user_vote, 1 );?>></input>
			<i class="icon-<?php echo $icon; ?>-up"></i>
			<span><?php echo $text_up; ?></span>
		</label>
		<label class="metric-vote metric-vote--1">
			<input name="metric-<?php echo $metric_id; ?>" type="radio" value="-1" <?php checked( $user_vote, -1 );?>></input>
			<i class="icon-<?php echo $icon; ?>-down"></i>
			<span><?php echo $text_down; ?></span>
		</label>
		<span class="metric-score"><?php echo $value; ?></span>
		<?php
	}

	// ===== VOTE / SCORE HANDLING ==== //
	public function validate_vote( $vote, $old_vote, $options ) {
		$vote = parent::validate_vote( $vote, $old_vote, $options );

		if ( $vote > 0 ) {
			return 1;
		} else if ( $vote < 0 ) {
			return -1;
		} else {
			return null;
		}
	}

	public function modify_score( $score, $vote, $old_vote, $metric, $context_id ) {
		$vote_diff = $vote - $old_vote;

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
		
		return parent::modify_score( $score, $vote, $old_vote, $metric, $context_id );
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
		// This means that every metric will be treated as if it has 10 (or user defined) votes to start with.
		// This is used to make unrated content appear higher than negatively rated content.
		// TODO: There may exist better solutions to this, such as using a negative wilson score.
		$positive += $base_votes / 2;
		$count += $base_votes;

		$p = 1.0 * $positive / $count;
		$numerator = $p + $z * $z / (2 * $count) - $z * sqrt(($p * (1 - $p) + $z * $z / (4 * $count)) / $count);
		$denominator = 1 + $z * $z / $count;
		return $numerator / $denominator;
	}

	// ===== ADMIN FACING CODE ==== //
	public function render_options( $options, $name = 'options[%s]' ) {
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
			<input type="text" name="<?php printf( $name, 'text_up' ); ?>" placeholder="Up" value="<?php echo $options['text_up']; ?>" autocomplete="off"></input>
			<br>
			<input type="text" name="<?php printf( $name, 'text_down' ); ?>" placeholder="Down" value="<?php echo $options['text_down']; ?>" autocomplete="off"></input>
		</dd>
		<?php
	}

}

Evaluate_Metrics::register_type( new Evaluate_Metric_Two_Way() );
