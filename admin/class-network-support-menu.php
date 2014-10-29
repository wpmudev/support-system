<?php

abstract class Incsub_Support_Admin_Menu {

	// Menu slug
	public $slug;

	// Page ID
	public $page_id;

	public function __construct( $slug, $network = false ) {
		
		$this->slug = $slug;

		if ( $network )
			add_action( 'network_admin_menu', array( &$this, 'add_menu' ) );
		else
			add_action( 'admin_menu', array( &$this, 'add_menu' ) );
	}

	public abstract function add_menu();
	public abstract function render_inner_page();

	public function render_page() {
		?>
			<div class="wrap">
				<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>

				<?php $this->render_inner_page(); ?>
			</div>

		<?php
	}

	public function on_load() {}



	protected function add_menu_page( $menu_title, $page_title, $cap, $icon = '' ) {
		if ( ! $this->slug || ! $cap )
			return;

		$this->page_id = add_menu_page( 
			$menu_title,
			$page_title,
			$cap,
			$this->slug, 
			array( $this, 'render_page' ), 
			$icon 
		);

		add_action( 'load-' . $this->page_id, array( $this, 'on_load' ) );
	}

	protected function add_submenu_page( $parent_slug, $menu_title, $page_title, $cap, $icon ) {

	}
}


class Incsub_Support_Network_Menu extends Incsub_Support_Admin_Menu {

	public function __construct( $slug, $network = false ) {
		parent::__construct( $slug, $network );
		add_filter( 'set-screen-option', array( $this, 'save_screen_options' ), 10, 3 );
	}

	public function save_screen_options( $status, $option, $value ) {
		if ( 'incsub_support_tickets_per_page' == $option ) 
			return $value;

		return $status;
	}


	public function add_menu() {
		parent::add_menu_page(
			__( 'Support Ticket Management System', INCSUB_SUPPORT_LANG_DOMAIN ),
			__( 'Support', INCSUB_SUPPORT_LANG_DOMAIN ), 
			'manage_network',
			'dashicons-sos'
		);

	}

	public function on_load() {

		// Add screen options
		add_screen_option( 'per_page', array( 'label' => __( 'Tickets per page', INCSUB_SBE_LANG_DOMAIN ), 'default' => 20, 'option' => 'incsub_support_tickets_per_page' ) );

		// Check filtering
		if ( isset( $_POST['filter_action'] ) ) {

			$filters = array(
				'category' => false,
				'priority' => false
			);

			$url = false;

			if ( ! empty( $_POST['ticket-cat'] ) && $cat_id = absint( $_POST['ticket-cat'] ) )
				$filters['category'] = $cat_id;



			if ( isset( $_POST['ticket-priority'] ) && $_POST['ticket-priority'] !== '' )
				$filters['priority'] = $_POST['ticket-priority'];
			
			$url = $_SERVER['REQUEST_URI'];
			foreach ( $filters as $key => $value ) {
				if ( $value === false )
					$url = remove_query_arg( $key, $url );
				else
					$url = add_query_arg( $key, $value, $url );
			}

			wp_redirect( $url );
			exit();
			
		}
	}

	public function render_inner_page() {
		include_once( 'inc/class-table-tickets.php' );

		$status = $this->get_status_filter();
		$category = $this->get_filter( 'category' );
		$priority = $this->get_filter( 'priority' );

	    $tickets_table = new Incsub_Support_Tickets_Table( $status );
	    $tickets_table->set_status( $status );
	    $tickets_table->set_priority( $priority );
	    $tickets_table->set_category( $category );
	    $tickets_table->prepare_items();

	    $all_tickets_count = incsub_support_get_tickets_count();
	    $archived_tickets_count = incsub_support_get_tickets_count( array( 'status' => 'archive' ) );
	    $active_tickets_count = $all_tickets_count - $archived_tickets_count;

	    

	    echo '<div class="wrap">';
	    include( 'views/ticket-status-links.php' );
	    include( 'views/tickets-table-form.php' );
	    echo '</div>';
	}

	private function get_status_filter() {
		if ( ! isset( $_GET['status'] ) )
			return 'all';

		return $_GET['status'];
	}

	private function get_filter( $slug ) {
		if ( ! isset( $_GET[ $slug ] ) )
			return false;

		return $_GET[ $slug ];
	}
}