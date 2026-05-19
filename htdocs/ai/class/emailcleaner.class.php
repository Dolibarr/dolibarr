<?php
/* Copyright (C) 2026
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

/**
 * Class EmailCleaner
 */
class EmailCleaner
{
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
			if ($text !== '' && is_array($out['samples'][$type]) && count($out['samples'][$type]) < 2) {
				$out['samples'][$type][] = (strlen($text) > 180 ? substr($text, 0, 180) : $text);
			}
		}
		return $out;
	}

	/**
	 * Extract attachment rows with stable metadata.
	 *
	 * @param array<int,mixed> $attachments EmailCollector attachments
	 * @param array<int,mixed> $savedAttachments Saved attachment metadata
	 * @param string $savedDir Directory where attachments were saved
	 * @return array<int,array<string,mixed>>
	 */
	public static function collectAttachmentRowsForQueue($attachments, $savedAttachments, $savedDir = '')
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
					if (method_exists($att, 'getName')) $name = (string) $att->getName();
					if (method_exists($att, 'getPath')) $relative = (string) $att->getPath();
					if (method_exists($att, 'getContent')) {
						$content = (string) $att->getContent();
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
						if (is_string($h) && $h !== '') $sha = $h;
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
		$out = array(
			'clean_body' => self::fallbackCleanBody($rawBody),
			'confidence' => 0.40,
			'segments' => array(),
			'engine' => 'fallback',
			'fallback_used' => true,
		);

		require_once DOL_DOCUMENT_ROOT.'/ai/class/ai.class.php';
		$ai = new Ai($this->db);
		if (empty($ai->getApiService())) return $out;

		$maxInput = (int) getDolGlobalInt('AI_EMAILCLEANER_MAX_INPUT', 16000);
		if ($maxInput <= 1000) $maxInput = 16000;
		$bodyForPrompt = $rawBody;
		if (strlen($bodyForPrompt) > $maxInput) {
			$bodyForPrompt = substr($bodyForPrompt, 0, $maxInput);
		}

		$prompt = "You are an EmailCleaner for ERP ingestion.\n";
		$prompt .= "Task: separate current message content from quoted thread and repetitive noise.\n";
		$prompt .= "Important constraints:\n";
		$prompt .= "- Do NOT classify business intent and do NOT decide any action.\n";
		$prompt .= "- Be conservative: when unsure, keep text in clean_body.\n";
		$prompt .= "- Return valid JSON only.\n";
		$prompt .= "JSON schema:\n";
		$prompt .= "{\n";
		$prompt .= "  \"clean_body\": string,\n";
		$prompt .= "  \"segments\": [\n";
		$prompt .= "    {\"type\":\"main_content|quoted_thread|signature|legal_disclaimer|system_noise|unknown\",\"text\":string}\n";
		$prompt .= "  ],\n";
		$prompt .= "  \"confidence\": number\n";
		$prompt .= "}\n";
		$prompt .= "Email metadata:\n";
		$prompt .= "FROM: ".$from."\n";
		$prompt .= "SUBJECT: ".$subject."\n";
		$prompt .= "BODY:\n".$bodyForPrompt;

		$generated = $ai->generateContent($prompt, 'auto', 'textgeneration', '');
		if (is_array($generated) && !empty($generated['error'])) return $out;
		if (!is_string($generated) || trim($generated) === '') return $out;

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
			$out['engine'] = 'ai_low_confidence_fallback';
			$out['fallback_used'] = true;
			return $out;
		}

		$out['clean_body'] = $cleanBody;
		$out['confidence'] = $confidence;
		$out['segments'] = $segments;
		$out['engine'] = 'ai';
		$out['fallback_used'] = false;

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
			foreach ((array) ($m[1] ?? array()) as $v) {
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
