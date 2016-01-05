<?php

class Evaluate_Metric_Poll {

	public static function init() {
		Evaluate_Metrics::register_type( array(
			'title'   => "Poll",
			'slug'    => 'poll',
			'render'   => array( __CLASS__, 'render_metric' ),
			'validate' => array( __CLASS__, 'validate_vote' ),
			'score'    => array( __CLASS__, 'adjust_score' ),
			'options'  => array( __CLASS__, 'render_options' ),
		) );
	}

	public static function render_metric( $options, $score, $user_vote = null ) {
		?>
		<div>
			<strong><?php echo $options['question']; ?></strong>
			<ul>
			<?php
			foreach ( preg_split( "/\r\n|\n|\r/", $options['answers'] ) as $index => $text ) {
				$vote_count = empty( $score['data']['votes'][ $index ] ) ? 0 : $score['data']['votes'][ $index ];
				?>
				<li>
					<a class="metric-vote metric-vote-<?php echo $index; ?><?php echo is_numeric( $user_vote ) && $user_vote == $index ? ' active' : '';?>" data-value="<?php echo $index; ?>">
						<?php echo $text; ?>
					</a>
					(<span class="metric-score-<?php echo $index; ?>"><?php echo $vote_count; ?></span>)
					<br>
					<progress class="metric-score-<?php echo $index; ?>" max="<?php echo $score['count']; ?>" value="<?php echo $vote_count; ?>"></progress>
				</li>
				<?php
			}
			?>
			<ul>
		</div>
		<?php
	}

	public static function validate_vote( $options, $vote ) {
		$answers_count = preg_match_all( "/\r\n|\n|\r/", $options['answers'] );
		$vote = intval( $vote );

		error_log('check for null ' . var_export( $vote, true ) . ", " . var_export( $answers_count, true ) . ", " . var_export( is_integer( $vote ), true ) . ", " . var_export( $vote < 0, true ) . ", " . var_export( $vote > $answers_count, true ) );
		if ( ! is_integer( $vote ) || $vote < 0 || $vote > $answers_count ) {
			return null;
		} else {
			return $vote;
		}
	}

	public static function adjust_score( $score, $options, $vote, $old_vote = null ) {
		error_log( "- adjust score: " . var_export( $score, true ) . ", " . var_export( $vote, true ) . ", " . var_export( $old_vote, true ) );

		if ( $vote !== $old_vote ) {
			if ( $vote === null ) {
				$score['count']--;
				error_log( "- increment count to " . var_export( $score['count'], true ) );
			} elseif ( $old_vote === null ) {
				$score['count']++;
				error_log( "- decrement count to " . var_export( $score['count'], true ) );
			}
		}

		// Initialize the data array
		if ( empty( $score['data']['votes'] ) ) {
			$answers_count = count( preg_split( "/\r\n|\n|\r/", $options['answers'] ) );

			for ( $i = 0; $i < $answers_count; $i++ ) { 
				$score['data']['votes'][ $i ] = 0;
			}
			error_log( "- initialize data array" );
		}

		if ( $old_vote !== null ) {
			$score['data']['votes'][ $old_vote ]--;
			error_log( "- decrement old vote to " . var_export( $score['data']['votes'][ $old_vote ], true ) );
		}

		if ( $vote !== null ) {
			$score['data']['votes'][ $vote ]++;
			error_log( "- increment new vote to " . var_export( $score['data']['votes'][ $vote ], true ) );
		}

		// Get the index for the highest value in our scores array.
		$score['average'] = array_keys( $score['data']['votes'], max( $score['data']['votes'] ) )[0];
		$score['value'] = $score['average'];

		error_log( "- average is " . $score['average'] . " in " . var_export( $score['data']['votes'], true ) );

		return $score;
	}

	public static function render_options( $options ) {
		$options = shortcode_atts( array(
			'question' => "",
			'answers' => "",
		), $options );

		?>
		<dt>Question</dt>
		<dd><input type="text" name="options[question]" value="<?php echo $options['question']; ?>"></input></dd>
		<dt>Answers</dt>
		<dd>
			<textarea name="options[answers]" value="<?php echo $options['answers']; ?>"></textarea>
			<br>
			<small>One answer per line</small>
		</dd>
		<?php
	}

}

Evaluate_Metric_Poll::init();
