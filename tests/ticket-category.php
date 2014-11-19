<?php


if ( ! defined( 'WPMUDEV_DIR' ) )
    define( 'WPMUDEV_DIR', '/vagrant/www/wordpress-wpmudev/wp-content' );


$plugin_file = WPMUDEV_DIR . '/plugins/incsub-support/incsub-support.php';
if ( is_file( $plugin_file ) )
    include_once $plugin_file;

class Support_Ticket extends WP_UnitTestCase {  
	function setUp() {  

        global $plugin_file;

		parent::setUp(); 

		if ( ! file_exists( $plugin_file ) ) {
			$this->markTestSkipped( 'Support plugin is not installed.' );
		}

        $_SERVER['SERVER_NAME'] = 'example.com';
        $_SERVER['REMOTE_ADDR'] = '';

        incsub_support()->activate();
        incsub_support()->init_plugin();


    } // end setup  

    function tearDown() {
        parent::tearDown();
    }

    function test_insert_ticket_category() {

        // Login a user
        $user_id = $this->factory->user->create_object(
            $this->factory->user->generate_args()
        );
        wp_set_current_user( $user_id );

        $cat_id = incsub_support_insert_ticket_category( 'A category' );

        $cat = incsub_support_get_ticket_category( $cat_id );

        $this->assertEquals( $cat->cat_name, 'A category' );
        $this->assertEquals( $cat->user_id, get_current_user_id() );

    }

    function test_update_ticket_category() {
        $user_id = $this->factory->user->create_object(
            $this->factory->user->generate_args()
        );

        $cat_id = incsub_support_insert_ticket_category( 'A category', 1 );

        $cat = incsub_support_get_ticket_category( $cat_id );

        incsub_support_update_ticket_category( $cat_id, array( 'cat_name' => 'New category name', 'user_id' => $user_id ) );

        $cat = incsub_support_get_ticket_category( $cat_id );


        $this->assertEquals( $cat->cat_name, 'New category name' );
        $this->assertEquals( $cat->user_id, $user_id );
    }

    function test_delete_ticket_category() {
        $cat_id = incsub_support_insert_ticket_category( 'A category', 1 );
        incsub_support_delete_ticket_category( $cat_id );

        $cat = incsub_support_get_ticket_category( $cat_id );
        $this->assertFalse( $cat );
    }

    
}
