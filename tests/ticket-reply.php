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

    function test_insert_ticket_reply() {

        // Login a user
        $user_id = $this->factory->user->create_object(
            $this->factory->user->generate_args()
        );

        $args = array(
            'title' => 'Ticket title',
            'message' => 'Ticket message',
        );

        $ticket_id = incsub_support_insert_ticket( $args );

        $reply_args = array(
            'poster_id' => $user_id,
            'message' => 'This is a ticket reply',
        );
        $reply_id = incsub_support_insert_ticket_reply( $ticket_id, $reply_args );
        
        $ticket = incsub_support_get_ticket( $ticket_id );

        $this->assertEquals( $ticket->last_reply_id, $reply_id );
        $this->assertEquals( $ticket->num_replies, 1 );

        $reply = incsub_support_get_ticket_reply( $reply_id );
        $this->assertEquals( $reply->get_poster_id(), $reply_args['poster_id'] );
        $this->assertContains( $reply_args['message'], $reply->message );
    }

    function test_delete_ticket_reply() {
        $args = array(
            'title' => 'Ticket title',
            'message' => 'Ticket message',
        );

        $ticket_id = incsub_support_insert_ticket( $args );

        $reply_args = array(
            'message' => 'This is a ticket reply',
        );
        $reply_id = incsub_support_insert_ticket_reply( $ticket_id, $reply_args );

        $result = incsub_support_delete_ticket_reply( $reply_id );
        $this->assertTrue( $result );

        $ticket = incsub_support_get_ticket( $ticket_id );

        $this->assertEquals( $ticket->last_reply_id, 0 );
        $this->assertEquals( $ticket->num_replies, 0 );
    }
    
}
