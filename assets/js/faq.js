jQuery(document).ready(function($) {
	$( ".accordion" ).accordion({collapsible:true,active:false,heightStyle:'content'});	
	$( "#tabs" ).tabs();

	$( '.vote-button' ).click( function(e) {
		e.preventDefault();
		var vote = $(this).data('vote');
		var parent = $(this).parent();
		var faq_id = parent.data('faq-id');
		parent.find('button').attr( 'disabled', 'true' );

		var loader = parent.find('img');
		loader.show();

		var data = {
			vote: vote,
			faq_id: faq_id,
			action: 'vote_faq_question'
		}
		$.post( ajaxurl, data, function(response) {
			loader.hide();
		});
	})
});