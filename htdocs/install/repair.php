<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015      Raphaël Doursenaud   <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2021-2024  Frédéric France      <frederic.france@free.fr>
 * Copyright (C) 2023      Gauthier VERDOL      <gauthier.verdol@atm-consulting.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024		Vincent de Grandpré	<vincent@de-grandpre.quebec>
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
 *      \file       htdocs/install/repair.php
 *      \brief      Run repair script
 */

include_once 'inc.php';
if (file_exists($conffile)) {
	include_once $conffile;
}
require_once $dolibarr_main_document_root.'/core/lib/admin.lib.php';
include_once $dolibarr_main_document_root.'/core/lib/images.lib.php';
require_once $dolibarr_main_document_root.'/core/class/extrafields.class.php';
require_once 'lib/repair.lib.php';

$step = 2;
$ok = 0;


// Cette page peut etre longue. On augmente le delai autorise.
// Ne fonctionne que si on est pas en safe_mode.
$err = error_reporting();
error_reporting(0);
@set_time_limit(120);
error_reporting($err);

$setuplang = GETPOST("selectlang", 'aZ09', 3) ? GETPOST("selectlang", 'aZ09', 3) : 'auto';
$langs->setDefaultLang($setuplang);

$langs->loadLangs(array("admin", "install", "other"));

if ($dolibarr_main_db_type == "mysqli") {
	$choix = 1;
}
if ($dolibarr_main_db_type == "pgsql") {
	$choix = 2;
}
if ($dolibarr_main_db_type == "mssql") {
	$choix = 3;
}


dolibarr_install_syslog("--- repair: entering upgrade.php page");
if (!is_object($conf)) {
	dolibarr_install_syslog("repair: conf file not initialized", LOG_ERR);
}


/*
 * View
 */

pHeader($langs->trans("Repair"), "upgrade2", GETPOST('action', 'aZ09'));

// Action to launch the repair script
$actiondone = 1;

print '<div class="warning" style="padding-top: 10px">';
print $langs->trans("SetAtLeastOneOptionAsUrlParameter");
print '</div>';

//print 'You must set one of the following option with a parameter value that is "test" or "confirmed" on the URL<br>';
//print $langs->trans("Example").': '.DOL_MAIN_URL_ROOT.'/install/repair.php?standard=confirmed<br>'."\n";
print '<br>';

print 'Option standard is '.(GETPOST('standard', 'alpha') ? GETPOST('standard', 'alpha') : 'undefined').'<br>'."\n";
// Disable modules
print 'Option force_disable_of_modules_not_found is '.(GETPOST('force_disable_of_modules_not_found', 'alpha') ? GETPOST('force_disable_of_modules_not_found', 'alpha') : 'undefined').'<br>'."\n";
// Files
print 'Option restore_thirdparties_logos is '.(GETPOST('restore_thirdparties_logos', 'alpha') ? GETPOST('restore_thirdparties_logos', 'alpha') : 'undefined').'<br>'."\n";
print 'Option restore_user_pictures is '.(GETPOST('restore_user_pictures', 'alpha') ? GETPOST('restore_user_pictures', 'alpha') : 'undefined').'<br>'."\n";
print 'Option rebuild_product_thumbs is '.(GETPOST('rebuild_product_thumbs', 'alpha') ? GETPOST('rebuild_product_thumbs', 'alpha') : 'undefined').'<br>'."\n";
// Clean tables and data
print 'Option clean_linked_elements is '.(GETPOST('clean_linked_elements', 'alpha') ? GETPOST('clean_linked_elements', 'alpha') : 'undefined').'<br>'."\n";
print 'Option clean_menus is '.(GETPOST('clean_menus', 'alpha') ? GETPOST('clean_menus', 'alpha') : 'undefined').'<br>'."\n";
print 'Option clean_orphelin_dir is '.(GETPOST('clean_orphelin_dir', 'alpha') ? GETPOST('clean_orphelin_dir', 'alpha') : 'undefined').'<br>'."\n";
print 'Option clean_product_stock_batch is '.(GETPOST('clean_product_stock_batch', 'alpha') ? GETPOST('clean_product_stock_batch', 'alpha') : 'undefined').'<br>'."\n";
print 'Option clean_perm_table is '.(GETPOST('clean_perm_table', 'alpha') ? GETPOST('clean_perm_table', 'alpha') : 'undefined').'<br>'."\n";
print 'Option repair_link_dispatch_lines_supplier_order_lines, is '.(GETPOST('repair_link_dispatch_lines_supplier_order_lines', 'alpha') ? GETPOST('repair_link_dispatch_lines_supplier_order_lines', 'alpha') : 'undefined').'<br>'."\n";
// Init data
print 'Option set_empty_time_spent_amount is '.(GETPOST('set_empty_time_spent_amount', 'alpha') ? GETPOST('set_empty_time_spent_amount', 'alpha') : 'undefined').'<br>'."\n";
// Structure
print 'Option force_utf8_on_tables (force utf8 + row=dynamic), for mysql/mariadb only, is '.(GETPOST('force_utf8_on_tables', 'alpha') ? GETPOST('force_utf8_on_tables', 'alpha') : 'undefined').'<br>'."\n";
print '<span class="valignmiddle">'."Option force_utf8mb4_on_tables (force utf8mb4 + row=dynamic, EXPERIMENTAL!), for mysql/mariadb only, is ".(GETPOST('force_utf8mb4_on_tables', 'alpha') ? GETPOST('force_utf8mb4_on_tables', 'alpha') : 'undefined');
print '</span>';
if ($dolibarr_main_db_character_set != 'utf8mb4') {
	print '<img src="../theme/eldy/img/warning.png" class="pictofortooltip valignmiddle" title="If you switch to utf8mb4, you must also check the value for $dolibarr_main_db_character_set and $dolibarr_main_db_collation into conf/conf.php file.">';
}
print "<br>\n";
print "Option force_collation_from_conf_on_tables (force ".$conf->db->character_set."/".$conf->db->dolibarr_main_db_collation." + row=dynamic), for mysql/mariadb only is ".(GETPOST('force_collation_from_conf_on_tables', 'alpha') ? GETPOST('force_collation_from_conf_on_tables', 'alpha') : 'undefined')."<br>\n";

// Rebuild sequence
print 'Option rebuild_sequences, for postgresql only, is '.(GETPOST('rebuild_sequences', 'alpha') ? GETPOST('rebuild_sequences', 'alpha') : 'undefined').'<br>'."\n";
print '<br>';

print '<hr>';

print '<table cellspacing="0" cellpadding="1" class="centpercent">';
$error = 0;

// If password is encoded, we decode it
if (preg_match('/crypted:/i', $dolibarr_main_db_pass) || !empty($dolibarr_main_db_encrypted_pass)) {
	require_once $dolibarr_main_document_root.'/core/lib/security.lib.php';
	if (preg_match('/crypted:/i', $dolibarr_main_db_pass)) {
		$dolibarr_main_db_pass = preg_replace('/crypted:/i', '', $dolibarr_main_db_pass);
		$dolibarr_main_db_pass = dol_decode($dolibarr_main_db_pass);
		$dolibarr_main_db_encrypted_pass = $dolibarr_main_db_pass; // We need to set this as it is used to know the password was initially encrypted
	} else {
		$dolibarr_main_db_pass = dol_decode($dolibarr_main_db_encrypted_pass);
	}
}

// $conf is already instancied inside inc.php
$conf->db->type = $dolibarr_main_db_type;
$conf->db->host = $dolibarr_main_db_host;
$conf->db->port = $dolibarr_main_db_port;
$conf->db->name = $dolibarr_main_db_name;
$conf->db->user = $dolibarr_main_db_user;
$conf->db->pass = $dolibarr_main_db_pass;

// For encryption
$conf->db->dolibarr_main_db_encryption = isset($dolibarr_main_db_encryption) ? $dolibarr_main_db_encryption : 0;
$conf->db->dolibarr_main_db_cryptkey = isset($dolibarr_main_db_cryptkey) ? $dolibarr_main_db_cryptkey : '';

$db = getDoliDBInstance($conf->db->type, $conf->db->host, $conf->db->user, $conf->db->pass, $conf->db->name, (int) $conf->db->port);

if ($db->connected) {
	print '<tr><td class="nowrap">';
	print $langs->trans("ServerConnection")." : $dolibarr_main_db_host</td><td class=\"right\">".$langs->trans("OK")."</td></tr>";
	dolibarr_install_syslog("repair: ".$langs->transnoentities("ServerConnection").": ".$dolibarr_main_db_host.$langs->transnoentities("OK"));
	$ok = 1;
} else {
	print "<tr><td>".$langs->trans("ErrorFailedToConnectToDatabase", $dolibarr_main_db_name)."</td><td class=\"right\">".$langs->transnoentities("Error")."</td></tr>";
	dolibarr_install_syslog("repair: ".$langs->transnoentities("ErrorFailedToConnectToDatabase", $dolibarr_main_db_name));
	$ok = 0;
}

if ($ok) {
	if ($db->database_selected) {
		print '<tr><td class="nowrap">';
		print $langs->trans("DatabaseConnection")." : ".$dolibarr_main_db_name."</td><td class=\"right\">".$langs->trans("OK")."</td></tr>";
		dolibarr_install_syslog("repair: database connection successful: ".$dolibarr_main_db_name);
		$ok = 1;
	} else {
		print "<tr><td>".$langs->trans("ErrorFailedToConnectToDatabase", $dolibarr_main_db_name)."</td><td class=\"right\">".$langs->trans("Error")."</td></tr>";
		dolibarr_install_syslog("repair: ".$langs->transnoentities("ErrorFailedToConnectToDatabase", $dolibarr_main_db_name));
		$ok = 0;
	}
}

// Show database version
if ($ok) {
	$version = $db->getVersion();
	$versionarray = $db->getVersionArray();
	print '<tr><td>'.$langs->trans("ServerVersion").'</td>';
	print '<td class="right">'.$version.'</td></tr>';
	dolibarr_install_syslog("repair: ".$langs->transnoentities("ServerVersion").": ".$version);
	//print '<td class="right">'.join('.',$versionarray).'</td></tr>';
}

$conf->setValues($db);
// Reset forced setup after the setValues
if (defined('SYSLOG_FILE')) {
	$conf->global->SYSLOG_FILE = constant('SYSLOG_FILE');
}
$conf->global->MAIN_ENABLE_LOG_TO_HTML = 1;


/* Start action here */
$oneoptionset = 0;
$oneoptionset = (GETPOST('standard', 'alpha') || GETPOST('restore_thirdparties_logos', 'alpha') || GETPOST('clean_linked_elements', 'alpha') || GETPOST('clean_menus', 'alpha')
	|| GETPOST('clean_orphelin_dir', 'alpha') || GETPOST('clean_product_stock_batch', 'alpha') || GETPOST('set_empty_time_spent_amount', 'alpha') || GETPOST('rebuild_product_thumbs', 'alpha')
	|| GETPOST('clean_perm_table', 'alpha')
	|| GETPOST('force_disable_of_modules_not_found', 'alpha')
	|| GETPOST('force_utf8_on_tables', 'alpha') || GETPOST('force_utf8mb4_on_tables', 'alpha') || GETPOST('force_collation_from_conf_on_tables', 'alpha')
	|| GETPOST('rebuild_sequences', 'alpha') || GETPOST('recalculateinvoicetotal', 'alpha'));

if ($ok && $oneoptionset) {
	// Show wait message
	print '<tr><td colspan="2">'.$langs->trans("PleaseBePatient").'<br><br></td></tr>';
	flush();
}


// run_sql: Run repair SQL file
if ($ok && GETPOST('standard', 'alpha')) {
	$dir = "mysql/migration/";

	$filelist = array();
	$i = 0;
	$ok = 0;

	// Recupere list fichier
	$filesindir = array();
	$handle = opendir($dir);
	if (is_resource($handle)) {
		while (($file = readdir($handle)) !== false) {
			if (preg_match('/\.sql$/i', $file)) {
				$filesindir[] = $file;
			}
		}
	}
	sort($filesindir);

	foreach ($filesindir as $file) {
		if (preg_match('/repair/i', $file)) {
			$filelist[] = $file;
		}
	}

	// Loop on each file
	foreach ($filelist as $file) {
		print '<tr><td class="nowrap">*** ';
		print $langs->trans("Script").'</td><td class="right">'.$file.'</td></tr>';

		$name = substr($file, 0, dol_strlen($file) - 4);

		// Run sql script
		$ok = run_sql($dir.$file, 0, '', 1);
	}
}


// sync_extrafields: Search list of fields declared and list of fields created into databases, then create fields missing

if ($ok && GETPOST('standard', 'alpha')) {
	$extrafields = new ExtraFields($db);

	// List of tables that has an extrafield table
	$listofmodulesextra = array('societe' => 'societe', 'adherent' => 'adherent', 'product' => 'product',
				'socpeople' => 'socpeople', 'propal' => 'propal', 'commande' => 'commande',
				'facture' => 'facture', 'facturedet' => 'facturedet', 'facture_rec' => 'facture_rec', 'facturedet_rec' => 'facturedet_rec',
				'supplier_proposal' => 'supplier_proposal', 'commande_fournisseur' => 'commande_fournisseur',
				'facture_fourn' => 'facture_fourn', 'facture_fourn_rec' => 'facture_fourn_rec', 'facture_fourn_det' => 'facture_fourn_det', 'facture_fourn_det_rec' => 'facture_fourn_det_rec',
				'fichinter' => 'fichinter', 'fichinterdet' => 'fichinterdet',
				'inventory' => 'inventory',
				'actioncomm' => 'actioncomm', 'bom_bom' => 'bom_bom', 'mrp_mo' => 'mrp_mo',
				'adherent_type' => 'adherent_type', 'user' => 'user', 'partnership' => 'partnership', 'projet' => 'projet', 'projet_task' => 'projet_task', 'ticket' => 'ticket');
	//$listofmodulesextra = array('fichinter'=>'fichinter');

	print '<tr><td colspan="2"><br>*** Check fields into extra table structure match table of definition. If not add column into table</td></tr>';
	foreach ($listofmodulesextra as $tablename => $elementtype) {
		// Get list of fields
		$tableextra = MAIN_DB_PREFIX.$tablename.'_extrafields';

		// Define $arrayoffieldsdesc
		$arrayoffieldsdesc = $extrafields->fetch_name_optionals_label($elementtype);

		// Define $arrayoffieldsfound
		$arrayoffieldsfound = array();
		$resql = $db->DDLDescTable($tableextra);
		if ($resql) {
			print '<tr><td>Check availability of extra field for '.$tableextra;
			$i = 0;
			while ($obj = $db->fetch_object($resql)) {
				$fieldname = $fieldtype = '';
				if (preg_match('/mysql/', $db->type)) {
					$fieldname = $obj->Field;
					$fieldtype = $obj->Type;
				} else {
					$fieldname = isset($obj->Key) ? $obj->Key : $obj->attname;
					$fieldtype = isset($obj->Type) ? $obj->Type : 'varchar';
				}

				if (empty($fieldname)) {
					continue;
				}
				if (in_array($fieldname, array('rowid', 'tms', 'fk_object', 'import_key'))) {
					continue;
				}
				$arrayoffieldsfound[$fieldname] = array('type' => $fieldtype);
			}
			print ' - Found '.count($arrayoffieldsfound).' fields into table';
			if (count($arrayoffieldsfound) > 0) {
				print ' <span class="opacitymedium">('.implode(', ', array_keys($arrayoffieldsfound)).')</span>';
			}
			print '<br>'."\n";

			// If it does not match, we create fields
			foreach ($arrayoffieldsdesc as $code => $label) {
				if (!in_array($code, array_keys($arrayoffieldsfound))) {
					print 'Found field '.$code.' declared into '.MAIN_DB_PREFIX.'extrafields table but not found into desc of table '.$tableextra." -> ";
					$type = $extrafields->attributes[$elementtype]['type'][$code];
					$length = $extrafields->attributes[$elementtype]['size'][$code];
					$attribute = '';
					$default = '';
					$extra = '';
					$null = 'null';

					if ($type == 'boolean') {
						$typedb = 'int';
						$lengthdb = '1';
					} elseif ($type == 'price') {
						$typedb = 'double';
						$lengthdb = '24,8';
					} elseif ($type == 'phone') {
						$typedb = 'varchar';
						$lengthdb = '20';
					} elseif ($type == 'mail') {
						$typedb = 'varchar';
						$lengthdb = '128';
					} elseif (($type == 'select') || ($type == 'sellist') || ($type == 'radio') || ($type == 'checkbox') || ($type == 'chkbxlst')) {
						$typedb = 'text';
						$lengthdb = '';
					} elseif ($type == 'link') {
						$typedb = 'int';
						$lengthdb = '11';
					} else {
						$typedb = $type;
						$lengthdb = $length;
					}

					$field_desc = array(
						'type' => $typedb,
						'value' => $lengthdb,
						'attribute' => $attribute,
						'default' => $default,
						'extra' => $extra,
						'null' => $null
					);
					//var_dump($field_desc);exit;

					$result = 0;
					if (GETPOST('standard', 'alpha') == 'confirmed') {
						$result = $db->DDLAddField($tableextra, $code, $field_desc, "");

						if ($result < 0) {
							print "KO ".$db->lasterror."<br>\n";
						} else {
							print "OK<br>\n";
						}
					} else {
						print ' - Mode test, no column added.';
					}
				}
			}

			print "</td><td>&nbsp;</td></tr>\n";
		} else {
			print '<tr><td>Table '.$tableextra.' is not found</td><td></td></tr>'."\n";
		}
	}
}


// clean_data_ecm_dir: Clean data into ecm_directories table
if ($ok && GETPOST('standard', 'alpha')) {
	clean_data_ecm_directories();
}


// clean declaration constants
if ($ok && GETPOST('standard', 'alpha')) {
	print '<tr><td colspan="2"><br>*** Clean constant record of modules not enabled</td></tr>';

	$sql = "SELECT name, entity, value";
	$sql .= " FROM ".MAIN_DB_PREFIX."const as c";
	$sql .= " WHERE name LIKE 'MAIN_MODULE_%_TPL' OR name LIKE 'MAIN_MODULE_%_CSS' OR name LIKE 'MAIN_MODULE_%_JS' OR name LIKE 'MAIN_MODULE_%_HOOKS'";
	$sql .= " OR name LIKE 'MAIN_MODULE_%_TRIGGERS' OR name LIKE 'MAIN_MODULE_%_THEME' OR name LIKE 'MAIN_MODULE_%_SUBSTITUTIONS' OR name LIKE 'MAIN_MODULE_%_MODELS'";
	$sql .= " OR name LIKE 'MAIN_MODULE_%_MENUS' OR name LIKE 'MAIN_MODULE_%_LOGIN' OR name LIKE 'MAIN_MODULE_%_BARCODE' OR name LIKE 'MAIN_MODULE_%_TABS_%'";
	$sql .= " OR name LIKE 'MAIN_MODULE_%_MODULEFOREXTERNAL'";
	$sql .= " ORDER BY name, entity";

	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);

		if ($num) {
			$db->begin();

			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);

				$reg = array();
				if (preg_match('/MAIN_MODULE_([^_]+)_(.+)/i', $obj->name, $reg)) {
					$name = $reg[1];
					$type = $reg[2];

					$sql2 = "SELECT COUNT(*) as nb";
					$sql2 .= " FROM ".MAIN_DB_PREFIX."const as c";
					$sql2 .= " WHERE name = 'MAIN_MODULE_".$name."'";
					$sql2 .= " AND entity = ".((int) $obj->entity);
					$resql2 = $db->query($sql2);
					if ($resql2) {
						$obj2 = $db->fetch_object($resql2);
						if ($obj2 && $obj2->nb == 0) {
							// Module not found, so we can remove entry
							$sqldelete = "DELETE FROM ".MAIN_DB_PREFIX."const WHERE name = '".$db->escape($obj->name)."' AND entity = ".((int) $obj->entity);

							if (GETPOST('standard', 'alpha') == 'confirmed') {
								$db->query($sqldelete);

								print '<tr><td>Widget '.$obj->name.' set in entity '.$obj->entity.' with value '.$obj->value.' -> Module '.$name.' not enabled in entity '.((int) $obj->entity).', we delete record</td></tr>';
							} else {
								print '<tr><td>Widget '.$obj->name.' set in entity '.$obj->entity.' with value '.$obj->value.' -> Module '.$name.' not enabled in entity '.((int) $obj->entity).', we should delete record (not done, mode test)</td></tr>';
							}
						} else {
							//print '<tr><td>Constant '.$obj->name.' set in entity '.$obj->entity.' with value '.$obj->value.' -> Module found in entity '.$obj->entity.', we keep record</td></tr>';
						}
					}
				}

				$i++;
			}

			$db->commit();
		}
	} else {
		dol_print_error($db);
	}
}


// clean box of not enabled modules
if ($ok && GETPOST('standard', 'alpha')) {
	print '<tr><td colspan="2"><br>*** Clean definition of boxes of modules not enabled</td></tr>';

	$sql = "SELECT file, entity FROM ".MAIN_DB_PREFIX."boxes_def";
	$sql .= " WHERE file like '%@%'";

	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);

		if ($num) {
			$db->begin();

			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);

				$reg = array();
				if (preg_match('/^(.+)@(.+)$/i', $obj->file, $reg)) {
					$name = $reg[1];
					$module = $reg[2];

					$sql2 = "SELECT COUNT(*) as nb";
					$sql2 .= " FROM ".MAIN_DB_PREFIX."const as c";
					$sql2 .= " WHERE name = 'MAIN_MODULE_".strtoupper($module)."'";
					$sql2 .= " AND entity = ".((int) $obj->entity);
					$sql2 .= " AND value <> 0";
					$resql2 = $db->query($sql2);
					if ($resql2) {
						$obj2 = $db->fetch_object($resql2);
						if ($obj2 && $obj2->nb == 0) {
							// Module not found, so we canremove entry
							$sqldeletea = "DELETE FROM ".MAIN_DB_PREFIX."boxes WHERE entity = ".((int) $obj->entity)." AND box_id IN (SELECT rowid FROM ".MAIN_DB_PREFIX."boxes_def WHERE file = '".$db->escape($obj->file)."' AND entity = ".((int) $obj->entity).")";
							$sqldeleteb = "DELETE FROM ".MAIN_DB_PREFIX."boxes_def WHERE file = '".$db->escape($obj->file)."' AND entity = ".((int) $obj->entity);

							if (GETPOST('standard', 'alpha') == 'confirmed') {
								$db->query($sqldeletea);
								$db->query($sqldeleteb);

								print '<tr><td>Constant '.$obj->file.' set in boxes_def for entity '.$obj->entity.' but MAIN_MODULE_'.strtoupper($module).' not defined in entity '.((int) $obj->entity).', we delete record</td></tr>';
							} else {
								print '<tr><td>Constant '.$obj->file.' set in boxes_def for entity '.$obj->entity.' but MAIN_MODULE_'.strtoupper($module).' not defined in entity '.((int) $obj->entity).', we should delete record (not done, mode test)</td></tr>';
							}
						} else {
							//print '<tr><td>Constant '.$obj->name.' set in entity '.$obj->entity.' with value '.$obj->value.' -> Module found in entity '.$obj->entity.', we keep record</td></tr>';
						}
					}
				}

				$i++;
			}

			$db->commit();
		}
	}
}


// restore_thirdparties_logos: Move logos to correct new directory.
if ($ok && GETPOST('restore_thirdparties_logos')) {
	//$exts=array('gif','png','jpg');

	$ext = '';

	print '<tr><td colspan="2"><br>*** Restore thirdparties logo<br>';

	$sql = "SELECT s.rowid, s.nom as name, s.logo FROM ".MAIN_DB_PREFIX."societe as s ORDER BY s.nom";
	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;

		while ($i < $num) {
			$obj = $db->fetch_object($resql);

			/*
			$name=preg_replace('/é/','',$obj->name);
			$name=preg_replace('/ /','_',$name);
			$name=preg_replace('/\'/','',$name);
			*/

			$tmp = explode('.', $obj->logo);
			$name = $tmp[0];
			if (isset($tmp[1])) {
				$ext = '.'.$tmp[1];
			}

			if (!empty($name)) {
				$filetotest = $dolibarr_main_data_root.'/societe/logos/'.$name.$ext;
				$filetotestsmall = $dolibarr_main_data_root.'/societe/logos/thumbs/'.$name.'_small'.$ext;
				$exists = (int) dol_is_file($filetotest);
				print 'Check thirdparty '.$obj->rowid.' name='.$obj->name.' logo='.$obj->logo.' file '.$filetotest." exists=".$exists."<br>\n";
				if ($exists) {
					$filetarget = $dolibarr_main_data_root.'/societe/'.$obj->rowid.'/logos/'.$name.$ext;
					$filetargetsmall = $dolibarr_main_data_root.'/societe/'.$obj->rowid.'/logos/thumbs/'.$name.'_small'.$ext;
					$existt = dol_is_file($filetarget);
					if (!$existt) {
						if (GETPOST('restore_thirdparties_logos', 'alpha') == 'confirmed') {
							dol_mkdir($dolibarr_main_data_root.'/societe/'.$obj->rowid.'/logos');
						}

						print "  &nbsp; &nbsp; &nbsp; -> Copy file ".$filetotest." -> ".$filetarget."<br>\n";
						if (GETPOST('restore_thirdparties_logos', 'alpha') == 'confirmed') {
							dol_copy($filetotest, $filetarget, '', 0);
						}
					}

					$existtt = dol_is_file($filetargetsmall);
					if (!$existtt) {
						if (GETPOST('restore_thirdparties_logos', 'alpha') == 'confirmed') {
							dol_mkdir($dolibarr_main_data_root.'/societe/'.$obj->rowid.'/logos/thumbs');
						}
						print "  &nbsp; &nbsp; &nbsp; -> Copy file ".$filetotestsmall." -> ".$filetargetsmall."<br>\n";
						if (GETPOST('restore_thirdparties_logos', 'alpha') == 'confirmed') {
							dol_copy($filetotestsmall, $filetargetsmall, '', 0);
						}
					}
				}
			}

			$i++;
		}
	} else {
		$ok = 0;
		dol_print_error($db);
	}

	print '</td></tr>';
}



// restore_user_pictures: Move pictures to correct new directory.
if ($ok && GETPOST('restore_user_pictures', 'alpha')) {
	//$exts=array('gif','png','jpg');

	$ext = '';

	print '<tr><td colspan="2"><br>*** Restore user pictures<br>';

	$sql = "SELECT s.rowid, s.firstname, s.lastname, s.login, s.photo FROM ".MAIN_DB_PREFIX."user as s ORDER BY s.rowid";
	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;

		while ($i < $num) {
			$obj = $db->fetch_object($resql);

			/*
			 $name=preg_replace('/é/','',$obj->name);
			 $name=preg_replace('/ /','_',$name);
			 $name=preg_replace('/\'/','',$name);
			 */

			$tmp = explode('.', $obj->photo);
			$name = $tmp[0];
			if (isset($tmp[1])) {
				$ext = '.'.$tmp[1];
			}

			if (!empty($name)) {
				$filetotest = $dolibarr_main_data_root.'/users/'.substr(sprintf('%08d', $obj->rowid), -1, 1).'/'.substr(sprintf('%08d', $obj->rowid), -2, 1).'/'.$name.$ext;
				$filetotestsmall = $dolibarr_main_data_root.'/users/'.substr(sprintf('%08d', $obj->rowid), -1, 1).'/'.substr(sprintf('%08d', $obj->rowid), -2, 1).'/thumbs/'.$name.'_small'.$ext;
				$filetotestmini = $dolibarr_main_data_root.'/users/'.substr(sprintf('%08d', $obj->rowid), -1, 1).'/'.substr(sprintf('%08d', $obj->rowid), -2, 1).'/thumbs/'.$name.'_mini'.$ext;
				$exists = (int) dol_is_file($filetotest);
				print 'Check user '.$obj->rowid.' lastname='.$obj->lastname.' firstname='.$obj->firstname.' photo='.$obj->photo.' file '.$filetotest." exists=".$exists."<br>\n";
				if ($exists) {
					$filetarget = $dolibarr_main_data_root.'/users/'.$obj->rowid.'/'.$name.$ext;
					$filetargetsmall = $dolibarr_main_data_root.'/users/'.$obj->rowid.'/thumbs/'.$name.'_small'.$ext;
					$filetargetmini = $dolibarr_main_data_root.'/users/'.$obj->rowid.'/thumbs/'.$name.'_mini'.$ext;

					$existt = dol_is_file($filetarget);
					if (!$existt) {
						if (GETPOST('restore_user_pictures', 'alpha') == 'confirmed') {
							dol_mkdir($dolibarr_main_data_root.'/users/'.$obj->rowid);
						}

						print "  &nbsp; &nbsp; &nbsp; -> Copy file ".$filetotest." -> ".$filetarget."<br>\n";
						if (GETPOST('restore_user_pictures', 'alpha') == 'confirmed') {
							dol_copy($filetotest, $filetarget, '', 0);
						}
					}

					$existtt = dol_is_file($filetargetsmall);
					if (!$existtt) {
						if (GETPOST('restore_user_pictures', 'alpha') == 'confirmed') {
							dol_mkdir($dolibarr_main_data_root.'/users/'.$obj->rowid.'/thumbs');
						}

						print "  &nbsp; &nbsp; &nbsp; -> Copy file ".$filetotestsmall." -> ".$filetargetsmall."<br>\n";
						if (GETPOST('restore_user_pictures', 'alpha') == 'confirmed') {
							dol_copy($filetotestsmall, $filetargetsmall, '', 0);
						}
					}

					$existtt = dol_is_file($filetargetmini);
					if (!$existtt) {
						if (GETPOST('restore_user_pictures', 'alpha') == 'confirmed') {
							dol_mkdir($dolibarr_main_data_root.'/users/'.$obj->rowid.'/thumbs');
						}

						print "  &nbsp; &nbsp; &nbsp; -> Copy file ".$filetotestmini." -> ".$filetargetmini."<br>\n";
						if (GETPOST('restore_user_pictures', 'alpha') == 'confirmed') {
							dol_copy($filetotestmini, $filetargetmini, '', 0);
						}
					}
				}
			}

			$i++;
		}
	} else {
		$ok = 0;
		dol_print_error($db);
	}

	print '</td></tr>';
}


// rebuild_product_thumbs: Rebuild thumbs for product files
if ($ok && GETPOST('rebuild_product_thumbs', 'alpha')) {
	$ext = '';
	global $maxwidthsmall, $maxheightsmall, $maxwidthmini, $maxheightmini;

	print '<tr><td colspan="2"><br>*** Rebuild product thumbs<br>';

	$sql = "SELECT s.rowid, s.ref FROM ".MAIN_DB_PREFIX."product as s ORDER BY s.ref";
	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;

		while ($i < $num) {
			$obj = $db->fetch_object($resql);

			if (!empty($obj->ref)) {
				$files = dol_dir_list($dolibarr_main_data_root.'/produit/'.$obj->ref, 'files', 0);
				foreach ($files as $file) {
					// Generate thumbs.
					if (image_format_supported($file['fullname']) == 1) {
						$imgThumbSmall = 'notbuild';
						if (GETPOST('rebuild_product_thumbs', 'alpha') == 'confirmed') {
							// Used on logon for example
							$imgThumbSmall = vignette($file['fullname'], $maxwidthsmall, $maxheightsmall, '_small', 50, "thumbs");
						}
						print 'Check product '.$obj->rowid.", file ".$file['fullname']." -> ".$imgThumbSmall." maxwidthsmall=".$maxwidthsmall." maxheightsmall=".$maxheightsmall."<br>\n";
						$imgThumbMini = 'notbuild';
						if (GETPOST('rebuild_product_thumbs', 'alpha') == 'confirmed') {
							// Create mini thumbs for image (Ratio is near 16/9)
							// Used on menu or for setup page for example
							$imgThumbMini = vignette($file['fullname'], $maxwidthmini, $maxheightmini, '_mini', 50, "thumbs");
						}
						print 'Check product '.$obj->rowid.", file ".$file['fullname']." -> ".$imgThumbMini." maxwidthmini=".$maxwidthmini." maxheightmini=".$maxheightmini."<br>\n";
					}
				}
			}

			$i++;
		}
	} else {
		$ok = 0;
		dol_print_error($db);
	}

	print '</td></tr>';
}

// clean_linked_elements: Check and clean linked elements
if ($ok && GETPOST('clean_linked_elements', 'alpha')) {
	print '<tr><td colspan="2"><br>*** Check table of linked elements and delete orphelins links</td></tr>';
	// propal => order
	print '<tr><td colspan="2">'.checkLinkedElements('propal', 'commande')."</td></tr>\n";

	// propal => invoice
	print '<tr><td colspan="2">'.checkLinkedElements('propal', 'facture')."</td></tr>\n";

	// order => invoice
	print '<tr><td colspan="2">'.checkLinkedElements('commande', 'facture')."</td></tr>\n";

	// order => shipping
	print '<tr><td colspan="2">'.checkLinkedElements('commande', 'shipping')."</td></tr>\n";

	// shipping => delivery
	print '<tr><td colspan="2">'.checkLinkedElements('shipping', 'delivery')."</td></tr>\n";

	// order_supplier => invoice_supplier
	print '<tr><td colspan="2">'.checkLinkedElements('order_supplier', 'invoice_supplier')."</td></tr>\n";
}


// clean_menus: Check orphelins menus
if ($ok && GETPOST('clean_menus', 'alpha')) {
	print '<tr><td colspan="2"><br>*** Clean menu entries coming from disabled modules</td></tr>';

	$sql = "SELECT rowid, module";
	$sql .= " FROM ".MAIN_DB_PREFIX."menu as c";
	$sql .= " WHERE module IS NOT NULL AND module <> ''";
	$sql .= " ORDER BY module";

	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		if ($num) {
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);

				$modulecond = $obj->module;
				$modulecondarray = explode('|', $obj->module); // Name of module

				print '<tr><td>';
				print $modulecond;

				$db->begin();

				if ($modulecond) {		// And menu entry for module $modulecond was found in database.
					$moduleok = 0;
					foreach ($modulecondarray as $tmpname) {
						if ($tmpname == 'margins') {
							$tmpname = 'margin'; // TODO Remove this when normalized
						}

						$result = 0;
						if (!empty($conf->$tmpname)) {
							$result = $conf->$tmpname->enabled;
						}
						if ($result) {
							$moduleok++;
						}
					}

					if (!$moduleok && $modulecond) {
						print ' - Module condition '.$modulecond.' seems ko, we delete menu entry.';
						if (GETPOST('clean_menus') == 'confirmed') {
							$sql2 = "DELETE FROM ".MAIN_DB_PREFIX."menu WHERE module = '".$db->escape($modulecond)."'";
							$resql2 = $db->query($sql2);
							if (!$resql2) {
								$error++;
								dol_print_error($db);
							} else {
								print ' - <span class="warning">Cleaned</span>';
							}
						} else {
							print ' - <span class="warning">Canceled (test mode)</span>';
						}
					} else {
						print ' - Module condition '.$modulecond.' is ok, we do nothing.';
					}
				}

				if (!$error) {
					$db->commit();
				} else {
					$db->rollback();
				}

				print'</td></tr>';

				if ($error) {
					break;
				}

				$i++;
			}
		} else {
			print '<tr><td>No menu entries of disabled menus found</td></tr>';
		}
	} else {
		dol_print_error($db);
	}
}



// clean_orphelin_dir: Run purge of directory
if ($ok && GETPOST('clean_orphelin_dir', 'alpha')) {
	$listmodulepart = array('company', 'invoice', 'invoice_supplier', 'propal', 'order', 'order_supplier', 'contract', 'tax');
	foreach ($listmodulepart as $modulepart) {
		$filearray = array();
		$upload_dir = isset($conf->$modulepart->dir_output) ? $conf->$modulepart->dir_output : '';
		if ($modulepart == 'company') {
			$upload_dir = $conf->societe->dir_output; // TODO change for multicompany sharing
		}
		if ($modulepart == 'invoice') {
			$upload_dir = $conf->facture->dir_output;
		}
		if ($modulepart == 'invoice_supplier') {
			$upload_dir = $conf->fournisseur->facture->dir_output;
		}
		if ($modulepart == 'order') {
			$upload_dir = $conf->commande->dir_output;
		}
		if ($modulepart == 'order_supplier') {
			$upload_dir = $conf->fournisseur->commande->dir_output;
		}
		if ($modulepart == 'contract') {
			$upload_dir = $conf->contrat->dir_output;
		}

		if (empty($upload_dir)) {
			continue;
		}

		print '<tr><td colspan="2"><br>*** Clean orphelins files into files '.$upload_dir.'</td></tr>';

		$filearray = dol_dir_list($upload_dir, "files", 1, '', array('^SPECIMEN\.pdf$', '^\.', '(\.meta|_preview.*\.png)$', '^temp$', '^payments$', '^CVS$', '^thumbs$'), '', SORT_DESC, 1, 1);

		// To show ref or specific information according to view to show (defined by $module)
		if ($modulepart == 'company') {
			include_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
			$object_instance = new Societe($db);
		}
		if ($modulepart == 'invoice') {
			include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
			$object_instance = new Facture($db);
		} elseif ($modulepart == 'invoice_supplier') {
			include_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
			$object_instance = new FactureFournisseur($db);
		} elseif ($modulepart == 'propal') {
			include_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
			$object_instance = new Propal($db);
		} elseif ($modulepart == 'order') {
			include_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
			$object_instance = new Commande($db);
		} elseif ($modulepart == 'order_supplier') {
			include_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
			$object_instance = new CommandeFournisseur($db);
		} elseif ($modulepart == 'contract') {
			include_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
			$object_instance = new Contrat($db);
		} elseif ($modulepart == 'tax') {
			include_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/chargesociales.class.php';
			$object_instance = new ChargeSociales($db);
		}

		foreach ($filearray as $key => $file) {
			if (!is_dir($file['name'])
			&& $file['name'] != '.'
			&& $file['name'] != '..'
			&& $file['name'] != 'CVS'
			) {
				// Define relative path used to store the file
				$relativefile = preg_replace('/'.preg_quote($upload_dir.'/', '/').'/', '', $file['fullname']);

				//var_dump($file);
				$id = 0;
				$ref = '';
				$object_instance->id = 0;
				$object_instance->ref = '';
				$label = '';

				// To show ref or specific information according to view to show (defined by $module)
				if ($modulepart == 'invoice') {
					preg_match('/(.*)\/[^\/]+$/', $relativefile, $reg);
					$ref = $reg[1];
				}
				if ($modulepart == 'invoice_supplier') {
					preg_match('/(\d+)\/[^\/]+$/', $relativefile, $reg);
					$id = empty($reg[1]) ? '' : $reg[1];
				}
				if ($modulepart == 'propal') {
					preg_match('/(.*)\/[^\/]+$/', $relativefile, $reg);
					$ref = $reg[1];
				}
				if ($modulepart == 'order') {
					preg_match('/(.*)\/[^\/]+$/', $relativefile, $reg);
					$ref = $reg[1];
				}
				if ($modulepart == 'order_supplier') {
					preg_match('/(.*)\/[^\/]+$/', $relativefile, $reg);
					$ref = $reg[1];
				}
				if ($modulepart == 'contract') {
					preg_match('/(.*)\/[^\/]+$/', $relativefile, $reg);
					$ref = $reg[1];
				}
				if ($modulepart == 'tax') {
					preg_match('/(\d+)\/[^\/]+$/', $relativefile, $reg);
					$id = $reg[1];
				}

				if ($id || $ref) {
					//print 'Fetch '.$id.' or '.$ref.'<br>';
					$result = $object_instance->fetch($id, $ref);
					//print $result.'<br>';
					if ($result == 0) {    // Not found but no error
						// Clean of orphelins directories are done into repair.php
						print '<tr><td colspan="2">';
						print 'Delete orphelins file '.$file['fullname'].'<br>';
						if (GETPOST('clean_orphelin_dir', 'alpha') == 'confirmed') {
							dol_delete_file($file['fullname'], 1, 1, 1);
							dol_delete_dir(dirname($file['fullname']), 1);
						}
						print "</td></tr>";
					} elseif ($result < 0) {
						print 'Error in '.get_class($object_instance).'.fetch of id'.$id.' ref='.$ref.', result='.$result.'<br>';
					}
				}
			}
		}
	}
}

// clean_linked_elements: Check and clean linked elements
if ($ok && GETPOST('clean_product_stock_batch', 'alpha')) {
	$methodtofix = GETPOST('methodtofix', 'alpha') ? GETPOST('methodtofix', 'alpha') : 'updatestock';

	print '<tr><td colspan="2"><br>*** Clean table product_batch, methodtofix='.$methodtofix.' (possible values: updatestock or updatebatch)</td></tr>';

	$sql = "SELECT p.rowid, p.ref, p.tobatch, ps.rowid as psrowid, ps.fk_entrepot, ps.reel, SUM(pb.qty) as reelbatch";
	$sql .= " FROM ".MAIN_DB_PREFIX."product as p, ".MAIN_DB_PREFIX."product_stock as ps LEFT JOIN ".MAIN_DB_PREFIX."product_batch as pb ON ps.rowid = pb.fk_product_stock";
	$sql .= " WHERE p.rowid = ps.fk_product";
	$sql .= " GROUP BY p.rowid, p.ref, p.tobatch, ps.rowid, ps.fk_entrepot, ps.reel";
	$sql .= " HAVING (SUM(pb.qty) IS NOT NULL AND reel != SUM(pb.qty)) OR (SUM(pb.qty) IS NULL AND p.tobatch > 0)";
	print $sql;
	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);

		if ($num) {
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				print '<tr><td>Product '.$obj->rowid.'-'.$obj->ref.' in warehouse id='.$obj->fk_entrepot.' (product_stock.id='.$obj->psrowid.'): '.$obj->reel.' (Stock product_stock.reel) != '.($obj->reelbatch ? $obj->reelbatch : '0').' (Stock batch sum product_batch)';

				// Fix is required
				if ($obj->reel != $obj->reelbatch) {
					if (empty($obj->tobatch)) {
						// If product is not a product that support batches, we can clean stock by deleting the product batch lines
						print ' -> Delete qty '.$obj->reelbatch.' for any lot linked to fk_product_stock='.$obj->psrowid;
						$sql2 = "DELETE FROM ".MAIN_DB_PREFIX."product_batch";
						$sql2 .= " WHERE fk_product_stock = ".((int) $obj->psrowid);
						print '<br>'.$sql2;

						if (GETPOST('clean_product_stock_batch') == 'confirmed') {
							$resql2 = $db->query($sql2);
							if (!$resql2) {
								$error++;
								dol_print_error($db);
							}
						}
					} else {
						if ($methodtofix == 'updatebatch') {
							// Method 1
							print ' -> Insert qty '.($obj->reel - $obj->reelbatch).' with lot 000000 linked to fk_product_stock='.$obj->psrowid;
							$sql2 = "INSERT INTO ".MAIN_DB_PREFIX."product_batch(fk_product_stock, batch, qty)";
							$sql2 .= "VALUES(".((int) $obj->psrowid).", '000000', ".((float) ($obj->reel - $obj->reelbatch)).")";
							print '<br>'.$sql2;

							if (GETPOST('clean_product_stock_batch') == 'confirmed') {
								$resql2 = $db->query($sql2);
								if (!$resql2) {
									// TODO If it fails, we must make update
									//$sql2 ="UPDATE ".MAIN_DB_PREFIX."product_batch";
									//$sql2.=" SET ".$obj->psrowid.", '000000', ".($obj->reel - $obj->reelbatch).")";
									//$sql2.=" WHERE fk_product_stock = ".((int) $obj->psrowid)
								}
							}
						}
						if ($methodtofix == 'updatestock') {
							// Method 2
							print ' -> Update qty of product_stock with qty = '.($obj->reelbatch ? ((float) $obj->reelbatch) : '0').' for ps.rowid = '.((int) $obj->psrowid);
							$sql2 = "UPDATE ".MAIN_DB_PREFIX."product_stock";
							$sql2 .= " SET reel = ".($obj->reelbatch ? ((float) $obj->reelbatch) : '0')." WHERE rowid = ".((int) $obj->psrowid);
							print '<br>'.$sql2;

							if (GETPOST('clean_product_stock_batch') == 'confirmed') {
								$error = 0;

								$db->begin();

								$resql2 = $db->query($sql2);
								if ($resql2) {
									// We update product_stock, so we must fill p.stock into product too.
									$sql3 = 'UPDATE '.MAIN_DB_PREFIX.'product p SET p.stock= (SELECT SUM(ps.reel) FROM '.MAIN_DB_PREFIX.'product_stock ps WHERE ps.fk_product = p.rowid)';
									$resql3 = $db->query($sql3);
									if (!$resql3) {
										$error++;
										dol_print_error($db);
									}
								} else {
									$error++;
									dol_print_error($db);
								}

								if (!$error) {
									$db->commit();
								} else {
									$db->rollback();
								}
							}
						}
					}
				}

				print'</td></tr>';

				$i++;
			}
		} else {
			print '<tr><td colspan="2">Nothing to do</td></tr>';
		}
	} else {
		dol_print_error($db);
	}
}


// clean_product_stock_negative_if_batch
if ($ok && GETPOST('clean_product_stock_negative_if_batch', 'alpha')) {
	print '<tr><td colspan="2"><br>Clean table product_batch, methodtofix='.$methodtofix.' (possible values: updatestock or updatebatch)</td></tr>';

	$sql = "SELECT p.rowid, p.ref, p.tobatch, ps.rowid as psrowid, ps.fk_entrepot, ps.reel, SUM(pb.qty) as reelbatch";
	$sql .= " FROM ".MAIN_DB_PREFIX."product as p, ".MAIN_DB_PREFIX."product_stock as ps, ".MAIN_DB_PREFIX."product_batch as pb";
	$sql .= " WHERE p.rowid = ps.fk_product AND ps.rowid = pb.fk_product_stock";
	$sql .= " AND p.tobatch > 0";
	$sql .= " GROUP BY p.rowid, p.ref, p.tobatch, ps.rowid, ps.fk_entrepot, ps.reel";
	$sql .= " HAVING reel != SUM(pb.qty)";
	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);

		if ($num) {
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				print '<tr><td>'.$obj->rowid.'-'.$obj->ref.'-'.$obj->fk_entrepot.' -> '.$obj->psrowid.': '.$obj->reel.' != '.$obj->reelbatch;

				// TODO
			}
		}
	}
}

// set_empty_time_spent_amount
if ($ok && GETPOST('set_empty_time_spent_amount', 'alpha')) {
	print '<tr><td colspan="2"><br>*** Set value of time spent without amount</td></tr>';

	$sql = "SELECT COUNT(ptt.rowid) as nb, u.rowid as user_id, u.login, u.thm as user_thm";
	$sql .= " FROM ".MAIN_DB_PREFIX."element_time as ptt, ".MAIN_DB_PREFIX."user as u";
	$sql .= " WHERE ptt.fk_user = u.rowid";
	$sql .= " AND ptt.thm IS NULL and u.thm > 0";
	$sql .= " GROUP BY u.rowid, u.login, u.thm";

	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);

		if ($num) {
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				print '<tr><td>'.$obj->login.'-'.$obj->user_id.' ('.$obj->nb.' lines to fix) -> '.$obj->user_thm;

				$db->begin();

				if (GETPOST('set_empty_time_spent_amount') == 'confirmed') {
					$sql2 = "UPDATE ".MAIN_DB_PREFIX."element_time";
					$sql2 .= " SET thm = ".$obj->user_thm." WHERE thm IS NULL AND fk_user = ".((int) $obj->user_id);
					$resql2 = $db->query($sql2);
					if (!$resql2) {
						$error++;
						dol_print_error($db);
					}
				}

				if (!$error) {
					$db->commit();
				} else {
					$db->rollback();
				}

				print'</td></tr>';

				if ($error) {
					break;
				}

				$i++;
			}
		} else {
			print '<tr><td>No time spent with empty line on users with a hourly rate defined</td></tr>';
		}
	} else {
		dol_print_error($db);
	}
}


// force_disable_of_modules_not_found
if ($ok && GETPOST('force_disable_of_modules_not_found', 'alpha')) {
	print '<tr><td colspan="2"><br>*** Force modules not found physically to be disabled (only modules adding js, css or hooks can be detected as removed physically)</td></tr>';

	$arraylistofkey = array('hooks', 'js', 'css');

	foreach ($arraylistofkey as $key) {
		$sql = "SELECT DISTINCT name, value";
		$sql .= " FROM ".MAIN_DB_PREFIX."const as c";
		$sql .= " WHERE name LIKE 'MAIN_MODULE_%_".strtoupper($key)."'";
		$sql .= " ORDER BY name";

		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);
			if ($num) {
				$i = 0;
				while ($i < $num) {
					$obj = $db->fetch_object($resql);
					$constantname = $obj->name; // Name of constant for hook or js or css declaration

					print '<tr><td>';
					print dol_escape_htmltag($constantname);

					$db->begin();

					$reg = array();
					if (preg_match('/MAIN_MODULE_(.*)_'.strtoupper($key).'/i', $constantname, $reg)) {
						$name = strtolower($reg[1]);

						if ($name) {		// An entry for key $key and module $name was found in database.
							$reloffile = '';
							$result = 'found';

							if ($key == 'hooks') {
								$reloffile = $name.'/class/actions_'.$name.'.class.php';
							}
							if ($key == 'js') {
								$value = $obj->value;
								$valuearray = (array) json_decode($value);	// Force cast into array because sometimes it is a stdClass
								$reloffile = $valuearray[0];
								$reloffile = preg_replace('/^\//', '', $valuearray[0]);
							}
							if ($key == 'css') {
								$value = $obj->value;
								$valuearray = (array) json_decode($value);	// Force cast into array because sometimes it is a stdClass
								if ($value && (!is_array($valuearray) || count($valuearray) == 0)) {
									$valuearray = array();
									$valuearray[0] = $value; // If value was not a json array but a string
								}
								$reloffile = preg_replace('/^\//', '', $valuearray[0]);
							}

							if ($reloffile) {
								//var_dump($key.' - '.$value.' - '.$reloffile);
								try {
									$result = dol_buildpath($reloffile, 0, 2);
								} catch (Exception $e) {
									$result = 'found'; // If error, we force like if we found to avoid any deletion
								}
							} else {
								$result = 'found';	//
							}

							if (!$result) {
								print ' - File of '.$key.' ('.$reloffile.') NOT found, we disable the module.';
								if (GETPOST('force_disable_of_modules_not_found') == 'confirmed') {
									$sql2 = "DELETE FROM ".MAIN_DB_PREFIX."const WHERE name = 'MAIN_MODULE_".strtoupper($name)."_".strtoupper($key)."'";
									$resql2 = $db->query($sql2);
									if (!$resql2) {
										$error++;
										dol_print_error($db);
									}
									$sql3 = "DELETE FROM ".MAIN_DB_PREFIX."const WHERE name = 'MAIN_MODULE_".strtoupper($name)."'";
									$resql3 = $db->query($sql3);
									if (!$resql3) {
										$error++;
										dol_print_error($db);
									} else {
										print ' - <span class="warning">Cleaned</span>';
									}
								} else {
									print ' - <span class="warning">Canceled (test mode)</span>';
								}
							} else {
								print ' - File of '.$key.' ('.$reloffile.') found, we do nothing.';
							}
						}

						if (!$error) {
							$db->commit();
						} else {
							$db->rollback();
						}
					}

					print'</td></tr>';

					if ($error) {
						break;
					}

					$i++;
				}
			} else {
				print '<tr><td>No active module with missing files found by searching on MAIN_MODULE_(.*)_'.strtoupper($key).'</td></tr>';
			}
		} else {
			dol_print_error($db);
		}
	}
}


// clean_old_module_entries: Clean data into const when files of module were removed without being
if ($ok && GETPOST('clean_perm_table', 'alpha')) {
	print '<tr><td colspan="2"><br>*** Clean table user_rights from lines of external modules no more enabled</td></tr>';

	$listofmods = '';
	foreach ($conf->modules as $key => $val) {
		$listofmods .= ($listofmods ? ',' : '')."'".$db->escape($val)."'";
	}

	$sql = "SELECT id, libelle as label, module from ".MAIN_DB_PREFIX."rights_def WHERE module NOT IN (".$db->sanitize($listofmods, 1).") AND id > 100000";

	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		if ($num) {
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				if ($obj->id > 0) {
					print '<tr><td>Found line with id '.$obj->id.', label "'.$obj->label.'" of module "'.$obj->module.'" to delete';
					if (GETPOST('clean_perm_table', 'alpha') == 'confirmed') {
						$sqldelete = "DELETE FROM ".MAIN_DB_PREFIX."rights_def WHERE id = ".((int) $obj->id);
						$resqldelete = $db->query($sqldelete);
						if (!$resqldelete) {
							dol_print_error($db);
						}
						print ' - deleted';
					}
					print '</td></tr>';
				}
				$i++;
			}
		} else {
			print '<tr><td>No lines of a disabled external module (with id > 100000) found into table rights_def</td></tr>';
		}
	} else {
		dol_print_error($db);
	}
}



// force utf8 on tables
if ($ok && GETPOST('force_utf8_on_tables', 'alpha')) {
	print '<tr><td colspan="2"><br>*** Force page code and collation of tables into utf8/utf8_unicode_ci and row_format=dynamic (for mysql/mariadb only)</td></tr>';

	if ($db->type == "mysql" || $db->type == "mysqli") {
		$force_utf8_on_tables = GETPOST('force_utf8_on_tables', 'alpha');

		$listoftables = $db->DDLListTablesFull($db->database_name);

		// Disable foreign key checking for avoid errors
		if ($force_utf8_on_tables == 'confirmed') {
			$sql = 'SET FOREIGN_KEY_CHECKS=0';
			print '<!-- '.$sql.' -->';
			print '<tr><td colspan="2">'.$sql.'</td></tr>';
			$resql = $db->query($sql);
		}

		$foreignkeystorestore = array();

		// First loop to delete foreign keys
		foreach ($listoftables as $table) {
			// do not convert llx_const if mysql encrypt/decrypt is used
			if ($conf->db->dolibarr_main_db_encryption != 0 && preg_match('/\_const$/', $table[0])) {
				continue;
			}
			if ($table[1] == 'VIEW') {
				print '<tr><td colspan="2">'.$table[0].' is a '.$table[1].' <span class="opacitymedium">(Skipped)</span></td></tr>';
				continue;
			}

			// Special case of tables with foreign key on varchar fields
			$arrayofforeignkey = array(
				'llx_accounting_account' => 'fk_accounting_account_fk_pcg_version',
				'llx_accounting_system' => 'fk_accounting_account_fk_pcg_version',
				'llx_c_type_contact' => 'fk_societe_commerciaux_fk_c_type_contact_code',
				'llx_societe_commerciaux' => 'fk_societe_commerciaux_fk_c_type_contact_code'
			);

			foreach ($arrayofforeignkey as $tmptable => $foreignkeyname) {
				if ($table[0] == $tmptable) {
					print '<tr><td colspan="2">';
					$sqltmp = 'ALTER TABLE '.$table[0].' DROP FOREIGN KEY '.$foreignkeyname;
					print $sqltmp;
					if ($force_utf8_on_tables == 'confirmed') {
						$resqltmp = $db->query($sqltmp);
					} else {
						print ' - <span class="opacitymedium">Disabled</span>';
					}
					print '</td></tr>';
					$foreignkeystorestore[$tmptable] = $foreignkeyname;
				}
			}
		}

		foreach ($listoftables as $table) {
			// do not convert llx_const if mysql encrypt/decrypt is used
			if ($conf->db->dolibarr_main_db_encryption != 0 && preg_match('/\_const$/', $table[0])) {
				continue;
			}
			if ($table[1] == 'VIEW') {
				print '<tr><td colspan="2">'.$table[0].' is a '.$table[1].' <span class="opacitymedium">(Skipped)</span></td></tr>';
				continue;
			}

			print '<tr><td colspan="2">';
			print $table[0];
			$sql1 = "ALTER TABLE ".$table[0]." ROW_FORMAT=dynamic";
			$sql2 = "ALTER TABLE ".$table[0]." CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci";
			print '<!-- '.$sql1.' -->';
			print '<!-- '.$sql2.' -->';
			if ($force_utf8_on_tables == 'confirmed') {
				$resql1 = $db->query($sql1);
				if ($resql1) {
					$resql2 = $db->query($sql2);
				} else {
					$resql2 = false;
				}
				print ' - Done '.(($resql1 && $resql2) ? '<span class="opacitymedium">(OK)</span>' : '<span class="error" title="'.dol_escape_htmltag($db->lasterror).'">(KO)</span>');
			} else {
				print ' - <span class="opacitymedium">Disabled</span>';
			}
			print '</td></tr>';
			flush();
			ob_flush();
		}

		// Restore dropped foreign keys
		foreach ($foreignkeystorestore as $tmptable => $foreignkeyname) {
			$stringtofindinline = 'ALTER TABLE .* ADD CONSTRAINT '.$foreignkeyname;
			$fileforkeys = DOL_DOCUMENT_ROOT.'/install/mysql/tables/'.$tmptable.'.key.sql';
			//print 'Search in '.$fileforkeys.' to get '.$stringtofindinline."<br>\n";

			$handle = fopen($fileforkeys, 'r');
			if ($handle) {
				while (($line = fgets($handle)) !== false) {
					// Process the line read.
					if (preg_match('/^'.$stringtofindinline.'/i', $line)) {
						$resqltmp = $db->query($line);
						print '<tr><td colspan="2">';
						print $line;
						print ' - Done '.($resqltmp ? '<span class="opacitymedium">(OK)</span>' : '<span class="error" title="'.dol_escape_htmltag($db->lasterror).'">(KO)</span>');
						print '</td></tr>';
						break;
					}
				}
				fclose($handle);
			}
			flush();
			ob_flush();
		}

		// Enable foreign key checking
		if ($force_utf8_on_tables == 'confirmed') {
			$sql = 'SET FOREIGN_KEY_CHECKS=1';
			print '<!-- '.$sql.' -->';
			print '<tr><td colspan="2">'.$sql.'</td></tr>';
			$resql = $db->query($sql);
		}
	} else {
		print '<tr><td colspan="2">Not available with database type '.$db->type.'</td></tr>';
	}
}

// force utf8mb4 on tables  EXPERIMENTAL !
if ($ok && GETPOST('force_utf8mb4_on_tables', 'alpha')) {
	print '<tr><td colspan="2"><br>*** Force page code and collation of tables into utf8mb4/utf8mb4_unicode_ci (for mysql/mariadb only)</td></tr>';

	if ($db->type == "mysql" || $db->type == "mysqli") {
		$force_utf8mb4_on_tables = GETPOST('force_utf8mb4_on_tables', 'alpha');


		$listoftables = $db->DDLListTablesFull($db->database_name);

		// Disable foreign key checking for avoid errors
		if ($force_utf8mb4_on_tables == 'confirmed') {
			$sql = 'SET FOREIGN_KEY_CHECKS=0';
			print '<!-- '.$sql.' -->';
			print '<tr><td colspan="2">'.$sql.'</td></tr>';
			$resql = $db->query($sql);
		}

		$foreignkeystorestore = array();

		// First loop to delete foreign keys
		foreach ($listoftables as $table) {
			// do not convert llx_const if mysql encrypt/decrypt is used
			if ($conf->db->dolibarr_main_db_encryption != 0 && preg_match('/\_const$/', $table[0])) {
				continue;
			}
			if ($table[1] == 'VIEW') {
				print '<tr><td colspan="2">'.$table[0].' is a '.$table[1].' <span class="opacitymedium">(Skipped)</span></td></tr>';
				continue;
			}

			// Special case of tables with foreign key on varchar fields
			$arrayofforeignkey = array(
				'llx_accounting_account' => 'fk_accounting_account_fk_pcg_version',
				'llx_accounting_system' => 'fk_accounting_account_fk_pcg_version',
				'llx_c_type_contact' => 'fk_societe_commerciaux_fk_c_type_contact_code',
				'llx_societe_commerciaux' => 'fk_societe_commerciaux_fk_c_type_contact_code'
			);

			foreach ($arrayofforeignkey as $tmptable => $foreignkeyname) {
				if ($table[0] == $tmptable) {
					print '<tr><td colspan="2">';
					$sqltmp = 'ALTER TABLE '.$table[0].' DROP FOREIGN KEY '.$foreignkeyname;
					print $sqltmp;
					if ($force_utf8mb4_on_tables == 'confirmed') {
						$resqltmp = $db->query($sqltmp);
					} else {
						print ' - <span class="opacitymedium">Disabled</span>';
					}
					print '</td></tr>';
					$foreignkeystorestore[$tmptable] = $foreignkeyname;
				}
			}
		}

		foreach ($listoftables as $table) {
			// do not convert llx_const if mysql encrypt/decrypt is used
			if ($conf->db->dolibarr_main_db_encryption != 0 && preg_match('/\_const$/', $table[0])) {
				continue;
			}
			if ($table[1] == 'VIEW') {
				print '<tr><td colspan="2">'.$table[0].' is a '.$table[1].' <span class="opacitymedium">(Skipped)</span></td></tr>';
				continue;
			}

			print '<tr><td colspan="2">';
			print $table[0];
			$sql1 = "ALTER TABLE ".$table[0]." ROW_FORMAT=dynamic";
			$sql2 = "ALTER TABLE ".$table[0]." CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
			print '<!-- '.$sql1.' -->';
			print '<!-- '.$sql2.' -->';
			if ($force_utf8mb4_on_tables == 'confirmed') {
				$resql1 = $db->query($sql1);
				if ($resql1) {
					$resql2 = $db->query($sql2);
				} else {
					$resql2 = false;
				}
				print ' - Done '.(($resql1 && $resql2) ? '<span class="opacitymedium">(OK)</span>' : '<span class="error" title="'.dol_escape_htmltag($db->lasterror).'">(KO)</span>');
			} else {
				print ' - <span class="opacitymedium">Disabled</span>';
			}
			print '</td></tr>';
			flush();
			ob_flush();
		}

		// Restore dropped foreign keys
		foreach ($foreignkeystorestore as $tmptable => $foreignkeyname) {
			$stringtofindinline = 'ALTER TABLE .* ADD CONSTRAINT '.$foreignkeyname;
			$fileforkeys = DOL_DOCUMENT_ROOT.'/install/mysql/tables/'.$tmptable.'.key.sql';
			//print 'Search in '.$fileforkeys.' to get '.$stringtofindinline."<br>\n";

			$handle = fopen($fileforkeys, 'r');
			if ($handle) {
				while (($line = fgets($handle)) !== false) {
					// Process the line read.
					if (preg_match('/^'.$stringtofindinline.'/i', $line)) {
						$resqltmp = $db->query($line);
						print '<tr><td colspan="2">';
						print $line;
						print ' - Done '.($resqltmp ? '<span class="opacitymedium">(OK)</span>' : '<span class="error" title="'.dol_escape_htmltag($db->lasterror).'">(KO)</span>');
						print '</td></tr>';
						break;
					}
				}
				fclose($handle);
			}
			flush();
			ob_flush();
		}

		// Enable foreign key checking
		if ($force_utf8mb4_on_tables == 'confirmed') {
			$sql = 'SET FOREIGN_KEY_CHECKS=1';
			print '<!-- '.$sql.' -->';
			print '<tr><td colspan="2">'.$sql.'</td></tr>';
			$resql = $db->query($sql);
		}
	} else {
		print '<tr><td colspan="2">Not available with database type '.$db->type.'</td></tr>';
	}
}

if ($ok && GETPOST('force_collation_from_conf_on_tables', 'alpha')) {
	print '<tr><td colspan="2"><br>*** Force page code and collation of tables into '.$conf->db->character_set.'/'.$conf->db->dolibarr_main_db_collation.' and row_format=dynamic (for mysql/mariadb only)</td></tr>';

	if ($db->type == "mysql" || $db->type == "mysqli") {
		$force_collation_from_conf_on_tables = GETPOST('force_collation_from_conf_on_tables', 'alpha');

		$listoftables = $db->DDLListTablesFull($db->database_name);

		// Disable foreign key checking for avoid errors
		if ($force_collation_from_conf_on_tables == 'confirmed') {
			$sql = 'SET FOREIGN_KEY_CHECKS=0';
			print '<!-- '.$sql.' -->';
			$resql = $db->query($sql);
		}

		foreach ($listoftables as $table) {
			// do not convert collation on llx_const if mysql encrypt/decrypt is used
			if ($conf->db->dolibarr_main_db_encryption != 0 && preg_match('/\_const$/', $table[0])) {
				continue;
			}
			if ($table[1] == 'VIEW') {
				print '<tr><td colspan="2">'.$table[0].' is a '.$table[1].' (Skipped)</td></tr>';
				continue;
			}

			print '<tr><td colspan="2">';
			print $table[0];
			$sql1 = "ALTER TABLE ".$table[0]." ROW_FORMAT=dynamic";
			$sql2 = "ALTER TABLE ".$table[0]." CONVERT TO CHARACTER SET ".$conf->db->character_set." COLLATE ".$conf->db->dolibarr_main_db_collation;
			print '<!-- '.$sql1.' -->';
			print '<!-- '.$sql2.' -->';
			if ($force_collation_from_conf_on_tables == 'confirmed') {
				$resql1 = $db->query($sql1);
				if ($resql1) {
					$resql2 = $db->query($sql2);
				} else {
					$resql2 = false;
				}
				print ' - Done '.(($resql1 && $resql2) ? '<span class="opacitymedium">(OK)</span>' : '<span class="error" title="'.dol_escape_htmltag($db->lasterror).'">(KO)</span>');
			} else {
				print ' - <span class="opacitymedium">Disabled</span>';
			}
			print '</td></tr>';
		}

		// Enable foreign key checking
		if ($force_collation_from_conf_on_tables == 'confirmed') {
			$sql = 'SET FOREIGN_KEY_CHECKS=1';
			print '<!-- '.$sql.' -->';
			$resql = $db->query($sql);
		}
	} else {
		print '<tr><td colspan="2">Not available with database type '.$db->type.'</td></tr>';
	}
}

// rebuild sequences for pgsql
if ($ok && GETPOST('rebuild_sequences', 'alpha')) {
	print '<tr><td colspan="2"><br>*** Force to rebuild sequences (for postgresql only)</td></tr>';

	if ($db->type == "pgsql") {
		$rebuild_sequence = GETPOST('rebuild_sequences', 'alpha');

		if ($rebuild_sequence == 'confirmed') {
			$sql = "SELECT dol_util_rebuild_sequences();";
			print '<!-- '.$sql.' -->';
			$resql = $db->query($sql);
		}
	} else {
		print '<tr><td colspan="2">Not available with database type '.$db->type.'</td></tr>';
	}
}

//
if ($ok && GETPOST('repair_link_dispatch_lines_supplier_order_lines')) {
	/*
	 * This script is meant to be run when upgrading from a dolibarr version < 3.8
	 * to a newer version.
	 *
	 * Version 3.8 introduces a new column in llx_commande_fournisseur_dispatch, which
	 * matches the dispatch to a specific supplier order line (so that if there are
	 * several with the same product, the user can specifically tell which products of
	 * which line were dispatched where).
	 *
	 * However when migrating, the new column has a default value of 0, which means that
	 * old supplier orders whose lines were dispatched using the old dolibarr version
	 * have unspecific dispatch lines, which are not taken into account by the new version,
	 * thus making the order look like it was never dispatched at all.
	 *
	 * This scripts sets this foreign key to the first matching supplier order line whose
	 * product (and supplier order of course) are the same as the dispatch’s.
	 *
	 * If the dispatched quantity is more than indicated on the order line (this happens if
	 * there are several order lines for the same product), it creates new dispatch lines
	 * pointing to the other order lines accordingly, until all the dispatched quantity is
	 * accounted for.
	 */

	$repair_link_dispatch_lines_supplier_order_lines = GETPOST('repair_link_dispatch_lines_supplier_order_lines', 'alpha');


	echo '<tr><th>Repair llx_receptiondet_batch.fk_commandefourndet</th></tr>';
	echo '<tr><td>Repair in progress. This may take a while.</td></tr>';

	$sql_dispatch = 'SELECT * FROM '.MAIN_DB_PREFIX.'receptiondet_batch WHERE COALESCE(fk_elementdet, 0) = 0';
	$db->begin();
	$resql_dispatch = $db->query($sql_dispatch);
	$n_processed_rows = 0;
	$errors = array();
	if ($resql_dispatch) {
		if ($db->num_rows($resql_dispatch) == 0) {
			echo '<tr><td>Nothing to do.</td></tr>';
			exit;
		}
		while ($obj_dispatch = $db->fetch_object($resql_dispatch)) {
			$sql_line = 'SELECT line.rowid, line.qty FROM '.MAIN_DB_PREFIX.'commande_fournisseurdet AS line';
			$sql_line .= ' WHERE line.fk_commande = '.((int) $obj_dispatch->fk_commande);
			$sql_line .= ' AND   line.fk_product  = '.((int) $obj_dispatch->fk_product);
			$resql_line = $db->query($sql_line);

			// s’il y a plusieurs lignes avec le même produit sur cette commande fournisseur,
			// on divise la ligne de dispatch en autant de lignes qu’on en a sur la commande pour le produit
			// et on met la quantité de la ligne dans la limit du "budget" indiqué par dispatch.qty

			$remaining_qty = $obj_dispatch->qty;
			$first_iteration = true;
			if (!$resql_line) {
				echo '<tr><td>Unable to find a matching supplier order line for dispatch #'.$obj_dispatch->rowid.'</td></tr>';
				$errors[] = $sql_line;
				$n_processed_rows++;
				continue;
			}
			if ($db->num_rows($resql_line) == 0) {
				continue;
			}
			while ($obj_line = $db->fetch_object($resql_line)) {
				if (!$remaining_qty) {
					break;
				}
				if (!$obj_line->rowid) {
					continue;
				}
				$qty_for_line = min($remaining_qty, $obj_line->qty);
				if ($first_iteration) {
					$sql_attach = 'UPDATE '.MAIN_DB_PREFIX.'receptiondet_batch';
					$sql_attach .= ' SET fk_elementdet = '.((int) $obj_line->rowid).', qty = '.((float) $qty_for_line);
					$sql_attach .= ' WHERE rowid = '.((int) $obj_dispatch->rowid);
					$first_iteration = false;
				} else {
					$sql_attach_values = array(
						(string) ((int) $obj_dispatch->fk_element),
						(string) ((int) $obj_dispatch->fk_product),
						(string) ((int) $obj_line->rowid),
						(string) ((float) $qty_for_line),
						(string) ((int) $obj_dispatch->fk_entrepot),
						(string) ((int) $obj_dispatch->fk_user),
						$obj_dispatch->datec ? "'".$db->idate($db->jdate($obj_dispatch->datec))."'" : 'NULL',
						$obj_dispatch->comment ? "'".$db->escape($obj_dispatch->comment)."'" : 'NULL',
						$obj_dispatch->status ? (string) ((int) $obj_dispatch->status) : 'NULL',
						$obj_dispatch->tms ? "'".$db->idate($db->jdate($obj_dispatch->tms))."'" : 'NULL',
						$obj_dispatch->batch ? "'".$db->escape($obj_dispatch->batch)."'" : 'NULL',
						$obj_dispatch->eatby ? "'".$db->escape($obj_dispatch->eatby)."'" : 'NULL',
						$obj_dispatch->sellby ? "'".$db->escape($obj_dispatch->sellby)."'" : 'NULL'
					);
					$sql_attach_values = implode(', ', $sql_attach_values);

					$sql_attach = 'INSERT INTO '.MAIN_DB_PREFIX.'receptiondet_batch';
					$sql_attach .= ' (fk_element, fk_product, fk_elementdet, qty, fk_entrepot, fk_user, datec, comment, status, tms, batch, eatby, sellby)';
					$sql_attach .= " VALUES (".$sql_attach_values.")";	// The string is already sanitized
				}

				if ($repair_link_dispatch_lines_supplier_order_lines == 'confirmed') {
					$resql_attach = $db->query($sql_attach);
				} else {
					$resql_attach = true; // Force success in test mode
				}

				if ($resql_attach) {
					$remaining_qty -= $qty_for_line;
				} else {
					$errors[] = $sql_attach;
				}

				$first_iteration = false;
			}
			$n_processed_rows++;

			// report progress every 256th row
			if (!($n_processed_rows & 0xff)) {
				echo '<tr><td>Processed '.$n_processed_rows.' rows with '.count($errors).' errors…'."</td></tr>\n";
				flush();
				ob_flush();
			}
		}
	} else {
		echo '<tr><td>Unable to find any dispatch without an fk_commandefourndet.'."</td></tr>\n";
		echo $sql_dispatch."\n";
	}
	echo '<tr><td>Fixed '.$n_processed_rows.' rows with '.count($errors).' errors…'."</td></tr>\n";
	echo '<tr><td>DONE.'."</td></tr>\n";

	if (count($errors)) {
		$db->rollback();
		echo '<tr><td>The transaction was rolled back due to errors: nothing was changed by the script.</td></tr>';
	} else {
		$db->commit();
	}
	$db->close();

	echo '<tr><td><h3>SQL queries with errors:</h3></tr></td>';
	echo '<tr><td>'.implode('</td></tr><tr><td>', $errors).'</td></tr>';
}

// Repair llx_commande_fournisseur to eliminate duplicate reference
if ($ok && GETPOST('repair_supplier_order_duplicate_ref')) {
	require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.commande.class.php';
	include_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';

	$db->begin();

	$err = 0;

	// Query to find all duplicate supplier orders
	$sql = "SELECT * FROM " . MAIN_DB_PREFIX . "commande_fournisseur";
	$sql .= " WHERE ref IN (SELECT cf.ref FROM " . MAIN_DB_PREFIX . "commande_fournisseur cf GROUP BY cf.ref, cf.entity HAVING COUNT(cf.rowid) > 1)";

	// Build a list of ref => []CommandeFournisseur
	$duplicateSupplierOrders = [];
	$resql = $db->query($sql);
	if ($resql) {
		while ($rawSupplierOrder = $db->fetch_object($resql)) {
			$supplierOrder = new CommandeFournisseur($db);
			$supplierOrder->setVarsFromFetchObj($rawSupplierOrder);

			$duplicateSupplierOrders[$rawSupplierOrder->ref] [] = $supplierOrder;
		}
	} else {
		$err++;
	}

	// Process all duplicate supplier order and regenerate the reference for all except the first one
	foreach ($duplicateSupplierOrders as $ref => $supplierOrders) {
		/** @var CommandeFournisseur $supplierOrder */
		foreach (array_slice($supplierOrders, 1) as $supplierOrder) {
			// Definition of supplier order numbering model name
			$soc = new Societe($db);
			$soc->fetch($supplierOrder->fourn_id);

			$newRef = $supplierOrder->getNextNumRef($soc);

			$sql = "UPDATE " . MAIN_DB_PREFIX . "commande_fournisseur cf SET cf.ref = '" . $db->escape($newRef) . "' WHERE cf.rowid = " . (int) $supplierOrder->id;
			if (!$db->query($sql)) {
				$err++;
			}
		}
	}

	if ($err == 0) {
		$db->commit();
	} else {
		$db->rollback();
	}
}

// Repair llx_invoice to calculate totals from line items
// WARNING : The process can be long on production environments due to restrictions.
// consider raising php_max_execution time if failing to execute completely.
if ($ok && GETPOST('recalculateinvoicetotal') == 'confirmed') {
	$err = 0;
	$db->begin();
	$sql = "SELECT f.rowid, SUM(fd.total_ht) as total_ht";
	$sql .= " FROM ".MAIN_DB_PREFIX."facture f";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."facturedet fd ON fd.fk_facture = f.rowid";
	$sql .= " WHERE f.total_ht = 0";
	$sql .= " GROUP BY fd.fk_facture HAVING SUM(fd.total_ht) <> 0";

	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		print "We found ".$num." factures qualified that will have their total recalculated because they are at zero and line items not at zero\n";
		dol_syslog("We found ".$num." factures qualified that will have their total recalculated because they are at zero and line items not at zero");

		if ($num) {
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				$sql_calculs = "
					SELECT
						SUM(fd.total_ht) as 'total_ht',
						SUM(fd.total_tva) as 'total_tva',
						SUM(fd.total_localtax1) as 'localtax1',
						SUM(fd.total_localtax2) as 'localtax2',
						SUM(fd.total_ttc) as 'total_ttc'
					FROM
						".MAIN_DB_PREFIX."facturedet fd
					WHERE
						fd.fk_facture = $obj->rowid";
				$ressql_calculs = $db->query($sql_calculs);
				while ($obj_calcul = $db->fetch_object($ressql_calculs)) {
					$sql_maj = "
						UPDATE ".MAIN_DB_PREFIX."facture
						SET
							total_ht = ".($obj_calcul->total_ht ? price2num($obj_calcul->total_ht, 'MT') : 0).",
							total_tva = ".($obj_calcul->total_tva ? price2num($obj_calcul->total_tva, 'MT') : 0).",
							localtax1 = ".($obj_calcul->localtax1 ? price2num($obj_calcul->localtax1, 'MT') : 0).",
							localtax2 = ".($obj_calcul->localtax2 ? price2num($obj_calcul->localtax2, 'MT') : 0).",
							total_ttc = ".($obj_calcul->total_ttc ? price2num($obj_calcul->total_ttc, 'MT') : 0)."
						WHERE
							rowid = $obj->rowid";
					$db->query($sql_maj);
				}
				$i++;
			}
		} else {
			print "Pas de factures à traiter\n";
		}
	} else {
		dol_print_error($db);
		dol_syslog("calculate_total_and_taxes.php: Error");
		$err++;
	}

	if ($err == 0) {
		$db->commit();
	} else {
		$db->rollback();
	}
}

print '</table>';

if (empty($actiondone)) {
	print '<div class="error">'.$langs->trans("ErrorWrongParameters").'</div>';
}

if ($oneoptionset) {
	print '<div class="center" style="padding-top: 10px"><a href="../index.php?mainmenu=home&leftmenu=home'.(GETPOSTISSET("login") ? '&username='.urlencode(GETPOST("login")) : '').'">';
	print $langs->trans("GoToDolibarr");
	print '</a></div>';
}

dolibarr_install_syslog("--- repair: end");
pFooter(1, $setuplang);

if ($db->connected) {
	$db->close();
}

// Return code if ran from command line
if (!$ok && isset($argv[1])) {
	exit(1);
}
