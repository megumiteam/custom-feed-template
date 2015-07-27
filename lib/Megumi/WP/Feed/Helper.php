<?php
namespace Megumi\WP\Feed;
class Helper {
	private static $instance = null;

	private final function __clone() {}
	
	public static function get_instance() {
		if(is_null(self::$instance)) {
			self::$instance = new self;
		}
		return self::$instance;
	}
	
	private final function __construct() {
	}

	public function get_related_post($post_id, $num=5){
		$the_list = array();
		if ( function_exists('sirp_get_related_posts_id') ) {
			$relatedposts = sirp_get_related_posts_id($num);
			if ( !is_array($relatedposts) || empty($relatedposts))
				return;
			$the_list = array();
			foreach ( $relatedposts as $relatedpost ) {
				$the_list[] = $relatedpost['ID'];
			}
		} elseif ( function_exists('st_get_related_posts') ) {
			$relatedposts = st_get_related_posts('post_id='.intval($post_id).'&number='.$num.'&format=array');
			if ( !is_array($relatedposts) || empty($relatedposts))
				return;
			$the_list = array();			
			foreach ( $relatedposts as $relatedpost ) {
				$the_list[] = $relatedpost->ID;
			}
		} else {
			return;
		}
		return $the_list;
	}

	public function date_iso8601($time) {
		$date = sprintf(
			'%1$sT%2$s',
			mysql2date('Y-m-d', $time, false),
			mysql2date('H:i:s+09:00', $time, false)
		);
		return $date;
	}

	public function get_enclosure_url($post_id){
		$images = wp_get_attachment_url(get_post_thumbnail_id($post_id));
		$the_list = '';
		if (empty($images)) {
			return $the_list;
		}
		return $images;
	}

	public function get_category_rss($post_id, $num=1) {
		$categories = get_the_category($post_id);
		$the_list = array();
		$cnt = 0;
		if ( !empty($categories) ) foreach ( (array) $categories as $category ) {
			if ( $cnt >= $num ) {
				break;
			}
			
			$args = array(
						'slug' => $category->slug,
						'name' => $category->name
					);
			$the_list[] = $args;
			$cnt++;
		}
		
		return $the_list;
	}

	public function get_tag_rss($post_id, $num=1) {
		$tags = get_the_tags($post_id);
		$the_list = array();
		$cnt = 0;
		if ( !empty($tags) ) foreach ( (array) $tags as $tag ) {
			if ( $cnt >= $num ) {
				break;
			}

			$args = array(
						'slug' => $tag->slug,
						'name' => $tag->name
					);
			$the_list[] = $args;
			$cnt++;
		}
		
		return $the_list;
	}

	public function get_custom_category($feed_name, $post_id, $num=1) {
		$categories = get_post_meta( $post_id, '_custom_feed_category_' . $feed_name, true );
		$the_list = array();
		$cnt = 0;
		if ( !empty($categories) ) foreach ( (array) $categories as $category ) {
			if ( $cnt >= $num ) {
				break;
			}

			$the_list[] = $category;
			$cnt++;
		}
		
		return $the_list;
	}
}