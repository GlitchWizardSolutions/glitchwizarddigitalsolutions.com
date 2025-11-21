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
