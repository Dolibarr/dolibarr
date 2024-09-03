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
					$url = '#button-id-1';
					print dolGetButtonAction($label, $html, $action_type, $url, $id, $user_right);

					$html = '<span class="fa fa-trash-alt paddingright" aria-hidden="true"></span> My delete action';
					$action_type = 'delete';
					$id = 'button-id-2';
					$url = '#button-id-2';
					print dolGetButtonAction($label, $html, $action_type, $url, $id, $user_right);

					$html = '<span class="fa fa-radiation paddingright" aria-hidden="true"></span> My danger action';
					$action_type = 'danger';
					$id = 'button-id-3';
					$url = '#button-id-3';
					print dolGetButtonAction($label, $html, $action_type, $url, $id, $user_right); ?>
					
					<br><br>

					<?php
					$user_right = 0;

					$html = '<span class="fa fa-clone paddingright" aria-hidden="true"></span> My default action';
					$action_type = 'default';
					$id = 'button-id-4';
					$url = '#button-id-4';
					print dolGetButtonAction($label, $html, $action_type, $url, $id, $user_right);

					$html = '<span class="fa fa-trash-alt paddingright" aria-hidden="true"></span> My delete action';
					$action_type = 'delete';
					$id = 'button-id-5';
					$url = '#button-id-5';
					print dolGetButtonAction($label, $html, $action_type, $url, $id, $user_right);

					$html = '<span class="fa fa-radiation paddingright" aria-hidden="true"></span> My danger action';
					$action_type = 'danger';
					$id = 'button-id-6';
					$url = '#button-id-6';
					print dolGetButtonAction($label, $html, $action_type, $url, $id, $user_right); ?>
				</div>
				<?php
				$lines = array(
					'<?php',
					'/**',
					' * Function dolGetButtonAction',
					' *',
					' * $label 	Label or tooltip of button if $text is provided. Also used as tooltip in title attribute. Can be escaped HTML content or full simple text.',
					' * $html	Optional : short label on button. Can be escaped HTML content or full simple text.',
					' * $actionType	default, danger, email, clone, cancel, delete, ...',
					' * $url 	Url for link or array of subbutton description',
					' * $id 		Attribute id of action button. Example \'action-delete\'. This can be used for full ajax confirm if this code is reused into the ->formconfirm() method.',
					' * $userRight 	User action right / 0 = No, 1 = Yes',
					' * ',
					' * See more in core/lib/functions.lib.php',
					' */',
					'print dolGetButtonAction($label, $html, $actionType, $url, $id, $userRight);',
				);
				echo $documentation->showCode($lines); ?>
			</div>

			<!-- Example of modal usage -->
			<div class="documentation-section" id="buttonsection-modals">
				<h2 class="documentation-title"><?php echo $langs->trans('DocButtonModal'); ?></h2>
				<p class="documentation-text"><?php echo $langs->trans('DocButtonModalDescription'); ?></p>
				<div class="documentation-example">
				</div>
				<?php
				$lines = array();
				echo $documentation->showCode($lines); ?>
			</div>

		</div>

	</div>

<?php
// Output close body + html
$documentation->docFooter();
?>