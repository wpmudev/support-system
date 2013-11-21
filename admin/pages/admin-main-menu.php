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
		private $filter_category;
		private $filter_status;

		/**
		 * Constructor
		 * 
		 * @since 1.8
		 */
		public function __construct() {

			$this->includes();

			$this->page_title = __( 'Support', INCSUB_SUPPORT_LANG_DOMAIN ); 
			$this->menu_title = MU_Support_System::$settings['incsub_support_menu_name'];
			$this->capability = 'read';
			$this->menu_slug = 'ticket-manager';
			$this->add_new_link = array(
				'label' => __( 'Add new ticket', INCSUB_SUPPORT_LANG_DOMAIN ),
				'link'	=> MU_Support_System::$admin_new_ticket_menu->get_permalink()
			);

			parent::__construct( false );

			add_action( 'admin_init', array( &$this, 'set_filter_vars' ) );
			
			// Status of the screen
			$this->filter_category = isset( $_REQUEST['filter_category'] ) ? absint( $_REQUEST['filter_category'] ) : false;
			$this->filter_status = ( isset( $_REQUEST['filter_status'] ) && array_key_exists( $_REQUEST['filter_status'], MU_Support_System::$ticket_status ) ) ? $_REQUEST['filter_status'] : false;
			
			$this->view = 'all';
			if ( 
				isset( $_REQUEST['view'] ) 
				&& $this->filter_category === false 
				&& $this->filter_status === false 
				&& in_array( $_REQUEST['view'], array( 'all', 'opened', 'closed' ) ) 
			) {
				$this->view = $_REQUEST['view'];
			}

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

		    $tickets_table = new MU_Support_Admin_Tickets_Table( $this->view, $this->filter_status, $this->filter_category );
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

		public function set_filter_vars() {
			if ( isset( $_POST['support-filter-submit'] ) ) {

				$redirect = $this->get_permalink();

				if ( $this->filter_status !== false )
					$redirect = add_query_arg( 'filter_status', $this->filter_status, $redirect );

				if ( $this->filter_category )
					$redirect = add_query_arg( 'filter_category', $this->filter_category, $redirect );

				wp_redirect( $redirect );
			}
			
			//wp_die();
		}

	}

}
