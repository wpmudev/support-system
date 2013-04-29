jQuery(document).ready(function($) {
	$( '#fetch_imap' ).change( function() {
		if ( $(this).val() == 'enabled' )
			$('.imap-settings').attr('disabled',false);
		else
			$('.imap-settings').attr('disabled',true);
	});
});