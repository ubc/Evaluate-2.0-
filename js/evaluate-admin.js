
jQuery(document).ready( function() {
	jQuery( '#type' ).change( function() {
		jQuery( '.options' ).hide();
		jQuery( '#options-' + jQuery(this).val() ).show();
	} );

	jQuery( 'form' ).submit( function() {
		jQuery( '.options:hidden *:input' ).prop( 'disabled', true );
	} );
} );
