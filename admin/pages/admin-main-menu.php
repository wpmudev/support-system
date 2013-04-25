<?php

/**
 * Support Network Main Menu
 */

if ( ! class_exists( 'MU_Support_Admin_Main_Menu' ) ) {

	class MU_Support_Admin_Main_Menu extends MU_Support_Menu {

		/**
		 * Status of the screen
		 */
		private $view;

		/**
		 * Constructor
		 * 
		 * @since 1.8
		 */
		public function __construct() {

			$this->includes();

			
			$this->menu_title = MU_Support_System::$settings['incsub_support_menu_name'];
			$this->capability = 'read';
			$this->menu_slug = 'ticket-manager';
			$this->add_new_link = array(
				'label' => __( 'Add new ticket', INCSUB_SUPPORT_LANG_DOMAIN ),
				'link'	=> Mu_Support_System::$admin_new_ticket_menu->get_permalink()
			);

			parent::__construct( false );

			// Status of the screen
			$this->view = 'active';
			if ( isset( $_GET['view'] ) && in_array( $_GET['view'], array( 'active', 'all' ) ) )
				$this->view = $_GET['view'];

			if ( 'all' == $this->view )
				$this->page_title = __('All Tickets', INCSUB_SUPPORT_LANG_DOMAIN); 
			else
				$this->page_title = __('Recent Tickets', INCSUB_SUPPORT_LANG_DOMAIN); 

		}

		/**
		 * Includes needed files
		 *
		 * @since 1.8
		 */
		private function includes() {
			// Model
			require_once( INCSUB_SUPPORT_PLUGIN_DIR . '/admin/tables/admin-tickets-table.php');
		}

		/**
		 * Renders the page contents
		 * 
		 * @since 1.8
		 */
		public function render_content() {

		    $tickets_table = new MU_Support_Admin_Tickets_Table( $this->view );
		    $tickets_table->prepare_items();

		    ?><ul class="subsubsub"><?php

		    if ( 'active' == $this->view ) {
		    	?>
		    		<li class="active"><a href="<?php echo add_query_arg( 'view', 'all' ); ?>"><?php echo __( 'View also closed tickets', INCSUB_SUPPORT_LANG_DOMAIN ); ?></a></li>
		    	<?php
		    }
		    else {
		    	?>
		    		<li class="active"><a href="<?php echo add_query_arg( 'view', 'active' ); ?>"><?php echo __( 'View recent tickets', INCSUB_SUPPORT_LANG_DOMAIN ); ?></a></li>
		    	<?php
		    }
				
		    ?></ul><?php
			
			$tickets_table->display();
		}

	}

}
