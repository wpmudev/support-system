<?php

function incsub_support_get_model() {
	return MU_Support_System_Model::get_instance();
}

function incsub_support_load_model( $model ) {
	$loader = MU_Support_System_Model_Loader::get_instance();
	return $loader->load( $model );
}

function incsub_support_get_ticket_model() {
	return incsub_support_load_model( 'ticket' );
}

function incsub_support_get_faq_model() {
	return incsub_support_load_model( 'faq' );
}
