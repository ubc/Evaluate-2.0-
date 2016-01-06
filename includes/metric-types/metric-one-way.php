<?php

class Evaluate_Metric_One_Way {

	public const SLUG = 'one-way';

	public static function init() {
		Evaluate_Metrics::register_type( "One Way", self::SLUG );
		add_action( 'evaluate_render_' . self::SLUG, array( __CLASS__, 'render_metric' ), 10, 3 );
		add_filter( 'evaluate_validate_vote_' . self::SLUG, array( __CLASS__, 'validate_vote' ), 10, 1 );
		add_filter( 'evaluate_adjust_score_' . self::SLUG, array( __CLASS__, 'adjust_score' ), 10, 3 );
		add_action( 'evaluate_render_options_' . self::SLUG, array( __CLASS__, 'render_options' ), 10, 2 );
	}

	public static function render_metric( $options, $score, $user_vote = null ) {
		?>
		<a class="metric-vote metric-vote-1<?php echo $user_vote == 1 ? ' active' : '';?>" data-value="1">
			<i class="icon-<?php echo $options['icon']; ?>-up"></i>
			<?php echo $options['text']; ?>
		</a>
		<span class="metric-score"><?php echo $score['value']; ?></span>
		<?php
	}

	public static function validate_vote( $vote, $options ) {
		if ( $vote > 0 ) {
			return 1;
		} else {
			return null;
		}
	}

	public static function adjust_score( $score, $options, $vote, $old_vote = null ) {
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
		return $score;
	}

	public static function render_options( $options, $name = 'options[%s]' ) {
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
		<dd><input type="text" name="<?php printf( $name, 'text' ); ?>" value="<?php echo $options['text']; ?>"></input></dd>
		<?php
	}

}

Evaluate_Metric_One_Way::init();
