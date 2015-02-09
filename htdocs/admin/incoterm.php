<?php

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

$langs->load("admin");
$langs->load("incoterm");
$langs->load("errors");

if (! $user->admin) accessforbidden();

$action = GETPOST('action','alpha');
$error = false;

/*
 * Actions
 */
 if ($action == 'switch_incoterm') 
 {	
	$value = dolibarr_get_const($db, 'INCOTERM_ACTIVATE');
	
	if (!empty($value)) $res = dolibarr_set_const($db, 'INCOTERM_ACTIVATE', 0);
	else $res = dolibarr_set_const($db, 'INCOTERM_ACTIVATE', 1);
	
	
	if (!$res) $error++;
	
	if (!$error) 
	{
		setEventMessage($langs->trans("SetupSaved"));
	}
	else
    {
        setEventMessage($langs->trans("Error"),'errors');
    }
 }

/*
 * View
 */
$form=new Form($db);

llxHeader("","");

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("IncotermSetup"),$linkback,'setup');
print '<br>';


$h = 0;

$head[$h][0] = DOL_URL_ROOT."/admin/incoterm.php";
$head[$h][1] = $langs->trans("Setup");
$h++;

dol_fiche_head($head, $hselected, $langs->trans("ModuleSetup"));

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("IncotermSetupTitle1").'</td>';
print '<td width="20"></td>';
print '<td align="center" width="100">'.$langs->trans("IncotermSetupTitle2").'</td>';
print '</tr>';

print '<tr class="impair">';
print '<td>'.$langs->trans('IncotermFunctionDesc').'</td>';
print '<td width="20"></td>';
print '<td width="100" align="center"><a href="'.$_SERVER["PHP_SELF"].'?action=switch_incoterm">';

if (!empty($conf->global->INCOTERM_ACTIVATE)) {
	print img_picto($langs->trans("Enabled"),'switch_on');
} else {
	print img_picto($langs->trans("Disabled"),'switch_off');
}

print '</a></td>';
print '</tr>';

print '';

print '</table>';



llxFooter();
$db->close();



