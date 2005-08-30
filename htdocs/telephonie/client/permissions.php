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

$soc = new Societe($db);

if ($_GET["id"])
{
  $result = $soc->fetch($_GET["id"], $user);
}

if (!$soc->perm_read)
  accessforbidden();

if (!$soc->perm_perms)
  accessforbidden();

llxHeader("","","Fiche client");

/*
 * Affichage
 *
 */

if ($soc->id)
{
  $h=0;

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
  
  dolibarr_fiche_head($head, $hselected, 'Client : '.$soc->nom);
  
  print '<table class="border" cellpadding="3" cellspacing="0" width="100%">';
  print '<tr><td width="20%">'.$langs->trans('Name').'</td><td>'.$soc->nom.'</td><td>'.$langs->trans('Code client').'</td><td>'.$soc->code_client.'</td></tr>';
  
  
  print "<tr><td valign=\"top\">".$langs->trans('Address')."</td><td colspan=\"3\">".nl2br($soc->adresse)."<br>".$soc->cp." ".$soc->ville." ".$soc->pays."</td></tr>";
  
  print '<tr><td>'.$langs->trans('Phone').'</td><td>'.dolibarr_print_phone($soc->tel).'</td>';
  print '<td>'.$langs->trans('Fax').'</td><td>'.dolibarr_print_phone($soc->fax).'</td></tr>';
   
  print '</table><br />';
  
  print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';
  
  /* Permissions */
  
  $sql = "SELECT u.firstname, u.name, p.pread, p.pwrite, p.pperms";
  $sql .= " FROM ".MAIN_DB_PREFIX."societe_perms as p";
  $sql .= " , ".MAIN_DB_PREFIX."user as u";
  
  $sql .= " WHERE p.fk_user = u.rowid";
  $sql .= " AND p.fk_soc = ".$soc->id;
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
	  
	  print "</tr>\n";
	  
	  while ($i < $num)
	    {
	      $obj = $db->fetch_object($resql);
	      $var=!$var;
	      
	      print "<tr $bc[$var]>";
	      print '<td>'.$obj->firstname." ".$obj->name."</td>\n";
	      
	      print '<td align="center">'.img_allow($obj->pread)."</td>\n";
	      print '<td align="center">'.img_allow($obj->pwrite)."</td>\n";
	      print '<td align="center">'.img_allow($obj->pperms)."</td>\n";
	      
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
