<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis@dolibarr.fr>
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
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/propale/modules_propale.php';
if (! empty($conf->projet->enabled)) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
}

$langs->load("propal");
if (! empty($conf->projet->enabled))
	$langs->load("projects");
$langs->load("companies");
$langs->load("bills");
$langs->load("orders");
$langs->load("deliveries");

$action=GETPOST('action','alpha');
$origin=GETPOST('origin','alpha');
$originid=GETPOST('originid','int');

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
$hookmanager=new HookManager($db);
$hookmanager->initHooks(array('propalcard'));

/*
 * Actions
 */

// None



/*
 * View
 */

llxHeader();

print_fiche_titre($langs->trans("NewProp"));

$form=new Form($db);

// Add new proposal
if ($action == 'create')
{
	$soc = new Societe($db);
	$result=$soc->fetch($_GET["socid"]);
	if ($result < 0)
	{
		dol_print_error($db,$soc->error);
		exit;
	}

	$object = new Propal($db);

	$numpr='';
	$obj = $conf->global->PROPALE_ADDON;
	if ($obj)
	{
		if (! empty($conf->global->PROPALE_ADDON) && is_readable(DOL_DOCUMENT_ROOT ."/core/modules/propale/".$conf->global->PROPALE_ADDON.".php"))
		{
			require_once DOL_DOCUMENT_ROOT ."/core/modules/propale/".$conf->global->PROPALE_ADDON.'.php';
			$modPropale = new $obj;
			$numpr = $modPropale->getNextValue($soc,$object);
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

	if ($origin != 'project' && $originid)
	{
		print '<input type="hidden" name="origin" value="'.$origin.'">';
		print '<input type="hidden" name="originid" value="'.$originid.'">';
	}

	print '<table class="border" width="100%">';

	// Ref
	print '<tr><td class="fieldrequired">'.$langs->trans("Ref").'</td>';
	print '<td colspan="2">'.$numpr.'</td>';
	print '<input type="hidden" name="ref" value="'.$numpr.'">';
	print '</tr>';

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
	$form->select_contacts($soc->id,'','contactidp',1);
	print '</td></tr>';

	// Ligne info remises tiers
	print '<tr><td>'.$langs->trans('Discounts').'</td><td colspan="2">';
	if ($soc->remise_client) print $langs->trans("CompanyHasRelativeDiscount",$soc->remise_client);
	else print $langs->trans("CompanyHasNoRelativeDiscount");
	$absolute_discount=$soc->getAvailableDiscounts();
	print '. ';
	if ($absolute_discount) print $langs->trans("CompanyHasAbsoluteDiscount",price($absolute_discount),$langs->trans("Currency".$conf->currency));
	else print $langs->trans("CompanyHasNoAbsoluteDiscount");
	print '.';
	print '</td></tr>';

	// Date
	print '<tr><td class="fieldrequired">'.$langs->trans('Date').'</td><td colspan="2">';
	$form->select_date('','','','','',"addprop");
	print '</td></tr>';

	// Validaty duration
	print '<tr><td class="fieldrequired">'.$langs->trans("ValidityDuration").'</td><td colspan="2"><input name="duree_validite" size="5" value="'.$conf->global->PROPALE_VALIDITY_DURATION.'"> '.$langs->trans("days").'</td></tr>';

	// Terms of payment
	print '<tr><td nowrap="nowrap" class="fieldrequired">'.$langs->trans('PaymentConditionsShort').'</td><td colspan="2">';
	$form->select_conditions_paiements($soc->cond_reglement,'cond_reglement_id');
	print '</td></tr>';

	// Mode of payment
	print '<tr><td>'.$langs->trans('PaymentMode').'</td><td colspan="2">';
	$form->select_types_paiements($soc->mode_reglement,'mode_reglement_id');
	print '</td></tr>';

	// What trigger creation
    print '<tr><td>'.$langs->trans('Source').'</td><td>';
    $form->select_demand_reason('','demand_reason_id',"SRC_PROP",1);
    print '</td></tr>';

	// Delivery delay
    print '<tr><td>'.$langs->trans('AvailabilityPeriod').'</td><td colspan="2">';
    $form->select_availability('','availability_id','',1);
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
		$form->select_date($syear."-".$smonth."-".$sday,'liv_','','','',"addprop");
	}
	else
	{
		$datepropal=empty($conf->global->MAIN_AUTOFILL_DATE)?-1:0;
		$form->select_date($datepropal,'liv_','','','',"addprop");
	}
	print '</td></tr>';

	// Model
	print '<tr>';
	print '<td>'.$langs->trans("DefaultModel").'</td>';
	print '<td colspan="2">';
	$liste=ModelePDFPropales::liste_modeles($db);
	print $form->selectarray('model',$liste,$conf->global->PROPALE_ADDON_PDF);
	print "</td></tr>";

	// Project
	if (! empty($conf->projet->enabled))
	{
		$projectid = 0;
		if ($origin == 'project') $projectid = ($originid?$originid:0);

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

	// Other attributes
	$parameters=array('colspan' => ' colspan="3"');
	$reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
	if (empty($reshook) && ! empty($extrafields->attribute_label))
	{
	    foreach($extrafields->attribute_label as $key=>$label)
	    {
	        $value=(isset($_POST["options_".$key])?$_POST["options_".$key]:$object->array_options["options_".$key]);
	        print "<tr><td>".$label.'</td><td colspan="3">';
	        print $extrafields->showInputField($key,$value);
	        print '</td></tr>'."\n";
	    }
	}

	print "</table>";
	print '<br>';

	/*
	 * Combobox pour la fonction de copie
	 */

	if (empty($conf->global->PROPAL_CLONE_ON_CREATE_PAGE))
	{
		print '<input type="hidden" name="createmode" value="empty">';
	}

	print '<table>';
	if (! empty($conf->global->PROPAL_CLONE_ON_CREATE_PAGE))
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
			print $form->selectarray("copie_propal",$liste_propal, 0);
		}
		else
		{
			dol_print_error($db);
		}
		print '</td></tr>';

		if (! empty($conf->global->PRODUCT_SHOW_WHEN_CREATE)) print '<tr><td colspan="3">&nbsp;</td></tr>';

		print '<tr><td valign="top"><input type="radio" name="createmode" value="empty" checked="checked"></td>';
		print '<td valign="top" colspan="2">'.$langs->trans("CreateEmptyPropal").'</td></tr>';
	}

	if (! empty($conf->global->PRODUCT_SHOW_WHEN_CREATE))
	{
		print '<tr><td colspan="3">';
		if (! empty($conf->product->enabled) || ! empty($conf->service->enabled))
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
				$form->select_produits('',"idprod".$i,'',$conf->product->limit_size,$soc->price_level);
				else
				$form->select_produits('',"idprod".$i,'',$conf->product->limit_size);
				print '</td>';
				print '<td><input type="text" size="2" name="qty'.$i.'" value="1"></td>';
				print '<td><input type="text" size="2" name="remise'.$i.'" value="'.$soc->remise_client.'">%</td>';
				print '</tr>';
			}

			print "</table>";

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


llxFooter();
$db->close();
?>
