<?php

/**
 * Support Network Menu Main class
 */

if ( ! class_exists( 'MU_Support_Menu' ) ) {

	abstract class MU_Support_Menu {

		public $menu_id;
		
		public $page_title = '';
		public $menu_title = '';
		public $capability = 'read';
		public $menu_slug;
		public $icon_url = 'div';
		public $position = null;
		public $tabs = false;
		public $active_tab;
		public $add_new_link = false;
		public $count_update = false;
		public $is_network = false;

		public $submenu = false;
		public $parent = null;

		public $page_id;

		private $errors = false;

		public static $menu_slug_name = '';

		/**
		 * Constructor
		 * 
		 * @param Boolean $network 
		 * @param Boolean $just_object Workaround (needs improving). It allows to create the object but not create the menu
		 * @return type
		 */
		public function __construct( $network = true, $just_object = false ) {
			$this->is_network = $network;
			if ( ! $just_object ) {
				if ( $network ) {
					add_action( 'network_admin_menu', array( &$this, 'add_menu' ) );
				}
				else {
					add_action( 'admin_menu', array( &$this, 'add_menu' ) );
				}
			}
		}

		/**
		 * Adds the menu to the Admin Panel
		 * 
		 * @since 1.8
		 */
		public function add_menu() {			
			
			if ( ( ! $this->submenu ) ) {
				$menu_title = $this->menu_title;

				if ( $this->count_update )
					$menu_title .= '<span class="update-plugins count-3"><span class="plugin-count">' . $this->count_update . '</span></span>';

				$this->page_id = add_menu_page( 
					$this->page_title, 
					$menu_title, 
					$this->capability, 
					$this->menu_slug, 
					array( &$this, 'render_page' ), 
					$this->icon_url, 
					$this->position
				);
			}
			else {
				$this->page_id = add_submenu_page( 
					$this->parent, 
					$this->page_title, 
					$this->menu_title, 
					$this->capability, 
					$this->menu_slug, 
					array( &$this, 'render_page' )
				);
			}

		}

		/**
		 * Renders the page
		 * 
		 * @since 1.8
		 */
		public function render_page() {

			if ( ! current_user_can( $this->capability ) )
				wp_die( __( 'You are not allowed to view this page.', INCSUB_SUPPORT_LANG_DOMAIN ) );

			?>
				<div class="wrap">
					<?php screen_icon( 'support' ); ?>
					<?php if ( is_array( $this->tabs ) ): ?>
						<h2 class="nav-tab-wrapper">
							<?php foreach ( $this->tabs as $tab ): ?>
								<a href="<?php echo $tab['link']; ?>" class="nav-tab <?php echo $this->active_tab == $tab['slug'] ? 'nav-tab-active' : ''; ?>"><?php echo $tab['label']; ?></a>
							<?php endforeach; ?>
						</h2>
					<?php else: ?>
						<h2>
							<?php echo $this->page_title; ?>
							<?php if ( $this->add_new_link ): ?>
								<a href="<?php echo $this->add_new_link['link']; ?>" class="add-new-h2"><?php echo $this->add_new_link['label']; ?></a>
							<?php endif; ?>
						</h2>
					<?php endif; ?>
						
					
					<?php $this->render_content(); ?>
				</div>
			<?php
		}

		/**
		 * Renders the content
		 * 
		 * Must be defined by a subclass
		 * 
		 * @since 1.8
		 */
		public abstract function render_content();

		/**
		 * Gets the link for the page
		 * 
		 * @since 1.8
		 */
		public function get_permalink() {
			if ( $this->is_network )
				return network_admin_url( 'admin.php?page=' . $this->menu_slug );
			else
				return admin_url( 'admin.php?page=' . $this->menu_slug );
		}

		/**
		 * Retrieves a row in WP format
		 * 
		 * @since 1.8
		 * 
		 * @param String title Title of the row
		 * @param String markup Content of the row
		 */
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

		/**
		 * Adds an error for handling forms purposes
		 * 
		 * @since 1.8
		 * 
		 * @param String $message Error message
		 */
		public function add_error( $slug, $message ) {
			if ( ! is_array( $this->errors ) )
				$this->errors = array();
			
			$this->errors[ $slug ] = $message;
		}

		/**
		 * Checks if there are errors
		 * 
		 * @since 1.8
		 * 
		 */
		public function is_error( $slug = null ) {

			if ( ! empty( $slug ) && isset( $this->errors[ $slug ] ) )
				return true;
			elseif ( empty( $slug ) ) {
				if ( $this->errors != false )
					return true;
				else
					return false;
			}
		}

		/**
		 * Gets the errors list
		 * 
		 * @since 1.8
		 * 
		 */
		public function get_errors() {
			return $this->errors;
		}

		public function render_errors() {
			?>
				<div class="error">
					<ul>
						<?php foreach ( $this->get_errors() as $error ): ?>
							<li><?php echo $error; ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php
		}

		/**
		 * In some cases we'd need to remove the menu
		 */
		public function remove_menu() {
			remove_menu_page( $this->menu_slug )	;
		}

	}

}