<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
 * Copyright (C) 2011      Philippe Grand       <philippe.grand@atoo-net.com>
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
 *	\file       htdocs/comm/addpropal.php
 *	\ingroup    propal
 *	\brief      Page to add a new commercial proposal
 *	\version    $Id: addpropal.php,v 1.131 2011/08/03 00:46:26 eldy Exp $
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php');
require_once(DOL_DOCUMENT_ROOT.'/includes/modules/propale/modules_propale.php');
if ($conf->projet->enabled)
{
	require_once(DOL_DOCUMENT_ROOT."/projet/class/project.class.php");
	require_once(DOL_DOCUMENT_ROOT.'/lib/project.lib.php');
}

$langs->load("propal");
if ($conf->projet->enabled) $langs->load("projects");
$langs->load("companies");
$langs->load("bills");
$langs->load("orders");
$langs->load("deliveries");

/*
 * Actions
 */

// None



/*
 * View
 */

llxHeader();

print_fiche_titre($langs->trans("NewProp"));

$html=new Form($db);


// Add new proposal
if ($_GET["action"] == 'create')
{
	$soc = new Societe($db);
	$result=$soc->fetch($_GET["socid"]);
	if ($result < 0)
	{
		dol_print_error($db,$soc->error);
		exit;
	}

	$propal = new Propal($db);

	$numpr='';
	$obj = $conf->global->PROPALE_ADDON;
	if ($obj)
	{
		if (! empty($conf->global->PROPALE_ADDON) && is_readable(DOL_DOCUMENT_ROOT ."/includes/modules/propale/".$conf->global->PROPALE_ADDON.".php"))
		{
			require_once(DOL_DOCUMENT_ROOT ."/includes/modules/propale/".$conf->global->PROPALE_ADDON.".php");
			$modPropale = new $obj;
			$numpr = $modPropale->getNextValue($soc,$propal);
		}
	}

	// Fix pour modele numerotation qui deconne
	// Si numero deja pris (ne devrait pas arriver), on incremente par .num+1
	$sql = "SELECT count(*) as nb";
	$sql.= " FROM ".MAIN_DB_PREFIX."propal";
	$sql.= " WHERE ref LIKE '".$numpr."%'";
	$sql.= " AND entity = ".$conf->entity;

	$resql=$db->query($sql);
	if ($resql)
	{
		$obj=$db->fetch_object($resql);
		$num = $obj->nb;
		$db->free($resql);
		if ($num > 0)
		{
			$numpr .= "." . ($num + 1);
		}
	}

	print '<form name="addprop" action="propal.php?socid='.$soc->id.'" method="POST">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="add">';

	if (isset($_GET["origin"]) && $_GET["origin"] != 'project' && isset($_GET["originid"]))
	{
		print '<input type="hidden" name="origin" value="'.$_GET["origin"].'">';
		print '<input type="hidden" name="originid" value="'.$_GET["originid"].'">';
	}

	print '<table class="border" width="100%">';

	// Ref
	print '<tr><td class="fieldrequired">'.$langs->trans("Ref").'</td><td colspan="2"><input name="ref" value="'.$numpr.'"></td></tr>';

	// Ref customer
	print '<tr><td>'.$langs->trans('RefCustomer').'</td><td colspan="2">';
	print '<input type="text" name="ref_client" value=""></td>';
	print '</tr>';

	// Third party
	print '<tr><td class="fieldrequired">'.$langs->trans('Company').'</td><td colspan="2">'.$soc->getNomUrl(1);
	print '<input type="hidden" name="socid" value="'.$soc->id.'">';
	print '</td>';
	print '</tr>';

	// Contacts
	print "<tr><td>".$langs->trans("DefaultContact")."</td><td colspan=\"2\">\n";
	$html->select_contacts($soc->id,$setcontact,'contactidp',1);
	print '</td></tr>';

	// Ligne info remises tiers
	print '<tr><td>'.$langs->trans('Discounts').'</td><td colspan="2">';
	if ($soc->remise_client) print $langs->trans("CompanyHasRelativeDiscount",$soc->remise_client);
	else print $langs->trans("CompanyHasNoRelativeDiscount");
	$absolute_discount=$soc->getAvailableDiscounts();
	print '. ';
	if ($absolute_discount) print $langs->trans("CompanyHasAbsoluteDiscount",price($absolute_discount),$langs->trans("Currency".$conf->monnaie));
	else print $langs->trans("CompanyHasNoAbsoluteDiscount");
	print '.';
	print '</td></tr>';

	// Date
	print '<tr><td class="fieldrequired">'.$langs->trans('Date').'</td><td colspan="2">';
	$html->select_date('','','','','',"addprop");
	print '</td></tr>';

	// Validaty duration
	print '<tr><td class="fieldrequired">'.$langs->trans("ValidityDuration").'</td><td colspan="2"><input name="duree_validite" size="5" value="'.$conf->global->PROPALE_VALIDITY_DURATION.'"> '.$langs->trans("days").'</td></tr>';

	// Terms of payment
	print '<tr><td nowrap="nowrap" class="fieldrequired">'.$langs->trans('PaymentConditionsShort').'</td><td colspan="2">';
	$html->select_conditions_paiements($soc->cond_reglement,'cond_reglement_id');
	print '</td></tr>';

	// Mode of payment
	print '<tr><td>'.$langs->trans('PaymentMode').'</td><td colspan="2">';
	$html->select_types_paiements($soc->mode_reglement,'mode_reglement_id');
	print '</td></tr>';

	// What trigger creation
    print '<tr><td>'.$langs->trans('Source').'</td><td>';
    $html->select_demand_reason($propal->demand_reason,'demand_reason_id',"SRC_PROP",1);
    print '</td></tr>';

	// Delivery delay
    print '<tr><td>'.$langs->trans('AvailabilityPeriod').'</td><td colspan="2">';
    $html->select_availability($propal->availability,'availability_id','',1);
    print '</td></tr>';

	// Delivery date (or manufacturing)
	print '<tr><td>'.$langs->trans("DeliveryDate").'</td>';
	print '<td colspan="2">';
	if ($conf->global->DATE_LIVRAISON_WEEK_DELAY != "")
	{
		$tmpdte = time() + ((7 * $conf->global->DATE_LIVRAISON_WEEK_DELAY) * 24 * 60 * 60);
		$syear = date("Y", $tmpdte);
		$smonth = date("m", $tmpdte);
		$sday = date("d", $tmpdte);
		$html->select_date($syear."-".$smonth."-".$sday,'liv_','','','',"addprop");
	}
	else
	{
		$datepropal=empty($conf->global->MAIN_AUTOFILL_DATE)?-1:0;
		$html->select_date($datepropal,'liv_','','','',"addprop");
	}
	print '</td></tr>';

	// Delivery address
	if ($conf->global->PROPALE_ADD_DELIVERY_ADDRESS)
	{
		print '<tr><td>'.$langs->trans('DeliveryAddress').'</td>';
		print '<td colspan="3">';
		$numaddress = $html->select_address($soc->fk_delivery_address, $_GET['socid'],'fk_address',1);
		if ($numaddress==0)
		{
			print ' &nbsp; <a href=../comm/address.php?socid='.$soc->id.'&action=create>'.$langs->trans("AddAddress").'</a>';
		}
		print '</td></tr>';
	}

	// Model
	print '<tr>';
	print '<td>'.$langs->trans("DefaultModel").'</td>';
	print '<td colspan="2">';
	$model=new ModelePDFPropales();
	$liste=$model->liste_modeles($db);
	print $html->selectarray('model',$liste,$conf->global->PROPALE_ADDON_PDF);
	print "</td></tr>";

	// Project
	if ($conf->projet->enabled)
	{
		$projectid = 0;
		if (isset($_GET["origin"]) && $_GET["origin"] == 'project') $projectid = ($_GET["originid"]?$_GET["originid"]:0);

		print '<tr>';
		print '<td valign="top">'.$langs->trans("Project").'</td><td colspan="2">';

		$numprojet=select_projects($soc->id,$projectid);
		if ($numprojet==0)
		{
			print ' &nbsp; <a href="../projet/fiche.php?socid='.$soc->id.'&action=create">'.$langs->trans("AddProject").'</a>';
		}
		print '</td>';
		print '</tr>';
	}

	print "</table>";
	print '<br>';

	/*
	 * Combobox pour la fonction de copie
	 */

	print '<table>';
	if (empty($conf->global->PROPAL_CLONE_ON_CREATE_PAGE))
	{
		print '<tr><td colspan="3"><input type="hidden" name="createmode" value="empty"></td></tr>';
	}
	else
	{
		// For backward compatibility
		print '<tr>';
		print '<td><input type="radio" name="createmode" value="copy"></td>';
		print '<td>'.$langs->trans("CopyPropalFrom").' </td>';
		print '<td>';
		$liste_propal = array();
		$liste_propal[0] = '';

		$sql ="SELECT p.rowid as id, p.ref, s.nom";
		$sql.=" FROM ".MAIN_DB_PREFIX."propal p";
		$sql.= ", ".MAIN_DB_PREFIX."societe s";
		$sql.= " WHERE s.rowid = p.fk_soc";
		$sql.= " AND p.entity = ".$conf->entity;
		$sql.= " AND p.fk_statut <> 0";
		$sql.= " ORDER BY Id";

		$resql = $db->query($sql);
		if ($resql)
		{
			$num = $db->num_rows($resql);
			$i = 0;
			while ($i < $num)
			{
				$row = $db->fetch_row($resql);
				$propalRefAndSocName = $row[1]." - ".$row[2];
				$liste_propal[$row[0]]=$propalRefAndSocName;
				$i++;
			}
			print $html->selectarray("copie_propal",$liste_propal, 0);
		}
		else
		{
			dol_print_error($db);
		}
		print '</td></tr>';

		if ($conf->global->PRODUCT_SHOW_WHEN_CREATE) print '<tr><td colspan="3">&nbsp;</td></tr>';

		print '<tr><td valign="top"><input type="radio" name="createmode" value="empty" checked="true"></td>';
		print '<td valign="top" colspan="2">'.$langs->trans("CreateEmptyPropal").'</td></tr>';
	}

	if ($conf->global->PRODUCT_SHOW_WHEN_CREATE)
	{
		print '<tr><td colspan="3">';
		if ($conf->product->enabled || $conf->service->enabled)
		{
			$lib=$langs->trans("ProductsAndServices");

			print '<table class="border" width="100%">';
			print '<tr>';
			print '<td>'.$lib.'</td>';
			print '<td>'.$langs->trans("Qty").'</td>';
			print '<td>'.$langs->trans("ReductionShort").'</td>';
			print '</tr>';
			for ($i = 1 ; $i <= $conf->global->PRODUCT_SHOW_WHEN_CREATE; $i++)
			{
				print '<tr><td>';
				// multiprix
				if($conf->global->PRODUIT_MULTIPRICES && $soc->price_level)
				$html->select_produits('',"idprod".$i,'',$conf->product->limit_size,$soc->price_level);
				else
				$html->select_produits('',"idprod".$i,'',$conf->product->limit_size);
				print '</td>';
				print '<td><input type="text" size="2" name="qty'.$i.'" value="1"></td>';
				print '<td><input type="text" size="2" name="remise'.$i.'" value="'.$soc->remise_client.'">%</td>';
				print '</tr>';
			}

			print "</table>";

		}
		else
		{
			print '&nbsp;';
		}
		print '</td></tr>';
	}
	print '</table>';
	print '<br>';

	$langs->load("bills");
	print '<center>';
	print '<input type="submit" class="button" value="'.$langs->trans("CreateDraft").'">';
	print '</center>';

	print "</form>";
}

$db->close();

llxFooter('$Date: 2011/08/03 00:46:26 $ - $Revision: 1.131 $');
?>
