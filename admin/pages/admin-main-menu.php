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
				'link'	=> MU_Support_System::$admin_new_ticket_menu->get_permalink()
			);

			parent::__construct( false );

			// Status of the screen
			$this->view = 'all';
			if ( isset( $_GET['view'] ) && in_array( $_GET['view'], array( 'active', 'all', 'archive' ) ) )
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

			$model = incsub_support_get_ticket_model();

		    $tickets_table = new MU_Support_Admin_Tickets_Table( $this->view );
		    $tickets_table->prepare_items();

		    $args = array(
		    	'blog_id' => get_current_blog_id()
		    );

		    if ( 'requestor' == MU_Support_System::$settings['incsub_ticket_privacy'] )
		    	$args['user_in'] = array( get_current_user_id() );

			$all_tickets_count = $model->get_tickets( 'all', 0, 0, $args );
		    $all_tickets_count = $all_tickets_count['total'];

		    $archived_tickets_count = $model->get_tickets( 'archive', 0, 0, $args );
		    $archived_tickets_count = $archived_tickets_count['total'];

		    $active_tickets_count = $all_tickets_count - $archived_tickets_count;

		    ?>
		    	<ul class="subsubsub">
					<li class="all"><a href="<?php echo add_query_arg( 'view', 'all' ); ?>" <?php echo 'all' == $this->view ? 'class="current"' : ''; ?> ><?php echo __( 'All', INCSUB_SUPPORT_LANG_DOMAIN ); ?> <span class="count">(<?php echo $all_tickets_count; ?>)</span></a> |</li>
					<li class="active"><a href="<?php echo add_query_arg( 'view', 'active' ); ?>" <?php echo 'active' == $this->view ? 'class="current"' : ''; ?> ><?php echo __( 'Opened', INCSUB_SUPPORT_LANG_DOMAIN ); ?> <span class="count">(<?php echo $active_tickets_count; ?>)</span></a> |</li>
					<li class="archived"><a href="<?php echo add_query_arg( 'view', 'archive' ); ?>" <?php echo 'archive' == $this->view ? 'class="current"' : ''; ?>><?php echo __( 'Closed', INCSUB_SUPPORT_LANG_DOMAIN ); ?> <span class="count">(<?php echo $archived_tickets_count; ?>)</span></a></li>
				</ul>
			<?php

			
			$tickets_table->display();
		}

	}

}
