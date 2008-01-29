<?php
/* Copyright (C) 2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
		\file 		htdocs/phenix/pre.inc.php
		\ingroup    phenix
		\brief      Fichier de gestion du menu gauche du module phenix
		\version    $Id$
*/

require ("../main.inc.php");


function llxHeader($head = "", $title="", $help_url='')
{
	global $langs;
	
	top_menu($head, $title);
	
	$menu = new Menu();
	
	
	
	left_menu($menu->liste, $help_url);
}
?>
