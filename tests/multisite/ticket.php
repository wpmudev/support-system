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

        // Create a new blog
        $blog_id = $this->factory->blog->create_object(
            $this->factory->blog->generate_args()
        );

        switch_to_blog( $blog_id );

        $args = array(
            'ticket_priority' => 2,
            'admin_id' => 1,
            'site_id' => 2,
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
        $this->assertEquals( $ticket->blog_id, $blog_id );
        $this->assertEquals( $ticket->site_id, $args['site_id'] );
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

        restore_current_blog();
    }

    
}
