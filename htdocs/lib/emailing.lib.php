<?php
/* Copyright (C) 2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	    \file       htdocs/lib/emailing.lib.php
 *		\brief      Library file with function for emailing module
 *		\version    $Id$
 */

/**
 * Enter description here...
 *
 * @param unknown_type $contrat
 * @return unknown
 */
function emailing_prepare_head($mil)
{
	global $langs, $conf;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT."/comm/mailing/fiche.php?id=".$mil->id;
	$head[$h][1] = $langs->trans("MailCard");
	$head[$h][2] = 'card';
	$h++;

	$head[$h][0] = DOL_URL_ROOT."/comm/mailing/cibles.php?id=".$mil->id;
	$head[$h][1] = $langs->trans("MailRecipients");
	$head[$h][2] = 'targets';
	$h++;

	$head[$h][0] = DOL_URL_ROOT."/comm/mailing/info.php?id=".$mil->id;
	$head[$h][1] = $langs->trans("Info");
	$head[$h][2] = 'info';
	$h++;

	return $head;
}

?>