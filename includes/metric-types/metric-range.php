<?php

// TODO: Make this work for fractional votes, or prevent fractional votes.
class Evaluate_Metric_Range extends Evaluate_Metric_Type {

	const SLUG = 'range';

	public function __construct() {
		parent::__construct( "Range", self::SLUG );
	}

	// ===== FRONT FACING CODE ==== //
	public function render_display( $metric, $score, $user_vote = null ) {
		$metric_id = $metric['metric_id'];
		$icon = $metric['options']['icon'];
		$max = $metric['options']['max'];
		$value = $score['average'];

		if ( $icon === 'slider' ) {
			?>
			<span class="metric-vote">
				<input class="metric-input" type="range" min="0" step="1" max="<?php echo $max; ?>" value="<?php echo $user_vote; ?>"></input>
			</span>
			<?php
		} else if ( $icon === 'numeric' ) {
			?>
			<span class="metric-vote">
				<input class="metric-input" type="number" min="0" step="1" max="<?php echo $max; ?>" placeholder="0" value="<?php echo $user_vote; ?>"></input> / <?php echo $max; ?> - 
			</span>
			<?php
		} else {
			self::render_icons( $max, $icon, $metric_id, $user_vote );
		}
		?>
		<span class="metric-score"><?php echo $value; ?></span>
		<?php
	}

	private static function render_icons( $i, $icon_slug, $metric_id, $user_vote ) {
		if ( $i > 0 ) {
			$icon_state = 'up';
			//$icon_state = ( $i <= $user_vote ) ? 'up' : 'empty';
			?>
			<span class="metric-vote metric-vote-<?php echo $i; ?>">
				<?php self::render_icons( $i - 1, $icon_slug, $metric_id, $user_vote ); ?>
				<label>
					<input name="metric-<?php echo $metric_id; ?>" type="radio" value="<?php echo $i; ?>" <?php checked( $user_vote, $i ); ?>></input>
					<i class="icon-<?php echo $icon_slug; ?>-<?php echo $icon_state; ?>"></i>
				</label>
			</span>
			<?php
		}
	}

	// ===== VOTE / SCORE HANDLING ==== //
	public function validate_vote( $vote, $old_vote, $options ) {
		$vote = parent::validate_vote( $vote, $old_vote, $options );

		// TODO: Prevent fractional votes, if necessary.
		if ( $vote <= 0 ) {
			return in_array( $options['icon'], array( 'numeric', 'slider' ) ) ? 0 : 1;
		} else if ( $vote > $options['max'] ) {
			return intval( $options['max'] );
		} else {
			return $vote;
		}
	}

	public function modify_score( $score, $vote, $old_vote, $metric, $context_id ) {
		$vote_diff = $vote - $old_vote;
		$score['average'] = $score['average'] * $score['count'];

		if ( $vote !== $old_vote ) {
			if ( $vote === null ) {
				$score['count']--;
			} elseif ( $old_vote === null ) {
				$score['count']++;
			}
		}

		if ( $score['count'] == 0 ) {
			$score['average'] = 0;
			$score['value'] = 0;
		} else {
			$score['average'] = ( $score['average'] + $vote_diff ) / $score['count'];
			$score['value'] = self::calculate_bayesian_score( $score['average'], $score['count'], $metric['options']['max'] );
		}

		return parent::modify_score( $score, $vote, $old_vote, $metric, $context_id );
	}

	/**
	 * Assumes score inherently tends towards 50%. ie. the bayesian prior is 50%
	 */
	public static function calculate_bayesian_score( $average, $total, $max ) {
		$prior = ( ( $max - 1 ) / 2 ) + 1;
		$constant = 1;
		return round( ( ( $constant * $prior ) + ( $average * $total ) ) / ( $constant + $total ), 5 );
	}

	// ===== ADMIN FACING CODE ==== //
	public function render_options( $options, $name = 'options[%s]' ) {
		$options = shortcode_atts( array(
			'icon' => 'stars',
			'max'  => 5,
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

Evaluate_Metrics::register_type( new Evaluate_Metric_Range() );
