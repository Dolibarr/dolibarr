#!/usr/bin/php
<?php
/* Copyright (C) 2007 Regis Houssin  <regis@dolibarr.fr>
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
 */

/**
    	\file       dev/fpdf/convert.php
		\ingroup    core
		\brief      Convert TTF for FPDF
		\version    $Id$
*/

// Test si mode batch
$sapi_type = php_sapi_name();
if (substr($sapi_type, 0, 3) == 'cgi') {
    echo "Erreur: Vous utilisez l'interpreteur PHP pour le mode CGI. Pour executer convert.php en ligne de commande, vous devez utiliser l'interpreteur PHP pour le mode CLI.\n";
    exit;
}

require('./makefont/makefont.php');

if ((! isset($argv[1]) || ! $argv[1]) && (! isset($argv[2]) || ! $argv[2])) {
    print "Usage:  convert.php TYPE_OF_FONT NAME_OF_FONT [ENCODAGE]\n";   
    exit;
}

$fontType = $argv[1];
$fontName = $argv[2];
$enc      = $argv[3];

//On v�rifie le type d'encodage
if ($enc)
{
	$file='./makefont/'.strtolower($enc).'.map';
	$a=file($file);
	if(empty($a))
	{
		print 'Error: encoding not found: '.$enc;
		exit;
	}
}
else
{
	$enc = "cp1250";
}

//On d�termine le type de la police
if ($fontType == 1)
{
	$font = './tmp/'.$fontName.'.ttf';
	$type = 'TrueType';
}
else if ($fontType == 2)
{
	$font = '';
	$type = 'Type1';
}
else
{
	print "Error: value 1 for TrueType or 2 for Type1\n";
	exit;
} 

//Fichier AFM
$afmFile = './tmp/'.$fontName.'.afm';

//construction du php
MakeFont($font,$afmFile,$enc,'',$type);
?>