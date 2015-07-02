<?php

/**
 * @group faq
 */
class Support_Faq extends Incsub_Support_UnitTestCase {  	

    function test_insert_faq() {
        
        $args = array(
            'question' => 'The question',
            'answer' => 'The answer',
        );

        $faq_id = incsub_support_insert_faq( $args );
        $faq = incsub_support_get_faq( $faq_id );

        $this->assertEquals( $faq->question, $args['question'] );
        $this->assertEquals( $faq->answer, $args['answer'] );
        $this->assertEquals( $faq->help_views, 0 );
        $this->assertEquals( $faq->help_count, 0 );
        $this->assertEquals( $faq->help_yes, 0 );
        $this->assertEquals( $faq->help_no, 0 );
    }

    function test_update_faq() {
        $args = array(
            'question' => 'The question',
            'answer' => 'The answer',
        );

        $faq_id = incsub_support_insert_faq( $args );

        $new_answer = "The new answer";

        incsub_support_update_faq( $faq_id, array( 'answer' => $new_answer ) );

        $faq = incsub_support_get_faq( $faq_id );

        $this->assertEquals( $faq->answer, $new_answer );
    }

    function test_vote_faq() {
        $args = array(
            'question' => 'The question',
            'answer' => 'The answer',
        );

        $faq_id = incsub_support_insert_faq( $args );

        incsub_support_vote_faq( $faq_id, true );

        $faq = incsub_support_get_faq( $faq_id );
        $this->assertEquals( $faq->help_yes, 1 );
        $this->assertEquals( $faq->help_no, 0 );

        incsub_support_vote_faq( $faq_id, true );

        $faq = incsub_support_get_faq( $faq_id );
        $this->assertEquals( $faq->help_yes, 2 );
        $this->assertEquals( $faq->help_no, 0 );

        incsub_support_vote_faq( $faq_id, false );

        $faq = incsub_support_get_faq( $faq_id );
        $this->assertEquals( $faq->help_yes, 2 );
        $this->assertEquals( $faq->help_no, 1 );
    }

    function test_delete_faq() {
        $args = array(
            'question' => 'The question',
            'answer' => 'The answer',
        );

        $faq_id = incsub_support_insert_faq( $args );

        incsub_support_delete_faq( $faq_id );

        $this->assertFalse( incsub_support_get_faq( $faq_id ) );
    }

    function test_get_faqs() {
        $args = array(
            'question' => 'The question',
            'answer' => 'The answer',
        );
        $faq_id_1 = incsub_support_insert_faq( $args );
        $faq_id_2 = incsub_support_insert_faq( $args );
        $faq_id_3 = incsub_support_insert_faq( $args );

        $faq_1 = incsub_support_get_faq( $faq_id_1 );

        $faqs = incsub_support_get_faqs();
        $this->assertCount( 3, $faqs );
        
        incsub_support_delete_faq( $faq_id_2 );

        $faqs = incsub_support_get_faqs();
        $this->assertCount( 2, $faqs );

        $args = array( 'answer' => 'New answer' );
        incsub_support_update_faq( $faq_id_1, $args );

        $faqs = incsub_support_get_faqs();
        $this->assertCount( 2, $faqs );        
        $faqs = wp_list_filter( $faqs, array( 'faq_id' => $faq_id_1 ) );
        $new_faq_1 = $faqs[0];

        $this->assertEquals( $new_faq_1->answer, 'New answer' );
    }

    function test_search_faqs() {
        $args = array(
            'question' => 'The question',
            'answer' => 'The answer',
        );

        $faq_id = incsub_support_insert_faq( $args );

        $args = array(
            's' => 'qqqqq'
        );
        $faqs = incsub_support_get_faqs( $args );
        $this->assertEmpty( $faqs );

        $args = array(
            's' => 'an'
        );
        $faqs = incsub_support_get_faqs( $args );
        $this->assertCount( 1, $faqs );
    }

    function test_get_faqs_by_category() {
        $cat_id_1 = incsub_support_insert_faq_category( 'A category 1' );
        $cat_id_2 = incsub_support_insert_faq_category( 'A category 2' );

        $args = array(
            'question' => 'The question',
            'answer' => 'The answer',
            'cat_id' => $cat_id_1
        );

        $faq_id_1 = incsub_support_insert_faq( $args );

        $args = array(
            'question' => 'The question 2',
            'answer' => 'The answer 2',
            'cat_id' => $cat_id_1
        );

        $faq_id_2 = incsub_support_insert_faq( $args );

        $args = array(
            'question' => 'The question 3',
            'answer' => 'The answer 3',
            'cat_id' => $cat_id_2
        );

        $faq_id_3= incsub_support_insert_faq( $args );

        $args = array(
            'question' => 'The question 4',
            'answer' => 'The answer 4'
        );

        $faq_id_4= incsub_support_insert_faq( $args );

        $faqs = incsub_support_get_faqs( array( 'category' => $cat_id_1 ) );
        $this->assertCount( 2, $faqs );

        $faqs = incsub_support_get_faqs( array( 'category' => $cat_id_2 ) );
        $this->assertCount( 1, $faqs );
    }
    
}
