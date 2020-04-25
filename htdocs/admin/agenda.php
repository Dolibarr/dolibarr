<?php
/* Copyright (C) 2008-2015	Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2011		Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2011-2012  Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2015		Jean-François Ferry <jfefe@aternatik.fr>
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
 *	    \file       htdocs/admin/agenda.php
 *      \ingroup    agenda
 *      \brief      Autocreate actions for agenda module setup page
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/agenda.lib.php';

if (!$user->admin)
    accessforbidden();

// Load translation files required by the page
$langs->loadLangs(array('admin', 'other', 'agenda'));

$action = GETPOST('action', 'alpha');
$cancel = GETPOST('cancel', 'alpha');

$search_event = GETPOST('search_event', 'alpha');

// Get list of triggers available
$triggers = array();
$sql = "SELECT a.rowid, a.code, a.label, a.elementtype, a.rang as position";
$sql .= " FROM ".MAIN_DB_PREFIX."c_action_trigger as a";
$sql .= " ORDER BY a.rang ASC";
$resql = $db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;
	while ($i < $num)
	{
		$obj = $db->fetch_object($resql);
		$triggers[$i]['rowid'] = $obj->rowid;
		$triggers[$i]['code'] 		= $obj->code;
		$triggers[$i]['element'] = $obj->elementtype;
		$triggers[$i]['label']		= ($langs->trans("Notify_".$obj->code) != "Notify_".$obj->code ? $langs->trans("Notify_".$obj->code) : $obj->label);
		$triggers[$i]['position'] = $obj->position;

		$i++;
	}
	$db->free($resql);
}
else
{
	dol_print_error($db);
}

//$triggers = dol_sort_array($triggers, 'code', 'asc', 0, 0, 1);


/*
 *	Actions
 */

// Purge search criteria
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) // All tests are required to be compatible with all browsers
{
	$search_event = '';
	$action = '';
}

if (GETPOST('button_search_x', 'alpha') || GETPOST('button_search.x', 'alpha') || GETPOST('button_search', 'alpha'))	// To avoid the save when we click on search
{
	$action = '';
}

if ($action == "save" && empty($cancel))
{
    $i = 0;

    $db->begin();

	foreach ($triggers as $trigger)
	{
		$keyparam = 'MAIN_AGENDA_ACTIONAUTO_'.$trigger['code'];
		//print "param=".$param." - ".$_POST[$param];
		if ($search_event === '' || preg_match('/'.preg_quote($search_event, '/').'/i', $keyparam))
		{
			$res = dolibarr_set_const($db, $keyparam, (GETPOST($keyparam, 'alpha') ?GETPOST($keyparam, 'alpha') : ''), 'chaine', 0, '', $conf->entity);
			if (!$res > 0) $error++;
		}
	}

 	if (!$error)
    {
        setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
        $db->commit();
    }
    else
    {
        setEventMessages($langs->trans("Error"), null, 'errors');
        $db->rollback();
    }
}



/**
 * View
 */

$wikihelp = 'EN:Module_Agenda_En|FR:Module_Agenda|ES:Módulo_Agenda';
llxHeader('', $langs->trans("AgendaSetup"), $wikihelp);

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("AgendaSetup"), $linkback, 'title_setup');

print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="save">';

$param = '';
$param .= '&search_event='.urlencode($search_event);

$head = agenda_prepare_head();

dol_fiche_head($head, 'autoactions', $langs->trans("Agenda"), -1, 'action');

print '<span class="opacitymedium">'.$langs->trans("AgendaAutoActionDesc")." ".$langs->trans("OnlyActiveElementsAreShown", 'modules.php').'</span><br>';
print "<br>\n";

print '<div class="div-table-responsive">'; // You can use div-table-responsive-no-min if you dont need reserved height for your table
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td class="liste_titre"><input type="text" name="search_event" value="'.dol_escape_htmltag($search_event).'"></td>';
print '<td class="liste_titre"></td>';
// Action column
print '<td class="liste_titre maxwidthsearch">';
$searchpicto = $form->showFilterButtons();
print $searchpicto;
print '</td>';
print '</tr>';
print '</tr>'."\n";

print '<tr class="liste_titre">';
print '<th class="liste_titre" colspan="2">'.$langs->trans("ActionsEvents").'</th>';
print '<th class="liste_titre"><a href="'.$_SERVER["PHP_SELF"].'?action=selectall'.($param ? $param : '').'">'.$langs->trans("All").'</a>/<a href="'.$_SERVER["PHP_SELF"].'?action=selectnone'.($param ? $param : '').'">'.$langs->trans("None").'</a></th>';
print '</tr>'."\n";
// Show each trigger (list is in c_action_trigger)
if (!empty($triggers))
{
	foreach ($triggers as $trigger)
	{
		$module = $trigger['element'];
		if ($module == 'order_supplier' || $module == 'invoice_supplier') $module = 'fournisseur';
		if ($module == 'shipping') $module = 'expedition_bon';
		if ($module == 'member') $module = 'adherent';
		if ($module == 'project') $module = 'projet';
		if ($module == 'proposal_supplier') $module = 'supplier_proposal';

		// If 'element' value is myobject@mymodule instead of mymodule
		$tmparray = explode('@', $module);
		if (!empty($tmparray[1])) {
			$module = $tmparray[1];
		}

		//print 'module='.$module.'<br>';
		if (!empty($conf->$module->enabled))
		{
			// Discard special case: If option FICHINTER_CLASSIFY_BILLED is not set, we discard both trigger FICHINTER_CLASSIFY_BILLED and FICHINTER_CLASSIFY_UNBILLED
			if ($trigger['code'] == 'FICHINTER_CLASSIFY_BILLED' && empty($conf->global->FICHINTER_CLASSIFY_BILLED)) continue;
			if ($trigger['code'] == 'FICHINTER_CLASSIFY_UNBILLED' && empty($conf->global->FICHINTER_CLASSIFY_BILLED)) continue;

			if ($search_event === '' || preg_match('/'.preg_quote($search_event, '/').'/i', $trigger['code']))
			{
				print '<tr class="oddeven">';
				print '<td>'.$trigger['code'].'</td>';
				print '<td>'.$trigger['label'].'</td>';
				print '<td class="right" width="40">';
				$key = 'MAIN_AGENDA_ACTIONAUTO_'.$trigger['code'];
				$value = $conf->global->$key;
				print '<input class="oddeven" type="checkbox" name="'.$key.'" value="1"'.((($action == 'selectall' || $value) && $action != "selectnone") ? ' checked' : '').'>';
				print '</td></tr>'."\n";
			}
		}
	}
}
print '</table>';
print '</div>';

dol_fiche_end();

print '<div class="center">';
print '<input type="submit" name="save" class="button" value="'.$langs->trans("Save").'">';
print "</div>";

print "</form>\n";


print "<br>";

// End of page
llxFooter();
$db->close();
