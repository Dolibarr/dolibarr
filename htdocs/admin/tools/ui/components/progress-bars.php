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
$documentation->docHeader('Progress-bars', $morejs);

// Set view for menu and breadcrumb
// Menu must be set in constructor of documentation class
$documentation->view = array('Components','Progress');

// Output sidebar
$documentation->showSidebar(); ?>

<div class="doc-wrapper">

		<?php $documentation->showBreadCrumb(); ?>

		<div class="doc-content-wrapper">

			<h1 class="documentation-title"><?php echo $langs->trans('DocProgressBarsTitle'); ?></h1>
			<p class="documentation-text"><?php echo $langs->trans('DocProgressBarsMainDescription'); ?></p>

			<!-- Summary -->
			<?php $documentation->showSummary(); ?>

			<!-- Basic usage -->
			<div class="documentation-section" id="progresse-section-basic-usage">
				<h2 class="documentation-title"><?php echo $langs->trans('DocBasicUsage'); ?></h2>
				<p class="documentation-text"><?php echo $langs->trans('DocProgressBarsDescription'); ?></p>
				<div class="documentation-example">

					<?php echo 'Xss'; ?>
					<div class="progress xxs spaced" title="10%">
						<div class="progress-bar" role="progressbar" style="width: 10%" aria-valuenow="10" aria-valuemin="0" aria-valuemax="100"></div>
					</div>

					<?php echo 'Xs'; ?>
					<div class="progress xs spaced" title="20%">
						<div class="progress-bar" role="progressbar" style="width: 20%" aria-valuenow="20" aria-valuemin="0" aria-valuemax="100"></div>
					</div>

					<?php echo 'Sm'; ?>
					<div class="progress sm spaced" title="40%">
						<div class="progress-bar" role="progressbar" style="width: 40%" aria-valuenow="40" aria-valuemin="0" aria-valuemax="100"></div>
					</div>

					<?php echo $langs->trans('Default'); ?>
					<div class="progress" title="80%">
						<div class="progress-bar" role="progressbar" style="width: 80%" aria-valuenow="80" aria-valuemin="0" aria-valuemax="100"></div>
					</div>
				</div>

				<?php
				$lines = array(
					'<div class="progress xxs" title="10%">',
					'    <div class="progress-bar" role="progressbar" style="width: 10%" aria-valuenow="10" aria-valuemin="0" aria-valuemax="100"></div>',
					'</div>',
					'',
					'<div class="progress xs" title="20%">',
					'    <div class="progress-bar" role="progressbar" style="width: 20%" aria-valuenow="20" aria-valuemin="0" aria-valuemax="100"></div>',
					'</div>',
					'',
					'<div class="progress sm" title="40%">',
					'    <div class="progress-bar" role="progressbar" style="width: 40%" aria-valuenow="40" aria-valuemin="0" aria-valuemax="100"></div>',
					'</div>',
					'',
					'<div class="progress" title="80%">',
					'    <div class="progress-bar" role="progressbar" style="width: 80%" aria-valuenow="80" aria-valuemin="0" aria-valuemax="100"></div>',
					'</div>',
				);
				echo $documentation->showCode($lines); ?>

				<p class="documentation-text"><?php echo $langs->trans('DocProgressCanBeSpaced'); ?></p>
				<div class="documentation-example">
					<div class="progress spaced" title="40%">
						<div class="progress-bar" role="progressbar"  style="width: 40%"  aria-valuenow="40" aria-valuemin="0" aria-valuemax="100"></div>
					</div>
				</div>

				<?php
				$lines = array(
					'<div class="progress spaced" title="40%">',
					'    <div class="progress-bar" role="progressbar" style="width: 40%" aria-valuenow="40" aria-valuemin="0" aria-valuemax="100"></div>',
					'</div>',
				);
				echo $documentation->showCode($lines); ?>

			</div>


			<!-- Colors usage -->
			<div class="documentation-section" id="progress-section-color">
				<h2 class="documentation-title"><?php echo $langs->trans('DocColorVariants'); ?></h2>
				<p class="documentation-text"><?php echo $langs->trans('DocColorVariantsDesc'); ?></p>
				<div class="documentation-example">
					<div class="progress spaced" title="40%">
						<div class="progress-bar progress-bar-success" role="progressbar" style="width: 40%" aria-valuenow="40" aria-valuemin="0" aria-valuemax="100"></div>
					</div>
					<div class="progress spaced" title="40%">
						<div class="progress-bar progress-bar-warning" role="progressbar" style="width: 40%" aria-valuenow="40" aria-valuemin="0" aria-valuemax="100"></div>
					</div>
					<div class="progress spaced" title="40%">
						<div class="progress-bar progress-bar-info" role="progressbar" style="width: 40%" aria-valuenow="40" aria-valuemin="0" aria-valuemax="100"></div>
					</div>
					<div class="progress spaced" title="40%">
						<div class="progress-bar progress-bar-danger" role="progressbar" style="width: 40%" aria-valuenow="40" aria-valuemin="0" aria-valuemax="100"></div>
					</div>
				</div>

				<?php
				$lines = array(
					'<div class="progress" title="40%">',
					'    <div class="progress-bar progress-bar-success" role="progressbar" style="width: 40%" aria-valuenow="40" aria-valuemin="0" aria-valuemax="100"></div>',
					'</div>',
					'',
					'<div class="progress" title="40%">',
					'    <div class="progress-bar progress-bar-warning" role="progressbar" style="width: 40%" aria-valuenow="40" aria-valuemin="0" aria-valuemax="100"></div>',
					'</div>',
					'',
					'<div class="progress" title="40%">',
					'    <div class="progress-bar progress-bar-info" role="progressbar" style="width: 40%" aria-valuenow="40" aria-valuemin="0" aria-valuemax="100"></div>',
					'</div>',
					'',
					'<div class="progress" title="40%">',
					'    <div class="progress-bar progress-bar-danger" role="progressbar" style="width: 40%" aria-valuenow="40" aria-valuemin="0" aria-valuemax="100"></div>',
					'</div>',
					'',

				);
				echo $documentation->showCode($lines); ?>
			</div>


			<!-- Striped usage -->
			<div class="documentation-section" id="progresse-section-stripped">
				<h2 class="documentation-title"><?php echo $langs->trans('DocStripedVariants'); ?></h2>
				<p class="documentation-text"><?php echo $langs->trans('DocStripedVariantsDesc'); ?></p>

				<div class="documentation-example">
					<div class="progress spaced progress-striped" title="40%">
						<div class="progress-bar progress-bar-success" role="progressbar" style="width: 40%" aria-valuenow="40" aria-valuemin="0" aria-valuemax="100"></div>
					</div>
					<div class="progress spaced progress-striped" title="40%">
						<div class="progress-bar progress-bar-warning" role="progressbar" style="width: 40%" aria-valuenow="40" aria-valuemin="0" aria-valuemax="100"></div>
					</div>
					<div class="progress spaced progress-striped" title="40%">
						<div class="progress-bar progress-bar-info" role="progressbar" style="width: 40%" aria-valuenow="40" aria-valuemin="0" aria-valuemax="100"></div>
					</div>
					<div class="progress spaced progress-striped" title="40%">
						<div class="progress-bar progress-bar-danger" role="progressbar" style="width: 40%" aria-valuenow="40" aria-valuemin="0" aria-valuemax="100"></div>
					</div>
				</div>

				<?php
				$lines = array(
					'<div class="progress progress-striped" title="40%">',
					'    <div class="progress-bar progress-bar-success" role="progressbar" style="width: 40%" aria-valuenow="40" aria-valuemin="0" aria-valuemax="100"></div>',
					'</div>',
					'',
					'<div class="progress progress-striped" title="40%">',
					'    <div class="progress-bar progress-bar-warning" role="progressbar" style="width: 40%" aria-valuenow="40" aria-valuemin="0" aria-valuemax="100"></div>',
					'</div>',
					'',
					'<div class="progress progress-striped" title="40%">',
					'    <div class="progress-bar progress-bar-info" role="progressbar" style="width: 40%" aria-valuenow="40" aria-valuemin="0" aria-valuemax="100"></div>',
					'</div>',
					'',
					'<div class="progress progress-striped" title="40%">',
					'    <div class="progress-bar progress-bar-danger" role="progressbar" style="width: 40%" aria-valuenow="40" aria-valuemin="0" aria-valuemax="100"></div>',
					'</div>',
					'',

				);
				echo $documentation->showCode($lines); ?>
			</div>
			<!--  -->


			<!-- other usage -->
			<div class="documentation-section" id="progresse-section-stripped">
				<h2 class="documentation-title"><?php echo $langs->trans('DocOtherVariants'); ?></h2>
				<p class="documentation-text"><?php echo $langs->trans('DocOtherVariantsDesc'); ?></p>

				<div class="documentation-example">
					.progress-bar-consumed
					<div class="progress spaced progress-bar-consumed" title="40%">
						<div class="progress-bar" role="progressbar" style="width: 40%" aria-valuenow="40" aria-valuemin="0" aria-valuemax="100"></div>
					</div>
					.progress-bar-consumed-late
					<div class="progress spaced progress-bar-consumed" title="40%">
						<div class="progress-bar" role="progressbar" style="width: 40%" aria-valuenow="40" aria-valuemin="0" aria-valuemax="100"></div>
					</div>
				</div>

				<?php
				$lines = array(
					'<div class="progress progress-striped" title="40%">',
					'    <div class="progress-bar progress-bar-success" role="progressbar" style="width: 40%" aria-valuenow="40" aria-valuemin="0" aria-valuemax="100"></div>',
					'</div>',
					'',
					'<div class="progress progress-striped" title="40%">',
					'    <div class="progress-bar progress-bar-warning" role="progressbar" style="width: 40%" aria-valuenow="40" aria-valuemin="0" aria-valuemax="100"></div>',
					'</div>',
					'',
					'<div class="progress progress-striped" title="40%">',
					'    <div class="progress-bar progress-bar-info" role="progressbar" style="width: 40%" aria-valuenow="40" aria-valuemin="0" aria-valuemax="100"></div>',
					'</div>',
					'',
					'<div class="progress progress-striped" title="40%">',
					'    <div class="progress-bar progress-bar-danger" role="progressbar" style="width: 40%" aria-valuenow="40" aria-valuemin="0" aria-valuemax="100"></div>',
					'</div>',
					'',
					'',

				);
				echo $documentation->showCode($lines); ?>
			</div>
			<!--  -->


		</div>

	</div>

<?php
// Output close body + html
$documentation->docFooter();
?>
