<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2010-2011 Herve Prot           <herve.prot@symeos.com>
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
 */

/**
 *	\file       htdocs/comm/prospect/fiche.php
 *	\ingroup    prospect
 *	\brief      Page de la fiche prospect
 *	\version    $Id$
 */

require_once("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT."/comm/prospect/class/prospect.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formcompany.class.php");
require_once(DOL_DOCUMENT_ROOT."/contact/class/contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/comm/action/class/actioncomm.class.php");
if ($conf->adherent->enabled) require_once(DOL_DOCUMENT_ROOT."/adherents/class/adherent.class.php");
if ($conf->propal->enabled) require_once(DOL_DOCUMENT_ROOT."/comm/propal/class/propal.class.php");
if ($conf->lead->enabled) require_once(DOL_DOCUMENT_ROOT."/lead/lib/lead.lib.php");

$langs->load('companies');
$langs->load('lead@lead');
$langs->load('projects');
$langs->load('propal');
$langs->load('commercial');

// Security check
$socid = isset($_GET["socid"])?$_GET["socid"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe',$socid,'');


/*
 * Actions
 */

if ($_GET["action"] == 'cstc')
{
        $sql= "SELECT libelle, type FROM ".MAIN_DB_PREFIX."c_stcomm";
        $sql.= " WHERE id=".$_GET["stcomm"];
        
        $resql=$db->query($sql);
        if ($resql)
        {
            $obj = $db->fetch_object($resql);
        }
        else
	{
            dol_print_error($db);
	}
        
        $sql= "SELECT fk_stcomm FROM ".MAIN_DB_PREFIX."societe";
        $sql.= " WHERE rowid=".$_GET["socid"];
        
        $resql=$db->query($sql);
        if ($resql)
        {
            $objp = $db->fetch_object($resql);
        }
        else
	{
            dol_print_error($db);
	}

	$sql = "UPDATE ".MAIN_DB_PREFIX."societe SET fk_stcomm = ".$_GET["stcomm"].", client=".($obj->type?"1":"2");
	$sql .= " WHERE rowid = ".$_GET["socid"];
	$db->query($sql);

        $actioncomm = new ActionComm($db);
        $actioncomm->addAutoTask('AC_PROSPECT',$_GET["stcomm"]." Statut de prospection : ".$obj->libelle,$_GET["socid"],'','');
        
        if($objp->fk_stcomm==0 && $_GET["stcomm"] > 0)
        {
            $actioncomm = new ActionComm($db);
            $actioncomm->addAutoTask('AC_SUSP',"Statut de prospection : ".$obj->libelle,$_GET["socid"],'','');
        }

        if (! empty($_GET["backtopage"]))
        {
            header("Location: ".$_GET["backtopage"]);
        }
}
// set prospect level
if ($_POST["action"] == 'setprospectlevel' && $user->rights->societe->creer)
{
	$societe = new Societe($db, $_GET["socid"]);
	$societe->fk_prospectlevel=$_POST['prospect_level_id'];
	$sql = "UPDATE ".MAIN_DB_PREFIX."societe SET fk_prospectlevel='".$_POST['prospect_level_id'];
	$sql.= "' WHERE rowid='".$_GET["socid"]."'";
	$result = $db->query($sql);
	if (! $result) dol_print_error($result);
}


/*********************************************************************************
 *
 * Mode fiche
 *
 *********************************************************************************/

llxHeader();

$now = dol_now();

$form=new Form($db);
$formcompany=new FormCompany($db);

if ($socid > 0)
{
	$actionstatic=new ActionComm($db);
	$societe = new Prospect($db, $socid);
	$result = $societe->fetch($socid);
	if ($result < 0)
	{
		dol_print_error($db);
		exit;
	}

	/*
	 * Affichage onglets
	 */
	$head = societe_prepare_head($societe);

	dol_fiche_head($head, 'prospect', $langs->trans("ThirdParty"),0,'company');

        $var=true;
	print '<table width="100%" class="notopnoleftnoright">';
	print '<tr><td valign="top" width="50%" class="notopnoleft">';

	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><td colspan="4">';
	$societe->next_prev_filter="te.client in (2,3)";
	print $form->showrefnav($societe,'socid','',($user->societe_id?0:1),'rowid','nom','','');
	print '</td></tr>';

        // Name
	print '<tr '.$bc[$var].'><td id="label" width="20%">'.$langs->trans('Name').'</td>';
	print '<td colspan="1" id="value" width="30%">';
	print $societe->getNomUrl(1);
	print '</td>';

	print '<td id="label" width="20%">'.$langs->trans('Prefix').'</td><td colspan="1" id="value" >'.$societe->prefix_comm.'</td></tr>';
        $var=!$var;

        if ($societe->client)
	{
		print '<tr '.$bc[$var].'><td  colspan="3" id="label">';
		print $langs->trans('CustomerCode').'</td><td id="value">';
		print $societe->code_client;
		if ($societe->check_codeclient() <> 0) print ' <font class="error">('.$langs->trans("WrongCustomerCode").')</font>';
		print '</td></tr>';
                $var=!$var;
	}

	if ($societe->fournisseur)
	{
		print '<tr '.$bc[$var].'><td colspan="3" id="label">';
		print $langs->trans('SupplierCode').'</td><td id="value">';
		print $societe->code_fournisseur;
		if ($societe->check_codefournisseur() <> 0) print ' <font class="error">('.$langs->trans("WrongSupplierCode").')</font>';
		print '</td></tr>';
                $var=!$var;
	}

	print '<tr '.$bc[$var].'><td valign="top" id="label">'.$langs->trans("Address").'</td><td colspan="3" id="value">'.nl2br($societe->address)."</td></tr>";
        $var=!$var;

	// Zip / Town
        print '<tr '.$bc[$var].'><td id="label" width="25%">'.$langs->trans('Zip').' / '.$langs->trans("Town").'</td><td id="value" colspan="3">';
        print $societe->cp.($societe->cp && $societe->ville?" / ":"").$societe->ville;
        print "</td>";
        print '</tr>';
        $var=!$var;

	// Country
        print '<tr '.$bc[$var].'><td id="label">'.$langs->trans("Country").'</td><td id="value" nowrap="nowrap">';
        $img=picto_from_langcode($societe->pays_code);
        if ($societe->isInEEC()) print $form->textwithpicto(($img?$img.' ':'').$societe->pays,$langs->trans("CountryIsInEEC"),1,0);
        else print ($img?$img.' ':'').$societe->pays;
        print '</td>';
        
        // MAP GPS
        if($conf->map->enabled)
            print '<td id="label" colspan="2">GPS '.img_picto(($societe->lat.','.$societe->lng),(($societe->lat && $societe->lng)?"statut4":"statut1")).'</td></tr>';
        else
            print '<td id="label" colspan="2"></td></tr>';
        $var=!$var;

	// Phone
	print '<tr '.$bc[$var].'><td id="label">'.$langs->trans("Phone").'</td><td id="value">'.dol_print_phone($societe->tel,$societe->pays_code,0,$societe->id,'AC_TEL').'</td><td id="label">'.$langs->trans("Fax").'</td><td id="value">'.dol_print_phone($societe->fax,$societe->pays_code).'</td></tr>';
        $var=!$var;

	// EMail
	print '<tr '.$bc[$var].'><td id="label">'.$langs->trans('EMail').'</td><td colspan="3" id="value">'.dol_print_email($societe->email,0,$societe->id,'AC_EMAIL').'</td></tr>';
        $var=!$var;

	// Web
	print '<tr '.$bc[$var].'><td id="label">'.$langs->trans("Web")."</td><td colspan=\"3\" id=\"value\"><a href=\"http://$societe->url\">$societe->url</a></td></tr>";
        $var=!$var;

	print '<tr '.$bc[$var].'><td id="label">'.$langs->trans('JuridicalStatus').'</td><td colspan="3"  id="value">'.$societe->forme_juridique.'</td></tr>';
        $var=!$var;

	// Level of prospect
	print '<tr '.$bc[$var].'><td nowrap>';
	print '<table width="100%" class="nobordernopadding"><tr><td nowrap  id="label">';
	print $langs->trans('ProspectLevelShort');
	print '<td>';
	if (($_GET['action'] != 'editlevel') && $user->rights->societe->creer) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editlevel&amp;socid='.$societe->id.'">'.img_edit($langs->trans('SetLevel'),1).'</a></td>';
	print '</tr></table>';
	print '</td><td colspan="3"  id="value">';
	if ($_GET['action'] == 'editlevel')
	{
		$formcompany->form_prospect_level($_SERVER['PHP_SELF'].'?socid='.$societe->id,$societe->fk_prospectlevel,'prospect_level_id',1);
	}
	else
	{
		print $societe->getLibLevel();
		//$formcompany->form_prospect_level($_SERVER['PHP_SELF'].'?socid='.$objsoc->id,$objsoc->mode_reglement,'none');
	}
	print "</td>";
	print '</tr>';
        $var=!$var;

	// Multiprice level
	if ($conf->global->PRODUIT_MULTIPRICES)
	{
		print '<tr '.$bc[$var].'><td nowrap>';
		print '<table width="100%" class="nobordernopadding"><tr><td nowrap id="label" id="value">';
		print $langs->trans("PriceLevel");
		print '<td><td align="right">';
		if ($user->rights->societe->creer)
		{
			print '<a href="'.DOL_URL_ROOT.'/comm/multiprix.php?id='.$societe->id.'">'.img_edit($langs->trans("Modify")).'</a>';
		}
		print '</td></tr></table>';
		print '</td><td colspan="3">'.$societe->price_level."</td>";
		print '</tr>';
                $var=!$var;
	}

	// Status
	print '<tr '.$bc[$var].'><td id="label">'.$langs->trans("Status").'</td><td id="value">'.$societe->getLibProspStatut(4).'</td>';
	print '<td id="value">'.$societe->getLibStatut(4).'</td>';
        print '<td>';
        // Affichage icone de changement de statut prospect
        print $societe->getIconList();
	print '</td></tr>';
        $var=!$var;

    // Module Adherent
    if ($conf->adherent->enabled)
    {
        $langs->load("members");
        $langs->load("users");
        print '<tr><td width="25%" valign="top">'.$langs->trans("LinkedToDolibarrMember").'</td>';
        print '<td colspan="3">';
        $adh=new Adherent($db);
        $result=$adh->fetch('','',$societe->id);
        if ($result > 0)
        {
            $adh->ref=$adh->getFullName($langs);
            print $adh->getNomUrl(1);
        }
        else
        {
            print $langs->trans("UserNotLinkedToMember");
        }
        print '</td>';
        print "</tr>\n";
    }

    // Commercial
    print '<tr '.$bc[$var].'><td>';
    print '<table width="100%" class="nobordernopadding"><tr><td id="label">';
    print $langs->trans('SalesRepresentatives');
    print '<td><td  id="value" align="right">';
    if ($user->rights->societe->creer)
        print '<a href="'.DOL_URL_ROOT.'/societe/commerciaux.php?socid='.$societe->id.'">'.img_edit().'</a>';
    else
        print '&nbsp;';
        print '</td></tr></table>';
        print '</td>';
        print '<td colspan="3">';

        $listsalesrepresentatives=$societe->getSalesRepresentatives($user);
        $nbofsalesrepresentative=sizeof($listsalesrepresentatives);
        if ($nbofsalesrepresentative > 3)   // We print only number
        {
            print '<a href="'.DOL_URL_ROOT.'/societe/commerciaux.php?socid='.$societe->id.'">';
            print $nbofsalesrepresentative;
            print '</a>';
        }
        else if ($nbofsalesrepresentative > 0)
        {
            $userstatic=new User($db);
            $i=0;
            foreach($listsalesrepresentatives as $val)
            {
                $userstatic->id=$val['id'];
                $userstatic->nom=$val['name'];
                $userstatic->prenom=$val['firstname'];
                print $userstatic->getNomUrl(1);
                $i++;
                if ($i < $nbofsalesrepresentative) print ', ';
            }
        }
        else print $langs->trans("NoSalesRepresentativeAffected");
    print '</td></tr>';
    $var=!$var;
         
    // Affichage des notes
    print '<tr '.$bc[$var].'><td valign="top">';
    print '<table width="100%" class="nobordernopadding"><tr><td id="label">';
    print $langs->trans("Note");
    print '</td><td align="right">';
    if ($user->rights->societe->creer)
        print '<a href="'.DOL_URL_ROOT.'/societe/socnote.php?socid='.$societe->id.'&action=edit&backtopage='.DOL_URL_ROOT.'/comm/prospect/fiche.php?socid='.$societe->id.'">'.img_edit() .'</a>';
    else
        print '&nbsp;';
    print '</td></tr></table>';
    print '</td>';
    print '<td colspan="3" id="value">';
    print nl2br($societe->note);
    print "</td></tr>";
    $var=!$var;

    print '</table>';


	print "</td>\n";
	print '<td valign="top" width="50%" class="notopnoright">';

	// Nbre max d'elements des petites listes
	$MAXLIST=5;
	$tableaushown=0;

        if ($conf->agenda->enabled && $user->rights->agenda->myactions->read)
        {
	// Lien recap
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td colspan="4"><table width="100%" class="nobordernopadding"><tr><td>'.$langs->trans("Summary").'</td>';
	print '<td align="right"><a href="'.DOL_URL_ROOT.'/comm/prospect/recap-prospect.php?socid='.$societe->id.'">'.$langs->trans("ShowProspectPreview").'</a></td></tr></table></td>';
	print '</tr>';
        print '<tr><td nowrap="nowrap" colspan="5">';
        print '</td></tr>';
	print '</table>';
	print '<br>';
        

        }

	/*
	 * Last proposals
	 */
	if ($conf->propal->enabled && $user->rights->propale->lire)
	{
		$propal_static=new Propal($db);
		
		$sql = "SELECT s.nom, s.rowid as socid, p.rowid as propalid, p.fk_statut, p.total_ht, p.ref, p.remise, ";
		$sql.= " p.datep as dp, p.fin_validite as datelimite,";
		$sql.= " c.label as statut, c.id as statutid";
		$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
		$sql.= ", ".MAIN_DB_PREFIX."propal as p";
		$sql.= ", ".MAIN_DB_PREFIX."c_propalst as c";
		$sql.= " WHERE p.fk_soc = s.rowid";
		$sql.= " AND p.fk_statut = c.id";
		$sql.= " AND p.entity = ".$conf->entity;
		$sql.= " AND s.rowid = ".$societe->id;
		$sql.= " ORDER BY p.datep DESC";

		$resql=$db->query($sql);
		if ($resql)
		{
			$var=true;
			$i = 0;
			$num = $db->num_rows($resql);

			if ($num > 0)
			{
                        print '<table class="noborder" width="100%">';
    			print '<tr class="liste_titre">';
    			print '<td colspan="4"><table width="100%" class="nobordernopadding"><tr><td>'.$langs->trans("LastPropals",($num<=$MAXLIST?"":$MAXLIST)).'</td><td align="right"><a href="'.DOL_URL_ROOT.'/comm/propal.php?socid='.$societe->id.'">'.$langs->trans("AllPropals").' ('.$num.')</a></td>';
    			print '<td width="20px" align="right"><a href="'.DOL_URL_ROOT.'/comm/propal/stats/index.php?socid='.$societe->id.'">'.img_picto($langs->trans("Statistics"),'stats').'</a></td>';
    			print '</tr></table></td>';
    			print '</tr>';
			}


			while ($i < $num && $i < $MAXLIST)
			{
				$objp = $db->fetch_object($resql);
				$var=!$var;
				print "<tr $bc[$var]>";
				print "<td><a href=\"../propal.php?id=$objp->propalid\">";
				print img_object($langs->trans("ShowPropal"),"propal");
				print " ".$objp->ref."</a>\n";
				if ($db->jdate($objp->dp) < ($now - $conf->propal->cloture->warning_delay) && $objp->fk_statut == 1)
				{
					print " ".img_warning();
				}
				print "</td><td align=\"right\">".dol_print_date($db->jdate($objp->dp),"day")."</td>\n";
				print "<td align=\"right\">".price($objp->total_ht)."</td>\n";
				print "<td align=\"right\">".$propal_static->LibStatut($objp->fk_statut,5)."</td></tr>\n";
				$i++;
			}
                        
                        if($num > 0)
                            print "</table>";
			$db->free();
		}
		else
		{
			dol_print_error($db);
		}

		
	}

	print "</td></tr>";
	print "</table>\n";

        dol_fiche_end();

	/*
	 * Barre d'action
	 */

	print '<div class="tabsAction">';

        if ($user->rights->societe->creer)
			{
				print '<a class="butAction" href="'.DOL_URL_ROOT.'/societe/soc.php?socid='.$societe->id.'&amp;action=edit&backtopage='.$_SERVER["PHP_SELF"].'?socid='.$societe->id.'">'.$langs->trans("Modify").'</a>';
			}


    if ($conf->propal->enabled && $user->rights->propale->creer)
    {
        print '<a class="butAction" href="'.DOL_URL_ROOT.'/comm/addpropal.php?socid='.$societe->id.'&amp;action=create">'.$langs->trans("AddProp").'</a>';
    }

    // Add action
    if ($conf->agenda->enabled && ! empty($conf->global->MAIN_REPEATTASKONEACHTAB))
    {
        if ($user->rights->agenda->myactions->create)
        {
            print '<a class="butAction" href="'.DOL_URL_ROOT.'/comm/action/fiche.php?action=create&socid='.$socid.'&backtopage='.$_SERVER["PHP_SELF"].'?socid='.$socid.'">'.$langs->trans("AddAction").'</a>';
        }
        else
        {
            print '<a class="butActionRefused" title="'.dol_escape_js($langs->trans("NotAllowed")).'" href="#">'.$langs->trans("AddAction").'</a>';
        }
    }

    //print '<a class="butAction" href="'.DOL_URL_ROOT.'/contact/fiche.php?socid='.$societe->id.'&amp;action=create">'.$langs->trans("AddContact").'</a>';

	print '</div>';

	print '<br>';


    if (! empty($conf->global->MAIN_REPEATCONTACTONEACHTAB))
    {
        print '<table width="100%" class="notopnoleftnoright">';
		print '<tr><td valign="top" width="50%" class="notopnoleft">';
        // List of contacts
        show_contacts($conf,$langs,$db,$societe,$_SERVER["PHP_SELF"].'?socid='.$societe->id);
    

        print "</td>\n";
		print '<td valign="top" width="50%" class="notopnoleft">';
        // List of todo actions
        show_actions_todo($conf,$langs,$db,$societe);

        // List of done actions
        //show_actions_done($conf,$langs,$db,$societe);
        print "</td>\n";
        print "</tr>\n";
        print "</table>\n";
        
    }
    if ($conf->lead->enabled)
    {
        print '<table width="100%" class="notopnoleftnoright">';
	print '<tr><td valign="top" width="50%" class="notopnoleft">';
        // Leads list
        $result=show_leads($conf,$langs,$db,$societe);

        print "</td>\n";
        print '<td valign="top" width="50%" class="notopnoleft">';
        print "</td>\n";
        print "</tr>\n";
        print "</table>\n";
    }
    
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
