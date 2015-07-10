<?php

/**
 * @group integration
 */
class Support_Integration extends Incsub_Support_UnitTestCase {

	function setUp() {
		include_once( 'lib/pro-sites.php' );
		parent::setUp();
	}

	function test_pro_sites_integration() {
		do_action( 'plugins_loaded' );
		$plugin = incsub_support();
		$found = false;
		foreach ( $plugin->integrators as $integrator )  {
			if ( $integrator instanceof Support_System_Pro_Sites_Integration )
				$found = true;
		}

		$this->assertTrue( $found );
	}

}
