<?php

/**
 * @group faq_category
 */
class Support_Faq_Category extends Incsub_Support_UnitTestCase {  

    function test_insert_faq_category() {

        $cat_id = incsub_support_insert_faq_category( 'A category' );

        $cat = incsub_support_get_faq_category( $cat_id );

        $this->assertEquals( $cat->cat_name, 'A category' );

    }

    function test_update_faq_category() {

        $cat_id = incsub_support_insert_faq_category( 'A category' );

        $cat = incsub_support_get_faq_category( $cat_id );

        incsub_support_update_faq_category( $cat_id, array( 'cat_name' => 'New category name' ) );

        $cat = incsub_support_get_faq_category( $cat_id );

        $this->assertEquals( $cat->cat_name, 'New category name' );
    }

    function test_delete_faq_category() {
        $cat_id = incsub_support_insert_faq_category( 'A category' );
        incsub_support_delete_faq_category( $cat_id );

        $cat = incsub_support_get_faq_category( $cat_id );
        $this->assertFalse( $cat );
    }

    function test_set_default_faq_category() {
        $default = incsub_support_get_default_faq_category();

        $cat_id = incsub_support_insert_faq_category( 'A category' );
        
        incsub_support_set_default_faq_category( $cat_id );

        $cat = incsub_support_get_default_faq_category( $cat_id );
        $this->assertEquals( $cat->cat_name, 'A category' );
    }

    function test_delete_default_category() {
        $first_default_category = incsub_support_get_default_faq_category();

        $cat_id = incsub_support_insert_faq_category( 'A category' );

        incsub_support_set_default_faq_category( $cat_id );

        // We cannot delete the default category
        $result = incsub_support_delete_faq_category( $cat_id );
        $this->assertFalse( $result );
    }


    function test_get_faq_categories() {

        $cat_id_1 = incsub_support_insert_faq_category( 'A category 1' );
        $cat_id_2 = incsub_support_insert_faq_category( 'A category 2' );
        $cat_id_3 = incsub_support_insert_faq_category( 'A category 3' );

        $cats = incsub_support_get_faq_categories();

        $this->assertCount( 4, $cats );

        incsub_support_delete_faq_category( $cat_id_2 );

        $cats = incsub_support_get_faq_categories();

        $this->assertCount( 3, $cats );        
    }

    function test_count_faqs_for_category() {
        $cat_id = incsub_support_insert_faq_category( 'A category' );
        $cat = incsub_support_get_faq_category( $cat_id );

        $args = array(
            'cat_id' => $cat_id,
            'question' => 'A question',
            'answer' => 'An answer'
        );
        $faq_1 = incsub_support_insert_faq( $args );
        $faq_2 = incsub_support_insert_faq( $args );

        $this->assertEquals( 2, $cat->get_faqs_count() );

        $faq_3 = incsub_support_insert_faq( $args );
        $this->assertEquals( 3, $cat->get_faqs_count() );

        $new_cat_id = incsub_support_insert_faq_category( 'A category 2' );
        $args = array( 'cat_id' => $new_cat_id );
        incsub_support_update_faq( $faq_3, $args );

        $this->assertEquals( 2, $cat->get_faqs_count() );

        incsub_support_delete_faq( $faq_1 );

        $this->assertEquals( 1, $cat->get_faqs_count() );
    }

    function test_reassign_faqs_to_default_category() {
        $cat_id = incsub_support_insert_faq_category( 'A category' );
        $cat = incsub_support_get_faq_category( $cat_id );

        $default_category = incsub_support_get_default_faq_category();
        $this->assertEquals( 0, $default_category->get_faqs_count() );

        $args = array(
            'cat_id' => $cat_id,
            'question' => 'A question',
            'answer' => 'An answer'
        );
        $faq_1 = incsub_support_insert_faq( $args );
        $faq_2 = incsub_support_insert_faq( $args );

        incsub_support_delete_faq_category( $cat_id );

        $this->assertEquals( 2, $default_category->get_faqs_count() );
    }

    function test_insert_duplicated_faq_category() {
        $cat_id = incsub_support_insert_faq_category( 'A category' );
        $cat_id = incsub_support_insert_faq_category( 'A category' );

        $this->assertFalse( $cat_id );
    }

    
}
