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
		public function __construct( $is_network = true, $capability = 'manage_network'  ) {

			$this->includes();

			$this->page_title = __('Tickets Categories', INCSUB_SUPPORT_LANG_DOMAIN); 
			$this->menu_title = __('Tickets Categories', INCSUB_SUPPORT_LANG_DOMAIN); 
			$this->capability = $capability;
			$this->menu_slug = 'ticket-categories';
			$this->parent = MU_Support_System::$network_main_menu->menu_slug;
			$this->submenu = true;

			parent::__construct( $is_network );

			add_action( 'admin_init', array( &$this, 'edit_category' ) );

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

			

			if ( isset( $_GET['action'] ) && 'edit' == $_GET['action'] && isset( $_GET['category'] ) && $cat_id = absint( $_GET['category'] ) ) {
				$model = MU_Support_System_Model::get_instance();
				$cat_name = $model->get_ticket_category( $cat_id );

				$user_id = $cat_name['user_id'];
				$user = get_user_by( 'id', $user_id );

				$user_login = 0;
				if ( $user )
					$user_login = $user->data->user_login;

				?>
					<form id="categories-table-form" action="" method="post">
						<table class="form-table">
							<?php
								ob_start();
							?>	
								<input type="text" name="ticket_cat_name" value="<?php echo esc_attr( $cat_name['cat_name'] ); ?>">
								<input type="hidden" name="ticket_cat_id" value="<?php echo esc_attr( $cat_id ); ?>">
							<?php
								$this->render_row( __( 'Category name', INCSUB_SUPPORT_LANG_DOMAIN ), ob_get_clean() );
							?>
							<?php
								ob_start();
							?>	
								<?php $this->admin_users_dropdown( $user_login ); ?>
							<?php
								$this->render_row( __( 'Assign to user', INCSUB_SUPPORT_LANG_DOMAIN ), ob_get_clean() );
							?>
						</table>
						<?php wp_nonce_field( 'edit-ticket-category', '_wpnonce' ); ?>
						<?php submit_button( null, 'primary', 'submit-edit-ticket-category' ); ?>
					</form>
				<?php
			}
			else {
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
										<div class="form-field">
											<label for="admin_user"><?php _e( 'Assign to user', INCSUB_SUPPORT_LANG_DOMAIN ); ?></label>
											<?php $this->admin_users_dropdown(); ?>
											<p><?php _e( 'Any new opened ticket with this category will be assigned to this user', INCSUB_SUPPORT_LANG_DOMAIN ); ?></p>
										</div>
										<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Add New Category"></p>
									</form>
								</div>
							</div>
						</div>
					</div>
			    <?php
			}

			
		}


		public function edit_category() {
			if ( isset( $_POST['submit-edit-ticket-category'] ) ) {
				if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'edit-ticket-category' ) )
					wp_die( __( 'Security check error', INCSUB_SUPPORT_LANG_DOMAIN ) );

				if ( isset( $_POST['ticket_cat_name'] ) && ! empty( $_POST['ticket_cat_name'] ) && isset( $_POST['ticket_cat_id'] ) ) {
					$model = MU_Support_System_Model::get_instance();
					$user_id = 0;
					if ( ! empty( $_POST['admin_user'] ) ) {
						$user = get_user_by( 'login', $_POST['admin_user'] );			
						if ( $user )
							$user_id = $user->ID;
					}

					$model->update_ticket_category( absint( $_POST['ticket_cat_id'] ), sanitize_text_field( $_POST['ticket_cat_name'] ), $user_id );
				}

				wp_redirect( $this->get_permalink() );

			}
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

			$user_id = 0;
			if ( ! empty( $_POST['admin_user'] ) ) {
				$user = get_user_by( 'login', $_POST['admin_user'] );
				if ( $user )
					$user_id = $user->ID;
			}

			$model = MU_Support_System_Model::get_instance();
			$model->add_ticket_category( $category_name, $user_id );

			return $category_name;

		}

		private function admin_users_dropdown( $selected = 0 ) {
			$admin_users = MU_Support_System::get_super_admins();
			?>
				<select name="admin_user" id="admin_user">
					<option value="" <?php selected( $selected, 0 ); ?>><?php _e( 'None', INCSUB_SUPPORT_LANG_DOMAIN ); ?></option>
					<?php foreach ( $admin_users as $admin_user ): ?>
						<option value="<?php echo $admin_user; ?>" <?php selected( $selected, $admin_user ); ?>><?php echo $admin_user; ?></option>
					<?php endforeach; ?>
				</select>
			<?php
		}

	}

}
