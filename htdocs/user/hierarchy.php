<?php
/* Copyright (C) 2005      Matthieu Valleton    <mv@seeschloss.org>
 * Copyright (C) 2005      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2006-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007      Patrick Raguin       <patrick.raguin@gmail.com>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2019-2021  Frédéric France     <frederic.france@netlogic.fr>
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
 *      \file       htdocs/user/hierarchy.php
 *      \ingroup    user
 *      \brief      Page of hierarchy view of user module
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/treeview.lib.php';

// Load translation files required by page
$langs->loadLangs(array('users', 'companies'));

// Security check (for external users)
$socid = 0;
if ($user->socid > 0) {
	$socid = $user->socid;
}

$optioncss = GETPOST('optioncss', 'alpha');
$contextpage = GETPOST('optioncss', 'aZ09');
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');

// Load mode employee
$mode = GETPOST("mode", 'alpha');

$search_statut = GETPOST('search_statut', 'int');

if ($search_statut == '' || $search_statut == '0') {
	$search_statut = '1';
}

if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // Both test are required to be compatible with all browsers
	$search_statut = "";
}

$userstatic = new User($db);

// Define value to know what current user can do on users
$canadduser = (!empty($user->admin) || $user->rights->user->user->creer);

if (!$user->rights->user->user->lire && !$user->admin) {
	accessforbidden();
}

$childids = $user->getAllChildIds(1);


/*
 * View
 */

$form = new Form($db);

$help_url = 'EN:Module_Users|FR:Module_Utilisateurs|ES:M&oacute;dulo_Usuarios|DE:Modul_Benutzer';
$title = $langs->trans("Users");
$arrayofjs = array(
	'/includes/jquery/plugins/jquerytreeview/jquery.treeview.js',
	'/includes/jquery/plugins/jquerytreeview/lib/jquery.cookie.js',
);
$arrayofcss = array('/includes/jquery/plugins/jquerytreeview/jquery.treeview.css');

llxHeader('', $title, $help_url, '', 0, 0, $arrayofjs, $arrayofcss, '', 'bodyforlist');


// Load hierarchy of users
$user_arbo = $userstatic->get_full_tree(0, ($search_statut != '' && $search_statut >= 0) ? "statut = ".$search_statut : '');


// Count total nb of records
$nbtotalofrecords = count($user_arbo);


if (!is_array($user_arbo) && $user_arbo < 0) {
	setEventMessages($userstatic->error, $userstatic->errors, 'warnings');
} else {
	// Define fulltree array
	$fulltree = $user_arbo;
	//var_dump($fulltree);
	// Define data (format for treeview)
	$data = array();
	$data[] = array('rowid'=>0, 'fk_menu'=>-1, 'title'=>"racine", 'mainmenu'=>'', 'leftmenu'=>'', 'fk_mainmenu'=>'', 'fk_leftmenu'=>'');
	foreach ($fulltree as $key => $val) {
		$userstatic->id = $val['id'];
		$userstatic->ref = $val['id'];
		$userstatic->login = $val['login'];
		$userstatic->firstname = $val['firstname'];
		$userstatic->lastname = $val['lastname'];
		$userstatic->statut = $val['statut'];
		$userstatic->email = $val['email'];
		$userstatic->gender = $val['gender'];
		$userstatic->socid = $val['fk_soc'];
		$userstatic->admin = $val['admin'];
		$userstatic->entity = $val['entity'];
		$userstatic->photo = $val['photo'];

		$entity = $val['entity'];
		$entitystring = '';

		// TODO Set of entitystring should be done with a hook
		if (!empty($conf->multicompany->enabled) && is_object($mc)) {
			if (empty($entity)) {
				$entitystring = $langs->trans("AllEntities");
			} else {
				$mc->getInfo($entity);
				$entitystring = $mc->label;
			}
		}

		$li = $userstatic->getNomUrl(-1, '', 0, 1);
		if (!empty($conf->multicompany->enabled) && $userstatic->admin && !$userstatic->entity) {
			$li .= img_picto($langs->trans("SuperAdministrator"), 'redstar');
		} elseif ($userstatic->admin) {
			$li .= img_picto($langs->trans("Administrator"), 'star');
		}
		$li .= ' <span class="opacitymedium">('.$val['login'].($entitystring ? ' - '.$entitystring : '').')</span>';

		$entry = '<table class="nobordernopadding centpercent"><tr class="trtree"><td class="'.($val['statut'] ? 'usertdenabled' : 'usertddisabled').'">'.$li.'</td><td align="right" class="'.($val['statut'] ? 'usertdenabled' : 'usertddisabled').'">'.$userstatic->getLibStatut(2).'</td></tr></table>';

		$data[] = array(
			'rowid'=>$val['rowid'],
			'fk_menu'=>$val['fk_user'],
			'statut'=>$val['statut'],
			'entry'=>$entry
		);
	}

	//var_dump($data);

	$param = "&search_statut=".urlencode($search_statut);

	$newcardbutton = '';
	$newcardbutton .= dolGetButtonTitle($langs->trans('ViewList'), '', 'fa fa-bars paddingleft imgforviewmode', DOL_URL_ROOT.'/user/list.php?mode=common'.preg_replace('/(&|\?)*mode=[^&]+/', '', $param), '', ((empty($mode) || $mode == 'common') ? 2 : 1), array('morecss'=>'reposition'));
	$newcardbutton .= dolGetButtonTitle($langs->trans('HierarchicView'), '', 'fa fa-stream paddingleft imgforviewmode', DOL_URL_ROOT.'/user/hierarchy.php?mode=hierarchy'.preg_replace('/(&|\?)*mode=[^&]+/', '', $param), '', (($mode == 'hierarchy') ? 2 : 1), array('morecss'=>'reposition'));
	$newcardbutton .= dolGetButtonTitle($langs->trans('ViewKanban'), '', 'fa fa-th-list imgforviewmode', DOL_URL_ROOT.'/user/list.php?mode=kanban'.preg_replace('/(&|\?)*mode=[^&]+/', '', $param), '', ($mode == 'kanban' ? 2 : 1), array('morecss'=>'reposition'));
	$newcardbutton .= dolGetButtonTitleSeparator();
	$newcardbutton .= dolGetButtonTitle($langs->trans('NewUser'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/user/card.php?action=create'.($mode == 'employee' ? '&employee=1' : '').'&leftmenu=', '', $canadduser);

	$massactionbutton = '';
	$num = 0;
	$limit = 0;

	print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'user', 0, $newcardbutton, '', $limit, 0, 0, 1);

	print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">'."\n";
	if ($optioncss != '') {
		print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	}
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="page" value="'.$page.'">';
	print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';
	print '<input type="hidden" name="mode" value="'.$mode.'">';

	print '<div class="div-table-responsive">'; // You can use div-table-responsive-no-min if you dont need reserved height for your table
	print '<table class="liste nohover centpercent">';

	print '<tr class="liste_titre_filter">';
	print '<td class="liste_titre">&nbsp;</td>';
	print '<td class="liste_titre">&nbsp;</td>';
	// Status
	print '<td class="liste_titre right">';
	print $form->selectarray('search_statut', array('-1'=>'', '1'=>$langs->trans('Enabled')), $search_statut, 0, 0, 0, '', 0, 0, 0, '', 'minwidth75imp');
	print '</td>';
	print '<td class="liste_titre maxwidthsearch">';
	$searchpicto = $form->showFilterAndCheckAddButtons(0);
	print $searchpicto;
	print '</td>';
	print '</tr>';

	print '<tr class="liste_titre">';
	print_liste_field_titre("HierarchicView");
	print_liste_field_titre('<div id="iddivjstreecontrol"><a href="#">'.img_picto('', 'folder', 'class="paddingright"').'<span class="hideonsmartphone">'.$langs->trans("UndoExpandAll").'</span></a> | <a href="#">'.img_picto('', 'folder-open', 'class="paddingright"').'<span class="hideonsmartphone">'.$langs->trans("ExpandAll").'</span></a></div>', $_SERVER['PHP_SELF'], "", '', "", 'align="center"');
	print_liste_field_titre("Status", $_SERVER['PHP_SELF'], "", '', "", 'align="right"');
	print_liste_field_titre('', $_SERVER["PHP_SELF"], "", '', '', '', '', '', 'maxwidthsearch ');
	print '</tr>';


	$nbofentries = (count($data) - 1);

	if ($nbofentries > 0) {
		print '<tr><td colspan="3">';
		tree_recur($data, $data[0], 0);
		print '</td>';
		print '<td></td>';
		print '</tr>';
	} else {
		print '<tr class="oddeven">';
		print '<td colspan="3">';
		print '<table class="nobordernopadding"><tr class="nobordernopadding"><td>'.img_picto_common('', 'treemenu/branchbottom.gif').'</td>';
		print '<td valign="middle">';
		print $langs->trans("NoCategoryYet");
		print '</td>';
		print '<td>&nbsp;</td>';
		print '</table>';
		print '</td>';
		print '<td></td>';
		print '</tr>';
	}

	print "</table>";
	print '</div>';

	print "</form>\n";
}

//
/*print '<script type="text/javascript">
jQuery(document).ready(function() {
	function init_myfunc()
	{
		jQuery(".usertddisabled").hide();
	}
	init_myfunc();
});
</script>';
*/

// End of page
llxFooter();
$db->close();
