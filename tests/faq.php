<?php


if ( ! defined( 'WPMUDEV_DIR' ) )
    define( 'WPMUDEV_DIR', '/vagrant/www/wordpress-wpmudev/wp-content' );


$plugin_file = WPMUDEV_DIR . '/plugins/incsub-support/incsub-support.php';
if ( is_file( $plugin_file ) )
    include_once $plugin_file;

class Support_faq extends WP_UnitTestCase {  
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

    function test_insert_faq() {
        
        $args = array(
            'question' => 'The question',
            'answer' => 'The answer',
        );

        $faq_id = incsub_support_insert_faq( $args );

        $this->assertNotInstanceOf( 'WP_Error', $faq_id );

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


    



    
}
