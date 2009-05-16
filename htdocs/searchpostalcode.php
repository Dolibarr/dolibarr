<?php
/* Copyright (C) 2005 Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
        \brief      Recherche de la ville correspondant au code postal saisi. 1er tour on cherche dans la table societé, si on a deux clients dans la même ville c'est direct. Si jamais ça ne donne rien alors on lance la recherche dans la table des codes postaux.
        \version    $Revision$
*/
require("pre.inc.php");

$langs->load("companies");


function run_request($table)
{
    global $db;
	$cp=isset($_GET["cp"])?trim($_GET["cp"]):'';
	$cp=eregi_replace('\*','%',$cp);
	
    $sql = "SELECT DISTINCT ville, cp";
    $sql.= " FROM ".MAIN_DB_PREFIX.$table;
    $sql.= " WHERE cp ".($cp?"LIKE":"=")." '".addslashes($cp)."'";
	$sql.= " ORDER by ville";
	
    $result=$db->query($sql);
    if (!$result)
    {
        dol_print_error($db);
    }
    //  print $sql;
}


// Sécurité accés client
if ($user->societe_id > 0) 
{
    $_GET["action"] = '';
    $_POST["action"] = '';
    $_GET["socid"] = $user->societe_id;
}



top_htmlhead("", $langs->trans("SearchTown"));

print "<body>";

print "
<script language=\"JavaScript\" type=\"text/javascript\">
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


print "<div><div><br>";    // Ouvre 3 div a la place de top_menu car le llxFooter en ferme 3

print "<form method=\"post\" action=\"javascript:MAJ(" . $_GET['targetobject'] . ");\" name=\"villes\" enctype=\"application/x-www-form-urlencoded\">";
print '<input type="hidden" name="token_level_1" value="'.$_SESSION['newtoken'].'">';
print "<table class=\"noborder\" align=\"center\" width=\"90%\">";
print "<tr class=\"liste_titre\">";
print "  <td colspan=\"3\" align=\"center\">";
print "  <b>Recherche code postal: " . $_GET['cp'] . " </b>";
print "  </td>";
print "</tr>\n";

run_request("societe");

$num=$db->num_rows();
if($num == 0)
{
	run_request("postalcode");
	$num=$db->num_rows();
}

// Si on n'a qu'un seul résultat on switche direct et on remplit le formulaire
if($num <= 1)
{
	$obj = $db->fetch_object($result);
	$ville = $obj->ville;
	$ville_code = urlencode("$ville");  
    print "<tr ".$bc[$var]."><td width=\"10%\">";
	print "<input type=\"radio\" name=\"choix\" value=\"$ville\" checked>";
	print "<script language=\"javascript\" type=\"text/javascript\">document.villes.submit();</script>\n";
    print "</td></tr>";
}
else
{
    // Sinon on affiche la liste des villes dont c'est le code postal ...
    for ($i = 0; $i < $num; $i++)
    {
        $obj = $db->fetch_object($result);
        $ville = $obj->ville;
        $ville_code = urlencode("$ville");
        if ($obj->cp)
        	$cp = "(" . $obj->cp . ")";
        else
        	$cp = "";

        if($bgcolor=="#DDDDFF")
        $bgcolor="#EEEEFF";
        else
        $bgcolor="#DDDDFF";

        $var=!$var;
        print "<tr ".$bc[$var]."><td width=\"10%\">";
        print "<input type=\"radio\" name=\"choix\" value=\"$ville\"> $ville $cp";
        print "</td></tr>";
    }
}



$var=!$var;
print "<tr><td align=\"center\" colspan=\"3\">";
print "<input type=\"hidden\" name=\"nb_i\" value=\"$i\">";
print "<input type=\"submit\" class=\"button\" name=\"envoyer\" value=\"".$langs->trans("Modify")."\">";
print " &nbsp; ";
print "<input type=\"button\" class=\"button\" value=\"".$langs->trans("Cancel")."\" onClick=\"window.close();\">";
print "</td></tr>";

print "</table></form><br>\n";

$db->close();

llxFooter('$Date$ - $Revision$',0);
?>
