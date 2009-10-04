<?php
/* Copyright (C) 2005-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	    \file       htdocs/lib/invoice.lib.php
 *		\brief      Ensemble de fonctions de base pour le module factures
 * 		\ingroup	invoice
 *		\version    $Id$ *
 *
 *		Ensemble de fonctions de base de dolibarr sous forme d'include
 */

function facture_prepare_head($fac)
{
	global $langs, $conf;
	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/compta/facture.php?facid='.$fac->id;
	$head[$h][1] = $langs->trans('CardBill');
	$head[$h][2] = 'compta';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/compta/facture/contact.php?facid='.$fac->id;
	$head[$h][1] = $langs->trans('BillContacts');
	$head[$h][2] = 'contact';
	$h++;

	if ($conf->use_preview_tabs)
	{
		$head[$h][0] = DOL_URL_ROOT.'/compta/facture/apercu.php?facid='.$fac->id;
		$head[$h][1] = $langs->trans('Preview');
		$head[$h][2] = 'preview';
		$h++;
	}

	if ($fac->mode_reglement_code == 'PRE')
	{
		$head[$h][0] = DOL_URL_ROOT.'/compta/facture/prelevement.php?facid='.$fac->id;
		$head[$h][1] = $langs->trans('StandingOrders');
		$head[$h][2] = 'standingorders';
		$h++;
	}

	$head[$h][0] = DOL_URL_ROOT.'/compta/facture/note.php?facid='.$fac->id;
	$head[$h][1] = $langs->trans('Notes');
	$head[$h][2] = 'note';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/compta/facture/document.php?facid='.$fac->id;
	/*$filesdir = $conf->facture->dir_output . "/" . dol_sanitizeFileName($fac->ref);
	include_once(DOL_DOCUMENT_ROOT.'/lib/files.lib.php');
	$listoffiles=dol_dir_list($filesdir,'files',1);
	$head[$h][1] = (sizeof($listoffiles)?$langs->trans('DocumentsNb',sizeof($listoffiles)):$langs->trans('Documents'));*/
	$head[$h][1] = $langs->trans('Documents');
	$head[$h][2] = 'documents';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/compta/facture/info.php?facid='.$fac->id;
	$head[$h][1] = $langs->trans('Info');
	$head[$h][2] = 'info';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:MyModule:@mymodule:/dolibarr/mymodule/mypage.php?id=__ID__');
	if (is_array($conf->tabs_modules['invoice']))
	{
		$i=0;
		foreach ($conf->tabs_modules['invoice'] as $value)
		{
			$values=split(':',$value);
			if ($values[2]) $langs->load($values[2]);
			$head[$h][0] = eregi_replace('__ID__',$fac->id,$values[3]);
			$head[$h][1] = $langs->trans($values[1]);
			$head[$h][2] = 'tab'.$values[1];
			$h++;
		}
	}

	return $head;
}

?>