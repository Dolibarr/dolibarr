<?php
/* Copyright (C) 2005-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis.houssin@inodbox.com>
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
 *       \file       htdocs/comm/mailing/list.php
 *       \ingroup    mailing
 *       \brief      Liste des mailings
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/comm/mailing/class/mailing.class.php';

// Load translation files required by the page
$langs->load("mails");

// Security check
$result = restrictedArea($user, 'mailing');

$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$optioncss = GETPOST('optioncss', 'alpha');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == -1 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha') || (empty($toselect) && $massaction === '0')) { $page = 0; }     // If $page is not defined, or '' or -1 or if we click on clear filters or if we select empty mass action
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortorder) $sortorder = "DESC";
if (!$sortfield) $sortfield = "m.date_creat";

$search_all = trim((GETPOST('search_all', 'alphanohtml') != '') ?GETPOST('search_all', 'alphanohtml') : GETPOST('sall', 'alphanohtml'));
$search_ref = GETPOST("search_ref", "alpha") ? GETPOST("search_ref", "alpha") : GETPOST("sref", "alpha");
$filteremail = GETPOST('filteremail', 'alpha');

$object = new Mailing($db);

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('mailinglist'));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
	'm.titre'=>'Ref',
);




/*
 * Actions
 */

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
		/*foreach($object->fields as $key => $val)
		{
			$search[$key]='';
		}*/
		$search_ref = '';
		$search_all = '';
		$toselect = '';
		$search_array_options = array();
	}
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')
		|| GETPOST('button_search_x', 'alpha') || GETPOST('button_search.x', 'alpha') || GETPOST('button_search', 'alpha'))
	{
		$massaction = ''; // Protection to avoid mass action if we force a new search during a mass action confirmation
	}

	// Mass actions
	/*$objectclass='MyObject';
	$objectlabel='MyObject';
	$permissiontoread = $user->rights->mymodule->read;
	$permissiontodelete = $user->rights->mymodule->delete;
	$uploaddir = $conf->mymodule->dir_output;
	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';
	*/
}


/*
 * View
 */

llxHeader('', $langs->trans("Mailing"), 'EN:Module_EMailing|FR:Module_Mailing|ES:M&oacute;dulo_Mailing');

$form = new Form($db);

if ($filteremail)
{
	$sql = "SELECT m.rowid, m.titre, m.nbemail, m.statut, m.date_creat as datec, m.date_envoi as date_envoi,";
	$sql .= " mc.statut as sendstatut";
	$sql .= " FROM ".MAIN_DB_PREFIX."mailing as m, ".MAIN_DB_PREFIX."mailing_cibles as mc";
	$sql .= " WHERE m.rowid = mc.fk_mailing AND m.entity = ".$conf->entity;
	$sql .= " AND mc.email = '".$db->escape($filteremail)."'";
	if ($search_ref) $sql .= " AND m.rowid = '".$db->escape($search_ref)."'";
	if ($search_all) $sql .= " AND (m.titre like '%".$db->escape($search_all)."%' OR m.sujet like '%".$db->escape($search_all)."%' OR m.body like '%".$db->escape($search_all)."%')";
	if (!$sortorder) $sortorder = "ASC";
	if (!$sortfield) $sortfield = "m.rowid";
} else {
	$sql = "SELECT m.rowid, m.titre, m.nbemail, m.statut, m.date_creat as datec, m.date_envoi as date_envoi";
	$sql .= " FROM ".MAIN_DB_PREFIX."mailing as m";
	$sql .= " WHERE m.entity = ".$conf->entity;
	if ($search_ref) $sql .= " AND m.rowid = '".$db->escape($search_ref)."'";
	if ($search_all) $sql .= " AND (m.titre like '%".$db->escape($search_all)."%' OR m.sujet like '%".$db->escape($search_all)."%' OR m.body like '%".$db->escape($search_all)."%')";
	if (!$sortorder) $sortorder = "ASC";
	if (!$sortfield) $sortfield = "m.rowid";
}

$sql .= $db->order($sortfield, $sortorder);

$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
	$resql = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($resql);
	if (($page * $limit) > $nbtotalofrecords)	// if total resultset is smaller then paging size (filtering), goto and load page 0
	{
		$page = 0;
		$offset = 0;
	}
}

$sql .= $db->plimit($limit + 1, $offset);
//print $sql;

dol_syslog("comm/mailing/list.php", LOG_DEBUG);
$resql = $db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);

	$title = $langs->trans("ListOfEMailings");
	if ($filteremail) $title .= ' ('.$langs->trans("SentTo", $filteremail).')';

	$newcardbutton = '';
	if ($user->rights->mailing->creer)
	{
		$newcardbutton .= dolGetButtonTitle($langs->trans('NewMailing'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/comm/mailing/card.php?action=create');
	}

	$i = 0;

	$param = "&search_all=".urlencode($search_all);
	if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage='.urlencode($contextpage);
	if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit='.urlencode($limit);
	if ($filteremail) $param .= '&filteremail='.urlencode($filteremail);

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';

	print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'object_email', 0, $newcardbutton, '', $limit, 0, 0, 1);

	$moreforfilter = '';

	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

	print '<tr class="liste_titre_filter">';
	print '<td class="liste_titre">';
	print '<input type="text" class="flat maxwidth50" name="search_ref" value="'.dol_escape_htmltag($search_ref).'">';
	print '</td>';
	// Title
	print '<td class="liste_titre">';
	print '<input type="text" class="flat maxwidth100 maxwidth50onsmartphone" name="search_all" value="'.dol_escape_htmltag($search_all).'">';
	print '</td>';
	print '<td class="liste_titre">&nbsp;</td>';
	if (!$filteremail) print '<td class="liste_titre">&nbsp;</td>';
	print '<td class="liste_titre">&nbsp;</td>';
	print '<td class="liste_titre">&nbsp;</td>';
	print '<td class="liste_titre maxwidthsearch">';
	$searchpicto = $form->showFilterAndCheckAddButtons(0);
	print $searchpicto;
	print '</td>';
	print "</tr>\n";

	print '<tr class="liste_titre">';
	print_liste_field_titre("Ref", $_SERVER["PHP_SELF"], "m.rowid", $param, "", "", $sortfield, $sortorder);
	print_liste_field_titre("Title", $_SERVER["PHP_SELF"], "m.titre", $param, "", "", $sortfield, $sortorder);
	print_liste_field_titre("DateCreation", $_SERVER["PHP_SELF"], "m.date_creat", $param, "", 'align="center"', $sortfield, $sortorder);
	if (!$filteremail) print_liste_field_titre("NbOfEMails", $_SERVER["PHP_SELF"], "m.nbemail", $param, "", 'align="center"', $sortfield, $sortorder);
	if (!$filteremail) print_liste_field_titre("DateLastSend", $_SERVER["PHP_SELF"], "m.date_envoi", $param, "", 'align="center"', $sortfield, $sortorder);
	else print_liste_field_titre("DateSending", $_SERVER["PHP_SELF"], "mc.date_envoi", $param, "", 'align="center"', $sortfield, $sortorder);
	print_liste_field_titre("Status", $_SERVER["PHP_SELF"], ($filteremail ? "mc.statut" : "m.statut"), $param, "", 'class="right"', $sortfield, $sortorder);
	print_liste_field_titre('', $_SERVER["PHP_SELF"], "", '', '', 'class="right"', $sortfield, $sortorder, 'maxwidthsearch ');
	print "</tr>\n";


	$email = new Mailing($db);

	while ($i < min($num, $limit))
	{
		$obj = $db->fetch_object($resql);

		$email->id = $obj->rowid;
		$email->ref = $obj->rowid;

		print '<tr class="oddeven">';

		print '<td>';
		print $email->getNomUrl(1);
		print '</td>';

		print '<td>'.$obj->title.'</td>';
		// Date creation

		print '<td class="center">';
		print dol_print_date($db->jdate($obj->datec), 'day');
		print '</td>';

		// Nb of email
		if (!$filteremail)
		{
			print '<td class="center">';
			$nbemail = $obj->nbemail;
			/*if ($obj->statut != 3 && !empty($conf->global->MAILING_LIMIT_SENDBYWEB) && $conf->global->MAILING_LIMIT_SENDBYWEB < $nbemail)
			{
				$text=$langs->trans('LimitSendingEmailing',$conf->global->MAILING_LIMIT_SENDBYWEB);
				print $form->textwithpicto($nbemail,$text,1,'warning');
			}
			else
			{
				print $nbemail;
			}*/
			print $nbemail;
			print '</td>';
		}

		// Last send
		print '<td align="center" class="nowrap">'.dol_print_date($db->jdate($obj->date_envoi), 'day').'</td>';
		print '</td>';

		// Status
		print '<td class="nowrap right">';
		if ($filteremail)
		{
			print $email::libStatutDest($obj->sendstatut, 2);
		} else {
			print $email->LibStatut($obj->statut, 5);
		}
		print '</td>';

		print '<td></td>';

		print "</tr>\n";
		$i++;
	}
	if (empty($num)) {
		$colspan = 6;
		if (!$filteremail) $colspan++;
		print '<tr><td colspan="'.$colspan.'"><span class="opacitymedium">'.$langs->trans("NoRecordFound").'</td></tr>';
	}

	print '</table>';
	print '</div>';
	print '</form>';

	$db->free($resql);
} else {
	dol_print_error($db);
}

// End of page
llxFooter();
$db->close();
