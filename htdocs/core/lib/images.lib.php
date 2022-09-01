<?php
/* Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2007 Regis Houssin        <regis.houssin@inodbox.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 * or see https://www.gnu.org/
 */

/**
 *  \file		htdocs/core/lib/images.lib.php
 *  \brief		Set of function for manipulating images
 */

// Define size of logo small and mini
$maxwidthsmall = 480;
$maxheightsmall = 270; // Near 16/9eme
$maxwidthmini = 128;
$maxheightmini = 72; // 16/9eme
$quality = 80;


/**
 *      Return if a filename is file name of a supported image format
 *
 *      @param	int		$acceptsvg	0=Default (depends on setup), 1=Always accept SVG as image files
 *      @return string				Return list fo image format
 */
function getListOfPossibleImageExt($acceptsvg = 0)
{
	global $conf;

	$regeximgext = '\.gif|\.jpg|\.jpeg|\.png|\.bmp|\.webp|\.xpm|\.xbm'; // See also into product.class.php
	if ($acceptsvg || !empty($conf->global->MAIN_ALLOW_SVG_FILES_AS_IMAGES)) {
		$regeximgext .= '|\.svg'; // Not allowed by default. SVG can contains javascript
	}

	return $regeximgext;
}

/**
 *      Return if a filename is file name of a supported image format
 *
 *      @param	string	$file       Filename
 *      @param	int		$acceptsvg	0=Default (depends on setup), 1=Always accept SVG as image files
 *      @return int         		-1=Not image filename, 0=Image filename but format not supported for conversion by PHP, 1=Image filename with format supported by this PHP
 */
function image_format_supported($file, $acceptsvg = 0)
{
	$regeximgext = getListOfPossibleImageExt();

	// Case filename is not a format image
	$reg = array();
	if (!preg_match('/('.$regeximgext.')$/i', $file, $reg)) {
		return -1;
	}

	// Case filename is a format image but not supported by this PHP
	$imgfonction = '';
	if (strtolower($reg[1]) == '.gif') {
		$imgfonction = 'imagecreatefromgif';
	}
	if (strtolower($reg[1]) == '.jpg') {
		$imgfonction = 'imagecreatefromjpeg';
	}
	if (strtolower($reg[1]) == '.jpeg') {
		$imgfonction = 'imagecreatefromjpeg';
	}
	if (strtolower($reg[1]) == '.png') {
		$imgfonction = 'imagecreatefrompng';
	}
	if (strtolower($reg[1]) == '.bmp') {
		$imgfonction = 'imagecreatefromwbmp';
	}
	if (strtolower($reg[1]) == '.webp') {
		$imgfonction = 'imagecreatefromwebp';
	}
	if (strtolower($reg[1]) == '.xpm') {
		$imgfonction = 'imagecreatefromxpm';
	}
	if (strtolower($reg[1]) == '.xbm') {
		$imgfonction = 'imagecreatefromxbm';
	}
	if (strtolower($reg[1]) == '.svg') {
		$imgfonction = 'imagecreatefromsvg'; // Never available
	}
	if ($imgfonction) {
		if (!function_exists($imgfonction)) {
			// Fonctions of conversion not available in this PHP
			return 0;
		}

		// Filename is a format image and supported for conversion by this PHP
		return 1;
	}

	return 0;
}


/**
 *    	Return size of image file on disk (Supported extensions are gif, jpg, png, bmp and webp)
 *
 * 		@param	string	$file		Full path name of file
 * 		@param	bool	$url		Image with url (true or false)
 * 		@return	array				array('width'=>width, 'height'=>height)
 */
function dol_getImageSize($file, $url = false)
{
	$ret = array();

	if (image_format_supported($file) < 0) {
		return $ret;
	}

	$filetoread = $file;
	if (!$url) {
		$filetoread = realpath(dol_osencode($file)); // Chemin canonique absolu de l'image
	}

	if ($filetoread) {
		$infoImg = getimagesize($filetoread); // Recuperation des infos de l'image
		$ret['width'] = $infoImg[0]; // Largeur de l'image
		$ret['height'] = $infoImg[1]; // Hauteur de l'image
	}

	return $ret;
}


/**
 *  Resize or crop an image file (Supported extensions are gif, jpg, png, bmp and webp)
 *
 *  @param	string	$file          	Path of source file to resize/crop
 * 	@param	int		$mode			0=Resize, 1=Crop
 *  @param  int		$newWidth      	Largeur maximum que dois faire l'image destination (0=keep ratio)
 *  @param  int		$newHeight     	Hauteur maximum que dois faire l'image destination (0=keep ratio)
 * 	@param	int		$src_x			Position of croping image in source image (not use if mode=0)
 * 	@param	int		$src_y			Position of croping image in source image (not use if mode=0)
 * 	@param	string	$filetowrite	Path of file to write (overwrite source file if not provided)
 *  @param	int		$newquality		Value for the new quality of image, for supported format (use 0 for maximum/unchanged).
 *	@return	string                  File name if OK, error message if KO
 *	@see dol_convert_file()
 */
function dol_imageResizeOrCrop($file, $mode, $newWidth, $newHeight, $src_x = 0, $src_y = 0, $filetowrite = '', $newquality = 0)
{
	require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

	global $conf, $langs;

	dol_syslog("dol_imageResizeOrCrop file=".$file." mode=".$mode." newWidth=".$newWidth." newHeight=".$newHeight." src_x=".$src_x." src_y=".$src_y);

	// Clean parameters
	$file = trim($file);

	// Check parameters
	if (!$file) {
		// Si le fichier n'a pas ete indique
		return 'Bad parameter file';
	} elseif (!file_exists($file)) {
		// Si le fichier passe en parametre n'existe pas
		return $langs->trans("ErrorFileNotFound", $file);
	} elseif (image_format_supported($file) < 0) {
		return 'This filename '.$file.' does not seem to be an image filename.';
	} elseif (!is_numeric($newWidth) && !is_numeric($newHeight)) {
		return 'Wrong value for parameter newWidth or newHeight';
	} elseif ($mode == 0 && $newWidth <= 0 && $newHeight <= 0 && (empty($filetowrite) || $filetowrite == $file)) {
		return 'At least newHeight or newWidth must be defined for resizing, or a target filename must be set to convert';
	} elseif ($mode == 1 && ($newWidth <= 0 || $newHeight <= 0)) {
		return 'Both newHeight or newWidth must be defined for croping';
	}

	$filetoread = realpath(dol_osencode($file)); // Chemin canonique absolu de l'image

	$infoImg = getimagesize($filetoread); 			// Get data about src image
	$imgWidth = $infoImg[0]; // Largeur de l'image
	$imgHeight = $infoImg[1]; // Hauteur de l'image

	$imgTargetName = ($filetowrite ? $filetowrite : $file);
	$newExt = strtolower(pathinfo($imgTargetName, PATHINFO_EXTENSION));

	if ($mode == 0) {	// If resize, we check parameters
		if (!empty($filetowrite) && $filetowrite != $file && $newWidth <= 0 && $newHeight <= 0) {
			$newWidth = $imgWidth;
			$newHeight = $imgHeight;
		}

		if ($newWidth <= 0) {
			$newWidth = intval(($newHeight / $imgHeight) * $imgWidth); // Keep ratio
		}
		if ($newHeight <= 0) {
			$newHeight = intval(($newWidth / $imgWidth) * $imgHeight); // Keep ratio
		}
	}

	// Test function to read source image exists
	$imgfonction = '';
	switch ($infoImg[2]) {
		case 1:	// IMG_GIF
			$imgfonction = 'imagecreatefromgif';
			break;
		case 2:	// IMG_JPG
			$imgfonction = 'imagecreatefromjpeg';
			break;
		case 3:	// IMG_PNG
			$imgfonction = 'imagecreatefrompng';
			break;
		case 4:	// IMG_WBMP
			$imgfonction = 'imagecreatefromwbmp';
			break;
		case 18: // IMG_WEBP
			$imgfonction = 'imagecreatefromwebp';
			break;
	}
	if ($imgfonction) {
		if (!function_exists($imgfonction)) {
			// Fonctions de conversion non presente dans ce PHP
			return 'Read of image not possible. This PHP does not support GD functions '.$imgfonction;
		}
	}

	// Test function to write target image exists
	if ($filetowrite) {
		$imgfonction = '';
		switch ($newExt) {
			case 'gif':		// IMG_GIF
				$imgfonction = 'imagecreatefromgif';
				break;
			case 'jpg':		// IMG_JPG
			case 'jpeg':	// IMG_JPEG
				$imgfonction = 'imagecreatefromjpeg';
				break;
			case 'png':		// IMG_PNG
				$imgfonction = 'imagecreatefrompng';
				break;
			case 'bmp':		// IMG_WBMP
				$imgfonction = 'imagecreatefromwbmp';
				break;
			case 'webp': 	// IMG_WEBP
				$imgfonction = 'imagecreatefromwebp';
				break;
		}
		if ($imgfonction) {
			if (!function_exists($imgfonction)) {
				// Fonctions de conversion non presente dans ce PHP
				return 'Write of image not possible. This PHP does not support GD functions '.$imgfonction;
			}
		}
	}

	// Read source image file
	switch ($infoImg[2]) {
		case 1:	// Gif
			$img = imagecreatefromgif($filetoread);
			$extImg = '.gif'; // File name extension of image
			break;
		case 2:	// Jpg
			$img = imagecreatefromjpeg($filetoread);
			$extImg = '.jpg';
			break;
		case 3:	// Png
			$img = imagecreatefrompng($filetoread);
			$extImg = '.png';
			break;
		case 4:	// Bmp
			$img = imagecreatefromwbmp($filetoread);
			$extImg = '.bmp';
			break;
		case 18: // Webp
			$img = imagecreatefromwebp($filetoread);
			$extImg = '.webp';
			break;
	}

	// Create empty image for target
	if ($newExt == 'gif') {
		// Compatibility image GIF
		$imgTarget = imagecreate($newWidth, $newHeight);
	} else {
		$imgTarget = imagecreatetruecolor($newWidth, $newHeight);
	}

	// Activate antialiasing for better quality
	if (function_exists('imageantialias')) {
		imageantialias($imgTarget, true);
	}

	// This is to keep transparent alpha channel if exists (PHP >= 4.2)
	if (function_exists('imagesavealpha')) {
		imagesavealpha($imgTarget, true);
	}

	// Set transparent color according to image extension
	$trans_colour = -1;	// By default, undefined
	switch ($newExt) {
		case 'gif':	// Gif
			$trans_colour = imagecolorallocate($imgTarget, 255, 255, 255); // On procede autrement pour le format GIF
			imagecolortransparent($imgTarget, $trans_colour);
			break;
		case 'jpg':	// Jpg
		case 'jpeg':	// Jpeg
			$trans_colour = imagecolorallocatealpha($imgTarget, 255, 255, 255, 0);
			break;
		case 'png':	// Png
			imagealphablending($imgTarget, false); // Pour compatibilite sur certain systeme
			$trans_colour = imagecolorallocatealpha($imgTarget, 255, 255, 255, 127); // Keep transparent channel
			break;
		case 'bmp':	// Bmp
			$trans_colour = imagecolorallocatealpha($imgTarget, 255, 255, 255, 0);
			break;
		case 'webp': // Webp
			$trans_colour = imagecolorallocatealpha($imgTarget, 255, 255, 255, 127);
			break;
	}
	if (function_exists("imagefill") && $trans_colour > 0) {
		imagefill($imgTarget, 0, 0, $trans_colour);
	}

	dol_syslog("dol_imageResizeOrCrop: convert image from ($imgWidth x $imgHeight) at position ($src_x x $src_y) to ($newWidth x $newHeight) as a $extImg");
	//imagecopyresized($imgTarget, $img, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $imgWidth, $imgHeight); // Insere l'image de base redimensionnee
	imagecopyresampled($imgTarget, $img, 0, 0, $src_x, $src_y, $newWidth, $newHeight, ($mode == 0 ? $imgWidth : $newWidth), ($mode == 0 ? $imgHeight : $newHeight)); // Insere l'image de base redimensionnee

	// Check if permission are ok
	//$fp = fopen($imgTargetName, "w");
	//fclose($fp);

	// Create image on disk (overwrite file if exists)
	switch ($newExt) {
		case 'gif':	// Gif
			$newquality = 'NU'; // Quality is not used for this format
			imagegif($imgTarget, $imgTargetName);
			break;
		case 'jpg':	// Jpg
		case 'jpeg':	// Jpeg
			$newquality = ($newquality ? $newquality : '100'); // % quality maximum
			imagejpeg($imgTarget, $imgTargetName, $newquality);
			break;
		case 'png':	// Png
			$newquality = 0; // No compression (0-9)
			imagepng($imgTarget, $imgTargetName, $newquality);
			break;
		case 'bmp':	// Bmp
			$newquality = 'NU'; // Quality is not used for this format
			imagewbmp($imgTarget, $imgTargetName);
			break;
		case 'webp': // Webp
			$newquality = ($newquality ? $newquality : '100'); // % quality maximum
			imagewebp($imgTarget, $imgTargetName, $newquality);
			break;
		default:
			dol_syslog("images.lib.php::imageResizeOrCrop() Format ".$newExt." is not supported", LOG_WARNING);
	}

	// Set permissions on file
	if (!empty($conf->global->MAIN_UMASK)) {
		@chmod($imgTargetName, octdec($conf->global->MAIN_UMASK));
	}

	// Free memory. This does not delete image.
	imagedestroy($img);
	imagedestroy($imgTarget);

	clearstatcache(); // File was replaced by a modified one, so we clear file caches.

	return $imgTargetName;
}


/**
 * dolRotateImage if image is a jpg file.
 * Currently use an autodetection to know if we can rotate.
 * TODO Introduce a new parameter to force rotate.
 *
 * @param 	string   $file_path      Full path to image to rotate
 * @return	boolean				     Success or not
 */
function dolRotateImage($file_path)
{
	return correctExifImageOrientation($file_path, $file_path);
}


/**
 * Add exif orientation correction for image
 *
 * @param string $fileSource Full path to source image to rotate
 * @param string|bool $fileDest string : Full path to image to rotate | false return gd img  | null  the raw image stream will be outputted directly
 * @param int $quality output image quality
 * @return bool : true on success or false on failure or gd img if $fileDest is false.
 */
function correctExifImageOrientation($fileSource, $fileDest, $quality = 95)
{
	if (function_exists('exif_read_data')) {
		$exif = @exif_read_data($fileSource);
		if ($exif && isset($exif['Orientation'])) {
			$infoImg = getimagesize($fileSource); // Get image infos

			$orientation = $exif['Orientation'];
			if ($orientation != 1) {
				$img = imagecreatefromjpeg($fileSource);
				$deg = 0;
				switch ($orientation) {
					case 3:
						$deg = 180;
						break;
					case 6:
						$deg = 270;
						break;
					case 8:
						$deg = 90;
						break;
				}
				if ($deg) {
					if ($infoImg[2] === 'IMAGETYPE_PNG') { // In fact there is no exif on PNG but just in case
						imagealphablending($img, false);
						imagesavealpha($img, true);
						$img = imagerotate($img, $deg, imageColorAllocateAlpha($img, 0, 0, 0, 127));
						imagealphablending($img, false);
						imagesavealpha($img, true);
					} else {
						$img = imagerotate($img, $deg, 0);
					}
				}
				// then rewrite the rotated image back to the disk as $fileDest
				if ($fileDest === false) {
					return $img;
				} else {
					// In fact there exif is only for JPG but just in case
					// Create image on disk
					$image = false;

					switch ($infoImg[2]) {
						case IMAGETYPE_GIF:	    // 1
							$image = imagegif($img, $fileDest);
							break;
						case IMAGETYPE_JPEG:    // 2
							$image = imagejpeg($img, $fileDest, $quality);
							break;
						case IMAGETYPE_PNG:	    // 3
							$image = imagepng($img, $fileDest, $quality);
							break;
						case IMAGETYPE_BMP:	    // 6
							// Not supported by PHP GD
							break;
						case IMAGETYPE_WBMP:    // 15
							$image = imagewbmp($img, $fileDest);
							break;
					}

					// Free up memory (imagedestroy does not delete files):
					@imagedestroy($img);

					return $image;
				}
			} // if there is some rotation necessary
		} // if have the exif orientation info
	} // if function exists

	return false;
}

/**
 *    	Create a thumbnail from an image file (Supported extensions are gif, jpg, png and bmp).
 *      If file is myfile.jpg, new file may be myfile_small.jpg
 *
 *    	@param     string	$file           	Path of source file to resize
 *    	@param     int		$maxWidth       	Largeur maximum que dois faire la miniature (-1=unchanged, 160 by default)
 *    	@param     int		$maxHeight      	Hauteur maximum que dois faire l'image (-1=unchanged, 120 by default)
 *    	@param     string	$extName        	Extension to differenciate thumb file name ('_small', '_mini')
 *    	@param     int		$quality        	Quality of compression (0=worst, 100=best)
 *      @param     string	$outdir           	Directory where to store thumb
 *      @param     int		$targetformat     	New format of target (IMAGETYPE_GIF, IMAGETYPE_JPG, IMAGETYPE_PNG, IMAGETYPE_BMP, IMAGETYPE_WBMP ... or 0 to keep old format)
 *    	@return    string						Full path of thumb or '' if it fails or 'Error...' if it fails
 */
function vignette($file, $maxWidth = 160, $maxHeight = 120, $extName = '_small', $quality = 50, $outdir = 'thumbs', $targetformat = 0)
{
	require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

	global $conf, $langs;

	dol_syslog("vignette file=".$file." extName=".$extName." maxWidth=".$maxWidth." maxHeight=".$maxHeight." quality=".$quality." outdir=".$outdir." targetformat=".$targetformat);

	// Clean parameters
	$file = trim($file);

	// Check parameters
	if (!$file) {
		// Si le fichier n'a pas ete indique
		return 'ErrorBadParameters';
	} elseif (!file_exists($file)) {
		// Si le fichier passe en parametre n'existe pas
		dol_syslog($langs->trans("ErrorFileNotFound", $file), LOG_ERR);
		return $langs->trans("ErrorFileNotFound", $file);
	} elseif (image_format_supported($file) < 0) {
		dol_syslog('This file '.$file.' does not seem to be an image format file name.', LOG_WARNING);
		return 'ErrorBadImageFormat';
	} elseif (!is_numeric($maxWidth) || empty($maxWidth) || $maxWidth < -1) {
		// Si la largeur max est incorrecte (n'est pas numerique, est vide, ou est inferieure a 0)
		dol_syslog('Wrong value for parameter maxWidth', LOG_ERR);
		return 'Error: Wrong value for parameter maxWidth';
	} elseif (!is_numeric($maxHeight) || empty($maxHeight) || $maxHeight < -1) {
		// Si la hauteur max est incorrecte (n'est pas numerique, est vide, ou est inferieure a 0)
		dol_syslog('Wrong value for parameter maxHeight', LOG_ERR);
		return 'Error: Wrong value for parameter maxHeight';
	}

	$filetoread = realpath(dol_osencode($file)); // Chemin canonique absolu de l'image

	$infoImg = getimagesize($filetoread); // Recuperation des infos de l'image
	$imgWidth = $infoImg[0]; // Largeur de l'image
	$imgHeight = $infoImg[1]; // Hauteur de l'image

	$ort = false;
	if (function_exists('exif_read_data')) {
		$exif = @exif_read_data($filetoread);
		if ($exif && !empty($exif['Orientation'])) {
			$ort = $exif['Orientation'];
		}
	}

	if ($maxWidth == -1) {
		$maxWidth = $infoImg[0]; // If size is -1, we keep unchanged
	}
	if ($maxHeight == -1) {
		$maxHeight = $infoImg[1]; // If size is -1, we keep unchanged
	}

	// Si l'image est plus petite que la largeur et la hauteur max, on ne cree pas de vignette
	if ($infoImg[0] < $maxWidth && $infoImg[1] < $maxHeight) {
		// On cree toujours les vignettes
		dol_syslog("File size is smaller than thumb size", LOG_DEBUG);
		//return 'Le fichier '.$file.' ne necessite pas de creation de vignette';
	}

	$imgfonction = '';
	switch ($infoImg[2]) {
		case IMAGETYPE_GIF:	    // 1
			$imgfonction = 'imagecreatefromgif';
			break;
		case IMAGETYPE_JPEG:    // 2
			$imgfonction = 'imagecreatefromjpeg';
			break;
		case IMAGETYPE_PNG:	    // 3
			$imgfonction = 'imagecreatefrompng';
			break;
		case IMAGETYPE_BMP:	    // 6
			// Not supported by PHP GD
			break;
		case IMAGETYPE_WBMP:	// 15
			$imgfonction = 'imagecreatefromwbmp';
			break;
	}
	if ($imgfonction) {
		if (!function_exists($imgfonction)) {
			// Fonctions de conversion non presente dans ce PHP
			return 'Error: Creation of thumbs not possible. This PHP does not support GD function '.$imgfonction;
		}
	}

	// On cree le repertoire contenant les vignettes
	$dirthumb = dirname($file).($outdir ? '/'.$outdir : ''); // Chemin du dossier contenant les vignettes
	dol_mkdir($dirthumb);

	// Initialisation des variables selon l'extension de l'image
	$img = null;
	switch ($infoImg[2]) {
		case IMAGETYPE_GIF:	    // 1
			$img = imagecreatefromgif($filetoread);
			$extImg = '.gif'; // Extension de l'image
			break;
		case IMAGETYPE_JPEG:    // 2
			$img = imagecreatefromjpeg($filetoread);
			$extImg = (preg_match('/\.jpeg$/', $file) ? '.jpeg' : '.jpg'); // Extension de l'image
			break;
		case IMAGETYPE_PNG:	    // 3
			$img = imagecreatefrompng($filetoread);
			$extImg = '.png';
			break;
		case IMAGETYPE_BMP:	    // 6
			// Not supported by PHP GD
			$extImg = '.bmp';
			break;
		case IMAGETYPE_WBMP:	// 15
			$img = imagecreatefromwbmp($filetoread);
			$extImg = '.bmp';
			break;
	}

	// Before PHP8, img was a resource, With PHP8, it is a GdImage
	if (!is_resource($img) && !($img instanceof \GdImage)) {
		dol_syslog('Failed to detect type of image. We found infoImg[2]='.$infoImg[2], LOG_WARNING);
		return 0;
	}

	$exifAngle = false;
	if ($ort && !empty($conf->global->MAIN_USE_EXIF_ROTATION)) {
		switch ($ort) {
			case 3: // 180 rotate left
				$exifAngle = 180;
				break;
			case 6: // 90 rotate right
				$exifAngle = -90;
				// changing sizes
				$trueImgWidth = $infoImg[1];
				$trueImgHeight = $infoImg[0];
				break;
			case 8:    // 90 rotate left
				$exifAngle = 90;
				// changing sizes
				$trueImgWidth = $infoImg[1]; // Largeur de l'image
				$trueImgHeight = $infoImg[0]; // Hauteur de l'image
				break;
		}
	}

	if ($exifAngle) {
		$rotated = false;

		if ($infoImg[2] === 'IMAGETYPE_PNG') { // In fact there is no exif on PNG but just in case
			imagealphablending($img, false);
			imagesavealpha($img, true);
			$rotated = imagerotate($img, $exifAngle, imageColorAllocateAlpha($img, 0, 0, 0, 127));
			imagealphablending($rotated, false);
			imagesavealpha($rotated, true);
		} else {
			$rotated = imagerotate($img, $exifAngle, 0);
		}

		// replace image with good orientation
		if (!empty($rotated) && isset($trueImgWidth) && isset($trueImgHeight)) {
			$img = $rotated;
			$imgWidth = $trueImgWidth;
			$imgHeight = $trueImgHeight;
		}
	}

	// Initialisation des dimensions de la vignette si elles sont superieures a l'original
	if ($maxWidth > $imgWidth) {
		$maxWidth = $imgWidth;
	}
	if ($maxHeight > $imgHeight) {
		$maxHeight = $imgHeight;
	}

	$whFact = $maxWidth / $maxHeight; // Facteur largeur/hauteur des dimensions max de la vignette
	$imgWhFact = $imgWidth / $imgHeight; // Facteur largeur/hauteur de l'original

	// Fixe les dimensions de la vignette
	if ($whFact < $imgWhFact) {
		// Si largeur determinante
		$thumbWidth  = $maxWidth;
		$thumbHeight = $thumbWidth / $imgWhFact;
	} else {
		// Si hauteur determinante
		$thumbHeight = $maxHeight;
		$thumbWidth  = $thumbHeight * $imgWhFact;
	}
	$thumbHeight = round($thumbHeight);
	$thumbWidth = round($thumbWidth);

	// Define target format
	if (empty($targetformat)) {
		$targetformat = $infoImg[2];
	}

	// Create empty image
	if ($targetformat == IMAGETYPE_GIF) {
		// Compatibilite image GIF
		$imgThumb = imagecreate($thumbWidth, $thumbHeight);
	} else {
		$imgThumb = imagecreatetruecolor($thumbWidth, $thumbHeight);
	}

	// Activate antialiasing for better quality
	if (function_exists('imageantialias')) {
		imageantialias($imgThumb, true);
	}

	// This is to keep transparent alpha channel if exists (PHP >= 4.2)
	if (function_exists('imagesavealpha')) {
		imagesavealpha($imgThumb, true);
	}

	// Initialisation des variables selon l'extension de l'image
	// $targetformat is 0 by default, in such case, we keep original extension
	switch ($targetformat) {
		case IMAGETYPE_GIF:	    // 1
			$trans_colour = imagecolorallocate($imgThumb, 255, 255, 255); // On procede autrement pour le format GIF
			imagecolortransparent($imgThumb, $trans_colour);
			$extImgTarget = '.gif';
			$newquality = 'NU';
			break;
		case IMAGETYPE_JPEG:    // 2
			$trans_colour = imagecolorallocatealpha($imgThumb, 255, 255, 255, 0);
			$extImgTarget = (preg_match('/\.jpeg$/i', $file) ? '.jpeg' : '.jpg');
			$newquality = $quality;
			break;
		case IMAGETYPE_PNG:	    // 3
			imagealphablending($imgThumb, false); // Pour compatibilite sur certain systeme
			$trans_colour = imagecolorallocatealpha($imgThumb, 255, 255, 255, 127); // Keep transparent channel
			$extImgTarget = '.png';
			$newquality = $quality - 100;
			$newquality = round(abs($quality - 100) * 9 / 100);
			break;
		case IMAGETYPE_BMP:	    // 6
			// Not supported by PHP GD
			$extImgTarget = '.bmp';
			$newquality = 'NU';
			break;
		case IMAGETYPE_WBMP:	// 15
			$trans_colour = imagecolorallocatealpha($imgThumb, 255, 255, 255, 0);
			$extImgTarget = '.bmp';
			$newquality = 'NU';
			break;
	}
	if (function_exists("imagefill")) {
		imagefill($imgThumb, 0, 0, $trans_colour);
	}

	dol_syslog("vignette: convert image from ($imgWidth x $imgHeight) to ($thumbWidth x $thumbHeight) as $extImg, newquality=$newquality");
	//imagecopyresized($imgThumb, $img, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $imgWidth, $imgHeight); // Insere l'image de base redimensionnee
	imagecopyresampled($imgThumb, $img, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $imgWidth, $imgHeight); // Insere l'image de base redimensionnee

	$fileName = preg_replace('/(\.gif|\.jpeg|\.jpg|\.png|\.bmp)$/i', '', $file); // On enleve extension quelquesoit la casse
	$fileName = basename($fileName);
	//$imgThumbName = $dirthumb.'/'.getImageFileNameForSize(basename($file), $extName, $extImgTarget);   // Full path of thumb file
	$imgThumbName = getImageFileNameForSize($file, $extName, $extImgTarget); // Full path of thumb file


	// Check if permission are ok
	//$fp = fopen($imgThumbName, "w");
	//fclose($fp);

	// Create image on disk
	switch ($targetformat) {
		case IMAGETYPE_GIF:	    // 1
			imagegif($imgThumb, $imgThumbName);
			break;
		case IMAGETYPE_JPEG:    // 2
			imagejpeg($imgThumb, $imgThumbName, $newquality);
			break;
		case IMAGETYPE_PNG:	    // 3
			imagepng($imgThumb, $imgThumbName, $newquality);
			break;
		case IMAGETYPE_BMP:	    // 6
			// Not supported by PHP GD
			break;
		case IMAGETYPE_WBMP:    // 15
			imagewbmp($imgThumb, $imgThumbName);
			break;
	}

	// Set permissions on file
	if (!empty($conf->global->MAIN_UMASK)) {
		@chmod($imgThumbName, octdec($conf->global->MAIN_UMASK));
	}

	// Free memory. This does not delete image.
	imagedestroy($img);
	imagedestroy($imgThumb);

	return $imgThumbName;
}
