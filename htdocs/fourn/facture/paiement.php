<?PHP
/* Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004 Éric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
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
require("./paiementfourn.class.php");

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socidp = $user->societe_id;
}
/*
 *
 */
if ($action == 'add') {
  $paiementfourn = new PaiementFourn($db);

  $paiementfourn->facid        = $facid;
  $paiementfourn->facnumber    = $facnumber;
  $paiementfourn->datepaye     = $db->idate(mktime(12, 0 , 0,
					      $HTTP_POST_VARS["remonth"], 
					      $HTTP_POST_VARS["reday"], 
					      $HTTP_POST_VARS["reyear"])); 
  $paiementfourn->amount       = $amount;
  $paiementfourn->accountid    = $accountid;
  $paiementfourn->societe      = $societe;
  $paiementfourn->author       = $author;
  $paiementfourn->paiementid   = $paiementid;
  $paiementfourn->num_paiement = $num_paiement;
  $paiementfourn->note         = $note;

  if ( $paiementfourn->create($user) )
    {
      Header("Location: fiche.php?facid=$facid");
    }

  $action = '';
}

/*
 *
 *
 */

llxHeader();

if ($action == 'create')
{

  $sql = "SELECT s.nom,s.idp, f.amount, f.total_ttc, f.facnumber";
  $sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."facture_fourn as f WHERE f.fk_soc = s.idp";
  $sql .= " AND f.rowid = $facid";

  $result = $db->query($sql);
  if ($result)
    {
      $num = $db->num_rows();
      if ($num)
	{
	  $obj = $db->fetch_object( 0);

	  $total = $obj->total_ttc;

      print_titre("Emettre un paiement");
      print '<form action="paiement.php?facid='.$facid.'" method="post">';
      print '<input type="hidden" name="action" value="add">';
      print '<table class="border" cellspacing="0" width="100%" cellpadding="3">';

      print "<tr class=\"liste_titre\"><td colspan=\"3\">Facture</td>";

      print '<tr><td>Numéro :</td><td colspan="2">';
      print '<a href="fiche.php?facid='.$facid.'">'.$obj->facnumber.'</a></td></tr>';
      print "<tr><td>Société :</td><td colspan=\"2\">$obj->nom</td></tr>";

      print "<tr><td>Montant :</td><td colspan=\"2\">".price($obj->total_ttc)." euros TTC</td></tr>";

      $sql = "SELECT sum(p.amount) FROM ".MAIN_DB_PREFIX."paiementfourn as p WHERE p.fk_facture_fourn = $facid;";
      $result = $db->query($sql);
      if ($result) {
	$sumpayed = $db->result(0,0);
	$db->free();
      }
      print '<tr><td>Déjà payé</td><td colspan="2"><b>'.price($sumpayed).'</b> euros TTC</td></tr>';

      print "<tr class=\"liste_titre\"><td colspan=\"3\">Paiement</td>";

      print "<input type=\"hidden\" name=\"facid\" value=\"$facid\">";
      print "<input type=\"hidden\" name=\"facnumber\" value=\"$obj->facnumber\">";
      print "<input type=\"hidden\" name=\"socid\" value=\"$obj->idp\">";
      print "<input type=\"hidden\" name=\"societe\" value=\"$obj->nom\">";
      
      $html = new Form($db);

      print "<tr><td>Date :</td><td>";
      $html->select_date();
      print "</td>";

      print "<td>Commentaires :</td></tr>";

      print "<input type=\"hidden\" name=\"author\" value=\"$author\">\n";

      print "<tr><td>Type :</td><td><select name=\"paiementid\">\n";

      $sql = "SELECT id, libelle FROM ".MAIN_DB_PREFIX."c_paiement ORDER BY id";
  
      if ($db->query($sql))
	{
	  $num = $db->num_rows();
	  $i = 0; 
	  while ($i < $num)
	    {
	      $objopt = $db->fetch_object($i);
	      print "<option value=\"$objopt->id\">$objopt->libelle</option>\n";
	      $i++;
	    }
      }
      print "</select><br>";
      print "</td>\n";

      print "<td rowspan=\"4\">";
      print '<textarea name="comment" wrap="soft" cols="40" rows="10"></textarea></td></tr>';

      print "<tr><td>Numéro :</td><td><input name=\"num_paiement\" type=\"text\"><br><em>Num du cheque ou virement</em></td></tr>\n";

      print "<tr><td>Compte à débiter :</td><td><select name=\"accountid\"><option value=\"\">-</option>\n";
      $sql = "SELECT rowid, label FROM ".MAIN_DB_PREFIX."bank_account ORDER BY rowid";
      $result = $db->query($sql);
      if ($result)
	{
	  $num = $db->num_rows();
	  $i = 0; 
	  while ($i < $num)
	    {
	      $objopt = $db->fetch_object( $i);
	      print '<option value="'.$objopt->rowid.'"';
	      if (defined("FACTURE_RIB_NUMBER") && FACTURE_RIB_NUMBER == $objopt->rowid)
		{
		  print ' SELECTED';
		}
	      print '>'.$objopt->label.'</option>';
	      $i++;
	    }
	}
      print "</select>";
      print "</td></tr>\n";

      print "<tr><td valign=\"top\">Reste à payer :</td><td><b>".price($total - $sumpayed)."</b> euros TTC</td></tr>\n";
      print "<tr><td valign=\"top\">Montant :</td><td><input name=\"amount\" type=\"text\"></td></tr>\n";
      print '<tr><td colspan="3" align="center"><input type="submit" value="Enregistrer"></td></tr>';
      print "</form>\n";
      print "</table>\n";

    }
  }
} 

if ($action == '') {

  if ($page == -1)
    {
      $page = 0 ;
    }
  $limit = $conf->liste_limit;
  $offset = $limit * $page ;

  $sql = "SELECT ".$db->pdate("p.datep")." as dp, p.amount, f.amount as fa_amount, f.facnumber, s.nom";
  $sql .=", f.rowid as facid, c.libelle as paiement_type, p.num_paiement";
  $sql .= " FROM ".MAIN_DB_PREFIX."paiementfourn as p, ".MAIN_DB_PREFIX."facture_fourn as f, ".MAIN_DB_PREFIX."c_paiement as c, ".MAIN_DB_PREFIX."societe as s";
  $sql .= " WHERE p.fk_facture_fourn = f.rowid AND p.fk_paiement = c.id AND s.idp = f.fk_soc";

  if ($socidp)
    {
      $sql .= " AND f.fk_soc = $socidp";
    }

  $sql .= " ORDER BY datep DESC";
  $sql .= $db->plimit($limit + 1 ,$offset);
  $result = $db->query($sql);

  if ($result)
    {
      $num = $db->num_rows();
      $i = 0; 
      $var=True;

      print_barre_liste("Paiements", $page, $PHP_SELF,"&amp;socidp=$socidp",$sortfield,$sortorder,'',$num);

      print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
      print '<TR class="liste_titre">';
      print "<td>Facture</td>";
      print "<td>Société</td>";
      print "<td>Date</td>";
      print "<td>Type</TD>";
      print '<td align="right">Montant</TD>';
      print "<td>&nbsp;</td></tr>";
    
      while ($i < min($num,$limit))
	{
	  $objp = $db->fetch_object( $i);
	  $var=!$var;
	  print "<TR $bc[$var]>";
	  print "<TD><a href=\"fiche.php?facid=$objp->facid\">$objp->facnumber</a></TD>\n";
	  print '<td>'.$objp->nom.'</td>';
	  print "<TD>".strftime("%d %B %Y",$objp->dp)."</TD>\n";
	  print "<TD>$objp->paiement_type $objp->num_paiement</TD>\n";
	  print '<TD align="right">'.price($objp->amount).'</TD><td>&nbsp;</td>';	
	  print "</tr>";
	  $i++;
	}
      print "</table>";
    }
  else
    {
      print $db->error();
    }

}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
