<?php

/**
*	\brief		Launch a sql file
*	\param		sqlfile		Full path to sql file
*	\return		int			<0 if ko, >0 if ok
*/
function run_sql($sqlfile,$silent=1)
{
	global $db, $conf, $langs, $user;
	
	dolibarr_syslog("Admin.lib::run_sql run sql file ".$sqlfile, LOG_DEBUG);

	$ok=0;
	$error=0;
	$i=0;
	$buffer = '';
	$arraysql = Array();

	// Get version of database
	$versionarray=$db->getVersionArray();

	$fp = fopen($sqlfile,"r");
	if ($fp)
	{
		while (!feof ($fp))
		{
			$buf = fgets($fp, 4096);
			
			// Cas special de lignes autorisees pour certaines versions uniquement
			if (eregi('^-- V([0-9\.]+)',$buf,$reg))
			{
				$versioncommande=split('\.',$reg[1]);
				//print var_dump($versioncommande);
				//print var_dump($versionarray);
				if (sizeof($versioncommande) && sizeof($versionarray)
						&& versioncompare($versioncommande,$versionarray) <= 0)
				{
					// Version qualified, delete SQL comments
					$buf=eregi_replace('^-- V([0-9\.]+)','',$buf);
					//print "Ligne $i qualifi?e par version: ".$buf.'<br>';
				}
			}
			
			// Ajout ligne si non commentaire
			if (! eregi('^--',$buf)) $buffer .= $buf;

			//          print $buf.'<br>';

			if (eregi(';',$buffer))
			{
				// Found new request
				$arraysql[$i]=trim($buffer);
				$i++;
				$buffer='';
			}
		}
		
		if ($buffer) $arraysql[$i]=trim($buffer);
		fclose($fp);
	}

	// Loop on each request
	foreach($arraysql as $i=>$sql)
	{
		if ($sql)
		{
			// Ajout trace sur requete (eventuellement ? commenter si beaucoup de requetes)
			if (! $silent) print '<tr><td valign="top">'.$langs->trans("Request").' '.($i+1)." sql='".$sql."'</td></tr>\n";
			dolibarr_syslog('Admin.lib::run_sql Request '.($i+1)." sql='".$sql);

			if ($db->query($sql))
			{
				// 	          print '<td align="right">OK</td>';
			}
			else
			{
				$errno=$db->errno();
				$okerror=array( 'DB_ERROR_TABLE_ALREADY_EXISTS',
				'DB_ERROR_COLUMN_ALREADY_EXISTS',
				'DB_ERROR_KEY_NAME_ALREADY_EXISTS',
				'DB_ERROR_RECORD_ALREADY_EXISTS',
				'DB_ERROR_NOSUCHTABLE',
				'DB_ERROR_NOSUCHFIELD',
				'DB_ERROR_NO_FOREIGN_KEY_TO_DROP',
				'DB_ERROR_CANNOT_CREATE',    		// Qd contrainte deja existante
				'DB_ERROR_CANT_DROP_PRIMARY_KEY',
				'DB_ERROR_PRIMARY_KEY_ALREADY_EXISTS'
				);
				if (in_array($errno,$okerror))
				{
					//if (! $silent) print $langs->trans("OK");
				}
				else
				{
					if (! $silent) print '<tr><td valign="top" colspan="2">';
					if (! $silent) print '<div class="error">'.$langs->trans("Error")." ".$db->errno().": ".$sql."<br>".$db->error()."</font></td>";
					if (! $silent) print '</tr>';
					dolibarr_syslog('Admin.lib::run_sql Request '.($i+1)." Error ".$db->errno()." ".$sql."<br>".$db->error());
					$error++;
				}
			}

			if (! $silent) print '</tr>';
		}
	}

	if ($error == 0)
	{
		if (! $silent) print '<tr><td>'.$langs->trans("ProcessMigrateScript").'</td>';
		if (! $silent) print '<td align="right">'.$langs->trans("OK").'</td></tr>';
		$ok = 1;
	}
	else
	{
		if (! $silent) print '<tr><td>'.$langs->trans("ProcessMigrateScript").'</td>';
		if (! $silent) print '<td align="right"><font class="error">'.$langs->trans("KO").'</font></td></tr>';
		$ok = 0;
	}

	return $ok;
}
?>