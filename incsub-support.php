<?php
/*
Plugin Name: Support System
Plugin URI: http://premium.wpmudev.org/project/support-system
Description: Support System for WordPress multi site 
Author: S H Mohanjith (Incsub), Luke Poland (Incsub), Andrew Billits (Incsub), Ignacio (Incsub)
WDP ID: 36
Network: true
Version: 1.8
Author URI: http://premium.wpmudev.org
Text Domain: incsub-support
*/

define( 'INCSUB_SUPPORT_PLUGIN_VERSION', '1.8' );

if ( ! defined( 'INCSUB_SUPPORT_LANG_DOMAIN' ) )
	define('INCSUB_SUPPORT_LANG_DOMAIN', 'incsub-support');

define( 'INCSUB_SUPPORT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'INCSUB_SUPPORT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

define( 'INCSUB_SUPPORT_ASSETS_URL', INCSUB_SUPPORT_PLUGIN_URL . 'assets/' );


if ( ! class_exists( 'MU_Support_System') ) {

	class MU_Support_System {

		// Current version of the plugin
		public static $version = INCSUB_SUPPORT_PLUGIN_VERSION;

		// Sets of valid values like status, or privacy options
		public static $ticket_status;
		public static $ticket_priority;
		public static $responsibilities;
		public static $privacy;
		public static $fetch_imap;
		public static $incsub_support_imap_ssl;

		// Plugin settings
		public static $settings = array();

		// Network menus
		public static $network_main_menu;
		public static $network_single_ticket_menu;
		public static $network_faq_manager_menu;
		public static $network_ticket_categories;
		public static $network_single_faq_question_menu;
		public static $network_faq_categories_menu;
		public static $network_support_settings_menu;

		// Blogs menus
		public static $admin_main_menu;
		public static $admin_new_ticket_menu;
		public static $admin_single_ticket_menu;
		public static $admin_faq_menu;

		/**
		 * Constructor: Initializes the plugin
		 * 
		 * @since 1.8
		 */
		public function __construct() {

			// Setting properties
			self::$ticket_status = array(
				0	=>	__( 'New', INCSUB_SUPPORT_LANG_DOMAIN ),
				1	=>	__( 'In progress', INCSUB_SUPPORT_LANG_DOMAIN ),
				2	=>	__( 'Waiting on User to reply', INCSUB_SUPPORT_LANG_DOMAIN ),
				3	=>	__( 'Waiting on Admin to reply', INCSUB_SUPPORT_LANG_DOMAIN ),
				4	=>	__( 'Stalled', INCSUB_SUPPORT_LANG_DOMAIN ),
				5	=>	__( 'Closed', INCSUB_SUPPORT_LANG_DOMAIN )
			);

			self::$ticket_priority = array(
				0	=>	__( 'Low', INCSUB_SUPPORT_LANG_DOMAIN ),
				1	=>	__( 'Normal', INCSUB_SUPPORT_LANG_DOMAIN ),
				2	=>	__( 'Elevated', INCSUB_SUPPORT_LANG_DOMAIN ),
				3	=>	__( 'High', INCSUB_SUPPORT_LANG_DOMAIN ),
				4	=>	__( 'Critical', INCSUB_SUPPORT_LANG_DOMAIN )
			);

			self::$responsibilities = array( 
				'keep',
				'punt', 
				'accept', 
				'help'
			);

			self::$privacy = array( 
				'all' => __( 'Allow all admins to see all tickets in a site', INCSUB_SUPPORT_LANG_DOMAIN ),
				'requestor' => __( 'Allow only requestors to see their own tickets', INCSUB_SUPPORT_LANG_DOMAIN )
			);

			self::$settings = array(
				'incsub_support_menu_name' => get_site_option( 'incsub_support_menu_name', __( 'Support', INCSUB_SUPPORT_LANG_DOMAIN ) ),
				'incsub_support_from_name' => get_site_option( 'incsub_support_from_name', get_bloginfo( 'blogname' ) ),
				'incsub_support_from_mail' => get_site_option( 'incsub_support_from_mail', get_bloginfo( 'admin_email' ) ),
				'incsub_support_fetch_imap' => get_site_option('incsub_support_fetch_imap', 'disabled'),
				'incsub_support_imap_frequency' => get_site_option('incsub_support_imap_frequency', ''),
				'incsub_allow_only_pro_sites' => get_site_option( 'incsub_allow_only_pro_sites', false ),
				'incsub_pro_sites_level' => get_site_option( 'incsub_pro_sites_level', '' ),
				'incsub_allow_only_pro_sites_faq' => get_site_option( 'incsub_allow_only_pro_sites_faq', false ),
				'incsub_pro_sites_faq_level' => get_site_option( 'incsub_pro_sites_faq_level', '' ),
				'incsub_ticket_privacy' => get_site_option( 'incsub_ticket_privacy', 'all' ),
				'incsub_support_faq_enabled' => get_site_option( 'incsub_support_faq_enabled', true )
			);

			// Include needed files
			$this->includes();

			// Activation/Upgrades
			register_activation_hook( __FILE__, array( &$this, 'activate' ) );
			register_deactivation_hook( __FILE__, array( &$this, 'deactivate' ) );

			// Is this an upgrade?
			add_action( 'admin_init', array( &$this, 'check_for_upgrades' ) );

			add_action( 'init', array( &$this, 'load_text_domain' ) );

			// Create Admin menus
			add_action( 'init', array( &$this, 'admin_menus' ) );

		}

		public function load_text_domain() {
			load_textdomain( INCSUB_SUPPORT_LANG_DOMAIN, WP_LANG_DIR . '/' . INCSUB_SUPPORT_LANG_DOMAIN . '/' . INCSUB_SUPPORT_LANG_DOMAIN . '-' . get_locale() . '.mo' );
        	load_plugin_textdomain( INCSUB_SUPPORT_LANG_DOMAIN, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );	
		}


		/**
		 * Includes needed files
		 *
		 * @since 1.8
		 */
		private function includes() {
			// Model
			require_once( INCSUB_SUPPORT_PLUGIN_DIR . '/model/model.php');

			// Admin
			require_once( INCSUB_SUPPORT_PLUGIN_DIR . '/inc/support-menu.php');
			require_once( INCSUB_SUPPORT_PLUGIN_DIR . '/admin/pages/network-main-menu.php');
			require_once( INCSUB_SUPPORT_PLUGIN_DIR . '/admin/pages/network-ticket-categories-menu.php');
			require_once( INCSUB_SUPPORT_PLUGIN_DIR . '/admin/pages/network-single-ticket-menu.php');
			require_once( INCSUB_SUPPORT_PLUGIN_DIR . '/admin/pages/network-faq-manager-menu.php');
			require_once( INCSUB_SUPPORT_PLUGIN_DIR . '/admin/pages/network-single-faq-question-menu.php');
			require_once( INCSUB_SUPPORT_PLUGIN_DIR . '/admin/pages/network-faq-categories-menu.php');
			require_once( INCSUB_SUPPORT_PLUGIN_DIR . '/admin/pages/network-support-settings-menu.php');

			require_once( INCSUB_SUPPORT_PLUGIN_DIR . '/admin/pages/admin-main-menu.php');
			require_once( INCSUB_SUPPORT_PLUGIN_DIR . '/admin/pages/admin-new-ticket-menu.php');
			require_once( INCSUB_SUPPORT_PLUGIN_DIR . '/admin/pages/admin-single-ticket-menu.php');
			require_once( INCSUB_SUPPORT_PLUGIN_DIR . '/admin/pages/admin-faq-menu.php');

			// Mail templates
			require_once( INCSUB_SUPPORT_PLUGIN_DIR . '/inc/mail-contents.php');
		}

		/**
		 * Activates the plugin
		 * 
		 * @since 1.8
		 */
		public function activate() {
			$model = MU_Support_System_Model::get_instance();
			$model->create_tables();
			update_site_option( 'incsub_support_version', self::$version );

			// Inizializing settings
			foreach ( self::$settings as $key => $setting ) {
				update_site_option( $key, $setting );
			}
		}

		/**
		 * Deactivates the plugin
		 * 
		 * @since 1.8
		 */
		public function deactivate() {
			delete_site_option( 'incsub_support_version' );
		}

		/**
		 * Upgrades the plugin
		 * 
		 * @since 1.8
		 * 
		 * @param String old_version old version number
		 */
		public function check_for_upgrades() {

			$saved_version = get_site_option( 'incsub_support_version', false );

			if ( ! $saved_version || version_compare( $saved_version, self::$version ) < 0 ) {

				$model = MU_Support_System_Model::get_instance();

				if ( version_compare( $saved_version, '1.7.2.2' ) < 0 )
					$model->upgrade_1722();

				if ( version_compare( $saved_version, '1.8' ) < 0 )
					$model->upgrade_18();

				update_site_option( 'incsub_support_version', self::$version );
			}

		}

		/**
		 * Add actions for admin menus
		 */
		public function admin_menus() {

			if ( is_network_admin() ) {
				// Order is important
				self::$network_main_menu = new MU_Support_Network_Main_Menu();
				self::$network_single_ticket_menu = new MU_Support_Network_Single_Ticket_Menu();
				self::$network_ticket_categories = new MU_Support_Network_Ticket_Categories();
				self::$network_single_faq_question_menu = new MU_Support_Network_Single_FAQ_Question_Menu();
				self::$network_faq_manager_menu = new MU_Support_Network_FAQ_Manager_Menu();
				self::$network_faq_categories_menu = new MU_Support_Network_FAQ_Categories();
				self::$network_support_settings_menu = new MU_Support_Network_Support_settings();
			}
			elseif ( is_admin() ) {

				$admin_ticket_menu_allowed = true;
				if ( (boolean)MU_Support_System::$settings['incsub_allow_only_pro_sites'] )
					$admin_ticket_menu_allowed = function_exists( 'is_pro_site' ) && is_pro_site( get_current_blog_id(), absint( MU_Support_System::$settings['incsub_pro_sites_level'] ) );

				$admin_faq_menu_allowed = true;
				if ( MU_Support_System::$settings['incsub_allow_only_pro_sites_faq'] )
					$admin_faq_menu_allowed = function_exists( 'is_pro_site' ) && is_pro_site( get_current_blog_id(), absint( MU_Support_System::$settings['incsub_pro_sites_faq_level'] ) );

				$admin_faq_menu_allowed = $admin_faq_menu_allowed && MU_Support_System::$settings['incsub_support_faq_enabled'];

				// If is not a Pro site we will not create the menu
				if ( $admin_ticket_menu_allowed ) {
					self::$admin_single_ticket_menu = new MU_Support_Admin_Single_Ticket_Menu();
					self::$admin_new_ticket_menu = new MU_Support_Admin_New_Ticket_Menu();
					self::$admin_main_menu = new MU_Support_Admin_Main_Menu();
				}
				
				if ( ! $admin_ticket_menu_allowed && $admin_faq_menu_allowed )
					self::$admin_faq_menu = new MU_Support_Admin_FAQ_Menu( true );
				elseif ( $admin_ticket_menu_allowed && $admin_faq_menu_allowed )
					self::$admin_faq_menu = new MU_Support_Admin_FAQ_Menu( false );
			}

		}

		/**
		 * Programs several schedules frequency
		 * 
		 * @since 1.6
		 * 
		 * @param Array $schedule WP Schedules Array
		 * 
		 * @param Array New WP Schedules Array
		 */
		public function cron_schedules( $schedules ) {
			if ( self::$settings['incsub_support_fetch_imap'] == 'enabled' ) {
				$schedules['everyminute'] = array( 'interval' => 60, 'display' => __('Once a minute', INCSUB_SUPPORT_LANG_DOMAIN) );
				$schedules['fiveminutes'] = array( 'interval' => 300, 'display' => __('Once every five minutes', INCSUB_SUPPORT_LANG_DOMAIN) );
				$schedules['fifteenminutes'] = array( 'interval' => 900, 'display' => __('Once every fifteen minutes', INCSUB_SUPPORT_LANG_DOMAIN) );
				$schedules['thirtyminutes'] = array( 'interval' => 1800, 'display' => __('Once every half an hour', INCSUB_SUPPORT_LANG_DOMAIN) );
			}
			
			return $schedules;
		}

		/**
		 * Sets HTML content for mails
		 * 
		 * @return String Content type
		 */
		public function set_mail_content_type() {
			return 'text/html';
		}

	}

}

$mu_support_system = new MU_Support_System();





