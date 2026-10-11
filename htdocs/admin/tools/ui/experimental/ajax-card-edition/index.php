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
				In core: customer ref and notes of proposals, orders and invoices, notes of third parties and products.
				Other fields keep the edition by reloading the page.<br>
				A click on the edit picto of the field replaces the value by its input, <kbd>Enter</kbd> saves, <kbd>Escape</kbd> cancels.
				An update is refused if the object was modified by someone else since the page was displayed.
			</p>
			<p class="documentation-text">
				<b>Note:</b> the hidden option <code>MAIN_USE_EDIT_IN_PLACE</code> (jeditable) is deprecated, it is ignored for the fields edited in ajax.
			</p>

			<h3>Lines</h3>
			<p class="documentation-text">
				Lines of a draft, on the cards using the core templates of lines (proposals, orders, invoices, supplier documents...):
			</p>
			<ul>
				<li><b>Edit:</b> the edit picto replaces the line by the edit form of the card. <kbd>Escape</kbd> cancels. If the card refuses the values, the error is displayed and the form stays open.</li>
				<li><b>Add:</b> the button <i>Add</i> inserts the new line at its place, then empties the form to add a line.</li>
				<li><b>Delete:</b> the delete picto asks a confirmation, then removes the line.</li>
			</ul>
			<p class="documentation-text">
				The first line of an empty document is still added by reloading the page.
			</p>

			<h3>What is refreshed</h3>
			<ul>
				<li>The value of the field or the line, and the lines next to the lines added or deleted (arrows to move the lines, numbers of the lines).</li>
				<li>The totals of the card, with their currency.</li>
				<li>The banner with the status, the action buttons and the generated documents. When the card has to be loaded again for them, it is loaded only when one of them is visible (lazy load): several changes in a row load the card once.</li>
			</ul>
		</div>

		<div class="documentation-section" id="ajax-card-edition-dev">
			<h2 class="documentation-title">For developers</h2>
			<p class="documentation-text">
				There is no code to write in the cards. The JS tools used, documented in <a href="<?php echo dol_buildpath($documentation->baseUrl.'/dolibarr-context/index.php', 1); ?>">Dolibarr JS context</a>:
			</p>
			<ul>
				<li><a href="<?php echo dol_buildpath($documentation->baseUrl.'/dolibarr-context/index.php', 1); ?>#titlesection-tool-object"><code>Dolibarr.tools.object</code></a>: controller of the object of a card (save a field, reload lines and sections, post an action to the card).</li>
				<li><a href="<?php echo dol_buildpath($documentation->baseUrl.'/dolibarr-context/index.php', 1); ?>#titlesection-tool-fieldedit"><code>Dolibarr.tools.fieldEdit</code></a>: edition of the fields, see <code>Form::isAjaxFieldEdit()</code>.</li>
				<li><a href="<?php echo dol_buildpath($documentation->baseUrl.'/dolibarr-context/index.php', 1); ?>#titlesection-tool-lineedit"><code>Dolibarr.tools.lineEdit</code></a>: edition, addition and deletion of the lines, see <code>CommonObject::isAjaxLineEdit()</code>.</li>
			</ul>
			<p class="documentation-text">
				Modules can add the selectors of their own sections of the cards to <code>Dolibarr.tools.object.cardSections</code>, so they are refreshed after a change.
			</p>
		</div>

	</div>

</div>

<?php
// Output close body + html
$documentation->docFooter();
?>
