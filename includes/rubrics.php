<?php

add_filter( 'evaluate_get_rubrics', function( $rubrics ) {
	$rubrics['sample'] = array(
		'name' => "Sample Rubric",
		'description' => "This is an example definition of a rubric.",
		'fields' => array(
			array(
				'type'    => 'one-way',
				'title'   => "Un",
				'weight'  => 1.0,
				'options' => array(),
			),
		),
	);

	return $rubrics;
} );
