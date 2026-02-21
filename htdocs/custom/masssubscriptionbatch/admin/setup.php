<?php
/* Copyright (C) 2026 */

require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once dol_buildpath('/custom/masssubscriptionbatch/lib/masssubscriptionbatch.lib.php', 0);

$langs->loadLangs(array('admin', 'members', 'masssubscriptionbatch@masssubscriptionbatch'));

if (!$user->admin) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');

if ($action == 'setdefaultsend') {
	dolibarr_set_const($db, 'MASSSUBSCRIPTIONBATCH_DEFAULT_SENDMAIL', GETPOSTINT('MASSSUBSCRIPTIONBATCH_DEFAULT_SENDMAIL'), 'yesno', 0, '', $conf->entity);
	setEventMessages($langs->trans('SetupSaved'), null, 'mesgs');
}

$page_name = 'MassSubscriptionBatchSetup';

llxHeader('', $langs->trans($page_name));

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans('BackToModuleList').'</a>';
print load_fiche_titre($langs->trans($page_name), $linkback, 'title_setup');

$head = masssubscriptionbatchAdminPrepareHead();
print dol_get_fiche_head($head, 'settings', $langs->trans('Module106500Name'), -1, 'payment');

print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="setdefaultsend">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td colspan="2">'.$langs->trans('Parameters').'</td></tr>';
print '<tr class="oddeven">';
print '<td>'.$langs->trans('MassSubscriptionBatchDefaultSendMail').'</td>';
print '<td>';
print '<input type="checkbox" name="MASSSUBSCRIPTIONBATCH_DEFAULT_SENDMAIL" value="1"'.(getDolGlobalInt('MASSSUBSCRIPTIONBATCH_DEFAULT_SENDMAIL') ? ' checked' : '').'>';
print '</td>';
print '</tr>';
print '</table>';

print '<div class="center">';
print '<input class="button button-save" type="submit" value="'.$langs->trans('Save').'">';
print '</div>';
print '</form>';

print dol_get_fiche_end();

llxFooter();
$db->close();
