<?php
/* Copyright (C) 2006      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copytight (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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
 *		\file   	htdocs/compta/paiement/cheque/pre.inc.php
 *		\ingroup    compta
 *		\brief  	Fichier gestionnaire du menu cheques
 *		\version	$Id$
 */

require("../../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT.'/compta/paiement/cheque/class/remisecheque.class.php');

$langs->load("bills");
$langs->load("compta");
$langs->load("banks");
$langs->load("categories");

function llxHeader($head = '', $title='', $help_url='', $target='', $disablejs=0, $disablehead=0, $arrayofjs='', $arrayofcss='')
{
	global $db, $user, $conf, $langs;

	top_htmlhead($head, $title, $disablejs, $disablehead, $arrayofjs, $arrayofcss);	// Show html headers
	top_menu($head, $title, $target, $disablejs, $disablehead, $arrayofjs, $arrayofcss);	// Show html headers

	$menu = new Menu();

	// Entry for each bank account
	if ($user->rights->banque->lire)
	{
		$sql = "SELECT rowid, label, courant";
		$sql.= " FROM ".MAIN_DB_PREFIX."bank_account";
		$sql.= " WHERE entity = ".$conf->entity;
		$sql.= " AND clos = 0";

		$resql = $db->query($sql);
		if ($resql)
		{
			$numr = $db->num_rows($resql);
			$i = 0;

			if ($numr > 0) 	$menu->add("/compta/bank/index.php",$langs->trans("BankAccounts"),0,$user->rights->banque->lire);

			while ($i < $numr)
			{
				$objp = $db->fetch_object($resql);
				$menu->add_submenu("/compta/bank/fiche.php?id=".$objp->rowid,$objp->label,1,$user->rights->banque->lire);
/*
				$menu->add_submenu("/compta/bank/annuel.php?account=".$objp->rowid ,$langs->trans("IOMonthlyReporting"));
				$menu->add_submenu("/compta/bank/graph.php?account=".$objp->rowid ,$langs->trans("Graph"));
				if ($objp->courant != 2) $menu->add_submenu("/compta/bank/releve.php?account=".$objp->rowid ,$langs->trans("AccountStatements"));
*/
				$i++;
			}
		}
		$db->free($resql);
	}

	left_menu('', $help_url, '', $menu->liste, 1);
	main_area();
}

?>
