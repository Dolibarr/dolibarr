<?php
/*
 * Copyright (C) 2024 Anthony Damhet <a.damhet@progiseize.fr>
 *
 * This program and files/directory inner it is free software: you can
 * redistribute it and/or modify it under the terms of the
 * GNU Affero General Public License (AGPL) as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AGPL for more details.
 *
 * You should have received a copy of the GNU AGPL
 * along with this program. If not, see <https://www.gnu.org/licenses/agpl-3.0.html>.
 */

$res=0;
if (! $res && file_exists("../main.inc.php")) : $res=@include '../main.inc.php';
endif;
if (! $res && file_exists("../../main.inc.php")) : $res=@include '../../main.inc.php';
endif;

// Protection if external user
if ($user->socid > 0) : accessforbidden();
endif;

// Includes
dol_include_once('documentation/class/documentation.class.php');

// Load documentation translations
$langs->load('documentation@documentation');

//
$documentation = new Documentation($db);

// Output html head + body - Param is Title
$documentation->docHeader('Buttons');

// Set view for menu and breadcrumb
// Menu must be set in constructor of documentation class
$documentation->view = array('Elements','Buttons');

// Output sidebar
$documentation->showSidebar(); ?>

<div class="doc-wrapper">
		
	<?php $documentation->showBreadCrumb(); ?>

	<div class="doc-content-wrapper">

		<h1 class="documentation-title"><?php echo $langs->trans('DocButtonsTitle'); ?></h1>
		<p class="documentation-text"><?php echo $langs->trans('DocButtonsMainDescription'); ?></p>

		<!-- Summary -->
		<?php
		$summary = array(
			'DocBasicUsage' => '#buttonsection-basicusage',
			'DocButtonModal' => '#buttonsection-modals',
			'DocButtonSubmenu' => '#buttonsection-submenu',
		);
		?>
		<ul class="documentation-summary">
			<?php foreach ($summary as $summary_label => $summary_link) : ?>
			<li>
				<a href="<?php echo $summary_link; ?>"><?php echo $langs->trans($summary_label); ?></a>
			</li>
			<?php endforeach; ?>
		</ul>

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
			echo $documentation->showCode($lines); ?>
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
					'confirm' => true
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
					'confirm' => true
				);
				print dolGetButtonAction($label, $html, $actionType, $url, $id, $userRight, $params); ?>
			</div>
			<?php
			$lines = array(
				'<?php',
				'// Default parameters',
				'$params = array(',
				'	\'confirm\' => true',
				');',
				'',
				'// Custom parameters',
				'$params = array(',
				'	\'confirm\' => array(',
				'		\'url\' => \'your confirm action url\',',
				'		\'title\' => \'Your title to display\',',
				'		\'action-btn-label\' => \'Your confirm label\',',
				'		\'cancel-btn-label\' => \'Your cancel label\',',
				'		\'content\' => \'Content to display  with <strong>HTML</strong> compatible <ul><li>test 01</li><li>test 02</li><li>test 03</li></ul>\'',
				'	)',
				');',
				'',
				'print dolGetButtonAction($label, $html, $actionType, $url, $id, $userRight, $params);',
			);
			echo $documentation->showCode($lines); ?>
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
						'perm' => 1
					),
					array(
						'lang'=>'documentation@documentation',
						'url'=> $submenu_url.'#'.$id,
						'label' => 'My SubAction 2',
						'perm' => 0
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
			echo $documentation->showCode($lines); ?>
		</div>

	</div>

</div>
<?php
// Output close body + html
$documentation->docFooter();
?>