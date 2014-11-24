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

	$( '.faq-category-question' ).hide();
	

	$( '.faq-category' ).click( function( e ) {
		e.preventDefault();

		var cat_id = $(this).data('cat-id');

		$( '.faq-category-question' ).fadeOut();
		$( '#faq-category-' + cat_id ).fadeIn();
	});

	$('.faq-category-answer').hide();
	$('.faq-question-selector').click( function(e) {
		e.preventDefault();

		var faq_id = $(this).data('faq-id');
		$('.faq-category-answer').slideUp();

		$( '#faq-answer-' + faq_id ).slideDown();

	});
});