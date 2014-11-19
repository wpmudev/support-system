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
			$this->capability = 'read';
			$this->menu_slug = 'support-faq';
			$this->parent = $parent;
			$this->submenu = ! empty( $parent );

			parent::__construct( false );

			add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_scripts' ) );

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
				wp_register_script( 'mu-support-faq-js', INCSUB_SUPPORT_ASSETS_URL . 'js/faq.js', array(), '20130402' );
				wp_enqueue_script( 'mu-support-faq-js' );
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

			$is_search = false;

			if ( isset( $_POST['submit-faq-search'] ) && check_admin_referer( 'faq_search' ) ) {
				$is_search = true;
				$new_faq_categories = array();
				foreach ( $faq_categories as $key => $item ) {
					$answers = $model->get_faqs( $item['cat_id'], stripslashes_deep( $_POST['faq-s'] ) );
					if ( count( $answers ) > 0 ) {
						$the_faq = $item;
		            	$the_faq['answers'] = $answers;
		            	$the_faq['faqs'] = count( $answers );
		            	$new_faq_categories[] = $the_faq;
		            }
		        }

		        $index = 0;
		        $faq_categories = $new_faq_categories;
			}
			else {
		    	foreach ( $faq_categories as $key => $item ) {
		            $faq_categories[ $key ]['faqs'] = $model->get_faqs_from_cat( $item['cat_id'] );
		            $faq_categories[ $key ]['answers'] = $model->get_faqs( $item['cat_id'] );
		        }
		    }		    

	        $half_of_array = ceil( count( $faq_categories ) / 2 );

		    ?>	

		    <style>
		    	.accordion ul li {
		    		list-style: disc;
		    		margin-left:25px;
		    	}
		    	#faq-categories .faq-categories-column:first-child {
					width: 36%;
				}
				#faq-categories .faq-categories-column {
					width: 32%;
					min-width: 200px;
					float: left;
				}
				.faq-question-title {
					cursor:pointer;
					background:none;
					font-size:15px;
					font-weight:normal;
				}
		    </style>
	
		    <div id="faq-categories" class="metabox-holder">
		    	<div class="postbox">
					<h3 class="hndle"><span><?php _e( 'FAQ Categories', INCSUB_SUPPORT_LANG_DOMAIN ); ?></span></h3>
					<div class="inside">
						<div class="faq-categories-column">
							<h4><?php _e( 'Search', INCSUB_SUPPORT_LANG_DOMAIN ); ?></h4>
							<form method="post">
								<input type="text" name="faq-s" value="<?php echo isset( $_POST['faq-s'] ) ? esc_attr( stripslashes_deep( $_POST['faq-s'] ) ) : ''; ?>">
								<?php wp_nonce_field( 'faq_search' ); ?>
								<?php submit_button( __( 'Search', INCSUB_SUPPORT_LANG_DOMAIN ), 'secondary', 'submit-faq-search' ); ?>
							</form>
						</div>
						<div class="faq-categories-column">
							<ul>
								<?php for ( $i = 0; $i < $half_of_array ; $i++ ): ?>
									<li><a href="#" class="faq-category" data-cat-id="<?php echo $faq_categories[ $i ]['cat_id']; ?>"><?php echo stripslashes_deep( $faq_categories[ $i ]['cat_name'] ) . ' (' . $faq_categories[ $i ]['faqs'] . ')'; ?></a></li>
								<?php endfor; ?>
							</ul>
						</div>
						<div class="faq-categories-column">
							<ul>
								<?php for ( $i = $half_of_array; $i < count( $faq_categories ) ; $i++ ): ?>
									<li><a href="#" class="faq-category" data-cat-id="<?php echo $faq_categories[ $i ]['cat_id']; ?>"><?php echo stripslashes_deep( $faq_categories[ $i ]['cat_name'] ) . ' (' . $faq_categories[ $i ]['faqs'] . ')'; ?></a></li>
								<?php endfor; ?>
							</ul>
						</div>
						<div class="clear"></div>
					</div>
				</div>
			</div>

			<div id="faq-category-details" class="metabox-holder">
				<div class="postbox">
					<h3 class="hndle"><span><?php _e( 'Select a category', INCSUB_SUPPORT_LANG_DOMAIN ); ?></span></h3>
					<div class="inside">
						<?php foreach ( $faq_categories as $category ): ?>

							<div id="faq-category-<?php echo $category['cat_id']; ?>" class="faq-category-question">
								<?php foreach ( $category['answers'] as $faq ): ?>

					    			<?php 
					    				add_filter( 'the_content', 'wptexturize'        );
										add_filter( 'the_content', 'convert_smilies'    );
										add_filter( 'the_content', 'convert_chars'      );
										add_filter( 'the_content', 'wpautop'            );
										add_filter( 'the_content', 'shortcode_unautop'  );
										add_filter( 'the_content', 'prepend_attachment' );

										$answer = preg_replace_callback( '|^\s*(https?://[^\s"]+)\s*$|im', array( &$this, 'embed_media' ), $faq['answer'] );
					    				$answer = apply_filters( 'the_content', $answer ); 
					    				
									?>

									<h4 class="faq-question-title"><a href="#" class="faq-question-selector" data-faq-id="<?php echo $faq['faq_id']; ?>"><?php echo stripslashes_deep( $faq['question'] ); ?></a></h4>

									<div class="faq-category-answer" id="faq-answer-<?php echo $faq['faq_id']; ?>">
										<?php echo ( $answer ); ?>
										<p class="submit" data-faq-id="<?php echo $faq['faq_id']; ?>"><?php _e( 'Was this solution helpful?', INCSUB_SUPPORT_LANG_DOMAIN ); ?> 
											<?php echo '<button class="button-primary vote-button" data-vote="yes"> ' . __( 'Yes', INCSUB_SUPPORT_LANG_DOMAIN ) . '</button> <button href="#" class="button vote-button" data-vote="no"> ' . __( 'No', INCSUB_SUPPORT_LANG_DOMAIN ) . '</button>'; ?>
											<img style="display:none; margin-left:10px;vertical-align:middle" src="<?php echo INCSUB_SUPPORT_ASSETS_URL . 'images/ajax-loader.gif'; ?>">
										</p>
									</div>
								<?php endforeach; ?>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			</div>

			
			<?php
		}

		public function embed_media( $match ) {
			require_once( ABSPATH . WPINC . '/class-oembed.php' );
			$wp_oembed = _wp_oembed_get_object();

			$embed_code = $wp_oembed->get_html( $match[1] );
			return $embed_code;
		}

	}

}
