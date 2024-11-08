<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Eric Seigne          <erics@rycks.com>
 * Copyright (C) 2004-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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
 *	    \file       htdocs/comm/action/rapport/index.php
 *      \ingroup    commercial
 *		\brief      Page with reports of actions
 */

// Load Dolibarr environment
require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array("agenda", "commercial"));

$action = GETPOST('action', 'aZ09');
$month = GETPOSTINT('month');
$year = GETPOSTINT('year');

$optioncss = GETPOST('optioncss', 'alpha');
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
	// If $page is not defined, or '' or -1 or if we click on clear filters
	$page = 0;
}
$offset = $limit * $page;
if (!$sortorder) {
	$sortorder = "DESC";
}
if (!$sortfield) {
	$sortfield = "a.datep";
}

// Security check
//$result = restrictedArea($user, 'agenda', 0, '', 'myactions');
if (!$user->hasRight("agenda", "allactions", "read")) {
	accessforbidden();
}


/*
 * Actions
 */

if ($action == 'builddoc' && $user->hasRight("agenda", "allactions", "read")) {
	require_once DOL_DOCUMENT_ROOT.'/core/modules/action/doc/pdf_standard_actions.class.php';

	$cat = new pdf_standard_actions($db, $month, $year);
	$result = $cat->write_file(null, $langs);
	if ($result < 0) {
		setEventMessages($cat->error, $cat->errors, 'errors');
	}
}


/*
 * View
 */

$formfile = new FormFile($db);

llxHeader();

$sql = "SELECT count(*) as cc,";
$sql .= " date_format(a.datep, '%m/%Y') as df,";
$sql .= " date_format(a.datep, '%m') as month,";
$sql .= " date_format(a.datep, '%Y') as year";
$sql .= " FROM ".MAIN_DB_PREFIX."actioncomm as a,";
$sql .= " ".MAIN_DB_PREFIX."user as u";
$sql .= " WHERE a.fk_user_author = u.rowid";
$sql .= ' AND a.entity IN ('.getEntity('agenda').')';
//$sql.= " AND percent = 100";
$sql .= " GROUP BY year, month, df";
$sql .= " ORDER BY year DESC, month DESC, df DESC";

$nbtotalofrecords = '';
if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
	$result = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($result);
	if (($page * $limit) > $nbtotalofrecords) {	// if total resultset is smaller then paging size (filtering), goto and load page 0
		$page = 0;
		$offset = 0;
	}
}

$sql .= $db->plimit($limit + 1, $offset);

//print $sql;
dol_syslog("select", LOG_DEBUG);
$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);

	$param = '';
	if ($limit > 0 && $limit != $conf->liste_limit) {
		$param .= '&limit='.$limit;
	}

	print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
	if ($optioncss != '') {
		print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	}
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';

	// @phan-suppress-next-line PhanPluginSuspiciousParamOrder
	print_barre_liste($langs->trans("EventReports"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'title_agenda', 0, '', '', $limit, 0, 0, 1);

	$moreforfilter = '';

	$i = 0;
	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Period").'</td>';
	print '<td class="center">'.$langs->trans("EventsNb").'</td>';
	print '<td class="center">'.$langs->trans("Action").'</td>';
	print '<td>'.$langs->trans("PDF").'</td>';
	print '<td class="center">'.$langs->trans("Date").'</td>';
	print '<td class="center">'.$langs->trans("Size").'</td>';
	print "</tr>\n";

	while ($i < min($num, $limit)) {
		$obj = $db->fetch_object($resql);

		if ($obj) {
			print '<tr class="oddeven">';

			// Date
			print "<td>".$obj->df."</td>\n";

			// Nb of events
			print '<td class="center">'.$obj->cc.'</td>';

			// Button to build doc
			print '<td class="center">';
			print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=builddoc&token='.newToken().'&page='.((int) $page).'&month='.((int) $obj->month).'&year='.((int) $obj->year).'">'.img_picto($langs->trans('BuildDoc'), 'filenew').'</a>';
			print '</td>';

			$name = "actions-".$obj->month."-".$obj->year.".pdf";
			$relativepath = $name;
			$file = $conf->agenda->dir_temp."/".$name;
			$modulepart = 'actionsreport';
			$documenturl = DOL_URL_ROOT.'/document.php';
			if (isset($conf->global->DOL_URL_ROOT_DOCUMENT_PHP)) {
				$documenturl = getDolGlobalString('DOL_URL_ROOT_DOCUMENT_PHP'); // To use another wrapper
			}

			if (file_exists($file)) {
				print '<td class="tdoverflowmax300">';
				//print '<a data-ajax="false" href="'.DOL_URL_ROOT.'/document.php?page='.$page.'&amp;file='.urlencode($relativepath).'&amp;modulepart=actionsreport">'.img_pdf().'</a>';

				$filearray = array('name' => basename($file), 'fullname' => $file, 'type' => 'file');
				$out = '';

				// Show file name with link to download
				$out .= '<a href="'.$documenturl.'?modulepart='.$modulepart.'&amp;file='.urlencode($relativepath).($param ? '&'.$param : '').'"';
				$mime = dol_mimetype($relativepath, '', 0);
				$out .= ' target="_blank" rel="noopener noreferrer">';
				$out .= img_mime($filearray["name"], $langs->trans("File").': '.$filearray["name"]);
				$out .= $filearray["name"];
				$out .= '</a>'."\n";
				$out .= $formfile->showPreview($filearray, $modulepart, $relativepath, 0, $param);
				print $out;

				print '</td>';
				print '<td class="center">'.dol_print_date(dol_filemtime($file), 'dayhour').'</td>';
				print '<td class="center">'.dol_print_size(dol_filesize($file)).'</td>';
			} else {
				print '<td>&nbsp;</td>';
				print '<td>&nbsp;</td>';
				print '<td>&nbsp;</td>';
			}

			print "</tr>\n";
		}
		$i++;
	}
	print "</table>";
	print '</div>';
	print '</form>';

	$db->free($resql);
} else {
	dol_print_error($db);
}

// End of page
llxFooter();
$db->close();
