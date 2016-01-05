
jQuery(document).ready( function() {
	jQuery( 'body' ).on( 'change', 'select.nav', function() {
		var element = jQuery(this);
		var anchor = element.data('anchor');

		console.log( element.data('siblings') );
		if ( element.data('siblings') == true ) {
			element.siblings( '.' + anchor ).hide();
			element.siblings( '.' + anchor + '-' + element.val() ).show();
		} else {
			jQuery( '.' + anchor ).hide();
			jQuery( '.' + anchor + '-' + element.val() ).show();
		}
	} );

	jQuery( 'form' ).submit( function( event ) {
		jQuery( '.options:hidden *:input' ).prop( 'disabled', true );

		// Properly index the rubric fields.
		if ( jQuery('#type').val() === 'rubric' ) {
			jQuery( '.rubric-options:hidden *:input' ).prop( 'disabled', true );
			jQuery( '.field-options:hidden *:input' ).prop( 'disabled', true );
			jQuery( '.rubric-field.empty *:input' ).prop( 'disabled', true );

			jQuery( '.rubric-field' ).each( function( index ) {
				jQuery(this).find( '*:input' ).each( function() {
					var element = jQuery(this);
					element.attr( 'name', element.attr( 'name' ).replace( '#', index ) );
				} );
			} );
		}
	} );

	// Rubric fields
	var rubric_field_template = jQuery( '.rubric-field.empty' ).last().clone();

	jQuery( '#rubric-fields' ).on( 'change', '.rubric-field-type', function() {
		var element = jQuery(this);
		var field = element.parent();

		if ( element.val() == "" ) {
			field.remove();
		} else if ( field.hasClass( 'empty' ) ) {
			field.removeClass( 'empty' );
			element.children('option:first-child').text( "- Delete -" );
			jQuery( '#rubric-fields' ).append( rubric_field_template.clone() );
		}
	} );

	// TODO: Move all rubric related code to it's own file somewhere else.
} );
