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

//
$action = GETPOST('action', 'aZ09');
if ($action == 'addticketexample') {
	$ticketMsg = 'You have submitted a form to create a new ticket<br><br>';
	$ticketMsg .= '<b>Ref:</b> '.GETPOST('ref').'<br>';
	$ticketMsg .= '<b>Request type:</b> '.ucfirst(GETPOST('type_code')).'<br>';
	$ticketMsg .= '<b>Socid:</b> '.GETPOSTINT('socid').'<br>';
	$ticketMsg .= '<b>Description:</b> '.GETPOST('description');

	setEventMessages($ticketMsg, '', 'mesgs');
}

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


<div class="doc-wrapper">


	<?php $documentation->showBreadCrumb(); ?>

	<div class="doc-content-wrapper">

		<h1 class="documentation-title"><?php echo $langs->trans('DocUiDialogTitle'); ?></h1>
		<p class="documentation-text"><?php echo $langs->trans('DocUiDialogDescription'); ?></p>

		<?php $documentation->showSummary(); ?>

		<!-- Basic usage -->
		<div id="dialogsection-basicusage" class="documentation-section" style="margin-top:24px;">
			<h2 class="documentation-title"><?php echo $langs->trans('DocBasicUsage'); ?></h2>
			<p class="documentation-text">Display a dialog depending on the position, either centered or anchored to the right</p>
			<div class="documentation-example">
				<button class="butAction" style="margin:4px;" id="btn-dialog-center" data-product-id="42" data-product-ref="PROD-ABC" data-product-price="29.90" ><span class="opacitylow">Dialog |</span> Center</button>
				<button class="butAction" style="margin:4px;" id="btn-dialog-right" data-product-id="101" data-product-ref="PROD-XYZ" data-product-price="3.20"><span class="opacitylow">Dialog |</span> Right</button>
			</div>
		</div>

		<!-- HEADER & FOOTER -->
		<div id="dialogsection-header" class="documentation-section">
			<h2 class="documentation-title"><?php echo $langs->trans('DocDialogHeaderAndFooter'); ?></h2>

			<h3 class="nomarginbottom">Header</h3>
			<p class="documentation-text">Allow customization of the header content to fit specific needs, or remove it entirely when not required. The <code>title</code> param controls the header text. Add an optional <code>icon</code> (FontAwesome class) and <code>iconColor</code> (CSS color value) to prefix the title with an icon.</p>
			<div class="documentation-example">
				<button class="butAction" style="margin:4px;" id="btn-dialog-without-title" data-dialog-header="no"><span class="opacitylow">Dialog |</span> Without header</button>
				<button class="butAction" style="margin:4px;" id="btn-dialog-with-icon" data-dialog-header="icon"><span class="opacitylow">Dialog |</span> Custom Icon</button>
				<button class="butAction" style="margin:4px;" id="btn-dialog-with-color-icon" data-dialog-header="iconcolor"><span class="opacitylow">Dialog |</span> Custom color Icon</button>
			</div>

			<h3 class="nomarginbottom">Footer</h3>
			<p class="documentation-text">
				By default no footer is rendered (<code>footer: null</code>). Pass a <code>footer</code> object to display a button bar anchored at the bottom of the dialog, outside the scrollable content area.
			</p>
			<p class="documentation-text">
				The <code>submitFormId</code> param uses the HTML5 <code>form</code> attribute to link the submit button to a <code>&lt;form&gt;</code> that lives inside the scrollable content — even though the button is outside the <code>&lt;form&gt;</code> tag.
			</p>
			<div class="documentation-example">
				<button class="butAction" style="margin:4px;" id="btn-dialog-footer-default"><span class="opacitylow">Dialog |</span> Default footer</button>
				<button class="butAction" style="margin:4px;" id="btn-dialog-footer-left"><span class="opacitylow">Dialog |</span> Align left</button>
				<button class="butAction" style="margin:4px;" id="btn-dialog-footer-center"><span class="opacitylow">Dialog |</span> Align center</button>
				<button class="butAction" style="margin:4px;" id="btn-dialog-footer-borderless"><span class="opacitylow">Dialog |</span> No border</button>
				<button class="butAction" style="margin:4px;" id="btn-dialog-footer-cancelonly"><span class="opacitylow">Dialog |</span> Cancel only</button>
			</div>

			<h3 class="nomarginbottom">Footer &amp; close buttons from modal content</h3>
			<p class="documentation-text">
				The <code>footer</code> JS param is optional. You can also define the footer directly inside the modal content file — the JS will automatically move it outside the scrollable area and anchor it at the bottom of the dialog.
			</p>
			<p class="documentation-text">
				If both are defined, <b>the JS <code>footer</code> param takes priority</b>: the footer found in the content is discarded.
			</p>
			<p class="documentation-text">
				Any element with the <code>data-dol-dialog-close</code> attribute — whether inside a <code>.dol-dialog-footer</code> or anywhere in the content — will close the dialog when clicked (with animation if enabled).
			</p>
		</div>

		<!-- VARS -->
		<div id="dialogsection-data" class="documentation-section">
			<h2 class="documentation-title"><?php echo $langs->trans('DocDialogData'); ?></h2>
			<p class="documentation-text">Just set a data- attribute in kebab-case and grab it in camelCase.<br>E.g. `data-product-ref` becomes `GETPOST('productRef')`.</p>
			<div class="documentation-example">
				<button class="butAction" style="margin:4px;" id="btn-dialog-with-data-a" data-product-id="12" data-product-ref="REF-PROD-A" data-product-type="product" data-product-price="14.99"><span class="opacitylow">Dialog |</span> Product data</button>
				<button class="butAction" style="margin:4px;" id="btn-dialog-with-data-b" data-product-id="54" data-product-ref="REF-SERV-B" data-product-type="service" data-product-price="182.60"><span class="opacitylow">Dialog |</span> Service data</button>
			</div>
		</div>

		<!-- FORMS -->
		<div id="dialogsection-forms" class="documentation-section">
			<h2 class="documentation-title"><?php echo $langs->trans('DocDialogForms'); ?></h2>
			<p class="documentation-text">
				There are two ways to use a form inside a dialog: <b>classic submission</b> (standard PHP page reload) and <b>AJAX submission</b> (no page reload, JSON response).
				In both cases, the form lives in the modal content loaded via <code>url</code>, and the submit button is injected by the <code>footer</code> parameter so that it sits outside the scrollable area.
			</p>

			<h3 class="nomarginbottom">Classic form submission</h3>
			<p class="documentation-text nomargintop">
				The form is placed in the modal content file with a unique <code>id</code>. The <code>footer</code> parameter uses <code>submitFormId</code> to associate the submit button with that form via the HTML5 <code>form</code> attribute — even though the button is rendered outside the <code>&lt;form&gt;</code> tag.
				On submit, the page navigates normally to the form <code>action</code>.
			</p>
			<h3 class="nomarginbottom">AJAX form submission</h3>
			<p class="documentation-text nomargintop">
				Add the class <code>dol-dialog-ajax</code> to the form. The dialog will intercept the submit event and send the form data via <code>fetch</code> (no page reload).
				The server must return a JSON response. If <code>success: true</code>, the dialog closes. If <code>success: false</code>, an error message is displayed inside the modal.
				Use the <code>onSuccess</code> callback to execute code after the dialog closes — it receives the full JSON response as argument.
			</p>
			<p class="documentation-text">
				<b>Multiple validation errors:</b> use <code>\r\n</code> as separator in the PHP message (e.g. <code>implode("\r\n", $errors)</code>). The error block renders each line separately thanks to <code>white-space: pre-line</code>.
			</p>

			<div class="documentation-example">
				<button class="butAction" style="margin:4px;" id="btn-dialog-with-form" data-use-ajax="0"><span class="opacitylow">Dialog |</span> Classic form</button>
				<button class="butAction" style="margin:4px;" id="btn-dialog-with-ajaxform" data-use-ajax="1"><span class="opacitylow">Dialog |</span> AJAX form</button>
			</div>
		</div>

		<!-- SIZE -->
		<div id="dialogsection-size" class="documentation-section">
			<h2 class="documentation-title"><?php echo $langs->trans('DocDialogSize'); ?></h2>
			<p class="documentation-text">You can override the size of the dialog with parameters. If they are not specified, the default parameters will be applied.</p>
			<p class="documentation-text">
				<b>Centered mode:</b> By default, the width is set to 500px and the height automatically adjusts to fit the content. You can override these values by using the `width` and `height` parameters.<br>
				<b>Right mode:</b> In this mode, the only parameter that can be overridden is the width. Height is always 100%.
			</p>
			<p class="documentation-text">You can provide these parameters in different formats: for both `width` and `height`, you can use a numeric value or specify a size with units such as `px`, `vw`, `%`, etc. For example: width:"600", width:"600px", or width:"50vw" are all valid.</p>

			<div class="documentation-example">
				<button class="butAction" style="margin:4px;" id="btn-dialog-params-size-center"><span class="opacitylow">Dialog |</span> Center: 50vw & 50vh</button>
				<button class="butAction" style="margin:4px;" id="btn-dialog-params-size-right"><span class="opacitylow">Dialog |</span> Right: 800px</button>
			</div>
		</div>

		<!-- OTHERS -->
		<div id="dialogsection-params" class="documentation-section">
			<h2 class="documentation-title"><?php echo $langs->trans('DocDialogOthers'); ?></h2>
			<h3 class="nomarginbottom">Animation</h3>
			<p class="documentation-text nomargintop">Animations can be blocked using the animation parameter (true/false). If false, the behavior will be that of the browser.</p>
			<div class="documentation-example">
				<button class="butAction" style="margin:4px;" id="btn-dialog-params-animation-center"><span class="opacitylow">Dialog |</span> Center with no Animation</button>
				<button class="butAction" style="margin:4px;" id="btn-dialog-params-animation-right"><span class="opacitylow">Dialog |</span> Right with no Animation</button>
			</div>
			<h3 class="nomarginbottom">HTML persistence in the DOM</h3>
			<p class="documentation-text nomargintop">You can choose whether the dialog box should remain persistent in the DOM. If it is persistent, it is loaded only once and remains in the DOM, which means that it will not be reloaded on subsequent clicks. This can be useful, for example, to preserve form values between two openings. Otherwise, the dialog box is removed from the DOM when it is closed and rebuilt each time it is opened.</p>
			<div class="documentation-example">
				<button class="butAction" style="margin:4px;" id="btn-dialog-params-persist" data-persist="1"><span class="opacitylow">Dialog |</span> Persistent</button>
				<button class="butAction" style="margin:4px;" id="btn-dialog-params-notpersist" data-persist="0"><span class="opacitylow">Dialog |</span> Non-persistent</button>
			</div>
		</div>

	</div>

</div>

<script nonce="<?php echo getNonce(); ?>">

	Dolibarr.on('Ready', async function () {

		await Dolibarr.tools.langs.load('uxdocumentation');

		// Basic usage
		const uiDialogTitleCenter = Dolibarr.tools.langs.trans('UiDolibarrDialogCenter');
		const uiDialogTitleRight = Dolibarr.tools.langs.trans('UiDolibarrDialogRight');
		let modalUrl = '<?php echo dol_buildpath('/'.$documentation->baseUrl.'/experimental/experiments/dialog/modals/example-simple.php', 1); ?>';
		Dolibarr.tools.uiDialog('#btn-dialog-center', {
			dialogId: 'dialog-center',
			align: 'center',
			title: uiDialogTitleCenter,
			url: modalUrl
		});
		Dolibarr.tools.uiDialog('#btn-dialog-right',  {
			dialogId: 'dialog-right',
			align: 'right',
			title: uiDialogTitleRight,
			url: modalUrl
		});

		// Header
		const uiDialogTitleWithIcon = 'Dialog with Custom Icon';
		modalUrl = '<?php echo dol_buildpath('/'.$documentation->baseUrl.'/experimental/experiments/dialog/modals/example-header.php', 1); ?>';
		Dolibarr.tools.uiDialog('#btn-dialog-without-title', {
			dialogId: 'dialog-without-title',
			align: 'center',
			url: modalUrl
		});
		Dolibarr.tools.uiDialog('#btn-dialog-with-icon', {
			dialogId: 'dialog-with-icon',
			title: uiDialogTitleWithIcon,
			icon: 'fas fa-flask',
			align: 'center',
			url: modalUrl
		});
		Dolibarr.tools.uiDialog('#btn-dialog-with-color-icon', {
			dialogId: 'dialog-with-color-icon',
			title: uiDialogTitleWithIcon,
			icon: 'fas fa-landmark',
			iconColor: '#b0bb39',
			align: 'center',
			url: modalUrl
		});

		// Footer
		const uiDialogTitleFooter = 'Dialog with footer';
		modalUrl = '<?php echo dol_buildpath('/'.$documentation->baseUrl.'/experimental/experiments/dialog/modals/example-simple.php', 1); ?>';
		Dolibarr.tools.uiDialog('#btn-dialog-footer-default', {
			dialogId: 'dialog-footer-default',
			title: uiDialogTitleFooter,
			icon: 'fas fa-stream',
			align: 'center',
			url: modalUrl,
			footer: {}
		});
		Dolibarr.tools.uiDialog('#btn-dialog-footer-left', {
			dialogId: 'dialog-footer-left',
			title: uiDialogTitleFooter,
			icon: 'fas fa-align-left',
			align: 'center',
			url: modalUrl,
			footer: { align: 'left' }
		});
		Dolibarr.tools.uiDialog('#btn-dialog-footer-center', {
			dialogId: 'dialog-footer-center',
			title: uiDialogTitleFooter,
			icon: 'fas fa-align-center',
			align: 'center',
			url: modalUrl,
			footer: { align: 'center' }
		});
		Dolibarr.tools.uiDialog('#btn-dialog-footer-borderless', {
			dialogId: 'dialog-footer-borderless',
			title: uiDialogTitleFooter,
			icon: 'fas fa-minus',
			align: 'center',
			url: modalUrl,
			footer: { borderTop: false }
		});
		Dolibarr.tools.uiDialog('#btn-dialog-footer-cancelonly', {
			dialogId: 'dialog-footer-cancelonly',
			title: uiDialogTitleFooter,
			icon: 'fas fa-times',
			align: 'center',
			url: modalUrl,
			footer: { showSubmit: false, cancelLabel: 'Close' }
		});

		// Data attributes
		const uiDialogTitleWithData = 'How to pass variables to a dialog?';
		modalUrl = '<?php echo dol_buildpath('/'.$documentation->baseUrl.'/experimental/experiments/dialog/modals/example-data.php', 1); ?>';
		Dolibarr.tools.uiDialog('#btn-dialog-with-data-a', {
			dialogId: 'dialog-with-data-a',
			title: uiDialogTitleWithData,
			icon: 'fas fa-code',
			align: 'center',
			url: modalUrl
		});
		Dolibarr.tools.uiDialog('#btn-dialog-with-data-b', {
			dialogId: 'dialog-with-data-b',
			title: uiDialogTitleWithData,
			icon: 'fas fa-code',
			align: 'center',
			url: modalUrl
		});

		// Forms
		const uiDialogTitleForm = 'Add a new ticket';
		modalUrl = '<?php echo dol_buildpath('/'.$documentation->baseUrl.'/experimental/experiments/dialog/modals/example-form.php', 1); ?>';
		Dolibarr.tools.uiDialog('#btn-dialog-with-form', {
			dialogId: 'dialog-with-form',
			title: uiDialogTitleForm,
			icon: 'fas fa-ticket-alt',
			iconColor: '#3bbfa8',
			align: 'right',
			url: modalUrl,
			footer: {
				submitFormId: 'dol-dialog-form-example'
			}
		});
		Dolibarr.tools.uiDialog('#btn-dialog-with-ajaxform', {
			dialogId: 'dialog-with-ajaxform',
			title: uiDialogTitleForm,
			icon: 'fas fa-ticket-alt',
			iconColor: '#3bbfa8',
			align: 'right',
			url: modalUrl,
			footer: {submitFormId: 'dol-dialog-ajaxform-example'},
			onSuccess: function(data) {
				Dolibarr.tools.setEventMessage(data.message);
			}
		});

		// Control size
		const uiDialogTitleSizeControl = 'Very large dialog';
		modalUrl = '<?php echo dol_buildpath('/'.$documentation->baseUrl.'/experimental/experiments/dialog/modals/example-size.php', 1); ?>';
		Dolibarr.tools.uiDialog('#btn-dialog-params-size-center', {
			dialogId: 'dialog-size-c',
			title: uiDialogTitleSizeControl,
			icon: 'fas fa-arrows-alt-h',
			align: 'center',
			url: modalUrl,
			width: '50vw',
			height: '50vh'
		});
		Dolibarr.tools.uiDialog('#btn-dialog-params-size-right', {
			dialogId: 'dialog-size-r',
			title: uiDialogTitleSizeControl,
			icon: 'fas fa-arrows-alt-h',
			align: 'right',
			url: modalUrl,
			width: 800,
		});

		// No animation
		const uiDialogTitleNoAnimation = 'No animation on opening';
		modalUrl = '<?php echo dol_buildpath('/'.$documentation->baseUrl.'/experimental/experiments/dialog/modals/example-no-animation.php', 1); ?>';
		Dolibarr.tools.uiDialog('#btn-dialog-params-animation-center', {
			dialogId: 'dialog-noanim-c',
			title: uiDialogTitleNoAnimation,
			icon: 'fas fa-eye-slash',
			align: 'center',
			url: modalUrl,
			animation: false,
		});
		Dolibarr.tools.uiDialog('#btn-dialog-params-animation-right', {
			dialogId: 'dialog-noanim-r',
			title: uiDialogTitleNoAnimation,
			icon: 'fas fa-eye-slash',
			align: 'right',
			url: modalUrl,
			animation: false,
		});

		// Persist
		const uiDialogTitlePersistYes = 'Persistant Dialog';
		const uiDialogTitlePersistNo = 'Non-persistent dialog';
		modalUrl = '<?php echo dol_buildpath('/'.$documentation->baseUrl.'/experimental/experiments/dialog/modals/example-persist.php', 1); ?>';
		Dolibarr.tools.uiDialog('#btn-dialog-params-persist', {
			dialogId: 'dialog-persist',
			title: uiDialogTitlePersistYes,
			icon: 'fas fa-bars',
			align: 'right',
			url: modalUrl,
			animation: false,
			persist: true
		});
		Dolibarr.tools.uiDialog('#btn-dialog-params-notpersist', {
			dialogId: 'dialog-notpersist',
			title: uiDialogTitlePersistNo,
			icon: 'fas fa-bars',
			align: 'right',
			url: modalUrl,
			animation: false,
			persist: false
		});
	});
</script>

<?php
// Output close body + html
$documentation->docFooter();
?>
