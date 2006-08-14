<?php
/* Copyright (C) 2003      Éric Seigne          <erics@rycks.com>
 * Copyright (C) 2003,2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
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

/**
        \file       htdocs/admin/external_rss.php
        \ingroup    external_rss
        \brief      Page d'administration/configuration du module ExternalRss
        \version    $Revision$
*/

require("./pre.inc.php");
include_once(MAGPIERSS_PATH."rss_fetch.inc");


$langs->load("admin");

if (!$user->admin) accessforbidden();

$def = array();

// positionne la variable pour le nombre de rss externes
$result=$db->query("select count(*) nb from ".MAIN_DB_PREFIX."const WHERE name like 'EXTERNAL_RSS_URLRSS_%'");
if ($result)
{
    $obj = $db->fetch_object($result);
    $nbexternalrss = $obj->nb;
}
else {
    dolibarr_print_error($db);
}

if ($_POST["action"] == 'add' || $_POST["modify"])
{
    $external_rss_urlrss = "external_rss_urlrss_" . $_POST["norss"];

    if(isset($_POST[$external_rss_urlrss]))
    {
        $boxlabel='(ExternalRSSInformations)';
        $external_rss_title = "external_rss_title_" . $_POST["norss"];
        //$external_rss_url = "external_rss_url_" . $_POST["norss"];

        $db->begin();

		if ($_POST["modify"])
		{
			// Supprime boite box_external_rss de définition des boites
	        $sql = "DELETE FROM ".MAIN_DB_PREFIX."boxes_def";
	        $sql.= " WHERE file ='box_external_rss.php' AND note like '".$_POST["norss"]." %'";
	        if (! $db->query($sql))
	        {
				dolibarr_print_error($db,"sql=$sql");
				exit;
	        }
		}
		
		// Ajoute boite box_external_rss dans définition des boites
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."boxes_def (name, file, note) VALUES ('".$boxlabel."','box_external_rss.php','".addslashes($_POST["norss"].' ('.$_POST[$external_rss_title]).")')";
        if (! $db->query($sql))
        {
            $err++;
        }

		$result1=dolibarr_set_const($db, "EXTERNAL_RSS_TITLE_" . $_POST["norss"],$_POST[$external_rss_title]);
		if ($result1) $result2=dolibarr_set_const($db, "EXTERNAL_RSS_URLRSS_" . $_POST["norss"],$_POST[$external_rss_urlrss]);

        if ($result1 && $result2)
        {
            $db->commit();
	  		//$mesg='<div class="ok">'.$langs->trans("Success").'</div>';
            header("Location: ".$_SERVER["PHP_SELF"]);
            exit;
        }
        else
        {
            $db->rollback();
            dolibarr_print_error($db);
        }
    }
}

if ($_POST["delete"])
{
    if(isset($_POST["norss"]))
    {
        $db->begin();

		// Supprime boite box_external_rss de définition des boites
        $sql = "DELETE FROM ".MAIN_DB_PREFIX."boxes_def";
        $sql.= " WHERE file ='box_external_rss.php' AND note like '".$_POST["norss"]." %'";
        if (! $db->query($sql))
        {
			dolibarr_print_error($db,"sql=$sql");
			exit;
        }

		$result1=dolibarr_del_const($db,"EXTERNAL_RSS_TITLE_" . $_POST["norss"]);
		if ($result1) $result2=dolibarr_del_const($db,"EXTERNAL_RSS_URLRSS_" . $_POST["norss"]);
		
        if ($result1 && $result2)
        {
            $db->commit();
	  		//$mesg='<div class="ok">'.$langs->trans("Success").'</div>';
            header("Location: external_rss.php");
        }
        else
        {
            $db->rollback();
            dolibarr_print_error($db);
        }
    }
}


/*
 * Affichage du formulaire de saisie
 */
  
llxHeader();

print_fiche_titre($langs->trans("ExternalRSSSetup"), $mesg, 'setup');

print '<form name="externalrssconfig" action="external_rss.php" method="post">';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td colspan="2">'.$langs->trans("NewRSS").'</td>';
print '<td>'.$langs->trans("Example").'</td>';
print '</tr>';
print '<tr class="impair">';
print '<td width="100">'.$langs->trans("Title").'</td>';
print '<td><input type="text" name="external_rss_title_'.$nbexternalrss.'" value="'.@constant("EXTERNAL_RSS_TITLE_" . $nbexternalrss).'" size="64"></td>';
print '<td>April,<br>LinuxFR,<br>Lolix</td>';
print '</tr>';
?>
<tr class="pair">
  <td>URL du RSS</td>
  <td><input type="text" name="external_rss_urlrss_<?php echo $nbexternalrss ?>" value="<?php echo @constant("EXTERNAL_RSS_URLRSS_" . $nbexternalrss) ?>" size="64"></td>
  <td>http://wiki.april.org/RecentChanges?format=rss<br>http://linuxfr.org/backend/news/rss20.rss<br>http://back.fr.lolix.org/jobs.rss.php3
</tr>
<tr><td colspan="3" align="center">
<input type="submit" class="button" value="<?php echo $langs->trans("Add") ?>">
<input type="hidden" name="action" value="add">
<input type="hidden" name="norss" value="<?php echo $nbexternalrss ?>">
</td>
</table>

</form>

<br>

<table class="noborder" width="100%">

<?php

for($i = 0; $i < $nbexternalrss; $i++)
{
	$var=true;
	
	$rss = fetch_rss( @constant("EXTERNAL_RSS_URLRSS_".$i) );
	// fetch_rss initialise les objets suivant:
	// print_r($rss->channel);
	// print_r($rss->image);
	// print_r($rss->items);
	
	print "<form name=\"externalrssconfig\" action=\"external_rss.php\" method=\"post\">";
	
	print "<tr class=\"liste_titre\">";
	print "<td colspan=\"2\">Syndication du flux numéro " . ($i+1) . "</td>";
	print "</tr>";

	$var=!$var;
	print "<tr ".$bc[$var].">";
	print "<td width=\"100\">".$langs->trans("Title")."</td>";
	print "<td><input type=\"text\" class=\"flat\" name=\"external_rss_title_" . $i . "\" value=\"" . @constant("EXTERNAL_RSS_TITLE_" . $i) . "\" size=\"64\"></td>";
	print "</tr>";

	$var=!$var;
	print "<tr ".$bc[$var].">";
	print "<td>".$langs->trans("URL")."</td>";
	print "<td><input type=\"text\" class=\"flat\" name=\"external_rss_urlrss_" . $i . "\" value=\"" . @constant("EXTERNAL_RSS_URLRSS_" . $i) . "\" size=\"64\"></td>";
	print "</tr>";

	$var=!$var;
	print "<tr ".$bc[$var].">";
	print "<td>".$langs->trans("Status")."</td>";
	print "<td>";
    if (! $rss->ERROR)
    {
		print '<font class="ok">'.$langs->trans("Online").'</div>';
	}
	else
	{
		print '<font class="error">'.$langs->trans("Offline").'</div>';
	}
	print "</td>";
	print "</tr>";

	// Logo
    if (! $rss->ERROR && $rss->image['url'])
    {
		$var=!$var;
		print "<tr ".$bc[$var].">";
		print "<td>".$langs->trans("Logo")."</td>";
		print '<td>';
		print '<img height="32" src="'.$rss->image['url'].'">';
		print '</td>';
		print "</tr>";
	}

	print "<tr>";
	print "<td colspan=\"2\" align=\"center\">";
	print "<input type=\"submit\" class=\"button\" name=\"modify\" value=\"".$langs->trans("Modify")."\"> &nbsp;";
	print "<input type=\"submit\" class=\"button\" name=\"delete\" value=\"".$langs->trans("Delete")."\">";
	print "<input type=\"hidden\" name=\"norss\"  value=\"$i\">";
	print "</td>";
	print "</tr>";

	print "</form>";
}
?>

</table>

<?php 


$db->close();

llxFooter('$Date$ - $Revision$');

?>
