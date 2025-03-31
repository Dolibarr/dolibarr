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

$experimentAssetsPath = $documentation->baseUrl . '/experimental/experiments/freeze-tooltip/assets/';

$js = [
	$experimentAssetsPath . 'freeze-by-alt-keypress.js'
];
$css = [
	$experimentAssetsPath . 'freeze-by-alt-keypress.css'
];

// Output html head + body - Param is Title
$documentation->docHeader($langs->trans('ExperimentalUxFreezeTooltip', $group), $js, $css);

// Set view for menu and breadcrumb
$documentation->view = [$group, 'ExperimentalUxFreezeTooltip'];

// Output sidebar
$documentation->showSidebar(); ?>

<div class="doc-wrapper">

	<?php $documentation->showBreadCrumb(); ?>

	<div class="doc-content-wrapper">

		<h1 class="documentation-title"><?php echo $langs->trans('ExperimentalUxFreezeTooltip'); ?></h1>

		<?php $documentation->showSummary(); ?>

		<div class="documentation-section" >
			<h2 class="documentation-title" >Tooltip Freeze with <kbd>Alt</kbd> Key</h2>

			<p>
				A new feature allows users to <strong>freeze tooltips</strong> in Dolibarr by holding down the <kbd>Alt</kbd> key.
				This makes it easier to read long tooltips without having to keep the cursor perfectly still.
			</p>

			<h3>How It Works</h3>
			<ol>
				<li>Hover over an element that displays a tooltip.</li>
				<li>Press and hold the <kbd>Alt</kbd> key while the tooltip is visible.</li>
				<li>The tooltip remains displayed even if you move the cursor away.</li>
				<li>Release the <kbd>Alt</kbd> key to hide the tooltip.</li>
			</ol>

			<h3>Use Cases</h3>
			<ul>
				<li>Reading detailed information in tooltips without worrying about cursor movement.</li>
				<li>Copying text from tooltips without them disappearing.</li>
				<li>Click on links in tooltips.</li>
			</ul>

			<h3>Limitations</h3>
			<ul>
				<li>May not work on all tooltips, depending on their implementation. Only work on <code>.classfortooltip</code> class</li>
				<li>Currently available only on experimental pages.</li>
			</ul>

			<h3>Example</h3>
			<div class="documentation-example">
				<?php
				$tooltip = '<p>
    Welcome to <a href="https://dolibarr.org" title="Official Dolibarr Website">Dolibarr</a>,
    an open-source ERP & CRM solution. This platform helps businesses manage their
    <abbr title="Customer Relationship Management">CRM</abbr>
    and <abbr title="Enterprise Resource Planning">ERP</abbr> needs efficiently.
</p>

<p>
    For documentation, visit our
    <a href="https://wiki.dolibarr.org" title="Dolibarr Documentation">Wiki</a>.
    Developers can contribute on
    <a href="https://github.com/Dolibarr/dolibarr" title="Dolibarr GitHub Repository">GitHub</a>.
</p>
<p><strong class="classfortooltip" title="Tooltips in tooltips">try tooltip in a tooltip</strong></p>
<p>
    Need help? Check out the
    <a href="https://www.dolibarr.org/forum.php" title="Dolibarr Community Forum">Community Forum</a>.
</p>

<p>
    <strong>Try a link with attribute</strong> <code>target="_blank"</code> <br/>
    <a href="https://www.dolibarr.org/" target="_blank" >Open website in a new window</a>.
</p>

';
				?>

				<button type="button" class="button classfortooltip" title="<?php echo dol_htmlentities($tooltip); ?>">
					Example of tooltip
				</button>
			</div>
		</div>


	</div>

</div>
<?php
// Output close body + html
$documentation->docFooter();
?>
