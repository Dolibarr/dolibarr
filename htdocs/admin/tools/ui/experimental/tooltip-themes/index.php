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
require '../../../../../main.inc.php';

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

$morejs = [
	'/includes/ace/src/ace.js',
	'/includes/ace/src/ext-statusbar.js',
	'/includes/ace/src/ext-language_tools.js',
];
// Output html head + body - Param is Title
$documentation->docHeader($langs->transnoentitiesnoconv('UxMenuTooltipTheme', $group), $morejs);

// Set view for menu and breadcrumb
$documentation->view = [$group, 'UxMenuTooltipTheme'];

// Output sidebar
$documentation->showSidebar(); ?>

<div class="doc-wrapper">

	<?php $documentation->showBreadCrumb(); ?>

	<div class="doc-content-wrapper">

		<h1 class="documentation-title"><?php echo $langs->trans('UxMenuTooltipTheme'); ?></h1>
		<p class="documentation-text">This page list all tooltips available in Dolibarr.</p>

		<?php $documentation->showSummary(); ?>

		<div class="documentation-section" id="ux-introduction" >
			<h2 class="documentation-title">Introduction</h2>
			<p class="documentation-text">
				Dolibarr uses tooltips throughout the application.<br/>
				By default, there are two types of tooltips: standard tooltips and AJAX tooltips.<br/>
				The first type is loaded with the page, while the second one is loaded on demand (typically when the mouse hovers over an element) in order to avoid overloading the initial page load.
				Ajax tooltips are used for object getNomUrl() function.
			</p>

			<p class="documentation-text">
				Dolibarr use jquery tooltip
			</p>

			<h3>Default tooltips</h3>
			<?php
			$lines = array(
				'<span class="classfortooltip" title="This is a default tooltip">',
				'	Example of a tooltip : A tooltip',
				'</span>',
			);
			$documentation->showCode($lines, 'php'); ?>
			<div class="documentation-example">

				<p>
					<?php print implode('', $lines) ?>
				</p>

				<p>
					To create a tooltip, add the class <code>.classfortooltip</code> and a <code>title</code> attribute.
				</p>

			</div>

			<h3>AJAX tooltips</h3>
			<?php

			$params = [
				'id' => $user->id,
				'objecttype' => $user->element,
				'infologin' => 0,
				'option' => '',
				'hidethirdpartylogo' => 1,
			];

			$dataparams = ' data-params="'.dol_escape_htmltag(json_encode($params)).'"';

			$lines = array(
				'<?php',
				'$params = [',
				'	\'id\' => $object->id,',
				'	\'objecttype\' => $object->element',
				'];',
				'',
				'$dataparams = \' data-params="\'.dol_escape_htmltag(json_encode($params)).\'"\';',
				'',
				'?>',
				'<span class="classforajaxtooltip" title="Loading" \'.$dataparams.\'>',
				'	Example of a tooltip : A tooltip',
				'</span>',
			);
			$documentation->showCode($lines, 'php'); ?>
			<div class="documentation-example">
				<p>
				Example of AJAX tooltip with getNomUrl():  <?php print $user->getNomUrl() ?>
				</p>

			</div>
		</div>


		<div class="documentation-section" id="tooltip-themes" >

			<h2 class="documentation-title"><?php print $langs->trans('TooltipThemesAndOrientation'); ?></h2>

			<p class="documentation-example">
				You can create your own tooltips using the jQuery tooltip system by defining a custom tooltip class instead of the default <code>.mytooltip</code> used by Dolibarr.
			</p>

			<h3>jQuery-based tooltips</h3>

			<p>Advantages:</p>
			<ul>
				<li>Tooltips are displayed above all other DOM elements</li>
				<li>The position automatically adapts to screen boundaries</li>
				<li>Supports HTML content inside the tooltip</li>
			</ul>

			<p>Drawbacks:</p>
			<ul>
				<li>Not AJAX-friendly by default (see the "initNewContent" event system in the Dolibarr JS context)</li>
			</ul>

			<?php
			$lines = array(
				'<script nonce="<?php print getNonce(); ?>">',
				'jQuery(function() {',
				'',
				'	const quickTooltip = function (target, className) {',
				'		$(target).tooltip({',
				'			tooltipClass: className,',
				'			show: { collision: "flipfit", effect: "toggle", delay: 50, duration: 20 },',
				'			hide: { delay: 250, duration: 20 },',
				'			content: function () {',
				'				return $(this).prop("title");',
				'			}',
				'		});',
				'	}',
				'',
				'	quickTooltip(\'.test-tooltip-jquery-dark\', "jquery-tooltip --dark-mode");',
				'	quickTooltip(\'.test-tooltip-jquery-light\', "jquery-tooltip --light-mode");',
				'});',
				'</script>',
				'',
				'<button class="button classfortooltip" title="This is an example of default jquery based tooltip theme, used for getNomUrl or long text.<br/>Compatible with alt key to freeze tooltip" >Try default theme</button>',
				'<button class="button test-tooltip-jquery-dark" title="This is an example of dark jquery based tooltip theme" >Try dark theme</button>',
				'<button class="button test-tooltip-jquery-light" title="This is an example of light jquery based tooltip theme" >Try light theme</button>',
				'<button class="button test-tooltip-jquery-light" title="" >Try light long text</button>',

			);
			$documentation->showCode($lines, 'php'); ?>


			<script nonce="<?php print getNonce(); ?>">
				jQuery(function() {

					const quickTooltip = function (target, className) {
						$(target).tooltip({
							tooltipClass: className,
							show: { collision: "flipfit", effect: "toggle", delay: 50, duration: 20 },
							hide: { delay: 250, duration: 20 },
							content: function () {
								return $(this).prop("title");
							}
						});
					}

					quickTooltip('.test-tooltip-jquery-dark', "jquery-tooltip --dark-mode");
					quickTooltip('.test-tooltip-jquery-light', "jquery-tooltip --light-mode");
				});
			</script>

			<div class="documentation-example">
				<button class="button classfortooltip" title="This is an example of default theme" >Try default theme</button>
				<button class="button test-tooltip-jquery-dark" title="This is an example of dark jquery based tooltip theme" >Try dark theme</button>
				<button class="button test-tooltip-jquery-light" title="This is an example of light jquery based tooltip theme" >Try light theme</button>
				<hr/>
				<button class="button classfortooltip" title="<?php print dol_htmlentities(Documentation::generateLoremIpsum()); ?>" >Try default theme with long text</button>
				<button class="button test-tooltip-jquery-light" title="<?php print dol_htmlentities(Documentation::generateLoremIpsum()); ?>" >Try light theme with long text</button>
			</div>

			<h3>CSS-only tooltips</h3>

			<p>Advantages:</p>
			<ul>
				<li>AJAX-friendly and compatible with DOM manipulations</li>
				<li>Ideal for short contextual help or replacing the default <code>title</code> attribute with a smooth, dynamic tooltip pop-up</li>
			</ul>

			<p>Drawbacks:</p>
			<ul>
				<li>Tooltip position does not automatically adapt to screen boundaries; you need to use modifier classes such as <code>--tooltip-top</code>, <code>--tooltip-bottom</code>, <code>--tooltip-left</code>, or <code>--tooltip-right</code> to adjust placement</li>
				<li>Does not support HTML content inside the tooltip</li>
				<li>Tooltip stacking (<code>z-index</code>) depends on the element itself</li>
				<li>In some cases tooltip style can by override by parent css rules</li>
			</ul>

			<?php
			$lines = array(
				'<button class="button dol-tooltip --dark-mode" data-title="This is an example of dark css based tooltip theme" >Try dark theme</button>',
				'<button class="button dol-tooltip" data-title="This is an example of light css based tooltip theme" >Try light theme</button>',
				'',
				'<h4>Orientation dark theme</h4>',
				'',
				'<button class="button dol-tooltip --dark-mode --tooltip-bottom" data-title="This is an example of dark css based tooltip theme" >Bottom</button>',
				'<button class="button dol-tooltip --dark-mode --tooltip-left" data-title="This is an example of dark css based tooltip theme" >Left</button>',
				'<button class="button dol-tooltip --dark-mode --tooltip-right" data-title="This is an example of dark css based tooltip theme" >Right</button>',
				'<button class="button dol-tooltip --dark-mode --tooltip-top" data-title="This is an example of dark css based tooltip theme" >Top</button>',
				'',
				'<h4>Orientation light theme</h4>',
				'',
				'<button class="button dol-tooltip --light-mode --tooltip-bottom" data-title="This is an example of light css based tooltip theme" >Bottom</button>',
				'<button class="button dol-tooltip --light-mode --tooltip-left" data-title="This is an example of light css based tooltip theme" >Left</button>',
				'<button class="button dol-tooltip --light-mode --tooltip-right" data-title="This is an example of light css based tooltip theme" >Right</button>',
				'<button class="button dol-tooltip --light-mode --tooltip-top" data-title="This is an example of light css based tooltip theme" >Top</button>',
			);
			$documentation->showCode($lines, 'php'); ?>
			<div class="documentation-example">
				<?php print implode(' ', $lines) ?>
			</div>
		</div>



	</div>

</div>
<?php
// Output close body + html
$documentation->docFooter();
?>
