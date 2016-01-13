<?php

class Evaluate_Metric_Rubric extends Evaluate_Metric_Type {

	const SLUG = 'rubric';
	const FIELD_KEY = '_field_';

	public function __construct() {
		require_once( Evaluate::$directory_path . 'includes/rubrics.php' );
		parent::__construct( "Rubric", self::SLUG );

		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts_and_styles' ) );
	}

	public static function enqueue_scripts_and_styles() {
		wp_register_script( 'evaluate-rubrics', Evaluate::$directory_url . 'js/evaluate-rubrics.js' );
	}

	// ===== FRONT FACING CODE ==== //
	public function render_display( $metric, $score, $user_vote = null ) {
		wp_enqueue_script( 'evaluate-rubrics' );
		$metric_id = $metric['metric_id'];
		$fields = $metric['options']['fields'];
		$metric_types = Evaluate_Metrics::get_metric_types();
		$value = $score['value'];

		?>
		<form method="POST" class="metric-form">
			<?php
			foreach ( $fields as $index => $field ) {
				?>
				<div class="metric metric-<?php echo $field['type']; ?>">
					<?php
					if ( ! empty( $field['options']['title'] ) ) {
						?>
						<strong class="metric-title"><?php echo $field['options']['title']; ?></strong>
						<?php
					}

					// TODO: Pass user_vote and score
					$score = array(
						'value' => null,
						'average' => null,
					);

					$vote = isset( $user_vote[ $index ] ) ? $user_vote[ $index ] : null;

					// TODO: 'field-'$index expects an integer, not a string
					$field['metric_id'] = 'field-' . $index;

					$metric_types[ $field['type'] ]->render_display( $field, $score, $vote );
					do_action( 'evaluate_render_metric_' . $field['type'], $field['options'], $score, 'field-' . $index, null );
					?>
				</div>
				<?php
			}

			?>
			<input type="submit" value="Submit"></input>
			<span class="metric-rubric-score"><?php echo $value; ?></span>
			<!--span class="metric-rubric-total"><?php echo $user_vote['total']; ?></span-->
		</form>
		<?php
	}

	// ===== VOTE / SCORE HANDLING ==== //
	public function validate_vote( $vote, $old_vote, $options ) {
		if ( ! is_array( $vote ) ) {
			return null;
		}

		$metric_types = Evaluate_Metrics::get_metric_types();
		$result = array();
		$null = true;

		foreach ( $options['fields'] as $index => $field ) {
			$key = 'metric-field-' . $index;

			if ( isset( $vote[ $key ] ) ) {
				$metric_type = $metric_types[ $field['type'] ];
				$result[ $index ] = $metric_type->validate_vote( $vote[ $key ], $old_vote[ $index ], $field['options'] );
				$null = false;
			} else {
				$result[ $index ] = null;
			}
		}

		if ( $result == $old_vote || $null ) {
			return null;
		} else {
			return self::calculate_total( $result, $options );
		}
	}

	public function modify_score( $score, $vote, $old_vote, $metric, $context_id ) {
		$vote_total = isset( $vote['total'] ) ? $vote['total'] : 0;
		$old_vote_total = isset( $old_vote['total'] ) ? $old_vote['total'] : 0;
		$vote_diff = $vote_total - $old_vote_total;
		$score['average'] = $score['average'] * $score['count'];

		error_log( "vote comparison " . var_export( $vote, true ) . ' !== ' . var_export( $old_vote, true ) . ' = ' . ( $vote !== $old_vote ) );
		if ( $vote !== $old_vote ) {
			if ( $vote === null ) {
				$score['count']--;
				error_log('decrement to ' . $score['count']);
			} elseif ( $old_vote === null ) {
				$score['count']++;
				error_log('increment to ' . $score['count']);
			}
		}

		if ( $score['count'] == 0 ) {
			$score['average'] = 0;
		} else {
			$score['average'] = ( $score['average'] + $vote_diff ) / $score['count'];
		}

		$score['value'] = $score['average'];
		return parent::modify_score( $score, $vote, $old_vote, $metric, $context_id );
	}

	public function set_vote( $vote, $metric, $context_id, $user_id ) {
		foreach ( $metric['options']['fields'] as $index => $field ) {
			$field_vote = isset( $vote[ $index ] ) ? $vote[ $index ] : null;
			$field_context = $context_id . self::FIELD_KEY . $index;

			parent::set_vote( $field_vote, $metric, $field_context, $user_id );
		}
	}

	public function get_vote( $metric, $context_id, $user_id ) {
		$vote = array();
		$null = true;

		foreach ( $metric['options']['fields'] as $index => $field ) {
			$field_context = $context_id . self::FIELD_KEY . $index;
			$vote[ $index ] = parent::get_vote( $metric, $field_context, $user_id );

			if ( $vote[ $index ] !== null ) $null = false;
		}

		if ( $null ) {
			return null;
		} else {
			return self::calculate_total( $vote, $metric['options'] );
		}
	}

	public function calculate_total( $vote, $options ) {
		$vote['total'] = 0;

		foreach ( $options['fields'] as $index => $field ) {
			$vote['total'] += $vote[ $index ] * $field['weight'];
		}

		return $vote;
	}

	// ===== ADMIN FACING CODE ==== //
	public function render_options( $options, $name = 'options[%s]' ) {
		$options = shortcode_atts( array(
			'rubric' => '',
			'fields' => array(),
		), $options );

		$rubrics = apply_filters( 'evaluate_get_rubrics', array() );

		$metric_types = Evaluate_Metrics::get_metric_types();
		foreach ( $metric_types as $slug => $title ) {
			if ( $slug === self::SLUG ) {
				unset( $metric_types[ $slug ] );
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
							echo $field['title'] . " - a " . $metric_types[ $field['type'] ]->name . " metric, with " . $field['weight'] . " weight.";
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
				foreach ( $metric_types as $slug => $metric_type ) {
					?>
					<option value="<?php echo $slug; ?>" <?php selected( $slug, $field['type'] ); ?>>
						<?php echo $metric_type->name; ?>
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
				<dl class="field-options-<?php echo $slug; ?> field-options"<?php echo $slug == $field['type'] ? '' : ' style="display: none;"'; ?>>
					<?php
					$options_name = sprintf( $name, 'options' ) . '[%s]';
					$metric_type->render_options( $field['options'], $options_name );
					?>
				</dl>
				<?php
			}
			?>
		</li>
		<?php
	}

}

Evaluate_Metrics::register_type( new Evaluate_Metric_Rubric() );
