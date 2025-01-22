<?php
/* Copyright (C) 2016		Marcos García			<marcosgdf@gmail.com>
 * Copyright (C) 2022   	Open-Dsi				<support@open-dsi.fr>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
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

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobjectline.class.php';
/**
 * Class ProductAttributeValue
 * Used to represent a product attribute value
 */
class ProductAttributeValue extends CommonObjectLine
{
	/**
	 * @var string ID of module.
	 */
	public $module = 'variants';

	/**
	 * @var string ID to identify managed object.
	 */
	public $element = 'productattributevalue';

	/**
	 * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
	 */
	public $table_element = 'product_attribute_value';

	/**
	 *  'type' field format ('integer', 'integer:ObjectClass:PathToClass[:AddCreateButtonOrNot[:Filter]]', 'sellist:TableName:LabelFieldName[:KeyFieldName[:KeyFieldParent[:Filter]]]', 'varchar(x)', 'double(24,8)', 'real', 'price', 'text', 'text:none', 'html', 'date', 'datetime', 'timestamp', 'duration', 'mail', 'phone', 'url', 'password')
	 *         Note: Filter can be a string like "(t.ref:like:'SO-%') or (t.date_creation:<:'20160101') or (t.nature:is:NULL)"
	 *  'label' the translation key.
	 *  'picto' is code of a picto to show before value in forms
	 *  'enabled' is a condition when the field must be managed (Example: 1 or 'getDolGlobalString("MY_SETUP_PARAM")'
	 *  'position' is the sort order of field.
	 *  'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty ('' or 0).
	 *  'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only, 3=Visible on create/update/view form only (not list), 4=Visible on list and update/view form only (not create). 5=Visible on list and view only (not create/not update). Using a negative value means field is not shown by default on list but can be selected for viewing)
	 *  'noteditable' says if field is not editable (1 or 0)
	 *  'default' is a default value for creation (can still be overwrote by the Setup of Default Values if field is editable in creation form). Note: If default is set to '(PROV)' and field is 'ref', the default value will be set to '(PROVid)' where id is rowid when a new record is created.
	 *  'index' if we want an index in database.
	 *  'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommended to name the field fk_...).
	 *  'searchall' is 1 if we want to search in this field when making a search from the quick search button.
	 *  'isameasure' must be set to 1 if you want to have a total on list for this field. Field type must be summable like integer or double(24,8).
	 *  'css' and 'cssview' and 'csslist' is the CSS style to use on field. 'css' is used in creation and update. 'cssview' is used in view mode. 'csslist' is used for columns in lists. For example: 'maxwidth200', 'wordbreak', 'tdoverflowmax200'
	 *  'help' is a 'TranslationString' to use to show a tooltip on field. You can also use 'TranslationString:keyfortooltiponlick' for a tooltip on click.
	 *  'showoncombobox' if value of the field must be visible into the label of the combobox that list record
	 *  'disabled' is 1 if we want to have the field locked by a 'disabled' attribute. In most cases, this is never set into the definition of $fields into class, but is set dynamically by some part of code.
	 *  'arrayofkeyval' to set list of value if type is a list of predefined values. For example: array("0"=>"Draft","1"=>"Active","-1"=>"Cancel")
	 *  'autofocusoncreate' to have field having the focus on a create form. Only 1 field should have this property set to 1.
	 *  'comment' is not used. You can store here any text of your choice. It is not used by application.
	 *
	 *  Note: To have value dynamic, you can set value to 0 in definition and edit the value on the fly into the constructor.
	 */
	/**
	 * @var array<string,array{type:string,label:string,enabled:int<0,2>|string,position:int,notnull?:int,visible:int<-5,5>|string,alwayseditable?:int<0,1>,noteditable?:int<0,1>,default?:string,index?:int,foreignkey?:string,searchall?:int<0,1>,isameasure?:int<0,1>,css?:string,csslist?:string,help?:string,showoncombobox?:int<0,4>,disabled?:int<0,1>,arrayofkeyval?:array<int|string,string>,autofocusoncreate?:int<0,1>,comment?:string,copytoclipboard?:int<1,2>,validate?:int<0,1>,showonheader?:int<0,1>}>  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields = array(
		'rowid' => array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => 1, 'position' => 1, 'notnull' => 1, 'visible' => 0, 'noteditable' => 1, 'index' => 1, 'css' => 'left', 'comment' => "Id"),
		'fk_product_attribute' => array('type' => 'integer:ProductAttribute:variants/class/ProductAttribute.class.php', 'label' => 'ProductAttribute', 'enabled' => 1, 'visible' => 0, 'position' => 10, 'notnull' => 1, 'index' => 1,),
		'ref' => array('type' => 'varchar(255)', 'label' => 'Ref', 'visible' => 1, 'enabled' => 1, 'position' => 20, 'notnull' => 1, 'index' => 1, 'searchall' => 1, 'comment' => "Reference of object", 'css' => ''),
		'value' => array('type' => 'varchar(255)', 'label' => 'Value', 'enabled' => 1, 'position' => 30, 'notnull' => 1, 'visible' => 1, 'searchall' => 1, 'css' => 'minwidth300', 'help' => "", 'showoncombobox' => 1,),
		'position' => array('type' => 'integer', 'label' => 'Rank', 'enabled' => 1, 'visible' => 0, 'default' => '0', 'position' => 200, 'notnull' => 1,),
	);

	/**
	 * ID of the ProductAttributeValue
	 * @var int
	 */
	public $id;

	/**
	 * ID of the parent attribute (ex: ID of the attribute "COLOR")
	 * @var int
	 */
	public $fk_product_attribute;

	/**
	 * Reference of the ProductAttributeValue (ex: "BLUE_1" or "RED_3")
	 * @var string
	 */
	public $ref;

	/**
	 * Label of the ProductAttributeValue (ex: "Dark blue" or "Chili Red")
	 * @var string
	 */
	public $value;

	/**
	 * Sorting position of the ProductAttributeValue
	 * @var int
	 */
	public $position;

	/**
	 * Constructor
	 *
	 * @param   DoliDB $db     Database handler
	 */
	public function __construct(DoliDB $db)
	{
		global $conf, $langs;

		$this->db = $db;

		$this->ismultientitymanaged = 1;
		$this->isextrafieldmanaged = 1;
		$this->entity = $conf->entity;

		if (!getDolGlobalString('MAIN_SHOW_TECHNICAL_ID') && isset($this->fields['rowid'])) {
			$this->fields['rowid']['visible'] = 0;
		}
		if (!isModEnabled('multicompany') && isset($this->fields['entity'])) {
			$this->fields['entity']['enabled'] = 0;
		}

		// Unset fields that are disabled
		foreach ($this->fields as $key => $val) {
			if (isset($val['enabled']) && empty($val['enabled'])) {
				unset($this->fields[$key]);
			}
		}

		// Translate some data of arrayofkeyval
		if (is_object($langs)) {
			foreach ($this->fields as $key => $val) {
				if (!empty($val['arrayofkeyval']) && is_array($val['arrayofkeyval'])) {
					foreach ($val['arrayofkeyval'] as $key2 => $val2) {
						$this->fields[$key]['arrayofkeyval'][$key2] = $langs->trans($val2);
					}
				}
			}
		}
	}

	/**
	 * Creates a value for a product attribute
	 *
	 * @param  User $user      Object user
	 * @param  int  $notrigger Do not execute trigger
	 * @return int Return integer <0 KO >0 OK
	 */
	public function create(User $user, $notrigger = 0)
	{
		global $langs;
		$error = 0;

		// Clean parameters
		$this->fk_product_attribute = $this->fk_product_attribute > 0 ? $this->fk_product_attribute : 0;
		$this->ref = strtoupper(dol_sanitizeFileName(dol_string_nospecial(trim($this->ref)))); // Ref must be uppercase
		$this->value = trim($this->value);

		// Check parameters
		if (empty($this->fk_product_attribute)) {
			$this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("ProductAttribute"));
			$error++;
		}
		if (empty($this->ref)) {
			$this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Ref"));
			$error++;
		}
		if (empty($this->value)) {
			$this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Value"));
			$error++;
		}
		// Position to use
		if (empty($this->position)) {
			$positionmax = $this->getMaxAttributesValuesPosition($this->fk_product_attribute);
			$this->position = $positionmax + 1;
		}
		if ($error) {
			dol_syslog(__METHOD__ . ' ' . $this->errorsToString(), LOG_ERR);
			return -1;
		}

		$this->db->begin();

		$sql = "INSERT INTO " . MAIN_DB_PREFIX . $this->table_element . " (";
		$sql .= " fk_product_attribute, ref, value, entity, position";
		$sql .= ")";
		$sql .= " VALUES (";
		$sql .= "  " . ((int) $this->fk_product_attribute);
		$sql .= ", '" . $this->db->escape($this->ref) . "'";
		$sql .= ", '" . $this->db->escape($this->value) . "'";
		$sql .= ", " . ((int) $this->entity);
		$sql .= ", " . ((int) $this->position);
		$sql .= ")";

		dol_syslog(__METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->errors[] = "Error " . $this->db->lasterror();
			$error++;
		}

		if (!$error) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . $this->table_element);
			$result = $this->insertExtraFields();
			if ($result < 0) {
				$error++;
			}
		}

		if (!$error && !$notrigger) {
			// Call trigger
			$result = $this->call_trigger('PRODUCT_ATTRIBUTE_VALUE_CREATE', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
		}

		if ($error) {
			$this->db->rollback();
			return -1 * $error;
		} else {
			$this->db->commit();
			return $this->id;
		}
	}

	/**
	 * Gets a product attribute value
	 *
	 * @param int $id Product attribute value id
	 * @return int Return integer <0 KO, >0 OK
	 */
	public function fetch($id)
	{
		global $langs;
		$error = 0;

		// Clean parameters
		$id = $id > 0 ? $id : 0;

		// Check parameters
		if (empty($id)) {
			$this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("TechnicalID"));
			$error++;
		}
		if ($error) {
			dol_syslog(__METHOD__ . ' ' . $this->errorsToString(), LOG_ERR);
			return -1;
		}

		$sql = "SELECT rowid, fk_product_attribute, ref, value";
		$sql .= " FROM " . MAIN_DB_PREFIX . $this->table_element;
		$sql .= " WHERE rowid = " . ((int) $id);
		$sql .= " AND entity IN (" . getEntity('product') . ")";

		dol_syslog(__METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->errors[] = "Error " . $this->db->lasterror();
			dol_syslog(__METHOD__ . ' ' . $this->errorsToString(), LOG_ERR);
			return -1;
		}

		$numrows = $this->db->num_rows($resql);
		if ($numrows) {
			$obj = $this->db->fetch_object($resql);

			$this->id = $obj->rowid;
			$this->fk_product_attribute = $obj->fk_product_attribute;
			$this->ref = $obj->ref;
			$this->value = $obj->value;
			$this->fetch_optionals();
		}
		$this->db->free($resql);

		return $numrows;
	}

	/**
	 * Returns all product attribute values of a product attribute
	 *
	 * @param 	int 	$prodattr_id	 	Product attribute id
	 * @param 	bool 	$only_used 			Fetch only used attribute values
	 * @param	int<0,1>	$returnonlydata		0: return object, 1: return only data
	 * @return 	ProductAttributeValue[]|stdClass[]	Array of object
	 */
	public function fetchAllByProductAttribute($prodattr_id, $only_used = false, $returnonlydata = 0)
	{
		$return = array();

		$sql = "SELECT v.fk_product_attribute, v.rowid, v.ref, v.value FROM " . MAIN_DB_PREFIX . "product_attribute_value v ";

		$sql .= "WHERE v.fk_product_attribute = " . ((int) $prodattr_id);

		if ($only_used) {
			$sql .= " AND EXISTS (SELECT c2v.fk_prod_attr_val ";
			$sql .= "FROM " . MAIN_DB_PREFIX . "product_attribute_combination2val c2v ";
			$sql .= "LEFT JOIN " . MAIN_DB_PREFIX . "product_attribute_combination c ON c.rowid = c2v.fk_prod_combination ";
			$sql .= "LEFT JOIN " . MAIN_DB_PREFIX . "product p ON p.rowid = c.fk_product_child";
			$sql .= " WHERE c2v.rowid IS NOT NULL AND p.tosell = 1 AND c2v.fk_prod_attr_val = v.rowid)";
		}

		$sql .= " ORDER BY v.position ASC";

		$query = $this->db->query($sql);

		if ($query) {
			while ($result = $this->db->fetch_object($query)) {
				if (empty($returnonlydata)) {
					$tmp = new ProductAttributeValue($this->db);
				} else {
					$tmp = new stdClass();
				}

				$tmp->fk_product_attribute = $result->fk_product_attribute;
				$tmp->id = $result->rowid;
				$tmp->ref = $result->ref;
				$tmp->value = $result->value;

				$return[] = $tmp;
			}
		}

		return $return;
	}

	/**
	 * Updates a product attribute value
	 *
	 * @param  User	$user	   Object user
	 * @param  int  $notrigger Do not execute trigger
	 * @return int Return integer <0 if KO, >0 if OK
	 */
	public function update(User $user, $notrigger = 0)
	{
		global $langs;
		$error = 0;

		// Clean parameters
		$this->fk_product_attribute = $this->fk_product_attribute > 0 ? $this->fk_product_attribute : 0;
		$this->ref = strtoupper(dol_sanitizeFileName(dol_string_nospecial(trim($this->ref)))); // Ref must be uppercase
		$this->value = trim($this->value);

		// Check parameters
		if (empty($this->fk_product_attribute)) {
			$this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("ProductAttribute"));
			$error++;
		}
		if (empty($this->ref)) {
			$this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Ref"));
			$error++;
		}
		if (empty($this->value)) {
			$this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Value"));
			$error++;
		}
		if ($error) {
			dol_syslog(__METHOD__ . ' ' . $this->errorsToString(), LOG_ERR);
			return -1;
		}

		$this->db->begin();

		$sql = "UPDATE " . MAIN_DB_PREFIX . $this->table_element . " SET";

		$sql .= "  fk_product_attribute = " . ((int) $this->fk_product_attribute);
		$sql .= ", ref = '" . $this->db->escape($this->ref) . "'";
		$sql .= ", value = '" . $this->db->escape($this->value) . "'";
		$sql .= ", position = " . ((int) $this->position);

		$sql .= " WHERE rowid = " . ((int) $this->id);

		dol_syslog(__METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->errors[] = "Error " . $this->db->lasterror();
			$error++;
		}

		if (!$error && !$notrigger) {
			// Call trigger
			$result = $this->call_trigger('PRODUCT_ATTRIBUTE_VALUE_MODIFY', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
		}

		if (!$error) {
			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			return -1 * $error;
		}
	}

	/**
	 * Deletes a product attribute value
	 *
	 * @param  User $user      Object user
	 * @param  int  $notrigger Do not execute trigger
	 * @return int Return integer <0 KO, >0 OK
	 */
	public function delete(User $user, $notrigger = 0)
	{
		global $langs;
		$error = 0;

		// Clean parameters
		$this->id = $this->id > 0 ? $this->id : 0;

		// Check parameters
		if (empty($this->id)) {
			$this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("TechnicalID"));
			$error++;
		}
		if ($error) {
			dol_syslog(__METHOD__ . ' ' . $this->errorsToString(), LOG_ERR);
			return -1;
		}

		$result = $this->isUsed();
		if ($result < 0) {
			return -1;
		} elseif ($result > 0) {
			$this->errors[] = $langs->trans('ErrorAttributeValueIsUsedIntoProduct');
			return -1;
		}

		$this->db->begin();

		if (!$error && !$notrigger) {
			// Call trigger
			$result = $this->call_trigger('PRODUCT_ATTRIBUTE_VALUE_DELETE', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
		}

		if (!$error) {
			$sql = "DELETE FROM " . MAIN_DB_PREFIX . $this->table_element . " WHERE rowid = " . ((int) $this->id);

			dol_syslog(__METHOD__, LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				$this->errors[] = "Error " . $this->db->lasterror();
				$error++;
			}
		}
		if (!$error) {
			$result = $this->insertExtraFields();
			if ($result < 0) {
				$error++;
			}
		}
		if (!$error) {
			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			return -1 * $error;
		}
	}

	/**
	 * Get max value used for position of attributes
	 * @param int $fk_product_attribute Id of attribute
	 *
	 * @return int  Max value of position in table of attributes
	 */
	public function getMaxAttributesValuesPosition($fk_product_attribute)
	{
		// Search the last position of attributes
		$sql = "SELECT max(position) FROM " . MAIN_DB_PREFIX . $this->table_element;
		$sql .= " WHERE entity IN (" . getEntity('product') . ")";
		$sql .= ' AND fk_product_attribute='.(int) $fk_product_attribute;
		dol_syslog(__METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$row = $this->db->fetch_row($resql);
			return $row[0];
		}
		return 0;
	}

	/**
	 * Test if used by a product
	 *
	 * @return int Return integer <0 KO, =0 if No, =1 if Yes
	 */
	public function isUsed()
	{
		global $langs;
		$error = 0;

		// Clean parameters
		$this->id = $this->id > 0 ? $this->id : 0;

		// Check parameters
		if (empty($this->id)) {
			$this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("TechnicalID"));
			$error++;
		}
		if ($error) {
			dol_syslog(__METHOD__ . ' ' . $this->errorsToString(), LOG_ERR);
			return -1;
		}

		$sql = "SELECT COUNT(*) AS nb FROM " . MAIN_DB_PREFIX . "product_attribute_combination2val WHERE fk_prod_attr_val = " . ((int) $this->id);

		dol_syslog(__METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->errors[] = "Error " . $this->db->lasterror();
			return -1;
		}

		$used = 0;
		if ($obj = $this->db->fetch_object($resql)) {
			$used = $obj->nb;
		}

		return $used ? 1 : 0;
	}
}
