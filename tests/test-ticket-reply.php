<?php


/**
 * @group ticket_reply
 */
class Support_Ticket_Reply extends Incsub_Support_UnitTestCase {  

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

        $this->assertEquals( $ticket->last_reply_id, $reply_id );
        $this->assertEquals( $ticket->num_replies, 1 );
    }
    
}
