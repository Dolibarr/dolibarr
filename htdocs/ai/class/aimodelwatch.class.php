<?php
/* Copyright (C) 2026	Jose Martinez			<jose.martinez@pichinov.com>
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
 * \file htdocs/ai/class/aimodelwatch.class.php
 * \ingroup ai
 * \brief Scheduled check that the configured AI models are still offered by the provider.
 *
 * No AI provider offers webhooks for model retirement, so polling their model
 * listing endpoint is the only proactive signal available. This class is wired
 * as a cron job by modAi (daily by default): it compares every configured
 * AI_API_<ACTIVESERVICE>_MODEL_* constant against the provider's live list,
 * stores the result in AI_MODEL_WATCH_LAST_RESULT (displayed as a banner on
 * the models admin page), and emails the admin with a closest-match suggestion
 * when a configured model disappeared.
 */

/**
 * Class AiModelWatch
 *
 * Cron target: check configured AI models against the provider's model list.
 */
class AiModelWatch
{
	/**
	 * @var DoliDB Database handler
	 */
	public $db;

	/**
	 * @var string Error message (cron convention)
	 */
	public $error = '';

	/**
	 * @var string Output message shown in the cron job card (cron convention)
	 */
	public $output = '';

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * CRON: compare the configured AI_API_<ACTIVESERVICE>_MODEL_* constants against
	 * the provider's current model list (live call, cache bypassed). The result is
	 * stored in AI_MODEL_WATCH_LAST_RESULT — read by the models admin page to
	 * display a warning banner — and, when at least one configured model is no
	 * longer offered, an alert email is sent with the closest available model
	 * suggested for each. Warn only: nothing is modified automatically, since a
	 * provider listing can be incomplete while the value still works.
	 *
	 * @return int 0 if OK (including "nothing to check"), -1 on technical error
	 */
	public function checkConfiguredModels()
	{
		global $conf, $langs;

		if (!isModEnabled('ai')) {
			$this->output = 'AI module not enabled — nothing to check.';
			return 0;
		}

		require_once DOL_DOCUMENT_ROOT.'/ai/lib/ai.lib.php';
		require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
		$langs->loadLangs(array('other'));

		$list = getAiProviderModelList($this->db, true);
		$serviceKey = $list['service'];
		$models = $list['models'];

		if (empty($serviceKey)) {
			$this->output = 'No AI provider configured — nothing to check.';
			return 0;
		}
		if (empty($models)) {
			// Local providers (or a temporary outage) expose no model listing: we
			// cannot tell configured models apart from retired ones, so do nothing
			// rather than raise false alerts.
			$this->output = "Provider '".$serviceKey."' returned no model list (no listing API, or call failed) — nothing to check.";
			return 0;
		}

		// Collect the configured model constants of the ACTIVE service only (the
		// admin models page shows the same scope).
		$missing = array();
		$checked = 0;
		$sql = "SELECT name, value FROM ".MAIN_DB_PREFIX."const";
		$sql .= " WHERE name LIKE 'AI_API_".$this->db->escape(strtoupper($serviceKey))."_MODEL_%'";
		$sql .= " AND entity IN (0, ".((int) $conf->entity).")";
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			return -1;
		}
		while ($obj = $this->db->fetch_object($resql)) {
			if ($obj->value === '' || $obj->value === null) {
				continue;
			}
			$checked++;
			if (!in_array($obj->value, $models)) {
				$missing[] = array(
					'const' => $obj->name,
					'model' => $obj->value,
					'suggest' => aiSuggestClosestModel($obj->value, $models)
				);
			}
		}

		// Always store the result (an empty 'missing' clears a previous banner).
		dolibarr_set_const($this->db, 'AI_MODEL_WATCH_LAST_RESULT', json_encode(array(
			'ts' => dol_now(),
			'service' => $serviceKey,
			'missing' => $missing
		)), 'chaine', 0, '', $conf->entity);

		if (empty($missing)) {
			$this->output = $checked." configured model(s) checked against ".count($models)." offered by '".$serviceKey."' — all still available.";
			return 0;
		}

		// Alert email with the closest-match suggestions
		$lines = array();
		foreach ($missing as $miss) {
			$line = $miss['const'].' = '.$miss['model'];
			if (!empty($miss['suggest'])) {
				$line .= ' — '.$langs->transnoentities("AIModelClosestAvailable", $miss['suggest']);
			}
			$lines[] = $line;
		}

		$sendto = getDolGlobalString('AI_MODEL_WATCH_EMAIL', getDolGlobalString('MAIN_INFO_SOCIETE_MAIL'));
		$from = getDolGlobalString('MAIN_MAIL_EMAIL_FROM');
		$mailinfo = 'no alert email sent (set AI_MODEL_WATCH_EMAIL or the company email, and MAIN_MAIL_EMAIL_FROM)';
		if ($sendto && $from) {
			include_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
			$subject = '['.getDolGlobalString('MAIN_APPLICATION_TITLE', 'Dolibarr').'] '.$langs->transnoentities("AIModelWatchEmailSubject");
			$body = $langs->transnoentities("AIModelWatchEmailIntro", $serviceKey)."\n\n- ".implode("\n- ", $lines)."\n";
			$mail = new CMailFile($subject, $sendto, $from, $body, array(), array(), array(), '', '', 0, 0);
			if ($mail->sendfile()) {
				$mailinfo = 'alert email sent to '.$sendto;
			} else {
				$mailinfo = 'alert email to '.$sendto.' FAILED: '.$mail->error;
			}
		}

		$this->output = count($missing)."/".$checked." configured model(s) no longer offered by '".$serviceKey."': ".implode('; ', $lines).' — '.$mailinfo;
		return 0;
	}
}
