<?php
/* Copyright (C) 2001-2006  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2018  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@capnetworks.com>
 * Copyright (C) 2012-2016  Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2013-2019	Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2013-2015  Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2013       Jean Heimburger         <jean@tiaris.info>
 * Copyright (C) 2013       Cédric Salvador         <csalvador@gpcsolutions.fr>
 * Copyright (C) 2013       Florian Henry           <florian.henry@open-concept.pro>
 * Copyright (C) 2013       Adolfo segura           <adolfo.segura@gmail.com>
 * Copyright (C) 2015       Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2016       Ferran Marcet		    <fmarcet@2byte.es>
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
 */

/**
 *  \file       htdocs/multicurrency/multicurrency_rate.php
 *  \ingroup    multicurrency
 *  \brief      Page to list multicurrency rate
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/multicurrency.lib.php';

// Load translation files required by the page
$langs->loadLangs(array('multicurrency'));

$action				= GETPOST('action', 'alpha');
$massaction			= GETPOST('massaction', 'alpha');
$show_files			= GETPOST('show_files', 'int');
$confirm			= GETPOST('confirm', 'alpha');
$toselect = GETPOST('toselect', 'array');
$id_rate_selected = GETPOST('id_rate', 'int');
$sall = trim((GETPOST('search_all', 'alphanohtml') != '') ?GETPOST('search_all', 'alphanohtml') : GETPOST('sall', 'alphanohtml'));
$search_date_sync = GETPOST('search_date_sync', 'alpha');
$search_rate		= GETPOST('search_rate', 'alpha');
$search_code		= GETPOST('search_code', 'alpha');
$multicurrency_code = GETPOST('multicurrency_code', 'alpha');
$dateinput 			= dol_mktime(0, 0, 0, GETPOST('dateinputmonth', 'int'), GETPOST('dateinputday', 'int'), GETPOST('dateinputyear', 'int'));
$rateinput 			= price2num(GETPOST('rateinput', 'alpha'));
$optioncss 			= GETPOST('optioncss', 'alpha');
$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield 			= GETPOST("sortfield", 'alpha');
$sortorder 			= GETPOST("sortorder", 'alpha');
$page = (GETPOST("page", 'int') ?GETPOST("page", 'int') : 0);

if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) $sortfield = "cr.date_sync";
if (!$sortorder) $sortorder = "DESC";


// Initialize technical object to manage hooks. Note that conf->hooks_modules contains array of hooks
$object = new CurrencyRate($db);

$extrafields = new ExtraFields($db);
$form = new Form($db);

$hookmanager->initHooks(array('EditorRatelist', 'globallist'));

if (empty($action)) $action = 'list';

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
	'cr.date_sync'=>"date_sync",
	'cr.rate'=>"rate",
	'm.code'=>"code",
);

// Definition of fields for lists
$arrayfields = array(
	'cr.date_sync'=>array('label'=>'Date', 'checked'=>1),
	'cr.rate'=>array('label'=>'Rate', 'checked'=>1),
	'm.code'=>array('label'=>'Code', 'checked'=>1),
);


$object->fields = dol_sort_array($object->fields, 'position');
$arrayfields = dol_sort_array($arrayfields, 'position');



/*
 * Actions
 */
if ($action == "create") {
	if (!empty($rateinput)) {
		$currencyRate_static = new CurrencyRate($db);
		$currency_static = new MultiCurrency($db);
		$fk_currency = $currency_static->getIdFromCode($db, $multicurrency_code);

		$currencyRate_static->fk_multicurrency = $fk_currency;
		$currencyRate_static->entity = $conf->entity;
		$currencyRate_static->date_sync = $dateinput;
		$currencyRate_static->rate = $rateinput;

		$result = $currencyRate_static->create(intval($fk_currency));
		if ($result > 0) {
			setEventMessages($langs->trans('successRateCreate', $multicurrency_code), null);
		} else {
			dol_syslog("currencyRate:createRate", LOG_WARNING);
			setEventMessages($currencyRate_static->error, $currencyRate_static->errors, 'errors');
		}
	} else {
		setEventMessages($langs->trans('NoEmptyRate'), null, "errors");
	}
}

if ($action == 'update') {
	$currencyRate = new CurrencyRate($db);
	$result = $currencyRate->fetch($id_rate_selected);
	if ($result > 0) {
		$currency_static = new MultiCurrency($db);
		$fk_currency = $currency_static->getIdFromCode($db, $multicurrency_code);
		$currencyRate->date_sync = $dateinput;
		$currencyRate->fk_multicurrency = $fk_currency;
		$currencyRate->rate = $rateinput;
		$res = $currencyRate->update();
		if ($res) {
			setEventMessages($langs->trans('successUpdateRate'), null);
		} else {
			setEventMessages($currencyRate->error, $currencyRate->errors, "errors");
		}
	} else {
		setEventMessages($langs->trans('Error'), null, "warnings");
	}
}

if ($action == "deleteRate") {
	$current_rate = new CurrencyRate($db);
	$current_rate->fetch(intval($id_rate_selected));

	if ($current_rate) {
		$current_currency = new MultiCurrency($db);
		$current_currency->fetch($current_rate->fk_multicurrency);
		if ($current_currency) {
			$delayedhtmlcontent = $form->formconfirm(
				$_SERVER["PHP_SELF"].'?id_rate='.$id_rate_selected,
				$langs->trans('DeleteLineRate'),
				$langs->trans('ConfirmDeleteLineRate', $current_rate->rate, $current_currency->name, $current_rate->date_sync),
				'confirm_delete',
				'',
				0,
				1
			);
		} else {
			dol_syslog("Multicurrency::fetch", LOG_WARNING);
		}
	} else {
		setEventMessage($langs->trans('NoCurrencyRateSelected'), "warnings");
	}
}

if ($action == "confirm_delete") {
	$current_rate = new CurrencyRate($db);
	$current_rate->fetch(intval($id_rate_selected));
	if ($current_rate) {
		$result = $current_rate->delete();
		if ($result) {
			setEventMessages($langs->trans('successRateDelete'), null);
		} else {
			setEventMessages($current_rate->error, $current_rate->errors, 'errors');
		}
	} else {
		setEventMessages($langs->trans('NoCurrencyRateSelected'), null, "warnings");
		dol_syslog($langs->trans('NoCurrencyRateSelected'), LOG_WARNING);
	}
}


if (GETPOST('cancel', 'alpha')) { $action = 'list'; $massaction = ''; }
if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') { $massaction = ''; }

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
if (empty($reshook))
{
	// Selection of new fields
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

	// Purge search criteria
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) // All tests are required to be compatible with all browsers
	{
		$sall = "";
		$search_date_sync = "";
		$search_rate = "";
		$search_code = "";
		$search_array_options = array();
	}

	// Mass actions
	$objectclass = "CurrencyRate";
	$uploaddir = $conf->multicurrency->multidir_output; // define only because core/actions_massactions.inc.php want it
	$permtoread = $user->admin;
	$permtodelete = $user->admin;
	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';
}

/*
 * View
 */

$htmlother = new FormOther($db);

$title = $langs->trans("CurrencyRate");
$page_name = "ListCurrencyRate";

llxHeader('', $title, $helpurl, '');
// Subheader
$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans($page_name), $linkback);

// Configuration header
$head = multicurrencyAdminPrepareHead();
print dol_get_fiche_head($head, 'ratelist', $langs->trans("ModuleSetup"), -1, "multicurrency");

// ACTION

if ($action != "updateRate") {
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("FormCreateRate").'</td>'."\n";
	print '</tr></table>';

	$form = new Form($db);
	print '<form action="'.$_SERVER["PHP_SELF"].'" method="post" name="formulaire">';
	print '<input type="hidden" name="token" value="'.newToken().'">';

	print '<table><tr>';

	print ' <td>'.$langs->trans('Date').'</td>';
	print ' <td>';
	print $form->selectDate($dateinput, 'dateinput');
	print '</td>';

	print '<td> '.$langs->trans('Currency').'</td>';
	print '<td>'.$form->selectMultiCurrency((GETPOSTISSET('multicurrency_code') ? GETPOST('multicurrency_code', 'alpha') : $multicurrency_code), 'multicurrency_code', 0, " code != '".$conf->currency."'", true).'</td>';

	print ' <td>'.$langs->trans('Rate').'</td>';
	print ' <td><input type="number" min="0" step="any" class="maxwidth75" name="rateinput" value="'.dol_escape_htmltag($rateinput).'"></td>';

	print '<td>';
	print '<input type="hidden" name="action" value="create">';
	print '<input type="submit" class="butAction" name="btnCreateCurrencyRate" value="'.$langs->trans('CreateRate').'">';
	print '</td>';

	print '</tr></table>';
	print '</form>';

	print '<br>';
}

if ($action == "updateRate") {
	$current_rate = new CurrencyRate($db);
	$current_rate->fetch(intval($id_rate_selected));

	if ($current_rate) {
		$curr = new MultiCurrency($db);
		$resultcurrentCurrency = $curr->fetch($current_rate->fk_multicurrency);

		if ($resultcurrentCurrency) {
			$currency_code = $curr->code;
		} else {
			$currency_code = '';
		}

		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans("FormUpdateRate").'</td>'."\n";
		print '</tr></table>';

		$form = new Form($db);
		print '<form action="'.$_SERVER["PHP_SELF"].'" method="post" name="formtoupdaterate">';
		print '<input type="hidden" name="token" value="'.newToken().'">';

		print '<table><tr>';
		print ' <td>'.$langs->trans('Date').'</td>';
		print '<td>';
		print $form->selectDate($current_rate->date_sync, 'dateinput');
		print '</td>';

		print '<td> '.$langs->trans('Currency').'</td>';
		print '<td>'.$form->selectMultiCurrency($currency_code, 'multicurrency_code', 0, " code != '".$conf->currency."'", true).'</td>';

		print '<td>'.$langs->trans('Rate').'</td>';
		print '<td><input class="minwidth200" name="rateinput" value="'.dol_escape_htmltag($current_rate->rate).'" type="text"></td>';

		print '<td>';
		print '<input type="hidden" name="action" value="update">';
		print '<input type="hidden" name="id_rate" value="'.$current_rate->id.'">';
		print '<input type="submit" class="butAction" name="btnupdateCurrencyRate" value="'.$langs->trans('UpdateRate').'">';
		print '<a href="'.$_SERVER["PHP_SELF"].'" class="butAction">'.$langs->trans('CancelUpdate').'</a>';

		print '</td>';
		print '</tr></table>';
		print '</form>';
	} else {
		dol_syslog("currency_rate:list:update", LOG_WARNING);
	}
}


$sql = 'SELECT cr.rowid, cr.date_sync, cr.rate, cr.entity, m.code, m.name ';
// Add fields from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
$sql .= ' FROM '.MAIN_DB_PREFIX.'multicurrency_rate as cr ';
$sql .= " INNER JOIN ".MAIN_DB_PREFIX."multicurrency AS m ON cr.fk_multicurrency = m.rowid";
if ($sall) $sql .= natural_search(array_keys($fieldstosearchall), $sall);
if ($search_date_sync) $sql .= natural_search('cr.date_sync', $search_date_sync);
if ($search_rate) $sql .= natural_search('cr.rate', $search_rate);
if ($search_code) $sql .= natural_search('m.code', $search_code);
$sql .= " WHERE m.code <> '".$db->escape($conf->currency)."'";

// Add where from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
$sql .= " GROUP BY cr.rowid, cr.date_sync, cr.rate, m.code, cr.entity, m.code, m.name";

// Add fields from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldSelect', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

$sql .= $db->order($sortfield, $sortorder);


$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
	$result = $db->query($sql);

	if ($result) {
		$nbtotalofrecords = $db->num_rows($result);
		if (($page * $limit) > $nbtotalofrecords)	// if total resultset is smaller then paging size (filtering), goto and load page 0
		{
			$page = 0;
			$offset = 0;
		}
	} else {
		setEventMessage($langs->trans('No_record_on_multicurrency_rate'), 'warnings');
	}
}

$sql .= $db->plimit($limit + 1, $offset);

$resql = $db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$arrayofselected = is_array($toselect) ? $toselect : array();

	$param = '';
	if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage='.urlencode($contextpage);
	if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit='.urlencode($limit);
	if ($sall) $param .= "&sall=".urlencode($sall);

	if ($search_date_sync) $param = "&search_date_sync=".urlencode($search_date_sync);
	if ($search_rate) $param = "&search_rate=".urlencode($search_rate);
	if ($search_code != '') $param .= "&search_code=".urlencode($search_code);

	// Add $param from extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

	if ($user->admin) $arrayofmassactions['predelete'] = $langs->trans("Delete");
	if (in_array($massaction, array('presend', 'predelete'))) $arrayofmassactions = array();
	$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

	print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST" name="formulaire">';
	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="page" value="'.$page.'">';
	print '<input type="hidden" name="type" value="'.$type.'">';

	print_barre_liste($texte, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'title_currency.png', 0, $newcardbutton, '', $limit);

	include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

	if ($sall)
	{
		foreach ($fieldstosearchall as $key => $val) $fieldstosearchall[$key] = $langs->trans($val);
		print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $sall).join(', ', $fieldstosearchall).'</div>';
	}

	// Filter on categories
	$moreforfilter = '';


	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) $moreforfilter .= $hookmanager->resPrint;
	else $moreforfilter = $hookmanager->resPrint;

	if ($moreforfilter)
	{
		print '<div class="liste_titre liste_titre_bydiv centpercent">';
		print $moreforfilter;
		print '</div>';
	}

	$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
	$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields
	if ($massactionbutton) $selectedfields .= $form->showCheckAddButtons('checkforselect', 1);

	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

	// Lines with input filters
	print '<tr class="liste_titre_filter">';

	// date
	if (!empty($arrayfields['cr.date_sync']['checked']))
	{
		print '<td class="liste_titre" align="left">';
		print '<input class="flat" type="text" name="search_date_sync" size="8" value="'.dol_escape_htmltag($search_date_sync).'">';
		print '</td>';
	}
	// code
	if (!empty($arrayfields['m.code']['checked']))
	{
		print '<td class="liste_titre" align="left">';
		print '<input class="flat" type="text" name="search_code" size="12" value="'.dol_escape_htmltag($search_code).'">';
		print '</td>';
	}
	// rate
	if (!empty($arrayfields['cr.rate']['checked']))
	{
		print '<td class="liste_titre" align="left">';
		print '<input class="flat" type="text" name="search_rate" size="8" value="'.dol_escape_htmltag($search_rate).'">';
		print '</td>';
	}

	// Fields from hook
	$parameters = array('arrayfields'=>$arrayfields);
	$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

	print '<td class="liste_titre" align="middle">';
	$searchpicto = $form->showFilterButtons();
	print $searchpicto;
	print '</td>';

	print '</tr>';

	print '<tr class="liste_titre">';
	if (!empty($arrayfields['cr.date_sync']['checked']))  print_liste_field_titre($arrayfields['cr.date_sync']['label'], $_SERVER["PHP_SELF"], "cr.date_sync", "", $param, "", $sortfield, $sortorder);
	if (!empty($arrayfields['m.code']['checked']))  print_liste_field_titre($arrayfields['m.code']['label'], $_SERVER["PHP_SELF"], "m.code", "", $param, "", $sortfield, $sortorder);
	if (!empty($arrayfields['cr.rate']['checked']))  print_liste_field_titre($arrayfields['cr.rate']['label'], $_SERVER["PHP_SELF"], "cr.rate", "", $param, "", $sortfield, $sortorder);

	// Hook fields
	$parameters = array('arrayfields'=>$arrayfields, 'param'=>$param, 'sortfield'=>$sortfield, 'sortorder'=>$sortorder);
	$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', 'align="center"', $sortfield, $sortorder, 'maxwidthsearch ');
	print "</tr>\n";

	$i = 0;
	$totalarray = array();
	while ($i < min($num, $limit))
	{
		$obj = $db->fetch_object($resql);

		print '<tr class="oddeven">';

		// date_sync
		if (!empty($arrayfields['cr.date_sync']['checked']))
		{
			print '<td class="tdoverflowmax200">';
			print $obj->date_sync;
			print "</td>\n";
			if (!$i) $totalarray['nbfield']++;
		}

		// code
		if (!empty($arrayfields['m.code']['checked']))
		{
			print '<td class="tdoverflowmax200">';
			print $obj->code." ".$obj->name;
			print "</td>\n";

			if (!$i) $totalarray['nbfield']++;
		}

		// rate
		if (!empty($arrayfields['cr.rate']['checked']))
		{
			print '<td class="tdoverflowmax200">';
			print $obj->rate;
			print "</td>\n";
			if (!$i) $totalarray['nbfield']++;
		}

		// Fields from hook
		$parameters = array('arrayfields'=>$arrayfields, 'obj'=>$obj);
		$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters); // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;

		// Action
		print '<td class="nowrap" align="center">';
		if ($massactionbutton || $massaction)   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
		{
			$selected = 0;
			if (in_array($obj->rowid, $arrayofselected)) $selected = 1;
			print '<a class="editfielda marginleftonly marginrightonly" href="'.$_SERVER["PHP_SELF"].'?action=updateRate&amp;id_rate='.$obj->rowid.'">'.img_picto('edit', 'edit').'</a>';
			print '<a class="marginleftonly marginrightonly" href="'.$_SERVER["PHP_SELF"].'?action=deleteRate&amp;id_rate='.$obj->rowid.'">'.img_picto('delete', 'delete').'</a>';
			print '<input id="cb'.$obj->rowid.'" class="flat checkforselect marginleftonly" type="checkbox" name="toselect[]" value="'.$obj->rowid.'"'.($selected ? ' checked="checked"' : '').'>';
		}
		print '</td>';
		if (!$i) $totalarray['nbfield']++;

		print "</tr>\n";
		$i++;
	}

	$db->free($resql);

	print "</table>";
	print "</div>";

	print '</form>';
}
else {
	dol_print_error($db);
}


llxFooter();
$db->close();
