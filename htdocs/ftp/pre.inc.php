<?php
/* Copyright (C) 2008-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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

/**
 *		\file 		htdocs/ftp/pre.inc.php
 *		\ingroup    ftp
 *		\brief      File to manage left menu for FTP module
 */

require (realpath(dirname(__FILE__)) . "/../main.inc.php");

$user->getrights('ecm');

/**
 * Replace the default llxHeader function
 *
 * @param 	string	$head			Optionnal head lines
 * @param 	string	$title			HTML title
 * @param 	string	$help_url		Link to online url help
 * @param 	string	$morehtml		More content into html header
 * @param 	string 	$target			Force target on menu links
 * @param 	int    	$disablejs		More content into html header
 * @param 	int    	$disablehead	More content into html header
 * @param 	array  	$arrayofjs		Array of complementary js files
 * @param 	array  	$arrayofcss		Array of complementary css files
 * @return	none
 */
function llxHeader($head = '', $title='', $help_url='', $morehtml='', $target='', $disablejs=0, $disablehead=0, $arrayofjs='', $arrayofcss='')
{
	global $conf,$langs,$user;
	$langs->load("ftp");

	top_htmlhead($head, $title, $disablejs, $disablehead, $arrayofjs, $arrayofcss);	// Show html headers
	top_menu($head, $title, $target, $disablejs, $disablehead, $arrayofjs, $arrayofcss);	// Show html headers

	$menu = new Menu();

	$MAXFTP=20;
	$i=1;
	while ($i <= $MAXFTP)
	{
		$paramkey='FTP_NAME_'.$i;
		//print $paramkey;
		if (! empty($conf->global->$paramkey))
		{
			$link="/ftp/index.php?idmenu=".$_SESSION["idmenu"]."&numero_ftp=".$i;

			$menu->add($link, dol_trunc($conf->global->$paramkey,24));
		}
		$i++;
	}


	left_menu($menu->liste, $help_url, $morehtml, '', 1);
	main_area();
}
?>
