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

// Hooks
$hookmanager->initHooks(array('uidocumentation'));

//
$documentation = new Documentation($db);

// Output html head + body - Param is Title
$documentation->docHeader('Tables');

// Set view for menu and breadcrumb
// Menu must be set in constructor of documentation class
$documentation->view = array('Content','Tables');

// Output sidebar
$documentation->showSidebar(); ?>

<div class="doc-wrapper">

		<?php $documentation->showBreadCrumb(); ?>

		<div class="doc-content-wrapper">

			<h1 class="documentation-title"><?php echo $langs->trans('DocTableTitle'); ?></h1>
			<p class="documentation-text"><?php echo $langs->trans('Description'); ?></p>

			<!-- Summary -->
			<?php $documentation->showSummary(); ?>

			<!-- Basic usage -->
			<div class="documentation-section" id="tablesection-basicusage">

				<h2 class="documentation-title">Table with a title line</h2>

				<p class="documentation-text"><?php echo $langs->trans('DocTableBasicDescription'); ?></p>
				<div class="documentation-example">
					<div class="div-table-responsive">
						<table class="tagtable noborder liste nobottomiftotal">
							<tr class="liste_titre">
								<th class="wrapcolumntitle left liste_titre" title="First Name">First Name</th>
								<th class="wrapcolumntitle left liste_titre" title="Last Name">Last Name</th>
								<th class="wrapcolumntitle center liste_titre" title="Age">Age</th>
								<th class="wrapcolumntitle right liste_titre" title="Country">Country</th>
							</tr>
							<tr class="oddeven">
								<td class="left">John</td>
								<td class="left">Doe</td>
								<td class="center">37</td>
								<td class="right">U.S.A</td>
							</tr>
							<tr class="oddeven">
								<td class="left">Jack</td>
								<td class="left">Sparrow</td>
								<td class="center">29</td>
								<td class="right">Caribbean</td>
							</tr>
							<tr class="oddeven">
								<td class="left">Sacha</td>
								<td class="left">Ketchum</td>
								<td class="center">16</td>
								<td class="right">Kanto</td>
							</tr>
							<tr class="oddeven">
								<td class="left">Albert</td>
								<td class="left">Einstein</td>
								<td class="center">72</td>
								<td class="right">Germany</td>
							</tr>
						</table>
					</div>
				</div>
				<?php
				$lines = array(
				);
				echo $documentation->showCode($lines); ?>
			</div>

			<!-- Table with filters -->
			<div class="documentation-section" id="tablesection-withfilters">

				<h2 class="documentation-title">Table with a filter line and title line</h2>

				<p class="documentation-text"><?php echo $langs->trans('DocTableWithFiltersDescription'); ?></p>
				<div class="documentation-example">
					<div class="div-table-responsive">
						<table class="tagtable noborder nobottomiftotal liste">
							<tr class="liste_titre_filter">
								<td><input></td>
								<td></td>
								<td></td>
								<td></td>
							</tr>
							<tr class="liste_titre">
								<th class="wrapcolumntitle left liste_titre" title="First Name">First Name</th>
								<th class="wrapcolumntitle left liste_titre" title="Last Name">Last Name</th>
								<th class="wrapcolumntitle center liste_titre" title="Age">Age</th>
								<th class="wrapcolumntitle right liste_titre" title="Country">Country</th>
							</tr>
							<tr class="oddeven">
								<td class="left">John</td>
								<td class="left">Doe</td>
								<td class="center">37</td>
								<td class="right">U.S.A</td>
							</tr>
							<tr class="oddeven">
								<td class="left">Jack</td>
								<td class="left">Sparrow</td>
								<td class="center">29</td>
								<td class="right">Caribbean</td>
							</tr>
							<tr class="oddeven">
								<td class="left">Sacha</td>
								<td class="left">Ketchum</td>
								<td class="center">16</td>
								<td class="right">Kanto</td>
							</tr>
							<tr class="oddeven">
								<td class="left">Albert</td>
								<td class="left">Einstein</td>
								<td class="center">72</td>
								<td class="right">Germany</td>
							</tr>
						</table>
					</div>
				</div>
				<?php
				$lines = array(
				);
				echo $documentation->showCode($lines); ?>
			</div>


			<!-- Table with no filter and no title line -->
			<div class="documentation-section" id="tablesection-withfilters">

				<h2 class="documentation-title">Table with no filter, no title line</h2>

				<p class="documentation-text"><?php echo $langs->trans('Description'); ?></p>
				<div class="documentation-example">
					<div class="div-table-responsive">
						<table class="tagtable noborder nobottomiftotal liste">
							<tr class="oddeven trfirstline">
								<td class="left">John</td>
								<td class="left">Doe</td>
								<td class="center">37</td>
								<td class="right">U.S.A</td>
							</tr>
							<tr class="oddeven">
								<td class="left">Jack</td>
								<td class="left">Sparrow</td>
								<td class="center">29</td>
								<td class="right">Caribbean</td>
							</tr>
							<tr class="oddeven">
								<td class="left">Sacha</td>
								<td class="left">Ketchum</td>
								<td class="center">16</td>
								<td class="right">Kanto</td>
							</tr>
							<tr class="oddeven trlastline">
								<td class="left">Albert</td>
								<td class="left">Einstein</td>
								<td class="center">72</td>
								<td class="right">Germany</td>
							</tr>
						</table>
					</div>
				</div>
				<?php
				$lines = array(
				);
				echo $documentation->showCode($lines); ?>
			</div>

		</div>

	</div>

<?php
// Output close body + html
$documentation->docFooter();
?>
