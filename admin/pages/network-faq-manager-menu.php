<?php

/**
 * Support Network Main Menu
 */
if ( ! class_exists( 'MU_Support_Network_FAQ_Manager_Menu' ) ) {

	class MU_Support_Network_FAQ_Manager_Menu extends MU_Support_Menu {

		/**
		 * Constructor
		 * 
		 * @since 1.8
		 */
		public function __construct() {
		
			$this->includes();

			$this->page_title = __( 'FAQ Manager', INCSUB_SUPPORT_LANG_DOMAIN ); 
			$this->menu_title = __( 'FAQ Manager', INCSUB_SUPPORT_LANG_DOMAIN); 
			$this->capability = 'manage_network';
			$this->menu_slug = 'support-faq-manager';
			$this->parent = MU_Support_System::$network_main_menu->menu_slug;
			$this->submenu = true;

			$add_new_link = MU_Support_System::$network_single_faq_question_menu->get_permalink();
			$add_new_link = add_query_arg( 'action', 'new', $add_new_link );
			$this->add_new_link = array(
				'label' => __( 'Add new FAQ question', INCSUB_SUPPORT_LANG_DOMAIN ),
				'link'=> $add_new_link
			);

			parent::__construct();

		}

		/**
		 * Includes needed files
		 *
		 * @since 1.8
		 */
		private function includes() {
			// Model
			require_once( INCSUB_SUPPORT_PLUGIN_DIR . '/admin/tables/faqs-table.php');
		}

		/**
		 * Renders the page contents
		 * 
		 * @since 1.8
		 */
		public function render_content() {

			if ( isset( $_GET['action'] ) && 'delete' == $_GET['action'] && isset( $_GET['fid'] ) ) {
				$faq_id = absint( $_GET['fid'] );
				$model = MU_Support_System_Model::get_instance();
				$model->delete_faq_question( $faq_id );
			}
				

		    $faqs_table = new MU_Support_FAQS_Table();
		    $faqs_table->prepare_items();
			
			$faqs_table->display();
		}

	}

}
