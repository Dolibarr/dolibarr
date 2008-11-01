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

$mesg = '';

llxHeader("","","Fiche client");

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
  $soc = new TelephonieClient($db);
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
	  $hselected = $h;
	  $h++;

	  $head[$h][0] = DOL_URL_ROOT."/telephonie/client/lignes.php?id=".$soc->id;
	  $head[$h][1] = $langs->trans("Lignes");
	  $hselected = $h;
	  $h++;

	  $head[$h][0] = DOL_URL_ROOT."/telephonie/client/factures.php?id=".$soc->id;
	  $head[$h][1] = $langs->trans("Factures");
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

	  $head[$h][0] = DOL_URL_ROOT."/telephonie/client/commentaires.php?id=".$soc->id;
	  $head[$h][1] = $langs->trans("Commentaires");
	  $num_comments = $soc->count_comment();
	  if ($num_comments > 0)
	    {
	      $head[$h][1] = $langs->trans("Commentaires ($num_comments)");
	    }
	  $h++;


	  dolibarr_fiche_head($head, $hselected, 'Client : '.$soc->nom);

	  print '<table class="border" cellpadding="3" cellspacing="0" width="100%">';
	  print '<tr><td width="20%">'.$langs->trans('Name').'</td><td>'.$soc->nom.'</td><td>'.$langs->trans('Code client').'</td><td>'.$soc->code_client.'</td></tr>';

	  
	  print "<tr><td valign=\"top\">".$langs->trans('Address')."</td><td colspan=\"3\">".nl2br($soc->adresse)."<br>".$soc->cp." ".$soc->ville." ".$soc->pays."</td></tr>";
	  
	  print '<tr><td>'.$langs->trans('Phone').'</td><td>'.dolibarr_print_phone($soc->tel,$soc->pays_code,0,$soc->id).'</td>';
	  print '<td>'.$langs->trans('Fax').'</td><td>'.dolibarr_print_phone($soc->fax,$soc->pays_code,0,$soc->id).'</td></tr>';
	  	  	  
	  print '</table><br />';

	  print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';
	  
	  //print '<tr><td width="20%">Num�ro</td><td>'.dolibarr_print_phone($ligne->numero).'</td>';
	  //print '<td>Factur�e : '.$ligne->facturable.'</td></tr>';
	  
	  /* Lignes */
	     
	  $sql = "SELECT s.rowid as socid, f.nom as fournisseur, s.nom";
	  $sql .= ", ss.nom as agence, ss.ville, ss.code_client";
	  $sql .= " , l.ligne,  l.statut, l.rowid, l.remise";
	  $sql .= " FROM ".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."telephonie_societe_ligne as l";
	  $sql .= " , ".MAIN_DB_PREFIX."societe as ss";
	  $sql .= " , ".MAIN_DB_PREFIX."telephonie_fournisseur as f";
	  $sql .= " WHERE l.fk_client_comm = s.rowid AND l.fk_fournisseur = f.rowid";
	  $sql .= " AND l.fk_soc = ss.rowid ";
	  $sql .= " AND s.rowid = ".$soc->id;
	  $sql .= " ORDER BY ss.rowid ASC";

	  $resql =  $db->query($sql);
	  
	  if ($resql)
	    {
	      $num = $db->num_rows($resql);
	      if ( $num > 0 )
		{
		  $i = 0;

		  $ligne = new LigneTel($db);

		  print '<tr class="liste_titre"><td width="15%" valign="center">Ligne';
		  print '</td><td colspan="3">Agence/Filiale</td>';
		  print '<td align="center">Remise LMN</td>';
		  if ($user->rights->telephonie->fournisseur->lire)
		    print '<td>Fournisseur</td>';

		  print "</tr>\n";

		  while ($i < $num)
		    {
		      $obj = $db->fetch_object($resql);
		      $var=!$var;

		      print "<tr $bc[$var]><td>";

		      print '<img src="../graph'.$obj->statut.'.png">&nbsp;';
      
		      print '<a href="'.DOL_URL_ROOT.'/telephonie/ligne/fiche.php?id='.$obj->rowid.'">';
		      print img_file();
      
		      print '</a>&nbsp;';

		      print '<a href="'.DOL_URL_ROOT.'/telephonie/ligne/fiche.php?id='.$obj->rowid.'">'.dolibarr_print_phone($obj->ligne,0,0,true)."</a></td>\n";

		      print '<td>'.$obj->code_client."</td>\n";
		      print '<td>'.$obj->agence."</td>\n";
		      print '<td>'.$obj->ville."</td>\n";

		      print '<td align="center">'.$obj->remise." %</td>\n";
		      if ($user->rights->telephonie->fournisseur->lire)
			print "<td>".$obj->fournisseur."</td>\n";
		      print "</tr>\n";
		      $i++;
		    }
		}
	      $db->free($resql);
	      
	    }
	  else
	    {
	      print $sql;
	    }
	  
	  print "</table>";
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
