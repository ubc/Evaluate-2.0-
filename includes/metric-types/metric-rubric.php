<?php

class Evaluate_Metric_Rubric {

	public static function init() {
		require_once( Evaluate::$directory_path . 'includes/rubrics.php' );

		Evaluate_Metrics::register_type( array(
			'title'   => "Rubric",
			'slug'    => 'rubric',
			'render'   => array( __CLASS__, 'render_metric' ),
			'validate' => array( __CLASS__, 'validate_vote' ),
			'score'    => array( __CLASS__, 'adjust_score' ),
			'options'  => array( __CLASS__, 'render_options' ),
		) );

		// TODO: convert all metric type callbacks to actions/filters, for improved extensibility.
		//add_filter( 'evaluate_validate_rubric_options', array( __CLASS__, 'validate_options' ) );
	}

	public static function render_metric( $options, $user_vote = null ) {
		foreach ( $options['fields'] as $index => $field ) {
			?>
			<span class="submetric submetric-<?php echo $metric['type']; ?>">
				<?php
				if ( ! empty( $field['options']['title'] ) ) {
					?>
					<strong class="metric-title"><?php echo $field['options']['title']; ?></strong>
					<?php
				}

				// TODO: Pass user_vote and score
				call_user_func( Evaluate_Metrics::get_type( $field['type'] )['render'], $field['options'], array(), null );
				?>
			</span>
			<?php
		}

		?>
		<input type="submit" class="metric-submit"></input>
		<hr>
		<?php
		var_dump( $options );
	}

	public static function validate_vote( $options, $vote ) {
		return $vote;
	}

	public static function adjust_score() {

	}

	public static function render_options( $options, $name = 'options[%s]' ) {
		$options = shortcode_atts( array(
			'rubric' => "",
			'fields' => array(),
		), $options );

		$rubrics = apply_filters( 'evaluate_get_rubrics', array() );

		$metric_types = Evaluate_Metrics::get_metric_types();
		foreach ( $metric_types as $key => $metric_type ) {
			if ( $metric_type['slug'] === 'rubric' ) {
				unset( $metric_types[ $key ] );
			}
		}

		?>
		<dt>Rubric Definition</dt>
		<dd>
			<select class="nav" data-anchor="rubric-options" data-siblings="true" name="<?php printf( $name, 'rubric' ); ?>">
				<option value="">- Choose a Rubric -</option>
				<option value="custom" <?php selected( $options['rubric'], 'custom' ); ?>>Custom</option>
				<?php
				foreach ( $rubrics as $slug => $rubric ) {
					?>
					<option value="<?php echo $slug; ?>" <?php selected( $slug, $options['rubric'] ); ?>>
						<?php echo $rubric['name']; ?>
					</option>
					<?php
				}
				?>
			</select>
			<ul id="rubric-fields" class="rubric-options-custom rubric-options"<?php echo $options['rubric'] == 'custom' ? '' : ' style="display: none;"'; ?>>
				<?php
				$field_name = sprintf( $name, 'fields' ) . '[#][%s]';
				// ^ The '#' character will be replaced using JavaScript. If you change it, make sure you update evaluate-admin.js!
				// This is to get around the fact that "options['fields'][]['title']" is not a valid name attribute.

				if ( ! empty( $options['fields'] ) ) {
					foreach ( $options['fields'] as $index => $field ) {
						self::render_field( $metric_types, $field_name, $field );
					}
				}

				self::render_field( $metric_types, $field_name );
				?>
			</ul>
			<?php
			foreach ( $rubrics as $slug => $rubric ) {
				?>
				<div class="rubric-options-<?php echo $slug; ?> rubric-options"<?php echo $slug == $options['rubric'] ? '' : ' style="display: none;"'; ?>>
					<p><?php echo $rubric['description']; ?></p>
					<strong>Fields:</strong>
					<ul>
						<?php
						foreach ( $rubric['fields'] as $key => $field ) {
							echo $field['title'] . " - a " . $metric_types[ $field['type'] ]['title'] . " metric, with " . $field['weight'] . " weight.";
						}
						?>
					</ul>
				</div>
				<?php
			}
			?>
		</dd>
		<?php
	}

	private static function render_field( $metric_types, $name, $field = array() ) {
		$field = shortcode_atts( array(
			'title'   => "",
			'weight'  => '',
			'type'    => '',
			'options' => array(),
		), $field );

		?>
		<li class="rubric-field<?php echo empty( $field['type'] ) ? ' empty' : ''; ?>">
			<select class="nav rubric-field-type" data-anchor="field-options" data-siblings="true" name="<?php printf( $name, 'type' ); ?>">
				<option value=""> - Type - </option>
				<?php
				foreach ( $metric_types as $key => $metric_type ) {
					?>
					<option value="<?php echo $metric_type['slug']; ?>" <?php selected( $metric_type['slug'], $field['type'] ); ?>>
						<?php echo $metric_type['title']; ?>
					</option>
					<?php
				}
				?>
			</select>
			<input name="<?php printf( $name, 'title' ); ?>" type="text" placeholder="Title" value="<?php echo $field['title']; ?>"></input>
			<input name="<?php printf( $name, 'weight' ); ?>" type="number" placeholder="Weight (1.0)" value="<?php echo $field['weight']; ?>"></input>
			<?php
			foreach ( $metric_types as $slug => $metric_type ) {
				?>
				<dl class="field-options-<?php echo $metric_type['slug']; ?> field-options"<?php echo $metric_type['slug'] == $field['type'] ? '' : ' style="display: none;"'; ?>>
					<?php
					$options_name = sprintf( $name, 'options' ) . '[%s]';
					echo $metric_type['options']( $field['options'], $options_name );
					?>
				</dl>
				<?php
			}
			?>
		</li>
		<?php
	}

}

Evaluate_Metric_Rubric::init();
