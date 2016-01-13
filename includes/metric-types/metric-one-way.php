<?php

class Evaluate_Metric_One_Way extends Evaluate_Metric_Type {

	const SLUG = 'one-way';

	public function __construct() {
		parent::__construct( "One Way", self::SLUG );
	}

	// ===== FRONT FACING CODE ==== //
	public function render_display( $metric, $score, $user_vote = null ) {
		$metric_id = $metric['metric_id'];
		$icon = $metric['options']['icon'];
		$text = $metric['options']['text'];
		$value = $score['value'];

		?>
		<label class="metric-vote metric-vote-1">
			<input name="metric-<?php echo $metric_id; ?>" type="radio" value="1" <?php checked( $user_vote, 1 );?>></input>
			<i class="icon-<?php echo $icon; ?>-up"></i>
			<span><?php echo $text; ?></span>
		</label>
		<span class="metric-score"><?php echo $value; ?></span>
		<?php
	}

	// ===== VOTE / SCORE HANDLING ==== //
	public function validate_vote( $vote, $old_vote, $options ) {
		$vote = parent::validate_vote( $vote, $old_vote, $options );

		if ( $vote > 0 ) {
			return 1;
		} else {
			return null;
		}
	}

	public function modify_score( $score, $vote, $old_vote, $metric, $context_id ) {
		$vote_diff = $vote - $old_vote;

		if ( $vote !== $old_vote ) {
			if ( $vote === null ) {
				$score['count']--;
			} elseif ( $old_vote === null ) {
				$score['count']++;
			}
		}

		$score['average'] += $vote_diff;
		$score['value'] = $score['average'];
		return parent::modify_score( $score, $vote, $old_vote, $metric, $context_id );
	}

	// ===== ADMIN FACING CODE ==== //
	public function render_options( $options, $name = 'options[%s]' ) {
		$options = shortcode_atts( array(
			'icon' => 'thumbs',
			'text' => "",
		), $options );

		?>
		<dt>Icon</dt>
		<dd>
			<select name="<?php printf( $name, 'icon' ); ?>">
				<?php
				$icons = array(
					'thumbs'    => "Thumb",
					'arrows'    => "Arrow",
					'bookmarks' => "Bookmark",
					'stars'     => "Star",
					'marks'     => "Checkmark",
					'hearts'    => "Heart",
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
		<dd><input type="text" name="<?php printf( $name, 'text' ); ?>" value="<?php echo $options['text']; ?>" autocomplete="off"></input></dd>
		<?php
	}

}

Evaluate_Metrics::register_type( new Evaluate_Metric_One_Way() );

