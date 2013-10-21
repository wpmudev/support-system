<?php

class MU_Support_System_Model_Loader {

	static $instance;

	private $ticket;
	private $faq;

	public static function get_instance() {
		if ( empty( self::$instance ) )
			self::$instance = new self();
		return self::$instance;
	}

	public function __construct() {
		$this->ticket = MU_Support_System_Ticket_Model::get_instance();
		$this->faq = MU_Support_System_Faq_Model::get_instance();
	}

	public function load( $model ) {
		$model_instance = $this->$model;
		return $model_instance;
	}
}