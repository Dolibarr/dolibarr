<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2006 Regis Houssin        <regis.houssin@cap-networks.com>
 * Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
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

/**
	    \file       htdocs/comm/addpropal.php
        \ingroup    propal
		\brief      Page d'ajout d'une proposition commmercial
		\version    $Revision$
*/

require("./pre.inc.php");

include_once(DOL_DOCUMENT_ROOT.'/includes/modules/propale/modules_propale.php');
if (defined("PROPALE_ADDON") && is_readable(DOL_DOCUMENT_ROOT ."/includes/modules/propale/".PROPALE_ADDON.".php"))
{
    require_once(DOL_DOCUMENT_ROOT ."/includes/modules/propale/".PROPALE_ADDON.".php");
}

$langs->load("propal");
if ($conf->projet->enabled) $langs->load("projects");
$langs->load("companies");
$langs->load("bills");
$langs->load("orders");

$user->getrights('propale');
$user->getrights('fichinter');
$user->getrights('commande');
$user->getrights('projet');



llxHeader();

print_titre($langs->trans("NewProp"));

$html=new Form($db);

// Récupération de l'id de projet
$projetid = 0;
if ($_GET["projetid"])
{
	$projetid = $_GET["projetid"];
}

/*
 *
 * Creation d'une nouvelle propale
 *
 */
if ($_GET["action"] == 'create')
{
    $soc = new Societe($db);
    $result=$soc->fetch($_GET["socid"]);
    if ($result < 0)
    {
        dolibarr_print_error($db,$soc->error);
        exit;
    }

    $obj = $conf->global->PROPALE_ADDON;
    $modPropale = new $obj;
    $numpr = $modPropale->getNextValue($soc);

	// Fix pour modele numerotation qui deconne
	// Si numero deja pris (ne devrait pas arriver), on incremente par .num+1
    $sql = "SELECT count(*) as nb FROM ".MAIN_DB_PREFIX."propal WHERE ref like '$numpr%'";
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

    print "<form name='addprop' action=\"propal.php?socid=".$soc->id."\" method=\"post\">";
    print "<input type=\"hidden\" name=\"action\" value=\"add\">";

    print '<table class="border" width="100%">';

    // Ref
    print '<tr><td>'.$langs->trans("Ref").'</td><td colspan="2"><input name="ref" value="'.$numpr.'"></td></tr>';

    // Reference client
	print '<tr><td>'.$langs->trans('RefCustomer').'</td><td>';
	print '<input type="text" name="ref_client" value=""></td>';
	print '</tr>';

	// Societe
	print '<tr><td>'.$langs->trans('Company').'</td><td colspan="2">'.$soc->getNomUrl(1);
	print '<input type="hidden" name="socid" value="'.$soc->id.'">';
	print '</td>';
	print '</tr>';

	/*
	* Contact de la propale
	*/
    print "<tr><td>".$langs->trans("DefaultContact")."</td><td colspan=\"2\">\n";
    $html->select_contacts($soc->id,$setcontact,'contactidp',1);
    print '</td></tr>';

	// Ligne info remises tiers
    print '<tr><td>'.$langs->trans('Discounts').'</td><td colspan="2">';
	if ($soc->remise_client) print $langs->trans("CompanyHasRelativeDiscount",$soc->remise_client);
	else print $langs->trans("CompanyHasNoRelativeDiscount");
	$absolute_discount=$soc->getCurrentDiscount();
	print '. ';
	if ($absolute_discount) print $langs->trans("CompanyHasAbsoluteDiscount",$absolute_discount,$langs->trans("Currency".$conf->monnaie));
	else print $langs->trans("CompanyHasNoAbsoluteDiscount");
	print '.';
	print '</td></tr>';

	// Date facture
	print '<tr><td>'.$langs->trans('Date').'</td><td colspan="2">';
	$html->select_date('','','','','',"addprop");
	print '</td></tr>';

	print '<tr><td>'.$langs->trans("ValidityDuration").'</td><td colspan="2"><input name="duree_validite" size="5" value="'.$conf->global->PROPALE_VALIDITY_DURATION.'"> '.$langs->trans("days").'</td></tr>';

	// Conditions de réglement
	print '<tr><td nowrap>'.$langs->trans('PaymentConditionsShort').'</td><td colspan="2">';
	$html->select_conditions_paiements($soc->cond_reglement,'cond_reglement_id');
	print '</td></tr>';

	// Mode de réglement
	print '<tr><td>'.$langs->trans('PaymentMode').'</td><td colspan="2">';
	$html->select_types_paiements($soc->mode_reglement,'mode_reglement_id');
	print '</td></tr>';

	// Date de livraison (ou de fabrication)
if ($conf->expedition->enabled)
{
	if ($conf->global->PROPALE_ADD_SHIPPING_DATE)
	{
		print '<tr><td>'.$langs->trans("DateDelivery").'</td>';
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
			$html->select_date(-1,'liv_','','','',"addprop");
		}
		print '</td></tr>';
	}

	// Adresse de livraison
	if ($conf->global->PROPALE_ADD_DELIVERY_ADDRESS)
	{
		print '<tr><td>'.$langs->trans('DeliveryAddress').'</td>';
		print '<td colspan="3">';
		$numaddress = $html->select_adresse_livraison($soc->adresse_livraison_id, $_GET['socid'],'adresse_livraison_id',1);
		if ($numaddress==0)
		{
			print ' &nbsp; <a href=../comm/adresse_livraison.php?socid='.$soc->id.'&action=create>'.$langs->trans("AddAddress").'</a>';
		}
    print '</td></tr>';
  }
}

    // Model
    print '<tr>';
    print '<td>'.$langs->trans("DefaultModel").'</td>';
    print '<td colspan="2">';
    $model=new ModelePDFPropales();
    $liste=$model->liste_modeles($db);
    $html->select_array('model',$liste,$conf->global->PROPALE_ADDON_PDF);
    print "</td></tr>";

	// Projet
    if ($conf->projet->enabled)
    {
	    print '<tr>';
        print '<td valign="top">'.$langs->trans("Project").'</td><td colspan="2">';

        $numprojet=$html->select_projects($soc->id,$projetid,'projetidp');
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
    print '<tr>';
    print '<td><input type="radio" name="createmode" value="copy"></td>';
    print '<td>'.$langs->trans("CopyPropalFrom").' </td>';
    print '<td>';
    $liste_propal = array();
    $liste_propal[0] = '';
    $sql ="SELECT p.rowid as id, CONCAT(p.ref, ' - ', s.nom)  as lib";
    $sql.=" FROM ".MAIN_DB_PREFIX."propal p, ".MAIN_DB_PREFIX."societe s";
    $sql.=" WHERE s.rowid = p.fk_soc AND fk_statut <> 0 ORDER BY Id";
    $resql = $db->query($sql);
    if ($resql)
    {
		$num = $db->num_rows($resql);
        $i = 0;
        while ($i < $num)
        {
        	$row = $db->fetch_row($resql);
            $liste_propal[$row[0]]=$row[1];
            $i++;
        }
        $html->select_array("copie_propal",$liste_propal, 0);
    }
    else
    {
      	dolibarr_print_error($db);
    }
    print '</td></tr>';

   	if ($conf->global->PRODUCT_SHOW_WHEN_CREATE) print '<tr><td colspan="3">&nbsp;</td></tr>';

    print '<tr><td valign="top"><input type="radio" name="createmode" value="empty" checked="true"></td>';
	print '<td valign="top" colspan="2">'.$langs->trans("CreateEmptyPropal").'</td></tr>';

	if ($conf->global->PRODUCT_SHOW_WHEN_CREATE)
	{
	    print '<tr><td colspan="3">';
	    if ($conf->produit->enabled || $conf->service->enabled)
	    {
	        $lib=$langs->trans("ProductsAndServices");

	        print '<table class="border" width="100%">';
	        print '<tr>';
	        print '<td>'.$lib.'</td>';
	        print '<td>'.$langs->trans("Qty").'</td>';
	        print '<td>'.$langs->trans("ReductionShort").'</td>';
	        print '</tr>';
	        for ($i = 1 ; $i <= $conf->global->PROPALE_NEW_FORM_NB_PRODUCT ; $i++)
	        {
	            print '<tr><td>';
				// multiprix
				if($conf->global->PRODUIT_MULTIPRICES == 1)
					$html->select_produits('',"idprod".$i,'',$conf->produit->limit_size,$soc->price_level);
				else
	            	$html->select_produits('',"idprod".$i,'',$conf->produit->limit_size);
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

llxFooter('$Date$ - $Revision$');
?>
