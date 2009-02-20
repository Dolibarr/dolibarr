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
  $soc = new Societe($db);
  $result = $soc->fetch($_GET["id"], $user);

  if ($_GET["action"] == 'add')
    {
      $sql = "INSERT INTO ".MAIN_DB_PREFIX."telephonie_societe_commentaire";
      $sql .= " (fk_soc, fk_user, commentaire,datec)";
      $sql .= " VALUES ('".$soc->id."','".$user->id."','".$_POST["comment"]."',now());";
      $db->query($sql);
    }

  if ($_GET["action"] == 'del')
    {
      $sql = "DELETE FROM ".MAIN_DB_PREFIX."telephonie_societe_commentaire";
      $sql .= " WHERE rowid = '".$_GET["commid"]."'";
      $sql .= " AND fk_user = '".$user->id."';";
      $db->query($sql);
    }

  if (!$soc->perm_read)
    {
      print "Lecture non authoris�e";
    }

  if ( $result == 1 && $soc->perm_read)
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
      $hselected = $h;
      $h++;
      
      if ($soc->perm_perms)
	{
	  $head[$h][0] = DOL_URL_ROOT."/telephonie/client/permissions.php?id=".$soc->id;
	  $head[$h][1] = $langs->trans("Permissions");
	  $h++;
	}
      
      dol_fiche_head($head, $hselected, 'Client : '.$soc->nom);
      
      print '<table class="border" cellpadding="3" cellspacing="0" width="100%">';
      print '<tr><td width="20%">'.$langs->trans('Name').'</td><td>'.$soc->nom.'</td><td>'.$langs->trans('Code client').'</td><td>'.$soc->code_client.'</td></tr>';
      
      print "<tr><td valign=\"top\">".$langs->trans('Address')."</td><td colspan=\"3\">".nl2br($soc->adresse)."<br>".$soc->cp." ".$soc->ville." ".$soc->pays."</td></tr>";
      
      print '<tr><td>'.$langs->trans('Phone').'</td><td>'.dol_print_phone($soc->tel,$soc->pays_code,0,$soc->id).'</td>';
      print '<td>'.$langs->trans('Fax').'</td><td>'.dol_print_phone($soc->fax,$soc->pays_code,0,$soc->id).'</td></tr>';
      
      print '<tr><td><a href="'.DOL_URL_ROOT.'/societe/rib.php?socid='.$soc->id.'">'.img_edit() ."</a>&nbsp;";
      print $langs->trans('RIB').'</td><td colspan="3">';
      print $soc->display_rib();
      print '</td></tr>';
      
      print '</table><br />';
      
      print '<form method="POST" action="commentaires.php?id='.$soc->id.'&action=add">';
      print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';	  
      print '<tr><td width="15%" valign="center">Nouveau<br>commentaire';
      print '</td><td><textarea cols="60" rows="3" name="comment"></textarea></td>';
      print '<td><input type="submit" value="Ajouter"></td></tr>';
      print "</table></form><br />";
      
      print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';
      
      /* Commentaires */
      
      $sql = "SELECT c.commentaire, u.firstname, u.name, u.login, c.rowid, c.fk_user";
      $sql .= " , ".$db->pdate("c.datec") ." as datec";
      $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_societe_commentaire as c";
      $sql .= " , ".MAIN_DB_PREFIX."user as u";
      $sql .= " WHERE fk_soc = ".$soc->id;
      $sql .= " AND c.fk_user = u.rowid";
      $sql .= " ORDER BY c.datec DESC";
      
      $resql = $db->query($sql);
      
      if ($resql)
	{
	  print '<tr class="liste_titre"><td width="15%" valign="center">Date';
	  print '</td><td>Commentaire</td><td align="center">Auteur</td><td>&nbsp;</td>';
	  print "</tr>\n";
	  
	  while ($obj = $db->fetch_object($resql))
	    {
	      print "<tr $bc[$var]><td>".strftime("%d/%m/%y %H:%M",$obj->datec);
	      print "</td>\n";
	      print '<td>'.nl2br(stripslashes($obj->commentaire))."</td>\n";
	      print '<td align="center">'.$obj->login."</td>\n";
	      print '<td align="center">&nbsp;';
	      if ($obj->fk_user == $user->id)
		{
		  print '<a href="commentaires.php?id='.$soc->id.'&amp;commid='.$obj->rowid.'&amp;action=del">';
		  print img_delete().'</a>';
		}
	      print "</td></tr>\n";
	      $var=!$var;		  
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
/*
print "\n<br>\n<div class=\"tabsAction\">\n";
print "<a class=\"butAction\" href=\"commentaires.php?action=add&amp;id=$soc->id\">".$langs->trans("Ajouter un commentaire")."</a>";
print "</div>";
*/

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
