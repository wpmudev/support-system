(function ( $ ) {

	$.fn.incsub_support_attachments = function( options ) {

		var settings = $.extend( $.fn.incsub_support_attachments.defaults, options );
		var current_slot = settings.first_slot;		

		return this.each( function() {
			var button = $( '<button/>', {
				text: settings.button_text,
				class: settings.button_class
			});

			button.data( 'settings', settings );
			button.data( 'current_slot', current_slot );

			button.click( function( e ) {
				e.preventDefault();
				$.fn.incsub_support_attachments.addFileSlot( $(this) );
			});

			var list = $( '<ul/>', {
				id: 'support-attachments-list'
			});

			button.data( 'list', $(list) );

			$(this)
				.append( list )
				.append( button )
				
		});
		
	};

	$.fn.incsub_support_attachments.defaults = {
		button_text: 'Add files...',
		button_class: 'button-secondary',
		first_slot: 0,
		slot_name: 'support-attachment',
		current_files: [],
		files_list_id: 'support-attachments-list',
		remove_file_title: 'Remove file'
	};

	$.fn.incsub_support_attachments.addFileSlot = function( clicked_button ) {
		var $this = $( clicked_button );

		var settings = $this.data( 'settings' );
		var current_slot = $this.data( 'current_slot' );
		var list = $this.data( 'list' );

		var file_element = $( '<input/>', {
			type: 'file',
			id: settings.slot_name + '-' + current_slot,
			name: settings.slot_name + '[]'
		});

		var remove_file_link = $( '<a>', {
			class: 'remove-file',
			'data-remove-file': settings.slot_name + '-' + current_slot,
			text: '[x]',
			href: '',
			title: settings.remove_file_title
		});

		remove_file_link.click( function( e ) {
			e.preventDefault();
			$.fn.incsub_support_attachments.removeFileSlot( $(this).data('remove-file'), $this );
		});

		list.append( 
			$('<li>' )
				.hide()
				.append( file_element )
				.append( remove_file_link )
				.fadeIn()
		);

		$this.data( 'current_slot', ++current_slot );


	};

	$.fn.incsub_support_attachments.removeFileSlot = function( file_id, button ) {
		var file_slot = $( '#' + file_id );
		if ( file_slot ) {
			file_slot.parent().remove();
		}
	};
 
}( jQuery ));

jQuery(document).ready(function($) {
	$('.support-attachments').incsub_support_attachments();
});
