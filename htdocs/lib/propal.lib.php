<?php
/* Copyright (C) 2006-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\file       htdocs/lib/propal.lib.php
 *	\brief      Ensemble de fonctions de base pour le module propal
 *	\ingroup    propal
 *	\version    $Id$
 *
 * 	Ensemble de fonctions de base de dolibarr sous forme d'include
 */

function propal_prepare_head($propal)
{
	global $langs, $conf, $user;
	$langs->load("propal");
	$langs->load("compta");

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/comm/propal.php?propalid='.$propal->id;
	$head[$h][1] = $langs->trans('CommercialCard');
	$head[$h][2] = 'comm';
	$h++;

	if ((!$conf->commande->enabled &&
	(($conf->expedition_bon->enabled && $user->rights->expedition->lire)
	|| ($conf->livraison_bon->enabled && $user->rights->expedition->livraison->lire))))
	{
		$langs->load("sendings");
		$head[$h][0] = DOL_URL_ROOT.'/expedition/propal.php?propalid='.$propal->id;
		if ($conf->expedition_bon->enabled) $text=$langs->trans("Sendings");
		if ($conf->livraison_bon->enabled)  $text.='/'.$langs->trans("Receivings");
		$head[$h][1] = $text;
		$head[$h][2] = 'shipping';
		$h++;
	}

	$head[$h][0] = DOL_URL_ROOT.'/compta/propal.php?propalid='.$propal->id;
	$head[$h][1] = $langs->trans('AccountancyCard');
	$head[$h][2] = 'compta';
	$h++;

	if ($conf->use_preview_tabs)
	{
		$head[$h][0] = DOL_URL_ROOT.'/comm/propal/apercu.php?propalid='.$propal->id;
		$head[$h][1] = $langs->trans("Preview");
		$head[$h][2] = 'preview';
		$h++;
	}

	$head[$h][0] = DOL_URL_ROOT.'/comm/propal/contact.php?propalid='.$propal->id;
	$head[$h][1] = $langs->trans('ProposalContact');
	$head[$h][2] = 'contact';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/comm/propal/note.php?propalid='.$propal->id;
	$head[$h][1] = $langs->trans('Notes');
	$head[$h][2] = 'note';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/comm/propal/document.php?propalid='.$propal->id;
	/*$filesdir = $conf->propale->dir_output . "/" . dol_sanitizeFileName($propal->ref);
	include_once(DOL_DOCUMENT_ROOT.'/lib/files.lib.php');
	$listoffiles=dol_dir_list($filesdir,'files',1);
	$head[$h][1] = (sizeof($listoffiles)?$langs->trans('DocumentsNb',sizeof($listoffiles)):$langs->trans('Documents'));*/
	$head[$h][1] = $langs->trans('Documents');
	$head[$h][2] = 'document';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/comm/propal/info.php?propalid='.$propal->id;
	$head[$h][1] = $langs->trans('Info');
	$head[$h][2] = 'info';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:MyModule:@mymodule:/dolibarr/mymodule/mypage.php?id=__ID__');
	if (is_array($conf->tabs_modules['propal']))
	{
		$i=0;
		foreach ($conf->tabs_modules['propal'] as $value)
		{
			$values=split(':',$value);
			if ($values[2]) $langs->load($values[2]);
			$head[$h][0] = eregi_replace('__ID__',$propal->id,$values[3]);
			$head[$h][1] = $langs->trans($values[1]);
			$head[$h][2] = 'tab'.$values[1];
			$h++;
		}
	}

	return $head;
}

?>