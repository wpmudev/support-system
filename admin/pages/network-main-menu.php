<?php

/**
 * Support Network Main Menu
 */

if ( ! class_exists( 'MU_Support_Network_Main_Menu' ) ) {

	class MU_Support_Network_Main_Menu extends MU_Support_Menu {

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

			$this->page_title = __('Support Ticket Management System', INCSUB_SUPPORT_LANG_DOMAIN); 
			$this->menu_title = __('Support', INCSUB_SUPPORT_LANG_DOMAIN); 
			$this->capability = 'manage_network';
			$this->menu_slug = 'ticket-manager';

			parent::__construct();

			// Status of the screen
			$this->view = 'active';
			if ( isset( $_GET['view'] ) && in_array( $_GET['view'], array( 'active', 'archive' ) ) )
				$this->view = $_GET['view'];

		}

		/**
		 * Includes needed files
		 *
		 * @since 1.8
		 */
		private function includes() {
			// Model
			require_once( INCSUB_SUPPORT_PLUGIN_DIR . '/admin/tables/tickets-table.php');
		}

		/**
		 * Renders the page contents
		 * 
		 * @since 1.8
		 */
		public function render_content() {

		    $tickets_table = new MU_Support_Tickets_Table( $this->view );
		    $tickets_table->prepare_items();

			?>
				<ul class="subsubsub">
					<li class="active"><a href="<?php echo add_query_arg( 'view', 'active' ); ?>" <?php echo 'active' == $this->view ? 'class="current"' : ''; ?> ><?php echo __( 'Active tickets', INCSUB_SUPPORT_LANG_DOMAIN ); ?></a> |</li>
					<li class="archived"><a href="<?php echo add_query_arg( 'view', 'archive' ); ?>" <?php echo 'archive' == $this->view ? 'class="current"' : ''; ?>><?php echo __( 'Archived tickets', INCSUB_SUPPORT_LANG_DOMAIN ); ?></a></li>
				</ul>
			<?php
			
			$tickets_table->display();
		}

	}

}
