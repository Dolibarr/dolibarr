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

// Load documentation translations
$langs->load('uxdocumentation');

//
$documentation = new Documentation($db);

$morejs = [
	'/includes/ace/src/ace.js',
	'/includes/ace/src/ext-statusbar.js',
	'/includes/ace/src/ext-language_tools.js',
];
// Output html head + body - Param is Title
$documentation->docHeader('Contributing', $morejs);

// Set view for menu and breadcrumb
// Menu must be set in constructor of documentation class
$documentation->view = array('Resources', 'Contributing');

// Output sidebar
$documentation->showSidebar(); ?>

<div class="doc-wrapper">

	<?php $documentation->showBreadCrumb(); ?>

	<div class="doc-content-wrapper">

		<h1 class="documentation-title"><?php echo $langs->trans('DocHowContribute'); ?></h1>
		<p class="documentation-text"><?php echo $langs->trans('DocHowContributeDescription'); ?></p>

		<!-- Summary -->
		<?php $documentation->showSummary(); ?>

		<!-- First Step -->
		<div class="documentation-section" id="contributesection-step1">
			<h2 class="documentation-title"><?php echo $langs->trans('DocContributeStep1'); ?></h2>
			<p class="documentation-text"><?php echo $langs->trans('DocContributeStep1Description'); ?></p>

			<?php
			$lines = array(
				'<?php',
				'/*',
				' * Copyright (C) 2024 Anthony Damhet <a.damhet@progiseize.fr>',
				' * Copyright (C) 2024 Frédéric France <frederic.france@free.fr>',
				' *',
				' * This program is free software; you can redistribute it and/or modify',
				' * it under the terms of the GNU General Public License as published by',
				' * the Free Software Foundation; either version 3 of the License, or',
				' * (at your option) any later version.',
				' *',
				' * This program is distributed in the hope that it will be useful,',
				' * but WITHOUT ANY WARRANTY; without even the implied warranty of',
				' * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the',
				' * GNU General Public License for more details.',
				' *',
				' * You should have received a copy of the GNU General Public License',
				' * along with this program. If not, see <https://www.gnu.org/licenses/>.',
				' */',
				'',
				'// Load Dolibarr environment',
				'require \'../../../../main.inc.php\';',
				'',
				'/**',
				' * @var DoliDB $db',
				' * @var HookManager $hookmanager',
				' * @var Translate $langs',
				' * @var User $user',
				' */',
				'',
				'// Protection if external user',
				'if ($user->socid > 0) {',
				'	accessforbidden();',
				'}',
				'',
				'// Includes',
				'require_once DOL_DOCUMENT_ROOT . \'/admin/tools/ui/class/documentation.class.php\';',
				'',
				'// Load documentation translations',
				'$langs->load(\'uxdocumentation\');',
				'',
				'// Hooks',
				'$hookmanager->initHooks(array(\'uidocumentation\'));',
				'',
				'//',
				'$documentation = new Documentation($db);',
				'',
				'// Add more js',
				'$morejs = [',
				'	\'/includes/ace/src/ace.js\',',
				'	\'/includes/ace/src/ext-statusbar.js\',',
				'	\'/includes/ace/src/ext-language_tools.js\',',
				'];',
				'// Output html head + body - First param is title',
				'$documentation->docHeader(\'DocMyPageTitle\', $morejs);',
				'',
				'// Set view for menu and breadcrumb',
				'// Menu must be set in constructor of documentation class',
				'$documentation->view = array(\'MyPageKey1\', \'MyPageKey2\');',
				'',
				'// Output sidebar',
				'$documentation->showSidebar(); ?>',
				'',
				'<div class="doc-wrapper">',
				'',
				'	<?php $documentation->showBreadCrumb(); ?>',
				'	<div class="doc-content-wrapper">',
				'	',
				'		<h1 class="documentation-title"><?php print $langs->trans(\'DocMyPageTitle\'); ?></h1>',
				'		<p class="documentation-text"><?php print $langs->trans(\'DocMyPageDescription\'); ?></p>',
				'		',
				'		<!-- Summary -->',
				'		<?php $documentation->showSummary(); ?>',
				'		',
				'		<!-- Section 1 -->',
				'		<div class="documentation-section" id="my-section-name">',
				'		',
				'			<h2 class="documentation-title"><?php print $langs->trans(\'DocMySectionTitle\'); ?></h2>',
				'			<p class="documentation-text"><?php print $langs->trans(\'DocMySectionText\'); ?></p>',
				'			',
				'			<div class="documentation-example">',
				'				<div class="div-table-responsive">',
				'					<p><?php print $langs->trans(\'DocMySectionExample\'); ?></p>',
				'				</div>',
				'			</div>',
				'			<?php',
				'			$lines = array(',
				'				\'<div class="div-table-responsive">\',',
				'				\'	<p>Here you can put an example of your component</p>\',',
				'				\'</div>\',',
				'			);',
				'			print $documentation->showCode($lines, \'html\'); ?>',
				'			',
				'			<p class="documentation-text"><?php print $langs->trans(\'DocMySectionText2\'); ?></p>',
				'		</div>',
				'		',
				'		<!-- Section 2-->',
				'		<div class="documentation-section" id="my-section2-name">',
				'		',
				'			<h2 class="documentation-title"><?php print $langs->trans(\'DocMySectionTitle\'); ?></h2>',
				'			<p class="documentation-text"><?php print $langs->trans(\'DocMySectionText\'); ?></p>',
				'			',
				'			<!-- Display messages -->',
				'			<div class="warning"><?php print $langs->trans(\'DocExampleWarning\'); ?></div>',
				'			<div class="info"><?php print $langs->trans(\'DocExampleInfo\'); ?></div>',
				'			<div class="error"><?php print $langs->trans(\'DocExampleError\'); ?></div>',
				'			<div class="green"><?php print $langs->trans(\'DocExampleGreen\'); ?></div>',
				'		</div>',
				'		',
				'	</div>',
				'</div>',
				'',
				'<?php',
				'// Output close body + html',
				'$documentation->docFooter();',
				'?>',
			);
			echo $documentation->showCode($lines, 'php'); ?>
		</div>

		<!-- Second Step -->
		<div class="documentation-section" id="contributesection-step2">

			<h2 class="documentation-title"><?php print $langs->trans('DocContributeStep2'); ?></h2>
			<p class="documentation-text"><?php print $langs->trans('DocContributeStep2Description'); ?></p>
			<p class="documentation-text"><?php print $langs->trans('DocContributeAddMenuEntry'); ?></p>

			<?php
			$lines = array(
				'<?php',
				'// in SetMenu() function, search "Components" and fill submenu',
				'	',
				'// Components',
				'$this->menu[\'Components\'] = array(',
				'	// url,',
				'	// icon,',
				'	\'submenu\' => array(',
				'		',
				'		// Others menu entries ...',
				'		',
				'		// My new menu entry',
				'		\'MyComponent\' => array(',
				'			// Url to my documentation page',
				'			\'url\' => dol_buildpath($this->baseUrl.\'/components/mycomponenturl.php\', 1),',
				'			// My component icon, use fontawesome class',
				'			\'icon\' => \'fas fa-mouse\', // use fontawesome class here',
				'			// You can add another submenu into this array',
				'			\'submenu\' => array(),',
				'			// Here is for build summary (LangKeySection => nameOfYourDiv)',
				'			\'summary\' => array(',
				'				\'MyLangKey1\' => \'#my-component-section1-div\',',
				'				\'MyLangKey2\' => \'#my-component-section2-div\',',
				'			),',
				'		),',
				'	)',
				');',
			);
			echo $documentation->showCode($lines, 'php'); ?>
		</div>

		<!-- Third Step -->
		<div class="documentation-section" id="contributesection-step3">

			<h2 class="documentation-title"><?php print $langs->trans('DocContributeStep3'); ?></h2>
			<p class="documentation-text"><?php print $langs->trans('DocContributeStep3Description'); ?></p>

			<?php
			$lines = array(
				'<?php',
				'',
				'// Set view for menu and breadcrumb',
				'$documentation->view = array(\'Components\', \'MyComponent\');',
			);
			echo $documentation->showCode($lines, 'php'); ?>
		</div>

	</div>

</div>

<?php
// Output close body + html
$documentation->docFooter();
?>
