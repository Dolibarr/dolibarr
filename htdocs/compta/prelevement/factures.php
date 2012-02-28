<?PHP
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005      Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2010-2011 Juanjo Menent        <jmenent@2byte.es>
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
 *     \file       htdocs/compta/prelevement/factures.php
 *     \ingroup    prelevement
 *     \brief      Page liste des factures prelevees
 */

require("../bank/pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/prelevement.lib.php");
require_once(DOL_DOCUMENT_ROOT."/compta/prelevement/class/bon-prelevement.class.php");
require_once(DOL_DOCUMENT_ROOT."/compta/prelevement/class/rejet-prelevement.class.php");
require_once(DOL_DOCUMENT_ROOT."/compta/paiement/class/paiement.class.php");

$langs->load("companies");
$langs->load("categories");

// Securite acces client
if ($user->societe_id > 0) accessforbidden();

// Get supervariables
$prev_id = GETPOST('id','int');
$socid = GETPOST('socid','int');
$page = GETPOST("page");
$sortorder = ((GETPOST("sortorder")=="")) ? "DESC" : GETPOST("sortorder");
$sortfield = ((GETPOST("sortfield")=="")) ? "p.ref" : GETPOST("sortfield");

llxHeader('',$langs->trans("WithdrawalReceipt"));

if ($prev_id)
{
  	$bon = new BonPrelevement($db,"");

  	if ($bon->fetch($prev_id) == 0)
    {
    	$head = prelevement_prepare_head($bon);	
      	dol_fiche_head($head, 'invoices', $langs->trans("WithdrawalReceipt"), '', 'payment');

      	print '<table class="border" width="100%">';
      	print '<tr><td width="20%">'.$langs->trans("Ref").'</td><td>'.$bon->getNomUrl(1).'</td></tr>';
		print '<tr><td width="20%">'.$langs->trans("Date").'</td><td>'.dol_print_date($bon->datec,'day').'</td></tr>';
		print '<tr><td width="20%">'.$langs->trans("Amount").'</td><td>'.price($bon->amount).'</td></tr>';
		print '<tr><td width="20%">'.$langs->trans("File").'</td><td>';

		$relativepath = 'receipts/'.$bon->ref;

		print '<a href="'.DOL_URL_ROOT.'/document.php?type=text/plain&amp;modulepart=prelevement&amp;file='.urlencode($relativepath).'">'.$relativepath.'</a>';

		print '</td></tr>';

		// Status
		print '<tr><td width="20%">'.$langs->trans('Status').'</td>';
		print '<td>'.$bon->getLibStatut(1).'</td>';
		print '</tr>';
		
		if($bon->date_trans <> 0)
		{
			$muser = new User($db);
			$muser->fetch($bon->user_trans);

			print '<tr><td width="20%">'.$langs->trans("TransData").'</td><td>';
			print dol_print_date($bon->date_trans,'day');
			print ' '.$langs->trans("By").' '.$muser->getFullName($langs).'</td></tr>';
			print '<tr><td width="20%">'.$langs->trans("TransMetod").'</td><td>';
			print $bon->methodes_trans[$bon->method_trans];
			print '</td></tr>';
		}
		if($bon->date_credit <> 0)
		{
			print '<tr><td width="20%">'.$langs->trans('CreditDate').'</td><td>';
			print dol_print_date($bon->date_credit,'day');
			print '</td></tr>';
		}
		
      	print '</table>';

      	print '</div>';
    }
  	else
    {
      	dol_print_error($db);
    }
}

$offset = $conf->liste_limit * $page ;

/*
 * Liste des factures
 */
$sql = "SELECT pf.rowid";
$sql.= ",f.rowid as facid, f.facnumber as ref, f.total_ttc";
$sql.= ", s.rowid as socid, s.nom, pl.statut";
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
$sql.= " ORDER BY $sortfield $sortorder ";
$sql.= $db->plimit($conf->liste_limit+1, $offset);

$result = $db->query($sql);

if ($result)
{
  	$num = $db->num_rows($result);
  	$i = 0;

  	$urladd = "&amp;id=".$prev_id;

  	print_barre_liste("", $page, "factures.php", $urladd, $sortfield, $sortorder, '', $num);

  	print"\n<!-- debut table -->\n";
  	print '<table class="liste" width="100%">';
  	print '<tr class="liste_titre">';
  	print_liste_field_titre($langs->trans("Bill"),"factures.php","p.ref",'',$urladd,'class="liste_titre"',$sortfield,$sortorder);
  	print_liste_field_titre($langs->trans("ThirdParty"),"factures.php","s.nom",'',$urladd,'class="liste_titre"',$sortfield,$sortorder);
  	print_liste_field_titre($langs->trans("Amount"),"factures.php","f.total_ttc","",$urladd,'class="liste_titre" align="center"',$sortfield,$sortorder);
  	print '<td class="liste_titre" colspan="2">&nbsp;</td></tr>';

  	$var=false;

  	$total = 0;

  	while ($i < min($num,$conf->liste_limit))
    {
     	$obj = $db->fetch_object($result);

      	print "<tr $bc[$var]><td>";

      	print '<a href="'.DOL_URL_ROOT.'/compta/facture.php?facid='.$obj->facid.'">';
      	print img_object($langs->trans("ShowBill"),"bill");
      	print '</a>&nbsp;';

      	print '<a href="'.DOL_URL_ROOT.'/compta/facture.php?facid='.$obj->facid.'">'.$obj->ref."</a></td>\n";

      	print '<td><a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$obj->socid.'">';
      	print img_object($langs->trans("ShowCompany"),"company"). ' '.stripslashes($obj->nom)."</a></td>\n";

      	print '<td align="center">'.price($obj->total_ttc)."</td>\n";

      	print '<td>';

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

      	print "</td></tr>\n";

      	$total += $obj->total_ttc;
      	$var=!$var;
      	$i++;
    }

  	if($socid)
    {
      	print "<tr $bc[$var]><td>";

     	print '<td>'.$langs->trans("Total").'</td>';

      	print '<td align="center">'.price($total)."</td>\n";

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

$db->close();

llxFooter();
?>
