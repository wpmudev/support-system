<?php

abstract class Incsub_Support_Admin_Menu {

	// Menu slug
	public $slug;
	public $network = false;

	// Page ID
	public $page_id = null;

	public function __construct( $slug, $network = false ) {
		
		$this->slug = $slug;
		$this->network = $network;

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
				<?php 
					/**
					 * Filters the Support System Admin Pages Titles
					 * 
					 * @param string $admin_page_title The admin page title
					 * @param string $page_id The admin page ID
					 */
					echo apply_filters( 'support_system_admin_page_title', '<h2>' . esc_html( get_admin_page_title() ) . '</h2>', $this->page_id ); 
				?>

				<?php $this->render_inner_page(); ?>
			</div>

		<?php
	}

	public function on_load() {}



	protected function add_menu_page( $menu_title, $page_title, $cap, $icon = '' ) {
		if ( ! $this->slug || ! $cap )
			return;

		$this->menu_title = $page_title;

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

	protected function add_submenu_page( $parent_slug, $menu_title, $page_title, $cap ) {
		$this->menu_title = $page_title;
		$this->page_id = add_submenu_page( 
			$parent_slug,
			$page_title,
			$menu_title,
			$cap,
			$this->slug,
			array( $this, 'render_page' ) 
		);

		add_action( 'load-' . $this->page_id, array( $this, 'on_load' ) );
	}

	public function get_menu_url() {
		if ( $this->network )
			return network_admin_url( 'admin.php?page=' . $this->slug );
		else
			return admin_url( 'admin.php?page=' . $this->slug );
	}

	public function get_menu_title() {
		return $this->menu_title;
	}

	public function render_row( $title, $markup ) {
		?>
			<tr valign="top">
				<th scope="row"><label for="site_name"><?php echo $title; ?></label></th>
				<td>
					<?php echo $markup; ?>			
				</td>
			</tr>
		<?php
	}
}