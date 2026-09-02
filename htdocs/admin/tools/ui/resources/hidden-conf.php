<?php
/*
 * Copyright (C) 2024 Anthony Damhet <a.damhet@progiseize.fr>
 * Copyright (C) 2026		MDW				<mdeweerd@users.noreply.github.com>
 *
 * This program and files/directory inner it is free software: you can
 * redistribute it and/or modify it under the terms of the
 * GNU Affero General Public License (AGPL) as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AGPL for more details.
 *
 * You should have received a copy of the GNU AGPL
 * along with this program. If not, see <https://www.gnu.org/licenses/agpl-3.0.html>.
 */

// Load Dolibarr environment
require '../../../../main.inc.php';

/**
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Protection if external user
if ($user->socid > 0) {
	accessforbidden();
}

// Includes
require_once DOL_DOCUMENT_ROOT . '/admin/tools/ui/class/documentation.class.php';
require_once __DIR__ . '/hidden-conf-list.lib.php';

// Load documentation translations
$langs->load('uxdocumentation');

//
$documentation = new Documentation($db);

// Output html head + body - Param is Title
$documentation->docHeader('Hidden-confs', [], ['admin/tools/ui/css/hidden-conf.css'], GETPOST('hidenavmenu'));

// Set view for menu and breadcrumb
// Menu must be set in constructor of documentation class
$documentation->view = array('Resources','Hidden-conf');
$form = new Form($db);

$mode = GETPOST('mode'); // ex : no-btn

// Output sidebar
if (!GETPOST('hidenavmenu')) {
	$documentation->showSidebar();
}
?>

<div class="doc-wrapper<?php print GETPOST('hidenavmenu') ? "-bis" : ""; ?>">

		<?php
		if (!GETPOST('hidenavmenu')) {
			$documentation->showBreadCrumb();
		}
		?>

		<div class="doc-content-wrapper">

			<h1 class="documentation-title"><?php echo $langs->trans('DocHiddenConfTitle'); ?></h1>
			<p class="documentation-text"><?php echo $langs->trans('DocHiddenConfDescription'); ?></p>
			<div class="documentation-disclaimer warning"><?php echo $langs->trans('DocHiddenConfDisclaimer'); ?></div>

			<!-- Summary -->
			<?php $documentation->showSummary(); ?>

			<br>

			<div class="documentation-section" id="search-form-container" >
				<?php
				print $form->getSearchFilterToolInput(
					'#hidden-conf-search-zone .conf-item',
					'search-tools-input',
					'',
					[
						'attr' => [
							'data-no-item-target' => '#search-form-container .search-tool-no-results',
							'data-counter-target' => '#search-form-container .counter',
						],
					]
				);
				?>

				<div id="hidden-conf-counter" ><span class="badge badge-pill badge-light">Counter : <strong class="counter badge badge-primary">--</strong></span></div>
				<div class="search-tool-no-results"></div>
			</div>
			<div id="hidden-conf-search-zone" >
			<!-- List of usage font awesome icon -->
			<?php renderHiddenConfList(); ?>
			</div>
		</div>
	</div>

<?php
// Output close body + html
$documentation->docFooter();
