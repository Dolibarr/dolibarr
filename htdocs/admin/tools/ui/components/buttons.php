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

// Load documentation translations
$langs->load('uxdocumentation');

//
$documentation = new Documentation($db);
$morejs = [
	'/includes/ace/src/ace.js',
	'/includes/ace/src/ext-statusbar.js',
	'/includes/ace/src/ext-language_tools.js',
];
// Output html head + body - Param is Title
$documentation->docHeader('Buttons', $morejs);

// Set view for menu and breadcrumb
// Menu must be set in constructor of documentation class
$documentation->view = array('Components','Buttons');

// Output sidebar
$documentation->showSidebar(); ?>

<div class="doc-wrapper">

	<?php $documentation->showBreadCrumb(); ?>

	<div class="doc-content-wrapper">

		<h1 class="documentation-title"><?php echo $langs->trans('DocButtonsTitle'); ?></h1>
		<p class="documentation-text"><?php echo $langs->trans('DocButtonsMainDescription'); ?></p>

		<!-- Summary -->
		<?php $documentation->showSummary(); ?>

		<!-- Example of simple usage -->
		<div class="documentation-section" id="buttonsection-basicusage">
			<h2 class="documentation-title"><?php echo $langs->trans('DocBasicUsage'); ?></h2>
			<p class="documentation-text"><?php echo $langs->trans('DocButtonBasicUsageDescription'); ?></p>
			<div class="documentation-example">
				<?php
				$label = 'My action label used for accessibility visually for impaired people';
				$user_right = 1;

				$html = '<span class="fa fa-clone paddingright" aria-hidden="true"></span> My default action';
				$action_type = 'default';
				$id = 'button-id-1';
				$url = '#'.$id;
				print dolGetButtonAction($label, $html, $action_type, $url, $id, $user_right);

				$html = '<span class="fa fa-trash-alt paddingright" aria-hidden="true"></span> My delete action';
				$action_type = 'delete';
				$id = 'button-id-2';
				$url = '#'.$id;
				print dolGetButtonAction($label, $html, $action_type, $url, $id, $user_right);

				$html = '<span class="fa fa-radiation paddingright" aria-hidden="true"></span> My danger action';
				$action_type = 'danger';
				$id = 'button-id-3';
				$url = '#'.$id;
				print dolGetButtonAction($label, $html, $action_type, $url, $id, $user_right); ?>

				<br><br>

				<?php
				$user_right = 0;

				$html = '<span class="fa fa-clone paddingright" aria-hidden="true"></span> My default action';
				$action_type = 'default';
				$id = 'button-id-4';
				$url = '#'.$id;
				print dolGetButtonAction($label, $html, $action_type, $url, $id, $user_right);

				$html = '<span class="fa fa-trash-alt paddingright" aria-hidden="true"></span> My delete action';
				$action_type = 'delete';
				$id = 'button-id-5';
				$url = '#'.$id;
				print dolGetButtonAction($label, $html, $action_type, $url, $id, $user_right);

				$html = '<span class="fa fa-radiation paddingright" aria-hidden="true"></span> My danger action';
				$action_type = 'danger';
				$id = 'button-id-6';
				$url = '#'.$id;
				print dolGetButtonAction($label, $html, $action_type, $url, $id, $user_right); ?>
			</div>
			<?php
			$lines = array(
				'<?php',
				'/**',
				' * Function dolGetButtonAction',
				' *',
				' * $label 		Label or tooltip of button if $text is provided. Also used as tooltip in title attribute. Can be escaped HTML content or full simple text.',
				' * $html		Optional : short label on button. Can be escaped HTML content or full simple text.',
				' * $actionType	default, danger, email, clone, cancel, delete, ...',
				' * $url 		Url for link or array of subbutton description',
				' * $id 		Attribute id of action button. Example \'action-delete\'. This can be used for full ajax confirm if this code is reused into the ->formconfirm() method.',
				' * $userRight 	User action right / 0 = No, 1 = Yes',
				' * $params 	Various params',
				' * ',
				' * See more in core/lib/functions.lib.php',
				' */',
				'print dolGetButtonAction($label, $html, $actionType, $url, $id, $userRight, $params);',
			);
			echo $documentation->showCode($lines, 'php'); ?>
		</div>

		<!-- Example of modal usage -->
		<div class="documentation-section" id="buttonsection-modals">
			<h2 class="documentation-title"><?php echo $langs->trans('DocButtonModal'); ?></h2>
			<p class="documentation-text"><?php echo $langs->trans('DocButtonModalDescription'); ?></p>
			<div class="documentation-example">
				<?php
				$userRight = 1;

				$html = '<span class="fa fa-clone" paddingright" aria-hidden="true"></span> My default action';
				$actionType = 'default';
				$id = 'button-id-7';
				$url = '#'.$id;
				$params = array(
					'confirm' => [],
				);
				print dolGetButtonAction($label, $html, $actionType, $url, $id, $userRight, $params);

				$html = '<span class="fa fa-trash-alt paddingright" aria-hidden="true"></span> My delete action';
				$actionType = 'delete';
				$id = 'button-id-8';
				$url = $_SERVER['PHP_SELF'] . '?token='.newToken().'#'.$id;
				$params = array(
					'confirm' => array(
						'url' => 'your confirm action url',
						'title' => 'Your title to display',
						'action-btn-label' => 'Your confirm label',
						'cancel-btn-label' => 'Your cancel label',
						'content' => 'Content to display  with <strong>HTML</strong> compatible <ul><li>test 01</li><li>test 02</li><li>test 03</li></ul>'
					)
				);
				print dolGetButtonAction($label, $html, $actionType, $url, $id, $userRight, $params);

				$userRight = 0;

				$html = '<span class="fa fa-clone" ></span> My default action';
				$actionType = 'delete';
				$id = 'button-id-9';
				$url = '#'.$id;
				$params = array(
					'confirm' => [],
				);
				print dolGetButtonAction($label, $html, $actionType, $url, $id, $userRight, $params); ?>
			</div>
			<?php
			$lines = array(
				'<?php',
				'// Default parameters',
				'$params = array(',
				'	\'confirm\' => [],',
				');',
				'',
				'// Custom parameters',
				'$params = array(',
				'	\'confirm\' => array(',
				'		\'url\' => \'your confirm action url\',',
				'		\'title\' => \'Your title to display\',',
				'		\'action-btn-label\' => \'Your confirm label\',',
				'		\'cancel-btn-label\' => \'Your cancel label\',',
				'		\'content\' => \'Content to display  with <strong>HTML</strong> compatible <ul><li>test 01</li><li>test 02</li><li>test 03</li></ul>\',',
				'	)',
				');',
				'',
				'print dolGetButtonAction($label, $html, $actionType, $url, $id, $userRight, $params);',
			);
			echo $documentation->showCode($lines, 'php'); ?>
		</div>

		<!-- Example of subbutton usage -->
		<div class="documentation-section" id="buttonsection-submenu">
			<h2 class="documentation-title"><?php echo $langs->trans('DocButtonSubmenu'); ?></h2>
			<p class="documentation-text"><?php echo $langs->trans('DocButtonSubmenuDescription'); ?></p>
			<div class="documentation-example">
				<?php
				$userRight = 1;
				$html = '<span class="fa fa-clone" paddingright" aria-hidden="true"></span> My default action';
				$actionType = 'default';
				$id = 'button-id-7';
				$submenu_url = str_replace(DOL_URL_ROOT, '', $_SERVER['PHP_SELF']);
				$url = array(
					array(
						'lang'=>'documentation@documentation',
						'url'=> $submenu_url.'#'.$id,
						'label' => 'My SubAction 1',
						'perm' => true,
						'enabled' => true,
					),
					array(
						'lang'=>'documentation@documentation',
						'url'=> $submenu_url.'#'.$id,
						'label' => 'My SubAction 2',
						'perm' => false,
						'enabled' => true,
					),
				);
				$params = array();
				print dolGetButtonAction($label, $html, $actionType, $url, $id, $userRight, $params); ?>
			</div>
			<?php
			$lines = array(
				'<?php',
				'// Simple button',
				'$url = \'url_script\';',
				'print dolGetButtonAction($label, $html, $actionType, $url, $id, $userRight, $params);',
				'',
				'// Dropdown button',
				'$url = array(',
				'	array(',
				'		\'lang\' => \'langfile\',',
				'		\'url\' => \'url_script_1\', // Url without DOL_URL_ROOT',
				'		\'label\' => \'My SubAction 1\',',
				'		\'perm\' => 1, // The user have the rights',
				'	),',
				'	array(',
				'		\'lang\' => \'langfile\',',
				'		\'url\' => \'url_script_2\', // Url without DOL_URL_ROOT',
				'		\'label\' => \'My SubAction 2\',',
				'		\'perm\' => 0, // The user does not have the rights',
				'	),',
				');',
				'print dolGetButtonAction($label, $html, $actionType, $url, $id, $userRight, $params);'
			);
			echo $documentation->showCode($lines, 'php'); ?>
		</div>


		<!-- Example of subbutton usage -->
		<div class="documentation-section" id="buttonsection-icon-btn">
			<h2 class="documentation-title"><?php echo $langs->trans('DocButtonIconsLowEmphasis'); ?></h2>
			<p class="documentation-text"><?php echo $langs->trans('DocButtonIconsDescriptionLowEmphasis'); ?></p>
			<div class="documentation-example">
				<?php
					$btnLabel = $langs->trans('Label');
					print ' <button class="btn-low-emphasis --btn-icon" title="'.dol_escape_htmltag($btnLabel).'" aria-label="'.dol_escape_htmltag($btnLabel).'" >'.img_picto($btnLabel, 'fa-arrow-right', 'aria-hidden="true"', 0, 0, 1).'</button>';

					$btnLabel = $langs->trans('Reset');
					print ' <button class="btn-low-emphasis --btn-icon"  title="'.dol_escape_htmltag($btnLabel).'" aria-label="'.dol_escape_htmltag($btnLabel).'" >'.img_picto($btnLabel, 'eraser', 'aria-hidden="true"', 0, 0, 1).'</button>';
				?>
			</div>
			<?php
			$lines = array(
				'<?php',
				'$btnLabel = $langs->trans(\'Label\');',
				'print \' <button class="btn-low-emphasis --btn-icon" title="\'.dol_escape_htmltag($btnLabel).\'" aria-label="\'.dol_escape_htmltag($btnLabel).\'" >\'.img_picto($btnLabel, \'fa-arrow-right\', \'aria-hidden="true"\', 0, 0, 1).\'</button>\';',
				'',
				'$btnLabel = $langs->trans(\'Reset\');',
				'print \' <button class="btn-low-emphasis --btn-icon" title="\'.dol_escape_htmltag($btnLabel).\'" aria-label="\'.dol_escape_htmltag($btnLabel).\'" >\'.img_picto($btnLabel, \'eraser\', \'aria-hidden="true"\', 0, 0, 1).\'</button>\';',

			);
			echo $documentation->showCode($lines, 'php'); ?>
		</div>

		<!-- Example of subbutton usage -->
		<div class="documentation-section" id="buttonsection-icon-btn">
			<h2 class="documentation-title"><?php echo $langs->trans('DocButtonIconsForTitle'); ?></h2>
			<div class="documentation-example">
				<?php


				$btnLabel = $langs->trans('Label');
				print dolGetButtonTitle($btnLabel, '', 'fa fa-file', '#', '', 0); // Not Enough Permissions
				print dolGetButtonTitle($btnLabel, '', 'fa fa-file', '#', '', 1); // Active
				print dolGetButtonTitle($btnLabel, '', 'fa fa-file', '#', '', 2); // Active and selected
				print dolGetButtonTitle($btnLabel, '', 'fa fa-file', '#', '', -1); // Functionality is disabled
				print dolGetButtonTitle($btnLabel, '', 'fa fa-file', '#', '', -2); // Disabled without info


				?>
			</div>
			<?php
			$lines = array(
				'<?php',
				'$btnLabel = $langs->trans(\'Label\');',
				'$status = 0; // Not Enough Permissions',
				'print dolGetButtonTitle($btnLabel, \'\', \'fa fa-file\', \'#\', \'\', $status);',
				'$status = 1; // Active',
				'print dolGetButtonTitle($btnLabel, \'\', \'fa fa-file\', \'#\', \'\', $status);',
				'$status = 2; // Active and selected',
				'print dolGetButtonTitle($btnLabel, \'\', \'fa fa-file\', \'#\', \'\', $status);',
				'$status = -1; // Functionality is disabled',
				'print dolGetButtonTitle($btnLabel, \'\', \'fa fa-file\', \'#\', \'\', $status);',
				'$status = -2; // Disabled without info',
				'print dolGetButtonTitle($btnLabel, \'\', \'fa fa-file\', \'#\', \'\', $status);',
			);

			echo $documentation->showCode($lines, 'php'); ?><div class="documentation-example">
				<?php

				$btnLabel = $langs->trans('Label', 'php');
				print dolGetButtonTitle($btnLabel, '', 'fa fa-download', '#', '', 0, ['forcenohideoftext'=>1]); // Not Enough Permissions
				print dolGetButtonTitle($btnLabel, '', 'fa fa-download', '#', '', 1, ['forcenohideoftext'=>1]); // Active
				print dolGetButtonTitle($btnLabel, '', 'fa fa-download', '#', '', 2, ['forcenohideoftext'=>1]); // Active and selected
				print dolGetButtonTitle($btnLabel, '', 'fa fa-download', '#', '', -1, ['forcenohideoftext'=>1]); // Functionality is disabled
				print dolGetButtonTitle($btnLabel, '', 'fa fa-download', '#', '', -2, ['forcenohideoftext'=>1]); // Disabled without info

				?>
			</div>

			<?php
			$lines = array(
				'<?php',
				'$btnLabel = $langs->trans(\'Label\');',
				'$status = 0; // Not Enough Permissions',
				'print dolGetButtonTitle($btnLabel, \'\', \'fa fa-download\', \'#\', \'\', $status, [\'forcenohideoftext\'=>1]);',
				'$status = 1; // Active',
				'print dolGetButtonTitle($btnLabel, \'\', \'fa fa-download\', \'#\', \'\', $status, [\'forcenohideoftext\'=>1]);',
				'$status = 2; // Active and selected',
				'print dolGetButtonTitle($btnLabel, \'\', \'fa fa-download\', \'#\', \'\', $status, [\'forcenohideoftext\'=>1]);',
				'$status = -1; // Functionality is disabled',
				'print dolGetButtonTitle($btnLabel, \'\', \'fa fa-download\', \'#\', \'\', $status, [\'forcenohideoftext\'=>1]);',
				'$status = -2; // Disabled without info',
				'print dolGetButtonTitle($btnLabel, \'\', \'fa fa-download\', \'#\', \'\', $status, [\'forcenohideoftext\'=>1]);',
			);
			echo $documentation->showCode($lines, 'php'); ?>
		</div>

	</div>

</div>
<?php
// Output close body + html
$documentation->docFooter();
?>
