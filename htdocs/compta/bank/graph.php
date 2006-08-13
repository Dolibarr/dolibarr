<?php
/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 *
 * $Id$
 * $Source$
 */

/**	  \file       htdocs/compta/bank/graph.php
	  \ingroup    banque
	  \brief      Page de détail des transactions bancaires
	  \version    $Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/bank.lib.php");

if (!$user->rights->banque->lire)
  accessforbidden();

$account = $_GET["account"];



llxHeader();

if ($account > 0)
{
    $datetime = time();
    $month = strftime("%m", $datetime);
    $year = strftime("%Y", $datetime);
    
    $acct = new Account($db);
    $acct->fetch($account);
    

	$titre=$langs->trans("FinancialAccount")." : ".$acct->label;
	print_fiche_titre($titre,$mesg);

	// Onglets
	$head=bank_prepare_head($acct);
	dolibarr_fiche_head($head,'graph',$langs->trans("FinancialAccount"),0);
	
	print '<table class="notopnoleftnoright" width="100%">';
    print '<tr><td>';
    $file = "solde.$account.$year.png";

	if (! file_exists($conf->banque->dir_images."/".$file))
	{
		print "Pour générer ou regénérer les graphiques, lancer le script ./scripts/banque/graph-solde.php en ligne de commande.<br>";
		print '<br>';
	}
	else
	{
		print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=bank&file='.$file.'" alt="" title="">';
	}

    print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=bank&file='.$file.'" alt="" title="">';
    print '</td></tr><tr><td>';
    
    $file = "mouvement.$account.$year.png";
    
    print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=bank&file='.$file.'" alt="" title="">';
    

    print '</td></tr><tr><td>';
    
    $file = "solde.$account.png";
    
    print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=bank&file='.$file.'" alt="" title="">';
    
    print '</td></tr></table>';
    
	print "\n</div>\n";
    
}
?>
