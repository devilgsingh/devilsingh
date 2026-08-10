<?php
session_start();
header("Content-type: image/png");

// Generate a random verification code
$rno = rand(1000, 99999);
$_SESSION['ckey'] = md5($rno);

// Simple generated background instead of relying on an external bg image file
$width = 150;
$height = 40;
$img = imagecreatetruecolor($width, $height);

$bg = imagecolorallocate($img, 245, 245, 245);
$noise = imagecolorallocate($img, 210, 210, 210);
$text_color = imagecolorallocate($img, 30, 30, 30);

imagefilledrectangle($img, 0, 0, $width, $height, $bg);

// Add some noise dots so it's not just plain text on white
for ($i = 0; $i < 120; $i++) {
    imagesetpixel($img, rand(0, $width - 1), rand(0, $height - 1), $noise);
}

imagestring($img, 5, 20, 13, $rno, $text_color);

imagepng($img);
imagedestroy($img);
