<?php

/**
 * Support Network Main Menu
 */

if ( ! class_exists( 'MU_Support_Network_Ticket_Categories' ) ) {

	class MU_Support_Network_Ticket_Categories extends MU_Support_Menu {

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

			$this->page_title = __('Tickets Categories', INCSUB_SUPPORT_LANG_DOMAIN); 
			$this->menu_title = __('Tickets Categories', INCSUB_SUPPORT_LANG_DOMAIN); 
			$this->capability = 'manage_network';
			$this->menu_slug = 'ticket-categories';
			$this->parent = MU_Support_System::$network_main_menu->menu_slug;
			$this->submenu = true;

			parent::__construct();

		}

		/**
		 * Includes needed files
		 *
		 * @since 1.8
		 */
		private function includes() {
			// Model
			require_once( INCSUB_SUPPORT_PLUGIN_DIR . '/admin/tables/ticket-categories-table.php');
		}

		/**
		 * Renders the page contents
		 * 
		 * @since 1.8
		 */
		public function render_content() {

			$category_name = '';
			if ( isset( $_POST['submit'] ) ) {
				$category_name = $this->validate_form( $_POST );

				if ( $this->is_error() ) {
					$this->render_errors();
				}
				else {
					$category_name = '';
					?>
						<div class="updated">
							<p><?php _e( 'A new category has been added', INCSUB_SUPPORT_LANG_DOMAIN ); ?></p>
						</div>
					<?php
				}
			}

			


			$cats_table = new MU_Support_Ticket_Categories_Table();
			$cats_table->prepare_items();
		    ?>
		    	<br class="clear">
				<div id="col-container">
					<div id="col-right">
						<div class="col-wrap">
							<div class="form-wrap">
								<form id="categories-table-form" action="" method="post">
									<?php $cats_table->display(); ?>
								</form>
							</div>
						</div>
					</div>
					<div id="col-left">
						<div class="col-wrap">
							<div class="form-wrap">
								<h3><?php _e( 'Add new category', INCSUB_SUPPORT_LANG_DOMAIN ); ?></h3>
								<form id="categories-table-form" action="" method="post">
									<?php wp_nonce_field( 'add-ticket-category' ); ?>
									<div class="form-field">
										<label for="cat_name"><?php _e( 'Category Name', INCSUB_SUPPORT_LANG_DOMAIN ); ?></label>
										<input name="cat_name" id="cat_name" type="text" value="<?php echo $category_name; ?>" size="40" aria-required="true"><br/>
										<p><?php _e('The name is used to identify the category to which tickets relate', INCSUB_SUPPORT_LANG_DOMAIN ); ?></p>
									</div>
									<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Add New Category"></p>
								</form>
							</div>
						</div>
					</div>
				</div>
		    <?php
		}

		/**
		 * Validates the Add category form
		 * 
		 * @since 1.8
		 * 
		 * @param Array $input $_POST Array 
		 * 
		 */
		private function validate_form( $input ) {

			if ( ! wp_verify_nonce( $input['_wpnonce'], 'add-ticket-category' ) )
				wp_die( __( 'Security check error', INCSUB_SUPPORT_LANG_DOMAIN ) );

			$category_name = '';
			if ( isset( $_POST['cat_name'] ) ) {
				$category_name = sanitize_text_field( $_POST['cat_name'] );
				if ( empty( $category_name ) )
					$this->add_error( 'category-name', __( 'Category name cannot be empty', INCSUB_SUPPORT_LANG_DOMAIN ) );
			}

			$model = MU_Support_System_Model::get_instance();
			$model->add_ticket_category( $category_name );

			return $category_name;

		}

	}

}
