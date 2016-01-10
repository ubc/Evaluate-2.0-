
var Evaluate_Rubrics = {

	update_rubric_display: function( metric, args ) {
		console.log("Received", args);
	},

};

jQuery('.metric-form').submit( function( event ) {
	var form = jQuery(this);
	var metric = form.closest( '.metric' );
	var data = form.serializeArray();
	var vote = {};

	for ( var i in data ) {
		vote[ data[i]['name'] ] = data[i]['value'];
	}
	console.log("Submit", vote);

	var metric_id = metric.data( 'id' );
	var nonce = metric.data( 'nonce' );
	var context_id = metric.data( 'context' );

	console.log("send", metric_id, nonce, context_id, "to", Evaluate.ajaxurl);
	jQuery.post( Evaluate.ajaxurl, {
		action: 'vote',
		nonce: nonce,
		metric_id: metric_id,
		context_id: context_id,
		vote: vote,
	}, function( response ) {
		console.log("Received response");
		Evaluate_Rubrics.update_rubric_display( metric, response );
	}, 'json' );

	event.preventDefault();
} );