<?php
/* Copyright (C) 2026 Marcel
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

/**
 * Store a bounded, redirect-specific result notice without changing standard event messages.
 * @param array{subscription:int,invoice:int,sent:int} $counts Result counters
 * @param int $owner Current user ID
 * @param int $entity Current entity
 * @return string Opaque redirect identifier
 */
function subscriptionOptionsStoreResultNotice($counts, $owner, $entity)
{
	$token = bin2hex(random_bytes(16));
	if (empty($_SESSION['subscription_options_result_notice'])) {
		$_SESSION['subscription_options_result_notice'] = array();
	}
	if (count($_SESSION['subscription_options_result_notice']) >= 5) {
		array_shift($_SESSION['subscription_options_result_notice']);
	}
	$_SESSION['subscription_options_result_notice'][$token] = array('counts' => $counts, 'owner' => (int) $owner, 'entity' => (int) $entity);
	return $token;
}

/**
 * Consume a result only on its matching redirect, validating ownership and counters.
 * @param mixed $token Redirect identifier; non-string values are rejected with null
 * @param int $owner Current user ID
 * @param int $entity Current entity
 * @return array{subscription:int,invoice:int,sent:int}|null
 */
function subscriptionOptionsTakeResultNotice($token, $owner, $entity)
{
	if (!is_string($token) || !preg_match('/^[a-f0-9]{32}$/D', $token) || !isset($_SESSION['subscription_options_result_notice'][$token])) {
		return null;
	}
	$notice = $_SESSION['subscription_options_result_notice'][$token];
	unset($_SESSION['subscription_options_result_notice'][$token]);
	if (!is_array($notice) || ($notice['owner'] ?? null) !== (int) $owner || ($notice['entity'] ?? null) !== (int) $entity || !isset($notice['counts']) || !is_array($notice['counts'])) {
		return null;
	}
	foreach (array('subscription', 'invoice', 'sent') as $key) {
		if (!isset($notice['counts'][$key]) || !is_int($notice['counts'][$key]) || $notice['counts'][$key] < 0) {
			return null;
		}
	}
	return array_intersect_key($notice['counts'], array('subscription' => 0, 'invoice' => 0, 'sent' => 0));
}

/**
 * Render a persistent success notice, retaining readable HTML if JavaScript or jNotify is unavailable.
 * @param array{subscription:int,invoice:int,sent:int} $counts Validated result counters
 * @return string HTML
 */
function subscriptionOptionsRenderResultNotice($counts)
{
	global $langs, $conf;

	$message = dol_escape_htmltag($langs->transnoentities('SubscriptionBulkSummary', $counts['subscription'], $counts['invoice'], $counts['sent']));
	$close = dol_escape_htmltag($langs->transnoentities('Close'));
	$usejnotify = !empty($conf->use_javascript_ajax) && !getDolGlobalString('MAIN_DISABLE_JQUERY_JNOTIFY') && !defined('DISABLE_JQUERY_JNOTIFY');
	$html = '<div id="subscription-options-result" class="ok clearboth" role="status">';
	$html .= '<button type="button" class="floatright" hidden aria-label="'.$close.'" title="'.$close.'">&times;</button>';
	$html .= '<span>'.$message.'</span></div>';
	$html .= '<script nonce="'.getNonce().'">
	document.addEventListener("DOMContentLoaded", function () {
		var fallback = document.getElementById("subscription-options-result");
		if (!fallback) return;
		var close = fallback.querySelector("button");
		close.hidden = false;
		close.addEventListener("click", function () { fallback.remove(); });
		if ('.($usejnotify ? 'true' : 'false').' && window.jQuery && typeof window.jQuery.jnotify === "function") {
			window.jQuery.jnotify(\''.dol_escape_js($message).'\', {
				type: "ok",
				sticky: true,
				showClose: true,
				fadeSpeed: 0,
				slideSpeed: 0,
				create: function (note) {
					note.attr("role", "status");
					note.find("a.jnotify-close").attr({
						role: "button",
						tabindex: "0",
						"aria-label": close.getAttribute("aria-label")
					}).on("keydown", function (event) {
						if (event.key === "Enter" || event.key === " ") {
							event.preventDefault();
							this.click();
						}
					});
				}
			});
			fallback.remove();
		}
	});
	</script>';
	return $html;
}

/**
 * Resolve only the three supported tokens, without evaluating template code.
 * @param string $template Plain text template (at most 1024 characters)
 * @param string $type Verified membership type label
 * @param int|null $start Verified start date
 * @param int|null $end Verified end date
 * @return string Plain text description, empty when required dates are unavailable
 * @throws InvalidArgumentException
 */
function subscriptionOptionsDescription($template, $type, $start, $end)
{
	if (!is_string($template) || dol_strlen($template) > 1024 || preg_match('/[\p{Cc}\p{Cf}]/u', $template) !== 0) {
		throw new InvalidArgumentException('SubscriptionBulkDescriptionInvalid');
	}
	if ((strpos($template, '{Start date}') !== false && $start <= 0) || (strpos($template, '{End date}') !== false && $end <= 0)) {
		return '';
	}
	return strtr($template, array(
		'{Member type}' => $type,
		'{Start date}' => $start > 0 ? dol_print_date($start, 'day') : '',
		'{End date}' => $end > 0 ? dol_print_date($end, 'day') : '',
	));
}

/**
 * Count processable rows from the current validation result, without SQL.
 * @param array<int,array<string,mixed>> $rows Preview rows
 * @return array{selected:int,ready:int,skipped:int}
 */
function subscriptionOptionsPreviewCounts($rows)
{
	$counts = array('selected' => count($rows), 'ready' => 0, 'skipped' => 0);
	foreach ($rows as $row) {
		$counts[empty($row['errors']) && in_array($row['status'], array('ready', 'warning'), true) ? 'ready' : 'skipped']++;
	}
	return $counts;
}

/**
 * Read a date selector without accepting PHP's normalization of invalid dates.
 * @param string $prefix Selector prefix
 * @return int Timestamp or zero for invalid input
 */
function subscriptionOptionsReadDate($prefix)
{
	$year = GETPOSTINT($prefix.'year');
	$month = GETPOSTINT($prefix.'month');
	$day = GETPOSTINT($prefix.'day');
	return $year >= 1970 && $year <= 9998 && checkdate($month, $day, $year) ? GETPOSTDATE($prefix) : 0;
}

/**
 * Preserve manual ends; only recalculate an automatic end when its start changes.
 * @param array<string,mixed> $previous Previous server-side preview row
 * @param int $start Submitted start
 * @param int $end Submitted end
 * @param bool $recalculate Explicitly reset the end to its suggestion
 * @param int|null $common Explicitly applied common start, null otherwise
 * @param bool $calendar Explicitly replace even manual ends with the configured calendar rule
 * @return array{start:int,end:int|null,manualend:bool}
 */
function subscriptionOptionsPeriodInput($previous, $start, $end, $recalculate = false, $common = null, $calendar = false)
{
	$manual = !empty($previous['manualend']) || $end !== $previous['end'];
	if ($common !== null) {
		$start = $common;
	}
	if ($recalculate || (!$manual && $start !== $previous['start'])) {
		$end = null;
		$manual = false;
	}
	if ($calendar) {
		$calendarend = Adherent::subscriptionCalendarEndForBatch($start);
		if ($calendarend !== null) {
			$end = $calendarend;
			$manual = true;
		}
	}
	return array('start' => $start, 'end' => $end, 'manualend' => $manual);
}

/**
 * Summarize differences in actual displayed periods, durations and amounts.
 * @param array<int,array<string,mixed>> $rows Preview rows
 * @return string[] Translation keys
 */
function subscriptionOptionsPreviewWarnings($rows)
{
	$periods = $durations = $amounts = array();
	foreach ($rows as $row) {
		if ($row['start'] && $row['end']) {
			$periods[$row['start'].':'.$row['end']] = true;
		}
		if ($row['quantity']) {
			$durations[$row['quantity'].$row['unit']] = true;
		}
		if ($row['amount'] !== null) {
			$amounts[(string) $row['amount']] = true;
		}
	}
	$warnings = array();
	if (count($periods) > 1 || count($durations) > 1) {
		$warnings[] = 'SubscriptionBulkDifferentPeriods';
	}
	if (count($amounts) > 1) {
		$warnings[] = 'SubscriptionBulkDifferentAmounts';
	}
	return $warnings;
}

/**
 * Render editable dates and escaped display values in the existing confirm form.
 * @param Form $form Form renderer
 * @param array<int,array<string,mixed>> $rows Authorized preview rows
 * @return string HTML
 */
function subscriptionOptionsRenderPreview($form, $rows)
{
	global $langs, $conf;
	$html = '';
	$noticegrid = 'display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,24rem),1fr));gap:.5rem;margin:.5rem 0';
	$noticestyle = 'margin:0;min-width:0;overflow-wrap:anywhere';
	$warnings = subscriptionOptionsPreviewWarnings($rows);
	if ($warnings) {
		$html .= '<div class="subscription-options-notices" style="'.$noticegrid.'">';
		foreach ($warnings as $warning) {
			$html .= '<div class="warning" style="'.$noticestyle.'">'.dol_escape_htmltag($langs->trans($warning)).'</div>';
		}
		$html .= '</div>';
	}
	$html .= '<div class="subscription-options-notices" style="'.$noticegrid.'">';
	$monthend = getDolGlobalInt('MEMBER_SUBSCRIPTION_SUGGEST_END_OF_MONTH');
	$yearend = getDolGlobalInt('MEMBER_SUBSCRIPTION_SUGGEST_END_OF_YEAR');
	if ($monthend) {
		$noticekey = $yearend ? 'SubscriptionBulkCalendarEndBothInfo' : 'SubscriptionBulkCalendarEndMonthInfo';
	} elseif ($yearend) {
		$noticekey = 'SubscriptionBulkCalendarEndYearInfo';
	} else {
		$noticekey = '';
	}
	if ($noticekey !== '') {
		$html .= '<div class="info" style="'.$noticestyle.'">'.dol_escape_htmltag($langs->transnoentities($noticekey));
		$html .= '<br><button class="button small" style="white-space:normal;max-width:100%;margin:.5rem 0 0" type="submit" name="applysubscriptioncalendar" value="1">'.dol_escape_htmltag($langs->trans('SubscriptionBulkApplyCalendar')).'</button></div>';
	}
	$html .= '<div class="info" style="'.$noticestyle.'">'.dol_escape_htmltag($langs->trans('SubscriptionBulkDateHelp')).'</div></div>';
	$counts = subscriptionOptionsPreviewCounts($rows);
	$html .= '<div class="small marginbottomonly">'.dol_escape_htmltag($langs->transnoentities('SubscriptionBulkPreviewCounts', $counts['selected'], $counts['ready'], $counts['skipped'])).'</div>';
	$html .= '<div class="div-table-responsive"><table class="noborder centpercent"><thead><tr class="liste_titre">';
	foreach (array('Member', 'Type', 'Amount', 'DateSubscription', 'DateEndSubscription', 'ThirdParty', 'Status') as $column) {
		$html .= '<th>'.dol_escape_htmltag($langs->trans($column)).'</th>';
	}
	$html .= '</tr></thead><tbody>';
	$singular = array('s' => 'Second', 'mn' => 'Minute', 'i' => 'Minute', 'h' => 'Hour', 'd' => 'Day', 'w' => 'Week', 'm' => 'Month', 'y' => 'Year');
	$plural = array('s' => 'Seconds', 'mn' => 'Minutes', 'i' => 'Minutes', 'h' => 'Hours', 'd' => 'Days', 'w' => 'Weeks', 'm' => 'Months', 'y' => 'Years');
	foreach ($rows as $id => $row) {
		$id = (int) $id;
		$units = $row['quantity'] == 1 ? $singular : $plural;
		$duration = $row['quantity'] ? $row['quantity'].' '.$langs->trans(isset($units[$row['unit']]) ? $units[$row['unit']] : $row['unit']) : '';
		$name = $row['member'] !== '' ? $row['member'] : $langs->trans('Member').' #'.$id;
		$html .= '<tr class="oddeven"><td>'.dol_escape_htmltag($name).'</td>';
		$html .= '<td>'.dol_escape_htmltag($row['type'].($duration !== '' ? ' ('.$duration.')' : '')).'</td>';
		$html .= '<td class="right nowrap">'.($row['amount'] === null ? '' : dol_escape_htmltag(price($row['amount']).' '.$conf->currency)).'</td>';
		$html .= '<td>'.$form->selectDate($row['start'] ?: -1, 'substart'.$id, 0, 0, 1, '', 1, 1).'</td>';
		$html .= '<td class="nowrap"><div style="display:inline-flex;flex-wrap:nowrap;align-items:center;gap:.25rem"><span style="flex:none">'.$form->selectDate($row['end'] ?: -1, 'subend'.$id, 0, 0, 1, '', 1, 0);
		$html .= '</span><button class="button small" style="flex:none;min-width:0;width:1.8rem;padding-left:.25rem;padding-right:.25rem;margin:0 !important;box-sizing:border-box" type="submit" name="recalculatesubscription" value="'.$id.'" title="'.dol_escape_htmltag($langs->transnoentities('Refresh')).'" aria-label="'.dol_escape_htmltag($langs->transnoentities('SubscriptionBulkRecalculateEnd').' — '.$name).'">&#8635;</button></div></td>';
		$html .= '<td>'.dol_escape_htmltag($row['thirdparty']).($row['autocreate'] ? '<br>'.dol_escape_htmltag($langs->trans('SubscriptionBulkThirdPartyPlanned')) : '').'</td>';
		$status = $row['status'] === 'error' ? 'SubscriptionBulkError' : ($row['status'] === 'warning' ? 'SubscriptionBulkWarning' : 'SubscriptionBulkReady');
		$html .= '<td><strong>'.dol_escape_htmltag($langs->trans($status)).'</strong>';
		foreach ($row['notices'] as $notice) {
			$html .= '<br>'.dol_escape_htmltag($langs->trans($notice));
		}
		if ($row['description'] !== '') {
			$html .= '<br><span class="opacitymedium">'.dol_escape_htmltag($langs->transnoentities('SubscriptionBulkInvoiceLine', $row['description'])).'</span>';
		}
		$html .= '</td></tr>';
	}
	return $html.'</tbody></table></div>';
}
