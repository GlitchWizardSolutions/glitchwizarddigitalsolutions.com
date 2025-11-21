<?php
include_once 'assets/includes/process-config.php';
// The GD extension is required for this captcha to work. Please uncomment "extension=php_gd2.dll" or "extension=gd" line in your php.ini file.
// Generate random 6 character string. Feel free to remove the numbers (or characters) if the captcha is too hard to read.
$captcha_code = strtoupper(substr(str_shuffle('012345678901234567801234567890123456789'), 0, 5));
// Update the session variable
$_SESSION['captcha'] = $captcha_code;
// Create the image canvas - width: 150px; height: 50px;
$final_image = imagecreate(150, 50);
// Background color (RGBA)
$rgba = [241, 246, 249, 0];
// Set the background color
$image_bg_color = imagecolorallocatealpha($final_image, $rgba[0], $rgba[1], $rgba[2], $rgba[3]);
// Convert the captcha text to an array
$captcha_code_chars = str_split($captcha_code);
// Iterate the above array
for($i = 0; $i < count($captcha_code_chars); $i++) {
    // Create the character image canvas
    $char_small = imagecreate(130, 16);
    $char_large = imagecreate(130, 16);
    // Character background color
    $char_bg_color = imagecolorallocate($char_small, $rgba[0], $rgba[1], $rgba[2]);
    // Character color
    $char_color = imagecolorallocate($char_small, rand(80,180), rand(80,180), rand(80, 180));
    // Draw the character on the canvas
    imagestring($char_small, 1, 1, 0, $captcha_code_chars[$i], $char_color);
    // Copy the image and enlarge it
    imagecopyresampled($char_large, $char_small, 0, 0, 0, 0, rand(250, 350), 16, 84, 8);
    // Rotate the character image
    $char_large = imagerotate($char_large, rand(-3,3), 0);
    // Add the character image to the main canvas
    imagecopymerge($final_image, $char_large, 20 + (24 * $i), 15, 0, 0, imagesx($char_large), imagesy($char_large), 70);
    // Destroy temporary canvases
    imagedestroy($char_small);
    imagedestroy($char_large);
}
// Output the created image
header('Content-type: image/png');
imagepng($final_image);
imagedestroy($final_image);
?>