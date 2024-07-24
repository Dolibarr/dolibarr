<?php
/* Copyright (C) 2004       Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004       Eric Seigne             <eric.seigne@ryxeo.com>
 * Copyright (C) 2004-2012  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2004       Benoit Mortier          <benoit.mortier@opensides.be>
 * Copyright (C) 2004       Sebastien DiCintio      <sdicintio@ressource-toi.org>
 * Copyright (C) 2005-2011  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2016       Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
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
 *       \file       htdocs/install/fileconf.php
 *       \ingroup    install
 *       \brief      Ask all information required to build Dolibarr htdocs/conf/conf.php file (will be written to disk on next page step1)
 */

include_once 'inc.php';

global $langs;

$err = 0;

$setuplang = GETPOST("selectlang", 'alpha', 3) ? GETPOST("selectlang", 'alpha', 3) : (GETPOST('lang', 'alpha', 1) ? GETPOST('lang', 'alpha', 1) : 'auto');
$langs->setDefaultLang($setuplang);

$langs->loadLangs(array("install", "errors"));

dolibarr_install_syslog("- fileconf: entering fileconf.php page");

// You can force preselected values of the config step of Dolibarr by adding a file
// install.forced.php into directory htdocs/install (This is the case with some wizard
// installer like DoliWamp, DoliMamp or DoliBuntu).
// We first init "forced values" to nothing.
if (!isset($force_install_noedit)) {
	$force_install_noedit = ''; // 1=To block vars specific to distrib, 2 to block all technical parameters
}
if (!isset($force_install_type)) {
	$force_install_type = '';
}
if (!isset($force_install_dbserver)) {
	$force_install_dbserver = '';
}
if (!isset($force_install_port)) {
	$force_install_port = '';
}
if (!isset($force_install_database)) {
	$force_install_database = '';
}
if (!isset($force_install_prefix)) {
	$force_install_prefix = '';
}
if (!isset($force_install_createdatabase)) {
	$force_install_createdatabase = '';
}
if (!isset($force_install_databaselogin)) {
	$force_install_databaselogin = '';
}
if (!isset($force_install_databasepass)) {
	$force_install_databasepass = '';
}
if (!isset($force_install_databaserootlogin)) {
	$force_install_databaserootlogin = '';
}
if (!isset($force_install_databaserootpass)) {
	$force_install_databaserootpass = '';
}
// Now we load forced values from install.forced.php file.
$useforcedwizard = false;
$forcedfile = "./install.forced.php";
if ($conffile == "/etc/dolibarr/conf.php") {
	$forcedfile = "/etc/dolibarr/install.forced.php"; // Must be after inc.php
}
if (@file_exists($forcedfile)) {
	$useforcedwizard = true;
	include_once $forcedfile;
}



/*
 *	View
 */

session_start(); // To be able to keep info into session (used for not losing pass during navigation. pass must not transit through parmaeters)

pHeader($langs->trans("ConfigurationFile"), "step1", "set", "", (empty($force_dolibarr_js_JQUERY) ? '' : $force_dolibarr_js_JQUERY.'/'), 'main-inside-bis');

// Test if we can run a first install process
if (!is_writable($conffile)) {
	print $langs->trans("ConfFileIsNotWritable", $conffiletoshow);
	dolibarr_install_syslog("fileconf: config file is not writable", LOG_WARNING);
	dolibarr_install_syslog("- fileconf: end");
	pFooter(1, $setuplang, 'jscheckparam');
	exit;
}

if (!empty($force_install_message)) {
	print '<div><br>'.$langs->trans($force_install_message).'</div>';

	/*print '<script type="text/javascript">';
	print '	jQuery(document).ready(function() {
				jQuery("#linktoshowtechnicalparam").click(function() {
					jQuery(".hidewhenedit").hide();
					jQuery(".hidewhennoedit").show();
				});';
				if ($force_install_noedit) print 'jQuery(".hidewhennoedit").hide();';
	print '});';
	print '</script>';

	print '<br><a href="#" id="linktoshowtechnicalparam" class="hidewhenedit">'.$langs->trans("ShowEditTechnicalParameters").'</a><br>';
	*/
}

?>
<div>


<table class="nobordernopadding<?php if ($force_install_noedit) {
	print ' hidewhennoedit';
							   } ?>">

	<tr>
		<td colspan="3" class="label">
		<h3><img class="valignmiddle inline-block paddingright" src="../theme/common/octicons/build/svg/globe.svg" width="20" alt="webserver"> <?php echo $langs->trans("WebServer"); ?></h3>
		</td>
	</tr>

	<!-- Documents root $dolibarr_main_document_root -->
	<tr>
		<td class="label"><label for="main_dir"><b><?php print $langs->trans("WebPagesDirectory"); ?></b></label></td>
<?php
if (empty($dolibarr_main_document_root)) {
	$dolibarr_main_document_root = GETPOSTISSET('main_dir') ? GETPOST('main_dir') : detect_dolibarr_main_document_root();
}
?>
		<td class="label">
			<input type="text"
				   class="minwidth300"
				   id="main_dir"
				   name="main_dir"
				   value="<?php print $dolibarr_main_document_root ?>"
<?php
if (!empty($force_install_noedit)) {
	print ' disabled';
}
?>
			>
		</td>
		<td class="comment"><?php
		print '<span class="opacitymedium">'.$langs->trans("WithNoSlashAtTheEnd")."</span><br>";
		print $langs->trans("Examples").":<br>";
		?>
		<ul>
			<li>/var/www/dolibarr/htdocs</li>
			<li>C:/wwwroot/dolibarr/htdocs</li>
		</ul>
		</td>
	</tr>

	<!-- Documents URL $dolibarr_main_data_root -->
	<tr>
		<td class="label"><label for="main_data_dir"><b><?php print $langs->trans("DocumentsDirectory"); ?></b></label></td>
		<?php
		if (!empty($force_install_main_data_root)) {
			$dolibarr_main_data_root = @$force_install_main_data_root;
		}
		if (empty($dolibarr_main_data_root)) {
			$dolibarr_main_data_root = GETPOSTISSET('main_data_dir') ? GETPOST('main_data_dir') : detect_dolibarr_main_data_root($dolibarr_main_document_root);
		}
		?>
		<td class="label">
			<input type="text"
				   class="minwidth300"
				   id="main_data_dir"
				   name="main_data_dir"
				   value="<?php print $dolibarr_main_data_root ?>"
<?php if (!empty($force_install_noedit)) {
			print ' disabled';
} ?>
			>
		</td>
		<td class="comment"><?php
		print '<span class="opacitymedium">'.$langs->trans("WithNoSlashAtTheEnd")."</span><br>";
		print $langs->trans("DirectoryRecommendation")."<br>";
		print $langs->trans("Examples").":<br>";
		?>
		<ul>
			<li>/var/lib/dolibarr/documents</li>
			<li>C:/My Documents/dolibarr/documents</li>
		</ul>
		</td>
	</tr>

	<!-- Root URL $dolibarr_main_url_root -->
	<?php
	if (empty($dolibarr_main_url_root)) {
		$dolibarr_main_url_root = GETPOSTISSET('main_url') ? GETPOST('main_url') : detect_dolibarr_main_url_root();
	}
	?>
	<tr>
		<td class="label"><label for="main_url"><b><?php echo $langs->trans("URLRoot"); ?></b></label>
		</td>
		<td class="label">
			<input type="text"
				   class="minwidth300"
				   id="main_url"
				   name="main_url"
				   value="<?php print $dolibarr_main_url_root; ?> "
<?php if (!empty($force_install_noedit)) {
		print ' disabled';
}
?>
			>
		</td>
		<td class="comment"><?php print $langs->trans("Examples").":<br>"; ?>
		<ul>
			<li>http://localhost/</li>
			<li>http://www.myserver.com:8180/dolibarr</li>
			<li>https://www.myvirtualfordolibarr.com/</li>
		</ul>
		</td>
	</tr>

	<?php
	if (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == 'on') {   // Enabled if the installation process is "https://"
		?>
	<tr>
					<td class="label"><label for="main_force_https"><?php echo $langs->trans("ForceHttps"); ?></label></td>
					<td class="label">
						<input type="checkbox"
							   id="main_force_https"
							   name="main_force_https"
				<?php if (!empty($force_install_mainforcehttps)) {
					print ' checked';
				} ?>
				<?php if ($force_install_noedit == 2 && $force_install_mainforcehttps !== null) {
					print ' disabled';
				} ?>
			>
		</td>
		<td class="comment"><?php echo $langs->trans("CheckToForceHttps"); ?>
		</td>

	</tr>
		<?php
	}
	?>

	<!-- Dolibarr database -->

	<tr>
		<td colspan="3" class="label"><br>
		<h3><img class="valignmiddle inline-block paddingright" src="../theme/common/octicons/build/svg/database.svg" width="20" alt="webserver"> <?php echo $langs->trans("DolibarrDatabase"); ?></h3>
		</td>
	</tr>

	<tr>
		<td class="label"><label for="db_name"><b><?php echo $langs->trans("DatabaseName"); ?></b></label></td>
		<td class="label">
			<input type="text"
				   id="db_name"
				   name="db_name"
				   value="<?php echo (!empty($dolibarr_main_db_name)) ? $dolibarr_main_db_name : ($force_install_database ? $force_install_database : 'dolibarr'); ?>"
				<?php if ($force_install_noedit == 2 && $force_install_database !== null) {
					print ' disabled';
				} ?>
			>
		</td>
		<td class="comment"><?php echo $langs->trans("DatabaseName"); ?></td>
	</tr>


	<?php
	if (!isset($dolibarr_main_db_host)) {
		$dolibarr_main_db_host = "localhost";
	}
	?>
	<tr>
		<!-- Driver type -->
		<td class="label"><label for="db_type"><b><?php echo $langs->trans("DriverType"); ?></b></label></td>

		<td class="label">
		<?php

		$defaultype = !empty($dolibarr_main_db_type) ? $dolibarr_main_db_type : (empty($force_install_type) ? 'mysqli' : $force_install_type);

		$modules = array();
		$nbok = $nbko = 0;
		$option = '';

		// Scan les drivers
		$dir = DOL_DOCUMENT_ROOT.'/core/db';
		$handle = opendir($dir);
		if (is_resource($handle)) {
			while (($file = readdir($handle)) !== false) {
				if (is_readable($dir."/".$file) && preg_match('/^(.*)\.class\.php$/i', $file, $reg)) {
					$type = $reg[1];
					if ($type === 'DoliDB') {
						continue; // Skip abstract class
					}
					$class = 'DoliDB'.ucfirst($type);
					include_once $dir."/".$file;

					if ($type == 'sqlite') {
						continue; // We hide sqlite because support can't be complete until sqlite does not manage foreign key creation after table creation (ALTER TABLE child ADD CONSTRAINT not supported)
					}
					if ($type == 'sqlite3') {
						continue; // We hide sqlite3 because support can't be complete until sqlite does not manage foreign key creation after table creation (ALTER TABLE child ADD CONSTRAINT not supported)
					}

					// Version min of database
					$versionbasemin = explode('.', $class::VERSIONMIN);
					$note = '('.$class::LABEL.' >= '.$class::VERSIONMIN.')';

					// Switch to mysql if mysqli is not present
					if ($defaultype == 'mysqli' && !function_exists('mysqli_connect')) {
						$defaultype = 'mysql';
					}

					// Show line into list
					if ($type == 'mysql') {
						$testfunction = 'mysql_connect';
						$testclass = '';
					}
					if ($type == 'mysqli') {
						$testfunction = 'mysqli_connect';
						$testclass = '';
					}
					if ($type == 'pgsql') {
						$testfunction = 'pg_connect';
						$testclass = '';
					}
					if ($type == 'mssql') {
						$testfunction = 'mssql_connect';
						$testclass = '';
					}
					if ($type == 'sqlite') {
						$testfunction = '';
						$testclass = 'PDO';
					}
					if ($type == 'sqlite3') {
						$testfunction = '';
						$testclass = 'SQLite3';
					}
					$option .= '<option value="'.$type.'"'.($defaultype == $type ? ' selected' : '');
					if ($testfunction && !function_exists($testfunction)) {
						$option .= ' disabled';
					}
					if ($testclass && !class_exists($testclass)) {
						$option .= ' disabled';
					}
					$option .= '>';
					$option .= $type.'&nbsp; &nbsp;';
					if ($note) {
						$option .= ' '.$note;
					}
					// Deprecated and experimental
					if ($type == 'mysql') {
						$option .= ' '.$langs->trans("Deprecated");
					} elseif ($type == 'mssql') {
						$option .= ' '.$langs->trans("VersionExperimental");
					} elseif ($type == 'sqlite') {
						$option .= ' '.$langs->trans("VersionExperimental");
					} elseif ($type == 'sqlite3') {
						$option .= ' '.$langs->trans("VersionExperimental");
					} elseif (!function_exists($testfunction)) {
						// No available
						$option .= ' - '.$langs->trans("FunctionNotAvailableInThisPHP");
					}
					$option .= '</option>';
				}
			}
		}
		?>
			<select id="db_type"
					name="db_type"
				<?php if ($force_install_noedit == 2 && $force_install_type !== null) {
					print ' disabled';
				} ?>
			>
				<?php print $option; ?>
			</select>

		</td>
		<td class="comment"><?php echo $langs->trans("DatabaseType"); ?></td>

	</tr>

	<tr class="hidesqlite">
		<td class="label"><label for="db_host"><b><?php echo $langs->trans("DatabaseServer"); ?></b></label></td>
		<td class="label">
			<input type="text"
				   id="db_host"
				   name="db_host"
				   value="<?php print(!empty($force_install_dbserver) ? $force_install_dbserver : (!empty($dolibarr_main_db_host) ? $dolibarr_main_db_host : 'localhost')); ?>"
				<?php if ($force_install_noedit == 2 && $force_install_dbserver !== null) {
					print ' disabled';
				} ?>
			>
		</td>
		<td class="comment"><?php echo $langs->trans("ServerAddressDescription"); ?>
		</td>

	</tr>

	<tr class="hidesqlite">
		<td class="label"><label for="db_port"><?php echo $langs->trans("Port"); ?></label></td>
		<td class="label">
			<input type="text"
				   name="db_port"
				   id="db_port"
				   value="<?php print (!empty($force_install_port)) ? $force_install_port : $dolibarr_main_db_port; ?>"
				<?php if ($force_install_noedit == 2 && $force_install_port !== null) {
					print ' disabled';
				} ?>
			>
		</td>
		<td class="comment"><?php echo $langs->trans("ServerPortDescription"); ?>
		</td>

	</tr>

	<tr class="hidesqlite">
		<td class="label"><label for="db_prefix"><?php echo $langs->trans("DatabasePrefix"); ?></label></td>
		<td class="label">
			<input type="text"
				   id="db_prefix"
				   name="db_prefix"
				   value="<?php echo(!empty($force_install_prefix) ? $force_install_prefix : (!empty($dolibarr_main_db_prefix) ? $dolibarr_main_db_prefix : 'llx_')); ?>"
				<?php if ($force_install_noedit == 2 && $force_install_prefix !== null) {
					print ' disabled';
				} ?>
			>
		</td>
		<td class="comment"><?php echo $langs->trans("DatabasePrefixDescription"); ?></td>
	</tr>

	<tr class="hidesqlite">
		<td class="label"><label for="db_create_database"><?php echo $langs->trans("CreateDatabase"); ?></label></td>
		<td class="label">
			<input type="checkbox"
				   id="db_create_database"
				   name="db_create_database"
				   value="on"
				<?php
				$checked = 0;
				if ($force_install_createdatabase) {
					$checked = 1;
					print ' checked';
				} ?>
				<?php if ($force_install_noedit == 2 && $force_install_createdatabase !== null) {
					print ' disabled';
				} ?>
			>
		</td>
		<td class="comment">
		<?php echo $langs->trans("CheckToCreateDatabase"); ?>
		</td>
	</tr>

	<tr class="hidesqlite">
		<td class="label"><label for="db_user"><b><?php echo $langs->trans("Login"); ?></b></label></td>
		<td class="label">
			<input type="text"
				   id="db_user"
				   name="db_user"
				   value="<?php print (!empty($force_install_databaselogin)) ? $force_install_databaselogin : $dolibarr_main_db_user; ?>"
				<?php if ($force_install_noedit == 2 && $force_install_databaselogin !== null) {
					print ' disabled';
				} ?>
			>
		</td>
		<td class="comment"><?php echo $langs->trans("AdminLogin"); ?></td>
	</tr>

	<tr class="hidesqlite">
		<td class="label"><label for="db_pass"><b><?php echo $langs->trans("Password"); ?></b></label></td>
		<td class="label">
			<input type="password" class="text-security"
				   id="db_pass" autocomplete="off"
				   name="db_pass"
				   value="<?php
					// If $force_install_databasepass is on, we don't want to set password, we just show '***'. Real value will be extracted from the forced install file at step1.
					$autofill = ((!empty($_SESSION['dol_save_pass'])) ? $_SESSION['dol_save_pass'] : str_pad('', strlen($force_install_databasepass), '*'));
					if (!empty($dolibarr_main_prod) && empty($_SESSION['dol_save_pass'])) {    // So value can't be found if install page still accessible
						$autofill = '';
					}
					print dol_escape_htmltag($autofill);
					?>"
				<?php if ($force_install_noedit == 2 && $force_install_databasepass !== null) {
						print ' disabled';
				} ?>
			>
		</td>
		<td class="comment"><?php echo $langs->trans("AdminPassword"); ?></td>
	</tr>

	<tr class="hidesqlite">
		<td class="label"><label for="db_create_user"><?php echo $langs->trans("CreateUser"); ?></label></td>
		<td class="label">
			<input type="checkbox"
				   id="db_create_user"
				   name="db_create_user"
				   value="on"
				<?php
				$checked = 0;
				if (!empty($force_install_createuser)) {
					$checked = 1;
					print ' checked';
				} ?>
				<?php if ($force_install_noedit == 2 && $force_install_createuser !== null) {
					print ' disabled';
				} ?>
			>
		</td>
		<td class="comment">
		<?php echo $langs->trans("CheckToCreateUser"); ?>
		</td>
	</tr>


	<!-- Super access -->
	<?php
	$force_install_databaserootlogin = parse_database_login($force_install_databaserootlogin);
	$force_install_databaserootpass = parse_database_pass($force_install_databaserootpass);
	?>
	<tr class="hidesqlite hideroot">
		<td colspan="3" class="label"><br>
		<h3><img class="valignmiddle inline-block paddingright" src="../theme/common/octicons/build/svg/shield.svg" width="20" alt="webserver"> <?php echo $langs->trans("DatabaseSuperUserAccess"); ?></h3>
		</td>
	</tr>

	<tr class="hidesqlite hideroot">
		<td class="label"><label for="db_user_root"><b><?php echo $langs->trans("Login"); ?></b></label></td>
		<td class="label">
			<input type="text"
				   id="db_user_root"
				   name="db_user_root"
				   class="needroot"
				   value="<?php print (!empty($force_install_databaserootlogin)) ? $force_install_databaserootlogin : (GETPOSTISSET('db_user_root') ? GETPOST('db_user_root') : (isset($db_user_root) ? $db_user_root : '')); ?>"
				<?php if ($force_install_noedit > 0 && !empty($force_install_databaserootlogin)) {
					print ' disabled';
				} ?>
			>
		</td>
		<td class="comment"><?php echo $langs->trans("DatabaseRootLoginDescription"); ?>
		<!--
		<?php echo '<br>'.$langs->trans("Examples").':<br>' ?>
		<ul>
			<li>root (Mysql)</li>
			<li>postgres (PostgreSql)</li>
		</ul>
		</td>
		 -->

	</tr>
	<tr class="hidesqlite hideroot">
		<td class="label"><label for="db_pass_root"><b><?php echo $langs->trans("Password"); ?></b></label></td>
		<td class="label">
			<input type="password"
				   autocomplete="off"
				   id="db_pass_root"
				   name="db_pass_root"
				   class="needroot text-security"
				   value="<?php
					// If $force_install_databaserootpass is on, we don't want to set password here, we just show '***'. Real value will be extracted from the forced install file at step1.
					$autofill = ((!empty($force_install_databaserootpass)) ? str_pad('', strlen($force_install_databaserootpass), '*') : (isset($db_pass_root) ? $db_pass_root : ''));
					if (!empty($dolibarr_main_prod)) {
						$autofill = '';
					}
					// Do not autofill password if instance is a production instance
					if (!empty($_SERVER["SERVER_NAME"]) && !in_array(
						$_SERVER["SERVER_NAME"],
						array('127.0.0.1', 'localhost', 'localhostgit')
					)
					) {
						$autofill = '';
					}    // Do not autofill password for remote access
					print dol_escape_htmltag($autofill);
					?>"
				<?php if ($force_install_noedit > 0 && !empty($force_install_databaserootpass)) {
						print ' disabled'; /* May be removed by javascript*/
				} ?>
			>
		</td>
		<td class="comment"><?php echo $langs->trans("KeepEmptyIfNoPassword"); ?>
		</td>
	</tr>

</table>
</div>

<script type="text/javascript">
function init_needroot()
{
	console.log("init_needroot force_install_noedit=<?php echo $force_install_noedit?>");
	console.log(jQuery("#db_create_database").is(":checked"));
	console.log(jQuery("#db_create_user").is(":checked"));

	if (jQuery("#db_create_database").is(":checked") || jQuery("#db_create_user").is(":checked"))
	{
		console.log("init_needroot show root section");
		jQuery(".hideroot").show();
		<?php
		if (empty($force_install_noedit)) { ?>
			jQuery(".needroot").removeAttr('disabled');
		<?php } ?>
	}
	else
	{
		console.log("init_needroot hide root section");
		jQuery(".hideroot").hide();
		jQuery(".needroot").prop('disabled', true);
	}
}

function checkDatabaseName(databasename) {
	if (databasename.match(/[;\.]/)) { return false; }
	return true;
}

function jscheckparam()
{
	console.log("Click on jscheckparam");

	var ok = true;

	if (document.forminstall.main_dir.value == '')
	{
		ok=false;
		alert('<?php echo dol_escape_js($langs->transnoentities("ErrorFieldRequired", $langs->transnoentitiesnoconv("WebPagesDirectory"))); ?>');
	}
	else if (document.forminstall.main_data_dir.value == '')
	{
		ok=false;
		alert('<?php echo dol_escape_js($langs->transnoentities("ErrorFieldRequired", $langs->transnoentitiesnoconv("DocumentsDirectory"))); ?>');
	}
	else if (document.forminstall.main_url.value == '')
	{
		ok=false;
		alert('<?php echo dol_escape_js($langs->transnoentities("ErrorFieldRequired", $langs->transnoentitiesnoconv("URLRoot"))); ?>');
	}
	else if (document.forminstall.db_host.value == '')
	{
		ok=false;
		alert('<?php echo dol_escape_js($langs->transnoentities("ErrorFieldRequired", $langs->transnoentitiesnoconv("Server"))); ?>');
	}
	else if (document.forminstall.db_name.value == '')
	{
		ok=false;
		alert('<?php echo dol_escape_js($langs->transnoentities("ErrorFieldRequired", $langs->transnoentitiesnoconv("DatabaseName"))); ?>');
	}
	else if (! checkDatabaseName(document.forminstall.db_name.value))
	{
		ok=false;
		alert('<?php echo dol_escape_js($langs->transnoentities("ErrorFieldCanNotContainSpecialCharacters", $langs->transnoentitiesnoconv("DatabaseName"))); ?>');
	}
	// If create database asked
	else if (document.forminstall.db_create_database.checked == true && (document.forminstall.db_user_root.value == ''))
	{
		ok=false;
		alert('<?php echo dol_escape_js($langs->transnoentities("YouAskToCreateDatabaseSoRootRequired")); ?>');
		init_needroot();
	}
	// If create user asked
	else if (document.forminstall.db_create_user.checked == true && (document.forminstall.db_user_root.value == ''))
	{
		ok=false;
		alert('<?php echo dol_escape_js($langs->transnoentities("YouAskToCreateDatabaseUserSoRootRequired")); ?>');
		init_needroot();
	}

	return ok;
}


jQuery(document).ready(function() {	// TODO Test $( window ).load(function() to see if the init_needroot work better after a back

	var dbtype = jQuery("#db_type");

	dbtype.change(function () {
		if (dbtype.val() == 'sqlite' || dbtype.val() == 'sqlite3') {
			jQuery(".hidesqlite").hide();
		} else {
			jQuery(".hidesqlite").show();
		}

		// Automatically set default database ports and admin user
		if (dbtype.val() == 'mysql' || dbtype.val() == 'mysqli') {
			jQuery("#db_port").val(3306);
			jQuery("#db_user_root").val('root');
		} else if (dbtype.val() == 'pgsql') {
			jQuery("#db_port").val(5432);
			jQuery("#db_user_root").val('postgres');
		} else if (dbtype.val() == 'mssql') {
			jQuery("#db_port").val(1433);
			jQuery("#db_user_root").val('sa');
		}

	});

	jQuery("#db_create_database").click(function() {
		console.log("click on db_create_database");
		init_needroot();
	});
	jQuery("#db_create_user").click(function() {
		console.log("click on db_create_user");
		init_needroot();
	});
	<?php if ($force_install_noedit == 2 && empty($force_install_databasepass)) { ?>
	jQuery("#db_pass").focus();
	<?php } ?>

	init_needroot();
});
</script>


<?php

// $db->close();	Not database connexion yet

dolibarr_install_syslog("- fileconf: end");
pFooter($err, $setuplang, 'jscheckparam');
