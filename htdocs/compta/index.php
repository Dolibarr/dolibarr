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

/**     \file       htdocs/compta/index.php
        \ingroup    compta
		\brief      Page accueil zone comptabilité
		\version    $Revision$
*/

require("./pre.inc.php");

$user->getrights('banque');

$langs->load("compta");

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socidp = $user->societe_id;
}

llxHeader("","Accueil Compta");


/*
 * Actions
 */

if ($_GET["action"] == 'add_bookmark')
{
  $sql = "DELETE FROM ".MAIN_DB_PREFIX."bookmark WHERE fk_soc = ".$socidp." AND fk_user=".$user->id;
  if (! $db->query($sql) )
    {
      dolibarr_print_error($db);
    }
  $sql = "INSERT INTO ".MAIN_DB_PREFIX."bookmark (fk_soc, dateb, fk_user) VALUES ($socidp, now(),".$user->id.");";
  if (! $db->query($sql) )
    {
      dolibarr_print_error($db);
    }
}

if ($_GET["action"] == 'del_bookmark')
{
  $sql = "DELETE FROM ".MAIN_DB_PREFIX."bookmark WHERE rowid=".$_GET["bid"];
  $result = $db->query($sql);
}



/*
 * Affichage page
 *
 */
print_titre("Espace comptabilité");

print '<table border="0" width="100%">';

print '<tr><td valign="top" width="30%">';

/*
 * Zone recherche facture
 */
print '<form method="post" action="facture.php">';
print '<table class="noborder" width="100%">';
print "<tr class=\"liste_titre\">";
print '<td colspan="2">'.$langs->trans("SearchABill").'</td></tr>';
print "<tr $bc[0]><td>";
print $langs->trans("Ref").' : <input type="text" name="sf_ref">&nbsp;<input type="submit" value="'.$langs->trans("Search").'" class="button"></td></tr>';
print "</table></form><br>";



/*
* Factures brouillons
*/
if ($conf->facture->enabled)
{
  
  $sql = "SELECT f.facnumber, f.rowid, s.nom, s.idp FROM ".MAIN_DB_PREFIX."facture as f, ".MAIN_DB_PREFIX."societe as s WHERE s.idp = f.fk_soc AND f.fk_statut = 0";
  
  if ($socidp)
    {
      $sql .= " AND f.fk_soc = $socidp";
    }
  
  if ( $db->query($sql) )
    {
      $num = $db->num_rows();
      $i = 0;
      
      if ($num)
	{
	  print '<table class="noborder" width="100%">';
	  print '<tr class="liste_titre">';
	  print '<td colspan="2">'.$langs->trans("DraftBills").' ('.$num.')</td></tr>';
	  $var = True;
	  while ($i < $num && $i < 20)
	    {
	      $obj = $db->fetch_object();
	      $var=!$var;
	      print '<tr '.$bc[$var].'><td width="92"><a href="facture.php?facid='.$obj->rowid.'">'.img_object($langs->trans("ShowBill"),"bill").'</a> ';
	      print '<a href="facture.php?facid='.$obj->rowid.'">'.$obj->facnumber.'</a></td>';
	      print '<td><a href="fiche.php?socid='.$obj->idp.'">'.$obj->nom.'</a></td></tr>';
	      $i++;
	    }
	  
	  print "</table><br>";
	}
    }
  else
    {
      dolibarr_print_error($db);
    }  
}

if ($conf->compta->enabled) {

/*
 * Charges a payer
 */
if ($user->societe_id == 0)
{
 
  $sql = "SELECT c.amount, cc.libelle";
  $sql .= " FROM ".MAIN_DB_PREFIX."chargesociales as c, ".MAIN_DB_PREFIX."c_chargesociales as cc";
  $sql .= " WHERE c.fk_type = cc.id AND c.paye=0";
  
  if ( $db->query($sql) ) 
    {
      $num = $db->num_rows();
      if ($num)
	{
	  print '<table class="noborder" width="100%">';
	  print '<tr class="liste_titre">';
	  print '<td colspan="2">Charges à payer ('.$num.')</td></tr>';
	  $i = 0;
	  $var = True;
	  while ($i < $num)
	    {
	      $obj = $db->fetch_object();
	      $var = !$var;
	      print "<tr $bc[$var]>";
	      print '<td>'.$obj->libelle.'</td>';
	      print '<td align="right">'.price($obj->amount).'</td>';
	      print '</tr>';
	      $i++;
	    }
	  print '</table><br>';
	}
    }
  else
    {
      dolibarr_print_error($db);
    }
}

}


/*
 * Bookmark
 */
$sql = "SELECT s.idp, s.nom,b.rowid as bid";
$sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."bookmark as b";
$sql .= " WHERE b.fk_soc = s.idp AND b.fk_user = ".$user->id;
$sql .= " ORDER BY lower(s.nom) ASC";

if ( $db->query($sql) )
{
  $num = $db->num_rows();
  $i = 0;
  if ($num)
    {
      print '<table class="noborder" width="100%">';
      print "<tr class=\"liste_titre\"><td colspan=\"2\">".$langs->trans("Bookmarks")."</td></tr>\n";
      $var = True;
      while ($i < $num)
	{
	  $obj = $db->fetch_object();
	  $var = !$var;
	  print "<tr $bc[$var]>";
	  print '<td><a href="fiche.php?socid='.$obj->idp.'">'.$obj->nom.'</a></td>';
	  print '<td align="right"><a href="index.php?action=del_bookmark&amp;bid='.$obj->bid.'">'.img_delete().'</a></td>';
	  print '</tr>';
	  $i++;
	}
      print '</table>';
    }
}

print '</td><td valign="top" width="70%">';


/*
 * Commandes à facturer
 */
if ($conf->commande->enabled && $user->rights->commande->lire)
{
  $langs->load("orders");

  $sql = "SELECT p.rowid, p.ref, s.nom, s.idp FROM ".MAIN_DB_PREFIX."commande as p, ".MAIN_DB_PREFIX."societe as s";
  $sql .= " WHERE p.fk_soc = s.idp AND p.fk_statut >= 1 AND p.facture = 0";
  if ($socidp)
    {
      $sql .= " AND p.fk_soc = $socidp";
    }

  if ( $db->query($sql) ) 
    {
      $num = $db->num_rows();
      if ($num)
	{
	  $i = 0;
	  print '<table class="noborder" width="100%">';
	  print "<tr class=\"liste_titre\">";
	  print '<td colspan="2">'.$langs->trans("OrdersToBill").' ('.$num.')</td></tr>';
	  $var = True;
	  while ($i < $num)
	    {
	      $var=!$var;
	      $obj = $db->fetch_object();
	      print "<tr $bc[$var]><td width=\"20%\"><a href=\"commande.php?id=$obj->rowid\">".img_object($langs->trans("ShowOrder"),"order")."</a>";
	      print " <a href=\"commande.php?id=$obj->rowid\">$obj->ref</a></td>";
	      print '<td><a href="fiche.php?socid='.$obj->idp.'">'.$obj->nom.'</a></td></tr>';
	      $i++;
	    }
	  print "</table><br>";
	}
    }
  else {
      dolibarr_print_error($db);
    }    
}


if ($conf->facture->enabled)
{
  
  /*
   * Factures impayées
   *
   */
  
  $sql = "SELECT f.facnumber, f.rowid, s.nom, s.idp, f.total, f.total_ttc, sum(pf.amount) as am";
  $sql .= " FROM ".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."facture as f left join ".MAIN_DB_PREFIX."paiement_facture as pf on f.rowid=pf.fk_facture";
  $sql .= " WHERE s.idp = f.fk_soc AND f.paye = 0 AND f.fk_statut = 1";
  if ($socidp)
    {
      $sql .= " AND f.fk_soc = $socidp";
    }
  $sql .= " GROUP BY f.facnumber,f.rowid,s.nom, s.idp, f.total_ttc";   
  
  if ( $db->query($sql) )
    {
      $num = $db->num_rows();
      $i = 0;
      
      if ($num)
	{
	  print '<table class="noborder" width="100%">';
	  print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("BillsCustomersUnpayed").' ('.$num.')</td>';
	  print '<td align="right">'.$langs->trans("AmountHT").'</td><td align="right">'.$langs->trans("AmountTTC").'</td><td align="right">'.$langs->trans("Received").'</td></tr>';
	  $var = True;
	  $total_ttc = $totalam = $total = 0;
	  while ($i < $num)
	    {
	      $obj = $db->fetch_object();

	      if ($i < 20)
		{
		  $var=!$var;
		  print '<tr '.$bc[$var].'><td width="20%"><a href="facture.php?facid='.$obj->rowid.'">'.img_object($langs->trans("ShowBill"),"bill").'</a>';
		  print ' <a href="facture.php?facid='.$obj->rowid.'">'.$obj->facnumber.'</a></td>';
		  print '<td><a href="fiche.php?socid='.$obj->idp.'">'.img_object($langs->trans("ShowCustomer"),"company").'</a>';
		  print ' <a href="fiche.php?socid='.$obj->idp.'">'.$obj->nom.'</a></td>';
		  print '<td align="right">'.price($obj->total).'</td>';
		  print '<td align="right">'.price($obj->total_ttc).'</td>';
		  print '<td align="right">'.price($obj->am).'</td></tr>';
		}
	      $total_ttc +=  $obj->total_ttc;
		  $total += $obj->total;
	      $totalam +=  $obj->am;
	      $i++;
	    }
	  $var=!$var;
	  print '<tr '.$bc[$var].'><td colspan="2" align="left"><i>'.$langs->trans("Total").' &nbsp; ('.$langs->trans("RemainderToTake").': '.price($total_ttc-$totalam).')</i></td><td align="right"><i>'.price($total)."</i></td><td align=\"right\"><i>".price($total_ttc)."</i></td><td align=\"right\"><i>".price($totalam)."</i></td></tr>";
	  print "</table><br>";
	}
      $db->free();
    }
  else
    {
      dolibarr_print_error($db);
    }  
}


// \todo Mettre ici recup des actions en rapport avec la compta
$result = 0;
if ( $result )
{
  print '<table class="noborder" width="100%">';
  print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("TasksToDo").'</td>';
  print "</tr>\n";
  $var = True;
  $i = 0;
  while ($i < $db->num_rows() )
    {
      $obj = $db->fetch_object();
      $var=!$var;
      
      print "<tr $bc[$var]><td>".strftime("%d %b %Y",$obj->da)."</td><td><a href=\"action/fiche.php\">$obj->libelle $obj->label</a></td></tr>";
      $i++;
    }
  $db->free();
  print "</table><br>";
}


/*
 * Factures a payer
 */
if ($conf->facture->enabled) {

    if ($user->societe_id == 0)
    {
      $sql = "SELECT ff.rowid, ff.facnumber, ff.libelle, ff.total_ht, ff.total_ttc, s.nom, s.idp";
      $sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."facture_fourn as ff";
      $sql .= " WHERE s.idp = ff.fk_soc";
      $sql .= " AND ff.paye=0";
      
      $result=$db->query($sql);
      if ($result) 
        {
          $num = $db->num_rows();
          if ($num)
    	{
    	  print '<table class="noborder" width="100%">';
          print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("BillsSuppliersUnpayed").' ('.$num.')</td>';
          print '<td align="right">'.$langs->trans("AmountHT").'</td>';
          print '<td align="right">'.$langs->trans("AmountTTC").'</td></tr>';
    	  print "</tr>\n";
    	  $i = 0;
    	  $var = True;
    	  $total = $total_ttc = $totalam = 0;
    	  while ($i < $num)
    	    {
    	      $obj = $db->fetch_object($result);
    	      $var = !$var;
        	  print '<tr '.$bc[$var].'><td width="20%"><a href="'.DOL_URL_ROOT.'/fourn/facture/fiche.php?facid='.$obj->rowid.'">'.img_object($langs->trans("ShowBill"),"bill").'</a>';
    	      print '&nbsp;<a href="'.DOL_URL_ROOT.'/fourn/facture/fiche.php?facid='.$obj->rowid.'">'.$obj->facnumber.'</a></td>';
    		  print '<td><a href="fiche.php?socid='.$obj->idp.'">'.img_object($langs->trans("ShowSupplier"),"company").'</a>';
	    	  print ' <a href="fiche.php?socid='.$obj->idp.'">'.$obj->nom.'</a></td>';
		      print '<td align="right">'.price($obj->total_ht).'</td>';
              print '<td align="right">'.price($obj->total_ttc).'</td>';
    	      print '</tr>';
        	  $total += $obj->total_ht;
        	  $total_ttc +=  $obj->total_ttc;
        	  $totalam +=  $obj->am;
    	      $i++;
            }
          $var=!$var;
          print '<tr '.$bc[$var].'><td colspan="2" align="left"><i>'.$langs->trans("Total").' &nbsp; ('.$langs->trans("RemainderToPay").': '.price($total_ttc-$totalam).')</td><td align="right">'.price($total).'</td><td align="right">'.price($total_ttc).'</td></tr>';
    	  print '</table><br>';
    	}
        }
      else
        {
          dolibarr_print_error($db);
        }
    }

}


print '</td></tr>';

print '</table>';

$db->close();
 
llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
