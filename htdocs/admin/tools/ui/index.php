<?php
/*
 * Copyright (C) 2024 Anthony Damhet <a.damhet@progiseize.fr>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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

// Load Dolibarr environment
require '../../../main.inc.php';

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
require_once DOL_DOCUMENT_ROOT . '/admin/tools/ui/class/documentation.class.php';

// Load documentation translations
$langs->load('uxdocumentation');

//
$documentation = new Documentation($db);

// Output html head + body - Param is Title
$documentation->docHeader();

// Set view for menu and breadcrumb
$documentation->view = array('DocumentationHome');

// Output sidebar
$documentation->showSidebar(); ?>

<div class="doc-wrapper">

	<?php $documentation->showBreadCrumb(); ?>

	<div class="doc-content-wrapper">
		<h1 class="documentation-title"><?php echo $langs->trans('WelcomeToDocumentation'); ?></h1>
		<p class="documentation-text"><?php echo $langs->trans('WelcomeToDocumentationDescription'); ?></p>

		<div class="doclinks-section">
			<h2 class="doclinks-title"><?php echo $langs->trans('DocLinkSectionResources'); ?></h2>
			<div class="doclinks-wrapper" style="justify-content: space-between;">
				<a class="doc-link size-default" href="<?php print dol_buildpath($documentation->baseUrl.'/resources/contributing.php', 1); ?>">
					<div class="link-title"><span class="fas fa-info-circle paddingright"></span> <?php print $langs->trans('DocHowContribute'); ?></div>
					<div class="link-content"><?php print $langs->trans('DocHowContributeDescription'); ?></div>
				</a>
				<a class="doc-link size-default" href="https://www.dolibarr.fr/forum/" target="_blank">
					<div class="link-title"><span class="fas fa-external-link-alt paddingright"></span> Dolibarr community</div>
					<div class="link-content">Meet and chat with the Dolibarr community on the dedicated forum</div>
				</a>
			</div>
		</div>

		<?php
		$indexMenu = $documentation->menu;

		// Remove BackToDolibarr and Documentation Home from menu
		// Remove Resources from menu (Set manually above)
		unset($indexMenu['BackToDolibarr']);
		unset($indexMenu['DocumentationHome']);
		unset($indexMenu['Resources']);

		if (!empty($indexMenu)) {
			foreach ($indexMenu as $keyMenu => $infosMenu) {
				print '<div class="doclinks-section">';
				print '<h2 class="doclinks-title">'.$langs->trans($keyMenu).'</h2>';
				print '<div class="doclinks-wrapper flex-fix" style="justify-content: flex-start;">';
				if (!empty($infosMenu['submenu'])) {
					foreach ($infosMenu['submenu'] as $keySubmenu => $infosSubmenu) {
						print '<a href="'.$infosSubmenu['url'].'" class="doc-link size-small">';
							print '<div class="link-icon"><span class="'.$infosSubmenu['icon'].'"></span></div>';
							print '<div class="link-title">'.$langs->trans($keySubmenu).'</div>';
						print '</a>';
					}
				}
				print '</div>';
				print '</div>';
			}
		}
		?>
	</div>

</div>

<?php
// Output close body + html
$documentation->docFooter();
?>
