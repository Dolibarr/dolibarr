<?php
/* Copyright (C) 2005      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2006-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * @author     Eric Seigne
 * @copyright  Eric Seigne 13/06/2005
 *
 * ************************************************************************* */

/**
 *       \file       htdocs/core/searchpostalcode.php
 *       \ingroup    societe
 *       \brief      Search the city corresponding to the ZIP code entered. 1st round is sought in the company table, if we have two customers in the same city that is direct. If the search never does anything then we start looking in the table of postcodes.
 *       \version    $Id$
 */

require("../main.inc.php");

$langs->load("companies");

// Security Access Client
if ($user->societe_id > 0)
{
    $_GET["action"] = '';
    $_POST["action"] = '';
    $_GET["socid"] = $user->societe_id;
}

// Entry parameter are
// $_GET = array
//  'cp' => string '78180' (length=5)
//  'targettown' => string 'window.opener.document.formsoc.ville' (length=36)
//  'targetcountry' => string 'window.opener.document.formsoc.pays_id' (length=38)
//  'targetstate'

/*
 * View
 */

$javascript="
<script language=\"JavaScript\" type=\"text/javascript\">
<!--
function MAJ(targettown,targetcountry,targetstate)
{
  for (var i = 0; i < document.searchform.elements.length; i++)
  {
    var e = document.searchform.elements[i];
    if (e.checked)
    {
    	newtown = e.value;
      	targettown.value = unescape(newtown);
      	break;
    }
  }
  window.close();
}
//-->
</script>\n";


top_htmlhead("", $langs->trans("SearchTown"));

// Same as llxHeader. Open what llxFooter close
print '<body>';

print $javascript;

print "<div><br>";    // Ouvre div a la place de top_menu car le llxFooter en ferme un

print "<form method=\"post\" action=\"javascript:MAJ(".$_GET['targettown'].",".$_GET['targetcountry'].",".$_GET['targetstate'].");\" name=\"searchform\" enctype=\"application/x-www-form-urlencoded\">";
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print "<table summary=\"listoftowns\" class=\"nobordernopadding\" align=\"center\" width=\"90%\">";
print "<tr class=\"liste_titre\">";
print "  <td colspan=\"3\" align=\"center\">";
print "  <b>Recherche code postal: " . $_GET['cp'] . " </b>";
print "  </td>";
print "</tr>\n";

$num=0;

$result = run_request("societe");
if ($result)
{
	$num=$db->num_rows($result);
	//print 'sql='.$sql.' num='.$num; exit;
	if($num == 0)
	{
		if ($conf->global->MAIN_AUTOFILL_TOWNFROMZIP == 2)
		{
			$result = run_request("postalcode");	// If a table llx_postalcode exists
			$num=$db->num_rows($result);
		}
	}

	$showselect=1;

	// If it has not, or only one result on switch and fill the form
	if($num <= 1)
	{
		$obj = $db->fetch_object($result);
		$ville = $obj->ville;
		$ville_code = urlencode("$ville");
		print "<tr ".$bc[$var]."><td width=\"10%\">";
		if ($ville && $ville_code)
		{
			print '<input type="radio" name="town" value="'.$ville.'" checked>';
			print "<script language=\"javascript\" type=\"text/javascript\">document.searchform.submit();</script>\n";
		}
		else
		{
			$langs->load("errors");
			print $langs->trans("ErrorRecordNotFound");
			$showselect=0;
		}
	    print "</td></tr>";
	}
	else
	{
		// Otherwise it displays the list of cities which is the postal code ...
	    for ($i = 0; $i < $num; $i++)
	    {
	        $obj = $db->fetch_object($result);

	        $cp = $obj->cp;
	        $ville = $obj->ville;
			$ville_code = urlencode("$ville");

	        $dep = $obj->fk_departement;
	        $dep_lib = $obj->fk_departement;

	        $country_code = $obj->pays_code;
	        $temp=$obj->pays_code?$langs->transcountry("Country",$obj->pays_code):'';
	        if ($temp == 'Country') $temp=$obj->pays_lib;
	        $country_lib = $temp;

	        $var=!$var;
	        print "<tr ".$bc[$var].">";
	        print '<td>'.$country_lib.'</td>';
	        print "<td width=\"80\" nowrap=\"nowrap\">";
	        print '<input type="radio" name="town" value="'.$ville.'">'.$cp.'</td>';
	        print '<td>'.$ville.'</td>';
	        print "</tr>";
	    }
	}
}


$var=!$var;
print "<tr><td align=\"center\" colspan=\"3\">";
print "<br><input type=\"hidden\" name=\"nb_i\" value=\"$i\">";
if ($showselect)
{
	print "<input type=\"submit\" class=\"button\" name=\"envoyer\" value=\"".$langs->trans("Select")."\">";
	print " &nbsp; ";
}
print "<input type=\"button\" class=\"button\" value=\"".$langs->trans("Cancel")."\" onClick=\"window.close();\">";
print "</td></tr>";

print "</table></form><br>\n";

$db->close();

llxFooter('$Date$ - $Revision$',0);


/**
 * Return cursor on request to find zip/town
 *
 * @param 	$table
 * @return 	cursor
 */
function run_request($table)
{
    global $db;
	$cp=isset($_GET["cp"])?trim($_GET["cp"]):'';

    $sql = "SELECT DISTINCT cp, ville, fk_departement, fk_pays, p.code as pays_code, p.libelle as pays_lib";
    $sql.= " FROM ".MAIN_DB_PREFIX.$table;
    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX.'c_pays as p ON fk_pays = p.rowid';
    $sql.= " WHERE";
    if ($cp)
    {
    	$cp=str_replace('*','%',$cp);
    	$sql.= " cp LIKE '".addslashes($cp)."' AND";
    	$sql.= " (ville IS NOT NULL OR fk_departement IS NOT NULL OR fk_pays IS NOT NULL)";
    }
    else $sql.= " cp != '' AND cp IS NOT NULL";
	$sql.= " ORDER by fk_pays, ville, cp";
	$sql.= $db->plimit(50);	// Avoid pb with bad criteria

    //print $sql.'<br>';
	$result=$db->query($sql);
    if (!$result)
    {
        dol_print_error($db);
        exit;
    }

    return $result;
}

?>
