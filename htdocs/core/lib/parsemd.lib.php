<?php
/* Copyright (C) 2008-2023	Laurent Destailleur			<eldy@users.sourceforge.net>
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
 * or see https://www.gnu.org/
 */

/**
 *	\file			htdocs/core/lib/parsemd.lib.php
 *	\brief			This file contains functions dedicated to MD parsing.
 */

/**
 * Function to parse MD content into HTML
 *
 * @param	string	  $content			    MD content
 * @param   string    $parser               'parsedown' or 'nl2br'
 * @param   string    $replaceimagepath     Replace path to image with another path. Exemple: ('doc/'=>'xxx/aaa/')
 * @return	string                          Parsed content
 */
function dolMd2Html($content, $parser = 'parsedown', $replaceimagepath = null)
{
	// Replace a HTML string with a Markdown syntax
	$content = preg_replace('/<a href="([^"]+)">([^<]+)<\/a>/', '[\2](\1)', $content);
	//$content = preg_replace('/<a href="([^"]+)" target="([^"]+)">([^<]+)<\/a>/', '[\3](\1){:target="\2"}', $content);
	$content = preg_replace('/<a href="([^"]+)" target="([^"]+)">([^<]+)<\/a>/', '[\3](\1)', $content);

	// Replace HTML coments
	$content = preg_replace('/<!--.*-->/Ums', '', $content);	// We remove HTML comment that are not MD comment because they will be escaped and output when setSafeMode is set to true.

	if (is_array($replaceimagepath)) {
		foreach ($replaceimagepath as $key => $val) {
			$keytoreplace = ']('.$key;
			$valafter = ']('.$val;
			$content = preg_replace('/'.preg_quote($keytoreplace, '/').'/m', $valafter, $content);
		}
	}
	if ($parser == 'parsedown') {
		include_once DOL_DOCUMENT_ROOT.'/includes/parsedown/Parsedown.php';
		$parsedown = new Parsedown();
		$parsedown->setSafeMode(true);		// This will escape HTML link <a href=""> into html entities but markdown links are ok
		$content = $parsedown->text($content);
	} else {
		$content = nl2br($content);
	}

	return $content;
}


/**
 * Function to parse MD content into ASCIIDOC
 *
 * @param	string	  $content			    MD content
 * @param   string    $parser               'dolibarr'
 * @param   string    $replaceimagepath     Replace path to image with another path. Exemple: ('doc/'=>'xxx/aaa/')
 * @return	string                          Parsed content
 */
function dolMd2Asciidoc($content, $parser = 'dolibarr', $replaceimagepath = null)
{
	if (is_array($replaceimagepath)) {
		foreach ($replaceimagepath as $key => $val) {
			$keytoreplace = ']('.$key;
			$valafter = ']('.$val;
			$content = preg_replace('/'.preg_quote($keytoreplace, '/').'/m', $valafter, $content);
		}
	}
	//if ($parser == 'dolibarr')
	//{
	$content = preg_replace('/<!--.*-->/msU', '', $content);
	//}
	//else
	//{
	//    $content = $content;
	//}

	return $content;
}
