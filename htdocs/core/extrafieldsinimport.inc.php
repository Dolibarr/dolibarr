<?php
'@phan-var-force DolibarrModules $this';

// $keyforselect = name of main table
// keyforelement = name of picto
// $keyforaliasextra = a key to avoid conflict with extrafields of other objects

if (empty($keyforselect) || empty($keyforelement) || empty($keyforaliasextra)) {
	//print $keyforselet.' - '.$keyforelement.' - '.$keyforaliasextra;
	dol_print_error(null, 'include of file extrafieldsinimport.inc.php was done but var $keyforselect or $keyforelement or $keyforaliasextra was not set');
	exit;
}

// Add extra fields
$sql = "SELECT name, label, type, param, fieldcomputed, fielddefault, fieldrequired FROM ".MAIN_DB_PREFIX."extrafields";
$sql .= " WHERE elementtype = '".$this->db->escape($keyforselect)."' AND type <> 'separate' AND entity IN (0, ".((int) $conf->entity).') ORDER BY pos ASC';
//print $sql;
$resql = $this->db->query($sql);
if ($resql) {    // This can fail when class is used on old database (during migration for example)
	while ($obj = $this->db->fetch_object($resql)) {
		$fieldname = $keyforaliasextra.'.'.$obj->name;
		$fieldlabel = ucfirst($obj->label);
		$typeFilter = "Text";
		$typefield = preg_replace('/\(.*$/', '', $obj->type); // double(24,8) -> double
		switch ($typefield) {
			case 'int':
			case 'integer':
			case 'double':
			case 'price':
				$typeFilter = "Numeric";
				break;
			case 'date':
			case 'datetime':
			case 'timestamp':
				$typeFilter = "Date";
				break;
			case 'boolean':
				$typeFilter = "Boolean";
				break;
			case 'checkbox':
			case 'select':
				if (getDolGlobalString('EXPORT_LABEL_FOR_SELECT')) {
					$tmpparam = jsonOrUnserialize($obj->param); // $tmpparam may be array with 'options' = array(key1=>val1, key2=>val2 ...)
					if ($tmpparam['options'] && is_array($tmpparam['options'])) {
						$typeFilter = "Select:".$obj->param;
					}
				}
				break;
			case 'sellist':
				$tmp = '';
				$tmpparam = jsonOrUnserialize($obj->param); // $tmp may be array 'options' => array 'c_currencies:code_iso:code_iso' => null
				if (is_array($tmpparam) && array_key_exists('options', $tmpparam) &&  $tmpparam['options'] && is_array($tmpparam['options'])) {
					$tmpkeys = array_keys($tmpparam['options']);
					$tmp = array_shift($tmpkeys);
				}
				if (preg_match('/[a-z0-9_]+:[a-z0-9_]+:[a-z0-9_]+/', $tmp)) {
					$typeFilter = "List:".$tmp;
				}
				break;
		}
		if ($obj->type != 'separate') {
			// If not a computed field
			if (empty($obj->fieldcomputed)) {
				$this->import_fields_array[$r][$fieldname] = $fieldlabel.($obj->fieldrequired ? '*' : '');
				$this->import_TypeFields_array[$r][$fieldname] = $typeFilter;
				$this->import_entities_array[$r][$fieldname] = $keyforelement;
			} else {
				// If this is a computed field
				$this->import_fields_array[$r][$fieldname] = $fieldlabel.($obj->fieldrequired ? '*' : '');
				$this->import_TypeFields_array[$r][$fieldname] = $typeFilter.'Compute';
				$this->import_entities_array[$r][$fieldname] = $keyforelement;
			}
		}
	}
}
// End add axtra fields
