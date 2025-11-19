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
$experimentName = 'UxDolibarrContext';

$experimentAssetsPath = $documentation->baseUrl . '/experimental/experiments/dolibarr-context/assets/';
$js = [
	'/includes/ace/src/ace.js',
	'/includes/ace/src/ext-statusbar.js',
	'/includes/ace/src/ext-language_tools.js',
	$experimentAssetsPath . '/dolibarr-context.umd.js',
];
$css = [];

// Output html head + body - Param is Title
$documentation->docHeader($langs->trans($experimentName, $group), $js, $css);

// Set view for menu and breadcrumb
$documentation->view = [$group, $experimentName];

// Output sidebar
$documentation->showSidebar(); ?>

<div class="doc-wrapper">

	<?php $documentation->showBreadCrumb(); ?>

	<div class="doc-content-wrapper">

		<h1 class="documentation-title"><?php echo $langs->trans($experimentName); ?></h1>

		<?php $documentation->showSummary(); ?>

		<div class="documentation-section">
			<h2 class="documentation-title">Introduction</h2>

			<p>
				DolibarrContext is a secure global JavaScript context for Dolibarr.
				It provides a single global object <code>window.Dolibarr</code>, which cannot be replaced.
				It allows defining non-replaceable tools and managing hooks/events in a modular and secure way.
			</p>

			<p>
				This system is designed to provide long-term flexibility and maintainability. You can define reusable tools
				that encapsulate functionality such as standardized AJAX requests and responses, ensuring consistent data handling across Dolibarr modules.
				For example, tools can be created to wrap API calls and automatically process returned data in a uniform format,
				reducing repetitive code and preventing errors.
			</p>

			<p>
				Beyond DOM-based events, DolibarrContext allows monitoring and reacting to business events using a
				hook-like mechanism. For instance, you can listen to events such as
				<code>Dolibarr.on('addline:load:productPricesList', function(e) { ... });</code>
				without relying on DOM changes. This enables creating logic that reacts directly to application state changes.
			</p>

			<p>
				Similarly, you can define tools that act as global helpers, like <code>Dolibarr.tools.setEventMessage()</code>.
				This tool can display notifications (similar to PHP's <code>setEventMessage()</code> in Dolibarr),
				initially using jNotify or any other library. In the future, the underlying library can change without affecting
				the way modules or external code call this tool, maintaining compatibility and reducing maintenance.
			</p>

			<p>
				In summary, DolibarrContext provides a secure, extensible foundation for adding tools, monitoring business events,
				and standardizing interactions across Dolibarr's frontend modules.
			</p>
		</div>

		<div class="documentation-section">
			<h2 class="documentation-title">Console help</h2>

			<p>
				Open your browser console with <code>F12</code> to view the available commands.<br/>
				If the help does not appear automatically, type <code>Dolibarr.tools.showConsoleHelp();</code> in the console to display it.
			</p>
		</div>

		<div class="documentation-section">
			<h2 class="documentation-title">JS Dolibarr hooks</h2>

			<h3>Event listener : the Dolibarr ready like</h3>
			<div class="documentation-example">
				<?php
				$lines = array(
					'<script>',
					'	// Add a listener to the Dolibarr theEventName event',
					'	Dolibarr.on(\'theEventName\', function(e) {',
					'		console.log(\'Dolibarr theEventName\', e.detail);',
					'	});',

					'	// But this  work too on document',
					'	document.addEventListener(\'Dolibarr:theEventName\', function(e) {',
					'		console.log(\'Dolibarr theEventName\', e.detail);',
					'	});',
					'</script>',
				);
				echo $documentation->showCode($lines, 'php'); ?>
			</div>

			<h3>Example of code usage</h3>
			<div class="documentation-example">
				<?php
				$lines = array(
					'<script>',
					'	document.addEventListener(\'Dolibarr:Ready\', function(e) {',
					'		// the dom is ready and you are sure Dolibarr js context is loaded',
					'		...',
					'		// Do your stuff',
					'		...',
					'',
					'		// Add a listener to the yourCustomHookName event',
					'		Dolibarr.on(\'yourCustomHookName\', function(e) {',
					'			console.log(\'e.detail will contain { data01: \'stuff\', data02: \'other stuff\' }\', e.detail);',
					'		});',
					'',
					'		// you can trigger js hooks',
					'		document.getElementById(\'try-event-yourCustomHookName\').addEventListener(\'click\', function(e) {',
					'			Dolibarr.executeHook(\'yourCustomHookName\', { data01: \'stuff\', data02: \'other stuff\' })',
					'		});',
					'',
					'		...',
					'		// Do your stuff',
					'		...',

					'	});',
					'</script>',
				);
				echo $documentation->showCode($lines, 'php'); ?>

				Open your console <code>F12</code> and click on  <button class="button" id="try-event-yourCustomHookName">try</button>
				<script nonce="<?php print getNonce() ?>"  >
					document.addEventListener('Dolibarr:Ready', function(e) {
						// the dom is ready and you are sure Dolibarr js context is loaded
						// Add a listener to the yourCustomHookName event
						Dolibarr.on('yourCustomHookName', function(e) {
							console.log('e.detail will contain { data01: stuff, data02: other stuff }', e.detail);
						});


						document.getElementById('try-event-yourCustomHookName').addEventListener('click', function(e) {
							// you can create js hooks
							Dolibarr.executeHook('yourCustomHookName', { data01: 'stuff', data02: 'other stuff' })
						});

					});
				</script>

			</div>

		</div>

		<div class="documentation-section">
			<h2 class="documentation-title">Example of creating a new context tool</h2>

			<h3>Defining Tools</h3>
			<p>
				You can define reusable and protected tools in the Dolibarr context using <code>Dolibarr.defineTool</code>:
			</p>

			<div class="documentation-example">
				<?php
				$lines = array(
				'<script>',
					'	// Define a simple tool',
					'	let overwrite = false; // Once a tool is defined, it cannot be replaced.',
					'	Dolibarr.defineTool(\'alertUser\', (msg) => alert(\'[Dolibarr] \' + msg), overwrite);',
					'',
					'	// Use the tool',
					'	Dolibarr.tools.alertUser(\'hello world\');',
				'</script>',
				);
				echo $documentation->showCode($lines, 'php'); ?>
				<script nonce="<?php print getNonce() ?>" >
					// Define a simple tool
					Dolibarr.defineTool('alertUser', (msg) => alert('[Dolibarr] ' + msg));
				</script>
			</div>

			<h3>Protected Tools</h3>
			<p>
				Once a tool is defined on overwrite false, it cannot be replaced. Attempting to redefine it without overwrite will throw an error:
			</p>

			<div class="documentation-example">
				<?php
				$lines = array(
					'<script>',
					'	try {',
					'		Dolibarr.defineTool(\'alertUser\', () => {});',
					'	} catch (e) {',
					'		console.error(e.message);',
					'	}',
					'</script>',
				);
				echo $documentation->showCode($lines, 'php'); ?>
			</div>

			<h3>Reading Tools</h3>
			<p>
				You can read the list of available tools using <code>Dolibarr.tools</code>. It returns a frozen copy:
			</p>

			<div class="documentation-example">
				<?php
				$lines = array(
					'<script>',
					'	console.log(Dolibarr.tools);',
					'	if(Dolibarr.checkToolExist(\'Tool name to check\')){/* ... */}else{/* ... */}; ',
					'</script>',
				);
				echo $documentation->showCode($lines, 'php'); ?>
			</div>

		</div>

	</div>

</div>
<?php
// Output close body + html
$documentation->docFooter();
?>
