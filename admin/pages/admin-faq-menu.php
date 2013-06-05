<?php

/**
 * Support Network Main Menu
 */
if ( ! class_exists( 'MU_Support_Admin_FAQ_Menu' ) ) {

	class MU_Support_Admin_FAQ_Menu extends MU_Support_Menu {

		/**
		 * Constructor
		 * 
		 * @param Boolean	$main_menu 	If the menu has a parent or not
		 * 
		 * @since 1.8
		 */
		public function __construct( $main_menu ) {
			
			// In some cases, FAQ menu can be a main menu on the admin panel
			if ( $main_menu )
				$parent = null;
			else
				$parent = MU_Support_System::$admin_main_menu->menu_slug;

			$this->page_title = __( 'Frequently Asked Questions', INCSUB_SUPPORT_LANG_DOMAIN ); 
			$this->menu_title = __( 'FAQ', INCSUB_SUPPORT_LANG_DOMAIN); 
			$this->capability = MU_Support_System::$settings['incsub_support_faqs_role'];
			$this->menu_slug = 'support-faq';
			$this->parent = $parent;
			$this->submenu = ! empty( $parent );

			parent::__construct( false );

			add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_scripts' ) );
			add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_styles' ) );

			add_action( 'wp_ajax_vote_faq_question', array( &$this, 'vote_faq_question' ) );

		}

		/**
		 * Votes a question via AJAX
		 * 
		 * @since 1.8
		 */
		public function vote_faq_question() {
			if ( isset( $_POST['faq_id'] ) && isset( $_POST['vote'] ) && in_array( $_POST['vote'], array( 'yes', 'no' ) ) ) {
				$faq_id = absint( $_POST['faq_id'] );
				$vote = 'yes' == $_POST['vote'] ? true : false;

				$model = MU_Support_System_Model::get_instance();
				$model->vote_faq_question( $faq_id, $vote );
			}
			die();
		}

		public function enqueue_scripts( $hook ) {
			if ( $this->page_id == $hook ) {
				wp_enqueue_script( 'jquery-ui-accordion' );
				wp_enqueue_script( 'jquery-ui-tabs' );

				wp_register_script( 'mu-support-faq-js', INCSUB_SUPPORT_ASSETS_URL . 'js/faq.js', array(), '20130402' );
				wp_enqueue_script( 'mu-support-faq-js' );
			}
		}

		public function enqueue_styles( $hook ) {
			if ( $this->page_id == $hook ) {
				wp_register_style( 'mu-support-jquery-ui', INCSUB_SUPPORT_ASSETS_URL . 'css/jquery-ui/jquery-ui-1.10.2.custom.min.css', array(), '20130402' );
				wp_enqueue_style( 'mu-support-jquery-ui' );
			}
		}

		/**
		 * Renders the page contents
		 * 
		 * @since 1.8
		 */
		public function render_content() {

		    $model = MU_Support_System_Model::get_instance();
		    $faq_categories = $model->get_faq_categories();
		    ?>	

		<div id="tabs">
			<ul>
				<?php foreach ( $faq_categories as $category ): ?>
				    <li>
				    	<a href="#category-<?php echo $category['cat_id']; ?>">
				    		<span><?php echo $category['cat_name']; ?> (<?php echo sprintf( __( '%d questions', INCSUB_SUPPORT_LANG_DOMAIN ), $category['qcount'] ); ?>)</span>
					    </a>
					</li>
				<?php endforeach; ?>
			</ul>

			<?php foreach ( $faq_categories as $category ): ?>
				<?php $faqs = $model->get_faqs( $category['cat_id'] ); ?>
				<div id="category-<?php echo $category['cat_id']; ?>" class="accordion" style="margin:20px">
		    		<?php foreach ( $faqs as $faq ): ?>
		    		
			    		<h3><?php echo $faq['question']; ?></h3>
						<div>
							<?php echo $faq['answer']; ?>
							<p class="submit" data-faq-id="<?php echo $faq['faq_id']; ?>"><?php _e( 'Was this solution helpful?', INCSUB_SUPPORT_LANG_DOMAIN ); ?> 
								<?php echo '<button class="button-primary vote-button" data-vote="yes"> ' . __( 'Yes', INCSUB_SUPPORT_LANG_DOMAIN ) . '</button> <button href="#" class="button vote-button" data-vote="no"> ' . __( 'No', INCSUB_SUPPORT_LANG_DOMAIN ) . '</button>'; ?>
								<img style="display:none; margin-left:10px;vertical-align:middle" src="<?php echo INCSUB_SUPPORT_ASSETS_URL . 'images/ajax-loader.gif'; ?>">
							</p>
						</div>
					
					<?php endforeach; ?>
				</div>

			<?php endforeach; ?>
		    			 
		</div>
		<?php
		}

	}

}
