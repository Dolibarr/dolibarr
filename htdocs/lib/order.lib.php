<?php
/* Copyright (C) 2006-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007      Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 *  \file       htdocs/lib/order.lib.php
 *  \brief      Ensemble de fonctions de base pour le module commande
 *  \ingroup    commande
 *  \version    $Id$
 */

function commande_prepare_head($commande)
{
	global $langs, $conf, $user;
	if ($conf->expedition->enabled) $langs->load("sendings");
	$langs->load("orders");

	$h = 0;
	$head = array();

	if ($conf->commande->enabled && $user->rights->commande->lire)
	{
		$head[$h][0] = DOL_URL_ROOT.'/commande/fiche.php?id='.$commande->id;
		$head[$h][1] = $langs->trans("OrderCard");
		$head[$h][2] = 'order';
		$h++;
	}

	if (($conf->expedition_bon->enabled && $user->rights->expedition->lire)
	|| ($conf->livraison_bon->enabled && $user->rights->expedition->livraison->lire))
	{
		$head[$h][0] = DOL_URL_ROOT.'/expedition/commande.php?id='.$commande->id;
		if ($conf->expedition_bon->enabled) $text=$langs->trans("Sendings");
		if ($conf->livraison_bon->enabled)  $text.='/'.$langs->trans("Receivings");
		$head[$h][1] = $text;
		$head[$h][2] = 'shipping';
		$h++;
	}

	// Commande a facturer
	if ($conf->facture->enabled)
	{
		$head[$h][0] = DOL_URL_ROOT.'/compta/commande/fiche.php?id='.$commande->id;
		$head[$h][1] = $langs->trans("Compta");
		$head[$h][2] = 'accountancy';
		$h++;
	}

	if ($conf->use_preview_tabs)
	{
		$head[$h][0] = DOL_URL_ROOT.'/commande/apercu.php?id='.$commande->id;
		$head[$h][1] = $langs->trans("Preview");
		$head[$h][2] = 'preview';
		$h++;
	}

	$head[$h][0] = DOL_URL_ROOT.'/commande/contact.php?id='.$commande->id;
	$head[$h][1] = $langs->trans('OrderContact');
	$head[$h][2] = 'contact';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/commande/document.php?id='.$commande->id;
	/*$filesdir = $conf->commande->dir_output . "/" . dol_sanitizeFileName($commande->ref);
	include_once(DOL_DOCUMENT_ROOT.'/lib/files.lib.php');
	$listoffiles=dol_dir_list($filesdir,'files',1);
	$head[$h][1] = (sizeof($listoffiles)?$langs->trans('DocumentsNb',sizeof($listoffiles)):$langs->trans('Documents'));*/
	$head[$h][1] = $langs->trans('Documents');
	$head[$h][2] = 'documents';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/commande/note.php?id='.$commande->id;
	$head[$h][1] = $langs->trans('Notes');
	$head[$h][2] = 'note';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/commande/info.php?id='.$commande->id;
	$head[$h][1] = $langs->trans("Info");
	$head[$h][2] = 'info';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:MyModule:@mymodule:/dolibarr/mymodule/mypage.php?id=__ID__');
	if (is_array($conf->tabs_modules['order']))
	{
		$i=0;
		foreach ($conf->tabs_modules['order'] as $value)
		{
			$values=split(':',$value);
			if ($values[2]) $langs->load($values[2]);
			$head[$h][0] = eregi_replace('__ID__',$commande->id,$values[3]);
			$head[$h][1] = $langs->trans($values[1]);
			$head[$h][2] = 'tab'.$values[1];
			$h++;
		}
	}

	return $head;
}

?>
