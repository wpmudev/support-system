( function () {
	tinymce.PluginManager.add( 'incsub_support_shortcodes', function ( editor ) {
		var ed = tinymce.activeEditor;
		editor.addButton( 'incsub_support_shortcodes', {
			icon: 'mce-i-incsub-support-sos',
			type: 'menubutton',
			menu: [
				{
					text: 'Tickets list',
					onclick: function () {
						editor.insertContent( '[support-system-tickets-index]' );
					}
				},
				{
					text: 'Submit ticket form',
					onclick: function () {
						editor.insertContent( '[support-system-submit-ticket-form]' );
					}
				}
			]			
		});
	});
})();