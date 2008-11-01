<?PHP
/* Copyright (C) 2004-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

llxHeader("","T�l�phonie - Factures client");

if ($cancel == $langs->trans("Cancel"))
{
  $action = '';
}
/*
 * Affichage
 *
 */

if ($_GET["id"])
{
  $soc = new Societe($db);
  $result = $soc->fetch($_GET["id"], $user);

  if (!$soc->perm_read)
    {
      print "Lecture non authoris�e";
    }

  if ( $result == 1 && $soc->perm_read)
    { 
      if ($_GET["action"] <> 'edit' && $_GET["action"] <> 're-edit')
	{
	  $h=0;
	  $head[$h][0] = DOL_URL_ROOT."/telephonie/client/fiche.php?id=".$soc->id;
	  $head[$h][1] = $langs->trans("Contrats");
	  $h++;

	  $head[$h][0] = DOL_URL_ROOT."/telephonie/client/lignes.php?id=".$soc->id;
	  $head[$h][1] = $langs->trans("Lignes");
	  $h++;

	  $head[$h][0] = DOL_URL_ROOT."/telephonie/client/factures.php?id=".$soc->id;
	  $head[$h][1] = $langs->trans("Factures");
	  $hselected = $h;
	  $h++;

	  $head[$h][0] = DOL_URL_ROOT."/telephonie/client/stats.php?id=".$soc->id;
	  $head[$h][1] = $langs->trans("Stats");
	  $h++;

	  $sql = "SELECT count(*) FROM ".MAIN_DB_PREFIX."telephonie_tarif_client";
	  $sql .= " WHERE fk_client = '".$soc->id."';";
	  $resql = $db->query($sql);

	  if ($resql)
	    {
	      $row = $db->fetch_row($resql);
	      $db->free($resql);
	    }

	  $head[$h][0] = DOL_URL_ROOT."/telephonie/client/tarifs.php?id=".$soc->id;
	  $head[$h][1] = $langs->trans("Tarifs (".$row[0].")");	       
	  $h++;
	  	
	  dolibarr_fiche_head($head, $hselected, 'Client : '.$soc->nom);

	  print '<table class="border" cellpadding="3" cellspacing="0" width="100%">';
	  print '<tr><td width="20%">'.$langs->trans('Name').'</td><td>'.$soc->nom.'</td><td>'.$langs->trans('Code Client').'</td><td>'.$soc->code_client.'</td></tr>';
	  
	  print "<tr><td valign=\"top\">".$langs->trans('Address')."</td><td colspan=\"3\">".nl2br($soc->adresse)."<br>".$soc->cp." ".$soc->ville." ".$soc->pays."</td></tr>";
	  
	  print '<tr><td>'.$langs->trans('Phone').'</td><td>'.dolibarr_print_phone($soc->tel,$soc->pays_code,0,$soc->id).'</td>';
	  print '<td>'.$langs->trans('Fax').'</td><td>'.dolibarr_print_phone($soc->fax,$soc->pays_code,0,$soc->id).'</td></tr>';
	  	  
	  print '</table><br />';

	  /*
	   * Factures
	   *
	   */

	  if ($page == -1) { $page = 0 ; }

	  if ($sortorder == "") {
	    $sortorder="DESC";
	  }
	  if ($sortfield == "") {
	    $sortfield="f.date";
	  }
	  $offset = $conf->liste_limit * $page ;
	  $pageprev = $page - 1;
	  $pagenext = $page + 1;
	  
	  $sql = "SELECT f.rowid, f.date, sum(f.cout_vente) as cout_vente, f.fk_facture";
	  $sql .= " ,s.nom, s.rowid as socid";
	  $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_facture as f";
	  $sql .= " , ".MAIN_DB_PREFIX."societe as s";
	  $sql .= " , ".MAIN_DB_PREFIX."telephonie_societe_ligne as l";
	  
	  $sql .= " WHERE s.rowid = l.fk_soc_facture AND l.rowid = f.fk_ligne";
	  $sql .= " AND s.rowid = ".$soc->id;
	  $sql .= " GROUP BY f.fk_facture";
	  $sql .= " ORDER BY $sortfield $sortorder " . $db->plimit($conf->liste_limit+1, $offset);
	  
	  $resql = $db->query($sql);
	  if ($resql)
	    {
	      $num = $db->num_rows($resql);
	      $i = 0;
	      	      
	      print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';
	      print '<tr class="liste_titre">';	      
	      print '<td align="center">Date</td><td align="right">Montant HT</td>';
	      print '<td align="center">Facture</td>';
	      print "</tr>\n";
	      	      
	      $var=True;
	      	      
	      while ($i < min($num,$conf->liste_limit))
		{
		  $obj = $db->fetch_object($resql);
		  $var=!$var;
		  
		  print "<tr $bc[$var]>";

		  print '<td align="center">'.$obj->date."</td>\n";
		  print '<td align="right">'.sprintf("%01.4f",$obj->cout_vente)."</td>\n";

		  print '<td align="center"><a href="facture.php?facid='.$obj->fk_facture.'">'.$obj->fk_facture."</a></td>\n";
		  print "</tr>\n";
		  $i++;
		}
	      print "</table>";
	      $db->free($resql);
	    }
	  else 
	    {
	      print $db->error() . ' ' . $sql;
	    }	  
	}
    }
}
else
{
  print "Error";
}

print '</div>';

/* ************************************************************************** */
/*                                                                            */ 
/* Barre d'action                                                             */ 
/*                                                                            */ 
/* ************************************************************************** */

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
