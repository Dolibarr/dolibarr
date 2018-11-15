<?php

// Protection to avoid direct call of template
if (empty($conf) || ! is_object($conf))
{
	print "Error, template page can't be called as URL";
	exit;
}

// Loop to complete $param for extrafields
if (! empty($search_array_options))	// $extrafieldsobject is the $object->table_element like 'societe', 'socpeople', ...
{
foreach ($search_array_options as $key => $val)
{
	$crit=$val;
	$tmpkey=preg_replace('/search_options_/','',$key);
	if ($val != '') $param.='&search_options_'.$tmpkey.'='.urlencode($val);
}
}
