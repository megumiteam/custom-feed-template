<?php
use Megumi\WP\Feed;

class Helper_Test extends WP_UnitTestCase {

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
	public function permalink_structure() {
		$this->assertQueryTrue( 'true' );
	}
}