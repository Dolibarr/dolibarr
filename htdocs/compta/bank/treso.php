<?php
/* Copytight (C) 2005-2007 Regis Houssin        <regis@dolibarr.fr>
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
 */

/**
	    \file       htdocs/compta/bank/treso.php
		\ingroup    banque
		\brief      Page de détail du budget de trésorerie
		\version    $Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/bank.lib.php");
require_once(DOL_DOCUMENT_ROOT.'/facture.class.php');
require_once(DOL_DOCUMENT_ROOT.'/fourn/fournisseur.facture.class.php');
require_once(DOL_DOCUMENT_ROOT."/societe.class.php");

$langs->load("banks");

$user->getrights('banque');

if (!$user->admin && !$user->rights->banque)
  accessforbidden();

$account=isset($_GET["account"])?$_GET["account"]:$_POST["account"];
$vline=isset($_GET["vline"])?$_GET["vline"]:$_POST["vline"];
$page=isset($_GET["page"])?$_GET["page"]:0;

$mesg='';



/*
* Affichage page
*/

llxHeader();

$societestatic = new Societe($db);
$facturestatic=new Facture($db);
$facturefournstatic=new FactureFournisseur($db);

$html = new Form($db);

if ($_REQUEST["account"] || $_REQUEST["ref"])
{
	if ($vline)
	{
		$viewline = $vline;
	}
	else
	{
		$viewline = 20;
	}

	$acct = new Account($db);
	if ($_GET["account"]) 
	{
		$result=$acct->fetch($_GET["account"]);
	}
	if ($_GET["ref"]) 
	{
		$result=$acct->fetch(0,$_GET["ref"]);
		$_GET["account"]=$acct->id;
	}


	/*
	*
	*
	*/
	// Onglets
	$head=bank_prepare_head($acct);
	dolibarr_fiche_head($head,'cash',$langs->trans("FinancialAccount"),0);
	
	print '<table class="border" width="100%">';

	// Ref
	print '<tr><td valign="top" width="25%">'.$langs->trans("Ref").'</td>';
	print '<td colspan="3">';
	print $html->showrefnav($acct,'ref','',1,'ref');
	print '</td></tr>';

	// Label
	print '<tr><td valign="top">'.$langs->trans("Label").'</td>';
	print '<td colspan="3">'.$acct->label.'</td></tr>';

	print '</table>';

	print '<br>';
	
	
	if ($mesg) print '<div class="error">'.$mesg.'</div>';


	/*
	* Calcul du solde du compte bancaire
	*/
	$sql = "SELECT sum( amount ) AS solde";
	$sql.= " FROM ".MAIN_DB_PREFIX."bank";
	$sql.= " WHERE fk_account =".$account;

	$result = $db->query($sql);
	if ($result)
	{
		$obj = $db->fetch_object($result);
		if ($obj) $solde = $obj->solde;
	}

	/*
	* Affiche tableau des echeances à venir
	*
	*/

	print '<table class="notopnoleftnoright" width="100% border="1">';

	// Ligne de titre tableau des ecritures
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Invoices").'</td>';
	print '<td>'.$langs->trans("ThirdParty").'</td>';
	print '<td>'.$langs->trans("DateEcheance").'</td>';
	print '<td align="right">'.$langs->trans("Debit").'</td>';
	print '<td align="right">'.$langs->trans("Credit").'</td>';
	print '<td align="right" width="80">'.$langs->trans("BankBalance").'</td>';
	print '</tr>';

	// Solde initial
	print '<tr class="liste_total"><td align="left" colspan="5">'.$langs->trans("CurrentBalance").'</td>';
	print '<td align="right" nowrap>'.price($solde).'</td>';
	print '</tr>';



	// Recuperation des factures clients et fournisseurs impayes
	$sql = "SELECT f.rowid as facid, f.facnumber, f.total_ttc, f.type, ".$db->pdate("f.date_lim_reglement")." as dlr,";
	$sql.= " s.rowid as socid, s.nom, s.fournisseur";
	$sql.= " FROM ".MAIN_DB_PREFIX."facture as f";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON f.fk_soc = s.rowid";
	$sql.= " WHERE f.paye = 0 AND fk_statut > 0";
	$sql.= " UNION DISTINCT";
	$sql.= " SELECT ff.rowid as facid, ff.facnumber, (-1*ff.total_ttc), ff.type, ".$db->pdate("ff.date_lim_reglement")." as dlr,";
	$sql.= " s.rowid as socid, s.nom, s.fournisseur";
	$sql.= " FROM ".MAIN_DB_PREFIX."facture_fourn as ff";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON ff.fk_soc = s.rowid";
	$sql.= " WHERE ff.paye = 0 AND fk_statut > 0";
	$sql.= " ORDER BY dlr ASC";

	$result = $db->query($sql);
	if ($result)
	{
		$var=False;
		$num = $db->num_rows($result);
		$i = 0;
		while ($i < $num)
		{
			$var=!$var;
			$obj = $db->fetch_object($result);
			
			$societestatic->id = $obj->socid;
			$societestatic->nom = $obj->nom;
			
			if ($obj->fournisseur == 1)
			{
				$facturefournstatic->ref=$obj->facnumber;
				$facturefournstatic->id=$obj->facid;
				$facturefournstatic->type=$obj->type;
				$facture = $facturefournstatic->getNomUrl(1,'');
			}
			else
			{
				$facturestatic->ref=$obj->facnumber;
				$facturestatic->id=$obj->facid;
				$facturestatic->type=$obj->type;
				$facture = $facturestatic->getNomUrl(1,'');
			}

			$solde += $obj->total_ttc;

			print "<tr $bc[$var]>";
			print "<td>".$facture."</td>";
			print "<td>".$societestatic->getNomUrl(0,'',16)."</td>";
			print "<td>".dolibarr_print_date($obj->dlr,"day")."</td>";
			if ($obj->total_ttc < 0) { print "<td align=\"right\">".price($obj->total_ttc)."</td><td>&nbsp;</td>"; };
			if ($obj->total_ttc >= 0) { print "<td>&nbsp;</td><td align=\"right\">".price($obj->total_ttc)."</td>"; };			
			print "<td align=\"right\">".price($solde)."</td>";
			print "</tr>";
			$i++;
		}
		$db->free($result);
	}

	print "</table>";

}
else
{
	print $langs->trans("ErrorBankAccountNotFound");
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
