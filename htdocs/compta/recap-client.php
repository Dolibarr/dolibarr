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

/**    	\file       htdocs/compta/fiche.php
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

    if ($conf->compta->enabled)
      {
        $langs->load("compta");
        $head[$h][0] = DOL_URL_ROOT.'/compta/fiche.php?socid='.$societe->id;
        $head[$h][1] = $langs->trans("Accountancy");
        $h++;

        $head[$h][0] = DOL_URL_ROOT.'/compta/recap-client.php?socid='.$societe->id;
        $head[$h][1] = $langs->trans("Recap");
        $hselected=$h;
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
    print '<tr><td valign="top">'.$langs->trans("Address").'</td><td colspan="3">'.nl2br($societe->adresse)."</td></tr>";
    
    print '<tr><td>'.$langs->trans('Zip').' / '.$langs->trans('Town').'</td><td colspan="3">'.$societe->cp." ".$societe->ville.'</td></tr>';
    
    print '<tr><td>'.$langs->trans("Phone").'</td><td>'.$societe->tel.'&nbsp;</td><td>Fax</td><td>'.$societe->fax.'&nbsp;</td></tr>';

    print '<tr><td nowrap>'.$langs->transcountry("ProfId1",$societe->pays_code).'</td><td><a href="http://www.societe.com/cgi-bin/recherche?rncs='.$societe->siren.'">'.$societe->siren.'</a>&nbsp;</td>';
    print '<td>'.$langs->trans("Prefix").'</td><td>';
    if ($societe->prefix_comm)
      {
    	print $societe->prefix_comm;
      }
    
    print "</td></tr>";

    print '<tr><td>Code compta</td><td>'.$societe->code_compta.'</td>';
    print '<td>'.$langs->trans("CustomerCode").'</td><td>';
    print $societe->code_client;
    print "</td></tr>";

    print "</table>";

    /*
     *
     */
    print "</td></tr></table>\n";
    
    print '</div>';


    if ($conf->facture->enabled && $user->rights->facture->lire)
      {
        print '<table class="border" width="100%">';
    
        $sql = "SELECT s.nom, s.idp, f.facnumber, f.amount, ".$db->pdate("f.datef")." as df";
	$sql .= " , f.paye as paye, f.fk_statut as statut, f.rowid as facid ";
        $sql .= " FROM ".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."facture as f";
        $sql .= " WHERE f.fk_soc = s.idp AND s.idp = ".$societe->id;
        $sql .= " ORDER BY f.datef DESC";
    
        if ( $db->query($sql) )
	  {
	    $var=true;
	    $num = $db->num_rows(); $i = 0;
	    if ($num > 0)
            {
	      print "<tr $bc[$var]>";
	      print '<td colspan="2">&nbsp;</td>';
	      print '<td align="right">'.$langs->trans("Debit").'</td>';
	      print '<td align="right">'.$langs->trans("Credit").'</td>';
	      print '<td align="right">'.$langs->trans("Solde").'</td>';
	      print '</tr>';
            }
	    
	    while ($i < $num)
	      {
		$objp = $db->fetch_object();
		$facs[$i] = $objp->facid;
		$i++;
	      }
	    $db->free();


	    $solde = 0;

	    for ($i = 0 ; $i < $num ; $i++)
	      {
		$var=!$var;

		$fac = new Facture($db);
		$fac->fetch($facs[$i]);

		print "<tr $bc[$var]>";

		print "<td align=\"right\">".dolibarr_print_date($fac->date)."</td>\n";
		print "<td><a href=\"../compta/facture.php?facid=$objp->facid\">".img_object($langs->trans("ShowBill"),"bill")." ".$fac->ref."</a></td>\n";
		
		print '<td align="right">'.number_format($fac->total_ttc, 2, ',', ' ')."</td>\n";
		$solde = $solde + $fac->total_ttc;

		print '<td align="right">&nbsp;</td>';
		print '<td align="right">'.number_format($solde, 2, ',', ' ')."</td>\n";		
		print "</tr>\n";


		$sql = "SELECT p.rowid,".$db->pdate("p.datep")." as dp, p.amount, p.statut";

		$sql .= " FROM ".MAIN_DB_PREFIX."paiement as p";
		$sql .= ", ".MAIN_DB_PREFIX."paiement_facture as pf";
		$sql .= " WHERE pf.fk_paiement = p.rowid";
		$sql .= " AND pf.fk_facture = ".$fac->id;	

		$result = $db->query($sql);
		
		if ($result)
		  {
		    $nump = $db->num_rows();
		    $j = 0; 
		    
		    while ($j < $nump)
		      {
			$objp = $db->fetch_object();
			//$var=!$var;
			print "<tr $bc[$var]>";
			print '<td align="right">'.dolibarr_print_date($objp->dp)."</td>\n";
			print '<td><a href="fiche.php?id='.$objp->rowid.'">'.img_file().' Paiement '.$objp->rowid.'</td>';
			print "<td>&nbsp;</td>\n";
			print '<td align="right">'.price($objp->amount).'</td>';
			$solde = $solde - $objp->amount;
			print '<td align="right">'.number_format($solde, 2, ',', ' ')."</td>\n";
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

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
