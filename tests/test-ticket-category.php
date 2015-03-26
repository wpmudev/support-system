<?php

/**
 * @group ticket_category
 */
class Support_Ticket_Category extends Incsub_Support_UnitTestCase {  

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
        wp_set_current_user( $user_id );

        $user_id_2 = $this->factory->user->create_object(
            $this->factory->user->generate_args()
        );

        $cat_id = incsub_support_insert_ticket_category( 'A category' );

        $cat = incsub_support_get_ticket_category( $cat_id );

        incsub_support_update_ticket_category( $cat_id, array( 'cat_name' => 'New category name', 'user_id' => $user_id_2 ) );

        $cat = incsub_support_get_ticket_category( $cat_id );

        $this->assertEquals( $cat->cat_name, 'New category name' );
        $this->assertEquals( $cat->user_id, $user_id_2 );
    }

    function test_delete_ticket_category() {
        $cat_id = incsub_support_insert_ticket_category( 'A category' );
        incsub_support_delete_ticket_category( $cat_id );

        $cat = incsub_support_get_ticket_category( $cat_id );
        $this->assertFalse( $cat );
    }

    function test_set_default_ticket_category() {
        $default = incsub_support_get_default_ticket_category();

        $cat_id = incsub_support_insert_ticket_category( 'A category' );
        
        incsub_support_set_default_ticket_category( $cat_id );

        $cat = incsub_support_get_default_ticket_category( $cat_id );
        $this->assertEquals( $cat->cat_name, 'A category' );
    }

    function test_delete_default_category() {
        $first_default_category = incsub_support_get_default_ticket_category();

        $cat_id = incsub_support_insert_ticket_category( 'A category' );

        incsub_support_set_default_ticket_category( $cat_id );

        // We cannot delete the default category
        $result = incsub_support_delete_ticket_category( $cat_id );
        $this->assertFalse( $result );
    }

    function test_get_ticket_categories() {

        $cat_id_1 = incsub_support_insert_ticket_category( 'A category 1' );
        $cat_id_2 = incsub_support_insert_ticket_category( 'A category 2' );
        $cat_id_3 = incsub_support_insert_ticket_category( 'A category 3' );

        $cats = incsub_support_get_ticket_categories();

        $this->assertCount( 4, $cats );

        incsub_support_delete_ticket_category( $cat_id_2 );

        $cats = incsub_support_get_ticket_categories();

        $this->assertCount( 3, $cats );        
    }

    function test_count_tickets_for_category() {
        $cat_id = incsub_support_insert_ticket_category( 'A category' );
        $cat = incsub_support_get_ticket_category( $cat_id );

        $args = array(
            'cat_id' => $cat_id,
            'title' => 'A ticket',
            'message' => 'A ticket message'
        );
        $ticket_1 = incsub_support_insert_ticket( $args );
        $ticket_2 = incsub_support_insert_ticket( $args );

        $this->assertEquals( 2, $cat->get_tickets_count() );

        $ticket_3 = incsub_support_insert_ticket( $args );
        $this->assertEquals( 3, $cat->get_tickets_count() );

        $new_cat_id = incsub_support_insert_ticket_category( 'A category 2' );
        $args = array( 'cat_id' => $new_cat_id );
        incsub_support_update_ticket( $ticket_3, $args );

        $this->assertEquals( 2, $cat->get_tickets_count() );

        incsub_support_delete_ticket( $ticket_1 );

        $this->assertEquals( 1, $cat->get_tickets_count() );
    }

    function test_reassign_tickets_to_default_category() {
        $cat_id = incsub_support_insert_ticket_category( 'A category' );
        $cat = incsub_support_get_ticket_category( $cat_id );

        $default_category = incsub_support_get_default_ticket_category();
        $this->assertEquals( 0, $default_category->get_tickets_count() );

        $args = array(
            'cat_id' => $cat_id,
            'title' => 'A ticket',
            'message' => 'A ticket message'
        );
        $ticket_1 = incsub_support_insert_ticket( $args );
        $ticket_2 = incsub_support_insert_ticket( $args );

        incsub_support_delete_ticket_category( $cat_id );

        $this->assertEquals( 2, $default_category->get_tickets_count() );
    }

    
}
