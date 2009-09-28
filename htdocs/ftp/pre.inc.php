<?php
/* Copyright (C) 2008-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *		\file 		htdocs/ftp/pre.inc.php
 *		\ingroup    ftp
 *		\brief      File to manage left menu for FTP module
 *		\version    $Id$
 */

require ("../main.inc.php");

$user->getrights('ecm');

function llxHeader($head = '', $title='', $help_url='', $morehtml='')
{
	global $conf,$langs,$user;
	$langs->load("ftp");

	top_menu($head, $title);

	$menu = new Menu();

	$MAXFTP=20;
	$i=1;
	while ($i <= $MAXFTP)
	{
		$paramkey='FTP_NAME_'.$i;
		//print $paramkey;
		if (! empty($conf->global->$paramkey))
		{
			$link=DOL_URL_ROOT."/ftp/index.php?idmenu=".$_SESSION["idmenu"]."&numero_ftp=".$i;

			$menu->add($link, dol_trunc($conf->global->$paramkey,24));
		}
		$i++;
	}


	left_menu($menu->liste, $help_url, $morehtml);
}
?>
