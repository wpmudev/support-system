<?php

function incsub_support_get_settings() {
	return incsub_support()->settings->get_all();
}

function incsub_support_get_setting( $name ) {
	return incsub_support()->settings->get( $name );
}

function incsub_support_get_default_settings() {
	return incsub_support()->settings->get_default_settings();
}

function incsub_support_update_setting( $name, $value ) {
	incsub_support()->settings->set( $name, $value );
}

function incsub_support_update_settings( $value ) {
	incsub_support()->settings->update( $value );
}

function incsub_support_get_support_page_url() {
	$page = incsub_support_get_support_page_id();
	if ( 'page' === get_post_type( $page ) )
		return get_permalink( $page );

	return false;
}

function incsub_support_get_faqs_page_id() {
	return apply_filters( 'support_system_faqs_page_id', incsub_support()->settings->get( 'incsub_support_faqs_page' ) );
}

function incsub_support_get_support_page_id() {
	return apply_filters( 'support_system_support_page_id', incsub_support()->settings->get( 'incsub_support_support_page' ) );	
}

function incsub_support_get_new_ticket_page_id() {
	return apply_filters( 'support_system_new_ticket_page_id', incsub_support()->settings->get( 'incsub_support_create_new_ticket_page' ) );	
}

