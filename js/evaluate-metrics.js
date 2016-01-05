
console.log('load');

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
