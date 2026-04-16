<?php
/* Copyright (C) 2004-2017  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2026		John BOTELLA
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
 * \file    quickmemo/admin/setup.php
 * \ingroup quickmemo
 * \brief   QuickMemo setup page.
 */


// Load Dolibarr environment
require '../../main.inc.php';
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
require_once __DIR__ . '/../lib/quickmemo.lib.php';
require_once __DIR__ . '/../class/memo.class.php';
//require_once "../class/myclass.class.php";

// Translations
$langs->loadLangs(array("admin", "quickmemo"));

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
/** @var HookManager $hookmanager */
$hookmanager->initHooks(array('quickmemosetup', 'globalsetup'));

// Parameters
$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');
$modulepart = GETPOST('modulepart', 'aZ09');	// Used by actions_setmoduleoptions.inc.php

$value = GETPOST('value', 'alpha');
$label = GETPOST('label', 'alpha');
$scandir = GETPOST('scan_dir', 'alpha');
$type = 'myobject';

$error = 0;
$setupnotempty = 0;

// Access control
if (!$user->admin) {
	accessforbidden();
}


// Set this to 1 to use the factory to manage constants. Warning, the generated module will be compatible with version v15+ only
$useFormSetup = 1;

if (!class_exists('FormSetup')) {
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formsetup.class.php';
}
$formSetup = new FormSetup($db);

$item = $formSetup->newItem('QUICKMEMO_COLORS_PRESET');
$item->fieldInputOverride = '
<div class="color-manager">
    <div class="controls">
        <input type="color" id="colorPicker" list="predefinedColors">
		<datalist id="predefinedColors">
		  <option value="#fff8a6">
		  <option value="#ffd6d6">
		  <option value="#d6ffd9">
		  <option value="#d6e6ff">
		  <option value="#f3d6ff">
		  <option value="#ffffff">
		  <option value="#f5f5f5">
		</datalist>
        <button title="'.$langs->trans('Add').'" class="btn-low-emphasis --btn-icon" type="button" id="addColor"><span class="fa fa-plus"></span></button>
    </div>

    <div class="palette" id="palette"></div>

    <div class="colors-container" id="colorsContainer"></div>

    <input type="hidden" name="QUICKMEMO_COLORS_PRESET" id="colorsInput">
</div>';

$setupnotempty += count($formSetup->items);



/*
 * Actions
 */

// For retrocompatibility Dolibarr < 15.0
if (versioncompare(explode('.', DOL_VERSION), array(15)) < 0 && $action == 'update' && !empty($user->admin)) {
	$formSetup->saveConfFromPost();
}

include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';


if (empty($action)) {
	$action = 'edit';
}


/*
 * View
 */

$form = new Form($db);

$help_url = '';
$title = "QuickMemoSetup";

llxHeader('', $langs->trans($title), $help_url, '', 0, 0, '', ['quickmemo/css/setup.css'], '', 'mod-quickmemo page-admin');



?>
<script nonce="<?php print getNonce(); ?>">
document.addEventListener('DOMContentLoaded', function () {

	const PRELOADED_COLORS = <?php print json_encode(Memo::getColorPreset()); ?>;

	const colorPicker = document.getElementById('colorPicker');
	const addColorBtn = document.getElementById('addColor');
	const colorsContainer = document.getElementById('colorsContainer');
	const colorsInput = document.getElementById('colorsInput');

	if (!colorPicker || !addColorBtn || !colorsContainer || !colorsInput) {
		return;
	}


	let colors = [];
	let draggedElement = null;

	const placeholder = document.createElement('div');
	placeholder.className = 'color-placeholder';

	function updateHiddenInput() {
		colorsInput.value = colors.join(',');
	}

	function normalize(color) {
		return color.toLowerCase();
	}

	function rebuildColorsFromDOM() {
		colors = Array.from(colorsContainer.querySelectorAll('.color-badge'))
			.map(el => el.dataset.color);
		updateHiddenInput();
	}

	function addColor(color) {
		color = normalize(color);
		if (colors.includes(color)) return;

		colors.push(color);

		const badge = document.createElement('div');
		badge.className = 'color-badge';
		badge.style.backgroundColor = color;
		badge.dataset.color = color;
		badge.draggable = true;

		const removeBtn = document.createElement('span');
		removeBtn.textContent = '×';

		removeBtn.addEventListener('click', function(e) {
			e.stopPropagation();
			badge.remove();
			rebuildColorsFromDOM();
		});

		badge.addEventListener('dragstart', function () {
			draggedElement = badge;
			badge.classList.add('dragging');

			placeholder.style.width = badge.offsetWidth + 'px';
			placeholder.style.height = badge.offsetHeight + 'px';

			setTimeout(() => {
				badge.style.display = 'none';
			});
		});

		badge.addEventListener('dragend', function () {
			badge.classList.remove('dragging');
			badge.style.display = '';
			placeholder.remove();
			draggedElement = null;
		});

		colorsContainer.addEventListener('dragover', function (e) {
			e.preventDefault();

			const afterElement = getDragAfterElement(colorsContainer, e.clientX);
			if (afterElement == null) {
				colorsContainer.appendChild(placeholder);
			} else {
				colorsContainer.insertBefore(placeholder, afterElement);
			}
		});

		colorsContainer.addEventListener('drop', function (e) {
			e.preventDefault();
			if (!draggedElement) return;

			colorsContainer.insertBefore(draggedElement, placeholder);
			rebuildColorsFromDOM();
		});

		badge.appendChild(removeBtn);
		colorsContainer.appendChild(badge);

		updateHiddenInput();
	}

	function getDragAfterElement(container, x) {
		const elements = [...container.querySelectorAll('.color-badge:not(.dragging)')];

		return elements.reduce((closest, child) => {
			const box = child.getBoundingClientRect();
			const offset = x - box.left - box.width / 2;

			if (offset < 0 && offset > closest.offset) {
				return { offset: offset, element: child };
			} else {
				return closest;
			}
		}, { offset: Number.NEGATIVE_INFINITY }).element;
	}

	addColorBtn.addEventListener('click', function () {
		addColor(colorPicker.value);
	});

	PRELOADED_COLORS.forEach(color => {
		addColor(color);
	});
});
</script>
<?php

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.img_picto($langs->trans("BackToModuleList"), 'back', 'class="pictofixedwidth"').'<span class="hideonsmartphone">'.$langs->trans("BackToModuleList").'</span></a>';

print load_fiche_titre($langs->trans($title), $linkback, 'title_setup');

// Configuration header
$head = quickmemoAdminPrepareHead();
print dol_get_fiche_head($head, 'settings', $langs->trans($title), -1, "fa-sticky-note_fa");

// Setup page goes here
echo '<span class="opacitymedium">'.$langs->trans("QuickMemoSetupPage").'</span><br><br>';


if (!empty($formSetup->items)) {
	print $formSetup->generateOutput(true);
	print '<br>';
}



if (empty($setupnotempty)) {
	print '<br>'.$langs->trans("NothingToSetup");
}

// Page end
print dol_get_fiche_end();

llxFooter();
$db->close();
