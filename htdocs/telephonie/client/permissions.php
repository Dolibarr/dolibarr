<?PHP
/* Copyright (C) 2005-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

$soc = new Societe($db);

if ($_GET["id"])
{
  $result = $soc->fetch($_GET["id"], $user);
}

if (!$soc->perm_read)
  accessforbidden();

if (!$soc->perm_perms)
  accessforbidden();

if ($_GET["action"] == 'inv')
{
  $sql = "UPDATE ".MAIN_DB_PREFIX."societe_perms";
  $sql .= " SET p".$_GET["p"]." = !p".$_GET["p"];
  $sql .= " WHERE fk_user=".$_GET["u"]." AND fk_soc=".$_GET["id"];

  if ($resql =  $db->query($sql))
    {
      Header("Location: permissions.php?id=$soc->id");
    }
}

if ($_POST["action"] == 'add')
{
  $sql = "INSERT INTO ".MAIN_DB_PREFIX."societe_perms";
  $sql .= " (fk_soc,fk_user,pread,pwrite,pperms) VALUES";
  $sql .= " (".$_GET["id"].",".$_POST["new_user"].",";
  $sql .=  $_POST["read"]=='on'?"1,":"0,";
  $sql .=  $_POST["read"]=='on'?"1,":"0,";
  $sql .=  $_POST["read"]=='on'?"1);":"0);";

  if ($resql =  $db->query($sql))
    {
      Header("Location: permissions.php?id=$soc->id");
    }
}

llxHeader("","","Fiche client");

/*
 * Affichage
 *
 */

if ($soc->id)
{
  $h=0;
  $form = new Form($db);

  $head[$h][0] = DOL_URL_ROOT."/telephonie/client/fiche.php?id=".$soc->id;
  $head[$h][1] = $langs->trans("Contrats");
  $hselected = $h;
  $h++;

  $head[$h][0] = DOL_URL_ROOT."/telephonie/client/lignes.php?id=".$soc->id;
  $head[$h][1] = $langs->trans("Lignes");
  $h++;
  
  $head[$h][0] = DOL_URL_ROOT."/telephonie/client/factures.php?id=".$soc->id;
  $head[$h][1] = $langs->trans("Factures");
  $h++;
  
  $head[$h][0] = DOL_URL_ROOT."/telephonie/client/ca.php?id=".$soc->id;
  $head[$h][1] = $langs->trans("CA");
  $h++;
  
  $head[$h][0] = DOL_URL_ROOT."/telephonie/client/tarifs.php?id=".$soc->id;
  $head[$h][1] = $langs->trans("Tarifs");
  $h++;
  
  $head[$h][0] = DOL_URL_ROOT."/telephonie/client/permissions.php?id=".$soc->id;
  $head[$h][1] = $langs->trans("Permissions");
  $hselected = $h;
  $h++;
  
  dol_fiche_head($head, $hselected, 'Client : '.$soc->nom);
  
  print '<table class="border" cellpadding="3" cellspacing="0" width="100%">';
  print '<tr><td width="20%">'.$langs->trans('Name').'</td><td>'.$soc->nom.'</td><td>'.$langs->trans('Code client').'</td><td>'.$soc->code_client.'</td></tr>';
  
  print "<tr><td valign=\"top\">".$langs->trans('Address')."</td><td colspan=\"3\">".nl2br($soc->adresse)."<br>".$soc->cp." ".$soc->ville." ".$soc->pays."</td></tr>";
  
  print '<tr><td>'.$langs->trans('Phone').'</td><td>'.dol_print_phone($soc->tel,$soc->pays_code,0,$soc->id).'</td>';
  print '<td>'.$langs->trans('Fax').'</td><td>'.dol_print_phone($soc->fax,$soc->pays_code,0,$soc->id).'</td></tr>';
   
  print '</table><br />';
  print '<form method="POST" action="permissions.php?id='.$soc->id.'">';
  print '<input type="hidden" name="token_level_1" value="'.$_SESSION['newtoken'].'">';
  print '<input type="hidden" name="action" value="add">';
  print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';
  
  /* Permissions du user en cours */
  $sql = "SELECT p.pread, p.pwrite, p.pperms";
  $sql .= " FROM ".MAIN_DB_PREFIX."societe_perms as p";  
  $sql .= " WHERE p.fk_soc=".$soc->id." AND p.fk_user=".$user->id.";";
  
  if ($resql =  $db->query($sql))
    {
      $num = $db->num_rows($resql);
      if ( $num > 0 )
	{
	  $obj = $db->fetch_object($resql);
	  $read = $obj->pread;
	  $write = $obj->pwrite;
	  $perms = $obj->pperms;
	}
      $db->free($resql);
    }
  else
    {
      print $sql;
    }

  /* Ajout un user */
  $uss = array();
  $sql = "SELECT u.rowid, u.firstname, u.name";
  $sql .= " FROM ".MAIN_DB_PREFIX."user as u, ".MAIN_DB_PREFIX."usergroup_user as ug";
  $sql .= " WHERE u.rowid = ug.fk_user";
  $sql .= " AND ug.fk_usergroup = '".TELEPHONIE_GROUPE_COMMERCIAUX_ID."'";
  $sql .= " ORDER BY name ";
  if ( $resql = $db->query( $sql) )
    {
      while ($row = $db->fetch_row($resql))
	{
	  $uss[$row[0]] = $row[1] . " " . $row[2];
	}
      $db->free($resql);
    }
  
  /* Permissions */
  $sql = "SELECT u.rowid,u.firstname, u.name, p.pread, p.pwrite, p.pperms";
  $sql .= " FROM ".MAIN_DB_PREFIX."societe_perms as p";
  $sql .= " , ".MAIN_DB_PREFIX."user as u";  
  $sql .= " WHERE p.fk_user = u.rowid AND p.fk_soc = ".$soc->id;
  $sql .= " ORDER BY u.name ASC";
  
  $resql =  $db->query($sql);
  
  if ($resql)
    {
      $num = $db->num_rows($resql);
      if ( $num > 0 )
	{
	  $i = 0;	  
	  $ligne = new LigneTel($db);
	  
	  print '<tr class="liste_titre">';
	  print '<td>Utilisateur</td>';
	  print '<td align="center">Lecture</td>';
	  print '<td align="center">Ecriture</td>';
	  print '<td align="center">Permissions</td>';	  
	  print "<td>&nbsp;</td></tr>\n";
	  

	  print '<tr class="liste_titre">';
	  print '<td>';
	  $form->select_array("new_user",$uss);
	  print '</td>';
	  print '<td align="center"><input name="read" type="checkbox"></td>';
	  print '<td align="center"><input name="write" type="checkbox"></td>';
	  print '<td align="center"><input name="perm" type="checkbox"></td>';
	  print '<td align="center"><input type="submit" value="Ajouter"></td>';
	  print "</tr>\n";

	  while ($i < $num)
	    {
	      $obj = $db->fetch_object($resql);
	      $var=!$var;
	      
	      print "<tr $bc[$var]>";
	      print '<td>'.$obj->firstname." ".$obj->name."</td>\n";

	      if ($perms == 1)
		{
		  print '<td align="center"><a href="permissions.php?id='.$soc->id.'&amp;u='.$obj->rowid.'&amp;p=read&amp;action=inv">'.img_allow($obj->pread)."</a></td>\n";
		  print '<td align="center"><a href="permissions.php?id='.$soc->id.'&amp;u='.$obj->rowid.'&amp;p=write&amp;action=inv">'.img_allow($obj->pwrite)."</td>\n";
		  print '<td align="center"><a href="permissions.php?id='.$soc->id.'&amp;u='.$obj->rowid.'&amp;p=perms&amp;action=inv">'.img_allow($obj->pperms)."</td>\n";	      
		}
	      else
		{
		  print '<td align="center">'.img_allow($obj->pread)."</td>\n";
		  print '<td align="center">'.img_allow($obj->pwrite)."</td>\n";
		  print '<td align="center">'.img_allow($obj->pperms)."</td>\n";
		}
	      print "<td>&nbsp;</td></tr>\n";
	      $i++;
	    }
	}
      $db->free($resql);
      
    }
  else
    {
      print $sql;
    }
  print "</table></form>";
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
