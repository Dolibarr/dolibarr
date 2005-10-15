<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Éric Seigne          <erics@rycks.com>
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
        \file       htdocs/fourn/fiche.php
        \ingroup    fournisseur, facture
        \brief      Page de fiche fournisseur
        \version    $Revision$
*/

require("./pre.inc.php");

$langs->load("suppliers");
$langs->load("products");
$langs->load("bills");
$langs->load("orders");
$langs->load("companies");

$socid = $_GET["socid"];
/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socid = $user->societe_id;
}


/*
 * Mode fiche
 */  
$societe = new Fournisseur($db);

if ( $societe->fetch($socid) )
{
  $addons[0][0] = DOL_URL_ROOT.'/fourn/fiche.php?socid='.$socid;
  $addons[0][1] = $societe->nom;

  llxHeader('',$langs->trans("SupplierCard").' : '.$societe->nom, $addons);

  /*
   * Affichage onglets
   */
  $h = 0;

  $head[$h][0] = DOL_URL_ROOT.'/soc.php?socid='.$socid;
  $head[$h][1] = $langs->trans("Company");
  $h++;

  if ($societe->client==1)
    {
      $head[$h][0] = DOL_URL_ROOT.'/comm/fiche.php?socid='.$socid;
      $head[$h][1] = $langs->trans("Customer");
      $h++;
    }
  if ($societe->client==2)
    {
      $head[$h][0] = DOL_URL_ROOT.'/comm/prospect/fiche.php?id='.$socid;
      $head[$h][1] = $langs->trans("Prospect");
      $h++;
    }
  if ($societe->fournisseur)
    {
      $hselected=$h;
      $head[$h][0] = DOL_URL_ROOT.'/fourn/fiche.php?socid='.$socid;
      $head[$h][1] = $langs->trans("Supplier");
      $h++;
    }

  if ($conf->compta->enabled) {
    $langs->load("compta");
    $head[$h][0] = DOL_URL_ROOT.'/compta/fiche.php?socid='.$socid;
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
        
  dolibarr_fiche_head($head, $hselected, $societe->nom);

  /*
   *
   *
   */

  print '<table width="100%">';
  print '<tr><td valign="top" width="50%">';

  print '<table class="border" width="100%">';
  print '<tr><td width="20%">'.$langs->trans("Name").'</td><td width="80%" colspan="3">'.$societe->nom.'</td></tr>';

    print '<tr><td>'.$langs->trans('Prefix').'</td><td colspan="3">'.$societe->prefix_comm.'</td></tr>';

    /*
    if ($societe->client) {
        print '<tr><td>';
        print $langs->trans('CustomerCode').'</td><td colspan="3">';
        print $societe->code_client;
        if ($societe->check_codeclient() <> 0) print ' '.$langs->trans("WrongCustomerCode");
        print '</td></tr>';
    }
    */
    
    if ($societe->fournisseur) {
        print '<tr><td>';
        print $langs->trans('SupplierCode').'</td><td colspan="3">';
        print $societe->code_fournisseur;
        if ($societe->check_codefournisseur() <> 0) print ' '.$langs->trans("WrongSupplierCode");
        print '</td></tr>';
        print '<tr>';
        print '<td nowrap>'.$langs->trans("SupplierAccountancyCode").'</td><td colspan="3">'.$societe->code_compta_fournisseur.'</td>';
        print '</tr>';
    }
    
  print '<tr><td valign="top">'.$langs->trans("Address").'</td><td colspan="3">'.nl2br($societe->adresse).'</td></tr>';
  
  print '<tr><td>'.$langs->trans("Zip").'</td><td>'.$societe->cp.'</td>';
  print '<td>'.$langs->trans("Town").'</td><td>'.$societe->ville.'</td></tr>';
  print '<tr><td>'.$langs->trans("Country").'</td><td colspan="3">'.$societe->pays.'</td></tr>';
  print '<tr><td>'.$langs->trans("Phone").'</td><td>'.dolibarr_print_phone($societe->tel).'&nbsp;</td><td>'.$langs->trans("Fax").'</td><td>'.dolibarr_print_phone($societe->fax).'&nbsp;</td></tr>';
  print '<tr><td>'.$langs->trans("Web")."</td><td colspan=\"3\"><a href=\"http://$societe->url\">$societe->url</a>&nbsp;</td></tr>";

    print "<tr><td nowrap>".$langs->transcountry("ProfId1",$societe->pays_code)."</td><td><a href=\"http://www.societe.com/cgi-bin/recherche?rncs=".$societe->siren."\">".$societe->siren."</a>&nbsp;</td>";
    print '<td>'.$langs->transcountry('ProfId2',$societe->pays_code).'</td><td>'.$societe->siret.'</td></tr>';

    print '<tr><td>'.$langs->transcountry('ProfId3',$societe->pays_code).'</td><td>'.$societe->ape.'</td><td colspan="2">&nbsp;</td></tr>';

    // Statut juridique
    print '<tr><td>'.$langs->trans('JuridicalStatus').'</td><td colspan="3">'.$societe->forme_juridique.'</td></tr>';

    // Type + Staff
    $arr = $societe->typent_array($societe->typent_id);
    $societe->typent= $arr[$societe->typent_id];
    print '<tr><td>'.$langs->trans("Type").'</td><td>'.$societe->typent.'</td><td>'.$langs->trans("Staff").'</td><td>'.$societe->effectif.'</td></tr>';

  print '</table>';


  /*
   *
   */

  print '</td><td valign="top" width="50%">';


  $var=true;

  /*
   * Liste des produits
   */
  if ($conf->produit->enabled || $conf->service->enabled)
    {
      $langs->load("products");
      print '<table class="noborder" width="100%">';
      print '<tr class="liste_titre">';
      print '<td>'.$langs->trans("ProductsAndServices").'</td><td align="right">';
      print '<a href="'.DOL_URL_ROOT.'/fourn/product/liste.php?fourn_id='.$societe->id.'">'.$langs->trans("All").' ('.$societe->NbProduct().')';
      print '</a></td></tr></table><br>';
    }

    /*
     * Liste des commandes associées
     */
    $max=4;
    
    $sql  = "SELECT p.rowid,p.ref,".$db->pdate("p.date_commande")." as dc, p.fk_statut";
    $sql.= " FROM ".MAIN_DB_PREFIX."commande_fournisseur as p ";
    $sql.= " WHERE p.fk_soc =".$societe->id;
    $sql.= " ORDER BY p.rowid";
    $sql.= " ".$db->plimit($max);
    $resql=$db->query($sql);
    if ($resql)
    {
        $i = 0 ;
        $num = $db->num_rows($resql);
        if ($num > 0)
        {
            print '<table class="noborder" width="100%">';
            print '<tr class="liste_titre">';
            print '<td colspan="3">';
            print '<table class="noborder" width="100%"><tr><td>'.$langs->trans("LastOrders",min($num,$max)).'</td>';
            print '<td align="right"><a href="commande/liste.php?socid='.$societe->id.'">'.$langs->trans("AllOrders").' ('.$num.')</td></tr></table>';
            print '</td></tr>';
        }
        while ($i < $num && $i <= $max)
        {
            $obj = $db->fetch_object($resql);
            $var=!$var;
    
            print "<tr $bc[$var]>";
            print '<td><a href="commande/fiche.php?id='.$obj->rowid.'">'.img_object($langs->trans("ShowOrder"),"order")." ".$obj->ref.'</a></td>';
            print '<td align="center" width="80">';
            if ($obj->dc)
            {
                print dolibarr_print_date($obj->dc);
            }
            else
            {
                print "-";
            }
            print '</td>';
            print '<td width="20"><img src="commande/statut'.$obj->fk_statut.'.png"></td>';
            print '</tr>';
            $i++;
        }
        $db->free($resql);
        if ($num > 0)
        {
            print "</table><br>";
        }
    }
    else
    {
        dolibarr_print_error($db);
    }


    /*
     * Liste des factures associées
     */
    $langs->load("bills");
    
    $max=5;
    
    $sql = "SELECT p.rowid,p.libelle,p.facnumber,p.fk_statut,".$db->pdate("p.datef")." as df, total_ttc as amount, paye";
    $sql.= " FROM ".MAIN_DB_PREFIX."facture_fourn as p WHERE p.fk_soc = $societe->id";
    $sql.= " ORDER BY p.datef DESC";
    $sql.= " ".$db->plimit($conf->liste_limit+1, $offset);
    
    $resql=$db->query($sql);
    if ($resql)
    {
        $i = 0 ;
        $num = $db->num_rows($resql);
        if ($num > 0)
        {
            print '<table class="noborder" width="100%">';
            print '<tr class="liste_titre">';
            print "<td colspan=\"4\">";
            print "<table class=\"noborder\" width=\"100%\"><tr><td>".$langs->trans("LastSuppliersBills",min($num,$max))."</td><td align=\"right\"><a href=\"facture/index.php?socid=$societe->id\">".$langs->trans("AllBills")." (".$num.")</td></tr></table>";
            print "</td></tr>";
        }
        while ($i < $num && $i < $max)
        {
            $obj = $db->fetch_object($resql);
            $var=!$var;
    
            print "<tr $bc[$var]>";
            print '<td>';
            print '<a href="facture/fiche.php?facid='.$obj->rowid.'">';
            print img_object($langs->trans("ShowBill"),"bill")." ".$obj->facnumber.'</a> '.dolibarr_trunc($obj->libelle,14).'</td>';
            print "<td align=\"right\" width=\"80\">".dolibarr_print_date($obj->df)."</td>";
            print '<td align="right">'.$obj->amount.'</td>';
            $fac = new FactureFournisseur($db);
            print '<td align="center">'.$fac->LibStatut($obj->paye,$obj->fk_statut).'</td>';
            print "</tr>";
            $i++;
        }
        $db->free($resql);
        if ($num > 0)
        {
            print "</table><br>";
        }
    }
    else
    {
        dolibarr_print_error($db);
    }

  /*
   *
   *
   */
  print '</td></tr>';
  print '</table>' . "\n";
  print '</div>';

  /*
   *
   * Barre d'actions
   *
   */
  
  print '<div class="tabsAction">';

  if ($user->rights->fournisseur->commande->creer)
    {
      $langs->load("orders");
      print '<a class="tabAction" href="'.DOL_URL_ROOT.'/fourn/commande/fiche.php?action=create&socid='.$societe->id.'">'.$langs->trans("AddOrder").'</a>';
    }

  if ($user->rights->fournisseur->facture->creer)
    {
      $langs->load("bills");
      print '<a class="tabAction" href="'.DOL_URL_ROOT.'/fourn/facture/fiche.php?action=create&socid='.$societe->id.'">'.$langs->trans("AddBill").'</a>';
    }
  print '</div>';

  /*
   *
   * Liste des contacts
   *
   */
  $langs->load("companies");

  print '<br><table class="noborder" width="100%">';

  print '<tr class="liste_titre"><td><b>'.$langs->trans("Contact").'</b></td>';
  print '<td><b>Poste</b></td><td><b>'.$langs->trans("Tel").'</b></td>';
  print "<td><b>".$langs->trans("Fax")."</b></td><td><b>".$langs->trans("EMail")."</b></td>";
  print "<td align=\"center\"><a href=\"".DOL_URL_ROOT.'/contact/fiche.php?socid='.$socid."&amp;action=create\">".$langs->trans("AddContact")."</a></td></tr>";
    
  $sql = "SELECT p.idp, p.name, p.firstname, p.poste, p.phone, p.fax, p.email, p.note";
  $sql.= " FROM ".MAIN_DB_PREFIX."socpeople as p";
  $sql.= " WHERE p.fk_soc = ".$societe->id;
  $sql.= "  ORDER by p.datec";

  $result = $db->query($sql);

  $i = 0 ;
  $num = $db->num_rows($result);
  $var=true;
  
  while ($i < $num)
    {
      $obj = $db->fetch_object($result);
      $var = !$var;

      print "<tr $bc[$var]>";

      print '<td>';
      print '<a href="'.DOL_URL_ROOT.'/contact/fiche.php?id='.$obj->idp.'">';
      print img_object($langs->trans("ShowContact"),"contact");
      print ' '.$obj->firstname.' '. $obj->name.'</a>&nbsp;';

      if ($obj->note)
	{
	  print "<br>".nl2br($obj->note);
	}
      print "</td>";
      print "<td>$obj->poste&nbsp;</td>";
      print '<td><a href="../comm/action/fiche.php?action=create&actionid=1&contactid='.$obj->idp.'&socid='.$societe->id.'">'.$obj->phone.'</a>&nbsp;</td>';
      print '<td><a href="../comm/action/fiche.php?action=create&actionid=2&contactid='.$obj->idp.'&socid='.$societe->id.'">'.$obj->fax.'</a>&nbsp;</td>';
      print '<td><a href="../comm/action/fiche.php?action=create&actionid=4&contactid='.$obj->idp.'&socid='.$societe->id.'">'.$obj->email.'</a>&nbsp;</td>';
      print "<td align=\"center\"><a href=\"../contact/fiche.php?action=edit&amp;id=$obj->idp\">".img_edit()."</a></td>";
      print "</tr>\n";
      $i++;
    }
  print "</table>";

}
else
{
  dolibarr_print_error($db);
}
$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
