<?PHP
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Éric Seigne          <erics@rycks.com>
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
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
require("./pre.inc.php");
require("../contact.class.php");

$langs->load("bills");
$langs->load("companies");


llxHeader();

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
 *
 * Mode fiche
 *
 *
 */  
$societe = new Societe($db);

if ( $societe->fetch($socid) )
{
    /*
     * Affichage onglets
     */
    $h = 0;

    $head[$h][0] = DOL_URL_ROOT.'/soc.php?socid='.$socid;
    $head[$h][1] = "Fiche société";
    $h++;

    if ($societe->client==1)
    {
        $head[$h][0] = DOL_URL_ROOT.'/comm/fiche.php?socid='.$socid;
        $head[$h][1] = 'Client';
        $h++;
    }
    if ($societe->client==2)
    {
        $head[$h][0] = DOL_URL_ROOT.'/comm/prospect/fiche.php?id='.$socid;
        $head[$h][1] = 'Prospect';
        $h++;
    }
    if ($societe->fournisseur)
    {
        $hselected=$h;
        $head[$h][0] = DOL_URL_ROOT.'/fourn/fiche.php?socid='.$socid;
        $head[$h][1] = 'Fournisseur';
        $h++;
    }
    if ($conf->produit->enabled) {
        $head[$h][0] = DOL_URL_ROOT.'/product/liste.php?type=0&fourn_id='.$societe->id;
        $head[$h][1] = 'Produits';
        $h++;
    }
        
    dolibarr_fiche_head($head, $hselected, $societe->nom);

  /*
   *
   *
   */
  print '<table width="100%" cellspacing="0" cellpadding="2">';
  print '<tr><td valign="top" width="50%">';
  /*
   *
   */
  print '<table class="border" cellpadding="2" cellspacing="0" width="100%">';
  print '<tr><td width="20%">Nom</td><td width="80%" colspan="3">'.$societe->nom.'</td></tr>';
  print '<tr><td valign="top">Adresse</td><td colspan="3">'.nl2br($societe->adresse).'<br>'.$societe->cp.' '.$societe->ville.'</td></tr>';
  print '<tr><td>Tél</td><td>'.dolibarr_print_phone($societe->tel).'&nbsp;</td><td>Fax</td><td>'.dolibarr_print_phone($societe->fax).'&nbsp;</td></tr>';
  print "<tr><td>Web</td><td colspan=\"3\"><a href=\"http://$societe->url\">$societe->url</a>&nbsp;</td></tr>";

  print '</table>';
  /*
   *
   */
  print '</td><td valign="top" width="50%">';
  /*
   *
   * Liste des factures associées
   *
   */
  $sql  = "SELECT p.rowid,p.libelle,p.facnumber,".$db->pdate("p.datef")." as df";
  $sql .= " FROM ".MAIN_DB_PREFIX."facture_fourn as p WHERE p.fk_soc = $societe->id";
  $sql .= " ORDER BY p.datef DESC LIMIT 0,4";
  if ( $db->query($sql) )
    {
      print '<table class="noborder" cellspacing="0" width="100%" cellpadding="1">';
      $i = 0 ; 
      $num = $db->num_rows();
      if ($num > 0)
	{
	  print '<tr class="liste_titre">';
	  print "<td colspan=\"2\"><a href=\"facture/index.php?socid=$societe->id\">liste des factures</td></tr>";
	}
      while ($i < $num && $i < 5)
	{
	  $obj = $db->fetch_object( $i);
      $var=!$var;
      
	  print "<tr $bc[$var]>";
	  print '<td>';
	  print '<a href="facture/fiche.php?facid='.$obj->rowid.'">';
	  print img_file();
	  print $obj->facnumber.'</a> '.$obj->libelle.'</td>';	    
	  print "<td align=\"right\" width=\"100\">".dolibarr_print_date($obj->df)."</td></tr>";
	  $i++;
	}
      $db->free();
      print "</table>";
    }
  else
    {
      print $db->error();
    }

  /*
   *
   *
   */
  print '</td></tr>';
  print '</table>' . "<br>\n";
  print '</div>';

  /*
   * Boutons Actions
   */
  
  print '<div class="tabsAction">';
  print '<a class="tabAction" href="facture/fiche.php?action=create&socid='.$societe->id.'">'.$langs->trans("CreateBill").'</a>';
  print '</div>';
    

  /*
   *
   * Liste des contacts
   *
   */
  $langs->load("companies");

  print '<br><table class="noborder" cellspacing="0" cellpadding="2" width="100%">';

  print '<tr class="liste_titre"><td><b>'.$langs->trans("FirstName").' '.$langs->trans("LastName").'</b></td>';
  print '<td><b>Poste</b></td><td><b>'.$langs->trans("Tel").'</b></td>';
  print "<td><b>".$langs->trans("Fax")."</b></td><td><b>".$langs->trans("EMail")."</b></td>";
  print "<td align=\"center\"><a href=\"".DOL_URL_ROOT.'/contact/fiche.php?socid='.$socid."&amp;action=create\">".$langs->trans("AddContact")."</a></td></tr>";
    
  $sql = "SELECT p.idp, p.name, p.firstname, p.poste, p.phone, p.fax, p.email, p.note";
  $sql .= " FROM ".MAIN_DB_PREFIX."socpeople as p WHERE p.fk_soc = $societe->id  ORDER by p.datec";
  $result = $db->query($sql);
  $i = 0 ; $num = $db->num_rows();
  $var=1;
  while ($i < $num)
    {
      $obj = $db->fetch_object( $i);
      $var = !$var;

      print "<tr $bc[$var]>";

      print "<td>$obj->firstname $obj->name";
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
  print "Erreur";
}
$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
