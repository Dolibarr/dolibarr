<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
	    \file       htdocs/comm/prospect/fiche.php
        \ingroup    prospect
		\brief      Page de la fiche prospect
		\version    $Id$
*/

require_once("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT."/prospect.class.php");
require_once(DOL_DOCUMENT_ROOT."/contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/actioncomm.class.php");
if ($conf->propal->enabled) require_once(DOL_DOCUMENT_ROOT."/propal.class.php");

$langs->load('companies');
$langs->load('projects');
$langs->load('propal');

// Security check
$socid = isset($_GET["socid"])?$_GET["socid"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe',$socid,'');


/*
 * Actions
 */
 
if ($_GET["action"] == 'cstc')
{
  $sql = "UPDATE ".MAIN_DB_PREFIX."societe SET fk_stcomm = ".$_GET["stcomm"];
  $sql .= " WHERE rowid = ".$_GET["socid"];
  $db->query($sql);
}
// set prospect level
if ($_POST["action"] == 'setprospectlevel' && $user->rights->societe->creer)
{
    
	$societe = new Societe($db, $_GET["socid"]);
    $societe->fk_prospectlevel=$_POST['prospect_level_id'];
	$sql = "UPDATE ".MAIN_DB_PREFIX."societe SET fk_prospectlevel='".$_POST['prospect_level_id'];
	$sql.= "' WHERE rowid='".$_GET["socid"]."'";
    $result = $db->query($sql);
    if (! $result) dolibarr_print_error($result);
}


/*********************************************************************************
 *
 * Mode fiche
 *
 *********************************************************************************/  

llxHeader();
$form=new Form($db);

if ($socid > 0)
{
    $actionstatic=new ActionComm($db);
    $societe = new Prospect($db, $socid);
    $result = $societe->fetch($socid);
    if ($result < 0)
    {
        dolibarr_print_error($db);
        exit;
    }

	/*
	 * Affichage onglets
	 */
	$head = societe_prepare_head($societe);

	dolibarr_fiche_head($head, 'prospect', $langs->trans("ThirdParty"));

    print "<table width=\"100%\">\n";
    print '<tr><td valign="top" width="50%">';

    print '<table class="border" width="100%">';
    print '<tr><td width="25%">'.$langs->trans("Name").'</td><td width="80%" colspan="3">'.$societe->nom.'</td></tr>';
    print '<tr><td valign="top">'.$langs->trans("Address").'</td><td colspan="3">'.nl2br($societe->adresse)."</td></tr>";

    print '<tr><td>'.$langs->trans('Zip').' / '.$langs->trans('Town').'</td><td colspan="3">'.$societe->cp." ".$societe->ville.'</td></tr>';

	// Country
	print '<tr><td>'.$langs->trans("Country").'</td><td colspan="3">';
	if ($societe->isInEEC()) print $form->textwithhelp($societe->pays,$langs->trans("CountryIsInEEC"),1,0);
	print '</td></tr>';

    print '<tr><td>'.$langs->trans("Phone").'</td><td>'.dolibarr_print_phone($societe->tel,$societe->pays_code).'</td><td>Fax</td><td>'.dolibarr_print_phone($societe->fax,$societe->pays_code).'</td></tr>';
    print '<tr><td>'.$langs->trans("Web")."</td><td colspan=\"3\"><a href=\"http://$societe->url\">$societe->url</a></td></tr>";

    if ($societe->rubrique)
    {
        print "<tr><td>Rubrique</td><td colspan=\"3\">".$societe->rubrique."</td></tr>";
    }

    print '<tr><td>'.$langs->trans('JuridicalStatus').'</td><td colspan="3">'.$societe->forme_juridique.'</td></tr>';

	// Level
	print '<tr><td nowrap>';
	print '<table width="100%" class="nobordernopadding"><tr><td nowrap>';
	print $langs->trans('ProspectLevelShort');
	print '<td>';
	if (($_GET['action'] != 'editlevel') && $user->rights->societe->creer) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editlevel&amp;socid='.$societe->id.'">'.img_edit($langs->trans('SetLevel'),1).'</a></td>';
	print '</tr></table>';
	print '</td><td colspan="3">';
	if ($_GET['action'] == 'editlevel')
	{
		$form->form_prospect_level($_SERVER['PHP_SELF'].'?socid='.$societe->id,$societe->fk_prospectlevel,'prospect_level_id',1);
	}
	else
	{
		print $societe->getLibLevel();
		//$html->form_prospect_level($_SERVER['PHP_SELF'].'?socid='.$objsoc->id,$objsoc->mode_reglement,'none');
	}
	print "</td>";
	print '</tr>';
	
    // Status
    print '<tr><td>'.$langs->trans("Status").'</td><td colspan="2">'.$societe->getLibStatut(4).'</td>';
    print '<td>';
    if ($societe->stcomm_id != -1) print '<a href="fiche.php?socid='.$societe->id.'&amp;stcomm=-1&amp;action=cstc">'.img_action(0,-1).'</a>';
    if ($societe->stcomm_id !=  0) print '<a href="fiche.php?socid='.$societe->id.'&amp;stcomm=0&amp;action=cstc">'.img_action(0,0).'</a>';
    if ($societe->stcomm_id !=  1) print '<a href="fiche.php?socid='.$societe->id.'&amp;stcomm=1&amp;action=cstc">'.img_action(0,1).'</a>';
    if ($societe->stcomm_id !=  2) print '<a href="fiche.php?socid='.$societe->id.'&amp;stcomm=2&amp;action=cstc">'.img_action(0,2).'</a>';
    if ($societe->stcomm_id !=  3) print '<a href="fiche.php?socid='.$societe->id.'&amp;stcomm=3&amp;action=cstc">'.img_action(0,3).'</a>';
    print '</td></tr>';
    print '</table>';


    print "</td>\n";
    print '<td valign="top" width="50%">';

    // Nbre max d'éléments des petites listes
    $MAXLIST=5;
    $tableaushown=0;


    /*
     * Dernieres propales
     *
     */
    if ($conf->propal->enabled)
	{
		$propal_static=new Propal($db);

	    print '<table class="noborder" width="100%">';
	    $sql = "SELECT s.nom, s.rowid as socid, p.rowid as propalid, p.fk_statut, p.price, p.ref, p.remise, ";
	    $sql.= " ".$db->pdate("p.datep")." as dp, ".$db->pdate("p.fin_validite")." as datelimite,";
	    $sql.= " c.label as statut, c.id as statutid";
	    $sql.= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."propal as p, ".MAIN_DB_PREFIX."c_propalst as c";
	    $sql.= " WHERE p.fk_soc = s.rowid AND p.fk_statut = c.id";
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
	            $tableaushown=1;
	            print '<tr class="liste_titre">';
	            print '<td colspan="4"><table width="100%" class="noborder"><tr><td>'.$langs->trans("LastPropals",($num<=$MAXLIST?"":$MAXLIST)).'</td><td align="right"><a href="'.DOL_URL_ROOT.'/comm/propal.php?socid='.$societe->id.'">'.$langs->trans("AllPropals").' ('.$num.')</a></td></tr></table></td>';
	            print '</tr>';
	        }

	        while ($i < $num && $i < $MAXLIST)
	        {
	            $objp = $db->fetch_object($resql);
	            $var=!$var;
	            print "<tr $bc[$var]>";
	            print "<td><a href=\"../propal.php?propalid=$objp->propalid\">";
	            print img_object($langs->trans("ShowPropal"),"propal");
	            print " $objp->ref</a>\n";
	            if ( ($objp->dp < time() - $conf->propal->cloture->warning_delay) && $objp->fk_statut == 1 )
	            {
	                print " ".img_warning();
	            }
	            print "</td><td align=\"right\">".dolibarr_print_date($objp->dp,"day")."</td>\n";
	            print "<td align=\"right\">".price($objp->price)."</td>\n";
	            print "<td align=\"right\">".$propal_static->LibStatut($objp->fk_statut,5)."</td></tr>\n";
	            $i++;
	        }
	        $db->free();
	    }
	    else
	    {
	    	dolibarr_print_error($db);
	    }

	    print "</table>";
	}
	
    print "</td></tr>";
    print "</table>\n</div>\n";


    /*
    * Barre d'action
    *
    */

    print '<div class="tabsAction">';

    print '<a class="butAction" href="'.DOL_URL_ROOT.'/contact/fiche.php?socid='.$societe->id.'&amp;action=create">'.$langs->trans("AddContact").'</a>';

    if ($conf->agenda->enabled)
    {
    	print '<a class="butAction" href="'.DOL_URL_ROOT.'/comm/action/fiche.php?action=create&socid='.$socid.'&afaire=1">'.$langs->trans("AddAction").'</a>';
    }

    if ($conf->propal->enabled && defined("MAIN_MODULE_PROPALE") && MAIN_MODULE_PROPALE && $user->rights->propale->creer)
    {
        print '<a class="butAction" href="'.DOL_URL_ROOT.'/comm/addpropal.php?socid='.$societe->id.'&amp;action=create">'.$langs->trans("AddProp").'</a>';
    }

    if ($conf->projet->enabled && $user->rights->projet->creer)
    {
        print '<a class="butAction" href="'.DOL_URL_ROOT.'/projet/fiche.php?socid='.$socid.'&action=create">'.$langs->trans("AddProject").'</a>';
    }
    print '</div>';

    print '<br>';



    if ($conf->clicktodial->enabled)
    {
        $user->fetch_clicktodial(); // lecture des infos de clicktodial
    }


        /*
         *
         * Liste des contacts
         *
         */
		print '<table class="noborder" width="100%">';
		
		print '<tr class="liste_titre"><td>'.$langs->trans("Firstname").' '.$langs->trans("Lastname").'</td>';
		print '<td>'.$langs->trans("Poste").'</td><td>'.$langs->trans("Tel").'</td>';
		print '<td>'.$langs->trans("Fax").'</td><td>'.$langs->trans("EMail").'</td>';
		print "<td align=\"center\">&nbsp;</td>";
        if ($conf->agenda->enabled && $user->rights->agenda->myactions->create)
        {
        	print '<td>&nbsp;</td>';
        }
		print "</tr>";

        $sql = "SELECT p.rowid, p.name, p.firstname, p.poste, p.phone, p.fax, p.email, p.note";
        $sql.= " FROM ".MAIN_DB_PREFIX."socpeople as p";
        $sql.= " WHERE p.fk_soc = ".$societe->id;
        $sql.= " ORDER by p.datec";

        $result = $db->query($sql);
        $i = 0 ; $num = $db->num_rows($result);
        $var=1;
        while ($i < $num)
        {
            $obj = $db->fetch_object($result);
            $var = !$var;

            print "<tr $bc[$var]>";

            print '<td>';
            print '<a href="'.DOL_URL_ROOT.'/contact/fiche.php?id='.$obj->rowid.'">'.img_object($langs->trans("ShowContact"),"contact").' '.$obj->firstname.' '. $obj->name.'</a>&nbsp;';

            if (trim($obj->note))
            {
                print '<br>'.nl2br(trim($obj->note));
            }
            print '</td>';
            print '<td>'.$obj->poste.'&nbsp;</td>';
            
            // Phone
			print '<td>';
            if ($conf->agenda->enabled && $user->rights->agenda->myactions->create)
            	print '<a href="'.DOL_URL_ROOT.'/comm/action/fiche.php?action=create&actioncode=AC_TEL&contactid='.$obj->rowid.'&socid='.$societe->id.'">';
            print dolibarr_print_phone($obj->phone,'');
            if ($conf->agenda->enabled && $user->rights->agenda->myactions->create)
	        	print '</a>';
			if ($obj->phone) print ' '.dol_phone_link($obj->phone);
			print '</td>';

        	// Fax
			print '<td>';
            if ($conf->agenda->enabled && $user->rights->agenda->myactions->create)
        		print '<a href="'.DOL_URL_ROOT.'/comm/action/fiche.php?action=create&actioncode=AC_FAX&contactid='.$obj->rowid.'&socid='.$societe->id.'">';
        	print dolibarr_print_phone($obj->fax);
            if ($conf->agenda->enabled && $user->rights->agenda->myactions->create)
        		print '</a>';
        	print '&nbsp;</td>';
            print '<td>';
            if ($conf->agenda->enabled && $user->rights->agenda->myactions->create)
            	print '<a href="'.DOL_URL_ROOT.'/comm/action/fiche.php?action=create&actioncode=AC_EMAIL&contactid='.$obj->rowid.'&socid='.$societe->id.'">';
            print $obj->email;
            if ($conf->agenda->enabled && $user->rights->agenda->myactions->create)
            	print '</a>';
            print '&nbsp;</td>';
    		
        	print '<td align="center">';
        	
           	if ($user->rights->societe->contact->creer)
    		{
        		print "<a href=\"".DOL_URL_ROOT."/contact/fiche.php?action=edit&amp;id=".$obj->rowid."\">";
        	 	print img_edit();
        	 	print '</a>';
        	}
        	else print '&nbsp;';
        		
        	print '</td>';

            if ($conf->agenda->enabled && $user->rights->agenda->myactions->create)
            {
	            print '<td align="center">';
	            print '<a href="'.DOL_URL_ROOT.'/comm/action/fiche.php?action=create&actioncode=AC_RDV&contactid='.$obj->rowid.'&socid='.$societe->id.'">';
	            print img_object($langs->trans("Rendez-Vous"),"action");
	            print '</a></td>';
            }
            
            print "</tr>\n";
            $i++;
            $tag = !$tag;
        }
        print "</table>";

        print "<br>";

	    /*
	     *      Listes des actions a faire
	     */
		show_actions_todo($conf,$langs,$db,$societe);
		
	    /*
	     *      Listes des actions effectuees
	     */
		show_actions_done($conf,$langs,$db,$societe);
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
