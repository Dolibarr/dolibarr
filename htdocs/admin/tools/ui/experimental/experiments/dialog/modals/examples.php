<?php
// Load Dolibarr environment
require '../../../../../../../main.inc.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var Translate $langs
 * @var User $user
 */

if ($user->socid > 0) {
	accessforbidden();
}

// Load documentation translations
$langs->load('uxdocumentation');

// --- Example to show: all the dialogs of the documentation page call this file with their own code ---
$example = GETPOST('example', 'aZ09');

switch ($example) {
	case 'simple':
		print '<p class="nomargintop">Hey <b>'.dol_escape_htmltag($user->login).'</b>, I\'m the new native HTML dialog. I\'m here to replace those old jQuery UI modals.</p>';

		print '<h4 class="nomarginbottom">Positioning</h4>';
		print '<p class="nomargintop">You can display me centered or anchored to the right, depending on your needs.</p>';

		print '<h4 class="nomarginbottom">Header & Footer</h4>';
		print '<p class="nomargintop">My header and footer are fully customizable. Remove them entirely if you don\'t need them.</p>';

		print '<h4 class="nomarginbottom">Need to pass data?</h4>';
		print '<p class="nomargintop">Just use data attributes, it\'s that simple.</p>';

		print '<h4 class="nomarginbottom">Form handling</h4>';
		print '<p class="nomargintop">I handle forms with ease, whether you prefer Ajax or standard submissions.</p>';

		print '<h4 class="nomarginbottom">Advanced options</h4>';
		print '<p class="nomargintop">I also come with cool options like size definitions and HTML persistence in the DOM.</p>';
		print '<p class="nomargintop"><b>Want to know everything I can do? Check out the documentation!</b></p>';
		break;

	case 'basic-multi':
		// --- Get data attributes (each trigger passes its own values) ---
		$productId = GETPOST('productId', 'int');
		$productRef = GETPOST('productRef', 'alpha');
		$productType = GETPOST('productType', 'alpha');

		print '<div class="modal-example">';
		print '<p class="nomargintop">This dialog was opened by one of several buttons sharing the same class selector, armed in a single call. The values below come from the button you clicked — proof that each trigger loads its own content:</p>';
		print '<p>';
		print '<b>ID:</b> '.$productId.'<br>';
		print '<b>Reference:</b> '.$productRef.'<br>';
		print '<b>Type:</b> '.ucfirst($productType);
		print '</p>';
		print '<p class="opacitymedium"><i class="fas fa-info-circle"></i> Curious how these values are passed to the dialog? See the <a href="#dialogsection-data" data-dol-dialog-close>Variables</a> section.</p>';
		print '</div>';

		print '<div class="dol-dialog-footer">';
		print '<button class="dialog-btn dialog-btn-primary" style="margin:0;" data-dol-dialog-close>OK</button>';
		print '</div>';
		break;

	case 'header':
		// --- Get data attributes ---
		$dialogHeader = GETPOST('dialogHeader', 'alphanohtml');

		if ($dialogHeader == 'no') {
			print '<div class="center">';
			print '<h4 class="nomargintop nomarginbottom">No header? No problem.</h4>';
			print '<p class="nomargintop">Perfect for lightweight confirmations, quick alerts, or minimal prompts where you just need a simple message.</p>';
			print '<div class="center" style="margin-top:24px;">';
			print '<button class="dialog-btn dialog-btn-primary" style="margin:0;" data-dol-dialog-close>OK</button>';
			print '</div>';
			print '</div>';
		} elseif ($dialogHeader == 'icon') {
			print '<p class="nomargintop">Use the icon parameter to display any icon of your choice in the dialog. Just pass FontAwesome class of your icon in params.</p>';
			print '<h4 class="nomargintop nomarginbottom">Examples</h4>';
			print '<p class="nomargintop">icon: \'fas fa-flask\'<br>';
			print 'icon: \'fas fa-dolly infobox-order_supplier\'</p>';
			print '<div class="right" style="margin-top:24px;">';
			print '<button class="dialog-btn dialog-btn-primary" style="margin:0;" data-dol-dialog-close>OK</button>';
			print '</div>';
		} elseif ($dialogHeader == 'iconcolor') {
			print '<p class="nomargintop">There is also a setting that allows you to control the color of the icon: icon_color</p>';
			print '<h4 class="nomargintop nomarginbottom">Examples</h4>';
			print '<p class="nomargintop">icon: \'fas fa-landmark\', icon_color: \'#b0bb39\'<br>';
			print 'icon: \'fas fa-cube\', icon_color: \'#a69944\'</p>';
			print '<div class="right" style="margin-top:24px;">';
			print '<button class="dialog-btn dialog-btn-primary" style="margin:0;" data-dol-dialog-close>OK</button>';
			print '</div>';
		}
		break;

	case 'data':
		// --- Get data attributes ---
		$productId = GETPOST('productId', 'int');
		$productRef = GETPOST('productRef', 'alpha');
		$productPrice = GETPOST('productPrice', 'alpha');
		$productType = GETPOST('productType', 'alpha');

		print '<div class="modal-example">';
		print '<p class="nomargintop">Just set a data- attribute to your button in kebab-case and grab it in camelCase. E.g. `data-product-ref` becomes `GETPOST(\'productRef\')`.</p>';
		print '<p>';
		print '<b>ID:</b> '.$productId.'<br>';
		print '<b>Reference:</b> '.$productRef.'<br>';
		print '<b>Type:</b> '.ucfirst($productType).'<br>';
		print '<b>Price:</b> '.price($productPrice).' '.$conf->currency;
		print '</p>';
		print '</div>';

		print '<div class="dol-dialog-footer">';
		print '<button class="dialog-btn dialog-btn-primary" style="margin:0;" data-dol-dialog-close>OK</button>';
		print '</div>';
		break;

	case 'form':
		// Load translations
		$langs->load('ticket');

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
		print '<button type="button" class="dialog-btn dialog-btn-destructive" data-dol-dialog-close>Cancel</button>';
		print '<button type="submit" form="'.$formID.'" class="dialog-btn dialog-btn-primary">Validate</button>';
		print '</div>';
		break;

	case 'size':
		require_once DOL_DOCUMENT_ROOT.'/admin/tools/ui/class/documentation.class.php';

		print '<h4 class="nomarginbottom nomargintop">A lot of text</h4>';
		print '<p class="nomargintop">'.Documentation::generateLoremIpsum(1, 100, false).'</p>';

		print Documentation::generateLoremIpsum(10, 70, true);

		print '<div class="dol-dialog-footer">';
		print '<button class="dialog-btn dialog-btn-primary" style="margin:0;" data-dol-dialog-close>OK</button>';
		print '</div>';
		break;

	case 'no-animation':
		print '<p class="nomargintop">I am a dialog that opens instantly without any opening animation, meaning I appear immediately in the DOM with no transition effects or visual delays.</p>';

		print '<div class="dol-dialog-footer">';
		print '<button class="dialog-btn dialog-btn-primary" style="margin:0;" data-dol-dialog-close>OK</button>';
		print '</div>';
		break;

	case 'persist':
		// --- Get data attributes ---
		$persist = GETPOSTINT('persist');

		// Form params
		$formID = 'dol-dialog-persist';
		$formAction = '#';

		print '<form class="dol-dialog-ajax" id="'.$formID.'" action="'.$formAction.'" method="POST">';

		if ($persist) {
			print '<p class="nomargintop">I am a persistent dialog. Once opened, I remain in the DOM. Try filling in the field below, then close me. When you reopen this dialog, the value you entered will still be in the field.</p>';
			print '<label for="" class="bold paddingbottom" style="display:block;">My persistent value</label>';
			print '<input type="text" name="persistantvalue" id="persistantvalue" class="centpercent">';
		} else {
			print '<p class="nomargintop">I am a non-persistent dialog. Once closed, I am removed from the DOM. Try filling in the field below, then close me. When you reopen this dialog, the value you entered will no longer be there because the dialog is rebuilt from scratch each time.</p>';
			print '<label for="" class="bold paddingbottom" style="display:block;">My persistent value</label>';
			print '<input type="text" name="nonpersistantvalue" id="nonpersistantvalue" class="centpercent">';
		}
		print '</form>';

		print '<div class="dol-dialog-footer">';
		print '<button class="dialog-btn dialog-btn-primary" data-dol-dialog-close>Close dialog</button>';
		print '</div>';
		break;

	default:
		// Unknown example: uiDialog() shows its error message when the HTTP code is not a success
		http_response_code(404);
}
