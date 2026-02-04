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
$experimentName = 'UxDolibarrContextLangsTool';

$experimentAssetsPath = $documentation->baseUrl . '/experimental/experiments/dolibarr-context/assets/';
$js = [
	'/includes/ace/src/ace.js',
	'/includes/ace/src/ext-statusbar.js',
	'/includes/ace/src/ext-language_tools.js',
	$experimentAssetsPath . '/dolibarr-context.umd.js',
	$experimentAssetsPath . '/dolibarr-tool.langs.js',
	$experimentAssetsPath . '/dolibarr-tool.seteventmessage.js',
];
$css = [];

// Output html head + body - Param is Title
$documentation->docHeader($langs->trans($experimentName, $group), $js, $css);

// Set view for menu and breadcrumb
$documentation->view = [$group, 'UxDolibarrContext', $experimentName];

// Output sidebar
$documentation->showSidebar(); ?>
<script>
	Dolibarr.setContextVars(<?php print json_encode([
		'DOL_VERSION' => DOL_VERSION,
		'MAIN_LANG_DEFAULT' => $langs->getDefaultLang(),
		'DOL_LANG_INTERFACE_URL' =>  dol_buildpath('admin/tools/ui/experimental/experiments/dolibarr-context/langs-tool-interface.php', 1),
	]) ?>);
</script>
<div class="doc-wrapper">

	<?php $documentation->showBreadCrumb(); ?>

	<div class="doc-content-wrapper">

		<h1 class="documentation-title"><?php echo $langs->trans($experimentName); ?></h1>

		<?php $documentation->showSummary(); ?>

		<div class="documentation-section">
			<h2 id="titlesection-basicusage" class="documentation-title">Introduction</h2>

			<p>
				The Dolibarr Context Langs Tool is a powerful JavaScript utility to manage translations and locales dynamically.<br/>
				It allows you to load translation domains, set the current language, clear cache, and retrieve translated strings in your scripts.
			</p>

		</div>

		<div class="documentation-section">
			<h2 id="titlesection-setup-contextvars" class="documentation-title">Setup Context Variables</h2>
			<p>
				Before using the tool, you should declare the necessary context variables on your page.<br/>
				These variables allow the tool to know the current Dolibarr version, the default language, and the interface URL used to fetch translations.
			</p>
			<p>
				However, like the setEventMessage tool, the Langs tool is a core tool and is always loaded by Dolibarr.<br/>
				Therefore, in most cases, you do not need to set these variables manually, as they are already defined.
			</p>
			<div class="documentation-example">

				<?php
				$lines = array(
					'<script nonce="'.getNonce().'" >',
					'Dolibarr.setContextVars(<?php print json_encode([',
					'	\'DOL_VERSION\' => DOL_VERSION,',
					'	\'MAIN_LANG_DEFAULT\'  => $langs->getDefaultLang(),',
					'	\'DOL_LANG_INTERFACE_URL\' =>  dol_buildpath(\'admin/tools/ui/experimental/experiments/dolibarr-context/langs-tool-interface.php\',1),',
					']) ?>);',
					'</script>',
				);
				echo $documentation->showCode($lines, 'php');
				?>
			</div>
		</div>

		<div class="documentation-section">
			<h2 id="titlesection-basic-usage" class="documentation-title">Basic Usage</h2>
			<p>
				The main features of the Langs tool are:
			</p>
			<ul>
				<li>Load translations for a specific domain with caching</li>
				<li>Set or change the current locale</li>
				<li>Clear cached translations</li>
				<li>Retrieve a translated string by key</li>
			</ul>

			<p>Example:</p>

			<div class="documentation-example">
				<?php
				$lines = array(
					'<script nonce="'.getNonce().'" >',
					'document.addEventListener(\'Dolibarr:Ready\', async function(e) {',
					'',
					'	if(Dolibarr.checkToolExist(\'langs\')){ // not mandatory because langs tool will be a core tool',
					'',
					'		// Load langs',
					'		Dolibarr.tools.langs.load(\'uxdocumentation\'); // will use cache but need to load lang in new local',
					'',
					'		// Clear cache',
					'		document.getElementById(\'clearCache\').addEventListener(\'click\', async function(e) {',
					'			await Dolibarr.tools.langs.clearCache();',
					'			const txt = Dolibarr.tools.langs.trans(\'CacheCleared\');',
					'			Dolibarr.tools.setEventMessage(txt);',
					'		});',
					'',
					'		// SET lang in fr_FR',
					'		document.getElementById(\'setlangFr\').addEventListener(\'click\', async function(e) {',
					'			await Dolibarr.tools.langs.setLocale(\'fr_FR\');',
					'			const txt = Dolibarr.tools.langs.trans(\'LangsLocalChangedTo\', \'fr_FR\');',
					'			Dolibarr.tools.setEventMessage(txt);',
					'		});',
					'',
					'		// SET lang in en_US',
					'		document.getElementById(\'setlangEn\').addEventListener(\'click\', async function(e) {',
					'			await Dolibarr.tools.langs.setLocale(\'en_US\');',
					'			const txt = Dolibarr.tools.langs.trans(\'LangsLocalChangedTo\', \'en_US\');',
					'			Dolibarr.tools.setEventMessage(txt);',
					'		});',
					'',
					'		// pop a message in current lang',
					'		document.getElementById(\'popmessage\').addEventListener(\'click\', async function(e) {',
					'			const txt = Dolibarr.tools.langs.trans(\'ContextLangToolTest\');',
					'			Dolibarr.tools.setEventMessage(txt);',
					'		});',
					'	}',
					'});',
					'</script>',
				);
				echo $documentation->showCode($lines, 'php');

				print implode("\n", $lines);
				?>
				<p>1. Set the current lang</p>
				<div >
					<button id="setlangFr" class="button">Set lang in french</button>
					<button id="setlangEn" class="button">Set lang in english</button>
					<button id="clearCache" class="button">Clear cache</button>
				</div>

				<p>2. Pop translated message</p>
				<div>
					<button id="popmessage" class="button">pop message</button>
				</div>
			</div>

		</div>




	</div>




</div>

</div>
<?php
// Output close body + html
$documentation->docFooter();
?>
