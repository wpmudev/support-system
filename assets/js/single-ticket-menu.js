jQuery(document).ready(function($) {

	attachment_id = 0;

	$( '#submit-new-attachment' ).click( function(e) {
		e.preventDefault();

		var new_attachment = $('<li id="attachments-list-item-' + attachment_id + '"><input type="file" name="attachments[' +attachment_id + ']"> <a href="#" class="remove_attachment" data-att-id="' + attachment_id + '">[-]</a></li>');
		$( "#attachments-list" ).append( new_attachment );
		attachment_id++;

		$('.remove_attachment').on('click', function(e) {
			e.preventDefault();
			var attachment_data = $(this).data('att-id');
			$('#attachments-list-item-' + attachment_data).remove();
		});
	});


});