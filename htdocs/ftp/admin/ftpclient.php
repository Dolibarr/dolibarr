<?php
/* Copyright (C) 2004-2022 Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2011       Juanjo Menent           <jmenent@2byte.es>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *       \file       htdocs/ftp/admin/ftpclient.php
 *       \ingroup    ftp
 *       \brief      Admin page to setup FTP client module
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

$langs->loadLangs(array("admin", "other"));

$def = array();
$lastftpentry = 0;

$action = GETPOST('action', 'aZ09');
$entry = GETPOST('numero_entry', 'alpha');

// Security check
if (!$user->admin) {
	accessforbidden();
}


/*
 * Action
 */

// Get value for $lastftpentry
$sql = "select MAX(name) as name from ".MAIN_DB_PREFIX."const";
$sql .= " WHERE name like 'FTP_SERVER_%'";
$result = $db->query($sql);
if ($result) {
	$obj = $db->fetch_object($result);
	$reg = array();
	preg_match('/([0-9]+)$/i', $obj->name, $reg);
	if (!empty($reg[1])) {
		$lastftpentry = $reg[1];
	}
} else {
	dol_print_error($db);
}

if ($action == 'add' || GETPOST('modify', 'alpha')) {
	$ftp_name = "FTP_NAME_".$entry;
	$ftp_server = "FTP_SERVER_".$entry;

	$error = 0;

	if (!GETPOST($ftp_name, 'alpha')) {
		$error = 1;
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Label")), null, 'errors');
	}

	if (!GETPOST($ftp_server, 'alpha')) {
		$error = 1;
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Server")), null, 'errors');
	}

	if (!$error) {
		$ftp_port = "FTP_PORT_".$entry;
		$ftp_user = "FTP_USER_".$entry;
		$ftp_password = "FTP_PASSWORD_".$entry;
		$ftp_passive = "FTP_PASSIVE_".$entry;

		$db->begin();

		$result1 = dolibarr_set_const($db, "FTP_PORT_".$entry, GETPOST($ftp_port, 'alpha'), 'chaine', 0, '', $conf->entity);
		if ($result1) {
			$result2 = dolibarr_set_const($db, "FTP_SERVER_".$entry, GETPOST($ftp_server, 'alpha'), 'chaine', 0, '', $conf->entity);
		}
		if ($result2) {
			$result3 = dolibarr_set_const($db, "FTP_USER_".$entry, GETPOST($ftp_user, 'alpha'), 'chaine', 0, '', $conf->entity);
		}
		if ($result3) {
			$result4 = dolibarr_set_const($db, "FTP_PASSWORD_".$entry, GETPOST($ftp_password, 'alpha'), 'chaine', 0, '', $conf->entity);
		}
		if ($result4) {
			$result5 = dolibarr_set_const($db, "FTP_NAME_".$entry, GETPOST($ftp_name, 'alpha'), 'chaine', 0, '', $conf->entity);
		}
		if ($result5) {
			$result6 = dolibarr_set_const($db, "FTP_PASSIVE_".$entry, GETPOST($ftp_passive, 'alpha'), 'chaine', 0, '', $conf->entity);
		}

		if ($result1 && $result2 && $result3 && $result4 && $result5 && $result6) {
			$db->commit();
			header("Location: ".$_SERVER["PHP_SELF"]);
			exit;
		} else {
			$db->rollback();
			dol_print_error($db);
		}
	}
}

if (GETPOST('delete', 'alpha')) {
	if ($entry) {
		$db->begin();

		$result1 = dolibarr_del_const($db, "FTP_PORT_".$entry, $conf->entity);
		if ($result1) {
			$result2 = dolibarr_del_const($db, "FTP_SERVER_".$entry, $conf->entity);
		}
		if ($result2) {
			$result3 = dolibarr_del_const($db, "FTP_USER_".$entry, $conf->entity);
		}
		if ($result3) {
			$result4 = dolibarr_del_const($db, "FTP_PASSWORD_".$entry, $conf->entity);
		}
		if ($result4) {
			$result5 = dolibarr_del_const($db, "FTP_NAME_".$entry, $conf->entity);
		}
		if ($result4) {
			$result6 = dolibarr_del_const($db, "FTP_PASSIVE_".$entry, $conf->entity);
		}

		if ($result1 && $result2 && $result3 && $result4 && $result5 && $result6) {
			$db->commit();
			header("Location: ".$_SERVER["PHP_SELF"]);
			exit;
		} else {
			$db->rollback();
			dol_print_error($db);
		}
	}
}


/*
 * View
 */

$form = new Form($db);


$help_url = 'EN:Module_FTP_En|FR:Module_FTP|ES:Módulo_FTP';

llxHeader('', 'FTP', $help_url);

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("FTPClientSetup"), $linkback, 'title_setup');
print '<br>';

if (!function_exists('ftp_connect')) {
	print $langs->trans("FTPFeatureNotSupportedByYourPHP");
} else {
	// Formulaire ajout
	print '<form name="ftpconfig" action="ftpclient.php" method="post">';
	print '<input type="hidden" name="token" value="'.newToken().'">';

	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td colspan="2">'.$langs->trans("NewFTPClient").'</td>';
	print '<td>'.$langs->trans("Example").'</td>';
	print '</tr>';

	print '<tr class="oddeven">';
	print '<td>'.$langs->trans("Label").'</td>';
	print '<td><input type="text" name="FTP_NAME_'.($lastftpentry + 1).'" value="'.GETPOST("FTP_NAME_".($lastftpentry + 1)).'" size="64"></td>';
	print '<td>My FTP access</td>';
	print '</tr>';

	print '<tr class="oddeven">';
	print '<td>'.$langs->trans("Server").'</td>';
	print '<td><input type="text" name="FTP_SERVER_'.($lastftpentry + 1).'" value="'.GETPOST("FTP_SERVER_".($lastftpentry + 1)).'" size="64"></td>';
	print '<td>localhost</td>';
	print '</tr>';

	print '<tr class="oddeven">';
	print '<td width="100">'.$langs->trans("Port").'</td>';
	print '<td><input type="text" name="FTP_PORT_'.($lastftpentry + 1).'" value="'.GETPOST("FTP_PORT_".($lastftpentry + 1)).'" size="64"></td>';
	print '<td>21 for pure non crypted FTP or if option FTP_CONNECT_WITH_SSL (See Home-Setup-Other) is on (FTPS)<br>22 if option FTP_CONNECT_WITH_SFTP (See Home-Setup-Other) is on (SFTP)</td>';
	print '</tr>';

	print '<tr class="oddeven">';
	print '<td>'.$langs->trans("User").'</td>';
	print '<td><input type="text" name="FTP_USER_'.($lastftpentry + 1).'" value="'.GETPOST("FTP_USER_".($lastftpentry + 1)).'" class="minwidth175"></td>';
	print '<td>myftplogin</td>';
	print '</tr>';

	print '<tr class="oddeven">';
	print '<td>'.$langs->trans("Password").'</td>';
	print '<td><input type="password" name="FTP_PASSWORD_'.($lastftpentry + 1).'" value="'.GETPOST("FTP_PASSWORD_".($lastftpentry + 1)).'" class="minwidth175"></td>';
	print '<td>myftppassword</td>';
	print '</tr>';

	print '<tr class="oddeven">';
	print '<td>'.$langs->trans("FTPPassiveMode").'</td>';
	$defaultpassive = GETPOST("FTP_PASSIVE_".($lastftpentry + 1));
	if (!GETPOSTISSET("FTP_PASSIVE_".($lastftpentry + 1))) {
		$defaultpassive = empty($conf->global->FTP_SUGGEST_PASSIVE_BYDEFAULT) ? 0 : 1;
	}
	print '<td>'.$form->selectyesno('FTP_PASSIVE_'.($lastftpentry + 1), $defaultpassive, 2).'</td>';
	print '<td>'.$langs->trans("No").'</td>';
	print '</tr>';

	print '</table>';

	?>
	<div class="center">
	<input type="submit" class="button" value="<?php echo $langs->trans("Add") ?>"></div>
	<input type="hidden" name="action" value="add">
	<input type="hidden" name="numero_entry" value="<?php echo ($lastftpentry + 1) ?>">
	<?php
	print '</form>';
	print '<br>';
	?>

	<br>

	<?php

	$sql = "select name, value, note from ".MAIN_DB_PREFIX."const";
	$sql .= " WHERE name like 'FTP_SERVER_%'";
	$sql .= " ORDER BY name";

	dol_syslog("ftpclient select ftp setup", LOG_DEBUG);
	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;

		while ($i < $num) {
			$obj = $db->fetch_object($resql);

			$reg = array();
			preg_match('/([0-9]+)$/i', $obj->name, $reg);
			$idrss = $reg[0];
			//print "x".join(',',$reg)."=".$obj->name."=".$idrss;

			print '<br>';
			print '<form name="externalrssconfig" action="'.$_SERVER["PHP_SELF"].'" method="post">';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="numero_entry" value="'.$idrss.'">';

			print '<div class="div-table-responsive-no-min">';
			print '<table class="noborder centpercent">'."\n";

			print '<tr class="liste_titre">';
			print '<td class="fieldtitle">'.$langs->trans("FTP")." ".($idrss)."</td>";
			print '<td></td>';
			print "</tr>";

			$keyforname = "FTP_NAME_".$idrss;
			$keyforserver = "FTP_SERVER_".$idrss;
			$keyforport = "FTP_PORT_".$idrss;
			$keyforuser = "FTP_USER_".$idrss;
			$keyforpassword = "FTP_PASSWORD_".$idrss;
			$keyforpassive = "FTP_PASSIVE_".$idrss;

			print '<tr class="oddeven">';
			print "<td>".$langs->trans("Name")."</td>";
			print "<td><input type=\"text\" class=\"flat\" name=\"FTP_NAME_".$idrss."\" value=\"".getDolGlobalString($keyforname)."\" size=\"64\"></td>";
			print "</tr>";


			print '<tr class="oddeven">';
			print "<td>".$langs->trans("Server")."</td>";
			print "<td><input type=\"text\" class=\"flat\" name=\"FTP_SERVER_".$idrss."\" value=\"".getDolGlobalString($keyforserver)."\" size=\"64\"></td>";
			print "</tr>";


			print '<tr class="oddeven">';
			print "<td width=\"100\">".$langs->trans("Port")."</td>";
			print "<td><input type=\"text\" class=\"flat\" name=\"FTP_PORT_".$idrss."\" value=\"".getDolGlobalString($keyforport)."\" size=\"64\"></td>";
			print "</tr>";


			print '<tr class="oddeven">';
			print "<td width=\"100\">".$langs->trans("User")."</td>";
			print "<td><input type=\"text\" class=\"flat\" name=\"FTP_USER_".$idrss."\" value=\"".getDolGlobalString($keyforuser)."\" size=\"24\"></td>";
			print "</tr>";


			print '<tr class="oddeven">';
			print "<td width=\"100\">".$langs->trans("Password")."</td>";
			print "<td><input type=\"password\" class=\"flat\" name=\"FTP_PASSWORD_".$idrss."\" value=\"".getDolGlobalString($keyforpassword)."\" size=\"24\"></td>";
			print "</tr>";


			print '<tr class="oddeven">';
			print "<td width=\"100\">".$langs->trans("FTPPassiveMode")."</td>";
			print '<td>'.$form->selectyesno('FTP_PASSIVE_'.$idrss, getDolGlobalString($keyforpassive), 1).'</td>';
			print "</tr>";

			print '</table>';
			print '</div>';

			print '<div class="center">';
			print '<input type="submit" class="button" name="modify" value="'.$langs->trans("Modify").'">';
			print " &nbsp; ";
			print '<input type="submit" class="button" name="delete" value="'.$langs->trans("Delete").'">';
			print '</center>';

			print "</form>";
			print '<br><br>';

			$i++;
		}
	} else {
		dol_print_error($db);
	}
}

// End of page
llxFooter();
$db->close();
