<?php
/* Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2007 Regis Houssin        <regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 * or see http://www.gnu.org/
 */

/**
   \file		htdocs/lib/images.lib.php
   \brief		Ensemble de fonctions de base de traitement d'images
   \version		$Id$
*/



/*
 *    \brief     Création de 2 vignettes a partir d'un fichier image (une small et un mini)
 *    \brief     Les extension prise en compte sont jpg et png
 *    \param     file           Chemin du fichier image a redimensionner
 *    \param     maxWidth       Largeur maximum que dois faire la miniature (160 par défaut)
 *    \param     maxHeight      Hauteur maximum que dois faire l'image (120 par défaut)
 *    \param     extName        Extension pour différencier le nom de la vignette
 *    \param     quality        Qualité de compression (0=worst, 100=best)
 *    \return    string			Chemin de la vignette
 */
function vignette($file, $maxWidth = 160, $maxHeight = 120, $extName='_small', $quality=50)
{
	global $langs;

	dolibarr_syslog("functions.inc::vignette file=".$file." extName=".$extName." maxWidth=".$maxWidth." maxHeight=".$maxHeight." quality=".$quality);
	
	// Nettoyage parametres
	$file=trim($file);

	// Vérification des paramétres
	if (! $file)
	{
		// Si le fichier n'a pas été indiqué
		return 'Bad parameter file';
	}
	elseif (! file_exists($file))
	{
		// Si le fichier passé en paramétre n'existe pas
		return $langs->trans("ErrorFileNotFound",$file);
	}
	elseif(image_format_supported($file) < 0)
	{
		return 'This file '.$file.' does not seem to be an image format file name.';
	}
	elseif(!is_numeric($maxWidth) || empty($maxWidth) || $maxWidth < 0){
		// Si la largeur max est incorrecte (n'est pas numérique, est vide, ou est inférieure a 0)
		return 'Valeur de la largeur incorrecte.';
	}
	elseif(!is_numeric($maxHeight) || empty($maxHeight) || $maxHeight < 0){
		// Si la hauteur max est incorrecte (n'est pas numérique, est vide, ou est inférieure a 0)
		return 'Valeur de la hauteur incorrecte.';
	}

	$fichier = realpath($file); // Chemin canonique absolu de l'image
	$dir = dirname($file).'/'; // Chemin du dossier contenant l'image
	$dirthumb = $dir.'thumbs/'; // Chemin du dossier contenant les vignettes

	$infoImg = getimagesize($fichier); // Récupération des infos de l'image
	$imgWidth = $infoImg[0]; // Largeur de l'image
	$imgHeight = $infoImg[1]; // Hauteur de l'image

	// Si l'image est plus petite que la largeur et la hauteur max, on ne crée pas de vignette
	if ($infoImg[0] < $maxWidth && $infoImg[1] < $maxHeight)
	{
		// On cree toujours les vignettes
		dolibarr_syslog("File size is smaller than thumb size",LOG_DEBUG);
		//return 'Le fichier '.$file.' ne nécessite pas de création de vignette';
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
			return 'Creation de vignette impossible. Ce PHP ne supporte pas les fonctions du module GD '.$imgfonction;
		}
	}

	// On crée le répertoire contenant les vignettes
	if (! file_exists($dirthumb))
	{
		create_exdir($dirthumb);
	}

	// Initialisation des variables selon l'extension de l'image
	switch($infoImg[2])
	{
		case 1:	// Gif
			$img = imagecreatefromgif($fichier);
			$extImg = '.gif'; // Extension de l'image
			$newquality='NU';
			break;
		case 2:	// Jpg
			$img = imagecreatefromjpeg($fichier);
			$extImg = '.jpg'; // Extension de l'image
			$newquality=$quality;
			break;
		case 3:	// Png
			$img = imagecreatefrompng($fichier);
			$extImg = '.png';
			$newquality=$quality-100;
			$newquality=round(abs($quality-100)*9/100);
			break;
		case 4:	// Bmp
			$img = imagecreatefromwbmp($fichier);
			$extImg = '.bmp';
			$newquality='NU';
			break;
	}

	// Initialisation des dimensions de la vignette si elles sont supérieures a l'original
	if($maxWidth > $imgWidth){ $maxWidth = $imgWidth; }
	if($maxHeight > $imgHeight){ $maxHeight = $imgHeight; }

	$whFact = $maxWidth/$maxHeight; // Facteur largeur/hauteur des dimensions max de la vignette
	$imgWhFact = $imgWidth/$imgHeight; // Facteur largeur/hauteur de l'original

	// Fixe les dimensions de la vignette
	if($whFact < $imgWhFact){
		// Si largeur déterminante
		$thumbWidth  = $maxWidth;
		$thumbHeight = $thumbWidth / $imgWhFact;
	} else {
		// Si hauteur déterminante
		$thumbHeight = $maxHeight;
		$thumbWidth  = $thumbHeight * $imgWhFact;
	}
	$thumbHeight=round($thumbHeight);
	$thumbWidth=round($thumbWidth);
	
	// Create empty image
	if ($infoImg[2] == 1)
	{
		// Compatibilité image GIF
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
	switch($infoImg[2])
	{
		case 1:	// Gif
			$trans_colour = imagecolorallocate($imgThumb, 255, 255, 255); // On procéde autrement pour le format GIF
			imagecolortransparent($imgThumb,$trans_colour);
			break;
		case 2:	// Jpg
			$trans_colour = imagecolorallocatealpha($imgThumb, 255, 255, 255, 0);
			break;
		case 3:	// Png
			imagealphablending($imgThumb,false); // Pour compatibilité sur certain systéme
			$trans_colour = imagecolorallocatealpha($imgThumb, 255, 255, 255, 127);	// Keep transparent channel
			break;
		case 4:	// Bmp
			$trans_colour = imagecolorallocatealpha($imgThumb, 255, 255, 255, 0);
			break;
	}
	if (function_exists("imagefill")) imagefill($imgThumb, 0, 0, $trans_colour);

	dolibarr_syslog("vignette: convert image from ($imgWidth x $imgHeight) to ($thumbWidth x $thumbHeight) as $extImg, newquality=$newquality");
	//imagecopyresized($imgThumb, $img, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $imgWidth, $imgHeight); // Insére l'image de base redimensionnée
	imagecopyresampled($imgThumb, $img, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $imgWidth, $imgHeight); // Insére l'image de base redimensionnée

	$fileName = eregi_replace('(\.gif|\.jpeg|\.jpg|\.png|\.bmp)$','',$file);	// On enleve extension quelquesoit la casse
	$fileName = basename($fileName);
	$imgThumbName = $dirthumb.$fileName.$extName.$extImg; // Chemin complet du fichier de la vignette

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
			image2wmp($imgThumb, $imgThumbName);
			break;
	}

	// Free memory
	imagedestroy($imgThumb);

	return $imgThumbName;
}

?>
