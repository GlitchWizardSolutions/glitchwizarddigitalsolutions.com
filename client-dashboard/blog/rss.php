<?php
//2025-06-25 Ready for Testing.
include_once 'assets/includes/blog-config.php';
include "core.php";
        $stmt = $blog_pdo->prepare('SELECT * FROM posts WHERE active="Yes" ORDER BY id DESC LIMIT 20');
	    $stmt->execute();
	    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

header( "Content-type: text/xml");
 
echo '<?xml version=\'1.0\' encoding=\'UTF-8\'?>
<rss version=\'2.0\'>
	<channel>
		<title>' . htmlspecialchars($settings['sitename']) . ' | RSS</title>
		<link>' . blog_site_url . '</link>
		<description>RSS Feed</description>
		<language>en-us</language>';
 
foreach ($posts as $row) {
	$title       = htmlspecialchars($post["title"]);
	$link        = blog_site_url . 'post?name=' . $post["slug"];
	$description = short_text(strip_tags(html_entity_decode($post['content'])), 100);
	$date        = $post["date"];
	$time        = $post["time"];
	$guid        = $post["id"];
	
	echo "
	<item>
		<title>$title</title>
		<link>$link</link>
		<!-- <description>$description</description>-->
		<pubDate>$date, $time</pubDate>
		<guid isPermaLink=\"false\">$guid</guid>
	</item>";
 }
 echo "</channel></rss>";
?>