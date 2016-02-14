<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2004      Sebastien DiCintio   <sdicintio@ressource-toi.org>
 * Copyright (C) 2005-2011 Regis Houssin        <regis.houssin@capnetworks.com>
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
 *       \file       htdocs/install/fileconf.php
 *       \ingroup    install
 *       \brief      Ask all informations required to build Dolibarr htdocs/conf/conf.php file (will be wrote on disk on next page)
 */

include_once 'inc.php';

$err=0;

$setuplang=GETPOST("selectlang",'',3)?GETPOST("selectlang",'',3):(isset($_GET["lang"])?$_GET["lang"]:'auto');
$langs->setDefaultLang($setuplang);

$langs->load("install");
$langs->load("errors");

dolibarr_install_syslog("--- fileconf: entering fileconf.php page");

// You can force preselected values of the config step of Dolibarr by adding a file
// install.forced.php into directory htdocs/install (This is the case with some wizard
// installer like DoliWamp, DoliMamp or DoliBuntu).
// We first init "forced values" to nothing.
if (! isset($force_install_noedit))			$force_install_noedit='';	// 1=To block var specific to distrib, 2 to block all technical parameters
if (! isset($force_install_type))				$force_install_type='';
if (! isset($force_install_dbserver))			$force_install_dbserver='';
if (! isset($force_install_port))				$force_install_port='';
if (! isset($force_install_database))			$force_install_database='';
if (! isset($force_install_prefix))			$force_install_prefix='';
if (! isset($force_install_createdatabase))	$force_install_createdatabase='';
if (! isset($force_install_databaselogin))		$force_install_databaselogin='';
if (! isset($force_install_databasepass))		$force_install_databasepass='';
if (! isset($force_install_databaserootlogin))	$force_install_databaserootlogin='';
if (! isset($force_install_databaserootpass))	$force_install_databaserootpass='';
// Now we load forced value from install.forced.php file.
$useforcedwizard=false;
$forcedfile="./install.forced.php";
if ($conffile == "/etc/dolibarr/conf.php") $forcedfile="/etc/dolibarr/install.forced.php";	// Must be after inc.php
if (@file_exists($forcedfile)) {
	$useforcedwizard=true; include_once $forcedfile;
}

//$force_install_message='This is the message';
//$force_install_noedit=1;


/*
 *	View
 */

session_start();	// To be able to keep info into session (used for not loosing pass during navigation. pass must not transit throug parmaeters)

pHeader($langs->trans("ConfigurationFile"),"step1","set","",(empty($force_dolibarr_js_JQUERY)?'':$force_dolibarr_js_JQUERY.'/'));

// Test if we can run a first install process
if (! is_writable($conffile))
{
    print $langs->trans("ConfFileIsNotWritable", $conffiletoshow);
	dolibarr_install_syslog("fileconf: config file is not writable", LOG_WARNING);
	dolibarr_install_syslog("--- fileconf: end");
    pFooter(1,$setuplang,'jscheckparam');
    exit;
}

if (! empty($force_install_message))
{
    print '<div><table><tr><td valign="middle"><img src="../theme/common/information.png" style="height:40px;"></td><td valign="middle">'.$langs->trans($force_install_message).'</td></tr></table>';

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


<table class="nobordernopadding<?php if ($force_install_noedit) print ' hidewhennoedit'; ?>">

	<tr>
		<td colspan="3" class="label" align="center">
		<h3><?php echo $langs->trans("WebServer"); ?></h3>
		</td>
	</tr>

	<!-- Documents root $dolibarr_main_document_root -->
	<tr>
	<?php
	print '<td valign="top" class="label"><b>';
	print $langs->trans("WebPagesDirectory");
	print "</b></td>";

	if(! isset($dolibarr_main_url_root) || dol_strlen($dolibarr_main_url_root) == 0)
	{
	    //print "x".$_SERVER["SCRIPT_FILENAME"]." y".$_SERVER["DOCUMENT_ROOT"];

	    // Si le php fonctionne en CGI, alors SCRIPT_FILENAME vaut le path du php et
	    // ce n'est pas ce qu'on veut. Dans ce cas, on propose $_SERVER["DOCUMENT_ROOT"]
	    if (preg_match('/^php$/i',$_SERVER["SCRIPT_FILENAME"]) || preg_match('/[\\/]php$/i',$_SERVER["SCRIPT_FILENAME"]) || preg_match('/php\.exe$/i',$_SERVER["SCRIPT_FILENAME"]))
	    {
	        $dolibarr_main_document_root=$_SERVER["DOCUMENT_ROOT"];

	        if (! preg_match('/[\\/]dolibarr[\\/]htdocs$/i',$dolibarr_main_document_root))
	        {
	            $dolibarr_main_document_root.="/dolibarr/htdocs";
	        }
	    }
	    else
	    {
	        $dolibarr_main_document_root = substr($_SERVER["SCRIPT_FILENAME"],0,dol_strlen($_SERVER["SCRIPT_FILENAME"]) - 21);
	        // Nettoyage du path propose
	        // Gere les chemins windows avec double "\"
	        $dolibarr_main_document_root = str_replace('\\\\','/',$dolibarr_main_document_root);

	        // Supprime les slash ou antislash de fins
	        $dolibarr_main_document_root = preg_replace('/[\\/]+$/','',$dolibarr_main_document_root);
	    }
	}
	?>
		<td class="label" valign="top"><?php
		if ($force_install_noedit) print '<input type="hidden" value="'.$dolibarr_main_document_root.'" name="main_dir">';
		print '<input type="text" size="60" value="'.$dolibarr_main_document_root.'"'.(empty($force_install_noedit)?'':' disabled').' name="main_dir'.(empty($force_install_noedit)?'':'_bis').'">';
		?></td>
		<td class="comment"><?php
		print $langs->trans("WithNoSlashAtTheEnd")."<br>";
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
		<td valign="top" class="label"><b> <?php print $langs->trans("DocumentsDirectory"); ?></b>
		</td>
		<?php
		if (empty($dolibarr_main_data_root))
		{
		    // Si le repertoire documents non defini, on en propose un par defaut
		    if (empty($force_install_main_data_root))
		    {
		        $dolibarr_main_data_root=preg_replace("/\/htdocs$/","",$dolibarr_main_document_root);
		        $dolibarr_main_data_root.="/documents";
		    }
		    else
		    {
		        $dolibarr_main_data_root=$force_install_main_data_root;
		    }
		}
		?>
		<td class="label" valign="top"><?php
		if ($force_install_noedit) print '<input type="hidden" value="'.$dolibarr_main_data_root.'" name="main_data_dir">';
		print '<input type="text" size="60" value="'.$dolibarr_main_data_root.'"'.(empty($force_install_noedit)?'':' disabled').' name="main_data_dir'.(empty($force_install_noedit)?'':'_bis').'">';
		?></td>
		<td class="comment"><?php
		print $langs->trans("WithNoSlashAtTheEnd")."<br>";
		print $langs->trans("DirectoryRecommendation")."<br>";
		print $langs->trans("Examples").":<br>";
		?>
		<ul>
			<li>/var/lib/dolibarr/documents</li>
			<li>C:/My Documents/dolibarr/</li>
		</ul>
		</td>
	</tr>

	<!-- Root URL $dolibarr_main_url_root -->
	<?php
	if (! empty($main_url)) $dolibarr_main_url_root=$main_url;
	if (empty($dolibarr_main_url_root))
	{
	    // If defined (Ie: Apache with Linux)
	    if (isset($_SERVER["SCRIPT_URI"])) {
	        $dolibarr_main_url_root=$_SERVER["SCRIPT_URI"];
	    }
	    // If defined (Ie: Apache with Caudium)
	    elseif (isset($_SERVER["SERVER_URL"]) && isset($_SERVER["DOCUMENT_URI"])) {
	        $dolibarr_main_url_root=$_SERVER["SERVER_URL"].$_SERVER["DOCUMENT_URI"];
	    }
	    // If SCRIPT_URI, SERVER_URL, DOCUMENT_URI not defined (Ie: Apache 2.0.44 for Windows)
	    else
	    {
	        $proto='http';
	        if (! empty($_SERVER["HTTP_HOST"])) $serverport=$_SERVER["HTTP_HOST"];
	        else $serverport=$_SERVER["SERVER_NAME"];
	        $dolibarr_main_url_root=$proto."://".$serverport.$_SERVER["SCRIPT_NAME"];
	    }
	    // Clean proposed URL
	    $dolibarr_main_url_root = preg_replace('/\/fileconf\.php$/','',$dolibarr_main_url_root);	// Remove the /fileconf.php
	    $dolibarr_main_url_root = preg_replace('/\/$/','',$dolibarr_main_url_root);					// Remove the /
	    $dolibarr_main_url_root = preg_replace('/\/index\.php$/','',$dolibarr_main_url_root);		// Remove the /index.php
	    $dolibarr_main_url_root = preg_replace('/\/install$/','',$dolibarr_main_url_root);			// Remove the /install
	}
	?>
	<tr>
		<td valign="top" class="label"><b> <?php echo $langs->trans("URLRoot"); ?></b>
		</td>
		<td valign="top" class="label"><?php
		if ($force_install_noedit) print '<input type="hidden" value="'.$dolibarr_main_url_root.'" name="main_url">';
		print '<input type="text" size="60" value="'.$dolibarr_main_url_root.'"'.(empty($force_install_noedit)?'':' disabled').' name="main_url'.(empty($force_install_noedit)?'':'_bis').'">';
		?></td>
		<td class="comment"><?php print $langs->trans("Examples").":<br>"; ?>
		<ul>
			<li>http://localhost/</li>
			<li>http://www.myserver.com:8180/dolibarr</li>
		</ul>
		</td>
	</tr>

	<?php
	if (! empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == 'on') {   // Enabled if the installation process is "https://"
	    ?>
	<tr>
		<td valign="top" class="label"><?php echo $langs->trans("ForceHttps"); ?></td>
		<td class="label" valign="top"><input type="checkbox"
			name="main_force_https"
			<?php if (! empty($force_install_mainforcehttps)) print ' checked'; ?>></td>
		<td class="comment"><?php echo $langs->trans("CheckToForceHttps"); ?>
		</td>

	</tr>
	<?php
	}
	?>

	<!-- Dolibarr database -->

	<tr>
		<td colspan="3" class="label" align="center"><br>
		<h3><?php echo $langs->trans("DolibarrDatabase"); ?></h3>
		</td>
	</tr>

	<tr>
	<td class="label" valign="top"><b> <?php echo $langs->trans("DatabaseName"); ?>
	</b></td>

	<td class="label" valign="top"><input type="text" id="db_name"
				name="db_name"
				value="<?php echo (! empty($dolibarr_main_db_name))?$dolibarr_main_db_name:($force_install_database?$force_install_database:'dolibarr'); ?>"></td>
	<td class="comment"><?php echo $langs->trans("DatabaseName"); ?></td>
	</tr>


	<?php
	if (!isset($dolibarr_main_db_host))
	{
	    $dolibarr_main_db_host = "localhost";
	}
	?>
	<tr>
		<!-- Driver type -->
		<td valign="top" class="label"><b> <?php echo $langs->trans("DriverType"); ?>
		</b></td>

		<td class="label"><?php

		$defaultype=! empty($dolibarr_main_db_type)?$dolibarr_main_db_type:($force_install_type?$force_install_type:'mysqli');

		$modules = array();
		$nbok = $nbko = 0;
		$option='';

		// Scan les drivers
		$dir=DOL_DOCUMENT_ROOT.'/core/db';
		$handle=opendir($dir);
		if (is_resource($handle))
		{
		    while (($file = readdir($handle))!==false)
		    {
		        if (is_readable($dir."/".$file) && preg_match('/^(.*)\.class\.php$/i',$file,$reg))
		        {
		            $type=$reg[1];
                    if ($type === 'DoliDB') continue; // Skip abstract class
                    $class='DoliDB'.ucfirst($type);
                    include_once $dir."/".$file;

                    if ($type == 'sqlite') continue;    // We hide sqlite because support can't be complete until sqlite does not manage foreign key creation after table creation (ALTER TABLE child ADD CONSTRAINT not supported)
                    if ($type == 'sqlite3') continue;   // We hide sqlite3 because support can't be complete until sqlite does not manage foreign key creation after table creation (ALTER TABLE child ADD CONSTRAINT not supported)

		            // Version min of database
                    $versionbasemin=explode('.',$class::VERSIONMIN);
                    $note='('.$class::LABEL.' >= '.$class::VERSIONMIN.')';

		            // Switch to mysql if mysqli is not present
		            if ($defaultype=='mysqli' && !function_exists('mysqli_connect')) $defaultype = 'mysql';

		            // Show line into list
		            if ($type=='mysql')  { $testfunction='mysql_connect'; $testclass=''; }
		            if ($type=='mysqli') { $testfunction='mysqli_connect'; $testclass=''; }
		            if ($type=='pgsql')  { $testfunction='pg_connect'; $testclass=''; }
		            if ($type=='mssql')  { $testfunction='mssql_connect'; $testclass=''; }
		        	if ($type=='sqlite') { $testfunction=''; $testclass='PDO'; }
		            if ($type=='sqlite3') { $testfunction=''; $testclass='SQLite3'; }
		            $option.='<option value="'.$type.'"'.($defaultype == $type?' selected':'');
		            if ($testfunction && ! function_exists($testfunction)) $option.=' disabled';
		            if ($testclass && ! class_exists($testclass)) $option.=' disabled';
		            $option.='>';
		            $option.=$type.'&nbsp; &nbsp;';
		            if ($note) $option.=' '.$note;
		            // Deprecated and experimental
					if ($type=='mysql') $option.=' ' . $langs->trans("Deprecated");
		            elseif ($type=='mssql')  $option.=' '.$langs->trans("VersionExperimental");
		            elseif ($type=='sqlite') $option.=' '.$langs->trans("VersionExperimental");
		            elseif ($type=='sqlite3') $option.=' '.$langs->trans("VersionExperimental");
		            // No available
		            elseif (! function_exists($testfunction)) $option.=' - '.$langs->trans("FunctionNotAvailableInThisPHP");
		            $option.='</option>';
		        }
		    }
		}

		if ($force_install_noedit && $force_install_type) print '<input id="db_type" type="hidden" value="'.$force_install_type.'" name="db_type">';
		print '<select id="db_type" name="db_type'.(empty($force_install_noedit) || empty($force_install_type)?'':'_bis').'"'.($force_install_noedit && $force_install_type?' disabled':'').'>';
		print $option;
		print '</select>';

		?></td>
		<td class="comment"><?php echo $langs->trans("DatabaseType"); ?></td>

	</tr>

	<tr class="hidesqlite">
		<td valign="top" class="label"><b> <?php echo $langs->trans("Server"); ?>
		</b></td>
		<td valign="top" class="label"><input type="text"
			name="db_host<?php print ($force_install_noedit==2 && $force_install_dbserver)?'_bis':''; ?>"
			<?php if ($force_install_noedit==2 && $force_install_dbserver) print ' disabled'; ?>
			value="<?php print (! empty($dolibarr_main_db_host))?$dolibarr_main_db_host:(empty($force_install_dbserver)?'localhost':$force_install_dbserver); ?>">
			<?php if ($force_install_noedit==2 && $force_install_dbserver) print '<input type="hidden" name="db_host" value="'.((! empty($dolibarr_main_db_host))?$dolibarr_main_db_host:$force_install_dbserver).'">'; ?>
		</td>
		<td class="comment"><?php echo $langs->trans("ServerAddressDescription"); ?>
		</td>

	</tr>

	<tr class="hidesqlite">
		<td valign="top" class="label"><?php echo $langs->trans("Port"); ?></td>
		<td valign="top" class="label"><input type="text"
			name="db_port<?php print ($force_install_noedit==2 && $force_install_port)?'_bis':''; ?>"
			<?php if ($force_install_noedit==2 && $force_install_port) print ' disabled'; ?>
			value="<?php print (! empty($dolibarr_main_db_port))?$dolibarr_main_db_port:$force_install_port; ?>">
			<?php if ($force_install_noedit==2 && $force_install_port) print '<input type="hidden" name="db_port" value="'.((! empty($dolibarr_main_db_port))?$dolibarr_main_db_port:$force_install_port).'">'; ?>
		</td>
		<td class="comment"><?php echo $langs->trans("ServerPortDescription"); ?>
		</td>

	</tr>

	<tr class="hidesqlite">
		<td class="label" valign="top"><?php echo $langs->trans("DatabasePrefix"); ?>
		</td>

		<td class="label" valign="top"><input type="text" id="db_prefix"
			name="db_prefix"
			value="<?php echo (! empty($dolibarr_main_db_prefix))?$dolibarr_main_db_prefix:($force_install_prefix?$force_install_prefix:'llx_'); ?>"></td>
		<td class="comment"><?php echo $langs->trans("DatabasePrefix"); ?></td>
	</tr>

	<tr class="hidesqlite">
		<td class="label" valign="top"><?php echo $langs->trans("CreateDatabase"); ?>
		</td>

		<td class="label" valign="top"><input type="checkbox"
			id="db_create_database" name="db_create_database"
			<?php if ($force_install_createdatabase) print ' checked'; ?>></td>
		<td class="comment"><?php echo $langs->trans("CheckToCreateDatabase"); ?>
		</td>
	</tr>

	<tr class="hidesqlite">
		<td class="label" valign="top"><b><?php echo $langs->trans("Login"); ?></b>
		</td>
		<td class="label" valign="top"><input type="text" id="db_user"
			name="db_user"
			value="<?php print (! empty($dolibarr_main_db_user))?$dolibarr_main_db_user:$force_install_databaselogin; ?>"></td>
		<td class="comment"><?php echo $langs->trans("AdminLogin"); ?></td>
	</tr>

	<tr class="hidesqlite">
		<td class="label" valign="top"><b><?php echo $langs->trans("Password"); ?></b>
		</td>
		<td class="label" valign="top"><input type="password" id="db_pass" autocomplete="off"
			name="db_pass"
			value="<?php
			//$autofill=((! empty($dolibarr_main_db_pass))?$dolibarr_main_db_pass:$force_install_databasepass);
			$autofill=((! empty($_SESSION['dol_save_pass']))?$_SESSION['dol_save_pass']:$force_install_databasepass);
			if (! empty($dolibarr_main_prod)) $autofill='';
			print dol_escape_htmltag($autofill);
			?>"></td>
		<td class="comment"><?php echo $langs->trans("AdminPassword"); ?></td>
	</tr>

	<tr class="hidesqlite">
		<td class="label" valign="top"><?php echo $langs->trans("CreateUser"); ?>
		</td>

		<td class="label" valign="top"><input type="checkbox"
			id="db_create_user" name="db_create_user"
			<?php if (! empty($force_install_createuser)) print ' checked'; ?>></td>
		<td class="comment"><?php echo $langs->trans("CheckToCreateUser"); ?>
		</td>
	</tr>


	<!-- Super access -->
	<?php
	$force_install_databaserootlogin=preg_replace('/__SUPERUSERLOGIN__/','root',$force_install_databaserootlogin);
	$force_install_databaserootpass=preg_replace('/__SUPERUSERPASSWORD__/','',$force_install_databaserootpass);
	?>
	<tr class="hidesqlite hideroot">
		<td colspan="3" class="label" align="center"><br>
		<h3><?php echo $langs->trans("DatabaseSuperUserAccess"); ?></h3>
		</td>
	</tr>

	<tr class="hidesqlite hideroot">
		<td class="label" valign="top"><b><?php echo $langs->trans("Login"); ?></b></td>
		<td class="label" valign="top"><input type="text" id="db_user_root"
			name="db_user_root" class="needroot"
			value="<?php print (! empty($db_user_root))?$db_user_root:$force_install_databaserootlogin; ?>"></td>
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
		<td class="label" valign="top"><b><?php echo $langs->trans("Password"); ?></b>
		</td>
		<td class="label" valign="top"><input type="password" autocomplete="off"
			id="db_pass_root" name="db_pass_root" class="needroot"
			value="<?php
			$autofill=((! empty($db_pass_root))?$db_pass_root:$force_install_databaserootpass);
			if (! empty($dolibarr_main_prod)) $autofill='';	// Do not autofill password if instance is a production instance
			if (! empty($_SERVER["SERVER_NAME"]) && ! in_array($_SERVER["SERVER_NAME"], array('127.0.0.1', 'localhost'))) $autofill='';	// Do not autofill password for remote access
			print dol_escape_htmltag($autofill);
			?>"></td>
		<td class="comment"><?php echo $langs->trans("KeepEmptyIfNoPassword"); ?>
		</td>
	</tr>

</table>
</div>

<script type="text/javascript">
jQuery(document).ready(function() {

	jQuery("#db_type").change(function() {
		if (jQuery("#db_type").val()=='sqlite' || jQuery("#db_type").val()=='sqlite3') { jQuery(".hidesqlite").hide(); }
		else  { jQuery(".hidesqlite").show(); }
	});

	function init_needroot()
	{
		/*alert(jQuery("#db_create_database").prop("checked")); */
		if (jQuery("#db_create_database").is(":checked") || jQuery("#db_create_user").is(":checked"))
		{
			jQuery(".hideroot").show();
			jQuery(".needroot").removeAttr('disabled');
		}
		else
		{
			jQuery(".hideroot").hide();
			jQuery(".needroot").prop('disabled', true);
		}
	}

	init_needroot();
	jQuery("#db_create_database").click(function() {
		init_needroot();
	});
	jQuery("#db_create_user").click(function() {
		init_needroot();
	});
	<?php if ($force_install_noedit && empty($force_install_databasepass)) { ?>
	jQuery("#db_pass").focus();
	<?php } ?>
});

function checkDatabaseName(databasename) {
	if (databasename.match(/[;\.]/)) { return false; }
	return true;
}

function jscheckparam()
{
	ok=true;

	if (document.forminstall.main_dir.value == '')
	{
		ok=false;
		alert('<?php echo dol_escape_js($langs->transnoentities("ErrorFieldRequired",$langs->transnoentitiesnoconv("WebPagesDirectory"))); ?>');
	}
	else if (document.forminstall.main_data_dir.value == '')
	{
		ok=false;
		alert('<?php echo dol_escape_js($langs->transnoentities("ErrorFieldRequired",$langs->transnoentitiesnoconv("DocumentsDirectory"))); ?>');
	}
	else if (document.forminstall.main_url.value == '')
	{
		ok=false;
		alert('<?php echo dol_escape_js($langs->transnoentities("ErrorFieldRequired",$langs->transnoentitiesnoconv("URLRoot"))); ?>');
	}
	else if (document.forminstall.db_host.value == '')
	{
		ok=false;
		alert('<?php echo dol_escape_js($langs->transnoentities("ErrorFieldRequired",$langs->transnoentitiesnoconv("Server"))); ?>');
	}
	else if (document.forminstall.db_name.value == '')
	{
		ok=false;
		alert('<?php echo dol_escape_js($langs->transnoentities("ErrorFieldRequired",$langs->transnoentitiesnoconv("DatabaseName"))); ?>');
	}
	else if (! checkDatabaseName(document.forminstall.db_name.value))
	{
		ok=false;
		alert('<?php echo dol_escape_js($langs->transnoentities("ErrorSpecialCharNotAllowedForField",$langs->transnoentitiesnoconv("DatabaseName"))); ?>');
	}
	// If create database asked
	else if (document.forminstall.db_create_database.checked == true && (document.forminstall.db_user_root.value == ''))
	{
		ok=false;
		alert('<?php echo dol_escape_js($langs->transnoentities("YouAskToCreateDatabaseSoRootRequired")); ?>');
	}
	else if (document.forminstall.db_create_database.checked == true && (document.forminstall.db_user_root.value == ''))
	{
		ok=false;
		alert('<?php echo dol_escape_js($langs->transnoentities("YouAskToCreateDatabaseSoRootRequired")); ?>');
	}
	// If create user asked
	else if (document.forminstall.db_create_user.checked == true && (document.forminstall.db_user_root.value == ''))
	{
		ok=false;
		alert('<?php echo dol_escape_js($langs->transnoentities("YouAskToCreateDatabaseUserSoRootRequired")); ?>');
	}
	else if (document.forminstall.db_create_user.checked == true && (document.forminstall.db_user_root.value == ''))
	{
		ok=false;
		alert('<?php echo dol_escape_js($langs->transnoentities("YouAskToCreateDatabaseUserSoRootRequired")); ?>');
	}

	return ok;
}
</script>


<?php

// $db->close();	Not database connexion yet

dolibarr_install_syslog("--- fileconf: end");
pFooter($err,$setuplang,'jscheckparam');
