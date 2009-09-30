<?php
/* Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *       \file       htdocs/admin/ftpclient.php
 *       \ingroup    ftp
 *       \brief      Admin page to setup FTP client module
 *       \version    $Id$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");

$langs->load("admin");

// Security check
if (!$user->admin) accessforbidden();

$def = array();
$lastftpentry=0;

// Positionne la variable pour le nombre de rss externes
$sql ="select MAX(name) name from ".MAIN_DB_PREFIX."const";
$sql.=" WHERE name like 'FTP_SERVER_%'";
$result=$db->query($sql);
if ($result)
{
    $obj = $db->fetch_object($result);
    eregi('([0-9]+)$',$obj->name,$reg);
	if ($reg[1]) $lastftpentry = $reg[1];
}
else
{
    dol_print_error($db);
}

if ($_POST["action"] == 'add' || $_POST["modify"])
{
    $ftp_name = "FTP_NAME_" . $_POST["numero_entry"];
	$ftp_server = "FTP_SERVER_" . $_POST["numero_entry"];

	$error=0;
	$mesg='';
	
	if (empty($_POST[$ftp_name]))
	{
		$error=1;
		$mesg.='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Label")).'</div>';
	}
	
	if (empty($_POST[$ftp_server]))
	{
		$error=1;
		$mesg.='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Server")).'</div>';
	}
	
    if (! $error)
    {
    	$ftp_port = "FTP_PORT_" . $_POST["numero_entry"];
        $ftp_user = "FTP_USER_" . $_POST["numero_entry"];
        $ftp_password = "FTP_PASSWORD_" . $_POST["numero_entry"];

        $db->begin();

		$result1=dolibarr_set_const($db, "FTP_PORT_" . $_POST["numero_entry"],$_POST[$ftp_port],'chaine',0,'',$conf->entity);
		if ($result1) $result2=dolibarr_set_const($db, "FTP_SERVER_" . $_POST["numero_entry"],$_POST[$ftp_server],'chaine',0,'',$conf->entity);
		if ($result2) $result3=dolibarr_set_const($db, "FTP_USER_" . $_POST["numero_entry"],$_POST[$ftp_user],'chaine',0,'',$conf->entity);
		if ($result3) $result4=dolibarr_set_const($db, "FTP_PASSWORD_" . $_POST["numero_entry"],$_POST[$ftp_password],'chaine',0,'',$conf->entity);
		if ($result4) $result5=dolibarr_set_const($db, "FTP_NAME_" . $_POST["numero_entry"],$_POST[$ftp_name],'chaine',0,'',$conf->entity);

        if ($result1 && $result2 && $result3 && $result4 && $result5)
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
    if(isset($_POST["numero_entry"]))
    {
        $db->begin();

		$result1=dolibarr_del_const($db,"ftp_port_" . $_POST["numero_entry"],$conf->entity);
		if ($result1) $result2=dolibarr_del_const($db,"ftp_server_" . $_POST["numero_entry"],$conf->entity);
		if ($result2) $result3=dolibarr_del_const($db,"ftp_user_" . $_POST["numero_entry"],$conf->entity);
		if ($result3) $result4=dolibarr_del_const($db,"ftp_password_" . $_POST["numero_entry"],$conf->entity);
		if ($result4) $result5=dolibarr_del_const($db,"ftp_name_" . $_POST["numero_entry"],$conf->entity);

        if ($result1 && $result2 && $result3 && $result4 && $result5)
        {
            $db->commit();
	  		//$mesg='<div class="ok">'.$langs->trans("Success").'</div>';
            header("Location: ftpclient.php");
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

llxHeader();

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("FTPClientSetup"), $linkback, 'setup');
print '<br>';

if (! function_exists('ftp_connect'))
{
	print $langs->trans("FTPFeatureNotSupportedByYourPHP");
}
else
{
	if ($mesg) print $mesg;
	
	// Formulaire ajout
	print '<form name="ftpconfig" action="ftpclient.php" method="post">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

	print '<table class="nobordernopadding" width="100%">';
	print '<tr class="liste_titre">';
	print '<td colspan="2">'.$langs->trans("NewFTPClient").'</td>';
	print '<td>'.$langs->trans("Example").'</td>';
	print '</tr>';

	print '<tr class="pair">';
	print '<td>'.$langs->trans("Label").'</td>';
	print '<td><input type="text" name="FTP_NAME_'.($lastftpentry+1).'" value="'.@constant("FTP_NAME_" . ($lastftpentry+1)).'" size="64"></td>';
	print '<td>My FTP access</td>';
	print '</tr>';

	print '<tr class="impair">';
	print '<td>'.$langs->trans("Server").'</td>';
	print '<td><input type="text" name="FTP_SERVER_'.($lastftpentry+1).'" value="'.@constant("FTP_SERVER_" . ($lastftpentry+1)).'" size="64"></td>';
	print '<td>localhost</td>';
	print '</tr>';

	print '<tr class="pair">';
	print '<td width="100">'.$langs->trans("Port").'</td>';
	print '<td><input type="text" name="FTP_PORT_'.($lastftpentry+1).'" value="'.@constant("FTP_PORT_" . ($lastftpentry+1)).'" size="64"></td>';
	print '<td>21</td>';
	print '</tr>';

	print '<tr class="impair">';
	print '<td>'.$langs->trans("User").'</td>';
	print '<td><input type="text" name="FTP_USER_'.($lastftpentry+1).'" value="'.@constant("FTP_USER_" . ($lastftpentry+1)).'" size="24"></td>';
	print '<td>myftplogin</td>';
	print '</tr>';

	print '<tr class="pair">';
	print '<td>'.$langs->trans("Password").'</td>';
	print '<td><input type="password" name="FTP_PASSWORD_'.($lastftpentry+1).'" value="'.@constant("FTP_PASSWORD_" . ($lastftpentry+1)).'" size="24"></td>';
	print '<td>myftppassword</td>';
	print '</tr>';

	?>
	<tr><td colspan="3" align="center">
	<input type="submit" class="button" value="<?php echo $langs->trans("Add") ?>">
	<input type="hidden" name="action" value="add">
	<input type="hidden" name="numero_entry" value="<?php echo ($lastftpentry+1) ?>">
	</td>
	</tr>
	<?php
	print '</table>';
	print '</form>';
	?>

	<br>

	<table class="nobordernopadding" width="100%">

	<?php

	$sql ="select name, value, note from ".MAIN_DB_PREFIX."const";
	$sql.=" WHERE name like 'FTP_SERVER_%'";
	$sql.=" ORDER BY name";

	dol_syslog("ftpclient select ftp setup sql=".$sql,LOG_DEBUG);
	$resql=$db->query($sql);
	if ($resql)
	{
		$num =$db->num_rows($resql);
		$i=0;

		while ($i < $num)
		{
			$obj = $db->fetch_object($resql);

		    eregi('([0-9]+)$',$obj->name,$reg);
			$idrss = $reg[0];
			//print "x".join(',',$reg)."=".$obj->name."=".$idrss;

			$var=true;

			print "<form name=\"externalrssconfig\" action=\"".$_SERVER["PHP_SELF"]."\" method=\"post\">";
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

			print "<tr class=\"liste_titre\">";
			print "<td colspan=\"2\">".$langs->trans("FTP")." ".($idrss)."</td>";
			print "</tr>";

			$var=!$var;
			print "<tr ".$bc[$var].">";
			print "<td>".$langs->trans("Name")."</td>";
			print "<td><input type=\"text\" class=\"flat\" name=\"FTP_NAME_" . $idrss . "\" value=\"" . @constant("FTP_NAME_" . $idrss) . "\" size=\"64\"></td>";
			print "</tr>";

			$var=!$var;
			print "<tr ".$bc[$var].">";
			print "<td>".$langs->trans("Server")."</td>";
			print "<td><input type=\"text\" class=\"flat\" name=\"FTP_SERVER_" . $idrss . "\" value=\"" . @constant("FTP_SERVER_" . $idrss) . "\" size=\"64\"></td>";
			print "</tr>";

			$var=!$var;
			print "<tr ".$bc[$var].">";
			print "<td width=\"100\">".$langs->trans("Port")."</td>";
			print "<td><input type=\"text\" class=\"flat\" name=\"FTP_PORT_" . $idrss . "\" value=\"" . @constant("FTP_PORT_" . $idrss) . "\" size=\"64\"></td>";
			print "</tr>";

			$var=!$var;
			print "<tr ".$bc[$var].">";
			print "<td width=\"100\">".$langs->trans("User")."</td>";
			print "<td><input type=\"text\" class=\"flat\" name=\"FTP_USER_" . $idrss . "\" value=\"" . @constant("FTP_USER_" . $idrss) . "\" size=\"24\"></td>";
			print "</tr>";

			$var=!$var;
			print "<tr ".$bc[$var].">";
			print "<td width=\"100\">".$langs->trans("Password")."</td>";
			print "<td><input type=\"password\" class=\"flat\" name=\"FTP_PASSWORD_" . $idrss . "\" value=\"" . @constant("FTP_PASSWORD_" . $idrss) . "\" size=\"24\"></td>";
			print "</tr>";

			print "<tr>";
			print "<td colspan=\"2\" align=\"center\">";
			print "<input type=\"submit\" class=\"button\" name=\"modify\" value=\"".$langs->trans("Modify")."\">";
			print " &nbsp; ";
			print "<input type=\"submit\" class=\"button\" name=\"delete\" value=\"".$langs->trans("Delete")."\">";
			print "<input type=\"hidden\" name=\"numero_entry\"  value=\"".$idrss."\">";
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

	print '</table>';

}


$db->close();

llxFooter('$Date$ - $Revision$');
?>
