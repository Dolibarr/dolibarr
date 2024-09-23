<?php
/* Copyright (C) 2011-2020  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
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
 *
 * $elementype must be defined.
 */

/**
 *	\file			htdocs/core/actions_extrafields.inc.php
 *  \brief			Code for actions on extrafields admin pages
 */

$maxsizestring = 255;
$maxsizeint = 10;
$mesg = array();

$extrasize = GETPOST('size', 'intcomma');
$type = GETPOST('type', 'alphanohtml');
$param = GETPOST('param', 'alphanohtml');
$css = GETPOST('css', 'alphanohtml');
$cssview = GETPOST('cssview', 'alphanohtml');
$csslist = GETPOST('csslist', 'alphanohtml');
$confirm = GETPOST('confirm', 'alpha');

if ($type == 'double' && strpos($extrasize, ',') === false) {
	$extrasize = '24,8';
}
if ($type == 'date') {
	$extrasize = '';
}
if ($type == 'datetime') {
	$extrasize = '';
}
if ($type == 'select') {
	$extrasize = '';
}

$listofreservedwords = array(
	'ADD', 'ALL', 'ALTER', 'ANALYZE', 'AND', 'AS', 'ASENSITIVE', 'BEFORE', 'BETWEEN', 'BINARY', 'BLOB', 'BOTH', 'CALL', 'CASCADE', 'CASE', 'CHANGE', 'CHAR', 'CHARACTER', 'CHECK', 'COLLATE', 'COLUMN', 'CONDITION', 'CONSTRAINT', 'CONTINUE', 'CONVERT', 'CREATE', 'CROSS', 'CURRENT_DATE', 'CURRENT_TIME', 'CURRENT_TIMESTAMP', 'CURRENT_USER',
	'CURSOR', 'DATABASE', 'DATABASES', 'DAY_HOUR', 'DAY_MICROSECOND', 'DAY_MINUTE', 'DAY_SECOND', 'DECIMAL', 'DECLARE', 'DEFAULT', 'DELAYED', 'DELETE', 'DESC', 'DESCRIBE', 'DETERMINISTIC', 'DISTINCT', 'DISTINCTROW', 'DOUBLE', 'DROP', 'DUAL',
	'EACH', 'ELSE', 'ELSEIF', 'ENCLOSED', 'ESCAPED', 'EXISTS', 'EXPLAIN', 'FALSE', 'FETCH', 'FLOAT', 'FLOAT4', 'FLOAT8', 'FORCE', 'FOREIGN', 'FULLTEXT', 'GRANT', 'GROUP', 'HAVING', 'HIGH_PRIORITY', 'HOUR_MICROSECOND', 'HOUR_MINUTE', 'HOUR_SECOND',
	'IGNORE', 'IGNORE_SERVER_IDS', 'INDEX', 'INFILE', 'INNER', 'INOUT', 'INSENSITIVE', 'INSERT', 'INT', 'INTEGER', 'INTERVAL', 'INTO', 'ITERATE',
	'KEYS', 'KEYWORD', 'LEADING', 'LEAVE', 'LEFT', 'LIKE', 'LIMIT', 'LINES', 'LOCALTIME', 'LOCALTIMESTAMP', 'LONGBLOB', 'LONGTEXT', 'MASTER_SSL_VERIFY_SERVER_CERT', 'MATCH', 'MEDIUMBLOB', 'MEDIUMINT', 'MEDIUMTEXT', 'MIDDLEINT', 'MINUTE_MICROSECOND', 'MINUTE_SECOND', 'MODIFIES', 'NATURAL', 'NOT', 'NO_WRITE_TO_BINLOG', 'NUMERIC',
	'OFFSET', 'ON', 'OPTION', 'OPTIONALLY', 'OUTER', 'OUTFILE',
	'PARTITION', 'POSITION', 'PRECISION', 'PRIMARY', 'PROCEDURE', 'PURGE', 'RANGE', 'READS', 'READ_WRITE', 'REAL', 'REFERENCES', 'REGEXP', 'RELEASE', 'RENAME', 'REPEAT', 'REQUIRE', 'RESTRICT', 'RETURN', 'REVOKE', 'RIGHT', 'RLIKE',
	'SCHEMAS', 'SECOND_MICROSECOND', 'SENSITIVE', 'SEPARATOR', 'SIGNAL', 'SMALLINT', 'SPATIAL', 'SPECIFIC', 'SQLEXCEPTION', 'SQLSTATE', 'SQLWARNING', 'SQL_BIG_RESULT', 'SQL_CALC_FOUND_ROWS', 'SQL_SMALL_RESULT', 'SSL', 'STARTING', 'STRAIGHT_JOIN',
	'TABLE', 'TERMINATED', 'TINYBLOB', 'TINYINT', 'TINYTEXT', 'TRAILING', 'TRIGGER', 'UNDO', 'UNIQUE', 'UNSIGNED', 'UPDATE', 'USAGE', 'USING', 'UTC_DATE', 'UTC_TIME', 'UTC_TIMESTAMP', 'VALUES', 'VARBINARY', 'VARCHAR', 'VARYING',
	'WHEN', 'WHERE', 'WHILE', 'WRITE', 'XOR', 'YEAR_MONTH', 'ZEROFILL'
);

// Add attribute
if ($action == 'add') {
	if (GETPOST("button") != $langs->trans("Cancel")) {
		// Check values
		if (!$type) {
			$error++;
			$langs->load("errors");
			$mesg[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Type"));
			$action = 'create';
		}
		if ($type == 'varchar' && $extrasize <= 0) {
			$error++;
			$langs->load("errors");
			$mesg[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Size"));
			$action = 'edit';
		}
		if ($type == 'varchar' && $extrasize > $maxsizestring) {
			$error++;
			$langs->load("errors");
			$mesg[] = $langs->trans("ErrorSizeTooLongForVarcharType", $maxsizestring);
			$action = 'create';
		}
		if ($type == 'int' && $extrasize > $maxsizeint) {
			$error++;
			$langs->load("errors");
			$mesg[] = $langs->trans("ErrorSizeTooLongForIntType", $maxsizeint);
			$action = 'create';
		}
		if ($type == 'select' && !$param) {
			$error++;
			$langs->load("errors");
			$mesg[] = $langs->trans("ErrorNoValueForSelectType");
			$action = 'create';
		}
		if ($type == 'sellist' && !$param) {
			$error++;
			$langs->load("errors");
			$mesg[] = $langs->trans("ErrorNoValueForSelectListType");
			$action = 'create';
		}
		if ($type == 'checkbox' && !$param) {
			$error++;
			$langs->load("errors");
			$mesg[] = $langs->trans("ErrorNoValueForCheckBoxType");
			$action = 'create';
		}
		if ($type == 'link' && !$param) {
			$error++;
			$langs->load("errors");
			$mesg[] = $langs->trans("ErrorNoValueForLinkType");
			$action = 'create';
		}
		if ($type == 'radio' && !$param) {
			$error++;
			$langs->load("errors");
			$mesg[] = $langs->trans("ErrorNoValueForRadioType");
			$action = 'create';
		}
		if ((($type == 'radio') || ($type == 'checkbox')) && $param) {
			// Construct array for parameter (value of select list)
			$parameters = $param;
			$parameters_array = explode("\r\n", $parameters);
			foreach ($parameters_array as $param_ligne) {
				if (!empty($param_ligne)) {
					if (preg_match_all('/,/', $param_ligne, $matches)) {
						if (count($matches[0]) > 1) {
							$error++;
							$langs->load("errors");
							$mesg[] = $langs->trans("ErrorBadFormatValueList", $param_ligne);
							$action = 'create';
						}
					} else {
						$error++;
						$langs->load("errors");
						$mesg[] = $langs->trans("ErrorBadFormatValueList", $param_ligne);
						$action = 'create';
					}
				}
			}
		}

		if (!$error) {
			if (strlen(GETPOST('attrname', 'aZ09')) < 3) {
				$error++;
				$langs->load("errors");
				$mesg[] = $langs->trans("ErrorValueLength", $langs->transnoentitiesnoconv("AttributeCode"), 3);
				$action = 'create';
			}
		}

		// Check reserved keyword with more than 3 characters
		if (!$error) {
			if (in_array(strtoupper(GETPOST('attrname', 'aZ09')), $listofreservedwords)) {
				$error++;
				$langs->load("errors");
				$mesg[] = $langs->trans("ErrorReservedKeyword", GETPOST('attrname', 'aZ09'));
				$action = 'create';
			}
		}

		if (!$error) {
			// attrname must be alphabetical and lower case only
			if (GETPOSTISSET("attrname") && preg_match("/^[a-z0-9_]+$/", GETPOST('attrname', 'aZ09')) && !is_numeric(GETPOST('attrname', 'aZ09'))) {
				// Construct array for parameter (value of select list)
				$default_value = GETPOST('default_value', 'alpha');
				$parameters = $param;
				$parameters_array = explode("\r\n", $parameters);
				$params = array();
				//In sellist we have only one line and it can have come to do SQL expression
				if ($type == 'sellist' || $type == 'chkbxlst') {
					foreach ($parameters_array as $param_ligne) {
						$params['options'] = array($parameters => null);
					}
				} else {
					// Else it's separated key/value and coma list
					foreach ($parameters_array as $param_ligne) {
						if (strpos($param_ligne, ',') !== false) {
							list($key, $value) = explode(',', $param_ligne);
							if (!array_key_exists('options', $params)) {
								$params['options'] = array();
							}
						} else {
							$key = $param_ligne;
							$value = null;
						}
						$params['options'][$key] = $value;
					}
				}

				// Visibility: -1=not visible by default in list, 1=visible, 0=hidden
				$visibility = GETPOST('list', 'alpha');
				if (in_array($type, ['separate', 'point', 'linestrg', 'polygon'])) {
					$visibility = 3;
				}

				$result = $extrafields->addExtraField(
					GETPOST('attrname', 'aZ09'),
					GETPOST('label', 'alpha'),
					$type,
					GETPOSTINT('pos'),
					$extrasize,
					$elementtype,
					(GETPOST('unique', 'alpha') ? 1 : 0),
					(GETPOST('required', 'alpha') ? 1 : 0),
					$default_value,
					$params,
					(GETPOST('alwayseditable', 'alpha') ? 1 : 0),
					(GETPOST('perms', 'alpha') ? GETPOST('perms', 'alpha') : ''),
					$visibility,
					GETPOST('help', 'alpha'),
					GETPOST('computed_value', 'alpha'),
					(GETPOST('entitycurrentorall', 'alpha') ? 0 : ''),
					GETPOST('langfile', 'alpha'),
					1,
					(GETPOST('totalizable', 'alpha') ? 1 : 0),
					GETPOST('printable', 'alpha'),
					array('css' => $css, 'cssview' => $cssview, 'csslist' => $csslist)
				);
				if ($result > 0) {
					setEventMessages($langs->trans('SetupSaved'), null, 'mesgs');
					header("Location: ".$_SERVER["PHP_SELF"]);
					exit;
				} else {
					$error++;
					$mesg = $extrafields->error;
					setEventMessages($mesg, null, 'errors');
				}
			} else {
				$error++;
				$langs->load("errors");
				$mesg = $langs->trans("ErrorFieldCanNotContainSpecialNorUpperCharacters", $langs->transnoentities("AttributeCode"));
				setEventMessages($mesg, null, 'errors');
				$action = 'create';
			}
		} else {
			setEventMessages($mesg, null, 'errors');
		}
	}
}

// Rename field
if ($action == 'update') {
	if (GETPOST("button") != $langs->trans("Cancel")) {
		// Check values
		if (!$type) {
			$error++;
			$langs->load("errors");
			$mesg[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Type"));
			$action = 'edit';
		}
		if ($type == 'varchar' && $extrasize <= 0) {
			$error++;
			$langs->load("errors");
			$mesg[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Size"));
			$action = 'edit';
		}
		if ($type == 'varchar' && $extrasize > $maxsizestring) {
			$error++;
			$langs->load("errors");
			$mesg[] = $langs->trans("ErrorSizeTooLongForVarcharType", $maxsizestring);
			$action = 'edit';
		}
		if ($type == 'int' && $extrasize > $maxsizeint) {
			$error++;
			$langs->load("errors");
			$mesg[] = $langs->trans("ErrorSizeTooLongForIntType", $maxsizeint);
			$action = 'edit';
		}
		if ($type == 'select' && !$param) {
			$error++;
			$langs->load("errors");
			$mesg[] = $langs->trans("ErrorNoValueForSelectType");
			$action = 'edit';
		}
		if ($type == 'sellist' && !$param) {
			$error++;
			$langs->load("errors");
			$mesg[] = $langs->trans("ErrorNoValueForSelectListType");
			$action = 'edit';
		}
		if ($type == 'checkbox' && !$param) {
			$error++;
			$langs->load("errors");
			$mesg[] = $langs->trans("ErrorNoValueForCheckBoxType");
			$action = 'edit';
		}
		if ($type == 'radio' && !$param) {
			$error++;
			$langs->load("errors");
			$mesg[] = $langs->trans("ErrorNoValueForRadioType");
			$action = 'edit';
		}
		if ((($type == 'radio') || ($type == 'checkbox')) && $param) {
			// Construct array for parameter (value of select list)
			$parameters = $param;
			$parameters_array = explode("\r\n", $parameters);
			foreach ($parameters_array as $param_ligne) {
				if (!empty($param_ligne)) {
					if (preg_match_all('/,/', $param_ligne, $matches)) {
						if (count($matches[0]) > 1) {
							$error++;
							$langs->load("errors");
							$mesg[] = $langs->trans("ErrorBadFormatValueList", $param_ligne);
							$action = 'edit';
						}
					} else {
						$error++;
						$langs->load("errors");
						$mesg[] = $langs->trans("ErrorBadFormatValueList", $param_ligne);
						$action = 'edit';
					}
				}
			}
		}

		if (!$error) {
			if (strlen(GETPOST('attrname', 'aZ09')) < 3 && !getDolGlobalString('MAIN_DISABLE_EXTRAFIELDS_CHECK_FOR_UPDATE')) {
				$error++;
				$langs->load("errors");
				$mesg[] = $langs->trans("ErrorValueLength", $langs->transnoentitiesnoconv("AttributeCode"), 3);
				$action = 'edit';
			}
		}

		// Check reserved keyword with more than 3 characters
		if (!$error) {
			if (in_array(strtoupper(GETPOST('attrname', 'aZ09')), $listofreservedwords) && !getDolGlobalString('MAIN_DISABLE_EXTRAFIELDS_CHECK_FOR_UPDATE')) {
				$error++;
				$langs->load("errors");
				$mesg[] = $langs->trans("ErrorReservedKeyword", GETPOST('attrname', 'aZ09'));
				$action = 'edit';
			}
		}

		if (!$error) {
			if (GETPOSTISSET("attrname") && preg_match("/^\w[a-zA-Z0-9-_]*$/", GETPOST('attrname', 'aZ09')) && !is_numeric(GETPOST('attrname', 'aZ09'))) {
				$pos = GETPOSTINT('pos');
				// Construct array for parameter (value of select list)
				$parameters = $param;
				$parameters_array = explode("\r\n", $parameters);
				$params = array();
				//In sellist we have only one line and it can have come to do SQL expression
				if ($type == 'sellist' || $type == 'chkbxlst') {
					foreach ($parameters_array as $param_ligne) {
						$params['options'] = array($parameters => null);
					}
				} else {
					//Else it's separated key/value and coma list
					foreach ($parameters_array as $param_ligne) {
						$tmp = explode(',', $param_ligne);
						$key = $tmp[0];
						if (!empty($tmp[1])) {
							$value = $tmp[1];
						}
						if (!array_key_exists('options', $params)) {
							$params['options'] = array();
						}
						$params['options'][$key] = $value;
					}
				}

				// $params['options'][$key] can be 'Facture:/compta/facture/class/facture.class.php' => '/custom'

				// Visibility: -1=not visible by default in list, 1=visible, 0=hidden
				$visibility = GETPOST('list', 'alpha');
				if (in_array($type, ['separate', 'point', 'linestrg', 'polygon'])) {
					$visibility = 3;
				}

				// Example: is_object($object) ? ($object->id < 10 ? round($object->id / 2, 2) : (2 * $user->id) * (int) substr($mysoc->zip, 1, 2)) : 'objnotdefined'
				$computedvalue = GETPOST('computed_value', 'nohtml');

				$result = $extrafields->update(
					GETPOST('attrname', 'aZ09'),
					GETPOST('label', 'alpha'),
					$type,
					$extrasize,
					$elementtype,
					(GETPOST('unique', 'alpha') ? 1 : 0),
					(GETPOST('required', 'alpha') ? 1 : 0),
					$pos,
					$params,
					(GETPOST('alwayseditable', 'alpha') ? 1 : 0),
					(GETPOST('perms', 'alpha') ? GETPOST('perms', 'alpha') : ''),
					$visibility,
					GETPOST('help', 'alpha'),
					GETPOST('default_value', 'alpha'),
					$computedvalue,
					(GETPOST('entitycurrentorall', 'alpha') ? 0 : ''),
					GETPOST('langfile'),
					GETPOST('enabled', 'nohtml'),
					(GETPOST('totalizable', 'alpha') ? 1 : 0),
					GETPOST('printable', 'alpha'),
					array('css' => $css, 'cssview' => $cssview, 'csslist' => $csslist)
				);
				if ($result > 0) {
					setEventMessages($langs->trans('SetupSaved'), null, 'mesgs');
					header("Location: ".$_SERVER["PHP_SELF"]);
					exit;
				} else {
					$error++;
					$mesg = $extrafields->error;
					setEventMessages($mesg, null, 'errors');
				}
			} else {
				$error++;
				$langs->load("errors");
				$mesg = $langs->trans("ErrorFieldCanNotContainSpecialCharacters", $langs->transnoentities("AttributeCode"));
				setEventMessages($mesg, null, 'errors');
			}
		} else {
			setEventMessages($mesg, null, 'errors');
		}
	}
}

// Delete attribute
if ($action == 'confirm_delete' && $confirm == "yes") {
	if (GETPOSTISSET("attrname") && preg_match("/^\w[a-zA-Z0-9-_]*$/", GETPOST("attrname", 'aZ09'))) {
		$attributekey = GETPOST('attrname', 'aZ09');

		$result = $extrafields->delete($attributekey, $elementtype);
		if ($result >= 0) {
			setEventMessages($langs->trans("ExtrafieldsDeleted", $attributekey), null, 'mesgs');

			header("Location: ".$_SERVER["PHP_SELF"]);
			exit;
		} else {
			$mesg = $extrafields->error;
		}
	} else {
		$error++;
		$langs->load("errors");
		$mesg = $langs->trans("ErrorFieldCanNotContainSpecialCharacters", $langs->transnoentities("AttributeCode"));
	}
}

// Recrypt data password
if ($action == 'encrypt') {
	// Load $extrafields->attributes
	$extrafields->fetch_name_optionals_label($elementtype);
	$attributekey = GETPOST('attrname', 'aZ09');

	if (!empty($extrafields->attributes[$elementtype]['type'][$attributekey]) && $extrafields->attributes[$elementtype]['type'][$attributekey] == 'password') {
		if (!empty($extrafields->attributes[$elementtype]['param'][$attributekey]['options'])) {
			if (array_key_exists('dolcrypt', $extrafields->attributes[$elementtype]['param'][$attributekey]['options'])) {
				// We can encrypt data with dolCrypt()
				$arrayofelement = getElementProperties($elementtype);
				if (!empty($arrayofelement['table_element'])) {
					if ($extrafields->attributes[$elementtype]['entityid'][$attributekey] == $conf->entity || empty($extrafields->attributes[$elementtype]['entityid'][$attributekey])) {
						dol_syslog("Loop on each extafields of table ".$arrayofelement['table_element']);

						$sql  = "SELECT te.rowid, te.".$attributekey;
						$sql .= " FROM ".MAIN_DB_PREFIX.$arrayofelement['table_element']." as t, ".MAIN_DB_PREFIX.$arrayofelement['table_element'].'_extrafields as te';
						$sql .= " WHERE te.fk_object = t.rowid";
						$sql .= " AND te.".$attributekey." NOT LIKE 'dolcrypt:%'";
						$sql .= " AND te.".$attributekey." IS NOT NULL";
						$sql .= " AND te.".$attributekey." <> ''";
						if ($extrafields->attributes[$elementtype]['entityid'][$attributekey] == $conf->entity) {
							$sql .= " AND t.entity = ".getEntity($arrayofelement['table_element'], 0);
						}

						//print $sql;
						$nbupdatedone = 0;
						$resql = $db->query($sql);
						if ($resql) {
							$num_rows = $db->num_rows($resql);
							$i = 0;
							while ($i < $num_rows) {
								$objtmp = $db->fetch_object($resql);
								$id = $objtmp->rowid;
								$pass = $objtmp->$attributekey;
								if ($pass) {
									$newpassword = dolEncrypt($pass);

									$sqlupdate = "UPDATE ".MAIN_DB_PREFIX.$arrayofelement['table_element'].'_extrafields';
									$sqlupdate .= " SET ".$attributekey." = '".$db->escape($newpassword)."'";
									$sqlupdate .= " WHERE rowid = ".((int) $id);

									$resupdate = $db->query($sqlupdate);
									if ($resupdate) {
										$nbupdatedone++;
									} else {
										setEventMessages($db->lasterror(), null, 'errors');
										$error++;
										break;
									}
								}

								$i++;
							}
						}

						if ($nbupdatedone > 0) {
							setEventMessages($langs->trans("PasswordFieldEncrypted", $nbupdatedone), null, 'mesgs');
						} else {
							setEventMessages($langs->trans("PasswordFieldEncrypted", $nbupdatedone), null, 'warnings');
						}
					}
				}
			}
		}
	}
}
