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
$experimentName = 'UiDolibarrDialog';

$experimentAssetsPath = $documentation->baseUrl . '/experimental/experiments/dialog/assets/';
$experimentAssetsPath2 = $documentation->baseUrl . '/experimental/experiments/dolibarr-context/assets/';
$js = [
	'/includes/ace/src/ace.js',
	'/includes/ace/src/ext-statusbar.js',
	'/includes/ace/src/ext-language_tools.js',
	$experimentAssetsPath2 . '/dolibarr-context.umd.js',
	$experimentAssetsPath2 . '/dolibarr-tool.seteventmessage.js',
	$experimentAssetsPath2 . '/dolibarr-tool.langs.js',
	$experimentAssetsPath . '/ui-dialog.js',
];
$css = [
	$experimentAssetsPath . '/ui-dialog.css',
];

// Output html head + body - Param is Title
$documentation->docHeader($langs->trans($experimentName, $group), $js, $css);

// Set view for menu and breadcrumb
$documentation->view = [$group, $experimentName];

// Output sidebar
$documentation->showSidebar(); ?>

<script nonce="<?php echo getNonce(); ?>">
//
Dolibarr.setContextVars(<?php print json_encode([
	'DOL_VERSION' => DOL_VERSION,
	'MAIN_LANG_DEFAULT'  => 'en_US',
	'DOL_LANG_INTERFACE_URL' =>  dol_buildpath('admin/tools/ui/experimental/experiments/dolibarr-context/langs-tool-interface.php',1),
]) ?>);
</script>

<div class="doc-wrapper">

	<?php $documentation->showBreadCrumb(); ?>

	<div class="doc-content-wrapper">

		<h1 class="documentation-title"><?php echo $langs->trans('DocUiDialogTitle'); ?></h1>
		<p class="documentation-text"><?php echo $langs->trans('DocUiDialogDescription'); ?></p>

		<?php $documentation->showSummary(); ?>

		<!-- Basic usage -->
		<div id="dialogsection-basicusage" class="documentation-section" style="margin-top:24px;">
			<h2 class="documentation-title">DocBasicUsage</h2>
			<p class="documentation-text"></p>
			<div class="documentation-example center">
				<button class="butAction" id="btn-dialog-center" data-product-id="42" data-product-ref="PROD-ABC" data-product-price="29.90" >Open Dialog -- Center</button>
				<button class="butAction" id="btn-dialog-right" data-product-id="101" data-product-ref="PROD-XYZ" data-product-price="3.20">Open Dialog -- Right</button>
			</div>
			<script nonce="<?php echo getNonce(); ?>">

				Dolibarr.on('Ready', async function () {

					await Dolibarr.tools.langs.load('uxdocumentation');
					const uiDialogTitleCenter = Dolibarr.tools.langs.trans('UiDolibarrDialogCenter');
					const uiDialogTitleRight = Dolibarr.tools.langs.trans('UiDolibarrDialogRight');
					const modalUrl = '<?php echo dol_buildpath('/'.$documentation->baseUrl.'/experimental/experiments/dialog/modals/example.php', 1); ?>';

					Dolibarr.tools.uiDialog('#btn-dialog-center', { dialogId: 'dialog-center', align: 'center', title: uiDialogTitleCenter, url: modalUrl });
					Dolibarr.tools.uiDialog('#btn-dialog-right',  { dialogId: 'dialog-right',  align: 'right', title: uiDialogTitleRight, url: modalUrl });
				});
			</script>
			<br>
		</div>

	</div>

</div>

<?php
// Output close body + html
$documentation->docFooter();
?>
