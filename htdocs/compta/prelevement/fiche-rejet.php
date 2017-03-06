<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005      Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2010-2012 Juanjo Menent        <jmenent@2byte.es>
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
 * 		\file       htdocs/compta/prelevement/fiche-rejet.php
 *      \ingroup    prelevement
 *		\brief      Withdraw reject
 */

require('../../main.inc.php');
require_once DOL_DOCUMENT_ROOT.'/core/lib/prelevement.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/prelevement/class/bonprelevement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/prelevement/class/rejetprelevement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

$langs->load("banks");
$langs->load("categories");
$langs->load('withdrawals');
$langs->load('bills');

// Securite acces client
if ($user->societe_id > 0) accessforbidden();

// Get supervariables
$prev_id = GETPOST('id','int');
$page = GETPOST('page','int');

/*
 * View
 */
llxHeader('',$langs->trans("WithdrawalsReceipts"));

if ($prev_id)
{
  	$bon = new BonPrelevement($db,"");

  	if ($bon->fetch($prev_id) == 0)
    {
    	$head = prelevement_prepare_head($bon);
		dol_fiche_head($head, 'rejects', $langs->trans("WithdrawalsReceipts"), '', 'payment');

      	print '<table class="border" width="100%">';

		print '<tr><td class="titlefield">'.$langs->trans("Ref").'</td><td>'.$bon->getNomUrl(1).'</td></tr>';
		print '<tr><td>'.$langs->trans("Date").'</td><td>'.dol_print_date($bon->datec,'day').'</td></tr>';
		print '<tr><td>'.$langs->trans("Amount").'</td><td>'.price($bon->amount).'</td></tr>';

		// Status
		print '<tr><td>'.$langs->trans('Status').'</td>';
		print '<td>'.$bon->getLibStatut(1).'</td>';
		print '</tr>';

		if($bon->date_trans <> 0)
		{
			$muser = new User($db);
			$muser->fetch($bon->user_trans);

			print '<tr><td>'.$langs->trans("TransData").'</td><td>';
			print dol_print_date($bon->date_trans,'day');
			print ' '.$langs->trans("By").' '.$muser->getFullName($langs).'</td></tr>';
			print '<tr><td>'.$langs->trans("TransMetod").'</td><td>';
			print $bon->methodes_trans[$bon->method_trans];
			print '</td></tr>';
		}
		if($bon->date_credit <> 0)
		{
			print '<tr><td>'.$langs->trans('CreditDate').'</td><td>';
			print dol_print_date($bon->date_credit,'day');
			print '</td></tr>';
		}

		print '</table>';

		print '<br>';

		print '<table class="border" width="100%"><tr><td class="titlefield">';
		print $langs->trans("WithdrawalFile").'</td><td>';
		$relativepath = 'receipts/'.$bon->ref.'.xml';
		print '<a data-ajax="false" href="'.DOL_URL_ROOT.'/document.php?type=text/plain&amp;modulepart=prelevement&amp;file='.urlencode($relativepath).'">'.$relativepath.'</a>';
		print '</td></tr></table>';

		dol_fiche_end();

    }
  	else
    {
      	dol_print_error($db);
    }
}

$rej = new RejetPrelevement($db, $user);

/*
 * Liste des factures
 */
$sql = "SELECT pl.rowid, pl.amount, pl.statut";
$sql.= " , s.rowid as socid, s.nom as name";
$sql.= " , pr.motif, pr.afacturer, pr.fk_facture";
$sql.= " FROM ".MAIN_DB_PREFIX."prelevement_bons as p";
$sql.= " , ".MAIN_DB_PREFIX."prelevement_lignes as pl";
$sql.= " , ".MAIN_DB_PREFIX."societe as s";
$sql.= " , ".MAIN_DB_PREFIX."prelevement_rejet as pr";
$sql.= " WHERE p.rowid=".$prev_id;
$sql.= " AND pl.fk_prelevement_bons = p.rowid";
$sql.= " AND p.entity = ".$conf->entity;
$sql.= " AND pl.fk_soc = s.rowid";
$sql.= " AND pl.statut = 3 ";
$sql.= " AND pr.fk_prelevement_lignes = pl.rowid";
if ($socid) $sql.= " AND s.rowid = ".$socid;
$sql.= " ORDER BY pl.amount DESC";

$resql = $db->query($sql);
if ($resql)
{
 	$num = $db->num_rows($resql);

  	print"\n<!-- debut table -->\n";
  	print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
  	print '<tr class="liste_titre">';
  	print '<td>'.$langs->trans("Line").'</td><td>'.$langs->trans("ThirdParty").'</td><td align="right">'.$langs->trans("Amount").'</td>';
  	print '<td>'.$langs->trans("Reason").'</td><td align="center">'.$langs->trans("ToBill").'</td><td align="center">'.$langs->trans("Invoice").'</td></tr>';

  	$var=True;
	$total = 0;

	if ($num > 0)
	{
      	$i = 0;
	    while ($i < $num)
        {
    		$obj = $db->fetch_object($resql);
    
    		print "<tr ".$bc[$var]."><td>";
    
    		print '<a href="'.DOL_URL_ROOT.'/compta/prelevement/ligne.php?id='.$obj->rowid.'">';
    		print img_picto('', 'statut'.$obj->statut).' ';
    		print substr('000000'.$obj->rowid, -6);
    		print '</a></td>';
    		print '<td><a href="'.DOL_URL_ROOT.'/comm/card.php?socid='.$obj->socid.'">'.$obj->name."</a></td>\n";
    
    		print '<td align="right">'.price($obj->amount)."</td>\n";
    		print '<td>'.$rej->motifs[$obj->motif].'</td>';
    
    		print '<td align="center">'.yn($obj->afacturer).'</td>';
    		print '<td align="center">'.$obj->fk_facture.'</td>';
    		print "</tr>\n";
    
    		$total += $obj->amount;
    		$var=!$var;
    		$i++;
    	}
	}
	else
	{
	   print '<tr><td colspan="5" class="opacitymedium">'.$langs->trans("None").'</td></tr>';    
	}
	
  	if ($num > 0)
    {
    	print '<tr class="liste_total"><td>&nbsp;</td>';
    	print '<td class="liste_total">'.$langs->trans("Total").'</td>';
    	print '<td align="right">'.price($total)."</td>\n";
    	print '<td colspan="3">&nbsp;</td>';
    	print "</tr>\n";
    }
    print "</table>\n";
	$db->free($resql);
}
else
{
	dol_print_error($db);
}

llxFooter();
$db->close();
