( function () {
	tinymce.PluginManager.add( 'incsub_support_shortcodes', function ( editor ) {
		var ed = tinymce.activeEditor;

		var support_system_menu = [
			{
				text: ed.getLang( 'support_system_shortcodes.tickets_list_menu_title' ),
				onclick: function () {
					editor.insertContent( '[support-system-tickets-index]' );
				}
			},
			{
				text: 'FAQs index',
				onclick: function () {
					editor.insertContent( '[support-system-faqs]' );
				}
			},
		];


		if ( ed.getLang( 'support_system_shortcodes.is_network' ) == 1 ) {
			support_system_menu.push({
				text: ed.getLang( 'support_system_shortcodes.submit_ticket_form_text' ),
				onclick: function () {
					editor.windowManager.open({
						title: ed.getLang( 'support_system_shortcodes.submit_ticket_form_submit_ticket_form_title' ),
						body: [
							{
								type:    'checkbox',
								name:    'blog_field',
								label:   ed.getLang( 'support_system_shortcodes.submit_ticket_form_blog_field_label' ),
								checked: true
							},
							{
								type:    'checkbox',
								name:    'category_field',
								label:   ed.getLang( 'support_system_shortcodes.submit_ticket_form_category_field_label' ),
								checked: true
							},
							{
								type:    'checkbox',
								name:    'priority_field',
								label:   ed.getLang( 'support_system_shortcodes.submit_ticket_form_priority_field_label' ),
								checked: true
							}
						],
						onsubmit: function ( e ) {
							var blog_field = e.data.blog_field ? '' : ' blog_field="0"';
							var category_field = e.data.category_field ? '' : ' category_field="0"';
							var priority_field = e.data.priority_field ? '' : ' priority_field="0"';

							editor.insertContent( '[support-system-submit-ticket-form' + blog_field + ' ' + category_field + ' ' + priority_field + ']' );
						}
					});
				}
			});
		}
		else {
			support_system_menu.push({
				text: ed.getLang( 'support_system_shortcodes.submit_ticket_form_text' ),
				onclick: function () {
					editor.windowManager.open({
						title: ed.getLang( 'support_system_shortcodes.submit_ticket_form_submit_ticket_form_title' ),
						body: [
							{
								type:    'checkbox',
								name:    'category_field',
								label:   ed.getLang( 'support_system_shortcodes.submit_ticket_form_category_field_label' ),
								checked: true
							},
							{
								type:    'checkbox',
								name:    'priority_field',
								label:   ed.getLang( 'support_system_shortcodes.submit_ticket_form_priority_field_label' ),
								checked: true
							}
						],
						onsubmit: function ( e ) {
							var category_field = e.data.category_field ? '' : ' category_field="0"';
							var priority_field = e.data.priority_field ? '' : ' priority_field="0"';

							editor.insertContent( '[support-system-submit-ticket-form' + category_field + ' ' + priority_field + ']' );
						}
					});
				}
			});
		}
		editor.addButton( 'incsub_support_shortcodes', {
			icon: 'mce-i-incsub-support-sos',
			type: 'menubutton',
			menu: support_system_menu			
		});
	});
})();