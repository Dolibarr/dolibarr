<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
    	\file       htdocs/compta/fiche.php
		\ingroup    compta
		\brief      Page de fiche compta
		\version    $Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/actioncomm.class.php");
require_once(DOL_DOCUMENT_ROOT."/facture.class.php");

$langs->load("companies");
if ($conf->facture->enabled) $langs->load("bills");
if ($conf->projet->enabled) $langs->load("projects");

// Sécurité accés client
$socid = $_GET["socid"];
if ($user->societe_id > 0)
{
    $action = '';
    $socid = $user->societe_id;
}

$user->getrights('facture');



if ($action == 'recontact')
{
    $dr = mktime(0, 0, 0, $remonth, $reday, $reyear);
    $sql = "INSERT INTO ".MAIN_DB_PREFIX."soc_recontact (fk_soc, datere, author) VALUES ($socid, $dr,'". $user->login ."')";
    $result = $db->query($sql);
}

/* TODO RODO
if ($action == 'stcomm')
{
  if ($stcommid <> 'null' && $stcommid <> $oldstcomm)
    {
      $sql = "INSERT INTO socstatutlog (datel, fk_soc, fk_statut, author) ";
      $sql .= " VALUES ('$dateaction',$socid,$stcommid,'" . $user->login . "')";
      $result = @$db->query($sql);

      if ($result)
        {
	  $sql = "UPDATE ".MAIN_DB_PREFIX."societe SET fk_stcomm=$stcommid WHERE idp=$socid";
	  $result = $db->query($sql);
        }
      else
        {
	  $errmesg = "ERREUR DE DATE !";
        }
    }

  if ($actioncommid)
    {
      $sql = "INSERT INTO ".MAIN_DB_PREFIX."actioncomm (datea, fk_action, fk_soc, fk_user_author) VALUES ('$dateaction',$actioncommid,$socid,'" . $user->id . "')";
      $result = @$db->query($sql);

      if (!$result)
        {
	  $errmesg = "ERREUR DE DATE !";
        }
    }
}
*/

/*
 * Recherche
 *
 */
if ($mode == 'search')
{
    if ($mode-search == 'soc')
    {
        $sql = "SELECT s.idp FROM ".MAIN_DB_PREFIX."societe as s ";
        $sql .= " WHERE lower(s.nom) like '%".strtolower($socname)."%'";
    }

    if ( $db->query($sql) )
    {
        if ( $db->num_rows() == 1)
        {
            $obj = $db->fetch_object();
            $socid = $obj->idp;
        }
        $db->free();
    }

    if ($user->societe_id > 0)
    {
        $socid = $user->societe_id;
    }

}


llxHeader();

/*
 * Mode fiche
 */
if ($socid > 0)
{
    $societe = new Societe($db);
    $societe->fetch($socid, $to);  // si $to='next' ajouter " AND s.idp > $socid ORDER BY idp ASC LIMIT 1";

    /*
     * Affichage onglets
     */
    $h = 0;

    $head[$h][0] = DOL_URL_ROOT.'/soc.php?socid='.$societe->id;
    $head[$h][1] = $langs->trans("Company");
    $h++;

    if ($societe->client==1)
    {
        $head[$h][0] = DOL_URL_ROOT.'/comm/fiche.php?socid='.$societe->id;
        $head[$h][1] = $langs->trans("Customer");
        $h++;
    }
    if ($societe->client==2)
    {
        $head[$h][0] = DOL_URL_ROOT.'/comm/prospect/fiche.php?id='.$societe->id;
        $head[$h][1] = $langs->trans("Prospect");
        $h++;
    }
    if ($societe->fournisseur)
    {
        $head[$h][0] = DOL_URL_ROOT.'/fourn/fiche.php?socid='.$societe->id;
        $head[$h][1] = $langs->trans("Supplier");
        $h++;
    }

    if ($conf->compta->enabled) {
        $langs->load("compta");
        $hselected=$h;
        $head[$h][0] = DOL_URL_ROOT.'/compta/fiche.php?socid='.$societe->id;
        $head[$h][1] = $langs->trans("Accountancy");
        $h++;
    }

    $head[$h][0] = DOL_URL_ROOT.'/socnote.php?socid='.$societe->id;
    $head[$h][1] = $langs->trans("Note");
    $h++;

    if ($user->societe_id == 0)
    {
        $head[$h][0] = DOL_URL_ROOT.'/docsoc.php?socid='.$societe->id;
        $head[$h][1] = $langs->trans("Documents");
        $h++;
    }

    $head[$h][0] = DOL_URL_ROOT.'/societe/notify/fiche.php?socid='.$societe->id;
    $head[$h][1] = $langs->trans("Notifications");
    $h++;

    $head[$h][0] = DOL_URL_ROOT.'/societe/info.php?socid='.$societe->id;
    $head[$h][1] = $langs->trans("Info");
    $h++;

    if ($user->societe_id == 0)
    {
    	$head[$h][0] = DOL_URL_ROOT."/index.php?socidp=$societe->id&action=add_bookmark";
    	$head[$h][1] = img_object($langs->trans("BookmarkThisPage"),'bookmark');
    	$head[$h][2] = 'image';
    }

    dolibarr_fiche_head($head, $hselected, $societe->nom);

    /*
     *
     */
    print "<table width=\"100%\">\n";
    print '<tr><td valign="top" width="50%">';

    print '<table class="border" width="100%">';
    
    print '<tr><td>'.$langs->trans("Name").'</td><td colspan="3">'.$societe->nom.'</td></tr>';
    
    print '<td>'.$langs->trans("Prefix").'</td><td colspan="3">';
    print ($societe->prefix_comm?$societe->prefix_comm:'&nbsp;');
    print '</td>';
    
    if ($societe->client)
    {
        print '<tr>';
        print '<td nowrap width="100">'.$langs->trans("CustomerCode"). '</td><td colspan="3">'. $societe->code_client . '</td>';
        print '</tr>';
        print '<tr>';
        print '<td nowrap>'.$langs->trans("CustomerAccountancyCode").'</td><td colspan="3">'.$societe->code_compta.'</td>';
        print '</tr>';
    }
    
    if ($societe->fournisseur)
    {
        print '<tr>';
        print '<td nowrap>'.$langs->trans("SupplierCode"). '</td><td colspan="3">'. $societe->code_fournisseur . '</td>';
        print '</tr>';
        print '<tr>';
        print '<td nowrap>'.$langs->trans("SupplierAccountancyCode").'</td><td colspan="3">'.$societe->code_compta_fournisseur.'</td>';
        print '</tr>';
    }
    
    print '<tr><td valign="top">'.$langs->trans("Address").'</td><td colspan="3">'.nl2br($societe->adresse)."</td></tr>";

    print '<tr><td>'.$langs->trans('Zip').'</td><td>'.$societe->cp.'</td>';
    print '<td>'.$langs->trans('Town').'</td><td>'.$societe->ville.'</td></tr>';
    
    print '<tr><td>'.$langs->trans('Country').'</td><td colspan="3">'.$societe->pays.'</td></tr>';

    print '<tr><td>'.$langs->trans("Phone").'</td><td>'.dolibarr_print_phone($societe->tel,$societe->pays_code).'&nbsp;</td><td>'.$langs->trans("Fax").'</td><td>'.dolibarr_print_phone($societe->fax,$societe->pays_code).'&nbsp;</td></tr>';
    print '<tr><td>'.$langs->trans("Web")."</td><td colspan=\"3\"><a href=\"http://$societe->url\" target=\"_blank\">$societe->url</a>&nbsp;</td></tr>";

    // TVA
    print '<tr><td nowrap>'.$langs->trans('VATIntraVeryShort').'</td><td colspan="3">';
    print $societe->tva_intra;
    print '</td></tr>';

    print '<tr><td>'.$langs->trans('Capital').'</td><td colspan="3">'.$societe->capital.' '.$langs->trans("Currency".$conf->monnaie).'</td></tr>';

    // Type + Staff
    $arr = $societe->typent_array($societe->typent_id);
    $societe->typent= $arr[$societe->typent_id];
    print '<tr><td>'.$langs->trans("Type").'</td><td>'.$societe->typent.'</td><td>'.$langs->trans("Staff").'</td><td>'.$societe->effectif.'</td></tr>';

    if ($societe->client == 1)
    {
        // Remise permanente
        print '<tr><td nowrap>';
        print '<table width="100%" class="nobordernopadding"><tr><td nowrap>';
        print $langs->trans("CustomerRelativeDiscount");
        print '<td><td align="right">';
        print '<a href="'.DOL_URL_ROOT.'/comm/remise.php?id='.$societe->id.'">'.img_edit($langs->trans("Modify")).'</a>';
        print '</td></tr></table>';
        print '</td><td colspan="3">'.$societe->remise_client."&nbsp;%</td>";
        print '</tr>';
        
        // Remise avoirs
        print '<tr><td nowrap>';
        print '<table width="100%" class="nobordernopadding">';
        print '<tr><td nowrap>';
        print $langs->trans("CustomerAbsoluteDiscount");
        print '<td><td align="right">';
        print '<a href="'.DOL_URL_ROOT.'/comm/remx.php?id='.$societe->id.'">'.img_edit($langs->trans("Modify")).'</a>';
        print '</td></tr></table>';
        print '</td>';
        print '<td colspan="3">';
        $sql  = "SELECT rc.amount_ht,".$db->pdate("rc.datec")." as dc";
        $sql .= " FROM ".MAIN_DB_PREFIX."societe_remise_except as rc";
        $sql .= " WHERE rc.fk_soc =". $societe->id;
        $sql .= " AND rc.fk_user = ".$user->id." AND fk_facture IS NULL";
        $resql=$db->query($sql);
        if ($resql)
        {
            $obj = $db->fetch_object($resql);
            if ($obj->amount_ht) print $obj->amount_ht.'&nbsp;'.$langs->trans("Currency".$conf->monnaie);
            else print $langs->trans("None");
        }
        print '</td>';
        print '</tr>';
    }
    
    print "</table>";

    print "</td>\n";


    print '<td valign="top" width="50%">';

    // Nbre max d'éléments des petites listes
    $MAXLIST=5;
    $tableaushown=0;

    /**
     *   Dernieres factures
     */
    if ($conf->facture->enabled && $user->rights->facture->lire)
    {
        print '<table class="noborder" width="100%">';

        $sql = "SELECT s.nom, s.idp, f.facnumber, f.amount, f.total, f.total_ttc, ".$db->pdate("f.datef")." as df, f.paye as paye, f.fk_statut as statut, f.rowid as facid ";
        $sql .= " FROM ".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."facture as f";
        $sql .= " WHERE f.fk_soc = s.idp AND s.idp = ".$societe->id;
        $sql .= " ORDER BY f.datef DESC";

        $resql=$db->query($sql);
        if ($resql)
        {
            $var=true;
            $num = $db->num_rows($resql);
            $i = 0;
            if ($num > 0)
            {
                $tableaushown=1;
                print '<tr class="liste_titre">';
                print '<td colspan="4"><table width="100%" class="noborder"><tr><td>'.$langs->trans("LastBills",($num<=$MAXLIST?"":$MAXLIST)).'</td><td align="right"><a href="'.DOL_URL_ROOT.'/compta/facture.php?socidp='.$societe->id.'">'.$langs->trans("AllBills").' ('.$num.')</td></tr></table></td>';
                print '</tr>';
            }

            while ($i < $num && $i < $MAXLIST)
            {
                $objp = $db->fetch_object($resql);
                $var=!$var;
                print "<tr $bc[$var]>";
                print "<td><a href=\"../compta/facture.php?facid=$objp->facid\">".img_object($langs->trans("ShowBill"),"bill")." ".$objp->facnumber."</a></td>\n";
                if ($objp->df > 0 )
                {
                    print "<td align=\"right\">".dolibarr_print_date($objp->df)."</td>\n";
                }
                else
                {
                    print "<td align=\"right\"><b>!!!</b></td>\n";
                }
                print "<td align=\"right\">".price($objp->total_ttc)."</td>\n";

                $fac = new Facture($db);
                print "<td align=\"center\">".($fac->LibStatut($objp->paye,$objp->statut,1))."</td>\n";
                print "</tr>\n";
                $i++;
            }
            $db->free($resql);
        }
        else
        {
            dolibarr_print_error($db);
        }
        print "</table>";
    }

    /*
     * Derniers projets associés
     */
    if ($conf->projet->enabled)
    {
        print '<table class="noborder" width="100%">';

        $sql  = "SELECT p.rowid,p.title,p.ref,".$db->pdate("p.dateo")." as do";
        $sql .= " FROM ".MAIN_DB_PREFIX."projet as p";
        $sql .= " WHERE p.fk_soc = $societe->id";
        $sql .= " ORDER by p.dateo";

        if ( $db->query($sql) )
        {
            $var=true;
            $i = 0 ;
            $num = $db->num_rows();
            if ($num > 0)
            {
                $tableaushown=1;
                print '<tr class="liste_titre">';
                print '<td colspan="2"><table width="100%" class="noborder"><tr><td>'.$langs->trans("LastProjects",($num<=$MAXLIST?"":$MAXLIST)).'</td><td align="right"><a href="'.DOL_URL_ROOT.'/projet/index.php?socidp='.$societe->id.'">'.$langs->trans("AllProjects").' ('.$num.')</td></tr></table></td>';
                print '</tr>';
            }
            while ($i < $num && $i < $MAXLIST)
            {
                $obj = $db->fetch_object();
                $var = !$var;
                print "<tr $bc[$var]>";
                print '<td><a href="../projet/fiche.php?id='.$obj->rowid.'">'.img_object($langs->trans("ShowProject"),"project")." ".$obj->title.'</a></td>';

                print "<td align=\"right\">".strftime("%d %b %Y", $obj->do) ."</td></tr>";
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

    // Lien recap
    if ($tableaushown) print '<br>';
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre">';
    print '<td colspan="4"><table width="100%" class="noborder"><tr><td>'.$langs->trans("Summary").'</td><td align="right"><a href="'.DOL_URL_ROOT.'/compta/recap-client.php?socid='.$societe->id.'">'.$langs->trans("ShowLog").'</td></tr></table></td>';
    print '</tr>';
    print '</table>';

    print "</td></tr>";
    print "</table></div>\n";


    /*
     * Barre d'actions
     *
     */
    print '<div class="tabsAction">';

    if ($user->societe_id == 0)
      {
    	// Si société cliente ou prospect, on affiche bouton "Créer facture client"
    	if ($societe->client != 0 && $conf->facture->enabled && $user->rights->facture->creer) {
            $langs->load("bills");
	        print "<a class=\"tabAction\" href=\"facture.php?action=create&socidp=$societe->id\">".$langs->trans("AddBill")."</a>";
        }

        if ($conf->deplacement->enabled) {
            $langs->load("trips");
            print "<a class=\"tabAction\" href=\"deplacement/fiche.php?socid=$societe->id&amp;action=create\">".$langs->trans("AddTrip")."</a>";
        }
      }

    print "<a class=\"tabAction\" href=\"".DOL_URL_ROOT.'/contact/fiche.php?socid='.$socid."&amp;action=create\">".$langs->trans("AddContact")."</a>";

    print '</div>';
    print "<br>\n";

    /*
     *
     *
     */
    if ($action == 'changevalue')
    {

        print "<hr noshade>";
        print "<form action=\"index.php?socid=$societe->id\" method=\"post\">";
        print "<input type=\"hidden\" name=\"action\" value=\"cabrecrut\">";
        print "Cette société est un cabinet de recrutement : ";
        print "<select name=\"selectvalue\">";
        print "<option value=\"\">";
        print "<option value=\"t\">Oui";
        print "<option value=\"f\">Non";
        print "</select>";
        print "<input type=\"submit\" class=\"button\" value=\"".$langs->trans("Update")."\">";
        print "</form>\n";

    }
    else
    {
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

        $sql = "SELECT p.idp, p.name, p.firstname, p.poste, p.phone, p.fax, p.email, p.note";
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
            print '<a href="'.DOL_URL_ROOT.'/contact/fiche.php?id='.$obj->idp.'">'.img_object($langs->trans("ShowContact"),"contact").' '.$obj->firstname.' '. $obj->name.'</a>&nbsp;';

            if (trim($obj->note))
            {
                print '<br>'.nl2br(trim($obj->note));
            }
            print '</td>';
            print '<td>'.$obj->poste.'&nbsp;</td>';
            print '<td><a href="../comm/action/fiche.php?action=create&actionid=1&contactid='.$obj->idp.'&socid='.$societe->id.'">'.$obj->phone.'</a>&nbsp;</td>';
            print '<td><a href="../comm/action/fiche.php?action=create&actionid=2&contactid='.$obj->idp.'&socid='.$societe->id.'">'.$obj->fax.'</a>&nbsp;</td>';
            print '<td><a href="../comm/action/fiche.php?action=create&actionid=4&contactid='.$obj->idp.'&socid='.$societe->id.'">'.$obj->email.'</a>&nbsp;</td>';

        	print '<td align="center">';
        	print "<a href=\"../contact/fiche.php?action=edit&amp;id=$obj->idp\">";
        	print img_edit();
        	print '</a></td>';

            print '<td align="center"><a href="../comm/action/fiche.php?action=create&actionid=5&contactid='.$obj->idp.'&socid='.$societe->id.'">';
            print img_object($langs->trans("Rendez-Vous"),"action");
            print '</a></td>';

            print "</tr>\n";
            $i++;
            $tag = !$tag;
        }
        print "</table><br>";

        /*
         *      Listes des actions effectuées
         */
        print '<table width="100%" class="noborder">';
        print '<tr class="liste_titre"><td colspan="8"><a href="action/index.php?socid='.$societe->id.'">'.$langs->trans("ActionsDone").'</a></td></tr>';

        $sql = "SELECT a.id, ".$db->pdate("a.datea")." as da, c.code as acode, c.libelle, a.propalrowid, a.fk_user_author, fk_contact, u.code, u.rowid ";
        $sql .= " FROM ".MAIN_DB_PREFIX."actioncomm as a, ".MAIN_DB_PREFIX."c_actioncomm as c, ".MAIN_DB_PREFIX."user as u ";
        $sql .= " WHERE a.fk_soc = $societe->id ";
        $sql .= " AND u.rowid = a.fk_user_author";
        $sql .= " AND c.id=a.fk_action ";
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

                if ($oldyear == strftime("%Y",$obj->da) ) {
                    print '<td width="30" align="center">|</td>';
                } else {
                    print '<td width="30" align="center">'.strftime("%Y",$obj->da)."</td>\n";
                    $oldyear = strftime("%Y",$obj->da);
                }

                if ($oldmonth == strftime("%Y%b",$obj->da) ) {
                    print '<td width="30" align="center">|</td>';
                } else {
                    print '<td width="30" align="center">'.strftime("%b",$obj->da)."</td>\n";
                    $oldmonth = strftime("%Y%b",$obj->da);
                }

                print '<td width="20">'.strftime("%d",$obj->da)."</td>\n";
                print '<td width="30">'.strftime("%H:%M",$obj->da)."</td>\n";

                print '<td>&nbsp;</td>';

        		print '<td>';
        		if ($obj->propalrowid)
        		  {
        		    print '<a href="'.DOL_URL_ROOT.'/comm/propal.php?propalid='.$obj->propalrowid.'">'.img_object($langs->trans("ShowTask"),"task");
                  $transcode=$langs->trans("Action".$obj->acode);
                  $libelle=($transcode!="Action".$obj->acode?$transcode:$obj->libelle);
                  print $libelle;
        		    print '</a></td>';
        		  }
        		else
        		  {
        		    print '<a href="'.DOL_URL_ROOT.'/comm/action/fiche.php?id='.$obj->id.'">'.img_object($langs->trans("ShowTask"),"task");
                  $transcode=$langs->trans("Action".$obj->acode);
                  $libelle=($transcode!="Action".$obj->acode?$transcode:$obj->libelle);
                  print $libelle;
        		    print '</a></td>';
        		  }

                // Contact pour cette action
                if ($obj->fk_contact) {
                    $contact = new Contact($db);
                    $contact->fetch($obj->fk_contact);
                    print '<td><a href="'.DOL_URL_ROOT.'/contact/fiche.php?id='.$contact->id.'">'.img_object($langs->trans("ShowContact"),"contact").' '.$contact->fullname.'</a></td>';
                } else {
                    print '<td>&nbsp;</td>';
                }

                print '<td><a href="'.DOL_URL_ROOT.'/user/fiche.php?id='.$obj->rowid.'">'.$obj->code.'</a></td>';
                print "</tr>\n";
                $i++;
            }

            $db->free();
        } else {
            dolibarr_print_error($db);
        }
        print "</table>";

    }

} else {
    print "Erreur";
}
$db->close();


llxFooter('$Date$ - $Revision$');

?>
