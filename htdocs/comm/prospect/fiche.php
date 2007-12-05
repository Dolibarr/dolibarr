<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
	    \file       htdocs/comm/prospect/fiche.php
        \ingroup    prospect
		\brief      Page de la fiche prospect
		\version    $Revision$
*/

require_once("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT."/prospect.class.php");
require_once(DOL_DOCUMENT_ROOT."/contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/actioncomm.class.php");
require_once(DOL_DOCUMENT_ROOT."/propal.class.php");

$langs->load('companies');
$langs->load('projects');
$langs->load('propal');

$user->getrights('propale');
$user->getrights('fichinter');
$user->getrights('commande');
$user->getrights('projet');
$user->getrights("commercial");

$socid = isset($_GET["id"])?$_GET["id"]:$_GET["socid"];		// Fonctionne si on passe id ou socid
if ($socid == '') accessforbidden();

// Protection quand utilisateur externe
if ($user->societe_id > 0)
{
    $socid = $user->societe_id;
}

// Protection restriction commercial
if (!$user->rights->commercial->client->voir && $socid && !$user->societe_id > 0)
{
        $sql = "SELECT sc.rowid";
        $sql .= " FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc, ".MAIN_DB_PREFIX."societe as s";
        $sql .= " WHERE sc.fk_soc = ".$socid." AND sc.fk_soc = s.rowid AND sc.fk_user = ".$user->id." AND s.client = 2";

        if ( $db->query($sql) )
        {
          if ( $db->num_rows() == 0) accessforbidden();
        }
}



/*
 * Actions
 */
 
if ($_GET["action"] == 'cstc')
{
  $sql = "UPDATE ".MAIN_DB_PREFIX."societe SET fk_stcomm = ".$_GET["stcomm"];
  $sql .= " WHERE rowid = ".$_GET["id"];
  $db->query($sql);
}


/*********************************************************************************
 *
 * Mode fiche
 *
 *********************************************************************************/  

llxHeader();

if ($socid > 0)
{
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

	dolibarr_fiche_head($head, 'prospect', $societe->nom);

    print "<table width=\"100%\">\n";
    print '<tr><td valign="top" width="50%">';

    print '<table class="border" width="100%">';
    print '<tr><td width="25%">'.$langs->trans("Name").'</td><td width="80%" colspan="3">'.$societe->nom.'</td></tr>';
    print '<tr><td valign="top">'.$langs->trans("Address").'</td><td colspan="3">'.nl2br($societe->adresse)."</td></tr>";

    print '<tr><td>'.$langs->trans('Zip').' / '.$langs->trans('Town').'</td><td colspan="3">'.$societe->cp." ".$societe->ville.'</td></tr>';
    print '<tr><td>'.$langs->trans('Country').'</td><td colspan="3">'.$societe->pays.'</td></tr>';

    print '<tr><td>'.$langs->trans("Phone").'</td><td>'.$societe->tel.'&nbsp;</td><td>Fax</td><td>'.$societe->fax.'&nbsp;</td></tr>';
    print '<tr><td>'.$langs->trans("Web")."</td><td colspan=\"3\"><a href=\"http://$societe->url\">$societe->url</a>&nbsp;</td></tr>";

    if ($societe->rubrique)
    {
        print "<tr><td>Rubrique</td><td colspan=\"3\">".$societe->rubrique."</td></tr>";
    }

    print '<tr><td>'.$langs->trans('JuridicalStatus').'</td><td colspan="3">'.$societe->forme_juridique.'</td></tr>';

	// Status
    print '<tr><td>'.$langs->trans("Status").'</td><td colspan="2">'.$societe->getLibStatut(4).'</td>';
    print '<td>';
    if ($societe->stcomm_id != -1) print '<a href="fiche.php?id='.$societe->id.'&amp;stcomm=-1&amp;action=cstc">'.img_action(0,-1).'</a>';
    if ($societe->stcomm_id !=  0) print '<a href="fiche.php?id='.$societe->id.'&amp;stcomm=0&amp;action=cstc">'.img_action(0,0).'</a>';
    if ($societe->stcomm_id !=  1) print '<a href="fiche.php?id='.$societe->id.'&amp;stcomm=1&amp;action=cstc">'.img_action(0,1).'</a>';
    if ($societe->stcomm_id !=  2) print '<a href="fiche.php?id='.$societe->id.'&amp;stcomm=2&amp;action=cstc">'.img_action(0,2).'</a>';
    if ($societe->stcomm_id !=  3) print '<a href="fiche.php?id='.$societe->id.'&amp;stcomm=3&amp;action=cstc">'.img_action(0,3).'</a>';
    print '</td></tr>';
    print '</table>';


    print "</td>\n";
    print '<td valign="top" width="50%">';

    // Nbre max d'éléments des petites listes
    $MAXLIST=5;
    $tableaushown=0;


    $propal_static=new Propal($db);

    /*
     * Dernieres propales
     *
     */
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

    print "</td></tr>";
    print "</table>\n</div>\n";


    /*
    * Barre d'action
    *
    */

    print '<div class="tabsAction">';

    print '<a class="butAction" href="'.DOL_URL_ROOT.'/contact/fiche.php?socid='.$societe->id.'&amp;action=create">'.$langs->trans("AddContact").'</a>';

    print '<a class="butAction" href="'.DOL_URL_ROOT.'/comm/action/fiche.php?action=create&socid='.$socid.'&afaire=1">'.$langs->trans("AddAction").'</a>';


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
		print '<td>&nbsp;</td>';
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
            print '<td><a href="'.DOL_URL_ROOT.'/comm/action/fiche.php?action=create&actioncode=AC_TEL&contactid='.$obj->rowid.'&socid='.$societe->id.'">'.$obj->phone;

	        if (strlen($obj->phone) && $user->clicktodial_enabled == 1)
	        {
	            print '<a href="'.DOL_URL_ROOT.'/comm/action/fiche.php?action=create&actioncode=AC_TEL&contactid='.$obj->rowid.'&amp;socid='.$societe->id.'&amp;call='.$obj->phone.'">';
	            print img_phone_out("Appel émis") ;
	        }
			print '</a></td>';

            print '<td><a href="'.DOL_URL_ROOT.'/comm/action/fiche.php?action=create&actioncode=AC_FAX&contactid='.$obj->rowid.'&socid='.$societe->id.'">'.$obj->fax.'</a>&nbsp;</td>';
            print '<td><a href="'.DOL_URL_ROOT.'/comm/action/fiche.php?action=create&actioncode=AC_EMAIL&contactid='.$obj->rowid.'&socid='.$societe->id.'">'.$obj->email.'</a>&nbsp;</td>';

        	print '<td align="center">';
        	
           	if ($user->rights->societe->contact->creer)
    		{
        		print "<a href=\"".DOL_URL_ROOT."/contact/fiche.php?action=edit&amp;id=".$obj->rowid."\">";
        	 	print img_edit();
        	 	print '</a>';
        	}
        	else print '&nbsp;';
        		
        	print '</td>';

            print '<td align="center"><a href="'.DOL_URL_ROOT.'/comm/action/fiche.php?action=create&actioncode=AC_RDV&contactid='.$obj->rowid.'&socid='.$societe->id.'">';
            print img_object($langs->trans("Rendez-Vous"),"action");
            print '</a></td>';

            print "</tr>\n";
            $i++;
            $tag = !$tag;
        }
        print "</table>";

        print "<br>";

        /*
         *      Listes des actions a faire
         *
         */
        print '<table width="100%" class="noborder">';
        print '<tr class="liste_titre"><td colspan="9"><a href="'.DOL_URL_ROOT.'/comm/action/index.php?socid='.$societe->id.'">'.$langs->trans("ActionsToDo").'</a></td><td align="right">&nbsp;</td></tr>';

        $sql = "SELECT a.id, a.label, ".$db->pdate("a.datep")." as dp, c.code as acode, c.libelle, u.login, a.propalrowid, a.fk_user_author, fk_contact, u.rowid ";
        $sql .= " FROM ".MAIN_DB_PREFIX."actioncomm as a, ".MAIN_DB_PREFIX."c_actioncomm as c, ".MAIN_DB_PREFIX."user as u ";
        $sql .= " WHERE a.fk_soc = ".$societe->id;
        $sql .= " AND u.rowid = a.fk_user_author";
        $sql .= " AND c.id=a.fk_action AND a.percent < 100";
        $sql .= " ORDER BY a.datep DESC, a.id DESC";

        $result=$db->query($sql);
        if ($result)
        {
            $i = 0 ;
            $num = $db->num_rows($result);
            $var=true;
            
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
                print '<td width="30">'.strftime("%H:%M",$obj->dp)."</td>\n";
				if (date("U",$obj->dp) < time())
				{
					print "<td>".img_warning("Late")."</td>";
				}
				else
				{
					print '<td>&nbsp;</td>';
				}

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
                    print '<td><a href="'.DOL_URL_ROOT.'/comm/action/fiche.php?id='.$obj->id.'">'.img_object($langs->trans("ShowAction"),"task");
                      $transcode=$langs->trans("Action".$obj->acode);
                      $libelle=($transcode!="Action".$obj->acode?$transcode:$obj->libelle);
                      print $libelle;
                    print '</a></td>';
                }
                print "<td>$obj->label</td>";

                // Contact pour cette action
                if ($obj->fk_contact) {
                    $contact = new Contact($db);
                    $contact->fetch($obj->fk_contact);
                    print '<td><a href="'.DOL_URL_ROOT.'/contact/fiche.php?id='.$contact->id.'">'.img_object($langs->trans("ShowContact"),"contact").' '.$contact->getFullName($langs).'</a></td>';
                } else {
                    print '<td>&nbsp;</td>';
                }

                print '<td width="80" nowrap="nowrap"><a href="'.DOL_URL_ROOT.'/user/fiche.php?id='.$obj->fk_user_author.'">'.img_object($langs->trans("ShowUser"),"user").' '.$obj->login.'</a></td>';
                print "</tr>\n";
                $i++;
            }

            $db->free($result);
        } else {
            dolibarr_print_error($db);
        }
        print "</table><br>";

        /*
         *      Listes des actions effectuées
         */
        print '<table width="100%" class="noborder">';
        print '<tr class="liste_titre"><td colspan="9"><a href="'.DOL_URL_ROOT.'/comm/action/index.php?socid='.$societe->id.'">'.$langs->trans("ActionsDone").'</a></td></tr>';

        $sql = "SELECT a.id, ".$db->pdate("a.datea")." as da, c.code as acode, c.libelle, a.propalrowid, a.fk_user_author, fk_contact, u.login, u.rowid ";
        $sql .= " FROM ".MAIN_DB_PREFIX."actioncomm as a, ".MAIN_DB_PREFIX."c_actioncomm as c, ".MAIN_DB_PREFIX."user as u ";
        $sql .= " WHERE a.fk_soc = ".$societe->id;
        $sql .= " AND u.rowid = a.fk_user_author";
        $sql .= " AND c.id=a.fk_action AND a.percent = 100";
        $sql .= " ORDER BY a.datea DESC, a.id DESC";

        $result=$db->query($sql);
        if ($result)
        {
            $i = 0 ;
            $num = $db->num_rows($result);
            $var=true;
            
            while ($i < $num)
            {
                $var = !$var;

                $obj = $db->fetch_object($result);
                print "<tr $bc[$var]>";

                if ($oldyear == strftime("%Y",$obj->da) && !$conf->global->COMPANY_VIEW_FULL_DATE_ACTIONS) {
                    print '<td width="30" align="center">|</td>';
                } else {
                    print '<td width="30" align="center">'.strftime("%Y",$obj->da)."</td>\n";
                    $oldyear = strftime("%Y",$obj->da);
                }

                if ($oldmonth == strftime("%Y%b",$obj->da) && !$conf->global->COMPANY_VIEW_FULL_DATE_ACTIONS) {
                    print '<td width="30" align="center">|</td>';
                } else {
                    print '<td width="30" align="center">'.strftime("%b",$obj->da)."</td>\n";
                    $oldmonth = strftime("%Y%b",$obj->da);
                }

                print '<td width="20">'.strftime("%d",$obj->da)."</td>\n";
                print '<td width="30">'.strftime("%H:%M",$obj->da)."</td>\n";

                // Espace
                print '<td>&nbsp;</td>';

				// Action
        		print '<td>';
				print '<a href="'.DOL_URL_ROOT.'/comm/action/fiche.php?id='.$obj->id.'">'.img_object($langs->trans("ShowTask"),"task");
				$transcode=$langs->trans("Action".$obj->acode);
				$libelle=($transcode!="Action".$obj->acode?$transcode:$obj->libelle);
				print $libelle;
				print '</a>';
				print '</td>';

        		print '<td>';
				if ($obj->propalrowid)
				{
					print '<a href="'.DOL_URL_ROOT.'/comm/propal.php?propalid='.$obj->propalrowid.'">'.img_object($langs->trans("ShowPropal"),"propal");
					print $langs->trans("Propal");
					print '</a>';
				}
				else print '&nbsp;';
        		print '</td>';

                // Contact pour cette action
                if ($obj->fk_contact)
                {
                    $contact = new Contact($db);
                    $contact->fetch($obj->fk_contact);
                    print '<td><a href="'.DOL_URL_ROOT.'/contact/fiche.php?id='.$contact->id.'">'.img_object($langs->trans("ShowContact"),"contact").' '.$contact->getFullName($langs).'</a></td>';
                }
                else
                {
                    print '<td>&nbsp;</td>';
                }

                print '<width="80" nowrap="nowrap"><a href="'.DOL_URL_ROOT.'/user/fiche.php?id='.$obj->rowid.'">'.img_object($langs->trans("ShowUser"),'user').' '.$obj->login.'</a></td>';
                print "</tr>\n";
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

$db->close();

llxFooter('$Date$ - $Revision$');
?>
