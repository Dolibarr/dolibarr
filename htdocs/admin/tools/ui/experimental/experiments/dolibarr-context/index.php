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
	$experimentAssetsPath . '/dolibarr-tool.seteventmessage.js',
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

		<h1 class="documentation-title"><?php echo $langs->trans($experimentName); ?> : <?php echo $langs->trans('UxDolibarrContextHowItWork'); ?></h1>

		<?php $documentation->showSummary(); ?>

		<div class="documentation-section">
			<h2 id="titlesection-basicusage" class="documentation-title">Introduction</h2>

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
				<code>Dolibarr.on('addline:load:productPricesList', function(data) { ... });</code>
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
			<h2 id="titlesection-console-help" class="documentation-title">Console help</h2>

			<p>
				Open your browser console with <code>F12</code> to view the available commands.<br/>
				If the help does not appear automatically, type <code>Dolibarr.tools.showConsoleHelp();</code> in the console to display it.
			</p>
		</div>

		<div class="documentation-section">
			<h2 class="documentation-title">Dolibarr.log() and debugMode()</h2>

			<p>
				<code>Dolibarr.log()</code> is a lightweight logging utility provided by the Dolibarr JS context.
				It does <strong>not</strong> replace <code>console.log()</code>, but it gives an important advantage:
				you can enable or disable all logs globally using <code>Dolibarr.debugMode()</code>.
			</p>

			<h3>Why use Dolibarr.log() instead of console.log()?</h3>
			<ul>
				<li>You can enable or disable logging dynamically from the browser console.</li>
				<li>Avoid polluting the console for end-users: logs appear only when debug mode is enabled.</li>
				<li>Ideal for module development: switch between quiet mode and verbose mode instantly.</li>
				<li>Useful in production debugging: you can activate logs without modifying any code.</li>
			</ul>

			<h3>How it works</h3>
			<p>
				When <code>debugMode</code> is disabled (default state), calls to <code>Dolibarr.log()</code> do nothing.
				When enabled, <code>Dolibarr.log()</code> behaves like <code>console.log()</code>.
			</p>

			<div class="documentation-example">
				<?php
				$lines = array(
					'<script>',
					'	// Log something (will only appear if debug mode is ON)',
					'	Dolibarr.log("My debug message");',
					'',
					'	// Enable verbose logs',
					'	Dolibarr.debugMode(true);',
					'',
					'	// Disable logs again',
					'	Dolibarr.debugMode(false);',
					'</script>',
				);
				echo $documentation->showCode($lines, 'php');
				?>
			</div>

			<h3>Summary</h3>
			<ul>
				<li><code>console.log()</code> → always prints messages, noisy, not controllable, but useful during active development and debugging.</li>
				<li><code>Dolibarr.log()</code> → prints messages only when debug mode is ON; fully controllable. Ideal for Dolibarr core logs or when you want to keep logs available but silent in production.</li>
				<li><code>Dolibarr.debugMode(true)</code> → enable verbose logs, activating <code>Dolibarr.log()</code> output.</li>
				<li><code>Dolibarr.debugMode(false)</code> → disable all <code>Dolibarr.log()</code> output, silencing debug messages.</li>
			</ul>

		</div>


		<div class="documentation-section">
			<h2 id="titlesection-hooks"  class="documentation-title">JS Dolibarr hooks</h2>

			<p>
				Dolibarr provides a hook system to allow modules and scripts to communicate with each other
				through named events. There are two ways to listen to these events in JS:
				<code>Dolibarr.on()</code> and <code>document.addEventListener()</code>.
			</p>

			<h3>Event listener example</h3>
			<p>
				You can use <code>Dolibarr.on()</code> to listen to a hook. The main difference with standard
				document events is that the callback receives <strong>directly the data object</strong> passed
				when the hook is executed, without needing to access <code>e.detail</code>.
			</p>
			<p>
				For backward compatibility and standard DOM integration, the same hook can also be caught
				using <code>document.addEventListener()</code>, but in this case the data is inside
				<code>e.detail</code> and the event name is prefixed by <code>Dolibarr:</code> so for a hook named A event name is <code>Dolibarr:A</code>
			</p>

			<div class="documentation-example">
				<?php
				$lines = array(
					'<script>',
					'	// Add a listener to the Dolibarr theEventName event',
					'	Dolibarr.on(\'theEventName\', function(data) {',
					'		console.log(\'Dolibarr theEventName\', data);',
					'	});',
					'',
					'	// But this  work too on document',
					'	document.addEventListener(\'Dolibarr:theEventName\', function(e) {',
					'		console.log(\'Dolibarr theEventName\', e.detail);',
					'	});',
					'</script>',
				);
				echo $documentation->showCode($lines, 'php'); ?>
			</div>

			<h3>Practical usage</h3>
			<p>
				When Dolibarr is ready (DOM loaded and JS context initialized), you can register your hooks
				or trigger them. Both <code>Dolibarr.on()</code> and <code>document.addEventListener()</code>
				are valid, but <code>Dolibarr.on()</code> is simpler and more convenient because you get the
				data directly.
			</p>
			<div  class="documentation-example">
				<?php
				$lines = array(
					'<script>',
					'	document.addEventListener(\'Dolibarr:Ready\', function(e) {',
					'		// the dom is ready and you are sure Dolibarr js context is loaded',
					'		...',
					'		// Do your stuff',
					'		...',
					'',
					'		// Add a listener to the yourCustomHookName event with dolibarr.on()',
					'		Dolibarr.on(\'yourCustomHookName\', function(data) {',
					'			console.log(\'With Dolibarr.on : data will contain { data01: \'stuff\', data02: \'other stuff\' }\', data);',
					'		});',
					'',
					'		// Or you can do : Add a listener to the yourCustomHookName document.addEventListener()',
					'		document.addEventListener(\'Dolibarr:yourCustomHookName\', function(e) {',
					'			console.log(\'With document.addEventListener :  e.detail will contain { data01: \'stuff\', data02: \'other stuff\' }\', e.detail);',
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
						Dolibarr.on('yourCustomHookName', function(data) {
							console.log('With Dolibarr.on : data will contain { data01: stuff, data02: other stuff }', data);
						});

						document.addEventListener('Dolibarr:yourCustomHookName', function(e) {
							console.log('With document.addEventListener : e.detail will contain { data01: stuff, data02: other stuff }', e.detail);
						});

						document.getElementById('try-event-yourCustomHookName').addEventListener('click', function(e) {
							// you can create js hooks
							Dolibarr.executeHook('yourCustomHookName', { data01: 'stuff', data02: 'other stuff' })
						});

					});
				</script>

			</div>

		</div>



		<div  id="titlesection-event-init-vs-ready" class="documentation-section">
			<h2 class="documentation-title">Difference between Dolibarr:Init and Dolibarr:Ready event</h2>

			<p>
				Dolibarr provides two main initialization events for its JavaScript context: <code>Dolibarr:Init</code> and <code>Dolibarr:Ready</code>.
				Understanding their difference is important when developing modules or tools.
			</p>

			<ul>
				<li>
					<strong>Dolibarr:Init</strong> is triggered immediately when the Dolibarr context is created.
					This event is intended for:
					<ul>
						<li>Defining or registering new tools via <code>Dolibarr.defineTool()</code>.</li>
						<li>Setting context variables (<code>Dolibarr.setContextVar()</code> / <code>Dolibarr.setContextVars()</code>).</li>
						<li>Preparing configuration that must be available before the DOM is fully loaded.</li>
					</ul>
					It occurs <em>before</em> <code>Dolibarr:Ready</code>, so it is ideal for setup tasks that other tools may depend on.
				</li>

				<li>
					<strong>Dolibarr:Ready</strong> is triggered once the DOM is ready, similar to <code>$(document).ready()</code> in jQuery.
					This event is intended for:
					<ul>
						<li>Running code that interacts with the DOM.</li>
						<li>Attaching event listeners to elements on the page.</li>
						<li>Executing functionality that requires all tools and context variables to be fully initialized.</li>
					</ul>
				</li>
			</ul>

			<p>
				In short, use <code>Dolibarr:Init</code> for setting up tools and context variables, and <code>Dolibarr:Ready</code> for code that needs the DOM and fully initialized context.
			</p>

			<h3>Examples of usage</h3>
			<div class="documentation-example">
				<?php
				$lines = array(
					'<script>',
					'	// Example: Dolibarr:Init - define a tool and set context variables early',
					'	document.addEventListener(\'Dolibarr:Init\', function(e) {',
					'		console.log("Init event fired, Dolibarr is initialised and receive context vars a tools");',
					'	});',
					'',
					'	// Example: Dolibarr:Ready - interact with DOM and use tools',
					'	document.addEventListener(\'Dolibarr:Ready\', function(e) {',
					'		console.log("Ready event fired, DOM is ready");',
					'',
					'',
					'		// Attach event listener to a DOM element',
					'		const btn = document.getElementById("myButton");',
					'		if(btn) {',
					'			btn.addEventListener("click", function() {',
					'				alert("Button clicked! Context value: " + Dolibarr.getContextVar("mySetting"));',
					'			});',
					'		}',
					'	});',
					'</script>',
				);
				echo $documentation->showCode($lines, 'php'); ?>
			</div>

			<p>
				In summary:
			<ul>
				<li><code>Dolibarr:Init</code> → early setup, tools, context variables, configuration.</li>
				<li><code>Dolibarr:Ready</code> → DOM is ready, safe to manipulate elements and use tools defined in Init.</li>
			</ul>
			</p>
		</div>

		<div class="documentation-section">
			<h2 id="titlesection-await-hooks" class="documentation-title">Async Hooks (Await Hooks) - sequential execution</h2>

			<p>
				Dolibarr supports <strong>asynchronous hooks</strong> using <code>Dolibarr.onAwait()</code> and <code>Dolibarr.executeHookAwait()</code>.
				These hooks allow you to register functions that execute <em>in sequence</em> and can modify data before passing it to the next hook.
				They are useful for complex workflows where multiple modules or scripts need to process or enrich the same data asynchronously.
			</p>

			<p>
				Each hook can optionally specify <code>before</code> or <code>after</code> to control the execution order relative to other hooks.
				Every hook registration returns a unique <code>id</code>, which can be used to reference or unregister the hook later.
			</p>

			<p>
				Unlike standard synchronous hooks registered with <code>Dolibarr.on()</code>, await hooks return a <code>Promise</code> when executed.
				This means you can <code>await</code> their results in your code, and any asynchronous operations inside a hook (e.g., API calls, timers) will be handled correctly before moving to the next hook.
			</p>

			<div class="documentation-example">
				<?php
				$lines = array(
					'<script nonce="<?php print getNonce() ?>">',
					'    document.addEventListener(\'Dolibarr:Ready\', async function(e) {',
					'',
					'        // Register async hooks will be executed in first place',
					'        Dolibarr.onAwait(\'calculateDiscount\', async function(order) {',
					'            order.total *= 0.9; // Apply 10% discount',
					'            return order;',
					'        }, { id: \'discount10\' });',
					'',
					'        // Register async hooks will be executed in third place',
					'        Dolibarr.onAwait(\'calculateDiscount\', async function(order) {',
					'            if(order.total > 1000) order.total -= 50; // Extra discount over 1000',
					'            return order;',
					'        }, { id: \'discountOver1000\', after: \'discount10\' });',
					'',
					'        // Register async hooks will be executed in second place',
					'        // this hook item as no id so plus10HookItemId will receive a unique random id ',
					'        let plus10HookItemId = Dolibarr.onAwait(\'calculateDiscount\', async function(order) {',
					'            order.newObjectAttribute = \'My value\';',
					'            order.total += 10;',
					'            return order;',
					'        }, { before: \'discountOver1000\' });',
					'',
					'        document.getElementById(\'try-event-yourCustomAwaitHookName\').addEventListener(\'click\', async function(e) {',
					'            // Execute all registered await hooks sequentially',
					'            let order = {total: 1200};',
					'            order = await Dolibarr.executeHookAwait(\'calculateDiscount\', order);',
					'            console.log(order); // order.total : 1200 -> 1080 -> 1090 -> 1040',
					'        });',
					'',
					'    });',
					'</script>',
				);
				echo $documentation->showCode($lines, 'php'); ?>

				Open your console <code>F12</code> and click on  <button class="button" id="try-event-yourCustomAwaitHookName">try</button>

				<script nonce="<?php print getNonce() ?>">
					document.addEventListener('Dolibarr:Ready', async function(e) {

						// Register async hooks will be executed in first place
						Dolibarr.onAwait('calculateDiscount', async function(order) {
							order.total *= 0.9; // Apply 10% discount
							return order;
						}, { id: 'discount10' });

						// Register async hooks will be executed in third place
						Dolibarr.onAwait('calculateDiscount', async function(order) {
							if(order.total > 1000) order.total -= 50; // Extra discount over 1000
							return order;
						}, { id: 'discountOver1000', after: 'discount10' });

						// Register async hooks will be executed in second place
						Dolibarr.onAwait('calculateDiscount', async function(order) {
							order.newObjectAttribute = 'My value';
							order.total+= 10;
							return order;
						}, { before: 'discountOver1000' });

						document.getElementById('try-event-yourCustomAwaitHookName').addEventListener('click', async function(e) {
							// Execute all registered await hooks sequentially
							let order = {total: 1200};
							order = await Dolibarr.executeHookAwait('calculateDiscount', order);
							console.log(order); // order.total : 1200 -> 1080 -> 1090 -> 1040
						});

					});
				</script>
			</div>

		</div>


		<div class="documentation-section">
			<h2 id="titlesection-create-tool-example" class="documentation-title">Example of creating a new context tool</h2>

			<h3>Defining Tools</h3>
			<p>
				You can define reusable and protected tools in the Dolibarr context using <code>Dolibarr.defineTool</code>.
			</p>
			<p>See also <code>dolibarr-context.mock.js</code> for defining all standard Dolibarr tools and creating mock implementations to improve code completion and editor support.</p>
			<p><b>Note :</b> a tool can be a class not only a function</p>

			<div class="documentation-example">
				<?php
				$lines = array(
				'<script>',
					'document.addEventListener(\'Dolibarr:Init\', function(e) {',
					'	// Define a simple tool',
					'	let overwrite = false; // Once a tool is defined, it cannot be replaced.',
					'	Dolibarr.defineTool(\'alertUser\', (msg) => alert(\'[Dolibarr] \' + msg), overwrite);',
					'});',
					'',
					'document.addEventListener(\'Dolibarr:Ready\', function(e) {',
					'	// Use the tool',
					'	Dolibarr.tools.alertUser(\'hello world\');',
					'});',
				'</script>',
				);
				echo $documentation->showCode($lines, 'php'); ?>
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

		<div class="documentation-section">
			<h2 id="titlesection-tool-seteventmessage" class="documentation-title">Set event message tool</h2>

			<p>
				Instead of calling JNotify directly in your code, use Dolibarr’s setEventMessage tool.
				Dolibarr provides the configuration option DISABLE_JQUERY_JNOTIFY, which disables the jQuery JNotify system, usually because another notification library will be used instead.
			</p>

			<p>
				If you rely on Dolibarr.tools.setEventMessage(), your code remains compatible even if the underlying notification system changes.
				The setEventMessage tool can be replaced internally without requiring any changes in your modules or custom scripts.
			</p>
			<p>
				This means all developers can write features without worrying about frontend compatibility or future library replacements. Enjoy!

			</p>

			<div class="documentation-example">
				<?php
				$lines = array(
					'<script nonce="<?php print getNonce() ?>" >',
					'	document.addEventListener(\'Dolibarr:Ready\', function(e) {',
					'',
					'		document.getElementById(\'setEventMessage-success\').addEventListener(\'click\', function(e) {',
					'			Dolibarr.tools.setEventMessage(\'Success Test\');',
					'		});',
					'',
					'		document.getElementById(\'setEventMessage-error\').addEventListener(\'click\', function(e) {',
					'			Dolibarr.tools.setEventMessage(\'Error Test\', \'errors\');',
					'		});',
					'',
					'		document.getElementById(\'setEventMessage-error-sticky\').addEventListener(\'click\', function(e) {',
					'			Dolibarr.tools.setEventMessage(\'Error Test\', \'errors\', true);',
					'		});',
					'',
					'		document.getElementById(\'setEventMessage-warning\').addEventListener(\'click\', function(e) {',
					'			Dolibarr.tools.setEventMessage(\'Warning Test\', \'warnings\');',
					'		});',
					'',
					'	});',
					'</script>',
				);
				echo $documentation->showCode($lines, 'php'); ?>
				<script nonce="<?php print getNonce() ?>"  >
					document.addEventListener('Dolibarr:Ready', function(e) {

						document.getElementById('setEventMessage-success').addEventListener('click', function(e) {
							Dolibarr.tools.setEventMessage('Success Test')
						});

						document.getElementById('setEventMessage-error').addEventListener('click', function(e) {
							Dolibarr.tools.setEventMessage('Error Test', 'errors');
						});

						document.getElementById('setEventMessage-error-sticky').addEventListener('click', function(e) {
							Dolibarr.tools.setEventMessage('Error Test', 'errors', true);
						});

						document.getElementById('setEventMessage-warning').addEventListener('click', function(e) {
							Dolibarr.tools.setEventMessage('Warning Test', 'warnings');
						});

					});
				</script>
				<button id="setEventMessage-success" class="button">Alert success</button>
				<button id="setEventMessage-error" class="button">Alert error</button>
				<button id="setEventMessage-error-sticky" class="button">Alert error sticky</button>
				<button id="setEventMessage-warning" class="button">Alert warning</button>
			</div>

		</div>



		<div class="documentation-section">
			<h2 id="titlesection-contextvars" class="documentation-title">Set and use context vars</h2>

			<p>
				The <strong>Context Vars</strong> system allows you to define and manage variables that are globally accessible within the Dolibarr JavaScript context. These variables can store configuration data, URLs, tokens, user IDs, object references, or any other values needed by your frontend code and tools.
				By using context vars, you can:
			<ul>
				<li>Pass server-side data (from PHP) to JavaScript safely and consistently.</li>
				<li>Provide reusable configuration for Dolibarr tools, widgets, or modules without hardcoding values.</li>
				<li>Define overridable or non-overridable vars to protect critical values while allowing flexible overrides when necessary.</li>
				<li>Use <code>Dolibarr.setContextVar</code> for single values or <code>Dolibarr.setContextVars</code> to pass multiple values at once.</li>
				<li>Access these variables anywhere in your code via <code>Dolibarr.getContextVar(key)</code>.</li>
				<li>Ensure that all your modules and tools can rely on consistent and up-to-date context information, improving maintainability and interoperability.</li>
			</ul>
			This system is particularly useful for setting up base URLs, API endpoints, user-specific information, or runtime data that needs to be shared across multiple Dolibarr frontend tools.
			</p>

			<h3>Add  context var (overridable or not)</h3>
			<div class="documentation-example">
				<?php
				$lines = array(
					'<script nonce="<?php print getNonce() ?>" >',
					'    document.addEventListener(\'Dolibarr:Init\', function(e) {',
					'    	// Add no overridable context var',
					'       Dolibarr.setContextVar(\'yourKey\', \'YourValue\');',
					'',
					'    	// Add overridable context var',
					'       Dolibarr.setContextVar(\'yourKey2\', \'YourValue\', true);',
					'    });',
					'</script>',
				);
				echo $documentation->showCode($lines, 'php');
				?>
			</div>


			<h3>Add multiple context vars (overridable or not)</h3>
			<div class="documentation-example">
				<?php
				$lines = array(
					'<?php',
					'	$contextConst = [',
					'		\'DOL_URL_ROOT\' => DOL_URL_ROOT,',
					'		\'token\' => newToken(),',
					'		\'cardObjectElement\' => $object->element,',
					'		\'cardObjectId\' => $object->id,',
					'		\'currentUserId\' => $user->id',
					'		// ...',
					'	];',
					'',
					'	$contextVars = [',
					'		\'lastCardDataRefresh\' => time(),',
					'		// ...',
					'	]',
					'?>',
					'<script nonce="<?php print getNonce() ?>" >',
					'    document.addEventListener(\'Dolibarr:Init\', function(e) {',
					'        Dolibarr.setContextVars(<?php print json_encode($contextConst); ?>);',
					'        Dolibarr.setContextVars(<?php print json_encode($contextVars); ?>, true);',
					'    });',
					'</script>',
				);
				echo $documentation->showCode($lines, 'php');
				?>
			</div>

			<h3>Get context var</h3>
			<div class="documentation-example">
				<?php
				$lines = array(
					'<script nonce="<?php print getNonce() ?>" >',
					'    document.addEventListener(\'Dolibarr:Ready\', function(e) {',
					'        let url = Dolibarr.getContextVar(\'DOL_URL_ROOT\', \'The optional fallback value\'));',
					'        console.log(url);',
					'    });',
					'</script>',
				);
				echo $documentation->showCode($lines, 'php');
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
