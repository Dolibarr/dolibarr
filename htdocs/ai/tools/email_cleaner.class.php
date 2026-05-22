<?php
/* Copyright (C) 2026  Braito                  <braito4@hotmail.com>
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
 * \file htdocs/ai/tools/email_cleaner.class.php
 * \ingroup ai
 * \brief MCP tools for EmailCollector AI cleaner (technical/descriptive only).
 */

/**
 * Class ToolEmailCleaner
 */
class ToolEmailCleaner extends McpTool
{
	private const TABLE_CLEANING = 'emailcollector_ai_cleaning';
	private const TABLE_HANDOFF = 'emailcollector_ai_handoff';
	private const TABLE_QUEUE_PDF = 'ai_unassigned_pdf_queue';

	/**
	 * @var array<string,bool>
	 */
	private $tableExistsCache = array();

	/**
	 * Returns tool definitions.
	 *
	 * @return list<array<string,mixed>>
	 */
	public function getDefinitions(): array
	{
		if (!isModEnabled('emailcollector') || !getDolGlobalInt('AI_EMAILCLEANER_ENABLED', 0)) {
			return [];
		}

		return [
			[
				'name' => 'list_email_cleaner_runs',
				'description' => 'List EmailCollector AI cleaner runs (technical metadata only). No business interpretation.',
				'inputSchema' => [
					'type' => 'object',
					'properties' => [
						'collector_id' => ['type' => 'integer', 'description' => 'Optional collector id filter.'],
						'message_id' => ['type' => 'string', 'description' => 'Optional message-id filter.'],
						'context_profile_code' => ['type' => 'string', 'description' => 'Optional context profile filter.'],
						'min_confidence' => ['type' => 'number', 'description' => 'Optional minimum cleaning confidence (0..1).'],
						'max_confidence' => ['type' => 'number', 'description' => 'Optional maximum cleaning confidence (0..1).'],
						'limit' => ['type' => 'integer', 'default' => 20],
						'offset' => ['type' => 'integer', 'default' => 0],
					],
				],
			],
			[
				'name' => 'get_email_cleaner_run',
				'description' => 'Get one EmailCollector AI cleaner run including clean output, email context and handoff payload.',
				'inputSchema' => [
					'type' => 'object',
					'properties' => [
						'cleaning_id' => ['type' => 'integer', 'description' => 'Cleaner rowid.'],
						'message_id' => ['type' => 'string', 'description' => 'Message-id fallback selector.'],
						'collector_id' => ['type' => 'integer', 'description' => 'Optional collector filter when using message_id.'],
					],
					'anyOf' => [
						['required' => ['cleaning_id']],
						['required' => ['message_id']],
					],
				],
			],
			[
				'name' => 'get_email_thread_context',
				'description' => 'Get only the technical email thread context extracted by EmailCleaner. No business interpretation.',
				'inputSchema' => [
					'type' => 'object',
					'properties' => [
						'cleaning_id' => ['type' => 'integer', 'description' => 'Cleaner rowid.'],
						'message_id' => ['type' => 'string', 'description' => 'Message-id fallback selector.'],
						'collector_id' => ['type' => 'integer', 'description' => 'Optional collector filter when using message_id.'],
					],
					'anyOf' => [
						['required' => ['cleaning_id']],
						['required' => ['message_id']],
					],
				],
			],
			[
				'name' => 'list_email_pdf_queue',
				'description' => 'List unassigned PDF queue generated from email attachments (diagnostic queue only).',
				'inputSchema' => [
					'type' => 'object',
					'properties' => [
						'status' => ['type' => 'string', 'description' => "Optional status filter (queued, processing, done, error, ignored)."],
						'collector_id' => ['type' => 'integer', 'description' => 'Optional collector id filter.'],
						'message_id' => ['type' => 'string', 'description' => 'Optional message-id filter.'],
						'detected_doc_type' => ['type' => 'string', 'description' => 'Optional detected doc type filter.'],
						'needs_human_review' => ['type' => 'integer', 'enum' => [0, 1], 'description' => 'Optional human review filter.'],
						'limit' => ['type' => 'integer', 'default' => 20],
						'offset' => ['type' => 'integer', 'default' => 0],
					],
				],
			],
			[
				'name' => 'get_email_pdf_queue_item',
				'description' => 'Get one unassigned PDF queue item with technical extraction/matching payloads.',
				'inputSchema' => [
					'type' => 'object',
					'properties' => [
						'queue_id' => ['type' => 'integer', 'description' => 'Queue rowid.'],
					],
					'required' => ['queue_id'],
				],
			],
			[
				'name' => 'get_email_handoff_payload',
				'description' => 'Get handoff payload generated by email cleaner. Evidence only, no decision.',
				'inputSchema' => [
					'type' => 'object',
					'properties' => [
						'handoff_id' => ['type' => 'integer', 'description' => 'Handoff rowid.'],
						'cleaning_id' => ['type' => 'integer', 'description' => 'Cleaner rowid fallback selector.'],
					],
					'anyOf' => [
						['required' => ['handoff_id']],
						['required' => ['cleaning_id']],
					],
				],
			],
		];
	}

	/**
	 * Return categories this tool belongs to.
	 *
	 * @return array<string>
	 */
	public function getCategories(): array
	{
		return ['global'];
	}

	/**
	 * Execute tool.
	 *
	 * @param string $name Tool function name
	 * @param array<string,mixed> $args Tool arguments
	 * @return mixed
	 */
	public function execute(string $name, array $args)
	{
		if (!$this->canReadEmailData()) {
			return $this->appendComplianceMetadata(['error' => "Permission Denied: You don't have rights to read email cleaner data."]);
		}

		$result = array();
		switch ($name) {
			case 'list_email_cleaner_runs':
				$result = $this->listEmailCleanerRuns($args);
				break;

			case 'get_email_cleaner_run':
				$result = $this->getEmailCleanerRun($args);
				break;

			case 'get_email_thread_context':
				$result = $this->getEmailThreadContext($args);
				break;

			case 'list_email_pdf_queue':
				$result = $this->listEmailPdfQueue($args);
				break;

			case 'get_email_pdf_queue_item':
				$result = $this->getEmailPdfQueueItem($args);
				break;

			case 'get_email_handoff_payload':
				$result = $this->getEmailHandoffPayload($args);
				break;

			default:
				$result = ['error' => "Tool function '$name' not found."];
				break;
		}

		return $this->appendComplianceMetadata($result);
	}

	/**
	 * Check read permission for technical email data.
	 *
	 * @return bool
	 */
	private function canReadEmailData(): bool
	{
		if (empty($this->user->id)) return false;
		if (!is_object($this->conf) || !isModEnabled('ai')) return false;
		if (!isModEnabled('emailcollector')) return false;
		if (!getDolGlobalInt('AI_EMAILCLEANER_ENABLED', 0)) return false;
		if (!empty($this->user->admin)) return true;
		if (method_exists($this->user, 'hasRight') && $this->user->hasRight('emailcollector', 'read')) return true;
		return false;
	}

	/**
	 * List cleaner runs.
	 *
	 * @param array<string,mixed> $args Filter and pagination arguments
	 * @return array<string,mixed>
	 */
	private function listEmailCleanerRuns(array $args): array
	{
		if (!$this->isTableAvailable(self::TABLE_CLEANING)) {
			return ['error' => 'Email cleaner storage table is not available.'];
		}

		$entity = (int) (!empty($this->conf->entity) ? $this->conf->entity : 1);
		$limit = $this->sanitizeLimit($args['limit'] ?? 20);
		$offset = max(0, (int) ($args['offset'] ?? 0));
		$collectorId = (int) ($args['collector_id'] ?? 0);
		$messageId = trim((string) ($args['message_id'] ?? ''));
		$contextProfileCode = trim((string) ($args['context_profile_code'] ?? ''));
		$minConfidence = $this->sanitizeConfidenceOrNull($args['min_confidence'] ?? null);
		$maxConfidence = $this->sanitizeConfidenceOrNull($args['max_confidence'] ?? null);

		$sql = "SELECT rowid, collector_id, msgid, cleaning_confidence, cleaning_model, prompt_code, prompt_version, context_profile_code, context_profile_version, date_creation";
		$sql .= " FROM ".MAIN_DB_PREFIX.self::TABLE_CLEANING;
		$sql .= " WHERE entity = ".((int) $entity);

		if ($collectorId > 0) {
			$sql .= " AND collector_id = ".((int) $collectorId);
		}
		if ($messageId !== '') {
			$sql .= " AND msgid = '".$this->db->escape($messageId)."'";
		}
		if ($contextProfileCode !== '') {
			$sql .= " AND context_profile_code = '".$this->db->escape($contextProfileCode)."'";
		}
		if ($minConfidence !== null) {
			$sql .= " AND cleaning_confidence >= ".((float) $minConfidence);
		}
		if ($maxConfidence !== null) {
			$sql .= " AND cleaning_confidence <= ".((float) $maxConfidence);
		}

		$sql .= " ORDER BY rowid DESC";
		$sql .= $this->db->plimit($limit, $offset);

		$resql = $this->db->query($sql);
		if (!$resql) {
			return ['error' => 'Database error while listing email cleaner runs: '.$this->db->lasterror()];
		}

		$items = array();
		while ($obj = $this->db->fetch_object($resql)) {
			$items[] = array(
				'cleaning_id' => (int) $obj->rowid,
				'collector_id' => (int) $obj->collector_id,
				'message_id' => (string) $obj->msgid,
				'cleaning_confidence' => (float) $obj->cleaning_confidence,
				'cleaning_model' => (!empty($obj->cleaning_model) ? (string) $obj->cleaning_model : null),
				'prompt_code' => (!empty($obj->prompt_code) ? (string) $obj->prompt_code : null),
				'prompt_version' => (!empty($obj->prompt_version) ? (string) $obj->prompt_version : null),
				'context_profile_code' => (!empty($obj->context_profile_code) ? (string) $obj->context_profile_code : null),
				'context_profile_version' => (!empty($obj->context_profile_version) ? (string) $obj->context_profile_version : null),
				'date_creation' => (!empty($obj->date_creation) ? (string) $obj->date_creation : null),
			);
		}
		$this->db->free($resql);

		return array(
			'items' => $items,
			'count' => count($items),
			'filters' => array(
				'entity' => $entity,
				'collector_id' => ($collectorId > 0 ? $collectorId : null),
				'message_id' => ($messageId !== '' ? $messageId : null),
				'context_profile_code' => ($contextProfileCode !== '' ? $contextProfileCode : null),
				'min_confidence' => $minConfidence,
				'max_confidence' => $maxConfidence,
				'limit' => $limit,
				'offset' => $offset,
			),
		);
	}

	/**
	 * Get one cleaner run.
	 *
	 * @param array<string,mixed> $args Selector arguments
	 * @return array<string,mixed>
	 */
	private function getEmailCleanerRun(array $args): array
	{
		if (!$this->isTableAvailable(self::TABLE_CLEANING)) {
			return ['error' => 'Email cleaner storage table is not available.'];
		}

		$entity = (int) (!empty($this->conf->entity) ? $this->conf->entity : 1);
		$cleaningId = (int) ($args['cleaning_id'] ?? 0);
		$messageId = trim((string) ($args['message_id'] ?? ''));
		$collectorId = (int) ($args['collector_id'] ?? 0);

		$sql = "SELECT rowid, entity, collector_id, msgid, raw_hash, clean_hash, clean_body, cleaning_json, cleaning_confidence, cleaning_model, prompt_code, prompt_version, context_profile_code, context_profile_version, handoff_payload_json, date_creation";
		$sql .= " FROM ".MAIN_DB_PREFIX.self::TABLE_CLEANING;
		$sql .= " WHERE entity = ".((int) $entity);

		if ($cleaningId > 0) {
			$sql .= " AND rowid = ".((int) $cleaningId);
		} elseif ($messageId !== '') {
			$sql .= " AND msgid = '".$this->db->escape($messageId)."'";
			if ($collectorId > 0) {
				$sql .= " AND collector_id = ".((int) $collectorId);
			}
			$sql .= " ORDER BY rowid DESC";
		} else {
			return ['error' => "Missing selector: use 'cleaning_id' or 'message_id'."];
		}

		$sql .= $this->db->plimit(1);

		$resql = $this->db->query($sql);
		if (!$resql) {
			return ['error' => 'Database error while reading cleaner run: '.$this->db->lasterror()];
		}
		$obj = $this->db->fetch_object($resql);
		$this->db->free($resql);
		if (!$obj) {
			return ['error' => 'Email cleaner run not found.'];
		}

		$cleaningJson = $this->decodeJsonOrRaw((string) $obj->cleaning_json);
		$handoffJson = $this->decodeJsonOrRaw((string) $obj->handoff_payload_json);

		return array(
			'cleaning_id' => (int) $obj->rowid,
			'entity' => (int) $obj->entity,
			'collector_id' => (int) $obj->collector_id,
			'message_id' => (string) $obj->msgid,
			'raw_hash' => (!empty($obj->raw_hash) ? (string) $obj->raw_hash : null),
			'clean_hash' => (!empty($obj->clean_hash) ? (string) $obj->clean_hash : null),
			'clean_body' => (!empty($obj->clean_body) ? (string) $obj->clean_body : ''),
			'cleaning_confidence' => (float) $obj->cleaning_confidence,
			'cleaning_model' => (!empty($obj->cleaning_model) ? (string) $obj->cleaning_model : null),
			'prompt_code' => (!empty($obj->prompt_code) ? (string) $obj->prompt_code : null),
			'prompt_version' => (!empty($obj->prompt_version) ? (string) $obj->prompt_version : null),
			'context_profile_code' => (!empty($obj->context_profile_code) ? (string) $obj->context_profile_code : null),
			'context_profile_version' => (!empty($obj->context_profile_version) ? (string) $obj->context_profile_version : null),
			'cleaning_json' => $cleaningJson,
			'handoff_payload_json' => $handoffJson,
			'date_creation' => (!empty($obj->date_creation) ? (string) $obj->date_creation : null),
		);
	}

	/**
	 * Get only the technical email thread context from a cleaner run.
	 *
	 * @param array<string,mixed> $args Selector arguments
	 * @return array<string,mixed>
	 */
	private function getEmailThreadContext(array $args): array
	{
		$run = $this->getEmailCleanerRun($args);
		if (!empty($run['error'])) {
			return $run;
		}

		$cleaningJson = (!empty($run['cleaning_json']) && is_array($run['cleaning_json'])) ? $run['cleaning_json'] : array();
		$handoffJson = (!empty($run['handoff_payload_json']) && is_array($run['handoff_payload_json'])) ? $run['handoff_payload_json'] : array();
		$emailContext = array();

		if (!empty($cleaningJson['email_context']) && is_array($cleaningJson['email_context'])) {
			$emailContext = $cleaningJson['email_context'];
		} elseif (!empty($handoffJson['conversation_context']) && is_array($handoffJson['conversation_context'])) {
			$emailContext = $handoffJson['conversation_context'];
		}

		return array(
			'cleaning_id' => (int) $run['cleaning_id'],
			'collector_id' => (int) $run['collector_id'],
			'message_id' => (string) $run['message_id'],
			'email_context' => $emailContext,
			'cleaning_confidence' => (float) $run['cleaning_confidence'],
			'context_profile_code' => (!empty($run['context_profile_code']) ? (string) $run['context_profile_code'] : null),
			'context_profile_version' => (!empty($run['context_profile_version']) ? (string) $run['context_profile_version'] : null),
		);
	}

	/**
	 * List unassigned PDF queue.
	 *
	 * @param array<string,mixed> $args Filter and pagination arguments
	 * @return array<string,mixed>
	 */
	private function listEmailPdfQueue(array $args): array
	{
		if (!$this->isTableAvailable(self::TABLE_QUEUE_PDF)) {
			return ['error' => 'Unassigned PDF queue table is not available.'];
		}

		$entity = (int) (!empty($this->conf->entity) ? $this->conf->entity : 1);
		$limit = $this->sanitizeLimit($args['limit'] ?? 20);
		$offset = max(0, (int) ($args['offset'] ?? 0));
		$status = trim((string) ($args['status'] ?? ''));
		$collectorId = (int) ($args['collector_id'] ?? 0);
		$messageId = trim((string) ($args['message_id'] ?? ''));
		$docType = trim((string) ($args['detected_doc_type'] ?? ''));
		$needsHumanReview = (isset($args['needs_human_review']) ? (int) $args['needs_human_review'] : -1);

		$sql = "SELECT rowid, status, priority, collector_id, message_id, email_date, email_from, email_subject, attachment_name, attachment_relpath, attachment_sha256, detected_doc_type, confidence, needs_human_review, attempts, last_error, datec, date_review, tms";
		$sql .= " FROM ".MAIN_DB_PREFIX.self::TABLE_QUEUE_PDF;
		$sql .= " WHERE entity = ".((int) $entity);

		if ($status !== '') {
			$sql .= " AND status = '".$this->db->escape($status)."'";
		}
		if ($collectorId > 0) {
			$sql .= " AND collector_id = ".((int) $collectorId);
		}
		if ($messageId !== '') {
			$sql .= " AND message_id = '".$this->db->escape($messageId)."'";
		}
		if ($docType !== '') {
			$sql .= " AND detected_doc_type = '".$this->db->escape($docType)."'";
		}
		if ($needsHumanReview === 0 || $needsHumanReview === 1) {
			$sql .= " AND needs_human_review = ".((int) $needsHumanReview);
		}

		$sql .= " ORDER BY priority ASC, rowid DESC";
		$sql .= $this->db->plimit($limit, $offset);

		$resql = $this->db->query($sql);
		if (!$resql) {
			return ['error' => 'Database error while listing PDF queue: '.$this->db->lasterror()];
		}

		$items = array();
		while ($obj = $this->db->fetch_object($resql)) {
			$items[] = array(
				'queue_id' => (int) $obj->rowid,
				'status' => (string) $obj->status,
				'priority' => (int) $obj->priority,
				'collector_id' => (int) $obj->collector_id,
				'message_id' => (string) $obj->message_id,
				'email_date' => (!empty($obj->email_date) ? (string) $obj->email_date : null),
				'email_from' => (!empty($obj->email_from) ? (string) $obj->email_from : null),
				'email_subject' => (!empty($obj->email_subject) ? (string) $obj->email_subject : null),
				'attachment_name' => (!empty($obj->attachment_name) ? (string) $obj->attachment_name : null),
				'attachment_relpath' => (!empty($obj->attachment_relpath) ? (string) $obj->attachment_relpath : null),
				'attachment_sha256' => (!empty($obj->attachment_sha256) ? (string) $obj->attachment_sha256 : null),
				'detected_doc_type' => (!empty($obj->detected_doc_type) ? (string) $obj->detected_doc_type : null),
				'confidence' => (float) $obj->confidence,
				'needs_human_review' => (int) $obj->needs_human_review,
				'attempts' => (int) $obj->attempts,
				'last_error' => (!empty($obj->last_error) ? (string) $obj->last_error : null),
				'date_creation' => (!empty($obj->datec) ? (string) $obj->datec : null),
				'date_review' => (!empty($obj->date_review) ? (string) $obj->date_review : null),
				'tms' => (!empty($obj->tms) ? (string) $obj->tms : null),
			);
		}
		$this->db->free($resql);

		return array(
			'items' => $items,
			'count' => count($items),
			'filters' => array(
				'entity' => $entity,
				'status' => ($status !== '' ? $status : null),
				'collector_id' => ($collectorId > 0 ? $collectorId : null),
				'message_id' => ($messageId !== '' ? $messageId : null),
				'detected_doc_type' => ($docType !== '' ? $docType : null),
				'needs_human_review' => ($needsHumanReview === 0 || $needsHumanReview === 1 ? $needsHumanReview : null),
				'limit' => $limit,
				'offset' => $offset,
			),
		);
	}

	/**
	 * Get one queue item.
	 *
	 * @param array<string,mixed> $args Queue selector arguments
	 * @return array<string,mixed>
	 */
	private function getEmailPdfQueueItem(array $args): array
	{
		if (!$this->isTableAvailable(self::TABLE_QUEUE_PDF)) {
			return ['error' => 'Unassigned PDF queue table is not available.'];
		}

		$entity = (int) (!empty($this->conf->entity) ? $this->conf->entity : 1);
		$queueId = (int) ($args['queue_id'] ?? 0);
		if ($queueId <= 0) {
			return ['error' => "Missing or invalid 'queue_id'."];
		}

		$sql = "SELECT rowid, entity, status, priority, source, collector_id, message_id, email_date, email_from, email_subject, attachment_name, attachment_relpath, attachment_sha256, detected_doc_type, extraction_json, matching_json, proposed_object_type, proposed_object_id, confidence, needs_human_review, review_note, attempts, last_error, fk_user_review, date_review, datec, tms";
		$sql .= " FROM ".MAIN_DB_PREFIX.self::TABLE_QUEUE_PDF;
		$sql .= " WHERE entity = ".((int) $entity)." AND rowid = ".((int) $queueId);
		$sql .= $this->db->plimit(1);

		$resql = $this->db->query($sql);
		if (!$resql) {
			return ['error' => 'Database error while reading PDF queue item: '.$this->db->lasterror()];
		}
		$obj = $this->db->fetch_object($resql);
		$this->db->free($resql);
		if (!$obj) {
			return ['error' => 'PDF queue item not found.'];
		}

		return array(
			'queue_id' => (int) $obj->rowid,
			'entity' => (int) $obj->entity,
			'status' => (string) $obj->status,
			'priority' => (int) $obj->priority,
			'source' => (!empty($obj->source) ? (string) $obj->source : null),
			'collector_id' => (int) $obj->collector_id,
			'message_id' => (!empty($obj->message_id) ? (string) $obj->message_id : null),
			'email_date' => (!empty($obj->email_date) ? (string) $obj->email_date : null),
			'email_from' => (!empty($obj->email_from) ? (string) $obj->email_from : null),
			'email_subject' => (!empty($obj->email_subject) ? (string) $obj->email_subject : null),
			'attachment_name' => (!empty($obj->attachment_name) ? (string) $obj->attachment_name : null),
			'attachment_relpath' => (!empty($obj->attachment_relpath) ? (string) $obj->attachment_relpath : null),
			'attachment_sha256' => (!empty($obj->attachment_sha256) ? (string) $obj->attachment_sha256 : null),
			'detected_doc_type' => (!empty($obj->detected_doc_type) ? (string) $obj->detected_doc_type : null),
			'extraction_json' => $this->decodeJsonOrRaw((string) $obj->extraction_json),
			'matching_state' => array(
				'matching_json' => $this->decodeJsonOrRaw((string) $obj->matching_json),
				'candidate_object_type' => (!empty($obj->proposed_object_type) ? (string) $obj->proposed_object_type : null),
				'candidate_object_id' => (!empty($obj->proposed_object_id) ? (int) $obj->proposed_object_id : null),
				'note' => 'Diagnostic candidate only. This tool does not approve, assign or update business objects.',
			),
			'confidence' => (float) $obj->confidence,
			'needs_human_review' => (int) $obj->needs_human_review,
			'review_note' => (!empty($obj->review_note) ? (string) $obj->review_note : null),
			'attempts' => (int) $obj->attempts,
			'last_error' => (!empty($obj->last_error) ? (string) $obj->last_error : null),
			'fk_user_review' => (!empty($obj->fk_user_review) ? (int) $obj->fk_user_review : null),
			'date_review' => (!empty($obj->date_review) ? (string) $obj->date_review : null),
			'date_creation' => (!empty($obj->datec) ? (string) $obj->datec : null),
			'tms' => (!empty($obj->tms) ? (string) $obj->tms : null),
		);
	}

	/**
	 * Get handoff payload.
	 *
	 * @param array<string,mixed> $args Handoff selector arguments
	 * @return array<string,mixed>
	 */
	private function getEmailHandoffPayload(array $args): array
	{
		if (!$this->isTableAvailable(self::TABLE_HANDOFF)) {
			return ['error' => 'Email handoff table is not available.'];
		}

		$entity = (int) (!empty($this->conf->entity) ? $this->conf->entity : 1);
		$handoffId = (int) ($args['handoff_id'] ?? 0);
		$cleaningId = (int) ($args['cleaning_id'] ?? 0);

		$sql = "SELECT rowid, entity, fk_cleaning, handoff_version, consumer_code, payload_json, payload_hash, quality_status, low_confidence_json, date_creation, tms";
		$sql .= " FROM ".MAIN_DB_PREFIX.self::TABLE_HANDOFF;
		$sql .= " WHERE entity = ".((int) $entity);

		if ($handoffId > 0) {
			$sql .= " AND rowid = ".((int) $handoffId);
		} elseif ($cleaningId > 0) {
			$sql .= " AND fk_cleaning = ".((int) $cleaningId);
			$sql .= " ORDER BY rowid DESC";
		} else {
			return ['error' => "Missing selector: use 'handoff_id' or 'cleaning_id'."];
		}

		$sql .= $this->db->plimit(1);

		$resql = $this->db->query($sql);
		if (!$resql) {
			return ['error' => 'Database error while reading handoff payload: '.$this->db->lasterror()];
		}
		$obj = $this->db->fetch_object($resql);
		$this->db->free($resql);
		if (!$obj) {
			return ['error' => 'Email handoff payload not found.'];
		}

		return array(
			'handoff_id' => (int) $obj->rowid,
			'entity' => (int) $obj->entity,
			'cleaning_id' => (int) $obj->fk_cleaning,
			'handoff_version' => (!empty($obj->handoff_version) ? (string) $obj->handoff_version : null),
			'consumer_code' => (!empty($obj->consumer_code) ? (string) $obj->consumer_code : null),
			'payload_json' => $this->decodeJsonOrRaw((string) $obj->payload_json),
			'payload_hash' => (!empty($obj->payload_hash) ? (string) $obj->payload_hash : null),
			'quality_status' => (!empty($obj->quality_status) ? (string) $obj->quality_status : null),
			'low_confidence_json' => $this->decodeJsonOrRaw((string) $obj->low_confidence_json),
			'date_creation' => (!empty($obj->date_creation) ? (string) $obj->date_creation : null),
			'tms' => (!empty($obj->tms) ? (string) $obj->tms : null),
		);
	}

	/**
	 * Check if a table is available.
	 *
	 * @param string $tableWithoutPrefix SQL table name without DB prefix
	 * @return bool
	 */
	private function isTableAvailable(string $tableWithoutPrefix): bool
	{
		if (isset($this->tableExistsCache[$tableWithoutPrefix])) {
			return $this->tableExistsCache[$tableWithoutPrefix];
		}

		$full = MAIN_DB_PREFIX.$tableWithoutPrefix;
		$ok = (bool) count($this->db->DDLInfoTable($full));
		$this->tableExistsCache[$tableWithoutPrefix] = $ok;

		return $ok;
	}

	/**
	 * Decode json string, fallback to raw.
	 *
	 * @param string $raw Raw value from database storage
	 * @return mixed
	 */
	private function decodeJsonOrRaw(string $raw)
	{
		$raw = trim($raw);
		if ($raw === '') return array();
		$dec = json_decode($raw, true);
		if (json_last_error() === JSON_ERROR_NONE && is_array($dec)) {
			return $dec;
		}
		return $raw;
	}

	/**
	 * Sanitize limit.
	 *
	 * @param mixed $raw User-provided limit
	 * @return int
	 */
	private function sanitizeLimit($raw): int
	{
		$limit = (int) $raw;
		if ($limit <= 0) $limit = 20;
		if ($limit > 200) $limit = 200;
		return $limit;
	}

	/**
	 * Sanitize confidence value or return null.
	 *
	 * @param mixed $raw User-provided confidence value
	 * @return float|null
	 */
	private function sanitizeConfidenceOrNull($raw): ?float
	{
		if ($raw === null || $raw === '') return null;
		$val = (float) $raw;
		if ($val < 0) $val = 0;
		if ($val > 1) $val = 1;
		return $val;
	}

	/**
	 * Append compliance disclosure to MCP responses.
	 *
	 * @param array<string,mixed> $result Tool output
	 * @return array<string,mixed>
	 */
	private function appendComplianceMetadata(array $result): array
	{
		$result['compliance'] = array(
			'ai_transparency_label' => 'AI-derived technical evidence',
			'human_review_required' => 1,
			'autonomous_business_action_allowed' => 0,
			'policy_scope' => 'diagnostic_only_no_business_decision',
		);

		return $result;
	}
}
