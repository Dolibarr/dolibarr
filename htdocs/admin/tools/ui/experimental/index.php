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
 * @var DoliDB      $db
 * @var HookManager $hookmanager
 * @var Translate   $langs
 * @var User        $user
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
$group = 'ExperimentalUx';

// Output html head + body - Param is Title
$documentation->docHeader($langs->trans('ExperimentalUxTitle', $group));

// Set view for menu and breadcrumb
$documentation->view = [$group];

// Output sidebar
$documentation->showSidebar(); ?>

<div class="doc-wrapper">

	<?php $documentation->showBreadCrumb(); ?>

	<div class="doc-content-wrapper">

		<h1 class="documentation-title"><?php echo $langs->trans($group); ?></h1>
		<p class="documentation-text"><?php echo $langs->trans('DocGroupIndexDescription', $group); ?></p>

		<?php /*$documentation->showSummary();*/ ?>

		<div class="documentation-section" id="experimental-ux-introduction" >
			<h2 class="documentation-title"><?php echo $langs->trans('ExperimentalUxIntroductionTitle'); ?></h2>
			<p class="documentation-text"><?php echo $langs->transnoentities('ExperimentalUxIntroductionTxt01'); ?></p>
			<p class="documentation-text"><?php echo $langs->trans('ExperimentalUxIntroductionTxt02'); ?></p>
			<p class="documentation-text"><?php echo img_picto('', 'warning') . ' ' . $langs->trans('ExperimentalUxIntroductionTxt03'); ?></p>
		</div>


		<div class="documentation-section" id="experimental-ux-contribution" >
			<h2 class="documentation-title"><?php echo $langs->trans('ExperimentalUxContributionTitle'); ?></h2>
			<p class="documentation-text"><?php echo $langs->trans('ExperimentalUxContributionTxt01'); ?></p>
			<pre>htdocs/admin/tools/ui/experimental/experiments/A/</pre>
			<p class="documentation-text"><?php echo $langs->trans('ExperimentalUxContributionTxt02'); ?></p>
			<pre>./experiments/A/assets/variant-name.css
./experiments/A/assets/variant-name-1.js
./experiments/A/assets/variant-name-1.css
./experiments/A/assets/variant-name-2.js
./experiments/A/assets/variant-name-2.css
./experiments/A/index.php
</pre>
			<p class="documentation-text"><?php echo $langs->trans('ExperimentalUxContributionTxt03'); ?></p>
			<pre>./experiments/A/variant-name-1.php
./experiments/A/variant-name-2.php
</pre>
			<p class="documentation-text"><?php echo $langs->trans('ExperimentalUxContributionEnd'); ?></p>
		</div>

	</div>

</div>
<?php
// Output close body + html
$documentation->docFooter();
?>
