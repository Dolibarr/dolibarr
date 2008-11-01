<?PHP
/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

$mesg = '';

llxHeader("","","Fiche Tarif");

if ($cancel == $langs->trans("Cancel"))
{
  $action = '';
}
/*
 * Affichage
 *
 */
/*
 * Cr�ation
 *
 */


if ($_GET["id"])
{
  
  
  $h=0;
  $head[$h][0] = DOL_URL_ROOT."/telephonie/tarifs/fiche.php?id=".$soc->id;
  $head[$h][1] = $langs->trans("Lignes");
  $hselected = $h;
  $h++;
  
  dolibarr_fiche_head($head, $hselected, 'Tarif : '.$soc->nom);
  
  print '<table class="border" cellpadding="3" cellspacing="0" width="100%">';
  print '<tr><td width="20%">'.$langs->trans('Name').'</td><td>'.$soc->nom.'</td><td>'.$langs->trans('Prefix').'</td><td>'.$soc->prefix_comm.'</td></tr>';
  
  print "<tr><td valign=\"top\">".$langs->trans('Address')."</td><td colspan=\"3\">".nl2br($soc->adresse)."<br>".$soc->cp." ".$soc->ville." ".$soc->pays."</td></tr>";
  
  print '<tr><td>'.$langs->trans('Phone').'</td><td>'.dolibarr_print_phone($soc->tel,$soc->pays_code,0,$soc->id).'</td>';
  print '<td>'.$langs->trans('Fax').'</td><td>'.dolibarr_print_phone($soc->fax,$soc->pays_code,0,$soc->id).'</td></tr>';
  print '<tr><td>'.$langs->trans('Web').'</td><td colspan="3">';
  if ($soc->url) { print '<a href="http://'.$soc->url.'">http://'.$soc->url.'</a>'; }
  print '</td></tr>';
  
  print '<tr><td><a href="'.DOL_URL_ROOT.'/societe/rib.php?socid='.$soc->id.'">'.img_edit() ."</a>&nbsp;";
  print $langs->trans('RIB').'</td><td colspan="3">';
  print $soc->display_rib();
  print '</td></tr>';
  
  print '</table><br />';
  
  print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';
	  
	  //print '<tr><td width="20%">Num�ro</td><td>'.dolibarr_print_phone($ligne->numero).'</td>';
	  //print '<td>Factur�e : '.$ligne->facturable.'</td></tr>';
	  
	  /* Lignes */
	     
	  $sql = "SELECT s.rowid as socid, s.nom, l.ligne, f.nom as fournisseur, l.statut, l.rowid, l.remise";
	  $sql .= " FROM ".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."telephonie_societe_ligne as l";
	  $sql .= " , ".MAIN_DB_PREFIX."telephonie_fournisseur as f";
	  $sql .= " WHERE l.fk_soc = s.rowid AND l.fk_fournisseur = f.rowid";
	  $sql .= " AND s.rowid = ".$soc->id;
	  
	  if ( $db->query( $sql) )
	    {
	      $num = $db->num_rows();
	      if ( $num > 0 )
		{
		  $i = 0;

		  $ligne = new LigneTel($db);

		  print '<tr class="liste_titre"><td width="15%" valign="center">Ligne';
		  print '</td><td align="center">Statut</td><td align="center">Remise LMN';
		  print '</td><td>Fournisseur</td>';

		  print "</tr>\n";

		  while ($i < $num)
		    {
		      $obj = $db->fetch_object($i);	
		      $var=!$var;

		      print "<tr $bc[$var]><td>";

		      print '<img src="./graph'.$obj->statut.'.png">&nbsp;';
      
		      print '<a href="'.DOL_URL_ROOT.'/telephonie/ligne/fiche.php?id='.$obj->rowid.'">';
		      print img_file();
      
		      print '</a>&nbsp;';

		      print '<a href="'.DOL_URL_ROOT.'/telephonie/ligne/fiche.php?id='.$obj->rowid.'">'.dolibarr_print_phone($obj->ligne,0,0,true)."</a></td>\n";

		      print '<td align="center">'.$ligne->statuts[$obj->statut]."</td>\n";

		      print '<td align="center">'.$obj->remise." %</td>\n";
		      print "<td>".$obj->fournisseur."</td>\n";
		      print "</tr>\n";
		      $i++;
		    }
		}
	      $db->free();     
	      
	    }
	  else
	    {
	      print $sql;
	    }
	  
	  print "</table>";
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
