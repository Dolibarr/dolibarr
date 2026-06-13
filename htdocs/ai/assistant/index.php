<?php
/* Copyright (C) 2026	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2026	Nick Fragoulis
 * Copyright (C) 2026	Jose Martinez			<jose.martinez@pichinov.com>
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
require_once DOL_DOCUMENT_ROOT.'/ai/lib/ai.lib.php';
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
// Per-user gate: the Assistant page is now governed by the standard
// 'ai/assistant/use' right (granted by default to new users), so admins
// can revoke access per user/group via the standard permission UI.
if (!$user->hasRight('ai', 'assistant', 'use')) {
	accessforbidden();
}

global $langs;
$langs->loadLangs(array("main", "other", "dict"));

$page_name = $langs->trans("AIAssistant");
llxHeader('', $page_name);
?>

<!-- CSS & JS INCLUDES -->
<link rel="stylesheet" href="<?php echo dol_buildpath('/ai/css/ai_assistant.css', 1); ?>">

<script type="module" src="<?php echo dol_buildpath('/ai/js/ai_assistant.js', 1).'?v='.urlencode((string) (@filemtime(DOL_DOCUMENT_ROOT.'/ai/js/ai_assistant.js') ?: DOL_VERSION)); ?>"></script>

<?php
// Chat markup (shared with the topbar popover fragment, see ai/assistant/popover.php).
// The data-ai-autoinit attribute makes ai_assistant.js initialize it on load.
print getAiChatAssistantHtml('page');

llxFooter();
