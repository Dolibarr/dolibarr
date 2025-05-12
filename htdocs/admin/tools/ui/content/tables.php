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
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';

// Load documentation translations
$langs->load('uxdocumentation');

// Hooks
$hookmanager->initHooks(array('uidocumentation'));

//
$documentation = new Documentation($db);
$form = new Form($db);

$morejs = [
	'/includes/ace/src/ace.js',
	'/includes/ace/src/ext-statusbar.js',
	'/includes/ace/src/ext-language_tools.js',
];
// Output html head + body - Param is Title
$documentation->docHeader('Tables', $morejs);

// Set view for menu and breadcrumb
// Menu must be set in constructor of documentation class
$documentation->view = array('Content','Tables');

// Output sidebar
$documentation->showSidebar(); ?>

<div class="doc-wrapper">

		<?php $documentation->showBreadCrumb(); ?>

		<div class="doc-content-wrapper">

			<h1 class="documentation-title"><?php echo $langs->trans('DocTableTitle'); ?></h1>
			<p class="documentation-text"><?php echo $langs->trans('DocTableMainDescription'); ?></p>
			<p class="documentation-text"><?php echo $langs->trans('DocTableMainDescriptionNote'); ?></p>

			<!-- Summary -->
			<?php $documentation->showSummary(); ?>

			<!-- Basic usage -->
			<div class="documentation-section" id="tablesection-basicusage">

				<h2 class="documentation-title"><?php echo $langs->trans('DocTableBasic'); ?></h2>

				<p class="documentation-text"><?php echo $langs->trans('DocTableBasicDescription'); ?></p>
				<div class="documentation-example">
					<div class="div-table-responsive">
						<table class="tagtable liste" id="we-recommend-always-adding-a-unique-identifier">
							<thead>
								<tr class="liste_titre">
									<th class="wrapcolumntitle left liste_titre" data-col="ProductRef" title="<?php echo dol_escape_htmltag($langs->trans('ProductRef')); ?>"><?php echo $langs->trans('ProductRef'); ?></th>
									<th class="wrapcolumntitle center liste_titre" data-col="qty" title="<?php echo dol_escape_htmltag($langs->trans('Qty')); ?>"><?php echo $langs->trans('Qty'); ?></th>
									<th class="wrapcolumntitle right liste_titre" data-col="amount-no-tax" title="<?php echo dol_escape_htmltag($langs->trans('AmountHT')); ?>"><?php echo $langs->trans('AmountHT'); ?></th>
									<th class="wrapcolumntitle right liste_titre" data-col="total-no-tax" title="<?php echo dol_escape_htmltag($langs->trans('TotalHT')); ?>"><?php echo $langs->trans('TotalHT'); ?></th>
								</tr>
							</thead>
							<tbody>
								<tr class="oddeven">
									<td class="left" data-col="ProductRef" >My Product A</td>
									<td class="center" data-col="qty" >13</td>
									<td class="right amount" data-col="amount-no-tax" ><?php echo price(9.99, 0, '', 1, -1, -1, 'auto'); ?></td>
									<td class="right amount" data-col="total-no-tax" ><?php echo price(129.87, 0, '', 1, -1, -1, 'auto'); ?></td>
								</tr>
								<tr class="oddeven">
									<td class="left" data-col="ProductRef" >My Product B</td>
									<td class="center" data-col="qty" >21</td>
									<td class="right amount" data-col="amount-no-tax" ><?php echo price(13.37, 0, '', 1, -1, -1, 'auto'); ?></td>
									<td class="right amount" data-col="total-no-tax" ><?php echo price(280.77, 0, '', 1, -1, -1, 'auto'); ?></td>
								</tr>
								<tr class="oddeven">
									<td class="left" data-col="ProductRef" >My Product C</td>
									<td class="center" data-col="qty" >7</td>
									<td class="right amount" data-col="amount-no-tax" ><?php echo price(16.66, 0, '', 1, -1, -1, 'auto'); ?></td>
									<td class="right amount" data-col="total-no-tax" ><?php echo price(116.62, 0, '', 1, -1, -1, 'auto'); ?></td>
								</tr>
							</tbody>
							<tfoot>
								<tr class="liste_total">
									<td class="left" data-col="ProductRef" >Total</td>
									<td class="center" data-col="qty" >41</td>
									<td class="right amount" data-col="amount-no-tax" >--</td>
									<td class="right amount" data-col="total-no-tax" ><?php echo price(527.26, 0, '', 1, -1, -1, 'auto'); ?></td>
								</tr>
							</tfoot>
						</table>
					</div>
				</div>
				<?php
				$lines = array(
					'<table class="tagtable liste" id="we-recommend-always-adding-a-unique-identifier" >',
					'',
					'	<!-- Table header -->',
					'	<thead>',
					'		<tr class="liste_titre">',
					'			<th class="wrapcolumntitle left liste_titre" data-col="ProductRef" title="<?php echo dol_escape_htmltag($langs->trans(\'ProductRef\')); ?>"><?php echo $langs->trans(\'ProductRef\'); ?></th>',
					'			<th class="wrapcolumntitle left liste_titre" data-col="qty" title="<?php echo dol_escape_htmltag($langs->trans(\'Qty\')); ?>"><?php echo $langs->trans(\'Qty\'); ?></th>',
					'			<th class="wrapcolumntitle center liste_titre" data-col="amount-no-tax" title="<?php echo dol_escape_htmltag($langs->trans(\'AmountHT\')); ?>"><?php echo $langs->trans(\'AmountHT\'); ?></th>',
					'			<th class="wrapcolumntitle right liste_titre" data-col="total-no-tax" title="<?php echo dol_escape_htmltag($langs->trans(\'TotalHT\')); ?>"><?php echo $langs->trans(\'TotalHT\'); ?></th>',
					'		</tr>',
					'	</thead>',
					'',
					'	<tbody>',
					'		<!-- Data lines -->',
					'		<tr class="oddeven">',
					'			<td class="left" data-col="ProductRef" >My Product A</td>',
					'			<td class="left" data-col="qty" >13</td>',
					'			<td class="center amount" data-col="amount-no-tax" >9,99 &euro;</td>',
					'			<td class="right amount" data-col="total-no-tax" >129,87 &euro;</td>',
					'		</tr>',
					'		<tr class="oddeven">',
					'			<td class="left" data-col="ProductRef" >My Product B</td>',
					'			<td class="left" data-col="qty" >21</td>',
					'			<td class="center amount" data-col="amount-no-tax" >13,37 &euro;</td>',
					'			<td class="right amount" data-col="total-no-tax" >280,77 &euro;</td>',
					'		</tr>',
					'	</tbody>',
					'',
					'	<!-- Total -->',
					'	<tfoot>',
					'		<tr class="liste_total">',
					'			<td class="left" data-col="ProductRef" >Total</td>',
					'			<td class="left" data-col="qty" >58</td>',
					'			<td class="center amount" data-col="amount-no-tax" >--</td>',
					'			<td class="right amount" data-col="total-no-tax" >1178,87 &euro;</td>',
					'		</tr>',
					'	</tfoot>',
					'',
					'</table>',
				);
				echo $documentation->showCode($lines, 'php'); ?>
			</div>

			<!-- Table with filters -->
			<div class="documentation-section" id="tablesection-withfilters">

				<h2 class="documentation-title"><?php echo $langs->trans('DocTableWithFilters'); ?></h2>

				<p class="documentation-text"><?php echo $langs->trans('DocTableWithFiltersDescription', dol_buildpath('admin/tools/ui/components/inputs.php', 1)); ?></p>
				<div class="documentation-example">
					<div class="div-table-responsive">
						<table class="tagtable liste">
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
									<div class="inline-block checkallactions"><input type="checkbox" id="checkforselects" name="checkforselects" class="checkallactions" disabled></div>
								</th>
								<th class="wrapcolumntitle left liste_titre" title="First Name">First Name</th>
								<th class="wrapcolumntitle left liste_titre" title="Last Name">Last Name</th>
								<th class="wrapcolumntitle center liste_titre" title="Age">Age</th>
								<th class="wrapcolumntitle right liste_titre" title="Country">Country</th>
							</tr>
							<tr class="oddeven">
								<td><input type="checkbox" name="" value="" disabled></td>
								<td class="left">John</td>
								<td class="left">Doe</td>
								<td class="center">37</td>
								<td class="right">U.S.A</td>
							</tr>
							<tr class="oddeven">
								<td><input type="checkbox" name="" value="" disabled></td>
								<td class="left">Jack</td>
								<td class="left">Sparrow</td>
								<td class="center">29</td>
								<td class="right">Caribbean</td>
							</tr>
							<tr class="oddeven">
								<td><input type="checkbox" name="" value="" disabled></td>
								<td class="left">Sacha</td>
								<td class="left">Ketchum</td>
								<td class="center">16</td>
								<td class="right">Kanto</td>
							</tr>
							<tr class="oddeven">
								<td><input type="checkbox" name="" value="" disabled></td>
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
					'<form method="POST" id="FORMID" action="ACTION_URL">',
					'',
					'	<input type="hidden" name="token" value="TOKEN_VALUE">',
					'	<input type="hidden" name="action" value="ACTION_VALUE">',
					'	<!-- other hidden fields like sortfield, sortorder, page, ... -->',
					'	',
					'	<table class="tagtable liste" id="we-recommend-always-adding-a-unique-identifier">',
					'	',
					'		<!-- Filters row -->',
					'		<tr class="liste_titre_filter">',
					'			<td class="liste_titre center maxwidthsearch">',
					'				<div class="nowraponall">',
					'					<button type="submit" class="liste_titre button_search reposition" name="button_search_x" value="x">',
					'						<span class="fas fa-search"></span>',
					'					</button>',
					'					<button type="submit" class="liste_titre button_removefilter reposition" name="button_removefilter_x" value="x">',
					'						<span class="fas fa-times"></span>',
					'					</button>',
					'				</div>',
					'			</td>',
					'			<td>',
					'				<input class="flat" type="text" name="search_firstname" value="">',
					'			</td>',
					'			<td>',
					'				<input class="flat" type="text" name="search_lasttname" value="">',
					'			</td>',
					'			<td class="center">',
					'				<input class="maxwidth50 flat" type="text" name="search_age" value="">',
					'			</td>',
					'			<td class="right">',
					'				<input class="flat" type="text" name="search_country" value="">',
					'			</td>',
					'		</tr>',
					'	',
					'		<!-- Table header -->',
					'		<!-- Data lines -->',
					'		<!-- Total -->',
					'	',
					'	</table>',
					'</form>',
				);
				echo $documentation->showCode($lines); ?>
			</div>

			<!-- Add a row before filters -->
			<div class="documentation-section" id="tablesection-beforefilters">

				<h2 class="documentation-title"><?php echo $langs->trans('DocTableBeforeFilters'); ?></h2>

				<p class="documentation-text"><?php echo $langs->trans('DocTableBeforeFiltersDescription'); ?></p>
				<div class="documentation-example">
					<div class="div-table-responsive">
						<div class="liste_titre liste_titre_bydiv centpercent">
							<div class="divsearchfield">
								<span class="fas fa-tag pictofixedwidth" style="" title="TitleOfMySelectBeforeFilter"></span>
								<?php echo $form->selectarray('myselectbeforefilter', array('ValueFilterA', 'ValueFilterB', 'ValueFilterC'), '', 1); ?>
							</div>
						</div>
						<table class="tagtable liste listwithfilterbefore">
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
									<div class="inline-block checkallactions"><input type="checkbox" id="checkforselects" name="checkforselects" class="checkallactions" disabled></div>
								</th>
								<th class="wrapcolumntitle left liste_titre" title="First Name">First Name</th>
								<th class="wrapcolumntitle left liste_titre" title="Last Name">Last Name</th>
								<th class="wrapcolumntitle center liste_titre" title="Age">Age</th>
								<th class="wrapcolumntitle right liste_titre" title="Country">Country</th>
							</tr>
							<tr class="oddeven">
								<td><input type="checkbox" name="" value="" disabled></td>
								<td class="left">John</td>
								<td class="left">Doe</td>
								<td class="center">37</td>
								<td class="right">U.S.A</td>
							</tr>
							<tr class="oddeven">
								<td><input type="checkbox" name="" value="" disabled></td>
								<td class="left">Jack</td>
								<td class="left">Sparrow</td>
								<td class="center">29</td>
								<td class="right">Caribbean</td>
							</tr>
							<tr class="oddeven">
								<td><input type="checkbox" name="" value="" disabled></td>
								<td class="left">Sacha</td>
								<td class="left">Ketchum</td>
								<td class="center">16</td>
								<td class="right">Kanto</td>
							</tr>
							<tr class="oddeven">
								<td><input type="checkbox" name="" value="" disabled></td>
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
					'<div class="liste_titre liste_titre_bydiv centpercent">',
					'	<div class="divsearchfield">',
					'		<span class="fas fa-tag pictofixedwidth" style="" title="TitleOfMySelectBeforeFilter"></span>',
					'		<!-- Use Form Class to show a select, see Inputs section. Select below is for example -->',
					'		<select name="myselectbeforefilter">',
					'			<option value="-1"></option>',
					'			<option value="ValueFilterA">ValueFilterA</option>',
					'			<option value="ValueFilterB">ValueFilterB</option>',
					'			<option value="ValueFilterC">ValueFilterC</option>',
					'		</select>',
					'	</div>',
					'</div>',
					'<table class="tagtable liste listwithfilterbefore" id="we-recommend-always-adding-a-unique-identifier">',
					'	<!-- Filters row -->',
					'	<!-- Table header -->',
					'	<!-- Data lines -->',
					'	<!-- Total -->',
					'</table>',
				);
				echo $documentation->showCode($lines); ?>
			</div>

			<!-- CSS classes for tables -->
			<div class="documentation-section" id="tablesection-cssclasses">

				<h2 class="documentation-title"><?php echo $langs->trans('DocTableCSSClass'); ?></h2>

				<p class="documentation-text"><?php echo $langs->transnoentities('DocTableCSSClassDescription'); ?></p>

				<p class="documentation-text"><?php echo $langs->transnoentities('DocTableTABLECSSClassDescription'); ?></p>
				<ul>
					<li><?php echo $langs->transnoentities('DocTableCSSClass_tagtable'); ?></li>
					<li><?php echo $langs->transnoentities('DocTableCSSClass_liste'); ?></li>
					<li><?php echo $langs->transnoentities('DocTableCSSClass_listwithfilterbefore'); ?></li>
				</ul>

				<p class="documentation-text"><?php echo $langs->transnoentities('DocTableTRCSSClassDescription'); ?></p>
				<ul>
					<li><?php echo $langs->transnoentities('DocTableCSSClass_liste_titre'); ?></li>
					<li><?php echo $langs->transnoentities('DocTableCSSClass_liste_titre_filter'); ?></li>
					<li><?php echo $langs->transnoentities('DocTableCSSClass_liste_total'); ?></li>
					<li><?php echo $langs->transnoentities('DocTableCSSClass_oddeven'); ?></li>
				</ul>
			</div>

		</div>

	</div>

<?php
// Output close body + html
$documentation->docFooter();
?>
