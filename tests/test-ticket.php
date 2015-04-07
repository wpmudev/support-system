<?php


/**
 * @group ticket
 */
class Support_Ticket extends Incsub_Support_UnitTestCase {  

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
        $ticket_id_3 = incsub_support_insert_ticket( $args );

        $result = incsub_support_get_tickets_count();
        $this->assertEquals( $result, 3 );

        incsub_support_delete_ticket( $ticket_id_2 );

        $tickets = incsub_support_get_tickets();
        $this->assertCount( 2, $tickets );

        $args = array( 'title' => 'New title' );
        incsub_support_update_ticket( $ticket_id_1, $args );

        $tickets = incsub_support_get_tickets();
        $this->assertCount( 2, $tickets );        
        $tickets = wp_list_filter( $tickets, array( 'ticket_id' => $ticket_id_1 ) );
        $new_ticket_1 = $tickets[0];

        $this->assertEquals( $new_ticket_1->title, 'New title' );
    }

    function test_user_ticket_url() {
        
        // Create a page for the front end
        $page_args = $this->factory->post->generate_args();
        $page_args['post_type'] = 'page';
        $tickets_page_id = $this->factory->post->create_object( $page_args );

        // Create a subscriber
        $subscriber_id = $this->factory->user->create_object( $this->factory->user->generate_args() );
        $admin_args = $this->factory->user->generate_args();
        $admin_args['role'] = 'administrator';
        $admin_id = $this->factory->user->create_object( $admin_args );

        $settings = incsub_support_get_settings();

        $args = array(
            'title' => 'Ticket title',
            'message' => 'Ticket message',
        );
        $ticket_id = incsub_support_insert_ticket( $args );
        $ticket = incsub_support_get_ticket( $ticket_id );

        $url = incsub_support_get_user_ticket_url( $ticket_id );

        // No frontend settings, user is not logged in
        $this->assertFalse( $url );

        // Let's login an administrator
        wp_set_current_user( $admin_id );
        $url = incsub_support_get_user_ticket_url( $ticket_id );
        $expected = add_query_arg(
            array( 
                'tid' => $ticket->ticket_id,
                'page' => 'ticket-manager',
                'action' => 'edit'
            ),
            get_admin_url( $ticket->blog_id, 'admin.php' )
        );

        $this->assertEquals( $expected, $url );

        // Let's login a subscriber. The URL should be the same
        wp_set_current_user( $subscriber_id );
        $url = incsub_support_get_user_ticket_url( $ticket_id );
        $this->assertEquals( $expected, $url );

        // Now remove the subscriber from Support roles
        $settings['incsub_support_tickets_role'] = array(
            "administrator",
            "editor",
            "author",
            "contributor"
        );
        incsub_support_update_settings( $settings );

        // URL should be false now
        $url = incsub_support_get_user_ticket_url( $ticket_id );
        $this->assertFalse( $url );


        // Set the frontend page
        $settings['incsub_support_blog_id'] = get_current_blog_id();
        $settings['incsub_support_support_page'] = $tickets_page_id;
        incsub_support_update_settings( $settings );

        // Let's login an administrator
        wp_set_current_user( $admin_id );
        $url = incsub_support_get_user_ticket_url( $ticket_id );

        $this->assertEquals( add_query_arg( 'tid', $ticket->ticket_id, get_permalink( $tickets_page_id ) ), $url );   

        // Let's login a subscriber. The URL should be false now
        wp_set_current_user( $subscriber_id );
        $url = incsub_support_get_user_ticket_url( $ticket_id );
        $this->assertFalse( $url );     

    }



    
}
