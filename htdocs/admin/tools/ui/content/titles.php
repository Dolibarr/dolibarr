<?php
/*
 * Copyright (C) 2024-2025	Anthony Damhet <a.damhet@progiseize.fr>
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
require '../../../../main.inc.php';

/**
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Protection if external user
if ($user->socid > 0) {
	accessforbidden();
}

// Includes
require_once DOL_DOCUMENT_ROOT . '/admin/tools/ui/class/documentation.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';

// Load documentation translations
$langs->load('uxdocumentation');

// Hooks
$hookmanager->initHooks(array('uidocumentation'));

//
$documentation = new Documentation($db);
$form = new Form($db);

$morejs = [
	'/includes/ace/src/ace.js',
	'/includes/ace/src/ext-statusbar.js',
	'/includes/ace/src/ext-language_tools.js',
];
// Output html head + body - Param is Title
$documentation->docHeader('Titles', $morejs);

// Set view for menu and breadcrumb
// Menu must be set in constructor of documentation class
$documentation->view = array('Content','Titles');

// Output sidebar
$documentation->showSidebar(); ?>

<div class="doc-wrapper">

		<?php $documentation->showBreadCrumb(); ?>

		<div class="doc-content-wrapper">

			<h1 class="documentation-title"><?php echo $langs->trans('DocTitleTitle'); ?></h1>
			<p class="documentation-text"><?php echo $langs->trans('DocTitleMainDescription'); ?></p>

			<!-- Summary -->
			<?php $documentation->showSummary(); ?>

			<!-- Basic usage -->
			<div class="documentation-section" id="titlesection-basicusage">

				<h2 class="documentation-title"><?php echo $langs->trans('DocBasicUsage'); ?></h2>

				<p class="documentation-text"><?php echo $langs->transnoentities('DocTitleBasicDescription'); ?></p>
				<ul>
					<li><?php echo $langs->trans('DocTitleIconUsageMethod1', dol_buildpath('/admin/tools/ui/components/icons.php#img-picto-section-list', 1)); ?></li>
					<li><?php echo $langs->trans('DocTitleIconUsageMethod2', dol_buildpath('/admin/tools/ui/components/icons.php#icon-section-list', 1)); ?></li>
					<li><?php echo $langs->trans('DocTitleIconUsageMethod3'); ?></li>
				</ul>
				<div class="documentation-example">
					<?php print load_fiche_titre($langs->trans('DocMyPageTitle'), '', 'info'); ?>
				</div>
				<?php
				$lines = array(
					'<?php',
					'',
					'// Title with icon, see list of icons in components/icons section',
					'print load_fiche_titre("'.$langs->trans('DocMyPageTitle').'", "", "info");',
					'',
					'// Title with fontawesome icon, see list of icons in components/icons section',
					'// use like this: ICONCLASS_FAMILYCLASS_COLOR',
					'print load_fiche_titre("'.$langs->trans('DocMyPageTitle').'", "", "fa-rocket_fas_#b0bb39");',
					'',
					'// Title with custom image icon, 4th parameter must be 1',
					'print load_fiche_titre("'.$langs->trans('DocMyPageTitle').'", "", "IMAGE_URL", 1);',
				);
				echo $documentation->showCode($lines, 'php'); ?>

				<p class="documentation-text"><?php echo $langs->trans('DocTitleMoreContentDescription'); ?></p>
				<div class="documentation-example">
					<?php print load_fiche_titre($langs->trans('DocMyPageTitle'), '<a href="#">'.img_picto('', 'add', '', 0, 0, 0, '', 'valignmiddle btnTitle-icon').'</a>', 'fa-rocket_fas_#b0bb39'); ?>
				</div>
				<?php
				$lines = array(
					'<?php',
					'',
					'// Title with picto link',
					'$moreHtmlRight = \'<a href="#">\'.img_picto("", "add").\'</a>\';',
					'print load_fiche_titre("'.$langs->trans('DocMyPageTitle').'", $moreHtmlRight, "fa-rocket_fas_#b0bb39");',
					'',
					'// Title with badge',
					'$moreHtmlRight = \'<span class="badge badge-pill badge-secondary">Secondary</span>\';',
					'print load_fiche_titre("'.$langs->trans('DocMyPageTitle').'", $moreHtmlRight, "fa-rocket_fas_#b0bb39");',

					'',
					'// Title with form',
					'$moreHtmlRight = \'<form>...</form>\';',
					'print load_fiche_titre("'.$langs->trans('DocMyPageTitle').'", $moreHtmlRight, "fa-rocket_fas_#b0bb39");',
					'',
					'// Set ID for table and css class',
					'$tableID = \'tableid\';',
					'$moreclass = \'class1 class2\';',
					'print load_fiche_titre("'.$langs->trans('DocMyPageTitle').'", $moreHtmlRight, "fa-rocket_fas_#b0bb39", 0, $tableID, $moreclass);',
					'',
					'// Add html between title and right content',
					'$moreContent = \'MyHTMLContent\';',
					'print load_fiche_titre("'.$langs->trans('DocMyPageTitle').'", $moreHtmlRight, "fa-rocket_fas_#b0bb39", 0, $tableID, $moreclass, $moreContent);',
				);
				echo $documentation->showCode($lines, 'php'); ?>
			</div>

			<!-- Table with filters -->
			<div class="documentation-section" id="titlesection-withfilters">

				<h2 class="documentation-title"><?php echo $langs->trans('DocTitleWithFilters'); ?></h2>

				<p class="documentation-text"><?php echo $langs->trans('DocTitleWithFiltersDescription', dol_buildpath('admin/tools/ui/components/inputs.php', 1)); ?></p>
				<div class="documentation-example">
					<?php
					$title = $langs->trans('DocMyPageTitle');
					$page = 1;
					$file = '#';
					$options = '';
					$sortfield = '';
					$sortorder = '';
					$morehtmlcenter = '';
					$num = 21;
					$totalnboflines = 56;
					$picto = 'facture';
					$pictoisfullpath = 0;
					$morehtmlright = dolGetButtonTitle($langs->trans('ViewList'), '', 'fas fa-bars', '#', '', 1);
					$morehtmlright .= dolGetButtonTitle($langs->trans('ViewKanban'), '', 'fas fa-grip-horizontal', '#', '', 1);
					$morehtmlright .= '<span class="button-title-separator"></span>';
					$morehtmlright .= '<a href="#">'.img_picto('', 'add', '', 0, 0, 0, '', 'valignmiddle btnTitle-icon').'</a>';
					$morecss = '';
					$limit = 20;
					$selectlimitsuffix = 0;
					$hidenavigation = 0;
					$pagenavastextinput = 1;
					$morehtmlrightbeforearrow = '';
					print_barre_liste($title, $page, $file, $options, $sortfield, $sortorder, $morehtmlcenter, $num, $totalnboflines, $picto, $pictoisfullpath, $morehtmlright, $morecss, $limit, $selectlimitsuffix, $hidenavigation, $pagenavastextinput, $morehtmlrightbeforearrow); ?>
				</div>
				<?php
				$lines = array(
					'<?php',
					'',
					'/**',
					' *	Print a title with navigation controls for pagination',
					' *',
					' *	@param	string	    $title				Title to show (required). Can be a string with a <br> as a substring.',
					' *	@param	int|null    $page				Numero of page to show in navigation links (required)',
					' *	@param	string	    $file				Url of page (required)',
					' *	@param	string	    $options         	More parameters for links (\'\' by default, does not include sortfield neither sortorder). Value must be \'urlencoded\' before calling function.',
					' *	@param	?string    	$sortfield       	Field to sort on (\'\' by default)',
					' *	@param	?string	    $sortorder       	Order to sort (\'\' by default)',
					' *	@param	string	    $morehtmlcenter     String in the middle (\'\' by default). We often find here string $massaction coming from $form->selectMassAction()',
					' *	@param	int		    $num				Number of records found by select with limit+1',
					' *	@param	int|string  $totalnboflines		Total number of records/lines for all pages (if known). Use a negative value of number to not show number. Use \'\' if unknown.',
					' *	@param	string	    $picto				Icon to use before title (should be a 32x32 transparent png file)',
					' *	@param	int		    $pictoisfullpath	1=Icon name is a full absolute url of image',
					' *	@param	string	    $morehtmlright		More html to show (after arrows)',
					' *	@param  string      $morecss            More css to the table',
					' *	@param  int         $limit              Max number of lines (-1 = use default, 0 = no limit, > 0 = limit).',
					' *	@param  int|string  $selectlimitsuffix    Suffix for limit ID of -1 to hide the select limit combo',
					' *	@param  int         $hidenavigation     Force to hide the arrows and page for navigation',
					' *	@param  int			$pagenavastextinput 1=Do not suggest list of pages to navigate but suggest the page number into an input field.',
					' *	@param	string		$morehtmlrightbeforearrow	More html to show (before arrows)',
					' *	@return	void',
					' */',
					'print_barre_liste($title, $page, $file, $options, $sortfield, $sortorder, $morehtmlcenter, $num, $totalnboflines, $picto, $pictoisfullpath, $morehtmlright, $morecss, $limit, $selectlimitsuffix, $hidenavigation, $pagenavastextinput, $morehtmlrightbeforearrow);',
				);
				echo $documentation->showCode($lines, 'php'); ?>
			</div>

		</div>

	</div>

<?php
// Output close body + html
$documentation->docFooter();
?>
