<?php
/* Copyright (C) 2003      Eric Seigne          <erics@rycks.com>
 * Copyright (C) 2003,2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2005-2011 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2011 	    Juanjo Menent		<jmenent@2byte.es>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *      \file       htdocs/admin/external_rss.php
 *      \ingroup    external_rss
 *      \brief      Page to setupe module ExternalRss
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/rssparser.class.php';

$langs->load("admin");

// Security check
if (!$user->admin) accessforbidden();

$def = array();
$lastexternalrss=0;
$action=GETPOST('action');


/*
 * Actions
 */

// positionne la variable pour le nombre de rss externes
$sql ="SELECT MAX(".$db->decrypt('name').") as name FROM ".MAIN_DB_PREFIX."const";
$sql.=" WHERE ".$db->decrypt('name')." LIKE 'EXTERNAL_RSS_URLRSS_%'";
$result=$db->query($sql);
if ($result)
{
    $obj = $db->fetch_object($result);
    preg_match('/([0-9]+)$/i',$obj->name,$reg);
	if ($reg[1]) $lastexternalrss = $reg[1];
}
else
{
    dol_print_error($db);
}

if ($action == 'add' || GETPOST("modify"))
{
    $external_rss_title = "external_rss_title_" . GETPOST("norss");
    $external_rss_urlrss = "external_rss_urlrss_" . GETPOST("norss");

    if (! empty($_POST[$external_rss_urlrss]))
    {
        $boxlabel='(ExternalRSSInformations)';
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
			$sql.= " VALUES ('box_external_rss.php','".$db->escape(GETPOST("norss").' ('.GETPOST($external_rss_title)).")')";
	        if (! $db->query($sql))
	        {
	        	dol_print_error($db);
	            $err++;
	        }
		}

		$result1=dolibarr_set_const($db, "EXTERNAL_RSS_TITLE_" . GETPOST("norss"),GETPOST($external_rss_title),'chaine',0,'',$conf->entity);
		if ($result1) $result2=dolibarr_set_const($db, "EXTERNAL_RSS_URLRSS_" . GETPOST("norss"),GETPOST($external_rss_urlrss),'chaine',0,'',$conf->entity);

        if ($result1 && $result2)
        {
            $db->commit();
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
    if(GETPOST("norss"))
    {
        $db->begin();

		// Supprime boite box_external_rss de definition des boites
        $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."boxes_def";
        $sql.= " WHERE file = 'box_external_rss.php' AND note LIKE '".GETPOST("norss")." %'";

		$resql=$db->query($sql);
		if ($resql)
        {
			$num = $db->num_rows($resql);
			$i=0;
			while ($i < $num)
			{
				$obj=$db->fetch_object($resql);

		        $sql = "DELETE FROM ".MAIN_DB_PREFIX."boxes";
		        $sql.= " WHERE entity = ".$conf->entity;
		        $sql.= " AND box_id = ".$obj->rowid;
				$resql=$db->query($sql);

		        $sql = "DELETE FROM ".MAIN_DB_PREFIX."boxes_def";
		        $sql.= " WHERE rowid = ".$obj->rowid;
				$resql=$db->query($sql);

				if (! $resql)
				{
					$db->rollback();
					dol_print_error($db,"sql=".$sql);
					exit;
				}

				$i++;
			}

			$db->commit();
		}
		else
		{
			$db->rollback();
			dol_print_error($db,"sql=".$sql);
			exit;
        }


		$result1=dolibarr_del_const($db,"EXTERNAL_RSS_TITLE_" . GETPOST("norss"),$conf->entity);
		if ($result1) $result2=dolibarr_del_const($db,"EXTERNAL_RSS_URLRSS_" . GETPOST("norss"),$conf->entity);

        if ($result1 && $result2)
        {
            $db->commit();
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
 * View
 */

llxHeader('',$langs->trans("ExternalRSSSetup"));

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("ExternalRSSSetup"), $linkback, 'title_setup');
print '<br>';

// Formulaire ajout
print '<form name="externalrssconfig" action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td colspan="2">'.$langs->trans("NewRSS").'</td>';
print '<td>'.$langs->trans("Example").'</td>';
print '</tr>';
print '<tr class="impair">';
print '<td width="100">'.$langs->trans("Title").'</td>';
print '<td><input type="text" name="external_rss_title_'.($lastexternalrss+1).'" value="'.@constant("EXTERNAL_RSS_TITLE_" . ($lastexternalrss+1)).'" size="64"></td>';
print '<td>'.$langs->trans('RSSUrlExample').'</td>';
print '</tr>';

print '<tr class="pair">';
print '<td>'.$langs->trans('RSSUrl').'</td>';
print '<td><input type="text" name="external_rss_urlrss_'.($lastexternalrss+1).'" value="'.@constant("EXTERNAL_RSS_URLRSS_" . ($lastexternalrss+1)).'" size="64"></td>';
print '<td>http://news.google.com/news?ned=us&topic=h&output=rss<br>http://www.dolibarr.org/rss</td>';
print '</tr>';
print '</table>';

print '<br><div class="center">';
print '<input type="submit" class="button" value="'.$langs->trans("Add").'">';
print '<input type="hidden" name="action" value="add">';
print '<input type="hidden" name="norss" value="'.($lastexternalrss+1).'">';
print '</div><br><br>';

print '</form>';


$sql ="SELECT rowid, file, note FROM ".MAIN_DB_PREFIX."boxes_def";
$sql.=" WHERE file = 'box_external_rss.php'";
$sql.=" ORDER BY note";

dol_syslog("select rss boxes", LOG_DEBUG);
$resql=$db->query($sql);
if ($resql)
{
	$num =$db->num_rows($resql);
	$i=0;

	while ($i < $num)
	{
		$obj = $db->fetch_object($resql);

	    preg_match('/^([0-9]+)/i',$obj->note,$reg);
		$idrss = $reg[1];
		//print "x".$idrss;

        $rssparser=new RssParser($db);
		$result = $rssparser->parser(@constant("EXTERNAL_RSS_URLRSS_".$idrss), 5, 300, $conf->externalrss->dir_temp);

		$var=true;

		print "<br>";
		print "<form name=\"externalrssconfig\" action=\"".$_SERVER["PHP_SELF"]."\" method=\"post\">";

		print '<table class="noborder" width="100%">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

		print "<tr class=\"liste_titre\">";
		print "<td>".$langs->trans("RSS")." ".($i+1)."</td>";
        print '<td align="right">';
        print "<input type=\"submit\" class=\"button\" name=\"modify\" value=\"".$langs->trans("Modify")."\">";
		print " &nbsp; ";
		print "<input type=\"submit\" class=\"button\" name=\"delete\" value=\"".$langs->trans("Delete")."\">";
		print "<input type=\"hidden\" name=\"norss\"  value=\"".$idrss."\">";
		print '</td>';
		print "</tr>";

		$var=!$var;
		print "<tr ".$bc[$var].">";
		print "<td width=\"100px\">".$langs->trans("Title")."</td>";
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
	    if ($result > 0 && empty($rss->error))
	    {
			print '<font class="ok">'.$langs->trans("Online").'</div>';
		}
		else
		{
			print '<font class="error">'.$langs->trans("Offline");
			$langs->load("errors");
			if ($rssparser->error) print ' - '.$langs->trans($rssparser->error);
			print '</div>';
		}
		print "</td>";
		print "</tr>";

		// Logo
	    if ($result > 0 && empty($rss->error))
	    {
			$var=!$var;
			print "<tr ".$bc[$var].">";
			print "<td>".$langs->trans("Logo")."</td>";
			print '<td>';
			$imageurl=$rssparser->getImageUrl();
			if ($imageurl) print '<img height="32" src="'.$imageurl.'">';
			else print $langs->trans("None");
			print '</td>';
			print "</tr>";
		}

		print '</table>';

		print "</form>";

		$i++;
	}
}
else
{
	dol_print_error($db);
}


$db->close();

llxFooter();
