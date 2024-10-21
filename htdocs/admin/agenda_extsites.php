<?php
/* Copyright (C) 2008-2015	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2011-2015	Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2015       Jean-François Ferry		<jfefe@aternatik.fr>
 * Copyright (C) 2016       Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2021		Frédéric France			<frederic.france@netlogic.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *	    \file       htdocs/admin/agenda_extsites.php
 *      \ingroup    agenda
 *      \brief      Page to setup external calendars for agenda module
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/agenda.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

if (!$user->admin) {
	accessforbidden();
}

// Load translation files required by the page
$langs->loadLangs(array('agenda', 'admin', 'other'));

$def = array();
$action = GETPOST('action', 'alpha');

if (!getDolGlobalString('AGENDA_EXT_NB')) {
	$conf->global->AGENDA_EXT_NB = 5;
}
$MAXAGENDA = getDolGlobalString('AGENDA_EXT_NB');

// List of available colors
$colorlist = array('BECEDD', 'DDBECE', 'BFDDBE', 'F598B4', 'F68654', 'CBF654', 'A4A4A5');

$reg = array();


/*
 * Actions
 */

$error = 0;
$errors = array();

if (preg_match('/set_(.*)/', $action, $reg)) {
	$db->begin();

	$code = $reg[1];
	$value = (GETPOST($code) ? GETPOST($code) : 1);

	$res = dolibarr_set_const($db, $code, $value, 'chaine', 0, '', $conf->entity);
	if (!($res > 0)) {
		$error++;
		$errors[] = $db->lasterror();
	}

	if ($error) {
		$db->rollback();
		setEventMessages('', $errors, 'errors');
	} else {
		$db->commit();
		setEventMessage($langs->trans('SetupSaved'));
		header('Location: ' . $_SERVER["PHP_SELF"]);
		exit();
	}
} elseif (preg_match('/del_(.*)/', $action, $reg)) {
	$db->begin();

	$code = $reg[1];

	$res = dolibarr_del_const($db, $code, $conf->entity);
	if (!($res > 0)) {
		$error++;
		$errors[] = $db->lasterror();
	}

	if ($error) {
		$db->rollback();
		setEventMessages('', $errors, 'errors');
	} else {
		$db->commit();
		setEventMessage($langs->trans('SetupSaved'));
		header('Location: ' . $_SERVER["PHP_SELF"]);
		exit();
	}
} elseif ($action == 'save') {
	$db->begin();

	$disableext = GETPOST('AGENDA_DISABLE_EXT', 'alpha');
	$res = dolibarr_set_const($db, 'AGENDA_DISABLE_EXT', $disableext, 'chaine', 0, '', $conf->entity);

	$i = 1;
	$errorsaved = 0;

	// Save agendas
	while ($i <= $MAXAGENDA) {
		$name = trim(GETPOST('AGENDA_EXT_NAME'.$i, 'alpha'));
		$src = trim(GETPOST('AGENDA_EXT_SRC'.$i, 'alpha'));
		$offsettz = trim(GETPOST('AGENDA_EXT_OFFSETTZ'.$i, 'alpha'));
		$color = trim(GETPOST('AGENDA_EXT_COLOR'.$i, 'alpha'));
		if ($color == '-1') {
			$color = '';
		}
		$enabled = trim(GETPOST('AGENDA_EXT_ENABLED'.$i, 'alpha'));

		if (!empty($src) && !dol_is_url($src)) {
			setEventMessages($langs->trans("ErrorParamMustBeAnUrl"), null, 'errors');
			$error++;
			$errorsaved++;
			break;
		}

		//print '-name='.$name.'-color='.$color;
		// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
		$res = dolibarr_set_const($db, 'AGENDA_EXT_NAME'.$i, $name, 'chaine', 0, '', $conf->entity);
		if (!($res > 0)) {
			$error++;
		}
		$res = dolibarr_set_const($db, 'AGENDA_EXT_SRC'.$i, $src, 'chaine', 0, '', $conf->entity);
		if (!($res > 0)) {
			$error++;
		}
		$res = dolibarr_set_const($db, 'AGENDA_EXT_OFFSETTZ'.$i, $offsettz, 'chaine', 0, '', $conf->entity);
		if (!($res > 0)) {
			$error++;
		}
		$res = dolibarr_set_const($db, 'AGENDA_EXT_COLOR'.$i, $color, 'chaine', 0, '', $conf->entity);
		if (!($res > 0)) {
			$error++;
		}
		$res = dolibarr_set_const($db, 'AGENDA_EXT_ENABLED'.$i, $enabled, 'chaine', 0, '', $conf->entity);
		if (!($res > 0)) {
			$error++;
		}
		$i++;
	}

	// Save nb of agenda
	if (!$error) {
		$res = dolibarr_set_const($db, 'AGENDA_EXT_NB', GETPOSTINT('AGENDA_EXT_NB'), 'chaine', 0, '', $conf->entity);
		if (!($res > 0)) {
			$error++;
		}
		if (!getDolGlobalString('AGENDA_EXT_NB')) {
			$conf->global->AGENDA_EXT_NB = 5;
		}
		$MAXAGENDA = getDolGlobalInt('AGENDA_EXT_NB', 5);
	}

	if (!$error) {
		$db->commit();
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		$db->rollback();
		if (empty($errorsaved)) {
			setEventMessages($langs->trans("Error"), null, 'errors');
		}
	}
}

/*
 * View
 */

$form = new Form($db);
$formadmin = new FormAdmin($db);
$formother = new FormOther($db);

$arrayofjs = array();
$arrayofcss = array();

$wikihelp = 'EN:Module_Agenda_En|FR:Module_Agenda|ES:Módulo_Agenda|DE:Modul_Terminplanung';
llxHeader('', $langs->trans("AgendaSetup"), $wikihelp, '', 0, 0, $arrayofjs, $arrayofcss, '', 'mod-admin page-agenda-extsites');

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("AgendaSetup"), $linkback, 'title_setup');

print '<form name="extsitesconfig" action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="save">';

$head = agenda_prepare_head();

print dol_get_fiche_head($head, 'extsites', $langs->trans("Agenda"), -1, 'action');

print '<span class="opacitymedium">'.$langs->trans("AgendaExtSitesDesc")."</span><br>\n";
print "<br>\n";


$selectedvalue = getDolGlobalInt('AGENDA_DISABLE_EXT');
if ($selectedvalue == 1) {
	$selectedvalue = 0;
} else {
	$selectedvalue = 1;
}

print "<table class=\"noborder\" width=\"100%\">";

print "<tr class=\"liste_titre\">";
print '<td>'.$langs->trans("Parameter")."</td>";
print '<td class="center">'.$langs->trans("Value")."</td>";
print "</tr>";

// Show external agenda

print '<tr class="oddeven">';
print "<td>".$langs->trans("ExtSitesEnableThisTool")."</td>";
print '<td class="center">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('AGENDA_DISABLE_EXT', array('enabled' => array(0 => '.hideifnotset')), null, 1);
} else {
	if (!getDolGlobalString('AGENDA_DISABLE_EXT')) {
		print '<a href="'.$_SERVER['PHP_SELF'].'?save=1&AGENDA_DISABLE_EXT=1">'.img_picto($langs->trans("Enabled"), 'on').'</a>';
	} else {
		print '<a href="'.$_SERVER['PHP_SELF'].'?save=1&AGENDA_DISABLE_EXT=0">'.img_picto($langs->trans("Disabled"), 'off').'</a>';
	}
}
print "</td>";
print "</tr>";

// Nb of agenda

print '<tr class="oddeven">';
print "<td>".$langs->trans("ExtSitesNbOfAgenda")."</td>";
print '<td class="center">';
print '<input class="flat hideifnotset width50 center" type="text" id="AGENDA_EXT_NB" name="AGENDA_EXT_NB" value="' . getDolGlobalString('AGENDA_EXT_NB').'">';
print "</td>";
print "</tr>";

print "</table>";
print "<br>";

print '<div class="div-table-responsive">';
print '<table class="noborder centpercent">'."\n";

print '<tr class="liste_titre">';
print "<td>".$langs->trans("Parameter")."</td>";
print "<td>".$langs->trans("Name")."</td>";
print "<td>".$langs->trans("ExtSiteUrlAgenda")." (".$langs->trans("Example").': http://yoursite/agenda/agenda.ics)</td>';
print "<td>".$form->textwithpicto($langs->trans("FixTZ"), $langs->trans("FillFixTZOnlyIfRequired"), 1).'</td>';
print '<td class="right">'.$langs->trans("Color").'</td>';
print '<td class="right">'.$langs->trans("ActiveByDefault").'</td>';
print "</tr>";

$i = 1;
while ($i <= $MAXAGENDA) {
	$key = $i;
	$name = 'AGENDA_EXT_NAME' . $key;
	$src = 'AGENDA_EXT_SRC' . $key;
	$offsettz = 'AGENDA_EXT_OFFSETTZ' . $key;
	$color = 'AGENDA_EXT_COLOR' . $key;
	$enabled = 'AGENDA_EXT_ENABLED' . $key;
	$default = 'AGENDA_EXT_ACTIVEBYDEFAULT' . $key;

	print '<tr class="oddeven">';
	// Nb  @phan-suppress-next-line PhanPluginSuspiciousParamPosition
	print '<td width="180" class="nowrap">' . $langs->trans("AgendaExtNb", $key) . "</td>";
	// Name
	print '<td><input type="text" class="flat hideifnotset" name="AGENDA_EXT_NAME' . $key . '" value="' . (GETPOST('AGENDA_EXT_NAME' . $key) ? GETPOST('AGENDA_EXT_NAME' . $key, 'alpha') : getDolGlobalString($name)) . '" size="28"></td>';
	// URL
	print '<td><input type="url" class="flat hideifnotset" name="AGENDA_EXT_SRC' . $key . '" value="' . (GETPOST('AGENDA_EXT_SRC' . $key) ? GETPOST('AGENDA_EXT_SRC' . $key, 'alpha') : getDolGlobalString($src)) . '" size="60"></td>';
	// Offset TZ
	print '<td><input type="text" class="flat hideifnotset" name="AGENDA_EXT_OFFSETTZ' . $key . '" value="' . (GETPOST('AGENDA_EXT_OFFSETTZ' . $key) ? GETPOST('AGENDA_EXT_OFFSETTZ' . $key) : getDolGlobalString($offsettz)) . '" size="2"></td>';
	// Color (Possible colors are limited by Google)
	print '<td class="nowraponall right">';
	print $formother->selectColor((GETPOST("AGENDA_EXT_COLOR" . $key) ? GETPOST("AGENDA_EXT_COLOR" . $key) : getDolGlobalString($color)), "AGENDA_EXT_COLOR" . $key, 'extsitesconfig', 1, array(), 'hideifnotset');
	print '</td>';
	// Calendar active by default
	print '<td class="nowrap right">';
	if (!empty($conf->use_javascript_ajax)) {
		print ajax_constantonoff('AGENDA_EXT_ACTIVEBYDEFAULT' . $key);
	} else {
		// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
		if (getDolGlobalString($default)) {
			print '<a href="' . $_SERVER['PHP_SELF'] . '?action=del_AGENDA_EXT_ACTIVEBYDEFAULT' . $key . '&token='.newToken().'">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
		} else {
			print '<a href="' . $_SERVER['PHP_SELF'] . '?action=set_AGENDA_EXT_ACTIVEBYDEFAULT' . $key . '&token='.newToken().'">' . img_picto($langs->trans("Enabled"), 'on') . '</a>';
		}
	}
	print '</td>';
	print "</tr>";
	$i++;
}

print '</table>';
print '</div>';

print dol_get_fiche_end();

print '<div class="center">';
print '<input type="submit" id="save" name="save" class="button hideifnotset button-save" value="'.$langs->trans("Save").'">';
print '</div>';

print "</form>\n";

// End of page
llxFooter();
$db->close();
