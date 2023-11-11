<?php
/* Copyright (C) 2004-2017  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2018       Nicolas ZABOURI         <info@inovea-conseil.com>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    htdocs/datapolicy/admin/setupmail.php
 * \ingroup datapolicy
 * \brief   Datapolicy setup page to define email content end send email for end user agreement.
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';
require_once DOL_DOCUMENT_ROOT.'/datapolicy/lib/datapolicy.lib.php';

// Translations
$langs->loadLangs(array('admin', 'companies', 'members', 'datapolicy'));


// Parameters
$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');

if (GETPOST('l')) {
	$l = GETPOST('l');
} else {
	$l = $langs->defaultlang;
}

// Security
if (!isModEnabled("datapolicy")) {
	accessforbidden();
}
if (!$user->admin) {
	accessforbidden();
}


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';

if ($action == 'setvalue' && $user->admin) {
	$db->begin();
	$sub = "DATAPOLICYSUBJECT_".$l;
	$result = dolibarr_set_const($db, $sub, GETPOST($sub), 'chaine', 0, '', $conf->entity);
	$cont = "DATAPOLICYCONTENT_".$l;
	$result = dolibarr_set_const($db, $cont, GETPOST($cont), 'chaine', 0, '', $conf->entity);
	$cont = "TXTLINKDATAPOLICYACCEPT_".$l;
	$result = dolibarr_set_const($db, $cont, GETPOST($cont), 'chaine', 0, '', $conf->entity);
	$cont = "TXTLINKDATAPOLICYREFUSE_".$l;
	$result = dolibarr_set_const($db, $cont, GETPOST($cont), 'chaine', 0, '', $conf->entity);
	$sub = "DATAPOLICYACCEPT_".$l;
	$result = dolibarr_set_const($db, $sub, GETPOST($sub), 'chaine', 0, '', $conf->entity);
	$sub = "DATAPOLICYREFUSE_".$l;
	$result = dolibarr_set_const($db, $sub, GETPOST($sub), 'chaine', 0, '', $conf->entity);
	if (!($result > 0)) {
		$error++;
	}
	if (!$error) {
		$db->commit();
		setEventMessage($langs->trans("SetupSaved"));
	} else {
		$db->rollback();
		dol_print_error($db);
	}
}


/*
 * View
 */

$formadmin = new FormAdmin($db);

$page_name = "datapolicySetup";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'generic');

// Configuration header
$head = datapolicyAdminPrepareHead();
print dol_get_fiche_head($head, 'emailing', '', -1, '');


print "<script type='text/javascript'>
        $(document).ready(function(){
         $('#default_lang').change(function(){
         lang=$('#default_lang').val();
                    window.location.replace('" . $_SERVER['PHP_SELF']."?l='+lang);
                    });
        });
</script>";

print '<form method="post" action="'.$_SERVER["PHP_SELF"].'?l='.$l.'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="setvalue">';
print '<table>';
if (getDolGlobalInt('MAIN_MULTILANGS')) {
	print '<tr><td>'.$form->editfieldkey('DefaultLang', 'default_lang', '', null, 0).'</td><td colspan="3" class="maxwidthonsmartphone">'."\n";
	print img_picto('', 'language', 'class="pictofixedwidth"');
	print $formadmin->select_language((GETPOST('l') ? GETPOST('l') : $langs->defaultlang), 'default_lang', 0, 0, 1, 0, 0, 'maxwidth200onsmartphone');
	print '</tr>';
}
$subject = 'DATAPOLICYSUBJECT_'.$l;
$linka = 'TXTLINKDATAPOLICYACCEPT_'.$l;
$linkr = 'TXTLINKDATAPOLICYREFUSE_'.$l;
$content = 'DATAPOLICYCONTENT_'.$l;
$acc = 'DATAPOLICYACCEPT_'.$l;
$ref = 'DATAPOLICYREFUSE_'.$l;
print '<tr class"oddeven"><td class="fieldrequired">';
print $langs->trans('DATAPOLICYSUBJECTMAIL').'</td><td>';
print '<input type="text" name="'.$subject.'" value="' . getDolGlobalString($subject).'" />';
print '</td><tr>';
print '<tr class"oddeven"><td class="fieldrequired">';
print $langs->trans('DATAPOLICYCONTENTMAIL').'</td><td>';
print '<span class="opacitymedium">';
print $langs->trans('DATAPOLICYSUBSITUTION');
print '__LINKACCEPT__,__LINKREFUSED__,__FIRSTNAME__,__NAME__,__CIVILITY__';
print '</span>';
$doleditor = new DolEditor($content, $conf->global->$content, '', 250, 'Full', '', false, true, 1, 200, 70);
$doleditor->Create();
print '</td><tr>';
print '<tr class"oddeven"><td class="fieldrequired">';
print $langs->trans('TXTLINKDATAPOLICYACCEPT').'</td><td>';
print '<input type="text" name="'.$linka.'" value="' . getDolGlobalString($linka).'" />';
print '</td><tr>';
print '<tr class"oddeven"><td class="fieldrequired">';
print $langs->trans('TXTLINKDATAPOLICYREFUSE').'</td><td>';
print '<input type="text" name="'.$linkr.'" value="' . getDolGlobalString($linkr).'" />';
print '</td><tr>';
print '<tr class"oddeven"><td class="fieldrequired">';

print $langs->trans('DATAPOLICYACCEPT').'</td><td>';

$doleditor = new DolEditor($acc, getDolGlobalString($acc), '', 250, 'Full', '', false, true, 1, 200, 70);
$doleditor->Create();
print '</td><tr>';
print '<tr class"oddeven"><td class="fieldrequired">';
print $langs->trans('DATAPOLICYREFUSE').'</td><td>';

print $langs->trans('');
$doleditor = new DolEditor($ref, getDolGlobalString($ref), '', 250, 'Full', '', false, true, 1, 200, 70);
$doleditor->Create();
print '</td><tr>';
print '</table>';

print '<br><center><input type="submit" class="button button-edit" value="'.$langs->trans("Modify").'"></center>';

print '</form>';

print dol_get_fiche_end();

llxFooter();
$db->close();
