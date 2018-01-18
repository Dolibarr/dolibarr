<?php

// Protection to avoid direct call of template
if (empty($conf) || ! is_object($conf))
{
	print "Error, template page can't be called as URL";
	exit;
}

// Loop to complete the sql search criterias from extrafields
foreach ($search_array_options as $key => $val)
{
	$crit=$val;
	$tmpkey=preg_replace('/search_options_/','',$key);
	$typ=$extrafields->attribute_type[$tmpkey];
	$mode_search=0;
	if (in_array($typ, array('int','double','real'))) $mode_search=1;								// Search on a numeric
	if (in_array($typ, array('sellist','link','chkbxlst','checkbox')) && $crit != '0' && $crit != '-1') $mode_search=2;	// Search on a foreign key int
	if ($crit != '' && (! in_array($typ, array('select','sellist')) || $crit != '0') && (! in_array($typ, array('link')) || $crit != '-1'))
	{
		$sql .= natural_search('ef.'.$tmpkey, $crit, $mode_search);
	}
}