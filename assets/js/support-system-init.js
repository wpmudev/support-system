jQuery(document).ready(function($) {
	$('#support-system').support_system({
		attachments: {
			container_selector: '.support-system-attachments',
			button_text: support_system_i18n.button_text,
			button_class: 'button tiny success',
			remove_file_title: support_system_i18n.remove_file_title,
			remove_link_class: "button tiny alert",
			remove_link_text: support_system_i18n.remove_link_text,
			description: support_system_i18n.desc
		}
	});

});