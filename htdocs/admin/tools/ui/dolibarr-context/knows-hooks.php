<?php
/*
 * Copyright (C) 2025 Anthony Damhet <a.damhet@progiseize.fr>
 * Copyright (C) 2025       Frédéric France         <frederic.france@free.fr>
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
$group = 'UxDolibarrContext';
$experimentName = 'UxDolibarrContextKnowsHooks';

$js = [
	'/includes/ace/src/ace.js',
	'/includes/ace/src/ext-statusbar.js',
	'/includes/ace/src/ext-language_tools.js'
];
$css = [];

// Output html head + body - Param is Title
$documentation->docHeader($langs->trans($experimentName, $group), $js, $css);

// Set view for menu and breadcrumb
$documentation->view = [$group, 'UxDolibarrContext', $experimentName];

// Output sidebar
$documentation->showSidebar(); ?>

<div class="doc-wrapper">

	<?php $documentation->showBreadCrumb(); ?>

	<div class="doc-content-wrapper">

		<h1 class="documentation-title"><?php echo $langs->trans($experimentName); ?></h1>

		<?php $documentation->showSummary(); ?>

		<div class="documentation-section">
			<h2 id="titlesection-basicusage" class="documentation-title">Introduction</h2>

			<p>
				Some hooks are not natively triggered by Dolibarr; instead, they rely on external modules. Therefore, we document them here to ensure everyone uses the same method of triggering them, until we provide a standardized native trigger, which does not yet exist.<br/>
				Please refer to the "How it works" section for further details.
			</p>

		</div>

		<div class="documentation-section">
			<h2 id="reloadDocumentLine" class="documentation-title">Hook : reloadDocumentLine</h2>
			<p>
				Next, let’s focus on the “reloadDocumentLine” hook. First, it’s important to note that this hook is not triggered automatically by Dolibarr.
				<br/>Instead, it must be activated via external modules. In the future, we plan to introduce a class directly tied to the object within Dolibarr tools, allowing this hook to be triggered natively. However, although Dolibarr does not currently initiate the trigger itself, it does listen for it.
				<br/>This is because it uses this trigger to reload certain elements on the lines, particularly the drag-and-drop system for rearranging line items in the document.
			</p>
			<p>
				In the meantime, here is how you can use the event listener.
				<br/>The process involves two steps: first, you trigger the event, and then you handle it with an event listener.
				<br/>Below, you will find a code example of how to implement this.
			</p>
			<h4>Listen event</h4>
			<div class="documentation-example">
				<?php
				$lines = array(
					'<script>',
					'Dolibarr.on(\'reloadDocumentLine\',',
					'	/** @param {{lineId:number, lineElement:string}} data */',
					'	function (data) {',
					'		// Do your stuff',
					'	}',
					');',
					'</script>',
				);
				$documentation->showCode($lines, 'php');
				?>
			</div>

			<h4>Trigger event</h4>
			<div class="documentation-example">
				<?php
				$lines = array(
					'<script>',
					'	const rowSelector = \'#row-\' + lineId;',
					'	const $row = $(rowSelector);',
					'',
					'	// newRow is the newly created row element that will replace the existing row',
					'	$row.replaceWith(newRow);',
					'',
					'	// Trigger the hook to dispatch reloaded line event.',
					'	// This hook will by used to rebuild drag and drop lines order system for example ',
					'	Dolibarr.executeHook(\'reloadDocumentLine\', {lineId, lineElement});',
					'',
					'	// Trigger initNewContent for all common Dom reloaded content. This will reload tooltips system for example ',
					'	// This will reload tooltips system for example ',
					'	Dolibarr.initNewContent(rowSelector);',
					'</script>',
				);
				$documentation->showCode($lines, 'php');
				?>
			</div>
		</div>

	</div>
</div>

</div>
<?php
// Output close body + html
$documentation->docFooter();
?>
