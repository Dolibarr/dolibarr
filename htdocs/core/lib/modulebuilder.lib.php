<?php
/* Copyright (C) 2009-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *  @param	array{}|array{name:string,key:string,type:string,label:string,picot?:string,enabled:int<0,1>,notnull:int<0,1>,position:int,visible:int,noteditable?:int<0,1>,alwayseditable?:int<0,1>,default?:string,index?:int,foreignkey?:string,searchall?:int,isameasure?:int<0,1>,css?:string,cssview?:string,csslist?:string,help?:string,showoncombobox?:int<0,1>,disabled?:int<0,1>,autofocusoncreate?:int<0,1>,arrayofkeyval?:array<string,string>,validate?:int<0,1>,comment?:string}	$addfieldentry	Array of 1 field entry to add
 *  @param	string		$delfieldentry	Id of field to remove
 * 	@return	int<-7,-1>|CommonObject		Return integer <=0 if KO, Object if OK
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
		dol_print_error(null, 'Bad parameter addfieldentry with a property arrayofkeyval defined but that is not an array.');
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
		'@phan-var-force CommonObject $object';

		// Backup old file
		dol_copy($pathoffiletoedittarget, $pathoffiletoedittarget.'.back', $newmask, 1);

		// Edit class files
		$contentclass = file_get_contents(dol_osencode($pathoffiletoeditsrc));

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
				$texttoinsert .= "\t\t".'"'.$key.'" => array(';
				$texttoinsert .= '"type"=>"'.dol_escape_php($val['type']).'",';
				$texttoinsert .= ' "label"=>"'.dol_escape_php($val['label']).'",';
				if (!empty($val['picto'])) {
					$texttoinsert .= ' "picto"=>"'.dol_escape_php($val['picto']).'",';
				}
				$texttoinsert .= ' "enabled"=>"'.($val['enabled'] !== '' ? dol_escape_php($val['enabled']) : 1).'",';
				$texttoinsert .= " 'position'=>".($val['position'] !== '' ? (int) $val['position'] : 50).",";
				$texttoinsert .= " 'notnull'=>".(empty($val['notnull']) ? 0 : (int) $val['notnull']).",";
				$texttoinsert .= ' "visible"=>"'.($val['visible'] !== '' ? dol_escape_js($val['visible']) : -1).'",';
				if (!empty($val['noteditable'])) {
					$texttoinsert .= ' "noteditable"=>"'.dol_escape_php($val['noteditable']).'",';
				}
				if (!empty($val['alwayseditable'])) {
					$texttoinsert .= ' "alwayseditable"=>"'.dol_escape_php($val['alwayseditable']).'",';
				}
				if (array_key_exists('default', $val) && (!empty($val['default']) || $val['default'] === '0')) {
					$texttoinsert .= ' "default"=>"'.dol_escape_php($val['default']).'",';
				}
				if (!empty($val['index'])) {
					$texttoinsert .= ' "index"=>"'.(int) $val['index'].'",';
				}
				if (!empty($val['foreignkey'])) {
					$texttoinsert .= ' "foreignkey"=>"'.(int) $val['foreignkey'].'",';
				}
				if (!empty($val['searchall'])) {
					$texttoinsert .= ' "searchall"=>"'.(int) $val['searchall'].'",';
				}
				if (!empty($val['isameasure'])) {
					$texttoinsert .= ' "isameasure"=>"'.(int) $val['isameasure'].'",';
				}
				if (!empty($val['css'])) {
					$texttoinsert .= ' "css"=>"'.dol_escape_php($val['css']).'",';
				}
				if (!empty($val['cssview'])) {
					$texttoinsert .= ' "cssview"=>"'.dol_escape_php($val['cssview']).'",';
				}
				if (!empty($val['csslist'])) {
					$texttoinsert .= ' "csslist"=>"'.dol_escape_php($val['csslist']).'",';
				}
				if (!empty($val['help'])) {
					$texttoinsert .= ' "help"=>"'.dol_escape_php($val['help']).'",';
				}
				if (!empty($val['showoncombobox'])) {
					$texttoinsert .= ' "showoncombobox"=>"'.(int) $val['showoncombobox'].'",';
				}
				if (!empty($val['disabled'])) {
					$texttoinsert .= ' "disabled"=>"'.(int) $val['disabled'].'",';
				}
				if (!empty($val['autofocusoncreate'])) {
					$texttoinsert .= ' "autofocusoncreate"=>"'.(int) $val['autofocusoncreate'].'",';
				}
				if (!empty($val['arrayofkeyval'])) {
					$texttoinsert .= ' "arrayofkeyval"=>array(';
					$i = 0;
					foreach ($val['arrayofkeyval'] as $key2 => $val2) {
						if ($i) {
							$texttoinsert .= ", ";
						}
						$texttoinsert .= '"'.dol_escape_php($key2).'" => "'.dol_escape_php($val2).'"';
						$i++;
					}
					$texttoinsert .= '),';
				}
				if (!empty($val['validate'])) {
					$texttoinsert .= ' "validate"=>"'.(int) $val['validate'].'",';
				}
				if (!empty($val['comment'])) {
					$texttoinsert .= ' "comment"=>"'.dol_escape_php($val['comment']).'"';
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
 * 	@return	int							Return integer <=0 if KO, >0 if OK
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

	$contentsql = file_get_contents(dol_osencode($pathoffiletoeditsrc));

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
			} elseif ($type == 'mail') {
				$type = 'varchar(128)';
			} elseif ($type == 'phone') {
				$type = 'varchar(20)';
			} elseif ($type == 'ip') {
				$type = 'varchar(32)';
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

	$contentsql = file_get_contents(dol_osencode($pathoffiletoeditsrc));

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
 * Get list of existing objects from a directory
 *
 * @param	string      $destdir		Directory
 * @return 	array|int                   Return integer <=0 if KO, array if OK
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
	if (count($objects) > 0) {
		return $objects;
	}

	return -1;
}

/**
 * Function to check if comment BEGIN and END exists in modMyModule class
 *
 * @param  string  $file    	Filename or path
 * @param  int     $number   	0 = For Menus, 1 = For permissions, 2 = For Dictionaries
 * @return int     				1 if OK , -1 if KO
 */
function checkExistComment($file, $number)
{
	if (!file_exists($file)) {
		return -1;
	}

	$content = file_get_contents($file);
	if ($number === 0) {
		$ret = 0;
		if (strpos($content, '/* BEGIN MODULEBUILDER TOPMENU MYOBJECT */') !== false
			|| strpos($content, '/* BEGIN MODULEBUILDER TOPMENU */') !== false) {
			$ret++;
		}
		if (strpos($content, '/* END MODULEBUILDER TOPMENU MYOBJECT */') !== false
			|| strpos($content, '/* END MODULEBUILDER TOPMENU */') !== false) {
			$ret++;
		}
		if (strpos($content, '/* BEGIN MODULEBUILDER LEFTMENU MYOBJECT */') !== false) {
			$ret++;
		}
		if (strpos($content, '/* END MODULEBUILDER LEFTMENU MYOBJECT */') !== false) {
			$ret++;
		}

		if ($ret == 4) {
			return 1;
		}
	} elseif ($number === 1) {
		if (strpos($content, '/* BEGIN MODULEBUILDER PERMISSIONS */') !== false && strpos($content, '/* END MODULEBUILDER PERMISSIONS */') !== false) {
			return 1;
		}
	} elseif ($number == 2) {
		if (strpos($content, '/* BEGIN MODULEBUILDER DICTIONARIES */') !== false && strpos($content, '/* END MODULEBUILDER DICTIONARIES */') !== false) {
			return 1;
		}
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
 * @param array<int,string[]> $permissions permissions existing in file
 * @param int|null    $key             key for permission needed
 * @param array{0:string,1:string}|null  $right           $right to update or add
 * @param string|null $objectname      name of object
 * @param string|null $module          name of module
 * @param int<-2,2>   $action          0 for delete, 1 for add, 2 for update, -1 when delete object completely, -2 for generate rights after add
 * @return int<-1,1>                   1 if OK,-1 if KO
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
		$key = null;
		$right = null;
		$objectOfRights = array();
		//check if object already declared in rights file
		foreach ($permissions as $right) {
			$objectOfRights[] = $right[4];
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
	'@phan-var-force array<int,string[]> $permissions';
	if (!$error) {
		// prepare permissions array
		$count_perms = count($permissions);
		foreach (array_keys($permissions) as $i) {
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


		// parcourir les objects
		$o = 0;
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
		// delete all permissions from file
		deletePerms($file);
		// rewrite all permissions again
		dolReplaceInFile($file, array('/* BEGIN MODULEBUILDER PERMISSIONS */' => '/* BEGIN MODULEBUILDER PERMISSIONS */'."\n\t\t".$rights_str));
		return 1;
	} else {
		return -1;
	}
}

/**
 * Converts a formatted properties string into an associative array.
 *
 * @param string $string The formatted properties string.
 * @return array<string,bool|int|float|string|mixed[]> The resulting associative array.
 */
function parsePropertyString($string)
{
	$string = str_replace("'", '', $string);

	// Uses a regular expression to capture keys and values
	preg_match_all('/\s*([^\s=>]+)\s*=>\s*([^,]+),?/', $string, $matches, PREG_SET_ORDER);
	$propertyArray = [];

	foreach ($matches as $match) {
		$key = trim($match[1]);
		$value = trim($match[2]);

		if (strpos($value, 'array(') === 0) {
			$nestedArray = substr($value, 6);
			$nestedArray = parsePropertyString($nestedArray);
			$value = $nestedArray;
		} elseif (strpos($value, '"Id")') !== false) {
			$value = str_replace(')', '', $value);
		} else {
			if (is_numeric($value)) {
				if (strpos($value, '.') !== false) {
					$value = (float) $value;
				} else {
					$value = (int) $value;
				}
			} else {
				if ($value === 'true') {
					$value = true;
				} elseif ($value === 'false') {
					$value = false;
				}
			}
		}
		$propertyArray[$key] = $value;
	}

	return $propertyArray;
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
	$attributesUnique = array('type','label', 'enabled', 'position', 'notnull', 'visible', 'noteditable', 'index', 'default' , 'foreignkey', 'arrayofkeyval', 'alwayseditable','validate', 'searchall','comment', 'isameasure', 'css', 'cssview','csslist', 'help', 'showoncombobox','picto' );

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
	$table .= "\n";
	$valuesModif = array();
	foreach ($keys as $string) {
		$string = trim($string, "'");
		$string = rtrim($string, ",");

		$array = parsePropertyString($string);

		// Iterate through the array to merge all key to one array
		$code = '';
		foreach ($array as $key => $value) {
			if (is_array($value)) {
				$code = $key;
				continue;
			} else {
				$array[$code][$key] = $value;
				unset($array[$key]);
			}
		}
		// check if is array after parsing the string
		if (!is_array($array)) {
			return -1;
		}
		$field = array_keys($array);
		if ($field[0] === '') {
			$field[0] = 'label';
		}
		$values = array_values($array)[0];

		// check each field has all properties and add it if missed
		foreach ($attributesUnique as $attUnique) {
			if ($attUnique == 'type' && $field[0] === 'label') {
				$values[$attUnique] = 'varchar(255)';
			}
			if (!array_key_exists($attUnique, $values)) {
				$valuesModif[$attUnique] = '';
			} else {
				$valuesModif[$attUnique] = $values[$attUnique];
			}
		}
		$table .= "|*" . $field[0] . "*|";
		$table .= implode("|", $valuesModif) . "\n";
	}

	// end table
	$table .= "|===\n";
	$table .= "__ end table for object $objectname\n";

	//write in file @phan-suppress-next-line PhanPluginSuspiciousParamPosition
	$writeInFile = dolReplaceInFile($destfile, array('== DATA SPECIFICATIONS' => $table));
	if ($writeInFile < 0) {
		return -1;
	}
	return 1;
}


/**
 * Delete property and permissions from documentation ascii file if we delete an object
 *
 * @param  string  $file         file or path
 * @param  string  $objectname   name of object wants to deleted
 * @return void
 */
function deletePropsAndPermsFromDoc($file, $objectname)
{
	if (dol_is_file($file)) {
		$start = "== Table of fields and their properties for object *".ucfirst($objectname)."* : ";
		$end = "__ end table for object ".ucfirst($objectname);

		$str = file_get_contents($file);

		$search = '/' . preg_quote($start, '/') . '(.*?)' . preg_quote($end, '/') . '/s';
		$new_contents = preg_replace($search, '', $str);
		file_put_contents($file, $new_contents);

		//perms If Exist
		$perms = "|*".strtolower($objectname)."*|";
		$search_pattern_perms = '/' . preg_quote($perms, '/') . '.*?\n/';
		$new_contents = preg_replace($search_pattern_perms, '', $new_contents);
		file_put_contents($file, $new_contents);
	}
}



/**
 * Search a string and return all lines needed from file. Does not include line $start nor $end
 *
 * @param  	string  $file    		file for searching
 * @param  	string  $start   		start line if exist
 * @param  	string  $end     		end line if exist
 * @param	string	$excludestart 	Ignore if start line is $excludestart
 * @param	int  	$includese		Include start and end line
 * @return 	string           		Return the lines between first line with $start and $end. "" if not found.
 */
function getFromFile($file, $start, $end, $excludestart = '', $includese = 0)
{
	$keys = array();

	//$lines = file(dol_osencode($file));
	$fhandle = fopen(dol_osencode($file), 'r');
	if ($fhandle) {
		// Search for start and end lines
		//foreach ($lines as $i => $line) {
		while ($line = fgets($fhandle)) {
			if (strpos($line, $start) !== false && (empty($excludestart) || strpos($line, $excludestart) === false)) {
				if ($includese) {
					$keys[] = $line;
				}
				// Copy lines until we reach the end
				while (($line = fgets($fhandle)) !== false) {
					if (strpos($line, $end) !== false) {
						if ($includese) {
							$keys[] = $line;
						}
						break;
					}
					$keys[] = $line;
				}
				break;
			}
		}
	}
	fclose($fhandle);

	$content = implode("", $keys);
	return $content;
}

/**
 * Write all permissions of each object in AsciiDoc format
 * @param  string   $file           path of the class
 * @param  string   $destfile       file where write table of permissions
 * @return int<-1,1>				1 if OK, -1 if KO
 */
function writePermsInAsciiDoc($file, $destfile)
{
	global $langs;
	//search and get all permissions in string
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
	$permissions = str_replace('$r++', '1', $permissions);

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
		$permsN[] = substr($element, strpos($element, "=") + 1);
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
	// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
	$write = dolReplaceInFile($destfile, array('__DATA_PERMISSIONS__' => $string));
	if ($write < 0) {
		return -1;
	}
	return 1;
}

/**
 * Add Object in ModuleApi File
 *
 * @param	string	$srcfile		Source file to use as example
 * @param  	string 	$file           Path of modified file
 * @param  	string[]	$objects	Array of objects in the module
 * @param  	string 	$modulename     Name of module
 * @return 	int<-1,1>              	Return 1 if OK, -1 if KO
 */
function addObjectsToApiFile($srcfile, $file, $objects, $modulename)
{
	global $langs, $user;

	if (!file_exists($file)) {
		return -1;
	}

	$now = dol_now();
	$content = file($file);	// $content is an array

	$includeClass = "dol_include_once\(\'\/\w+\/class\/\w+\.class\.php\'\);";
	$props = 'public\s+\$\w+;';
	$varcommented = '@var\s+\w+\s+\$\w+\s+{@type\s+\w+}';
	$constructObj = '\$this->\w+\s+=\s+new\s+\w+\(\$this->db\);';

	// add properties and declare them in constructor
	foreach ($content as $lineNumber => &$lineContent) {
		if (preg_match('/'.$varcommented.'/', $lineContent)) {
			$lineContent = '';
			foreach ($objects as $objectname) {
				$lineContent .= "\t * @var ".$objectname." \$".strtolower($objectname)." {@type ".$objectname."}". PHP_EOL;
			}
			//var_dump($lineContent);exit;
		} elseif (preg_match('/'.$props.'/', $lineContent)) {
			$lineContent = '';
			foreach ($objects as $objectname) {
				$lineContent .= "\tpublic \$".strtolower($objectname).";". PHP_EOL;
			}
		} elseif (preg_match('/'.$constructObj.'/', $lineContent)) {
			$lineContent = '';
			foreach ($objects as $objectname) {
				$lineContent .= "\t\t\$this->".strtolower($objectname)." = new ".$objectname."(\$this->db);". PHP_EOL;
			}
		} elseif (preg_match('/'.$includeClass.'/', $lineContent)) {
			$lineContent = '';
			foreach ($objects as $objectname) {
				$lineContent .= "dol_include_once('/".strtolower($modulename)."/class/".strtolower($objectname).".class.php');". PHP_EOL;
			}
		}
	}

	$allContent = implode("", $content);
	file_put_contents($file, $allContent);

	// Add methods for each object
	$allContent = getFromFile($srcfile, '/* BEGIN MODULEBUILDER API MYOBJECT */', '/* END MODULEBUILDER API MYOBJECT */');
	foreach ($objects as $objectname) {
		$arrayreplacement = array(
			'mymodule' => strtolower($modulename),
			'MyModule' => $modulename,
			'MYMODULE' => strtoupper($modulename),
			'My module' => $modulename,
			'my module' => $modulename,
			'Mon module' => $modulename,
			'mon module' => $modulename,
			'htdocs/modulebuilder/template' => strtolower($modulename),
			'myobject' => strtolower($objectname),
			'MyObject' => $objectname,
			'MYOBJECT' => strtoupper($objectname),
			'---Put here your own copyright and developer email---' => dol_print_date($now, '%Y').' '.$user->getFullName($langs).($user->email ? ' <'.$user->email.'>' : '')
		);
		$contentReplaced = make_substitutions($allContent, $arrayreplacement, null);
		//$contentReplaced = str_replace(["myobject","MyObject"], [strtolower($object),$object], $allContent);

		dolReplaceInFile($file, array(
			'/* BEGIN MODULEBUILDER API MYOBJECT */' => '/* BEGIN MODULEBUILDER API '.strtoupper($objectname).' */'.$contentReplaced."\t".'/* END MODULEBUILDER API '.strtoupper($objectname).' */'."\n\n\n\t".'/* BEGIN MODULEBUILDER API MYOBJECT */'
		));
	}

	// Remove the block $allContent found in src file
	// TODO Replace with a replacement of all text including into /* BEGIN MODULEBUILDER API MYOBJECT */ and /* END MODULEBUILDER API MYOBJECT */
	dolReplaceInFile($file, array($allContent => ''));

	return 1;
}

/**
 * Remove 	Object variables and methods from API_Module File
 *
 * @param 	string   	$file         	File api module
 * @param  	string[] 	$objects        Array of objects in the module
 * @param 	string   	$objectname   	Name of object want to remove
 * @return 	int<-1,1> 					1 if OK, -1 if KO
 */
function removeObjectFromApiFile($file, $objects, $objectname)
{
	if (!file_exists($file)) {
		return -1;
	}

	$content = file($file);	// $content is an array

	$includeClass = "dol_include_once\(\'\/\w+\/class\/".strtolower($objectname)."\.class\.php\'\);";
	$props = 'public\s+\$'.strtolower($objectname);
	$varcommented = '@var\s+\w+\s+\$'.strtolower($objectname).'\s+{@type\s+\w+}';
	$constructObj = '\$this->'.strtolower($objectname).'\s+=\s+new\s+\w+\(\$this->db\);';

	// add properties and declare them in constructor
	foreach ($content as $lineNumber => &$lineContent) {
		if (preg_match('/'.$varcommented.'/i', $lineContent)) {
			$lineContent = '';
		} elseif (preg_match('/'.$props.'/i', $lineContent)) {
			$lineContent = '';
		} elseif (preg_match('/'.$constructObj.'/i', $lineContent)) {
			$lineContent = '';
		} elseif (preg_match('/'.$includeClass.'/i', $lineContent)) {
			$lineContent = '';
		}
	}

	$allContent = implode("", $content);
	file_put_contents($file, $allContent);

	// for delete methods of object
	$begin = '/* BEGIN MODULEBUILDER API '.strtoupper($objectname).' */';
	$end = '/* END MODULEBUILDER API '.strtoupper($objectname).' */';
	$allContent = getFromFile($file, $begin, $end);
	$check = dolReplaceInFile($file, array($allContent => ''));
	if ($check) {
		dolReplaceInFile($file, array($begin => '', $end => ''));
	}

	return 1;
}


/**
 * @param	string         $file       path of filename
 * @param	array<int,array{commentgroup:string,fk_menu:string,type:string,titre:string,mainmenu:string,leftmenu:string,url:string,langs:string,position:int,enabled:int,perms:string,target:string,user:int}>		$menus      all menus for module
 * @param	mixed|null     $menuWantTo  menu get for do actions
 * @param	int|null       $key        key for the concerned menu
 * @param	int<-1,2>      $action     for specify what action (0 = delete perm, 1 = add perm, 2 = update perm, -1 = when we delete object)
 * @return	int<-1,1>					1 if OK, -1 if KO
 */
function reWriteAllMenus($file, $menus, $menuWantTo, $key, $action)
{
	$errors = 0;
	$counter = 0;
	if (!file_exists($file)) {
		return -1;
	}

	if ($action == 0 && !empty($key)) {
		// delete menu manually
		array_splice($menus, array_search($menus[$key], $menus), 1);
	} elseif ($action == 1) {
		// add menu manually
		array_push($menus, $menuWantTo);
	} elseif ($action == 2 && !empty($key) && !empty($menuWantTo)) {
		// update right from permissions array
		$urlCounter = 0;
		// check if the values already exists
		foreach ($menus as $index => $menu) {
			if ($index !== $key) {
				if ($menu['type'] === $menuWantTo['type']) {
					if (strcasecmp(str_replace(' ', '', $menu['titre']), str_replace(' ', '', $menuWantTo['titre'])) === 0) {
						$counter++;
					}
					if (strcasecmp(str_replace(' ', '', $menu['url']), str_replace(' ', '', $menuWantTo['url'])) === 0) {
						$urlCounter++;
					}
				}
			}
		}
		if (!$counter && $urlCounter < 2) {
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
		// delete All LEFT Menus (except for commented template MYOBJECT)
		$beginMenu = '/* BEGIN MODULEBUILDER LEFTMENU';
		$excludeBeginMenu = '/* BEGIN MODULEBUILDER LEFTMENU MYOBJECT';
		$endMenu = '/* END MODULEBUILDER LEFTMENU';
		$protection = 0;
		while ($protection <= 1000 && $allMenus = getFromFile($file, $beginMenu, $endMenu, $excludeBeginMenu, 1)) {
			$protection++;
			dolReplaceInFile($file, array($allMenus => ''));
		}

		// forge the menu code in a string
		$str_menu = "";
		foreach ($menus as $index => $menu) {
			$menu['position'] = "1000 + \$r";
			if ($menu['type'] === 'left') {
				$start = "\t\t".'/* BEGIN MODULEBUILDER LEFTMENU '.strtoupper(empty($menu['object']) ? $menu['titre'] : $menu['object']).' */';
				$end   = "\t\t".'/* END MODULEBUILDER LEFTMENU '.strtoupper(empty($menu['object']) ? $menu['titre'] : $menu['object']).' */';

				$val_actuel = $menu;
				$next_val = empty($menus[$index + 1]) ? null : $menus[$index + 1];
				//var_dump(dol_escape_php($menu['perms'], 1)); exit;

				$str_menu .= $start."\n";
				$str_menu .= "\t\t\$this->menu[\$r++]=array(\n";
				$str_menu .= "\t\t\t 'fk_menu' => '".dol_escape_php($menu['fk_menu'], 1)."',\n";
				$str_menu .= "\t\t\t 'type' => '".dol_escape_php($menu['type'], 1)."',\n";
				$str_menu .= "\t\t\t 'titre' => '".dol_escape_php($menu['titre'], 1)."',\n";
				$str_menu .= "\t\t\t 'mainmenu' => '".dol_escape_php($menu['mainmenu'], 1)."',\n";
				$str_menu .= "\t\t\t 'leftmenu' => '".dol_escape_php($menu['leftmenu'], 1)."',\n";
				$str_menu .= "\t\t\t 'url' => '".dol_escape_php($menu['url'], 1)."',\n";
				$str_menu .= "\t\t\t 'langs' => '".dol_escape_php($menu['langs'], 1)."',\n";
				$str_menu .= "\t\t\t 'position' => ".((int) $menu['position']).",\n";
				$str_menu .= "\t\t\t 'enabled' => '".dol_escape_php($menu['enabled'], 1)."',\n";
				$str_menu .= "\t\t\t 'perms' => '".dol_escape_php($menu['perms'], 1)."',\n";
				$str_menu .= "\t\t\t 'target' => '".dol_escape_php($menu['target'], 1)."',\n";
				$str_menu .= "\t\t\t 'user' => ".((int) $menu['user']).",\n";
				$str_menu .= "\t\t\t 'object' => '".dol_escape_php($menu['object'], 1)."',\n";
				$str_menu .= "\t\t);\n";

				if (is_null($next_val) || $val_actuel['leftmenu'] !== $next_val['leftmenu']) {
					$str_menu .= $end."\n";
				}
			}
		}

		dolReplaceInFile($file, array('/* BEGIN MODULEBUILDER LEFTMENU MYOBJECT */' => $str_menu."\n\t\t/* BEGIN MODULEBUILDER LEFTMENU MYOBJECT */"));
		return 1;
	}
	return -1;
}

/**
 * Updates a dictionary in a module descriptor file.
 *
 * @param string $module The name of the module.
 * @param string $file The path to the module descriptor file.
 * @param array<string,string|array<string,int|string>> $dicts The dictionary data to be updated.
 * @return int Returns the number of replacements made in the file.
 */
function updateDictionaryInFile($module, $file, $dicts)
{
	$isEmpty = false;
	$dicData = "\t\t\$this->dictionaries=array(\n";
	$module = strtolower($module);
	foreach ($dicts as $key => $value) {
		if (empty($value)) {
			$isEmpty = true;
			$dicData = "\t\t\$this->dictionaries=array();";
			break;
		}

		$dicData .= "\t\t\t'$key'=>";

		if ($key === 'tabcond') {
			$conditions = array_map(
				/**
				 * @param mixed $val
				 * @return string|int
				 */
				function ($val) use ($module) {
					return is_bool($val) ? "isModEnabled('$module')" : $val;
				},
				$value
			);
			$dicData .= "array(" . implode(",", $conditions) . ")";
		} elseif ($key === 'tabhelp') {
			$helpItems = array();
			foreach ($value as $helpValue) {
				$helpItems[] = "array('code'=>\$langs->trans('".$helpValue['code']."'), 'field2' => 'field2tooltip')";
			}
			$dicData .= "array(" . implode(",", $helpItems) . ")";
		} else {
			if (is_array($value)) {
				$dicData .= "array(" . implode(
					",",
					array_map(
						/**
						 * @param string $val
						 * @return string
						 */
						static function ($val) {
							return "'$val'";
						},
						$value
					)
				) . ")";
			} else {
				$dicData .= "'$value'";
			}
		}
		$dicData .= ",\n";
	}
	$dicData .= (!$isEmpty ? "\t\t);" : '');

	$stringDic = getFromFile($file, '/* BEGIN MODULEBUILDER DICTIONARIES */', '/* END MODULEBUILDER DICTIONARIES */');
	$writeInfile = dolReplaceInFile($file, array($stringDic => $dicData."\n"));

	return $writeInfile;
}

/**
 * Creates a new dictionary table.
 *
 * for creating a new dictionary table in Dolibarr. It generates the necessary SQL code to define the table structure,
 * including columns such as 'rowid', 'code', 'label', 'position', 'use_default', 'active', etc. The table name is constructed based on the provided $namedic parameter.
 *
 * @param 	string 		$modulename 	The lowercase name of the module for which the dictionary table is being created.
 * @param 	string 		$file 			The file path to the Dolibarr module builder file where the dictionaries are defined.
 * @param 	string 		$namedic 		The name of the dictionary, which will also be used as the base for the table name.
 * @param 	array<string,string|array<string,int|string>>	$dictionnaires An optional array containing pre-existing dictionary data, including 'tabname', 'tablib', 'tabsql', etc.
 * @return 	int<-1,-1> 					Return int < 0 if error, return nothing on success
 */
function createNewDictionnary($modulename, $file, $namedic, $dictionnaires = null)
{
	global $db, $langs;

	if (empty($namedic)) {
		setEventMessages($langs->trans("ErrorEmptyNameDic"), null, 'errors');
		return -1;
	}
	if (!file_exists($file)) {
		return -1;
	}
	$modulename = strtolower($modulename);

	if (empty($dictionnaires)) {
		$dictionnaires = array('langs' => '', 'tabname' => array(), 'tablib' => array(), 'tabsql' => array(), 'tabsqlsort' => array(), 'tabfield' => array(), 'tabfieldvalue' => array(), 'tabfieldinsert' => array(), 'tabrowid' => array(), 'tabcond' => array(), 'tabhelp' => array());
	}

	$columns = array(
		'rowid' => array('type' => 'integer', 'value' => 11, 'extra' => 'AUTO_INCREMENT'),
		'code' => array('type' => 'varchar', 'value' => 255, 'null' => 'NOT NULL'),
		'label' => array('type' => 'varchar', 'value' => 255, 'null' => 'NOT NULL'),
		'position' => array('type' => 'integer', 'value' => 11, 'null' => 'NULL'),
		'use_default' => array('type' => 'varchar', 'value' => 11, 'default' => '1'),
		'active' => array('type' => 'integer', 'value' => 3)
	);

	$primaryKey = 'rowid';
	foreach ($columns as $key => $value) {
		if ($key === 'rowid') {
			$primaryKey = 'rowid';
			break;
		}
		if (!array_key_exists('rowid', $columns)) {
			$primaryKey = array_key_first($columns);
			break;
		}
	}

	// check if tablename exist in Database and create it if not
	$checkTable = $db->DDLDescTable(MAIN_DB_PREFIX.strtolower($namedic));
	if ($checkTable && $db->num_rows($checkTable) > 0) {
		setEventMessages($langs->trans("ErrorTableExist", $namedic), null, 'errors');
		return -1;
	} else {
		$_results = $db->DDLCreateTable(MAIN_DB_PREFIX.strtolower($namedic), $columns, $primaryKey, "");
		if ($_results < 0) {
			dol_print_error($db);
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorTableNotFound", $namedic), null, 'errors');
		}
	}

	// rewrite dictionary if
	$dictionnaires['langs'] = $modulename.'@'.$modulename;
	$dictionnaires['tabname'][] = strtolower($namedic);
	$dictionnaires['tablib'][] = ucfirst(substr($namedic, 2));
	$dictionnaires['tabsql'][] = 'SELECT t.rowid as rowid, t.code, t.label, t.active FROM '.MAIN_DB_PREFIX.strtolower($namedic).' as t';
	$dictionnaires['tabsqlsort'][] = (array_key_exists('label', $columns) ? 'label ASC' : '');
	$dictionnaires['tabfield'][] = (array_key_exists('code', $columns) && array_key_exists('label', $columns) ? 'code,label' : '');
	$dictionnaires['tabfieldvalue'][] = (array_key_exists('code', $columns) && array_key_exists('label', $columns) ? 'code,label' : '');
	$dictionnaires['tabfieldinsert'][] = (array_key_exists('code', $columns) && array_key_exists('label', $columns) ? 'code,label' : '');
	$dictionnaires['tabrowid'][] = $primaryKey;
	$dictionnaires['tabcond'][] = isModEnabled('$modulename');  // @phan-suppress-current-line UnknownModuleName
	$dictionnaires['tabhelp'][] = (array_key_exists('code', $columns) ? array('code' => $langs->trans('CodeTooltipHelp'), 'field2' => 'field2tooltip') : '');

	// Build the dictionary string
	$writeInfile = updateDictionaryInFile($modulename, $file, $dictionnaires);
	if ($writeInfile > 0) {
		setEventMessages($langs->trans("DictionariesCreated", ucfirst(substr($namedic, 2))), null);
	}

	return -1;
}

/**
 * Generate Urls and add them to documentation module
 *
 * @param string $file_api   filename or path of api
 * @param string $file_doc   filename or path of documentation
 * @return int<-1,1>         -1 if KO, 1 if OK, 0 if nothing change
 */
function writeApiUrlsInDoc($file_api, $file_doc)
{
	$error = 0;
	if (!dol_is_file($file_api) || !dol_is_file($file_doc)) {
		$error++;
	}
	$string = getFromFile($file_api, '/*begin methods CRUD*/', '/*end methods CRUD*/');
	$extractUrls = explode("\n", $string);

	// extract urls from file
	$urlValues = [];
	foreach ($extractUrls as $key => $line) {
		$lineWithoutTabsSpaces = preg_replace('/^[\t\s]+/', '', $line);
		if (strpos($lineWithoutTabsSpaces, '* @url') === 0) {
			$urlValue = trim(substr($lineWithoutTabsSpaces, strlen('* @url')));
			$urlValues[] = $urlValue;
		}
	}

	// get urls by object
	$str = $_SERVER['HTTP_HOST'].'/api/index.php/';
	$groupedUrls = [];
	foreach ($urlValues as $url) {
		if (preg_match('/(?:GET|POST|PUT|DELETE) (\w+)s/', $url, $matches)) {
			$objectName = $matches[1];
			$url = $str.trim(strstr($url, ' '));
			$groupedUrls[$objectName][] = $url;
		}
	}
	if (empty($groupedUrls)) {
		$error++;
	}

	// build format asciidoc for urls in table
	if (!$error) {
		$asciiDocTable = "[options=\"header\"]\n|===\n|Object | URLs\n";  // phpcs:ignore
		foreach ($groupedUrls as $objectName => $urls) {
			$urlsList = implode(" +\n*", $urls);
			$asciiDocTable .= "|$objectName | \n*$urlsList +\n";
		}
		$asciiDocTable .= "|===\n";
		$file_write = dolReplaceInFile($file_doc, array('__API_DOC__' => '__API_DOC__'."\n".$asciiDocTable));
		if ($file_write < 0) {
			return -1;
		}
		return 1;
	}
	return -1;
}


/**
 * count directories or files in modulebuilder folder
 * @param  string $path  path of directory
 * @param  int    $type  type of file 1= file,2=directory
 * @return int|bool
 */
function countItemsInDirectory($path, $type = 1)
{
	if (!is_dir($path)) {
		return false;
	}

	$allFilesAndDirs = scandir($path);
	$count = 0;

	foreach ($allFilesAndDirs as $item) {
		if ($item != '.' && $item != '..') {
			if ($type == 1 && is_file($path . DIRECTORY_SEPARATOR . $item) && strpos($item, '.back') === false) {
				$count++;
			} elseif ($type == 2 && is_dir($path . DIRECTORY_SEPARATOR . $item)) {
				$count++;
			}
		}
	}
	return $count;
}
