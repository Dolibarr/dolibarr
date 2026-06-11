<?php
/* Copyright (C) 2026  Braito                  <braito4@hotmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file    htdocs/ai/class/emailcleaner.class.php
 * \ingroup ai
 * \brief   Service class to clean EmailCollector messages without business decisions.
 */

require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

/**
 * Class EmailCleaner
 */
class EmailCleaner
{
	private const TABLE_CLEANING = 'emailcollector_ai_cleaning';
	private const TABLE_HANDOFF = 'emailcollector_ai_handoff';

	/**
	 * @var DoliDB
	 */
	public $db;

	/**
	 * Constructor.
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * Check if the runtime dependencies for EmailCleaner are available.
	 *
	 * @return bool
	 */
	public static function isRuntimeAvailable()
	{
		if (!isModEnabled('ai')) return false;
		if (!isModEnabled('emailcollector')) return false;
		return true;
	}

	/**
	 * Check if the EmailCollector hook action targets EmailCleaner.
	 *
	 * @param string $action Hook action
	 * @return bool
	 */
	public static function isEmailCollectorHookAction($action)
	{
		$actionCode = strtolower(trim((string) $action));
		if (strpos($actionCode, 'hook') !== 0) return false;
		return in_array($actionCode, array('hook_ai_emailcleaner', 'hookaiemailcleaner'), true);
	}

	/**
	 * Process one EmailCollector message through the isolated EmailCleaner workflow.
	 *
	 * @param array<string,mixed> $parameters Hook parameters
	 * @param CommonObject $object EmailCollector object
	 * @return array<string,mixed>
	 */
	public function processEmailCollectorMessage($parameters, &$object)
	{
		global $conf, $dolibarr_main_data_root;

		$this->ensureStorageTables();

		$rawBody = self::normalizeText((string) ($parameters['messagetext'] ?? ''));
		$subject = (string) ($parameters['subject'] ?? '');
		$header = (string) ($parameters['header'] ?? '');
		$from = (string) ($parameters['from'] ?? '');
		$to = self::extractHeaderFieldValue($header, 'To');
		$cc = self::extractHeaderFieldValue($header, 'Cc');
		$replyTo = self::extractHeaderFieldValue($header, 'Reply-To');
		$headerDate = self::extractHeaderFieldValue($header, 'Date');
		$inReplyTo = self::extractHeaderFieldValue($header, 'In-Reply-To');
		$referencesRaw = self::extractHeaderFieldValue($header, 'References');
		$references = self::extractHeaderMessageIds($referencesRaw);
		$collectorId = (!empty($object->id) ? (int) $object->id : 0);
		$entity = (!empty($conf->entity) ? (int) $conf->entity : 1);
		$isolatedMode = (int) getDolGlobalInt('AI_EMAILCLEANER_ISOLATED_MODE', 1);
		$thirdpartyId = ($isolatedMode ? 0 : (!empty($parameters['thirdpartyid']) ? (int) $parameters['thirdpartyid'] : 0));
		$objectId = ($isolatedMode ? 0 : (!empty($parameters['objectid']) ? (int) $parameters['objectid'] : 0));
		$hasAttachments = (!empty($parameters['attachments']) && is_array($parameters['attachments']));
		$actionParamRaw = (string) ($parameters['actionparam'] ?? '');
		$actionParamCfg = self::parseActionParamConfig($actionParamRaw);
		$contextProfileCode = trim((string) (!empty($actionParamCfg['context_profile_code']) ? $actionParamCfg['context_profile_code'] : ''));
		$contextProfileVersion = trim((string) (!empty($actionParamCfg['context_profile_version']) ? $actionParamCfg['context_profile_version'] : ''));

		if (trim($rawBody) === '') return array();

		$msgid = self::extractMessageIdFromHeader($header);
		if ($msgid === '') {
			$msgid = sha1($subject."\n".$from."\n".substr($rawBody, 0, 4000));
		}
		$replyOnly = self::extractReplyOnlyTextBasic($rawBody);
		$quotedContext = self::extractQuotedThreadSnippetBasic($rawBody, 1200);
		$emailContext = array(
			'threading' => array(
				'message_id' => $msgid,
				'in_reply_to' => ($inReplyTo !== '' ? $inReplyTo : null),
				'references' => (!empty($references) ? $references : array()),
				'thread_depth' => (int) count($references) + ($inReplyTo !== '' ? 1 : 0),
			),
			'addresses' => array(
				'from' => $from,
				'to' => ($to !== '' ? $to : null),
				'cc' => ($cc !== '' ? $cc : null),
				'reply_to' => ($replyTo !== '' ? $replyTo : null),
			),
			'meta' => array(
				'date_header' => ($headerDate !== '' ? $headerDate : null),
				'subject' => $subject,
				'subject_normalized' => self::normalizeText($subject),
				'collector_id' => $collectorId,
				'thirdparty_id' => ($thirdpartyId > 0 ? $thirdpartyId : null),
				'object_id' => ($objectId > 0 ? $objectId : null),
				'has_attachments' => ($hasAttachments ? 1 : 0),
				'isolated_mode' => ($isolatedMode ? 1 : 0),
			),
			'body_context' => array(
				'reply_only' => ($replyOnly !== '' ? $replyOnly : null),
				'quoted_context' => ($quotedContext !== '' ? $quotedContext : null),
				'has_quoted_context' => ($quotedContext !== '' ? 1 : 0),
			),
		);
		$attachmentRows = self::collectAttachmentRows(
			(isset($parameters['attachments']) && is_array($parameters['attachments']) ? $parameters['attachments'] : array()),
			(isset($parameters['savedattachments']) && is_array($parameters['savedattachments']) ? $parameters['savedattachments'] : array()),
			(string) ($parameters['savedattachmentsdir'] ?? '')
		);
		$hasPdfAttachments = false;
		foreach ($attachmentRows as $attRow) {
			if (!empty($attRow['is_pdf'])) {
				$hasPdfAttachments = true;
				break;
			}
		}

		$cleanResult = $this->runEmailCleaner($subject, $from, $rawBody);
		$cleanedText = (string) ($cleanResult['clean_body'] ?? '');
		$confidence = (float) ($cleanResult['confidence'] ?? 0.0);
		$segments = (!empty($cleanResult['segments']) && is_array($cleanResult['segments'])) ? $cleanResult['segments'] : array();
		$emailUnderstanding = (!empty($cleanResult['email_understanding']) && is_array($cleanResult['email_understanding'])) ? $cleanResult['email_understanding'] : self::buildFallbackEmailUnderstanding($cleanedText);
		$engine = (string) ($cleanResult['engine'] ?? 'fallback');
		$fallbackUsed = !empty($cleanResult['fallback_used']);
		$hCleanup = self::buildNoiseSummaryFromSegments($segments);
		$complianceMeta = self::buildComplianceMetadata($engine, $fallbackUsed, $confidence);
		$pdfAttachmentItems = array();
		if ($hasPdfAttachments) {
			foreach ($attachmentRows as $attRow) {
				if (empty($attRow['is_pdf'])) continue;
				$pdfAttachmentItems[] = array(
					'name' => (string) ($attRow['name'] ?? ''),
					'relative_path' => (string) ($attRow['relative_path'] ?? ''),
					'sha256' => (string) ($attRow['sha256'] ?? ''),
					'detected_doc_type' => self::detectPdfDocTypeFromAttachmentNameAndText((string) ($attRow['name'] ?? ''), $cleanedText),
					'linking_target' => 'actioncomm',
					'final_object_attachment_pending' => 1,
				);
			}
		}
		$handoffPayload = array(
			'handoff_version' => 'emailcleaner_v2',
			'email' => array(
				'subject' => $subject,
				'from' => $from,
				'date' => ($headerDate !== '' ? $headerDate : null),
				'raw_hash' => hash('sha256', $rawBody),
				'message_id' => $msgid,
			),
			'decision_relevant_text' => array(
				array(
					'type' => 'main_content',
					'text' => $cleanedText,
					'confidence' => $confidence,
					'source' => 'clean_body',
				),
			),
			'email_understanding' => $emailUnderstanding,
			'conversation_context' => $emailContext,
			'supporting_evidence' => array(
				'segment_count' => count($segments),
				'fallback_used' => ($fallbackUsed ? 1 : 0),
				'understanding_source' => ($engine === 'ai' ? 'ai_structured_preprocess' : 'fallback_clean_text'),
			),
			'excluded_noise_summary' => $hCleanup,
			'compliance' => $complianceMeta,
			'needs_reprocessing' => ($fallbackUsed || $confidence < (float) getDolGlobalString('AI_EMAILCLEANER_MIN_CONFIDENCE', '0.60') ? 1 : 0),
			'needs_pdf_linking' => ($hasPdfAttachments ? 1 : 0),
			'pdf_attachments' => $pdfAttachmentItems,
		);

		$baseDir = (!empty($conf->ai->dir_output) ? $conf->ai->dir_output : $dolibarr_main_data_root.'/ai');
		$storeDir = rtrim($baseDir, '/').'/emailcleaner/entity_'.$entity;
		dol_mkdir($storeDir);

		$safeKey = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $msgid);
		if ($safeKey === '' || $safeKey === null) $safeKey = sha1($msgid);
		if (strlen($safeKey) > 180) $safeKey = substr($safeKey, 0, 180);

		$payload = array(
			'entity' => $entity,
			'collector_id' => $collectorId,
			'isolated_mode' => ($isolatedMode ? 1 : 0),
			'message_id' => $msgid,
			'email_from' => $from,
			'email_subject' => $subject,
			'raw_hash' => hash('sha256', $rawBody),
			'clean_hash' => hash('sha256', $cleanedText),
			'raw_body' => $rawBody,
			'clean_body' => $cleanedText,
			'segments' => $segments,
			'cleaning_confidence' => $confidence,
			'cleaning_model' => ($engine === 'ai' ? 'auto' : null),
			'prompt_code' => 'email_cleaner_v2',
			'prompt_version' => '2',
			'context_profile_code' => ($contextProfileCode !== '' ? $contextProfileCode : null),
			'context_profile_version' => ($contextProfileVersion !== '' ? $contextProfileVersion : null),
			'engine' => $engine,
			'fallback_used' => ($fallbackUsed ? 1 : 0),
			'email_context' => $emailContext,
			'email_understanding' => $emailUnderstanding,
			'compliance' => $complianceMeta,
			'handoff_payload_json' => $handoffPayload,
			'date_cleaning_gmt' => dol_print_date(dol_now('gmt'), '%Y-%m-%d %H:%M:%S'),
		);

		$cleaningId = $this->insertCleaningRow(
			$entity,
			$collectorId,
			$msgid,
			$rawBody,
			$cleanedText,
			$segments,
			$confidence,
			$engine,
			($engine === 'ai' ? 'auto' : null),
			'email_cleaner_v2',
			'2',
			$contextProfileCode,
			$contextProfileVersion,
			$emailContext,
			$emailUnderstanding,
			$handoffPayload
		);
		if ($cleaningId > 0) {
			$payload['cleaning_id'] = $cleaningId;
			$handoffPayload['cleaning_id'] = $cleaningId;
			$payload['handoff_payload_json'] = $handoffPayload;
		}

		$json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		if ($json !== false) {
			$file = $storeDir.'/'.dol_sanitizeFileName($safeKey).'.json';
			dol_syslog(__METHOD__.": save email cleaner output into ".$file, LOG_DEBUG);
			@file_put_contents($file, $json);
			$payload['file'] = $file;
		}

		return $payload;
	}

	/**
	 * Ensure SQL storage tables exist for cleaner handoff data.
	 *
	 * @return void
	 */
	private function ensureStorageTables()
	{
		static $done = false;
		if ($done) return;

		$sql1 = "CREATE TABLE IF NOT EXISTS ".MAIN_DB_PREFIX.self::TABLE_CLEANING." (";
		$sql1 .= " rowid integer AUTO_INCREMENT PRIMARY KEY,";
		$sql1 .= " entity integer DEFAULT 1 NOT NULL,";
		$sql1 .= " collector_id integer,";
		$sql1 .= " msgid varchar(255),";
		$sql1 .= " raw_hash varchar(80),";
		$sql1 .= " clean_hash varchar(80),";
		$sql1 .= " clean_body MEDIUMTEXT,";
		$sql1 .= " cleaning_json LONGTEXT,";
		$sql1 .= " cleaning_confidence double,";
		$sql1 .= " cleaning_model varchar(190),";
		$sql1 .= " prompt_code varchar(128),";
		$sql1 .= " prompt_version varchar(32),";
		$sql1 .= " context_profile_code varchar(128),";
		$sql1 .= " context_profile_version varchar(32),";
		$sql1 .= " handoff_payload_json LONGTEXT,";
		$sql1 .= " date_creation datetime,";
		$sql1 .= " tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
		$sql1 .= " ) ENGINE=innodb";
		$this->db->query($sql1);

		$sql3 = "CREATE TABLE IF NOT EXISTS ".MAIN_DB_PREFIX.self::TABLE_HANDOFF." (";
		$sql3 .= " rowid integer AUTO_INCREMENT PRIMARY KEY,";
		$sql3 .= " entity integer DEFAULT 1 NOT NULL,";
		$sql3 .= " fk_cleaning integer,";
		$sql3 .= " handoff_version varchar(64),";
		$sql3 .= " consumer_code varchar(64),";
		$sql3 .= " payload_json LONGTEXT,";
		$sql3 .= " payload_hash varchar(80),";
		$sql3 .= " quality_status varchar(32),";
		$sql3 .= " low_confidence_json LONGTEXT,";
		$sql3 .= " date_creation datetime,";
		$sql3 .= " tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
		$sql3 .= " ) ENGINE=innodb";
		$this->db->query($sql3);

		$this->db->query("ALTER TABLE ".MAIN_DB_PREFIX.self::TABLE_CLEANING." ADD INDEX idx_emailcollector_ai_cleaning_entity_msgid (entity, msgid)");
		$this->db->query("ALTER TABLE ".MAIN_DB_PREFIX.self::TABLE_HANDOFF." ADD INDEX idx_emailcollector_ai_handoff_entity_cleaning (entity, fk_cleaning)");

		$done = true;
	}

	/**
	 * Insert cleaner output row into SQL storage.
	 *
	 * @param int $entity Entity id
	 * @param int $collectorId Collector id
	 * @param string $msgid Message id
	 * @param string $rawBody Raw message body
	 * @param string $cleanedText Cleaned message body
	 * @param array<int,array<string,mixed>> $segments Cleaner segments
	 * @param float $confidence Cleaner confidence
	 * @param string $engine Cleaner engine
	 * @param string|null $model AI model identifier
	 * @param string $promptCode Prompt code
	 * @param string $promptVersion Prompt version
	 * @param string $contextProfileCode Context profile code
	 * @param string $contextProfileVersion Context profile version
	 * @param array<string,mixed> $emailContext Extracted email context
	 * @param array<string,mixed> $emailUnderstanding Structured email understanding
	 * @param array<string,mixed> $handoffPayload Handoff payload
	 * @return int
	 */
	private function insertCleaningRow($entity, $collectorId, $msgid, $rawBody, $cleanedText, $segments, $confidence, $engine, $model, $promptCode, $promptVersion, $contextProfileCode, $contextProfileVersion, $emailContext, $emailUnderstanding, $handoffPayload)
	{
		$cleaningJson = array(
			'clean_body' => $cleanedText,
			'segments' => (is_array($segments) ? $segments : array()),
			'confidence' => (float) $confidence,
			'engine' => (string) $engine,
			'email_context' => (is_array($emailContext) ? $emailContext : array()),
			'email_understanding' => (is_array($emailUnderstanding) ? $emailUnderstanding : array()),
		);
		$cleaningJsonRaw = json_encode($cleaningJson, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		$handoffRaw = json_encode($handoffPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		if ($cleaningJsonRaw === false || $handoffRaw === false) return 0;

		$sql = "INSERT INTO ".MAIN_DB_PREFIX.self::TABLE_CLEANING."(";
		$sql .= "entity,collector_id,msgid,raw_hash,clean_hash,clean_body,cleaning_json,cleaning_confidence,cleaning_model,prompt_code,prompt_version,context_profile_code,context_profile_version,handoff_payload_json,date_creation";
		$sql .= ") VALUES (";
		$sql .= (int) $entity;
		$sql .= ",".(int) $collectorId;
		$sql .= ",'".$this->db->escape((string) $msgid)."'";
		$sql .= ",'".$this->db->escape(hash('sha256', (string) $rawBody))."'";
		$sql .= ",'".$this->db->escape(hash('sha256', (string) $cleanedText))."'";
		$sql .= ",'".$this->db->escape((string) $cleanedText)."'";
		$sql .= ",'".$this->db->escape($cleaningJsonRaw)."'";
		$sql .= ",".(float) $confidence;
		$sql .= ",".($model !== null && $model !== '' ? "'".$this->db->escape((string) $model)."'" : "NULL");
		$sql .= ",".($promptCode !== '' ? "'".$this->db->escape((string) $promptCode)."'" : "NULL");
		$sql .= ",".($promptVersion !== '' ? "'".$this->db->escape((string) $promptVersion)."'" : "NULL");
		$sql .= ",".($contextProfileCode !== '' ? "'".$this->db->escape((string) $contextProfileCode)."'" : "NULL");
		$sql .= ",".($contextProfileVersion !== '' ? "'".$this->db->escape((string) $contextProfileVersion)."'" : "NULL");
		$sql .= ",'".$this->db->escape($handoffRaw)."'";
		$sql .= ", '".$this->db->idate(dol_now())."'";
		$sql .= ")";
		$res = $this->db->query($sql);
		if (!$res) return 0;
		$rowid = (int) $this->db->last_insert_id(MAIN_DB_PREFIX.self::TABLE_CLEANING);
		if ($rowid > 0) {
			$this->insertHandoffRow($entity, $rowid, $handoffPayload, (float) $confidence);
		}
		return $rowid;
	}

	/**
	 * Persist handoff payload in dedicated table.
	 *
	 * @param int $entity Entity id
	 * @param int $cleaningId Cleaning row id
	 * @param array<string,mixed> $handoffPayload Handoff payload
	 * @param float $confidence Cleaner confidence
	 * @return void
	 */
	private function insertHandoffRow($entity, $cleaningId, $handoffPayload, $confidence)
	{
		$payloadRaw = json_encode($handoffPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		if ($payloadRaw === false) return;
		$lowConfidence = array();
		$minConfidence = (float) getDolGlobalString('AI_EMAILCLEANER_MIN_CONFIDENCE', '0.60');
		if ($minConfidence <= 0 || $minConfidence > 1) $minConfidence = 0.60;
		if ((float) $confidence < $minConfidence) {
			$lowConfidence[] = array('type' => 'cleaning_confidence', 'value' => (float) $confidence);
		}
		$lowRaw = json_encode($lowConfidence, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		if ($lowRaw === false) $lowRaw = '[]';

		$sql = "INSERT INTO ".MAIN_DB_PREFIX.self::TABLE_HANDOFF."(";
		$sql .= "entity,fk_cleaning,handoff_version,consumer_code,payload_json,payload_hash,quality_status,low_confidence_json,date_creation";
		$sql .= ") VALUES (";
		$sql .= (int) $entity;
		$sql .= ",".(int) $cleaningId;
		$sql .= ",'emailcleaner_v2'";
		$sql .= ",'generic'";
		$sql .= ",'".$this->db->escape($payloadRaw)."'";
		$sql .= ",'".$this->db->escape(hash('sha256', $payloadRaw))."'";
		$sql .= ",".((float) $confidence >= $minConfidence ? "'ok'" : "'low_confidence'");
		$sql .= ",'".$this->db->escape($lowRaw)."'";
		$sql .= ",'".$this->db->idate(dol_now())."'";
		$sql .= ")";
		$this->db->query($sql);
	}

	/**
	 * Parse action parameter configuration (JSON).
	 *
	 * @param string $raw Raw action parameter
	 * @return array<string,mixed>
	 */
	public static function parseActionParamConfig($raw)
	{
		$raw = trim((string) $raw);
		if ($raw === '') return array();
		if ($raw[0] !== '{') return array('context_profile_code' => $raw);
		$dec = json_decode($raw, true);
		if (!is_array($dec)) return array();
		return $dec;
	}

	/**
	 * Build minimal excluded-noise summary from segments.
	 *
	 * @param array<int,mixed> $segments Segments returned by the cleaner
	 * @return array<string,mixed>
	 */
	public static function buildNoiseSummaryFromSegments($segments)
	{
		$out = array('counts' => array(), 'samples' => array());
		if (empty($segments) || !is_array($segments)) return $out;
		foreach ($segments as $seg) {
			if (!is_array($seg)) continue;
			$type = strtolower(trim((string) ($seg['type'] ?? 'unknown')));
			$text = trim((string) ($seg['text'] ?? ''));
			if ($type === '') $type = 'unknown';
			if (empty($out['counts'][$type])) $out['counts'][$type] = 0;
			$out['counts'][$type]++;
			if ($text !== '' && (empty($out['samples'][$type]) || !is_array($out['samples'][$type]))) $out['samples'][$type] = array();
			if ($text !== '' && count($out['samples'][$type]) < 2) {
				$out['samples'][$type][] = (strlen($text) > 180 ? substr($text, 0, 180) : $text);
			}
		}
		return $out;
	}

	/**
	 * Build compliance metadata for transparency and supervision.
	 *
	 * @param string $engine Cleaner engine
	 * @param bool $fallbackUsed 1 if fallback mode was used
	 * @param float $confidence Cleaner confidence
	 * @return array<string,mixed>
	 */
	public static function buildComplianceMetadata($engine, $fallbackUsed, $confidence)
	{
		$minConfidence = (float) getDolGlobalString('AI_EMAILCLEANER_MIN_CONFIDENCE', '0.60');
		if ($minConfidence <= 0 || $minConfidence > 1) $minConfidence = 0.60;

		return array(
			'ai_transparency_label' => 'AI-generated technical preprocessing output',
			'human_review_required' => 1,
			'autonomous_business_action_allowed' => 0,
			'pii_redaction_enabled' => (getDolGlobalInt('AI_PRIVACY_REDACTION', 0) ? 1 : 0),
			'ai_system_used' => ((string) $engine === 'ai' ? 1 : 0),
			'fallback_used' => ($fallbackUsed ? 1 : 0),
			'cleaning_confidence' => (float) $confidence,
			'confidence_threshold' => $minConfidence,
			'policy_scope' => 'diagnostic_only_no_business_decision',
		);
	}

	/**
	 * Extract attachment rows with stable metadata.
	 *
	 * @param array<int,mixed> $attachments EmailCollector attachments
	 * @param array<int,mixed> $savedAttachments Saved attachment metadata
	 * @param string $savedDir Directory where attachments were saved
	 * @return array<int,array<string,mixed>>
	 */
	public static function collectAttachmentRows($attachments, $savedAttachments, $savedDir = '')
	{
		$out = array();
		$savedByName = array();
		if (is_array($savedAttachments)) {
			foreach ($savedAttachments as $sa) {
				if (!is_array($sa)) continue;
				$n = trim((string) ($sa['name'] ?? ''));
				if ($n === '') continue;
				$savedByName[strtolower($n)] = $sa;
			}
		}

		if (is_array($attachments)) {
			foreach ($attachments as $att) {
				$name = '';
				$relative = '';
				$sha = '';
				if (is_object($att)) {
					if (is_callable(array($att, 'getName'))) $name = (string) call_user_func(array($att, 'getName'));
					if (is_callable(array($att, 'getPath'))) $relative = (string) call_user_func(array($att, 'getPath'));
					if (is_callable(array($att, 'getContent'))) {
						$content = (string) call_user_func(array($att, 'getContent'));
						if ($content !== '') $sha = hash('sha256', $content);
					}
				} elseif (is_array($att)) {
					$name = (string) (!empty($att['name']) ? $att['name'] : '');
					$relative = (string) (!empty($att['path']) ? $att['path'] : '');
				}
				if ($name === '') continue;
				$lookup = strtolower($name);
				if (isset($savedByName[$lookup]) && is_array($savedByName[$lookup])) {
					$sa = $savedByName[$lookup];
					if (!empty($sa['relative_path'])) $relative = (string) $sa['relative_path'];
					if (!empty($sa['sha256'])) $sha = (string) $sa['sha256'];
				}
				if ($sha === '' && $relative !== '') {
					$candidate = $relative;
					if ($candidate[0] !== '/' && $savedDir !== '') $candidate = rtrim($savedDir, '/').'/'.$candidate;
					if (@is_readable($candidate)) {
						$h = @hash_file('sha256', $candidate);
						if (is_string($h)) $sha = $h;
					}
				}
				$ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
				$out[] = array(
					'name' => $name,
					'relative_path' => $relative,
					'sha256' => $sha,
					'is_pdf' => ($ext === 'pdf'),
				);
			}
		}

		if (empty($out) && is_array($savedAttachments)) {
			foreach ($savedAttachments as $sa) {
				if (!is_array($sa)) continue;
				$name = trim((string) ($sa['name'] ?? ''));
				if ($name === '') continue;
				$ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
				$out[] = array(
					'name' => $name,
					'relative_path' => (string) ($sa['relative_path'] ?? ''),
					'sha256' => (string) ($sa['sha256'] ?? ''),
					'is_pdf' => ($ext === 'pdf'),
				);
			}
		}

		return $out;
	}

	/**
	 * Detect document-like type from attachment name and cleaned text.
	 *
	 * @param string $filename Attachment filename
	 * @param string $cleanedText Cleaned email text
	 * @return string
	 */
	public static function detectPdfDocTypeFromAttachmentNameAndText($filename, $cleanedText)
	{
		$nameLow = strtolower(dol_string_nospecial((string) $filename));
		$textLow = strtolower(dol_string_nospecial((string) $cleanedText));
		if (preg_match('/\b(invoice|factura|fatura|fattura|facture)\b/', $nameLow.' '.$textLow)) return 'invoice_like';
		if (preg_match('/\b(order|pedido|booking|reserva|reservation|proforma|pro forma)\b/', $nameLow.' '.$textLow)) return 'order_like';
		return 'unknown';
	}

	/**
	 * Run AI cleaner with conservative fallback.
	 *
	 * @param string $subject Email subject
	 * @param string $from Sender address
	 * @param string $rawBody Raw email body
	 * @return array<string,mixed>
	 */
	public function runEmailCleaner($subject, $from, $rawBody)
	{
		$fallbackCleanBody = self::fallbackCleanBody($rawBody);
		$out = array(
			'clean_body' => $fallbackCleanBody,
			'confidence' => 0.40,
			'segments' => array(),
			'email_understanding' => self::buildFallbackEmailUnderstanding($fallbackCleanBody),
			'engine' => 'fallback',
			'fallback_used' => true,
		);

		require_once DOL_DOCUMENT_ROOT.'/ai/class/ai.class.php';
		$ai = new Ai($this->db);
		if (empty($ai->getApiService())) return $out;

		$maxInput = (int) getDolGlobalInt('AI_EMAILCLEANER_MAX_INPUT', 16000);
		if ($maxInput <= 1000) $maxInput = 16000;
		$redactionEnabled = (int) getDolGlobalInt('AI_PRIVACY_REDACTION', 0);
		$guard = null;
		$fromForPrompt = $from;
		$subjectForPrompt = $subject;
		$bodyForPrompt = $rawBody;
		if (strlen($bodyForPrompt) > $maxInput) {
			$bodyForPrompt = substr($bodyForPrompt, 0, $maxInput);
		}
		if ($redactionEnabled) {
			require_once DOL_DOCUMENT_ROOT.'/ai/class/privacy_guard.class.php';
			if (class_exists('PrivacyGuard')) {
				$guard = new PrivacyGuard();
				$fromForPrompt = $guard->mask((string) $fromForPrompt);
				$subjectForPrompt = $guard->mask((string) $subjectForPrompt);
				$bodyForPrompt = $guard->mask((string) $bodyForPrompt);
			}
		}

		$prompt = "You are an EmailCleaner and structured email preprocessor for ERP ingestion.\n";
		$prompt .= "Task: separate current message content from quoted thread/noise and extract neutral understanding of the email.\n";
		$prompt .= "Important constraints:\n";
		$prompt .= "- Do NOT classify business intent and do NOT decide any action.\n";
		$prompt .= "- Do NOT create, update, approve, reject, match, or route business objects.\n";
		$prompt .= "- Extract only explicit information present in the email. Do not guess missing dates, amounts, references, contacts, or meaning.\n";
		$prompt .= "- Be conservative: when unsure, keep text in clean_body.\n";
		$prompt .= "- Put quoted/forwarded old thread content in segments, but keep only current-message facts in email_understanding.\n";
		$prompt .= "- Return valid JSON only.\n";
		$prompt .= "JSON schema:\n";
		$prompt .= "{\n";
		$prompt .= "  \"clean_body\": string,\n";
		$prompt .= "  \"segments\": [\n";
		$prompt .= "    {\"type\":\"main_content|quoted_thread|signature|legal_disclaimer|system_noise|unknown\",\"text\":string}\n";
		$prompt .= "  ],\n";
		$prompt .= "  \"email_understanding\": {\n";
		$prompt .= "    \"key_points\": string[],\n";
		$prompt .= "    \"standardized\": {\n";
		$prompt .= "      \"dates\": string[],\n";
		$prompt .= "      \"amounts\": string[],\n";
		$prompt .= "      \"references\": string[],\n";
		$prompt .= "      \"contacts\": string[],\n";
		$prompt .= "      \"languages\": string[]\n";
		$prompt .= "    },\n";
		$prompt .= "    \"categories\": {\n";
		$prompt .= "      \"business_topics\": string[],\n";
		$prompt .= "      \"document_mentions\": string[],\n";
		$prompt .= "      \"requested_actions\": string[],\n";
		$prompt .= "      \"risks_or_warnings\": string[],\n";
		$prompt .= "      \"noise_types\": string[]\n";
		$prompt .= "    },\n";
		$prompt .= "    \"structured\": {\n";
		$prompt .= "      \"decision_relevant_elements\": string[],\n";
		$prompt .= "      \"payment_or_document_elements\": string[],\n";
		$prompt .= "      \"other_elements\": string[]\n";
		$prompt .= "    }\n";
		$prompt .= "  },\n";
		$prompt .= "  \"confidence\": number\n";
		$prompt .= "}\n";
		$prompt .= "Constraints:\n";
		$prompt .= "- clean_body max 2000 chars.\n";
		$prompt .= "- key_points max 8 items, each max 160 chars.\n";
		$prompt .= "- every standardized/categories/structured list max 10 items, each max 180 chars.\n";
		$prompt .= "Email metadata:\n";
		$prompt .= "FROM: ".$fromForPrompt."\n";
		$prompt .= "SUBJECT: ".$subjectForPrompt."\n";
		$prompt .= "BODY:\n".$bodyForPrompt;

		$generated = $ai->generateContent($prompt, 'auto', 'textgeneration', '');
		if (is_array($generated) && !empty($generated['error'])) return $out;
		if (!is_string($generated) || trim($generated) === '') return $out;
		if ($guard) {
			$generated = $guard->unmaskAiResponse($generated);
		}

		$decoded = self::decodeJsonFromAiString($generated);
		if (empty($decoded) || !is_array($decoded)) return $out;

		$cleanBody = self::normalizeText((string) ($decoded['clean_body'] ?? ''));
		if ($cleanBody === '') return $out;

		$confidence = (float) ($decoded['confidence'] ?? 0.0);
		if ($confidence < 0) $confidence = 0;
		if ($confidence > 1) $confidence = 1;

		$minConfidence = (float) getDolGlobalString('AI_EMAILCLEANER_MIN_CONFIDENCE', '0.60');
		if ($minConfidence <= 0 || $minConfidence > 1) $minConfidence = 0.60;

		$segments = array();
		if (!empty($decoded['segments']) && is_array($decoded['segments'])) {
			foreach ($decoded['segments'] as $seg) {
				if (!is_array($seg)) continue;
				$type = trim((string) ($seg['type'] ?? 'unknown'));
				$text = self::normalizeText((string) ($seg['text'] ?? ''));
				if ($text === '') continue;
				if ($type === '') $type = 'unknown';
				$segments[] = array('type' => $type, 'text' => $text);
				if (count($segments) >= 80) break;
			}
		}

		if ($confidence < $minConfidence) {
			// Conservative fallback: keep more context if confidence is low.
			$out['clean_body'] = self::fallbackCleanBody($rawBody);
			$out['confidence'] = $confidence;
			$out['segments'] = $segments;
			$out['email_understanding'] = self::sanitizeEmailUnderstanding($decoded['email_understanding'] ?? array(), $out['clean_body']);
			$out['engine'] = 'ai_low_confidence_fallback';
			$out['fallback_used'] = true;
			return $out;
		}

		$out['clean_body'] = $cleanBody;
		$out['confidence'] = $confidence;
		$out['segments'] = $segments;
		$out['email_understanding'] = self::sanitizeEmailUnderstanding($decoded['email_understanding'] ?? array(), $cleanBody);
		$out['engine'] = 'ai';
		$out['fallback_used'] = false;

		return $out;
	}

	/**
	 * Build a minimal understanding payload when AI structured preprocessing is unavailable.
	 *
	 * @param string $cleanBody Cleaned body
	 * @return array<string,mixed>
	 */
	private static function buildFallbackEmailUnderstanding($cleanBody)
	{
		$cleanBody = self::normalizeText((string) $cleanBody);
		$keyPoints = array();
		if ($cleanBody !== '') {
			$lines = preg_split("/\n/", $cleanBody);
			if (is_array($lines)) {
				foreach ($lines as $line) {
					$line = trim((string) $line);
					if ($line === '') continue;
					$line = preg_replace('/\s+/', ' ', $line);
					if (strlen($line) > 160) $line = substr($line, 0, 160);
					$keyPoints[] = $line;
					if (count($keyPoints) >= 5) break;
				}
			}
		}

		return array(
			'key_points' => $keyPoints,
			'standardized' => array(
				'dates' => array(),
				'amounts' => array(),
				'references' => array(),
				'contacts' => array(),
				'languages' => array(),
			),
			'categories' => array(
				'business_topics' => array(),
				'document_mentions' => array(),
				'requested_actions' => array(),
				'risks_or_warnings' => array(),
				'noise_types' => array(),
			),
			'structured' => array(
				'decision_relevant_elements' => $keyPoints,
				'payment_or_document_elements' => array(),
				'other_elements' => array(),
			),
		);
	}

	/**
	 * Sanitize structured understanding returned by AI.
	 *
	 * @param mixed $raw Raw AI understanding payload
	 * @param string $fallbackCleanBody Cleaned body used as fallback
	 * @return array<string,mixed>
	 */
	private static function sanitizeEmailUnderstanding($raw, $fallbackCleanBody = '')
	{
		if (!is_array($raw)) {
			return self::buildFallbackEmailUnderstanding($fallbackCleanBody);
		}

		$out = self::buildFallbackEmailUnderstanding('');
		$out['key_points'] = self::sanitizeStringList($raw['key_points'] ?? array(), 8, 160);

		$standardized = (isset($raw['standardized']) && is_array($raw['standardized'])) ? $raw['standardized'] : array();
		$out['standardized'] = array(
			'dates' => self::sanitizeStringList($standardized['dates'] ?? array(), 10, 80),
			'amounts' => self::sanitizeStringList($standardized['amounts'] ?? array(), 10, 80),
			'references' => self::sanitizeStringList($standardized['references'] ?? array(), 10, 120),
			'contacts' => self::sanitizeStringList($standardized['contacts'] ?? array(), 10, 160),
			'languages' => self::sanitizeStringList($standardized['languages'] ?? array(), 5, 40),
		);

		$categories = (isset($raw['categories']) && is_array($raw['categories'])) ? $raw['categories'] : array();
		$out['categories'] = array(
			'business_topics' => self::sanitizeStringList($categories['business_topics'] ?? array(), 10, 160),
			'document_mentions' => self::sanitizeStringList($categories['document_mentions'] ?? array(), 10, 160),
			'requested_actions' => self::sanitizeStringList($categories['requested_actions'] ?? array(), 10, 160),
			'risks_or_warnings' => self::sanitizeStringList($categories['risks_or_warnings'] ?? array(), 10, 180),
			'noise_types' => self::sanitizeStringList($categories['noise_types'] ?? array(), 10, 80),
		);

		$structured = (isset($raw['structured']) && is_array($raw['structured'])) ? $raw['structured'] : array();
		$out['structured'] = array(
			'decision_relevant_elements' => self::sanitizeStringList($structured['decision_relevant_elements'] ?? array(), 12, 180),
			'payment_or_document_elements' => self::sanitizeStringList($structured['payment_or_document_elements'] ?? array(), 12, 180),
			'other_elements' => self::sanitizeStringList($structured['other_elements'] ?? array(), 12, 180),
		);

		if (empty($out['key_points']) && empty($out['structured']['decision_relevant_elements'])) {
			return self::buildFallbackEmailUnderstanding($fallbackCleanBody);
		}

		return $out;
	}

	/**
	 * Sanitize a list of short strings.
	 *
	 * @param mixed $items Raw list
	 * @param int $maxItems Maximum number of items
	 * @param int $maxLen Maximum item length
	 * @return array<int,string>
	 */
	private static function sanitizeStringList($items, $maxItems, $maxLen)
	{
		$items = is_array($items) ? $items : array();
		$maxItems = max(0, (int) $maxItems);
		$maxLen = max(1, (int) $maxLen);
		$out = array();
		$seen = array();

		foreach ($items as $item) {
			$item = trim((string) $item);
			if ($item === '' || strtolower($item) === 'null') continue;
			$item = preg_replace('/\s+/', ' ', $item);
			$item = trim((string) $item);
			if ($item === '') continue;
			if (strlen($item) > $maxLen) {
				$item = substr($item, 0, $maxLen);
				$item = rtrim($item, " \t\r\n,;:");
			}
			$key = strtolower($item);
			if (!empty($seen[$key])) continue;
			$seen[$key] = 1;
			$out[] = $item;
			if (count($out) >= $maxItems) break;
		}

		return $out;
	}

	/**
	 * Normalize text to UTF-8 LF-only.
	 *
	 * @param string $text Text to normalize
	 * @return string
	 */
	public static function normalizeText($text)
	{
		$text = (string) $text;
		$text = str_replace("\r\n", "\n", $text);
		$text = str_replace("\r", "\n", $text);
		return trim($text);
	}

	/**
	 * Extract Message-ID from raw RFC822 header.
	 *
	 * @param string $header Raw RFC822 header
	 * @return string
	 */
	public static function extractMessageIdFromHeader($header)
	{
		$header = (string) $header;
		if ($header === '') return '';
		if (preg_match('/^Message-ID:\s*<([^>]+)>/mi', $header, $m)) {
			return trim((string) $m[1]);
		}
		if (preg_match('/^Message-ID:\s*([^\s]+)/mi', $header, $m)) {
			return trim(str_replace(array('<', '>'), '', (string) $m[1]));
		}
		return '';
	}

	/**
	 * Extract unfolded RFC822 header value.
	 *
	 * @param string $header Raw RFC822 header
	 * @param string $fieldName Header field name
	 * @return string
	 */
	public static function extractHeaderFieldValue($header, $fieldName)
	{
		$header = str_replace("\r\n", "\n", (string) $header);
		$header = str_replace("\r", "\n", $header);
		$fieldName = trim((string) $fieldName);
		if ($header === '' || $fieldName === '') return '';

		$re = '/^'.preg_quote($fieldName, '/').'\s*:\s*(.+)$/mi';
		if (!preg_match($re, $header, $m, PREG_OFFSET_CAPTURE)) return '';

		$val = (string) ($m[1][0] ?? '');
		$offset = (int) ($m[1][1] ?? 0);
		$after = substr($header, $offset + strlen($val));
		if ($after !== '') {
			$lines = preg_split("/\n/", $after);
			if (is_array($lines)) {
				foreach ($lines as $ln) {
					if ($ln === '') break;
					if (preg_match('/^[ \t]+/', $ln)) {
						$val .= ' '.trim($ln);
						continue;
					}
					break;
				}
			}
		}

		return trim(str_replace(array('<', '>'), '', $val));
	}

	/**
	 * Parse message-id list from References-like header.
	 *
	 * @param string $raw Raw References header
	 * @return array<int,string>
	 */
	public static function extractHeaderMessageIds($raw)
	{
		$raw = trim((string) $raw);
		if ($raw === '') return array();

		$out = array();
		if (preg_match_all('/<([^>]+)>/', $raw, $m)) {
			foreach ((array) $m[1] as $v) {
				$v = trim((string) $v);
				if ($v === '') continue;
				$out[] = $v;
			}
		}
		if (empty($out)) {
			$parts = preg_split('/\s+/', str_replace(array(',', ';'), ' ', $raw));
			if (is_array($parts)) {
				foreach ($parts as $p) {
					$p = trim(str_replace(array('<', '>'), '', (string) $p));
					if ($p === '' || strpos($p, '@') === false) continue;
					$out[] = $p;
				}
			}
		}

		$out = array_values(array_unique($out));
		if (count($out) > 20) $out = array_slice($out, -20);
		return $out;
	}

	/**
	 * Extract reply-only part with a conservative separator strategy.
	 *
	 * @param string $body Raw email body
	 * @return string
	 */
	public static function extractReplyOnlyTextBasic($body)
	{
		$body = self::normalizeText((string) $body);
		if ($body === '') return '';

		$separators = array(
			'-----Original Message-----',
			'----- Original Message -----',
			'-----Mensaje original-----',
			'----- Mensaje original -----',
			'-----Mensagem original-----',
			'----- Mensagem original -----',
			'----- Forwarded message -----',
		);
		foreach ($separators as $sep) {
			$pos = stripos($body, $sep);
			if ($pos !== false && $pos > 0) {
				return trim(substr($body, 0, $pos));
			}
		}

		$lines = preg_split("/\n/", $body);
		if (!is_array($lines)) return $body;
		$out = array();
		foreach ($lines as $line) {
			$trim = trim((string) $line);
			if ($trim === '') {
				if (!empty($out)) $out[] = '';
				continue;
			}
			if (preg_match('/^(from|de|sent|enviado|to|para|subject|asunto)\b[^:]{0,12}:/i', $trim)) break;
			if (preg_match('/^>+/', $trim)) break;
			$out[] = $line;
			if (strlen(implode("\n", $out)) > 4000) break;
		}

		return trim(implode("\n", $out));
	}

	/**
	 * Build compact quoted context snippet for auditing/context transfer.
	 *
	 * @param string $body Raw email body
	 * @param int $maxLen Maximum snippet length
	 * @return string
	 */
	public static function extractQuotedThreadSnippetBasic($body, $maxLen = 1200)
	{
		$body = self::normalizeText((string) $body);
		$maxLen = (int) $maxLen;
		if ($body === '') return '';
		if ($maxLen <= 0) $maxLen = 1200;

		$startPos = false;
		foreach (array('original message', 'mensaje original', 'mensagem original', 'forwarded message') as $needle) {
			$p = stripos($body, $needle);
			if ($p !== false && ($startPos === false || $p < $startPos)) $startPos = $p;
		}
		if ($startPos === false) {
			$lines = preg_split("/\n/", $body);
			if (!is_array($lines)) return '';
			$quoteLines = array();
			foreach ($lines as $ln) {
				$t = trim((string) $ln);
				if ($t === '') continue;
				if (preg_match('/^\>+/', $t)) {
					$quoteLines[] = preg_replace('/^\>+\s*/', '', $t);
				}
			}
			$txt = trim(implode("\n", $quoteLines));
			return (strlen($txt) > $maxLen ? substr($txt, 0, $maxLen) : $txt);
		}

		$txt = trim(substr($body, (int) $startPos));
		if (strlen($txt) > $maxLen) $txt = substr($txt, 0, $maxLen);
		return $txt;
	}

	/**
	 * Conservative fallback cleaner.
	 *
	 * @param string $rawBody Raw email body
	 * @return string
	 */
	private static function fallbackCleanBody($rawBody)
	{
		$text = self::normalizeText($rawBody);
		if ($text === '') return '';

		$lines = preg_split('/\n+/', $text);
		if (!is_array($lines)) return trim($text);

		$out = array();
		foreach ($lines as $line) {
			$line = trim((string) $line);
			if ($line === '') continue;

			$low = dol_string_nospecial($line);
			$low = strtolower($low);
			if (preg_match('/^(from|de|sent|enviado|to|para|subject|asunto)\b[^:]{0,12}:/i', $line)) break;
			if (strpos($low, 'original message') !== false) break;
			if (strpos($low, 'mensaje original') !== false) break;
			if (strpos($low, 'forwarded message') !== false) break;
			if (preg_match('/^\>+/', $line)) continue;
			if (preg_match('/\b(confidential|privacidad|confidencialidad)\b/i', $line)) continue;

			$out[] = $line;
			if (strlen(implode("\n", $out)) > 6000) break;
		}

		$clean = trim(implode("\n", $out));
		if ($clean === '') {
			$clean = trim(substr($text, 0, 2000));
		}

		return $clean;
	}

	/**
	 * Decode JSON from AI output that may contain wrappers.
	 *
	 * @param string $raw Raw AI output
	 * @return array<string,mixed>|null
	 */
	private static function decodeJsonFromAiString($raw)
	{
		$raw = trim((string) $raw);
		if ($raw === '') return null;

		$decoded = json_decode($raw, true);
		if (is_array($decoded)) return $decoded;

		$first = strpos($raw, '{');
		$last = strrpos($raw, '}');
		if ($first === false || $last === false || $last <= $first) return null;

		$payload = substr($raw, $first, $last - $first + 1);
		$decoded = json_decode($payload, true);
		if (!is_array($decoded)) return null;

		return $decoded;
	}
}
