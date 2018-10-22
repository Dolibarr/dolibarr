<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path = dirname(__FILE__).'/';

// Test if batch mode
if (substr($sapi_type, 0, 3) == 'cgi')
{
	echo "Error: You are using PHP for CGI. To execute ".$script_file." from command line, you must use PHP for CLI mode.\n";
	exit(-1);
}



require_once($path."../../htdocs/master.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/ldap.class.php");
require_once(DOL_DOCUMENT_ROOT."/user/class/usergroup.class.php");

// Global variables
$version = DOL_VERSION;
$error = 0;


/*
 * Main
 */

@set_time_limit(0);

// We always choose in mysql directory (Conversion is done by driver to translate SQL syntax)
$dir = DOL_DOCUMENT_ROOT."/install/mysql/tables/";
$sql='ALTER TABLE '.MAIN_DB_PREFIX.'commande_fournisseur_dispatch ADD COLUMN fk_reception integer DEFAULT NULL;';


$resql = $db->query($sql);
if(empty($resql)){
	var_dump($db->error);
}

$sql=" insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('RECEPTION_VALIDATE','Reception validated','Executed when a reception is validated','reception',22);";

$resql = $db->query($sql);
if(empty($resql)){
	print '<pre>';
	var_dump($db->error);
	print '</pre>';
}

$sql="insert into llx_c_action_trigger (code,label,description,elementtype,rang) values ('RECEPTION_SENTBYMAIL','Reception sent by mail','Executed when a reception is sent by mail','reception',22); ";

$resql = $db->query($sql);
if(empty($resql)){
        print '<pre>';
        var_dump($db->error);
        print '</pre>';
}


$sql=" ALTER TABLE ".MAIN_DB_PREFIX."commande_fournisseur_dispatch CHANGE comment comment TEXT;";
$resql = $db->query($sql);
if(empty($resql)){
	var_dump($db->error);
}




$ok = 0;
$handle = opendir($dir);
$tablefound = 0;
$tabledata = array();
if (is_resource($handle))
{
	while (($file = readdir($handle)) !== false)
	{
		if (preg_match('/\.sql$/i', $file) && preg_match('/^llx_/i', $file) && !preg_match('/\.key\.sql$/i', $file))
		{
			if (strpos($file, 'reception') !== false || strpos($file, 'commande_fournisseur_dispatch') !== false){
				$tablefound++;
				$tabledata[] = $file;
			}
		}
	}

	closedir($handle);
}



// Sort list of sql files on alphabetical order (load order is important)
sort($tabledata);
foreach ($tabledata as $file)
{
	$name = substr($file, 0, dol_strlen($file) - 4);
	$buffer = '';
	$fp = fopen($dir.$file, "r");
	if ($fp)
	{
		while (!feof($fp))
		{
			$buf = fgets($fp, 4096);
			if (substr($buf, 0, 2) <> '--')
			{
				$buf = preg_replace('/--(.+)*/', '', $buf);
				$buffer .= $buf;
			}
		}
		fclose($fp);

		$buffer = trim($buffer);
		if ($conf->db->type == 'mysql' || $conf->db->type == 'mysqli') // For Mysql 5.5+, we must replace type=innodb with ENGINE=innodb
		{
			$buffer = preg_replace('/type=innodb/i', 'ENGINE=innodb', $buffer);
		}
		else if ($conf->db->type == 'mssql')
		{
			$buffer = preg_replace('/type=innodb/i', '', $buffer);
			$buffer = preg_replace('/ENGINE=innodb/i', '', $buffer);
		}

		// Replace the prefix tables
		if ($dolibarr_main_db_prefix != 'llx_')
		{
			$buffer = preg_replace('/llx_/i', $dolibarr_main_db_prefix, $buffer);
		}

		//print "<tr><td>Creation de la table $name/td>";
		$requestnb++;

		$resql = $db->query($buffer, 0, 'dml');
		if ($resql)
		{
			// print "<td>OK requete ==== $buffer</td></tr>";
			$db->free($resql);
		}
		else
		{
			if ($db->errno() == 'DB_ERROR_TABLE_ALREADY_EXISTS' ||
				$db->errno() == 'DB_ERROR_TABLE_OR_KEY_ALREADY_EXISTS')
			{
				//print "<td>Deja existante</td></tr>";
			}
			else
			{
				print "<tr><td>".$langs->trans("CreateTableAndPrimaryKey", $name);
				print "<br>\n".$langs->trans("Request").' '.$requestnb.' : '.$buffer.' <br>Executed query : '.$db->lastquery;
				print "\n</td>";
				print '<td><font class="error">'.$langs->trans("ErrorSQL")." ".$db->errno()." ".$db->error().'</font></td></tr>';
				$error++;
			}
		}
	}
	else
	{
		print "<tr><td>".$langs->trans("CreateTableAndPrimaryKey", $name);
		print "</td>";
		print '<td><font class="error">'.$langs->trans("Error").' Failed to open file '.$dir.$file.'</td></tr>';
		$error++;
	}
}

if ($tablefound)
{
	if ($error == 0)
	{
		print '<tr><td>';
		print $langs->trans("TablesAndPrimaryKeysCreation").'</td><td><img src="../theme/eldy/img/tick.png" alt="Ok"></td></tr>';
		$ok = 1;
	}
}
else
{
	print '<tr><td>'.$langs->trans("ErrorFailedToFindSomeFiles", $dir).'</td><td><img src="../theme/eldy/img/error.png" alt="Error"></td></tr>';
}



// We always choose in mysql directory (Conversion is done by driver to translate SQL syntax)


$okkeys = 0;
$handle = opendir($dir);
$tablefound = 0;
$tabledata = array();
if (is_resource($handle))
{
	while (($file = readdir($handle)) !== false)
	{
		if (preg_match('/\.sql$/i', $file) && preg_match('/^llx_/i', $file) && preg_match('/\.key\.sql$/i', $file))
		{
			if (strpos($file, 'reception') !== false || strpos($file, 'commande_fournisseur_dispatch') !== false){
				$tablefound++;
				$tabledata[] = $file;
			}
		}
	}
	closedir($handle);
}

// Sort list of sql files on alphabetical order (load order is important)
sort($tabledata);
foreach ($tabledata as $file)
{
	$name = substr($file, 0, dol_strlen($file) - 4);
	//print "<tr><td>Creation de la table $name</td>";
	$buffer = '';
	$fp = fopen($dir.$file, "r");
	if ($fp)
	{
		while (!feof($fp))
		{
			$buf = fgets($fp, 4096);

			// Cas special de lignes autorisees pour certaines versions uniquement
			if ($choix == 1 && preg_match('/^--\sV([0-9\.]+)/i', $buf, $reg))
			{
				$versioncommande = explode('.', $reg[1]);
				//print var_dump($versioncommande);
				//print var_dump($versionarray);
				if (count($versioncommande) && count($versionarray) && versioncompare($versioncommande, $versionarray) <= 0)
				{
					// Version qualified, delete SQL comments
					$buf = preg_replace('/^--\sV([0-9\.]+)/i', '', $buf);
					//print "Ligne $i qualifiee par version: ".$buf.'<br>';
				}
			}
			if ($choix == 2 && preg_match('/^--\sPOSTGRESQL\sV([0-9\.]+)/i', $buf, $reg))
			{
				$versioncommande = explode('.', $reg[1]);
				//print var_dump($versioncommande);
				//print var_dump($versionarray);
				if (count($versioncommande) && count($versionarray) && versioncompare($versioncommande, $versionarray) <= 0)
				{
					// Version qualified, delete SQL comments
					$buf = preg_replace('/^--\sPOSTGRESQL\sV([0-9\.]+)/i', '', $buf);
					//print "Ligne $i qualifiee par version: ".$buf.'<br>';
				}
			}

			// Ajout ligne si non commentaire
			if (!preg_match('/^--/i', $buf))
				$buffer .= $buf;
		}
		fclose($fp);

		// Si plusieurs requetes, on boucle sur chaque
		$listesql = explode(';', $buffer);
		foreach ($listesql as $req)
		{
			$buffer = trim($req);
			if ($buffer)
			{
				// Replace the prefix tables
				if ($dolibarr_main_db_prefix != 'llx_')
				{
					$buffer = preg_replace('/llx_/i', $dolibarr_main_db_prefix, $buffer);
				}

				//print "<tr><td>Creation des cles et index de la table $name: '$buffer'</td>";
				$requestnb++;

				$resql = $db->query($buffer, 0, 'dml');
				if ($resql)
				{
					//print "<td>OK requete ==== $buffer</td></tr>";
					$db->free($resql);
				}
				else
				{
					if ($db->errno() == 'DB_ERROR_KEY_NAME_ALREADY_EXISTS' ||
						$db->errno() == 'DB_ERROR_CANNOT_CREATE' ||
						$db->errno() == 'DB_ERROR_PRIMARY_KEY_ALREADY_EXISTS' ||
						$db->errno() == 'DB_ERROR_TABLE_OR_KEY_ALREADY_EXISTS' ||
						preg_match('/duplicate key name/i', $db->error()))
					{
						//print "<td>Deja existante</td></tr>";
						$key_exists = 1;
					}
					else
					{
						print "<tr><td>".$langs->trans("CreateOtherKeysForTable", $name);
						print "<br>\n".$langs->trans("Request").' '.$requestnb.' : '.$db->lastqueryerror();
						print "\n</td>";
						print '<td><font class="error">'.$langs->trans("ErrorSQL")." ".$db->errno()." ".$db->error().'</font></td></tr>';
						$error++;
					}
				}
			}
		}
	}
	else
	{
		print "<tr><td>".$langs->trans("CreateOtherKeysForTable", $name);
		print "</td>";
		print '<td><font class="error">'.$langs->trans("Error")." Failed to open file ".$dir.$file."</font></td></tr>";
		$error++;
	}
}

if ($tablefound && $error == 0)
{
	print '<tr><td>';
	print $langs->trans("OtherKeysCreation").'</td><td><img src="../theme/eldy/img/tick.png" alt="Ok"></td></tr>';
	$okkeys = 1;
}




