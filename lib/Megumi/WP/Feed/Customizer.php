<?php
namespace Megumi\WP\Feed;
class Custom_Feed {
	private $feed_name;
	private $categories = array();
	private $revision_key;
	private $status_key;
	private $feed_file;
	private $revision_first_value = 1;
	private $status               = array(
								'create' => 1,
								'update' => 2,
								'delete' => 3
								);

	public function __construct( $feed_name, $feed_file ) {
		$this->feed_name    = $feed_name;
		$this->revision_key = '_' . $this->feed_name . '_revision_id';
		$this->status_key   = '_' . $this->feed_name . '_feed_status';
		$this->feed_file    = $feed_file;
		
	}

	public function register() {
		add_action( 'init', array( $this, 'init') );
		register_activation_hook( __FILE__, array( $this, 'register_activation_hook' ) );
	}

	public function register_activation_hook() {
		$this->init();
		flush_rewrite_rules();
	}

	public function init() {
		add_action( 'init'               , array( $this, 'init' ) );
		add_action( 'save_post'          , array( $this, 'post_revision' ), 10, 2 );
		add_action( 'publish_post'       , array( $this, 'post_status' ) );
		add_action( 'save_post'          , array( $this, 'private_post' ), 10, 2 );
		add_action( 'wp_trash_post'      , array( $this, 'trash_feed_status' ) );
		add_action( 'pre_get_posts'      , array( $this, 'exclude_category' ) );
		add_filter( 'the_content'        , array( $this, 'strip_related_post' ) );
		add_filter( 'template_redirect'  , array( $this, 'template_redirect' ) );
		add_filter( 'query_vars'         , array( $this, 'query_vars' ) );
		
		if ( $this->get_categories() ) {
			add_action( 'add_meta_boxes', function(){
				add_meta_box(
					'Custom_feed' . $this->feed_name,
					$this->feed_name . ' Category',
					array( $this, 'add_meta_boxes' ),
					'post',
					'side'
				);
			} );
			add_action( 'save_post', array( $this, 'save_post' ) );
		}
		add_feed( $this->feed_name, array( $this, 'do_feed' ) );
	}

	public function query_vars( $vars )
	{
		$vars[] = "type";
		return $vars;
	}

	public function do_feed() {
		load_template( $this->feed_file );
		exit;
	}

	public function template_redirect() {
		if ( $this->is_custom_feed() ) {
			$this->do_feed();
		}
	}

	public function is_custom_feed() {
		if ( is_feed( 'rss2' ) && $this->feed_name === get_query_var( 'type' ) ) {
			return true;
		} else {
			return is_feed( $this->feed_name );
		}
	}

	public function post_revision( $post_id, $post ) {
		if ( $post->post_status !== 'publish' && $post->post_status !== 'private' && $post->post_status !== 'trash' ) {
			return;
		}
		$revision = get_post_meta( $post_id, $this->revision_key, true );
		if ( $revision === '' ) {
			update_post_meta( $post_id, $this->revision_key, $this->revision_first_value );
		} else {
			$revision = intval($revision);
			update_post_meta( $post_id, $this->revision_key, ++$revision );
		}
	}
	public function post_status( $post_id ) {
		$revision = get_post_meta( $post_id, $this->status_key, true );
		if ( $revision === '' ) {
			update_post_meta( $post_id, $this->status_key, $this->status['create'] );
		} else {
			update_post_meta( $post_id, $this->status_key, $this->status['update'] );
		}
	}
	public function private_post( $post_id, $post ) {
		if ( $post->post_status === 'private' ) {
			update_post_meta( $post_id, $this->status_key, $this->status['delete'] );
		}
	}
	public function trash_feed_status( $post_id ) {
		update_post_meta($post_id, $this->status_key, $this->status['delete']);
	}
	public function exclude_category( $query ) {
		if ( $query->is_main_query() && $query->is_feed( $this->feed_name ) ) {
			$cat = get_category_by_slug('pr');
			if ( $cat ) {
				$query->set( 'category__not_in', array($cat->term_id) );
			}
		}
	}
	public function strip_related_post( $content ) {
		if ( !is_admin() && is_feed( $this->feed_name ) ) {
			$content = trim(preg_replace(
				'/^(.*)<p>【関連記事】.*$/ims' ,
				'$1',
				$content
			));
			$content = trim(preg_replace(
				'/^(.*)<strong>【関連記事】.*$/ims' ,
				'$1',
				$content
			));
			$content = trim(preg_replace(
				'/^(.*)【関連記事】.*$/ims' ,
				'$1',
				$content
			));
		}
		return $content;
	}
	public function get_status($post_id) {
		$status = get_post_meta( $post_id, $this->status_key, true );
		if ( $status === '' ) {
			$post = get_post($post_id);
			if ( is_object($post) && ( $post->post_status === 'trash' || $post->post_status === 'private' ) ) {
				$status = $this->status['delete'];
			} else {
				$status = $this->status['create'];
			}
			
		}
		return $status;
	}
	public function get_revision($post_id) {
		$revision = get_post_meta( $post_id, $this->revision_key, true );
		if ( $revision === '' ) {
			$revision = $this->revision_first_value;
		}
		return $revision;
	}
	
	public function save_post( $post_id ) {

		if ( ! isset( $_POST['custom_feed_category_nonce_' . $this->feed_name] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( $_POST['custom_feed_category_nonce_' . $this->feed_name], 'custom_feed_category_' . $this->feed_name ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		if ( ! isset( $_POST['custom_feed_category_' . $this->feed_name] ) ) {
			delete_post_meta( $post_id, '_custom_feed_category_' . $this->feed_name );
			return;
		}
		update_post_meta( $post_id, '_custom_feed_category_' . $this->feed_name,  $_POST['custom_feed_category_' . $this->feed_name] );
	}

	public function add_meta_boxes( $post ) {
		wp_nonce_field( 'custom_feed_category_' . $this->feed_name, 'custom_feed_category_nonce_' . $this->feed_name );
		$value = get_post_meta( $post->ID, '_custom_feed_category_' . $this->feed_name, true );

		echo '<ul>';
		foreach ( $this->get_categories() as $key => $cat ) {
			printf(
				'<li><label><input type="checkbox" name="%1$s[]" value="%2$s" %4$s /> %3$s</label></li>',
				esc_attr('custom_feed_category_' . $this->feed_name),
				esc_attr($key),
				esc_html($cat),
				( is_array($value) && in_array( $key, $value ) ) ? 'checked="checked"' : ''
			);
		}
		echo '</ul>';
	}

	public function set_categories( $categories ) {
		$this->categories = $categories;
	}

	public function get_categories() {
		return $this->categories;
	}
}