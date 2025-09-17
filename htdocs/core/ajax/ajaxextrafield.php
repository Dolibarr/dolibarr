<?php
/* Copyright (C) 2007-2024  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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
 *      \file       htdocs/core/ajax/ajaxextrafield.php
 *      \ingroup    core
 *      \brief      This script returns content of extrafield. See extrafield to update value.
 */

if (!defined('NOTOKENRENEWAL')) {
	// Disables token renewal
	define('NOTOKENRENEWAL', 1);
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1');
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}
if (!defined('NOHEADERNOFOOTER')) {
	define('NOHEADERNOFOOTER', '1');
}

include '../../main.inc.php';
include_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var Translate $langs
 * @var User $user
 */

// object id
$objectid = GETPOST('objectid', 'aZ09');
// 'module' or 'myobject@mymodule', 'mymodule_myobject'
$objecttype = GETPOST('objecttype', 'aZ09arobase');
$objectkey = GETPOST('objectkey', 'restricthtml');
$search = GETPOST('search', 'restricthtml');
$page = GETPOSTINT('page');
$mode = GETPOSTINT('mode');
$value = GETPOST('value', 'alphanohtml');
$limit = 10;
$offset = (($page - 1) * $limit);
$element_ref = '';
if (is_numeric($objectid)) {
	$objectid = (int) $objectid;
} else {
	$element_ref = $objectid;
	$objectid = 0;
}
// Load object according to $element
$object = fetchObjectByElement($objectid, $objecttype, $element_ref);
if (empty($object->element)) {
	httponly_accessforbidden('Failed to get object with fetchObjectByElement(id=' . $objectid . ', objecttype=' . $objecttype . ')');
}

$module = $object->module;
$element = $object->element;

$usesublevelpermission = ($module != $element ? $element : '');
if ($usesublevelpermission && !$user->hasRight($module, $element)) {	// There is no permission on object defined, we will check permission on module directly
	$usesublevelpermission = '';
}

// print $object->id.' - '.$object->module.' - '.$object->element.' - '.$object->table_element.' - '.$usesublevelpermission."\n";

// Security check
restrictedArea($user, $object->module, $object, $object->table_element, $usesublevelpermission);


/*
 * View
 */

top_httphead();

$data = [
	'results' => [],
	'pagination' => [
		'more' => true,
	]
];
if ($page == 1) {
	$data['results'][] = [
		'id' => -1,
		'text' => '&nbsp;',
	];
}
$i = 0;
if ($object instanceof CommonObject) {
	$extrafields = new ExtraFields($db);
	$extrafields->fetch_name_optionals_label($element);
	$options = $extrafields->attributes[$element]['param'][$objectkey]['options'];
	if (is_array($options)) {
		// WARNING!! @FIXME This code is duplicated into core/class/extrafields.class.php

		$tmpparamoptions = array_keys($options);
		$paramoptions = preg_split('/[\r\n]+/', $tmpparamoptions[0]);

		$InfoFieldList = explode(":", $paramoptions[0], 5);
		// 0 : tableName
		// 1 : label field name
		// 2 : key fields name (if different of rowid)
		// optional parameters...
		// 3 : key field parent (for dependent lists). How this is used ?
		// 4 : where clause filter on column or table extrafield, syntax field='value' or extra.field=value. Or use USF on the second line.
		// 5 : string category type. This replace the filter.
		// 6 : ids categories list separated by comma for category root. This replace the filter.
		// 7 : sort field (not used here but used into format for commobject)

		// If there is a filter, we extract it by taking all content inside parenthesis.
		if (! empty($InfoFieldList[4])) {
			$pos = 0;	// $pos will be position of ending filter
			$parenthesisopen = 0;
			while (substr($InfoFieldList[4], $pos, 1) !== '' && ($parenthesisopen || $pos == 0 || substr($InfoFieldList[4], $pos, 1) != ':')) {
				if (substr($InfoFieldList[4], $pos, 1) == '(') {
					$parenthesisopen++;
				}
				if (substr($InfoFieldList[4], $pos, 1) == ')') {
					$parenthesisopen--;
				}
				$pos++;
			}
			$tmpbefore = substr($InfoFieldList[4], 0, $pos);
			$tmpafter = substr($InfoFieldList[4], $pos + 1);
			//var_dump($InfoFieldList[4].' -> '.$pos); var_dump($tmpafter);
			$InfoFieldList[4] = $tmpbefore;
			if ($tmpafter !== '') {
				$InfoFieldList = array_merge($InfoFieldList, explode(':', $tmpafter));
			}

			// Fix better compatibility with some old extrafield syntax filter "(field=123)"
			$reg = array();
			if (preg_match('/^\(?([a-z0-9]+)([=<>]+)(\d+)\)?$/i', $InfoFieldList[4], $reg)) {
				$InfoFieldList[4] = '(' . $reg[1] . ':' . $reg[2] . ':' . $reg[3] . ')';
			}

			//var_dump($InfoFieldList);
		}

		$parentName = '';
		$parentField = '';
		$keyList = (empty($InfoFieldList[2]) ? 'rowid' : $InfoFieldList[2] . ' as rowid');

		if (count($InfoFieldList) > 3 && !empty($InfoFieldList[3])) {
			list($parentName, $parentField) = explode('|', $InfoFieldList[3]);
			$keyList .= ', ' . $parentField;
		}
		if (count($InfoFieldList) > 4 && !empty($InfoFieldList[4])) {
			if (strpos($InfoFieldList[4], 'extra.') !== false) {
				$keyList = 'main.' . $InfoFieldList[2] . ' as rowid';
			} else {
				$keyList = $InfoFieldList[2] . ' as rowid';
			}
		}

		$filter_categorie = false;
		if (count($InfoFieldList) > 5) {
			if ($InfoFieldList[0] == 'categorie') {
				$filter_categorie = true;
			}
		}

		if (!$filter_categorie) {
			$fields_label = explode('|', $InfoFieldList[1]);
			if (count($fields_label) > 0) {
				$keyList .= ', ';
				$keyList .= implode(', ', $fields_label);
			}

			$sqlwhere = '';
			$sql = "SELECT " . $keyList;
			$sql .= ' FROM ' . $db->prefix() . $InfoFieldList[0];

			// Add filter from 4th field
			if (!empty($InfoFieldList[4])) {
				// can use current entity filter
				if (strpos($InfoFieldList[4], '$ENTITY$') !== false) {
					$InfoFieldList[4] = str_replace('$ENTITY$', (string) $conf->entity, $InfoFieldList[4]);
				}
				// can use SELECT request
				if (!getDolGlobalString("MAIN_DISALLOW_UNSECURED_SELECT_INTO_EXTRAFIELDS_FILTER")) {
					if (strpos($InfoFieldList[4], '$SEL$') !== false) {
						$InfoFieldList[4] = str_replace('$SEL$', 'SELECT', $InfoFieldList[4]);
					}
				}
				// can use MODE parameter (list or view)
				if (strpos($InfoFieldList[4], '$MODE$') !== false) {
					$InfoFieldList[4] = str_replace('$MODE$', preg_replace('/[^a-z0-9_]/i', '', (string) $mode), $InfoFieldList[4]);
				}

				// current object id can be use into filter
				if (strpos($InfoFieldList[4], '$ID$') !== false && !empty($objectid)) {
					$InfoFieldList[4] = str_replace('$ID$', (string) $objectid, $InfoFieldList[4]);
				} else {
					$InfoFieldList[4] = str_replace('$ID$', '0', $InfoFieldList[4]);
				}

				// can filter on any field of object
				//if (is_object($object)) {
				$tags = [];
				preg_match_all('/\$(.*?)\$/', $InfoFieldList[4], $tags);
				foreach ($tags[0] as $keytag => $valuetag) {
					$property = preg_replace('/[^a-z0-9_]/', '', strtolower($tags[1][$keytag]));
					if (strpos($InfoFieldList[4], $valuetag) !== false && property_exists($object, $property) && !empty($object->$property)) {
						$InfoFieldList[4] = str_replace($valuetag, (string) $object->$property, $InfoFieldList[4]);
					} else {
						$InfoFieldList[4] = str_replace($valuetag, '0', $InfoFieldList[4]);
					}
				}
				//}

				// We have to filter on a field of the extrafield table
				$errstr = '';
				if (strpos($InfoFieldList[4], 'extra.') !== false) {
					$sql .= ' as main, ' . $db->sanitize($db->prefix() . $InfoFieldList[0]) . '_extrafields as extra';	// Add the join
					$sqlwhere .= " WHERE extra.fk_object = main." . $db->sanitize($InfoFieldList[2]);
					$sqlwhere .= " AND " . forgeSQLFromUniversalSearchCriteria($InfoFieldList[4], $errstr, 1);	// Add the filter
				} else {
					$sqlwhere .= " WHERE " . forgeSQLFromUniversalSearchCriteria($InfoFieldList[4], $errstr, 1);
				}
			} else {
				$sqlwhere .= ' WHERE 1=1';
			}

			// Some tables may have field, some other not. For the moment we disable it.
			if (in_array($InfoFieldList[0], array('tablewithentity'))) {
				$sqlwhere .= ' AND entity = ' . ((int) $conf->entity);
			}
			if ($search) {
				if ($fields_label) {
					$sqlwhere .= " " . natural_search($fields_label, $search, 0);
				}
			}

			$sql .= $sqlwhere;

			$orderfields = explode('|', $InfoFieldList[1]);
			$keyList = $InfoFieldList[1];
			if (count($orderfields)) {
				$keyList = implode(', ', $orderfields);
			}
			$sql .= $db->order($keyList);
			$sql .= $db->plimit($limit, $offset);

			$data['sql'] = $sql;

			$resql = $db->query($sql);
			if ($resql) {
				// $out .= '<option value="0">&nbsp;</option>';
				$num = $db->num_rows($resql);
				$i = 0;
				while ($i < $num) {
					$labeltoshow = '';
					$obj = $db->fetch_object($resql);

					// Several field into label (eq table:code|label:rowid)
					$notrans = false;
					$fields_label = explode('|', $InfoFieldList[1]);
					if (count($fields_label) > 1) {
						$notrans = true;
						foreach ($fields_label as $field_toshow) {
							$labeltoshow .= $obj->$field_toshow . ' ';
						}
					} else {
						$labeltoshow = $obj->{$InfoFieldList[1]};
					}

					if ($value == $obj->rowid) {
						if (!$notrans) {
							foreach ($fields_label as $field_toshow) {
								$translabel = $langs->trans($obj->$field_toshow);
								$labeltoshow = $translabel . ' ';
							}
						}
						// $out .= '<option value="'.$obj->rowid.'" selected>'.$labeltoshow.'</option>';
						$data['results'][] = [
							'id' => $obj->rowid,
							'text' => $labeltoshow,
						];
					} else {
						if (!$notrans) {
							$translabel = $langs->trans($obj->{$InfoFieldList[1]});
							$labeltoshow = $translabel;
						}
						if (empty($labeltoshow)) {
							$labeltoshow = '(not defined)';
						}

						if (!empty($InfoFieldList[3]) && $parentField) {
							$parent = $parentName . ':' . $obj->{$parentField};
						}

						// $out .= '<option value="'.$obj->rowid.'"';
						// $out .= ($value == $obj->rowid ? ' selected' : '');
						// $out .= (!empty($parent) ? ' parent="'.$parent.'"' : '');
						// $out .= '>'.$labeltoshow.'</option>';
						$data['results'][] = [
							'id' => $obj->rowid,
							'text' => $labeltoshow,
						];
					}

					$i++;
				}
				$db->free($resql);
			} else {
				dol_syslog('Error in request ' . $db->lasterror() . '. Check setup of extra parameters.', LOG_ERR);
			}
		} else {
			require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
			require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
		}
	}
}

if ($page > 1 && $i < 9) {
	$data['pagination'] = [
		'more' => false,
	];
}
print json_encode($data);

$db->close();
