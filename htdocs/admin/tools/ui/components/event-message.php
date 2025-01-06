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

$action = GETPOST('action', 'alpha');

if ($action == 'displayeventmessage') {
	setEventMessages($langs->trans('DocSetEventMessageUnique'), null);
} elseif ($action == 'displayeventmessages') {
	$messageArray = [$langs->trans('DocSetEventMessage', '1'),
					$langs->trans('DocSetEventMessage', '2'),
					$langs->trans('DocSetEventMessage', '3')];
	setEventMessages(null, $messageArray);
} elseif ($action == 'displayeventmessageok') {
	setEventMessages($langs->trans('DocSetEventMessageOK'), null);
} elseif ($action == 'displayeventmessagewarning') {
	setEventMessages($langs->trans('DocSetEventMessageWarning'), null, 'warnings');
} elseif ($action == 'displayeventmessageerror') {
	setEventMessages($langs->trans('DocSetEventMessageError'), null, 'errors');
}

//
$documentation = new Documentation($db);
$morejs = [
	'/includes/ace/src/ace.js',
	'/includes/ace/src/ext-statusbar.js',
	'/includes/ace/src/ext-language_tools.js',
];
// Output html head + body - Param is Title
$documentation->docHeader('SetEventMessages', $morejs);

// Set view for menu and breadcrumb
// Menu must be set in constructor of documentation class
$documentation->view = array('Components','Event Message');

// Output sidebar
$documentation->showSidebar(); ?>

<div class="doc-wrapper">

	<?php $documentation->showBreadCrumb(); ?>

	<div class="doc-content-wrapper">

		<h1 class="documentation-title"><?php echo $langs->trans('DocSetEventMessageTitle'); ?></h1>
		<p class="documentation-text"><?php echo $langs->trans('DocSetEventMessageMainDescription'); ?></p>

		<!-- Summary -->
		<?php $documentation->showSummary(); ?>

		<!-- Basic usage -->
		<div class="documentation-section" id="seteventmessagesection-basicusage">
			<h2 class="documentation-title"><?php echo $langs->trans('DocBasicUsage'); ?></h2>
			<p class="documentation-text"><?php echo $langs->trans('DocSetEventMessageDescription'); ?></p>
			<div class="documentation-example">
				<?php
					$label = 'My action label used for accessibility visually for impaired people';
					$user_right = 1;

					$html = '<span class="fa fa-comment paddingright"></span>'.$langs->trans('DocSetEventMessageDisplayMessage');
					$action_type = 'displayeventmessage';
					$url = $_SERVER["PHP_SELF"].'?action=displayeventmessage';
					print dolGetButtonAction($label, $html, $action_type, $url, '', $user_right);

					$label = 'My action label used for accessibility visually for impaired people';
					$user_right = 1;

					$html = '<span class="fa fa-comments paddingright"></span>'.$langs->trans('DocSetEventMessageDisplayMessages');
					$action_type = 'displayeventmessages';
					$url = $_SERVER["PHP_SELF"].'?action=displayeventmessages';
					print dolGetButtonAction($label, $html, $action_type, $url, '', $user_right); ?>
			</div>
			<?php
			$lines = array(
					'<?php',
					'/**',
					'* Function dolGetButtonAction',
					'*',
					'*  Set event messages in dol_events session object. Will be output by calling dol_htmloutput_events',
					'*  Note: Calling dol_htmloutput_events is done into pages by standard llxFooter() function',
					'*',
					'*  @param  string|null     $mesg       Message string',
					'*  @param  string[]|null   $mesgs      Message array',
					'*  @param  string  $style              Which style to use ("mesgs" by default, "warnings", "errors")',
					'*  @param  string  $messagekey         A key to be used to allow the feature "Never show this message during this session again"',
					'*  @param  int     $noduplicate        1 means we do not add the message if already present in session stack',
					'*  @return void',
					'*  @see	dol_htmloutput_events()',
					'*/',
					'',
					'setEventMessages("message", null);',
					'setEventMessages(null, messages[]);',
			);
			echo $documentation->showCode($lines, 'php'); ?>
		</div>

		<!-- Contextual variations -->
		<div class="documentation-section" id="seteventmessagesection-contextvariations">
			<h2 class="documentation-title"><?php echo $langs->trans('DocSetEventMessageContextualVariations'); ?></h2>
			<p class="documentation-text"><?php echo $langs->trans('DocSetEventMessageContextualVariationsDescription'); ?></p>
			<div class="documentation-example">
				<?php
				$label = 'My action label used for accessibility visually for impaired people';
				$user_right = 1;
				$html = '<span class="fa fa-comment paddingright"></span>'.$langs->trans('DocSetEventMessageDisplayOKMessage');
				$action_type = 'displayeventmessageok';
				$url = $_SERVER["PHP_SELF"].'?action=displayeventmessageok#seteventmessagesection-contextvariations';
				$params['attr']['style'] = 'background: #446548';
				print dolGetButtonAction('', $html, $action_type, $url, '', $user_right, $params);

				$label = 'My action label used for accessibility visually for impaired people';
				$user_right = 1;
				$html = '<span class="fa fa-comment paddingright"></span>'.$langs->trans('DocSetEventMessageDisplayWarningMessage');
				$action_type = 'displayeventmessagewarning';
				$url = $_SERVER["PHP_SELF"].'?action=displayeventmessagewarning#seteventmessagesection-contextvariations';
				$params['attr']['style'] = 'background: #a28918';
				print dolGetButtonAction($label, $html, $action_type, $url, '', $user_right, $params);

				$label = 'My action label used for accessibility visually for impaired people';
				$user_right = 1;
				$html = '<span class="fa fa-comment paddingright"></span>'.$langs->trans('DocSetEventMessageDisplayErrorMessage');
				$action_type = 'displayeventmessageerror';
				$url = $_SERVER["PHP_SELF"].'?action=displayeventmessageerror#seteventmessagesection-contextvariations';
				$params['attr']['style'] = 'background: #a72947';
				print dolGetButtonAction($label, $html, $action_type, $url, '', $user_right, $params); ?>
			</div>
			<?php
			$lines = array(
				'<?php',
				'setEventMessages("message", null)',
				'setEventMessages("message", null, "warnings")',
				'setEventMessages("message", null, "errors")'
			);
			echo $documentation->showCode($lines, 'php'); ?>
		</div>
		<!--  -->
	</div>

</div>

<?php
// Output close body + html
$documentation->docFooter();

?>
