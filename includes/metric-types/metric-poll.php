<?php

class Evaluate_Metric_Poll extends Evaluate_Metric_Type {

	const SLUG = 'poll';

	public function __construct() {
		parent::__construct( "Poll", self::SLUG );
	}

	// ===== FRONT FACING CODE ==== //
	public function render_display( $metric, $score, $user_vote = null ) {
		$metric_id = $metric['metric_id'];
		$question = $metric['options']['question'];
		$answers = preg_split( "/\r\n|\n|\r/", $metric['options']['answers'] );
		$total_votes = $score['count'];
		$votes = $score['data']['votes'];

		?>
		<div>
			<strong><?php echo $question; ?></strong>
			<ul>
			<?php
			foreach ( $answers as $i => $text ) {
				$vote_count = empty( $votes[ $i ] ) ? 0 : $votes[ $i ];
				?>
				<li>
					<label class="metric-vote metric-vote-<?php echo $i; ?>">
						<input name="metric-<?php echo $metric_id; ?>" type="radio" value="<?php echo $i; ?>" <?php checked( $user_vote, $i );?>></input>
						<span><?php echo $text; ?></span>
					</label>
					(<span class="metric-score-<?php echo $i; ?>"><?php echo $vote_count; ?></span>)
					<br>
					<progress class="metric-score-<?php echo $i; ?>" max="<?php echo $total_votes; ?>" value="<?php echo $vote_count; ?>"></progress>
				</li>
				<?php
			}
			?>
			<ul>
		</div>
		<?php
	}

	// ===== VOTE / SCORE HANDLING ==== //
	public function validate_vote( $vote, $old_vote, $options ) {
		if ( $vote === '' ) {
			return null;
		}

		$answers_count = preg_match_all( "/\r\n|\n|\r/", $options['answers'] );
		$vote = parent::validate_vote( $vote, $old_vote, $options );
		$vote = intval( $vote ); // Force integer value.

		if ( ! is_integer( $vote ) || $vote < 0 || $vote > $answers_count ) {
			return null;
		} else {
			return $vote;
		}
	}

	public function modify_score( $score, $vote, $old_vote, $metric, $context_id ) {

		if ( $vote !== $old_vote ) {
			if ( $vote === null ) {
				$score['count']--;
			} elseif ( $old_vote === null ) {
				$score['count']++;
			}
		}

		// Initialize the data array
		if ( empty( $score['data']['votes'] ) ) {
			$answers_count = count( preg_split( "/\r\n|\n|\r/", $metric['options']['answers'] ) );

			for ( $i = 0; $i < $answers_count; $i++ ) { 
				$score['data']['votes'][ $i ] = 0;
			}
		}

		// Tally votes
		if ( $old_vote !== null ) {
			$score['data']['votes'][ $old_vote ]--;
		} 

		if ( $vote !== null ) {
			$score['data']['votes'][ $vote ]++;
		}

		// Get the index for the highest value in our scores array.
		$score['average'] = array_keys( $score['data']['votes'], max( $score['data']['votes'] ) )[0];
		$score['value'] = $score['average'];

		return parent::modify_score( $score, $vote, $old_vote, $metric, $context_id );
	}

	// ===== ADMIN FACING CODE ==== //
	public function render_options( $options, $name = 'options[%s]' ) {
		$options = shortcode_atts( array(
			'question' => "",
			'answers' => "",
		), $options );

		?>
		<dt>Question</dt>
		<dd><input type="text" name="<?php printf( $name, 'question' ); ?>" value="<?php echo $options['question']; ?>" autocomplete="off"></input></dd>
		<dt>Answers</dt>
		<dd>
			<textarea name="<?php printf( $name, 'answers' ); ?>"><?php echo $options['answers']; ?></textarea>
			<br>
			<small>One answer per line</small>
		</dd>
		<?php
	}

}

Evaluate_Metrics::register_type( new Evaluate_Metric_Poll() );
