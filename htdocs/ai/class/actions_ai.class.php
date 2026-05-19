<?php
/* Copyright (C) 2026
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file    htdocs/ai/class/actions_ai.class.php
 * \ingroup ai
 * \brief   Hooks to integrate AI cleaner with EmailCollector.
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonhookactions.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/ai/class/emailcleaner.class.php';

/**
 * Class ActionsAi
 */
class ActionsAi extends CommonHookActions
{
	private const TABLE_CLEANING = 'emailcollector_ai_cleaning';
	private const TABLE_QUEUE_PDF = 'ai_unassigned_pdf_queue';
	private const TABLE_HANDOFF = 'emailcollector_ai_handoff';

	/**
	 * @var DoliDB
	 */
	public $db;

	/**
	 * @var string
	 */
	public $error = '';

	/**
	 * @var array<int,string>
	 */
	public $errors = array();

	/**
	 * @var array<string,mixed>
	 */
	public $results = array();

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
	 * Add AI Email Cleaner action in EmailCollector operations.
	 *
	 * @param array<string,mixed> $parameters
	 * @param CommonObject $object
	 * @param string $action
	 * @param HookManager $hookmanager
	 * @return int
	 */
	public function addMoreActionsEmailCollector($parameters, &$object, &$action, $hookmanager)
	{
		if (!$this->isEmailCleanerRuntimeAvailable()) return 0;
		if (!getDolGlobalInt('AI_EMAILCLEANER_EXPOSE_OPERATION', 0)) return 0;

		$arrayoftypes = (!empty($parameters['arrayoftypes']) && is_array($parameters['arrayoftypes'])) ? $parameters['arrayoftypes'] : array();
		$arrayoftypes['hook_ai_emailcleaner'] = 'AI cleaner (no business decision)';
		$this->results = $arrayoftypes;

		return 1;
	}

	/**
	 * Execute AI cleaner when EmailCollector operation type is "hook_ai_emailcleaner".
	 *
	 * @param array<string,mixed> $parameters
	 * @param CommonObject $object
	 * @param string $action
	 * @param HookManager $hookmanager
	 * @return int
	 */
	public function doCollectImapOneCollector($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $dolibarr_main_data_root;

		if (!$this->isEmailCleanerRuntimeAvailable()) return 0;
		$actionCode = strtolower(trim((string) $action));
		if (strpos($actionCode, 'hook') !== 0) return 0;
		if (!in_array($actionCode, array('hook_ai_emailcleaner', 'hookaiemailcleaner'), true)) return 0;
		if (!getDolGlobalInt('AI_EMAILCLEANER_ENABLED', 0)) return 0;
		$this->ensureStorageTables();

		$rawBody = EmailCleaner::normalizeText((string) ($parameters['messagetext'] ?? ''));
		$subject = (string) ($parameters['subject'] ?? '');
		$header = (string) ($parameters['header'] ?? '');
		$from = (string) ($parameters['from'] ?? '');
		$to = EmailCleaner::extractHeaderFieldValue($header, 'To');
		$cc = EmailCleaner::extractHeaderFieldValue($header, 'Cc');
		$replyTo = EmailCleaner::extractHeaderFieldValue($header, 'Reply-To');
		$headerDate = EmailCleaner::extractHeaderFieldValue($header, 'Date');
		$inReplyTo = EmailCleaner::extractHeaderFieldValue($header, 'In-Reply-To');
		$referencesRaw = EmailCleaner::extractHeaderFieldValue($header, 'References');
		$references = EmailCleaner::extractHeaderMessageIds($referencesRaw);
		$collectorId = (!empty($object->id) ? (int) $object->id : 0);
		$entity = (!empty($conf->entity) ? (int) $conf->entity : 1);
		$isolatedMode = (int) getDolGlobalInt('AI_EMAILCLEANER_ISOLATED_MODE', 1);
		$thirdpartyId = ($isolatedMode ? 0 : (!empty($parameters['thirdpartyid']) ? (int) $parameters['thirdpartyid'] : 0));
		$objectId = ($isolatedMode ? 0 : (!empty($parameters['objectid']) ? (int) $parameters['objectid'] : 0));
		$hasAttachments = (!empty($parameters['attachments']) && is_array($parameters['attachments']));
		$actionParamRaw = (string) ($parameters['actionparam'] ?? '');
		$actionParamCfg = EmailCleaner::parseActionParamConfig($actionParamRaw);
		$contextProfileCode = trim((string) (!empty($actionParamCfg['context_profile_code']) ? $actionParamCfg['context_profile_code'] : ''));
		$contextProfileVersion = trim((string) (!empty($actionParamCfg['context_profile_version']) ? $actionParamCfg['context_profile_version'] : ''));

		if (trim($rawBody) === '') return 0;

		$msgid = EmailCleaner::extractMessageIdFromHeader($header);
		if ($msgid === '') {
			$msgid = sha1($subject."\n".$from."\n".substr($rawBody, 0, 4000));
		}
		$replyOnly = EmailCleaner::extractReplyOnlyTextBasic($rawBody);
		$quotedContext = EmailCleaner::extractQuotedThreadSnippetBasic($rawBody, 1200);
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
				'subject_normalized' => EmailCleaner::normalizeText($subject),
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
		$attachmentRows = EmailCleaner::collectAttachmentRowsForQueue(
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

		$emailCleaner = new EmailCleaner($this->db);
		$cleanResult = $emailCleaner->runEmailCleaner($subject, $from, $rawBody);
		$cleanedText = (string) ($cleanResult['clean_body'] ?? '');
		$confidence = (float) ($cleanResult['confidence'] ?? 0.0);
		$segments = (!empty($cleanResult['segments']) && is_array($cleanResult['segments'])) ? $cleanResult['segments'] : array();
		$engine = (string) ($cleanResult['engine'] ?? 'fallback');
		$fallbackUsed = !empty($cleanResult['fallback_used']);
		$hCleanup = EmailCleaner::buildNoiseSummaryFromSegments($segments);
		$handoffPayload = array(
			'handoff_version' => 'emailcleaner_v1',
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
			'conversation_context' => $emailContext,
			'supporting_evidence' => array(
				'segment_count' => count($segments),
				'fallback_used' => ($fallbackUsed ? 1 : 0),
			),
			'excluded_noise_summary' => $hCleanup,
			'needs_reprocessing' => ($fallbackUsed || $confidence < (float) getDolGlobalString('AI_EMAILCLEANER_MIN_CONFIDENCE', '0.60') ? 1 : 0),
			'needs_pdf_review' => ($hasPdfAttachments ? 1 : 0),
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
			'prompt_code' => 'email_cleaner_v1',
			'prompt_version' => '1',
			'context_profile_code' => ($contextProfileCode !== '' ? $contextProfileCode : null),
			'context_profile_version' => ($contextProfileVersion !== '' ? $contextProfileVersion : null),
			'engine' => $engine,
			'fallback_used' => ($fallbackUsed ? 1 : 0),
			'email_context' => $emailContext,
			'handoff_payload_json' => $handoffPayload,
			'date_cleaning_gmt' => dol_print_date(dol_now('gmt'), '%Y-%m-%d %H:%M:%S'),
		);

		$queuedPdfItems = array();
		if ($hasPdfAttachments && getDolGlobalInt('AI_UNASSIGNED_PDF_QUEUE_ENABLED', 1)) {
			foreach ($attachmentRows as $attRow) {
				if (empty($attRow['is_pdf'])) continue;
				$docType = EmailCleaner::detectPdfDocTypeFromAttachmentNameAndText((string) ($attRow['name'] ?? ''), $cleanedText);
				$qid = $this->insertUnassignedPdfQueueRow(
					$entity,
					$collectorId,
					$msgid,
					$from,
					$subject,
					(string) ($headerDate !== '' ? $headerDate : ''),
					$attRow,
					$docType,
					$confidence
				);
				if ($qid > 0) {
					$queuedPdfItems[] = array(
						'queue_id' => $qid,
						'name' => (string) ($attRow['name'] ?? ''),
						'sha256' => (string) ($attRow['sha256'] ?? ''),
						'detected_doc_type' => $docType,
					);
					$this->appendUnassignedPdfQueueEventJsonl($entity, array(
						'event' => 'queued',
						'ts' => dol_print_date(dol_now('gmt'), '%Y-%m-%d %H:%M:%S'),
						'queue_id' => $qid,
						'entity' => $entity,
						'message_id' => $msgid,
						'attachment_sha256' => (string) ($attRow['sha256'] ?? ''),
						'status' => 'queued',
						'confidence' => $confidence,
						'actor' => 'cron',
						'note' => 'queued_by_emailcleaner',
					));
				}
			}
		}
		if (!empty($queuedPdfItems)) {
			$handoffPayload['queued_pdf_items'] = $queuedPdfItems;
			$handoffPayload['needs_pdf_review'] = 1;
			$payload['handoff_payload_json'] = $handoffPayload;
		}

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
			'email_cleaner_v1',
			'1',
			$contextProfileCode,
			$contextProfileVersion,
			$emailContext,
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
			$this->results['ai_emailcleaner_file'] = $file;
		}

		return 0;
	}

	/**
	 * Check if AI cleaner runtime is available.
	 *
	 * @return bool
	 */
	private function isEmailCleanerRuntimeAvailable()
	{
		if (!isModEnabled('ai')) return false;
		if (!isModEnabled('emailcollector')) return false;
		return true;
	}

	/**
	 * Ensure SQL storage tables exist for cleaner and unassigned PDF queue.
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

		$sql2 = "CREATE TABLE IF NOT EXISTS ".MAIN_DB_PREFIX.self::TABLE_QUEUE_PDF." (";
		$sql2 .= " rowid integer AUTO_INCREMENT PRIMARY KEY,";
		$sql2 .= " entity integer DEFAULT 1 NOT NULL,";
		$sql2 .= " status varchar(32) DEFAULT 'queued' NOT NULL,";
		$sql2 .= " priority integer DEFAULT 50,";
		$sql2 .= " source varchar(32) DEFAULT 'emailcollector' NOT NULL,";
		$sql2 .= " collector_id integer,";
		$sql2 .= " message_id varchar(255),";
		$sql2 .= " email_date varchar(190),";
		$sql2 .= " email_from varchar(255),";
		$sql2 .= " email_subject varchar(255),";
		$sql2 .= " attachment_name varchar(255),";
		$sql2 .= " attachment_relpath varchar(1024),";
		$sql2 .= " attachment_sha256 varchar(80),";
		$sql2 .= " detected_doc_type varchar(32) DEFAULT 'unknown',";
		$sql2 .= " extraction_json LONGTEXT,";
		$sql2 .= " matching_json LONGTEXT,";
		$sql2 .= " proposed_object_type varchar(64),";
		$sql2 .= " proposed_object_id integer,";
		$sql2 .= " confidence double,";
		$sql2 .= " needs_human_review integer DEFAULT 1,";
		$sql2 .= " review_note varchar(255),";
		$sql2 .= " attempts integer DEFAULT 0,";
		$sql2 .= " last_error varchar(255),";
		$sql2 .= " fk_user_review integer,";
		$sql2 .= " date_review datetime,";
		$sql2 .= " datec datetime,";
		$sql2 .= " tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
		$sql2 .= " ) ENGINE=innodb";
		$this->db->query($sql2);

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
		$this->db->query("ALTER TABLE ".MAIN_DB_PREFIX.self::TABLE_QUEUE_PDF." ADD INDEX idx_ai_unassigned_pdf_queue_entity_status (entity, status, priority, rowid)");
		$this->db->query("ALTER TABLE ".MAIN_DB_PREFIX.self::TABLE_QUEUE_PDF." ADD INDEX idx_ai_unassigned_pdf_queue_msgid (entity, message_id)");
		$this->db->query("ALTER TABLE ".MAIN_DB_PREFIX.self::TABLE_QUEUE_PDF." ADD INDEX idx_ai_unassigned_pdf_queue_sha (entity, attachment_sha256)");
		$this->db->query("ALTER TABLE ".MAIN_DB_PREFIX.self::TABLE_HANDOFF." ADD INDEX idx_emailcollector_ai_handoff_entity_cleaning (entity, fk_cleaning)");

		$done = true;
	}

	/**
	 * Insert cleaner output row into SQL storage.
	 *
	 * @return int
	 */
	private function insertCleaningRow($entity, $collectorId, $msgid, $rawBody, $cleanedText, $segments, $confidence, $engine, $model, $promptCode, $promptVersion, $contextProfileCode, $contextProfileVersion, $emailContext, $handoffPayload)
	{
		$cleaningJson = array(
			'clean_body' => $cleanedText,
			'segments' => (is_array($segments) ? $segments : array()),
			'confidence' => (float) $confidence,
			'engine' => (string) $engine,
			'email_context' => (is_array($emailContext) ? $emailContext : array()),
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
	 * Insert one unassigned PDF queue row.
	 *
	 * @param int $entity
	 * @param int $collectorId
	 * @param string $msgid
	 * @param string $emailFrom
	 * @param string $emailSubject
	 * @param string $emailDate
	 * @param array<string,mixed> $attRow
	 * @param string $docType
	 * @param float $confidence
	 * @return int
	 */
	private function insertUnassignedPdfQueueRow($entity, $collectorId, $msgid, $emailFrom, $emailSubject, $emailDate, $attRow, $docType, $confidence)
	{
		$attachmentSha = (string) ($attRow['sha256'] ?? '');
		if ($msgid !== '' && $attachmentSha !== '') {
			$sqlCheck = "SELECT rowid FROM ".MAIN_DB_PREFIX.self::TABLE_QUEUE_PDF;
			$sqlCheck .= " WHERE entity = ".((int) $entity);
			$sqlCheck .= " AND message_id = '".$this->db->escape((string) $msgid)."'";
			$sqlCheck .= " AND attachment_sha256 = '".$this->db->escape($attachmentSha)."'";
			$sqlCheck .= $this->db->plimit(1);
			$resCheck = $this->db->query($sqlCheck);
			if ($resCheck) {
				$objCheck = $this->db->fetch_object($resCheck);
				$this->db->free($resCheck);
				if ($objCheck && !empty($objCheck->rowid)) {
					return (int) $objCheck->rowid;
				}
			}
		}

		$extract = array(
			'name' => (string) ($attRow['name'] ?? ''),
			'relative_path' => (string) ($attRow['relative_path'] ?? ''),
			'sha256' => $attachmentSha,
		);
		$extractRaw = json_encode($extract, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		if ($extractRaw === false) $extractRaw = '{}';

		$sql = "INSERT INTO ".MAIN_DB_PREFIX.self::TABLE_QUEUE_PDF."(";
		$sql .= "entity,status,priority,source,collector_id,message_id,email_date,email_from,email_subject,attachment_name,attachment_relpath,attachment_sha256,detected_doc_type,extraction_json,confidence,needs_human_review,datec";
		$sql .= ") VALUES (";
		$sql .= (int) $entity;
		$sql .= ",'queued'";
		$sql .= ",50";
		$sql .= ",'emailcollector'";
		$sql .= ",".(int) $collectorId;
		$sql .= ",'".$this->db->escape((string) $msgid)."'";
		$sql .= ",".($emailDate !== '' ? "'".$this->db->escape((string) $emailDate)."'" : "NULL");
		$sql .= ",'".$this->db->escape((string) $emailFrom)."'";
		$sql .= ",'".$this->db->escape((string) $emailSubject)."'";
		$sql .= ",'".$this->db->escape((string) ($attRow['name'] ?? ''))."'";
		$sql .= ",".(!empty($attRow['relative_path']) ? "'".$this->db->escape((string) $attRow['relative_path'])."'" : "NULL");
		$sql .= ",".(!empty($attRow['sha256']) ? "'".$this->db->escape((string) $attRow['sha256'])."'" : "NULL");
		$sql .= ",'".$this->db->escape((string) $docType)."'";
		$sql .= ",'".$this->db->escape((string) $extractRaw)."'";
		$sql .= ",".(float) $confidence;
		$sql .= ",1";
		$sql .= ", '".$this->db->idate(dol_now())."'";
		$sql .= ")";
		$res = $this->db->query($sql);
		if (!$res) return 0;
		return (int) $this->db->last_insert_id(MAIN_DB_PREFIX.self::TABLE_QUEUE_PDF);
	}

	/**
	 * Persist handoff payload in dedicated table.
	 *
	 * @param int $entity
	 * @param int $cleaningId
	 * @param array<string,mixed> $handoffPayload
	 * @param float $confidence
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
		$sql .= ",'emailcleaner_v1'";
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
	 * Append one JSONL event line for PDF queue.
	 *
	 * @param int $entity
	 * @param array<string,mixed> $event
	 * @return void
	 */
	private function appendUnassignedPdfQueueEventJsonl($entity, $event)
	{
		global $conf, $dolibarr_main_data_root;
		$baseDir = (!empty($conf->ai->dir_output) ? $conf->ai->dir_output : $dolibarr_main_data_root.'/ai');
		$jsonlDir = rtrim($baseDir, '/').'/unassigned_pdf_queue';
		dol_mkdir($jsonlDir);
		$file = $jsonlDir.'/entity_'.((int) $entity).'.jsonl';
		$line = json_encode($event, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		if ($line === false) return;
		@file_put_contents($file, $line."\n", FILE_APPEND);
	}

}
