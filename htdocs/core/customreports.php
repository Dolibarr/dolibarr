<?php
/* Copyright (C) 2020 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * This tool can be included into a list page with
 * define('USE_CUSTOME_REPORT_AS_INCLUDE', 1);
 * include DOL_DOCUMENT_ROOT.'/core/customreports.php';
 */

/**
 *   	\file       htdocs/core/customreports.php
 *		\ingroup    core
 *		\brief      Page to make custom reports
 */

if (!defined('USE_CUSTOME_REPORT_AS_INCLUDE'))
{
	require '../main.inc.php';
	require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
	require_once DOL_DOCUMENT_ROOT."/core/lib/company.lib.php";
	require_once DOL_DOCUMENT_ROOT."/core/class/dolgraph.class.php";
	require_once DOL_DOCUMENT_ROOT."/core/class/doleditor.class.php";

	// Get parameters
	$action     = GETPOST('action', 'aZ09') ?GETPOST('action', 'aZ09') : 'view'; // The action 'add', 'create', 'edit', 'update', 'view', ...
	$massaction = GETPOST('massaction', 'alpha'); // The bulk action (combo box choice into lists)

	$mode = GETPOST('mode', 'alpha') ? GETPOST('mode', 'alpha') : 'graph';
	$objecttype = GETPOST('objecttype', 'aZ09');
	$tabfamily  = GETPOST('tabfamily', 'aZ09');

	if (empty($objecttype)) $objecttype = 'thirdparty';

	$search_filters = GETPOST('search_filters', 'array');
	$search_measures = GETPOST('search_measures', 'array');
	$search_xaxis = GETPOST('search_xaxis', 'array');
	$search_yaxis = GETPOST('search_yaxis', 'array');
	$search_graph = GETPOST('search_graph', 'none');

	// Load variable for pagination
	$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
	$sortfield = GETPOST('sortfield', 'alpha');
	$sortorder = GETPOST('sortorder', 'alpha');
	$page = GETPOST('page', 'int');
	if (empty($page) || $page == -1 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha') || (empty($toselect) && $massaction === '0')) { $page = 0; }     // If $page is not defined, or '' or -1 or if we click on clear filters or if we select empty mass action
	$offset = $limit * $page;
	$pageprev = $page - 1;
	$pagenext = $page + 1;

	$diroutputmassaction = $conf->user->dir_temp.'/'.$user->id.'/customreport';
}

// Load traductions files requiredby by page
$langs->loadLangs(array("companies", "other", "exports"));

$extrafields = new ExtraFields($db);

$hookmanager->initHooks(array('customreport')); // Note that conf->hooks_modules contains array

$title = '';
$picto = '';
$head = array();
$object = null;
$ObjectClassName = '';
// Objects available by default
$arrayoftype = array(
	'thirdparty' => array('label' => 'ThirdParties', 'ObjectClassName' => 'Societe', 'enabled' => $conf->societe->enabled, 'ClassPath' => DOL_DOCUMENT_ROOT."/societe/class/societe.class.php"),
	'contact' => array('label' => 'Contacts', 'ObjectClassName' => 'Contact', 'enabled' => $conf->societe->enabled, 'ClassPath' => DOL_DOCUMENT_ROOT."/contact/class/contact.class.php"),
	'contract' => array('label' => 'Contracts', 'ObjectClassName' => 'Contrat', 'enabled' => $conf->contrat->enabled, 'ClassPath' => DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php", 'langs'=>'contract'),
	'invoice' => array('label' => 'Invoices', 'ObjectClassName' => 'Facture', 'enabled' => $conf->facture->enabled, 'ClassPath' => DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php"),
	'invoice_template'=>array('label' => 'PredefinedInvoices', 'ObjectClassName' => 'FactureRec', 'enabled' => $conf->facture->enabled, 'ClassPath' => DOL_DOCUMENT_ROOT."/compta/class/facturerec.class.php", 'langs'=>'bills'),
	'bom' => array('label' => 'BOM', 'ObjectClassName' => 'Bom', 'enabled' => $conf->bom->enabled),
	'mo' => array('label' => 'MO', 'ObjectClassName' => 'Mo', 'enabled' => $conf->mrp->enabled, 'ClassPath' => DOL_DOCUMENT_ROOT."/mrp/class/mo.class.php"),
	'ticket' => array('label' => 'Ticket', 'ObjectClassName' => 'Ticket', 'enabled' => $conf->ticket->enabled),
	'member' => array('label' => 'Adherent', 'ObjectClassName' => 'Adherent', 'enabled' => $conf->adherent->enabled, 'ClassPath' => DOL_DOCUMENT_ROOT."/adherents/class/adherent.class.php", 'langs'=>'members'),
	'cotisation' => array('label' => 'Subscriptions', 'ObjectClassName' => 'Subscription', 'enabled' => $conf->adherent->enabled, 'ClassPath' => DOL_DOCUMENT_ROOT."/adherents/class/subscription.class.php", 'langs'=>'members'),
);

// Complete $arrayoftype by external modules
$parameters = array('objecttype'=>$objecttype, 'tabfamily'=>$tabfamily);
$reshook = $hookmanager->executeHooks('loadDataForCustomReports', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
elseif (is_array($hookmanager->resArray)) {
	if (!empty($hookmanager->resArray['title'])) {		// Add entries for tabs
		$title = $hookmanager->resArray['title'];
	}
	if (!empty($hookmanager->resArray['picto'])) {		// Add entries for tabs
		$picto = $hookmanager->resArray['picto'];
	}
	if (!empty($hookmanager->resArray['head'])) {		// Add entries for tabs
		$head = array_merge($head, $hookmanager->resArray['head']);
	}
	if (!empty($hookmanager->resArray['arrayoftype'])) {	// Add entries from hook
		foreach ($hookmanager->resArray['arrayoftype'] as $key => $val) {
	        $arrayoftype[$key] = $val;
	    }
	}
	if (!empty($hookmanager->resArray['modenotusedforlist'])) {		// Show objecttype selection even if objecttype is set
		$modenotusedforlist = $hookmanager->resArray['modenotusedforlist'];
	}
}

if ($objecttype) {
    try {
    	if ($arrayoftype[$objecttype]['ClassPath']) {
    		include_once $arrayoftype[$objecttype]['ClassPath'];
    	} else {
    		include_once DOL_DOCUMENT_ROOT."/".$objecttype."/class/".$objecttype.".class.php";
    	}
        $ObjectClassName = $arrayoftype[$objecttype]['ObjectClassName'];
        $object = new $ObjectClassName($db);
    }
    catch (Exception $e) {
        print 'Failed to load class for type '.$objecttype;
    }
}

// Security check
$socid = 0;
if ($user->socid > 0)	// Protection if external user
{
	//$socid = $user->socid;
	accessforbidden();
}
$result = restrictedArea($user, $object->element, 0, '');

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);
//$extrafields->fetch_name_optionals_label($object->table_element_line);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

$search_component_params = array('');


/*
 * Actions
 */





/*
 * View
 */

$form = new Form($db);

if (!defined('USE_CUSTOME_REPORT_AS_INCLUDE')) {
	llxHeader('', $langs->transnoentitiesnoconv('CustomReports'), '');

	dol_fiche_head($head, 'customreports', $title, -1, $picto);
}

// Check parameters
if ($action == 'viewgraph') {
	if (!count($search_measures)) {
		setEventMessages($langs->trans("AtLeastOneMeasureIsRequired"), null, 'warnings');
	} elseif ($mode == 'graph' && count($search_xaxis) > 1) {
		setEventMessages($langs->trans("OnlyOneFieldForXAxisIsPossible"), null, 'warnings');
		$search_xaxis = array(0 => $search_xaxis[0]);
	}
	if (!count($search_xaxis)) {
		setEventMessages($langs->trans("AtLeastOneXAxisIsRequired"), null, 'warnings');
	} elseif ($mode == 'graph' && $search_graph == 'bars' && count($search_measures) > 3) {
		setEventMessages($langs->trans("GraphInBarsAreLimitedTo3Measures"), null, 'warnings');
		$search_graph = 'lines';
	}
}

$tmparray = dol_getdate(dol_now());
$endyear = $tmparray['year'];
$endmonth = $tmparray['mon'];
$datelastday = dol_get_last_day($endyear, $endmonth, 1);
$startyear = $endyear - 2;

$param = '';

$arrayofmesures = array('t.count'=>'Count');
$arrayofxaxis = array();
$arrayofyaxis = array();

print '<form method="post" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="viewgraph">';
print '<input type="hidden" name="tabfamily" value="'.$tabfamily.'">';

print '<div class="liste_titre liste_titre_bydiv centpercent">';

// Select object
print '<div class="divadvancedsearchfield center floatnone">';
print '<div class="inline-block"><span class="opacitymedium">'.$langs->trans("StatisticsOn").'</span></div> ';
$newarrayoftype = array();
foreach ($arrayoftype as $key => $val) {
	if (dol_eval($val['enabled'], 1)) {
		$newarrayoftype[$key] = $arrayoftype[$key];
	}
	if ($val['langs']) {
		$langs->load($val['langs']);
	}
}
print $form->selectarray('objecttype', $newarrayoftype, $objecttype, 0, 0, 0, '', 1, 0, 0, '', '', 1);
if (empty($conf->use_javascript_ajax)) print '<input type="submit" class="button" name="changeobjecttype" value="'.$langs->trans("Refresh").'">';
else {
    print '<script type="text/javascript" language="javascript">
        jQuery(document).ready(function() {
        	jQuery("#objecttype").change(function() {
        		console.log("Reload for "+jQuery("#objecttype").val());
                location.href = "'.$_SERVER["PHP_SELF"].'?objecttype="+jQuery("#objecttype").val()+"'.($tabfamily ? '&tabfamily='.$tabfamily : '').'";
        	});
        });
    </script>';
}
print '</div><div class="clearboth"></div>';

// Add Filter
print '<div class="divadvancedsearchfield quatrevingtpercent">';
print $form->searchComponent(array($object->element => $object->fields), $search_component_params);
print '</div>';

// Measures
print '<div class="divadvancedsearchfield clearboth">';
foreach ($object->fields as $key => $val) {
    if ($val['isameasure']) {
        $arrayofmesures['t.'.$key.'-sum'] = $langs->trans($val['label']).' <span class="opacitymedium">('.$langs->trans("Sum").')</span>';
        $arrayofmesures['t.'.$key.'-average'] = $langs->trans($val['label']).' <span class="opacitymedium">('.$langs->trans("Average").')</span>';
        $arrayofmesures['t.'.$key.'-min'] = $langs->trans($val['label']).' <span class="opacitymedium">('.$langs->trans("Minimum").')</span>';
        $arrayofmesures['t.'.$key.'-max'] = $langs->trans($val['label']).' <span class="opacitymedium">('.$langs->trans("Maximum").')</span>';
    }
}
// Add measure from extrafields
if ($object->isextrafieldmanaged) {
    foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) {
        if (!empty($extrafields->attributes[$object->table_element]['totalizable'][$key])) {
            $arrayofmesures['te.'.$key.'-sum'] = $langs->trans($extrafields->attributes[$object->table_element]['label'][$key]).' <span class="opacitymedium">('.$langs->trans("Sum").')</span>';
            $arrayofmesures['te.'.$key.'-average'] = $langs->trans($extrafields->attributes[$object->table_element]['label'][$key]).' <span class="opacitymedium">('.$langs->trans("Average").')</span>';
            $arrayofmesures['te.'.$key.'-min'] = $langs->trans($extrafields->attributes[$object->table_element]['label'][$key]).' <span class="opacitymedium">('.$langs->trans("Minimum").')</span>';
            $arrayofmesures['te.'.$key.'-max'] = $langs->trans($extrafields->attributes[$object->table_element]['label'][$key]).' <span class="opacitymedium">('.$langs->trans("Maximum").')</span>';
        }
    }
}
print '<div class="inline-block opacitymedium"><span class="fas fa-chart-line paddingright" title="'.$langs->trans("Measures").'"></span>'.$langs->trans("Measures").'</div> ';
print $form->multiselectarray('search_measures', $arrayofmesures, $search_measures, 0, 0, 'minwidth500', 1);
print '</div>';

// XAxis
print '<div class="divadvancedsearchfield">';
foreach ($object->fields as $key => $val) {
    if (!$val['measure']) {
        if (in_array($key, array(
        	'id', 'ref_int', 'ref_ext', 'rowid', 'entity', 'last_main_doc', 'logo', 'logo_squarred', 'extraparams',
        	'parent', 'photo', 'socialnetworks', 'webservices_url', 'webservices_key'))) continue;
        if (isset($val['enabled']) && !dol_eval($val['enabled'], 1)) continue;
        if (isset($val['visible']) && !dol_eval($val['visible'], 1)) continue;
        if (preg_match('/^fk_/', $key) && !preg_match('/^fk_statu/', $key)) continue;
        if (preg_match('/^pass/', $key)) continue;
        if (in_array($val['type'], array('html', 'text'))) continue;
        if (in_array($val['type'], array('timestamp', 'date', 'datetime'))) {
            $arrayofxaxis['t.'.$key.'-year'] = array('label' => $langs->trans($val['label']).' ('.$langs->trans("Year").')', 'position' => $val['position'].'-y');
            $arrayofxaxis['t.'.$key.'-month'] = array('label' => $langs->trans($val['label']).' ('.$langs->trans("Month").')', 'position' => $val['position'].'-m');
            $arrayofxaxis['t.'.$key.'-day'] = array('label' => $langs->trans($val['label']).' ('.$langs->trans("Day").')', 'position' => $val['position'].'-d');
        } else {
            $arrayofxaxis['t.'.$key] = array('label' => $val['label'], 'position' => (int) $val['position']);
        }
    }
    // Add measure from extrafields
    if ($object->isextrafieldmanaged) {
        foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) {
            $arrayofxaxis['te.'.$key] = array('label' => $extrafields->attributes[$object->table_element]['label'][$key], 'position' => (int) $extrafields->attributes[$object->table_element]['pos'][$key]);
        }
    }
}

$arrayofxaxis = dol_sort_array($arrayofxaxis, 'position', 'asc', 1);
$arrayofxaxislabel = array();
foreach ($arrayofxaxis as $key => $val) {
    $arrayofxaxislabel[$key] = $val['label'];
}
print '<div class="inline-block opacitymedium"><span class="fas fa-ruler-horizontal paddingright" title="'.$langs->trans("XAxis").'"></span>'.$langs->trans("XAxis").'</div> ';
print $form->multiselectarray('search_xaxis', $arrayofxaxislabel, $search_xaxis, 0, 0, 'minwidth500', 1);
print '</div>';

// YAxis
if ($mode == 'grid') {
    print '<div class="divadvancedsearchfield">';
    foreach ($object->fields as $key => $val) {
        if (!$val['measure']) {
            if (in_array($key, array('id', 'rowid', 'entity', 'last_main_doc', 'extraparams'))) continue;
            if (preg_match('/^fk_/', $key)) continue;
            if (in_array($val['type'], array('html', 'text'))) continue;
            if (in_array($val['type'], array('timestamp', 'date', 'datetime'))) {
                $arrayofyaxis['t.'.$key.'-year'] = array('label' => $langs->trans($val['label']).' ('.$langs->trans("Year").')', 'position' => $val['position']);
                $arrayofyaxis['t.'.$key.'-month'] = array('label' => $langs->trans($val['label']).' ('.$langs->trans("Month").')', 'position' => $val['position']);
                $arrayofyaxis['t.'.$key.'-day'] = array('label' => $langs->trans($val['label']).' ('.$langs->trans("Day").')', 'position' => $val['position']);
            } else {
                $arrayofyaxis['t.'.$key] = array('label' => $val['label'], 'position' => (int) $val['position']);
            }
        }
        // Add measure from extrafields
        if ($object->isextrafieldmanaged) {
            foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) {
                if (!empty($extrafields->attributes[$object->table_element]['totalizable'][$key])) {
                    $arrayofyaxis['te.'.$key] = array('label' => $extrafields->attributes[$object->table_element]['label'][$key], 'position' => (int) $extrafields->attributes[$object->table_element]['pos'][$key]);
                }
            }
        }
    }
    $arrayofyaxis = dol_sort_array($arrayofyaxis, 'position');
    $arrayofyaxislabel = array();
    foreach ($arrayofyaxis as $key => $val) {
        $arrayofyaxislabel[$key] = $val['label'];
    }
    print '<div class="inline-block opacitymedium"><span class="fas fa-ruler-vertical paddingright" title="'.$langs->trans("YAxis").'"></span>'.$langs->trans("YAxis").'</div> ';
    print $form->multiselectarray('search_yaxis', $arrayofyaxislabel, $search_yaxis, 0, 0, 'minwidth100', 1);
    print '</div>';
}

if ($mode == 'graph') {
    print '<div class="divadvancedsearchfield">';
    $arrayofgraphs = array('bars' => 'Bars', 'lines' => 'Lines'); // also 'pies'
    print '<div class="inline-block opacitymedium"><span class="fas fa-chart-area paddingright" title="'.$langs->trans("Graph").'"></span>'.$langs->trans("Graph").'</div> ';
    print $form->selectarray('search_graph', $arrayofgraphs, $search_graph, 0, 0, 0, 'minwidth100', 1);
    print '</div>';
}
print '<div class="divadvancedsearchfield">';
print '<input type="submit" class="button" value="'.$langs->trans("Refresh").'">';
print '</div>';
print '</div>';
print '</form>';


// Generate the SQL request
$sql = '';
if (!empty($search_measures) && !empty($search_xaxis))
{
    $fieldid = 'rowid';

    $sql = 'SELECT ';
    foreach ($search_xaxis as $key => $val) {
        if (preg_match('/\-year$/', $val)) {
            $tmpval = preg_replace('/\-year$/', '', $val);
            $sql .= 'DATE_FORMAT('.$tmpval.", '%Y') as x_".$key.', ';
        } elseif (preg_match('/\-month$/', $val)) {
            $tmpval = preg_replace('/\-month$/', '', $val);
            $sql .= 'DATE_FORMAT('.$tmpval.", '%Y-%m') as x_".$key.', ';
        } elseif (preg_match('/\-day$/', $val)) {
            $tmpval = preg_replace('/\-day$/', '', $val);
            $sql .= 'DATE_FORMAT('.$tmpval.", '%Y-%m-%d') as x_".$key.', ';
        }
        else $sql .= $val.' as x_'.$key.', ';
    }
    foreach ($search_measures as $key => $val) {
        if ($val == 't.count') $sql .= 'COUNT(t.'.$fieldid.') as y_'.$key.', ';
        elseif (preg_match('/\-sum$/', $val)) {
            $tmpval = preg_replace('/\-sum$/', '', $val);
            $sql .= 'SUM('.$db->ifsql($tmpval.' IS NULL', '0', $tmpval).') as y_'.$key.', ';
        }
        elseif (preg_match('/\-average$/', $val)) {
            $tmpval = preg_replace('/\-average$/', '', $val);
            $sql .= 'AVG('.$db->ifsql($tmpval.' IS NULL', '0', $tmpval).') as y_'.$key.', ';
        }
        elseif (preg_match('/\-min$/', $val)) {
        	$tmpval  = preg_replace('/\-min$/', '', $val);
        	$sql .= 'MIN('.$db->ifsql($tmpval.' IS NULL', '0', $tmpval).') as y_'.$key.', ';
        }
        elseif (preg_match('/\-max$/', $val)) {
        	$tmpval = preg_replace('/\-max$/', '', $val);
        	$sql .= 'MAX('.$db->ifsql($tmpval.' IS NULL', '0', $tmpval).') as y_'.$key.', ';
        }
    }
    $sql = preg_replace('/,\s*$/', '', $sql);
    $sql .= ' FROM '.MAIN_DB_PREFIX.$object->table_element.' as t';
    // Add measure from extrafields
    if ($object->isextrafieldmanaged) {
        $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.$object->table_element.'_extrafields as te ON te.fk_object = t.'.$fieldid;
    }
    if ($object->ismultientitymanaged) {
    	if ($object->ismultientitymanaged == 1) {
    		// Nothing here
    	} else {
    		$tmparray = explode('@', $object->ismultientitymanaged);
    		$sql .= ' INNER JOIN '.MAIN_DB_PREFIX.$tmparray[1].' as parenttable ON t.'.$tmparray[0].' = parenttable.rowid';
    		$sql .= ' AND parenttable.entity IN ('.getEntity($tmparray[1]).')';
    	}
    }
    $sql .= ' WHERE 1 = 1';
    if ($object->ismultientitymanaged == 1) {
   		$sql .= ' AND entity IN ('.getEntity($object->element).')';
    }
    foreach ($search_filters as $key => $val) {
		// TODO
    }
    $sql .= ' GROUP BY ';
    foreach ($search_xaxis as $key => $val) {
        if (preg_match('/\-year$/', $val)) {
            $tmpval = preg_replace('/\-year$/', '', $val);
            $sql .= 'DATE_FORMAT('.$tmpval.", '%Y'), ";
        } elseif (preg_match('/\-month$/', $val)) {
            $tmpval = preg_replace('/\-month$/', '', $val);
            $sql .= 'DATE_FORMAT('.$tmpval.", '%Y-%m'), ";
        } elseif (preg_match('/\-day$/', $val)) {
            $tmpval = preg_replace('/\-day$/', '', $val);
            $sql .= 'DATE_FORMAT('.$tmpval.", '%Y-%m-%d'), ";
        }
        else $sql .= $val.', ';
    }
    $sql = preg_replace('/,\s*$/', '', $sql);
    $sql .= ' ORDER BY ';
    foreach ($search_xaxis as $key => $val) {
        if (preg_match('/\-year$/', $val)) {
            $tmpval = preg_replace('/\-year$/', '', $val);
            $sql .= 'DATE_FORMAT('.$tmpval.", '%Y'), ";
        } elseif (preg_match('/\-month$/', $val)) {
            $tmpval = preg_replace('/\-month$/', '', $val);
            $sql .= 'DATE_FORMAT('.$tmpval.", '%Y-%m'), ";
        } elseif (preg_match('/\-day$/', $val)) {
            $tmpval = preg_replace('/\-day$/', '', $val);
            $sql .= 'DATE_FORMAT('.$tmpval.", '%Y-%m-%d'), ";
        }
        else $sql .= $val.', ';
    }
    $sql = preg_replace('/,\s*$/', '', $sql);
}


$legend = array();
foreach ($search_measures as $key => $val) {
    $legend[] = $langs->trans($arrayofmesures[$val]);
}

// Execute the SQL request
$totalnbofrecord = 0;
$data = array();
if ($sql) {
    $resql = $db->query($sql);
    if (!$resql) {
        dol_print_error($db);
    }

    while ($obj = $db->fetch_object($resql)) {
        // $this->data  = array(array(0=>'labelxA',1=>yA1,...,n=>yAn), array('labelxB',yB1,...yBn));   // or when there is n series to show for each x
        foreach ($search_xaxis as $xkey => $xval) {
            $fieldforxkey = 'x_'.$xkey;
            $xlabel = $obj->$fieldforxkey;
            $xvalwithoutprefix = preg_replace('/^[a-z]+\./', '', $xval);
            if (!empty($object->fields[$xvalwithoutprefix]['arrayofkeyval'])) {
                $xlabel = $object->fields[$xvalwithoutprefix]['arrayofkeyval'][$obj->$fieldforxkey];
            }
            $xarray = array(0 => (($xlabel || $xlabel == '0') ? dol_trunc($xlabel, 20, 'middle') : $langs->trans("NotDefined")));
            foreach ($search_measures as $key => $val) {
                $fieldfory = 'y_'.$key;
                $xarray[] = $obj->$fieldfory;
            }
            $data[] = $xarray;
        }
    }

    $totalnbofrecord = count($data);
}


print '<div class="customreportsoutput'.($totalnbofrecord ? '' : ' customreportsoutputnotdata').'">';


if ($mode == 'grid') {
	// TODO
}

if ($mode == 'graph') {
    $WIDTH = '80%';
    $HEIGHT = 200;

    // Show graph
    $px1 = new DolGraph();
    $mesg = $px1->isGraphKo();
    if (!$mesg)
    {
    	$px1->SetData($data);
    	unset($data);

    	$arrayoftypes = array();
    	foreach ($search_measures as $key => $val) {
    	    $arrayoftypes[] = $search_graph;
    	}

    	$px1->SetLegend($legend);
    	$px1->SetMinValue($px1->GetFloorMinValue());
    	$px1->SetMaxValue($px1->GetCeilMaxValue());
    	$px1->SetWidth($WIDTH);
    	$px1->SetHeight($HEIGHT);
    	$px1->SetYLabel($langs->trans("Y"));
    	$px1->SetShading(3);
    	$px1->SetHorizTickIncrement(1);
    	$px1->SetCssPrefix("cssboxes");
    	$px1->SetType($arrayoftypes);
    	$px1->mode = 'depth';
    	$px1->SetTitle('');

    	$dir = $conf->user->dir_temp;
    	dol_mkdir($dir);
    	$filenamenb = $dir.'/customreport_'.$object->element.'.png';
    	$fileurlnb = DOL_URL_ROOT.'/viewimage.php?modulepart=user&file=customreport_'.$object->element.'.png';

    	$px1->draw($filenamenb, $fileurlnb);

    	print $px1->show($totalnbofrecord ? 0 : $langs->trans("SelectYourGraphOptionsFirst"));
    }
}

if ($sql) {
    // Show admin info
    print '<br>'.info_admin($langs->trans("SQLUsedForExport").':<br> '.$sql, 0, 0, 1, '', 'TechnicalInformation');
}

print '<div>';

if (!defined('USE_CUSTOME_REPORT_AS_INCLUDE')) {
	dol_fiche_end();
}

// End of page
llxFooter();

$db->close();
