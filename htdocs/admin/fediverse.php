<?php
/* Copyright (C) 2003      Eric Seigne          <erics@rycks.com>
 * Copyright (C) 2003,2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2005-2011 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2011 	   Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2020		Tobias Sekan		<tobias.sekan@startmail.com>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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
 *      \file       htdocs/admin/faitdivers.php
 *      \ingroup    faitdivers
 *      \brief      Page to setupe module Socialnetworks
 */

//load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/socialnetwork.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/modSocialNetworks.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/infobox.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/fediverseparser.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/boxes/box_fediverse.php';



//load translation files requires by the page
$langs->loadLangs(array('admin', 'users', 'dict'));

$action = GETPOST('action', 'aZ09');

// Security check
if (!$user->admin) {
	accessforbidden();
}
if (!isModEnabled('socialnetworks')) {
	accessforbidden('Module Social Networks is not enabled');
}


/*
 * Actions
 */

if ($action == 'add') {
	$error = 0;

	if (empty(GETPOST('socialnetwork_name')) || empty(GETPOST('socialnetwork_url'))) {
		$error++;
	}

	$socialNetworkName = GETPOST('socialnetwork_name', 'alpha');
	$socialNetworkUrl = GETPOST('socialnetwork_url', 'alpha');
	if (!$error) {
		$db->begin();

		$socialNetworkData = array(
			'title' => $socialNetworkName,
			'url' => $socialNetworkUrl
		);

		$boxlabel = '(SocialNetwoksInformations)';

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."boxes_def (file, note)";
		$sql .= " VALUES ('box_fediverse.php', '".$db->escape($socialNetworkName)."')";

		if (!$db->query($sql)) {
			dol_print_error($db);
			$error++;
		} else {
			$jsonData = json_encode($socialNetworkData);
			$result = dolibarr_set_const($db, "SOCIAL_NETWORKS_DATA_".$socialNetworkName, $jsonData, 'chaine', 0, '', $conf->entity);
		}
	}
	if ($result) {
		$db->commit();
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	} else {
		$db->rollback();
		dol_print_error($db);
	}
}

if ($action == 'confirm_delete' && GETPOST('confirm') == 'yes') {
	$error = 0;
	$key = GETPOST('key', 'alpha');
	$name = GETPOST('socialnetwork_name');
	$url = GETPOST('socialnetwork_url');

	$db->begin();

	$sql = "DELETE FROM ".MAIN_DB_PREFIX."boxes";
	$sql .= " WHERE entity = ".$conf->entity;
	$sql .= " AND box_id = ".((int) $key);
	$resql1 = $db->query($sql);

	$sql = "DELETE FROM ".MAIN_DB_PREFIX."boxes_def";
	$sql .= " WHERE rowid = ".((int) $key);
	$resql2 = $db->query($sql);

	if (!$resql1 || !$resql2) {
		$db->rollback();
		dol_print_error($db, "sql=".$sql);
		exit;
	} else {
		$result = dolibarr_del_const($db, "SOCIAL_NETWORKS_DATA_".$name, $conf->entity);
		if ($result) {
			$db->commit();
			header("Location: ".$_SERVER["PHP_SELF"]);
			exit;
		} else {
			$db->rollback();
			dol_print_error($db);
		}
	}
}



/*
 * View
 */

$form = new Form($db);

llxHeader('', $langs->trans("FediverseSetup"), '', '', 0, 0, '', '', '', 'mod-admin page-dict');

$head = socialnetwork_prepare_head();

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print dol_get_fiche_head($head, 'divers', $langs->trans('MenuDict'), -1, 'user', 0, $linkback, '', 0, '', 0);

$title = $langs->trans("ConfigImportSocialNetwork");

print_barre_liste($title, '', $_SERVER["PHP_SELF"], '', '', '', '', -1, '', 'tools', 0, '', '', -1, 0, 0, 0, '');


print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.newToken().'">';

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';

print '<tr class="liste_titre">';
print '<td colspan="2">'.$langs->trans("NewSocialNetwork").'</td>';
print '<td>'.$langs->trans("Example").'</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans("Title").'</td>';
print '<td><input type="text" class="flat minwidth300" name="socialnetwork_name"></td>';
print '<td>Mastodon</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans('SocialNetworkUrl').'</td>';
print '<td><input type="text" class="flat minwidth300" name="socialnetwork_url"></td>';
print '<td>https://mastodon.social/api/v1/accounts/id_user</td>';
print '</tr>';
print '</table>';

print '</div>';

print $form->buttonsSaveCancel("Add", '');
print '<input type="hidden" name="action" value="add">';

print '</form>';

print '<br><br>';
print '<span class="opacitymedium">'.$langs->trans('SocialNetworksNote').'</span>';
print ' - ';
print '<a href="'.DOL_URL_ROOT.'/admin/boxes.php?backtopage='.urlencode($_SERVER["PHP_SELF"]).'">'.$langs->trans('JumpToBoxes').'</a>';
print '<br><br>';


if ($action == 'deletesocialnetwork') {
	$formconfirm = $form->formconfirm(
		$_SERVER["PHP_SELF"].'?key='.urlencode(GETPOST('key', 'alpha')),
		$langs->trans('Delete'),
		$langs->trans('ConfirmDeleteSocialNetwork', GETPOST('key', 'alpha')),
		'confirm_delete',
		'',
		0,
		1
	);
	print $formconfirm;
}
$sql = "SELECT rowid, file, note FROM ".MAIN_DB_PREFIX."boxes_def";
$sql .= " WHERE file = 'box_fediverse.php'";
$sql .= " ORDER BY note";

dol_syslog("select socialnetworks boxes", LOG_DEBUG);
$resql = $db->query($sql);
if ($resql) {
	$boxlist = InfoBox::listBoxes($db, 'activated', -1, null);
	$num = $db->num_rows($resql);
	$i = 0;
	while ($i < $num) {
		$obj = $db->fetch_object($resql);

		$jsonData = getDolGlobalString("SOCIAL_NETWORKS_DATA_".$obj->note);

		$socialNetworkData = json_decode($jsonData, true);

		$socialNetworkTitle = $socialNetworkData['title'];
		$socialNetworkUrl = $socialNetworkData['url'];
		$key = $obj->rowid;

		$fediverseparser = new SocialNetworkManager($socialNetworkTitle);
		$path_fediverse = DOL_DATA_ROOT.'/fediverse/temp/'.$socialNetworkTitle;

		$result = $fediverseparser->fetchPosts($socialNetworkUrl, 5, 300, $path_fediverse);

		print "<br>";
		print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">'."\n";
		print '<input type="hidden" name="token" value="'.newToken().'">'."\n";

		print '<table class="noborder centpercent">'."\n";

		print '<tr class="liste_titre">';
		print "<td>".$langs->trans("SocialNetworks")." ".($i+1)."</td>";
		print '<td class="right">';
		print '<a class="viewfielda reposition marginleftonly marginrighttonly showInputBtn" href="'.$_SERVER["PHP_SELF"].'?action=editsocialnetwork&token='.newToken().'&key='.urlencode($key).'">'.img_edit().'</a>';
		print '<a class="deletefielda reposition marginleftonly right" href="'.$_SERVER["PHP_SELF"].'?action=deletesocialnetwork&token='.newToken().'&key='.urlencode($key).'">'.img_delete().'</a>';
		print '<input type="hidden" name="id" value="'.$key.'">';
		print '</td>';
		print '</tr>'."\n";

		print '<tr class="oddeven">';
		print '<td>'.$langs->trans("Title")."</td>";
		print '<td><input type="text" class="flat minwidth300" name="socialnetwork_name" value="'.dol_escape_htmltag($socialNetworkTitle).'" '.($action != "editsocialnetwork" ? 'disabled' : '').'></td>';
		print '</tr>'."\n";

		print '<tr class="oddeven">';
		print "<td>".$langs->trans("URL")."</td>";
		print '<td><input type="text" class="flat minwidth300" name="socialnetwork_url" value="'.dol_escape_htmltag($socialNetworkUrl).'" '.($action != "editsocialnetwork" ? 'disabled' : '').'></td>';
		print '</tr>'."\n";

		print '<tr class="oddeven">';
		print "<td>".$langs->trans("Status")."</td>";
		print "<td>";
		if ($result > 0 && empty($fediverseparser->error)) {
			print '<span class="ok">'.img_picto($langs->trans("Online"), 'tick', 'class="pictofixedwidth"').$langs->trans("Online").'</div>';
		} else {
			print '<span class="error">'.$langs->trans("Offline");
			$langs->load("errors");
			if ($fediverseparser->error) {
				print ' - '.$langs->trans($fediverseparser->error);
			}
			print '</div>';
		}
		print "</td>";
		print '</tr>'."\n";

		// Active
		$active = _isInBoxListFediverse((int) $key, $boxlist) ? 'yes' : 'no';

		print '<tr class="oddeven">';
		print '<td>'.$langs->trans('WidgetAvailable').'</td>';
		print '<td>'.yn($active);
		print ' &nbsp; - &nbsp; <a href="'.DOL_URL_ROOT.'/admin/boxes.php?backtopage='.urlencode($_SERVER["PHP_SELF"]).'">';
		print $langs->trans("JumpToBoxes");
		print '</a>';
		print '</td>';
		print '</tr>'."\n";

		print '</table>'."\n";

		print "</form>\n";

		$i++;
	}
} else {
	dol_print_error($db);
}

print dol_get_fiche_end();

llxFooter();
$db->close();

/**
 * Check if the given fediverse feed if inside the list of boxes/widgets
 *
 * @param	int		$id		The id of the socialnetwork
 * @param array<int, stdClass> $boxlist A list with boxes/widgets (array of stdClass objects).
 * @return	bool					True if the socialnetwork is inside the box/widget list, otherwise false
 */
function _isInBoxListFediverse(int $id, array $boxlist)
{
	foreach ($boxlist as $box) {
		if ($box->boxcode === "lastfediverseinfos") {
			return true;
		}
	}
	return false;
}
