<?PHP
/* Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004 Benoit Mortier <benoit.mortier@opensides.be>
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
 */
require("./pre.inc.php");

$acts[0] = "add";
$acts[1] = "delete";
$actl[0] = "Activer";
$actl[1] = "Désactiver";

$active = 1;

// Mettre ici tous les caractéristiques des dictionnaires editables
$tabid[1] = "llx_c_forme_juridique";
$tabid[2] = "llx_c_departements";
$tabid[3] = "llx_c_regions";
$tabid[4] = "llx_c_pays";
$tabid[5] = "llx_c_civilite";

$tabnom[1] = "Formes juridiques";
$tabnom[2] = "Départements/Provinces/Cantons";
$tabnom[3] = "Régions";
$tabnom[4] = "Pays";
$tabnom[5] = "Formules de Politesses";

$tabsql[1] = "SELECT f.code, f.libelle, p.libelle as pays, f.active FROM llx_c_forme_juridique as f, llx_c_pays as p WHERE f.fk_pays=p.rowid ORDER BY p.rowid, f.active DESC, code ASC";
$tabsql[2] = "SELECT d.rowid as rowid, d.code_departement as code , d.nom as libelle, p.libelle as pays, d.active FROM llx_c_departements as d, llx_c_regions as r, llx_c_pays as p WHERE d.fk_region=r.code_region and r.fk_pays=p.rowid ORDER BY p.rowid, d.active DESC, code ASC";
$tabsql[3] = "SELECT r.rowid as rowid, code_region as code , nom as libelle, p.libelle as pays, r.active FROM llx_c_regions as r, llx_c_pays as p WHERE r.fk_pays=p.rowid ORDER BY p.rowid, r.active DESC, code ASC";
$tabsql[4] = "SELECT rowid, code, libelle, active FROM llx_c_pays ORDER BY active DESC, code ASC";

$tabsql[5] = "SELECT c.rowid AS rowid, c.civilite AS libelle, c.active
FROM llx_c_civilite AS c, llx_c_pays AS p
WHERE c.fk_pays = p.rowid";


// Champs à afficher
$tabfield[1] = "code,libelle,pays";
$tabfield[2] = "code,libelle,pays";
$tabfield[3] = "code,libelle,pays";
$tabfield[4] = "code,libelle";
$tabfield[5] = "libelle";

if (! $user->admin)
  accessforbidden();


if ($_GET["action"] == 'delete')
{
  if ($_GET["rowid"] >0) {
    $sql = "UPDATE ".$tabid[$_GET["id"]]." SET active = 0 WHERE rowid=".$_GET["rowid"];
  }
  elseif ($_GET["code"] >0) {
    $sql = "UPDATE ".$tabid[$_GET["id"]]." SET active = 0 WHERE code=".$_GET["code"];
  }
  
  $result = $db->query($sql);
  if (!$result)
{
  print $db->error();
}
}
if ($_GET["action"] == 'add')
{
  $sql = "UPDATE ".$tabid[$_GET["id"]]." SET active = 1 WHERE rowid=".$_GET["rowid"];
  
  $result = $db->query($sql);
  if (!$result)
    {
  print $db->error();
    }
}



llxHeader();

if ($_GET["id"])
{
    print_titre("Configuration des dictionnaires de données : ".$tabnom[$_GET["id"]]);
    print '<br>';

    // Affiche table des valeurs
  $sql=$tabsql[$_GET["id"]];
  if ($db->query($sql))
    {
    $num = $db->num_rows();
    $i = 0;
    $var=True;
    if ($num)
    {
	  print '<table class="noborder" cellpadding="3" cellspacing="0" width="100%">';
	  print '<tr class="liste_titre">';

	  $fieldlist=split(',',$tabfield[$_GET["id"]]);
      foreach ($fieldlist as $field => $value) {
	    print '<td>'.ucfirst($fieldlist[$field]).'</td>';
	  }

	  print '<td>Actif</td>';
	  print '<td>Inactif</td>';
	  print '</tr>';      
	  while ($i < $num)
	    {
	      $obj = $db->fetch_object($i);
	      $var=!$var;
	      
	      print "<tr $bc[$var] class=\"value\">";

          foreach ($fieldlist as $field => $value) {
	        print '<td>'.$obj->$fieldlist[$field].'</td>';
	      }

	      if ($obj->active) {
	        print '<td>';
	        print '<a href="'.$PHP_SELF.'?rowid='.$obj->rowid.'&amp;code='.$obj->code.'&amp;id='.$_GET["id"].'&amp;action='.$acts[$obj->active].'">'.$actl[$obj->active].'</a>';
	        print "</td>";
          }
          else print '<td>&nbsp;</td>';
  	      if (! $obj->active) {
    	      print '<td>';
    	      print '<a href="'.$PHP_SELF.'?rowid='.$obj->rowid.'&amp;code='.$obj->code.'&amp;id='.$_GET["id"].'&amp;action='.$acts[$obj->active].'">'.$actl[$obj->active].'</a>';
    	      print "</td>";
          }
          else print '<td>&nbsp;</td>';
          	      
	      print "</tr>\n";
	      $i++;
	    }
	  print '</table>';
	}
    }
  else {
    print "Erreur : $sql : ".$db->error(); 
  }
}
else
{
    print_titre("Configuration des dictionnaires de données");
    print '<br>';
    
    foreach ($tabid as $i => $value) {
        print '<a href="dict.php?id='.$i.'">'.$tabnom[$i].'</a> (Table '.$tabid[$i].')<br>';
    }
}

print '<br>';

$db->close();

llxFooter();


?>
