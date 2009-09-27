<?php
/* Copyright (C) 2003      Eric Seigne          <erics@rycks.com>
 * Copyright (C) 2003,2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**
 *      \file       htdocs/admin/external_rss.php
 *      \ingroup    external_rss
 *      \brief      Page d'administration/configuration du module ExternalRss
 *      \version    $Id$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");
include_once(MAGPIERSS_PATH."rss_fetch.inc");


$langs->load("admin");

if (!$user->admin) accessforbidden();

$def = array();
$lastexternalrss=0;

// positionne la variable pour le nombre de rss externes
$sql ="select MAX(name) name from ".MAIN_DB_PREFIX."const";
$sql.=" WHERE name like 'EXTERNAL_RSS_URLRSS_%'";
$result=$db->query($sql);
if ($result)
{
    $obj = $db->fetch_object($result);
    eregi('([0-9]+)$',$obj->name,$reg);
	if ($reg[1]) $lastexternalrss = $reg[1];
}
else
{
    dol_print_error($db);
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
			// Supprime boite box_external_rss de definition des boites
/*	        $sql = "UPDATE ".MAIN_DB_PREFIX."boxes_def";
			$sql.= " SET name = '".$boxlabel."'";
	        $sql.= " WHERE file ='box_external_rss.php' AND note like '".$_POST["norss"]." %'";

			$resql=$db->query($sql);
			if (! $resql)
	        {
				dol_print_error($db,"sql=$sql");
				exit;
	        }
*/
		}
		else
		{
			// Ajoute boite box_external_rss dans definition des boites
	        $sql = "INSERT INTO ".MAIN_DB_PREFIX."boxes_def (file, note)";
			$sql.= " VALUES ('box_external_rss.php','".addslashes($_POST["norss"].' ('.$_POST[$external_rss_title]).")')";
	        if (! $db->query($sql))
	        {
	        	dol_print_error($db);
	            $err++;
	        }
		}

		$result1=dolibarr_set_const($db, "EXTERNAL_RSS_TITLE_" . $_POST["norss"],$_POST[$external_rss_title],'chaine',0,'',$conf->entity);
		if ($result1) $result2=dolibarr_set_const($db, "EXTERNAL_RSS_URLRSS_" . $_POST["norss"],$_POST[$external_rss_urlrss],'chaine',0,'',$conf->entity);

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
            dol_print_error($db);
        }
    }
}

if ($_POST["delete"])
{
    if(isset($_POST["norss"]))
    {
        $db->begin();

		// Supprime boite box_external_rss de definition des boites
        $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."boxes_def";
        $sql.= " WHERE file ='box_external_rss.php' AND note like '".$_POST["norss"]." %'";

		$resql=$db->query($sql);
		if ($resql)
        {
			$num = $db->num_rows($resql);
			$i=0;
			while ($i < $num)
			{
				$obj=$db->fetch_object($resql);

		        $sql = "DELETE FROM ".MAIN_DB_PREFIX."boxes";
		        $sql.= " WHERE box_id = ".$obj->rowid;
				$resql=$db->query($sql);

		        $sql = "DELETE FROM ".MAIN_DB_PREFIX."boxes_def";
		        $sql.= " WHERE rowid = ".$obj->rowid;
				$resql=$db->query($sql);

				if (! $resql)
				{
					$db->rollback();
					dol_print_error($db,"sql=$sql");
					exit;
				}

				$i++;
			}

			$db->commit();
		}
		else
		{
			$db->rollback();
			dol_print_error($db,"sql=$sql");
			exit;
        }


		$result1=dolibarr_del_const($db,"EXTERNAL_RSS_TITLE_" . $_POST["norss"],$conf->entity);
		if ($result1) $result2=dolibarr_del_const($db,"EXTERNAL_RSS_URLRSS_" . $_POST["norss"],$conf->entity);

        if ($result1 && $result2)
        {
            $db->commit();
	  		//$mesg='<div class="ok">'.$langs->trans("Success").'</div>';
            header("Location: external_rss.php");
            exit;
        }
        else
        {
            $db->rollback();
            dol_print_error($db);
        }
    }
}


/*
 * Affichage page
 */

llxHeader();

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("ExternalRSSSetup"), $linkback, 'setup');
print '<br>';

// Formulaire ajout
print '<form name="externalrssconfig" action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

print '<table class="nobordernopadding" width="100%">';
print '<tr class="liste_titre">';
print '<td colspan="2">'.$langs->trans("NewRSS").'</td>';
print '<td>'.$langs->trans("Example").'</td>';
print '</tr>';
print '<tr class="impair">';
print '<td width="100">'.$langs->trans("Title").'</td>';
print '<td><input type="text" name="external_rss_title_'.($lastexternalrss+1).'" value="'.@constant("EXTERNAL_RSS_TITLE_" . ($lastexternalrss+1)).'" size="64"></td>';
print '<td>April,<br>LinuxFR,<br>Lolix</td>';
print '</tr>';
?>
<tr class="pair">
  <td>URL du RSS</td>
  <td><input type="text" name="external_rss_urlrss_<?php echo ($lastexternalrss+1) ?>" value="<?php echo @constant("EXTERNAL_RSS_URLRSS_" . ($lastexternalrss+1)) ?>" size="64"></td>
  <td>http://wiki.april.org/RecentChanges?format=rss<br>http://linuxfr.org/backend/news/rss20.rss<br>http://back.fr.lolix.org/jobs.rss.php3</td>
</tr>
<tr><td colspan="3" align="center">
<input type="submit" class="button" value="<?php echo $langs->trans("Add") ?>">
<input type="hidden" name="action" value="add">
<input type="hidden" name="norss" value="<?php echo ($lastexternalrss+1) ?>">
</td>
</tr>
<?php
print '</table>';
print '</form>';


print '<br>';


print '<table class="nobordernopadding" width="100%">';

$sql ="select rowid, file, note from ".MAIN_DB_PREFIX."boxes_def";
$sql.=" WHERE file = 'box_external_rss.php'";
$sql.=" ORDER BY note";

dol_syslog("external_rss select rss boxes sql=".$sql,LOG_DEBUG);
$resql=$db->query($sql);
if ($resql)
{
	$num =$db->num_rows($resql);
	$i=0;

	while ($i < $num)
	{
		$obj = $db->fetch_object($resql);

	    eregi('^([0-9]+)',$obj->note,$reg);
		$idrss = $reg[1];
		//print "x".$idrss;

		$var=true;

		$rss = fetch_rss( @constant("EXTERNAL_RSS_URLRSS_".$idrss) );
		// fetch_rss initialise les objets suivant:
		// print_r($rss->channel);
		// print_r($rss->image);
		// print_r($rss->items);

		print "<form name=\"externalrssconfig\" action=\"".$_SERVER["PHP_SELF"]."\" method=\"post\">";
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

		print "<tr class=\"liste_titre\">";
		print "<td colspan=\"2\">".$langs->trans("RSS")." ".($i+1)."</td>";
		print "</tr>";

		$var=!$var;
		print "<tr ".$bc[$var].">";
		print "<td width=\"100\">".$langs->trans("Title")."</td>";
		print "<td><input type=\"text\" class=\"flat\" name=\"external_rss_title_" . $idrss . "\" value=\"" . @constant("EXTERNAL_RSS_TITLE_" . $idrss) . "\" size=\"64\"></td>";
		print "</tr>";

		$var=!$var;
		print "<tr ".$bc[$var].">";
		print "<td>".$langs->trans("URL")."</td>";
		print "<td><input type=\"text\" class=\"flat\" name=\"external_rss_urlrss_" . $idrss . "\" value=\"" . @constant("EXTERNAL_RSS_URLRSS_" . $idrss) . "\" size=\"64\"></td>";
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
		print "<input type=\"submit\" class=\"button\" name=\"modify\" value=\"".$langs->trans("Modify")."\">";
		print " &nbsp; ";
		print "<input type=\"submit\" class=\"button\" name=\"delete\" value=\"".$langs->trans("Delete")."\">";
		print "<input type=\"hidden\" name=\"norss\"  value=\"".$idrss."\">";
		print "</td>";
		print "</tr>";

		print "</form>";

		$i++;
	}
}
else
{
	dol_print_error($db);
}

print '</table>'."\n";


$db->close();

llxFooter('$Date$ - $Revision$');
?>
