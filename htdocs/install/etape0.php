<?php
/* Copyright (C) 2007      Cyrille de Lambert   <cyrille.delambert@auguria.net>
 * Copyright (C) 2007-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2010-2011 Regis Houssin        <regis@dolibarr.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/install/etape0.php
 *	\ingroup	install
 *	\brief      Show and ask charset for database
 *	\version    $Id: etape0.php,v 1.42 2011/07/31 23:26:25 eldy Exp $
 */

define('DONOTLOADCONF',1);	// To avoid loading conf by file inc.php

include_once("./inc.php");

//print ">> ".$conf->db->character_set;
//print ">> ".$conf->db->dolibarr_main_db_collation;


$setuplang=isset($_POST["selectlang"])?$_POST["selectlang"]:(isset($_GET["selectlang"])?$_GET["selectlang"]:'auto');
$langs->setDefaultLang($setuplang);

$langs->load("admin");
$langs->load("install");
$langs->load("errors");

$error = 0;

// Recuparation des information de connexion
$userroot=isset($_POST["db_user_root"])?$_POST["db_user_root"]:"";
$passroot=isset($_POST["db_pass_root"])?$_POST["db_pass_root"]:"";
// Repertoire des pages dolibarr
$main_dir=isset($_POST["main_dir"])?trim($_POST["main_dir"]):'';

// Init "forced values" to nothing. "forced values" are used after an doliwamp install wizard.
$useforcedwizard=false;
if (file_exists("./install.forced.php")) { $useforcedwizard=true; include_once("./install.forced.php"); }
else if (file_exists("/etc/dolibarr/install.forced.php")) { $useforcedwizard=include_once("/etc/dolibarr/install.forced.php"); }

dolibarr_install_syslog("--- etape0: Entering etape0.php page");


/*
 *	View
 */

pHeader($langs->trans("ConfigurationFile"),"etape1");

// Test if we can run a first install process
if (! is_writable($conffile))
{
    print $langs->trans("ConfFileIsNotWritable",$conffiletoshow);
    pFooter(1,$setuplang,'jscheckparam');
    exit;
}

// On reporte champ formulaire precedent pour propagation
if ($_POST["action"] == "set")
{
	umask(0);
	foreach($_POST as $cle=>$valeur)
	{
		echo '<input type="hidden" name="'.$cle.'"  value="'.$valeur.'">';
		if (! preg_match('/^db_pass/i',$cle)) dolibarr_install_syslog("Choice for ".$cle." = ".$valeur);
	}
}

// Check parameters
if (empty($_POST["db_type"]))
{
	print '<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentities("DatabaseType")).'</div>';
	$error++;
}
if (empty($_POST["db_host"]))
{
	print '<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentities("Server")).'</div>';
	$error++;
}
if (empty($_POST["db_name"]))
{
	print '<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentities("DatabaseName")).'</div>';
	$error++;
}
if (empty($_POST["db_user"]))
{
	print '<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentities("Login")).'</div>';
	$error++;
}
if (! empty($_POST["db_port"]) && ! is_numeric($_POST["db_port"]))
{
    print '<div class="error">'.$langs->trans("ErrorBadValueForParameter",$_POST["db_port"],$langs->transnoentities("Port")).'</div>';
    $error++;
}

/**
 * 	Tentative de connexion a la base
 */
if (! $error)
{
	$result=@include_once($main_dir."/lib/databases/".$_POST["db_type"].".lib.php");
	if ($result)
	{
		// If we ask database or user creation we need to connect as root
		if (! empty($_POST["db_create_database"]) && ! $userroot)
		{
			print '<div class="error">'.$langs->trans("YouAskDatabaseCreationSoDolibarrNeedToConnect",$_POST["db_name"]).'</div>';
			print '<br>';
			if (empty($db->connected)) print $langs->trans("BecauseConnectionFailedParametersMayBeWrong").'<br><br>';
			print $langs->trans("ErrorGoBackAndCorrectParameters");
			$error++;
		}
		if (! empty($_POST["db_create_user"]) && ! $userroot)
		{
			print '<div class="error">'.$langs->trans("YouAskLoginCreationSoDolibarrNeedToConnect",$_POST["db_user"]).'</div>';
			print '<br>';
			if (! $db->connected) print $langs->trans("BecauseConnectionFailedParametersMayBeWrong").'<br><br>';
			print $langs->trans("ErrorGoBackAndCorrectParameters");
			$error++;
		}

		// If we need root access
		if (! $error && (! empty($_POST["db_create_database"]) || ! empty($_POST["db_create_user"])))
		{
			$databasefortest=$_POST["db_name"];
			if (! empty($_POST["db_create_database"]))
			{
				if ($_POST["db_type"] == 'mysql' || $_POST["db_type"] == 'mysqli')
				{
					$databasefortest='mysql';
				}
				elseif ($_POST["db_type"] == 'pgsql')
				{
					$databasefortest='postgres';
				}
				else
				{
					$databasefortest='mssql';
				}
			}
			//print $_POST["db_type"].",".$_POST["db_host"].",$userroot,$passroot,$databasefortest,".$_POST["db_port"];
			$db = new DoliDb($_POST["db_type"],$_POST["db_host"],$userroot,$passroot,$databasefortest,$_POST["db_port"]);

			dol_syslog("databasefortest=".$databasefortest." connected=".$db->connected." database_selected=".$db->database_selected, LOG_DEBUG);
			//print "databasefortest=".$databasefortest." connected=".$db->connected." database_selected=".$db->database_selected;

			if (empty($_POST["db_create_database"]) && $db->connected && ! $db->database_selected)
			{
				print '<div class="error">'.$langs->trans("ErrorConnectedButDatabaseNotFound",$_POST["db_name"]).'</div>';
				print '<br>';
				if (! $db->connected) print $langs->trans("IfDatabaseNotExistsGoBackAndUncheckCreate").'<br><br>';
				print $langs->trans("ErrorGoBackAndCorrectParameters");
				$error++;
			}
			elseif ($db->error && ! (! empty($_POST["db_create_database"]) && $db->connected))
			{
				print '<div class="error">'.$db->error.'</div>';
				if (! $db->connected) print $langs->trans("BecauseConnectionFailedParametersMayBeWrong").'<br><br>';
				print $langs->trans("ErrorGoBackAndCorrectParameters");
				$error++;
			}
		}
		// If we need simple access
		if (! $error && (empty($_POST["db_create_database"]) && empty($_POST["db_create_user"])))
		{
			$db = new DoliDb($_POST["db_type"],$_POST["db_host"],$_POST["db_user"],$_POST["db_pass"],$_POST["db_name"],$_POST["db_port"]);
			if ($db->error)
			{
				print '<div class="error">'.$db->error.'</div>';
				if (! $db->connected) print $langs->trans("BecauseConnectionFailedParametersMayBeWrong").'<br><br>';
				print $langs->trans("ErrorGoBackAndCorrectParameters");
				$error++;
			}
		}
	}
	else
	{
		print "<br>\nFailed to include_once(\"".$main_dir."/lib/databases/".$_POST["db_type"].".lib.php\")<br>\n";
		print '<div class="error">'.$langs->trans("ErrorWrongValueForParameter",$langs->transnoentities("WebPagesDirectory")).'</div>';
		print $langs->trans("ErrorGoBackAndCorrectParameters");
		$error++;
	}
}

else
{
	if (isset($db)) print $db->lasterror();
	if (isset($db) && ! $db->connected) print '<br>'.$langs->trans("BecauseConnectionFailedParametersMayBeWrong").'<br><br>';
	print $langs->trans("ErrorGoBackAndCorrectParameters");
	$error++;
}

/*
 * Si creation database demandee, il est possible de faire un choix
 */
$disabled="";
if (! $error && ! empty($_POST["db_create_database"]))
{
	$disabled="";
}else{
	$disabled="disabled";
}

if (! $error && $db->connected)
{
	if (! empty($_POST["db_create_database"]))
	{
		$result=$db->select_db($_POST["db_name"]);
		if ($result)
		{
			print '<div class="error">'.$langs->trans("ErrorDatabaseAlreadyExists",$_POST["db_name"]).'</div>';
			print $langs->trans("IfDatabaseExistsGoBackAndCheckCreate").'<br><br>';
			print $langs->trans("ErrorGoBackAndCorrectParameters");
			$error++;
		}
	}
}

if (! $error && $db->connected)
{
	?>
<table border="0" cellpadding="1" cellspacing="0" width="100%">

	<tr>
		<td align="center" class="label" colspan="3">
		<h3><?php echo $langs->trans("CharsetChoice");?></h3>
		</td>
	</tr>

	<?php
	if (! empty($_POST["db_create_database"]))	// If we create database, we force default value
	{
		$defaultCharacterSet=$db->forcecharset;
		$defaultCollationConnection=$db->forcecollate;
	}
	else	// If already created, we take current value
	{
		$defaultCharacterSet=$db->getDefaultCharacterSetDatabase();
		$defaultCollationConnection=$db->getDefaultCollationDatabase();
	}

	$listOfCharacterSet=$db->getListOfCharacterSet();
	$listOfCollation=$db->getListOfCollation();

	// Choice of dolibarr_main_db_character_set
	?>
	<tr>
		<td valign="top" class="label"><?php echo $langs->trans("CharacterSetDatabase"); ?></td>
		<td valign="top" class="label"><?php
		$nbofchoice=0;
		if (sizeof($listOfCharacterSet))
		{
			print '<select name="dolibarr_main_db_character_set" '.$disabled.'>';
			$selected="";
			foreach ($listOfCharacterSet as $characterSet)
			{
				$linedisabled=false;

				// We keep only utf8
				//if (($_POST["db_type"] == 'mysql' ||$_POST["db_type"] == 'mysqli') && ! preg_match('/(utf8|latin1)/i',$characterSet['charset'])) $linedisabled=true;
				if (($_POST["db_type"] == 'mysql' ||$_POST["db_type"] == 'mysqli') && ! preg_match('/utf8$/i',$characterSet['charset'])) $linedisabled=true;

				if ($defaultCharacterSet == $characterSet['charset'] )
				{
					$selected="selected";
				}
				else
				{
					$selected="";
				}
				if (! $linedisabled) $nbofchoice++;
				print '<option value="'.$characterSet['charset'].'" '.$selected.($linedisabled?' disabled="true"':'').'>'.$characterSet['charset'].' ('.$characterSet['description'].')</option>';
			}
			print '</select>';
			if ($disabled=="disabled")
			{
				print '<input type="hidden" name="dolibarr_main_db_character_set" value="'.$defaultCharacterSet.'">';
			}
		}
		else
		{
			print '<input type="text" name="dolibarr_main_db_character_set" value="'.$defaultCharacterSet.'">';
		}
        if ($nbofchoice > 1) {
		?></td>
		<td class="label">
		<div class="comment"><?php if ($nbofchoice > 1) echo $langs->trans("CharacterSetDatabaseComment"); ?></div>
		</td>
		<?php } ?>
	</tr>
	<?php

	// Choice of dolibarr_main_db_collation
	if ($defaultCollationConnection)
	{
		?>
	<tr>
		<td valign="top" class="label"><?php echo $langs->trans("CollationConnection"); ?></td>
		<td valign="top" class="label"><?php
		$nbofchoice=0;
		if (sizeof($listOfCollation))
		{
			print '<select name="dolibarr_main_db_collation" '.$disabled.'>';
			$selected="";
			foreach ($listOfCollation as $collation)
			{
				$linedisabled=false;

				// We keep only utf8 and iso
                //if (($_POST["db_type"] == 'mysql' ||$_POST["db_type"] == 'mysqli') && ! preg_match('/(utf8_general|latin1_swedish)/i',$collation['collation'])) $linedisabled=true;
                if (($_POST["db_type"] == 'mysql' ||$_POST["db_type"] == 'mysqli') && ! preg_match('/utf8_general/i',$collation['collation'])) $linedisabled=true;

				if ($defaultCollationConnection == $collation['collation'])
				{
					$selected="selected";
				}
				else
				{
					$selected="";
				}
                if (! $linedisabled) $nbofchoice++;
				print '<option value="'.$collation['collation'].'" '.$selected.($linedisabled?' disabled="true"':'').'>'.$collation['collation'].'</option>';
			}
			print '</select>';
			if ($disabled=="disabled"){
				print '<input type="hidden" name="dolibarr_main_db_collation" value="'.$defaultCollationConnection.'">';
			}
		}
		else
		{
			print '<input type="text" name="dolibarr_main_db_collation" value="'.$defaultCollationConnection.'">';
		}
        if ($nbofchoice > 1) {
		?></td>
		<td class="label">
		<div class="comment"><?php if ($nbofchoice > 1) echo $langs->trans("CollationConnectionComment"); ?></div>
		</td>
        <?php } ?>
	</tr>
	<?php
}
?>
</table>
<?php
}

dolibarr_install_syslog("--- install/etape0.php end", LOG_INFO);

pFooter($error,$setuplang);
?>