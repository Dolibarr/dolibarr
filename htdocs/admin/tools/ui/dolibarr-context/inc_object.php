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
		<li><code>reload({sections, lines}, options)</code> replaces the sections (CSS selectors) and the document lines (rows <code>tr#row-{id}</code>) by their new version. The lines are loaded alone with <code>core/ajax/objectline.php</code>, the sections with the card loaded without its lines (<code>nolines=1</code>), the two requests in parallel. A line not in the page yet is inserted at its place, a line deleted is removed from the page. <code>reloadSection(selector)</code> and <code>reloadLine(lineId)</code> are shortcuts.</li>
		<li><code>on(eventName, callback)</code> listens to the <code>object:*</code> hooks of this object only, and returns a function to remove the listener.</li>
	</ul>
	<p>
		Options of the controller: <code>tms</code> is the date of modification of the object displayed. When given, an update is refused (HTTP 409) if the object was modified since. It is updated after each update.
		<code>url</code> is the url of the card used to reload sections, the current page without action parameters by default.
	</p>
	<p>
		<code>reload()</code> returns the new elements (<code>null</code> if not found, or line deleted), <code>lineids</code> the ids of all the lines of the object in the order of the card and <code>tms</code> its date of modification (when lines are asked), and <code>document</code> the card loaded (when sections are asked).
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
		'		// Reload a line and the totals, with one request',
		'		await propal.reload({ lines: [345], sections: [\'[data-dol-field="total_ht"]\', \'[data-dol-field="total_ttc"]\'] });',
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
