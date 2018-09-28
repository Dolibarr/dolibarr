<?php
/* Copyright (C) 2018	Andreu Bisquerra	<jove@bisquerra.com>
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
 */

define('NOCSRFCHECK',1);	// This is main home and login page. We must be able to go on it from another web site.

$res=@include("../../main.inc.php");
if (! $res) $res=@include("../../../main.inc.php");

$id= GETPOST('id');
$w= GETPOST('w');
$h= GETPOST('h');
$query= GETPOST('query');

header('Content-Type: image/jpeg');
header('Cache-Control: max-age=604800, public, must-revalidate');
header('Pragma: cache');

require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
$objProd = new Product($db);
$objProd->fetch($id);

if (strpos(DOL_VERSION, '3.7') !== false){
	$pdir = get_exdir($id,2) . $id ."/photos/"; //Dolibarr 3.7.x
	//$dir = $conf->product->multidir_output[$objProd->entity].'/'.$pdir; // Dolibarr 3.6.x
	$dir = $conf->product->multidir_output[$objProd->entity].'/'.dol_sanitizeFileName($objProd->ref).'/'; //Dolibarr 3.7.x
}
else{
	$dir .= get_exdir(0,0,0,0,$objProd,'product').$objProd->ref.'/';
	$pdir .= get_exdir(0,0,0,0,$objProd,'product').$objProd->ref.'/';
}

foreach ($objProd->liste_photos($dir) as $key => $obj)
	{
	$filename=$obj['photo'];
	}
$filename = $dir.$filename;

if (!file_exists($filename)){
	$dir = $conf->product->multidir_output[$objProd->entity].'/'.$pdir;
	foreach ($objProd->liste_photos($dir) as $key => $obj)
	{
	$filename=$obj['photo'];
	}
$filename = $dir.$filename;
}

if (!file_exists($filename)) $filename="empty.jpg";

$file_extension = strtolower(substr(strrchr($filename,"."),1));

switch( $file_extension ) {
    case "gif": $ctype="image/gif"; break;
    case "png": $ctype="image/png"; break;
    case "jpeg":
    case "jpg": $ctype="image/jpeg"; break;
    default:
}

header('Content-type: ' . $ctype);
readfile($filename);

?>