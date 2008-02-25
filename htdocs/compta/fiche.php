<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
    	\file       htdocs/compta/fiche.php
		\ingroup    compta
		\brief      Page de fiche compta
		\version    $Id$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT."/contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/facture.class.php");

$langs->load("companies");
if ($conf->facture->enabled) $langs->load("bills");
if ($conf->projet->enabled)  $langs->load("projects");

// Security check
$socid = isset($_GET["socid"])?$_GET["socid"]:'';
$result = restrictedArea($user, 'societe',$socid,'',1);


/*
 * Recherche
 *
 */
if ($mode == 'search')
{
    if ($mode-search == 'soc')
    {
        $sql = "SELECT s.rowid FROM ".MAIN_DB_PREFIX."societe as s ";
        $sql .= " WHERE lower(s.nom) like '%".strtolower($socname)."%'";
    }

    if ( $db->query($sql) )
    {
        if ( $db->num_rows() == 1)
        {
            $obj = $db->fetch_object();
            $socid = $obj->rowid;
        }
        $db->free();
    }

    if ($user->societe_id > 0)
    {
        $socid = $user->societe_id;
    }

}



/*
 * Mode fiche
 */

llxHeader();

$facturestatic=new Facture($db);
$contactstatic = new Contact($db);

if ($socid > 0)
{
    $societe = new Societe($db);
    $societe->fetch($socid, $to);  // si $to='next' ajouter " AND s.rowid > $socid ORDER BY idp ASC LIMIT 1";
	if ($societe->id <= 0)
	{
		dolibarr_print_error($db,$societe->error);
	}
	
	/*
	 * Affichage onglets
	 */
	$head = societe_prepare_head($societe);

	dolibarr_fiche_head($head, 'compta', $langs->trans("ThirdParty"));


    print '<table width="100%" class="notopnoleftnoright">';
    print '<tr><td valign="top" width="50%" class="notopnoleft">';

    print '<table class="border" width="100%">';
    
    print '<tr><td width="100">'.$langs->trans("Name").'</td><td colspan="3">'.$societe->nom.'</td></tr>';
    
    // Prefix
    print '<tr><td>'.$langs->trans("Prefix").'</td><td colspan="3">';
    print ($societe->prefix_comm?$societe->prefix_comm:'&nbsp;');
    print '</td>';
    
    if ($societe->client)
    {
        print '<tr>';
        print '<td nowrap>'.$langs->trans("CustomerCode"). '</td><td colspan="3">'. $societe->code_client . '</td>';
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

	// Assujeti � TVA ou pas
	print '<tr>';
	print '<td nowrap="nowrap">'.$langs->trans('VATIsUsed').'</td><td colspan="3">';
	print yn($societe->tva_assuj);
	print '</td>';
	print '</tr>';

    // TVA Intra
    print '<tr><td nowrap>'.$langs->trans('VATIntraVeryShort').'</td><td colspan="3">';
    print $societe->tva_intra;
    print '</td></tr>';

    if ($societe->client == 1)
    {
        // Remise permanente
        print '<tr><td nowrap>';
        print '<table width="100%" class="nobordernopadding"><tr><td nowrap>';
        print $langs->trans("CustomerRelativeDiscountShort");
        print '<td><td align="right">';
        if (!$user->societe_id > 0)
        {
        	print '<a href="'.DOL_URL_ROOT.'/comm/remise.php?id='.$societe->id.'">'.img_edit($langs->trans("Modify")).'</a>';
        }
        print '</td></tr></table>';
        print '</td><td colspan="3">'.($societe->remise_client?price2num($societe->remise_client,'MT').'%':$langs->trans("DiscountNone")).'</td>';
        print '</tr>';
        
        // R�ductions (Remises-Ristournes-Rabbais)
        print '<tr><td nowrap>';
        print '<table width="100%" class="nobordernopadding">';
        print '<tr><td nowrap>';
        print $langs->trans("CustomerAbsoluteDiscountShort");
        print '<td><td align="right">';
        if (!$user->societe_id > 0)
        {
        	print '<a href="'.DOL_URL_ROOT.'/comm/remx.php?id='.$societe->id.'">'.img_edit($langs->trans("Modify")).'</a>';
        }
        print '</td></tr></table>';
        print '</td>';
        print '<td colspan="3">';
		$amount_discount=$societe->getAvailableDiscounts();
		if ($amount_discount < 0) dolibarr_print_error($db,$societe->error);
        if ($amount_discount > 0) print price($amount_discount).'&nbsp;'.$langs->trans("Currency".$conf->monnaie);
        else print $langs->trans("DiscountNone");
        print '</td>';
        print '</tr>';
    }
    
    print "</table>";

    print "</td>\n";


    print '<td valign="top" width="50%" class="notopnoleftnoright">';

    // Nbre max d'�l�ments des petites listes
    $MAXLIST=5;
    $tableaushown=1;

    // Lien recap
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre">';
    print '<td colspan="4"><table width="100%" class="noborder"><tr><td>'.$langs->trans("Summary").'</td>';
    print '<td align="right"><a href="'.DOL_URL_ROOT.'/compta/recap-compta.php?socid='.$societe->id.'">'.$langs->trans("ShowAccountancyPreview").'</a></td></tr></table></td>';
    print '</tr>';
    print '</table>';
    print '<br>';

    /**
     *   Dernieres factures
     */
    if ($conf->facture->enabled && $user->rights->facture->lire)
    {
        $facturestatic = new Facture($db);

        print '<table class="noborder" width="100%">';

        $sql = 'SELECT f.rowid as facid, f.facnumber, f.type, f.amount, f.total, f.total_ttc';
        $sql.= ', '.$db->pdate("f.datef").' as df, f.paye as paye, f.fk_statut as statut';
		    $sql.= ', s.nom, s.rowid as socid';
		    $sql.= ', sum(pf.amount) as am';
        $sql.= " FROM ".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."facture as f";
		    $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'paiement_facture as pf ON f.rowid=pf.fk_facture';
        $sql.= " WHERE f.fk_soc = s.rowid AND s.rowid = ".$societe->id;
		    $sql.= ' GROUP BY f.rowid';
        $sql.= " ORDER BY f.datef DESC, f.datec DESC";

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
                print '<td colspan="4"><table width="100%" class="noborder"><tr><td>'.$langs->trans("LastCustomersBills",($num<=$MAXLIST?"":$MAXLIST)).'</td><td align="right"><a href="'.DOL_URL_ROOT.'/compta/facture.php?socid='.$societe->id.'">'.$langs->trans("AllBills").' ('.$num.')</a></td></tr></table></td>';
                print '</tr>';
            }

            while ($i < $num && $i < $MAXLIST)
            {
                $objp = $db->fetch_object($resql);
                $var=!$var;
                print "<tr $bc[$var]>";
                print '<td>';
                $facturestatic->id=$objp->facid;
                $facturestatic->ref=$objp->facnumber;
                $facturestatic->type=$objp->type;
                print $facturestatic->getNomUrl(1);
                print '</td>';
                if ($objp->df > 0)
                {
                    print "<td align=\"right\">".dolibarr_print_date($objp->df)."</td>\n";
                }
                else
                {
                    print "<td align=\"right\"><b>!!!</b></td>\n";
                }
                print "<td align=\"right\">".price($objp->total_ttc)."</td>\n";

                print '<td align="right" nowrap="nowrap">'.($facturestatic->LibStatut($objp->paye,$objp->statut,5,$objp->am))."</td>\n";
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
     * Derniers projets associes
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
                print '<td colspan="2"><table width="100%" class="noborder"><tr><td>'.$langs->trans("LastProjects",($num<=$MAXLIST?"":$MAXLIST)).'</td><td align="right"><a href="'.DOL_URL_ROOT.'/projet/index.php?socid='.$societe->id.'">'.$langs->trans("AllProjects").' ('.$num.')</td></tr></table></td>';
                print '</tr>';
            }
            while ($i < $num && $i < $MAXLIST)
            {
                $obj = $db->fetch_object();
                $var = !$var;
                print "<tr $bc[$var]>";
                print '<td><a href="../projet/fiche.php?id='.$obj->rowid.'">'.img_object($langs->trans("ShowProject"),"project")." ".$obj->title.'</a></td>';

                print "<td align=\"right\">".dolibarr_print_date($obj->do,"day") ."</td></tr>";
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
    print "</table></div>\n";


    /*
     * Barre d'actions
     *
     */
    print '<div class="tabsAction">';

	if ($user->societe_id == 0)
	{
		// Si soci�t� cliente ou prospect, on affiche bouton "Cr�er facture client"
		if ($societe->client != 0 && $conf->facture->enabled && $user->rights->facture->creer) {
			$langs->load("bills");
			print "<a class=\"butAction\" href=\"".DOL_URL_ROOT."/compta/facture.php?action=create&socid=$societe->id\">".$langs->trans("AddBill")."</a>";
		}
	
		if ($conf->deplacement->enabled) {
			$langs->load("trips");
			print "<a class=\"butAction\" href=\"".DOL_URL_ROOT."/compta/deplacement/fiche.php?socid=$societe->id&amp;action=create\">".$langs->trans("AddTrip")."</a>";
		}
	}
	
    if ($conf->agenda->enabled && $user->rights->agenda->myactions->create)
    {
		print '<a class="butAction" href="'.DOL_URL_ROOT.'/comm/action/fiche.php?action=create&socid='.$socid.'">'.$langs->trans("AddAction").'</a>';
    }
    
	if ($user->rights->societe->contact->creer)
	{
		print "<a class=\"butAction\" href=\"".DOL_URL_ROOT.'/contact/fiche.php?socid='.$socid."&amp;action=create\">".$langs->trans("AddContact")."</a>";
	}

    print '</div>';
    print "<br>\n";

    /*
     *
     * Liste des contacts
     *
     */
	print_titre($langs->trans("ContactsForCompany"));
	print '<table class="noborder" width="100%">';
	
	print '<tr class="liste_titre"><td>'.$langs->trans("Name").'</td>';
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
        print '<td>';
        if ($conf->agenda->enabled && $user->rights->agenda->myactions->create)
        	print '<a href="../comm/action/fiche.php?action=create&actioncode=AC_TEL&contactid='.$obj->rowid.'&socid='.$societe->id.'">';
        print $obj->phone;
        if ($conf->agenda->enabled && $user->rights->agenda->myactions->create)
        	print '</a>';
		if ($obj->phone) print ' '.dol_phone_link($obj->phone);
        print '</td>';
	    print '<td>';
        if ($conf->agenda->enabled && $user->rights->agenda->myactions->create)
	    	print '<a href="../comm/action/fiche.php?action=create&actioncode=AC_FAX&contactid='.$obj->rowid.'&socid='.$societe->id.'">';
	    print $obj->fax;
        if ($conf->agenda->enabled && $user->rights->agenda->myactions->create)
	    	print '</a>';
	    print '</td>';
	    print '<td>';
        if ($conf->agenda->enabled && $user->rights->agenda->myactions->create)
	    	print '<a href="../comm/action/fiche.php?action=create&actioncode=AC_EMAIL&contactid='.$obj->rowid.'&socid='.$societe->id.'">';
	    print $obj->email;
        if ($conf->agenda->enabled && $user->rights->agenda->myactions->create)
	    	print '</a>';
	    print '&nbsp;</td>';
       	print '<td align="center">';
    	
       	if ($user->rights->societe->contact->creer)
		{
    		print "<a href=\"../contact/fiche.php?action=edit&amp;id=".$obj->rowid."\">";
    	 	print img_edit();
    	 	print '</a>';
    	}
    	else print '&nbsp;';
    		
    	print '</td>';

    	if ($conf->agenda->enabled && $user->rights->agenda->myactions->create)
    	{
        	print '<td align="center"><a href="../comm/action/fiche.php?action=create&actioncode=AC_RDV&contactid='.$obj->rowid.'&socid='.$societe->id.'">';
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
else
{
    dolibarr_print_error($db,'Bad value for socid parameter');
}
$db->close();


llxFooter('$Date$ - $Revision$');
?>
