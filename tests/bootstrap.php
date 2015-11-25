<?php

$_tests_dir = '/tmp/wordpress-tests-lib';

require_once $_tests_dir . '/includes/functions.php';

function _manually_load_plugin() {
	require dirname( __FILE__ ) . '/../incsub-support.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';


class Incsub_Support_UnitTestCase extends WP_UnitTestCase {
	function setUp() {
		parent::setUp();

		incsub_support()->activate();
	}

	function tearDown() {
		global $wpdb;
		
		parent::tearDown();

		$model = incsub_support_get_model();
		$wpdb->query( "DROP TABLE IF EXISTS $model->tickets_messages_table;" );
		$wpdb->query( "DROP TABLE IF EXISTS $model->ticketmeta;" );
		$wpdb->query( "DROP TABLE IF EXISTS $model->faq_table;" );
		$wpdb->query( "DROP TABLE IF EXISTS $model->faq_cats_table;" );
		$wpdb->query( "DROP TABLE IF EXISTS $model->tickets_table;" );
		$wpdb->query( "DROP TABLE IF EXISTS $model->tickets_cats_table;" );
		delete_site_option( 'incsub_support_version' );
		
	}
}

