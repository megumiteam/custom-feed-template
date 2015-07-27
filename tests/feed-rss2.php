<?php
nocache_headers();
header('Content-Type: ' . feed_content_type('rss-http') . '; charset=' . get_option('blog_charset'), true);
echo '<?xml version="1.0" encoding="'.get_option('blog_charset').'"?'.'>'."\n"; 
?>
<feed xmlns="http://www.w3.org/2005/Atom">
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
    <content type="html" xml:lang="ja"><![CDATA[<?php the_content(); ?>]]></content>
  </entry>
<?php endwhile ; ?>
</feed>