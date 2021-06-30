<?php

function create_thumbnail($imageExtension, $imageDestination, $imageNewName) {
    if($imageExtension === "png") {
        $source_image = imagecreatefrompng($imageDestination);
    }
    else {
        $source_image = imagecreatefromjpeg($imageDestination);
    }

    $width = imagesx($source_image);
    $height = imagesy($source_image);
    $desired_height = 125;
    $desired_width = 200;
    $virtual_image = imagecreatetruecolor($desired_width, $desired_height);

    imagealphablending($virtual_image,false);
    imagecopyresampled($virtual_image, $source_image, 0, 0, 0, 0, $desired_width, $desired_height, $width, $height);
    imagesavealpha($virtual_image,true);

    $upload_dir = "../web/images/thumbnails/".$imageNewName;

    if($imageExtension === "png") {
        imagepng($virtual_image, $upload_dir);
    }
    else {
        imagejpeg($virtual_image, $upload_dir);
    }

    imagedestroy($virtual_image);
    imagedestroy($source_image);
}

function create_watermarked($imageExtension, $imageDestination, $imageWatermark, $imageNewName) {
    if($imageExtension === "png") {
        $source_image = imagecreatefrompng($imageDestination);
    }
    else {
        $source_image = imagecreatefromjpeg($imageDestination);
    }

    $color = imagecolorallocate($source_image,255,0,0);
    $font = 'static/css/Montserrat-Regular.ttf';

    imagettftext($source_image, 20, -45, 10, 20, $color, $font, $imageWatermark);
    imagesavealpha($source_image,true);

    $upload_dir = "../web/images/watermarked/".$imageNewName;

    if($imageExtension === "png") {
        imagepng($source_image, $upload_dir);
    }
    else {
        imagejpeg($source_image, $upload_dir);
    }

    imagedestroy($source_image);
}