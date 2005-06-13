<?php
/** **************************************************************************
 * Copyright (C) 2005 Eric Seigne <eric.seigne@ryxeo.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 * ***************************************************************************
 * File  : searchpostalcode.php
 * Author  : Eric SEIGNE
 *           mailto:eric.seigne@ryxeo.com
 *           http://www.ryxeo.com/
 * Date    : 13/06/2005
 * Licence : GNU/GPL Version 2
 *
 * Description:
 * ------------
 *
 * @version    $Id$
 * @source     $Source$
 * @revision   $Revision$
 * @author     Eric Seigne
 * @project   
 * @copyright  Eric Seigne 13/06/2005
 *
 * ************************************************************************* */

/**
   \file       htdocs/searchpostalcode.php
   \ingroup    societe
   \brief      Recherche de la ville correspondant au code postal saisi
   \version    $Revision$
*/

require("pre.inc.php");
$user->getrights('societe');
$langs->load("companies");


/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $_GET["action"] = '';
  $_POST["action"] = '';
  $_GET["socid"] = $user->societe_id;
}

print "
<script language=\"JavaScript\">
<!--
function MAJ(target)
{
  var e2 = \"\";
  for (var i = 0; i < document.villes.elements.length && e2 == \"\"; i++)
  {
    var e = document.villes.elements[i];
    if (e.checked){
      e2 = e.value;
      target.value = unescape(e2);
    }
  }
  window.close();
}

function change_categorie(urlbase,leselect)
{
  if (leselect.options[leselect.selectedIndex].value!=\"dummy\")
  eval(\"location='\"+urlbase+\"?c=\"+leselect.options[leselect.selectedIndex].value+\"&objet=$objet'\");
}
//-->
</script>\n";

print "<form method=\"post\" action=\"javascript:MAJ(" . $_GET['targetobject'] . ");\" name=\"villes\" enctype=\"application/x-www-form-urlencoded\">
<table border=\"0\" align=\"center\" width=\"90%\" cellpadding=\"0\" cellspacing=\"0\">
<tr>
  <td colspan=\"3\" bgcolor=\"#002266\" align=\"center\">
    <font color=\"#EEEEFF\" face=\"Arial, Helvetica\" size=\"3\"><b>Recherche code postal: " . $_GET['cp'] . " </b></font>
  </td>
</tr>\n";

$sql = "SELECT ville,postalcode from ".MAIN_DB_PREFIX."postalcode";
if(isset($_GET['cp']) && trim($_GET['cp']) != "") {
  $sql .= " where postalcode ";
  if(strstr($_GET['cp'],'%'))
    $sql .="LIKE";
  else
    $sql .="=";
  $sql .= " '" . $_GET['cp'] . "'";
}
else {
  $sql .= " LIMIT 30";
}
$result=$db->query($sql);
if (!$result) {
  dolibarr_print_error($db);
}

$num=$db->num_rows();
//Si on n'a qu'un seul résultat on switche direct et on remplit le formulaire
if($num <= 1) {
  $obj = $db->fetch_object($result);
  $ville = $obj->ville;
  $ville_code = urlencode("$ville");  
  print "<input type=\"radio\" name=\"choix\" value=\"$ville_code\" checked>
<script language=\"javascript\">
document.villes.submit();
</script>\n";
}
else {
  // sinon on affiche la liste des villes dont c'est le code postal ...
  for($i = 0; $i < $num; $i++){
   $obj = $db->fetch_object($result);
   $ville = $obj->ville;
   $ville_code = urlencode("$ville");
   if(strstr($_GET['cp'],'%') || trim($_GET['cp'])=="")
     $cp = "(" . $obj->postalcode . ")";
   else
     $cp = "";

   if($bgcolor=="#DDDDFF")
    $bgcolor="#EEEEFF";
   else
    $bgcolor="#DDDDFF";
  
   print "<tr>
  <td bgcolor=\"$bgcolor\" width=\"10%\">
    <label><input type=\"radio\" name=\"choix\" value=\"$ville\"> $ville $cp</label>
  </td>
</tr>
<tr>\n";
  }
}

print "    <input type=\"hidden\" name=\"nb_i\" value=\"$i\">

<tr>
  <td align=\"center\" colspan=\"3\" bgcolor=\"#DDDDFF\">
    <input type=\"submit\" name=\"envoyer\" value=\"OK\"> - 
    <input type=\"button\" value=\"Annuler\" onClick=\"window.close();\">
  </td>
</tr>
</table>
</form>\n";

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
