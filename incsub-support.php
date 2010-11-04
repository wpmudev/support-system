<?php
/*
 ﻿Plugin Name: MU Support System
 Plugin URI: http://premium.wpmudev.org/project/support-system
 Description: Support System for WordPress multi site 
 Author: S H Mohanjith (Incsub), Luke Poland (Incsub), Andrew Billits (Incsub)
 WDP ID: 119
 Version: 1.5.5a5
 Author URI: http://premium.wpmudev.org
*/

define('INCSUB_SUPPORT_VERSION', '1.5.5a5');
define('INCSUB_SUPPORT_LANG_DOMAIN', 'incsub-support');

global $ticket_status, $ticket_priority;

function incsub_support() {
	// We only need a single set of databases for the whole network
	register_activation_hook(__FILE__, 'incsub_support_install');
	register_deactivation_hook(__FILE__, 'incsub_support_uninstall');
	
	add_action('init', 'incsub_support_init');
	add_action('admin_menu', 'incsub_support_menu');
	add_action('admin_print_styles-ms-admin_page_support-options', 'incsub_support_admin_styles');
	add_action('admin_print_styles-toplevel_page_incsub_support', 'incsub_support_admin_styles');
	add_action('admin_print_styles-'.sanitize_title_with_dashes(__(get_site_option('incsub_support_menu_name', 'Support'), INCSUB_SUPPORT_LANG_DOMAIN)).'_page_incsub_support_tickets', 'incsub_support_admin_styles');
	add_action('admin_print_styles-'.sanitize_title_with_dashes(__(get_site_option('incsub_support_menu_name', 'Support'), INCSUB_SUPPORT_LANG_DOMAIN)).'_page_incsub_support_faq', 'incsub_support_admin_styles');
	
	add_action('admin_print_scripts-ms-admin_page_support-options', 'incsub_support_admin_script');
	add_action('admin_print_scripts-toplevel_page_incsub_support', 'incsub_support_admin_script');
	add_action('admin_print_scripts-'.sanitize_title_with_dashes(__(get_site_option('incsub_support_menu_name', 'Support'), INCSUB_SUPPORT_LANG_DOMAIN)).'_page_incsub_support_tickets', 'incsub_support_admin_script');
	add_action('admin_print_scripts-'.sanitize_title_with_dashes(__(get_site_option('incsub_support_menu_name', 'Support'), INCSUB_SUPPORT_LANG_DOMAIN)).'_page_incsub_support_faq', 'incsub_support_admin_script');
	add_action('admin_print_scripts-support_page_incsub_support_faq', 'incsub_support_admin_script');
	add_action('incsub_support_fetch_imap', 'incsub_support_fetch_imap');
	
	add_filter('whitelist_options', 'incsub_support_whitelist_options');
	add_filter('cron_schedules', 'incsub_support_cron_schedules');
	
	if (is_admin()) {
		wp_register_style('incsub_support_admin_css', plugins_url('incsub-support/css/wp_admin.css'));
		wp_register_script('incsub_support_admin_js', plugins_url('incsub-support/js/wp_admin.js'), array('jquery'), INCSUB_SUPPORT_VERSION, true);
	}
}

function incsub_support_init() {
	global $wpdb, $ticket_status, $ticket_priority;
	
	if (preg_match('/mu\-plugin/', __FILE__) > 0) {
		load_muplugin_textdomain(INCSUB_SUPPORT_LANG_DOMAIN, false, dirname(plugin_basename(__FILE__)).'/languages');
	} else {
		load_plugin_textdomain(INCSUB_SUPPORT_LANG_DOMAIN, false, dirname(plugin_basename(__FILE__)).'/languages');
	}
	
	$wpdb->tickets = incsub_support_tablename('tickets');
	$wpdb->tickets_messages = incsub_support_tablename('tickets_messages');
	$wpdb->tickets_cats = incsub_support_tablename('tickets_cats');
	$wpdb->faq = incsub_support_tablename('faq');
	$wpdb->faq_cats = incsub_support_tablename('faq_cats');
	
	$ticket_priority = array(
		0	=>	"Low",
		1	=>	"Normal",
		2	=>	"Elevated",
		3	=>	"High",
		4	=>	"Critical"
	);

	$ticket_status = array(
		0	=>	"New",
		1	=>	"In progress",
		2	=>	"Waiting on User to reply",
		3	=>	"Waiting on Admin to reply",
		4	=>	"Stalled",
		5	=>	"Closed"
	);
	
	$incsub_support_imap = get_site_option('incsub_support_imap',
		array(
		      'host' => 'imap.gmail.com',
		      'port' => '993',
		      'ssl' => '/ssl',
		      'mailbox' => 'INBOX',
		      'username' => '',
		      'password' => ''
		)
	);
	
	if (isset($_POST['incsub_support_menu_name']) && isset($_POST['incsub_support_from_name'])
	    && isset($_POST['incsub_support_from_mail']) && isset($_POST['incsub_support_fetch_imap'])) {
		update_site_option('incsub_support_menu_name', $_POST['incsub_support_menu_name']);
		update_site_option('incsub_support_from_name', $_POST['incsub_support_from_name']);
		update_site_option('incsub_support_from_mail', $_POST['incsub_support_from_mail']);
		update_site_option('incsub_support_fetch_imap', $_POST['incsub_support_fetch_imap']);
		
		if (get_site_option('incsub_support_imap_frequency', '') != $_POST['incsub_support_imap_frequency']) {
			if (wp_reschedule_event(0, $_POST['incsub_support_imap_frequency'], 'incsub_support_fetch_imap') === false) {
				wp_schedule_event(0, $_POST['incsub_support_imap_frequency'], 'incsub_support_fetch_imap');
			}
			update_site_option('incsub_support_imap_frequency', $_POST['incsub_support_imap_frequency']);
		}
		
		if (empty($_POST['incsub_support_imap']['password'])) {
			$_POST['incsub_support_imap']['password'] = $incsub_support_imap['password'];
		}
		
		update_site_option('incsub_support_imap', $_POST['incsub_support_imap']);
		wp_redirect("ms-admin.php?page=support-options&updated=true");
	}
}

function incsub_support_admin_styles() {
	wp_enqueue_style('incsub_support_admin_css');
}

function incsub_support_admin_script() {
	wp_enqueue_script('incsub_support_admin_js');
}

function incsub_support_install() {
	global $wpdb, $current_site;
	
	/**
	 * WordPress database upgrade/creation functions
	 */
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	
	// Get the correct character collate
	if ( ! empty($wpdb->charset) )
		$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
	if ( ! empty($wpdb->collate) )
		$charset_collate .= " COLLATE $wpdb->collate";
	
	$sql_main = "CREATE TABLE IF NOT EXISTS ".incsub_support_tablename('faq')." (
			faq_id bigint(20) unsigned NOT NULL auto_increment,
			site_id bigint(20) unsigned NOT NULL,
			cat_id bigint(20) unsigned NOT NULL,
			question varchar(255) NOT NULL,
			answer mediumtext NOT NULL,
			help_views bigint(20) unsigned NOT NULL default '0',
			help_count bigint(20) unsigned NOT NULL default '0',
			help_yes int(12) unsigned NOT NULL default '0',
			help_no int(12) unsigned NOT NULL default '0',
			PRIMARY KEY  (faq_id),
			KEY site_id (site_id,cat_id)
		      ) ENGINE=MyISAM $charset_collate;";
	dbDelta($sql_main);
	
	$sql_main = "CREATE TABLE IF NOT EXISTS ".incsub_support_tablename('faq_cats')." (
			cat_id bigint(20) unsigned NOT NULL auto_increment,
			site_id bigint(20) unsigned NOT NULL,
			cat_name varchar(255) NOT NULL,
			qcount smallint(3) unsigned NOT NULL,
			defcat enum('0','1') NOT NULL default '0',
			PRIMARY KEY  (cat_id),
			KEY site_id (site_id)
		      ) ENGINE=MyISAM $charset_collate;";
	dbDelta($sql_main);
	
	$sql_main = "CREATE TABLE IF NOT EXISTS ".incsub_support_tablename('tickets')." (
			ticket_id bigint(20) unsigned NOT NULL auto_increment,
			site_id bigint(20) unsigned NOT NULL,
			blog_id bigint(20) unsigned NOT NULL,
			cat_id bigint(20) unsigned NOT NULL,
			user_id bigint(20) unsigned NOT NULL,
			admin_id bigint(20) unsigned NOT NULL default '0',
			last_reply_id bigint(20) unsigned NOT NULL default '0',
			ticket_type tinyint(1) unsigned NOT NULL default '1',
			ticket_priority tinyint(1) unsigned NOT NULL default '1',
			ticket_status tinyint(1) unsigned NOT NULL default '0',
			ticket_opened timestamp NOT NULL default '0000-00-00 00:00:00',
			ticket_updated timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
			num_replies smallint(3) unsigned NOT NULL default '0',
			title varchar(120) character set utf8 NOT NULL,
			PRIMARY KEY  (ticket_id),
			KEY site_id (site_id),
			KEY blog_id (blog_id),
			KEY user_id (user_id),
			KEY admin_id (admin_id),
			KEY ticket_status (ticket_status),
			KEY ticket_updated (ticket_updated)
		      ) ENGINE=MyISAM $charset_collate;";
	dbDelta($sql_main);
	
	$sql_main = "CREATE TABLE IF NOT EXISTS ".incsub_support_tablename('tickets_cats')." (
			cat_id bigint(20) unsigned NOT NULL auto_increment,
			site_id bigint(20) unsigned NOT NULL,
			cat_name varchar(100) NOT NULL,
			defcat enum('0','1') NOT NULL default '0',
			PRIMARY KEY  (cat_id),
			KEY site_id (site_id)
		      ) ENGINE=MyISAM $charset_collate;";
	dbDelta($sql_main);
	
	$sql_main = "CREATE TABLE IF NOT EXISTS ".incsub_support_tablename('tickets_messages')." (
			message_id bigint(20) unsigned NOT NULL auto_increment,
			site_id bigint(20) unsigned NOT NULL,
			ticket_id bigint(20) unsigned NOT NULL,
			user_id bigint(20) unsigned NOT NULL,
			admin_id bigint(20) unsigned NOT NULL,
			message_date timestamp NOT NULL default CURRENT_TIMESTAMP,
			subject varchar(255) character set utf8 NOT NULL,
			message mediumtext character set utf8 NOT NULL,
			PRIMARY KEY  (message_id),
			KEY ticket_id (ticket_id)
		      ) ENGINE=MyISAM $charset_collate;";
	dbDelta($sql_main);
	
	$wpdb->query("INSERT IGNORE INTO ".incsub_support_tablename('faq_cats')." (site_id, cat_name, defcat) VALUES ('{$current_site->id}', 'General Questions', '1')");
	$wpdb->query("INSERT IGNORE INTO ".incsub_support_tablename('tickets_cats')." (site_id, cat_name, defcat) VALUES ('{$current_site->id}', 'General Questions', '1')");
	
	add_site_option('incsub_support_version', INCSUB_SUPPORT_VERSION);
	add_site_option('incsub_support_menu_name', 'Support');
	
	update_site_option('incsub_support_version', INCSUB_SUPPORT_VERSION);
}

function incsub_support_uninstall() {
	// Nothing to do
}

function incsub_support_tablename($table) {
	global $wpdb;
	return $wpdb->base_prefix.'support_'.$table;
}

function incsub_support_menu() {
	global $menu, $submenu;

	add_menu_page(__('MU Support System', INCSUB_SUPPORT_LANG_DOMAIN), __(get_site_option('incsub_support_menu_name', 'Support'), INCSUB_SUPPORT_LANG_DOMAIN),  8, 'incsub_support', 'incsub_support_output_main', null, 30);
	
	add_submenu_page('incsub_support', __('Frequently Asked Questions', INCSUB_SUPPORT_LANG_DOMAIN), __('FAQ', INCSUB_SUPPORT_LANG_DOMAIN), 'read', 'incsub_support_faq', 'incsub_support_output_faq' );
	add_submenu_page('incsub_support', __('Support Tickets', INCSUB_SUPPORT_LANG_DOMAIN), __('Support Tickets', INCSUB_SUPPORT_LANG_DOMAIN), 'edit_posts', 'incsub_support_tickets', 'incsub_support_output_tickets' );
	
	add_submenu_page('ms-admin.php', __('Frequently Asked Questions', INCSUB_SUPPORT_LANG_DOMAIN), __('FAQ Manager', INCSUB_SUPPORT_LANG_DOMAIN), 'manage_options', 'faq-manager', 'incsub_support_faqadmin' );
	add_submenu_page('ms-admin.php', __('Support Ticket Management System', INCSUB_SUPPORT_LANG_DOMAIN), __('Support Ticket Manager', INCSUB_SUPPORT_LANG_DOMAIN), 'manage_options', 'ticket-manager', 'incsub_support_ticketadmin' );
	add_submenu_page('ms-admin.php', __('Support System Options', INCSUB_SUPPORT_LANG_DOMAIN), __('Support Options', INCSUB_SUPPORT_LANG_DOMAIN), 'manage_options', 'support-options', 'incsub_support_options' );
}

function incsub_support_include($file = 'main') {
	include_once(dirname(__FILE__) .'/incsub-support-'. $file .'.php');
}

function incsub_support_whitelist_options($options) {
	$added = array( 'incsub_support' => array( 'incsub_support_menu_name' ) );
	$options = add_option_whitelist( $added, $options );
	return $options;
}
	
/**
 * Plugin options
 */
function incsub_support_options() {
	$incsub_support_imap = get_site_option('incsub_support_imap',
		array(
		      'host' => 'imap.gmail.com',
		      'port' => '993',
		      'ssl' => '/ssl',
		      'mailbox' => 'INBOX',
		      'username' => '',
		      'password' => ''
		)
	);
	
	if (isset($_GET['updated'])) {
		echo '<div class="updated fade"><p>'.__('Support options saved.', INCSUB_SUPPORT_LANG_DOMAIN).'</p></div>';
	}
	
	?>
	<div class="wrap">
		<h2><?php _e('Support System Settings', INCSUB_SUPPORT_LANG_DOMAIN); ?></h2>
		<form method="post" action="">
	    
			<table border="0" cellpadding="4" cellspacing="0" class="support_options">
				<tr>
					<td><label for="incsub_support_menu_name"><?php _e('Support menu name', INCSUB_SUPPORT_LANG_DOMAIN); ?></label> </td>
					<td><input type="text" id="incsub_support_menu_name" name="incsub_support_menu_name" value="<?php print get_site_option('incsub_support_menu_name', __('Support', INCSUB_SUPPORT_LANG_DOMAIN)); ?>" class="incsub_support_menu_name" size="30" /></td>
					<td class="info"> <?php _e("Change the text of the 'Support' menu item to anything you need.", INCSUB_SUPPORT_LANG_DOMAIN); ?></td>
				</tr>
				<tr>
					<td><label for="incsub_support_from_name"><?php _e('Support from name', INCSUB_SUPPORT_LANG_DOMAIN); ?></label> </td>
					<td><input type="text" id="incsub_support_from_name" name="incsub_support_from_name" value="<?php print get_site_option('incsub_support_from_name', get_bloginfo('blogname')); ?>" class="incsub_support_from_name" size="30" /></td>
					<td class="info"> <?php _e("Support mail from name.", INCSUB_SUPPORT_LANG_DOMAIN); ?></td>
				</tr>
				<tr>
					<td><label for="incsub_support_from_mail"><?php _e('Support from e-mail', INCSUB_SUPPORT_LANG_DOMAIN); ?></label> </td>
					<td><input type="text" id="incsub_support_from_mail" name="incsub_support_from_mail" value="<?php print get_site_option('incsub_support_from_mail', get_bloginfo('admin_email')); ?>" class="incsub_support_from_mail" size="30" /></td>
					<td class="info"> <?php _e("Support mail from address.", INCSUB_SUPPORT_LANG_DOMAIN); ?></td>
				</tr>
				<tr>
					<td><label for="incsub_support_fetch_imap"><?php _e('Fetch responses via IMAP', INCSUB_SUPPORT_LANG_DOMAIN); ?></label> </td>
					<td>
						<select name="incsub_support_fetch_imap" id="incsub_support_fetch_imap" <?php echo (function_exists('imap_open'))?'':'disabled="disabled"'; ?>>
							<option value="enabled"<?php if ( get_site_option('incsub_support_fetch_imap', 'disabled') == 'enabled' ) { echo ' selected="selected" '; } ?>><?php _e('Enabled', INCSUB_SUPPORT_LANG_DOMAIN); ?></option>
							<option value="disabled"<?php if ( !function_exists('imap_open') || get_site_option('incsub_support_fetch_imap', 'disabled') == 'disabled' ) { echo ' selected="selected" '; } ?>><?php _e('Disabled', INCSUB_SUPPORT_LANG_DOMAIN); ?></option>
						</select>
					</td>
					<td class="info"> <?php _e("Enable or disable fetching responses to tickets via IMAP", INCSUB_SUPPORT_LANG_DOMAIN); ?></td>
				</tr>
				<tr class="imap_details" >
					<td><label for="incsub_support_imap_frequency"><?php _e('Fetch responses via IMAP', INCSUB_SUPPORT_LANG_DOMAIN); ?></label> </td>
					<td>
						<select name="incsub_support_imap_frequency" id="incsub_support_imap_frequency">
							<option value=""<?php if ( get_site_option('incsub_support_imap_frequency', '') == '' ) { echo ' selected="selected" '; } ?>><?php _e('', INCSUB_SUPPORT_LANG_DOMAIN); ?></option>
							<?php
							foreach (wp_get_schedules() as $recurrence => $schedule) { ?>
							<option value="<?php print $recurrence; ?>"<?php if ( get_site_option('incsub_support_imap_frequency', '') == $recurrence ) { echo ' selected="selected" '; } ?>><?php _e($schedule['display'], INCSUB_SUPPORT_LANG_DOMAIN); ?></option>
							<?php
							} ?>
						</select>
					</td>
					<td class="info"> <?php _e("Enable or disable fetching responses to tickets via IMAP", INCSUB_SUPPORT_LANG_DOMAIN); ?></td>
				</tr>
				<tr class="imap_details" >
					<td><label for="incsub_support_imap_server"><?php _e('IMAP details', INCSUB_SUPPORT_LANG_DOMAIN); ?></label> </td>
					<td>
						<label for="incsub_support_imap_host"><?php _e('IMAP server host', INCSUB_SUPPORT_LANG_DOMAIN); ?></label><br/>
						<input type="text" id="incsub_support_imap_host" name="incsub_support_imap[host]" value="<?php print $incsub_support_imap['host']; ?>" size="40" />
						<br/>
						<label for="incsub_support_imap_port"><?php _e('IMAP server port', INCSUB_SUPPORT_LANG_DOMAIN); ?></label><br/>
						<input type="text" id="incsub_support_imap_port" name="incsub_support_imap[port]" value="<?php print $incsub_support_imap['port']; ?>" size="4" />
						<br/>
						<label for="incsub_support_imap_ssl"><?php _e('SSL', INCSUB_SUPPORT_LANG_DOMAIN); ?></label><br/>
						<select name="incsub_support_imap[ssl]" id="incsub_support_imap_ssl">
							<option value="/ssl"<?php if ( $incsub_support_imap['ssl'] == '/ssl' ) { echo ' selected="selected" '; } ?>><?php _e('Yes', INCSUB_SUPPORT_LANG_DOMAIN); ?></option>
							<option value=""<?php if ( $incsub_support_imap['ssl'] == '' ) { echo ' selected="selected" '; } ?>><?php _e('No', INCSUB_SUPPORT_LANG_DOMAIN); ?></option>
						</select>
						<br/>
						<label for="incsub_support_imap_mailbox"><?php _e('IMAP mailbox', INCSUB_SUPPORT_LANG_DOMAIN); ?></label><br/>
						<input type="text" id="incsub_support_imap_mailbox" name="incsub_support_imap[mailbox]" value="<?php print $incsub_support_imap['mailbox']; ?>" size="40" />
						<br/>
						<label for="incsub_support_imap_username"><?php _e('IMAP username', INCSUB_SUPPORT_LANG_DOMAIN); ?></label><br/>
						<input type="text" id="incsub_support_imap_username" name="incsub_support_imap[username]" value="<?php print $incsub_support_imap['username']; ?>" size="40" />
						<br/>
						<label for="incsub_support_imap_password"><?php _e('IMAP password', INCSUB_SUPPORT_LANG_DOMAIN); ?></label><br/>
						<input type="password" id="incsub_support_imap_password" name="incsub_support_imap[password]" value="" size="40" />
					</td>
					<td class="info"> <?php _e("IMAP server details.<br/>e.g. Host: imap.gmail.com <br/>Port: 993 <br/>Mailbox: INBOX <br/>Username: test@example.com", INCSUB_SUPPORT_LANG_DOMAIN); ?></td>
				</tr>
			</table>
			
			<p class="submit"><input type="submit" name="Submit" value="<?php _e('Save Changes', INCSUB_SUPPORT_LANG_DOMAIN) ?>" /></p>
		</form>
	</div>
	<?php
}

function incsub_support_faqadmin() {
?>
	<div class="wrap">
<?php
	switch($_GET['action']) {
		case "questions":
			incsub_support_faqadmin_questions();
		break;
		case "categories":
			incsub_support_faqadmin_categories();
		break;
		case "editquestions":
			incsub_support_faqadmin_editquestions();
		break;
		default :
			incsub_support_faqadmin_main();
		break;
	}		

?>
	</div>
<?php
}

function incsub_support_faqadmin_main() {
	global $wpdb, $current_site;
	$questions = $wpdb->get_var("SELECT COUNT(faq_id) FROM ".incsub_support_tablename('faq')." WHERE site_id = '{$current_site->id}'");
	$cats = $wpdb->get_var("SELECT COUNT(cat_id) FROM ".incsub_support_tablename('faq_cats')." WHERE site_id = '{$current_site->id}'");
	$sum_help_yes = $wpdb->get_var("SELECT SUM(help_yes) FROM ".incsub_support_tablename('faq')." WHERE site_id = '{$current_site->id}'");
	$question_text = sprintf( __ngettext( '%s question', '%s questions', $questions, INCSUB_SUPPORT_LANG_DOMAIN ), number_format_i18n( $questions ) );
	$cats_text = sprintf( __ngettext( '%s category', '%s categories', $cats, INCSUB_SUPPORT_LANG_DOMAIN ), number_format_i18n( $cats ) );
	$sentence = sprintf( __( 'You have %1$s contained within %2$s.', INCSUB_SUPPORT_LANG_DOMAIN ), $question_text, $cats_text );
	if ( $sum_help_yes > 0 ) {
		$sum_help_count = $wpdb->get_var("SELECT SUM(help_count) FROM ".incsub_support_tablename('faq')." WHERE site_id = '{$current_site->id}'");
		$sum_help_percentage = ceil( ($sum_help_yes/$sum_help_count)*100);
		$userusers = sprintf( __ngettext( '%s user', '%s users', $sum_help_count, INCSUB_SUPPORT_LANG_DOMAIN ), number_format_i18n( $sum_help_count ) );
		$users_helped = "<li>". sprintf( __('%1$s out of %2$s have been helped, for an overall success rate of %3$s&#37;', INCSUB_SUPPORT_LANG_DOMAIN), $sum_help_yes, $userusers, $sum_help_percentage) . "</li>";
	} else {
		$users_helped = "";
	}
	// top 5 helpful questions
	$top5help = $wpdb->get_results("SELECT faq_id, question, help_yes, help_no, (help_yes/help_count)*100 AS help_percent FROM ".incsub_support_tablename('faq')." WHERE site_id = '{$current_site->id}' AND ((help_yes/help_count)*100) > '0' ORDER BY help_percent DESC LIMIT 0, 5");
	$bot5help = $wpdb->get_results("SELECT faq_id, question, help_yes, help_no, (help_yes/help_count)*100 AS help_percent FROM ".incsub_support_tablename('faq')." WHERE site_id = '{$current_site->id}' AND ((help_yes/help_count)*100) > '0' ORDER BY help_percent ASC LIMIT 0, 5");

?>
	<h2><?php _e('FAQ Manager'); ?></h2>
	<div class="handlediv">
		<h3 class='hndle'>
			<span><?php _e('FAQ Stats/Info'); ?></span>
			<a href="ms-admin.php?page=faq-manager&amp;action=categories" class="rbutton"><strong><?php _e('Manage FAQ Categories'); ?></strong></a>
			<a href="ms-admin.php?page=faq-manager&amp;action=questions" class="rbutton"><strong><?php _e('Manage Questions'); ?></strong></a>
			<br class="clear" />
		</h3>
		<div class="youhave">
			<ul>
				<li><?php echo $sentence; ?></li>
				<?php echo $users_helped; ?>
			</ul>
			<h4><?php _e("Top 5: Most Helpful"); ?></h4>
<?php
	if ( !empty($top5help) ) {
		echo "
			<ul>";
		$already_done = array();
		foreach ( $top5help as $top5 ) {
			// anything less than 50% isn't very helpful, is it?
			if ( $top5->help_percent < 50 ) {
				continue;
			}
			$already_done[] = $top5->faq_id;
			echo "
				<li>{$top5->question} <small>(". ceil($top5->help_percent) ."%)</small></li>";
		}
		echo "
			</ul>";
	} else {
?>
			<p><?php _e("There have not been any ratings for any questions/answers.", INCSUB_SUPPORT_LANG_DOMAIN); ?></p>
<?php
	}
?>
			<h4><?php _e("Top 5: Least Helpful", INCSUB_SUPPORT_LANG_DOMAIN); ?></h4>
<?php
	if ( !empty($bot5help) ) {
		echo "
			<ul>";
		foreach ( $bot5help as $bot5 ) {
			if ( in_array($bot5->faq_id, $already_done) ) {
				continue;
			}
			echo "
				<li>{$bot5->question} <small>(". ceil($bot5->help_percent) ."%)</small></li>";
		}
		echo "
			</ul>";
	} else {
?>
			<p><?php _e("There have not been any ratings for any questions/answers.", INCSUB_SUPPORT_LANG_DOMAIN); ?></p>
<?php
	}
?>
		</div>
	</div>
<?php
}

function incsub_support_faqadmin_questions() {
	global $wpdb, $current_site;
	if ( !empty($_POST) ) {
		// post data received...
		if ( !empty($_POST['deleteq']) and $_POST['deleteq'] == 1 ) {
			// deleting
			check_admin_referer("incsub_faqmanagement_managequestions");
			$wh = '';
			foreach($_POST['delete'] as $key => $val) {
				$count[$val['cat_id']] = (!empty($count[$val['cat_id']])) ? $count[$val['cat_id']]+1 : 1;
				if ( is_numeric($val['faq_id']) and is_numeric($key) ) {
					if ( $key == 0 ) {
						$wh .= "WHERE ((faq_id = '". $val['faq_id'] ."'";
					} else {
						$wh .= " OR faq_id = '". $val['faq_id'] ."'";
					}
				}
			}
			if ( !empty($wh) ) {
				// if $wh is empty, there wouldn't be anything to delete.
				$wh .= ") AND site_id = '{$current_site->id}')";
				$wpdb->query("DELETE FROM ".incsub_support_tablename('faq')." ". $wh);
				if ( !empty($wpdb->rows_affected) ) {
					$delete_text = sprintf( __ngettext( '%s question was', '%s questions were', $wpdb->rows_affected, INCSUB_SUPPORT_LANG_DOMAIN ), number_format_i18n( $wpdb->rows_affected ) );
					$sentence = sprintf( __( '%1$s removed', INCSUB_SUPPORT_LANG_DOMAIN ), $delete_text );
					$mclass = "updated fade";
				} else {
					$sentence = __( "There wasn't anything to delete." , INCSUB_SUPPORT_LANG_DOMAIN);
					$mclass = "error";
				}
				foreach ( $count as $key => $val ) {
					$wpdb->query("UPDATE ".incsub_support_tablename('faq_cats')." SET qcount = qcount-{$val} WHERE site_id = '{$current_site->id}' AND cat_id = '{$key}'");
				}
			}
		} elseif ( !empty($_POST['addq']) and $_POST['addq'] == 1 ) {
			check_admin_referer("incsub_faqmanagement_managequestions");
			if ( empty($_POST['question']) ) {
				$sentence = __( "The question field is empty.", INCSUB_SUPPORT_LANG_DOMAIN );
				$mclass = "error";
			} elseif ( empty($_POST['answer']) ) {
				$sentence = __( "The answer field is empty.", INCSUB_SUPPORT_LANG_DOMAIN );
				$mclass = "error";
			} else {
				$question = wp_specialchars(strip_tags($_POST['question']));
				$answer = wp_specialchars(wpautop($_POST['answer']));
				if ( !is_numeric($_POST['category']) ) {
					$the_cat = $wpdb->get_var("SELECT cat_id FROM ".incsub_support_tablename('faq_cats')." WHERE defcat = '1' AND site_id = '{$current_site->id}'");
				} else {
					$the_cat = $_POST['category'];
				}
				$wpdb->query("INSERT INTO ".incsub_support_tablename('faq')." (site_id, cat_id, question, answer) VALUES ( '{$current_site->id}', '{$the_cat}', '{$question}', '{$answer}')");
				$wpdb->query("UPDATE ".incsub_support_tablename('faq_cats')." SET qcount = qcount+1 WHERE site_id = '{$current_site->id}' AND cat_id = '{$the_cat}'");
				if ( !empty($wpdb->insert_id) ) {
					$sentence = __( "New Q&amp;A inserted successfully.", INCSUB_SUPPORT_LANG_DOMAIN );
					$mclass = "updated fade";
				} else {
					$sentence = __( "Something happened, and nothing was inserted. Check your error logs.", INCSUB_SUPPORT_LANG_DOMAIN );
					$mclass = "error";
				}
			}
		} elseif ( !empty($_POST['updateq']) and $_POST['updateq'] == 1 ) {
			check_admin_referer("incsub_faqmanagement_managequestions");
			if ( empty($_POST['question']) ) {
				$sentence = __( "The question field is empty.", INCSUB_SUPPORT_LANG_DOMAIN );
				$mclass = "error";
			} elseif ( empty($_POST['answer']) ) {
				$sentence = __( "The answer field is empty.", INCSUB_SUPPORT_LANG_DOMAIN );
				$mclass = "error";
			} elseif ( !is_numeric($_POST['faq_id']) ) {
				$sentence = __( "Invalid identification for the question being updated.", INCSUB_SUPPORT_LANG_DOMAIN );
				$mclass = "error";
			} else {
				$question = wp_specialchars(strip_tags($_POST['question']));
				$answer = wp_specialchars(wpautop($_POST['answer']));
				if ( !is_numeric($_POST['category']) ) {
					$the_cat = $wpdb->get_var("SELECT cat_id FROM ".incsub_support_tablename('faq_cats')." WHERE defcat = '1' AND site_id = '{$current_site->id}'");
				} else { 
					$the_cat = $_POST['category'];
					$the_id = $_POST['faq_id'];
				}
				$wpdb->query("UPDATE ".incsub_support_tablename('faq')." SET site_id = '{$current_site->id}', cat_id = '{$the_cat}', question = '{$question}', answer = '{$answer}' WHERE faq_id = '{$the_id}' AND site_id = '{$current_site->id}'");
				if ( !empty($wpdb->rows_affected) ) {
					$sentence = __( "Question/Answer updated successfully.", INCSUB_SUPPORT_LANG_DOMAIN );
					$mclass = "updated fade";
					if ( is_numeric($_POST['old_cat_id']) and $_POST['old_cat_id'] != $the_cat ) {
						// we changed cats, and the update was a success;
						$wpdb->query("UPDATE ".incsub_support_tablename('faq_cats')." SET qcount = qcount-1 WHERE cat_id = '". $_POST['old_cat_id'] ."' AND site_id = '{$current_site->id}'");
						$wpdb->query("UPDATE ".incsub_support_tablename('faq_cats')." SET qcount = qcount+1 WHERE cat_id = '{$the_cat}' AND site_id = '{$current_site->id}'");
					}
				} else {
					$sentence = __( "Something happened, and nothing was updated. Check your error logs.", INCSUB_SUPPORT_LANG_DOMAIN );
					$mclass = "error";
				}
			}
		}
?>
		<div class="<?php echo $mclass; ?>"><p><?php echo $sentence; ?></p></div>
<?php
	}
	$questions = $wpdb->get_results("
		SELECT
			q.faq_id AS faq_id, q.cat_id AS cat_id, q.question AS question, q.answer AS answer, c.cat_name AS cat_name
		FROM ".incsub_support_tablename('faq')." as q
		LEFT JOIN ".incsub_support_tablename('faq_cats')." AS c ON ( q.cat_id = c.cat_id )
		WHERE q.site_id = '{$current_site->id}'
		ORDER BY c.cat_name, q.question ASC
	");

	if ( !empty($_GET['qid']) and is_numeric($_GET['qid']) ) {
		// we need to edit a post;
		$editq = $wpdb->get_results("SELECT faq_id, cat_id, question, answer FROM ".incsub_support_tablename('faq')." WHERE site_id = '{$current_site->id}' AND faq_id = '". $_GET['qid'] ."' LIMIT 1");
		if ( empty($editq[0]) ) {
?>
		<div class="error"><p><?php _e("That question does not exist.", INCSUB_SUPPORT_LANG_DOMAIN); ?></p></div>
<?php
		} else {
			$editq = $editq[0];
?>			
		<h2><?php _e("Editing: ", INCSUB_SUPPORT_LANG_DOMAIN); echo $editq->question; ?></h2>
			<form id="addquestion" action="ms-admin.php?page=faq-manager&action=questions" method="post">
		<?php wp_nonce_field("incsub_faqmanagement_managequestions"); ?>
<?php incsub_support_faqadmin_postbox($editq); ?>
				<p class="submit" style="padding: 10px;">
					<input type="hidden" name="faq_id" value="<?php echo $editq->faq_id; ?>" />
					<input type="hidden" name="old_cat_id" value="<?php echo $editq->cat_id; ?>" />
					<input type="hidden" name="updateq" value="1" />
					<input type="submit" class="button" value="Update" />
				</p>
			</form>
<?php
		}
	}
?>
	<script type="text/javascript" language="JavaScript"><!--
		function FAQReverseDisplay(d) {
			jQuery('#'+d).toggleClass('invisible');
		}
		//-->
	</script>
	<h2><?php _e("FAQ Manager", INCSUB_SUPPORT_LANG_DOMAIN); ?></h2>
	<div class="handlediv">
		<h3 class='hndle'>
			<span><?php _e("Manage Questions/Answers", INCSUB_SUPPORT_LANG_DOMAIN); ?></span>
			<a href="ms-admin.php?page=faq-manager&amp;action=categories" class="rbutton"><strong><?php _e('Manage FAQ Categories'); ?></strong></a>
 			<a href="#addquestion" class="rbutton"><strong><?php _e("Add New Question"); ?></strong></a>
			<br class="clear" />
		</h3>
		<div class="youhave">
			<form id="managecats" action="ms-admin.php?page=faq-manager&action=questions" method="post">
				<?php wp_nonce_field("incsub_faqmanagement_managequestions"); ?>

<?php
	$cat_name = '';
	foreach ($questions as $question) {
		if ( $cat_name != $question->cat_name ) {
			if ( !empty($cat_name) and $cat_name != $question->cat_name ) {
?>
					</tbody>
				</table>
				<br /><br />
<?php
			}
?>
				<h3 style="font-size: 140%; text-align: left; padding: 0; margin: 0;"><a href="#" style="text-decoration: none;" onclick="javascript:FAQReverseDisplay('catbody-<?php echo $question->cat_id; ?>')"><?php _e("FAQ Category: "); echo $question->cat_name; ?> <small style="font-size: 12px;">(<?php _e("view questions"); ?>)</small></a></h3>
				<table class="widefat" id="catbody-<?php echo $question->cat_id; ?>" style="width: 100%;" width="100%" class="invisible">
					<thead>
						<tr>
							<th scope="col" class="check-column">&nbsp;</th>
							<th scope="col" align="left" style="text-align: left; width: 40%;"><?php _e("Question", INCSUB_SUPPORT_LANG_DOMAIN); ?></th>
							<th scope="col" align="left" style="text-align: left; width: 45%;"><?php _e("Answer", INCSUB_SUPPORT_LANG_DOMAIN); ?></th>
							<th scope="col" align="left" style="text-align: left;" width="15%"><?php _e("Option(s)", INCSUB_SUPPORT_LANG_DOMAIN); ?></th>
						</tr>
					</thead>
					<tbody class="list:cat">
<?php
			$cat_name = $question->cat_name;
		}
?>
						<tr id='question-4' class='alternate'>
							<th scope='row' class='check-column'><input type='checkbox' name='delete[][faq_id]' value='<?php echo $question->faq_id; ?>' /><input type="hidden" name="delete[][cat_id]" value="<?php echo $question->cat_id; ?>" /></th>
							<td valign="top">
								<?php echo $question->question; ?>

							</td>
							<td valign="top">
								<?php echo html_entity_decode($question->answer); ?>
							</td>
							<td valign="middle" style="vertical-align: middle;"><a href="ms-admin.php?page=faq-manager&amp;action=questions&amp;qid=<?php echo $question->faq_id; ?>" class="button" title="edit this"><?php _e("Edit This", INCSUB_SUPPORT_LANG_DOMAIN); ?></a></td>
						</tr>
<?php
	}
?>
					</tbody>
				</table>
				<p class="submit" style="padding: 10px;">
					<input type="hidden" name="deleteq" value="1" />
					<input type="submit" class="button" value="<?php _e("Delete Questions", INCSUB_SUPPORT_LANG_DOMAIN); ?>" />
				</p>
			</form>
			<br /><br />
			<h2><?php _e('Add New Question', INCSUB_SUPPORT_LANG_DOMAIN); ?></h2>
			<form id="addquestion" action="ms-admin.php?page=faq-manager&action=questions" method="post">
		<?php wp_nonce_field("incsub_faqmanagement_managequestions"); ?>
<?php incsub_support_faqadmin_postbox(); ?>
				<p class="submit" style="padding: 10px;">
					<input type="hidden" name="addq" value="1" />
					<input type="submit" class="button" value="<?php _e('Add New Question', INCSUB_SUPPORT_LANG_DOMAIN); ?>" />
				</p>
			</form>
		</div>
	</div>
<?php
}

function incsub_support_faqadmin_categories() {
	global $wpdb, $current_site;
	if ( !empty($_POST['updateq']) ) {
		check_admin_referer("incsub_faqmanagement_managecats");
		if ( !empty($_POST['deleteme']) ) {
				if ( !is_numeric($_POST['defcat']) ) {
					$defcat = $wpdb->get_var("SELECT cat_id FROM ".incsub_support_tablename('faq_cats')." WHERE site_id = '{$current_site->id}' AND defcat = '1'");
				} else {
					$defcat = $_POST['defcat'];
				}
				$wh = '';
			foreach($_POST['delete'] as $key => $val) {
				if ( $defcat == $val ) {
					continue;
				}

				if ( is_numeric($val) and is_numeric($key) ) {
					if ( $key == 0 ) {
						$wh .= "WHERE ( (cat_id = '{$val}'";
					} else {
						$wh .= " OR cat_id = '{$val}'";
					}
				}
			}
			if ( !empty($wh) ) {
				// if $wh is empty, there wouldn't be anything to delete.
				$wh .= ") AND site_id = '{$current_site->id}' AND defcat != '1')";
				$wpdb->query("DELETE FROM ".incsub_support_tablename('faq_cats')." ". $wh);
				$delete_text = sprintf( __ngettext( '%s category was', '%s categories were', $wpdb->rows_affected, INCSUB_SUPPORT_LANG_DOMAIN ), number_format_i18n( $wpdb->rows_affected ) );
				$sentence = sprintf( __( '%1$s removed', INCSUB_SUPPORT_LANG_DOMAIN ), $delete_text );
				// set any orphaned questions to the default cat.
				$wpdb->query("UPDATE ".incsub_support_tablename('faq')." SET cat_id = '{$defcat}' ". str_replace(" AND defcat != '1'", "", $wh));
?>
		<div class="updated fade"><p><?php echo $sentence; ?></p></div>
<?php
			} else {
?>
		<div class="error"><p><?php _e("There was not anything to delete.", INCSUB_SUPPORT_LANG_DOMAIN); ?></p></div>
<?php
			}
		} elseif ( !empty($_POST['updateme']) ) {
			$x = 0;
			foreach ( $_POST['cat'] as $key => $val ) {
				if ( is_numeric($key) ) {
					$wpdb->query("UPDATE ".incsub_support_tablename('faq_cats')." SET cat_name = '". attribute_escape(wp_specialchars(strip_tags($val))) ."' WHERE site_id = '{$current_site->id}' AND cat_id = '{$key}'");
					$x++;
				}
			}
			if ( $x > 0 ) {
				$update_text = sprintf( __ngettext( '%s category was', '%s categories were', $x, INCSUB_SUPPORT_LANG_DOMAIN ), number_format_i18n( $x ) );
				$sentence = sprintf( __( '%1$s updated', INCSUB_SUPPORT_LANG_DOMAIN ), $update_text );
?>
		<div class="updated fade"><p><?php echo $sentence; ?></p></div>
<?php
			} else {
?>
		<div class="error"><p><?php _e("There was not anything to update.", INCSUB_SUPPORT_LANG_DOMAIN); ?></p></div>
<?php

			}
		}
	} elseif ( !empty($_POST['addme']) ) {
		check_admin_referer("incsub_faqmanagement_addcat");
		if ( !empty($_POST['cat_name']) ) {
			$cat_name = attribute_escape(wp_specialchars($_POST['cat_name']));
			$wpdb->query("INSERT INTO ".incsub_support_tablename('faq_cats')." (site_id, cat_name, defcat) VALUES ('{$current_site->id}', '{$cat_name}', '0')");
			if ( !empty($wpdb->insert_id) ) {
?>
		<div class="updated fade"><p><?php _e("New category added successfully.", INCSUB_SUPPORT_LANG_DOMAIN); ?></p></div>
<?php
			}
		}
	}


	$cats = $wpdb->get_results("SELECT cat_id, cat_name, qcount, defcat FROM ".incsub_support_tablename('faq_cats')." WHERE site_id = '{$current_site->id}' ORDER BY defcat DESC, cat_name ASC");
?>
	<h2><?php _e("FAQ Manager", INCSUB_SUPPORT_LANG_DOMAIN); ?></h2>
	<div class="handlediv">
		<h3 class='hndle'>
			<span><?php _e("Manage Categories", INCSUB_SUPPORT_LANG_DOMAIN); ?></span>
 			<a href="#addcat" class="rbutton"><strong><?php _e("Add New Category", INCSUB_SUPPORT_LANG_DOMAIN); ?></strong></a>
			<a href="ms-admin.php?page=faq-manager&amp;action=questions" class="rbutton"><strong><?php _e('Manage Questions'); ?></strong></a>
			<br class="clear" />
		</h3>
		<div class="youhave">
			<form id="managecats" action="ms-admin.php?page=faq-manager&action=categories" method="post">
<?php wp_nonce_field("incsub_faqmanagement_managecats"); ?>
				<?php if ( count($cats) > 1 ) { ?><p class="submit" style="border-top: none;"><input type="submit" class="button" name="deleteme" value="Delete" /></p><?php } ?>
				<table class="widefat">
					<thead>
						<tr>
							<th scope="col" class="check-column"><?php if ( count($cats) > 1 ) { ?><input type="checkbox" /><?php } ?></th>
				    	    <th scope="col"><?php _e("Name", INCSUB_SUPPORT_LANG_DOMAIN); ?></th>
				    	    <th scope="col" class="num"><?php _e("Questions", INCSUB_SUPPORT_LANG_DOMAIN);?></th>
						</tr>
					</thead>
					<tbody id="the-list" class="list:cat">
<?php
	foreach ($cats as $cat) {
		if ( $cat->defcat == 1 ) {
			$checkcol = '<input type="hidden" name="defcat" value="'. $cat->cat_id .'" />';
			$textcol = "<h3>". $cat->cat_name . "</h3> <small>( ".__('Default category, cannot be removed.', INCSUB_SUPPORT_LANG_DOMAIN)." )</small>";
		} else {
			$checkcol = '<input type="checkbox" name="delete[]" value="'. $cat->cat_id .'" />';
			$textcol = '<input type="text" size="40" name="cat['. $cat->cat_id .']" value="'. $cat->cat_name .'" />';
		}
		if ( $class == ' class="alternate"' ) {
			$class = "";
		} else {
			$class = ' class="alternate"';
		}
?>
						<tr id="cat-<?php echo $cat->cat_id; ?>" class="<?php echo $class; ?>">
							<th scope="row" class="check-column"><?php echo $checkcol; ?></th>
							<td><?php echo $textcol; ?></td>
							<td class='num'><?php echo $cat->qcount; ?></td>
						</tr>
<?php
	}
?>
					</tbody>
				</table>

				<p class="submit" style="padding: 10px;">
					<input type="hidden" name="updateq" value="1" />
					<?php if ( count($cats) > 1 ) { ?><input type="submit" class="button" name="updateme" value="<?php _e('Update Categories', INCSUB_SUPPORT_LANG_DOMAIN); ?>" />&nbsp;&nbsp;&nbsp;<input type="submit" class="button" name="deleteme" value="<?php _e('Delete', INCSUB_SUPPORT_LANG_DOMAIN); ?>" /><?php } ?>
				</p>
			</form>
		</div>
	</div>
	<br />
	<h2><?php _e('Add New Category', INCSUB_SUPPORT_LANG_DOMAIN); ?></h2>
	<form name="addcat" id="addcat" method="post" action="ms-admin.php?page=faq-manager&amp;action=categories">
	<?php wp_nonce_field("incsub_faqmanagement_addcat"); ?>
	<table class="form-table">
		<tr class="form-field form-required">
			<th scope="row" valign="top"><label for="cat_name"><?php _e('Category Name', INCSUB_SUPPORT_LANG_DOMAIN); ?></label></th>
			<td>
				<input name="cat_name" id="cat_name" type="text" value="" size="40" aria-required="true" /><br />
	            <?php _e('The name is used to identify the category to which questions relate.', INCSUB_SUPPORT_LANG_DOMAIN); ?>
			</td>
		</tr>
	</table>
	<p class="submit"><input type="submit" class="button" name="addme" value="<?php _e('Add Category', INCSUB_SUPPORT_LANG_DOMAIN); ?>" /></p>
	</form>
<?php
}

function incsub_support_faqadmin_editquestions() {
?>
	<h2><?php _e('FAQ Edit', INCSUB_SUPPORT_LANG_DOMAIN); ?></h2>
<?php
}

function incsub_support_faqadmin_postbox($data = '') {
	global $wpdb, $current_site;
	$cats = $wpdb->get_results("SELECT cat_id, cat_name, defcat FROM ".incsub_support_tablename('faq_cats')." WHERE site_id = '{$current_site->id}' ORDER BY defcat DESC, cat_name ASC");
	$rows = get_option('default_post_edit_rows');
	if (($rows < 3) || ($rows > 60)){
		$rows = 12;
	}
	$rows = "rows='$rows'";
	if ( user_can_richedit() ) {
		add_filter('the_editor_content', 'wp_richedit_pre');
	}
	
	wp_tiny_mce( false , // true makes the editor "teeny"
		array(
			"editor_selector" => "answer"
		)
	);
?>
	<div id="post-body">
		<h3><label for="question"><?php _e('Question', INCSUB_SUPPORT_LANG_DOMAIN); ?></label></h3>
		<div id="titlewrap">
			<input type="text" name="question" tabindex="1" value="<?php if ( !empty($data->question) ) { echo $data->question; }?>" id="title" autocomplete="off" style="width: 68.7%;" />
		</div>
		<h3><label for="category"><?php _e('FAQ Category', INCSUB_SUPPORT_LANG_DOMAIN); ?></label>&nbsp;&nbsp;<small style="font-size: 60%">( <a href="ms-admin.php?page=faq-manager&amp;action=newcat"><?php _e('Add new FAQ Category?', INCSUB_SUPPORT_LANG_DOMAIN); ?></a> )</small></h3>
		<div id="content">
			<select name="category" id="category">
<?php
	$x = 0;
	foreach ( $cats as $cat ) {
		if ( $x == 0 and empty($data->cat_id) ) { $selected = ' selected="selected"'; $x++; }
		elseif ( !empty($data->cat_id) and $data->cat_id == $cat->cat_id ) { $selected = ' selected="selected"'; }
		else { $selected = ''; }
?>
				<option<?php echo $selected; ?> value="<?php echo $cat->cat_id; ?>"><?php echo $cat->cat_name; ?></option>
<?php
	}
?>
			</select>
		</div>
		<h3><label for="answer"><?php _e('Answer', INCSUB_SUPPORT_LANG_DOMAIN); ?></label></h3>
		<textarea <?php echo $rows; ?> class="answer" name="answer" tabindex="3" id="answer"><?php if ( !empty($data->answer) ) { echo $data->answer; } else { echo ""; }?></textarea>
	</div>
<?php

}

function incsub_support_output_main() {
	global $wpdb, $current_site, $blog_id, $ticket_status, $ticket_priority;
	$open_tickets = $wpdb->get_results("
			SELECT t.ticket_id, t.ticket_priority, t.ticket_status, t.ticket_updated, t.title, l.display_name AS last_user_reply 
			FROM ".incsub_support_tablename('tickets')." AS t LEFT JOIN {$wpdb->users} AS l ON ( t.last_reply_id = l.ID )
			WHERE site_id = '{$current_site->id}' AND blog_id = '{$blog_id}' AND ticket_status != '5' 
			ORDER BY ticket_priority DESC, ticket_updated DESC LIMIT 5");

	$top5help = $wpdb->get_results("SELECT faq_id, question, answer, help_yes, help_no, (help_yes/help_count)*100 AS help_percent FROM ".incsub_support_tablename('faq')." WHERE site_id = '{$current_site->id}' ORDER BY help_percent DESC LIMIT 0, 5");



?>
<br />
<div class="wrap">
	<script type="text/javascript" language="JavaScript"><!--
		function FAQReverseDisplay(d) {
			jQuery('#'+d).toggleClass('invisible');
		}
		//-->
	</script>
	<h2><?php _e("Support System", INCSUB_SUPPORT_LANG_DOMAIN); ?></h2>
	<div style="width: 63%; float: left;">
		<h3><?php _e("Recent Support Tickets", INCSUB_SUPPORT_LANG_DOMAIN); ?></h3>
<?php
		if ( !empty($open_tickets) ) {
?>
		<table class="widefat" cellpadding="3" cellspacing="3" border="1">
			<thead>
				<tr>
					<th scope="col"><?php _e("Ticket Subject", INCSUB_SUPPORT_LANG_DOMAIN); ?></th>
					<th scope="col" width="35%"><?php _e("Last Updated", INCSUB_SUPPORT_LANG_DOMAIN); ?></th>
					<th scope="col" width="35%"><?php _e("Details", INCSUB_SUPPORT_LANG_DOMAIN); ?></th>
				</tr>
			</thead>
			<tbody>
<?php
			foreach ( $open_tickets as $ticket ) {
				$mclass = ($mclass == "alternate") ? "" : "alternate";
?>
					<tr class="<?php echo $mclass; ?>">
						<th scope="row"><a href="admin.php?page=incsub_support_tickets&tid=<?php echo $ticket->ticket_id; ?>"><?php echo $ticket->title; ?></a></th>
						<td><?php echo str_replace(" ... ", "<br />", date(get_option("date_format") ." ... ". get_option("time_format") ." T  (\G\M\T P)", strtotime($ticket->ticket_updated))); ?></td>
						<td>
							<strong><?php _e("Priority", INCSUB_SUPPORT_LANG_DOMAIN); ?>:</strong> <?php echo $ticket_priority[$ticket->ticket_priority]; ?><br />
							<strong><?php _e("Status", INCSUB_SUPPORT_LANG_DOMAIN); ?>:</strong> <?php echo $ticket_status[$ticket->ticket_status]; ?><br />
							<strong><?php _e("Last Reply From", INCSUB_SUPPORT_LANG_DOMAIN); ?>:</strong> <?php echo $ticket->last_user_reply; ?>
						</td>
					</tr>
<?php
			}
?>
			</tbody>
		</table>
<?php
		} else {
?>
		<p><?php _e("You're in luck today, as you don't have any unanswered support tickets.", INCSUB_SUPPORT_LANG_DOMAIN); ?></p>
<?php
		}
?>
	</div>

	<div style="float: right; width: 35%">
		<h3><?php _e("Popular FAQ's", INCSUB_SUPPORT_LANG_DOMAIN); ?></h3>
<?php
		if ( !empty($top5help) ) {
?>
		<ul>
<?php
			foreach ( $top5help AS $faq ) {
?>
			<li>
				<a href="javascript:FAQReverseDisplay('answer-<?php echo $faq->faq_id; ?>')"><?php echo $faq->question; ?></a><br />
				<div id="answer-<?php echo $faq->faq_id; ?>" style="padding: 15px; border: 1px solid #464646; width: 90%;" class="invisible answer">
<?php
				if ( !empty($faq->help_count) and $faq->help_yes > 0 ) {
					$sentence = sprintf( __( '%1$s of %2$s users found this to be helpful.', INCSUB_SUPPORT_LANG_DOMAIN ), $faq->help_yes, $faq->help_count );
				} else {
					$sentence = "";
				}
?>
					<?php echo html_entity_decode($faq->answer); ?>
					<p style="padding: 10px; text-align: right;" class="vote_response" id="vote-response-<?php echo $faq->faq_id; ?>" >
						<?php _e("Was this solution helpful? ", INCSUB_SUPPORT_LANG_DOMAIN); ?>
						<a class="vote" href="admin.php?page=incsub_support_faq&amp;action=vote&amp;help=yes&amp;qid=<?php echo $faq->faq_id; ?>"><?php _e("Yes", INCSUB_SUPPORT_LANG_DOMAIN); ?></a> | <a class="vote" href="admin.php?page=incsub_support_faq&amp;action=vote&amp;help=no&amp;qid=<?php echo $faq->faq_id; ?>"><?php _e("No", INCSUB_SUPPORT_LANG_DOMAIN); ?></a><br />
						<?php echo "<small><em>{$sentence}</em></small>"; ?>
					</p>
				</div>
			</li>
<?php
			}
		} else {
?>
		<p><?php _e("We're currently updating and collecting new stats on our FAQ. Please visit", INCSUB_SUPPORT_LANG_DOMAIN); ?> <a href="admin.php?page=incsub_support_faq"><?php _e("our FAQ"); ?></a> <?php _e("for a full listing."); ?></p>
<?php
		}
?>
	</div>
</div>
<?php
}

function incsub_support_tickets_output() {
	global $current_site, $current_user, $blog_id, $wpdb, $ticket_status, $ticket_priority;
	
	// post routine.
	if ( !empty($_POST['addticket']) and $_POST['addticket'] == 1 ) {
		if ( empty($_POST['subject']) or !is_numeric($_POST['category']) or !is_numeric($_POST['priority']) or empty($_POST['message']) ) {
			$notification = __("Ticket Error: All fields are required.", INCSUB_SUPPORT_LANG_DOMAIN);
			$nclass = "error";
		} else {
			$title = strip_tags($_POST['subject']);
			$message = strip_tags($_POST['message']);
			$category = $_POST['category'];
			$priority = $_POST['priority'];
			$email_message = false;
			$wpdb->query("INSERT INTO ".incsub_support_tablename('tickets')."
				(site_id, blog_id, cat_id, user_id, ticket_priority, ticket_opened, title)
			VALUES (	
				'{$current_site->id}', '{$blog_id}', '{$category}', '{$current_user->id}',
				'{$priority}', NOW(), '{$title}')
			");
			if ( !empty($wpdb->insert_id) ) {
				$ticket_id = $wpdb->insert_id;
				$wpdb->query("INSERT INTO ".incsub_support_tablename('tickets_messages')."
					(site_id, ticket_id, user_id, subject, message)
					VALUES (
						'{$current_site->id}', '{$ticket_id}', '{$current_user->id}', '{$title}', '{$message}')
				");
				if ( !empty($wpdb->insert_id) ) {
					$notification = __("Thank you. Your ticket has been submitted. You will be notified by email of any responses to this ticket.", INCSUB_SUPPORT_LANG_DOMAIN);
					$nclass = "updated fade";
					$title = stripslashes($title);
					$email_message = array(
						"to"		=> incsub_support_notification_admin_email(),
						"subject"	=> __("New Support Ticket: ", INCSUB_SUPPORT_LANG_DOMAIN) . $title,
						"message"	=> _("
	".((get_site_option('incsub_support_fetch_imap', 'disabled') == 'enabled')?"***  DO NOT WRITE BELLOW THIS LINE  ***":"***  DO NOT REPLY TO THIS EMAIL  ***")."

	Subject: ". $title ."
	Status: ". $ticket_status[$status] ."
	Priority: ". $ticket_priority[$priority] ."

	Visit:

		http://". $current_site->domain . $current_site->path ."wp-admin/ms-admin.php?page=ticket-manager&tid={$ticket_id}

	to reply to view the new ticket.


	------------------------------
	     Begin Ticket Message
	------------------------------

	". $wpdb->get_var("SELECT user_nicename FROM {$wpdb->users} WHERE ID = '{$current_user->id}'") ." said:


	". $message ."

	------------------------------
	      End Ticket Message
	------------------------------


	Ticket URL:
		http://". $current_site->domain . $current_site->path ."wp-admin/ms-admin.php?page=ticket-manager&tid={$ticket_id}"), // ends lang string

	"headers"	=> "MIME-Version: 1.0\n" . "From: \"". get_site_option('incsub_support_from_name', get_bloginfo('blogname')) ."\" <". get_site_option('incsub_support_from_mail', get_bloginfo('admin_email')) .">\n" . "Content-Type: text/plain; charset=\"" . get_option('blog_charset') . "\"\n",
					); // ends array.
				} else {
				$notification = __("Ticket Error: There was an error submitting your ticket. Please try again in a few minutes.", INCSUB_SUPPORT_LANG_DOMAIN);
					$nclass = "error";
				}
			} else {
				$notification = __("Ticket Error: There was an error submitting your ticket. Please try again in a few minutes.", INCSUB_SUPPORT_LANG_DOMAIN);
				$nclass = "error";
			}
		}
	} elseif ( !empty($_POST['modifyticket']) and $_POST['modifyticket'] == 1 ) {
		if ( !empty($_POST['canelsubmit']) ) {
			wp_redirect("admin.php?page=incsub_support_tickets");
			exit();
		}
		if ( empty($_POST['subject']) or !is_numeric($_POST['category']) or !is_numeric($_POST['priority']) or !is_numeric($_POST['status']) or !is_numeric($_POST['ticket_id']) or empty($_POST['message']) ) {
			$notification = __("Ticket Error: All fields are required.", INCSUB_SUPPORT_LANG_DOMAIN);
			$nclass = "error";
		} else {
			$title = strip_tags($_POST['subject']);
			$message = strip_tags($_POST['message']);
			$category = $_POST['category'];
			$priority = $_POST['priority'];
			$ticket_id = $_POST['ticket_id'];
			$status = ($_POST['closeticket'] == 1) ? 5 : 3;
			$email_message = false;
			$wpdb->query("INSERT INTO ".incsub_support_tablename('tickets_messages')."
				(site_id, ticket_id, user_id, subject, message)
				VALUES ('{$current_site->id}', '{$ticket_id}', '{$current_user->id}', '{$title}', '{$message}')
			");

			if ( !empty($wpdb->insert_id) ) {
				$wpdb->query("UPDATE ".incsub_support_tablename('tickets')."
					SET
						cat_id = '{$category}', last_reply_id = '{$current_user->id}', ticket_priority = '{$priority}', ticket_status = '{$status}', num_replies = num_replies+1
					WHERE site_id = '{$current_site->id}' AND blog_id = '{$blog_id}' AND ticket_id = '{$ticket_id}'
					LIMIT 1
				");

				if ( !empty($wpdb->rows_affected) ) {
					$notification = __("Thank you. Your ticket has been updated. You will be notified by email of any responses to this ticket.", INCSUB_SUPPORT_LANG_DOMAIN);
					$nclass = "updated fade";
					$title = stripslashes($title);
					$email_message = array(
						"to"		=> incsub_support_notification_admin_email(),
						"subject"	=> __("[#{$ticket_id}] ", INCSUB_SUPPORT_LANG_DOMAIN) . $title,
						"message"	=> _("

	".((get_site_option('incsub_support_fetch_imap', 'disabled') == 'enabled')?"***  DO NOT WRITE BELLOW THIS LINE  ***":"***  DO NOT REPLY TO THIS EMAIL  ***")."

	Subject: ". $title ."
	Status: ". $ticket_status[$status] ."
	Priority: ". $ticket_priority[$priority] ."

	Visit:

		http://". $current_site->domain . $current_site->path ."wp-admin/ms-admin.php?page=ticket-manager&tid={$ticket_id}

	to respond to this ticket update.


	------------------------------
	     Begin Ticket Message
	------------------------------

	". $wpdb->get_var("SELECT user_nicename FROM {$wpdb->users} WHERE ID = '{$current_user->id}'") ." said:


	". $message ."

	------------------------------
	      End Ticket Message
	------------------------------


	Ticket URL:
		http://". $current_site->domain . $current_site->path ."wp-admin/ms-admin.php?page=ticket-manager&tid={$ticket_id}"), // ends lang string

	"headers"	=> "MIME-Version: 1.0\n" . "From: \"". get_site_option('incsub_support_from_name', get_bloginfo('blogname')) ."\" <". get_site_option('incsub_support_from_mail', get_bloginfo('admin_email')) .">\n" . "Content-Type: text/plain; charset=\"" . get_option('blog_charset') . "\"\n",

					); // ends array.

				} else {
					$notification = __("Ticket Error: There was an error updating your ticket. Please try again in a few minutes.", INCSUB_SUPPORT_LANG_DOMAIN);
					$nclass = "error";
				}
			} else {
				$notification = __("Ticket Error: There was an error adding your reply. Please try again in a few minutes.", INCSUB_SUPPORT_LANG_DOMAIN);
				$nclass = "error";
			}
		}
	}
	if ( !empty($notification) ) {
		if ( !empty($email_message) and is_array($email_message) ) {
			wp_mail($email_message["to"], $email_message["subject"], $email_message["message"], $email_message["headers"]);
		}
?>
	<div class="<?php echo $nclass; ?>"><?php echo $notification; ?></div>
<?php
	}

	$do_history = ( !empty($_GET['action']) and $_GET['action'] == 'history' ) ? '' : 'AND t.ticket_updated > DATE_SUB(CURDATE(), INTERVAL 1 MONTH)';
	$tickets = $wpdb->get_results("
		SELECT t.ticket_id, t.user_id, t.cat_id, t.admin_id, t.ticket_type, t.ticket_priority, t.ticket_status, t.ticket_updated, t.title, c.cat_name, u.display_name
		FROM ".incsub_support_tablename('tickets')." AS t
		LEFT JOIN ".incsub_support_tablename('tickets_cats')." AS c ON (t.cat_id = c.cat_id)
		LEFT JOIN $wpdb->users AS u ON (t.admin_id = u.ID)
		WHERE t.site_id = '{$current_site->id}' AND t.blog_id = '{$blog_id}' {$do_history}
	");
?>
<div class="wrap">
<?php
	if ( !empty($_GET['tid']) and is_numeric($_GET['tid']) ) {
		$current_ticket = $wpdb->get_results("
		SELECT
			t.ticket_id, t.cat_id, t.user_id, t.admin_id, t.ticket_type, t.ticket_priority, t.ticket_status, t.ticket_opened, t.ticket_updated, t.title,
			c.cat_name, u.display_name AS user_name, a.display_name AS admin_name, l.display_name AS last_user_reply, m.user_id AS user_avatar_id, 
			m.admin_id AS admin_avatar_id, m.message_date, m.subject, m.message, r.display_name AS reporting_name, s.display_name AS staff_member
		FROM ".incsub_support_tablename('tickets')."_messages AS m
		LEFT JOIN ".incsub_support_tablename('tickets')." AS t ON (m.ticket_id = t.ticket_id)
		LEFT JOIN $wpdb->users AS u ON (t.user_id = u.ID)
		LEFT JOIN $wpdb->users AS a ON (t.admin_id = a.ID)
		LEFT JOIN $wpdb->users AS l ON (t.last_reply_id = l.ID)
		LEFT JOIN $wpdb->users AS r ON (m.user_id = r.ID)
		LEFT JOIN $wpdb->users AS s ON (m.admin_id = s.ID)
		LEFT JOIN ".incsub_support_tablename('tickets_cats')." AS c ON (t.cat_id = c.cat_id)
		WHERE (m.ticket_id = '". $_GET['tid'] ."' AND t.site_id = '{$current_site->id}' AND t.blog_id = '{$blog_id}')
		ORDER BY m.message_id ASC
	");

		if ( empty($current_ticket) ) {
			$ticket_error = 1;
?>
	<h2 class="error"><?php _e("Error: Invalid Ticket Selected", INCSUB_SUPPORT_LANG_DOMAIN); ?></h2>
<?
		} else {
		$message_list = $current_ticket;
		$current_ticket = $current_ticket[0];
		$current_ticket->admin_name = !empty($current_ticket->admin_name) ? $current_ticket->admin_name : __("Not yet assigned", INCSUB_SUPPORT_LANG_DOMAIN);
?>
	<h2><?php _e("Ticket Details", INCSUB_SUPPORT_LANG_DOMAIN); ?></h2>
		<form action="admin.php?page=incsub_support_tickets" method="post" name="updateticket" id="updateticket">
			<table class="form-table" border="1">
				<tr class="form-field form-required">
					<th scope="row" style="background: #464646; color: #FEFEFE; border: 1px solid #242424;"><?php _e("Ticket Subject:", INCSUB_SUPPORT_LANG_DOMAIN); ?></th>
					<td style="border-bottom:0;">
						<?php echo $current_ticket->title; ?>
						<input type="hidden" name="tickettitle" value="<?php echo "Re: ".$current_ticket->title; ?>" />
						<input type="hidden" name="ticket_id" value="<?php echo $current_ticket->ticket_id; ?>" />
					</td>
					<th scope="row" style="background: #464646; color: #FEFEFE; border: 1px solid #242424;"><?php _e("Current Date/Time:", INCSUB_SUPPORT_LANG_DOMAIN); ?></th>
					<td style="border-bottom:0;"><?php echo date(get_option("date_format") ." ". get_option("time_format") ." T  (\G\M\T P)", time()); ?></td>
				</tr>
				<tr class="form-field form-required">
					<th scope="row" style="background: #464646; color: #FEFEFE; border: 1px solid #242424;"><?php _e("Reporting User:", INCSUB_SUPPORT_LANG_DOMAIN); ?></th>
					<td style="border-bottom:0;"><?php echo $current_ticket->user_name; ?></td>
					<th scope="row" style="background: #464646; color: #FEFEFE; border: 1px solid #242424;"><?php _e("Staff Representative:", INCSUB_SUPPORT_LANG_DOMAIN); ?></th>
					<td style="border-bottom:0;"><?php echo $current_ticket->admin_name; ?></td>
				</tr>
				<tr class="form-field form-required">
					<th scope="row" style="background: #464646; color: #FEFEFE; border: 1px solid #242424;"><?php _e("Last Reply From:", INCSUB_SUPPORT_LANG_DOMAIN); ?></th>
					<td style="border-bottom:0;"><?php echo $current_ticket->last_user_reply; ?></td>
					<th scope="row" style="background: #464646; color: #FEFEFE; border: 1px solid #242424;"><?php _e("Current Status:", INCSUB_SUPPORT_LANG_DOMAIN); ?></th>
					<td style="border-bottom:0;">
						<?php echo $ticket_status[$current_ticket->ticket_status]; ?>
						<input type="hidden" name="status" value="<?php echo $current_ticket->ticket_status; ?>" />
					</td>
				</tr>
				<tr class="form-field form-required">
					<th scope="row" style="background: #464646; color: #FEFEFE; border: 1px solid #242424;"><?php _e("Last Updated:", INCSUB_SUPPORT_LANG_DOMAIN); ?></th>
					<td style="border-bottom:0;"><?php echo date(get_option("date_format") ." ". get_option("time_format") ." T  (\G\M\T P)", strtotime($current_ticket->ticket_updated)); ?></td>
					<th scope="row" style="background: #464646; color: #FEFEFE; border: 1px solid #242424;"><?php _e("Created On:", INCSUB_SUPPORT_LANG_DOMAIN); ?></th>
					<td style="border-bottom:0;"><?php echo date(get_option("date_format") ." ". get_option("time_format") ." T  (\G\M\T P)", strtotime($current_ticket->ticket_opened)); ?></td>
				</tr>
			</table>
			<br /><br />
			<h2><?php _e("Ticket History", INCSUB_SUPPORT_LANG_DOMAIN); ?></h2><br />
			<table class="widefat" cellpadding="3" cellspacing="3" border="1">
				<thead>
					<tr>
						<th scope="col"><?php _e("Author", INCSUB_SUPPORT_LANG_DOMAIN); ?></th>
						<th scope="col"><?php _e("Ticket Message/Reply", INCSUB_SUPPORT_LANG_DOMAIN); ?></th>
						<th scope="col"><?php _e("Date/Time", INCSUB_SUPPORT_LANG_DOMAIN); ?></th>
					</tr>
				</thead>
				<tbody>
<?php
			if ( !empty($message_list) ) {
				foreach ( $message_list as $message ) {
					if ( !empty($message->reporting_name) ) {
						$avatar_id = $message->user_avatar_id;
						$avatar = '<img src="'. WP_PLUGIN_URL . '/incsub-support/images/user.gif" alt="User" />';
						$display_name = $message->reporting_name ."<br /><br />";
						$mclass = ' class="alternate"';
					} elseif ( !empty($message->staff_member) ) {
						$avatar_id = $message->admin_avatar_id;
						$avatar = '<img src="'. WP_PLUGIN_URL . '/incsub-support/images/staff.gif" alt="User" />';
						$display_name = $message->staff_member ."<br /><br />";
						$mclass = ' style="background-color: #cccccc;"';
					} else {
						$avatar_id = "";
						$display_name = __("User", INCSUB_SUPPORT_LANG_DOMAIN);
						$mclass = '';
					}
					if ( function_exists("get_blog_avatar") ) {
						// check for blog avatar function, as get_avatar is too common.
						$avatar = get_avatar($avatar_id,'32','gravatar_default');
					}
?>
					<tr<?php echo $mclass; ?>>
						<th scope="row" style="text-align: center;"><?php echo $display_name . $avatar; ?></th>
						<td style="padding: 0 5px 5px 5px;">
							<h3 style="margin-top: .5em;"><?php echo $message->subject; ?></h3>
							<div style="padding: 0 20px;">
								<?php echo wpautop(html_entity_decode($message->message)); ?>
							</div>
						</td>
						<td><?php echo date(get_option("date_format") ." ". get_option("time_format") ." T  (\G\M\T P)", strtotime($message->message_date)); ?></td>
					</tr>
<?php
				}
?>

<?php
			}
?>
				</tbody>
			</table>
			<br /><br />
<?php
			if ( $current_ticket->ticket_status != 5 ) {
			// ticket isn't closed
?>
			<h2><?php _e("Update This Ticket", INCSUB_SUPPORT_LANG_DOMAIN); ?></h2>
			<p><em><?php _e("* All fields are required.", INCSUB_SUPPORT_LANG_DOMAIN); ?></em></p>
			<table class="form-table">
				<tr class="form-field form-required">
					<th scope="row"><label for="subject"><?php _e("Subject", INCSUB_SUPPORT_LANG_DOMAIN); ?></label></th>
					<td><input type="text" name="subject" id="subject" maxlength="100" size="60" value="Re: <?php echo $current_ticket->title; ?>" />&nbsp;<small>(<?php _e("max: 100 characters"); ?>)</small></td>
				</tr>
				<tr class="form-field form-required">
					<th scope="row"><?php _e('Category', INCSUB_SUPPORT_LANG_DOMAIN); ?>:</th>
					<td>
						<select name="category" id="category">
<?php
				$get_cats = $wpdb->get_results("SELECT cat_id, cat_name FROM ".incsub_support_tablename('tickets_cats')." WHERE site_id = '{$current_site->id}' ORDER BY cat_name ASC");
				if ( empty($get_cats) ) {
					$wpdb->query("INSERT INTO ".incsub_support_tablename('tickets_cats')." (site_id, cat_name) VALUES ('{$current_site->id}', 'General')");
					$get_cats = $wpdb->get_results("SELECT cat_id, cat_name FROM ".incsub_support_tablename('tickets_cats')." WHERE site_id = '{$current_site->id}' ORDER BY cat_name ASC");
				}
				$x = 0;
				foreach ($get_cats as $cat) {
					if ( $cat->cat_id == $current_ticket->cat_id ) {
						$selected = ' selected="selected"';
						$x++;
					} else {
						$selected = "";
					}
?>
							<option<?php echo $selected; ?> value="<?php echo $cat->cat_id; ?>"><?php echo $cat->cat_name; ?></option>
<?php
				}
?>
						</select>
					</td>
				</tr>
				<tr class="form-field form-required">
					<th scope="row"><?php _e('Priority', INCSUB_SUPPORT_LANG_DOMAIN); ?>:</th>
					<td>
						<select name="priority" id="priority">
<?php
				foreach ($ticket_priority as $key => $val) {
					if ( $key == $current_ticket->ticket_priority ) {
						$selected = ' selected="selected"';
					} else {
						$selected = "";
					}
?>
							<option<?php echo $selected; ?> value="<?php echo $key; ?>"><?php echo $val; ?></option>
<?php
				}
?>
						</select>
					</td>
				</tr>
				<tr class="form-field form-required">
					<th scope="row"><label for="message"><?php _e("Add A Reply", INCSUB_SUPPORT_LANG_DOMAIN); ?></label></th>
					<td>&nbsp;<small>(<?php _e("Please provide as much information as possible, so that we may better help you.", INCSUB_SUPPORT_LANG_DOMAIN); ?>)</small><br /><textarea name="message" id="message" class="message" rows="12" cols="58"></textarea></td>
				</tr>
				<tr class="form-field form-required">
					<th scope="row"><label for="closeticket"><?php _e("Close Ticket?", INCSUB_SUPPORT_LANG_DOMAIN); ?></label></th>
					<td><input type="checkbox" name="closeticket" id="closeticket" value="1" /> <strong><?php _e("Yes, please close this ticket.", INCSUB_SUPPORT_LANG_DOMAIN); ?></strong><br /><small><em><?php _e("Once a ticket is closed, you can no longer reply to (or update) it.", INCSUB_SUPPORT_LANG_DOMAIN); ?></em></small></td>
				</tr>
			</table>
			<p class="submit">
				<input type="hidden" name="modifyticket" value="1" />
				<input name="updateticket" type="submit" id="updateticket" value="<?php _e("Update Ticket", INCSUB_SUPPORT_LANG_DOMAIN); ?>" />&nbsp;&nbsp;&nbsp;&nbsp;<input name="canelsubmit" type="submit" id="cancelsubmit" value="<?php _e("Cancel", INCSUB_SUPPORT_LANG_DOMAIN); ?>" />
			</p>
		</form>
<?php
			} // end if ticket !closed check.
		} // end else check for current ticket
	} // end check for GET tid

	if ( empty($current_ticket) and empty($ticket_error) ) {
?>
	<h2><?php _e("Recent Support Tickets", INCSUB_SUPPORT_LANG_DOMAIN); ?> <small style="font-size: 12px; padding-left: 10px;">(<a href="admin.php?page=incsub_support_tickets&amp;action=history"><?php _e("Ticket History", INCSUB_SUPPORT_LANG_DOMAIN); ?></a>)</small></h2>
	<br />
		<table width="100%" cellpadding="3" cellspacing="3" class="widefat">
			<thead>
				<tr>
					<th scope="col"><?php _e("Ticket ID", INCSUB_SUPPORT_LANG_DOMAIN); ?></th>
					<th scope="col"><?php _e("Subject", INCSUB_SUPPORT_LANG_DOMAIN); ?></th>
					<th scope="col"><?php _e("Status", INCSUB_SUPPORT_LANG_DOMAIN); ?></th>
					<th scope="col"><?php _e("Priority", INCSUB_SUPPORT_LANG_DOMAIN); ?></th>
					<th scope="col"><?php _e("Staff Member", INCSUB_SUPPORT_LANG_DOMAIN); ?></th>
					<th scope="col"><?php _e("Last Updated", INCSUB_SUPPORT_LANG_DOMAIN); ?></th>
				</tr>
			</thead>
			<tbody id="the-list">
<?php

		if ( empty($tickets) ) {
?>
				<tr class='alternate'>
					<th scope="row" colspan="6">
						<p><?php _e("There aren't any tickets to view at this time.", INCSUB_SUPPORT_LANG_DOMAIN); ?></p>
					</th>
				</tr>
<?php
		} else {
			foreach ($tickets as $ticket) {
			$class = ( $class != "alternate") ? "alternate" : "";
			if ( empty($ticket->display_name) ) { $ticket->display_name = __("Unassigned", INCSUB_SUPPORT_LANG_DOMAIN); }
?>
				<tr class='<?php echo $class; ?>'>
					<th scope="row"><?php echo $ticket->ticket_id; ?></th>
					<td valign="top"><a href="admin.php?page=incsub_support_tickets&amp;tid=<?php echo $ticket->ticket_id; ?>"><?php echo $ticket->title; ?></a></td>
					<td valign="top"><?php echo $ticket_status[$ticket->ticket_status]; ?></td>
					<td valign="top"><?php echo $ticket_priority[$ticket->ticket_priority]; ?></td>
					<td valign="top"><?php echo $ticket->display_name; ?></td>
					<td valign="top"><?php echo date(get_option("date_format") ." ". get_option("time_format") ." T  (\G\M\T P)", strtotime($ticket->ticket_updated)); ?></td>
				</tr>
<?php
			}
		}
?>
			</tbody>
		</table>
	<br /><br />

	<h2><?php _e("Add Ticket", INCSUB_SUPPORT_LANG_DOMAIN); ?></h2>
		<p><em><?php _e("* All fields are required.", INCSUB_SUPPORT_LANG_DOMAIN); ?></em></p>
		<form action="admin.php?page=incsub_support_tickets" method="post" name="newticket" id="newticket">
			<table class="form-table">
				<tr class="form-field form-required">
					<th scope="row"><label for="subject"><?php _e("Subject", INCSUB_SUPPORT_LANG_DOMAIN); ?></label></th>
					<td><input type="text" name="subject" id="subject" maxlength="100" size="60" />&nbsp;<small>(<?php _e("max: 100 characters", INCSUB_SUPPORT_LANG_DOMAIN); ?>)</small></td>
				</tr>
				<tr class="form-field form-required">
					<th scope="row"><?php _e('Category', INCSUB_SUPPORT_LANG_DOMAIN); ?>:</th>
					<td>
						<select name="category" id="category">
<?php 			
		$get_cats = $wpdb->get_results("SELECT cat_id, cat_name FROM ".incsub_support_tablename('tickets_cats')." WHERE site_id = '{$current_site->id}' ORDER BY cat_name ASC");
		if ( empty($get_cats) ) {
			$wpdb->query("INSERT INTO ".incsub_support_tablename('tickets_cats')." (site_id, cat_name) VALUES ('{$current_site->id}', 'General')");
			$get_cats = $wpdb->get_results("SELECT cat_id, cat_name FROM ".incsub_support_tablename('tickets_cats')." WHERE site_id = '{$current_site->id}' ORDER BY cat_name ASC");
		}
		$x = 0;
		foreach ($get_cats as $cat) {
			if ( $x == 0 ) {
				$selected = ' selected="selected"';
				$x++;
			} else {
				$selected = "";
			}
?>
							<option<?php echo $selected; ?> value="<?php echo $cat->cat_id; ?>"><?php echo $cat->cat_name; ?></option>
<?php
		}
?>
						</select>
					</td>
				</tr>
				<tr class="form-field form-required">
					<th scope="row"><?php _e('Priority', INCSUB_SUPPORT_LANG_DOMAIN); ?>:</th>
					<td>
						<select name="priority" id="priority">
<?php
		$x = 0;
		foreach ($ticket_priority as $key => $val) {
			if ( $x == 0 ) {
				$selected = ' selected="selected"';
				$x++;
			} else {
				$selected = "";
			}
?>
							<option<?php echo $selected; ?> value="<?php echo $key; ?>"><?php echo $val; ?></option>
<?php
		}
?>
						</select>
					</td>
				</tr>
				<tr class="form-field form-required">
					<th scope="row"><?php _e("Status", INCSUB_SUPPORT_LANG_DOMAIN); ?></th>
					<td><em><?php _e("New Ticket", INCSUB_SUPPORT_LANG_DOMAIN); ?></em></td>
				</tr>
				<tr class="form-field form-required">
					<th scope="row"><label for="message"><?php _e("Problem Description", INCSUB_SUPPORT_LANG_DOMAIN); ?></label></th>
					<td>&nbsp;<small>(<?php _e("Please provide as much information as possible, so that we may better help you.", INCSUB_SUPPORT_LANG_DOMAIN); ?>)</small><br /><textarea name="message" id="message" class="message" rows="12" cols="58"></textarea></td>
				</tr>
			</table>
			<p class="submit">
				<input type="hidden" name="addticket" value="1" />
				<input name="submitticket" type="submit" id="addusersub" value="<?php _e("Submit New Ticket", INCSUB_SUPPORT_LANG_DOMAIN); ?>" />
			</p>
		</form>

<?php
	} // end empty current ticket check
?>
</div>
<?php
}

function incsub_support_notification_admin_email() {
	global $wpdb;
	$admins = get_site_option("site_admins");
	if ( !empty($admins) ) {
		// we only need the first one.
		return $wpdb->get_var("SELECT user_email FROM {$wpdb->users} WHERE user_login = '{$admins[0]}'");
	} else {
		// not likely, if so, they have more problems than we can help with. :)
		return get_site_option("admin_email");
	}
}

function incsub_support_process_reply($curr_user = null) {
	global $current_site, $current_user, $blog_id, $wpdb, $ticket_status, $ticket_priority;
	
	if ($curr_user !=  null) {
		$current_user = $curr_user;
		$current_user->id = $current_user->ID;
	}
	
	// post routine.
	if ( !empty($_POST['addticket']) and $_POST['addticket'] == 1 ) {
		if ( empty($_POST['subject']) or !is_numeric($_POST['category']) or !is_numeric($_POST['priority']) or empty($_POST['message']) ) {
			$notification = __("Ticket Error: All fields are required.", INCSUB_SUPPORT_LANG_DOMAIN);
			$nclass = "error";
		} else {
			$title = strip_tags($_POST['subject']);
			$message = strip_tags($_POST['message']);
			$category = $_POST['category'];
			$priority = $_POST['priority'];
			$email_message = false;
			$wpdb->query("INSERT INTO ".incsub_support_tablename('tickets')."
				(site_id, blog_id, cat_id, user_id, ticket_priority, ticket_opened, title)
			VALUES (	
				'{$current_site->id}', '{$blog_id}', '{$category}', '{$current_user->id}',
				'{$priority}', NOW(), '{$title}')
			");
			if ( !empty($wpdb->insert_id) ) {
				$ticket_id = $wpdb->insert_id;
				$wpdb->query("INSERT INTO ".incsub_support_tablename('tickets_messages')."
					(site_id, ticket_id, user_id, subject, message)
					VALUES (
						'{$current_site->id}', '{$ticket_id}', '{$current_user->id}', '{$title}', '{$message}')
				");
				if ( !empty($wpdb->insert_id) ) {
					$notification = __("Thank you. Your ticket has been submitted. You will be notified by email of any responses to this ticket.", INCSUB_SUPPORT_LANG_DOMAIN);
					$nclass = "updated fade";
					$title = stripslashes($title);
					$email_message = array(
						"to"		=> incsub_support_notification_admin_email(),
						"subject"	=> __("New Support Ticket: ", INCSUB_SUPPORT_LANG_DOMAIN) . $title,
						"message"	=> _("
	".((get_site_option('incsub_support_fetch_imap', 'disabled') == 'enabled')?"***  DO NOT WRITE BELLOW THIS LINE  ***":"***  DO NOT REPLY TO THIS EMAIL  ***")."

	Subject: ". $title ."
	Status: ". $ticket_status[$status] ."
	Priority: ". $ticket_priority[$priority] ."

	Visit:

		http://". $current_site->domain . $current_site->path ."wp-admin/ms-admin.php?page=ticket-manager&tid={$ticket_id}

	to reply to view the new ticket.


	------------------------------
	     Begin Ticket Message
	------------------------------

	". $wpdb->get_var("SELECT user_nicename FROM {$wpdb->users} WHERE ID = '{$current_user->id}'") ." said:


	". $message ."

	------------------------------
	      End Ticket Message
	------------------------------


	Ticket URL:
		http://". $current_site->domain . $current_site->path ."wp-admin/ms-admin.php?page=ticket-manager&tid={$ticket_id}"), // ends lang string

	"headers"	=> "MIME-Version: 1.0\n" . "From: \"". get_site_option('incsub_support_from_name', get_bloginfo('blogname')) ."\" <". get_site_option('incsub_support_from_mail', get_bloginfo('admin_email')) .">\n" . "Content-Type: text/plain; charset=\"" . get_option('blog_charset') . "\"\n",
					); // ends array.
				} else {
				$notification = __("Ticket Error: There was an error submitting your ticket. Please try again in a few minutes.", INCSUB_SUPPORT_LANG_DOMAIN);
					$nclass = "error";
				}
			} else {
				$notification = __("Ticket Error: There was an error submitting your ticket. Please try again in a few minutes.", INCSUB_SUPPORT_LANG_DOMAIN);
				$nclass = "error";
			}
		}
	} elseif ( !empty($_POST['modifyticket']) and $_POST['modifyticket'] == 1 ) {
		if ( !empty($_POST['canelsubmit']) ) {
			wp_redirect("admin.php?page=incsub_support_tickets");
			exit();
		}
		if ( empty($_POST['subject']) or !is_numeric($_POST['category']) or !is_numeric($_POST['priority']) or !is_numeric($_POST['status']) or !is_numeric($_POST['ticket_id']) or empty($_POST['message']) ) {
			$notification = __("Ticket Error: All fields are required.", INCSUB_SUPPORT_LANG_DOMAIN);
			$nclass = "error";
		} else {
			$title = strip_tags($_POST['subject']);
			$message = strip_tags($_POST['message']);
			$category = $_POST['category'];
			$priority = $_POST['priority'];
			$ticket_id = $_POST['ticket_id'];
			$status = ($_POST['closeticket'] == 1) ? 5 : 3;
			$email_message = false;
			$wpdb->query("INSERT INTO ".incsub_support_tablename('tickets_messages')."
				(site_id, ticket_id, user_id, subject, message)
				VALUES ('{$current_site->id}', '{$ticket_id}', '{$current_user->id}', '{$title}', '{$message}')
			");

			if ( !empty($wpdb->insert_id) ) {
				$wpdb->query("UPDATE ".incsub_support_tablename('tickets')."
					SET
						cat_id = '{$category}', last_reply_id = '{$current_user->id}', ticket_priority = '{$priority}', ticket_status = '{$status}', num_replies = num_replies+1
					WHERE site_id = '{$current_site->id}' AND blog_id = '{$blog_id}' AND ticket_id = '{$ticket_id}'
					LIMIT 1
				");

				if ( !empty($wpdb->rows_affected) ) {
					$notification = __("Thank you. Your ticket has been updated. You will be notified by email of any responses to this ticket.", INCSUB_SUPPORT_LANG_DOMAIN);
					$nclass = "updated fade";
					$title = stripslashes($title);
					$email_message = array(
						"to"		=> incsub_support_notification_admin_email(),
						"subject"	=> __("[#{$ticket_id}] ", INCSUB_SUPPORT_LANG_DOMAIN) . $title,
						"message"	=> _("

	".((get_site_option('incsub_support_fetch_imap', 'disabled') == 'enabled')?"***  DO NOT WRITE BELLOW THIS LINE  ***":"***  DO NOT REPLY TO THIS EMAIL  ***")."

	Subject: ". $title ."
	Status: ". $ticket_status[$status] ."
	Priority: ". $ticket_priority[$priority] ."

	Visit:

		http://". $current_site->domain . $current_site->path ."wp-admin/ms-admin.php?page=ticket-manager&tid={$ticket_id}

	to respond to this ticket update.


	------------------------------
	     Begin Ticket Message
	------------------------------

	". $wpdb->get_var("SELECT user_nicename FROM {$wpdb->users} WHERE ID = '{$current_user->id}'") ." said:


	". $message ."

	------------------------------
	      End Ticket Message
	------------------------------


	Ticket URL:
		http://". $current_site->domain . $current_site->path ."wp-admin/ms-admin.php?page=ticket-manager&tid={$ticket_id}"), // ends lang string

	"headers"	=> "MIME-Version: 1.0\n" . "From: \"". get_site_option('incsub_support_from_name', get_bloginfo('blogname')) ."\" <". get_site_option('incsub_support_from_mail', get_bloginfo('admin_email')) .">\n" . "Content-Type: text/plain; charset=\"" . get_option('blog_charset') . "\"\n",

					); // ends array.

				} else {
					$notification = __("Ticket Error: There was an error updating your ticket. Please try again in a few minutes.", INCSUB_SUPPORT_LANG_DOMAIN);
					$nclass = "error";
				}
			} else {
				$notification = __("Ticket Error: There was an error adding your reply. Please try again in a few minutes.", INCSUB_SUPPORT_LANG_DOMAIN);
				$nclass = "error";
			}
		}
	}
	
	if ( !empty($notification) ) {
		if ( !empty($email_message) and is_array($email_message) ) {
			wp_mail($email_message["to"], $email_message["subject"], $email_message["message"], $email_message["headers"]);
		}
	}
	
	return array($notification, $nclass);
}

function incsub_support_output_tickets() {
	global $current_site, $current_user, $blog_id, $wpdb, $ticket_status, $ticket_priority;
	// post routine.
	
	list($notification, $nclass) = incsub_support_process_reply();
	
	if ( !empty($notification) ) {
?>
	<div class="<?php echo $nclass; ?>"><?php echo $notification; ?></div>
<?php
	}

	$do_history = ( !empty($_GET['action']) and $_GET['action'] == 'history' ) ? '' : 'AND t.ticket_updated > DATE_SUB(CURDATE(), INTERVAL 1 MONTH)';
	$tickets = $wpdb->get_results("
		SELECT t.ticket_id, t.user_id, t.cat_id, t.admin_id, t.ticket_type, t.ticket_priority, t.ticket_status, t.ticket_updated, t.title, c.cat_name, u.display_name
		FROM ".incsub_support_tablename('tickets')." AS t
		LEFT JOIN ".incsub_support_tablename('tickets_cats')." AS c ON (t.cat_id = c.cat_id)
		LEFT JOIN $wpdb->users AS u ON (t.admin_id = u.ID)
		WHERE t.site_id = '{$current_site->id}' AND t.blog_id = '{$blog_id}' {$do_history}
	");
?>
<div class="wrap">
<?php
	if ( !empty($_GET['tid']) and is_numeric($_GET['tid']) ) {
		$current_ticket = $wpdb->get_results("
		SELECT
			t.ticket_id, t.cat_id, t.user_id, t.admin_id, t.ticket_type, t.ticket_priority, t.ticket_status, t.ticket_opened, t.ticket_updated, t.title,
			c.cat_name, u.display_name AS user_name, a.display_name AS admin_name, l.display_name AS last_user_reply, m.user_id AS user_avatar_id, 
			m.admin_id AS admin_avatar_id, m.message_date, m.subject, m.message, r.display_name AS reporting_name, s.display_name AS staff_member
		FROM ".incsub_support_tablename('tickets')."_messages AS m
		LEFT JOIN ".incsub_support_tablename('tickets')." AS t ON (m.ticket_id = t.ticket_id)
		LEFT JOIN $wpdb->users AS u ON (t.user_id = u.ID)
		LEFT JOIN $wpdb->users AS a ON (t.admin_id = a.ID)
		LEFT JOIN $wpdb->users AS l ON (t.last_reply_id = l.ID)
		LEFT JOIN $wpdb->users AS r ON (m.user_id = r.ID)
		LEFT JOIN $wpdb->users AS s ON (m.admin_id = s.ID)
		LEFT JOIN ".incsub_support_tablename('tickets_cats')." AS c ON (t.cat_id = c.cat_id)
		WHERE (m.ticket_id = '". $_GET['tid'] ."' AND t.site_id = '{$current_site->id}' AND t.blog_id = '{$blog_id}')
		ORDER BY m.message_id ASC
	");

		if ( empty($current_ticket) ) {
			$ticket_error = 1;
?>
	<h2 class="error"><?php _e("Error: Invalid Ticket Selected", INCSUB_SUPPORT_LANG_DOMAIN); ?></h2>
<?
		} else {
		$message_list = $current_ticket;
		$current_ticket = $current_ticket[0];
		$current_ticket->admin_name = !empty($current_ticket->admin_name) ? $current_ticket->admin_name : __("Not yet assigned");
?>
	<h2><?php _e("Ticket Details", INCSUB_SUPPORT_LANG_DOMAIN); ?></h2>
		<form action="admin.php?page=incsub_support_tickets" method="post" name="updateticket" id="updateticket">
			<table class="form-table" border="1">
				<tr class="form-field form-required">
					<th scope="row" style="background: #464646; color: #FEFEFE; border: 1px solid #242424;"><?php _e("Ticket Subject:", INCSUB_SUPPORT_LANG_DOMAIN); ?></th>
					<td style="border-bottom:0;">
						<?php echo $current_ticket->title; ?>
						<input type="hidden" name="tickettitle" value="<?php echo "Re: ".$current_ticket->title; ?>" />
						<input type="hidden" name="ticket_id" value="<?php echo $current_ticket->ticket_id; ?>" />
					</td>
					<th scope="row" style="background: #464646; color: #FEFEFE; border: 1px solid #242424;"><?php _e("Current Date/Time:", INCSUB_SUPPORT_LANG_DOMAIN); ?></th>
					<td style="border-bottom:0;"><?php echo date(get_option("date_format") ." ". get_option("time_format") ." T  (\G\M\T P)", time()); ?></td>
				</tr>
				<tr class="form-field form-required">
					<th scope="row" style="background: #464646; color: #FEFEFE; border: 1px solid #242424;"><?php _e("Reporting User:", INCSUB_SUPPORT_LANG_DOMAIN); ?></th>
					<td style="border-bottom:0;"><?php echo $current_ticket->user_name; ?></td>
					<th scope="row" style="background: #464646; color: #FEFEFE; border: 1px solid #242424;"><?php _e("Staff Representative:", INCSUB_SUPPORT_LANG_DOMAIN); ?></th>
					<td style="border-bottom:0;"><?php echo $current_ticket->admin_name; ?></td>
				</tr>
				<tr class="form-field form-required">
					<th scope="row" style="background: #464646; color: #FEFEFE; border: 1px solid #242424;"><?php _e("Last Reply From:", INCSUB_SUPPORT_LANG_DOMAIN); ?></th>
					<td style="border-bottom:0;"><?php echo $current_ticket->last_user_reply; ?></td>
					<th scope="row" style="background: #464646; color: #FEFEFE; border: 1px solid #242424;"><?php _e("Current Status:", INCSUB_SUPPORT_LANG_DOMAIN); ?></th>
					<td style="border-bottom:0;">
						<?php echo $ticket_status[$current_ticket->ticket_status]; ?>
						<input type="hidden" name="status" value="<?php echo $current_ticket->ticket_status; ?>" />
					</td>
				</tr>
				<tr class="form-field form-required">
					<th scope="row" style="background: #464646; color: #FEFEFE; border: 1px solid #242424;"><?php _e("Last Updated:", INCSUB_SUPPORT_LANG_DOMAIN); ?></th>
					<td style="border-bottom:0;"><?php echo date(get_option("date_format") ." ". get_option("time_format") ." T  (\G\M\T P)", strtotime($current_ticket->ticket_updated)); ?></td>
					<th scope="row" style="background: #464646; color: #FEFEFE; border: 1px solid #242424;"><?php _e("Created On:", INCSUB_SUPPORT_LANG_DOMAIN); ?></th>
					<td style="border-bottom:0;"><?php echo date(get_option("date_format") ." ". get_option("time_format") ." T  (\G\M\T P)", strtotime($current_ticket->ticket_opened)); ?></td>
				</tr>
			</table>
			<br /><br />
			<h2><?php _e("Ticket History", INCSUB_SUPPORT_LANG_DOMAIN); ?></h2><br />
			<table class="widefat" cellpadding="3" cellspacing="3" border="1">
				<thead>
					<tr>
						<th scope="col"><?php _e("Author", INCSUB_SUPPORT_LANG_DOMAIN); ?></th>
						<th scope="col"><?php _e("Ticket Message/Reply", INCSUB_SUPPORT_LANG_DOMAIN); ?></th>
						<th scope="col"><?php _e("Date/Time", INCSUB_SUPPORT_LANG_DOMAIN); ?></th>
					</tr>
				</thead>
				<tbody>
<?php
			if ( !empty($message_list) ) {
				foreach ( $message_list as $message ) {
					if ( !empty($message->reporting_name) ) {
						$avatar_id = $message->user_avatar_id;
						$avatar = '<img src="'. WP_PLUGIN_URL . '/incsub-support/images/user.gif" alt="User" />';
						$display_name = $message->reporting_name ."<br /><br />";
						$mclass = ' class="alternate"';
					} elseif ( !empty($message->staff_member) ) {
						$avatar_id = $message->admin_avatar_id;
						$avatar = '<img src="'. WP_PLUGIN_URL . '/incsub-support/images/staff.gif" alt="User" />';
						$display_name = $message->staff_member ."<br /><br />";
						$mclass = ' style="background-color: #cccccc;"';
					} else {
						$avatar_id = "";
						$display_name = __("User", INCSUB_SUPPORT_LANG_DOMAIN);
						$mclass = '';
					}
					if ( function_exists("get_blog_avatar") ) {
						// check for blog avatar function, as get_avatar is too common.
						$avatar = get_avatar($avatar_id,'32','gravatar_default');
					}
//					$mclass = ($mclass == "alternate") ? "" : "alternate";
?>
					<tr<?php echo $mclass; ?>>
						<th scope="row" style="text-align: center;"><?php echo $display_name . $avatar; ?></th>
						<td style="padding: 0 5px 5px 5px;">
							<h3 style="margin-top: .5em;"><?php echo $message->subject; ?></h3>
							<div style="padding: 0 20px;">
								<?php echo wpautop(html_entity_decode($message->message)); ?>
							</div>
						</td>
						<td><?php echo date(get_option("date_format") ." ". get_option("time_format") ." T  (\G\M\T P)", strtotime($message->message_date)); ?></td>
					</tr>
<?php
				}
?>

<?php
			}
?>
				</tbody>
			</table>
			<br /><br />
<?php
			if ( $current_ticket->ticket_status != 5 ) {
			// ticket isn't closed
?>
			<h2><?php _e("Update This Ticket", INCSUB_SUPPORT_LANG_DOMAIN); ?></h2>
			<p><em><?php _e("* All fields are required.", INCSUB_SUPPORT_LANG_DOMAIN); ?></em></p>
			<table class="form-table">
				<tr class="form-field form-required">
					<th scope="row"><label for="subject"><?php _e("Subject", INCSUB_SUPPORT_LANG_DOMAIN); ?></label></th>
					<td><input type="text" name="subject" id="subject" maxlength="100" size="60" value="Re: <?php echo $current_ticket->title; ?>" />&nbsp;<small>(<?php _e("max: 100 characters"); ?>)</small></td>
				</tr>
				<tr class="form-field form-required">
					<th scope="row"><?php _e('Category', INCSUB_SUPPORT_LANG_DOMAIN); ?>:</th>
					<td>
						<select name="category" id="category">
<?php
				$get_cats = $wpdb->get_results("SELECT cat_id, cat_name FROM ".incsub_support_tablename('tickets_cats')." WHERE site_id = '{$current_site->id}' ORDER BY cat_name ASC");
				if ( empty($get_cats) ) {
					$wpdb->query("INSERT INTO ".incsub_support_tablename('tickets_cats')." (site_id, cat_name) VALUES ('{$current_site->id}', 'General')");
					$get_cats = $wpdb->get_results("SELECT cat_id, cat_name FROM ".incsub_support_tablename('tickets_cats')." WHERE site_id = '{$current_site->id}' ORDER BY cat_name ASC");
				}
				$x = 0;
				foreach ($get_cats as $cat) {
					if ( $cat->cat_id == $current_ticket->cat_id ) {
						$selected = ' selected="selected"';
						$x++;
					} else {
						$selected = "";
					}
?>
							<option<?php echo $selected; ?> value="<?php echo $cat->cat_id; ?>"><?php echo $cat->cat_name; ?></option>
<?php
				}
?>
						</select>
					</td>
				</tr>
				<tr class="form-field form-required">
					<th scope="row"><?php _e('Priority', INCSUB_SUPPORT_LANG_DOMAIN); ?>:</th>
					<td>
						<select name="priority" id="priority">
<?php
				foreach ($ticket_priority as $key => $val) {
					if ( $key == $current_ticket->ticket_priority ) {
						$selected = ' selected="selected"';
					} else {
						$selected = "";
					}
?>
							<option<?php echo $selected; ?> value="<?php echo $key; ?>"><?php echo $val; ?></option>
<?php
				}
?>
						</select>
					</td>
				</tr>
				<tr class="form-field form-required">
					<th scope="row"><label for="message"><?php _e("Add A Reply", INCSUB_SUPPORT_LANG_DOMAIN); ?></label></th>
					<td>&nbsp;<small>(<?php _e("Please provide as much information as possible, so that we may better help you.", INCSUB_SUPPORT_LANG_DOMAIN); ?>)</small><br /><textarea name="message" id="message" class="message" rows="12" cols="58"></textarea></td>
				</tr>
				<tr class="form-field form-required">
					<th scope="row"><label for="closeticket"><?php _e("Close Ticket?", INCSUB_SUPPORT_LANG_DOMAIN); ?></label></th>
					<td><input type="checkbox" name="closeticket" id="closeticket" value="1" /> <strong><?php _e("Yes, please close this ticket.", INCSUB_SUPPORT_LANG_DOMAIN); ?></strong><br /><small><em><?php _e("Once a ticket is closed, you can no longer reply to (or update) it.", INCSUB_SUPPORT_LANG_DOMAIN); ?></em></small></td>
				</tr>
			</table>
			<p class="submit">
				<input type="hidden" name="modifyticket" value="1" />
				<input name="updateticket" type="submit" id="updateticket" value="<?php _e("Update Ticket", INCSUB_SUPPORT_LANG_DOMAIN); ?>" />&nbsp;&nbsp;&nbsp;&nbsp;<input name="canelsubmit" type="submit" id="cancelsubmit" value="<?php _e("Cancel"); ?>" />
			</p>
		</form>
<?php
			} // end if ticket !closed check.
		} // end else check for current ticket
	} // end check for GET tid

	if ( empty($current_ticket) and empty($ticket_error) ) {
?>
	<h2><?php _e("Recent Support Tickets", INCSUB_SUPPORT_LANG_DOMAIN); ?> <small style="font-size: 12px; padding-left: 10px;">(<a href="admin.php?page=incsub_support_tickets&amp;action=history"><?php _e("Ticket History", INCSUB_SUPPORT_LANG_DOMAIN); ?></a>)</small></h2>
	<br />
		<table width="100%" cellpadding="3" cellspacing="3" class="widefat">
			<thead>
				<tr>
					<th scope="col"><?php _e("Ticket ID", INCSUB_SUPPORT_LANG_DOMAIN); ?></th>
					<th scope="col"><?php _e("Subject", INCSUB_SUPPORT_LANG_DOMAIN); ?></th>
					<th scope="col"><?php _e("Status", INCSUB_SUPPORT_LANG_DOMAIN); ?></th>
					<th scope="col"><?php _e("Priority", INCSUB_SUPPORT_LANG_DOMAIN); ?></th>
					<th scope="col"><?php _e("Staff Member", INCSUB_SUPPORT_LANG_DOMAIN); ?></th>
					<th scope="col"><?php _e("Last Updated", INCSUB_SUPPORT_LANG_DOMAIN); ?></th>
				</tr>
			</thead>
			<tbody id="the-list">
<?php

		if ( empty($tickets) ) {
?>
				<tr class='alternate'>
					<th scope="row" colspan="6">
						<p><?php _e("There aren't any tickets to view at this time.", INCSUB_SUPPORT_LANG_DOMAIN); ?></p>
					</th>
				</tr>
<?php
		} else {
			foreach ($tickets as $ticket) {
			$class = ( $class != "alternate") ? "alternate" : "";
			if ( empty($ticket->display_name) ) { $ticket->display_name = __("Unassigned", INCSUB_SUPPORT_LANG_DOMAIN); }
?>
				<tr class='<?php echo $class; ?>'>
					<th scope="row"><?php echo $ticket->ticket_id; ?></th>
					<td valign="top"><a href="admin.php?page=incsub_support_tickets&amp;tid=<?php echo $ticket->ticket_id; ?>"><?php echo $ticket->title; ?></a></td>
					<td valign="top"><?php echo $ticket_status[$ticket->ticket_status]; ?></td>
					<td valign="top"><?php echo $ticket_priority[$ticket->ticket_priority]; ?></td>
					<td valign="top"><?php echo $ticket->display_name; ?></td>
					<td valign="top"><?php echo date(get_option("date_format") ." ". get_option("time_format") ." T  (\G\M\T P)", strtotime($ticket->ticket_updated)); ?></td>
				</tr>
<?php
			}
		}
?>
			</tbody>
		</table>
	<br /><br />

	<h2><?php _e("Add Ticket", INCSUB_SUPPORT_LANG_DOMAIN); ?></h2>
		<p><em><?php _e("* All fields are required.", INCSUB_SUPPORT_LANG_DOMAIN); ?></em></p>
		<form action="admin.php?page=incsub_support_tickets" method="post" name="newticket" id="newticket">
			<table class="form-table">
				<tr class="form-field form-required">
					<th scope="row"><label for="subject"><?php _e("Subject", INCSUB_SUPPORT_LANG_DOMAIN); ?></label></th>
					<td><input type="text" name="subject" id="subject" maxlength="100" size="60" />&nbsp;<small>(<?php _e("max: 100 characters"); ?>)</small></td>
				</tr>
				<tr class="form-field form-required">
					<th scope="row"><?php _e('Category', INCSUB_SUPPORT_LANG_DOMAIN); ?>:</th>
					<td>
						<select name="category" id="category">
<?php
		$get_cats = $wpdb->get_results("SELECT cat_id, cat_name FROM ".incsub_support_tablename('tickets_cats')." WHERE site_id = '{$current_site->id}' ORDER BY cat_name ASC");
		if ( empty($get_cats) ) {
			$wpdb->query("INSERT INTO ".incsub_support_tablename('tickets_cats')." (site_id, cat_name) VALUES ('{$current_site->id}', 'General')");
			$get_cats = $wpdb->get_results("SELECT cat_id, cat_name FROM ".incsub_support_tablename('tickets_cats')." WHERE site_id = '{$current_site->id}' ORDER BY cat_name ASC");
		}
		$x = 0;
		foreach ($get_cats as $cat) {
			if ( $x == 0 ) {
				$selected = ' selected="selected"';
				$x++;
			} else {
				$selected = "";
			}
?>
							<option<?php echo $selected; ?> value="<?php echo $cat->cat_id; ?>"><?php echo $cat->cat_name; ?></option>
<?php
		}
?>
						</select>
					</td>
				</tr>
				<tr class="form-field form-required">
					<th scope="row"><?php _e('Priority', INCSUB_SUPPORT_LANG_DOMAIN); ?>:</th>
					<td>
						<select name="priority" id="priority">
<?php
		$x = 0;
		foreach ($ticket_priority as $key => $val) {
			if ( $x == 0 ) {
				$selected = ' selected="selected"';
				$x++;
			} else {
				$selected = "";
			}
?>
							<option<?php echo $selected; ?> value="<?php echo $key; ?>"><?php echo $val; ?></option>
<?php
		}
?>
						</select>
					</td>
				</tr>
				<tr class="form-field form-required">
					<th scope="row"><?php _e("Status", INCSUB_SUPPORT_LANG_DOMAIN); ?></th>
					<td><em><?php _e("New Ticket", INCSUB_SUPPORT_LANG_DOMAIN); ?></em></td>
				</tr>
				<tr class="form-field form-required">
					<th scope="row"><label for="message"><?php _e("Problem Description", INCSUB_SUPPORT_LANG_DOMAIN); ?></label></th>
					<td>&nbsp;<small>(<?php _e("Please provide as much information as possible, so that we may better help you.", INCSUB_SUPPORT_LANG_DOMAIN); ?>)</small><br /><textarea name="message" id="message" class="message" rows="12" cols="58"></textarea></td>
				</tr>
			</table>
			<p class="submit">
				<input type="hidden" name="addticket" value="1" />
				<input name="submitticket" type="submit" id="addusersub" value="<?php _e("Submit New Ticket", INCSUB_SUPPORT_LANG_DOMAIN); ?>" />
			</p>
		</form>

<?php
	} // end empty current ticket check
?>
</div>
<?php
}

function incsub_support_output_faq() {
	global $current_site, $wpdb;
	
	if ( !empty($_GET['action']) and $_GET['action'] == 'vote' ) {
		if ( ($_GET['help'] == "yes" or $_GET['help'] == "no") and is_numeric($_GET['qid']) ) {
			$get_help = ($_GET['help'] == "no") ? "help_no = help_no+1" : "help_yes = help_yes+1";
			$wpdb->query("UPDATE ".incsub_support_tablename('faq')." SET {$get_help}, help_count = help_count+1 WHERE faq_id = '". $_GET['qid'] ."' AND site_id = '{$current_site->id}'");
		}
	}
	
	$faqs = $wpdb->get_results("SELECT 
		q.faq_id, q.question, q.answer, q.help_count, q.help_yes, q.help_no, c.cat_name, c.cat_id, c.qcount
		FROM ".incsub_support_tablename('faq')." AS q
		LEFT JOIN ".incsub_support_tablename('faq_cats')." AS c ON ( q.cat_id = c.cat_id )
		WHERE q.site_id = '{$current_site->id}'
		ORDER BY c.cat_name ASC");
?>
<div class="wrap">
	<script type="text/javascript" language="JavaScript"><!--
		function FAQReverseDisplay(d) {
			jQuery('#'+d).toggleClass('invisible');
		}
		//-->
	</script>
	<h2><?php _e("Frequently Asked Questions"); ?></h2>
	<ul>
	<?php
	$current_cat = '';
	foreach ($faqs as $faq) {
		if ( $current_cat != $faq->cat_name ) {
			if ( !empty($current_cat) ) {
			?>
				</ul>
			</li>
			<?php
			}
			$available_text = sprintf( __ngettext( '%s question', '%s questions', $faq->qcount , INCSUB_SUPPORT_LANG_DOMAIN), number_format_i18n( $faq->qcount ) );
			$sentence = sprintf( __( '%1$s available' , INCSUB_SUPPORT_LANG_DOMAIN), $available_text );
			?>
			<li><a href="javascript:FAQReverseDisplay('category-<?php echo $faq->cat_id; ?>')"><?php echo $faq->cat_name; ?> <?php echo "<small>({$sentence})</small>"; ?></a>
			<ul id="category-<?php echo $faq->cat_id; ?>" class="invisible category">
		<?php
			$current_cat = $faq->cat_name;
		}
		?>
			<li>
				<a href="javascript:FAQReverseDisplay('answer-<?php echo $faq->faq_id; ?>')"><?php echo $faq->question; ?></a><br />
				<div id="answer-<?php echo $faq->faq_id; ?>" style="padding: 15px; border: 1px solid #464646; width: 60%;" class="invisible answer">
					<?php
					if ( !empty($faq->help_count) and $faq->help_yes > 0 ) {
						$sentence = sprintf( __( '%1$s of %2$s users found this to be helpful.' , INCSUB_SUPPORT_LANG_DOMAIN), $faq->help_yes, $faq->help_count );
					} else {
						$sentence = "";
					}
					?>
					<?php echo html_entity_decode($faq->answer); ?>
					<p style="padding: 10px; text-align: right;" class="vote_response" id="vote-response-<?php echo $faq->faq_id; ?>" >
						<?php _e("Was this solution helpful? ", INCSUB_SUPPORT_LANG_DOMAIN); ?>
						<a class="vote" href="admin.php?page=incsub_support_faq&amp;action=vote&amp;help=yes&amp;qid=<?php echo $faq->faq_id; ?>"><?php _e("Yes", INCSUB_SUPPORT_LANG_DOMAIN); ?></a> | <a class="vote" href="admin.php?page=incsub_support_faq&amp;action=vote&amp;help=no&amp;qid=<?php echo $faq->faq_id; ?>"><?php _e("No", INCSUB_SUPPORT_LANG_DOMAIN); ?></a><br />
						<?php echo "<small><em>{$sentence}</em></small>"; ?>
					</p>
				</div>
			</li>
	<?php
	}
	?>
			</ul>
		</li>
	</ul>
</div>
<?php
}


function incsub_support_ticketadmin() {
?>
	<div class="wrap">
<?php
	switch($_GET['action']) {
		case "categories":
			incsub_support_ticketadmin_categories();
		break;
		default :
			incsub_support_ticketadmin_main();
		break;
	}

?>
	</div>
<?php
}

function incsub_support_ticketadmin_main() {
	global $wpdb, $current_site, $current_user, $ticket_status, $ticket_priority;
	if ( !empty($_POST['modifyticket']) and $_POST['modifyticket'] == 1 ) {
		if ( !empty($_POST['canelsubmit']) ) {
			wp_redirect("ms-admin.php?page=ticket-manager");
			exit();
		}
		if ( empty($_POST['subject']) or !is_numeric($_POST['category']) or !is_numeric($_POST['priority']) or !is_numeric($_POST['status']) or !is_numeric($_POST['ticket_id']) or empty($_POST['message']) ) {
			$notification = __("Ticket Error: All fields are required.", INCSUB_SUPPORT_LANG_DOMAIN);
			$nclass = "error";
		} else {
			$title = strip_tags($_POST['subject']);
			$message = strip_tags($_POST['message']);
			$category = $_POST['category'];
			$priority = $_POST['priority'];
			$ticket_id = $_POST['ticket_id'];
			$status = ($_POST['closeticket'] == 1) ? 5 : 2;
			$responsibility_options = array( "keep" => "", "punt" => ", admin_id = '0'", "accept" => ", admin_id = '{$current_user->id}'", "help" => "");
			$email_message = false;
			// get the user to reply to, before inserting a new message.
			$reply_to_id = $wpdb->get_var("SELECT user_id FROM ".incsub_support_tablename('tickets_messages')." WHERE ticket_id = '{$ticket_id}' AND admin_id = 0 ORDER BY message_date DESC LIMIT 1");
			if ( array_key_exists($_POST['responsibility'], $responsibility_options) ) {
				$adding_update_key = $responsibility_options[$_POST['responsibility']];
			} else {
				// screwing around? we'll just see about that.
				$adding_update_key = $responsibility_options['accept'];
			}

			$wpdb->query("INSERT INTO ".incsub_support_tablename('tickets_messages')."
				(site_id, ticket_id, admin_id, subject, message)
				VALUES ('{$current_site->id}', '{$ticket_id}', '{$current_user->id}', '{$title}', '{$message}')
			");

			if ( !empty($wpdb->insert_id) ) {
				$wpdb->query("UPDATE ".incsub_support_tablename('tickets')."
					SET
						cat_id = '{$category}', last_reply_id = '{$current_user->id}', ticket_priority = '{$priority}', ticket_status = '{$status}', num_replies = num_replies+1{$adding_update_key}
					WHERE site_id = '{$current_site->id}' AND ticket_id = '{$ticket_id}'
					LIMIT 1
				");

				if ( !empty($wpdb->rows_affected) ) {
					$ticket_blog_id = $wpdb->get_var("SELECT blog_id FROM ".incsub_support_tablename('tickets')." WHERE ticket_id = '{$ticket_id}' LIMIT 1");
			
					$target_blog = get_blog_details($ticket_blog_id);
					$notification = __("Ticket has been updated successfully, and the user notified of your response. You will be notified by email of any responses to this ticket.", INCSUB_SUPPORT_LANG_DOMAIN);
					$nclass = "updated fade";
					$title = stripslashes($title);
					$email_message = array(
						"to"		=> incsub_support_notification_user_email($reply_to_id),
						"subject"	=> __("[#{$ticket_id}] ", INCSUB_SUPPORT_LANG_DOMAIN) . $title,
						"message"	=> _("

	***  DO NOT REPLY TO THIS EMAIL  ***

	Subject: ". $title ."
	Status: ". $ticket_status[$status] ."
	Priority: ". $ticket_priority[$priority] ."

	Please log into your site and visit the support page to reply to this ticket, if needed.
	
	Visit:

		http://". $target_blog->domain . $target_blog->path ."wp-admin/admin.php?page=incsub_support_tickets&tid={$ticket_id}

	to reply to this ticket, if needed.

	------------------------------
	     Begin Ticket Message
	------------------------------

	". $message ."

	------------------------------
	      End Ticket Message
	------------------------------

	Thanks,
	". $wpdb->get_var("SELECT user_nicename FROM {$wpdb->users} WHERE ID = '{$current_user->id}'") .",
	". get_site_option("site_name") ."\r\n\r\n"), // ends lang string

						"headers"	=> "MIME-Version: 1.0\n" . "From: \"". get_site_option('incsub_support_from_name', get_bloginfo('blogname')) ."\" <". get_site_option('incsub_support_from_mail', get_bloginfo('admin_email')) .">\n" . "Content-Type: text/plain; charset=\"" . get_option('blog_charset') . "\"\n",
					); // ends array.
				} else {
					$notification = __("Ticket Error: There was an error updating your ticket. Please try again in a few minutes.", INCSUB_SUPPORT_LANG_DOMAIN);
					$nclass = "error";
				}
			} else {
				$notification = __("Ticket Error: There was an error adding your reply. Please try again in a few minutes.", INCSUB_SUPPORT_LANG_DOMAIN);
				$nclass = "error";
			}
		}
	}
	if ( !empty($notification) ) {
		if ( !empty($email_message) and is_array($email_message) ) {
			wp_mail($email_message["to"], $email_message["subject"], $email_message["message"], $email_message["headers"]);
		}
?>
	<div class="<?php echo $nclass; ?>"><?php echo $notification; ?></div>
<?php
	}

	$do_history = ( !empty($_GET['action']) and $_GET['action'] == 'history' ) ? "AND t.ticket_status = '5'" : "AND t.ticket_status != '5'";
	$tickets = $wpdb->get_results("
		SELECT t.ticket_id, t.user_id, t.cat_id, t.admin_id, t.ticket_type, t.ticket_priority, t.ticket_status, t.ticket_updated, t.title, c.cat_name, u.display_name
		FROM ".incsub_support_tablename('tickets')." AS t
		LEFT JOIN ".incsub_support_tablename('tickets_cats')." AS c ON (t.cat_id = c.cat_id)
		LEFT JOIN $wpdb->users AS u ON (t.admin_id = u.ID)
		WHERE t.site_id = '{$current_site->id}' {$do_history}
	");
?>
	<h2><?php _e("Support Ticket Management", INCSUB_SUPPORT_LANG_DOMAIN); ?></h2>
	<div class="handlediv">
		<h3 class='hndle'>
<?php
	if ( !empty($_GET['tid']) or !empty($_GET['action']) ) {
		if ( !empty($_GET['action']) ) {
?>
			<span><?php _e("Archived Tickets", INCSUB_SUPPORT_LANG_DOMAIN); ?></span>
<?php
		} else {
?>
			<span><?php _e("Managing Ticket", INCSUB_SUPPORT_LANG_DOMAIN); ?></span>
<?php
		}
?>
 			<a href="ms-admin.php?page=ticket-manager&amp;action=categories#addcat" class="rbutton"><strong><?php _e("Add New Category", INCSUB_SUPPORT_LANG_DOMAIN); ?></strong></a>
			<a href="ms-admin.php?page=ticket-manager" class="rbutton"><strong><?php _e('Ticket Main', INCSUB_SUPPORT_LANG_DOMAIN); ?></strong></a>
<?php
	} else {
?>
			<span><?php _e("Active Tickets", INCSUB_SUPPORT_LANG_DOMAIN); ?></span>
 			<a href="ms-admin.php?page=ticket-manager&amp;action=categories#addcat" class="rbutton"><strong><?php _e("Add New Category", INCSUB_SUPPORT_LANG_DOMAIN); ?></strong></a>
			<a href="ms-admin.php?page=ticket-manager&amp;action=history" class="rbutton"><strong><?php _e('Archived Tickets', INCSUB_SUPPORT_LANG_DOMAIN); ?></strong></a>
<?php
	}
?>
			<br class="clear" />
		</h3>
		<div class="youhave">
<?php
	if ( !empty($_GET['tid']) and is_numeric($_GET['tid']) ) {
		$current_ticket = $wpdb->get_results("
		SELECT
			t.ticket_id, t.cat_id, t.user_id, t.admin_id, t.ticket_type, t.ticket_priority, t.ticket_status, t.ticket_opened, t.ticket_updated, t.title,
			c.cat_name, u.display_name AS user_name, a.display_name AS admin_name, l.display_name AS last_user_reply, m.user_id AS user_avatar_id, 
			m.admin_id AS admin_avatar_id, m.message_date, m.subject, m.message, r.display_name AS reporting_name, s.display_name AS staff_member
		FROM ".incsub_support_tablename('tickets')."_messages AS m
		LEFT JOIN ".incsub_support_tablename('tickets')." AS t ON (m.ticket_id = t.ticket_id)
		LEFT JOIN $wpdb->users AS u ON (t.user_id = u.ID)
		LEFT JOIN $wpdb->users AS a ON (t.admin_id = a.ID)
		LEFT JOIN $wpdb->users AS l ON (t.last_reply_id = l.ID)
		LEFT JOIN $wpdb->users AS r ON (m.user_id = r.ID)
		LEFT JOIN $wpdb->users AS s ON (m.admin_id = s.ID)
		LEFT JOIN ".incsub_support_tablename('tickets_cats')." AS c ON (t.cat_id = c.cat_id)
		WHERE (m.ticket_id = '". $_GET['tid'] ."' AND t.site_id = '{$current_site->id}')
		ORDER BY m.message_id ASC
	");


		if ( empty($current_ticket) ) {
			$ticket_error = 1;
?>
	<h2 class="error"><?php _e("Error: Invalid Ticket Selected", INCSUB_SUPPORT_LANG_DOMAIN); ?></h2>
<?
		} else {
		$message_list = $current_ticket;
		$current_ticket = $current_ticket[0];
		$current_ticket->admin_name = !empty($current_ticket->admin_name) ? $current_ticket->admin_name : __("Not yet assigned");
?>
	<h2><?php _e("Ticket Details", INCSUB_SUPPORT_LANG_DOMAIN); ?></h2>
		<form action="ms-admin.php?page=ticket-manager" method="post" name="updateticket" id="updateticket">
			<table class="form-table" border="1">
				<tr class="form-field form-required">
					<th scope="row" style="background: #464646; color: #FEFEFE; border: 1px solid #242424;"><?php _e("Ticket Subject:", INCSUB_SUPPORT_LANG_DOMAIN); ?></th>
					<td style="border-bottom:0;">
						<?php echo $current_ticket->title; ?>
						<input type="hidden" name="tickettitle" value="<?php echo "Re: ".$current_ticket->title; ?>" />
						<input type="hidden" name="ticket_id" value="<?php echo $current_ticket->ticket_id; ?>" />
					</td>
					<th scope="row" style="background: #464646; color: #FEFEFE; border: 1px solid #242424;"><?php _e("Current Date/Time:", INCSUB_SUPPORT_LANG_DOMAIN); ?></th>
					<td style="border-bottom:0;"><?php echo date(get_option("date_format") ." ". get_option("time_format") ." T  (\G\M\T P)", time()); ?></td>
				</tr>
				<tr class="form-field form-required">
					<th scope="row" style="background: #464646; color: #FEFEFE; border: 1px solid #242424;"><?php _e("Reporting User:", INCSUB_SUPPORT_LANG_DOMAIN); ?></th>
					<td style="border-bottom:0;"><?php echo $current_ticket->user_name; ?></td>
					<th scope="row" style="background: #464646; color: #FEFEFE; border: 1px solid #242424;"><?php _e("Staff Representative:", INCSUB_SUPPORT_LANG_DOMAIN); ?></th>
					<td style="border-bottom:0;"><?php echo $current_ticket->admin_name; ?></td>
				</tr>
				<tr class="form-field form-required">
					<th scope="row" style="background: #464646; color: #FEFEFE; border: 1px solid #242424;"><?php _e("Last Reply From:", INCSUB_SUPPORT_LANG_DOMAIN); ?></th>
					<td style="border-bottom:0;"><?php echo $current_ticket->last_user_reply; ?></td>
					<th scope="row" style="background: #464646; color: #FEFEFE; border: 1px solid #242424;"><?php _e("Current Status:", INCSUB_SUPPORT_LANG_DOMAIN); ?></th>
					<td style="border-bottom:0;">
						<?php echo $ticket_status[$current_ticket->ticket_status]; ?>
						<input type="hidden" name="status" value="<?php echo $current_ticket->ticket_status; ?>" />
					</td>
				</tr>
				<tr class="form-field form-required">
					<th scope="row" style="background: #464646; color: #FEFEFE; border: 1px solid #242424;"><?php _e("Last Updated:", INCSUB_SUPPORT_LANG_DOMAIN); ?></th>
					<td style="border-bottom:0;"><?php echo date(get_option("date_format") ." ". get_option("time_format") ." T  (\G\M\T P)", strtotime($current_ticket->ticket_updated)); ?></td>
					<th scope="row" style="background: #464646; color: #FEFEFE; border: 1px solid #242424;"><?php _e("Created On:"); ?></th>
					<td style="border-bottom:0;"><?php echo date(get_option("date_format") ." ". get_option("time_format") ." T  (\G\M\T P)", strtotime($current_ticket->ticket_opened)); ?></td>
				</tr>
			</table>
			<br /><br />
			<h2><?php _e("Ticket History", INCSUB_SUPPORT_LANG_DOMAIN); ?></h2><br />
			<table class="widefat" cellpadding="3" cellspacing="3" border="1">
				<thead>
					<tr>
						<th scope="col"><?php _e("Author", INCSUB_SUPPORT_LANG_DOMAIN); ?></th>
						<th scope="col"><?php _e("Ticket Message/Reply", INCSUB_SUPPORT_LANG_DOMAIN); ?></th>
						<th scope="col"><?php _e("Date/Time", INCSUB_SUPPORT_LANG_DOMAIN); ?></th>
					</tr>
				</thead>
				<tbody>
<?php
			if ( !empty($message_list) ) {
				foreach ( $message_list as $message ) {
					if ( !empty($message->reporting_name) ) {
						$avatar_id = $message->user_avatar_id;
						$avatar = '<img src="'. WP_PLUGIN_URL . '/incsub-support/images/user.gif" alt="User" />';
						$display_name = $message->reporting_name ."<br /><br />";
						$mclass = ' class="alternate"';
					} elseif ( !empty($message->staff_member) ) {
						$avatar_id = $message->admin_avatar_id;
						$avatar = '<img src="'. WP_PLUGIN_URL . '/incsub-support/images/staff.gif" alt="User" />';
						$display_name = $message->staff_member ."<br /><br />";
						$mclass = ' style="background-color: #cccccc;"';
					} else {
						$avatar_id = "";
						$display_name = __("User", INCSUB_SUPPORT_LANG_DOMAIN);
						$mclass = '';
					}
					if ( function_exists("get_blog_avatar") ) {
						// check for blog avatar function, as get_avatar is too common.
						$avatar = get_avatar($avatar_id,'32','gravatar_default');
					}
//					$mclass = ($mclass == "alternate") ? "" : "alternate";
?>
					<tr<?php echo $mclass; ?>>
						<th scope="row" style="text-align: center;"><?php echo $display_name . $avatar; ?></th>
						<td style="padding: 0 5px 5px 5px;">
							<h3 style="margin-top: .5em;"><?php echo $message->subject; ?></h3>
							<div style="padding: 0 20px;">
								<?php echo wpautop(html_entity_decode($message->message)); ?>
							</div>
						</td>
						<td><?php echo date(get_option("date_format") ." ". get_option("time_format") ." T  (\G\M\T P)", strtotime($message->message_date)); ?></td>
					</tr>
<?php
				}
?>

<?php
			}
?>
				</tbody>
			</table>
			<br /><br />
<?php
			if ( $current_ticket->ticket_status != 5 ) {
			// ticket isn't closed
?>
			<h2><?php _e("Update This Ticket", INCSUB_SUPPORT_LANG_DOMAIN); ?></h2>
			<p><em><?php _e("* All fields are required.", INCSUB_SUPPORT_LANG_DOMAIN); ?></em></p>
			<table class="form-table">
				<tr class="form-field form-required">
					<th scope="row"><label for="subject"><?php _e("Subject", INCSUB_SUPPORT_LANG_DOMAIN); ?></label></th>
					<td><input type="text" name="subject" id="subject" maxlength="100" size="60" value="Re: <?php echo $current_ticket->title; ?>" />&nbsp;<small>(<?php _e("max: 100 characters"); ?>)</small></td>
				</tr>
				<tr class="form-field form-required">
					<th scope="row">Category:</th>
					<td>
						<select name="category" id="category">
<?php
				$get_cats = $wpdb->get_results("SELECT cat_id, cat_name FROM ".incsub_support_tablename('tickets_cats')." WHERE site_id = '{$current_site->id}' ORDER BY cat_name ASC");
				if ( empty($get_cats) ) {
					$wpdb->query("INSERT INTO ".incsub_support_tablename('tickets_cats')." (site_id, cat_name) VALUES ('{$current_site->id}', 'General')");
					$get_cats = $wpdb->get_results("SELECT cat_id, cat_name FROM ".incsub_support_tablename('tickets_cats')." WHERE site_id = '{$current_site->id}' ORDER BY cat_name ASC");
				}
				$x = 0;
				foreach ($get_cats as $cat) {
					if ( $cat->cat_id == $current_ticket->cat_id ) {
						$selected = ' selected="selected"';
						$x++;
					} else {
						$selected = "";
					}
?>
							<option<?php echo $selected; ?> value="<?php echo $cat->cat_id; ?>"><?php echo $cat->cat_name; ?></option>
<?php
				}
?>
						</select>
					</td>
				</tr>
				<tr class="form-field form-required">
					<th scope="row"><?php _e('Priority', INCSUB_SUPPORT_LANG_DOMAIN); ?>:</th>
					<td>
						<select name="priority" id="priority">
<?php
				foreach ($ticket_priority as $key => $val) {
					if ( $key == $current_ticket->ticket_priority ) {
						$selected = ' selected="selected"';
					} else {
						$selected = "";
					}
?>
							<option<?php echo $selected; ?> value="<?php echo $key; ?>"><?php echo $val; ?></option>
<?php
				}
?>
						</select>
					</td>
				</tr>
				<tr class="form-field form-required">
					<th scope="row"><label for="responsibility"><?php _e("Ticket Responsibility", INCSUB_SUPPORT_LANG_DOMAIN); ?></label></th>
					<td>
						<select name="responsibility" id="responsibility">
<?php
				if ( $current_ticket->admin_id == $current_user->id ) {
?>
							<option selected="selected" value="keep"><?php _e("Keep Responsibility For This Ticket", INCSUB_SUPPORT_LANG_DOMAIN); ?></option>
							<option value="punt"><?php _e("Give Up Responsibility To Allow Another Admin To Accept", INCSUB_SUPPORT_LANG_DOMAIN); ?></option>
<?php
				} else {
?>
							<option selected="selected" value="accept"><?php _e("Accept Responsibility For This Ticket", INCSUB_SUPPORT_LANG_DOMAIN); ?></option>
<?php
					if ( !empty($current_ticket->admin_id) or $current_ticket->admin_id != 0 ) {
?>
							<option value="help"><?php _e("Keep Current Admin, And Just Help Out With A Reply", INCSUB_SUPPORT_LANG_DOMAIN); ?></option>
<?php
					}
				}
?>
					</td>
				</tr>
				<tr class="form-field form-required">
					<th scope="row"><label for="message"><?php _e("Add A Reply", INCSUB_SUPPORT_LANG_DOMAIN); ?></label></th>
					<td>&nbsp;<small>(<?php _e("Please provide as much information as possible, so that the user can understand the solution/request.", INCSUB_SUPPORT_LANG_DOMAIN); ?>)</small><br /><textarea name="message" id="message" class="message" rows="12" cols="58"></textarea></td>
				</tr>
				<tr class="form-field form-required">
					<th scope="row"><label for="closeticket"><?php _e("Close Ticket?", INCSUB_SUPPORT_LANG_DOMAIN); ?></label></th>
					<td><input type="checkbox" name="closeticket" id="closeticket" value="1" /> <strong><?php _e("Yes, close this ticket.", INCSUB_SUPPORT_LANG_DOMAIN); ?></strong><br /><small><em><?php _e("Once a ticket is closed, users can no longer reply to (or update) it.", INCSUB_SUPPORT_LANG_DOMAIN); ?></em></small></td>
				</tr>
			</table>
			<p class="submit">
				<input type="hidden" name="modifyticket" value="1" />
				<input name="updateticket" type="submit" id="updateticket" value="<?php _e("Update Ticket", INCSUB_SUPPORT_LANG_DOMAIN); ?>" />&nbsp;&nbsp;&nbsp;&nbsp;<input name="canelsubmit" type="submit" id="cancelsubmit" value="<?php _e("Cancel", INCSUB_SUPPORT_LANG_DOMAIN); ?>" />
			</p>
		</form>
<?php
			} // end if ticket !closed check.
		} // end else check for current ticket
	} // end check for GET tid

	if ( empty($current_ticket) and empty($ticket_error) ) {
?>
	<br />
		<table width="100%" cellpadding="3" cellspacing="3" class="widefat">
			<thead>
				<tr>
					<th scope="col"><?php _e("Ticket ID", INCSUB_SUPPORT_LANG_DOMAIN); ?></th>
					<th scope="col"><?php _e("Subject", INCSUB_SUPPORT_LANG_DOMAIN); ?></th>
					<th scope="col"><?php _e("Status", INCSUB_SUPPORT_LANG_DOMAIN); ?></th>
					<th scope="col"><?php _e("Priority", INCSUB_SUPPORT_LANG_DOMAIN); ?></th>
					<th scope="col"><?php _e("Staff Member", INCSUB_SUPPORT_LANG_DOMAIN); ?></th>
					<th scope="col"><?php _e("Last Updated", INCSUB_SUPPORT_LANG_DOMAIN); ?></th>
				</tr>
			</thead>
			<tbody id="the-list">
<?php

		if ( empty($tickets) ) {
?>
				<tr class='alternate'>
					<th scope="row" colspan="6">
						<p><?php _e("There aren't any tickets to view at this time.", INCSUB_SUPPORT_LANG_DOMAIN); ?></p>
					</th>
				</tr>
<?php
		} else {
			foreach ($tickets as $ticket) {
			$class = ( $class != "alternate") ? "alternate" : "";
			if ( empty($ticket->display_name) ) { $ticket->display_name = __("Unassigned", INCSUB_SUPPORT_LANG_DOMAIN); }
?>
				<tr class='<?php echo $class; ?>'>
					<th scope="row"><?php echo $ticket->ticket_id; ?></th>
					<td valign="top"><a href="ms-admin.php?page=ticket-manager&amp;tid=<?php echo $ticket->ticket_id; ?>"><?php echo $ticket->title; ?></a></td>
					<td valign="top"><?php echo $ticket_status[$ticket->ticket_status]; ?></td>
					<td valign="top"><?php echo $ticket_priority[$ticket->ticket_priority]; ?></td>
					<td valign="top"><?php echo $ticket->display_name; ?></td>
					<td valign="top"><?php echo date(get_option("date_format") ." ". get_option("time_format") ." T  (\G\M\T P)", strtotime($ticket->ticket_updated)); ?></td>
				</tr>
<?php
			}
		}
?>
			</tbody>
		</table>
<?php
	}
?>
		</div>
<?php

}

function incsub_support_ticketadmin_categories() {
	global $wpdb, $current_site;
	if ( !empty($_POST['updateq']) ) {
		check_admin_referer("incsub_ticketmanagement_managecats");
		if ( !empty($_POST['deleteme']) ) {
				if ( !is_numeric($_POST['defcat']) ) {
					$defcat = $wpdb->get_var("SELECT cat_id FROM ".incsub_support_tablename('tickets_cats')." WHERE site_id = '{$current_site->id}' AND defcat = '1'");
				} else {
					$defcat = $_POST['defcat'];
				}
				$wh = '';
			foreach($_POST['delete'] as $key => $val) {
				if ( $defcat == $val ) {
					continue;
				}

				if ( is_numeric($val) and is_numeric($key) ) {
					if ( $key == 0 ) {
						$wh .= "WHERE ( (cat_id = '{$val}'";
					} else {
						$wh .= " OR cat_id = '{$val}'";
					}
				}
			}
			if ( !empty($wh) ) {
				// if $wh is empty, there wouldn't be anything to delete.
				$wh .= ") AND site_id = '{$current_site->id}' AND defcat != '1')";
				$wpdb->query("DELETE FROM ".incsub_support_tablename('tickets_cats')." ". $wh);
				$delete_text = sprintf( __ngettext( '%s category was', '%s categories were', $wpdb->rows_affected , INCSUB_SUPPORT_LANG_DOMAIN), number_format_i18n( $wpdb->rows_affected ) );
				$sentence = sprintf( __( '%1$s removed', INCSUB_SUPPORT_LANG_DOMAIN ), $delete_text );
				// set any orphaned questions to the default cat.
				$wpdb->query("UPDATE ".incsub_support_tablename('faq')." SET cat_id = '{$defcat}' ". str_replace(" AND defcat != '1'", "", $wh));
?>
		<div class="updated fade"><p><?php echo $sentence; ?></p></div>
<?php
			} else {
?>
		<div class="error"><p><?php _e("There was not anything to delete.", INCSUB_SUPPORT_LANG_DOMAIN); ?></p></div>
<?php
			}
		} elseif ( !empty($_POST['updateme']) ) {
			$x = 0;
			foreach ( $_POST['cat'] as $key => $val ) {
				if ( is_numeric($key) ) {
					$wpdb->query("UPDATE ".incsub_support_tablename('tickets_cats')." SET cat_name = '". attribute_escape(wp_specialchars(strip_tags($val))) ."' WHERE site_id = '{$current_site->id}' AND cat_id = '{$key}'");
					$x++;
				}
			}
			if ( $x > 0 ) {
				$update_text = sprintf( __ngettext( '%s category was', '%s categories were', $x , INCSUB_SUPPORT_LANG_DOMAIN), number_format_i18n( $x ) );
				$sentence = sprintf( __( '%1$s updated', INCSUB_SUPPORT_LANG_DOMAIN ), $update_text );
?>
		<div class="updated fade"><p><?php echo $sentence; ?></p></div>
<?php
			} else {
?>
		<div class="error"><p><?php _e("There was not anything to update.", INCSUB_SUPPORT_LANG_DOMAIN); ?></p></div>
<?php

			}
		}
	} elseif ( !empty($_POST['addme']) ) {
		check_admin_referer("incsub_faqmanagement_addcat");
		if ( !empty($_POST['cat_name']) ) {
			$cat_name = attribute_escape(wp_specialchars($_POST['cat_name']));
			$wpdb->query("INSERT INTO ".incsub_support_tablename('tickets_cats')." (site_id, cat_name, defcat) VALUES ('{$current_site->id}', '{$cat_name}', '0')");
			if ( !empty($wpdb->insert_id) ) {
?>
		<div class="updated fade"><p><?php _e("New category added successfully.", INCSUB_SUPPORT_LANG_DOMAIN); ?></p></div>
<?php
			}
		}
	}


	$cats = $wpdb->get_results("SELECT cat_id, cat_name, defcat FROM ".incsub_support_tablename('tickets_cats')." WHERE site_id = '{$current_site->id}' ORDER BY defcat DESC, cat_name ASC");
	if ( empty($cats) ) {
		$wpdb->query("INSERT INTO ".incsub_support_tablename('tickets_cats')." (site_id, cat_name, defcat) VALUES ('{$current_site->id}', 'General', '1')");
		$cats = $wpdb->get_results("SELECT cat_id, cat_name, defcat FROM ".incsub_support_tablename('tickets_cats')." WHERE site_id = '{$current_site->id}' ORDER BY defcat DESC, cat_name ASC");
	}
?>
	<h2><?php _e("Support Ticket Management", INCSUB_SUPPORT_LANG_DOMAIN); ?></h2>
	<div class="handlediv">
		<h3 class='hndle'>
			<span><?php _e("Ticket Categories", INCSUB_SUPPORT_LANG_DOMAIN); ?></span>
 			<a href="#addcat" class="rbutton"><strong><?php _e("Add New Category", INCSUB_SUPPORT_LANG_DOMAIN); ?></strong></a>
			<a href="ms-admin.php?page=ticket-manager" class="rbutton"><strong><?php _e('Ticket Manager', INCSUB_SUPPORT_LANG_DOMAIN); ?></strong></a>
			<br class="clear" />
		</h3>
		<div class="youhave">
			<form id="managecats" action="ms-admin.php?page=ticket-manager&action=categories" method="post">
<?php wp_nonce_field("incsub_ticketmanagement_managecats"); ?>
				<?php if ( count($cats) > 1 ) { ?><p class="submit" style="border-top: none;"><input type="submit" class="button" name="deleteme" value="<?php _e('Delete', INCSUB_SUPPORT_LANG_DOMAIN); ?>" /></p><?php } ?>
				<table class="widefat">
					<thead>
						<tr>
							<th scope="col" class="check-column"><?php if ( count($cats) > 1 ) { ?><input type="checkbox" /><?php } ?></th>
				    	    <th scope="col"><?php _e("Name", INCSUB_SUPPORT_LANG_DOMAIN); ?></th>
						</tr>
					</thead>
					<tbody id="the-list" class="list:cat">
<?php
	foreach ($cats as $cat) {
		if ( $cat->defcat == 1 ) {
			$checkcol = '<input type="hidden" name="defcat" value="'. $cat->cat_id .'" />';
			$textcol = "<h3>". $cat->cat_name . "</h3> <small>( ".__('Default category, cannot be removed.', INCSUB_SUPPORT_LANG_DOMAIN)." )</small>";
		} else {
			$checkcol = '<input type="checkbox" name="delete[]" value="'. $cat->cat_id .'" />';
			$textcol = '<input type="text" size="40" name="cat['. $cat->cat_id .']" value="'. $cat->cat_name .'" />';
		}
		if ( $class == ' class="alternate"' ) {
			$class = "";
		} else {
			$class = ' class="alternate"';
		}
?>
						<tr id="cat-<?php echo $cat->cat_id; ?>" class="<?php echo $class; ?>">
							<th scope="row" class="check-column"><?php echo $checkcol; ?></th>
							<td><?php echo $textcol; ?></td>
						</tr>
<?php
	}
?>
					</tbody>
				</table>

				<p class="submit" style="padding: 10px;">
					<input type="hidden" name="updateq" value="1" />
					<?php if ( count($cats) > 1 ) { ?><input type="submit" class="button" name="updateme" value="<?php _e('Update Categories', INCSUB_SUPPORT_LANG_DOMAIN); ?>" />&nbsp;&nbsp;&nbsp;<input type="submit" class="button" name="deleteme" value="<?php _e('Delete', INCSUB_SUPPORT_LANG_DOMAIN); ?>" /><?php } ?>
				</p>
			</form>
		</div>
	</div>
	<br />
	<h2><?php _e('Add New Category', INCSUB_SUPPORT_LANG_DOMAIN); ?></h2>
	<form name="addcat" id="addcat" method="post" action="ms-admin.php?page=ticket-manager&amp;action=categories">
	<?php wp_nonce_field("incsub_faqmanagement_addcat"); ?>
	<table class="form-table">
		<tr class="form-field form-required">
			<th scope="row" valign="top"><label for="cat_name"><?php _e('Category Name', INCSUB_SUPPORT_LANG_DOMAIN); ?></label></th>
			<td>
				<input name="cat_name" id="cat_name" type="text" value="" size="40" aria-required="true" /><br />
	            <?php _e('The name is used to identify the category to which tickets relate.', INCSUB_SUPPORT_LANG_DOMAIN); ?>
			</td>
		</tr>
	</table>
	<p class="submit"><input type="submit" class="button" name="addme" value="<?php _e('Add Category', INCSUB_SUPPORT_LANG_DOMAIN); ?>" /></p>
	</form>
<?php
}

function incsub_support_notification_user_email($user_id) {
	global $wpdb;
	return $wpdb->get_var("SELECT user_email FROM {$wpdb->users} WHERE ID = '{$user_id}'");
}

/**
 * Fetch mails via IMAP
 *
 * @todo fetch mails via IMAP and add responses
 */
function incsub_support_fetch_imap() {
	$imap_settings = get_site_option('incsub_support_imap',
		array(
		      'host' => 'imap.gmail.com',
		      'port' => '993',
		      'ssl' => '/ssl',
		      'mailbox' => 'INBOX',
		      'username' => '',
		      'password' => ''
		)
	);
	
	/* connect to IMAP server */
	$hostname = "{{$imap_settings['host']}:{$imap_settings['port']}/imap{$imap_settings['ssl']}}{$imap_settings['mailbox']}";
	$username = $imap_settings['username'];
	$password = $imap_settings['password'];
	
	/* try to connect */
	$inbox = imap_open($hostname,$username,$password) or die('Cannot connect to Gmail: ' . imap_last_error());
	
	/* grab emails */
	$emails = imap_search($inbox,'UNSEEN');
	
	/* if emails are returned, cycle through each... */
	if($emails) {
		foreach($emails as $email_number) {
			$overview = imap_fetch_overview($inbox, $email_number, 0);
			
			$from = preg_replace('/.*<([A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4})>$/i', '$1', trim($overview[0]->from));
			
			$user = get_user_by('email', $from);
			
			if (!$user) {
				continue;
			}
			
			$message = quoted_printable_decode(imap_fetchbody($inbox, $email_number, 1));
			
			$tlines = preg_split("/(\r\n|\n|\r)>/", $message);
			
			$lines = preg_split("/\r\n|\n\r|\n|\r/", trim($tlines[0]));
			
			array_pop($lines);
			
			$_POST['message'] = trim(join("\r\n", $lines));
			$_POST['category'] = 1;
			$_POST['priority'] = 1;
			$_POST['status'] = 3;
			
			if (preg_match('/R.+\[#[0-9]+\]/i', $overview[0]->subject) >= 1) {
				$_POST['modifyticket'] = 1;	
				$_POST['subject'] = preg_replace('/R.+\[#([0-9]+)\] (.*)/i', '$2', $overview[0]->subject);
				$_POST['ticket_id'] = preg_replace('/R.+\[#([0-9]+)\] .*/i', '$1', $overview[0]->subject);
			} else {
				$_POST['addticket'] = 1;
				$_POST['subject'] = $overview[0]->subject;
			}
			
			incsub_support_process_reply($user);
		}
		imap_setflag_full($inbox, join(',', $emails), "\\Seen");
	}
	
	imap_close($inbox);
}

function incsub_support_cron_schedules($schedules) {
	$schedules['everyminute'] = array( 'interval' => 60, 'display' => __('Once a minute', INCSUB_SUPPORT_LANG_DOMAIN) );
	$schedules['fiveminutes'] = array( 'interval' => 300, 'display' => __('Once every five minutes', INCSUB_SUPPORT_LANG_DOMAIN) );
	$schedules['fifteenminutes'] = array( 'interval' => 900, 'display' => __('Once every fifteen minutes', INCSUB_SUPPORT_LANG_DOMAIN) );
	$schedules['thirtyminutes'] = array( 'interval' => 1800, 'display' => __('Once every half an hour', INCSUB_SUPPORT_LANG_DOMAIN) );
	
	return $schedules;
}

incsub_support();


