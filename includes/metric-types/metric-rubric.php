<?php

class Evaluate_Metric_Rubric {

	public static function init() {
		Evaluate_Metrics::register_type( array(
			'title'   => "Rubric",
			'slug'    => 'rubric',
			'render'  => array( __CLASS__, 'render' ),
			'score'   => array( __CLASS__, 'score' ),
			'options' => array( __CLASS__, 'render_options' ),
		) );
	}

	public static function render( $options, $user_vote = null ) {
		?>
		Rubric
		<?php
		var_dump( $options );
	}

	public static function score() {

	}

	public static function render_options( $options ) {
		$options = shortcode_atts( array(
			'rubric' => "",
		), $options );

		?>
		<dt>Predefined Rubric</dt>
		<dd>
			<select name="options[rubric]">
				<?php
				$rubrics = array();

				foreach ( $rubrics as $slug => $text ) {
					?>
					<option value="<?php echo $slug; ?>" <?php selected( $slug, $options['icon'] ); ?>>
						<?php echo $text; ?>
					</option>
					<?php
				}
				?>
			</select>
		</dd>
		<?php
	}

}

Evaluate_Metric_Rubric::init();
