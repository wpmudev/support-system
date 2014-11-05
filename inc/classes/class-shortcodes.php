<?php

class Incsub_Support_Shortcodes {

	public $shortcodes = array();

	public function __construct() {
		$this->init();
		$this->register_shortcodes();
	}

	private function init() {
		$this->shortcodes = apply_filters( 'support_system_shortccodes', array(
			'support-system-tickets-index' => array( $this, 'render_tickets_index' )
		) );
	}

	public function register_shortcodes() {
		foreach ( $this->shortcodes as $shortcode => $function ) {
			add_shortcode( $shortcode, $function );
		}
	}

	private function start() {
		incsub_support()->query->query();
		echo '<div class="support-system">';
		ob_start();
	}

	public function render_tickets_index() {

		$this->start();
		
		if ( ! incsub_support_current_user_can( 'read_ticket' ) )
			return $this->end();

		if ( incsub_support_is_tickets_index() )
			incsub_support_get_template( 'index', 'tickets' );

		if ( incsub_support_is_single_ticket() )
			incsub_support_get_template( 'single', 'ticket' );

		return $this->end();
	}

	private function end() {
		echo '</div><div style="clear:both"></div>';
		return ob_get_clean();
	}
}