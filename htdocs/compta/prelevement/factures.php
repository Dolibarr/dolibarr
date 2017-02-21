<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
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
 *     \file       htdocs/compta/prelevement/factures.php
 *     \ingroup    prelevement
 *     \brief      Page liste des factures prelevees
 */

require('../../main.inc.php');
require_once DOL_DOCUMENT_ROOT.'/core/lib/prelevement.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/prelevement/class/bonprelevement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/prelevement/class/rejetprelevement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

$langs->load("banks");
$langs->load("categories");
$langs->load("companies");
$langs->load('withdrawals');
$langs->load('bills');

// Securite acces client
if ($user->societe_id > 0) accessforbidden();

// Get supervariables
$prev_id = GETPOST('id','int');
$socid = GETPOST('socid','int');

$limit = GETPOST("limit")?GETPOST("limit","int"):$conf->liste_limit;
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortfield) $sortfield='p.ref';
if (! $sortorder) $sortorder='DESC';


/*
 * View
 */

$invoicetmp = new Facture($db);
$thirdpartytmp = new Societe($db);

llxHeader('',$langs->trans("WithdrawalsReceipts"));

if ($prev_id)
{
  	$bon = new BonPrelevement($db,"");

  	if ($bon->fetch($prev_id) == 0)
    {
    	$head = prelevement_prepare_head($bon);
		dol_fiche_head($head, 'invoices', $langs->trans("WithdrawalsReceipts"), '', 'payment');

      	print '<table class="border" width="100%">';

		print '<tr><td class="titlefield">'.$langs->trans("Ref").'</td><td>'.$bon->getNomUrl(1).'</td></tr>';
		print '<tr><td>'.$langs->trans("Date").'</td><td>'.dol_print_date($bon->datec,'day').'</td></tr>';
		print '<tr><td>'.$langs->trans("Amount").'</td><td>'.price($bon->amount).'</td></tr>';
		// Status
		print '<tr><td>'.$langs->trans('Status').'</td><td>'.$bon->getLibStatut(1).'</td></tr>';

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


// List of invoices
$sql = "SELECT pf.rowid";
$sql.= ",f.rowid as facid, f.facnumber as ref, f.total_ttc";
$sql.= ", s.rowid as socid, s.nom as name, pl.statut";
$sql.= " FROM ".MAIN_DB_PREFIX."prelevement_bons as p";
$sql.= ", ".MAIN_DB_PREFIX."prelevement_lignes as pl";
$sql.= ", ".MAIN_DB_PREFIX."prelevement_facture as pf";
$sql.= ", ".MAIN_DB_PREFIX."facture as f";
$sql.= ", ".MAIN_DB_PREFIX."societe as s";
$sql.= " WHERE pf.fk_prelevement_lignes = pl.rowid";
$sql.= " AND pl.fk_prelevement_bons = p.rowid";
$sql.= " AND f.fk_soc = s.rowid";
$sql.= " AND pf.fk_facture = f.rowid";
$sql.= " AND f.entity = ".$conf->entity;
if ($prev_id) $sql.= " AND p.rowid=".$prev_id;
if ($socid) $sql.= " AND s.rowid = ".$socid;

$sql.= $db->order($sortfield,$sortorder);

// Count total nb of records
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
    $result = $db->query($sql);
    $nbtotalofrecords = $db->num_rows($result);
}

$sql.= $db->plimit($limit + 1,$offset);

$result = $db->query($sql);
if ($result)
{
  	$num = $db->num_rows($result);
  	$i = 0;

  	$param = "&amp;id=".$prev_id;

	// Lines of title fields
	print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
    if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
    print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';
	print '<input type="hidden" name="viewstatut" value="'.$viewstatut.'">';

	$massactionbutton='';
	
	print_barre_liste('', $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, '', 0, '', '', $limit);
  	
  	print"\n<!-- debut table -->\n";
  	print '<table class="liste" width="100%">';
  	print '<tr class="liste_titre">';
  	print_liste_field_titre($langs->trans("Bill"),$_SERVER["PHP_SELF"],"p.ref",'',$param,'',$sortfield,$sortorder);
  	print_liste_field_titre($langs->trans("ThirdParty"),$_SERVER["PHP_SELF"],"s.nom",'',$param,'',$sortfield,$sortorder);
  	print_liste_field_titre($langs->trans("Amount"),$_SERVER["PHP_SELF"],"f.total_ttc","",$param,'align="right"',$sortfield,$sortorder);
  	print_liste_field_titre($langs->trans("StatusDebitCredit"),$_SERVER["PHP_SELF"],"","",$param,'align="center"',$sortfield,$sortorder);
	print_liste_field_titre('');
	print "</tr>\n";

  	$var=false;

  	$total = 0;

  	while ($i < min($num, $limit))
    {
     	$obj = $db->fetch_object($result);

     	$invoicetmp->id = $obj->facid;
     	$invoicetmp->ref = $obj->ref;
     	
     	$thirdpartytmp->id = $obj->socid;
     	$thirdpartytmp->name = $obj->name;
     	
      	print "<tr ".$bc[$var].">";
      	
      	print "<td>";
      	print $invoicetmp->getNomUrl(1);
        print "</td>\n";

      	print '<td>';
      	print $thirdpartytmp->getNomUrl(1);
      	print "</td>\n";

      	// Amount
      	print '<td align="right">'.price($obj->total_ttc)."</td>\n";

      	// Status of requests
      	print '<td align="center">';

      	if ($obj->statut == 0)
		{
	  		print '-';
		}
      	elseif ($obj->statut == 2)
		{
	  		print $langs->trans("StatusCredited");
		}
      	elseif ($obj->statut == 3)
		{
	  		print '<b>'.$langs->trans("StatusRefused").'</b>';
		}

      	print "</td>";
      	
      	print "<td></td>";
      	
      	print "</tr>\n";

      	$total += $obj->total_ttc;
      	$var=!$var;
      	$i++;
    }

  	if ($num > 0)
    {
      	print '<tr class="liste_total">';
     	print '<td>'.$langs->trans("Total").'</td>';
      	print '<td>&nbsp;</td>';
      	print '<td align="right">'.price($total)."</td>\n";
      	print '<td>&nbsp;</td>';
      	print '<td>&nbsp;</td>';
      	print "</tr>\n";
    }

  	print "</table>";
  	$db->free($result);
}
else
{
	dol_print_error($db);
}


llxFooter();

$db->close();
