<?php 
//This is set up with a cron job to run every other week and update it for webcrawlers, and it includes new posts.
include "core.php";

$xml = "<?xml version='1.0' encoding='UTF-8'?>\n";
$xml .= "<urlset xmlns='http://www.sitemaps.org/schemas/sitemap/0.9'>\n";

// Home page
$xml .= "<url>";
$xml .= '<loc>' . $settings['blog_site_url'] . '/</loc>';
$xml .= "<changefreq>daily</changefreq>";
$xml .= "<priority>1.0</priority>";
$xml .= "</url>\n";

// Pages from `menu` table
$stmt = $blog_pdo->query("SELECT * FROM `menu` WHERE path != 'index.php'");
while ($link = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $xml .= "<url>";
    $xml .= '<loc>' . $settings['blog_site_url'] . '/' . $link['path'] . '</loc>';
    $xml .= "<changefreq>weekly</changefreq>";
    $xml .= "<priority>0.8</priority>";
    $xml .= "</url>\n";
}

// Categories
$stmt = $blog_pdo->query("SELECT * FROM `categories`");
while ($cat = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $xml .= "<url>";
    $xml .= '<loc>' . $settings['blog_site_url'] . '/category?name=' . $cat['slug'] . '</loc>';
    $xml .= "<changefreq>weekly</changefreq>";
    $xml .= "<priority>0.7</priority>";
    $xml .= "</url>\n";
}

$xml .= "</urlset>\n";

// Save to sitemap-blog.xml
file_put_contents('sitemap-blog.xml', $xml);
?>