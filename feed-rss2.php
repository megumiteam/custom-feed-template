<?php
nocache_headers();
header('Content-Type: ' . feed_content_type('rss-http') . '; charset=' . get_option('blog_charset'), true);
echo '<?xml version="1.0" encoding="'.get_option('blog_charset').'"?'.'>'."\n"; 
?>
<feed xmlns="http://www.w3.org/2005/Atom">
  <title><?php bloginfo_rss('name'); ?></title>
  <link rel="alternate" type="text/html" href="<?php bloginfo_rss('url') ?>"/>
  <link rel="self" type="application/atom+xml" href="<?php echo home_url( '/feed?type=yahoo-manabi' ) ?>"/>
  <updated><?php echo Megumi\WP\Feed\Helper::get_instance()->date_iso8601(get_lastpostmodified('blog')); ?></updated>
<?php
while (have_posts()) :
	the_post();
	$post_id = intval(get_the_ID());
	$pub_date = get_post_time('Y-m-d H:i:s', false);
	$mod_date = get_post_modified_time('Y-m-d H:i:s', false);
?>
  <entry>
    <title><![CDATA[ <?php the_title_rss(); ?> ]]></title>
    <link rel="alternate" type="text/html" href="<?php the_permalink(); ?>"/>
    <id><?php the_ID(); ?></id>
    <published><?php echo Megumi\WP\Feed\Helper::get_instance()->date_iso8601($pub_date); ?></published>
    <updated><?php echo Megumi\WP\Feed\Helper::get_instance()->date_iso8601($mod_date); ?></updated>
    <summary><![CDATA[ <?php the_excerpt(); ?> ]]></summary>
    <image><?php echo Megumi\WP\Feed\Helper::get_instance()->get_enclosure_url($post_id); ?></image>
    <?php
    	$custom_categories = Megumi\WP\Feed\Helper::get_instance()->get_custom_category('yahoo-manabi', $post_id, 4);
    	if ( !empty($custom_categories) && is_array($custom_categories) ) :
    		foreach ( $custom_categories as $custom_category ) :
    ?>
    <category term="<?php echo $custom_category; ?>"/>
    <?php
    		endforeach;
    	endif;

    	$categories = Megumi\WP\Feed\Helper::get_instance()->get_category_rss($post_id, 1);
    	if ( !empty($categories) && is_array($categories) ) :
    ?>
    <theme term="<?php echo $categories[0]['name']; ?>"/>
    <?php 
    	endif;

    	$tags = Megumi\WP\Feed\Helper::get_instance()->get_tag_rss($post_id, 3);
    	if ( !empty($tags) && is_array($tags) ) :
    		foreach ( $tags as $tag ) :
    ?>
    <keyword term="<?php echo $tag['name']; ?>"/>
    <?php
    		endforeach;
    	endif;
    ?>
    <content type="html" xml:lang="ja"><![CDATA[<?php the_content(); ?>]]></content>
  </entry>
<?php endwhile ; ?>
</feed>