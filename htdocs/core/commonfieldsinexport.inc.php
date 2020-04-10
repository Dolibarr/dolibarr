<?php

if (empty($keyforclass) || empty($keyforclassfile) || empty($keyforelement))
{
    //print $keyforclass.' - '.$keyforclassfile.' - '.$keyforelement;
    dol_print_error('', 'include of file commonfieldsinexport.inc.php was done but var $keyforclass or $keyforclassfile or $keyforelement was not set');
    exit;
}
if (empty($keyforalias)) $keyforalias = 't';

dol_include_once($keyforclassfile);
if (class_exists($keyforclass))
{
	$tmpobject = new $keyforclass($this->db);

	// Add common fields
	foreach ($tmpobject->fields as $keyfield => $valuefield)
	{
		$fieldname = $keyforalias.'.'.$keyfield;
		$fieldlabel = ucfirst($valuefield['label']);
		$typeFilter = "Text";
		$typefield = preg_replace('/\(.*$/', '', $valuefield['type']); // double(24,8) -> double
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
			/*
			 * case 'sellist':
			 * $tmp='';
			 * $tmpparam=unserialize($obj->param); // $tmp ay be array 'options' => array 'c_currencies:code_iso:code_iso' => null
			 * if ($tmpparam['options'] && is_array($tmpparam['options'])) {
			 * $tmpkeys=array_keys($tmpparam['options']);
			 * $tmp=array_shift($tmpkeys);
			 * }
			 * if (preg_match('/[a-z0-9_]+:[a-z0-9_]+:[a-z0-9_]+/', $tmp)) $typeFilter="List:".$tmp;
			 * break;
			 */
		}
		$helpfield = '';
		if (!empty($valuefield['help'])) {
			$helpfield = preg_replace('/\(.*$/', '', $valuefield['help']);
		}
		if ($valuefield['enabled']) {
			$this->export_fields_array[$r][$fieldname] = $fieldlabel;
			$this->export_TypeFields_array[$r][$fieldname] = $typeFilter;
			$this->export_entities_array[$r][$fieldname] = $keyforelement;
			$this->export_help_array[$r][$fieldname] = $helpfield;
		}
	}
}
else
{
	dol_print_error($this->db, 'Failed to find class '.$keyforclass.', even after the include of '.$keyforclassfile);
}
// End add common fields
