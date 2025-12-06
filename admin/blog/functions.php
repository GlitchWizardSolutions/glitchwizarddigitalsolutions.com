<?php
// Blog system utility functions
// Do not output any HTML from this file - it's included by other pages

if (!function_exists('short_text')) {
    function short_text($text, $length)
    {
        $maxTextLenght = $length;
        $aspace        = " ";
        if (strlen($text) > $maxTextLenght) {
            $text = substr(trim($text), 0, $maxTextLenght);
            $text = substr($text, 0, strlen($text) - strpos(strrev($text), $aspace));
            $text = $text . '...';
        }
        return $text;
    }
}

if (!function_exists('byte_convert')) {
    function byte_convert($size)
    {
        if ($size < 1024)
            return $size . ' Byte';
        if ($size < 1048576)
            return sprintf("%4.2f KB", $size / 1024);
        if ($size < 1073741824)
            return sprintf("%4.2f MB", $size / 1048576);
        if ($size < 1099511627776)
            return sprintf("%4.2f GB", $size / 1073741824);
        else
            return sprintf("%4.2f TB", $size / 1073741824);
    }
}

if (!function_exists('post_author')) {
    function post_author($author_id)
    {
        global $blog_pdo;
        
        $author = '-';
        
        $stmt = $blog_pdo->prepare("SELECT username FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$author_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $author = $user['username'];
        }
     
        return $author;
    }
}

if (!function_exists('generateSeoURL')) {
    function generateSeoURL($string, $random_numbers = 1, $wordLimit = 8) { 
        $separator = '-'; 
         
        if($wordLimit != 0){ 
            $wordArr = explode(' ', $string); 
            $string = implode(' ', array_slice($wordArr, 0, $wordLimit)); 
        } 
     
        $quoteSeparator = preg_quote($separator, '#'); 
     
        $trans = array( 
            '&.+?;'                 => '', 
            '[^\w\d _-]'            => '', 
            '\s+'                   => $separator, 
            '('.$quoteSeparator.')+'=> $separator 
        ); 
     
        $string = strip_tags($string); 
        foreach ($trans as $key => $val){ 
            $string = preg_replace('#'.$key.'#iu', $val, $string); 
        } 
     
        $string = strtolower($string); 
        if ($random_numbers == 1) {
            $string = $string . '-' . rand(10000, 99999); 
        }
     
        return trim(trim($string, $separator)); 
    }
}

if (!function_exists('cleanup_unused_images')) {
    function cleanup_unused_images($content, $featured_image = null, $exclude_post_id = null) {
        global $blog_pdo;
        
        // Extract image URLs from content
        $images_in_content = [];
        if (!empty($content)) {
            // Find all img src attributes
            preg_match_all('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $content, $matches);
            if (!empty($matches[1])) {
                foreach ($matches[1] as $img_url) {
                    // Extract filename from URL
                    $filename = basename(parse_url($img_url, PHP_URL_PATH));
                    if (!empty($filename)) {
                        $images_in_content[] = $filename;
                    }
                }
            }
        }
        
        // Add featured image if provided
        if (!empty($featured_image)) {
            $featured_filename = basename($featured_image);
            if (!empty($featured_filename)) {
                $images_in_content[] = $featured_filename;
            }
        }
        
        // Remove duplicates
        $images_in_content = array_unique($images_in_content);
        
        // Check each image to see if it's used elsewhere
        foreach ($images_in_content as $image_filename) {
            // Check if image is used in other posts
            if ($exclude_post_id !== null) {
                $stmt = $blog_pdo->prepare("
                    SELECT COUNT(*) as usage_count FROM posts 
                    WHERE (content LIKE ? OR image LIKE ?) AND id != ?
                ");
                $stmt->execute([
                    '%' . $image_filename . '%',
                    '%' . $image_filename . '%',
                    $exclude_post_id
                ]);
            } else {
                $stmt = $blog_pdo->prepare("
                    SELECT COUNT(*) as usage_count FROM posts 
                    WHERE (content LIKE ? OR image LIKE ?)
                ");
                $stmt->execute([
                    '%' . $image_filename . '%',
                    '%' . $image_filename . '%'
                ]);
            }
            $usage = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Also check blog templates
            $stmt = $blog_pdo->prepare("
                SELECT COUNT(*) as template_usage FROM blog_templates 
                WHERE content LIKE ?
            ");
            $stmt->execute(['%' . $image_filename . '%']);
            $template_usage = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $total_usage = $usage['usage_count'] + $template_usage['template_usage'];
            
            // If image is not used anywhere else, delete it
            if ($total_usage == 0) {
                // Determine which directory the image is in
                $image_paths = [
                    '../../client-dashboard/blog/uploads/images/' . $image_filename,
                    '../../client-dashboard/blog/uploads/posts/' . $image_filename
                ];
                
                foreach ($image_paths as $image_path) {
                    if (file_exists($image_path)) {
                        unlink($image_path);
                        break; // Only delete from first matching location
                    }
                }
            }
        }
    }
}
