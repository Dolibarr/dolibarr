<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
 * Copyright (C) 2005-2007 Regis Houssin        <regis@dolibarr.fr>
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
        \file       htdocs/comm/fiche.php
        \ingroup    commercial
        \brief      Onglet client de la fiche societe
        \version    $Revision$
*/

require_once("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT."/actioncomm.class.php");
require_once(DOL_DOCUMENT_ROOT."/contact.class.php");
if ($conf->facture->enabled) require_once(DOL_DOCUMENT_ROOT."/facture.class.php");
if ($conf->propal->enabled) require_once(DOL_DOCUMENT_ROOT."/propal.class.php");
if ($conf->commande->enabled) require_once(DOL_DOCUMENT_ROOT."/commande/commande.class.php");
if ($conf->contrat->enabled) require_once(DOL_DOCUMENT_ROOT."/contrat/contrat.class.php");

$langs->load("companies");
$langs->load("orders");
$langs->load("bills");
$langs->load("contracts");
if ($conf->fichinter->enabled) $langs->load("interventions");

$socid = isset($_GET["socid"])?$_GET["socid"]:'';

// Sécurité d'accès client et commerciaux
$socid = restrictedArea($user, 'societe', $socid);

$sortorder=$_GET["sortorder"];
$sortfield=$_GET["sortfield"];
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="nom";


/*
 * Actions
 */

if ($_GET["action"] == 'attribute_prefix' && $user->rights->societe->creer)
{
    $societe = new Societe($db, $_GET["socid"]);
    $societe->attribute_prefix($db, $_GET["socid"]);
}
// conditions de règlement
if ($_POST["action"] == 'setconditions' && $user->rights->societe->creer)
{
    
	$societe = new Societe($db, $_GET["socid"]);
    $societe->cond_reglement=$_POST['cond_reglement_id'];
	$sql = "UPDATE ".MAIN_DB_PREFIX."societe SET cond_reglement='".$_POST['cond_reglement_id'];
	$sql.= "' WHERE rowid='".$_GET["socid"]."'";
    $result = $db->query($sql);
    if (! $result) dolibarr_print_error($result);
}
// mode de règlement
if ($_POST["action"] == 'setmode' && $user->rights->societe->creer)
{
    $societe = new Societe($db, $_GET["socid"]);
    $societe->mode_reglement=$_POST['mode_reglement_id'];
	$sql = "UPDATE ".MAIN_DB_PREFIX."societe SET mode_reglement='".$_POST['mode_reglement_id'];
	$sql.= "' WHERE rowid='".$_GET["socid"]."'";
    $result = $db->query($sql);
    if (! $result) dolibarr_print_error($result);
}
// assujétissement à la TVA
if ($_POST["action"] == 'setassujtva' && $user->rights->societe->creer)
{
	$societe = new Societe($db, $_GET["socid"]);
    $societe->tva_assuj=$_POST['assujtva_value'];
	$sql = "UPDATE ".MAIN_DB_PREFIX."societe SET tva_assuj='".$_POST['assujtva_value']."' WHERE rowid='".$socid."'";
    $result = $db->query($sql);
    if (! $result) dolibarr_print_error($result);
}


/*
 * Recherche
 *
 */
if ($mode == 'search') {
    if ($mode-search == 'soc') {
        $sql = "SELECT s.rowid";
        if (!$user->rights->commercial->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user ";
        $sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
        if (!$user->rights->commercial->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
        $sql .= " WHERE lower(s.nom) like '%".strtolower($socname)."%'";
        if (!$user->rights->commercial->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
    }

    if ( $db->query($sql) ) {
        if ( $db->num_rows() == 1) {
            $obj = $db->fetch_object();
            $socid = $obj->rowid;
        }
        $db->free();
    }
}



/*********************************************************************************
 *
 * Mode fiche
 *
 *********************************************************************************/

llxHeader('',$langs->trans('CustomerCard'));

$actionstatic=new ActionComm($db);
$facturestatic=new Facture($db);
$contactstatic = new Contact($db);
$userstatic=new User($db);

if ($socid > 0)
{
    // On recupere les donnees societes par l'objet
    $objsoc = new Societe($db);
    $objsoc->id=$socid;
    $objsoc->fetch($socid,$to);

    $dac = strftime("%Y-%m-%d %H:%M", time());
    if ($errmesg)
    {
        print "<b>$errmesg</b><br>";
    }

    /*
     * Affichage onglets
     */

	$head = societe_prepare_head($objsoc);

    dolibarr_fiche_head($head, 'customer', $objsoc->nom);


    /*
     *
     */
    print '<table width="100%" class="notopnoleftnoright">';
    print '<tr><td valign="top" class="notopnoleft">';
	
    print '<table class="border" width="100%">';

    print '<tr><td width="30%">'.$langs->trans("Name").'</td><td width="70%" colspan="3">';
    print $objsoc->nom;
    print '</td></tr>';

    print '<tr><td>'.$langs->trans('Prefix').'</td><td colspan="3">'.$objsoc->prefix_comm.'</td></tr>';

    if ($objsoc->client)
    {
        print '<tr><td nowrap>';
        print $langs->trans('CustomerCode').'</td><td colspan="3">';
        print $objsoc->code_client;
        if ($objsoc->check_codeclient() <> 0) print '  <font class="error">('.$langs->trans("WrongCustomerCode").')</font>';
        print '</td></tr>';
    }

    print "<tr><td valign=\"top\">".$langs->trans('Address')."</td><td colspan=\"3\">".nl2br($objsoc->adresse)."</td></tr>";

    print '<tr><td>'.$langs->trans('Zip').'</td><td>'.$objsoc->cp."</td>";
    print '<td>'.$langs->trans('Town').'</td><td>'.$objsoc->ville."</td></tr>";
    if ($objsoc->pays) {
    	print '<tr><td>'.$langs->trans('Country').'</td><td colspan="3">'.$objsoc->pays.'</td></tr>';
    }

    print '<tr><td>'.$langs->trans('Phone').'</td><td>'.dolibarr_print_phone($objsoc->tel,$objsoc->pays_code).'</td>';
    print '<td>'.$langs->trans('Fax').'</td><td>'.dolibarr_print_phone($objsoc->fax,$objsoc->pays_code).'</td></tr>';

    print '<tr><td>'.$langs->trans("Web")."</td><td colspan=\"3\"><a href=\"http://$objsoc->url\" target=\"_blank\">".$objsoc->url."</a>&nbsp;</td></tr>";

	// Assujeti à TVA ou pas
	print '<tr>';
	print '<td nowrap="nowrap">'.$langs->trans('VATIsUsed').'</td><td colspan="3">';
	print yn($objsoc->tva_assuj);
	print '</td>';
	print '</tr>';

	// Conditions de réglement par défaut
	$langs->load('bills');
	$html = new Form($db);
	print '<tr><td nowrap>';
	print '<table width="100%" class="nobordernopadding"><tr><td nowrap>';
	print $langs->trans('PaymentConditions');
	print '<td>';
	if (($_GET['action'] != 'editconditions') && $user->rights->societe->creer) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editconditions&amp;socid='.$objsoc->id.'">'.img_edit($langs->trans('SetConditions'),1).'</a></td>';
	print '</tr></table>';
	print '</td><td colspan="3">';
	if ($_GET['action'] == 'editconditions')
	{
		$html->form_conditions_reglement($_SERVER['PHP_SELF'].'?socid='.$objsoc->id,$objsoc->cond_reglement,'cond_reglement_id',-1,1);
	}
	else
	{
		$html->form_conditions_reglement($_SERVER['PHP_SELF'].'?socid='.$objsoc->id,$objsoc->cond_reglement,'none');
	}
	print "</td>";
	print '</tr>';

	// Mode de règlement
	print '<tr><td nowrap>';
	print '<table width="100%" class="nobordernopadding"><tr><td nowrap>';
	print $langs->trans('PaymentMode');
	print '<td>';
	if (($_GET['action'] != 'editmode') && $user->rights->societe->creer) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editmode&amp;socid='.$objsoc->id.'">'.img_edit($langs->trans('SetMode'),1).'</a></td>';
	print '</tr></table>';
	print '</td><td colspan="3">';
	if ($_GET['action'] == 'editmode')
	{
		$html->form_modes_reglement($_SERVER['PHP_SELF'].'?socid='.$objsoc->id,$objsoc->mode_reglement,'mode_reglement_id');
	}
	else
	{
		$html->form_modes_reglement($_SERVER['PHP_SELF'].'?socid='.$objsoc->id,$objsoc->mode_reglement,'none');
	}
	print "</td>";
	print '</tr>';

    // Réductions relative (Remises-Ristournes-Rabbais)
    print '<tr><td nowrap>';
    print '<table width="100%" class="nobordernopadding"><tr><td nowrap>';
    print $langs->trans("CustomerRelativeDiscountShort");
    print '<td><td align="right">';
    if ($user->rights->societe->creer)
    {
    	print '<a href="'.DOL_URL_ROOT.'/comm/remise.php?id='.$objsoc->id.'">'.img_edit($langs->trans("Modify")).'</a>';
    }
    print '</td></tr></table>';
    print '</td><td colspan="3">'.($objsoc->remise_client?$objsoc->remise_client.'%':$langs->trans("DiscountNone")).'</td>';
    print '</tr>';
    
    // Réductions absolues (Remises-Ristournes-Rabbais)
    print '<tr><td nowrap>';
    print '<table width="100%" class="nobordernopadding">';
    print '<tr><td nowrap>';
    print $langs->trans("CustomerAbsoluteDiscountShort");
    print '<td><td align="right">';
    if ($user->rights->societe->creer)
    {
    	print '<a href="'.DOL_URL_ROOT.'/comm/remx.php?id='.$objsoc->id.'">'.img_edit($langs->trans("Modify")).'</a>';
    }
    print '</td></tr></table>';
    print '</td>';
    print '<td colspan="3">';
		$amount_discount=$objsoc->getAvailableDiscounts();
		if ($amount_discount < 0) dolibarr_print_error($db,$societe->error);
        if ($amount_discount > 0) print price($amount_discount).'&nbsp;'.$langs->trans("Currency".$conf->monnaie);
        else print $langs->trans("DiscountNone");
    print '</td>';
    print '</tr>';

	// multiprix
	if ($conf->global->PRODUIT_MULTIPRICES)
	{
		print '<tr><td nowrap>';
		print '<table width="100%" class="nobordernopadding"><tr><td nowrap>';
		print $langs->trans("PriceLevel");
		print '<td><td align="right">';
		if ($user->rights->societe->creer)
		{
			print '<a href="'.DOL_URL_ROOT.'/comm/multiprix.php?id='.$objsoc->id.'">'.img_edit($langs->trans("Modify")).'</a>';
		}
		print '</td></tr></table>';
		print '</td><td colspan="3">'.$objsoc->price_level."</td>";
		print '</tr>';
	}
	
	// Adresse de livraison
	if ($conf->expedition->enabled)
	{
		print '<tr><td nowrap>';
		print '<table width="100%" class="nobordernopadding"><tr><td nowrap>';
		print $langs->trans("DeliveriesAddress");
		print '<td><td align="right">';
		if ($user->rights->societe->creer)
	    {
    		print '<a href="'.DOL_URL_ROOT.'/comm/adresse_livraison.php?socid='.$objsoc->id.'">'.img_edit($langs->trans("Modify")).'</a>';
    	}
		print '</td></tr></table>';
		print '</td><td colspan="3">';

		$sql = "SELECT count(rowid) as nb";
	    $sql.= " FROM ".MAIN_DB_PREFIX."societe_adresse_livraison";
	    $sql.= " WHERE fk_societe =".$objsoc->id;
	
	    $resql = $db->query($sql);
	    if ($resql)
	    {
	        $num = $db->num_rows($resql);
	        $objal = $db->fetch_object($resql);
	        print $objal->nb?($objal->nb):$langs->trans("NoOtherDeliveryAddress");
	    }
	    else
	    {
	        dolibarr_print_error($db);
	    }
	    
		print '</td>';
		print '</tr>';
	}	

    print "</table>";

    print "</td>\n";


    print '<td valign="top" width="50%" class="notopnoleftnoright">';

    // Nbre max d'éléments des petites listes
    $MAXLIST=4;

    // Lien recap
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre">';
    print '<td colspan="4"><table width="100%" class="noborder"><tr><td>'.$langs->trans("Summary").'</td>';
    print '<td align="right"><a href="'.DOL_URL_ROOT.'/comm/recap-client.php?socid='.$objsoc->id.'">'.$langs->trans("ShowCustomerPreview").'</a></td></tr></table></td>';
    print '</tr>';
    print '</table>';
    print '<br>';


    /*
     * Dernieres propales
     */
    if ($conf->propal->enabled)
    {
        $propal_static=new Propal($db);

        print '<table class="noborder" width="100%">';

	    $sql = "SELECT s.nom, s.rowid, p.rowid as propalid, p.fk_statut, p.total_ht, p.ref, p.remise, ";
	    $sql.= " ".$db->pdate("p.datep")." as dp, ".$db->pdate("p.fin_validite")." as datelimite";
        $sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."propal as p, ".MAIN_DB_PREFIX."c_propalst as c";
        $sql .= " WHERE p.fk_soc = s.rowid AND p.fk_statut = c.id";
        $sql .= " AND s.rowid = ".$objsoc->id;
        $sql .= " ORDER BY p.datep DESC";
        
        $resql=$db->query($sql);
        if ($resql)
        {
            $var=true;
            $num = $db->num_rows($resql);
            if ($num > 0)
            {
                print '<tr class="liste_titre">';
                print '<td colspan="4"><table width="100%" class="noborder"><tr><td>'.$langs->trans("LastPropals",($num<=$MAXLIST?"":$MAXLIST)).'</td><td align="right"><a href="'.DOL_URL_ROOT.'/comm/propal.php?socid='.$objsoc->id.'">'.$langs->trans("AllPropals").' ('.$num.')</a></td></tr></table></td>';
                print '</tr>';
                $var=!$var;
            }
            $i = 0;
            while ($i < $num && $i < $MAXLIST)
            {
                $objp = $db->fetch_object($resql);
                print "<tr $bc[$var]>";
                print "<td nowrap><a href=\"propal.php?propalid=$objp->propalid\">".img_object($langs->trans("ShowPropal"),"propal")." ".$objp->ref."</a>\n";
                if ( ($objp->dp < time() - $conf->propal->cloture->warning_delay) && $objp->fk_statut == 1 )
                {
                    print " ".img_warning();
                }
                print '</td><td align="right" width="80">'.dolibarr_print_date($objp->dp)."</td>\n";
                print '<td align="right" width="120">'.price($objp->total_ht).'</td>';
                print '<td align="right" nowrap="nowrap">'.$propal_static->LibStatut($objp->fk_statut,5).'</td></tr>';
                $var=!$var;
                $i++;
            }
            $db->free($resql);
        }
        else {
            dolibarr_print_error($db);
        }
        print "</table>";
    }

    /*
     * Dernieres commandes
     */
    if($conf->commande->enabled)
    {
        $commande_static=new Commande($db);
        
        print '<table class="noborder" width="100%">';

        $sql = "SELECT s.nom, s.rowid,";
        $sql.= " c.rowid as cid, c.total_ht, c.ref, c.fk_statut, c.facture,";
        $sql.= " ".$db->pdate("c.date_commande")." as dc";
        $sql.= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."commande as c";
        $sql.= " WHERE c.fk_soc = s.rowid ";
        $sql.= " AND s.rowid = ".$objsoc->id;
        $sql.= " ORDER BY c.date_commande DESC";

        $resql=$db->query($sql);
        if ($resql)
        {
            $var=true;
            $num = $db->num_rows($resql);
            if ($num >0 )
            {
                print '<tr class="liste_titre">';
                print '<td colspan="4"><table width="100%" class="noborder"><tr><td>'.$langs->trans("LastOrders",($num<=$MAXLIST?"":$MAXLIST)).'</td><td align="right"><a href="'.DOL_URL_ROOT.'/commande/liste.php?socid='.$objsoc->id.'">'.$langs->trans("AllOrders").' ('.$num.')</a></td></tr></table></td>';
                print '</tr>';
            }
            $i = 0;
            while ($i < $num && $i < $MAXLIST)
            {
                $objp = $db->fetch_object($resql);
                $var=!$var;
                print "<tr $bc[$var]>";
                print '<td nowrap="nowrap"><a href="'.DOL_URL_ROOT.'/commande/fiche.php?id='.$objp->cid.'">'.img_object($langs->trans("ShowOrder"),"order").' '.$objp->ref."</a>\n";
                print '</td><td align="right" width="80">'.dolibarr_print_date($objp->dc)."</td>\n";
                print '<td align="right" width="120">'.price($objp->total_ht).'</td>';
                print '<td align="right" width="100">'.$commande_static->LibStatut($objp->fk_statut,$objp->facture,5).'</td></tr>';
                $i++;
            }
            $db->free($resql);
        }
        else {
            dolibarr_print_error($db);
        }
        print "</table>";
    }

    /*
     * Derniers contrats
     */
    if($conf->contrat->enabled)
    {
        $contratstatic=new Contrat($db);
        
        print '<table class="noborder" width="100%">';

        $sql = "SELECT s.nom, s.rowid, c.rowid as id, c.ref as ref, c.statut, ".$db->pdate("c.datec")." as dc";
        $sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."contrat as c";
        $sql .= " WHERE c.fk_soc = s.rowid ";
        $sql .= " AND s.rowid = ".$objsoc->id;
        $sql .= " ORDER BY c.datec DESC";

        $resql=$db->query($sql);
        if ($resql)
        {
            $var=true;
            $num = $db->num_rows($resql);
            if ($num >0 )
            {
                print '<tr class="liste_titre">';
                print '<td colspan="4"><table width="100%" class="noborder"><tr><td>'.$langs->trans("LastContracts",($num<=$MAXLIST?"":$MAXLIST)).'</td>';
                print '<td align="right"><a href="'.DOL_URL_ROOT.'/contrat/liste.php?socid='.$objsoc->id.'">'.$langs->trans("AllContracts").' ('.$num.')</a></td></tr></table></td>';
                print '</tr>';
            }
            $i = 0;
            while ($i < $num && $i < $MAXLIST)
            {
                $objp = $db->fetch_object($resql);
                $var=!$var;
                print "<tr $bc[$var]>";
                print '<td><a href="'.DOL_URL_ROOT.'/contrat/fiche.php?id='.$objp->id.'">'.img_object($langs->trans("ShowContract"),"contract").' '
                .(!isset($objp->ref) ? $objp->id : $objp->ref) ."</a></td>\n";
                print '<td align="right" width="80">'.dolibarr_print_date($objp->dc)."</td>\n";
                print '<td width="20">&nbsp;</td>';
                print '<td align="right" nowrap="nowrap">'.$contratstatic->LibStatut($objp->statut,5)."</td>\n";
                print '</tr>';
                $i++;
            }
            $db->free($resql);
        }
        else {
            dolibarr_print_error($db);
        }
        print "</table>";
    }
    
    /*
     * Dernieres interventions
     */
    if ($conf->fichinter->enabled)
    {
        print '<table class="noborder" width="100%">';

        $sql = "SELECT s.nom, s.rowid, f.rowid as id, f.ref, ".$db->pdate("f.datei")." as di";
        $sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."fichinter as f";
        $sql .= " WHERE f.fk_soc = s.rowid";
        $sql .= " AND s.rowid = ".$objsoc->id;
        $sql .= " ORDER BY f.datei DESC";
        
        $resql=$db->query($sql);
        if ($resql)
        {
            $var=true;
            $num = $db->num_rows($resql);
            if ($num >0 )
            {
                print '<tr class="liste_titre">';
                print '<td colspan="4"><table width="100%" class="noborder"><tr><td>'.$langs->trans("LastInterventions",($num<=$MAXLIST?"":$MAXLIST)).'</td><td align="right"><a href="'.DOL_URL_ROOT.'/fichinter/index.php?socid='.$objsoc->id.'">'.$langs->trans("AllInterventions").' ('.$num.')</td></tr></table></td>';
                print '</tr>';
                $var=!$var;
            }
            $i = 0;
            while ($i < $num && $i < $MAXLIST)
            {
                $objp = $db->fetch_object($resql);
                print "<tr $bc[$var]>";
                print '<td nowrap><a href="'.DOL_URL_ROOT."/fichinter/fiche.php?id=".$objp->id."\">".img_object($langs->trans("ShowPropal"),"propal")." ".$objp->ref."</a>\n";
                print "</td><td align=\"right\">".dolibarr_print_date($objp->di)."</td>\n";
                print '</tr>';
                $var=!$var;
                $i++;
            }
            $db->free($resql);
        }
        else {
            dolibarr_print_error($db);
        }
        print "</table>";
    }
    
    /*
     * Derniers projets associés
     */
    if ($conf->projet->enabled)
    {
        print '<table class="noborder" width=100%>';

        $sql  = "SELECT p.rowid,p.title,p.ref,".$db->pdate("p.dateo")." as do";
        $sql .= " FROM ".MAIN_DB_PREFIX."projet as p";
        $sql .= " WHERE p.fk_soc = $objsoc->id";
        $sql .= " ORDER BY p.dateo DESC";

        $result=$db->query($sql);
        if ($result) {
            $var=true;
            $i = 0 ;
            $num = $db->num_rows($result);
            if ($num > 0) {
                print '<tr class="liste_titre">';
                print '<td colspan="2"><table width="100%" class="noborder"><tr><td>'.$langs->trans("LastProjects",($num<=$MAXLIST?"":$MAXLIST)).'</td><td align="right"><a href="'.DOL_URL_ROOT.'/projet/liste.php?socid='.$objsoc->id.'">'.$langs->trans("AllProjects").' ('.$num.')</td></tr></table></td>';
                print '</tr>';
            }
            while ($i < $num && $i < $MAXLIST) {
                $obj = $db->fetch_object($result);
                $var = !$var;
                print "<tr $bc[$var]>";
                print '<td><a href="../projet/fiche.php?id='.$obj->rowid.'">'.img_object($langs->trans("ShowProject"),"project")." ".$obj->title.'</a></td>';

                print "<td align=\"right\">".$obj->ref ."</td></tr>";
                $i++;
            }
            $db->free($result);
        }
        else
        {
            dolibarr_print_error($db);
        }
        print "</table>";
    }

    print "</td></tr>";
    print "</table></div>\n";


    /*
     * Barre d'action
     *
     */
    print '<div class="tabsAction">';

    if ($conf->propal->enabled && $user->rights->propale->creer)
    {
        $langs->load("propal");
        print '<a class="butAction" href="'.DOL_URL_ROOT.'/comm/addpropal.php?socid='.$objsoc->id.'&amp;action=create">'.$langs->trans("AddProp").'</a>';
    }

    if ($conf->commande->enabled && $user->rights->commande->creer)
    {
        $langs->load("orders");
        print '<a class="butAction" href="'.DOL_URL_ROOT.'/commande/fiche.php?socid='.$objsoc->id.'&amp;action=create">'.$langs->trans("AddOrder").'</a>';
    }

    if ($user->rights->contrat->creer)
    {
        $langs->load("contracts");
        print '<a class="butAction" href="'.DOL_URL_ROOT.'/contrat/fiche.php?socid='.$objsoc->id.'&amp;action=create">'.$langs->trans("AddContract").'</a>';
    }

    if ($conf->fichinter->enabled && $user->rights->ficheinter->creer)
    {
        $langs->load("fichinter");
        print '<a class="butAction" href="'.DOL_URL_ROOT.'/fichinter/fiche.php?socid='.$objsoc->id.'&amp;action=create">'.$langs->trans("AddIntervention").'</a>';
    }

    print '<a class="butAction" href="'.DOL_URL_ROOT.'/comm/action/fiche.php?action=create&socid='.$objsoc->id.'">'.$langs->trans("AddAction").'</a>';

	if ($user->rights->societe->contact->creer)
	{
	    print '<a class="butAction" href="'.DOL_URL_ROOT.'/contact/fiche.php?socid='.$objsoc->id.'&amp;action=create">'.$langs->trans("AddContact").'</a>';
	}
	
    print '</div>';
    print '<br>';

    /*
     *
     * Liste des contacts
     *
     */
    if ($conf->clicktodial->enabled)
    {
        $user->fetch_clicktodial(); // lecture des infos de clicktodial
    }

	print_titre($langs->trans("ContactsForCompany"));
    print '<table class="noborder" width="100%">';

    print '<tr class="liste_titre"><td>'.$langs->trans("Name").'</td>';
    print '<td>'.$langs->trans("Poste").'</td><td colspan="2">'.$langs->trans("Tel").'</td>';
    print '<td>'.$langs->trans("Fax").'</td><td>'.$langs->trans("EMail").'</td>';
    print "<td>&nbsp;</td>";
    print '<td>&nbsp;</td>';
    print "</tr>";

    $sql = "SELECT p.rowid, p.name, p.firstname, p.poste, p.phone, p.fax, p.email, p.note ";
    $sql .= " FROM ".MAIN_DB_PREFIX."socpeople as p";
    $sql .= " WHERE p.fk_soc = ".$objsoc->id;
    $sql .= " ORDER by p.datec";

    $result = $db->query($sql);
    $i = 0;
    $num = $db->num_rows($result);
    $var=true;

    while ($i < $num)
    {
        $obj = $db->fetch_object($result);
        $var = !$var;

        print "<tr $bc[$var]>";

        print '<td>';
        $contactstatic->id = $obj->rowid;
        $contactstatic->name = $obj->name;
        $contactstatic->firstname = $obj->firstname;
        print $contactstatic->getNomUrl(1);
        print '</td>';

        print '<td>'.$obj->poste.'&nbsp;</td>';

        // Lien click to dial
        if (strlen($obj->phone) && $user->clicktodial_enabled == 1)
        {
            print '<td>';
            print '<a href="'.DOL_URL_ROOT.'/comm/action/fiche.php?action=create&actioncode=AC_TEL&contactid='.$obj->rowid.'&amp;socid='.$objsoc->id.'&amp;call='.$obj->phone.'">';
            print img_phone_out("Appel émis") ;
            print '</td><td>';
        }
        else
        {
        	print '<td colspan="2">';
        }
        
        print '<a href="action/fiche.php?action=create&actioncode=AC_TEL&contactid='.$obj->rowid.'&socid='.$objsoc->id.'">'.dolibarr_print_phone($obj->phone).'</a>&nbsp;</td>';
        print '<td><a href="action/fiche.php?action=create&actioncode=AC_FAX&contactid='.$obj->rowid.'&socid='.$objsoc->id.'">'.dolibarr_print_phone($obj->fax).'</a>&nbsp;</td>';
        print '<td><a href="action/fiche.php?action=create&actioncode=AC_EMAIL&contactid='.$obj->rowid.'&socid='.$objsoc->id.'">'.$obj->email.'</a>&nbsp;</td>';

        print '<td align="center">';
        print "<a href=\"../contact/fiche.php?action=edit&amp;id=".$obj->rowid."\">";
        print img_edit();
        print '</a></td>';

        print '<td align="center"><a href="action/fiche.php?action=create&actioncode=AC_RDV&contactid='.$obj->rowid.'&socid='.$objsoc->id.'">';
        print img_object($langs->trans("Rendez-Vous"),"action");
        print '</a></td>';

        print "</tr>\n";
        $i++;
    }
    print "</table>";

    print "<br>";


    /*
     *      Listes des actions a faire
     *
     */
	print_titre($langs->trans("ActionsOnCompany"));
	
    print '<table width="100%" class="noborder">';
    print '<tr class="liste_titre">';
    print '<td colspan="11"><a href="'.DOL_URL_ROOT.'/comm/action/index.php?socid='.$objsoc->id.'&amp;status=todo">'.$langs->trans("ActionsToDoShort").'</a></td><td align="right">&nbsp;</td>';
    print '</tr>';

    $sql = "SELECT a.id, a.label,";
    $sql.= " ".$db->pdate("a.datep")." as dp,";
    $sql.= " ".$db->pdate("a.datea")." as da,";
    $sql.= " a.percent,";
    $sql.= " c.code as acode, c.libelle, a.propalrowid, a.fk_user_author, a.fk_contact,";
	$sql.= " u.login, u.rowid,";
	$sql.= " sp.name, sp.firstname";
    $sql.= " FROM ".MAIN_DB_PREFIX."c_actioncomm as c, ".MAIN_DB_PREFIX."user as u, ".MAIN_DB_PREFIX."actioncomm as a";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."socpeople as sp ON a.fk_contact = sp.rowid";
    $sql.= " WHERE a.fk_soc = ".$objsoc->id;
    $sql.= " AND u.rowid = a.fk_user_author";
    $sql.= " AND c.id=a.fk_action AND a.percent < 100";
    $sql.= " ORDER BY a.datep DESC, a.id DESC";

	dolibarr_syslog("comm/fiche.php sql=".$sql);
    $result=$db->query($sql);
    if ($result)
    {
        $i = 0 ;
        $num = $db->num_rows($result);
        $var=true;
        
        if ($num)
        {
            while ($i < $num)
            {
                $var = !$var;

                $obj = $db->fetch_object($result);
                print "<tr $bc[$var]>";

                if ($oldyear == strftime("%Y",$obj->dp) && !$conf->global->COMPANY_VIEW_FULL_DATE_ACTIONS)
                {
                    print '<td width="30" align="center">|</td>';
                }
                else
                {
                    print '<td width="30" align="center">'.strftime("%Y",$obj->dp)."</td>\n";
                    $oldyear = strftime("%Y",$obj->dp);
                }

                if ($oldmonth == strftime("%Y%b",$obj->dp) && !$conf->global->COMPANY_VIEW_FULL_DATE_ACTIONS)
                {
                    print '<td width="30" align="center">|</td>';
                }
                else
                {
                    print '<td width="30" align="center">' .strftime("%b",$obj->dp)."</td>\n";
                    $oldmonth = strftime("%Y%b",$obj->dp);
                }

                print '<td width="20">'.strftime("%d",$obj->dp)."</td>\n";
               	print '<td width="30" nowrap="nowrap">'.strftime("%H:%M",$obj->dp).'</td>';
				
				// Picto warning
				print '<td width="16">';
				if (date("U",$obj->dp) < time()) print ' '.img_warning("Late");
				else print '&nbsp;';
				print '</td>';

                // Status/Percent
                print '<td width="30">&nbsp;</td>';

                if ($obj->propalrowid)
                {
                    print '<td><a href="propal.php?propalid='.$obj->propalrowid.'">'.img_object($langs->trans("ShowAction"),"task");
                    $transcode=$langs->trans("Action".$obj->acode);
                    $libelle=($transcode!="Action".$obj->acode?$transcode:$obj->libelle);
                    print $libelle;
                    print '</a></td>';
                }
                else
                {
		            $actionstatic->code=$obj->acode;
		            $actionstatic->libelle=$obj->libelle;
		            $actionstatic->id=$obj->id;
		            print '<td>'.$actionstatic->getNomUrl(1,16).'</td>';
                }
                print '<td colspan="2">'.$obj->label.'</td>';

                // Contact pour cette action
                if ($obj->fk_contact > 0)
                {
                    $contactstatic->name=$obj->name;
                    $contactstatic->firstname=$obj->firstname;
                    $contactstatic->id=$obj->fk_contact;
	                print '<td>'.$contactstatic->getNomUrl(1).'</td>';
                }
                else
                {
                    print '<td>&nbsp;</td>';
                }

                print '<td width="80" nowrap="nowrap">';
				$userstatic->id=$obj->fk_user_author;
				$userstatic->login=$obj->login;
				print $userstatic->getLoginUrl(1);
				print '</td>';

				// Statut
                print '<td nowrap="nowrap" width="20">'.$actionstatic->LibStatut($obj->percent,3).'</td>';

                print "</tr>\n";
                $i++;
            }
		}
		else
		{
			// Aucun action à faire
				
		}
        $db->free($result);
    }
    else
    {
        dolibarr_print_error($db);
    }
    print "</table>";

    print "<br>";


    /*
     *      Listes des actions effectuees
     */
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre">';
    print '<td colspan="12"><a href="'.DOL_URL_ROOT.'/comm/action/index.php?socid='.$objsoc->id.'&amp;status=done">'.$langs->trans("ActionsDoneShort").'</a></td>';
    print '</tr>';

    $sql = "SELECT a.id, a.label,";
    $sql.= " ".$db->pdate("a.datep")." as dp,";
    $sql.= " ".$db->pdate("a.datea")." as da,";
    $sql.= " a.percent,";
    $sql.= " a.propalrowid, a.fk_facture, a.fk_user_author, a.fk_contact,";
    $sql.= " c.code as acode, c.libelle,";
    $sql.= " u.login, u.rowid,";
    $sql.= " sp.name, sp.firstname";
    $sql.= " FROM ".MAIN_DB_PREFIX."c_actioncomm as c, ".MAIN_DB_PREFIX."user as u, ".MAIN_DB_PREFIX."actioncomm as a";
    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."socpeople as sp ON a.fk_contact = sp.rowid";
    $sql.= " WHERE a.fk_soc = ".$objsoc->id;
    $sql.= " AND u.rowid = a.fk_user_author";
    $sql.= " AND c.id=a.fk_action AND a.percent = 100";
    $sql.= " ORDER BY a.datea DESC, a.id DESC";

	dolibarr_syslog("comm/fiche.php sql=".$sql);
    $result=$db->query($sql);
    if ($result)
    {
        $i = 0 ;
        $num = $db->num_rows($result);
        $oldyear='';
        $oldmonth='';
        $var=true;

        while ($i < $num)
        {
            $var = !$var;

            $obj = $db->fetch_object($result);
            print "<tr $bc[$var]>";

            // Champ date
            if ($oldyear == strftime("%Y",$obj->da) && !$conf->global->COMPANY_VIEW_FULL_DATE_ACTIONS)
            {
                print '<td width="30" align="center">|</td>';
            }
            else
            {
                print '<td width="30" align="center">'.strftime("%Y",$obj->da)."</td>\n";
                $oldyear = strftime("%Y",$obj->da);
            }

            if ($oldmonth == strftime("%Y%b",$obj->da) && !$conf->global->COMPANY_VIEW_FULL_DATE_ACTIONS)
            {
                print '<td width="30" align="center">|</td>';
            }
            else
            {
                print '<td width="30" align="center">'.strftime("%b",$obj->da)."</td>\n";
                $oldmonth = strftime("%Y%b",$obj->da);
            }
            print '<td width="20">'.strftime("%d",$obj->da)."</td>\n";
            print '<td width="30">'.strftime("%H:%M",$obj->da)."</td>\n";

			// Picto
            print '<td width="16">&nbsp;</td>';

            // Espace
            print '<td width="30">&nbsp;</td>';

			// Action
    		print '<td>';
            $actionstatic->code=$obj->acode;
            $actionstatic->libelle=$obj->libelle;
            $actionstatic->id=$obj->id;
            print $actionstatic->getNomUrl(1,16);
			print '</td>';

    		// Objet lié
    		print '<td>';
			if ($obj->propalrowid)
			{
				print '<a href="'.DOL_URL_ROOT.'/comm/propal.php?propalid='.$obj->propalrowid.'">'.img_object($langs->trans("ShowPropal"),"propal");
				print $langs->trans("Propal");
				print '</a>';
			}
			if ($obj->fk_facture)
			{
				$facturestatic->ref=$langs->trans("Invoice");
				$facturestatic->id=$obj->fk_facture;
				$facturestatic->type=$obj->type;
				print $facturestatic->getNomUrl(1,'compta');
			}
			else print '&nbsp;';
    		print '</td>';

			// Libellé
      print '<td>'.$obj->label.'</td>';

            // Contact pour cette action
            if ($obj->fk_contact > 0)
            {
				$contactstatic->name=$obj->name;
				$contactstatic->firstname=$obj->firstname;
				$contactstatic->id=$obj->fk_contact;
                print '<td>'.$contactstatic->getNomUrl(1).'</td>';
            }
            else
            {
                print '<td>&nbsp;</td>';
            }

			// Auteur
            print '<td nowrap="nowrap" width="80">';
			$userstatic->id=$obj->rowid;
			$userstatic->login=$obj->login;
			print $userstatic->getLoginUrl(1);
			print '</td>';

			// Statut
      print '<td nowrap="nowrap" width="20">'.$actionstatic->LibStatut($obj->percent,3).'</td>';
			
            print "</tr>\n";
            $i++;
        }

        $db->free($result);
    }
    else
    {
        dolibarr_print_error($db);
    }
 
    print "</table><br>";
}
else
{
    dolibarr_print_error($db,'Bad value for socid parameter');
}

$db->close();


llxFooter('$Date$ - $Revision$');
?>
