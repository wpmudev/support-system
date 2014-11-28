jQuery(document).ready(function($) {
	//$( ".accordion" ).accordion({collapsible:true,active:false,heightStyle:'content'});	

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
	});

	$( '.faq-category-wrap' )
		.hide()
		.find( '.postbox' )
			.addClass( 'closed' )
			.find( '.inside' )
				.hide();

	

	$( '.faq-category' ).click( function( e ) {
		e.preventDefault();

		var cat_id = $(this).data('cat-id');

		$( '.faq-category-wrap' ).fadeOut();
		$( '#faq-category-' + cat_id ).fadeIn();
	});

	$('.faq-category-wrap .postbox').find( '.handlediv, .hndle' ).click( function(e) {
		e.preventDefault();

		var postbox = $(this).parent();
		postbox.find('.inside').toggle();
		
		if ( postbox.hasClass( 'closed' ) ) {
			postbox.removeClass( 'closed' );
		}
		else {
			postbox.addClass( 'closed' );
		}



	});
});