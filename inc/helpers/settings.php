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
	$page = incsub_support()->settings->get( 'incsub_support_support_page' );
	if ( 'page' === get_post_type( $page ) )
		return get_permalink( $page );

	return false;
}

function incsub_support_is_support_page() {
	if ( is_multisite() ) {
		$blog_id = incsub_support()->settings->get( 'incsub_support_blog_id' );
		if ( $blog_id != get_current_blog_id() )
			return false;
	}
	
	$page = incsub_support()->settings->get( 'incsub_support_support_page' );
	return get_the_ID() == $page;
}

function incsub_support_is_new_ticket_page() {
	if ( is_multisite() ) {
		$blog_id = incsub_support()->settings->get( 'incsub_support_blog_id' );
		if ( $blog_id != get_current_blog_id() )
			return false;
	}

	$page = incsub_support()->settings->get( 'incsub_support_create_new_ticket_page' );
	return get_the_ID() == $page;
}

