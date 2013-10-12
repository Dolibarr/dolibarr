<?php
/* Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2007 Regis Houssin        <regis.houssin@capnetworks.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *  \file		htdocs/core/lib/images.lib.php
 *  \brief		Set of function for manipulating images
 */

// Define size of logo small and mini
$maxwidthsmall=270;$maxheightsmall=150;
$maxwidthmini=128;$maxheightmini=72;
$quality = 80;



/**
 *      Return if a filename is file name of a supported image format
 *
 *      @param	string	$file       Filename
 *      @return int         		-1=Not image filename, 0=Image filename but format not supported by PHP, 1=Image filename with format supported
 */
function image_format_supported($file)
{
    // Case filename is not a format image
    if (! preg_match('/(\.gif|\.jpg|\.jpeg|\.png|\.bmp)$/i',$file,$reg)) return -1;

    // Case filename is a format image but not supported by this PHP
    $imgfonction='';
    if (strtolower($reg[1]) == '.gif')  $imgfonction = 'imagecreatefromgif';
    if (strtolower($reg[1]) == '.png')  $imgfonction = 'imagecreatefrompng';
    if (strtolower($reg[1]) == '.jpg')  $imgfonction = 'imagecreatefromjpeg';
    if (strtolower($reg[1]) == '.jpeg') $imgfonction = 'imagecreatefromjpeg';
    if (strtolower($reg[1]) == '.bmp')  $imgfonction = 'imagecreatefromwbmp';
    if ($imgfonction)
    {
        if (! function_exists($imgfonction))
        {
            // Fonctions de conversion non presente dans ce PHP
            return 0;
        }
    }

    // Filename is a format image and supported by this PHP
    return 1;
}


/**
 *    	Return size of image file on disk (Supported extensions are gif, jpg, png and bmp)
 *
 * 		@param	string	$file		Full path name of file
 * 		@param	bool	$url		Image with url (true or false)
 * 		@return	array				array('width'=>width, 'height'=>height)
 */
function dol_getImageSize($file, $url = false)
{
	$ret=array();

	if (image_format_supported($file) < 0) return $ret;

	$fichier = $file;
	if (!$url)
	{
		$fichier = realpath($file); 	// Chemin canonique absolu de l'image
		$dir = dirname($file); 			// Chemin du dossier contenant l'image
	}

	$infoImg = getimagesize($fichier); // Recuperation des infos de l'image
	$ret['width']=$infoImg[0]; // Largeur de l'image
	$ret['height']=$infoImg[1]; // Hauteur de l'image

	return $ret;
}


/**
 *    	Resize or crop an image file (Supported extensions are gif, jpg, png and bmp)
 *
 *    	@param	string	$file          	Path of file to resize/crop
 * 		@param	int		$mode			0=Resize, 1=Crop
 *    	@param  int		$newWidth      	Largeur maximum que dois faire l'image destination (0=keep ratio)
 *    	@param  int		$newHeight     	Hauteur maximum que dois faire l'image destination (0=keep ratio)
 * 		@param	int		$src_x			Position of croping image in source image (not use if mode=0)
 * 		@param	int		$src_y			Position of croping image in source image (not use if mode=0)
 *		@return	int						File name if OK, error message if KO
 */
function dol_imageResizeOrCrop($file, $mode, $newWidth, $newHeight, $src_x=0, $src_y=0)
{
	require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

	global $conf,$langs;

	dol_syslog("dol_imageResizeOrCrop file=".$file." mode=".$mode." newWidth=".$newWidth." newHeight=".$newHeight." src_x=".$src_x." src_y=".$src_y);

	// Clean parameters
	$file=trim($file);

	// Check parameters
	if (! $file)
	{
		// Si le fichier n'a pas ete indique
		return 'Bad parameter file';
	}
	elseif (! file_exists($file))
	{
		// Si le fichier passe en parametre n'existe pas
		return $langs->trans("ErrorFileNotFound",$file);
	}
	elseif(image_format_supported($file) < 0)
	{
		return 'This filename '.$file.' does not seem to be an image filename.';
	}
	elseif(!is_numeric($newWidth) && !is_numeric($newHeight))
	{
		return 'Wrong value for parameter newWidth or newHeight';
	}
	elseif ($mode == 0 && $newWidth <= 0 && $newHeight <= 0)
	{
		return 'At least newHeight or newWidth must be defined for resizing';
	}
	elseif ($mode == 1 && ($newWidth <= 0 || $newHeight <= 0))
	{
		return 'Both newHeight or newWidth must be defined for croping';
	}

	$fichier = realpath($file); 	// Chemin canonique absolu de l'image
	$dir = dirname($file); 			// Chemin du dossier contenant l'image

	$infoImg = getimagesize($fichier); // Recuperation des infos de l'image
	$imgWidth = $infoImg[0]; // Largeur de l'image
	$imgHeight = $infoImg[1]; // Hauteur de l'image

	if ($mode == 0)	// If resize, we check parameters
	{
		if ($newWidth  <= 0)
		{
			$newWidth=intval(($newHeight / $imgHeight) * $imgWidth);	// Keep ratio
		}
		if ($newHeight <= 0)
		{
			$newHeight=intval(($newWidth / $imgWidth) * $imgHeight);	// Keep ratio
		}
	}

	$imgfonction='';
	switch($infoImg[2])
	{
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
	}
	if ($imgfonction)
	{
		if (! function_exists($imgfonction))
		{
			// Fonctions de conversion non presente dans ce PHP
			return 'Resize not possible. This PHP does not support GD functions '.$imgfonction;
		}
	}

	// Initialisation des variables selon l'extension de l'image
	switch($infoImg[2])
	{
		case 1:	// Gif
			$img = imagecreatefromgif($fichier);
			$extImg = '.gif';	// File name extension of image
			$newquality='NU';	// Quality is not used for this format
			break;
		case 2:	// Jpg
			$img = imagecreatefromjpeg($fichier);
			$extImg = '.jpg';
			$newquality=100;	// % quality maximum
			break;
		case 3:	// Png
			$img = imagecreatefrompng($fichier);
			$extImg = '.png';
			$newquality=0;		// No compression (0-9)
			break;
		case 4:	// Bmp
			$img = imagecreatefromwbmp($fichier);
			$extImg = '.bmp';
			$newquality='NU';	// Quality is not used for this format
			break;
	}

	// Create empty image
	if ($infoImg[2] == 1)
	{
		// Compatibilite image GIF
		$imgThumb = imagecreate($newWidth, $newHeight);
	}
	else
	{
		$imgThumb = imagecreatetruecolor($newWidth, $newHeight);
	}

	// Activate antialiasing for better quality
	if (function_exists('imageantialias'))
	{
		imageantialias($imgThumb, true);
	}

	// This is to keep transparent alpha channel if exists (PHP >= 4.2)
	if (function_exists('imagesavealpha'))
	{
		imagesavealpha($imgThumb, true);
	}

	// Initialisation des variables selon l'extension de l'image
	switch($infoImg[2])
	{
		case 1:	// Gif
			$trans_colour = imagecolorallocate($imgThumb, 255, 255, 255); // On procede autrement pour le format GIF
			imagecolortransparent($imgThumb,$trans_colour);
			break;
		case 2:	// Jpg
			$trans_colour = imagecolorallocatealpha($imgThumb, 255, 255, 255, 0);
			break;
		case 3:	// Png
			imagealphablending($imgThumb,false); // Pour compatibilite sur certain systeme
			$trans_colour = imagecolorallocatealpha($imgThumb, 255, 255, 255, 127);	// Keep transparent channel
			break;
		case 4:	// Bmp
			$trans_colour = imagecolorallocatealpha($imgThumb, 255, 255, 255, 0);
			break;
	}
	if (function_exists("imagefill")) imagefill($imgThumb, 0, 0, $trans_colour);

	dol_syslog("dol_imageResizeOrCrop: convert image from ($imgWidth x $imgHeight) at position ($src_x x $src_y) to ($newWidth x $newHeight) as $extImg, newquality=$newquality");
	//imagecopyresized($imgThumb, $img, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $imgWidth, $imgHeight); // Insere l'image de base redimensionnee
	imagecopyresampled($imgThumb, $img, 0, 0, $src_x, $src_y, $newWidth, $newHeight, ($mode==0?$imgWidth:$newWidth), ($mode==0?$imgHeight:$newHeight)); // Insere l'image de base redimensionnee

	$imgThumbName = $file;

	// Check if permission are ok
	//$fp = fopen($imgThumbName, "w");
	//fclose($fp);

	// Create image on disk
	switch($infoImg[2])
	{
		case 1:	// Gif
			imagegif($imgThumb, $imgThumbName);
			break;
		case 2:	// Jpg
			imagejpeg($imgThumb, $imgThumbName, $newquality);
			break;
		case 3:	// Png
			imagepng($imgThumb, $imgThumbName, $newquality);
			break;
		case 4:	// Bmp
			image2wbmp($imgThumb, $imgThumbName);
			break;
	}

	// Set permissions on file
	if (! empty($conf->global->MAIN_UMASK)) @chmod($imgThumbName, octdec($conf->global->MAIN_UMASK));

	// Free memory. This does not delete image.
    imagedestroy($img);
	imagedestroy($imgThumb);

	clearstatcache();	// File was replaced by a modified one, so we clear file caches.

	return $imgThumbName;
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
 *      @param     int		$targetformat     	New format of target (1,2,3,... or 0 to keep old format)
 *    	@return    string						Full path of thumb or '' if it fails
 */
function vignette($file, $maxWidth = 160, $maxHeight = 120, $extName='_small', $quality=50, $outdir='thumbs', $targetformat=0)
{
	require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

	global $conf,$langs;

	dol_syslog("vignette file=".$file." extName=".$extName." maxWidth=".$maxWidth." maxHeight=".$maxHeight." quality=".$quality." outdir=".$outdir." targetformat=".$targetformat);

	// Clean parameters
	$file=trim($file);

	// Check parameters
	if (! $file)
	{
		// Si le fichier n'a pas ete indique
		return 'ErrorBadParameters';
	}
	elseif (! file_exists($file))
	{
		// Si le fichier passe en parametre n'existe pas
        dol_syslog($langs->trans("ErrorFileNotFound",$file),LOG_ERR);
	    return $langs->trans("ErrorFileNotFound",$file);
	}
	elseif(image_format_supported($file) < 0)
	{
        dol_syslog('This file '.$file.' does not seem to be an image format file name.',LOG_WARNING);
	    return 'ErrorBadImageFormat';
	}
	elseif(!is_numeric($maxWidth) || empty($maxWidth) || $maxWidth < -1){
		// Si la largeur max est incorrecte (n'est pas numerique, est vide, ou est inferieure a 0)
        dol_syslog('Wrong value for parameter maxWidth',LOG_ERR);
	    return 'Error: Wrong value for parameter maxWidth';
	}
	elseif(!is_numeric($maxHeight) || empty($maxHeight) || $maxHeight < -1){
		// Si la hauteur max est incorrecte (n'est pas numerique, est vide, ou est inferieure a 0)
        dol_syslog('Wrong value for parameter maxHeight',LOG_ERR);
	    return 'Error: Wrong value for parameter maxHeight';
	}

	$fichier = realpath($file); 	// Chemin canonique absolu de l'image
	$dir = dirname($file); 			// Chemin du dossier contenant l'image

	$infoImg = getimagesize($fichier); // Recuperation des infos de l'image
	$imgWidth = $infoImg[0]; // Largeur de l'image
	$imgHeight = $infoImg[1]; // Hauteur de l'image

	if ($maxWidth  == -1) $maxWidth=$infoImg[0];	// If size is -1, we keep unchanged
	if ($maxHeight == -1) $maxHeight=$infoImg[1];	// If size is -1, we keep unchanged

	// Si l'image est plus petite que la largeur et la hauteur max, on ne cree pas de vignette
	if ($infoImg[0] < $maxWidth && $infoImg[1] < $maxHeight)
	{
		// On cree toujours les vignettes
		dol_syslog("File size is smaller than thumb size",LOG_DEBUG);
		//return 'Le fichier '.$file.' ne necessite pas de creation de vignette';
	}

	$imgfonction='';
	switch($infoImg[2])
	{
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
	if ($imgfonction)
	{
		if (! function_exists($imgfonction))
		{
			// Fonctions de conversion non presente dans ce PHP
			return 'Error: Creation of thumbs not possible. This PHP does not support GD function '.$imgfonction;
		}
	}

	// On cree le repertoire contenant les vignettes
	$dirthumb = $dir.($outdir?'/'.$outdir:''); 	// Chemin du dossier contenant les vignettes
	dol_mkdir($dirthumb);

	// Initialisation des variables selon l'extension de l'image
	switch($infoImg[2])
	{
		case IMAGETYPE_GIF:	    // 1
			$img = imagecreatefromgif($fichier);
			$extImg = '.gif'; // Extension de l'image
			break;
		case IMAGETYPE_JPEG:    // 2
			$img = imagecreatefromjpeg($fichier);
			$extImg = (preg_match('/\.jpeg$/',$file)?'.jpeg':'.jpg'); // Extension de l'image
			break;
		case IMAGETYPE_PNG:	    // 3
			$img = imagecreatefrompng($fichier);
			$extImg = '.png';
			break;
		case IMAGETYPE_BMP:	    // 6
            // Not supported by PHP GD
			$extImg = '.bmp';
			break;
		case IMAGETYPE_WBMP:	// 15
			$img = imagecreatefromwbmp($fichier);
			$extImg = '.bmp';
			break;
	}
    if (! is_resource($img))
    {
        dol_syslog('Failed to detect type of image. We found infoImg[2]='.$infoImg[2], LOG_WARNING);
        return 0;
    }

	// Initialisation des dimensions de la vignette si elles sont superieures a l'original
	if($maxWidth > $imgWidth){ $maxWidth = $imgWidth; }
	if($maxHeight > $imgHeight){ $maxHeight = $imgHeight; }

	$whFact = $maxWidth/$maxHeight; // Facteur largeur/hauteur des dimensions max de la vignette
	$imgWhFact = $imgWidth/$imgHeight; // Facteur largeur/hauteur de l'original

	// Fixe les dimensions de la vignette
	if($whFact < $imgWhFact)
	{
		// Si largeur determinante
		$thumbWidth  = $maxWidth;
		$thumbHeight = $thumbWidth / $imgWhFact;
	}
	else
	{
		// Si hauteur determinante
		$thumbHeight = $maxHeight;
		$thumbWidth  = $thumbHeight * $imgWhFact;
	}
	$thumbHeight=round($thumbHeight);
	$thumbWidth=round($thumbWidth);

    // Define target format
    if (empty($targetformat)) $targetformat=$infoImg[2];

	// Create empty image
	if ($targetformat == IMAGETYPE_GIF)
	{
		// Compatibilite image GIF
		$imgThumb = imagecreate($thumbWidth, $thumbHeight);
	}
	else
	{
		$imgThumb = imagecreatetruecolor($thumbWidth, $thumbHeight);
	}

	// Activate antialiasing for better quality
	if (function_exists('imageantialias'))
	{
		imageantialias($imgThumb, true);
	}

	// This is to keep transparent alpha channel if exists (PHP >= 4.2)
	if (function_exists('imagesavealpha'))
	{
		imagesavealpha($imgThumb, true);
	}

	// Initialisation des variables selon l'extension de l'image
	switch($targetformat)
	{
		case IMAGETYPE_GIF:	    // 1
			$trans_colour = imagecolorallocate($imgThumb, 255, 255, 255); // On procede autrement pour le format GIF
			imagecolortransparent($imgThumb,$trans_colour);
            $extImgTarget = '.gif';
            $newquality='NU';
            break;
		case IMAGETYPE_JPEG:    // 2
            $trans_colour = imagecolorallocatealpha($imgThumb, 255, 255, 255, 0);
            $extImgTarget = (preg_match('/\.jpeg$/',$file)?'.jpeg':'.jpg');
            $newquality=$quality;
            break;
		case IMAGETYPE_PNG:	    // 3
			imagealphablending($imgThumb,false); // Pour compatibilite sur certain systeme
			$trans_colour = imagecolorallocatealpha($imgThumb, 255, 255, 255, 127);	// Keep transparent channel
            $extImgTarget = '.png';
            $newquality=$quality-100;
            $newquality=round(abs($quality-100)*9/100);
            break;
		case IMAGETYPE_BMP:	    // 6
            // Not supported by PHP GD
            $extImgTarget = '.bmp';
            $newquality='NU';
            break;
		case IMAGETYPE_WBMP:	// 15
			$trans_colour = imagecolorallocatealpha($imgThumb, 255, 255, 255, 0);
            $extImgTarget = '.bmp';
            $newquality='NU';
            break;
	}
	if (function_exists("imagefill")) imagefill($imgThumb, 0, 0, $trans_colour);

	dol_syslog("vignette: convert image from ($imgWidth x $imgHeight) to ($thumbWidth x $thumbHeight) as $extImg, newquality=$newquality");
	//imagecopyresized($imgThumb, $img, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $imgWidth, $imgHeight); // Insere l'image de base redimensionnee
	imagecopyresampled($imgThumb, $img, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $imgWidth, $imgHeight); // Insere l'image de base redimensionnee

	$fileName = preg_replace('/(\.gif|\.jpeg|\.jpg|\.png|\.bmp)$/i','',$file);	// On enleve extension quelquesoit la casse
	$fileName = basename($fileName);
	$imgThumbName = $dirthumb.'/'.$fileName.$extName.$extImgTarget; // Chemin complet du fichier de la vignette

	// Check if permission are ok
	//$fp = fopen($imgThumbName, "w");
	//fclose($fp);

	// Create image on disk
	switch($targetformat)
	{
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
			image2wbmp($imgThumb, $imgThumbName);
			break;
	}

	// Set permissions on file
	if (! empty($conf->global->MAIN_UMASK)) @chmod($imgThumbName, octdec($conf->global->MAIN_UMASK));

    // Free memory. This does not delete image.
    imagedestroy($img);
    imagedestroy($imgThumb);

	return $imgThumbName;
}


/**
 *	This function returns the html for the moneymeter.
 *
 *	@param	int		$actualValue	amount of actual money
 *	@param	int		$pendingValue	amount of money of pending memberships
 *	@param	int		$intentValue	amount of intended money (that's without the amount of actual money)
 *	@return string					thermometer htmlLegenda
 */
function moneyMeter($actualValue=0, $pendingValue=0, $intentValue=0)
{
	global $langs;

	// variables
	$height="200";
	$maximumValue=125000;

	$imageDir = "http://eucd.info/images/therm/";

	$imageTop = $imageDir . "therm_top.png";
	$imageMiddleActual = $imageDir . "therm_actual.png";
	$imageMiddlePending = $imageDir . "therm_pending.png";
	$imageMiddleIntent = $imageDir . "therm_intent.png";
	$imageMiddleGoal = $imageDir . "therm_goal.png";
	$imageIndex = $imageDir . "therm_index.png";
	$imageBottom =  $imageDir . "therm_bottom.png";
	$imageColorActual = $imageDir . "therm_color_actual.png";
	$imageColorPending = $imageDir . "therm_color_pending.png";
	$imageColorIntent = $imageDir . "therm_color_intent.png";

	$formThermTop = '
        <!-- Thermometer Begin -->
        <table cellpadding="0" cellspacing="4" border="0">
        <tr><td>
        <table cellpadding="0" cellspacing="0" border="0">
          <tr>
            <td colspan="2"><img src="' . $imageTop . '" width="58" height="6" border="0"></td>
          </tr>
          <tr>
            <td>
              <table cellpadding="0" cellspacing="0" border="0">';

	$formSection = '
          <tr><td><img src="{image}" width="26" height="{height}" border="0"></td></tr>';

	$formThermbottom = '
              </table>
            </td>
            <td><img src="' . $imageIndex . '" width="32" height="200" border="0"></td>
          </tr>
          <tr>
            <td colspan="2"><img src="' . $imageBottom . '" width="58" height="32" border="0"></td>
          </tr>
        </table>
        </td>
      </tr></table>';

	// legenda

	$legendaActual = "&euro; " . round($actualValue);
	$legendaPending = "&euro; " . round($pendingValue);
	$legendaIntent = "&euro; " . round($intentValue);
	$legendaTotal = "&euro; " . round($actualValue + $pendingValue + $intentValue);
	$formLegenda = '

        <table cellpadding="0" cellspacing="0" border="0">
          <tr><td><img src="' . $imageColorActual . '" width="9" height="9">&nbsp;</td><td><font size="1" face="Verdana, Arial, Helvetica, sans-serif"><b>'.$langs->trans("Paid").':<br>' . $legendaActual . '</b></font></td></tr>
          <tr><td><img src="' . $imageColorPending . '" width="9" height="9">&nbsp;</td><td><font size="1" face="Verdana, Arial, Helvetica, sans-serif">'.$langs->trans("Waiting").':<br>' . $legendaPending . '</font></td></tr>
          <tr><td><img src="' . $imageColorIntent . '" width="9" height="9">&nbsp;</td><td><font size="1" face="Verdana, Arial, Helvetica, sans-serif">'.$langs->trans("Promesses").':<br>' . $legendaIntent . '</font></td></tr>
          <tr><td>&nbsp;</td><td><font size="1" face="Verdana, Arial, Helvetica, sans-serif">Total:<br>' . $legendaTotal . '</font></td></tr>
        </table>

        <!-- Thermometer End -->';

	// check and edit some values

	$error = 0;
	if ( $maximumValue <= 0 || $height <= 0 || $actualValue < 0 || $pendingValue < 0 || $intentValue < 0)
	{
		return "The money meter could not be processed<br>\n";
	}
	if ( $actualValue > $maximumValue )
	{
		$actualValue = $maximumValue;
		$pendingValue = 0;
		$intentValue = 0;
	}
	else
	{
		if ( ($actualValue + $pendingValue) > $maximumValue )
		{
	  $pendingValue = $maximumValue - $actualValue;
	  $intentValue = 0;
		}
		else
		{
	  if ( ($actualValue + $pendingValue + $intentValue) > $maximumValue )
	  {
	  	$intentValue = $maximumValue - $actualValue - $pendingValue;
	  }
		}
	}

	// start writing the html (from bottom to top)

	// bottom
	$thermometer = $formThermbottom;

	// actual
	$sectionHeight = round(($actualValue / $maximumValue) * $height);
	$totalHeight = $totalHeight + $sectionHeight;
	if ( $sectionHeight > 0 )
	{
		$section = $formSection;
		$section = str_replace("{image}", $imageMiddleActual, $section);
		$section = str_replace("{height}", $sectionHeight, $section);
		$thermometer = $section . $thermometer;
	}

	// pending
	$sectionHeight = round(($pendingValue / $maximumValue) * $height);
	$totalHeight = $totalHeight + $sectionHeight;
	if ( $sectionHeight > 0 )
	{
		$section = $formSection;
		$section = str_replace("{image}", $imageMiddlePending, $section);
		$section = str_replace("{height}", $sectionHeight, $section);
		$thermometer = $section . $thermometer;
	}

	// intent
	$sectionHeight = round(($intentValue / $maximumValue) * $height);
	$totalHeight = $totalHeight + $sectionHeight;
	if ( $sectionHeight > 0 )
	{
		$section = $formSection;
		$section = str_replace("{image}", $imageMiddleIntent, $section);
		$section = str_replace("{height}", $sectionHeight, $section);
		$thermometer = $section . $thermometer;
	}

	// goal
	$sectionHeight = $height- $totalHeight;
	if ( $sectionHeight > 0 )
	{
		$section = $formSection;
		$section = str_replace("{image}", $imageMiddleGoal, $section);
		$section = str_replace("{height}", $sectionHeight, $section);
		$thermometer = $section . $thermometer;
	}

	// top
	$thermometer = $formThermTop . $thermometer;

	return $thermometer . $formLegenda;
}

?>
