<?php

class Incsub_Support_Admin_Ajax {
	public function __construct() {
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

			incsub_support_vote_faq( $faq_id, $vote );
		}
		die();
	}
}

new Incsub_Support_Admin_Ajax();
