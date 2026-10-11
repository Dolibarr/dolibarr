<?php

/**
 * @var Documentation $documentation
 *
 * @phan-var Documentation $documentation
 */

if (!defined('DOL_VERSION')) {die();}

global $documentation;

if ($documentation === null || !($documentation instanceof Documentation)) { return; }


?>

<div class="documentation-section">
	<h2 id="titlesection-tool-replacecontent" class="documentation-title">Replace content tool</h2>

	<p>
		<code>Dolibarr.tools.replaceContent(target, content, options)</code> replaces an element of the page, or its content, by html received in ajax, like if it was in the page from the start:
	</p>
	<ul>
		<li>The scripts of the new html are executed (select2, date pickers...), with the nonce of the current page. An external script already loaded by the page is not executed again. Scripts waiting for <code>Dolibarr:Ready</code> or <code>DOMContentLoaded</code> are never executed: these events are already passed.</li>
		<li><code>Dolibarr.initNewContent()</code> is called on the new elements (tooltips...), unless option <code>initNewContent: false</code>.</li>
		<li><code>target</code> is an element, a jQuery object or a CSS selector (only the first element found is used).</li>
		<li><code>content</code> is html, or an element of another document (ex: a page parsed with <code>DOMParser</code>).</li>
		<li>Option <code>mode</code>: <code>'replace'</code> (default) replaces the element and returns the new one, <code>'inner'</code> replaces its content and returns the target.</li>
	</ul>
	<?php
	$lines = array(
		'<script nonce="<?php print getNonce() ?>" >',
		'	const html = await Dolibarr.tools.fetch.get(url, { id: 12 }, { responseType: \'text\' });',
		'	const newBlock = Dolibarr.tools.replaceContent(\'#myblock\', html);',
		'',
		'	// Replace only the content',
		'	Dolibarr.tools.replaceContent(\'#myblock\', \'<span class="classfortooltip" title="Hello">Hello</span>\', { mode: \'inner\' });',
		'</script>',
	);
	$documentation->showCode($lines, 'php'); ?>

	<div class="documentation-example">
		<script nonce="<?php print getNonce() ?>"  >
			document.addEventListener('Dolibarr:Ready', function(e) {
				let count = 0;
				document.getElementById('replacecontent-try').addEventListener('click', function(e) {
					count++;
					Dolibarr.tools.replaceContent('#replacecontent-demo',
						'<div id="replacecontent-demo"><span class="classfortooltip" title="Tooltip initialized by initNewContent">Content replaced ' + count + ' time(s), hover me</span>'
						+ '<script>Dolibarr.log("Script of the new content executed");<\/script></div>');
				});
			});
		</script>
		<div id="replacecontent-demo">Original content</div>
		<br>
		<button id="replacecontent-try" class="button">Replace content</button>
	</div>
</div>

<div class="documentation-section">
	<h2 id="titlesection-tool-object" class="documentation-title">Object tool</h2>

	<p>
		<code>Dolibarr.tools.object(element, id, options)</code> returns a controller of an object displayed on a card (propal, commande, facture, objects of modules...), to update it in ajax without reloading the page.
		It is generic: the rules are on the server side, in the class of the object.
	</p>
	<ul>
		<li><code>setField(field, value, options)</code> saves one field with <code>core/ajax/objectfield.php</code>. Only fields editable from the UI can be saved: <code>'uieditable' => 1</code> in <code>$fields</code>, see <code>CommonObject::isFieldEditableFromUi()</code>. Returns the data of the response <code>{element, id, field, value, html, tms}</code>, where <code>html</code> is the new value formatted for display. For a field with several inputs, like a date, <code>value</code> is an object with the suffixes of the inputs of <code>showInputField()</code>: <code>{day: 15, month: 10, year: 2026}</code>.</li>
		<li><code>reload({sections, lines}, options)</code> replaces the sections (CSS selectors) and the document lines (rows <code>tr#row-{id}</code>) by their new version. The lines are loaded alone with <code>core/ajax/objectline.php</code>, the sections with the card loaded without its lines (<code>nolines=1</code>), the two requests in parallel. A line not in the page yet is inserted at its place, a line deleted is removed from the page. <code>reloadSection(selector)</code> and <code>reloadLine(lineId)</code> are shortcuts. When lines are reloaded, the fields displayed in the page (totals...) are refreshed in the same time, see below: give <code>fields</code> to choose them, <code>[]</code> for none.</li>
		<li><code>refreshFields(fields, options)</code> refreshes the values of fields displayed in the page with <code>core/ajax/objectfield.php</code>, all the fields displayed when <code>fields</code> is not given. Only fields readable from the UI are returned, see <code>CommonObject::isFieldReadableFromUi()</code>. A value is displayed in a container <code>[data-dol-field][data-dol-element][data-dol-id]</code>, or <code>[data-dol-field]</code> without element for the object of the card, like the totals of the cards: <code>&lt;td data-dol-field="total_ht"&gt;</code>. The values are output by <code>CommonObject::ajaxOutputField()</code>, the totals with their currency.</li>
		<li><code>refreshCardSections(doc)</code> refreshes the sections of the card that change with the object: banner with the status (<code>div.arearef</code>), action buttons (<code>div.tabsAction</code>) and generated documents (<code>#builddoc_form</code>). The list is <code>Dolibarr.tools.object.cardSections</code>, modules can add the selectors of their sections. The sections are taken from <code>doc</code> (ex: the card returned by <code>postCard()</code>), or from the card loaded without its lines. A section with an edition in progress is not changed.</li>
		<li><code>scheduleCardSectionsRefresh()</code> does the same later, when one of the sections is visible in the window (lazy load): several changes in a row load the card once, and not at all while the sections are out of the window. <code>flushCardSections()</code> does it now. Used after an update of a field, and after a line is added or deleted when the card redirects (no card returned by the action).</li>
		<li><code>postCard(data, options)</code> posts data to the card, like a form of the card submitted (ex: <code>action=updateline</code>), without reloading the page. The card answers without its lines (<code>nolines=1</code>) and keeps the event messages of the action in session (<code>noflusheventmessages=1</code>): they are returned and displayed by the next call of <code>fetchLines()</code> or <code>reload()</code> with lines, in <code>eventMessages</code>. When the card redirects after the action (most cards after adding or deleting a line), the redirect is not followed and <code>postCard()</code> returns <code>null</code>.</li>
		<li><code>on(eventName, callback)</code> listens to the <code>object:*</code> hooks of this object only, and returns a function to remove the listener.</li>
	</ul>
	<p>
		Options of the controller: <code>tms</code> is the date of modification of the object displayed. When given, an update is refused (HTTP 409) if the object was modified since. It is updated after each update and each refresh of fields.
		<code>url</code> is the url of the card used to reload sections, the current page without action parameters by default.
	</p>
	<p>
		<code>reload()</code> returns the new elements (<code>null</code> if not found, or line deleted), <code>fields</code> the fields refreshed <code>{field: {value, html}}</code>, <code>eventMessages</code> the messages kept in session by <code>postCard()</code> (when lines are asked), <code>lineids</code> the ids of all the lines of the object in the order of the card and <code>tms</code> its date of modification (when lines or fields are refreshed), and <code>document</code> the card loaded (when sections are asked).
	</p>
	<p>
		<b>Note:</b> loading the card renews the anti CSRF token of the session. The tokens of the page (meta tags, and forms if the token is renewed on each call) are updated with the ones of the card loaded.
	</p>

	<?php
	$lines = array(
		'<script nonce="<?php print getNonce() ?>" >',
		'	document.addEventListener(\'Dolibarr:Ready\', async function(e) {',
		'		const propal = Dolibarr.tools.object(\'propal\', <?php print $object->id ?>, { tms: <?php print (int) $object->date_modification ?> });',
		'',
		'		// Save a field and display its new value',
		'		try {',
		'			await propal.setField(\'note_public\', \'Hello\', { target: \'#note_public_value\' });',
		'		} catch (err) {',
		'			// Error already displayed, use err.status and err.data for specific cases',
		'			if (err.status === 409) {',
		'				// Object modified by someone else since the page was displayed',
		'			}',
		'		}',
		'',
		'		// Reload a line changed, the totals displayed are refreshed in the same time',
		'		await propal.reload({ lines: [345] });',
		'',
		'		// Refresh only some fields',
		'		await propal.refreshFields([\'total_ht\', \'total_ttc\']);',
		'',
		'		// Listen to updates of this object only',
		'		const stop = propal.on(\'FieldUpdated\', ({ field, value }) => Dolibarr.log(field + \' saved\'));',
		'	});',
		'</script>',
	);
	$documentation->showCode($lines, 'php'); ?>

	<h3>Hooks</h3>
	<ul>
		<li><code>object:BeforeFieldUpdate</code> (await hook, see <code>Dolibarr.onAwait()</code>): <code>{element, id, field, value}</code>. A listener returns the data, with another <code>value</code> if needed, or <code>false</code> to cancel the update.</li>
		<li><code>object:FieldUpdated</code>: <code>{element, id, field, value, data}</code>.</li>
		<li><code>object:FieldUpdateError</code>: <code>{element, id, field, value, error}</code>.</li>
		<li><code>object:FieldsRefreshed</code>: <code>{element, id, fields, tms}</code>, when values of fields displayed are refreshed (also after an update). <code>tms</code> is the date of modification of the object for these values.</li>
		<li><code>object:SectionReloaded</code>: <code>{element, id, selector, node}</code>.</li>
		<li><code>object:LineReloaded</code>: <code>{element, id, lineId, node}</code>. The hook <code>reloadDocumentLine</code> <code>{lineId, lineElement}</code> is also executed for each line: modules adding cells or behaviors to the lines must listen to it.</li>
		<li><code>object:LineRemoved</code>: <code>{element, id, lineId}</code>, when a line deleted is removed from the page.</li>
	</ul>
	<?php
	$lines = array(
		'<script nonce="<?php print getNonce() ?>" >',
		'	Dolibarr.onAwait(\'object:BeforeFieldUpdate\', async (data) => {',
		'		if (data.field === \'ref_client\') {',
		'			data.value = data.value.trim().toUpperCase();',
		'		}',
		'		return data;',
		'	});',
		'</script>',
	);
	$documentation->showCode($lines, 'php'); ?>
</div>

<div class="documentation-section">
	<h2 id="titlesection-tool-fieldedit" class="documentation-title">Field edit tool</h2>

	<p>
		<code>Dolibarr.tools.fieldEdit</code> edits the fields of a card in ajax, without reloading the page. There is no code to write in the card:
		<code>Form::editfieldkey()</code> and <code>Form::editfieldval()</code> print the markup when the field is edited in ajax.
	</p>
	<ul>
		<li>Enabled with <code>MAIN_FEATURES_LEVEL</code> &gt;= 2, or with the hidden option <code>MAIN_USE_AJAX_FIELD_EDIT</code>, see <code>Form::isAjaxFieldEdit()</code>.</li>
		<li>Only for the fields of a <code>CommonObject</code> editable from the user interface (<code>'uieditable' => 1</code> in <code>$fields</code>, see <code>CommonObject::isFieldEditableFromUi()</code>). Other fields keep the edition by reloading the page.</li>
		<li>The edit link gets the attributes <code>data-dol-edit-field</code>, <code>data-dol-element</code>, <code>data-dol-id</code> and <code>data-dol-tms</code>, its url is still used without javascript. The value is printed in a container <code>[data-dol-field][data-dol-element][data-dol-id]</code>.</li>
		<li>A click on the link replaces the value by the input of the field, returned by <code>core/ajax/objectfield.php</code> with <code>input=1</code> (<code>CommonObject::ajaxInputField()</code>). <kbd>Enter</kbd> saves a single line input, <kbd>Escape</kbd> cancels.</li>
		<li>The value is saved with <code>setField()</code> of <code>Dolibarr.tools.object</code>, refused if the object was modified since the page was displayed. Then the value and the other fields changed by the update (containers <code>[data-dol-field]</code> of the page) are refreshed, and the sections of the card (status, action buttons, documents) when they are visible (<code>scheduleCardSectionsRefresh()</code>).</li>
		<li><code>Dolibarr.tools.fieldEdit.open(link)</code> opens the edition of the field of an edit link.</li>
	</ul>
	<p>
		<b>Note:</b> the hidden option <code>MAIN_USE_EDIT_IN_PLACE</code> (jeditable) is deprecated, the ajax edition of fields replaces it.
	</p>
</div>

<div class="documentation-section">
	<h2 id="titlesection-tool-lineedit" class="documentation-title">Line edit tool</h2>

	<p>
		<code>Dolibarr.tools.lineEdit</code> edits the document lines of a card in ajax, without reloading the page. There is no code to write in the card:
		the edit link of the lines printed by <code>objectline_view.tpl.php</code> is used.
	</p>
	<ul>
		<li>Enabled with <code>MAIN_FEATURES_LEVEL</code> &gt;= 2, or with the hidden option <code>MAIN_USE_AJAX_LINE_EDIT</code>, only on a draft: see <code>CommonObject::isAjaxLineEdit()</code>.</li>
		<li>The edit link of a line gets the attributes <code>data-dol-edit-line</code>, <code>data-dol-element</code> and <code>data-dol-id</code>, its url is still used without javascript.</li>
		<li>A click on the link replaces the line by its edit form, the one of the card: <code>core/ajax/objectline.php</code> with <code>action=editline</code> (<code>CommonObject::getObjectLineHtml($lineid, 'editline')</code>). The form to add a line is removed from the page during the edition, it uses the same names and ids of inputs. One line is edited at a time, <kbd>Escape</kbd> cancels.</li>
		<li>The form is saved by the action <code>updateline</code> of the card, posted with <code>postCard()</code> of <code>Dolibarr.tools.object</code>: all the rules of the card are applied (prices, taxes, hooks, triggers, document regenerated...).</li>
		<li>If the card returns an error, it is displayed and the form stays open. Otherwise the line, the fields displayed (totals...) and the sections of the card (status, action buttons, documents, see <code>refreshCardSections()</code>) are refreshed.</li>
		<li>Lines are also added and deleted in ajax, with the actions <code>addline</code> and <code>confirm_deleteline</code> of the card. The button Add of the form to add a line gets the attributes <code>data-dol-add-line</code>, <code>data-dol-element</code> and <code>data-dol-id</code>, the delete link of a line <code>data-dol-delete-line</code>, <code>data-dol-element</code> and <code>data-dol-id</code>. The new lines are inserted at their place and the form to add a line is emptied. A deletion is confirmed first. The lines next to the lines added or deleted are refreshed too (arrows to move the lines, numbers of the lines).</li>
		<li>The result of an action is read from the object, not from the messages: a line saved, added or deleted is displayed even if an error happened after the action (ex: document not generated), like in the card.</li>
		<li><code>Dolibarr.tools.lineEdit.open(link)</code> opens the edition of the line of an edit link, <code>Dolibarr.tools.lineEdit.close()</code> cancels the edition in progress. <code>addLine(button)</code> and <code>deleteLine(link)</code> add and delete lines like the button Add and the delete links.</li>
	</ul>
	<p>
		<b>Note:</b> a page loaded with the parameter <code>noflusheventmessages=1</code> does not display the event messages, they are kept in session for the next <code>JsonResponse</code> (<code>JsonResponse::addSessionEventMessages()</code>).
	</p>
</div>
