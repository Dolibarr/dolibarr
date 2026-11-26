<?php
/* Copyright (C) 2026		John BOTELLA
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
 * \file       htdocs/admin/tools/ui/resources/hidden-conf.lib.php
 * \brief      Functions to output the documentation page of hidden configuration constants
 */

require_once __DIR__.'/hidden-conf-list.lib.php';

/**
 * Base url of the wiki page the sections come from
 */
const HIDDENCONF_WIKI_URL = 'https://wiki.dolibarr.org/index.php/Setup_Other';

/**
 * Return the translation of a key of hiddenconf.lang, or an empty string if the key does not exist
 *
 * @param	string	$key	Translation key
 * @return	string			Translated text, not escaped
 */
function hiddenConfTrans($key)
{
	global $langs;

	$text = $langs->transnoentitiesnoconv($key);

	return $text === $key ? '' : $text;
}

/**
 * Return a title or description of hiddenconf.lang ready for output (only b, i and code tags are kept)
 *
 * @param	string	$text	Text from hiddenconf.lang
 * @return	string			HTML
 */
function hiddenConfPrintText($text)
{
	// Allowed tags (without attribute) are replaced by markers, the text is escaped, then markers are restored
	$allowed = array('<code>', '</code>', '<b>', '</b>', '<i>', '</i>');
	$markers = array();
	foreach (array_keys($allowed) as $i) {
		$markers[] = '[[HIDDENCONFTAG'.$i.']]';
	}

	return str_replace($markers, $allowed, dol_escape_htmltag(str_replace($allowed, $markers, $text)));
}

/**
 * Apply default values to a conf entry of getListOfHiddenConf()
 * Title, description and warning come from the keys HiddenConfTitle_<NAME>, HiddenConfDesc_<NAME> and HiddenConfWarning_<NAME> of hiddenconf.lang
 *
 * @param	string					$name	Name of the constant
 * @param	array<string, mixed>	$conf	Conf entry
 * @return	array{name: string, title: string, description: string, warning: string, type: string, example: string, added_in: string, removed_in: string, status: string, setup_url: string, deprecated: bool, tags: string[]}
 */
function hiddenConfNormalize($name, $conf)
{
	$status = (string) ($conf['status'] ?? 'hidden');
	if (!in_array($status, array('hidden', 'in_setup', 'removed'), true)) {
		$status = 'hidden';
	}

	return array(
		'name' => $name,
		'title' => hiddenConfTrans('HiddenConfTitle_'.$name),
		'description' => hiddenConfTrans('HiddenConfDesc_'.$name),
		'warning' => hiddenConfTrans('HiddenConfWarning_'.$name),
		'type' => (string) ($conf['type'] ?? 'bool'),
		'example' => (string) ($conf['example'] ?? ''),
		'added_in' => (string) ($conf['added_in'] ?? ''),
		'removed_in' => (string) ($conf['removed_in'] ?? ''),
		'status' => $status,
		'setup_url' => (string) ($conf['setup_url'] ?? ''),
		'deprecated' => !empty($conf['deprecated']),
		'tags' => array_values(array_unique(array_map('strval', (array) ($conf['tags'] ?? array())))),
	);
}

/**
 * Return the current value of a constant if the current user is allowed to see it
 *
 * @param	string	$name	Name of the constant
 * @return	string|null		Null if constant is not set, value (masked for secrets) otherwise
 */
function hiddenConfCurrentValue($name)
{
	global $conf, $user;

	if (empty($user->admin) || !isset($conf->global->$name) || (string) $conf->global->$name === '') {
		return null;
	}
	if (preg_match('/(PASS|PWD|SECRET|TOKEN|KEY)/', $name)) {
		return '********';
	}

	return (string) $conf->global->$name;
}

/**
 * Output the warning listing the constants currently set that are removed or deprecated
 *
 * @param	array<string, array{label: string, wiki_anchor: string, confs: array<string, array<string, mixed>>}>	$data	List of hidden conf
 * @return	void
 */
function hiddenConfPrintObsoleteWarning($data)
{
	global $langs;

	$obsoletes = array();
	foreach ($data as $section) {
		foreach ($section['confs'] as $name => $item) {
			$item = hiddenConfNormalize($name, $item);
			if (($item['status'] == 'removed' || $item['deprecated']) && hiddenConfCurrentValue($name) !== null) {
				$obsoletes[$name] = '<a href="#hiddenconf-'.dolPrintHTMLForAttribute($name).'">'.dolPrintHTML($name).'</a>';
			}
		}
	}

	if (!empty($obsoletes)) {
		print '<div class="warning">'.$langs->trans('HiddenConfObsoleteSet').' '.implode(', ', $obsoletes).'</div>';
	}
}

/**
 * Output all sections of hidden conf
 *
 * @param	array<string, array{label: string, wiki_anchor: string, confs: array<string, array<string, mixed>>}>	$data	List of hidden conf
 * @return	void
 */
function hiddenConfPrintList($data)
{
	global $langs;

	foreach ($data as $sectionKey => $section) {
		print '<div class="documentation-section hiddenconf-section" id="hiddenconf-section-'.dolPrintHTMLForAttribute($sectionKey).'">';
		print '<h2 class="documentation-title">'.$langs->trans($section['label']);
		print ' <a class="hiddenconf-wiki" href="'.HIDDENCONF_WIKI_URL.'#'.dolPrintHTMLForAttribute($section['wiki_anchor']).'" target="_blank" rel="noopener" title="'.dolPrintHTMLForAttribute($langs->trans('HiddenConfSeeWiki')).'"><span class="fas fa-external-link-alt"></span></a>';
		print '</h2>';

		print '<div class="conf-list">';
		foreach ($section['confs'] as $name => $item) {
			hiddenConfPrintItem(hiddenConfNormalize($name, $item), $section['wiki_anchor']);
		}
		print '</div>';
		print '</div>';
	}
}

/**
 * Output one conf entry
 *
 * @param	array{name: string, title: string, description: string, warning: string, type: string, example: string, added_in: string, removed_in: string, status: string, setup_url: string, deprecated: bool, tags: string[]}	$item		Normalized conf entry
 * @param	string		$wikiAnchor	Anchor of the section in the wiki page
 * @return	void
 */
function hiddenConfPrintItem($item, $wikiAnchor)
{
	global $langs;

	$value = hiddenConfCurrentValue($item['name']);

	print '<div class="conf-item conf-status-'.$item['status'].'" id="hiddenconf-'.dolPrintHTMLForAttribute($item['name']).'">';

	print '<div class="conf-header">';
	print '<span class="conf-name">'.dolPrintHTML($item['name']).'</span>';
	print '<span class="conf-badges">'.hiddenConfBadges($item, $value).'</span>';
	print '</div>';

	print '<div class="conf-body">';
	if ($item['description'] !== '') {
		print '<details class="conf-details">';
		print '<summary class="conf-title">'.hiddenConfPrintText($item['title'] !== '' ? $item['title'] : $langs->transnoentitiesnoconv('Description')).'</summary>';
		print '<div class="conf-desc">'.hiddenConfPrintText($item['description']).'</div>';
		print '</details>';
	} elseif ($item['title'] !== '') {
		print '<div class="conf-title">'.hiddenConfPrintText($item['title']).'</div>';
	}
	if ($item['warning'] !== '') {
		print '<div class="conf-warning"><span class="fas fa-exclamation-triangle paddingright"></span>'.hiddenConfPrintText($item['warning']).'</div>';
	}

	$type = $item['type'];
	if ($type == 'bool') {
		$type .= ' (0 | 1)';
	}

	print '<div class="conf-meta">';
	print hiddenConfMeta($langs->trans('Type'), $type);
	print hiddenConfMeta($langs->trans('Example'), $item['example']);
	print hiddenConfMeta($langs->trans('AddedInVersion'), $item['added_in']);
	print hiddenConfMeta($langs->trans('HiddenConfRemovedInVersion'), $item['removed_in']);
	print hiddenConfMeta($langs->trans('HiddenConfCurrentValue'), (string) $value);
	if ($item['status'] == 'in_setup' && $item['setup_url'] !== '') {
		print '<div class="conf-meta-line"><a href="'.DOL_URL_ROOT.dolPrintHTMLForAttribute($item['setup_url']).'">'.$langs->trans('HiddenConfOpenSetupPage').'</a></div>';
	}
	if ($item['description'] === '') {
		print '<div class="conf-meta-line"><a href="'.HIDDENCONF_WIKI_URL.'#'.dolPrintHTMLForAttribute($wikiAnchor).'" target="_blank" rel="noopener">'.$langs->trans('HiddenConfSeeWiki').'</a></div>';
	}
	print '</div>';

	print '</div>';
	print '</div>';
}

/**
 * Return html of one meta information line
 *
 * @param	string	$label	Label
 * @param	string	$value	Value, line is not output if empty
 * @return	string
 */
function hiddenConfMeta($label, $value)
{
	if ($value === '') {
		return '';
	}

	return '<div class="conf-meta-line"><span class="meta-label">'.dolPrintHTML($label).':</span> <span class="meta-value">'.dolPrintHTML($value).'</span></div>';
}

/**
 * Return html of badges of a conf entry (status, deprecated, current value, tags)
 *
 * @param	array{name: string, title: string, description: string, warning: string, type: string, example: string, added_in: string, removed_in: string, status: string, setup_url: string, deprecated: bool, tags: string[]}	$item	Normalized conf entry
 * @param	?string		$value	Current value (null if not set or not visible)
 * @return	string
 */
function hiddenConfBadges($item, $value)
{
	global $langs;

	$html = '';
	if ($value !== null) {
		$html .= '<span class="badge badge-pill badge-success">'.$langs->trans('HiddenConfIsSet').'</span> ';
	}
	if ($item['status'] == 'removed') {
		$html .= '<span class="badge badge-pill badge-danger">'.$langs->trans('HiddenConfRemoved').'</span> ';
	} elseif ($item['status'] == 'in_setup') {
		$html .= '<span class="badge badge-pill badge-primary">'.$langs->trans('HiddenConfInSetup').'</span> ';
	}
	if ($item['warning'] !== '') {
		$html .= '<span class="badge badge-pill badge-warning">'.$langs->trans('HiddenConfRemark').'</span> ';
	}
	if ($item['deprecated']) {
		$html .= '<span class="badge badge-pill badge-warning">'.$langs->trans('Deprecated').'</span> ';
	}

	$tagclasses = array('security' => 'badge-danger', 'danger' => 'badge-danger', 'ui' => 'badge-info', 'pdf' => 'badge-secondary');
	foreach ($item['tags'] as $tag) {
		$html .= '<span class="badge badge-pill '.($tagclasses[$tag] ?? 'badge-light').'">'.dolPrintHTML($tag).'</span> ';
	}

	return $html;
}
