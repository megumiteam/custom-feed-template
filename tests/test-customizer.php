<?php

use MegumiLib\WP\Feed;

class Customizer_Test extends WP_UnitTestCase {

	private $feed;
	private $feed_name = 'unittest';

	public function setUp() {
		parent::setUp();
		global $wp_rewrite;
		$wp_rewrite->init();
		$wp_rewrite->set_permalink_structure('/archives/%post_id%');

		$this->feed = new MegumiLib\WP\Feed\Customizer( $this->feed_name, dirname(__FILE__) . '/feed-rss2.php' );
		$this->feed->register_activation_hook();
		$this->feed->register();		
	}
	
    /**
	 * @test
	 */
	public function permalink_structure()
	{
		$this->go_to( '/' );
		$this->assertSame( false, is_feed() );
		$this->assertSame( false, $this->feed->is_custom_feed() );
		$this->go_to( '/feed/?type=' . $this->feed_name );
		$this->assertQueryTrue( 'is_feed' );
		$this->assertSame( true, $this->feed->is_custom_feed() );
		$this->go_to( '/feed/' );
		$this->assertQueryTrue( 'is_feed' );
		$this->assertSame( false, $this->feed->is_custom_feed() );
		$this->go_to( '/feed/?type=' . $this->feed_name );
		$this->assertSame( true, $this->feed->is_custom_feed() );
	}

    /**
     * @test
     * 記事内の【関連記事】以下を削除するテスト
     */
	function delete_related_post() {

		$content = '<p>pretext</p>';
		$content .= '<p>【関連記事】</p>';
		$content .= '<p>aftertext</p>';
		$args = array( 'post_content' => $content );
		$post_id = $this->factory->post->create( $args );
		
		$this->go_to( '/feed/?type=' . $this->feed_name );
		while( have_posts() ) {
			the_post();
			the_content();
		}
		
		$this->expectOutputString( '<p>pretext</p>' );
	}
    /**
     * @test
     * PRカテゴリが配信されていないかのテスト
     */
	function strip_pr_category() {
		$cat_id  = $this->factory->category->create( array('slug' => 'pr') );
		$post_ids = $this->factory->post->create_many( 5 );
		$this->factory->post->create_many( 5, array( 'post_category' => array( $cat_id ) ) );
		
		$this->go_to( '/feed/?type=' . $this->feed_name );
		
		$roop_post_id = array();
		while( have_posts() ) {
			the_post();
			$roop_post_id[] = get_the_ID();
		}

		$this->assertEquals(array_multisort($post_ids), array_multisort($roop_post_id));
		$this->assertEquals(count($post_ids), count($roop_post_id));
	}
    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * feed-rss2.phpにてPHPエラーが発生していないかテスト
     */
	 function error_check() {
		$post_ids = $this->factory->post->create_many( 5 );
		
		$this->go_to( '/feed/?type=' . $this->feed_name );
		
		require_once( dirname(__FILE__) . '/feed-rss2.php' );
	 }

}

