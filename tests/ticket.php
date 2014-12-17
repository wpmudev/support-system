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

    function test_insert_ticket() {

        // Login a user
        $user_id = $this->factory->user->create_object(
            $this->factory->user->generate_args()
        );
        wp_set_current_user( $user_id );

        
        $args = array(
            'ticket_priority' => 2,
            'admin_id' => 1,
            'view_by_superadmin' => 1,
            'title' => 'Ticket title',
            'message' => 'Ticket message',
        );

        $time = current_time( 'mysql' );
        $ticket_id = incsub_support_insert_ticket( $args );

        $this->assertNotInstanceOf( 'WP_Error', $ticket_id );

        $ticket = incsub_support_get_ticket( $ticket_id );

        $this->assertContains( $args['message'], $ticket->message );
        $this->assertEquals( $ticket->cat_id, incsub_support_get_default_ticket_category()->cat_id );
        $this->assertEquals( $ticket->user_id, get_current_user_id() );
        $this->assertEquals( $ticket->admin_id, $args['admin_id'] );
        $this->assertEquals( $ticket->last_reply_id, 0 );
        $this->assertEquals( $ticket->ticket_status, 0 );
        $this->assertEquals( $ticket->num_replies, 0 );
        $this->assertEquals( $ticket->title, $args['title'] );
        $this->assertEquals( $ticket->ticket_opened, $time );
        $this->assertEquals( $ticket->ticket_updated, $ticket->ticket_opened );
        $this->assertEquals( $ticket->ticket_updated, $ticket->ticket_opened );
        $this->assertCount( 1, $ticket->get_replies() );

        $staff = get_userdata( $args['admin_id'] );
        $this->assertEquals( $staff->data->display_name, $ticket->get_staff_name() );
        $this->assertEquals( $staff->data->user_login, $ticket->get_staff_login() );
        $this->assertFalse( $ticket->is_closed() );
    }

    function test_insert_bad_ticket_mesage() {
        $user_id = $this->factory->user->create_object(
            $this->factory->user->generate_args()
        );
        wp_set_current_user( $user_id );

        
        $args = array(
            'title' => 'Ticket title',
            'message' => '<script>alert();</script>',
        );

        $ticket_id = incsub_support_insert_ticket( $args );
        $this->assertNotContains( incsub_support_get_ticket( $ticket_id )->message, '<script>' );

        $args = array(
            'title' => 'Ticket title',
            'message' => '<h1>alert();</h1>',
        );

        $ticket_id = incsub_support_insert_ticket( $args );
        var_dump(incsub_support_get_ticket( $ticket_id ));
    }


    function test_update_ticket() {

        $new_user_id = $this->factory->user->create_object(
            $this->factory->user->generate_args()
        );

        $args = array(
            'title' => 'Ticket title',
            'message' => 'Ticket message',
        );

        $ticket_id = incsub_support_insert_ticket( $args );

        $result = incsub_support_update_ticket( $ticket_id, array( 'user_id' => $new_user_id ) );

        $this->assertTrue( $result );

        $ticket = incsub_support_get_ticket( $ticket_id );
        $this->assertEquals( $ticket->user_id, $new_user_id );
    }

    function test_close_ticket() {
        $args = array(
            'title' => 'Ticket title',
            'message' => 'Ticket message',
        );

        $ticket_id = incsub_support_insert_ticket( $args );

        incsub_support_close_ticket( $ticket_id );

        $this->assertTrue( incsub_support_get_ticket( $ticket_id )->is_closed() );
    }

    function test_open_ticket() {
        $args = array(
            'title' => 'Ticket title',
            'message' => 'Ticket message',
        );

        $ticket_id = incsub_support_insert_ticket( $args );

        incsub_support_close_ticket( $ticket_id );

        incsub_support_open_ticket( $ticket_id );

        $this->assertFalse( incsub_support_get_ticket( $ticket_id )->is_closed() );
    }

    function test_delete_ticket() {
        $args = array(
            'title' => 'Ticket title',
            'message' => 'Ticket message',
        );

        $ticket_id = incsub_support_insert_ticket( $args );

        incsub_support_close_ticket( $ticket_id );
        $result = incsub_support_delete_ticket( $ticket_id );
        
        $this->assertTrue( $result );
        $this->assertFalse( incsub_support_get_ticket( $ticket_id ) );

        // Check that there are no replies for that ticket
        $replies = incsub_support_get_ticket_replies( $ticket_id );
        $this->assertEmpty( $replies );

    }

    function test_count_tickets() {
        $args = array(
            'ticket_priority' => 2,
            'admin_id' => 1,
            'view_by_superadmin' => 1,
            'title' => 'Ticket title',
            'message' => 'Ticket message',
        );

        
        $ticket_id_1 = incsub_support_insert_ticket( $args );
        $ticket_id_2 = incsub_support_insert_ticket( $args );

        $result = incsub_support_get_tickets_count();
        $this->assertEquals( $result, 2 );
    }



    
}
