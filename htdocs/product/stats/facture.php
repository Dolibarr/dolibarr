<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
require_once("../../facture.class.php");


$mesg = '';

if ($user->societe_id > 0)
{
  $action = '';
  $socid = $user->societe_id;
}

llxHeader();

/*
 *
 *
 */

if ($_GET["id"])
{
  $product = new Product($db);
  $result = $product->fetch($_GET["id"]);
  
  if ( $result )
    { 
      print_fiche_titre('Fiche produit : '.$product->ref, $mesg);
      
      print '<table class="border" width="100%"><tr>';
      print '<td width="20%">'.$langs->trans("Ref").'</td><td width="40%"><a href="../fiche.php?id='.$product->id.'">'.$product->ref.'</a></td>';
      print '<td><a href="fiche.php?id='.$product->id.'">'.$langs->trans("Statistics").'</a></td></tr>';
      print '<tr><td>'.$langs->trans("Label").'</td><td>'.$product->libelle.'</td>';
      print '<td valign="top" rowspan="2">';
      // Propals
      if ($conf->propal->enabled) {
        $langs->load("propal");
        print '<a href="propal.php?id='.$product->id.'">'.$langs->trans("Proposals").'</a> : '.$product->count_propale($socid);
        print " (Proposé à ".$product->count_propale_client($socid)." clients)<br>";
      }
      // Commande
      if ($conf->commande->enabled) {
        $langs->load("orders");
        print '<a href="commande.php?id='.$product->id.'">'.$langs->trans("Orders").'</a> : '.$product->count_facture($socid)."<br>";
      }
      // Factures
      if ($conf->facture->enabled) {
        $langs->load("bills");
        print '<a href="facture.php?id='.$product->id.'">'.$langs->trans("Bills").'</a> : '.$product->count_facture($socid);
      }
      print '</td></tr>';
      print '<tr><td>'.$langs->trans("CurrentPrice").'</td><td>'.price($product->price).'</td></tr>';
      print "</table>";

      if ($page == -1)
	{
	  $page = 0 ;
	}
      $limit = $conf->liste_limit;
      $offset = $limit * $page ;
      
      if ($sortorder == "")
	{
	  $sortorder="DESC";
	}
      if ($sortfield == "")
	{
	  $sortfield="f.datef";
	}
      
      print "<br>";
      print_barre_liste("Factures",$page,"facture.php","&amp;id=$product->id",$sortfield,$sortorder);
      
      $sql = "SELECT distinct(f.rowid), s.nom,s.idp,f.facnumber,f.amount,".$db->pdate("f.datef")." as df,f.paye,f.fk_statut as statut,f.rowid as facid";
      $sql .= " FROM ".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."facture as f, ".MAIN_DB_PREFIX."facturedet as d WHERE f.fk_soc = s.idp";
      $sql .= " AND d.fk_facture = f.rowid AND d.fk_product =".$product->id;
      if ($socid)
	{
	  $sql .= " AND f.fk_soc = $socid";
	}
      $sql .= " ORDER BY $sortfield $sortorder ";
      $sql .= $db->plimit( $limit ,$offset);

      $result = $db->query($sql);
      if ($result) {
	$num = $db->num_rows();
    
	$i = 0;
	print "<table class=\"noborder\" width=\"100%\">";

	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("Ref"),"facture.php","s.idp","","&amp;id=".$_GET["id"],'',$sortfield);
	print_liste_field_titre($langs->trans("Company"),"facture.php","s.nom","","&amp;id=".$_GET["id"],'',$sortfield);
	print_liste_field_titre($langs->trans("Date"),"facture.php","f.datef","","&amp;id=".$_GET["id"],'align="right"',$sortfield);
	print_liste_field_titre($langs->trans("Amount"),"facture.php","f.amount","","&amp;id=".$_GET["id"],'align="right"',$sortfield);
	print '<td>&nbsp;</td>';
	print "</tr>\n";
	
	if ($num > 0)
	  {
	    $var=True;
	    while ($i < $num)
	      {
		$objp = $db->fetch_object( $i);
		$var=!$var;
		
		print "<tr $bc[$var]>";
		print '<td><a href="'.DOL_URL_ROOT.'/compta/facture.php?facid='.$objp->facid.'">';
		if ($objp->paye)
		  {
		    print $objp->facnumber;
		  }
		else
		  {
		    print '<b>'.$objp->facnumber.'</b>';
		  }
		print "</a></td>\n";
		print '<td><a href="'.DOL_URL_ROOT.'/compta/fiche.php?socid='.$objp->idp.'">'.$objp->nom.'</a></td>';
		
		if ($objp->df > 0 )
		  {
		    print "<td align=\"right\">";
		    print strftime("%d %B %Y",$objp->df)."</td>";
		  }
		else
		  {
		    print "<td align=\"right\"><b>!!!</b></td>\n";
		  }
		
		print "<td align=\"right\">".price($objp->amount)."</td>\n";
		
		$fac=new Facture($db);
        print '<td align="center">'.$fac->LibStatut($objp->paye,$objp->statut).'</td>';
		
		print "</tr>\n";
		$i++;
	      }
	  }
      }
      else {
        dolibarr_print_error($db); 
      }
      print "</table>";
      $db->free();
    }
}
else
{
  print "Error";
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
