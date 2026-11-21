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

// Load Dolibarr environment
require '../../../../../main.inc.php';

/**
 * @var Conf        $conf
 * @var DoliDB      $db
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

$documentation = new Documentation($db);
$group = 'ExperimentalUx';

// Current state of the experiment on this instance
$featureslevel = getDolGlobalInt('MAIN_FEATURES_LEVEL');
$withajax = !empty($conf->use_javascript_ajax);
$fieldedit = $withajax && ($featureslevel >= 2 || getDolGlobalString('MAIN_USE_AJAX_FIELD_EDIT'));
$jscontexturl = dol_buildpath($documentation->baseUrl.'/dolibarr-context/index.php', 1);

// Output html head + body - Param is Title
$documentation->docHeader($langs->transnoentitiesnoconv('UxMenuAjaxCardEdition'));

// Set view for menu and breadcrumb
$documentation->view = [$group, 'UxMenuAjaxCardEdition'];

// Output sidebar
$documentation->showSidebar(); ?>

<div class="doc-wrapper">

	<?php $documentation->showBreadCrumb(); ?>

	<div class="doc-content-wrapper">

		<h1 class="documentation-title"><?php echo $langs->trans('UxMenuAjaxCardEdition'); ?></h1>
		<p class="documentation-text">Edition of the cards (proposals, orders, invoices, third parties...) without reloading the page.</p>

		<?php $documentation->showSummary(); ?>

		<div class="documentation-section" id="ajax-card-edition-introduction">
			<h2 class="documentation-title">Introduction</h2>
			<p class="documentation-text">
				On a card, each change of a field reloads the whole page, twice: once to display the form, once after the save.
				On a document with many lines, each reload takes seconds.<br>
				With this experiment, the fields are edited in place. Only what changed is refreshed: the value and the fields changed by the update.
			</p>
			<p class="documentation-text">
				The rules are the ones of the card: a field is saved with the triggers of the object, and refused if the object was modified since the page was displayed.
				Without javascript, or with <kbd>Ctrl</kbd> + click on a link, the classic edition by reloading the page is used.
			</p>
		</div>

		<div class="documentation-section" id="ajax-card-edition-enable">
			<h2 class="documentation-title">How to enable it</h2>
			<p class="documentation-text">
				The experiment is disabled by default. It is enabled by one of these constants, added in <b>Home - Setup - Other setup</b>
				<?php if ($user->admin) { ?>(<a href="<?php echo DOL_URL_ROOT; ?>/admin/const.php">admin/const.php</a>)<?php } ?>:
			</p>
			<table class="noborder centpercent">
				<tr class="liste_titre">
					<td>Constant</td>
					<td>Value</td>
					<td>Effect</td>
				</tr>
				<tr class="oddeven">
					<td><code>MAIN_USE_AJAX_FIELD_EDIT</code></td>
					<td>1</td>
					<td>Fields of the cards edited in ajax</td>
				</tr>
				<tr class="oddeven">
					<td><code>MAIN_FEATURES_LEVEL</code></td>
					<td>2</td>
					<td>Same, with all the other experimental features of Dolibarr (for test instances only)</td>
				</tr>
			</table>
			<p class="documentation-text">
				The ajax requests must also be enabled (option to disable javascript and ajax not set, see <b>Home - Setup - Display</b>).
			</p>

			<h3>State on this instance</h3>
			<table class="noborder centpercent">
				<tr class="liste_titre">
					<td>Feature</td>
					<td>Enabled</td>
				</tr>
				<tr class="oddeven">
					<td>Edition of the fields</td>
					<td><?php echo yn($fieldedit); ?></td>
				</tr>
			</table>
			<p class="documentation-text opacitymedium">
				<code>MAIN_FEATURES_LEVEL</code> = <?php echo $featureslevel; ?>,
				<code>MAIN_USE_AJAX_FIELD_EDIT</code> = <?php echo dol_escape_htmltag(getDolGlobalString('MAIN_USE_AJAX_FIELD_EDIT', '-')); ?>
			</p>
		</div>

		<div class="documentation-section" id="ajax-card-edition-what">
			<h2 class="documentation-title">What is edited in ajax</h2>

			<h3>Fields</h3>
			<p class="documentation-text">
				Only the fields declared editable from the user interface, with <code>'uieditable' => 1</code> in <code>$fields</code> of the class.
				In core: customer ref and notes of proposals, orders and invoices, notes of third parties and products.
				Other fields keep the edition by reloading the page.<br>
				A click on the edit picto of the field replaces the value by its input, <kbd>Enter</kbd> saves, <kbd>Escape</kbd> cancels.
				An update is refused if the object was modified by someone else since the page was displayed.
			</p>

			<h3>What is refreshed</h3>
			<ul>
				<li>The value of the field.</li>
				<li>The other fields displayed and changed by the update (computed fields, totals with their currency...).</li>
			</ul>
		</div>

		<div class="documentation-section" id="titlesection-tool-fieldedit">
			<h2 class="documentation-title">Field edit tool</h2>

			<p class="documentation-text">
				<code>Dolibarr.tools.card.fields</code>, part of the <a href="<?php echo $jscontexturl; ?>#titlesection-tool-card">Card tool</a>, edits the fields of a card in ajax, without reloading the page. There is no code to write in the card:
				<code>Form::editfieldkey()</code> and <code>Form::editfieldval()</code> print the markup when the field is edited in ajax.
			</p>
			<ul>
				<li>Enabled with <code>MAIN_FEATURES_LEVEL</code> &gt;= 2, or with the hidden option <code>MAIN_USE_AJAX_FIELD_EDIT</code>, see <code>Form::isAjaxFieldEdit()</code>.</li>
				<li>Only for the fields of a <code>CommonObject</code> editable from the user interface (<code>'uieditable' => 1</code> in <code>$fields</code>, see <code>CommonObject::isFieldEditableFromUi()</code>). Other fields keep the edition by reloading the page.</li>
				<li>The edit link gets the attributes <code>data-dol-edit-field</code>, <code>data-dol-element</code>, <code>data-dol-id</code> and <code>data-dol-tms</code>, its url is still used without javascript. The value is printed in a container <code>[data-dol-field][data-dol-element][data-dol-id]</code>.</li>
				<li>A click on the link replaces the value by the input of the field, returned by <code>core/ajax/objectfield.php</code> with <code>input=1</code> (<code>CommonObject::ajaxInputField()</code>). <kbd>Enter</kbd> saves a single line input, <kbd>Escape</kbd> cancels.</li>
				<li>The value is saved with <code>setField()</code> of the controller of the card (<code>Dolibarr.tools.card</code>), refused if the object was modified since the page was displayed. Then the value and the other fields changed by the update (containers <code>[data-dol-field]</code> of the page) are refreshed.</li>
				<li><code>Dolibarr.tools.card.fields.open(link)</code> opens the edition of the field of an edit link.</li>
			</ul>
			<p class="documentation-text">
				<b>Note:</b> the hidden option <code>MAIN_USE_EDIT_IN_PLACE</code> (jeditable) is deprecated, the ajax edition of fields replaces it: it is ignored for the fields edited in ajax.
			</p>
		</div>

	</div>

</div>

<?php
// Output close body + html
$documentation->docFooter();
?>
