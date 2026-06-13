<?php
/* Copyright (C) 2026	Laurent Destailleur		<eldy@users.sourceforge.net>
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

/**
 * \file    htdocs/ai/assistant/popover.php
 * \ingroup ai
 * \brief   AJAX endpoint returning the AI Assistant chat HTML fragment for the topbar popover
 *
 * The fragment is injected by the bootstrap script of top_menu_ai() (main.inc.php)
 * into the #topmenu-ai-popover shell, then initialized by initAiAssistant() from
 * ai/js/ai_assistant.js. It must contain no <script> nor <link> tag: scripts
 * injected via innerHTML are not executed; CSS and JS are loaded by the caller.
 */

if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', 1);
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', 1);
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', 1);
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', 1);
}

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/ai/lib/ai.lib.php';
/**
 * @var Conf $conf
 * @var Translate $langs
 * @var User $user
 */

// Security check (same gates as ai/assistant/index.php)
if (!isModEnabled('ai') || !getDolGlobalString('AI_ASSISTANT_ENABLED')) {
	accessforbidden('Module or feature not allowed');
}
if (!$user->hasRight('ai', 'assistant', 'use')) {
	accessforbidden();
}

$langs->loadLangs(array("main", "other", "dict"));

top_httphead('text/html');

print getAiChatAssistantHtml('popover');
