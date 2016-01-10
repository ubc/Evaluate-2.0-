
console.log('load');

var Evaluate = {

	ajaxurl: evaluate_ajaxurl, // Defined using wp_localize_script

	get_vote: function( input ) {
		var tag = input.prop('tagName').toLowerCase();
		var type = input.attr('type')

		if ( tag == 'input' && ( type == 'checkbox' || type == 'radio' ) && ! input.prop('checked') ) {
			return null;
		}

		return input.val()
	},

	update_metric_display: function( metric, args ) {
		console.log("Got data", args, args.data.votes);
		metric.find( '.metric-score' ).text( Math.round( args.average ) );

		if ( typeof args.data.votes !== 'undefined' ) {
			console.log("adding votes");
			for ( var i = args.data.votes.length - 1; i >= 0; i-- ) {
				console.log("adding vote " + i);
				metric.find( 'span.metric-score-' + i ).text( args.data.votes[i] );
				var progress = metric.find( 'progress.metric-score-' + i );
				progress.val( args.data.votes[i] );
				progress.attr( 'max', args.count );
			}
		}
	},

}

// Prevent the no-JS fallback.
jQuery( '.metric .metric-vote a' ).click( function( event ) {
	event.preventDefault();
} );

jQuery( '.metric .metric-vote input[type="radio"]' ).click( function( event ) {
	var input = jQuery(this);

	// TODO: Defined data 'previous' in the HTML
	if ( input.data('previous') == "true" ) {
		input.data( 'previous', 'false' );
		input.attr( 'checked', false );
		input.change();
	} else {
		console.log(input.attr('name'));
		jQuery('input[name="'+input.attr('name')+'"]').data( 'previous', 'false' );
		input.data( 'previous', 'true' );
	}
} );

jQuery( '.metric .metric-vote *:input' ).change( function( event ) {
	var element = jQuery(this);
	var metric = element.closest( '.metric' );
	var vote = Evaluate.get_vote( element );
	console.log("Changed to", vote);

	var metric_id = metric.data( 'id' );
	var nonce = metric.data( 'nonce' );
	var context_id = metric.data( 'context' );

	if ( metric_id != null && nonce != null && context_id != null ) {
		console.log('voting', vote, 'on', '#'+metric.data('id'));
		
		jQuery.post( Evaluate.ajaxurl, {
			action: 'vote',
			nonce: nonce,
			metric_id: metric_id,
			context_id: context_id,
			vote: vote,
		}, function( response ) {
			Evaluate.update_metric_display( metric, response );
		}, 'json' );
	}
} );

/*
jQuery( '.metric-vote' ).click( function( event ) {
	var element = jQuery(this);
	var metric = element.closest( '.metric' );
	var vote = null;

	if ( ! element.hasClass('active') ) {
		vote = element.data( 'value' );
	}

	console.log('voting', vote, 'on', '#'+metric.data('id'));

	jQuery.post( ajaxurl, {
		action: 'vote',
		nonce: metric.data( 'nonce' ),
		metric_id: metric.data( 'id' ),
		context_id: metric.data( 'context' ),
		vote: vote,
	}, function( response ) {
		console.log( "Received", response );
		if ( response == 0 ) return;

		metric.find( '.metric-score' ).text( Math.round( response.average ) );

		var active_option = metric.find( '.metric-vote.active' );

		if ( response.vote != active_option.data('value') ) {
			active_option.removeClass('active');

			if ( response.vote != null ) {
				metric.find( '.metric-vote-' + response.vote ).addClass('active');
			}
		}

		if ( typeof response.data.votes !== 'undefined' ) {
			console.log("adding votes");
			for ( var i = response.data.votes.length - 1; i >= 0; i-- ) {
				console.log("adding vote " + i);
				metric.find( 'span.metric-score-' + i ).text( response.data.votes[i] );
				var progress = metric.find( 'progress.metric-score-' + i );
				progress.val( response.data.votes[i] );
				progress.attr( 'max', response.count );
			}
		}
	}, 'json' );

	event.stopPropagation();
} );

jQuery( '.metric-input' ).blur( function() {
	var element = jQuery(this);
	var metric = element.closest( '.metric' );
	var vote = element.val();

	console.log('test', vote, '==', element.data('value'), vote == element.data( 'value' ));
	if ( vote == element.data( 'value' ) ) {
		return;
	}

	console.log('voting', vote, 'on metric', '#'+metric.data('id'));

	jQuery.post( ajaxurl, {
		action: 'vote',
		nonce: metric.data( 'nonce' ),
		metric_id: metric.data( 'id' ),
		context_id: metric.data( 'context' ),
		vote: vote,
	}, function( response ) {
		console.log( "Received", response );
		if ( response == 0 ) return;

		metric.find( '.metric-score' ).text( Math.round( response.average ) );
		element.data( 'value', response.vote );
		element.val( response.vote );
	}, 'json' );
} );

jQuery( '.metric-submit' ).click( function() {

} );
*/
