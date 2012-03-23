<?php
/* Copyright (C) 2006      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *		\file   	htdocs/compta/paiement/cheque/pre.inc.php
 *		\ingroup    compta
 *		\brief  	Fichier gestionnaire du menu cheques
 */

require_once(realpath(dirname(__FILE__)) . "/../../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/compta/bank/class/account.class.php");

$langs->load("banks");
$langs->load("categories");


/**
 * Replace the default llxHeader function
 *
 * @param 	string 	$head		Optionnal head lines
 * @param 	string 	$title		HTML title
 * @param 	string 	$help_url	Link to online url help to show on left menu
 * @param 	string 	$target		Force target on menu links
 * @param 	int    	$disablejs	More content into html header
 * @param 	int    	$disablehead	More content into html header
 * @param 	array  	$arrayofjs	Array of complementary js files
 * @param 	array  	$arrayofcss	Array of complementary css files
 * @return	none
 */
function llxHeader($head = '', $title='', $help_url='', $target='', $disablejs=0, $disablehead=0, $arrayofjs='', $arrayofcss='')
{
	global $db, $user, $conf, $langs;

	top_htmlhead($head, $title, $disablejs, $disablehead, $arrayofjs, $arrayofcss);	// Show html headers
	top_menu($head, $title, $target, $disablejs, $disablehead, $arrayofjs, $arrayofcss);	// Show html headers

	$menu = new Menu();

	// Entry for each bank account
	if ($user->rights->banque->lire)
	{
		$sql = "SELECT rowid, label, courant, rappro, courant";
		$sql.= " FROM ".MAIN_DB_PREFIX."bank_account";
		$sql.= " WHERE entity = ".$conf->entity;
		$sql.= " AND clos = 0";
        $sql.= " ORDER BY label";

		$resql = $db->query($sql);
		if ($resql)
		{
			$numr = $db->num_rows($resql);
			$i = 0;

			if ($numr > 0) 	$menu->add('/compta/bank/index.php',$langs->trans("BankAccounts"),0,$user->rights->banque->lire);

			while ($i < $numr)
			{
				$objp = $db->fetch_object($resql);
				$menu->add('/compta/bank/fiche.php?id='.$objp->rowid,$objp->label,1,$user->rights->banque->lire);
                if ($objp->rappro && $objp->courant != 2)  // If not cash account and can be reconciliate
                {
				    $menu->add('/compta/bank/rappro.php?account='.$objp->rowid,$langs->trans("Conciliate"),2,$user->rights->banque->consolidate);
                }
				$i++;
			}
		}
		else dol_print_error($db);
		$db->free($resql);
	}

	left_menu('', $help_url, '', $menu->liste, 1);
    main_area();
}

?>
