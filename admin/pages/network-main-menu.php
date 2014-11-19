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
		public function __construct( $is_network = true, $capability = 'manage_network' ) {

			$this->includes();

			$this->page_title = __('Support Ticket Management System', INCSUB_SUPPORT_LANG_DOMAIN); 
			$this->menu_title = __('Support', INCSUB_SUPPORT_LANG_DOMAIN); 
			$this->capability = $capability;
			$this->menu_slug = 'ticket-manager';

			$model = MU_Support_System_Model::get_instance();
			
			parent::__construct( $is_network );

			add_action( 'init', array( &$this, 'get_new_tickets' ) );

			// Status of the screen
			$this->view = 'all';
			if ( isset( $_GET['view'] ) && in_array( $_GET['view'], array( 'all', 'active', 'archive' ) ) )
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

			$model = MU_Support_System_Model::get_instance();

		    $tickets_table = new MU_Support_Tickets_Table( $this->view );
		    $tickets_table->prepare_items();

		    $all_tickets_count = $model->get_tickets( 'all' );
		    $all_tickets_count = $all_tickets_count['total'];

		    $archived_tickets_count = $model->get_tickets( 'archive' );
		    $archived_tickets_count = $archived_tickets_count['total'];

		    $active_tickets_count = $all_tickets_count - $archived_tickets_count;

			?>
				<ul class="subsubsub">
					<li class="all"><a href="<?php echo add_query_arg( 'view', 'all' ); ?>" <?php echo 'all' == $this->view ? 'class="current"' : ''; ?> ><?php echo __( 'All', INCSUB_SUPPORT_LANG_DOMAIN ); ?> <span class="count">(<?php echo $all_tickets_count; ?>)</span></a> |</li>
					<li class="active"><a href="<?php echo add_query_arg( 'view', 'active' ); ?>" <?php echo 'active' == $this->view ? 'class="current"' : ''; ?> ><?php echo __( 'Opened', INCSUB_SUPPORT_LANG_DOMAIN ); ?> <span class="count">(<?php echo $active_tickets_count; ?>)</span></a> |</li>
					<li class="archived"><a href="<?php echo add_query_arg( 'view', 'archive' ); ?>" <?php echo 'archive' == $this->view ? 'class="current"' : ''; ?>><?php echo __( 'Closed', INCSUB_SUPPORT_LANG_DOMAIN ); ?> <span class="count">(<?php echo $archived_tickets_count; ?>)</span></a></li>
				</ul>
				<form id="support-tickets" method="post">
					<?php $tickets_table->display(); ?>
				</form>
			<?php
		}

		public function get_new_tickets() {
			$model = MU_Support_System_Model::get_instance();
			$this->count_update = $model->get_unchecked_tickets();
		}

	}

}
