<?php
/* Copyright (C) 2007 Cyrille de Lambert   <cyrille.delambert@auguria.net>
 * Copyright (C) 2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
   \file       htdocs/install/etape0.php
   \brief      Permet d'afficher et de confirmer le charset par rapport aux informations précédentes -> sélection suite à connexion'
   \version    $Id$
*/

define('DONOTLOADCONF',1);	// To avoid loading conf by file inc.php

include_once("./inc.php");

$setuplang=isset($_POST["selectlang"])?$_POST["selectlang"]:(isset($_GET["selectlang"])?$_GET["selectlang"]:'auto');
$langs->setDefaultLang($setuplang);

$langs->load("admin");
$langs->load("install");
$langs->load("errors");

$error = 0;

/**
 * Récuparation des information de connexion
 */
$userroot=isset($_POST["db_user_root"])?$_POST["db_user_root"]:"";
$passroot=isset($_POST["db_pass_root"])?$_POST["db_pass_root"]:"";
// Répertoire des pages dolibarr
$main_dir=isset($_POST["main_dir"])?trim($_POST["main_dir"]):'';


/*
 * Affichage page
 */

pHeader($langs->trans("ConfigurationFile"),"etape1");

// On reporte champ formulaire précédent pour propagation
if ($_POST["action"] == "set")
{
	umask(0);
    foreach($_POST as $cle=>$valeur)
    {
    	echo '<input type="hidden" name="'.$cle.'"  value="'.$valeur.'">';
		if (! eregi('^db_pass',$cle)) dolibarr_install_syslog("Choice for ".$cle." = ".$valeur);
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


/**
* 	Tentative de connexion a la base
*/
if (! $error)
{
	$result=include_once($main_dir."/lib/databases/".$_POST["db_type"].".lib.php");
	if ($result)
	{
		// If we ask database or user creation we need to connect as root
		if (! empty($_POST["db_create_database"]) && ! $userroot)
		{
			print '<div class="error">'.$langs->trans("YouAskDatabaseCreationSoDolibarrNeedToConnect",$_POST["db_name"]).'</div>';
			print '<br>';
			if (! $db->connected) print $langs->trans("BecauseConnectionFailedParametersMayBeWrong").'<br><br>';
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
				if ($_POST["db_type"] == 'mysql' ||$_POST["db_type"] == 'mysqli')
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
			$db = new DoliDb($_POST["db_type"],$_POST["db_host"],$userroot,$passroot,$databasefortest,$_POST["db_port"]);
			
			dolibarr_syslog("databasefortest=".$databasefortest." connected=".$db->connected." database_selected=".$db->database_selected, LOG_DEBUG);
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
		if (! $db->connected) print '<br>'.$langs->trans("BecauseConnectionFailedParametersMayBeWrong").'<br><br>';
		print $langs->trans("ErrorGoBackAndCorrectParameters");
		$error++;
}

/*
* Si creation database demandée, il est possible de faire un choix
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
	<table border="0" cellpadding="1" cellspacing="0">
	
	<tr><td align="center" class="label" colspan="3"><h3><?php echo $langs->trans("CharsetChoice");?></h3></td></tr>
	
	<tr>
		<td valign="top" class="label"><?php echo $langs->trans("CharacterSetClient"); ?></td>
		<td valign="top" class="label"><select name="character_set_client">
		<option value="ISO-8859-1">ISO-8859-1</option>
		<option value="UTF-8">UTF-8 <?php echo $langs->trans("Experimental") ?></option>
<!--
		<option>ISO-8859-15</option>
		<option>cp866</option>
		<option>cp1251</option>
		<option>cp1252</option>
		<option>KOI8-R</option>
		<option>BIG5</option>
		<option>GB2312</option>
		<option>BIG5-HKSCS</option>
		<option>Shift_JIS</option>
		<option>EUC-JP</option>
-->
		</select></td>
		<td class="label"><div class="comment"><?php echo $langs->trans("CharacterSetClientComment"); ?></div></td>
	</tr>

	<?php
	$defaultCharacterSet=$db->getDefaultCharacterSetDatabase();
	$defaultCollationConnection=$db->getDefaultCollationDatabase();
	$listOfCharacterSet=$db->getListOfCharacterSet();
	$listOfCollation=$db->getListOfCollation();

	
		?>
		<tr>
		<td valign="top" class="label"><?php echo $langs->trans("CharacterSetDatabase"); ?></td>
		<td valign="top" class="label">
		<?php 
		if (sizeof($listOfCharacterSet))
		{
			print '<select name="character_set_database" '.$disabled.'>';
			$selected="";
			foreach ($listOfCharacterSet as $characterSet)
			{
				if ($defaultCharacterSet == $characterSet['charset'] )
				{
					$selected="selected";
				}
				else
				{
					$selected="";
				}
				print '<option value="'.$characterSet['charset'].'" '.$selected.'>'.$characterSet['charset'].' ('.$characterSet['description'].')</option>';
			}
			print '</select>';
			if ($disabled=="disabled"){
				print '<input type="hidden" name="character_set_database"  value="'.$defaultCharacterSet.'">';
			}
		}
		else
		{
			print '<input type="text" name="character_set_database"  value="'.$defaultCharacterSet.'">';
		}
		?>
		</td>
		<td class="label"><div class="comment"><?php echo $langs->trans("CharacterSetDatabaseComment"); ?></div></td>
		</tr>
		<?php
	

	if ($defaultCollationConnection)
	{
		?>
		<tr>
		<td valign="top" class="label"><?php echo $langs->trans("CollationConnection"); ?></td>
		<td valign="top" class="label">
		<?php
		if (sizeof($listOfCollation))
		{
			print '<select name="dolibarr_main_db_collation" '.$disabled.'>';
			$selected="";
			foreach ($listOfCollation as $collation)
			{
				if ($defaultCollationConnection == $collation['collation'])
				{
					$selected="selected";
				}
				else
				{
					$selected="";
				}
				print '<option value="'.$collation['collation'].'" '.$selected.'>'.$collation['collation'].'</option>';
			}
			print '</select>';
			if ($disabled=="disabled"){
				print '<input type="hidden" name="dolibarr_main_db_collation"  value="'.$defaultCollationConnection.'">';
			}
		}
		else
		{
			print '<input type="text" name="dolibarr_main_db_collation"  value="'.$defaultCollationConnection.'">';
		}
		?>
		</td>
		<td class="label"><div class="comment"><?php echo $langs->trans("CollationConnectionComment"); ?></div></td>
		</tr>
		<?php
	}
	?>
	</table>
	<?php
}


pFooter($error,$setuplang);
?>