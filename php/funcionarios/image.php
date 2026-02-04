<?php
// The file
$filename = (isset($_REQUEST['img'] ) ? $_REQUEST['img'] : "" );
$w = (isset($_REQUEST['w'] ) ? $_REQUEST['w'] : "100" );
$h = (isset($_REQUEST['h'] ) ? $_REQUEST['h'] : "100" );
// Content type
header('Content-type: image/jpeg');

// Get new dimensions
list($width, $height) = getimagesize($filename);
// Resample
$image_p = imagecreatetruecolor($w, $h);
$image = imagecreatefromjpeg($filename);
imagecopyresampled($image_p, $image, 0, 0, 0, 0, $w, $h, $width, $height);

// Output
imagejpeg($image_p, null, 100);
?>