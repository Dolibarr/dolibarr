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

llxHeader("","Téléphonie - Factures client");

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
  $result = $soc->fetch($_GET["id"]);

  if ( $result == 1 )
    { 
      if ($_GET["action"] <> 'edit' && $_GET["action"] <> 're-edit')
	{
	  $h=0;
	  $head[$h][0] = DOL_URL_ROOT."/telephonie/client/fiche.php?id=".$soc->id;
	  $head[$h][1] = $langs->trans("Contrats");
	  $h++;

	  $head[$h][0] = DOL_URL_ROOT."/telephonie/client/fiche.php?id=".$soc->id;
	  $head[$h][1] = $langs->trans("Lignes");
	  $h++;

	  $head[$h][0] = DOL_URL_ROOT."/telephonie/client/factures.php?id=".$soc->id;
	  $head[$h][1] = $langs->trans("Factures");
	  $hselected = $h;
	  $h++;

	  $head[$h][0] = DOL_URL_ROOT."/telephonie/client/ca.php?id=".$soc->id;
	  $head[$h][1] = $langs->trans("CA");
	  $h++;
	  	
	  $head[$h][0] = DOL_URL_ROOT."/telephonie/client/tarifs.php?id=".$soc->id;
	  $head[$h][1] = $langs->trans("Tarifs");
	  $h++;


	  dolibarr_fiche_head($head, $hselected, 'Client : '.$soc->nom);

	  print '<table class="border" cellpadding="3" cellspacing="0" width="100%">';
	  print '<tr><td width="20%">'.$langs->trans('Name').'</td><td>'.$soc->nom.'</td><td>'.$langs->trans('Code Client').'</td><td>'.$soc->code_client.'</td></tr>';
	  
	  print "<tr><td valign=\"top\">".$langs->trans('Address')."</td><td colspan=\"3\">".nl2br($soc->adresse)."<br>".$soc->cp." ".$soc->ville." ".$soc->pays."</td></tr>";
	  
	  print '<tr><td>'.$langs->trans('Phone').'</td><td>'.dolibarr_print_phone($soc->tel).'</td>';
	  print '<td>'.$langs->trans('Fax').'</td><td>'.dolibarr_print_phone($soc->fax).'</td></tr>';
	  	  
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
	  
	  $sql = "SELECT f.rowid, f.date, f.ligne, f.fourn_montant, f.cout_vente, f.cout_vente_remise, f.gain, f.fk_facture";
	  $sql .= " ,s.nom, s.idp";
	  $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_facture as f";
	  $sql .= " , ".MAIN_DB_PREFIX."societe as s";
	  $sql .= " , ".MAIN_DB_PREFIX."telephonie_societe_ligne as l";
	  
	  $sql .= " WHERE s.idp = l.fk_soc_facture AND l.rowid = f.fk_ligne";
	  $sql .= " AND s.idp = ".$soc->id;	  
	  $sql .= " ORDER BY $sortfield $sortorder " . $db->plimit($conf->liste_limit+1, $offset);
	  
	  $result = $db->query($sql);
	  if ($result)
	    {
	      $num = $db->num_rows();
	      $i = 0;
	      	      
	      print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';
	      print '<tr class="liste_titre"><td>Ligne</td>';	      
	      print '<td align="center">Date</td><td align="right">Montant HT</td>';
	      print '<td align="right">Coût fournisseur HT';
	      print '</td><td align="right">Gain</td><td align="center">Facture</td>';
	      print "</tr>\n";
	      	      
	      $var=True;
	      	      
	      while ($i < min($num,$conf->liste_limit))
		{
		  $obj = $db->fetch_object();
		  $var=!$var;
		  
		  print "<tr $bc[$var]>";

		  print '<td><a href="'.DOL_URL_ROOT.'/telephonie/ligne/fiche.php?numero='.$obj->ligne.'">'.dolibarr_print_phone($obj->ligne)."</a></td>\n";
		  print '<td align="center">'.$obj->date."</td>\n";
		  print '<td align="right">'.sprintf("%01.4f",$obj->cout_vente_remise)."</td>\n";
		  print '<td align="right">'.sprintf("%01.4f",$obj->fourn_montant)."</td>\n";
		  
		  print '<td align="right">';
		  if ($obj->gain < 0 && $obj->cout_vente_remise)
		    {
		      print '<font color="red"><b>';
		      print sprintf("%01.4f",$obj->gain);
		      print "</b></font>";
		    }
		  else
		    {
		      print sprintf("%01.4f",$obj->gain);
		    }
		  print "</td>\n";
		  print '<td align="center"><a href="'.DOL_URL_ROOT.'/compta/facture.php?facid='.$obj->fk_facture.'">'.$obj->fk_facture."</a></td>\n";
		  print "</tr>\n";
		  $i++;
		}
	      print "</table>";
	      $db->free();
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
