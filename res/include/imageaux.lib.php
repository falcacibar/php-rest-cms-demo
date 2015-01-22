<?php
/**
 * Resizes or crop an image to a custom width and height
 * 
 * @param $destiny	resource	Destiny GD resource image
 * @param $source	resource	Source GD resource image
 * @param $dw		integer		Destiny with
 * @param $dh		integer		Destiny height
 * @param $crop		boolean		if you want to crop the image
 * @return resource $destiny var is returned
 */
function imageresizeorcrop($destiny, $source, $dw, $dh, $crop=false) {
	$sw     = imagesx($source);
	$sh     = imagesy($source);
	
	$func   = ($crop) ? 'max' : 'min';
	
	$r      = $func($dw/$sw, $dh/$sh);
	$a      = (($r == $dw/$sw) ? 'w' : 'h');
	
	$pw     = $sw*$r;
	$ph     = $sh*$r;
	
	$sx     = $sy = $dx = $dy = 0;
	
	if($crop) {
		if($a == 'h')   $sx     = ceil($sw/2 - ($dw * ($sh/$dh))/2);
		else            $sy     = ceil($sh/2 - ($dh * ($sw/$dw))/2);
	} else {
		if($a == 'h')   $dx     = ceil($dw/2 - $pw/2);
		else            $dy     = ceil($dh/2 - $ph/2);
	}

	imagecopyresampled($destiny,$source,$dx,$dy,$sx,$sy,$pw,$ph,$sw,$sh);
	return $destiny;

}

/**
 * Create an image with transparent background
 * 
 * @param $width	integer Width of the image
 * @param $height	integer Height of the image
 * @return resource
 */
function imagecreatetruecoloralpha($width, $height) {
	$gdImage = imagecreatetruecolor($width, $height);
	imagealphablending( $gdImage, false );
	imagefill($gdImage, 0,0, imagecolorallocatealpha($gdImage, 0,0,0,127));
	imagesavealpha($gdImage, true);
	
	return $gdImage;
}

/**
 * Get image mime type from a image string.
 * 
 * @param $string	The image string
 * @return string	The mime type of the image
 */
function imagegetmimetypefromstring($string) {
	foreach(array(
				array("\xff\xd8\xff", 3, 'image/jpeg')
				,array("\x89PNG\x0d\x0a\x1a\x0a", 8, 'image/png')
				,array("GIF89a", 6, 'image/gif')
				,array("GIF87a", 6, 'image/gif')
	) as $id) 
		if(substr($string, 0, $id[1]) === $id[0]) return $id[2];

	return false;
}
?>