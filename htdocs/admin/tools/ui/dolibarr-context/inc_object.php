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
		<code>Dolibarr.tools.object(element, id, options)</code> returns a controller of an object (propal, commande, facture, objects of modules...), to read and update it in ajax.
		It is generic: it works for any object loaded by <code>fetchObjectByElement()</code>, the rules are on the server side, in the class of the object.
		It does not change the page: see the <a href="#titlesection-tool-card">Card tool</a> for the card of an object displayed in the page.
	</p>
	<ul>
		<li><code>setField(field, value, options)</code> saves one field with <code>core/ajax/objectfield.php</code>. Only fields editable from the UI can be saved: <code>'uieditable' => 1</code> in <code>$fields</code>, see <code>CommonObject::isFieldEditableFromUi()</code>. Returns the data of the response <code>{element, id, field, value, html, fields, tms}</code>, where <code>html</code> is the new value formatted for display and <code>fields</code> the updated field and the other fields changed by the update. For a field with several inputs, like a date, <code>value</code> is an object with the suffixes of the inputs of <code>showInputField()</code>: <code>{day: 15, month: 10, year: 2026}</code>.</li>
		<li><code>fetchFields(fields, options)</code> reads the values of fields with <code>core/ajax/objectfield.php</code>: <code>{field: {value, html}}</code>, the html being output by <code>CommonObject::ajaxOutputField()</code> (the totals with their currency). Only fields readable from the UI are returned, see <code>CommonObject::isFieldReadableFromUi()</code>. With the option <code>input: true</code>, the input of the fields editable from the UI is returned too (<code>CommonObject::ajaxInputField()</code>).</li>
		<li><code>fetchLines(lineIds, options)</code> reads the html of document lines with <code>core/ajax/objectline.php</code>, as printed in the card, and the ids of all the lines in the order of the card. The event messages waiting in session are returned in <code>eventMessages</code> and displayed.</li>
		<li><code>on(eventName, callback)</code> listens to the <code>object:*</code> hooks of this object only, and returns a function to remove the listener.</li>
	</ul>
	<p>
		Option <code>tms</code>: date of modification of the object known by the caller. When given, an update is refused (HTTP 409) if the object was modified since. It is updated after each update.
	</p>

	<?php
	$lines = array(
		'<script nonce="<?php print getNonce() ?>" >',
		'	document.addEventListener(\'Dolibarr:Ready\', async function(e) {',
		'		const propal = Dolibarr.tools.object(\'propal\', 12, { tms: 1791623636 });',
		'',
		'		try {',
		'			const data = await propal.setField(\'note_public\', \'Hello\');',
		'		} catch (err) {',
		'			// Error already displayed, use err.status and err.data for specific cases',
		'			if (err.status === 409) {',
		'				// Object modified by someone else since its date of modification known',
		'			}',
		'		}',
		'',
		'		const { fields } = await propal.fetchFields([\'total_ht\', \'total_ttc\']);',
		'	});',
		'</script>',
	);
	$documentation->showCode($lines, 'php'); ?>

	<h3>Hooks</h3>
	<ul>
		<li><code>object:BeforeFieldUpdate</code> (await hook, see <code>Dolibarr.onAwait()</code>): <code>{element, id, field, value}</code>. A listener returns the data, with another <code>value</code> if needed, or <code>false</code> to cancel the update.</li>
		<li><code>object:FieldUpdated</code>: <code>{element, id, field, value, data}</code>.</li>
		<li><code>object:FieldUpdateError</code>: <code>{element, id, field, value, error}</code>.</li>
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
	<h2 id="titlesection-tool-card" class="documentation-title">Card tool</h2>

	<p>
		<code>Dolibarr.tools.card(element, id, options)</code> returns the controller of the card of an object displayed in the page, to update the page in ajax without reloading it.
		It is built on the <a href="#titlesection-tool-object">Object tool</a> (property <code>object</code>). There is one controller by card, shared by all the scripts of the page: same date of modification, same state.
	</p>
	<ul>
		<li><code>setField(field, value, options)</code> saves a field (see Object tool), then refreshes the fields displayed changed by the update. Option <code>target</code>: element whose content is replaced by the new value.</li>
		<li><code>refreshFields(fields, options)</code> refreshes the values of fields displayed in the page, all the fields displayed when <code>fields</code> is not given. A value is displayed in a container <code>[data-dol-field][data-dol-element][data-dol-id]</code>, or <code>[data-dol-field]</code> without element for the object of the card, like the totals of the cards: <code>&lt;td data-dol-field="total_ht"&gt;</code>.</li>
		<li><code>reload({sections, lines, fields}, options)</code> replaces the sections (CSS selectors) and the document lines (rows <code>tr#row-{id}</code>) by their new version. The lines are loaded alone with <code>core/ajax/objectline.php</code>, the sections with the card loaded without its lines (<code>nolines=1</code>), the requests in parallel. A line not in the page yet is inserted at its place, a line deleted is removed from the page. When lines are reloaded, the fields displayed in the page (totals...) are refreshed in the same time: give <code>fields</code> to choose them, <code>[]</code> for none. <code>reloadSection(selector)</code> and <code>reloadLine(lineId)</code> are shortcuts.</li>
		<li><code>postCard(data, options)</code> posts data to the card, like a form of the card submitted (ex: <code>action=updateline</code>), without reloading the page. The card answers without its lines (<code>nolines=1</code>) and keeps the event messages of the action in session (<code>noflusheventmessages=1</code>): they are returned and displayed by the next call of <code>fetchLines()</code> or <code>reload()</code> with lines, in <code>eventMessages</code>. When the card redirects after the action (most cards after adding or deleting a line), the redirect is not followed and <code>postCard()</code> returns <code>null</code>.</li>
		<li><code>refreshSections(doc)</code> refreshes the sections of the card that change with the object: banner with the status (<code>div.arearef</code>), action buttons (<code>div.tabsAction</code>) and generated documents (<code>#builddoc_form</code>). The sections are taken from <code>doc</code> (ex: the card returned by <code>postCard()</code>), or from the card loaded without its lines. A section with an edition in progress is not changed.</li>
		<li><code>scheduleSectionsRefresh()</code> does the same later, when one of the sections is visible in the window (lazy load): several changes in a row load the card once, and not at all while the sections are out of the window. <code>flushSections()</code> does it now.</li>
		<li><code>on(eventName, callback)</code> listens to the <code>card:*</code> hooks of this card only.</li>
	</ul>
	<p>
		Options of the controller, used when it is created: <code>tms</code> is the date of modification of the object displayed (set later if the controller did not know it yet), <code>url</code> the url of the card used to reload sections and post actions, the current page without action parameters by default.
	</p>
	<p>
		<code>reload()</code> returns the new elements (<code>null</code> if not found, or line deleted), <code>fields</code> the fields refreshed <code>{field: {value, html}}</code>, <code>eventMessages</code> the messages kept in session by <code>postCard()</code> (when lines are asked), <code>lineids</code> the ids of all the lines of the object in the order of the card and <code>tms</code> its date of modification (when lines or fields are refreshed), and <code>document</code> the card loaded (when sections are asked).
	</p>

	<h3>Sections and specializations</h3>
	<p>
		The sections of all the cards are listed in <code>Dolibarr.tools.card.sections</code>, modules can add the selectors of their own sections.
		<code>Dolibarr.tools.card.register(element, {sections, Controller})</code> specializes the cards of an element, only for needs the server side can not cover:
		more sections for this element, or a class extending <code>Dolibarr.tools.card.Controller</code>. To call before a controller of this element is created.
	</p>
	<?php
	$lines = array(
		'<script nonce="<?php print getNonce() ?>" >',
		'	// Payments of the invoices refreshed with the other sections',
		'	Dolibarr.tools.card.register(\'facture\', { sections: [\'#paymenttable\'] });',
		'',
		'	document.addEventListener(\'Dolibarr:Ready\', async function(e) {',
		'		const card = Dolibarr.tools.card(\'propal\', <?php print $object->id ?>, { tms: <?php print (int) $object->date_modification ?> });',
		'',
		'		// Reload a line changed, the totals displayed are refreshed in the same time',
		'		await card.reload({ lines: [345] });',
		'',
		'		// Status, action buttons and documents refreshed when they are visible',
		'		card.scheduleSectionsRefresh();',
		'',
		'		const stop = card.on(\'LineReloaded\', ({ lineId }) => Dolibarr.log(\'line \' + lineId + \' reloaded\'));',
		'	});',
		'</script>',
	);
	$documentation->showCode($lines, 'php'); ?>

	<h3>Hooks</h3>
	<ul>
		<li><code>card:FieldsRefreshed</code>: <code>{element, id, fields, tms}</code>, when values of fields displayed are refreshed (also after an update). <code>tms</code> is the date of modification of the object for these values.</li>
		<li><code>card:SectionReloaded</code>: <code>{element, id, selector, node}</code>.</li>
		<li><code>card:LineReloaded</code>: <code>{element, id, lineId, node}</code>. The hook <code>reloadDocumentLine</code> <code>{lineId, lineElement}</code> is also executed for each line: modules adding cells or behaviors to the lines must listen to it.</li>
		<li><code>card:LineRemoved</code>: <code>{element, id, lineId}</code>, when a line deleted is removed from the page.</li>
	</ul>
	<p>
		<b>Note:</b> loading the card renews the anti CSRF token of the session. The tokens of the page (meta tags, and forms if the token is renewed on each call) are updated with the ones of the card loaded.
	</p>
	<p>
		<b>Note:</b> a page loaded with the parameter <code>noflusheventmessages=1</code> does not display the event messages, they are kept in session for the next <code>JsonResponse</code> (<code>JsonResponse::addSessionEventMessages()</code>).
	</p>
	<p>
		The edition of the cards in place is done by the parts <code>Dolibarr.tools.card.fields</code> and <code>Dolibarr.tools.card.lines</code> of this tool (experimental, see <a href="<?php echo dol_buildpath($documentation->baseUrl.'/experimental/ajax-card-edition/index.php', 1); ?>">Experimental UX - Ajax edition of cards</a>).
	</p>
</div>
