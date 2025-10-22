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
require '../../../../../../main.inc.php';

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
$experimentName = 'ExperimentalUxIntuitiveSelect';

$experimentAssetsPath = $documentation->baseUrl . '/experimental/experiments/intuitive-select/assets/';
$js = [
	'/includes/ace/src/ace.js',
	'/includes/ace/src/ext-statusbar.js',
	'/includes/ace/src/ext-language_tools.js',
];
$css = [
//	$experimentAssetsPath . '-01.css'
];

// Output html head + body - Param is Title
$documentation->docHeader($langs->trans($experimentName, $group), $js, $css);

// Set view for menu and breadcrumb
$documentation->view = [$group, $experimentName];

// Output sidebar
$documentation->showSidebar();
$form = new Form($db);

?>

<div class="doc-wrapper">

	<?php $documentation->showBreadCrumb(); ?>

	<div class="doc-content-wrapper">

		<h1 class="documentation-title"><?php echo $langs->trans($experimentName); ?></h1>

		<?php $documentation->showSummary(); ?>

		<div class="documentation-section" >
			<h2 class="documentation-title" >Select table lines by <kbd>Ctrl</kbd> + <kbd>Click</kbd>  (Experimental)</h2>


			<h2>Description</h2>
			<p>
				This feature allows users to <strong>select</strong> or <strong>deselect</strong> table rows (<code>&lt;tr&gt;</code>) in an HTML table:
			</p>
			<ul>
				<li>Holding down <strong>Ctrl</strong> (or <strong>Cmd</strong> on Mac) allows users to add or remove individual rows from the selection.</li>
				<li>Holding down <strong>Shift</strong> allows users to <strong>select a range</strong> of rows between the last selected row and the clicked row.</li>
			</ul>

			<h2>Detailed Behavior</h2>
			<table border="1" cellpadding="6" cellspacing="0">
				<thead>
				<tr>
					<th>User Action</th>
					<th>Expected Result</th>
				</tr>
				</thead>
				<tbody>
				<tr>
					<td>Ctrl + Click on a row</td>
					<td>Toggles selection of the clicked row (add/remove).</td>
				</tr>
				<tr>
					<td>Shift + Click on a row</td>
					<td>Selects all rows between the last selected and clicked row.</td>
				</tr>
				</tbody>
			</table>

			<h2>HTML Requirements</h2>
			<p>
				The table should follow a standard structure with <code>&lt;thead&gt;</code> and <code>&lt;tbody&gt;</code> elements,
				and each <code>&lt;tr&gt;</code> must be selectable by applying <code>.row-with-select</code> CSS class and have checkbox with <code>.checkforselect</code> class
			</p>

			<h2>Key Notes</h2>
			<ul>
				<li>The script supports <strong>Cmd</strong> key (for Mac users) as an alternative to <strong>Ctrl</strong>.</li>
				<li>When Shift-clicking without a previously selected row, selection will not start.</li>
				<li>When user start selection, the css rules will remove text selection possibility.</li>
				<li>When the user double-clicks, the selection functionality is reactivated, but the reference to the last selected row is cleared, thus disabling the <code>Shift + Click</code> range selection behavior.</li>
			</ul>


			<div class="documentation-example">
				<div class="div-table-responsive">
					<table class="tagtable liste listwithfilterbefore table-with-select" id="try-line-ctrl-click-feature">
						<thead>
							<tr class="liste_titre_filter">
								<td class="liste_titre center maxwidthsearch">
									<div class="nowraponall">
										<button type="submit" class="liste_titre button_search reposition" name="button_search_x" value="x">
											<span class="fas fa-search"></span>
										</button>
										<button type="submit" class="liste_titre button_removefilter reposition" name="button_removefilter_x" value="x">
											<span class="fas fa-times"></span>
										</button>
									</div>
								</td>
								<td><input class="flat" type="text" name="search_firstname" value=""></td>
								<td><input class="flat" type="text" name="search_lasttname" value=""></td>
								<td class="center"><input class="maxwidth50 flat" type="text" name="search_age" value=""></td>
								<td class="right"><input class="flat" type="text" name="search_country" value=""></td>
							</tr>
							<tr class="liste_titre">
								<th>
									<dl class="dropdown" style="opacity: 0.5;">
										<dt><span class="fas fa-list" style=""></span></dt>
										<dd class="dropdowndd">
											<div class="multiselectcheckboxselectedfields">
												<ul class="selectedfieldsleft"></ul>
											</div>
										</dd>
									</dl>
									<div class="inline-block checkallactions">
										<input type="checkbox" id="checkforselects" name="checkforselects" class="checkallactions" >
										<script nonce="<?php echo getNonce(); ?>" >
											// TODO : Dolibarr use this kind of script inclusion for toggle checkboxes : we need to add a more global js method
											$(document).ready(function() {
												$("#checkforselects").click(function() {
													if($(this).is(':checked')){
														console.log("We check all checkforselect and trigger the change method");
														$(".checkforselect").prop('checked', true).trigger('change');
													}
													else
													{
														console.log("We uncheck all");
														$(".checkforselect").prop('checked', false).trigger('change');
													}
													if (typeof initCheckForSelect == 'function') { initCheckForSelect(0, "massaction", "checkforselect"); } else { console.log("No function initCheckForSelect found. Call won't be done."); }         });
												$(".checkforselect").change(function() {
													// TODO : change this by a simple css rule
													$(this).closest("tr").toggleClass("highlight", this.checked);
												});
											});
										</script>
									</div>
								</th>
								<th class="wrapcolumntitle left" title="First Name">First Name</th>
								<th class="wrapcolumntitle left" title="Last Name">Last Name</th>
								<th class="wrapcolumntitle center" title="Age">Age</th>
								<th class="wrapcolumntitle right" title="Country">Country</th>
							</tr>
						</thead>
						<tbody>
							<tr class="oddeven row-with-select" >
								<td><input class="checkforselect" type="checkbox" name="" value="" ></td>
								<td class="left">John</td>
								<td class="left">Doe</td>
								<td class="center">37</td>
								<td class="right">U.S.A</td>
							</tr>
							<tr class="oddeven row-with-select">
								<td><input class="checkforselect" type="checkbox" name="" value="" ></td>
								<td class="left">Jack</td>
								<td class="left">Sparrow</td>
								<td class="center">29</td>
								<td class="right">Caribbean</td>
							</tr>
							<tr class="oddeven row-with-select">
								<td><input class="checkforselect" type="checkbox" name="" value="" ></td>
								<td class="left">Sacha</td>
								<td class="left">Ketchum</td>
								<td class="center">16</td>
								<td class="right">Kanto</td>
							</tr>
							<tr class="oddeven row-with-select">
								<td><input class="checkforselect" type="checkbox" name="" value="" ></td>
								<td class="left">Albert</td>
								<td class="left">Einstein</td>
								<td class="center">72</td>
								<td class="right">Germany</td>
							</tr>
							<tr class="oddeven row-with-select" >
								<td><input class="checkforselect" type="checkbox" name="" value="" ></td>
								<td class="left">John</td>
								<td class="left">Doe</td>
								<td class="center">37</td>
								<td class="right">U.S.A</td>
							</tr>
							<tr class="oddeven row-with-select">
								<td><input class="checkforselect" type="checkbox" name="" value="" ></td>
								<td class="left">Jack</td>
								<td class="left">Sparrow</td>
								<td class="center">29</td>
								<td class="right">Caribbean</td>
							</tr>
							<tr class="oddeven row-with-select">
								<td><input class="checkforselect" type="checkbox" name="" value="" ></td>
								<td class="left">Sacha</td>
								<td class="left">Ketchum</td>
								<td class="center">16</td>
								<td class="right">Kanto</td>
							</tr>
							<tr class="oddeven row-with-select">
								<td><input class="checkforselect" type="checkbox" name="" value="" ></td>
								<td class="left">Albert</td>
								<td class="left">Einstein</td>
								<td class="center">72</td>
								<td class="right">Germany</td>
							</tr>
							<tr class="oddeven row-with-select" >
								<td><input class="checkforselect" type="checkbox" name="" value="" ></td>
								<td class="left">John</td>
								<td class="left">Doe</td>
								<td class="center">37</td>
								<td class="right">U.S.A</td>
							</tr>
							<tr class="oddeven row-with-select">
								<td><input class="checkforselect" type="checkbox" name="" value="" ></td>
								<td class="left">Jack</td>
								<td class="left">Sparrow</td>
								<td class="center">29</td>
								<td class="right">Caribbean</td>
							</tr>
							<tr class="oddeven row-with-select">
								<td><input class="checkforselect" type="checkbox" name="" value="" ></td>
								<td class="left">Sacha</td>
								<td class="left">Ketchum</td>
								<td class="center">16</td>
								<td class="right">Kanto</td>
							</tr>
							<tr class="oddeven row-with-select">
								<td><input class="checkforselect" type="checkbox" name="" value="" ></td>
								<td class="left">Albert</td>
								<td class="left">Einstein</td>
								<td class="center">72</td>
								<td class="right">Germany</td>
							</tr>
							<tr class="oddeven row-with-select" >
								<td><input class="checkforselect" type="checkbox" name="" value="" ></td>
								<td class="left">John</td>
								<td class="left">Doe</td>
								<td class="center">37</td>
								<td class="right">U.S.A</td>
							</tr>
							<tr class="oddeven row-with-select">
								<td><input class="checkforselect" type="checkbox" name="" value="" ></td>
								<td class="left">Jack</td>
								<td class="left">Sparrow</td>
								<td class="center">29</td>
								<td class="right">Caribbean</td>
							</tr>
							<tr class="oddeven row-with-select">
								<td><input class="checkforselect" type="checkbox" name="" value="" ></td>
								<td class="left">Sacha</td>
								<td class="left">Ketchum</td>
								<td class="center">16</td>
								<td class="right">Kanto</td>
							</tr>
							<tr class="oddeven row-with-select">
								<td><input class="checkforselect" type="checkbox" name="" value="" ></td>
								<td class="left">Albert</td>
								<td class="left">Einstein</td>
								<td class="center">72</td>
								<td class="right">Germany</td>
							</tr>
						</tbody>
					</table>

				</div>
				<style>
					/* Remove text selection */
					.row-with-select[data-is-last-changed] * {
						-webkit-touch-callout: none; /* iOS Safari */
						-webkit-user-select: none; /* Safari */
						-khtml-user-select: none; /* Konqueror HTML */
						-moz-user-select: none; /* Old versions of Firefox */
						-ms-user-select: none; /* Internet Explorer/Edge */
						user-select: none; /* Non-prefixed version, currently
								  supported by Chrome, Edge, Opera and Firefox */
					}
				</style>
				<script nonce="<?php echo getNonce(); ?>" >
					$(function() {

						/**
						 * @param {jQuery}  el
						 * @param {Integer}  status
						 */
						let setLastClickedRowStatus = function (el, status = 1){
							$('.row-with-select').attr('data-is-last-changed', 0);
							el.attr('data-is-last-changed', status === 0 ? 0 : 1);
						}

						/**
						 * Remove data-is-last-changed on double click
						 * Because if data-is-last-changed is present the user can't select text
						 */
						$(document).on("dblclick", ".row-with-select", function(e) {
							$('.row-with-select[data-is-last-changed]').removeAttr( 'data-is-last-changed' );
						});

						/**
						 * DISABLE on click a and button
						 * Because Ctrl + Click on link is also used for open ion a new tab
						 * we need to block select tool
						 */
						$(document).on("click", ".row-with-select a, .row-with-select button", function (e) {
							// we need to block select tool
							if (e.ctrlKey) {
								e.stopPropagation();
							}
						});

						$(document).on("click", ".row-with-select", function (e) {
							let checkBox = $(this).find('.checkforselect');
							let nextCheckStatus = !checkBox.is(':checked')

							if (e.ctrlKey || e.metaKey) {
								// Add line to selection
								if(checkBox){
									checkBox.prop('checked', nextCheckStatus).trigger('change');
								}
								setLastClickedRowStatus($(this), 1);
							}

							if (e.shiftKey) {
								let lastLastChanged = $(this).closest('table').find('.row-with-select[data-is-last-changed="1"]');

								if(lastLastChanged.length>0){
									// Add all lines to selection betwin last selected line
									if($(this).index() === lastLastChanged.index()) {
										return null;
									}

									if($(this).index() < lastLastChanged.index()) {
										$(this).nextUntil(lastLastChanged, ".row-with-select" ).find('.checkforselect').prop('checked', nextCheckStatus).trigger('change');
									}else{
										lastLastChanged.nextUntil($(this), ".row-with-select" ).find('.checkforselect').prop('checked', nextCheckStatus).trigger('change');
									}


									lastLastChanged.find('.checkforselect').prop('checked', nextCheckStatus).trigger('change');
									checkBox.prop('checked', nextCheckStatus).trigger('change');

									setLastClickedRowStatus($(this), 1);
								}
							}
						});
					});

				</script>
			</div>
		</div>


	</div>

</div>
<?php
// Output close body + html
$documentation->docFooter();
?>
