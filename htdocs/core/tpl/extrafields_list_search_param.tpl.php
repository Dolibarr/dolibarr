<?php

// Protection to avoid direct call of template
if (empty($conf) || !is_object($conf))
{
	print "Error, template page can't be called as URL";
	exit;
}

// Loop to complete $param for extrafields
if (!empty($search_array_options) && is_array($search_array_options))	// $extrafieldsobject is the $object->table_element like 'societe', 'socpeople', ...
{
    if (empty($search_options_pattern)) $search_options_pattern = 'search_options_';

    foreach ($search_array_options as $key => $val)
    {
        $crit = $val;
        $tmpkey = preg_replace('/'.$search_options_pattern.'/', '', $key);
		if (is_array($val) && array_key_exists('start', $val) && array_key_exists('end', $val)) {
			// date range from list filters is stored as array('start' => <timestamp>, 'end' => <timestamp>)
			// start date
			$param .= '&' . $search_options_pattern.$tmpkey.'_startyear='  . intval(dol_print_date($val['start'], '%Y'));
			$param .= '&' . $search_options_pattern.$tmpkey.'_startmonth=' . intval(dol_print_date($val['start'], '%m'));
			$param .= '&' . $search_options_pattern.$tmpkey.'_startday='   . intval(dol_print_date($val['start'], '%d'));
			$param .= '&' . $search_options_pattern.$tmpkey.'_starthour='  . intval(dol_print_date($val['start'], '%H'));
			$param .= '&' . $search_options_pattern.$tmpkey.'_startmin='   . intval(dol_print_date($val['start'], '%M'));
			// end date
			$param .= '&' . $search_options_pattern.$tmpkey.'_endyear='  . intval(dol_print_date($val['end'], '%Y'));
			$param .= '&' . $search_options_pattern.$tmpkey.'_endmonth=' . intval(dol_print_date($val['end'], '%m'));
			$param .= '&' . $search_options_pattern.$tmpkey.'_endday='   . intval(dol_print_date($val['end'], '%d'));
			$param .= '&' . $search_options_pattern.$tmpkey.'_endhour='  . intval(dol_print_date($val['end'], '%H'));
			$param .= '&' . $search_options_pattern.$tmpkey.'_endmin='   . intval(dol_print_date($val['end'], '%M'));
		}
        if ($val != '') $param .= '&'.$search_options_pattern.$tmpkey.'='.urlencode($val);
    }
}
