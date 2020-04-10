<?php
/* Copyright (C) 2014-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2014-2018 Frederic France      <frederic.france@netlogic.fr>
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
 * or see https://www.gnu.org/
 */

/**
 *  \file           htdocs/core/actions_printing.inc.php
 *  \brief          Code for actions print_file to print file with calling trigger
 */


// $action must be defined
// $db, $user, $conf, $langs must be defined
// Filename to print must be provided into 'file' parameter

// Print file
if ($action == 'print_file' && $user->rights->printing->read) {
	$langs->load("printing");
	require_once DOL_DOCUMENT_ROOT.'/core/modules/printing/modules_printing.php';
	$objectprint = new PrintingDriver($db);
	$list = $objectprint->listDrivers($db, 10);
	if (!empty($list)) {
		$errorprint = 0;
		$printerfound = 0;
		foreach ($list as $driver) {
			require_once DOL_DOCUMENT_ROOT.'/core/modules/printing/'.$driver.'.modules.php';
			$langs->load($driver);
			$classname = 'printing_'.$driver;
			$printer = new $classname($db);
			//print '<pre>'.print_r($printer, true).'</pre>';

			if (!empty($conf->global->{$printer->active}))
			{
				$printerfound++;

				$subdir = '';
				$module = GETPOST('printer', 'alpha');
				switch ($module)
				{
					case 'livraison' :
						$subdir = 'receipt';
						$module = 'expedition';
						break;
					case 'expedition' :
						$subdir = 'sending';
						break;
					case 'commande_fournisseur' :
						$module = 'fournisseur';
						$subdir = 'commande';
						break;
				}
				try {
					$ret = $printer->printFile(GETPOST('file', 'alpha'), $module, $subdir);
					if ($ret > 0) {
						//print '<pre>'.print_r($printer->errors, true).'</pre>';
						setEventMessages($printer->error, $printer->errors, 'errors');
					}
					if ($ret == 0)
					{
						//print '<pre>'.print_r($printer->errors, true).'</pre>';
						setEventMessages($printer->error, $printer->errors);
						setEventMessages($langs->transnoentitiesnoconv("FileWasSentToPrinter", basename(GETPOST('file', 'alpha'))).' '.$langs->transnoentitiesnoconv("ViaModule").' '.$printer->name, null);
					}
				}
				catch (Exception $e)
				{
					$ret = 1;
					setEventMessages($e->getMessage(), null, 'errors');
				}
			}
		}
		if ($printerfound == 0) setEventMessages($langs->trans("NoActivePrintingModuleFound", $langs->transnoentities("Module64000Name")), null, 'warnings');
	} else {
		setEventMessages($langs->trans("NoModuleFound"), null, 'warnings');
	}
	$action = '';
}
