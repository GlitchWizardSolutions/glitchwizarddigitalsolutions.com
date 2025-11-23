<?php
//This is redundant for a sitemap, likely won't be checked by crawlers since there is an xml file produced with chron.
//If this code is reused in another application that does not use auto updates, include this file.
include 'core.php';
header('Content-type: application/xml');

$stmt = $blog_pdo->query("SELECT * FROM menu WHERE path != 'index.php'");

echo "<?xml version='1.0' encoding='UTF-8'?>"."\n";
echo "<urlset xmlns='http://www.sitemaps.org/schemas/sitemap/0.9'>"."\n";

	echo "<url>";
	echo '<loc>' . $settings['site_url'] . '/</loc>';
	echo "<changefreq>always</changefreq>";
	echo "<priority>1.0</priority>";
	echo "</url>";

while($link = $stmt->fetch(PDO::FETCH_ASSOC)) {
	echo "<url>";
	echo '<loc>' . $settings['site_url'] . '/' . $link['path'] . '</loc>';
	echo "<changefreq>always</changefreq>";
	echo "<priority>1.0</priority>";
	echo "</url>";
}

$stmt_cat = $blog_pdo->query("SELECT * FROM categories");
while($cat = $stmt_cat->fetch(PDO::FETCH_ASSOC)) {
	echo "<url>";
	echo '<loc>' . $settings['site_url'] . '/category?name=' . $cat['slug'] . '</loc>';
	echo "<changefreq>always</changefreq>";
	echo "<priority>0.7</priority>";
	echo "</url>";
}

	echo "<url>";
	echo '<loc>' . $settings['site_url'] . '/login</loc>';
	echo "<changefreq>yearly</changefreq>";
	echo "<priority>0.8</priority>";
	echo "</url>";

echo "</urlset>";

?>