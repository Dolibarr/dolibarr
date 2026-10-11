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
$lineedit = $withajax && ($featureslevel >= 2 || getDolGlobalString('MAIN_USE_AJAX_LINE_EDIT'));
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
		<p class="documentation-text">Edition of the cards of documents (proposals, orders, invoices...) without reloading the page.</p>

		<?php $documentation->showSummary(); ?>

		<div class="documentation-section" id="ajax-card-edition-introduction">
			<h2 class="documentation-title">Introduction</h2>
			<p class="documentation-text">
				On a card, each change of a field or of a line reloads the whole page, twice: once to display the form, once after the save.
				On a document with many lines, each reload takes seconds.<br>
				With this experiment, the fields and the lines are edited in place. Only what changed is refreshed: the value, the line, the totals,
				then the status, the action buttons and the generated documents.
			</p>
			<p class="documentation-text">
				The rules are the ones of the card: a line is saved, added or deleted by the actions of the card (prices, taxes, hooks, triggers, document generated...).
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
					<td><code>MAIN_USE_AJAX_LINE_EDIT</code></td>
					<td>1</td>
					<td>Lines of the documents edited, added and deleted in ajax</td>
				</tr>
				<tr class="oddeven">
					<td><code>MAIN_FEATURES_LEVEL</code></td>
					<td>2</td>
					<td>Both, with all the other experimental features of Dolibarr (for test instances only)</td>
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
				<tr class="oddeven">
					<td>Edition, addition and deletion of the lines</td>
					<td><?php echo yn($lineedit); ?></td>
				</tr>
			</table>
			<p class="documentation-text opacitymedium">
				<code>MAIN_FEATURES_LEVEL</code> = <?php echo $featureslevel; ?>,
				<code>MAIN_USE_AJAX_FIELD_EDIT</code> = <?php echo dol_escape_htmltag(getDolGlobalString('MAIN_USE_AJAX_FIELD_EDIT', '-')); ?>,
				<code>MAIN_USE_AJAX_LINE_EDIT</code> = <?php echo dol_escape_htmltag(getDolGlobalString('MAIN_USE_AJAX_LINE_EDIT', '-')); ?>
			</p>
		</div>

		<div class="documentation-section" id="ajax-card-edition-what">
			<h2 class="documentation-title">What is edited in ajax</h2>

			<h3>Fields</h3>
			<p class="documentation-text">
				Only the fields declared editable from the user interface, with <code>'uieditable' => 1</code> in <code>$fields</code> of the class.
				In core: customer ref and notes of proposals, orders and invoices, notes of third parties and products,
				and the fields of the header of proposals, orders and invoices: dates, payment term and mode, delivery delay, shipping method, source,
				bank account, and the project shown under the ref (projects of the thirdparty, like on the cards). They are editable in the same statuses as on the cards (draft only for proposals, dates of orders and invoices),
				and the fields depending on them are computed like on the cards: end of validity of a proposal following its date,
				due date of an invoice following its date and payment term.
				Other fields keep the edition by reloading the page.<br>
				A click on the edit picto of the field opens its input in a popover placed over the value, so the card does not move. <kbd>Enter</kbd> saves, <kbd>Escape</kbd> cancels.
				Only one field is edited at a time: opening another field cancels the first one. A click outside the popover closes it when the value was not changed.<br>
				After a change of a field, the document of the card is generated again like the cards do (unless <code>MAIN_DISABLE_PDF_AUTOUPDATE</code>), once, a few seconds after the last change.
				An update is refused if the object was modified by someone else since the page was displayed.
			</p>

			<h3>Lines</h3>
			<p class="documentation-text">
				Lines of a draft, on the cards using the core templates of lines (proposals, orders, invoices, supplier documents...):
			</p>
			<ul>
				<li><b>Edit a value:</b> a click on a value of a line (VAT, unit prices, quantity, unit, discount, buying price) opens its input in the same popover as the fields, over the value. <kbd>Enter</kbd> saves, <kbd>Tab</kbd> saves and opens the next value (<kbd>Shift</kbd> + <kbd>Tab</kbd> the previous one, on the next or previous line at the end), <kbd>Escape</kbd> cancels. The value saved, and lighter the values changed with it (total of the line, totals of the card), are highlighted once.</li>
				<li><b>Edit:</b> the edit picto replaces the line by the edit form of the card. <kbd>Escape</kbd> cancels. If the card refuses the values, the error is displayed and the form stays open.</li>
				<li><b>Add:</b> the button <i>Add</i> inserts the new line at its place, then empties the form to add a line.</li>
				<li><b>Delete:</b> the delete picto asks a confirmation, then removes the line.</li>
			</ul>
			<p class="documentation-text">
				The first line of an empty document is still added by reloading the page.
				Credit lines and the lines of situation invoices are edited with the edit picto only.
				After a value of a line is changed, the document is generated again once, a few seconds after the last change, like after a change of a field.
			</p>

			<h3>What is refreshed</h3>
			<ul>
				<li>The value of the field or the line, and the lines next to the lines added or deleted (arrows to move the lines, numbers of the lines).</li>
				<li>The totals of the card, with their currency.</li>
				<li>The banner with the status, the action buttons and the generated documents. When the card has to be loaded again for them, it is loaded only when one of them is visible (lazy load): several changes in a row load the card once.</li>
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
				<li>The edit link gets the attributes <code>data-dol-edit-field</code>, <code>data-dol-element</code>, <code>data-dol-id</code> and <code>data-dol-tms</code>, its url is still used without javascript. The value is printed in a container <code>[data-dol-field][data-dol-element][data-dol-id]</code>.
					Cards printing their own edit link and value (lists of dictionaries...) add these attributes with <code>Form::getAjaxFieldEditAttributes()</code> and <code>Form::getAjaxFieldValueAttributes()</code>.
					When the name given to <code>editfieldkey()</code> is not the field of <code>$fields</code> (ex: <code>'conditions'</code>), the field is given by its last parameter <code>$ajaxfield</code> (ex: <code>'fk_cond_reglement'</code>).</li>
				<li>On the server, the field is read in the property used by the cards (<code>CommonObject::getFieldUiValue()</code>, ex: <code>fk_cond_reglement</code> in <code>cond_reglement_id</code>), edited with the selector of the card (<code>ajaxInputField()</code>), shown like on the card (<code>ajaxOutputField()</code>) and saved with the setter of the card (<code>persistFieldValue()</code>, ex: <code>setPaymentTerms()</code>). The fields depending on it are recomputed by <code>onFieldValueChanged()</code>, then refreshed on the page.</li>
				<li>A click on the link opens the input of the field, returned by <code>core/ajax/objectfield.php</code> with <code>input=1</code> (<code>CommonObject::ajaxInputField()</code>), in a <b>field popover</b> <code>.dol-field-popover</code> placed over the value (light theme of the tooltips, <code>--light-mode</code> or <code>--dark-mode</code>): the layout of the card does not move. <kbd>Enter</kbd> saves a single line input, <kbd>Escape</kbd> cancels.</li>
				<li>The popover has icon buttons (save, cancel, labels as tooltips). A list replaced by select2 gets the focus on its select2 box.</li>
				<li>When the answer of the update asks it (<code>builddoc</code>, unless <code>MAIN_DISABLE_PDF_AUTOUPDATE</code>), the controller of the card generates the document again with <code>scheduleDocumentBuild()</code>: the form of generation of the page (<code>form#builddoc_form</code>, same model, language and options as the button Generate) is posted to the card <code>DOCUMENT_BUILD_DELAY</code> ms (3 s) after the last change, once at a time, and once again after if the object changed during the generation. A generation still waiting when the page is left is sent with <code>navigator.sendBeacon()</code>.</li>
				<li>One field at a time: opening another field cancels the edition in progress (unless it is being saved). A click outside the popover closes it only when the value was not changed. Clicks in the layers of the inputs (dropdown of select2, date picker, dialogs of the rich text editor) are not outside.</li>
				<li>The value is saved with <code>setField()</code> of the controller of the card (<code>Dolibarr.tools.card</code>), refused if the object was modified since the page was displayed. Then the value and the other fields changed by the update (containers <code>[data-dol-field]</code> of the page) are refreshed, and the sections of the card (status, action buttons, documents) when they are visible (<code>scheduleSectionsRefresh()</code>).</li>
				<li><code>Dolibarr.tools.card.fields.open(link)</code> opens the edition of the field of an edit link. <code>Dolibarr.tools.card.fields.openPopover(container, options)</code> opens a field popover over any value (used for the values of the lines): <code>content</code> (html or node of the inputs), <code>getValue(inputs)</code>, <code>save(value, popover)</code> (an error thrown keeps the popover open), optional <code>onClose()</code> and <code>onKeydown(event, popover)</code>.</li>
			</ul>
			<p class="documentation-text">
				The controller of the card is shared with the edition of the lines: after a change of the lines, a field can be saved without being refused.
			</p>
			<p class="documentation-text">
				<b>Note:</b> the hidden option <code>MAIN_USE_EDIT_IN_PLACE</code> (jeditable) is deprecated, the ajax edition of fields replaces it: it is ignored for the fields edited in ajax.
			</p>
		</div>

		<div class="documentation-section" id="titlesection-tool-lineedit">
			<h2 class="documentation-title">Line edit tool</h2>

			<p class="documentation-text">
				<code>Dolibarr.tools.card.lines</code>, part of the <a href="<?php echo $jscontexturl; ?>#titlesection-tool-card">Card tool</a>, edits, adds and deletes the document lines of a card in ajax, without reloading the page. There is no code to write in the card:
				the links of the lines printed by <code>objectline_view.tpl.php</code> and the button Add of <code>objectline_create.tpl.php</code> are used.
			</p>
			<ul>
				<li>Enabled with <code>MAIN_FEATURES_LEVEL</code> &gt;= 2, or with the hidden option <code>MAIN_USE_AJAX_LINE_EDIT</code>, only on a draft: see <code>CommonObject::isAjaxLineEdit()</code>.</li>
				<li>The edit link of a line gets the attributes <code>data-dol-edit-line</code>, <code>data-dol-element</code> and <code>data-dol-id</code>, its url is still used without javascript.</li>
				<li>A click on the link replaces the line by its edit form, the one of the card: <code>core/ajax/objectline.php</code> with <code>action=editline</code> (<code>CommonObject::getObjectLineHtml($lineid, 'editline')</code>). The form to add a line is removed from the page during the edition, it uses the same names and ids of inputs. One line is edited at a time, <kbd>Escape</kbd> cancels.</li>
				<li>The form is saved by the action <code>updateline</code> of the card, posted with <code>postCard()</code> of the controller of the card (<code>Dolibarr.tools.card</code>): all the rules of the card are applied (prices, taxes, hooks, triggers, document regenerated...).</li>
				<li>If the card returns an error, it is displayed and the form stays open. Otherwise the line, and the fields displayed (totals...) and the sections of the card (status, action buttons, documents, see <code>refreshSections()</code>) are refreshed.</li>
				<li>The values of a line are edited in their cell: <code>objectline_view.tpl.php</code> adds <code>data-dol-line-field</code> (<code>tva_tx</code>, <code>price_ht</code>, <code>price_ttc</code>, <code>qty</code>, <code>units</code>, <code>remise_percent</code>, <code>buying_price</code>) to the cells of the lines that have the edit link (not credit lines nor lines of situation invoices).
					A click on a cell loads the edit form of the line out of the page (also when the mouse stays on a value, so it opens at once), and opens a field popover with a copy of the input of the form: same lists (VAT rates, units) and formats as the card.
					The value is saved by the action <code>updateline</code> of the card, posted with all the values of the edit form of the line like the button Save, so the values not edited are kept. Like the scripts of the edit form, a unit price entered empties the other one (<code>price_ht</code> or <code>price_ttc</code>).
					A value that can not be edited alone (input read only or missing) opens the edition of the whole line.
					The card does not generate the document (<code>nodocumentbuild=1</code>), it is generated once after the last change with <code>scheduleDocumentBuild()</code> when <code>core/ajax/objectline.php</code> answers <code>builddoc</code>.</li>
				<li>Lines are also added and deleted in ajax, with the actions <code>addline</code> and <code>confirm_deleteline</code> of the card. The button Add of the form to add a line gets the attributes <code>data-dol-add-line</code>, <code>data-dol-element</code> and <code>data-dol-id</code>, the delete link of a line <code>data-dol-delete-line</code>, <code>data-dol-element</code> and <code>data-dol-id</code>. The new lines are inserted at their place and the form to add a line is emptied. A deletion is confirmed first. The lines next to the lines added or deleted are refreshed too (arrows to move the lines, numbers of the lines).</li>
				<li>The result of an action is read from the object, not from the messages: a line saved, added or deleted is displayed even if an error happened after the action (ex: document not generated), like in the card.</li>
				<li><code>Dolibarr.tools.card.lines.open(link)</code> opens the edition of the line of an edit link, <code>Dolibarr.tools.card.lines.close()</code> cancels the edition in progress, <code>editCell(cell)</code> opens the edition of a value of a line. <code>addLine(button)</code> and <code>deleteLine(link)</code> add and delete lines like the button Add and the delete links.</li>
			</ul>
			<p class="documentation-text">
				Modules can add the selectors of their own sections of the cards to <code>Dolibarr.tools.card.sections</code>, or for one element with <code>Dolibarr.tools.card.register()</code>, so they are refreshed after a change.
			</p>
		</div>

	</div>

</div>

<?php
// Output close body + html
$documentation->docFooter();
?>
