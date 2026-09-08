<?php

/**
 * @var string $setEventMessageJsContextTitle
 * @var Documentation $documentation
 *
 * @phan-var string|null $setEventMessageJsContextTitle
 * @phan-var Documentation $documentation
 */

if (!defined('DOL_VERSION')) {die();}

global $documentation, $setEventMessageJsContextTitle;

if ($documentation === null || !($documentation instanceof Documentation)) { return; }


?>

<div class="documentation-section">
	<h2 id="titlesection-tool-seteventmessage" class="documentation-title"><?php print $setEventMessageJsContextTitle ?? 'Set event message tool'; ?></h2>

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
	$documentation->showCode($lines, 'php'); ?>
	<div class="documentation-example">

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
