<?php
/* Copytight (C) 2005-2009 Regis Houssin               <regis.houssin@capnetworks.com>
 * Copyright (C) 2008-2009 Laurent Destailleur (Eldy)  <eldy@users.sourceforge.net>
 * Copyright (C) 2008      Raphael Bertrand (Resultic) <raphael.bertrand@resultic.fr>
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
 *	\file       htdocs/compta/bank/treso.php
 *	\ingroup    banque
 *	\brief      Page de detail du budget de tresorerie
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/bank.lib.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/chargesociales.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

$langs->load("banks");
$langs->load("categories");
$langs->load("bills");

// Security check
if (isset($_GET["account"]) || isset($_GET["ref"]))
{
	$id = isset($_GET["account"])?$_GET["account"]:(isset($_GET["ref"])?$_GET["ref"]:'');
}
$fieldid = isset($_GET["ref"])?'ref':'rowid';
if ($user->societe_id) $socid=$user->societe_id;
$result=restrictedArea($user,'banque',$id,'bank_account','','',$fieldid);


$vline=isset($_GET["vline"])?$_GET["vline"]:$_POST["vline"];
$page=isset($_GET["page"])?$_GET["page"]:0;

$mesg='';



/*
 * View
 */

llxHeader();

$societestatic = new Societe($db);
$facturestatic=new Facture($db);
$facturefournstatic=new FactureFournisseur($db);
$socialcontribstatic=new ChargeSociales($db);

$form = new Form($db);

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
	dol_fiche_head($head,'cash',$langs->trans("FinancialAccount"),0,'account');

	print '<table class="border" width="100%">';

	$linkback = '<a href="'.DOL_URL_ROOT.'/compta/bank/index.php">'.$langs->trans("BackToList").'</a>';

	// Ref
	print '<tr><td valign="top" width="25%">'.$langs->trans("Ref").'</td>';
	print '<td colspan="3">';
	print $form->showrefnav($acct, 'ref', $linkback, 1, 'ref');
	print '</td></tr>';

	// Label
	print '<tr><td valign="top">'.$langs->trans("Label").'</td>';
	print '<td colspan="3">'.$acct->label.'</td></tr>';

	print '</table>';

	print '<br>';


	if ($mesg) print '<div class="error">'.$mesg.'</div>';


	$solde = $acct->solde(0);

	/*
	 * Affiche tableau des echeances a venir
	 */
	print '<table class="notopnoleftnoright" width="100% border="1">';

	// Ligne de titre tableau des ecritures
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("DateEcheance").'</td>';
	print '<td>'.$langs->trans("Description").'</td>';
	print '<td>'.$langs->trans("ThirdParty").'</td>';
	print '<td align="right">'.$langs->trans("Debit").'</td>';
	print '<td align="right">'.$langs->trans("Credit").'</td>';
	print '<td align="right" width="80">'.$langs->trans("BankBalance").'</td>';
	print '</tr>';

	$var=true;

	// Solde actuel
	$var=!$var;
	print '<tr class="liste_total">';
	print '<td align="left" colspan="5">'.$langs->trans("CurrentBalance").'</td>';
	print '<td align="right" nowrap>'.price($solde).'</td>';
	print '</tr>';

	$var=!$var;
	print '<tr class="liste_total">';
	print '<td align="left" colspan="5">'.$langs->trans("RemainderToPay").'</td>';
	print '<td align="right" nowrap>&nbsp;</td>';
	print '</tr>';


	// Remainder to pay in future

	// Customer invoices
	$sql = "SELECT 'invoice' as family, f.rowid as objid, f.facnumber as ref, f.total_ttc, f.type, f.date_lim_reglement as dlr,";
	$sql.= " s.rowid as socid, s.nom, s.fournisseur";
	$sql.= " FROM ".MAIN_DB_PREFIX."facture as f";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON f.fk_soc = s.rowid";
	$sql.= " WHERE f.entity = ".$conf->entity;
	$sql.= " AND f.paye = 0 AND f.fk_statut = 1";	// Not paid
	$sql.= " ORDER BY dlr ASC";

	// Supplier invoices
	$sql2= " SELECT 'invoice_supplier' as family, ff.rowid as objid, ff.ref_supplier as ref, (-1*ff.total_ttc) as total_ttc, ff.type, ff.date_lim_reglement as dlr,";
	$sql2.= " s.rowid as socid, s.nom, s.fournisseur";
	$sql2.= " FROM ".MAIN_DB_PREFIX."facture_fourn as ff";
	$sql2.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON ff.fk_soc = s.rowid";
	$sql2.= " WHERE ff.entity = ".$conf->entity;
	$sql2.= " AND ff.paye = 0 AND fk_statut = 1";	// Not paid
	$sql2.= " ORDER BY dlr ASC";

	// Social contributions
	$sql3= " SELECT 'social_contribution' as family, cs.rowid as objid, cs.libelle as ref, (-1*cs.amount) as total_ttc, ccs.libelle as type, cs.date_ech as dlr";
	$sql3.= " FROM ".MAIN_DB_PREFIX."chargesociales as cs";
	$sql3.= " LEFT JOIN ".MAIN_DB_PREFIX."c_chargesociales as ccs ON cs.fk_type = ccs.id";
	$sql3.= " WHERE cs.entity = ".$conf->entity;
	$sql3.= " AND cs.paye = 0";	// Not paid
	$sql3.= " ORDER BY dlr ASC";

	$error=0;
	$tab_sqlobjOrder=array();
	$tab_sqlobj=array();

	// List customer invoices
	$result = $db->query($sql);
	if ($result)
	{
		$num = $db->num_rows($result);
		for ($i = 0;$i < $num;$i++)
		{
			$sqlobj = $db->fetch_object($result);
			$tab_sqlobj[] = $sqlobj;
			$tab_sqlobjOrder[]= $db->jdate($sqlobj->dlr);
		}
		$db->free($result);
	}
	else $error++;

	// List supplier invoices
	$result2=$db->query($sql2);
	if ($result2)
	{
		$num = $db->num_rows($result2);
		for ($i = 0;$i < $num;$i++)
		{
			$sqlobj = $db->fetch_object($result2);
			$tab_sqlobj[] = $sqlobj;
			$tab_sqlobjOrder[]= $db->jdate($sqlobj->dlr);
		}
		$db->free($result2);
	}
	else $error++;

	// List social contributions
	$result3=$db->query($sql3);
	if ($result3)
	{
		$num = $db->num_rows($result3);

		for ($i = 0;$i < $num;$i++)
		{
			$sqlobj = $db->fetch_object($result3);
			$tab_sqlobj[] = $sqlobj;
			$tab_sqlobjOrder[]= $db->jdate($sqlobj->dlr);
		}
		$db->free($result3);
	}
	else $error++;


	// Sort array
	if (! $error)
	{
		array_multisort($tab_sqlobjOrder,$tab_sqlobj);

		//Apply distinct filter
		foreach ($tab_sqlobj as $key=>$value) {
			$tab_sqlobj[$key] = "'" . serialize($value) . "'";
		}
		$tab_sqlobj = array_unique($tab_sqlobj);
		foreach ($tab_sqlobj as $key=>$value) {
			$tab_sqlobj[$key] = unserialize(trim($value, "'"));
		}

		$num = count($tab_sqlobj);

		//$num = $db->num_rows($result);
		$i = 0;
		while ($i < $num)
		{
			$paiement = '';
			$ref = '';
			$refcomp = '';

			//$obj = $db->fetch_object($result);
			$obj = array_shift($tab_sqlobj);

			if ($obj->family == 'invoice_supplier')
			{
				// TODO This code is to avoid to count suppliers credit note (ff.type = 2)
				// Ajouter gestion des avoirs fournisseurs, champ
				if (($obj->total_ttc < 0 && $obj->type != 2)
				 || ($obj->total_ttc > 0 && $obj->type == 2))
				{
					$facturefournstatic->ref=$obj->ref;
					$facturefournstatic->id=$obj->objid;
					$facturefournstatic->type=$obj->type;
					$ref = $facturefournstatic->getNomUrl(1,'');

					$societestatic->id = $obj->socid;
					$societestatic->nom = $obj->nom;
					$refcomp=$societestatic->getNomUrl(1,'',24);

					$paiement = -1*$facturefournstatic->getSommePaiement();	// Payment already done
				}
			}
			if ($obj->family == 'invoice')
			{
				$facturestatic->ref=$obj->ref;
				$facturestatic->id=$obj->objid;
				$facturestatic->type=$obj->type;
				$ref = $facturestatic->getNomUrl(1,'');

				$societestatic->id = $obj->socid;
				$societestatic->nom = $obj->nom;
				$refcomp=$societestatic->getNomUrl(1,'',24);

				$paiement = $facturestatic->getSommePaiement();	// Payment already done
			}
			if ($obj->family == 'social_contribution')
			{
				$socialcontribstatic->ref=$obj->ref;
				$socialcontribstatic->id=$obj->objid;
				$socialcontribstatic->lib=$obj->type;
				$ref = $socialcontribstatic->getNomUrl(1,24);

				$paiement = -1*$socialcontribstatic->getSommePaiement();	// Payment already done
			}

			$total_ttc = $obj->total_ttc;
			if ($paiement) $total_ttc = $obj->total_ttc - $paiement;
			$solde += $total_ttc;

			// We discard with a remain to pay to 0
			if (price2num($total_ttc) != 0)
			{
                $var=!$var;

    			// Show line
    			print "<tr ".$bc[$var].">";
    			print '<td>';
    			if ($obj->dlr) print dol_print_date($db->jdate($obj->dlr),"day");
    			else print $langs->trans("NotDefined");
    			print "</td>";
    			print "<td>".$ref."</td>";
    			print "<td>".$refcomp."</td>";
    			if ($obj->total_ttc < 0) { print "<td align=\"right\">".price($total_ttc)."</td><td>&nbsp;</td>"; };
    			if ($obj->total_ttc >= 0) { print "<td>&nbsp;</td><td align=\"right\">".price($total_ttc)."</td>"; };
    			print '<td align="right">'.price($solde).'</td>';
    			print "</tr>";
			}

			$i++;
		}
	}
	else
	{
		dol_print_error($db);
	}

	// Solde actuel
	$var=!$var;
	print '<tr class="liste_total">';
	print '<td align="left" colspan="5">'.$langs->trans("FutureBalance").'</td>';
	print '<td align="right" nowrap>'.price($solde).'</td>';
	print '</tr>';

	print "</table>";

}
else
{
	print $langs->trans("ErrorBankAccountNotFound");
}

$db->close();

llxFooter();
?>
