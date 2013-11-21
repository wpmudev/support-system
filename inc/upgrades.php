<?php



/**
 * Groups the old settings into just one setting option
 * 
 * @since 1.9
 */
function incsub_support_group_settings_upgrade() {
	$saved_version = get_site_option( 'incsub_support_version', false );
	if ( ! $saved_version || version_compare( $saved_version, '1.9' ) < 0 ) {
		// We're going to group all settings into one option
		$default_settings = MU_Support_System::get_default_settings();
		$old_settings = array(
			'incsub_support_menu_name' => get_site_option( 'incsub_support_menu_name', $default_settings['incsub_support_menu_name'] ),
			'incsub_support_from_name' => get_site_option( 'incsub_support_from_name', $default_settings['incsub_support_from_name'] ),
			'incsub_support_from_mail' => get_site_option( 'incsub_support_from_mail', $default_settings['incsub_support_from_mail'] ),
			'incsub_support_fetch_imap' => get_site_option('incsub_support_fetch_imap', $default_settings['incsub_support_fetch_imap'] ),
			'incsub_support_imap_frequency' => get_site_option('incsub_support_imap_frequency', $default_settings['incsub_support_imap_frequency'] ),
			'incsub_allow_only_pro_sites' => get_site_option( 'incsub_allow_only_pro_sites', $default_settings['incsub_allow_only_pro_sites'] ),
			'incsub_pro_sites_level' => get_site_option( 'incsub_pro_sites_level', $default_settings['incsub_pro_sites_level'] ),
			'incsub_allow_only_pro_sites_faq' => get_site_option( 'incsub_allow_only_pro_sites_faq', $default_settings['incsub_allow_only_pro_sites_faq'] ),
			'incsub_pro_sites_faq_level' => get_site_option( 'incsub_pro_sites_faq_level', $default_settings['incsub_pro_sites_faq_level'] ),
			'incsub_ticket_privacy' => get_site_option( 'incsub_ticket_privacy', $default_settings['incsub_ticket_privacy'] ),
			'incsub_support_faq_enabled' => get_site_option( 'incsub_support_faq_enabled', false ),
			'incsub_support_tickets_role' => get_site_option( 'incsub_support_tickets_role', $default_settings['incsub_support_tickets_role'] ),
			'incsub_support_faqs_role' => get_site_option( 'incsub_support_faqs_role', $default_settings['incsub_support_faqs_role'] )
		);
		update_site_option( 'incsub_support_settings', $old_settings );

		foreach ( $old_settings as $key => $value ) {
			delete_site_option( $key );
		}
	}
}


/**
 * Upgrades the plugin
 * 
 * @since 1.8
 * 
 */
function incsub_support_check_for_upgrades() {

	$saved_version = get_site_option( 'incsub_support_version', false );

	if ( ! $saved_version || version_compare( $saved_version, INCSUB_SUPPORT_PLUGIN_VERSION ) < 0 ) {

		if ( version_compare( $saved_version, '1.7.2.2' ) < 0 )
			incsub_support_upgrade_1722();

		if ( version_compare( $saved_version, '1.8' ) < 0 )
			incsub_support_upgrade_18();

		if ( version_compare( $saved_version, '1.8.1' ) < 0 )
			incsub_support_upgrade_181();

		if ( version_compare( $saved_version, '1.9.1' ) < 0 ) {
			incsub_support_set_new_roles();
		}

		if ( version_compare( $saved_version, '1.9.6' ) < 0 ) {
			incsub_support_upgrade_196();
		}

		if ( version_compare( $saved_version, '1.9.8' ) < 0 ) {
			incsub_support_upgrade_198();
		}

		if ( version_compare( $saved_version, '1.9.8.1' ) < 0 ) {
			incsub_support_upgrade_1981();
		}

		if ( version_compare( $saved_version, '2.0beta.0' ) < 0 ) {
			
			
		}

		update_site_option( 'incsub_support_version', INCSUB_SUPPORT_PLUGIN_VERSION );
	}

}

/**
 * Sets a new system based on roles instead of capabilities
 * 
 * @since 1.9.1
 */
function incsub_support_set_new_roles() {
	global $wp_roles;

	$roles_settings = array( 
		'incsub_support_tickets_role' => MU_Support_System::$settings['incsub_support_tickets_role'], 
		'incsub_support_faqs_role' => MU_Support_System::$settings['incsub_support_faqs_role'] 
	);

	foreach ( $roles_settings as $key => $value ) {
		switch ( $value ) {
			case 'manage_options':
				MU_Support_System::$settings[ $key ] = array( 'administrator' );
				break;
			case 'publish_pages':
				MU_Support_System::$settings[ $key ] = array( 'administrator', 'editor' );
				break;
			case 'publish_posts':
				MU_Support_System::$settings[ $key ] = array( 'administrator', 'editor', 'author' );
				break;
			case 'edit_posts':
				MU_Support_System::$settings[ $key ] = array( 'administrator', 'editor', 'author', 'contributor' );
				break;
			case 'read':
				MU_Support_System::$settings[ $key ] = array( 'administrator', 'editor', 'author', 'contributor', 'subscriber' );
				break;
		}
	}

	update_site_option( 'incsub_support_settings', MU_Support_System::$settings );
}

function incsub_support_upgrade_1981() {
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	$model = incsub_support_get_faq_model();
	$model->create_tables();
}

function incsub_support_upgrade_198() {
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	$model = incsub_support_get_faq_model();
	$model->create_tables();
	$model->update_faq_counts();
}

function incsub_support_upgrade_196() {
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	$model = incsub_support_get_ticket_model();
	$model->create_tables();
}

/**
 * Upgrades the Database to 1.8 version
 * 
 * @since 1.8
 */
function incsub_support_upgrade_18() {
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	$ticket_model = incsub_support_get_ticket_model();
	$faq_model = incsub_support_get_faq_model();
	$ticket_model->create_tables();

	// Converting FAQs entities to text
	// So they can be properly displayed in the WP Editor
	global $wpdb;
	$faqs_ids = $wpdb->get_col( "SELECT faq_id FROM $faq_model->faq_table" );

	if ( ! empty( $faqs_ids ) ) {
		foreach( $faqs_ids as $faq_id ) {
			$faq = $wpdb->get_row( 
				"SELECT question, answer, cat_id 
				FROM $faq_model->faq_table
				WHERE faq_id = $faq_id",
				ARRAY_A );
			$answer = htmlspecialchars_decode( stripslashes_deep( html_entity_decode( str_replace( '&nbsp;', '<br/>', $faq['answer'] ), ENT_QUOTES ) ) );
			$question = stripslashes_deep( html_entity_decode( $faq['question'], ENT_QUOTES ) );
			$cat_id = $faq['cat_id'];
			$faq_model->update_faq_question( $faq_id, $question, $answer, $cat_id );
		}
	}

	// Checking all tickets as viewed by a Super Admin
	$wpdb->query( "UPDATE $ticket_model->tickets_table SET view_by_superadmin = 1" );

	// Same for tickets messages
	$messages_ids = $wpdb->get_col( "SELECT message_id FROM $ticket_model->tickets_messages_table" );
	if ( ! empty( $messages_ids ) ) {
		foreach( $messages_ids as $message_id ) {
			$message = $wpdb->get_row( 
				"SELECT subject, message 
				FROM $ticket_model->tickets_messages_table
				WHERE message_id = $message_id",
				ARRAY_A );
			$subject = stripslashes_deep( $message['subject'] );
			$message_text = wpautop( stripslashes_deep( $message['message'] ) );
			$ticket_model->update_ticket_message( $message_id, $subject, $message_text );
		}
	}
}

/**
 * Upgrades the Database to 1.8.1 version
 * 
 * @since 1.8.1
 */
function incsub_support_upgrade_181() {
	global $wpdb;
	
	$ticket_model = incsub_support_get_ticket_model();
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	$ticket_model->create_tickets_table();				
}

/**
 * Upgrades the Database to 1.7.2.2 version
 * 
 * @since 1.8
 */
function incsub_support_upgrade_1722() {
	global $wpdb;

	$faq_model = incsub_support_get_faq_model();
	$faq_cats = $wpdb->get_results("SELECT cat_id, site_id FROM $faq_model->faq_cats_table;");

	foreach ($faq_cats as $faq_cat) {
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE $faq_model->faq_cats_table 
				SET qcount = (SELECT COUNT(*) FROM $faq_model->faq_table 
					WHERE cat_id = '%d' 
					AND site_id = '%d') 
				WHERE cat_id = %d AND site_id = %d;", 
				$faq_cat->cat_id, 
				$faq_cat->site_id, 
				$faq_cat->cat_id, 
				$faq_cat->site_id
			)
		);
	}
}