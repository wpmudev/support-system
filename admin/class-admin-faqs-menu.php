<?php

class Incsub_Support_Admin_FAQ_Menu extends Incsub_Support_Admin_Menu {
	public function __construct( $slug, $network = false ) {
		parent::__construct( $slug, $network );
	}


	public function add_menu() {		
		parent::add_submenu_page(
			'ticket-manager-b',
			__( 'FAQ', INCSUB_SUPPORT_LANG_DOMAIN ),
			__( 'Frequently Asked Questions', INCSUB_SUPPORT_LANG_DOMAIN ), 
			'manage_options'
		);

		add_action( 'load-' . $this->page_id, array( $this, 'set_filters' ) );

	}

	public function set_filters() {
		add_action( 'wp_ajax_vote_faq_question', array( &$this, 'vote_faq_question' ) );
		add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_scripts' ) );
	}

	public function enqueue_scripts( $hook ) {
		wp_enqueue_script( 'mu-support-faq-js', INCSUB_SUPPORT_PLUGIN_URL . '/admin/assets/js/faq.js', array(), '20130402' );
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

			incsub_support_vote_faq( $faq_id );
		}
		die();
	}

	public function render_inner_page() {
		$faq_categories = incsub_support_get_faq_categories();

		if ( isset( $_POST['submit-faq-search'] ) && check_admin_referer( 'faq_search' ) ) {
			$new_faq_categories = array();
			foreach ( $faq_categories as $key => $item ) {
				$answers = incsub_support_get_faqs( array( 's' => $_POST['faq-s'], 'per_page' => -1 ) );
				if ( count( $answers ) > 0 ) {
					$the_faq = $item;
	            	$the_faq->answers = $answers;
	            	$the_faq->faqs = count( $answers );
	            	$new_faq_categories[] = $the_faq;
	            }
	        }

	        $index = 0;
	        $faq_categories = $new_faq_categories;
		}
		else {
	    	foreach ( $faq_categories as $key => $item ) {
	            $faq_categories[ $key ]->faqs = incsub_support_count_faqs_on_category( $item->cat_id );
	            $faq_categories[ $key ]->answers = incsub_support_get_faqs( array( 'cat_id' => $item->cat_id ) );
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
								<li><a href="#" class="faq-category" data-cat-id="<?php echo $faq_categories[ $i ]->cat_id; ?>"><?php echo $faq_categories[ $i ]->cat_name . ' (' . $faq_categories[ $i ]->faqs . ')'; ?></a></li>
							<?php endfor; ?>
						</ul>
					</div>
					<div class="faq-categories-column">
						<ul>
							<?php for ( $i = $half_of_array; $i < count( $faq_categories ) ; $i++ ): ?>
								<li><a href="#" class="faq-category" data-cat-id="<?php echo $faq_categories[ $i ]->cat_id; ?>"><?php echo $faq_categories[ $i ]->cat_name . ' (' . $faq_categories[ $i ]->faqs . ')'; ?></a></li>
							<?php endfor; ?>
						</ul>
					</div>
					<div class="clear"></div>
				</div>
			</div>
		</div>

		<div id="faq-category-details" class="metabox-holder">
			<div class="postbox">
				<h3 class="hndle"><span><?php _e( 'Select a question', INCSUB_SUPPORT_LANG_DOMAIN ); ?></span></h3>
				<div class="inside">
					<?php foreach ( $faq_categories as $category ): ?>

						<div id="faq-category-<?php echo $category->cat_id; ?>" class="faq-category-question">

							<?php foreach ( $category->answers as $faq ): ?>

				    			<?php 
				    				add_filter( 'the_content', 'wptexturize'        );
									add_filter( 'the_content', 'convert_smilies'    );
									add_filter( 'the_content', 'convert_chars'      );
									add_filter( 'the_content', 'wpautop'            );
									add_filter( 'the_content', 'shortcode_unautop'  );
									add_filter( 'the_content', 'prepend_attachment' );

									$answer = preg_replace_callback( '|^\s*(https?://[^\s"]+)\s*$|im', array( &$this, 'embed_media' ), $faq->answer );
				    				$answer = apply_filters( 'the_content', $answer ); 
								?>

								<h4 class="faq-question-title"><a href="#" class="faq-question-selector" data-faq-id="<?php echo $faq->faq_id; ?>"><?php echo stripslashes_deep( $faq->question ); ?></a></h4>

								<div class="faq-category-answer" id="faq-answer-<?php echo $faq->faq_id; ?>">
									<?php echo ( $answer ); ?>
									<p class="submit" data-faq-id="<?php echo $faq->faq_id; ?>"><?php _e( 'Was this solution helpful?', INCSUB_SUPPORT_LANG_DOMAIN ); ?> 
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