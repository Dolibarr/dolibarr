<?php

if (empty($keyforselect) || empty($keyforelement) || empty($keyforaliasextra))
{
    //print $keyforselet.' - '.$keyforelement.' - '.$keyforaliasextra;
    dol_print_error('', 'include of file extrafieldsinexport.inc.php was done but var $keyforselect or $keyforelement or $keyforaliasextra was not set');
    exit;
}

// Add extra fields
$sql="SELECT name, label, type, param FROM ".MAIN_DB_PREFIX."extrafields WHERE elementtype = '".$keyforselect."' AND type != 'separate' AND entity IN (0, ".$conf->entity.')';
//print $sql;
$resql=$this->db->query($sql);
if ($resql)    // This can fail when class is used on old database (during migration for example)
{
	while ($obj=$this->db->fetch_object($resql))
	{
		$fieldname=$keyforaliasextra.'.'.$obj->name;
		$fieldlabel=ucfirst($obj->label);
		$typeFilter="Text";
		switch($obj->type)
		{
			case 'int':
			case 'double':
			case 'price':
				$typeFilter="Numeric";
				break;
			case 'date':
			case 'datetime':
				$typeFilter="Date";
				break;
			case 'boolean':
				$typeFilter="Boolean";
				break;
			case 'sellist':
				$tmp='';
				$tmpparam=unserialize($obj->param);	// $tmp ay be array 'options' => array 'c_currencies:code_iso:code_iso' => null
				if ($tmpparam['options'] && is_array($tmpparam['options'])) {
					$tmpkeys=array_keys($tmpparam['options']);
					$tmp=array_shift($tmpkeys);
				}
				if (preg_match('/[a-z0-9_]+:[a-z0-9_]+:[a-z0-9_]+/', $tmp)) $typeFilter="List:".$tmp;
				break;
		}
		if ($obj->type!='separate') {
			$this->export_fields_array[$r][$fieldname]=$fieldlabel;
			$this->export_TypeFields_array[$r][$fieldname]=$typeFilter;
			$this->export_entities_array[$r][$fieldname]=$keyforelement;
		}
	}
}
// End add axtra fields
