<?php
/* Copyright (C) 2026	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2026	Nick Fragoulis
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY, without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 * or see https://www.gnu.org/
 */

/**
 * \file 	htdocs/ai/assistant/index.php
 * \ingroup ai
 * \brief 	Main Chat Interface for MCP Server AI Assistant
 */

// Load Dolibarr environment
require '../../main.inc.php';
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Security check
if (!isModEnabled('ai') || !getDolGlobalString('AI_ASSISTANT_ENABLED')) {
	accessforbidden('Module or feature not allowed');
}

global $langs;
$langs->loadLangs(array("main", "other", "dict"));

$keys = [
	// General UI
	'NoDataAvailable',
	'Error',
	'NoRecordFound',
	'Download',
	'Show',
	'Confirm',
	'ConfirmAiAction',
	'ClearChatHistoryTitle',
	'HistoryCleared',
	'Send',
	'TypeYourQuestion',

	// Placeholders & Status
	'TypeOrSpeak',
	'UploadLocalDoc',
	'UploadCloudDoc',
	'DocLoaded',
	'Listening',
	'Transcribed',
	'NoSpeech',
	'ProcessingAudio',
	'Timeout',
	'Cancelled',

	// Engine Specific
	'CloudSpeechReady',
	'WhisperReady',
	'DownloadingModel',
	'ModelLoading',

	// Document Processing
	'ProcessingFile',
	'ReadingPdf',
	'PdfError',
	'UnsupportedFileType',
	'TryingOCR',
	'OcrProgress',
	'SwitchingAIModel',
	'OcrFailed',
	'ReadingWord',
	'ReadingExcel',
	'ReadingOdf',

	// Errors
	'MicError',
	'MicTooQuiet',
	'ConnectionBlocked',
	'ConnectionBlockedHelp',
	'WorkerInitFailed',
	'NetworkError',
	'AIError',
	'EmptyAIResponse',
	'BrowserNotSupported',

	// Actions & Dialogs
	'YesProceed',
	'Cancel',
	'Submit',
	'ActionCancelled',
	'ExecutingTool',
	'FetchingData',
	'GeneratingLink',
	'Found',
	'TypeResponse',
	'OpenVerb',

	// Voice Confirmation
	'VoiceYesNo',
	'VoiceQuiet',
	'PleaseRepeat',
	'HeardText',

	// Context
	'DocContextIntro',
	'DocContextOutro'
];
$ai_translations = [];

foreach ($keys as $key) {
	$ai_translations[$key] = $langs->transnoentitiesnoconv($key);
}
$ai_translations['DownloadPdf']
	= $langs->transnoentitiesnoconv("Download") . ' PDF';

$ai_translations['CloudVoiceRequiresSecureContext'] = $langs->trans(
	"CloudVoiceRequiresSecureContext",
	"HTTPS",
	"localhost",
	"Whisper"
);

// Get Admin Configuration
$default_mode = getDolGlobalString('AI_DEFAULT_INPUT_MODE');

$page_name = $langs->trans("AIAssistant");
llxHeader('', $page_name);

// Define global JS config object
$js_config = json_encode([
	'mode'   => $default_mode,
	'labels' => $ai_translations
]);
?>

<!-- Javascript configuration -->
<script>
	window.AI_CONFIG = <?php echo $js_config; ?>;
</script>

<!-- CSS & JS INCLUDES -->
<link rel="stylesheet" href="<?php echo dol_buildpath('/ai/css/ai_assistant.css', 1); ?>">

<script type="module" src="<?php echo dol_buildpath('/ai/js/ai_assistant.js', 1); ?>"></script>

<!-- HTML -->
<div class="ai-chat-container">

	<!-- Header -->
	<div class="chat-header">
		<h2><?php echo img_picto('', 'fa-robot') . ' ' . $langs->trans("AIAssistant"); ?></h2>

		<div class="header-controls">
			<!-- Clear Button -->
			<button type="button" id="clear-btn" class="icon-btn" title="<?php echo $langs->trans("ClearChatHistoryTitle"); ?>">
				<?php echo img_picto('', 'fa-trash'); ?> <?php echo $langs->trans("Clear"); ?>
			</button>

			<!-- Engine Switcher -->
			<select id="engine-select" class="engine-select">
				<option value="text"><?php echo $langs->transnoentitiesnoconv("OptionTextOnly"); ?></option>
				<option value="cloud"><?php echo $langs->transnoentitiesnoconv("OptionCloudFast"); ?></option>
				<option value="whisper"><?php echo $langs->transnoentitiesnoconv("OptionWhisperLocal"); ?></option>
				<option value="local_docs"><?php echo $langs->transnoentitiesnoconv("OptionLocalParsing"); ?></option>
				<option value="cloud_docs"><?php echo $langs->transnoentitiesnoconv("OptionCloudParsing"); ?></option>
			</select>
		</div>
	</div>

	<!-- Chat History -->
	<div id="chat-history" class="chat-history">
		<div class="msg system">
			<?php echo $langs->trans("AIWelcomeMessage"); ?>
		</div>
	</div>

	<!-- Controls -->
	<div class="chat-controls">

		<!-- Microphone Wrapper (Visible only in Voice modes) -->
		<div id="mic-wrapper" class="mic-wrapper hidden">
			<button type="button" id="mic-btn" class="round-btn mic-btn" title="<?php echo $langs->trans("ToggleMicrophone"); ?>">
				<?php echo img_picto('', 'fa-microphone'); ?>
			</button>
		</div>

		<!-- Upload Wrapper (Visible only in Doc modes) -->
		<div id="upload-wrapper" class="upload-wrapper hidden">
			<input type="file" id="file-upload" accept=".pdf,.txt,.xml,.png,.jpg,.jpeg,.doc,.docx,.xls,.xlsx,.odt,.ods" style="display: none;">
			<button type="button" id="upload-btn" class="round-btn" title="<?php echo $langs->transnoentitiesnoconv("AttachFile"); ?>">
				<?php echo img_picto('', 'fa-paperclip'); ?>
			</button>
		</div>

		<!-- Text Input -->
		<textarea id="user-input" rows="1" placeholder="<?php echo $langs->trans("TypeYourQuestion"); ?>" autocomplete="off"></textarea>

		<!-- Send Button -->
		<button type="button" id="send-btn" class="button"><?php echo $langs->trans("SendPrompt"); ?></button>
	</div>

	<div id="status-bar"></div>
</div>

<?php llxFooter(); ?>
