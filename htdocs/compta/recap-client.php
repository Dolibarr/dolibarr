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
 *
 */

/**
    	\file       htdocs/compta/recap-client.php
		\ingroup    compta
		\brief      Page de fiche compta
		\version    $Revision$
*/

require("./pre.inc.php");
require("../facture.class.php");

$langs->load("companies");
if ($conf->facture->enabled) $langs->load("bills");

/*
 * Sécurité accés client
 */
$socid = $_GET["socid"];
if ($user->societe_id > 0)
{
  $action = '';
  $socid = $user->societe_id;
}

$user->getrights('facture');

llxHeader();


/*
 *
 * Mode fiche
 *
 */
if ($socid > 0)
{
    $societe = new Societe($db);
    $societe->fetch($socid, $to);  // si $to='next' ajouter " AND s.idp > $socid ORDER BY idp ASC LIMIT 1";

    /*
     * Affichage onglets
     */
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

    if ($user->societe_id == 0)
    {
    	$head[$h][0] = DOL_URL_ROOT."/index.php?socidp=$societe->id&action=add_bookmark";
    	$head[$h][1] = '<img border="0" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/bookmark.png" alt="Bookmark" title="Bookmark">';
    	$head[$h][2] = 'image';
    }

    dolibarr_fiche_head($head, $hselected, $societe->nom);

    /*
     *
     */
    print "<table width=\"100%\">\n";
    print '<tr><td valign="top" width="50%">'; 

    print '<table class="border" width="100%">';

    print '<tr><td width="20%">'.$langs->trans("Name").'</td><td width="80%" colspan="3">'.$societe->nom.'</td></tr>';

    print '<td>'.$langs->trans("Prefix").'</td><td colspan="3">';
    print ($societe->prefix_comm?$societe->prefix_comm:'&nbsp;');
    print '</td>';
    
    if ($societe->client) {
    print '<tr>';
    print '<td nowrap width="100">'.$langs->trans("CustomerCode"). '</td><td colspan="3">'. $societe->code_client . '</td>';
    print '</tr>';
    print '<tr>';
    print '<td nowrap>'.$langs->trans("CustomerAccountancyCode").'</td><td colspan="3">'.$societe->code_compta.'</td>';
    print '</tr>';
    }
    
    if ($societe->fournisseur) {
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
    
    print '<tr><td>'.$langs->trans('Country').'</td><td colspan="3">'.$soc->pays.'</td>';

    print '<tr><td>'.$langs->trans("Phone").'</td><td>'.dolibarr_print_phone($societe->tel,$societe->pays_code).'&nbsp;</td><td>'.$langs->trans("Fax").'</td><td>'.dolibarr_print_phone($societe->fax,$societe->pays_code).'&nbsp;</td></tr>';

    print "<tr><td nowrap>".$langs->transcountry("ProfId1",$societe->pays_code)."</td><td><a href=\"http://www.societe.com/cgi-bin/recherche?rncs=".$societe->siren."\">".$societe->siren."</a>&nbsp;</td>";
    print '<td>'.$langs->transcountry('ProfId2',$societe->pays_code).'</td><td>'.$societe->siret.'</td></tr>';

    print '<tr><td>'.$langs->transcountry('ProfId3',$societe->pays_code).'</td><td>'.$societe->ape.'</td><td colspan="2">&nbsp;</td></tr>';

    print "</table>";

    /*
     *
     */
    print "</td></tr></table>\n";

    print '</div>';


    if ($conf->facture->enabled && $user->rights->facture->lire)
    {
        // Factures
        print_fiche_titre("Bills");
    
        print '<table class="border" width="100%">';
    
        $sql = "SELECT s.nom, s.idp, f.facnumber, f.amount, ".$db->pdate("f.datef")." as df";
        $sql .= " , f.paye as paye, f.fk_statut as statut, f.rowid as facid ";
        $sql .= " FROM ".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."facture as f";
        $sql .= " WHERE f.fk_soc = s.idp AND s.idp = ".$societe->id;
        $sql .= " ORDER BY f.datef DESC";
    
        $resql=$db->query($sql);
        if ($resql)
        {
            $var=true;
            $num = $db->num_rows($resql);
            if ($num > 0)
            {
                print '<tr class="liste_titre">';
                print '<td width="100" align="center">'.$langs->trans("Date").'</td><td>&nbsp;</td>';
                print '<td align="right">'.$langs->trans("Debit").'</td>';
                print '<td align="right">'.$langs->trans("Credit").'</td>';
                print '<td align="right">'.$langs->trans("Balance").'</td>';
                print '</tr>';
            }
            else
            {
                print $langs->trans("NoBills");
            }
            
            $i = 0;
            while ($i < $num)
            {
                $objp = $db->fetch_object($resql);
                $facs[$i] = $objp->facid;
                $i++;
            }
            $db->free($resql);
    
    
            $solde = 0;
    
            // Boucle sur chaque facture
            for ($i = 0 ; $i < $num ; $i++)
            {
                $fac = new Facture($db);
                $ret=$fac->fetch($facs[$i]);
                if ($ret < 0) 
                {
                    print $fac->error."<br>";
                    continue;
                }

                $var=!$var;
                print "<tr $bc[$var]>";
    
                print "<td align=\"center\">".dolibarr_print_date($fac->date)."</td>\n";
                print "<td><a href=\"../compta/facture.php?facid=$fac->id\">".img_object($langs->trans("ShowBill"),"bill")." ".$fac->ref."</a></td>\n";
    
                print '<td align="right">'.price($fac->total_ttc)."</td>\n";
                $solde = $solde + $fac->total_ttc;
    
                print '<td align="right">&nbsp;</td>';
                print '<td align="right">'.price($solde)."</td>\n";
                print "</tr>\n";
    
                // Paiements
                $sql = "SELECT p.rowid,".$db->pdate("p.datep")." as dp, pf.amount, p.statut";
    
                $sql .= " FROM ".MAIN_DB_PREFIX."paiement as p";
                $sql .= ", ".MAIN_DB_PREFIX."paiement_facture as pf";
                $sql .= " WHERE pf.fk_paiement = p.rowid";
                $sql .= " AND pf.fk_facture = ".$fac->id;
    
                $resql = $db->query($sql);
                if ($resql)
                {
                    $nump = $db->num_rows($resql);
                    $j = 0;
    
                    while ($j < $nump)
                    {
                        $objp = $db->fetch_object($resql);
                        //$var=!$var;
                        print "<tr $bc[$var]>";
                        print '<td align="center">'.dolibarr_print_date($objp->dp)."</td>\n";
                        print '<td>';
                        print '&nbsp; &nbsp; &nbsp; '; // Décalage
                        print '<a href="paiement/fiche.php?id='.$objp->rowid.'">'.img_object($langs->trans("ShowPayment"),"payment").' '.$langs->trans("Payment").' '.$objp->rowid.'</td>';
                        print "<td>&nbsp;</td>\n";
                        print '<td align="right">'.price($objp->amount).'</td>';
                        $solde = $solde - $objp->amount;
                        print '<td align="right">'.price($solde)."</td>\n";
                        print '</tr>';
    
                        $j++;
                    }
                }
            }
        }
        else
        {
            dolibarr_print_error($db);
        }
        print "</table>";
    }      
}
else
{
  print "Erreur";
}


$db->close();

llxFooter('$Date$ - $Revision$');
?>
