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
		private $filter_category;
		private $filter_status;

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

			$model = incsub_support_get_ticket_model();
			
			parent::__construct( $is_network );

			add_action( 'init', array( &$this, 'get_new_tickets' ) );

			// Status of the screen
			$this->filter_category = isset( $_POST['filter_category'] ) ? absint( $_POST['filter_category'] ) : false;
			$this->filter_status = (  isset( $_POST['filter_status'] ) && array_key_exists( $_POST['filter_status'], MU_Support_System::$ticket_status ) ) ? $_POST['filter_status'] : false;

			$this->view = 'all';
			if ( isset( $_GET['view'] ) && $this->filter_category === false && $this->filter_status === false && in_array( $_GET['view'], array( 'all', 'opened', 'closed' ) ) )
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

			$model = incsub_support_get_ticket_model();

		    $tickets_table = new MU_Support_Tickets_Table( $this->view, $this->filter_status, $this->filter_category );
		    $tickets_table->prepare_items();

		    $tickets_count = incsub_support_get_tickets_count();

		    $current_view = ( $this->filter_status !== false || $this->filter_category !== false ) ? '' : $this->view;
			?>
				<ul class="subsubsub">
					<li class="all"><a href="<?php echo add_query_arg( 'view', 'all', $this->get_permalink() ); ?>" <?php echo 'all' == $current_view ? 'class="current"' : ''; ?> ><?php echo __( 'All', INCSUB_SUPPORT_LANG_DOMAIN ); ?> <span class="count">(<?php echo $tickets_count['all']; ?>)</span></a> |</li>
					<li class="active"><a href="<?php echo add_query_arg( 'view', 'opened', $this->get_permalink() ); ?>" <?php echo 'opened' == $current_view ? 'class="current"' : ''; ?> ><?php echo __( 'Opened', INCSUB_SUPPORT_LANG_DOMAIN ); ?> <span class="count">(<?php echo $tickets_count['opened']; ?>)</span></a> |</li>
					<li class="archived"><a href="<?php echo add_query_arg( 'view', 'closed', $this->get_permalink() ); ?>" <?php echo 'closed' == $current_view ? 'class="current"' : ''; ?>><?php echo __( 'Closed', INCSUB_SUPPORT_LANG_DOMAIN ); ?> <span class="count">(<?php echo $tickets_count['closed']; ?>)</span></a></li>
				</ul>
				<form id="support-tickets" method="post">
					<?php $tickets_table->display(); ?>
				</form>
			<?php
		}

		public function get_new_tickets() {
			$model = incsub_support_get_ticket_model();
			$this->count_update = $model->get_unchecked_tickets();
		}

	}

}
