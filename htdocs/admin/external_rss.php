<?php
/* Copyright (C) 2003      Éric Seigne          <erics@rycks.com>
 * Copyright (C) 2003,2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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

if ($_POST["action"] == 'add')
{
    $external_rss_urlrss = "external_rss_urlrss_" . $_POST["norss"];

    if(isset($_POST[$external_rss_urlrss])) {
        $external_rss_title = "external_rss_title_" . $_POST["norss"];
        //$external_rss_url = "external_rss_url_" . $_POST["norss"];

        $db->begin();

        $sql = "DELETE FROM ".MAIN_DB_PREFIX."const WHERE name ='EXTERNAL_RSS_TITLE_" . $_POST["norss"] . "'; ";
        $db->query($sql);

        $sql = "DELETE FROM ".MAIN_DB_PREFIX."const WHERE name ='EXTERNAL_RSS_URLRSS_" . $_POST["norss"] . "'; ";
        $db->query($sql);

        $sql1 = "INSERT INTO ".MAIN_DB_PREFIX."const (name,value,visible) VALUES ('EXTERNAL_RSS_TITLE_" . $_POST["norss"] . "','".$_POST[$external_rss_title]."',0) ;";
        $sql2 = "INSERT INTO ".MAIN_DB_PREFIX."const (name,value,visible) VALUES ('EXTERNAL_RSS_URLRSS_" . $_POST["norss"] . "','".$_POST[$external_rss_urlrss]."',0) ;";

        if ($db->query($sql1) && $db->query($sql2))
        {
            $db->commit();
            header("Location: external_rss.php");
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
    if(isset($_POST["norss"])) {
        $db->begin();

        $sql1 = "DELETE FROM ".MAIN_DB_PREFIX."const WHERE name = 'EXTERNAL_RSS_URL_" . $_POST["norss"]."'";
        $sql2 = "DELETE FROM ".MAIN_DB_PREFIX."const WHERE name = 'EXTERNAL_RSS_TITLE_" . $_POST["norss"]."'";
        $sql3 = "DELETE FROM ".MAIN_DB_PREFIX."const WHERE name = 'EXTERNAL_RSS_URLRSS_" . $_POST["norss"]."'";

        $result1 = $db->query($sql1);
        $result2 = $db->query($sql2);
        $result3 = $db->query($sql3);

        if ($result1 && $result2 && $result3)
        {
            $db->commit();
            header("Location: external_rss.php");
        }
        else
        {
            $db->rollback();
            dolibarr_print_error($db);
        }
    }
}

if ($_POST["modify"])
{
    $external_rss_urlrss = "external_rss_urlrss_" . $_POST["norss"];
    if(isset($_POST[$external_rss_urlrss])) {
        $db->begin();

        $external_rss_title = "external_rss_title_" . $_POST["norss"];

        $sql = "DELETE FROM ".MAIN_DB_PREFIX."const WHERE name = 'EXTERNAL_RSS_TITLE_" . $_POST["norss"]."';";
        $db->query($sql);

        $sql = "DELETE FROM ".MAIN_DB_PREFIX."const WHERE name = 'EXTERNAL_RSS_URLRSS_" . $_POST["norss"]."';";
        $db->query($sql);

        $sql1 = "INSERT INTO ".MAIN_DB_PREFIX."const (name,value,visible) VALUES('" . "EXTERNAL_RSS_TITLE_" . $_POST["norss"] . "','". $_POST[$external_rss_title]."',0) ;";
        $sql2 = "INSERT INTO ".MAIN_DB_PREFIX."const (name,value,visible) VALUES('" . "EXTERNAL_RSS_URLRSS_" . $_POST["norss"] . "','". $_POST[$external_rss_urlrss]."',0)";

        if ($db->query($sql1) && $db->query($sql2))
        {
            $db->commit();
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

print_fiche_titre($langs->trans("ExternalRSSSetup"), $mesg);

print '<form name="externalrssconfig" action="external_rss.php" method="post">';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td colspan="2">'.$langs->trans("NewRSS").'</td>';
print '<td>'.$langs->trans("Example").'</td>';
print '</tr>';
print '<tr class="impair">';
print '<td width="100">'.$langs->trans("Title").'</td>';
print '<td><input type="text" name="external_rss_title_'.$nbexternalrss.'" value="'.@constant("EXTERNAL_RSS_TITLE_" . $nbexternalrss).'" size="45"></td>';
print '<td>April,<br>LinuxFR,<br>Lolix,<br>Parinux</td>';
print '</tr>';
?>
<tr class="pair">
  <td>URL du RSS</td>
  <td><input type="text" name="external_rss_urlrss_<?php echo $nbexternalrss ?>" value="<?php echo @constant("EXTERNAL_RSS_URLRSS_" . $nbexternalrss) ?>" size="45"></td>
  <td>http://wiki.april.org/RecentChanges?format=rss,<br>http://www.linuxfr.org/backend.rss,<br>http://back.fr.lolix.org/jobs.rss.php3,<br>http://parinux.org/backend.rss</td>
</tr>
<tr><td colspan="3" align="center">
<input type="submit"  value="<?php echo $langs->trans("Add") ?>">
<input type="hidden" name="action" value="add">
<input type="hidden" name="norss" value="<?php echo $nbexternalrss ?>">
</td>
</table>

</form>

<br>

<table class="noborder" width="100%">

<?php

for($i = 0; $i < $nbexternalrss; $i++) {
  print "<tr class=\"liste_titre\">
<form name=\"externalrssconfig\" action=\"external_rss.php\" method=\"post\">
  <td colspan=\"2\">Syndication du flux numéro " . ($i+1) . "</td>
</tr>
<tr class=\"impair\">
  <td width=\"100\">Titre</td>
  <td><input type=\"text\" name=\"external_rss_title_" . $i . "\" value=\"" . @constant("EXTERNAL_RSS_TITLE_" . $i) . "\" size=\"45\"></td>
</tr>
<tr class=\"pair\">
  <td>URL du RSS</td>
  <td><input type=\"text\" name=\"external_rss_urlrss_" . $i . "\" value=\"" . @constant("EXTERNAL_RSS_URLRSS_" . $i) . "\" size=\"45\"></td>
</tr>
<tr>
<td colspan=\"2\" align=\"center\">
<input type=\"submit\" name=\"modify\" value=\"".$langs->trans("Modify")."\">
<input type=\"submit\" name=\"delete\" value=\"".$langs->trans("Delete")."\">
<input type=\"hidden\" name=\"norss\"  value=\"$i\">
</td>
</form>
</tr>
";
}
?>

</table>

<?php 


$db->close();

llxFooter('$Date$ - $Revision$');

?>
