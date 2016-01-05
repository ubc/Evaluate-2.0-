<?php

/**
 * This class provides an interface for the plugin.
 * All functions in this class will be kept as stable as possible.
 * 
 * Many of the functions may simply be a wrapper for functions elsewhere in the plugin, but these functions are preferrable, because the rest of the plugin may change while this class will generally not change.
 */
class Evaluate_API {

	// ===== HOOKS AND FILTERS ===== //



	// ===== FUNCTIONS ===== //

	public static function register_metric_type() {
		Evaluate_Metric::register_type();
	}

	public static function set_vote() {
		
	}

	public static function get_metric_data() {

	}

	public static function get_user_data() {

	}

	public static function render_metric_instance() {
		
	}
	
}