<?php

/**
 * Support Network Main Menu
 */

if ( ! class_exists( 'MU_Support_Network_FAQ_Categories' ) ) {

	class MU_Support_Network_FAQ_Categories extends MU_Support_Menu {

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

			$this->page_title = __('FAQ Categories', INCSUB_SUPPORT_LANG_DOMAIN); 
			$this->menu_title = __('FAQ Categories', INCSUB_SUPPORT_LANG_DOMAIN); 
			$this->capability = $capability;
			$this->menu_slug = 'faq-categories';
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
			require_once( INCSUB_SUPPORT_PLUGIN_DIR . '/admin/tables/faq-categories-table.php');
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
				$cat_name = $model->get_faq_category( $cat_id );

				?>
					<form id="categories-table-form" action="" method="post">
						<table class="form-table">
							<?php
								ob_start();
							?>	
								<input type="text" name="faq_cat_name" value="<?php echo esc_attr( $cat_name['cat_name'] ); ?>">
								<input type="hidden" name="faq_cat_id" value="<?php echo esc_attr( $cat_id ); ?>">
							<?php
								$this->render_row( __( 'Category name', INCSUB_SUPPORT_LANG_DOMAIN ), ob_get_clean() );
							?>

							
						</table>
						<?php wp_nonce_field( 'edit-faq-category', '_wpnonce' ); ?>
						<?php submit_button( null, 'primary', 'submit-edit-faq-category' ); ?>
					</form>
				<?php
			}
			else {
				$cats_table = new MU_Support_FAQ_Categories_Table();
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
										<?php wp_nonce_field( 'add-faq-category' ); ?>
										<div class="form-field">
											<label for="cat_name"><?php _e( 'Category Name', INCSUB_SUPPORT_LANG_DOMAIN ); ?></label>
											<input name="cat_name" id="cat_name" type="text" value="<?php echo $category_name; ?>" size="40" aria-required="true"><br/>
											<p><?php _e('The name is used to identify the category to which FAQ question relate', INCSUB_SUPPORT_LANG_DOMAIN ); ?></p>
										</div>
										<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e( 'Add New Category', INCSUB_SUPPORT_LANG_DOMAIN ); ?>"></p>
									</form>
								</div>
							</div>
						</div>
					</div>
			    <?php
			}
		}

		public function edit_category() {
			return;
			if ( isset( $_POST['submit-edit-faq-category'] ) ) {
				if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'edit-faq-category' ) )
					wp_die( __( 'Security check error', INCSUB_SUPPORT_LANG_DOMAIN ) );

				if ( isset( $_POST['faq_cat_name'] ) && ! empty( $_POST['faq_cat_name'] ) && isset( $_POST['faq_cat_id'] ) ) {
					$model = MU_Support_System_Model::get_instance();
					$model->update_faq_category( absint( $_POST['faq_cat_id'] ), sanitize_text_field( $_POST['faq_cat_name'] ) );
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

			if ( ! wp_verify_nonce( $input['_wpnonce'], 'add-faq-category' ) )
				wp_die( __( 'Security check error', INCSUB_SUPPORT_LANG_DOMAIN ) );

			$category_name = '';
			if ( isset( $_POST['cat_name'] ) ) {
				$category_name = sanitize_text_field( $_POST['cat_name'] );
				if ( empty( $category_name ) )
					$this->add_error( 'category-name', __( 'Category name cannot be empty', INCSUB_SUPPORT_LANG_DOMAIN ) );
			}

			$model = MU_Support_System_Model::get_instance();
			$model->add_faq_category( $category_name );

			return $category_name;

		}

		

	}



}
