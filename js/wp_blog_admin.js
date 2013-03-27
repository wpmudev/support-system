jQuery(document).ready(function($) {
	$('#upload-file').click( function(e) {
		e.preventDefault();

		var old_send_attachment = wp.media.editor.send.attachment;

		var the_link = $(this);
		var uploading_media = true;

		wp.media.editor.send.attachment = function(args, file){
	      if ( uploading_media ) {
	      	var last_uploaded_file = $('.uploaded-file').last();
	      	if ( last_uploaded_file.length ) {
	      		var next_id = last_uploaded_file.data('fileid') + 1;
	      	}
	      	else {
	      		var next_id = 1;
	      	}

	      	var new_text_node = $('<p></p>')
	      		.html(file.filename + ' <a href="#" class="remove_file" data-fileid="' + next_id + '"><strong>' + translation_strings['remove'] + '</strong></a>')
	      		.attr('id','uploaded-filename-' + next_id);

	      	var new_hidden_node = $('<input type="hidden">')
	      		.attr('id','uploaded-file-' + next_id)
	      		.attr('class','uploaded-file')
	      		.attr('name','uploaded-files[]')
	      		.val(file.id)
	      		.attr('data-fileid', next_id);

	      	new_text_node
	      		.insertBefore(the_link).slideDown();

	      	new_hidden_node
	      		.insertAfter(new_text_node);
	      		
	      } else {
	        return old_send_attachment.apply(this,[args, file]);
	      };
	    }

	    wp.media.editor.open(the_link);
	});

	$('.remove_file').live( 'click', function(e) {
		e.preventDefault();
		var file_id = $(this).data('fileid');
		

		//var data = {
		//	action: 'remove_attachment',
		//	attachment_id: $('#uploaded-file-' + file_id).val()
		//};
//
		//jQuery.post(ajaxurl, data, function(response) {
		//});

		$('#uploaded-file-' + file_id).remove();
		$('#uploaded-filename-' + file_id).remove();
	});
});