<?php
/* Copyright (C) 2009-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *  \file		htdocs/core/lib/modulebuilder.lib.php
 *  \brief		Set of function for modulebuilder management
 */


/**
 * 	Regenerate files .class.php
 *
 *  @param	string      $destdir		Directory
 * 	@param	string		$module			Module name
 *  @param	string      $objectname		Name of object
 * 	@param	string		$newmask		New mask
 *  @param	string      $readdir		Directory source (use $destdir when not defined)
 *  @param	string		$addfieldentry	Array of 1 field entry to add array('key'=>,'type'=>,''label'=>,'visible'=>,'enabled'=>,'position'=>,'notnull'=>','index'=>,'searchall'=>,'comment'=>,'help'=>,'isameasure')
 *  @param	string		$delfieldentry	Id of field to remove
 * 	@return	int|object					<=0 if KO, Object if OK
 *  @see rebuildObjectSql()
 */
function rebuildObjectClass($destdir, $module, $objectname, $newmask, $readdir = '', $addfieldentry = array(), $delfieldentry = '')
{
	global $db, $langs;

	if (empty($objectname)) {
		return -6;
	}
	if (empty($readdir)) {
		$readdir = $destdir;
	}

	if (!empty($addfieldentry['arrayofkeyval']) && !is_array($addfieldentry['arrayofkeyval'])) {
		dol_print_error('', 'Bad parameter addfieldentry with a property arrayofkeyval defined but that is not an array.');
		return -7;
	}

	$error = 0;

	// Check parameters
	if (is_array($addfieldentry) && count($addfieldentry) > 0) {
		if (empty($addfieldentry['name'])) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Name")), null, 'errors');
			return -2;
		}
		if (empty($addfieldentry['label'])) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Label")), null, 'errors');
			return -2;
		}
		if (!preg_match('/^(integer|price|sellist|varchar|double|text|html|duration)/', $addfieldentry['type'])
			&& !preg_match('/^(boolean|smallint|real|date|datetime|timestamp|phone|mail|url|ip|password)$/', $addfieldentry['type'])) {
			setEventMessages($langs->trans('BadValueForType', $addfieldentry['type']), null, 'errors');
			return -2;
		}
	}

	$pathoffiletoeditsrc = $readdir.'/class/'.strtolower($objectname).'.class.php';
	$pathoffiletoedittarget = $destdir.'/class/'.strtolower($objectname).'.class.php'.($readdir != $destdir ? '.new' : '');
	if (!dol_is_file($pathoffiletoeditsrc)) {
		$langs->load("errors");
		setEventMessages($langs->trans("ErrorFileNotFound", $pathoffiletoeditsrc), null, 'errors');
		return -3;
	}

	//$pathoffiletoedittmp=$destdir.'/class/'.strtolower($objectname).'.class.php.tmp';
	//dol_delete_file($pathoffiletoedittmp, 0, 1, 1);

	try {
		include_once $pathoffiletoeditsrc;
		if (class_exists($objectname)) {
			$object = new $objectname($db);
		} else {
			return -4;
		}

		// Backup old file
		dol_copy($pathoffiletoedittarget, $pathoffiletoedittarget.'.back', $newmask, 1);

		// Edit class files
		$contentclass = file_get_contents(dol_osencode($pathoffiletoeditsrc), 'r');

		// Update ->fields (to add or remove entries defined into $addfieldentry)
		if (count($object->fields)) {
			if (is_array($addfieldentry) && count($addfieldentry)) {
				$name = $addfieldentry['name'];
				unset($addfieldentry['name']);

				$object->fields[$name] = $addfieldentry;
			}
			if (!empty($delfieldentry)) {
				$name = $delfieldentry;
				unset($object->fields[$name]);
			}
		}

		dol_sort_array($object->fields, 'position');

		$i = 0;
		$texttoinsert = '// BEGIN MODULEBUILDER PROPERTIES'."\n";
		$texttoinsert .= "\t".'/**'."\n";
		$texttoinsert .= "\t".' * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.'."\n";
		$texttoinsert .= "\t".' */'."\n";
		$texttoinsert .= "\t".'public $fields=array('."\n";

		if (count($object->fields)) {
			foreach ($object->fields as $key => $val) {
				$i++;
				$texttoinsert .= "\t\t'".$key."' => array('type'=>'".$val['type']."',";
				$texttoinsert .= " 'label'=>'".$val['label']."',";
				if (!empty($val['picto'])) {
					$texttoinsert .= " 'picto'=>'".$val['picto']."',";
				}
				$texttoinsert .= " 'enabled'=>'".($val['enabled'] !== '' ? $val['enabled'] : 1)."',";
				$texttoinsert .= " 'position'=>".($val['position'] !== '' ? $val['position'] : 50).",";
				$texttoinsert .= " 'notnull'=>".(empty($val['notnull']) ? 0 : $val['notnull']).",";
				$texttoinsert .= " 'visible'=>".($val['visible'] !== '' ? $val['visible'] : -1).",";
				if (!empty($val['noteditable'])) {
					$texttoinsert .= " 'noteditable'=>'".$val['noteditable']."',";
				}
				if (!empty($val['alwayseditable'])) {
					$texttoinsert .= " 'alwayseditable'=>'".$val['alwayseditable']."',";
				}
				if (!empty($val['default']) || (isset($val['default']) && $val['default'] === '0')) {
					$texttoinsert .= " 'default'=>'".$val['default']."',";
				}
				if (!empty($val['index'])) {
					$texttoinsert .= " 'index'=>".$val['index'].",";
				}
				if (!empty($val['foreignkey'])) {
					$texttoinsert .= " 'foreignkey'=>'".$val['foreignkey']."',";
				}
				if (!empty($val['searchall'])) {
					$texttoinsert .= " 'searchall'=>".$val['searchall'].",";
				}
				if (!empty($val['isameasure'])) {
					$texttoinsert .= " 'isameasure'=>'".$val['isameasure']."',";
				}
				if (!empty($val['css'])) {
					$texttoinsert .= " 'css'=>'".$val['css']."',";
				}
				if (!empty($val['cssview'])) {
					$texttoinsert .= " 'cssview'=>'".$val['cssview']."',";
				}
				if (!empty($val['csslist'])) {
					$texttoinsert .= " 'csslist'=>'".$val['csslist']."',";
				}
				if (!empty($val['help'])) {
					$texttoinsert .= " 'help'=>\"".preg_replace('/"/', '', $val['help'])."\",";
				}
				if (!empty($val['showoncombobox'])) {
					$texttoinsert .= " 'showoncombobox'=>'".$val['showoncombobox']."',";
				}
				if (!empty($val['disabled'])) {
					$texttoinsert .= " 'disabled'=>'".$val['disabled']."',";
				}
				if (!empty($val['autofocusoncreate'])) {
					$texttoinsert .= " 'autofocusoncreate'=>'".$val['autofocusoncreate']."',";
				}
				if (!empty($val['arrayofkeyval'])) {
					$texttoinsert .= " 'arrayofkeyval'=>array(";
					$i = 0;
					foreach ($val['arrayofkeyval'] as $key2 => $val2) {
						if ($i) {
							$texttoinsert .= ", ";
						}
						$texttoinsert .= "'".$key2."'=>'".$val2."'";
						$i++;
					}
					$texttoinsert .= "),";
				}
				if (!empty($val['validate'])) {
					$texttoinsert .= " 'validate'=>'".$val['validate']."',";
				}
				if (!empty($val['comment'])) {
					$texttoinsert .= " 'comment'=>\"".preg_replace('/"/', '', $val['comment'])."\"";
				}

				$texttoinsert .= "),\n";
				//print $texttoinsert;
			}
		}

		$texttoinsert .= "\t".');'."\n";
		//print ($texttoinsert);exit;

		if (count($object->fields)) {
			//$typetotypephp=array('integer'=>'integer', 'duration'=>'integer', 'varchar'=>'string');

			foreach ($object->fields as $key => $val) {
				$i++;
				//$typephp=$typetotypephp[$val['type']];
				$texttoinsert .= "\t".'public $'.$key.";";
				//if ($key == 'rowid')  $texttoinsert.= ' AUTO_INCREMENT PRIMARY KEY';
				//if ($key == 'entity') $texttoinsert.= ' DEFAULT 1';
				//$texttoinsert.= ($val['notnull']?' NOT NULL':'');
				//if ($i < count($object->fields)) $texttoinsert.=";";
				$texttoinsert .= "\n";
			}
		}

		$texttoinsert .= "\t".'// END MODULEBUILDER PROPERTIES';

		//print($texttoinsert);

		$contentclass = preg_replace('/\/\/ BEGIN MODULEBUILDER PROPERTIES.*END MODULEBUILDER PROPERTIES/ims', $texttoinsert, $contentclass);
		//print $contentclass;

		dol_mkdir(dirname($pathoffiletoedittarget));

		//file_put_contents($pathoffiletoedittmp, $contentclass);
		$result = file_put_contents(dol_osencode($pathoffiletoedittarget), $contentclass);

		if ($result) {
			dolChmod($pathoffiletoedittarget, $newmask);
		} else {
			$error++;
		}

		return $error ? -1 : $object;
	} catch (Exception $e) {
		print $e->getMessage();
		return -5;
	}
}

/**
 * 	Save data into a memory area shared by all users, all sessions on server
 *
 *  @param	string      $destdir		Directory
 * 	@param	string		$module			Module name
 *  @param	string      $objectname		Name of object
 * 	@param	string		$newmask		New mask
 *  @param	string      $readdir		Directory source (use $destdir when not defined)
 *  @param	Object		$object			If object was already loaded/known, it is pass to avoid another include and new.
 *  @param	string		$moduletype		'external' or 'internal'
 * 	@return	int							<=0 if KO, >0 if OK
 *  @see rebuildObjectClass()
 */
function rebuildObjectSql($destdir, $module, $objectname, $newmask, $readdir = '', $object = null, $moduletype = 'external')
{
	global $db, $langs;

	$error = 0;

	if (empty($objectname)) {
		return -1;
	}
	if (empty($readdir)) {
		$readdir = $destdir;
	}

	$pathoffiletoclasssrc = $readdir.'/class/'.strtolower($objectname).'.class.php';

	// Edit .sql file
	if ($moduletype == 'internal') {
		$pathoffiletoeditsrc = '/../install/mysql/tables/llx_'.strtolower($module).'_'.strtolower($objectname).'.sql';
		if (! dol_is_file($readdir.$pathoffiletoeditsrc)) {
			$pathoffiletoeditsrc = '/../install/mysql/tables/llx_'.strtolower($module).'_'.strtolower($objectname).'-'.strtolower($module).'.sql';
			if (! dol_is_file($readdir.$pathoffiletoeditsrc)) {
				$pathoffiletoeditsrc = '/../install/mysql/tables/llx_'.strtolower($module).'-'.strtolower($module).'.sql';
				if (! dol_is_file($readdir.$pathoffiletoeditsrc)) {
					$pathoffiletoeditsrc = '/../install/mysql/tables/llx_'.strtolower($module).'.sql';
				}
			}
		}
	} else {
		$pathoffiletoeditsrc = '/sql/llx_'.strtolower($module).'_'.strtolower($objectname).'.sql';
		if (! dol_is_file($readdir.$pathoffiletoeditsrc)) {
			$pathoffiletoeditsrc = '/sql/llx_'.strtolower($module).'_'.strtolower($objectname).'-'.strtolower($module).'.sql';
			if (! dol_is_file($readdir.$pathoffiletoeditsrc)) {
				$pathoffiletoeditsrc = '/sql/llx_'.strtolower($module).'-'.strtolower($module).'.sql';
				if (! dol_is_file($readdir.$pathoffiletoeditsrc)) {
					$pathoffiletoeditsrc = '/sql/llx_'.strtolower($module).'.sql';
				}
			}
		}
	}

	// Complete path to be full path
	$pathoffiletoedittarget = $destdir.$pathoffiletoeditsrc.($readdir != $destdir ? '.new' : '');
	$pathoffiletoeditsrc = $readdir.$pathoffiletoeditsrc;

	if (!dol_is_file($pathoffiletoeditsrc)) {
		$langs->load("errors");
		setEventMessages($langs->trans("ErrorFileNotFound", $pathoffiletoeditsrc), null, 'errors');
		return -1;
	}

	// Load object from myobject.class.php
	try {
		if (!is_object($object)) {
			include_once $pathoffiletoclasssrc;
			if (class_exists($objectname)) {
				$object = new $objectname($db);
			} else {
				return -1;
			}
		}
	} catch (Exception $e) {
		print $e->getMessage();
	}

	// Backup old file
	dol_copy($pathoffiletoedittarget, $pathoffiletoedittarget.'.back', $newmask, 1);

	$contentsql = file_get_contents(dol_osencode($pathoffiletoeditsrc), 'r');

	$i = 0;
	$texttoinsert = '-- BEGIN MODULEBUILDER FIELDS'."\n";
	if (count($object->fields)) {
		foreach ($object->fields as $key => $val) {
			$i++;

			$type = $val['type'];
			$type = preg_replace('/:.*$/', '', $type); // For case type = 'integer:Societe:societe/class/societe.class.php'

			if ($type == 'html') {
				$type = 'text'; // html modulebuilder type is a text type in database
			} elseif ($type == 'price') {
				$type = 'double'; // html modulebuilder type is a text type in database
			} elseif (in_array($type, array('link', 'sellist', 'duration'))) {
				$type = 'integer';
			}
			$texttoinsert .= "\t".$key." ".$type;
			if ($key == 'rowid') {
				$texttoinsert .= ' AUTO_INCREMENT PRIMARY KEY';
			} elseif ($type == 'timestamp') {
				$texttoinsert .= ' DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP';
			}
			if ($key == 'entity') {
				$texttoinsert .= ' DEFAULT 1';
			} else {
				if (!empty($val['default'])) {
					if (preg_match('/^null$/i', $val['default'])) {
						$texttoinsert .= " DEFAULT NULL";
					} elseif (preg_match('/varchar/', $type)) {
						$texttoinsert .= " DEFAULT '".$db->escape($val['default'])."'";
					} else {
						$texttoinsert .= (($val['default'] > 0) ? ' DEFAULT '.$val['default'] : '');
					}
				}
			}
			$texttoinsert .= ((!empty($val['notnull']) && $val['notnull'] > 0) ? ' NOT NULL' : '');
			if ($i < count($object->fields)) {
				$texttoinsert .= ", ";
			}
			$texttoinsert .= "\n";
		}
	}
	$texttoinsert .= "\t".'-- END MODULEBUILDER FIELDS';

	$contentsql = preg_replace('/-- BEGIN MODULEBUILDER FIELDS.*END MODULEBUILDER FIELDS/ims', $texttoinsert, $contentsql);

	$result = file_put_contents($pathoffiletoedittarget, $contentsql);
	if ($result) {
		dolChmod($pathoffiletoedittarget, $newmask);
	} else {
		$error++;
		setEventMessages($langs->trans("ErrorFailToCreateFile", $pathoffiletoedittarget), null, 'errors');
	}

	// Edit .key.sql file
	$pathoffiletoeditsrc = preg_replace('/\.sql$/', '.key.sql', $pathoffiletoeditsrc);
	$pathoffiletoedittarget = preg_replace('/\.sql$/', '.key.sql', $pathoffiletoedittarget);
	$pathoffiletoedittarget = preg_replace('/\.sql.new$/', '.key.sql.new', $pathoffiletoedittarget);

	$contentsql = file_get_contents(dol_osencode($pathoffiletoeditsrc), 'r');

	$i = 0;
	$texttoinsert = '-- BEGIN MODULEBUILDER INDEXES'."\n";
	if (count($object->fields)) {
		foreach ($object->fields as $key => $val) {
			$i++;
			if (!empty($val['index'])) {
				$texttoinsert .= "ALTER TABLE llx_".strtolower($module).'_'.strtolower($objectname)." ADD INDEX idx_".strtolower($module).'_'.strtolower($objectname)."_".$key." (".$key.");";
				$texttoinsert .= "\n";
			}
			if (!empty($val['foreignkey'])) {
				$tmp = explode('.', $val['foreignkey']);
				if (!empty($tmp[0]) && !empty($tmp[1])) {
					$texttoinsert .= "ALTER TABLE llx_".strtolower($module).'_'.strtolower($objectname)." ADD CONSTRAINT llx_".strtolower($module).'_'.strtolower($objectname)."_".$key." FOREIGN KEY (".$key.") REFERENCES llx_".preg_replace('/^llx_/', '', $tmp[0])."(".$tmp[1].");";
					$texttoinsert .= "\n";
				}
			}
		}
	}
	$texttoinsert .= '-- END MODULEBUILDER INDEXES';

	$contentsql = preg_replace('/-- BEGIN MODULEBUILDER INDEXES.*END MODULEBUILDER INDEXES/ims', $texttoinsert, $contentsql);

	dol_mkdir(dirname($pathoffiletoedittarget));

	$result2 = file_put_contents($pathoffiletoedittarget, $contentsql);
	if ($result2) {
		dolChmod($pathoffiletoedittarget, $newmask);
	} else {
		$error++;
		setEventMessages($langs->trans("ErrorFailToCreateFile", $pathoffiletoedittarget), null, 'errors');
	}

	return $error ? -1 : 1;
}

/**
 * Get list of existing objects from directory
 *
 * @param	string      $destdir		Directory
 * @return 	array|int                    <=0 if KO, array if OK
 */
function dolGetListOfObjectClasses($destdir)
{
	$objects = array();
	$listofobject = dol_dir_list($destdir.'/class', 'files', 0, '\.class\.php$');
	foreach ($listofobject as $fileobj) {
		if (preg_match('/^api_/', $fileobj['name'])) {
			continue;
		}
		if (preg_match('/^actions_/', $fileobj['name'])) {
			continue;
		}

		$tmpcontent = file_get_contents($fileobj['fullname']);
		$reg = array();
		if (preg_match('/class\s+([^\s]*)\s+extends\s+CommonObject/ims', $tmpcontent, $reg)) {
			$objectnameloop = $reg[1];
			$objects[$fileobj['fullname']] = $objectnameloop;
		}
	}
	if (count($objects)>0) {
		return $objects;
	}

	return -1;
}

/**
 * Delete all permissions
 *
 * @param string         $file         file with path
 * @return void
 */
function deletePerms($file)
{
	$start = "/* BEGIN MODULEBUILDER PERMISSIONS */";
	$end = "/* END MODULEBUILDER PERMISSIONS */";
	$i = 1;
	$array = array();
	$lines = file($file);
	// Search for start and end lines
	foreach ($lines as $i => $line) {
		if (strpos($line, $start) !== false) {
			$start_line = $i + 1;

			// Copy lines until the end on array
			while (($line = $lines[++$i]) !== false) {
				if (strpos($line, $end) !== false) {
					$end_line = $i + 1;
					break;
				}
				$array[] = $line;
			}
			break;
		}
	}
	$allContent = implode("", $array);
	dolReplaceInFile($file, array($allContent => ''));
}

/**
 *  Compare two value
 * @param int|string  $a value 1
 * @param int|string  $b value 2
 * @return int      less 0 if str1 is less than str2; > 0 if str1 is greater than str2, and 0 if they are equal.
*/
function compareFirstValue($a, $b)
{
	return strcmp($a[0], $b[0]);
}
/**
 * Rewriting all permissions after any actions
 * @param string      $file            filename or path
 * @param array       $permissions     permissions existing in file
 * @param int|null    $key             key for permission needed
 * @param array|null  $right           $right to update or add
 * @param string|null $objectname      name of object
 * @param string|null $module          name of module
 * @param int         $action          0 for delete, 1 for add, 2 for update, -1 when delete object completly, -2 for generate rights after add
 * @return int                         1 if OK,-1 if KO
 */
function reWriteAllPermissions($file, $permissions, $key, $right, $objectname, $module, $action)
{
	$error = 0;
	$rights = array();
	if ($action == 0) {
		// delete right from permissions array
		array_splice($permissions, array_search($permissions[$key], $permissions), 1);
	} elseif ($action == 1) {
		array_push($permissions, $right);
	} elseif ($action == 2 && !empty($right)) {
		// update right from permissions array
		array_splice($permissions, array_search($permissions[$key], $permissions), 1, $right);
	} elseif ($action == -1 && !empty($objectname)) {
		// when delete object
		$key = null;
		$right = null;
		foreach ($permissions as $perms) {
			if ($perms[4] === strtolower($objectname)) {
				array_splice($permissions, array_search($perms, $permissions), 1);
			}
		}
	} elseif ($action == -2 && !empty($objectname) && !empty($module)) {
		$key= null;
		$right = null;
		$objectOfRights = array();
		//check if object already declared in rights file
		foreach ($permissions as $right) {
			$objectOfRights[]= $right[4];
		}
		if (in_array(strtolower($objectname), $objectOfRights)) {
			$error++;
		} else {
			$permsToadd = array();
			$perms = array(
				'read' => 'Read '.$objectname.' object of '.ucfirst($module),
				'write' => 'Create/Update '.$objectname.' object of '.ucfirst($module),
				'delete' => 'Delete '.$objectname.' object of '.ucfirst($module)
			);
			$i = 0;
			foreach ($perms as $index => $value) {
				$permsToadd[$i][0] = '';
				$permsToadd[$i][1] = $value;
				$permsToadd[$i][4] = strtolower($objectname);
				$permsToadd[$i][5] = $index;
				array_push($permissions, $permsToadd[$i]);
				$i++;
			}
		}
	} else {
		$error++;
	}
	if (!$error) {
		// prepare permissions array
		$count_perms = count($permissions);
		for ($i = 0;$i<$count_perms;$i++) {
			$permissions[$i][0] = "\$this->rights[\$r][0] = \$this->numero . sprintf('%02d', \$r + 1)";
			$permissions[$i][1] = "\$this->rights[\$r][1] = '".$permissions[$i][1]."'";
			$permissions[$i][4] = "\$this->rights[\$r][4] = '".$permissions[$i][4]."'";
			$permissions[$i][5] = "\$this->rights[\$r][5] = '".$permissions[$i][5]."';\n\t\t";
		}
			// for group permissions by object
			$perms_grouped = array();
		foreach ($permissions as $perms) {
			$object = $perms[4];
			if (!isset($perms_grouped[$object])) {
				$perms_grouped[$object] = [];
			}
			$perms_grouped[$object][] = $perms;
		}
		//$perms_grouped = array_values($perms_grouped);
		$permissions = $perms_grouped;


		// parcourir les objets
		$o=0;
		foreach ($permissions as &$object) {
			// récupérer la permission de l'objet
			$p = 1;
			foreach ($object as &$obj) {
				if (str_contains($obj[5], 'read')) {
					$obj[0] = "\$this->rights[\$r][0] = \$this->numero . sprintf('%02d', (".$o." * 10) + 0 + 1)";
				} elseif (str_contains($obj[5], 'write')) {
					$obj[0] = "\$this->rights[\$r][0] = \$this->numero . sprintf('%02d', (".$o." * 10) + 1 + 1)";
				} elseif (str_contains($obj[5], 'delete')) {
					$obj[0] = "\$this->rights[\$r][0] = \$this->numero . sprintf('%02d', (".$o." * 10) + 2 + 1)";
				} else {
					$obj[0] = "\$this->rights[\$r][0] = \$this->numero . sprintf('%02d', (".$o." * 10) + ".$p." + 1)";
					$p++;
				}
			}
			usort($object, 'compareFirstValue');
			$o++;
		}

		//convert to string
		foreach ($permissions as $perms) {
			foreach ($perms as $per) {
				$rights[] = implode(";\n\t\t", $per);
				$rights[] = "\$r++;\n\t\t";
			}
		}
		$rights_str = implode("", $rights);
		// delete all permission from file
		deletePerms($file);
		// rewrite all permission again
		dolReplaceInFile($file, array('/* BEGIN MODULEBUILDER PERMISSIONS */' => '/* BEGIN MODULEBUILDER PERMISSIONS */'."\n\t\t".$rights_str));
		return 1;
	} else {
		return -1;
	}
}

/**
 * Write all properties of the object in AsciiDoc format
 * @param  string   $file           path of the class
 * @param  string   $objectname     name of the objectClass
 * @param  string   $destfile       file where write table of properties
 * @return int                      1 if OK, -1 if KO
 */
function writePropsInAsciiDoc($file, $objectname, $destfile)
{

	// stock all properties in array
	$attributesUnique = array ('label', 'type', 'arrayofkeyval', 'notnull', 'default', 'index', 'foreignkey', 'position', 'enabled', 'visible', 'noteditable', 'alwayseditable', 'searchall', 'isameasure', 'css','cssview','csslist', 'help', 'showoncombobox', 'validate','comment','picto' );

	$start = "public \$fields=array(";
	$end = ");";
	$i = 1;
	$keys = array();
	$lines = file($file);
	// Search for start and end lines
	foreach ($lines as $i => $line) {
		if (strpos($line, $start) !== false) {
			// Copy lines until the end on array
			while (($line = $lines[++$i]) !== false) {
				if (strpos($line, $end) !== false) {
					break;
				}
				$keys[] = $line;
			}
			break;
		}
	}
	// write the begin of table with specifics options
	$table = "== DATA SPECIFICATIONS\n";
	$table .= "=== Table of fields with properties for object *$objectname* : \n";
	$table .= "[options='header',grid=rows,frame=topbot,width=100%,caption=Organisation]\n";
	$table .= "|===\n";
	$table .= "|code";
	// write all properties in the header of the table
	foreach ($attributesUnique as $attUnique) {
		$table .= "|".$attUnique;
	}
	$table .="\n";
	foreach ($keys as $string) {
		$string = trim($string, "'");
		$string = rtrim($string, ",");
		$array = eval("return [$string];");

		// check if is array after cleaning string
		if (!is_array($array)) {
			return -1;
		}

		$field = array_keys($array);
		$values = array_values($array)[0];

		// check each field has all properties and add it if missed
		foreach ($attributesUnique as $attUnique) {
			if (!array_key_exists($attUnique, $values)) {
				$values[$attUnique] = '';
			}
		}

		$table .= "|*" . $field[0] . "*|";
		$table .= implode("|", $values) . "\n";
	}
	// end table
	$table .= "|===\n";
	$table .= "__ end table for object $objectname\n";
	//write in file
	$writeInFile = dolReplaceInFile($destfile, array('== DATA SPECIFICATIONS' => $table));
	if ($writeInFile<0) {
		return -1;
	}
	return 1;
}

/**
 * Delete property from documentation if we delete object
 * @param  string  $file         file or path
 * @param  string  $objectname   name of object wants to deleted
 * @return void
 */
function deletePropsFromDoc($file, $objectname)
{

	$start = "== Table of fields and their properties for object *".ucfirst($objectname)."* : ";
	$end = "__ end table for object ".ucfirst($objectname);
	$str = file_get_contents($file);
	$search = '/' . preg_quote($start, '/') . '(.*?)' . preg_quote($end, '/') . '/s';
	$new_contents = preg_replace($search, '', $str);
	file_put_contents($file, $new_contents);
}

/**
 * Search a string and return all lines needed from file
 * @param  string  $file    file for searching
 * @param  string  $start   start line if exist
 * @param  string  $end     end line if exist
 * @return string           return the content needed
 */
function getFromFile($file, $start, $end)
{
	$i = 1;
	$keys = array();
	$lines = file($file);
	// Search for start and end lines
	foreach ($lines as $i => $line) {
		if (strpos($line, $start) !== false) {
			// Copy lines until the end on array
			while (($line = $lines[++$i]) !== false) {
				if (strpos($line, $end) !== false) {
					break;
				}
				$keys[] = $line;
			}
			break;
		}
	}
	$content = implode("", $keys);
	return $content;
}

/**
 * Write all permissions of each object in AsciiDoc format
 * @param  string   $file           path of the class
 * @param  string   $destfile       file where write table of permissions
 * @return int                      1 if OK, -1 if KO
 */
function writePermsInAsciiDoc($file, $destfile)
{
	global $langs;
	//search and get all permssion in stirng
	$start = '/* BEGIN MODULEBUILDER PERMISSIONS */';
	$end = '/* END MODULEBUILDER PERMISSIONS */';
	$content = getFromFile($file, $start, $end);
	if (empty($content)) {
		return -1;
	}
	//prepare table
	$string = "[options='header',grid=rows,width=60%,caption=Organisation]\n";
	$string .= "|===\n";
	// header for table
	$header = array($langs->trans('Objects'),$langs->trans('Permission'));
	foreach ($header as $h) {
		$string .= "|".$h;
	}
	$string .= "\n";
	//content table
	$array = explode(";", $content);
	$permissions = array_filter($array);
	// delete  occurrences "$r++" and ID
	$permissions = str_replace('$r++', 1, $permissions);

	$permsN = array();
	foreach ($permissions as $i => $element) {
		if ($element == 1) {
			unset($permissions[$i]);
		}
		if (str_contains($element, '$this->numero')) {
			unset($permissions[$i]);
		}
		if (str_contains($element, '$this->rights[$r][5]')) {
			unset($permissions[$i]);
		}
	}
	// cleaning the string on each element
	foreach ($permissions as $key => $element) {
		$element = str_replace(" '", '', $element);
		$element = trim($element, "'");
		$permsN[] = substr($element, strpos($element, "=")+1);
	}
	array_pop($permsN);

	// Group permissions by Object and add it to string
	$final_array = [];
	$index = 0;
	while ($index < count($permsN)) {
		$temp_array = [$permsN[$index], $permsN[$index + 1]];
		$final_array[] = $temp_array;
		$index += 2;
	}

	$result = array();
	foreach ($final_array as $subarray) {
		// found object
		$key = $subarray[1];
		// add sub array to object
		$result[$key][] = $subarray;
	}
	foreach ($result as $i => $pems) {
		$string .= "|*".$i."*|";
		foreach ($pems as $tab) {
			$string .= $tab[0]." , ";
		}
		$string .= "\n";
	}
	// end table
	$string .= "\n|===\n";
	$write = dolReplaceInFile($destfile, array('__DATA_PERMISSIONS__'=> $string));
	if ($write<0) {
		return -1;
	}
	return 1;
}

/**
 * Add Object in ModuleApi File
 * @param  string $file           path of file
 * @param  array  $objects        array of objects in the module
 * @param  string $modulename     name of module
 * @return int                    1 if OK, -1 if KO
 */
function addObjectsToApiFile($file, $objects, $modulename)
{
	if (!file_exists($file)) {
		return -1;
	}
	$content = file($file);
	$includeClass = "dol_include_once('/mymodule/class/myobject.class.php');";
	$props = "public \$myobject;";
	$varcomented = "@var MyObject \$myobject {@type MyObject}";
	$constructObj = "\$this->myobject = new MyObject(\$this->db);";

	// add properties and declare them in consturctor
	foreach ($content as $lineNumber => &$lineContent) {
		if (strpos($lineContent, $varcomented) !== false) {
			$lineContent = '';
			foreach ($objects as $object) {
				$lineContent .= "\t * @var ".$object." \$".strtolower($object)." {@type ".$object."}". PHP_EOL;
			}
			//var_dump($lineContent);exit;
		}
		if (strpos($lineContent, $props) !== false) {
			$lineContent = '';
			foreach ($objects as $object) {
				$lineContent .= "\tpublic \$".strtolower($object).";". PHP_EOL;
			}
		}
		if (strpos($lineContent, $constructObj) !== false) {
			$lineContent = '';
			foreach ($objects as $object) {
				$lineContent .= "\t\t\$this->".strtolower($object)." = new ".$object."(\$this->db);". PHP_EOL;
			}
		}
		if (strpos($lineContent, $includeClass) !== false) {
			$lineContent = '';
			foreach ($objects as $object) {
				$lineContent .= "dol_include_once('/".strtolower($modulename)."/class/".strtolower($object).".class.php');". PHP_EOL;
			}
		}
	}
	$allContent = implode("", $content);
	file_put_contents($file, $allContent);

	//add methods for each object
	$allContent = getFromFile($file, '/*begin methods CRUD*/', '/*end methods CRUD*/');
	foreach ($objects as $object) {
		$contentReplaced =str_replace(["myobject","MyObject"], [strtolower($object),$object], $allContent);
		dolReplaceInFile($file, array('/*end methods CRUD*/' => '/*CRUD FOR '.strtoupper($object).'*/'."\n".$contentReplaced."\n\t".'/*END CRUD FOR '.strtoupper($object).'*/'."\n\t".'/*end methods CRUD*/'));
	}
	dolReplaceInFile($file, array($allContent => '','MyModule' => ucfirst($modulename)));
	return 1;
}

/**
 * Remove Object variables and methods from API_Module File
 * @param string   $file         file api module
 * @param string   $objectname   name of object whant to remove
 * @param string   $modulename   name of module
 * @return int                    1 if OK, -1 if KO
 */
function removeObjectFromApiFile($file, $objectname, $modulename)
{
	$begin = '/*CRUD FOR '.strtoupper($objectname).'*/';
	$end = '/*END CRUD FOR '.strtoupper($objectname).'*/';
	$includeClass = "dol_include_once('/".strtolower($modulename)."/class/".strtolower($objectname).".class.php');";
	$varcomentedDel = "\t * @var ".$objectname." \$".strtolower($objectname)." {@type ".$objectname."}";
	$propsDel = "\tpublic \$".strtolower($objectname).";";
	$constructObjDel = "\t\t\$this->".strtolower($objectname)." = new ".$objectname."(\$this->db);";

	if (!file_exists($file)) {
		return -1;
	}
	$content = file($file);
	// for delete property and the initialization from the construct
	foreach ($content as $lineNumber => &$lineContent) {
		if (strpos($lineContent, $includeClass) !== false) {
			$lineContent = '';
		}
		if (strpos($lineContent, $varcomentedDel) !== false) {
			$lineContent = '';
		}
		if (strpos($lineContent, $propsDel) !== false) {
			$lineContent = '';
		}
		if (strpos($lineContent, $constructObjDel) !== false) {
			$lineContent = '';
		}
	}
	$allContent = implode("", $content);
	file_put_contents($file, $allContent);
	// for delete methods of object
	$allContent = getFromFile($file, $begin, $end);
	$check = dolReplaceInFile($file, array($allContent => ''));
	if ($check) {
		dolReplaceInFile($file, array($begin => '', $end => ''));
	}
	return 1;
}


/**
 * @param    string         $file       path of filename
 * @param    mixed          $menus      all menus for module
 * @param    mixed|null     $menuWantTo  menu get for do actions
 * @param    int|null       $key        key for the concerned menu
 * @param    int            $action     for specify what action (0 = delete, 1 = add, 2 = update, -1 = when delete object)
 * @return   int            1 if OK, -1 if KO
 */
function reWriteAllMenus($file, $menus, $menuWantTo, $key, $action)
{
	$errors =0;
	$counter = 0;
	if (!file_exists($file)) {
		return -1;
	}
	if ($action == 0 && !empty($key)) {
		// delete menu manuelly
		array_splice($menus, array_search($menus[$key], $menus), 1);
	} elseif ($action == 1) {
		// add menu manualy
		array_push($menus, $menuWantTo);
	} elseif ($action == 2 && !empty($key) && !empty($menuWantTo)) {
		// update right from permissions array

		// check if the values already exists
		foreach ($menus as $index => $menu) {
			if ($index !== $key) {
				if ($menu['type'] === $menuWantTo['type']) {
					if (strcasecmp(str_replace(' ', '', $menu['titre']), str_replace(' ', '', $menuWantTo['titre'])) === 0) {
						$counter++;
					}
					if (strcasecmp(str_replace(' ', '', $menu['url']), str_replace(' ', '', $menuWantTo['url'])) === 0) {
						$counter++;
					}
				}
			}
		}
		if (!$counter) {
			$menus[$key] = $menuWantTo;
		} else {
			$errors++;
		}
	} elseif ($action == -1 && !empty($menuWantTo)) {
		// delete menus when delete Object
		foreach ($menus as $index => $menu) {
			if ((strpos(strtolower($menu['fk_menu']), strtolower($menuWantTo)) !== false) || (strpos(strtolower($menu['leftmenu']), strtolower($menuWantTo)) !== false)) {
				array_splice($menus, array_search($menu, $menus), 1);
			}
		}
	} else {
		$errors++;
	}
	if (!$errors) {
		// delete All LEFT Menus
		$beginMenu = '/* BEGIN MODULEBUILDER LEFTMENU MYOBJECT */';
		$endMenu = '/* END MODULEBUILDER LEFTMENU MYOBJECT */';
		$allMenus = getFromFile($file, $beginMenu, $endMenu);
		dolReplaceInFile($file, array($allMenus => ''));

		//prepare each menu and stock them in string
		$str_menu = "";
		foreach ($menus as $index =>$menu) {
			$menu['position'] = "1000 + \$r";
			if ($menu['type'] === 'left') {
				$start = "\t\t".'/* LEFTMENU '.strtoupper($menu['titre']).' */';
				$end   = "\t\t".'/* END LEFTMENU '.strtoupper($menu['titre']).' */';
				$val_actuel = $menu;
				$next_val = $menus[$index + 1];
				$str_menu .= $start."\n";
				$str_menu.= "\t\t\$this->menu[\$r++]=array(\n";
				$str_menu.= "\t\t\t 'fk_menu' =>'".$menu['fk_menu']."',\n";
				$str_menu.= "\t\t\t 'type' =>'".$menu['type']."',\n";
				$str_menu.= "\t\t\t 'titre' =>'".$menu['titre']."',\n";
				$str_menu.= "\t\t\t 'mainmenu' =>'".$menu['mainmenu']."',\n";
				$str_menu.= "\t\t\t 'leftmenu' =>'".$menu['leftmenu']."',\n";
				$str_menu.= "\t\t\t 'url' =>'".$menu['url']."',\n";
				$str_menu.= "\t\t\t 'langs' =>'".$menu['langs']."',\n";
				$str_menu.= "\t\t\t 'position' =>".$menu['position'].",\n";
				$str_menu.= "\t\t\t 'enabled' =>'".$menu['enabled']."',\n";
				$str_menu.= "\t\t\t 'perms' =>'".$menu['perms']."',\n";
				$str_menu.= "\t\t\t 'target' =>'".$menu['target']."',\n";
				$str_menu.= "\t\t\t 'user' =>".$menu['user'].",\n";
				$str_menu.= "\t\t);\n";

				if ($val_actuel['leftmenu'] !== $next_val['leftmenu']) {
					$str_menu .= $end."\n";
				}
			}
		}

		dolReplaceInFile($file, array($beginMenu => $beginMenu."\n".$str_menu."\n"));
		return 1;
	}return -1;
}
