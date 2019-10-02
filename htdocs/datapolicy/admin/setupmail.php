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

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formadmin.class.php';
require_once '../lib/datapolicy.lib.php';

// Translations
$langs->loadLangs(array('admin', 'companies', 'members', 'datapolicy'));


// Parameters
$action = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$formadmin = new FormAdmin($db);

if (GETPOST('l')) {
    $l = GETPOST('l');
} else {
    $l = $langs->defaultlang;
}
// Access control
if (!$user->admin)
    accessforbidden();

/*
 * Actions
 */

include DOL_DOCUMENT_ROOT . '/core/actions_setmoduleoptions.inc.php';

if ($action == 'setvalue' && $user->admin) {
    $db->begin();
    $sub = "DATAPOLICIESSUBJECT_" . $l;
    $result = dolibarr_set_const($db, $sub, GETPOST($sub), 'chaine', 0, '', $conf->entity);
    $cont = "DATAPOLICIESCONTENT_" . $l;
    $result = dolibarr_set_const($db, $cont, GETPOST($cont), 'chaine', 0, '', $conf->entity);
    $cont = "TXTLINKDATAPOLICIESACCEPT_" . $l;
    $result = dolibarr_set_const($db, $cont, GETPOST($cont), 'chaine', 0, '', $conf->entity);
    $cont = "TXTLINKDATAPOLICIESREFUSE_" . $l;
    $result = dolibarr_set_const($db, $cont, GETPOST($cont), 'chaine', 0, '', $conf->entity);
    $sub = "DATAPOLICIESACCEPT_" . $l;
    $result = dolibarr_set_const($db, $sub, GETPOST($sub), 'chaine', 0, '', $conf->entity);
    $sub = "DATAPOLICIESREFUSE_" . $l;
    $result = dolibarr_set_const($db, $sub, GETPOST($sub), 'chaine', 0, '', $conf->entity);
    if (!$result > 0)
        $error++;
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

$page_name = "datapolicySetup";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="' . ($backtopage ? $backtopage : DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans("BackToModuleList") . '</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'object_datapolicy@datapolicy');

// Configuration header
$head = datapolicyAdminPrepareHead();
dol_fiche_head($head, 'settings', '', -1, "datapolicy@datapolicy");


print "<script type='text/javascript'>
        $(document).ready(function(){
         $('#default_lang').change(function(){
         lang=$('#default_lang').val();
                    window.location.replace('" . $_SERVER['PHP_SELF'] . "?l='+lang);
                    });
        });
</script>";

print '<form method="post" action="' . $_SERVER["PHP_SELF"] . '?l=' . $l . '">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="setvalue">';
print '<table>';
if ($conf->global->MAIN_MULTILANGS) {
    print '<tr><td>' . $form->editfieldkey('DefaultLang', 'default_lang', '', null, 0) . '</td><td colspan="3" class="maxwidthonsmartphone">' . "\n";
    print $formadmin->select_language((GETPOST('l') ? GETPOST('l') : $langs->defaultlang), 'default_lang', 0, 0, 1, 0, 0, 'maxwidth200onsmartphone');
    print '</tr>';
}
$subject = 'DATAPOLICIESSUBJECT_' . $l;
$linka = 'TXTLINKDATAPOLICIESACCEPT_' . $l;
$linkr = 'TXTLINKDATAPOLICIESREFUSE_' . $l;
$content = 'DATAPOLICIESCONTENT_' . $l;
$acc = 'DATAPOLICIESACCEPT_' . $l;
$ref = 'DATAPOLICIESREFUSE_' . $l;
print '<tr class"oddeven"><td class="fieldrequired">';
print $langs->trans('DATAPOLICIESSUBJECTMAIL') . '</td><td>';
print '<input type="text" size="100" name="' . $subject . '" value="' . $conf->global->$subject . '" />';
print '</td><tr>';
print '<tr class"oddeven"><td class="fieldrequired">';
print $langs->trans('DATAPOLICIESCONTENTMAIL').'</td><td>';
print $langs->trans('DATAPOLICIESSUBSITUTION');echo'__LINKACCEPT__,__LINKREFUSED__,__FIRSTNAME__,__NAME__,__CIVILITY__';
$doleditor = new DolEditor($content, $conf->global->$content, '', 250, 'Full', '', false, true, 1, 200, 70);
$doleditor->Create();
print '</td><tr>';
print '<tr class"oddeven"><td class="fieldrequired">';
print $langs->trans('TXTLINKDATAPOLICIESACCEPT') . '</td><td>';
print '<input type="text" size="200" name="' . $linka . '" value="' . $conf->global->$linka . '" />';
print '</td><tr>';
print '<tr class"oddeven"><td class="fieldrequired">';
print $langs->trans('TXTLINKDATAPOLICIESREFUSE') . '</td><td>';
print '<input type="text" size="200" name="' . $linkr . '" value="' . $conf->global->$linkr . '" />';
print '</td><tr>';
print '<tr class"oddeven"><td class="fieldrequired">';

print $langs->trans('DATAPOLICIESACCEPT').'</td><td>';

$doleditor = new DolEditor($acc, $conf->global->$acc, '', 250, 'Full', '', false, true, 1, 200, 70);
$doleditor->Create();
print '</td><tr>';
print '<tr class"oddeven"><td class="fieldrequired">';
print $langs->trans('DATAPOLICIESREFUSE').'</td><td>';

print $langs->trans('');
$doleditor = new DolEditor($ref, $conf->global->$ref, '', 250, 'Full', '', false, true, 1, 200, 70);
$doleditor->Create();
print '</td><tr>';
print '</table>';

print '<br><center><input type="submit" class="button" value="' . $langs->trans("Modify") . '"></center>';

print '</form>';

dol_fiche_end();

print '<br><br>';

print $langs->trans('SendAgreementText');
print '<a class="button" href="'.dol_buildpath('/datapolicy/mailing.php').'">'.$langs->trans('SendAgreement').'</a>';

llxFooter();
$db->close();
