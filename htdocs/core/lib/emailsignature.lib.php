<?php
/* Copyright (C) 2026 Frédéric France      <frederic.france@free.fr>
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
 *	\file		htdocs/core/lib/emailsignature.lib.php
 *	\brief		Library to evaluate the quality of a user HTML email signature against emailing best practices
 */


/**
 * Analyze an HTML email signature and return a quality score (0..100) against
 * common emailing best practices: enough real text vs images, a reasonable
 * number of images, alt text, absolute https URLs, no script/style, contact
 * details present, no oversized fixed widths.
 *
 * Thresholds can be tuned with the constants SIGNATURE_QUALITY_MAX_IMG,
 * SIGNATURE_QUALITY_MAX_IMG_WIDTH, SIGNATURE_QUALITY_MAX_TEXT_LEN,
 * SIGNATURE_QUALITY_MAX_LINKS and SIGNATURE_QUALITY_MIN_CHARS_PER_IMG.
 *
 * @param	string	$html	Raw HTML content of the signature
 * @return	array{score:int,grade:string,checks:array<string,array{status:string,weight:int}>}
 *						score: 0..100, grade: excellent|good|fair|poor,
 *						checks: one entry per rule with status ok|warning|error and the applied penalty
 */
function dolCheckSignatureQuality($html)
{
	$html = (string) $html;

	// Tunable thresholds
	$maximg = getDolGlobalInt('SIGNATURE_QUALITY_MAX_IMG', 3);
	$maximgwidth = getDolGlobalInt('SIGNATURE_QUALITY_MAX_IMG_WIDTH', 600);
	$maxtextlen = getDolGlobalInt('SIGNATURE_QUALITY_MAX_TEXT_LEN', 600);
	$maxlinks = getDolGlobalInt('SIGNATURE_QUALITY_MAX_LINKS', 5);
	$mincharsperimg = getDolGlobalInt('SIGNATURE_QUALITY_MIN_CHARS_PER_IMG', 60);
	$mintextlen = getDolGlobalInt('SIGNATURE_QUALITY_MIN_TEXT_LEN', 20);

	// Fixed penalty weights (kept internal so the 0..100 scale stays meaningful)
	$w = array(
		'script_tag' => 60,
		'style_block' => 10,
		'non_absolute_url' => 25,
		'text_image_ratio_error' => 55,
		'text_image_ratio_warning' => 12,
		'image_count_error' => 20,
		'image_count_warning' => 10,
		'image_alt_per_image' => 5,
		'image_alt_cap' => 15,
		'image_weight' => 10,
		'link_count' => 10,
		'text_length' => 10,
		'contact_info' => 20,
		'fixed_width' => 5,
	);

	// A signature with almost no real text (and no image) can never be more than this
	$insufficientcontentcap = 35;

	$codes = array(
		'content', 'script_tag', 'style_block', 'non_absolute_url', 'text_image_ratio',
		'image_count', 'image_alt', 'image_weight', 'link_count', 'text_length',
		'contact_info', 'fixed_width',
	);
	$checks = array();
	foreach ($codes as $code) {
		$checks[$code] = array('status' => 'ok', 'weight' => 0);
	}
	$score = 100;

	$set = function (string $code, string $status, int $weight) use (&$checks, &$score) {
		$checks[$code] = array('status' => $status, 'weight' => $weight);
		$score -= $weight;
	};

	// Plain text length, ignoring the content of <script>/<style> blocks
	$htmlnoscript = preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/is', ' ', $html);
	$text = dol_string_nohtmltag((string) $htmlnoscript, 1, 'UTF-8', 0, 1);
	$text = trim((string) preg_replace('/\s+/u', ' ', (string) $text));
	$textlen = function_exists('mb_strlen') ? mb_strlen($text, 'UTF-8') : strlen($text);
	$wordcount = preg_match_all('/\p{L}{2,}/u', $text);

	// Collect <img> tags
	$matches = array();
	preg_match_all('/<img\b[^>]*>/i', $html, $matches);
	$imgtags = $matches[0];
	$nbimg = count($imgtags);

	// Nothing usable at all: this is not a signature
	if ($textlen == 0 && $nbimg == 0) {
		$checks['content']['status'] = 'error';
		$checks['text_image_ratio']['status'] = 'error';
		$checks['text_length']['status'] = 'error';
		$checks['contact_info']['status'] = 'error';
		return array('score' => 0, 'grade' => 'poor', 'checks' => $checks);
	}

	// Real content: a text-only signature must carry at least a couple of words (a name)
	$insufficientcontent = ($nbimg == 0 && ($textlen < $mintextlen || $wordcount < 2));
	if ($insufficientcontent) {
		$set('content', 'error', 0);
	}

	// Executable / non portable markup
	if (preg_match('/<script\b/i', $html)) {
		$set('script_tag', 'error', $w['script_tag']);
	}
	if (preg_match('/<style\b/i', $html) || preg_match('/<link\b[^>]*stylesheet/i', $html)) {
		$set('style_block', 'warning', $w['style_block']);
	}

	// Per image inspection
	$nbimgnoalt = 0;
	$hasnonabsolute = false;
	$hasheavyimg = false;
	foreach ($imgtags as $tag) {
		$src = '';
		if (preg_match('/\ssrc\s*=\s*("|\')(.*?)\1/is', $tag, $ms)) {
			$src = trim(html_entity_decode($ms[2], ENT_QUOTES, 'UTF-8'));
		}
		// Accepted: https://... , protocol relative //... , inline data:
		if ($src === '' || (!preg_match('#^(https:)?//#i', $src) && strpos($src, 'data:') !== 0)) {
			$hasnonabsolute = true;
		}
		if (strpos($src, 'data:') === 0) {
			$hasheavyimg = true; // inline base64 is reloaded in every single mail
		}
		if (!preg_match('/\salt\s*=\s*("|\')(.*?)\1/is', $tag) && !preg_match('/\salt\s*=\s*[^"\'\s>]+/i', $tag)) {
			$nbimgnoalt++;
		}
		if (preg_match('/\swidth\s*=\s*("|\')?\s*(\d+)/i', $tag, $mw) && (int) $mw[2] > $maximgwidth) {
			$hasheavyimg = true;
		}
		if (preg_match('/width\s*:\s*(\d+)\s*px/i', $tag, $mw2) && (int) $mw2[1] > $maximgwidth) {
			$hasheavyimg = true;
		}
	}

	if ($hasnonabsolute) {
		$set('non_absolute_url', 'error', $w['non_absolute_url']);
	}

	// Balance between real text and images
	if ($nbimg > 0) {
		$ratio = $textlen / $nbimg;
		if ($textlen < 10 || $ratio < 20) {
			$set('text_image_ratio', 'error', $w['text_image_ratio_error']);
		} elseif ($ratio < $mincharsperimg) {
			$set('text_image_ratio', 'warning', $w['text_image_ratio_warning']);
		}
	}

	// Too many images
	if ($nbimg > 2 * $maximg) {
		$set('image_count', 'error', $w['image_count_error']);
	} elseif ($nbimg > $maximg) {
		$set('image_count', 'warning', $w['image_count_warning']);
	}

	// Images without alt text
	if ($nbimgnoalt > 0) {
		$set('image_alt', 'warning', min($w['image_alt_cap'], $nbimgnoalt * $w['image_alt_per_image']));
	}

	// Heavy / oversized images
	if ($hasheavyimg) {
		$set('image_weight', 'warning', $w['image_weight']);
	}

	// Too many links
	$nblinks = preg_match_all('/<a\b[^>]*\shref\s*=/i', $html);
	if ($nblinks > $maxlinks) {
		$set('link_count', 'warning', $w['link_count']);
	}

	// Overly long text
	if ($textlen > $maxtextlen) {
		$set('text_length', 'warning', $w['text_length']);
	}

	// No reachable contact detail
	$hascontact = (preg_match('/mailto:/i', $html) || preg_match('/tel:/i', $html)
		|| preg_match('/[\w.+-]+@[\w-]+\.[\w.-]+/', $text) || preg_match('/[\w.+-]+@[\w-]+\.[\w.-]+/', $html));
	if (!$hascontact) {
		$set('contact_info', 'warning', $w['contact_info']);
	}

	// Oversized fixed layout width (breaks on mobile)
	$fixedwidth = false;
	if (preg_match_all('/<(?:table|td|div)\b[^>]*\swidth\s*=\s*("|\')?\s*(\d+)/i', $html, $mfw)) {
		foreach ($mfw[2] as $wv) {
			if ((int) $wv > $maximgwidth) {
				$fixedwidth = true;
				break;
			}
		}
	}
	if ($fixedwidth) {
		$set('fixed_width', 'warning', $w['fixed_width']);
	}

	if ($insufficientcontent) {
		$score = min($score, $insufficientcontentcap);
	}

	$score = max(0, min(100, $score));

	if ($score >= 85) {
		$grade = 'excellent';
	} elseif ($score >= 65) {
		$grade = 'good';
	} elseif ($score >= 40) {
		$grade = 'fair';
	} else {
		$grade = 'poor';
	}

	return array('score' => (int) $score, 'grade' => $grade, 'checks' => $checks);
}


/**
 * Build a ready to print HTML badge summarising the quality of an email signature,
 * with a collapsible list of the best practices that are not met.
 *
 * @param	string		$html		Raw HTML content of the signature
 * @param	?Translate	$outputlangs	Language object for output (defaults to global $langs)
 * @return	string					HTML snippet, empty string if the signature is empty
 */
function dolGetSignatureQualityBadge($html, $outputlangs = null)
{
	global $langs;

	if (!is_object($outputlangs)) {
		$outputlangs = $langs;
	}
	$outputlangs->load('users');

	if (trim((string) $html) === '') {
		return '';
	}

	$res = dolCheckSignatureQuality($html);

	$gradecolors = array(
		'excellent' => '#1a9850',
		'good' => '#66bd63',
		'fair' => '#f46d43',
		'poor' => '#d73027',
	);
	$color = isset($gradecolors[$res['grade']]) ? $gradecolors[$res['grade']] : '#999999';
	$gradelabel = $outputlangs->trans('SignatureQuality'.ucfirst($res['grade']));

	$out = '<div class="signaturequalityscore paddingtop">';
	$out .= '<span class="badge" style="background-color: '.$color.';" title="'.dol_escape_htmltag($outputlangs->trans('SignatureQualityTooltip')).'">';
	$out .= dol_escape_htmltag($outputlangs->trans('SignatureQuality')).': '.dol_escape_htmltag($gradelabel).' &mdash; '.((int) $res['score']).'/100';
	$out .= '</span>';

	$issues = array();
	foreach ($res['checks'] as $code => $check) {
		if ($check['status'] === 'ok') {
			continue;
		}
		$key = 'SignatureCheck'.str_replace(' ', '', ucwords(str_replace('_', ' ', $code)));
		$advkey = 'SignatureAdvice'.str_replace(' ', '', ucwords(str_replace('_', ' ', $code)));
		$icon = ($check['status'] === 'error' ? img_picto('', 'error') : img_picto('', 'warning'));
		$issues[] = '<li>'.$icon.' <strong>'.dol_escape_htmltag($outputlangs->trans($key)).'</strong> &mdash; '.dol_escape_htmltag($outputlangs->trans($advkey)).'</li>';
	}

	if (count($issues)) {
		$out .= ' <details class="signaturequalitydetails inline-block">';
		$out .= '<summary class="cursorpointer">'.dol_escape_htmltag($outputlangs->trans('SignatureQualityDetails')).'</summary>';
		$out .= '<ul class="nomargin">'.implode('', $issues).'</ul>';
		$out .= '</details>';
	} else {
		$out .= ' <span class="opacitymedium">'.dol_escape_htmltag($outputlangs->trans('SignatureQualityNoIssue')).'</span>';
	}

	$out .= '</div>';

	return $out;
}
