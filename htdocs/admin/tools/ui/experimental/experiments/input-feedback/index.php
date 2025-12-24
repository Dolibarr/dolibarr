<?php
/*
 * Copyright (C) 2024 Anthony Damhet <a.damhet@progiseize.fr>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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
$experimentName = 'ExperimentalUxInputAjaxFeedback';

$experimentAssetsPath = $documentation->baseUrl . '/experimental/experiments/input-feedback/assets/';
$js = [
	'/includes/ace/src/ace.js',
	'/includes/ace/src/ext-statusbar.js',
	'/includes/ace/src/ext-language_tools.js',
];
$css = [
	$experimentAssetsPath . 'feddback-01.css'
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

		<h1 class="documentation-title"><?php echo $langs->trans($experimentName); ?></h1>

		<?php $documentation->showSummary(); ?>

		<div class="documentation-section" >
			<h2 class="documentation-title" >Input Feedback (Experimental)</h2>

			<p>
				This experimental feature provides visual feedback on input fields based on their processing state.
				Currently, it is only available in this documentation and may be integrated into the <code>develop</code> branch of Dolibarr in the future.
			</p>

			<h3>How It Works</h3>
			<ul>
				<li><strong>Processing:</strong> The <code>.processing-feedback</code> class is applied while an action is being processed.</li>
				<li><strong>Success:</strong> Once successfully completed, the <code>.success-feedback</code> class is applied.</li>
				<li><strong>Failure:</strong> In case of an error, the <code>.fail-feedback</code> class is applied.</li>
			</ul>

			<h3>Live Demo</h3>

			<div class="documentation-example">
				<input type="text" id="test-input-01" placeholder="Type something..." />
				<button id="btn-process-fail" type="button" class="button" >Processing will fail</button>
				<button id="btn-process-success" type="button" class="button" >Processing will success</button>
				<script>
					document.getElementById('btn-process-success').addEventListener('click', function () {
						let input = document.getElementById('test-input-01');
						input.classList.add('processing-feedback');

						setTimeout(() => {
							input.classList.remove('processing-feedback');
							input.classList.add('success-feedback');
							setTimeout(() => {
								input.classList.remove('success-feedback');
							}, 1000);
						}, 1500);
					});


					document.getElementById('btn-process-fail').addEventListener('click', function () {
						let input = document.getElementById('test-input-01');
						input.classList.add('processing-feedback');

						setTimeout(() => {
							input.classList.remove('processing-feedback');
							input.classList.add('fail-feedback');
							setTimeout(() => {
								input.classList.remove('fail-feedback');
							}, 1000);
						}, 1500);
					});
				</script>
			</div>
			<?php
			$lines = array(
			'<script>',
			'document.getElementById(\'btn-process-success\').addEventListener(\'click\', function () {
	let input = document.getElementById(\'test-input-01\');
	input.classList.add(\'processing-feedback\');

	setTimeout(() => {
		input.classList.remove(\'processing-feedback\');
		input.classList.add(\'success-feedback\');
		setTimeout(() => {
			input.classList.remove(\'success-feedback\');
		}, 1000);
	}, 1500);
});


document.getElementById(\'btn-process-fail\').addEventListener(\'click\', function () {
	let input = document.getElementById(\'test-input-01\');
	input.classList.add(\'processing-feedback\');

	setTimeout(() => {
		input.classList.remove(\'processing-feedback\');
		input.classList.add(\'fail-feedback\');
		setTimeout(() => {
			input.classList.remove(\'fail-feedback\');
		}, 1000);
	}, 1500);
});',
			'</script>',
			);
			echo $documentation->showCode($lines, 'html'); ?>
		</div>


	</div>

</div>
<?php
// Output close body + html
$documentation->docFooter();
?>
