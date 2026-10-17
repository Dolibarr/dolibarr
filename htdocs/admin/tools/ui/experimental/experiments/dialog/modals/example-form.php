<?php
// Load Dolibarr environment
require '../../../../../../../main.inc.php';

/**
 * @var DoliDB $db
 * @var Translate $langs
 * @var User $user
 */

if ($user->socid > 0) {
	accessforbidden();
}

// Load translations
$langs->loadLangs(array('ticket', 'uxdocumentation'));

// Form class
$form = new Form($db);

// --- Get data attributes ---
$useAjax = GETPOSTINT('useAjax');

// Form params

if ($useAjax) {
	$formID = 'dol-dialog-ajaxform-example';
	$formAction = dol_buildpath('/admin/tools/ui/experimental/experiments/dialog/ajax/actions_modals.php', 1);
	$formClass = 'dol-dialog-ajax';
} else {
	$formID = 'dol-dialog-form-example';
	$formAction = dol_buildpath('/admin/tools/ui/experimental/experiments/dialog/index.php', 1);
	$formClass = '';
}

print '<form class="'.$formClass.'" id="'.$formID.'" action="'.$formAction.'" method="POST">';

	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="addticketexample">';

	// Ref
	print '<div>';
		print '<label for="ref" class="bold paddingbottom" style="display:block;">Ref</label>';
		print '<input type="text" name="ref" id="ref" class="centpercent">';
	print '</div>';

	// Type
	$arrayTypes = array(
		'commercial' => 'Commercial question',
		'help' => 'Request for functional help',
		'issue' => 'Issue or bug',
	);
	print '<div class="margintoponly marginbottomonly">';
		print '<label for="type_code" class="bold paddingbottom" style="display:block;">Request type</label>';
		print $form->selectarray('type_code', $arrayTypes, '', 0, 0, 0, '', 0, 0, 0, '', 'centpercent');
	print '</div>';

	// Thirdparty
	print '<div class="margintoponly marginbottomonly">';
		print '<label for="socid" class="bold paddingbottom" style="display:block;">Thirdparty</label>';
		print $form->select_company('', 'socid', '', '', 0, 0, array(), 10, 'centpercent');
	print '</div>';

	// Description
	print '<div class="margintoponly marginbottomonly">';
		print '<label for="description" class="bold paddingbottom" style="display:block;">Description</label>';
		print '<textarea name="description" id="description" class="centpercent" style="resize:vertical;max-height:200px;"></textarea>';
	print '</div>';

	print '</form>';


	print '<div class="dol-dialog-footer">';
	print '<button type="button" class="dialog-btn dialog-btn-destructive" data-dol-dialog-close>Annuler</button>';
	print '<button type="submit" form="'.$formID.'" class="dialog-btn dialog-btn-primary">Valider</button>';
	print '</div>';
