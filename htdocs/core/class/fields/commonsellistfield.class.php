<?php
/* Copyright (C) 2025 		Open-Dsi         <support@open-dsi.fr>
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
 *    \file        htdocs/core/class/fields/commonsellistfield.class.php
 *    \ingroup    core
 *    \brief      File of class to common sellist field
 */

require_once DOL_DOCUMENT_ROOT . '/core/class/fields/commonfield.class.php';


/**
 *    Class to common sellist field
 */
class CommonSellistField extends CommonField
{
	/**
	 * @var string    Url of the AJAX page for get options of the sellist
	 */
	public static $ajaxUrl = DOL_URL_ROOT . '/core/ajax/ajaxfield.php';

	/**
	 * @var array<string,array<string,array{label:string,parent:string}>>	Options cached
	 */
	public static $options = array();

	/**
	 * @var array<int,string> 	Code mapping from ID. For backward compatibility
	 */
	const MAP_ID_TO_CODE = array(
		0  => 'product',
		1  => 'supplier',
		2  => 'customer',
		3  => 'member',
		4  => 'contact',
		5  => 'bank_account',
		6  => 'project',
		7  => 'user',
		8  => 'bank_line',
		9  => 'warehouse',
		10 => 'actioncomm',
		11 => 'website_page',
		12 => 'ticket',
		13 => 'knowledgemanagement',
		14 => 'fichinter',
		16 => 'order',
		17 => 'invoice',
		20 => 'supplier_order',
		21 => 'supplier_invoice'
	);

	/**
	 * Get all parameters in the options
	 *
	 * @param	array<string,mixed>		$options	Options of the field
	 * @return	array{all:string,tableName:string,labelFullFields:string[],labelFields:string[],labelAlias:string[],keyField:string,parentName:string,parentFullField:string,parentField:string,parentAlias:string,filter:string,categoryType:string,categoryRoots:string,sortField:string}
	 */
	public function getOptionsParams($options)
	{
		$options = is_array($options) ? $options : array();
		$paramList = array_keys($options);
		$paramList = preg_split('/[\r\n]+/', $paramList[0]);
		// 0 : tableName
		// 1 : label field name
		// 2 : key fields name (if different of rowid)
		// optional parameters...
		// 3 : key field parent (for dependent lists). (= 'parentName|parentField'; parentName: Name of the input field (ex: ref or options_code); parentField: Name of the field in the table for getting the value)
		//     Only the value who is equal to the selected value of the parentName input with the value of the parentField is displayed in this select options
		// 4 : where clause filter on column or table extrafield, syntax field='value' or extra.field=value. Or use USF on the second line.
		// 5 : string category type. This replace the filter.
		// 6 : ids categories list separated by comma for category root. This replace the filter.
		// 7 : sort field (Don't manage ASC or DESC)

		$all = (string) $paramList[0];
		$InfoFieldList = explode(":", $all, 5);

		// If there is a filter, we extract it by taking all content inside parenthesis.
		if (!empty($InfoFieldList[4])) {
			$pos = 0;    // $pos will be position of ending filter
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
			$InfoFieldList[4] = $tmpbefore;
			if ($tmpafter !== '') {
				$InfoFieldList = array_merge($InfoFieldList, explode(':', $tmpafter));
			}

			// Fix better compatibility with some old extrafield syntax filter "(field=123)"
			$reg = array();
			if (preg_match('/^\(?([a-z0-9]+)([=<>]+)(\d+)\)?$/i', $InfoFieldList[4], $reg)) {
				$InfoFieldList[4] = '(' . $reg[1] . ':' . $reg[2] . ':' . $reg[3] . ')';
			}
		}

		$tableName = (string) ($InfoFieldList[0] ?? '');
		$labelFullFields = (string) ($InfoFieldList[1] ?? '');
		// @phpstan-ignore-next-line
		$labelFullFields = array_filter(array_map('trim', explode('|', $labelFullFields)), 'strlen');
		$labelFields = array();
		$labelAlias = array();
		foreach ($labelFullFields as $labelFullField) {
			$tmp = $this->getSqlFieldInfo($labelFullField);
			$labelFields[] = $tmp['field'];
			$labelAlias[] = $tmp['alias'];
		}
		$keyField = (string) ($InfoFieldList[2] ?? '');
		if (empty($keyField)) $keyField = 'rowid';
		$keyFieldParent = (string) ($InfoFieldList[3] ?? '');
		$tmp = array_map('trim', explode('|', $keyFieldParent));
		$parentName = (string) ($tmp[0] ?? '');
		$parentFullField = (string) ($tmp[1] ?? '');
		$tmp = $this->getSqlFieldInfo($parentFullField);
		$parentField = $tmp['field'];
		$parentAlias = $tmp['alias'];
		$filter = (string) ($InfoFieldList[4] ?? '');
		$categoryType = (string) ($InfoFieldList[5] ?? '');
		if (is_numeric($categoryType)) {    // deprecated: must use the category code instead of id. For backward compatibility.
			require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
			$categoryType = self::MAP_ID_TO_CODE[(int) $categoryType] ?? '';
		}
		$categoryRoots = (string) ($InfoFieldList[6] ?? '');
		$sortField = (string) ($InfoFieldList[7] ?? '');

		return array(
			'all' => $all,
			'tableName' => $tableName,
			'labelFullFields' => $labelFullFields,
			'labelFields' => $labelFields,
			'labelAlias' => $labelAlias,
			'keyField' => $keyField,
			'parentName' => $parentName,
			'parentFullField' => $parentFullField,
			'parentField' => $parentField,
			'parentAlias' => $parentAlias,
			'filter' => $filter,
			'categoryType' => $categoryType,
			'categoryRoots' => $categoryRoots,
			'sortField' => $sortField,
		);
	}

	/**
	 * Get sql info of the full field
	 *
	 * @param	string	$fullField	Full field (ex: p.test AS label or f(a,b,c) AS label)
	 * @return	array{field:string,alias:string}
	 */
	public function getSqlFieldInfo($fullField)
	{
		if (preg_match('/(.*)\s+AS\s+(\w+)$/i', $fullField, $matches)) {
			$field = $matches[1];
			$alias = $matches[2];
		} else {
			$field = $fullField;
			$alias = $fullField;
		}

		if (preg_match('/^\w+\.(.*)/i', $field, $matches)) {
			$alias = $matches[1];
		}

		return array(
			'field' => $field,
			'alias' => $alias,
		);
	}

	/**
	 * Get list of options
	 *
	 * @param   FieldInfos    											$fieldInfos     Array of properties for field to show
	 * @param	string													$key			Key of field
	 * @param	bool													$addEmptyValue	Add also empty value if needed
	 * @param 	bool													$reload			Force reload options
	 * @param	string|array<int,string>								$selectedValues Only selected values
	 * @return  array<string,array{label:string,parent:string}>|null					Return null if error
	 */
	public function getOptions($fieldInfos, $key, $addEmptyValue = false, $reload = false, $selectedValues = array())
	{
		global $conf, $langs;

		$selectedValues = array_map('trim', is_array($selectedValues) ? $selectedValues : array($selectedValues));

		if (!isset(self::$options[$key]) || $reload) {
			$options = array();
			if (!empty($fieldInfos->options) && is_array($fieldInfos->options)) {
				$optionsParams = $this->getOptionsParams($fieldInfos->options);

				if ($optionsParams['tableName'] == 'categorie' && !empty($optionsParams['categoryType'])) {
					$data = self::$form->select_all_categories($optionsParams['categoryType'], '', 'parent', 64, $optionsParams['categoryRoots'], 1, 1);
					if (is_array($data)) {
						foreach ($data as $data_key => $data_value) {
							$options[$data_key] = array(
								'label' => $data_value,
								'parent' => '',
							);
						}
					}
				} else {
					$filter = $optionsParams['filter'];
					$hasExtra = !empty($filter) && strpos($filter, 'extra.') !== false;
					$keyField = ($hasExtra ? 'main.' : '') . $optionsParams['keyField'];

					$keyList = $keyField . ' AS rowid';
					if (!empty($optionsParams['parentFullField'])) {
						$keyList .= ', ' . $optionsParams['parentFullField'];
					}
					if (!empty($optionsParams['labelFullFields'])) {
						$keyList .= ', ' . implode(', ', $optionsParams['labelFullFields']);
					}

					$sql = "SELECT " . $keyList;
					$sql .= " FROM " . $this->db->sanitize($this->db->prefix() . $optionsParams['tableName']);
					if ($hasExtra) {
						$sql .= " AS main";
						$sql .= " LEFT JOIN " . $this->db->sanitize($this->db->prefix() . $optionsParams['tableName']) . "_extrafields AS extra ON extra.fk_object = " . $keyField;
					}

					// Add filter from 4th field
					if (!empty($filter)) {
						// can use current entity filter
						if (strpos($filter, '$ENTITY$') !== false) {
							$filter = str_replace('$ENTITY$', (string) $conf->entity, $filter);
						}
						// can use SELECT request
						if (strpos($filter, '$SEL$') !== false && !getDolGlobalString("MAIN_DISALLOW_UNSECURED_SELECT_INTO_EXTRAFIELDS_FILTER")) {
							$filter = str_replace('$SEL$', 'SELECT', $filter);
						}
						// can use MODE parameter (list or view)
						if (strpos($filter, '$MODE$') !== false) {
							$filter = str_replace('$MODE$', empty($fieldInfos->mode) ? 'view' : $fieldInfos->mode, $filter);
						}

						// Current object id can be used into filter
						$objectid = isset($fieldInfos->otherParams['objectId']) ? (int) $fieldInfos->otherParams['objectId'] : (isset($fieldInfos->object) && is_object($fieldInfos->object) ? (int) $fieldInfos->object->id : 0);
						if (strpos($filter, '$ID$') !== false && !empty($objectid)) {
							$filter = str_replace('$ID$', (string) $objectid, $filter);
						} elseif (substr($_SERVER["PHP_SELF"], -8) == 'list.php') {
							// In filters of list views, we do not want $ID$ replaced by 0. So we remove the '=' condition.
							// Do nothing if condition is using 'IN' keyword
							// Replace 'column = $ID$' by "word"
							$filter = preg_replace('#\b([a-zA-Z0-9-\.-_]+)\b *= *\$ID\$#', '$1', $filter);
							// Replace '$ID$ = column' by "word"
							$filter = preg_replace('#\$ID\$ *= *\b([a-zA-Z0-9-\.-_]+)\b#', '$1', $filter);
						} else {
							$filter = str_replace('$ID$', '0', $filter);
						}

						// can use filter on any field of object
						if (isset($fieldInfos->object) && is_object($fieldInfos->object)) {
							$object = $fieldInfos->object;
							$tags = [];
							preg_match_all('/\$(.*?)\$/', $filter, $tags);    // Example: $filter is ($dateadh$:<=:CURRENT_DATE)
							foreach ($tags[0] as $keytag => $valuetag) {
								$property = preg_replace('/[^a-z0-9_]/', '', strtolower($tags[1][$keytag]));
								if (strpos($filter, $valuetag) !== false && property_exists($object, $property) && !empty($object->$property)) {
									$filter = str_replace($valuetag, (string) $object->$property, $filter);
								} else {
									$filter = str_replace($valuetag, '0', $filter);
								}
							}
						}

						$errstr = '';
						$sql .= " WHERE " . forgeSQLFromUniversalSearchCriteria($filter, $errstr, 1);
					} else {
						$sql .= ' WHERE 1=1';
					}
					// Some tables may have field, some other not. For the moment we disable it.
					if (in_array($optionsParams['tableName'], array('tablewithentity'))) {
						$sql .= " AND entity = " . ((int) $conf->entity);
					}
					// Manage dependency list (from AJAX)
					if (isset($fieldInfos->optionsSqlDependencyValue)) {
						// TODO rework for dependency with a date or a multiselect
						$sql .= " AND " . $optionsParams['parentField'] . " = '" . $this->db->escape($fieldInfos->optionsSqlDependencyValue) . "'";
					}
					// Only selected values
					if (!empty($selectedValues)) {
						$tmp = "'" . implode("','", array_map(array($this->db, 'escape'), $selectedValues)) . "'";
						$sql .= " AND " . $keyField . " IN (" . $this->db->sanitize($tmp, 1) . ")";
					}

					// Note: $InfoFieldList can be 'sellist:TableName:LabelFieldName[:KeyFieldName[:KeyFieldParent[:Filter[:CategoryIdType[:CategoryIdList[:Sortfield]]]]]]'
					if (preg_match('/^[a-z0-9_\-,]+$/i', $optionsParams['sortField'])) {
						$sql .= $this->db->order($optionsParams['sortField']);
					} else {
						$sql .= $this->db->order(implode(', ', $optionsParams['labelFields']));
					}

					$limit = getDolGlobalInt('MAIN_EXTRAFIELDS_LIMIT_SELLIST_SQL', $fieldInfos->optionsSqlLimit ?? 1000);
					$offset = $fieldInfos->optionsSqlOffset ?? (isset($fieldInfos->optionsSqlPage) ? (((int) $fieldInfos->optionsSqlPage) - 1) * $limit : 0);
					$sql .= $this->db->plimit($limit, $offset);

					dol_syslog(get_class($this) . '::getOptions', LOG_DEBUG);
					$resql = $this->db->query($sql);
					if ($resql) {
						while ($obj = $this->db->fetch_object($resql)) {
							$optionKey = (string) $obj->rowid;

							$toPrint = array();
							foreach ($optionsParams['labelAlias'] as $fieldToShow) {
								$toPrint[] = is_string($obj->$fieldToShow) ? $langs->trans($obj->$fieldToShow) : $obj->$fieldToShow;
							}
							$optionLabel = implode(' ', $toPrint);

							if (empty($optionLabel)) {
								$optionLabel = '(not defined)';
							}

							// Manage dependency list
							$fieldValueParent = !empty($optionsParams['parentName']) && !empty($optionsParams['parentAlias']) ? $optionsParams['parentName'] . ':' . ((string) $obj->{$optionsParams['parentAlias']}) : '';

							$options[$optionKey] = array(
								'id' => $optionKey,
								'label' => $optionLabel,
								'parent' => $fieldValueParent,
							);
						}
						$this->db->free($resql);
					} else {
						$this->error = 'Error in request ' . $sql . ' ' . $this->db->lasterror() . '. Check setup of extra parameters.<br>';
						return null;
					}
				}
			}
			if ($addEmptyValue && (!$fieldInfos->required || count($options) > 1)) {
				// For preserve the numeric key indexes
				$options = array(
					'' => array(
						'id' => '',
						'label' => '&nbsp;',
						'parent' => '',
					)
				) + $options;
			}

			self::$options[$key] = $options;
		}

		$options = self::$options[$key];
		// Only selected values
		if (!empty($selectedValues)) {
			$options = array_intersect_key($options, array_flip($selectedValues));
		}

		return $options;
	}
}
