<?php
/* Copyright (C) 2026		John BOTELLA
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
 * \file       htdocs/admin/tools/ui/resources/hidden-conf.php
 * \brief      Documentation page listing hidden configuration constants
 */

// Load Dolibarr environment
require '../../../../main.inc.php';

/**
 * @var DoliDB $db
 * @var Translate $langs
 * @var User $user
 */

// Protection if external user
if ($user->socid > 0) {
	accessforbidden();
}

// Includes
require_once DOL_DOCUMENT_ROOT.'/admin/tools/ui/class/documentation.class.php';
require_once DOL_DOCUMENT_ROOT.'/admin/tools/ui/resources/hidden-conf.lib.php';

// Load documentation translations
$langs->loadLangs(array('uxdocumentation', 'hiddenconf'));

$hidenavmenu = GETPOST('hidenavmenu');

$documentation = new Documentation($db);
$data = getListOfHiddenConf();

// Summary with one link per section
foreach ($data as $sectionKey => $section) {
	$documentation->menu['Resources']['submenu']['HiddenConf']['summary'][$section['label']] = '#hiddenconf-section-'.$sectionKey;
}

// Output html head + body - Param is Title
$documentation->docHeader($langs->trans('DocHiddenConfTitle'), [], ['admin/tools/ui/css/hidden-conf.css'], $hidenavmenu);

// Set view for menu and breadcrumb
// Menu must be set in constructor of documentation class
$documentation->view = array('Resources', 'HiddenConf');
$form = new Form($db);

// Output sidebar
if (!$hidenavmenu) {
	$documentation->showSidebar();
}
?>

<div class="doc-wrapper<?php print $hidenavmenu ? "-bis" : ""; ?>">

		<?php
		if (!$hidenavmenu) {
			$documentation->showBreadCrumb();
		}
		?>

		<div class="doc-content-wrapper">

			<h1 class="documentation-title"><?php print $langs->trans('DocHiddenConfTitle'); ?></h1>
			<p class="documentation-text"><?php print $langs->trans('DocHiddenConfDescription'); ?></p>
			<div class="documentation-disclaimer warning"><?php print $langs->trans('DocHiddenConfDisclaimer'); ?></div>
			<p class="documentation-text"><?php print $langs->trans('DocHiddenConfHowTo', DOL_URL_ROOT.'/admin/const.php'); ?></p>

			<?php hiddenConfPrintObsoleteWarning($data); ?>

			<!-- Summary -->
			<?php $documentation->showSummary(); ?>

			<div class="documentation-section" id="search-form-container">
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
				<div id="hidden-conf-counter"><span class="badge badge-pill badge-light"><?php print $langs->trans('HiddenConfCounter'); ?> <strong class="counter badge badge-primary">--</strong></span></div>
				<div class="search-tool-no-results"></div>
			</div>

			<div id="hidden-conf-search-zone">
				<?php hiddenConfPrintList($data); ?>
			</div>
		</div>
	</div>

<?php
// Output close body + html
$documentation->docFooter();
