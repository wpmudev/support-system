<?php
/*
Plugin Name: Support System
Plugin URI: http://premium.wpmudev.org/project/support-system/
Description: Set up an awesome support ticket system on any WordPress site, complete with FAQ.
Author: WPMU DEV
WDP ID: 36
Network: true
Version: 2.1.9.1
License: GPLv2
Author URI: http://premium.wpmudev.org
Text Domain: incsub-support
*/

/*
Copyright 2007-2015 Incsub (http://incsub.com)
Author – Ignacio Cruz (igmoweb)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License (Version 2 – GPLv2) as published by
the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA

Foundation 5 License: See license-foundation.txt
*/

define( 'INCSUB_SUPPORT_PLUGIN_VERSION', '2.1.9' );

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
		public $settings = array();

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

		public $admin;
		public $shortcodes = null;

		private static $instance = null;

		public static function get_instance() {
			if ( ! self::$instance )
				self::$instance = new self();
			
			return self::$instance;
		}
		

		/**
		 * Constructor: Initializes the plugin
		 * 
		 * @since 1.8
		 */
		public function __construct() {

			// Include needed files
			$this->includes();

			// Initializes plugin
			add_action( 'init', array( &$this, 'init_plugin' ), 1 );

			// Activation/Upgrades
			register_activation_hook( __FILE__, array( &$this, 'activate' ) );
			register_deactivation_hook( __FILE__, array( &$this, 'deactivate' ) );

			// Is this an upgrade?
			add_action( 'init', 'incsub_support_check_for_upgrades' );

			add_action( 'plugins_loaded', array( &$this, 'load_text_domain' ), 100 );

			// Create Admin menus
			//add_action( 'init', array( &$this, 'admin_menus' ) );

			//load dashboard notice
			global $wpmudev_notices;
			$wpmudev_notices[] = array(
				'id'      => 36,
				'name'    => 'Support System',
				'screens' => array(
					'toplevel_page_ticket-manager-network',
					'support-2_page_ticket-categories-network',
					'support-2_page_support-faq-manager-network',
					'support-2_page_faq-categories-network',
					'support-2_page_mu-support-settings-network',
					'toplevel_page_ticket-manager',
					'support_page_support-faq',
					'support_page_ticket-categories',
					'support_page_support-faq-manager',
					'support_page_faq-categories',
					'support_page_mu-support-settings'
				)
			);
			include_once( INCSUB_SUPPORT_PLUGIN_DIR . '/dash-notice/wpmudev-dash-notification.php' );

		}


		public function init_plugin() {

			$this->model = incsub_support_get_model();
			$this->settings = new Incsub_Support_Settings();
			$this->query = new Incsub_Support_Query();

			if ( is_admin() )
				$this->admin = new Incsub_Support_Admin();

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
				'all' => is_multisite() ? __( 'Allow all users to see all tickets in a site', INCSUB_SUPPORT_LANG_DOMAIN ) : __( 'Allow all users to see all tickets', INCSUB_SUPPORT_LANG_DOMAIN ),
				'requestor' => __( 'Allow only requestors to see their own tickets', INCSUB_SUPPORT_LANG_DOMAIN )
			);


			if ( ( is_multisite() && $this->settings->get( 'incsub_support_blog_id' ) == get_current_blog_id() ) || ! is_multisite() )
				$this->shortcodes = new Incsub_Support_Shortcodes();
		}

		public function load_text_domain() {

			$locale = apply_filters( 'plugin_locale', get_locale(), INCSUB_SUPPORT_LANG_DOMAIN );

			load_textdomain( INCSUB_SUPPORT_LANG_DOMAIN, WP_LANG_DIR . '/' . INCSUB_SUPPORT_LANG_DOMAIN . '/' . INCSUB_SUPPORT_LANG_DOMAIN . '-' . $locale . '.mo' );
			load_plugin_textdomain( INCSUB_SUPPORT_LANG_DOMAIN, FALSE, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
			
		}


		/**
		 * Includes needed files
		 *
		 * @since 1.8
		 */
		private function includes() {
			// Model
			require_once( INCSUB_SUPPORT_PLUGIN_DIR . '/model/model.php');

			if ( is_admin() ) {
				require_once( INCSUB_SUPPORT_PLUGIN_DIR . '/admin/class-incsub-support-admin.php' );
			}

			// Classes
			require_once( INCSUB_SUPPORT_PLUGIN_DIR . '/inc/classes/class-ticket.php');
			require_once( INCSUB_SUPPORT_PLUGIN_DIR . '/inc/classes/class-ticket-category.php');
			require_once( INCSUB_SUPPORT_PLUGIN_DIR . '/inc/classes/class-ticket-reply.php');
			require_once( INCSUB_SUPPORT_PLUGIN_DIR . '/inc/classes/class-shortcodes.php');
			require_once( INCSUB_SUPPORT_PLUGIN_DIR . '/inc/classes/class-query.php');
			require_once( INCSUB_SUPPORT_PLUGIN_DIR . '/inc/classes/class-faq.php');
			require_once( INCSUB_SUPPORT_PLUGIN_DIR . '/inc/classes/class-faq-category.php');
			require_once( INCSUB_SUPPORT_PLUGIN_DIR . '/inc/classes/class-settings.php');

			require_once( INCSUB_SUPPORT_PLUGIN_DIR . '/inc/helpers/general.php');
			require_once( INCSUB_SUPPORT_PLUGIN_DIR . '/inc/helpers/ticket.php');
			require_once( INCSUB_SUPPORT_PLUGIN_DIR . '/inc/helpers/ticket-category.php');
			require_once( INCSUB_SUPPORT_PLUGIN_DIR . '/inc/helpers/ticket-reply.php');
			require_once( INCSUB_SUPPORT_PLUGIN_DIR . '/inc/helpers/template.php');
			require_once( INCSUB_SUPPORT_PLUGIN_DIR . '/inc/helpers/settings.php');
			require_once( INCSUB_SUPPORT_PLUGIN_DIR . '/inc/helpers/capabilities.php');
			require_once( INCSUB_SUPPORT_PLUGIN_DIR . '/inc/helpers/faq.php');
			require_once( INCSUB_SUPPORT_PLUGIN_DIR . '/inc/helpers/faq-category.php');

			// Integration
			require_once( INCSUB_SUPPORT_PLUGIN_DIR . '/inc/integration/pro-sites.php');

			// Mail templates
			require_once( INCSUB_SUPPORT_PLUGIN_DIR . '/inc/mail-contents.php');

			// Upgrades
			require_once( INCSUB_SUPPORT_PLUGIN_DIR . '/inc/upgrades.php');
		}

		/**
		 * Activates the plugin
		 * 
		 * @since 1.8
		 */
		public function activate() {
			$model = incsub_support_get_model();
			$model->create_tables();

			$settings = new Incsub_Support_Settings();

			update_site_option( 'incsub_support_version', self::$version );
			update_site_option( 'incsub_support_settings', $settings->get_all() );	

			set_transient( 'incsub_support_welcome', true );		
		}

		/**
		 * Deactivates the plugin
		 * 
		 * @since 1.8
		 */
		public function deactivate() {
			//delete_site_option( 'incsub_support_version' );
			//delete_site_option( 'incsub_support_settings' );
		}

		

		/**
		 * Add actions for admin menus
		 */
		public function admin_menus() {

			if ( is_multisite() ) {
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

					$user = get_userdata( get_current_user_id() );
					$user_role = isset( $user->roles[0] ) ? $user->roles[0] : ( is_super_admin() ? 'administrator' : '' );

					$admin_ticket_menu_allowed = false;
					// Tickets allowed?
					foreach ( $this->settings->get( 'incsub_support_tickets_role' ) as $ticket_role ) {
						if ( $user_role == $ticket_role ) {
							$admin_ticket_menu_allowed = true;
							break;
						}
					}

					if ( (boolean)$this->settings->get( 'incsub_allow_only_pro_sites' ) && $admin_ticket_menu_allowed )
						$admin_ticket_menu_allowed = function_exists( 'is_pro_site' ) && is_pro_site( get_current_blog_id(), absint( $this->settings->get( 'incsub_pro_sites_level' ) ) );

					// FAQs allowed?
					$admin_faq_menu_allowed = false;
					foreach ( $this->settings->get( 'incsub_support_faqs_role' ) as $faq_role ) {
						if ( $user_role == $faq_role ) {
							$admin_faq_menu_allowed = true;
							break;
						}
					}
					if ( $this->settings->get( 'incsub_allow_only_pro_sites_faq' ) && $admin_faq_menu_allowed )
						$admin_faq_menu_allowed = function_exists( 'is_pro_site' ) && is_pro_site( get_current_blog_id(), absint( $this->settings->get( 'incsub_pro_sites_faq_level' ) ) );

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
			elseif ( ! is_multisite() && is_admin() ) {
				if ( current_user_can( 'administrator' ) ) {
					self::$network_main_menu = new MU_Support_Network_Main_Menu( false, 'manage_options' );
					self::$network_single_ticket_menu = new MU_Support_Network_Single_Ticket_Menu( false, false, 'manage_options' );
					self::$network_ticket_categories = new MU_Support_Network_Ticket_Categories( false, 'manage_options' );
					self::$network_single_faq_question_menu = new MU_Support_Network_Single_FAQ_Question_Menu( false, 'manage_options' );
					self::$network_faq_manager_menu = new MU_Support_Network_FAQ_Manager_Menu( false, 'manage_options' );
					self::$network_faq_categories_menu = new MU_Support_Network_FAQ_Categories( false, 'manage_options' );
					self::$network_support_settings_menu = new MU_Support_Network_Support_settings( false, 'manage_options' );
				}
				else {

					$user = get_userdata( get_current_user_id() );
					$user_role = $user->roles[0];

					// Tickets allowed?
					$admin_ticket_menu_allowed = false;
					foreach ( $this->settings->get( 'incsub_support_tickets_role' ) as $ticket_role ) {
						if ( $user_role == $ticket_role ) {
							$admin_ticket_menu_allowed = true;
							break;
						}
					}
					if ( $admin_ticket_menu_allowed ) {
						self::$admin_single_ticket_menu = new MU_Support_Admin_Single_Ticket_Menu();
						self::$admin_new_ticket_menu = new MU_Support_Admin_New_Ticket_Menu();
						self::$admin_main_menu = new MU_Support_Admin_Main_Menu();
					}
					

					// FAQs allowed?
					$admin_faq_menu_allowed = false;
					foreach ( $this->settings->get( 'incsub_support_faqs_role' ) as $faq_role ) {
						if ( $user_role == $faq_role ) {
							$admin_faq_menu_allowed = true;
							break;
						}
					}
					if ( ! $admin_ticket_menu_allowed && $admin_faq_menu_allowed )
						self::$admin_faq_menu = new MU_Support_Admin_FAQ_Menu( true );
					elseif ( $admin_ticket_menu_allowed && $admin_faq_menu_allowed )
						self::$admin_faq_menu = new MU_Support_Admin_FAQ_Menu( false );
				}
			}
		}

		/**
		 * Sets HTML content for mails
		 * 
		 * @return String Content type
		 */
		public function set_mail_content_type() {
			return 'text/html';
		}

		/**
		 * Return all roles depending on multisite or not
		 * 
		 * @since 1.9
		 */
		public static function get_roles() {

			global $wp_roles;

			$just_roles = $wp_roles->roles;

			if ( ! is_multisite() )
				unset( $just_roles['administrator'] );

			$support_roles = array();
			foreach ( $just_roles as $key => $role ) {
				$support_roles[ $key ] = translate_user_role( $role['name'] );
			}
			
			return $support_roles;

		}

		/**
		 * Return a ist of Super Admins for multisites or Admins for single sites
		 * 
		 * @since 1.9.5
		 * 
		 * @return Array of Super Admins/Admins
		 */
		public static function get_super_admins() {

			if ( is_multisite() ) {
				$super_admins = get_super_admins();
			}
			else {
				$admins = get_users( 
					array( 
						'role' => 'administrator',
						'fields' => array( 'ID', 'user_login' )
					)
				);

				$super_admins = array();
				foreach ( $admins as $admin ) {
					$super_admins[ $admin->ID ] = $admin->user_login;
				}

			}

			return $super_admins;
		}

		public static function get_main_admin_email() {
			$administrator = incsub_support_get_setting( 'incsub_support_main_super_admin' );
			$super_admins = MU_Support_System::get_super_admins();
			$admin_login = $super_admins[ $administrator ];
			$admin_user = get_user_by( 'login', $admin_login );
			return $admin_user->user_email;
		}

		public static function get_main_admin_details() {
			$administrator = incsub_support_get_setting( 'incsub_support_main_super_admin' );
			$super_admins = MU_Support_System::get_super_admins();
			$admin_login = $super_admins[ $administrator ];
			$admin_user = get_user_by( 'login', $admin_login );
			return $admin_user;
		}

	}

}

function incsub_support() {
	return MU_Support_System::get_instance();	
}


add_action( 'plugins_loaded', 'support_system_init' );
function support_system_init() {
	global $mu_support_system;
	$mu_support_system = incsub_support();	
}

