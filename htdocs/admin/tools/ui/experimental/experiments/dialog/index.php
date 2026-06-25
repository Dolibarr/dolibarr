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
			<p class="documentation-text">Display a dialog depending on the position, either centered or anchored to the right. The first argument is any CSS selector: an <b>ID</b> arms a single element, while a <b>class</b> (or any selector matching several elements) arms every matching trigger in one call.</p>

			<h3 class="nomarginbottom">Single trigger (ID selector)</h3>
			<div class="documentation-example">
				<button class="butAction" style="margin:4px;" id="btn-dialog-center" data-product-id="42" data-product-ref="PROD-ABC" data-product-price="29.90" ><span class="opacitylow">Dialog |</span> Center</button>
				<button class="butAction" style="margin:4px;" id="btn-dialog-right" data-product-id="101" data-product-ref="PROD-XYZ" data-product-price="3.20"><span class="opacitylow">Dialog |</span> Right</button>
			</div>

			<h3 class="nomarginbottom">Multiple triggers (class selector)</h3>
			<p class="documentation-text">A single call arms the three buttons below. Each one sends its own <code>data-*</code> attributes, and the <code>dialogId</code> is automatically suffixed with the element index so every dialog stays independent — ideal for action buttons in a list or a table.</p>
			<div class="documentation-example">
				<button class="butAction dialog-multi-trigger" style="margin:4px;" data-product-id="1" data-product-ref="REF-001" data-product-type="product" data-product-price="9.99"><span class="opacitylow">Row 1 |</span> Open</button>
				<button class="butAction dialog-multi-trigger" style="margin:4px;" data-product-id="2" data-product-ref="REF-002" data-product-type="service" data-product-price="49.00"><span class="opacitylow">Row 2 |</span> Open</button>
				<button class="butAction dialog-multi-trigger" style="margin:4px;" data-product-id="3" data-product-ref="REF-003" data-product-type="product" data-product-price="120.50"><span class="opacitylow">Row 3 |</span> Open</button>
			</div>

			<?php
			$lines = array(
				'<!-- ID selector: arms a single trigger -->',
				'<button id="btn-open" class="butAction">Open</button>',
				'',
				'<!-- Class selector: one call arms every matching trigger -->',
				'<button class="row-trigger" data-product-id="1">Row 1</button>',
				'<button class="row-trigger" data-product-id="2">Row 2</button>',
				'',
				'<script>',
				'Dolibarr.on("Ready", function () {',
				'	// Single trigger',
				'	Dolibarr.tools.uiDialog("#btn-open", {',
				'		dialogId: "my-dialog",',
				'		align: "center",',
				'		url: "/path/to/modal.php"',
				'	});',
				'',
				'	// Multiple triggers: dialogId is auto-suffixed per element (row-dialog-0, -1, ...)',
				'	Dolibarr.tools.uiDialog(".row-trigger", {',
				'		dialogId: "row-dialog",',
				'		url: "/path/to/modal.php"',
				'	});',
				'});',
				'</script>',
			);
			$documentation->showCode($lines, 'php');
			?>

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

			<?php
			$lines = array(
				'<script>',
				'// Header: title + optional FontAwesome icon and color',
				'Dolibarr.tools.uiDialog("#btn-open", {',
				'	dialogId: "dlg-header",',
				'	header: { title: "My title", icon: "fas fa-user", iconColor: "#3bbfa8" },',
				'	url: "/path/to/modal.php"',
				'});',
				'',
				'// Footer: Cancel + Submit bar. submitFormId links the submit button',
				'// to a <form id="my-form"> that lives inside the modal content.',
				'Dolibarr.tools.uiDialog("#btn-open-2", {',
				'	dialogId: "dlg-footer",',
				'	url: "/path/to/modal.php",',
				'	footer: { showCancel: true, showSubmit: true, submitFormId: "my-form", align: "right" }',
				'});',
				'</script>',
			);
			$documentation->showCode($lines, 'php');
			?>

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

			<?php
			$lines = array(
				'<!-- Set data-* in kebab-case on the trigger -->',
				'<button id="btn-open" data-product-id="12" data-product-ref="REF-A">Open</button>',
				'',
				'<script>',
				'// Every data-* of the clicked trigger is appended to the AJAX url query',
				'Dolibarr.tools.uiDialog("#btn-open", {',
				'	dialogId: "dlg-data",',
				'	url: "/path/to/modal.php"',
				'});',
				'</script>',
			);
			$documentation->showCode($lines, 'php');
			?>

			<p class="documentation-text">In the modal, grab them in camelCase:</p>
			<?php
			$lines = array(
				'<?php',
				'',
				'$id  = GETPOST("productId", "int");',
				'$ref = GETPOST("productRef", "alpha");',
			);
			$documentation->showCode($lines, 'php');
			?>
		</div>

		<!-- BUTTONS -->
		<div id="dialogsection-data" class="documentation-section">
			<h2 class="documentation-title">Footer dialog button class</h2>
			<div class="documentation-example">
				<button class="dialog-btn dialog-btn-primary classfortooltip" title=".dialog-btn .dialog-btn-primary"  >Primary button</button>
				<button class="dialog-btn classfortooltip"  title=".dialog-btn"  >Secondary button</button>
				<button class="dialog-btn dialog-btn-destructive classfortooltip"  title=".dialog-btn .dialog-btn-destructive"  >Destructive button</button>
				<button class="dialog-btn dialog-btn-warning classfortooltip"  title=".dialog-btn .dialog-btn-warning"  >Warning button</button>
				<button class="dialog-btn dialog-btn-success classfortooltip"  title=".dialog-btn .dialog-btn-success"  >Success button</button>
			</div>
			<h4 class="documentation-title">Colorblind user : dialog button variant</h4>
			<div class="documentation-example colorblind-protanopia">
				<button class="dialog-btn dialog-btn-primary classfortooltip" title=".dialog-btn .dialog-btn-primary"  >Primary button</button>
				<button class="dialog-btn classfortooltip"  title=".dialog-btn"  >Secondary button</button>
				<button class="dialog-btn dialog-btn-destructive classfortooltip"  title=".dialog-btn .dialog-btn-destructive"  >Destructive button</button>
				<button class="dialog-btn dialog-btn-warning classfortooltip"  title=".dialog-btn .dialog-btn-warning"  >Warning button</button>
				<button class="dialog-btn dialog-btn-success classfortooltip"  title=".dialog-btn .dialog-btn-success"  >Success button</button>
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
				The server must return a JSON response (use the <code>JsonResponse</code> class). If <code>result</code> is <code>1</code>, the dialog closes. If <code>result</code> is <code>0</code>, the <code>msg</code> is displayed as an error inside the modal.
				Use the <code>onSuccess</code> callback to execute code after the dialog closes — it receives the full JSON response as argument.
			</p>
			<p class="documentation-text">
				<b>Multiple validation errors:</b> use <code>\r\n</code> as separator in the PHP message (e.g. <code>implode("\r\n", $errors)</code>). The error block renders each line separately thanks to <code>white-space: pre-line</code>.
			</p>

			<div class="documentation-example">
				<button class="butAction" style="margin:4px;" id="btn-dialog-with-form" data-use-ajax="0"><span class="opacitylow">Dialog |</span> Classic form</button>
				<button class="butAction" style="margin:4px;" id="btn-dialog-with-ajaxform" data-use-ajax="1"><span class="opacitylow">Dialog |</span> AJAX form</button>
			</div>

			<?php
			$lines = array(
				'<script>',
				'// Classic submission (page reload): submit button linked to the form via submitFormId',
				'Dolibarr.tools.uiDialog("#btn-form", {',
				'	dialogId: "dlg-form",',
				'	url: "/path/to/form.php",',
				'	footer: { submitFormId: "my-form" }',
				'});',
				'',
				'// AJAX submission: add class "dol-dialog-ajax" to the <form> inside the content',
				'Dolibarr.tools.uiDialog("#btn-ajaxform", {',
				'	dialogId: "dlg-ajaxform",',
				'	url: "/path/to/form.php",',
				'	footer: { submitFormId: "my-form" },',
				'	onSuccess: function (data) { Dolibarr.tools.setEventMessage(data.msg); }',
				'});',
				'</script>',
			);
			$documentation->showCode($lines, 'php');
			?>

			<p class="documentation-text">Server side — the AJAX endpoint must answer with a <code>JsonResponse</code>:</p>
			<?php
			$lines = array(
				'<?php',
				'',
				'require_once DOL_DOCUMENT_ROOT.\'/core/class/jsonResponse.class.php\';',
				'',
				'$response = new JsonResponse();',
				'$response->result = 1;          // 1 = success (dialog closes), 0 = error',
				'$response->msg = "Saved!";      // shown via onSuccess, or as error when result = 0',
				'print $response->getResponse();',
			);
			$documentation->showCode($lines, 'php');
			?>
		</div>

		<!-- SIZE -->
		<div id="dialogsection-size" class="documentation-section">
			<h2 class="documentation-title"><?php echo $langs->trans('DocDialogSize'); ?></h2>

			<p class="documentation-text">You can override the size of the dialog with parameters. If they are not specified, the default parameters will be applied.</p>
			<p class="documentation-text">
				<b>Responsive size:</b> xs, lg, xl, xxl<br/>
				<b>Centered mode:</b> By default, the width is set to 500px and the height automatically adjusts to fit the content. You can override these values by using the `width` and `height` parameters.<br>
				<b>Right mode:</b> In this mode, the only parameter that can be overridden is the width. Height is always 100%.
			</p>
			<p class="documentation-text">You can provide these parameters in different formats: for both `width` and `height`, you can use a numeric value or specify a size with units such as `px`, `vw`, `%`, etc. For example: width:"600", width:"600px", or width:"50vw" are all valid.</p>

			<div class="documentation-example">
				<button class="butAction" style="margin:4px;" id="btn-dialog-params-size-center"><span class="opacitylow">Dialog |</span> Center: 50vw & 50vh</button>
				<button class="butAction" style="margin:4px;" id="btn-dialog-params-size-right"><span class="opacitylow">Dialog |</span> Right: 800px</button>
			</div>

			<h5>Right size</h5>
			<div class="documentation-example">
				<button class="butAction" style="margin:4px;" id="btn-dialog-xs" ><span class="opacitylow">Dialog |</span>  xs</button>
				<button class="butAction" style="margin:4px;" id="btn-dialog-default-size" ><span class="opacitylow">Dialog |</span> default</button>
				<button class="butAction" style="margin:4px;" id="btn-dialog-lg" ><span class="opacitylow">Dialog |</span> lg</button>
				<button class="butAction" style="margin:4px;" id="btn-dialog-xl" ><span class="opacitylow">Dialog |</span> xl</button>
				<button class="butAction" style="margin:4px;" id="btn-dialog-xxl" ><span class="opacitylow">Dialog |</span> xxl</button>
			</div>

			<h5>Center size</h5>
			<div class="documentation-example">
				<button class="butAction" style="margin:4px;" id="btn-dialog-xs-center" ><span class="opacitylow">Dialog |</span> xs</button>
				<button class="butAction" style="margin:4px;" id="btn-dialog-default-size-center" ><span class="opacitylow">Dialog |</span> default</button>
				<button class="butAction" style="margin:4px;" id="btn-dialog-lg-center" ><span class="opacitylow">Dialog |</span> lg</button>
				<button class="butAction" style="margin:4px;" id="btn-dialog-xl-center" ><span class="opacitylow">Dialog |</span> xl</button>
				<button class="butAction" style="margin:4px;" id="btn-dialog-xxl-center" ><span class="opacitylow">Dialog |</span> xxl</button>
			</div>

			<?php
			$lines = array(
				'<script>',
				'// Responsive keywords: xs, lg, xl, xxl',
				'Dolibarr.tools.uiDialog("#btn-lg", {',
				'	dialogId: "dlg-lg",',
				'	align: "right",',
				'	width: "lg",',
				'	url: "/path/to/modal.php"',
				'});',
				'',
				'// Custom size: number (px) or any CSS unit. height is ignored when align is "right".',
				'Dolibarr.tools.uiDialog("#btn-center", {',
				'	dialogId: "dlg-size",',
				'	align: "center",',
				'	width: "50vw",   // or 600, "600px", "50%"',
				'	height: "50vh",',
				'	url: "/path/to/modal.php"',
				'});',
				'</script>',
			);
			$documentation->showCode($lines, 'php');
			?>

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
				<button class="butAction" style="margin:4px;" id="btn-dialog-params-nobackdrop" data-persist="0"><span class="opacitylow">Dialog |</span> No backdrop</button>
			</div>

			<?php
			$lines = array(
				'<script>',
				'Dolibarr.tools.uiDialog("#btn-open", {',
				'	dialogId: "dlg-others",',
				'	url: "/path/to/modal.php",',
				'	animation: false,  // disable open/close animation',
				'	persist: false,    // rebuild and reload the content on every open',
				'	isModal: false     // no backdrop (uses dialog.show() instead of showModal())',
				'});',
				'</script>',
			);
			$documentation->showCode($lines, 'php');
			?>
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
			header: { title: uiDialogTitleCenter },
			url: modalUrl
		});

		Dolibarr.tools.uiDialog('#btn-dialog-right',  {
			dialogId: 'dialog-right',
			align: 'right',
			header: { title: uiDialogTitleRight },
			url: modalUrl
		});

		// Single call arming several triggers (shared class selector)
		const uiDialogTitleMultiTrigger = 'Multiple triggers, one call';
		const multiTriggerUrl = '<?php echo dol_buildpath('/'.$documentation->baseUrl.'/experimental/experiments/dialog/modals/example-basic-multi.php', 1); ?>';
		Dolibarr.tools.uiDialog('.dialog-multi-trigger', {
			dialogId: 'dlg-basic-multi',
			align: 'center',
			header: { title: uiDialogTitleMultiTrigger, icon: 'fas fa-layer-group' },
			url: multiTriggerUrl,
			persist: false
		});

		Dolibarr.tools.uiDialog('#btn-dialog-xs',  {
			dialogId: 'test-dialog-xs',
			align: 'right',
			width: 'xs',
			header: { title: uiDialogTitleRight + ' xs size' },
			url: modalUrl
		});

		Dolibarr.tools.uiDialog('#btn-dialog-default-size',  {
			dialogId: 'test-dialog-default',
			align: 'right',
			header: { title: uiDialogTitleRight + ' default size' },
			url: modalUrl
		});

		Dolibarr.tools.uiDialog('#btn-dialog-lg',  {
			dialogId: 'test-dialog-lg',
			align: 'right',
			width: 'lg',
			header: { title: uiDialogTitleRight + ' lg size' },
			url: modalUrl
		});

		Dolibarr.tools.uiDialog('#btn-dialog-xl',  {
			dialogId: 'test-dialog-xl',
			align: 'right',
			width: 'xl',
			header: { title: uiDialogTitleRight + ' XL size' },
			url: modalUrl
		});

		Dolibarr.tools.uiDialog('#btn-dialog-xxl',  {
			dialogId: 'test-dialog-xxl',
			align: 'right',
			width: 'xxl',
			header: { title: uiDialogTitleRight + ' XXL size' },
			url: modalUrl
		});

		Dolibarr.tools.uiDialog('#btn-dialog-xs-center',  {
			dialogId: 'test-dialog-xs-center',
			align: 'center',
			width: 'xs',
			header: { title: uiDialogTitleRight + ' xs size' },
			url: modalUrl
		});

		Dolibarr.tools.uiDialog('#btn-dialog-default-size-center',  {
			dialogId: 'test-dialog-default-center',
			align: 'center',
			header: { title: uiDialogTitleRight + ' default size' },
			url: modalUrl
		});

		Dolibarr.tools.uiDialog('#btn-dialog-lg-center',  {
			dialogId: 'test-dialog-lg-center',
			align: 'center',
			width: 'lg',
			header: { title: uiDialogTitleRight + ' lg size' },
			url: modalUrl
		});

		Dolibarr.tools.uiDialog('#btn-dialog-xl-center',  {
			dialogId: 'test-dialog-xl-center-center',
			align: 'center',
			width: 'xl',
			header: { title: uiDialogTitleRight + ' XL size' },
			url: modalUrl
		});

		Dolibarr.tools.uiDialog('#btn-dialog-xxl-center',  {
			dialogId: 'test-dialog-xxl-center',
			align: 'center',
			width: 'xxl',
			header: { title: uiDialogTitleRight + ' XXL size' },
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
			align: 'center',
			header: { title: uiDialogTitleWithIcon, icon: 'fas fa-flask' },
			url: modalUrl
		});
		Dolibarr.tools.uiDialog('#btn-dialog-with-color-icon', {
			dialogId: 'dialog-with-color-icon',
			align: 'center',
			header: { title: uiDialogTitleWithIcon, icon: 'fas fa-landmark', iconColor: '#b0bb39' },
			url: modalUrl
		});

		// Footer
		const uiDialogTitleFooter = 'Dialog with footer';
		modalUrl = '<?php echo dol_buildpath('/'.$documentation->baseUrl.'/experimental/experiments/dialog/modals/example-simple.php', 1); ?>';
		Dolibarr.tools.uiDialog('#btn-dialog-footer-default', {
			dialogId: 'dialog-footer-default',
			align: 'center',
			header: { title: uiDialogTitleFooter, icon: 'fas fa-stream' },
			url: modalUrl,
			footer: {}
		});
		Dolibarr.tools.uiDialog('#btn-dialog-footer-left', {
			dialogId: 'dialog-footer-left',
			align: 'center',
			header: { title: uiDialogTitleFooter, icon: 'fas fa-align-left' },
			url: modalUrl,
			footer: { align: 'left' }
		});
		Dolibarr.tools.uiDialog('#btn-dialog-footer-center', {
			dialogId: 'dialog-footer-center',
			align: 'center',
			header: { title: uiDialogTitleFooter, icon: 'fas fa-align-center' },
			url: modalUrl,
			footer: { align: 'center' }
		});
		Dolibarr.tools.uiDialog('#btn-dialog-footer-borderless', {
			dialogId: 'dialog-footer-borderless',
			align: 'center',
			header: { title: uiDialogTitleFooter, icon: 'fas fa-minus' },
			url: modalUrl,
			footer: { borderTop: false }
		});
		Dolibarr.tools.uiDialog('#btn-dialog-footer-cancelonly', {
			dialogId: 'dialog-footer-cancelonly',
			align: 'center',
			header: { title: uiDialogTitleFooter, icon: 'fas fa-times' },
			url: modalUrl,
			footer: { showSubmit: false, cancelLabel: 'Close' }
		});

		// Data attributes
		const uiDialogTitleWithData = 'How to pass variables to a dialog?';
		modalUrl = '<?php echo dol_buildpath('/'.$documentation->baseUrl.'/experimental/experiments/dialog/modals/example-data.php', 1); ?>';
		Dolibarr.tools.uiDialog('#btn-dialog-with-data-a', {
			dialogId: 'dialog-with-data-a',
			align: 'center',
			header: { title: uiDialogTitleWithData, icon: 'fas fa-code' },
			url: modalUrl
		});
		Dolibarr.tools.uiDialog('#btn-dialog-with-data-b', {
			dialogId: 'dialog-with-data-b',
			align: 'center',
			header: { title: uiDialogTitleWithData, icon: 'fas fa-code' },
			url: modalUrl
		});

		// Forms
		const uiDialogTitleForm = 'Add a new ticket';
		modalUrl = '<?php echo dol_buildpath('/'.$documentation->baseUrl.'/experimental/experiments/dialog/modals/example-form.php', 1); ?>';
		Dolibarr.tools.uiDialog('#btn-dialog-with-form', {
			dialogId: 'dialog-with-form',
			align: 'right',
			header: { title: uiDialogTitleForm, icon: 'fas fa-ticket-alt', iconColor: '#3bbfa8' },
			url: modalUrl,
			footer: { submitFormId: 'dol-dialog-form-example' }
		});
		Dolibarr.tools.uiDialog('#btn-dialog-with-ajaxform', {
			dialogId: 'dialog-with-ajaxform',
			align: 'right',
			header: { title: uiDialogTitleForm, icon: 'fas fa-ticket-alt', iconColor: '#3bbfa8' },
			url: modalUrl,
			footer: { submitFormId: 'dol-dialog-ajaxform-example' },
			onSuccess: function(data) {
				Dolibarr.tools.setEventMessage(data.msg);
			}
		});

		// Control size
		const uiDialogTitleSizeControl = 'Very large dialog';
		modalUrl = '<?php echo dol_buildpath('/'.$documentation->baseUrl.'/experimental/experiments/dialog/modals/example-size.php', 1); ?>';
		Dolibarr.tools.uiDialog('#btn-dialog-params-size-center', {
			dialogId: 'dialog-size-c',
			align: 'center',
			header: { title: uiDialogTitleSizeControl, icon: 'fas fa-arrows-alt-h' },
			url: modalUrl,
			width: '50vw',
			height: '50vh'
		});
		Dolibarr.tools.uiDialog('#btn-dialog-params-size-right', {
			dialogId: 'dialog-size-r',
			align: 'right',
			header: { title: uiDialogTitleSizeControl, icon: 'fas fa-arrows-alt-h' },
			url: modalUrl,
			width: 800,
		});

		// No animation
		const uiDialogTitleNoAnimation = 'No animation on opening';
		modalUrl = '<?php echo dol_buildpath('/'.$documentation->baseUrl.'/experimental/experiments/dialog/modals/example-no-animation.php', 1); ?>';
		Dolibarr.tools.uiDialog('#btn-dialog-params-animation-center', {
			dialogId: 'dialog-noanim-c',
			align: 'center',
			header: { title: uiDialogTitleNoAnimation, icon: 'fas fa-eye-slash' },
			url: modalUrl,
			animation: false,
		});
		Dolibarr.tools.uiDialog('#btn-dialog-params-animation-right', {
			dialogId: 'dialog-noanim-r',
			align: 'right',
			header: { title: uiDialogTitleNoAnimation, icon: 'fas fa-eye-slash' },
			url: modalUrl,
			animation: false,
		});

		// Persist
		const uiDialogTitlePersistYes = 'Persistant Dialog';
		const uiDialogTitlePersistNo = 'Non-persistent dialog';
		modalUrl = '<?php echo dol_buildpath('/'.$documentation->baseUrl.'/experimental/experiments/dialog/modals/example-persist.php', 1); ?>';
		Dolibarr.tools.uiDialog('#btn-dialog-params-persist', {
			dialogId: 'dialog-persist',
			align: 'right',
			header: { title: uiDialogTitlePersistYes, icon: 'fas fa-bars' },
			url: modalUrl,
			animation: false,
			persist: true
		});
		Dolibarr.tools.uiDialog('#btn-dialog-params-notpersist', {
			dialogId: 'dialog-notpersist',
			align: 'right',
			header: { title: uiDialogTitlePersistNo, icon: 'fas fa-bars' },
			url: modalUrl,
			animation: false,
			persist: false
		});
		Dolibarr.tools.uiDialog('#btn-dialog-params-nobackdrop', {
			dialogId: 'dialog-nobackdrop',
			align: 'right',
			header: { title: uiDialogTitlePersistNo, icon: 'fas fa-bars' },
			url: modalUrl,
			animation: false,
			persist: false,
			isModal: false
		});
	});
</script>

<?php
// Output close body + html
$documentation->docFooter();
?>
