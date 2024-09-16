<?php
/* Copyright (C) 2020-2024 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
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
 * \file       htdocs/core/customreports.php
 * \ingroup    core
 * \brief      Page to make custom reports. Page can also be used alone or as a tab among other tabs of an object
 *
 * To include this tool into another PHP page:
 * define('USE_CUSTOM_REPORT_AS_INCLUDE', 1);
 * define('MAIN_DO_NOT_USE_JQUERY_MULTISELECT', 1);
 * define('MAIN_CUSTOM_REPORT_KEEP_GRAPH_ONLY', 1);	// TODO Use a variable
 * $SHOWLEGEND = 0;
 * $search_xaxis = array('t.column');
 * $customreportkey='abc';
 * include DOL_DOCUMENT_ROOT.'/core/customreports.php';
 */

if (!defined('USE_CUSTOM_REPORT_AS_INCLUDE')) {
	require '../main.inc.php';

	// Get parameters
	$action     = GETPOST('action', 'aZ09') ? GETPOST('action', 'aZ09') : 'view'; // The action 'add', 'create', 'edit', 'update', 'view', ...
	$massaction = GETPOST('massaction', 'alpha'); // The bulk action (combo box choice into lists)

	$mode = GETPOST('mode', 'alpha');
	$objecttype = GETPOST('objecttype', 'aZ09arobase');
	$tabfamily  = GETPOST('tabfamily', 'aZ09');

	$search_measures = GETPOST('search_measures', 'array');

	//$search_xaxis = GETPOST('search_xaxis', 'array');
	if (GETPOST('search_xaxis', 'alpha') && GETPOST('search_xaxis', 'alpha') != '-1') {
		$search_xaxis = array(GETPOST('search_xaxis', 'alpha'));
	} else {
		$search_xaxis = array();
	}
	//$search_groupby = GETPOST('search_groupby', 'array');
	if (GETPOST('search_groupby', 'alpha') && GETPOST('search_groupby', 'alpha') != '-1') {
		$search_groupby = array(GETPOST('search_groupby', 'alpha'));
	} else {
		$search_groupby = array();
	}

	$search_yaxis = GETPOST('search_yaxis', 'array');
	$search_graph = GETPOST('search_graph', 'restricthtml');

	// Load variable for pagination
	$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
	$sortfield = GETPOST('sortfield', 'aZ09comma');
	$sortorder = GETPOST('sortorder', 'aZ09comma');
	$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
	if (empty($page) || $page == -1 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha') || (empty($toselect) && $massaction === '0')) {
		$page = 0;
	}     // If $page is not defined, or '' or -1 or if we click on clear filters or if we select empty mass action
	$offset = $limit * $page;
	$pageprev = $page - 1;
	$pagenext = $page + 1;

	$diroutputmassaction = $conf->user->dir_temp.'/'.$user->id.'/customreport';

	$object = null;
} else {
	$langs->load("main");
	// $search_measures, $search_xaxis or $search_yaxis may have been defined by the parent.

	if (empty($user) || empty($user->id)) {
		print 'Page is called as an include but $user and its permission loaded with loadRights() are not defined. We stop here.';
		exit(-1);
	}
	if (empty($object)) {
		print 'Page is called as an include but $object is not defined. We stop here.';
		exit(-1);
	}
}

if (empty($mode)) {
	$mode = 'graph';
}
if (!isset($search_measures)) {
	$search_measures = array(0 => 't.count');
}
if (!empty($object)) {
	$objecttype = $object->element.($object->module ? '@'.$object->module : '');
}
if (empty($objecttype)) {
	$objecttype = 'thirdparty';
}

require_once DOL_DOCUMENT_ROOT."/core/class/extrafields.class.php";
require_once DOL_DOCUMENT_ROOT."/core/class/html.form.class.php";
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT."/core/lib/company.lib.php";
require_once DOL_DOCUMENT_ROOT."/core/lib/date.lib.php";
require_once DOL_DOCUMENT_ROOT."/core/lib/customreports.lib.php";
require_once DOL_DOCUMENT_ROOT."/core/class/dolgraph.class.php";
require_once DOL_DOCUMENT_ROOT."/core/class/doleditor.class.php";
require_once DOL_DOCUMENT_ROOT."/core/class/html.formother.class.php";

// Load traductions files requiredby by page
$langs->loadLangs(array("companies", "other", "exports", "sendings"));

$extrafields = new ExtraFields($db);

$hookmanager->initHooks(array('customreport')); // Note that conf->hooks_modules contains array

$title = '';
$picto = '';
$head = array();
$ObjectClassName = '';
// Objects available by default
$arrayoftype = array(
	'thirdparty' => array('langs' => 'companies', 'label' => 'ThirdParties', 'picto' => 'company', 'ObjectClassName' => 'Societe', 'enabled' => isModEnabled('societe'), 'ClassPath' => "/societe/class/societe.class.php"),
	'contact' => array('label' => 'Contacts', 'picto' => 'contact', 'ObjectClassName' => 'Contact', 'enabled' => isModEnabled('societe'), 'ClassPath' => "/contact/class/contact.class.php"),
	'proposal' => array('label' => 'Proposals', 'picto' => 'proposal', 'ObjectClassName' => 'Propal', 'enabled' => isModEnabled('propal'), 'ClassPath' => "/comm/propal/class/propal.class.php"),
	'order' => array('label' => 'Orders', 'picto' => 'order', 'ObjectClassName' => 'Commande', 'enabled' => isModEnabled('order'), 'ClassPath' => "/commande/class/commande.class.php"),
	'invoice' => array('langs' => 'facture', 'label' => 'Invoices', 'picto' => 'bill', 'ObjectClassName' => 'Facture', 'enabled' => isModEnabled('invoice'), 'ClassPath' => "/compta/facture/class/facture.class.php"),
	'invoice_template' => array('label' => 'PredefinedInvoices', 'picto' => 'bill', 'ObjectClassName' => 'FactureRec', 'enabled' => isModEnabled('invoice'), 'ClassPath' => "/compta/class/facturerec.class.php", 'langs' => 'bills'),
	'contract' => array('label' => 'Contracts', 'picto' => 'contract', 'ObjectClassName' => 'Contrat', 'enabled' => isModEnabled('contract'), 'ClassPath' => "/contrat/class/contrat.class.php", 'langs' => 'contracts'),
	'contractdet' => array('label' => 'ContractLines', 'picto' => 'contract', 'ObjectClassName' => 'ContratLigne', 'enabled' => isModEnabled('contract'), 'ClassPath' => "/contrat/class/contrat.class.php", 'langs' => 'contracts'),
	'bom' => array('label' => 'BOM', 'picto' => 'bom', 'ObjectClassName' => 'Bom', 'enabled' => isModEnabled('bom')),
	'mrp' => array('label' => 'MO', 'picto' => 'mrp', 'ObjectClassName' => 'Mo', 'enabled' => isModEnabled('mrp'), 'ClassPath' => "/mrp/class/mo.class.php"),
	'ticket' => array('label' => 'Ticket', 'picto' => 'ticket', 'ObjectClassName' => 'Ticket', 'enabled' => isModEnabled('ticket')),
	'member' => array('label' => 'Adherent', 'picto' => 'member', 'ObjectClassName' => 'Adherent', 'enabled' => isModEnabled('member'), 'ClassPath' => "/adherents/class/adherent.class.php", 'langs' => 'members'),
	'cotisation' => array('label' => 'Subscriptions', 'picto' => 'member', 'ObjectClassName' => 'Subscription', 'enabled' => isModEnabled('member'), 'ClassPath' => "/adherents/class/subscription.class.php", 'langs' => 'members'),
);


// Complete $arrayoftype by external modules
$parameters = array('objecttype' => $objecttype, 'tabfamily' => $tabfamily);
$reshook = $hookmanager->executeHooks('loadDataForCustomReports', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
} elseif (is_array($hookmanager->resArray)) {
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
}

// Load the main $object for statistics
if ($objecttype) {
	try {
		if (!empty($arrayoftype[$objecttype]['ClassPath'])) {
			$fileforclass = $arrayoftype[$objecttype]['ClassPath'];
		} else {
			$fileforclass = "/".$objecttype."/class/".$objecttype.".class.php";
		}
		dol_include_once($fileforclass);

		$ObjectClassName = $arrayoftype[$objecttype]['ObjectClassName'];
		if (!empty($ObjectClassName)) {
			if (class_exists($ObjectClassName)) {
				$object = new $ObjectClassName($db);
			} else {
				print 'Failed to load class for type '.$objecttype.'. Class file found but Class object named '.$ObjectClassName.' not found.';
			}
		} else {
			print 'Failed to load class for type '.$objecttype.'. Class file name is unknown.';
		}
	} catch (Exception $e) {
		print 'Failed to load class for type '.$objecttype.'. Class path not found.';
	}
}

'@phan-var-force CommonObject $object';

// Security check
$socid = 0;
if ($user->socid > 0) {	// Protection if external user
	//$socid = $user->socid;
	accessforbidden('Access forbidden to external users');
}

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label('all');	// We load all extrafields definitions for all objects
//$extrafields->fetch_name_optionals_label($object->table_element_line);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

$search_component_params = array('');
$search_component_params_hidden = trim(GETPOST('search_component_params_hidden', 'alphanohtml'));
$search_component_params_input = trim(GETPOST('search_component_params_input', 'alphanohtml'));
//var_dump($search_component_params_hidden);
//var_dump($search_component_params_input);

// If string is not an universal filter string, we try to convert it into universal filter syntax string
$errorstr = '';
forgeSQLFromUniversalSearchCriteria($search_component_params_input, $errorstr);	// Try conversion UFS->SQL
//var_dump($errorstr);
if ($errorstr) {
	$value = $search_component_params_input;

	$value = preg_replace('/([a-z\.]+)\s*([!<>=]+|in|notin|like|notlike)\s*/', '\1:\2:', $value); // Clean string 'x < 10' into 'x:<:10' so we can then explode on space to get all AND tests to do
	$value = preg_replace('/\s*\|\s*/', '|', $value);
	//var_dump($value);

	$crits = explode(' ', trim($value)); // the string after the name of the field. Explode on each AND
	$res = '';

	$i1 = 0;	// count the nb of and criteria added (all fields / criteria)
	foreach ($crits as $crit) {		// Loop on each AND criteria
		$crit = trim($crit);

		$i2 = 0;	// count the nb of valid criteria added for this first criteria
		$newres = '';
		$tmpcrits = explode('|', $crit);
		$i3 = 0;	// count the nb of valid criteria added for this current field
		foreach ($tmpcrits as $tmpcrit) {
			if ($tmpcrit !== '0' && empty($tmpcrit)) {
				continue;
			}
			$tmpcrit = trim($tmpcrit);
			//var_dump($tmpcrit);

			$errorstr = '';
			$parenthesislevel = 0;
			$rescheckfilter = dolCheckFilters($tmpcrit, $errorstr, $parenthesislevel);
			if ($rescheckfilter) {
				while ($parenthesislevel > 0) {
					$tmpcrit = preg_replace('/^\(/', '', preg_replace('/\)$/', '', $tmpcrit));
					$parenthesislevel--;
				}
			}

			$field = preg_replace('/(:[!<>=\s]+:|:in:|:notin:|:like:|:notlike:).*$/', '', $tmpcrit);		// the name of the field
			$tmpcrit = preg_replace('/^.*(:[!<>=\s]+:|:in:|:notin:|:like:|:notlike:)/', '\1', $tmpcrit);	// the condition after the name of the field
			//var_dump($field); var_dump($tmpcrit); var_dump($i3);

			$newres .= (($i2 > 0 || $i3 > 0) ? ' OR ' : '');

			$operator = '=';
			$newcrit = preg_replace('/(:[!<>=\s]+:|:in:|:notin:|:like:|:notlike:)/', '', $tmpcrit);
			//var_dump($newcrit);

			$reg = array();
			preg_match('/:([!<>=\s]+|in|notin|like|notlike):/', $tmpcrit, $reg);
			if (!empty($reg[1])) {
				$operator = $reg[1];
			}
			if ($newcrit != '') {
				if (!preg_match('/^\'[^\']*\'$/', $newcrit)) {
					$numnewcrit = price2num($newcrit);
					$newres .= '('.$field.':'.$operator.':'.((float) $numnewcrit).')';
				} else {
					$newres .= '('.$field.':'.$operator.":".((string) $newcrit).')';
				}
				$i3++; // a criteria was added to string
			}
		}
		$i2++; // a criteria for 1 more field was added to string

		if ($newres) {
			$res = $res.($res ? ' AND ' : '').($i2 > 1 ? '(' : '').$newres.($i2 > 1 ? ')' : '');
		}
		$i1++;
	}
	$res = "(".$res.")";

	//var_dump($res);exit;
	$search_component_params_input = $res;
}

$arrayofandtagshidden = dolForgeExplodeAnd($search_component_params_hidden);
$arrayofandtagsinput = dolForgeExplodeAnd($search_component_params_input);

$search_component_params_hidden = implode(' AND ', array_merge($arrayofandtagshidden, $arrayofandtagsinput));
//var_dump($search_component_params_hidden);

$MAXUNIQUEVALFORGROUP = 20;
$MAXMEASURESINBARGRAPH = 20;
$SHOWLEGEND = (isset($SHOWLEGEND) ? $SHOWLEGEND : 1);

$YYYY = substr($langs->trans("Year"), 0, 1).substr($langs->trans("Year"), 0, 1).substr($langs->trans("Year"), 0, 1).substr($langs->trans("Year"), 0, 1);
$MM = substr($langs->trans("Month"), 0, 1).substr($langs->trans("Month"), 0, 1);
$DD = substr($langs->trans("Day"), 0, 1).substr($langs->trans("Day"), 0, 1);
$HH = substr($langs->trans("Hour"), 0, 1).substr($langs->trans("Hour"), 0, 1);
$MI = substr($langs->trans("Minute"), 0, 1).substr($langs->trans("Minute"), 0, 1);
$SS = substr($langs->trans("Second"), 0, 1).substr($langs->trans("Second"), 0, 1);

$arrayofmesures = array();
$arrayofxaxis = array();
$arrayofgroupby = array();
$arrayofyaxis = array();
$arrayofvaluesforgroupby = array();

$features = $object->element;
if (!empty($object->element_for_permission)) {
	$features = $object->element_for_permission;
} else {
	$features .= (empty($object->module) ? '' : '@'.$object->module);
}

restrictedArea($user, $features, 0, '');

$error = 0;


/*
 * Actions
 */

// None



/*
 * View
 */

$form = new Form($db);
$formother = new FormOther($db);

if (!defined('USE_CUSTOM_REPORT_AS_INCLUDE')) {
	llxHeader('', $langs->transnoentitiesnoconv('CustomReports'), '');

	if (empty($head)) {
		print dol_get_fiche_head($head, 'customreports', $title, -2, $picto);
	} else {
		print dol_get_fiche_head($head, 'customreports', $title, -1, $picto);
	}
}

$newarrayoftype = array();
foreach ($arrayoftype as $key => $val) {
	if (dol_eval($val['enabled'], 1, 1, '1')) {
		$newarrayoftype[$key] = $arrayoftype[$key];
	}
	if (!empty($val['langs'])) {
		$langs->load($val['langs']);
	}
}

$count = 0;
$arrayofmesures = fillArrayOfMeasures($object, 't', $langs->trans($newarrayoftype[$objecttype]['label']), $arrayofmesures, 0, $count);
$arrayofmesures = dol_sort_array($arrayofmesures, 'position', 'asc', 0, 0, 1);

$count = 0;
$arrayofxaxis = fillArrayOfXAxis($object, 't', $langs->trans($newarrayoftype[$objecttype]['label']), $arrayofxaxis, 0, $count);
$arrayofxaxis = dol_sort_array($arrayofxaxis, 'position', 'asc', 0, 0, 1);

$count = 0;
$arrayofgroupby = fillArrayOfGroupBy($object, 't', $langs->trans($newarrayoftype[$objecttype]['label']), $arrayofgroupby, 0, $count);
$arrayofgroupby = dol_sort_array($arrayofgroupby, 'position', 'asc', 0, 0, 1);


// Check parameters
if ($action == 'viewgraph') {
	if (!count($search_measures)) {
		setEventMessages($langs->trans("AtLeastOneMeasureIsRequired"), null, 'warnings');
	} elseif ($mode == 'graph' && count($search_xaxis) > 1) {
		setEventMessages($langs->trans("OnlyOneFieldForXAxisIsPossible"), null, 'warnings');
		$search_xaxis = array(0 => $search_xaxis[0]);
	}
	if (count($search_groupby) >= 2) {
		setEventMessages($langs->trans("ErrorOnlyOneFieldForGroupByIsPossible"), null, 'warnings');
		$search_groupby = array(0 => $search_groupby[0]);
	}
	if (!count($search_xaxis)) {
		setEventMessages($langs->trans("AtLeastOneXAxisIsRequired"), null, 'warnings');
	} elseif ($mode == 'graph' && $search_graph == 'bars' && count($search_measures) > $MAXMEASURESINBARGRAPH) {
		$langs->load("errors");
		setEventMessages($langs->trans("GraphInBarsAreLimitedToNMeasures", $MAXMEASURESINBARGRAPH), null, 'warnings');
		$search_graph = 'lines';
	}
}

// Get all possible values of fields when a 'group by' is set, and save this into $arrayofvaluesforgroupby
// $arrayofvaluesforgroupby will be used to forge lael of each grouped series
if (is_array($search_groupby) && count($search_groupby)) {
	$fieldtocount = '';
	foreach ($search_groupby as $gkey => $gval) {
		$gvalwithoutprefix = preg_replace('/^[a-z]+\./', '', $gval);

		if (preg_match('/\-year$/', $search_groupby[$gkey])) {
			$tmpval = preg_replace('/\-year$/', '', $search_groupby[$gkey]);
			$fieldtocount .= 'DATE_FORMAT('.$tmpval.", '%Y')";
		} elseif (preg_match('/\-month$/', $search_groupby[$gkey])) {
			$tmpval = preg_replace('/\-month$/', '', $search_groupby[$gkey]);
			$fieldtocount .= 'DATE_FORMAT('.$tmpval.", '%Y-%m')";
		} elseif (preg_match('/\-day$/', $search_groupby[$gkey])) {
			$tmpval = preg_replace('/\-day$/', '', $search_groupby[$gkey]);
			$fieldtocount .= 'DATE_FORMAT('.$tmpval.", '%Y-%m-%d')";
		} else {
			$fieldtocount = $search_groupby[$gkey];
		}

		$sql = "SELECT DISTINCT ".$fieldtocount." as val";

		if (strpos($fieldtocount, 'te') === 0) {
			$tabletouse = $object->table_element;
			$tablealiastouse = 'te';
			if (!empty($arrayofgroupby[$gval])) {
				$tmpval = explode('.', $gval);
				$tabletouse = $arrayofgroupby[$gval]['table'];
				$tablealiastouse = $tmpval[0];
			}
			//var_dump($tablealiastouse);exit;

			//$sql .= " FROM ".MAIN_DB_PREFIX.$object->table_element."_extrafields as te";
			$sql .= " FROM ".MAIN_DB_PREFIX.$tabletouse."_extrafields as ".$tablealiastouse;
		} else {
			$tabletouse = $object->table_element;
			$tablealiastouse = 't';
			if (!empty($arrayofgroupby[$gval])) {
				$tmpval = explode('.', $gval);
				$tabletouse = $arrayofgroupby[$gval]['table'];
				$tablealiastouse = $tmpval[0];
			}
			$sql .= " FROM ".MAIN_DB_PREFIX.$tabletouse." as ".$tablealiastouse;
		}

		// Add a where here keeping only the criteria on $tabletouse
		/* TODO
		if ($search_component_params_hidden) {
			$errormessage = '';
			$sql .= forgeSQLFromUniversalSearchCriteria($search_component_params_hidden, $errormessage);
		}
		*/

		$sql .= " LIMIT ".((int) ($MAXUNIQUEVALFORGROUP + 1));

		//print $sql;
		$resql = $db->query($sql);
		if (!$resql) {
			dol_print_error($db);
		}

		while ($obj = $db->fetch_object($resql)) {
			if (is_null($obj->val)) {
				$keytouse = '__NULL__';
				$valuetranslated = $langs->transnoentitiesnoconv("NotDefined");
			} elseif ($obj->val === '') {
				$keytouse = '';
				$valuetranslated = $langs->transnoentitiesnoconv("Empty");
			} else {
				$keytouse = (string) $obj->val;
				$valuetranslated = $obj->val;
			}

			$regs = array();
			if (!empty($object->fields[$gvalwithoutprefix]['arrayofkeyval'])) {
				$valuetranslated = $object->fields[$gvalwithoutprefix]['arrayofkeyval'][$obj->val];
				if (is_null($valuetranslated)) {
					$valuetranslated = $langs->transnoentitiesnoconv("UndefinedKey");
				}
				$valuetranslated = $langs->trans($valuetranslated);
			} elseif (preg_match('/integer:([^:]+):([^:]+)$/', $object->fields[$gvalwithoutprefix]['type'], $regs)) {
				$classname = $regs[1];
				$classpath = $regs[2];
				dol_include_once($classpath);
				if (class_exists($classname)) {
					$tmpobject = new $classname($db);
					$tmpobject->fetch($obj->val);
					foreach ($tmpobject->fields as $fieldkey => $field) {
						if ($field['showoncombobox']) {
							$valuetranslated = $tmpobject->$fieldkey;
							//if ($valuetranslated == '-') $valuetranslated = $langs->transnoentitiesnoconv("Unknown")
							break;
						}
					}
					//$valuetranslated = $tmpobject->ref.'eee';
				}
			}

			$arrayofvaluesforgroupby['g_'.$gkey][$keytouse] = $valuetranslated;
		}
		// Add also the possible NULL value if field is a parent field that is not a strict join
		$tmpfield = explode('.', $gval);
		if ($tmpfield[0] != 't' || (is_array($object->fields[$tmpfield[1]]) && empty($object->fields[$tmpfield[1]]['notnull']))) {
			dol_syslog("The group by field ".$gval." may be null (because field is null or it is a left join), so we add __NULL__ entry in list of possible values");
			//var_dump($gval); var_dump($object->fields);
			$arrayofvaluesforgroupby['g_'.$gkey]['__NULL__'] = $langs->transnoentitiesnoconv("NotDefined");
		}

		if (is_array($arrayofvaluesforgroupby['g_'.$gkey])) {
			asort($arrayofvaluesforgroupby['g_'.$gkey]);
		}

		// Add a protection/error to refuse the request if number of differentr values for the group by is higher than $MAXUNIQUEVALFORGROUP
		if (is_array($arrayofvaluesforgroupby['g_'.$gkey]) && count($arrayofvaluesforgroupby['g_'.$gkey]) > $MAXUNIQUEVALFORGROUP) {
			$langs->load("errors");

			if (strpos($fieldtocount, 'te') === 0) {			// This is a field of an extrafield
				//if (!empty($extrafields->attributes[$object->table_element]['langfile'][$gvalwithoutprefix])) {
				//      $langs->load($extrafields->attributes[$object->table_element]['langfile'][$gvalwithoutprefix]);
				//}
				$keyforlabeloffield = $extrafields->attributes[$object->table_element]['label'][$gvalwithoutprefix];
				$labeloffield = $langs->transnoentitiesnoconv($keyforlabeloffield);
			} elseif (strpos($fieldtocount, 't__') === 0) {		// This is a field of a foreign key
				$reg = array();
				if (preg_match('/^(.*)\.(.*)/', $gvalwithoutprefix, $reg)) {
					/*
					$gvalwithoutprefix = preg_replace('/\..*$/', '', $gvalwithoutprefix);
					$gvalwithoutprefix = preg_replace('/^t__/', '', $gvalwithoutprefix);
					$keyforlabeloffield = $object->fields[$gvalwithoutprefix]['label'];
					$labeloffield = $langs->transnoentitiesnoconv($keyforlabeloffield).'-'.$reg[2];
					*/
					$labeloffield = $arrayofgroupby[$fieldtocount]['labelnohtml'];
				} else {
					$labeloffield = $langs->transnoentitiesnoconv($keyforlabeloffield);
				}
			} else {											// This is a common field
				$reg = array();
				if (preg_match('/^(.*)\-(year|month|day)/', $gvalwithoutprefix, $reg)) {
					$gvalwithoutprefix = preg_replace('/\-(year|month|day)/', '', $gvalwithoutprefix);
					$keyforlabeloffield = $object->fields[$gvalwithoutprefix]['label'];
					$labeloffield = $langs->transnoentitiesnoconv($keyforlabeloffield).'-'.$reg[2];
				} else {
					$keyforlabeloffield = $object->fields[$gvalwithoutprefix]['label'];
					$labeloffield = $langs->transnoentitiesnoconv($keyforlabeloffield);
				}
			}
			//var_dump($object->fields);
			setEventMessages($langs->trans("ErrorTooManyDifferentValueForSelectedGroupBy", $MAXUNIQUEVALFORGROUP, $labeloffield), null, 'warnings');
			$search_groupby = array();
		}

		$db->free($resql);
	}
}
//var_dump($arrayofvaluesforgroupby);exit;


$tmparray = dol_getdate(dol_now());
$endyear = $tmparray['year'];
$endmonth = $tmparray['mon'];
$datelastday = dol_get_last_day($endyear, $endmonth, 1);
$startyear = $endyear - 2;

$param = '';


if (!defined('MAIN_CUSTOM_REPORT_KEEP_GRAPH_ONLY')) {
	print '<form method="post" action="'.$_SERVER['PHP_SELF'].'" autocomplete="off">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="viewgraph">';
	print '<input type="hidden" name="tabfamily" value="'.$tabfamily.'">';

	$viewmode = '';

	$viewmode .= '<div class="divadvancedsearchfield">';
	$arrayofgraphs = array('bars' => 'Bars', 'lines' => 'Lines'); // also 'pies'
	$viewmode .= '<div class="inline-block opacitymedium"><span class="fas fa-chart-area paddingright" title="'.$langs->trans("Graph").'"></span>'.$langs->trans("Graph").'</div> ';
	$viewmode .= $form->selectarray('search_graph', $arrayofgraphs, $search_graph, 0, 0, 0, '', 1, 0, 0, '', 'graphtype width100');
	$viewmode .= '</div>';

	$num = 0;
	$massactionbutton = '';
	$nav = '';
	$newcardbutton = '';
	$limit = 0;

	print_barre_liste('', $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, -1, 'object_action', 0, $nav.'<span class="marginleftonly"></span>'.$newcardbutton, '', $limit, 1, 0, 1, $viewmode);


	foreach ($newarrayoftype as $tmpkey => $tmpval) {
		$newarrayoftype[$tmpkey]['label'] = img_picto('', $tmpval['picto'], 'class="pictofixedwidth"').$langs->trans($tmpval['label']);
	}

	print '<div class="liste_titre liste_titre_bydiv liste_titre_bydiv_inlineblock centpercent">';

	// Select object
	print '<div class="divadvancedsearchfield center floatnone">';
	print '<div class="inline-block"><span class="opacitymedium">'.$langs->trans("StatisticsOn").'</span></div> ';
	print $form->selectarray('objecttype', $newarrayoftype, $objecttype, 0, 0, 0, '', 1, 0, 0, '', 'minwidth200', 1, '', 0, 1);
	if (empty($conf->use_javascript_ajax)) {
		print '<input type="submit" class="button buttongen button-save nomargintop" name="changeobjecttype" value="'.$langs->trans("Refresh").'">';
	} else {
		print '<!-- js code to reload page with good object type -->
		<script nonce="'.getNonce().'" type="text/javascript">
	        jQuery(document).ready(function() {
	        	jQuery("#objecttype").change(function() {
	        		console.log("Reload for "+jQuery("#objecttype").val());
	                location.href = "'.$_SERVER["PHP_SELF"].'?objecttype="+jQuery("#objecttype").val()+"'.($tabfamily ? '&tabfamily='.urlencode($tabfamily) : '').(GETPOSTINT('show_search_component_params_hidden') ? '&show_search_component_params_hidden='.((int) GETPOSTINT('show_search_component_params_hidden')) : '').'";
	        	});
	        });
	    </script>';
	}
	print '</div><div class="clearboth"></div>';

	// Filter (you can use param &show_search_component_params_hidden=1 for debug)
	if (!empty($object)) {
		print '<div class="divadvancedsearchfield">';
		print $form->searchComponent(array($object->element => $object->fields), $search_component_params, array(), $search_component_params_hidden);
		print '</div>';
	}

	// YAxis (add measures into array)
	$count = 0;
	//var_dump($arrayofmesures);
	print '<div class="divadvancedsearchfield clearboth">';
	print '<div class="inline-block"><span class="fas fa-ruler-combined paddingright pictofixedwidth" title="'.dol_escape_htmltag($langs->trans("Measures")).'"></span><span class="fas fa-caret-left caretleftaxis" title="'.dol_escape_htmltag($langs->trans("Measures")).'"></span></div>';
	$simplearrayofmesures = array();
	foreach ($arrayofmesures as $key => $val) {
		$simplearrayofmesures[$key] = $arrayofmesures[$key]['label'];
	}
	print $form->multiselectarray('search_measures', $simplearrayofmesures, $search_measures, 0, 0, 'minwidth300', 1, 0, '', '', $langs->trans("Measures"));	// Fill the array $arrayofmeasures with possible fields
	print '</div>';

	// XAxis
	$count = 0;
	print '<div class="divadvancedsearchfield">';
	print '<div class="inline-block"><span class="fas fa-ruler-combined paddingright pictofixedwidth" title="'.dol_escape_htmltag($langs->trans("XAxis")).'"></span><span class="fas fa-caret-down caretdownaxis" title="'.dol_escape_htmltag($langs->trans("XAxis")).'"></span></div>';
	//var_dump($arrayofxaxis);
	print $formother->selectXAxisField($object, $search_xaxis, $arrayofxaxis, $langs->trans("XAxis"), 'minwidth300 maxwidth400');	// Fill the array $arrayofxaxis with possible fields
	print '</div>';

	// Group by
	$count = 0;
	print '<div class="divadvancedsearchfield">';
	print '<div class="inline-block opacitymedium"><span class="fas fa-ruler-horizontal paddingright pictofixedwidth" title="'.dol_escape_htmltag($langs->trans("GroupBy")).'"></span></div>';
	print $formother->selectGroupByField($object, $search_groupby, $arrayofgroupby, 'minwidth250 maxwidth300', $langs->trans("GroupBy"));	// Fill the array $arrayofgroupby with possible fields
	print '</div>';


	if ($mode == 'grid') {
		// YAxis
		print '<div class="divadvancedsearchfield">';
		foreach ($object->fields as $key => $val) {
			if (empty($val['measure']) && (!isset($val['enabled']) || dol_eval($val['enabled'], 1, 1, '1'))) {
				if (in_array($key, array('id', 'rowid', 'entity', 'last_main_doc', 'extraparams'))) {
					continue;
				}
				if (preg_match('/^fk_/', $key)) {
					continue;
				}
				if (in_array($val['type'], array('html', 'text'))) {
					continue;
				}
				if (in_array($val['type'], array('timestamp', 'date', 'datetime'))) {
					$arrayofyaxis['t.'.$key.'-year'] = array(
						'label' => $langs->trans($val['label']).' ('.$YYYY.')',
						'position' => $val['position'],
						'table' => $object->table_element
					);
					$arrayofyaxis['t.'.$key.'-month'] = array(
						'label' => $langs->trans($val['label']).' ('.$YYYY.'-'.$MM.')',
						'position' => $val['position'],
						'table' => $object->table_element
					);
					$arrayofyaxis['t.'.$key.'-day'] = array(
						'label' => $langs->trans($val['label']).' ('.$YYYY.'-'.$MM.'-'.$DD.')',
						'position' => $val['position'],
						'table' => $object->table_element
					);
				} else {
					$arrayofyaxis['t.'.$key] = array(
						'label' => $val['label'],
						'position' => (int) $val['position'],
						'table' => $object->table_element
					);
				}
			}
		}
		// Add measure from extrafields
		if ($object->isextrafieldmanaged) {
			foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) {
				if (!empty($extrafields->attributes[$object->table_element]['totalizable'][$key]) && (!isset($extrafields->attributes[$object->table_element]['enabled'][$key]) || dol_eval((string) $extrafields->attributes[$object->table_element]['enabled'][$key], 1, 1, '1'))) {
					$arrayofyaxis['te.'.$key] = array(
						'label' => $extrafields->attributes[$object->table_element]['label'][$key],
						'position' => (int) $extrafields->attributes[$object->table_element]['pos'][$key],
						'table' => $object->table_element
					);
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
		//
	}

	print '<div class="divadvancedsearchfield">';
	print '<input type="submit" class="button buttongen button-save nomargintop" value="'.$langs->trans("Refresh").'">';
	print '</div>';
	print '</div>';
	print '</form>';
}

// Generate the SQL request
$sql = '';
if (!empty($search_measures) && !empty($search_xaxis)) {
	$errormessage = '';

	$fieldid = 'rowid';

	$sql = "SELECT ";
	foreach ($search_xaxis as $key => $val) {
		if (preg_match('/\-year$/', $val)) {
			$tmpval = preg_replace('/\-year$/', '', $val);
			$sql .= "DATE_FORMAT(".$tmpval.", '%Y') as x_".$key.', ';
		} elseif (preg_match('/\-month$/', $val)) {
			$tmpval = preg_replace('/\-month$/', '', $val);
			$sql .= "DATE_FORMAT(".$tmpval.", '%Y-%m') as x_".$key.', ';
		} elseif (preg_match('/\-day$/', $val)) {
			$tmpval = preg_replace('/\-day$/', '', $val);
			$sql .= "DATE_FORMAT(".$tmpval.", '%Y-%m-%d') as x_".$key.', ';
		} else {
			$sql .= $val." as x_".$key.", ";
		}
	}
	if (!empty($search_groupby)) {
		foreach ($search_groupby as $key => $val) {
			if (preg_match('/\-year$/', $val)) {
				$tmpval = preg_replace('/\-year$/', '', $val);
				$sql .= "DATE_FORMAT(".$tmpval.", '%Y') as g_".$key.', ';
			} elseif (preg_match('/\-month$/', $val)) {
				$tmpval = preg_replace('/\-month$/', '', $val);
				$sql .= "DATE_FORMAT(".$tmpval.", '%Y-%m') as g_".$key.', ';
			} elseif (preg_match('/\-day$/', $val)) {
				$tmpval = preg_replace('/\-day$/', '', $val);
				$sql .= "DATE_FORMAT(".$tmpval.", '%Y-%m-%d') as g_".$key.', ';
			} else {
				$sql .= $val." as g_".$key.", ";
			}
		}
	}
	foreach ($search_measures as $key => $val) {
		if ($val == 't.count') {
			$sql .= "COUNT(t.".$fieldid.") as y_".$key.', ';
		} elseif (preg_match('/\-sum$/', $val)) {
			$tmpval = preg_replace('/\-sum$/', '', $val);
			$sql .= "SUM(".$db->ifsql($tmpval.' IS NULL', '0', $tmpval).") as y_".$key.", ";
		} elseif (preg_match('/\-average$/', $val)) {
			$tmpval = preg_replace('/\-average$/', '', $val);
			$sql .= "AVG(".$db->ifsql($tmpval.' IS NULL', '0', $tmpval).") as y_".$key.", ";
		} elseif (preg_match('/\-min$/', $val)) {
			$tmpval = preg_replace('/\-min$/', '', $val);
			$sql .= "MIN(".$db->ifsql($tmpval.' IS NULL', '0', $tmpval).") as y_".$key.", ";
		} elseif (preg_match('/\-max$/', $val)) {
			$tmpval = preg_replace('/\-max$/', '', $val);
			$sql .= "MAX(".$db->ifsql($tmpval.' IS NULL', '0', $tmpval).") as y_".$key.", ";
		}
	}
	$sql = preg_replace('/,\s*$/', '', $sql);
	$sql .= " FROM ".MAIN_DB_PREFIX.$object->table_element." as t";
	// Add measure from extrafields
	if ($object->isextrafieldmanaged) {
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$object->table_element."_extrafields as te ON te.fk_object = t.".$fieldid;
	}
	// Add table for link on multientity
	if ($object->ismultientitymanaged) {	// 0=No test on entity, 1=Test with field entity, 'field@table'=Test with link by field@table
		if ($object->ismultientitymanaged == 1) {
			// No table to add here
		} else {
			$tmparray = explode('@', $object->ismultientitymanaged);
			$sql .= " INNER JOIN ".MAIN_DB_PREFIX.$tmparray[1]." as parenttableforentity ON t.".$tmparray[0]." = parenttableforentity.rowid";
			$sql .= " AND parenttableforentity.entity IN (".getEntity($tmparray[1]).")";
		}
	}

	// Init the list of tables added. We include by default always the main table.
	$listoftablesalreadyadded = array($object->table_element => $object->table_element);

	// Add LEFT JOIN for all parent tables mentioned into the Xaxis
	//var_dump($arrayofxaxis); var_dump($search_xaxis);
	foreach ($search_xaxis as $key => $val) {
		if (!empty($arrayofxaxis[$val])) {
			$tmpval = explode('.', $val);
			//var_dump($arrayofgroupby);
			$tmpforloop = dolExplodeIntoArray($arrayofxaxis[$val]['tablefromt'], ',');
			foreach ($tmpforloop as $tmptable => $tmptablealias) {
				if (! in_array($tmptable, $listoftablesalreadyadded)) {	// We do not add join for main table and tables already added
					$tmpforexplode = explode('__', $tmptablealias);
					$endpart = end($tmpforexplode);
					$parenttableandfield = preg_replace('/__'.$endpart.'$/', '', $tmptablealias).'.'.$endpart;

					$sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$tmptable." as ".$db->sanitize($tmptablealias)." ON ".$db->sanitize($parenttableandfield)." = ".$db->sanitize($tmptablealias).".rowid";
					$listoftablesalreadyadded[$tmptable] = $tmptable;

					if (preg_match('/^te/', $tmpval[0]) && preg_replace('/^t_/', 'te_', $tmptablealias) == $tmpval[0]) {
						$sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$tmptable."_extrafields as ".$db->sanitize($tmpval[0])." ON ".$db->sanitize($tmpval[0]).".fk_object = ".$db->sanitize($tmptablealias).".rowid";
						$listoftablesalreadyadded[$tmptable] = $tmptable;
					}
				}
			}
		} else {
			$errormessage = 'Found a key into search_xaxis not found into arrayofxaxis';
		}
	}

	// Add LEFT JOIN for all parent tables mentioned into the Group by
	//var_dump($arrayofgroupby); var_dump($search_groupby);
	foreach ($search_groupby as $key => $val) {
		if (!empty($arrayofgroupby[$val])) {
			$tmpval = explode('.', $val);
			//var_dump($arrayofgroupby[$val]); var_dump($tmpval);
			$tmpforloop = dolExplodeIntoArray($arrayofgroupby[$val]['tablefromt'], ',');
			foreach ($tmpforloop as $tmptable => $tmptablealias) {
				if (! in_array($tmptable, $listoftablesalreadyadded)) {	// We do not add join for main table and tables already added
					$tmpforexplode = explode('__', $tmptablealias);
					$endpart = end($tmpforexplode);
					$parenttableandfield = preg_replace('/__'.$endpart.'$/', '', $tmptablealias).'.'.$endpart;

					$sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$tmptable." as ".$db->sanitize($tmptablealias)." ON ".$db->sanitize($parenttableandfield)." = ".$db->sanitize($tmptablealias).".rowid";
					$listoftablesalreadyadded[$tmptable] = $tmptable;

					if (preg_match('/^te/', $tmpval[0]) && preg_replace('/^t_/', 'te_', $tmptablealias) == $tmpval[0]) {
						$sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$tmptable."_extrafields as ".$db->sanitize($tmpval[0])." ON ".$db->sanitize($tmpval[0]).".fk_object = ".$db->sanitize($tmptablealias).".rowid";
						$listoftablesalreadyadded[$tmptable] = $tmptable;
					}
				}
			}
		} else {
			$errormessage = 'Found a key into search_groupby not found into arrayofgroupby';
		}
	}

	// Add LEFT JOIN for all parent tables mentioned into the Yaxis
	//var_dump($arrayofgroupby); var_dump($search_groupby);
	foreach ($search_measures as $key => $val) {
		if (!empty($arrayofmesures[$val])) {
			$tmpval = explode('.', $val);
			//var_dump($arrayofgroupby);
			$tmpforloop = dolExplodeIntoArray($arrayofmesures[$val]['tablefromt'], ',');
			foreach ($tmpforloop as $tmptable => $tmptablealias) {
				if (! in_array($tmptable, $listoftablesalreadyadded)) {	// We do not add join for main table and tables already added
					$tmpforexplode = explode('__', $tmptablealias);
					$endpart = end($tmpforexplode);
					$parenttableandfield = preg_replace('/__'.$endpart.'$/', '', $tmptablealias).'.'.$endpart;

					$sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$tmptable." as ".$db->sanitize($tmptablealias)." ON ".$db->sanitize($parenttableandfield)." = ".$db->sanitize($tmptablealias).".rowid";
					$listoftablesalreadyadded[$tmptable] = $tmptable;

					if (preg_match('/^te/', $tmpval[0]) && preg_replace('/^t_/', 'te_', $tmptablealias) == $tmpval[0]) {
						$sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$tmptable."_extrafields as ".$db->sanitize($tmpval[0])." ON ".$db->sanitize($tmpval[0]).".fk_object = ".$db->sanitize($tmptablealias).".rowid";
						$listoftablesalreadyadded[$tmptable] = $tmptable;
					}
				}
			}
		} else {
			$errormessage = 'Found a key into search_measures not found into arrayofmesures';
		}
	}

	$sql .= " WHERE 1 = 1";
	if ($object->ismultientitymanaged == 1) {	// 0=No test on entity, 1=Test with field entity, 'field@table'=Test with link by field@table
		$sql .= " AND t.entity IN (".getEntity($object->element).")";
	}
	// Add the where here
	$sqlfilters = $search_component_params_hidden;
	if ($sqlfilters) {
		$sql .= forgeSQLFromUniversalSearchCriteria($sqlfilters, $errormessage, 0, 0, 1);
	}
	$sql .= " GROUP BY ";
	foreach ($search_xaxis as $key => $val) {
		if (preg_match('/\-year$/', $val)) {
			$tmpval = preg_replace('/\-year$/', '', $val);
			$sql .= "DATE_FORMAT(".$tmpval.", '%Y'), ";
		} elseif (preg_match('/\-month$/', $val)) {
			$tmpval = preg_replace('/\-month$/', '', $val);
			$sql .= "DATE_FORMAT(".$tmpval.", '%Y-%m'), ";
		} elseif (preg_match('/\-day$/', $val)) {
			$tmpval = preg_replace('/\-day$/', '', $val);
			$sql .= "DATE_FORMAT(".$tmpval.", '%Y-%m-%d'), ";
		} else {
			$sql .= $val.", ";
		}
	}
	if (!empty($search_groupby)) {
		foreach ($search_groupby as $key => $val) {
			if (preg_match('/\-year$/', $val)) {
				$tmpval = preg_replace('/\-year$/', '', $val);
				$sql .= "DATE_FORMAT(".$tmpval.", '%Y'), ";
			} elseif (preg_match('/\-month$/', $val)) {
				$tmpval = preg_replace('/\-month$/', '', $val);
				$sql .= "DATE_FORMAT(".$tmpval.", '%Y-%m'), ";
			} elseif (preg_match('/\-day$/', $val)) {
				$tmpval = preg_replace('/\-day$/', '', $val);
				$sql .= "DATE_FORMAT(".$tmpval.", '%Y-%m-%d'), ";
			} else {
				$sql .= $val.', ';
			}
		}
	}
	$sql = preg_replace('/,\s*$/', '', $sql);
	$sql .= ' ORDER BY ';
	foreach ($search_xaxis as $key => $val) {
		if (preg_match('/\-year$/', $val)) {
			$tmpval = preg_replace('/\-year$/', '', $val);
			$sql .= "DATE_FORMAT(".$tmpval.", '%Y'), ";
		} elseif (preg_match('/\-month$/', $val)) {
			$tmpval = preg_replace('/\-month$/', '', $val);
			$sql .= "DATE_FORMAT(".$tmpval.", '%Y-%m'), ";
		} elseif (preg_match('/\-day$/', $val)) {
			$tmpval = preg_replace('/\-day$/', '', $val);
			$sql .= "DATE_FORMAT(".$tmpval.", '%Y-%m-%d'), ";
		} else {
			$sql .= $val.', ';
		}
	}
	if (!empty($search_groupby)) {
		foreach ($search_groupby as $key => $val) {
			if (preg_match('/\-year$/', $val)) {
				$tmpval = preg_replace('/\-year$/', '', $val);
				$sql .= "DATE_FORMAT(".$tmpval.", '%Y'), ";
			} elseif (preg_match('/\-month$/', $val)) {
				$tmpval = preg_replace('/\-month$/', '', $val);
				$sql .= "DATE_FORMAT(".$tmpval.", '%Y-%m'), ";
			} elseif (preg_match('/\-day$/', $val)) {
				$tmpval = preg_replace('/\-day$/', '', $val);
				$sql .= "DATE_FORMAT(".$tmpval.", '%Y-%m-%d'), ";
			} else {
				$sql .= $val.', ';
			}
		}
	}
	$sql = preg_replace('/,\s*$/', '', $sql);

	// Can overwrite the SQL with a custom SQL string (when used as an include)
	if (!empty($customsql)) {
		$sql = $customsql;
	}
}
//print $sql;

if ($errormessage) {
	print '<div class="warning">';
	print dol_escape_htmltag($errormessage);
	//print '<br>'.dol_escape_htmltag('SQL is '.$sql);
	print '</div>';
	$sql = '';
}

$legend = array();
foreach ($search_measures as $key => $val) {
	$legend[] = $langs->trans($arrayofmesures[$val]['label']);
}

$useagroupby = (is_array($search_groupby) && count($search_groupby));
//var_dump($useagroupby);
//var_dump($arrayofvaluesforgroupby);

// Execute the SQL request
$totalnbofrecord = 0;
$data = array();
if ($sql) {
	$resql = $db->query($sql);
	if (!$resql) {
		print '<div class="warning">';
		print dol_escape_htmltag($db->lasterror());
		//print '<br>'.dol_escape_htmltag('SQL is '.$sql);
		print '</div>';
	} else {
		$ifetch = 0;
		$xi = 0;
		$oldlabeltouse = '';
		while ($obj = $db->fetch_object($resql)) {
			$ifetch++;
			if ($useagroupby) {
				$xval = $search_xaxis[0];
				$fieldforxkey = 'x_0';
				$xlabel = $obj->$fieldforxkey;
				$xvalwithoutprefix = preg_replace('/^[a-z]+\./', '', $xval);

				// Define $xlabel
				if (!empty($object->fields[$xvalwithoutprefix]['arrayofkeyval'])) {
					$xlabel = $object->fields[$xvalwithoutprefix]['arrayofkeyval'][$obj->$fieldforxkey];
				}
				$labeltouse = (($xlabel || $xlabel == '0') ? dol_trunc($xlabel, 20, 'middle') : ($xlabel === '' ? $langs->transnoentitiesnoconv("Empty") : $langs->transnoentitiesnoconv("NotDefined")));

				if ($oldlabeltouse !== '' && ($labeltouse != $oldlabeltouse)) {
					$xi++; // Increase $xi
				}
				//var_dump($labeltouse.' '.$oldlabeltouse.' '.$xi);
				$oldlabeltouse = $labeltouse;

				/* Example of value for $arrayofvaluesforgroupby
				 * array (size=1)
				 *	  'g_0' =>
				 *	    array (size=6)
				 *	      0 => string '0' (length=1)
				 *	      '' => string 'Empty' (length=5)
				 *	      '__NULL__' => string 'Not defined' (length=11)
				 *	      'done' => string 'done' (length=4)
				 *	      'processing' => string 'processing' (length=10)
				 *	      'undeployed' => string 'undeployed' (length=10)
				 */
				foreach ($search_measures as $key => $val) {
					$gi = 0;
					foreach ($search_groupby as $gkey) {
						//var_dump('*** Fetch #'.$ifetch.' for labeltouse='.$labeltouse.' measure number '.$key.' and group g_'.$gi);
						//var_dump($arrayofvaluesforgroupby);
						foreach ($arrayofvaluesforgroupby['g_'.$gi] as $gvaluepossiblekey => $gvaluepossiblelabel) {
							$ykeysuffix = $gvaluepossiblelabel;
							$gvalwithoutprefix = preg_replace('/^[a-z]+\./', '', $gval);

							$fieldfory = 'y_'.$key;
							$fieldforg = 'g_'.$gi;
							$fieldforybis = 'y_'.$key.'_'.$ykeysuffix;
							//var_dump('gvaluepossiblekey='.$gvaluepossiblekey.' gvaluepossiblelabel='.$gvaluepossiblelabel.' ykeysuffix='.$ykeysuffix.' gval='.$gval.' gvalwithoutsuffix='.$gvalwithoutprefix);
							//var_dump('fieldforg='.$fieldforg.' obj->$fieldforg='.$obj->$fieldforg.' fieldfory='.$fieldfory.' obj->$fieldfory='.$obj->$fieldfory.' fieldforybis='.$fieldforybis);

							if (!is_array($data[$xi])) {
								$data[$xi] = array();
							}

							if (!array_key_exists('label', $data[$xi])) {
								$data[$xi] = array();
								$data[$xi]['label'] = $labeltouse;
							}

							$objfieldforg = $obj->$fieldforg;
							if (is_null($objfieldforg)) {
								$objfieldforg = '__NULL__';
							}

							if ($gvaluepossiblekey == '0') {	// $gvaluepossiblekey can have type int or string. So we create a special if, used when value is '0'
								//var_dump($objfieldforg.' == \'0\' -> '.($objfieldforg == '0'));
								if ($objfieldforg == '0') {
									// The record we fetch is for this group
									$data[$xi][$fieldforybis] = $obj->$fieldfory;
								} elseif (!isset($data[$xi][$fieldforybis])) {
									// The record we fetch is not for this group
									$data[$xi][$fieldforybis] = '0';
								}
							} else {
								//var_dump((string) $objfieldforg.' === '.(string) $gvaluepossiblekey.' -> '.((string) $objfieldforg === (string) $gvaluepossiblekey));
								if ((string) $objfieldforg === (string) $gvaluepossiblekey) {
									// The record we fetch is for this group
									$data[$xi][$fieldforybis] = $obj->$fieldfory;
								} elseif (!isset($data[$xi][$fieldforybis])) {
									// The record we fetch is not for this group
									$data[$xi][$fieldforybis] = '0';
								}
							}
						}
						//var_dump($data[$xi]);
						$gi++;
					}
				}
			} else {	// No group by
				$xval = $search_xaxis[0];
				$fieldforxkey = 'x_0';
				$xlabel = $obj->$fieldforxkey;
				$xvalwithoutprefix = preg_replace('/^[a-z]+\./', '', $xval);

				// Define $xlabel
				if (!empty($object->fields[$xvalwithoutprefix]['arrayofkeyval'])) {
					$xlabel = $object->fields[$xvalwithoutprefix]['arrayofkeyval'][$obj->$fieldforxkey];
				}

				$labeltouse = (($xlabel || $xlabel == '0') ? dol_trunc($xlabel, 20, 'middle') : ($xlabel === '' ? $langs->transnoentitiesnoconv("Empty") : $langs->transnoentitiesnoconv("NotDefined")));
				$xarrayforallseries = array('label' => $labeltouse);
				foreach ($search_measures as $key => $val) {
					$fieldfory = 'y_'.$key;
					$xarrayforallseries[$fieldfory] = $obj->$fieldfory;
				}
				$data[$xi] = $xarrayforallseries;
				$xi++;
			}
		}

		$totalnbofrecord = count($data);
	}
}
//var_dump($data);

print '<!-- Section to show the result -->'."\n";
print '<div class="customreportsoutput'.($totalnbofrecord ? '' : ' customreportsoutputnotdata').'">';


if ($mode == 'grid') {
	// TODO
}

if ($mode == 'graph') {
	$WIDTH = '80%';
	$HEIGHT = (empty($_SESSION['dol_screenheight']) ? 400 : $_SESSION['dol_screenheight'] - 500);

	// Show graph
	$px1 = new DolGraph();
	$mesg = $px1->isGraphKo();
	if (!$mesg) {
		//var_dump($legend);
		//var_dump($data);
		$px1->SetData($data);
		unset($data);

		$arrayoftypes = array();
		foreach ($search_measures as $key => $val) {
			$arrayoftypes[] = $search_graph;
		}

		$px1->SetLegend($legend);
		$px1->setShowLegend($SHOWLEGEND);
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
		// $customreportkey may be defined when using customreports.php as an include
		$filenamekey = $dir.'/customreport_'.$object->element.(empty($customreportkey) ? '' : $customreportkey).'.png';
		$fileurlkey = DOL_URL_ROOT.'/viewimage.php?modulepart=user&file=customreport_'.$object->element.(empty($customreportkey) ? '' : $customreportkey).'.png';

		$px1->draw($filenamekey, $fileurlkey);

		$texttoshow = $langs->trans("NoRecordFound");
		if (!GETPOSTISSET('search_measures') || !GETPOSTISSET('search_xaxis')) {
			$texttoshow = $langs->trans("SelectYourGraphOptionsFirst");
		}

		print $px1->show($totalnbofrecord ? 0 : $texttoshow);
	}
}

if ($sql && !defined('MAIN_CUSTOM_REPORT_KEEP_GRAPH_ONLY')) {
	// Show admin info
	print '<br>'.info_admin($langs->trans("SQLUsedForExport").':<br> '.$sql, 0, 0, '1', '', 'TechnicalInformation');
}

print '<div>';

if (!defined('USE_CUSTOM_REPORT_AS_INCLUDE')) {
	print dol_get_fiche_end();

	llxFooter();
	// End of page

	$db->close();
}
