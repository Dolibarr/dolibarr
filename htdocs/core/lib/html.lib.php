<?php
/* Copyright (C) 2026	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2026       Frédéric France         <frederic.france@free.fr>
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
 * @file       htdocs/core/lib/html.lib.php
 * @brief      HTML rendering functions for Dolibarr
 *             This file contains all HTML output, formatting and display functions.
 *             It was extracted from functions.lib.php to reduce file size and improve maintainability.
 *
 * Functions included:
 * - Icon and image rendering (img_*, picto_*)
 * - HTML output functions (dolPrint*, dol_print_*)
 * - Form and list rendering (fiche, barre_liste, etc.)
 * - Button and badge rendering
 * - Formatted output (price, vatrate, dates, etc.)
 * - Error and message display
 */

/**
 * Return a string label (so on 1 line only and that should not contains any HTML) ready to be output on HTML page.
 * To use text that is not HTML content inside an attribute, you can simply use only dol_escape_htmltag(). In doubt, use dolPrintHTMLForAttribute().
 *
 * @param	string	$s						String to print
 * @param	int		$escapeonlyhtmltags		1=Escape only html tags, not the special chars like accents.
 * @return	string							String ready for HTML output
 * @see dolPrintText()
 */
function dolPrintLabel($s, $escapeonlyhtmltags = 0)
{
	return dol_escape_htmltag(dol_string_nohtmltag($s, 1, 'UTF-8', 0, 0), 0, 0, '', $escapeonlyhtmltags, 1);
}

/**
 * Return a string label (possible on several lines and that should not contains any HTML) ready to be output on HTML page.
 * To use text that is not HTML content inside an attribute, you can simply use only dol_escape_htmltag(). In doubt, use dolPrintHTMLForAttribute().
 *
 * @param	string	$s		String to print
 * @return	string			String ready for HTML output
 * @see dolPrintLabel(), dolPrintHTML()
 */
function dolPrintText($s)
{
	return dol_escape_htmltag(dol_string_nohtmltag($s, 2, 'UTF-8', 0, 0), 0, 1, '', 0, 1);
}

/**
 * Return a string (that can be on several lines) ready to be output on a HTML page.
 * To output a text inside an attribute, you can use dolPrintHTMLForAttribute() or dolPrintHTMLForTextArea() inside a textarea
 * With dolPrintHTML(), only content not already in HTML is encoded with HTML.
 *
 * @param	int|float|string	$s					String to print
 * @param	int					$allowiframe		Allow iframe tags
 * @param 	string[] 			$moreallowedtags 	Array of extra allowed tags (in addition to 'common' list)
 * @return	string									String ready for HTML output (sanitized and escape)
 * @see dolPrintHTMLForAttribute(), dolPrintHTMLFortextArea(), dolPrintText()
 */
function dolPrintHTML($s, $allowiframe = 0, $moreallowedtags = array())
{
	// If text is already HTML, we want to escape only dangerous chars else we want to escape all content.
	//$isAlreadyHTML = dol_textishtml($s);

	// dol_htmlentitiesbr encode all chars except "'" if string is not already HTML, but
	// encode only special char like accented chars but not &, <, >, ", ' if already HTML.
	$stringWithEntitesForSpecialChar = dol_htmlentitiesbr((string) $s);

	$allowedtags = 'common';
	if (!empty($moreallowedtags)) {
		$allowedtags .= ','.implode(',', $moreallowedtags);
	}
	return dol_escape_htmltag(dol_htmlwithnojs(dol_string_onlythesehtmltags($stringWithEntitesForSpecialChar, 1, 1, 1, $allowiframe, $allowedtags)), 1, 1, $allowedtags, 0, 1);
}

/**
 * Return a string ready to be output into an HTML attribute (alt, title, data-html, ...)
 * With dolPrintHTMLForAttribute(), the content is HTML encode, even if it is already HTML content.
 *
 * @param	string		$s						String to print
 * @param	int			$escapeonlyhtmltags		1=Escape only html tags, not the special chars like accents.
 * @param	string[]	$allowothertags			List of other tags allowed
 * @return	string								String ready for HTML output
 * @see dolPrintHTML(), dolPrintHTMLFortextArea()
 */
function dolPrintHTMLForAttribute($s, $escapeonlyhtmltags = 0, $allowothertags = array())
{
	$allowedtags = array('br', 'b', 'font', 'hr', 'span');
	if (!empty($allowothertags) && is_array($allowothertags)) {
		$allowedtags = array_merge($allowedtags, $allowothertags);
	}
	// The dol_htmlentitiesbr will convert simple text into html, including switching accent into HTML entities
	// The dol_escape_htmltag will escape html tags.
	if ($escapeonlyhtmltags) {
		return dol_escape_htmltag(dol_string_onlythesehtmltags($s, 1, 0, 0, 0, $allowedtags), 1, -1, '', 1, 1);
	} else {
		return dol_escape_htmltag(dol_string_onlythesehtmltags(dol_htmlentitiesbr($s), 1, 0, 0, 0, $allowedtags), 1, -1, '', 0, 1);
	}
}

/**
 * Return a string ready to be output on a href attribute (this one need a special because we need content is HTML with no way to detect it is HTML).
 * With dolPrintHTMLForAttribute(), the content is HTML encode, even if it is already HTML content.
 *
 * @param	string	$s		String to print
 * @return	string			String ready for HTML output
 * @see dolPrintHTML(), dolPrintHTMLFortextArea()
 */
function dolPrintHTMLForAttributeUrl($s)
{
	// The dol_htmlentitiesbr has been removed compared to dolPrintHTMLForAttribute because we know content is a HTML URL string (even if we have no way to detect it automatically)
	// The dol_escape_htmltag will escape html chars.
	$escapeonlyhtmltags = 1;
	return dol_escape_htmltag(dol_string_onlythesehtmltags($s, 1, 1, 1, 0, array()), 0, 0, '', $escapeonlyhtmltags, 1);
}

/**
 * Return a string ready to be output on input textarea.
 * Differs from dolPrintHTML because all tags are escape. With dolPrintHTML, all tags except common one are escaped.
 *
 * @param	string	$s				String to print
 * @param	int		$allowiframe	Allow iframe tags
 * @return	string					String ready for HTML output into a textarea
 * @see dolPrintHTML(), dolPrintHTMLForAttribute()
 */
function dolPrintHTMLForTextArea($s, $allowiframe = 0)
{
	return dol_escape_htmltag(dol_htmlwithnojs(dol_string_onlythesehtmltags(dol_htmlentitiesbr($s), 1, 1, 1, $allowiframe)), 1, 1, '', 0, 1);
}

/**
 * Return a string ready to be output on an HTML attribute (alt, title, ...)
 *
 * @param	string	$s		String to print
 * @return	string			String ready for HTML output
 */
function dolPrintPassword($s)
{
	return htmlspecialchars($s, ENT_HTML5, 'UTF-8');
}


/**
 *  Returns text escaped for inclusion in HTML alt or title or value tags, or into values of HTML input fields.
 *  When we need to output strings on pages, we should use:
 *        - dolPrintLabel...
 *        - dolPrintHTML... that is dol_escape_htmltag(dol_htmlwithnojs(dol_string_onlythesehtmltags(dol_htmlentitiesbr(...), 1, 1, 1, 0)), 1, 1, 'common', 0, 1) for notes or descriptions into textarea, add 'common' if into a html content
 *        - dolPrintPassword that is a simple htmlspecialchars(... , ENT_COMPAT, 'UTF-8') for passwords.
 *
 *  @param      string		$stringtoescape			String to escape
 *  @param		int			$keepb					1=Replace b tags with escaped value (except if in $noescapetags), 0=Remove them completely
 *  @param      int         $keepn              	1=Preserve \r\n strings, 0=Replace them with escaped value, -1=Remove them. Set to 1 when escaping for a <textarea>.
 *  @param		string		$noescapetags			'' (escape all html tags) or 'common' (do not escape some common tags) or 'common,a,b,c' or list of tags to not escape.
 *  @param		int			$escapeonlyhtmltags		1=Escape only html tags and double quotes, not the special chars like accents.
 *  @param		int			$cleanalsojavascript	Clean also javascript. @TODO switch this option to 1 by default.
 *  @return     string     				 			Escaped string
 *  @see		dol_string_nohtmltag(), dol_string_onlythesehtmltags(), dol_string_nospecial(), dol_string_unaccent(), dol_htmlentitiesbr()
 */
function dol_escape_htmltag($stringtoescape, $keepb = 0, $keepn = 0, $noescapetags = '', $escapeonlyhtmltags = 0, $cleanalsojavascript = 0)
{
	$reg = array();
	if (preg_match('/^common([a-z,]*)/', $noescapetags, $reg)) {
		$noescapetags = 'html,body,a,b,em,hr,i,u,ul,ol,li,br,div,img,font,p,span,strong,table,tr,td,th,tbody,h1,h2,h3,h4,h5,h6,h7,h8,h9';
		// Add also html5 tags
		$noescapetags .= ',header,footer,nav,section,menu,menuitem';
		if (!empty($reg[1])) {
			$noescapetags .= $reg[1];
		}
	}
	if ($cleanalsojavascript) {
		$stringtoescape = dol_string_onlythesehtmltags($stringtoescape, 0, 0, $cleanalsojavascript, 0, array(), 0);
	}

	// escape quotes and backslashes, newlines, etc.
	if ($escapeonlyhtmltags) {
		$tmp = htmlspecialchars_decode((string) $stringtoescape, ENT_COMPAT);
	} else {
		// We make a manipulation by calling the html_entity_decode() to convert content into NON HTML UTF8 string.
		// Because content can be or not already HTML.
		// For example, this decode &egrave; into its UTF-8 char so string is UTF8 (but numbers entities like &#39; is not decoded).
		// In a future, we should not need this

		$tmp = (string) $stringtoescape;

		// We protect the 6 special entities that we don't want to decode.
		$tmp = str_ireplace('&lt', '__DONOTDECODELT', $tmp);
		$tmp = str_ireplace('&gt', '__DONOTDECODEGT', $tmp);
		$tmp = str_ireplace('&amp', '__DONOTDECODEAMP', $tmp);
		$tmp = str_ireplace('&quot', '__DONOTDECODEQUOT', $tmp);
		$tmp = str_ireplace('&apos', '__DONOTDECODEAPOS', $tmp);
		$tmp = str_ireplace('&#39', '__DONOTDECODE39', $tmp);

		$tmp = html_entity_decode((string) $tmp, ENT_COMPAT, 'UTF-8');		// Convert entities into UTF8

		// We restore the 6 special entities that we don't want to have been decoded by previous command
		$tmp = str_ireplace('__DONOTDECODELT', '&lt', $tmp);
		$tmp = str_ireplace('__DONOTDECODEGT', '&gt', $tmp);
		$tmp = str_ireplace('__DONOTDECODEAMP', '&amp', $tmp);
		$tmp = str_ireplace('__DONOTDECODEQUOT', '&quot', $tmp);
		$tmp = str_ireplace('__DONOTDECODEAPOS', '&apos', $tmp);
		$tmp = str_ireplace('__DONOTDECODE39', '&#39', $tmp);

		$tmp = str_ireplace('&#39;', '__SIMPLEQUOTE__', $tmp);	// HTML 4
	}
	if (!$keepb) {
		$tmp = strtr($tmp, array("<b>" => '', '</b>' => '', '<strong>' => '', '</strong>' => ''));
	}
	if (!$keepn) {
		$tmp = strtr($tmp, array("\r" => '\\r', "\n" => '\\n'));
	} elseif ($keepn == -1) {
		$tmp = strtr($tmp, array("\r" => '', "\n" => ''));
	}

	if ($escapeonlyhtmltags) {
		$tmp = htmlspecialchars($tmp, ENT_COMPAT, 'UTF-8');
		return $tmp;
	} else {
		// Now we protect all the tags we want to keep
		$tmparrayoftags = array();
		if ($noescapetags) {
			$tmparrayoftags = explode(',', $noescapetags);
		}

		if (count($tmparrayoftags)) {
			// Now we will protect tags (defined into $tmparrayoftags) that we want to keep untouched

			$reg = array();
			// Remove reserved keywords. They are forbidden in a source string
			$tmp = str_ireplace(array('__DOUBLEQUOTE', '__BEGINTAGTOREPLACE', '__ENDTAGTOREPLACE', '__BEGINENDTAGTOREPLACE'), '', $tmp);

			foreach ($tmparrayoftags as $tagtoreplace) {
				// For case of tag without attributes '<abc>', '</abc>', '<abc />', we protect them to avoid transformation by htmlentities() later
				$tmp = preg_replace('/<' . preg_quote($tagtoreplace, '/') . '>/', '__BEGINTAGTOREPLACE' . $tagtoreplace . '__', $tmp);
				$tmp = str_ireplace('</' . $tagtoreplace . '>', '__ENDTAGTOREPLACE' . $tagtoreplace . '__', $tmp);
				$tmp = preg_replace('/<' . preg_quote($tagtoreplace, '/') . ' \/>/', '__BEGINENDTAGTOREPLACE' . $tagtoreplace . '__', $tmp);

				// For case of tag with attributes.
				// All the occurrences are protected in a single pass: the replacement string contains no '<', so it
				// can never build a new tag to protect (a loop replacing one distinct attribute string per round was
				// rescanning the whole content for each of them, so the cost was quadratic on large contents).
				$tmp = preg_replace_callback(
					'/<'.preg_quote($tagtoreplace, '/').'(\s+)([^>]+)>/',
					/**
					 * @param string[] $reg
					 * @return string
					 */
					static function ($reg) use ($tagtoreplace) {
						// We want to protect the attribute part ... in '<xxx ...>' to avoid transformation by htmlentities() later
						$tmpattributes = str_ireplace(array('[', ']'), '_', $reg[2]);	// We must never have [ ] inside the attribute string
						$tmpattributes = str_ireplace('"', '__DOUBLEQUOTE__', $tmpattributes);
						$tmpattributes = preg_replace('/[^a-z0-9_%,\/\?\;\s=&\.\-@:\.#\+]/i', '', $tmpattributes);
						//$tmpattributes = preg_replace("/float:\s*(left|right)/", "", $tmpattributes);	// Disabled: we must not remove content
						return '__BEGINTAGTOREPLACE'.$tagtoreplace.'['.$tmpattributes.']__';
					},
					$tmp
				) ?? $tmp;
			}

			$tmp = str_ireplace('&amp', '__ANDNOSEMICOLON__', $tmp);
			$tmp = str_ireplace('&quot', '__DOUBLEQUOTENOSEMICOLON__', $tmp);
			$tmp = str_ireplace('&lt', '__LESSTHAN__', $tmp);
			$tmp = str_ireplace('&gt', '__GREATERTHAN__', $tmp);
		}

		// Warning: htmlentities encode all special chars that remains (except "'" with ENT_COMPAT).
		$result = htmlentities($tmp, ENT_COMPAT, 'UTF-8');

		//print $result;

		if (count($tmparrayoftags)) {
			// Restore protected tags
			foreach ($tmparrayoftags as $tagtoreplace) {
				$result = str_ireplace('__BEGINTAGTOREPLACE' . $tagtoreplace . '__', '<' . $tagtoreplace . '>', $result);
				$result = preg_replace('/__BEGINTAGTOREPLACE' . $tagtoreplace . '\[([^\]]*)\]__/', '<' . $tagtoreplace . ' \1>', $result);
				$result = str_ireplace('__ENDTAGTOREPLACE' . $tagtoreplace . '__', '</' . $tagtoreplace . '>', $result);
				$result = str_ireplace('__BEGINENDTAGTOREPLACE' . $tagtoreplace . '__', '<' . $tagtoreplace . ' />', $result);
				$result = preg_replace('/__BEGINENDTAGTOREPLACE' . $tagtoreplace . '\[([^\]]*)\]__/', '<' . $tagtoreplace . ' \1 />', $result);
			}

			$result = str_ireplace('__DOUBLEQUOTE__', '"', $result);

			$result = str_ireplace('__ANDNOSEMICOLON__', '&amp', $result);
			$result = str_ireplace('__DOUBLEQUOTENOSEMICOLON__', '&quot', $result);
			$result = str_ireplace('__LESSTHAN__', '&lt', $result);
			$result = str_ireplace('__GREATERTHAN__', '&gt', $result);
		}

		$result = str_ireplace('__SIMPLEQUOTE__', '&#39;', $result);

		//$result="\n\n\n".var_export($tmp, true)."\n\n\n".var_export($result, true);

		return $result;
	}
}


/**
 * Create a dialog with two buttons for export and overwrite of a website
 *
 * @param 	string $name          	Unique identifier for the dialog
 * @param 	string $label         	Title of the dialog
 * @param 	string $buttonstring  	Text for the button that opens the dialog
 * @param 	string $exportSiteName 	Name of the "submit" input for site export
 * @param 	string $overwriteGitUrl URL for the link that triggers the overwrite action in GIT
 * @param	Website	$website		Website object
 * @return 	string               	HTML and JavaScript code for the button and the dialog
 */
function dolButtonToOpenExportDialog($name, $label, $buttonstring, $exportSiteName, $overwriteGitUrl, $website)
{
	global $langs, $db;

	$form = new Form($db);

	$templatenameforexport = $website->name_template;	// Example 'website_template-corporate'
	if (empty($templatenameforexport)) {
		$templatenameforexport = 'website_' . $website->ref;
	}

	$out = '';
	$out .= '<input type="button" class="cursorpointer button bordertransp" id="open-dialog-' . $name . '"  value="' . dol_escape_htmltag($buttonstring) . '"/>';

	// for generate popup
	$out .= '<script nonce="' . getNonce() . '" type="text/javascript">';
	$out .= 'jQuery(document).ready(function () {';
	$out .= '  jQuery("#open-dialog-' . $name . '").click(function () {';
	$out .= '    var dialogHtml = \'';

	$dialogcontent = '      <div id="custom-dialog-' . $name . '">';
	$dialogcontent .= '        <div style="margin-top: 20px;">';
	$dialogcontent .= '          <label for="export-site-' . $name . '"><strong>' . $langs->trans("ExportSiteLabel") . '...</label><br>';
	$dialogcontent .= '          <button class="button smallpaddingimp" id="export-site-' . $name . '">' . dol_escape_htmltag($langs->trans("DownloadZip")) . '</button>';
	$dialogcontent .= '        </div>';
	$dialogcontent .= '        <br>';
	$dialogcontent .= '        <div style="margin-top: 20px;">';
	$dialogcontent .= '          <strong>' . $langs->trans("ExportSiteGitLabel") . ' ' . $form->textwithpicto('', $langs->trans("SourceFiles"), 1, 'help', '', 0, 3, '') . '</strong><br>';
	$dialogcontent .= '     		<form action="' . dol_escape_htmltag($overwriteGitUrl) . '" method="POST">';
	$dialogcontent .= '        		<input type="hidden" name="action" value="overwritesite">';
	$dialogcontent .= '        		<input type="hidden" name="token" value="' . newToken() . '">';
	$dialogcontent .= '          		<input type="text" autofocus name="export_path" id="export-path-' . $name . '" placeholder="' . $langs->trans('ExportPath') . '" style="width:400px " value="' . dol_escape_htmltag($templatenameforexport) . '"/><br>';
	$dialogcontent .= '          		<button type="submit" class="button smallpaddingimp" id="overwrite-git-' . $name . '">' . dol_escape_htmltag($langs->trans("ExportIntoGIT")) . '</button>';
	$dialogcontent .= '      		</form>';
	$dialogcontent .= '        </div>';
	$dialogcontent .= '      </div>';

	$out .= dol_escape_js($dialogcontent);

	$out .= '\';';


	// Add the content of the dialog to the body of the page
	$out .= '    var $dialog = jQuery("#custom-dialog-' . $name . '");';
	$out .= ' if ($dialog.length > 0) {
		$dialog.remove();
	}
	jQuery("body").append(dialogHtml);';

	// Configuration of popup
	$out .= '    jQuery("#custom-dialog-' . $name . '").dialog({';
	$out .= '      autoOpen: false,';
	$out .= '      modal: true,';
	$out .= '      height: 290,';
	$out .= '      width: "40%",';
	$out .= '      title: "' . dol_escape_js($label) . '",';
	$out .= '    });';

	// Simulate a click on the original "submit" input to export the site.
	$out .= '    jQuery("#export-site-' . $name . '").click(function () {';
	$out .= '      console.log("Clic on exportsite.");';
	$out .= '      var target = jQuery("input[name=\'' . dol_escape_js($exportSiteName) . '\']");';
	$out .= '      console.log("element founded:", target.length > 0);';
	$out .= '      if (target.length > 0) { target.click(); }';
	$out .= '      jQuery("#custom-dialog-' . $name . '").dialog("close");';
	$out .= '    });';

	// open popup
	$out .= '    jQuery("#custom-dialog-' . $name . '").dialog("open");';
	$out .= '    return false;';
	$out .= '  });';
	$out .= '});';
	$out .= '</script>';

	return $out;
}


/**
 *	Return HTML code to output a button to open a dialog popup box.
 *  Such buttons must be included inside a HTML form.
 *
 *	@param	string	$name				A name for the html component
 *	@param	string	$label 	    		Label shown in Popup title top bar
 *	@param  string	$buttonstring  		button string (HTML text we can click on)
 *	@param  string	$url				Relative Url to open. For example '/project/card.php'
 *  @param	string	$disabled			Disabled text
 *  @param	string	$morecss			More CSS
 *  @param	string	$jsonopen			Some JS code to execute on click/open of popup
 *  @param	string	$jsonclose			Some JS code to execute on close of popup
 *  									Value is 'keyforpopupid:Name_of_html_component_to_set_with id,Name_of_html_component_to_set_with_label'
 *  @param	string	$accesskey			A key to use shortcut
 * 	@return	string						HTML component with button
 */
function dolButtonToOpenUrlInDialogPopup($name, $label, $buttonstring, $url, $disabled = '', $morecss = 'classlink button bordertransp', $jsonopen = '', $jsonclose = '', $accesskey = '')
{
	global $conf;

	if (strpos($url, '?') > 0) {
		$url .= '&dol_hide_topmenu=1&dol_hide_leftmenu=1&dol_openinpopup=' . urlencode($name);
	} else {
		$url .= '?dol_hide_topmenu=1&dol_hide_leftmenu=1&dol_openinpopup=' . urlencode($name);
	}

	if (preg_match('/^https/i', $url)) {
		$urltoopen = $url;
	} else {
		$urltoopen = DOL_URL_ROOT . $url;
	}

	$out = '';

	//print '<input type="submit" class="button bordertransp"'.$disabled.' value="'.dol_escape_htmltag($langs->trans("MediaFiles")).'" name="file_manager">';
	$out .= '<!-- a link for button to open url into a dialog popup -->';
	$out .= '<a ' . ($accesskey ? ' accesskey="' . $accesskey . '"' : '') . ' class="cursorpointer reposition button_' . $name . ($morecss ? ' ' . $morecss : '') . '"' . $disabled . ' title="' . dol_escape_htmltag($label) . '"';
	if (empty($conf->use_javascript_ajax)) {
		$out .= ' href="' . $urltoopen . '" target="_blank"';
	} elseif ($jsonopen) {
		$out .= ' href="#" onclick="' . $jsonopen . '"';
	} else {
		$out .= ' href="#"';
	}
	$out .= '>' . $buttonstring . '</a>';

	if (!empty($conf->use_javascript_ajax)) {
		// Add code to open url using the popup.
		$out .= '<!-- code to open popup and variables to retrieve returned variables -->';
		$out .= '<div id="idfordialog' . $name . '" class="hidden">' . (getDolGlobalInt('MAIN_OPTIMIZEFORTEXTBROWSER') < 2 ? 'div for dialog' : '') . '</div>';

		$out .= '<!-- Add js code to open dialog popup on dialog -->';
		$out .= '<script nonce="' . getNonce() . '" type="text/javascript">
					jQuery(document).ready(function () {
						jQuery(".button_' . $name . '").click(function () {
							console.log(\'Open popup with jQuery(...).dialog() on URL ' . dol_escape_js($urltoopen) . '\');
							var $tmpdialog = $(\'#idfordialog' . $name . '\');
							$tmpdialog.html(\'<iframe class="iframedialog" id="iframedialog' . $name . '" style="border: 0px;" src="' . $urltoopen . '" width="100%" height="98%"></iframe>\');
							$tmpdialog.dialog({
								autoOpen: false,
							 	modal: true,
							 	height: (window.innerHeight - 150),
							 	width: \'80%\',
							 	title: \'' . dol_escape_js($label) . '\',
								open: function (event, ui) {
									console.log("open popup name=' . $name . '");
		   						},
								close: function (event, ui) {
									console.log("Popup is closed, run jsonclose = ' . $jsonclose . '");
									' . (empty($jsonclose) || preg_match('/^TODO/', $jsonclose) ? '' : $jsonclose . ';') . '
								}
							});

							$tmpdialog.dialog(\'open\');
							return false;
						});
					});
				</script>';
	}
	return $out;
}

/**
 *	Show tab header of a card
 *
 *	@param	array<int,array<int<0,5>,string>>	$links				Array of tabs (0=>url, 1=>label, 2=>code, 3=>not used, 4=>text after link, 5=>morecssonlink). Currently initialized by calling a function xxx_admin_prepare_head. Note that label into $links[$i][1] must be already HTML escaped.
 *	@param	string	$active     		Active tab name (document', 'info', 'ldap', ....)
 *	@param  string	$title      		Title
 *	@param  int		$notab				-1 or 0=Add tab header, 1=no tab header (if you set this to 1, using print dol_get_fiche_end() to close tab is not required), -2=Add tab header with no sepaaration under tab (to start a tab just after), -3=Add tab header but no footer separation
 * 	@param	string	$picto				Add a picto on tab title
 *	@param	int		$pictoisfullpath	If 1, image path is a full path. If you set this to 1, you can use url returned by dol_buildpath('/mymodule/img/myimg.png',1) for $picto.
 *  @param	string	$morehtmlright		Add more html content on right of tabs title
 *  @param	string	$morecss			More Css
 *  @param	int		$limittoshow		Limit number of tabs to show. Use 0 to use automatic default value.
 *  @param	string	$moretabssuffix		A suffix to use when you have several dol_get_fiche_head() in same page
 * 	@return	void
 *  @deprecated Use print dol_get_fiche_head() instead
 */
function dol_fiche_head($links = array(), $active = '0', $title = '', $notab = 0, $picto = '', $pictoisfullpath = 0, $morehtmlright = '', $morecss = '', $limittoshow = 0, $moretabssuffix = '')
{
	print dol_get_fiche_head($links, $active, $title, $notab, $picto, $pictoisfullpath, $morehtmlright, $morecss, $limittoshow, $moretabssuffix);
}

/**
 *  Show tabs of a record
 *
 *	@param	array<int,array<int<0,5>,string>>	$links	Array of tabs (0=>url, 1=>label, 2=>code, 3=>not used, 4=>text after link, 5=>morecssonlink). Currently initialized by calling a function xxx_admin_prepare_head. Note that label into $links[$i][1] must be already HTML escaped.
 *	@param	string	$active     		Active tab name (using the old numeric int is deprecated)
 *	@param  string	$title      		Title
 *	@param  int		$notab				-1 or 0=Add tab header, 1=no tab header (if you set this to 1, using print dol_get_fiche_end() to close tab is not required), -2=Add tab header with no separation under tab (to start a tab just after), -3=-2+'noborderbottom'
 * 	@param	string	$picto				Add a picto on tab title
 *	@param	int		$pictoisfullpath	If 1, image path is a full path. If you set this to 1, you can use url returned by dol_buildpath('/mymodule/img/myimg.png',1) for $picto.
 *  @param	string	$morehtmlright		Add more html content on right of tabs title
 *  @param	string	$morecss			More CSS on the link <a>
 *  @param	int		$limittoshow		Limit number of tabs to show. Use 0 to use automatic default value.
 *  @param	string	$moretabssuffix		A suffix to use when you have several dol_get_fiche_head() in same page
 *  @param	int     $dragdropfile       0 (default) or 1. 1 enable a drop zone for file to be upload, 0 disable it
 *  @param	string	$morecssdiv			More CSS on the div
 * 	@return	string
 */
function dol_get_fiche_head($links = array(), $active = '', $title = '', $notab = 0, $picto = '', $pictoisfullpath = 0, $morehtmlright = '', $morecss = '', $limittoshow = 0, $moretabssuffix = '', $dragdropfile = 0, $morecssdiv = '')
{
	global $conf, $langs, $hookmanager;

	// Show title
	$showtitle = 1;
	if (!empty($conf->dol_optimize_smallscreen)) {
		$showtitle = 0;
	}

	$out = "\n" . '<!-- dol_fiche_head - dol_get_fiche_head -->';

	if ((!empty($title) && $showtitle) || $morehtmlright || !empty($links)) {
		$out .= '<div class="tabs' . ($picto ? '' : ' nopaddingleft') . '" data-role="controlgroup" data-type="horizontal">' . "\n";
	}

	// Show right part
	if ($morehtmlright) {
		$out .= '<div class="inline-block floatright tabsElem">' . $morehtmlright . '</div>'; // Output right area first so when space is missing, text is in front of tabs and not under.
	}

	// Show tabs

	// Define max of key (max may be higher than sizeof because of hole due to module disabling some tabs).
	$maxkey = -1;
	if (is_array($links) && !empty($links)) {
		$keys = array_keys($links);
		if (count($keys)) {
			$maxkey = max($keys);
		}
	}

	// Show tabs
	// if =0 we don't use the feature
	if (empty($limittoshow)) {
		$limittoshow = getDolGlobalInt('MAIN_MAXTABS_IN_CARD', 99);
	}
	if (!empty($conf->dol_optimize_smallscreen)) {	// If on smartphone, we limit to 1 tab to show
		$limittoshow = 1;
	}

	$displaytab = 0;
	$nbintab = 0;
	$popuptab = 0;
	$outmore = '';
	for ($i = 0; $i <= $maxkey; $i++) {
		if ((is_numeric($active) && $i == $active) || (!empty($links[$i][2]) && !is_numeric($active) && $active == $links[$i][2])) {
			// If active tab is already present
			if ($i >= $limittoshow) {
				$limittoshow--;
			}
		}
	}

	for ($i = 0; $i <= $maxkey; $i++) {
		if ((is_numeric($active) && $i == $active) || (!empty($links[$i][2]) && !is_numeric($active) && $active == $links[$i][2])) {
			$isactive = true;
		} else {
			$isactive = false;
		}

		if ($i < $limittoshow || $isactive) {
			// Output entry with a visible tab
			$out .= '<div class="inline-block tabsElem' . ($isactive ? ' tabsElemActive' : '') . ((!$isactive && getDolGlobalString('MAIN_HIDE_INACTIVETAB_ON_PRINT')) ? ' hideonprint' : '') . '"><!-- id tab = ' . (empty($links[$i][2]) ? '' : dol_escape_htmltag($links[$i][2])) . ' -->';

			if (isset($links[$i][2]) && $links[$i][2] == 'image') {
				if (!empty($links[$i][0])) {
					$out .= '<a class="tabimage' . ($morecss ? ' ' . $morecss : '') . '" href="' . $links[$i][0] . '">' . $links[$i][1] . '</a>' . "\n";
				} else {
					$out .= '<span class="tabspan">' . $links[$i][1] . '</span>' . "\n";
				}
			} elseif (!empty($links[$i][1])) {
				//print "x $i $active ".$links[$i][2]." z";
				$out .= '<div class="tab tab' . ($isactive ? 'active' : 'unactive') . '" style="margin: 0 !important">';

				if (!empty($links[$i][0])) {
					$titletoshow = preg_replace('/<.*$/', '', $links[$i][1]);
					$out .= '<a' . (!empty($links[$i][2]) ? ' id="' . $links[$i][2] . '"' : '') . ' class="tab inline-block valignmiddle' . ($morecss ? ' ' . $morecss : '') . (!empty($links[$i][5]) ? ' ' . $links[$i][5] : '') . '" href="' . $links[$i][0] . '" title="' . dol_escape_htmltag($titletoshow) . '">';
				}

				if ($displaytab == 0 && $picto) {
					$out .= img_picto($title, $picto, '', $pictoisfullpath, 0, 0, '', 'imgTabTitle paddingright marginrightonlyshort');
				}

				$out .= $links[$i][1];
				if (!empty($links[$i][0])) {
					$out .= '</a>' . "\n";
				}
				$out .= empty($links[$i][4]) ? '' : $links[$i][4];
				$out .= '</div>';
			}

			$out .= '</div>';
		} else {
			// Add entry into the combo popup with the other tabs
			if (!$popuptab) {
				$popuptab = 1;
				$outmore .= '<div class="popuptabset wordwrap">'; // The css used to hide/show popup
			}
			$outmore_content = '';

			if (isset($links[$i][2]) && $links[$i][2] == 'image') {
				if (!empty($links[$i][0])) {
					$outmore_content .= '<a class="tabimage' . ($morecss ? ' ' . $morecss : '') . '" href="' . $links[$i][0] . '">' . $links[$i][1] . '</a>' . "\n";
				} else {
					$outmore_content .= '<span class="tabspan">' . $links[$i][1] . '</span>' . "\n";
				}
			} elseif (!empty($links[$i][1])) {
				$outmore_content .= '<a' . (!empty($links[$i][2]) ? ' id="' . $links[$i][2] . '"' : '') . ' class="wordwrap inline-block' . ($morecss ? ' ' . $morecss : '') . '" href="' . $links[$i][0] . '">';
				$outmore_content .= preg_replace('/([a-z])\|([a-z])/i', '\\1 | \\2', $links[$i][1]); // Replace x|y with x | y to allow wrap on long composed texts.
				$outmore_content .= '</a>' . "\n";
			}
			if ($outmore_content !== '') {
				$outmore .= '<div class="popuptab wordwrap" style="display:inherit;">' . $outmore_content . '</div>';
			}

			$nbintab++;
		}

		$displaytab = $i + 1;
	}
	if ($popuptab) {
		$outmore .= '</div>';
	}

	if ($popuptab) {	// If there is some tabs not shown
		$left = ($langs->trans("DIRECTION") == 'rtl' ? 'right' : 'left');
		$right = ($langs->trans("DIRECTION") == 'rtl' ? 'left' : 'right');
		$widthofpopup = 240;

		$tabsname = $moretabssuffix;
		if (empty($tabsname)) {
			$tabsname = str_replace("@", "", $picto);
		}
		$out .= '<div id="moretabs' . $tabsname . '" class="inline-block tabsElem valignmiddle">';
		if (getDolGlobalInt('MAIN_OPTIMIZEFORTEXTBROWSER') < 2) {
			$out .= '<div class="tab valignmiddle"><a href="#" class="tab moretab inline-block tabunactive valignmiddle"><span class="fa fa-angle-down"></span> <span class="opacitymedium">+' . $nbintab . '</span></a></div>'; // Do not use "reposition" class in the "More".
		}
		$out .= '<div id="moretabsList' . $tabsname . '" style="width: ' . $widthofpopup . 'px; position: absolute; ' . $left . ': -999em; text-align: ' . $left . '; margin:0px; padding:2px; z-index:10;">';
		$out .= $outmore;
		$out .= '</div>';
		$out .= '<div></div>';
		$out .= "</div>\n";

		$out .= '<script nonce="' . getNonce() . '">';
		$out .= "$('#moretabs" . $tabsname . "').mouseenter( function() {
			var x = this.offsetLeft, y = this.offsetTop;
			console.log('mouseenter " . $left . " x='+x+' y='+y+' window.innerWidth='+window.innerWidth);
			if ((window.innerWidth - x) < " . ($widthofpopup + 10) . ") {
				$('#moretabsList" . $tabsname . "').css('" . $right . "','8px');
			}
			$('#moretabsList" . $tabsname . "').css('" . $left . "','auto');
			});
		";
		$out .= "$('#moretabs" . $tabsname . "').mouseleave( function() { console.log('mouseleave " . $left . "'); $('#moretabsList" . $tabsname . "').css('" . $left . "','-999em');});";
		$out .= "</script>";
	}

	if ((!empty($title) && $showtitle) || $morehtmlright || !empty($links)) {
		$out .= "</div>\n";
	}

	if (!$notab || $notab == -1 || $notab == -2 || $notab == -3 || $notab == -4) {
		$out .= "\n" . '<div id="dragDropAreaTabBar" class="tabBar' . ($notab == -1 ? '' : ($notab == -2 ? ' tabBarNoTop' : ((($notab == -3 || $notab == -4) ? ' noborderbottom' : '') . ($notab == -4 ? '' : ' tabBarWithBottom'))));
		$out .= ($morecssdiv ? ' ' . $morecssdiv : '');
		$out .= '">' . "\n";
	}
	if (!empty($dragdropfile)) {
		include_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
		$out .= dragAndDropFileUpload("dragDropAreaTabBar");
	}
	$parameters = array('tabname' => $active, 'out' => $out);
	$reshook = $hookmanager->executeHooks('printTabsHead', $parameters); // This hook usage is called just before output the head of tabs. Take also a look at "completeTabsHead"
	if ($reshook > 0) {
		$out = $hookmanager->resPrint;
	}

	return $out;
}

/**
 *  Show tab footer of a card
 *
 *  @param	int<-1,1>	$notab       -1 or 0=Add tab footer, 1=no tab footer
 *  @return	void
 *  @deprecated Use print dol_get_fiche_end() instead
 */
function dol_fiche_end($notab = 0)
{
	print dol_get_fiche_end($notab);
}

/**
 *	Return tab footer of a card
 *
 *	@param  int<-1,1>	$notab		-1 or 0=Add tab footer, 1=no tab footer
 *  @return	string
 */
function dol_get_fiche_end($notab = 0)
{
	if (!$notab || $notab == -1) {
		return "\n</div>\n";
	} else {
		return '';
	}
}

/**
 *  Show tab footer of a card.
 *  Note: $object->next_prev_filter can be set to restrict select to find next or previous record by $form->showrefnav.
 *
 *  @param	CommonObject $object		Object to show
 *  @param	string		$paramid   		Name of parameter to use to name the id into the URL next/previous link
 *  @param	string		$morehtml  		More html content to output just before the nav bar
 *  @param	int|bool 	$shownav	  	Show Condition (navigation is shown if value is 1 or true)
 *  @param	string		$fieldid   		Name of the field in DB to use to select next et previous (we make the select max and min on this field). Use 'none' for no prev/next search.
 *  @param	string		$fieldref   	Name of the field (object->ref) to use to select next et previous
 *  @param	string		$morehtmlref  	More html to show after the ref (see $morehtmlleft for before)
 *  @param	string		$moreparam  	More param to add in nav link url.
 *	@param	int			$nodbprefix		Do not include DB prefix to forge table name
 *	@param	string		$morehtmlleft	More html code to show before the ref (see $morehtmlref for after)
 *	@param	string		$morehtmlstatus	More html code to show under navigation arrows
 *  @param  int     	$onlybanner     Put this to 1, if the card will contains only a banner (this add css 'arearefnobottom' on div)
 *	@param	string		$morehtmlright	More html code to show before navigation arrows
 *  @return	void
 */
function dol_banner_tab($object, $paramid, $morehtml = '', $shownav = 1, $fieldid = 'rowid', $fieldref = 'ref', $morehtmlref = '', $moreparam = '', $nodbprefix = 0, $morehtmlleft = '', $morehtmlstatus = '', $onlybanner = 0, $morehtmlright = '')
{
	global $conf, $form, $user, $langs, $hookmanager, $action;

	$error = 0;

	$maxvisiblephotos = 1;
	$showimage = 1;
	$entity = (empty($object->entity) ? $conf->entity : $object->entity);
	// @phan-suppress-next-line PhanUndeclaredMethod
	$showbarcode = !isModEnabled('barcode') ? 0 : (empty($object->barcode) ? 0 : 1);
	if (getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && !$user->hasRight('barcode', 'lire_advance')) {
		$showbarcode = 0;
	}
	$modulepart = 'unknown';

	if (in_array($object->element, ['societe', 'contact', 'product', 'ticket', 'bom'])) {
		$modulepart = $object->element;
	} elseif ($object->element == 'member') {
		$modulepart = 'memberphoto';
	} elseif ($object->element == 'user') {
		$modulepart = 'userphoto';
	}

	if (class_exists("Imagick")) {
		if ($object->element == 'expensereport' || $object->element == 'propal' || $object->element == 'commande' || $object->element == 'facture' || $object->element == 'supplier_proposal') {
			$modulepart = $object->element;
		} elseif ($object->element == 'fichinter' || $object->element == 'intervention') {
			$modulepart = 'ficheinter';
		} elseif ($object->element == 'contrat' || $object->element == 'contract') {
			$modulepart = 'contract';
		} elseif ($object->element == 'order_supplier') {
			$modulepart = 'supplier_order';
		} elseif ($object->element == 'invoice_supplier') {
			$modulepart = 'supplier_invoice';
		}
	}

	if ($object->element == 'product') {
		/** @var Product $object */
		'@phan-var-force Product $object';
		$width = 80;
		$cssclass = 'photowithmargin photoref';
		$showimage = $object->is_photo_available($conf->product->multidir_output[$entity]);
		$maxvisiblephotos = getDolGlobalInt('PRODUCT_MAX_VISIBLE_PHOTO', 5);
		if ($conf->browser->layout == 'phone') {
			$maxvisiblephotos = 1;
		}
		$useLinkPathPhoto = getDolGlobalInt('PRODUCT_USE_LINK_PATH_FOR_PHOTO');
		if ($showimage || $useLinkPathPhoto) {
			$morehtmlleft .= '<div class="floatleft inline-block valignmiddle divphotoref">' . $object->show_photos('product', $conf->product->multidir_output[$entity], 1, $maxvisiblephotos, 0, 0, 0, 0, $width, 0, '') . '</div>';
		} else {
			if (getDolGlobalString('PRODUCT_NODISPLAYIFNOPHOTO')) {
				$nophoto = '';
				$morehtmlleft .= '<div class="floatleft inline-block valignmiddle divphotoref"></div>';
			} else {    // Show no photo link
				$nophoto = '/public/theme/common/nophoto.png';
				$morehtmlleft .= '<div class="floatleft inline-block valignmiddle divphotoref"><img class="photo' . $modulepart . ' ' . $cssclass . '" title="' . dol_escape_htmltag($langs->trans("UploadAnImageToSeeAPhotoHere", $langs->transnoentitiesnoconv("Documents"))) . '" alt="No photo" style="width: ' . $width . 'px" src="' . DOL_URL_ROOT . $nophoto . '"></div>';
			}
		}
	} elseif ($object->element == 'category') {
		/** @var Categorie $object */
		'@phan-var-force Categorie $object';
		$width = 80;
		$cssclass = 'photowithmargin photoref';
		$showimage = $object->isAnyPhotoAvailable($conf->categorie->multidir_output[$entity]);
		$maxvisiblephotos = getDolGlobalInt('CATEGORY_MAX_VISIBLE_PHOTO', 5);
		if ($conf->browser->layout == 'phone') {
			$maxvisiblephotos = 1;
		}
		if ($showimage) {
			$morehtmlleft .= '<div class="floatleft inline-block valignmiddle divphotoref">' . $object->show_photos('category', $conf->categorie->multidir_output[$entity], 'small', $maxvisiblephotos, 0, 0, 0, 0, $width, 0, '') . '</div>';
		} else {
			if (getDolGlobalString('CATEGORY_NODISPLAYIFNOPHOTO')) {
				$nophoto = '';
				$morehtmlleft .= '<div class="floatleft inline-block valignmiddle divphotoref"></div>';
			} else {    // Show no photo link
				$nophoto = '/public/theme/common/nophoto.png';
				$morehtmlleft .= '<div class="floatleft inline-block valignmiddle divphotoref"><img class="photo' . $modulepart . ' ' . $cssclass . '" title="' . dol_escape_htmltag($langs->trans("UploadAnImageToSeeAPhotoHere", $langs->transnoentitiesnoconv("Documents"))) . '" alt="No photo" style="width: ' . $width . 'px" src="' . DOL_URL_ROOT . $nophoto . '"></div>';
			}
		}
	} elseif ($object->element == 'bom') {
		/** @var BOM $object */
		'@phan-var-force Bom $object';
		$width = 80;
		$cssclass = 'photowithmargin photoref';
		$showimage = $object->is_photo_available($conf->bom->multidir_output[$entity]);
		$maxvisiblephotos = getDolGlobalInt('BOM_MAX_VISIBLE_PHOTO', 5);
		if ($conf->browser->layout == 'phone') {
			$maxvisiblephotos = 1;
		}
		if ($showimage) {
			$morehtmlleft .= '<div class="floatleft inline-block valignmiddle divphotoref">' . $object->show_photos('bom', $conf->bom->multidir_output[$entity], 'small', $maxvisiblephotos, 0, 0, 0, 0, $width, 0, '') . '</div>';
		} else {
			if (getDolGlobalString('BOM_NODISPLAYIFNOPHOTO')) {
				$nophoto = '';
				$morehtmlleft .= '<div class="floatleft inline-block valignmiddle divphotoref"></div>';
			} else {    // Show no photo link
				$nophoto = '/public/theme/common/nophoto.png';
				$morehtmlleft .= '<div class="floatleft inline-block valignmiddle divphotoref"><img class="photo' . $modulepart . ' ' . $cssclass . '" title="' . dol_escape_htmltag($langs->trans("UploadAnImageToSeeAPhotoHere", $langs->transnoentitiesnoconv("Documents"))) . '" alt="No photo" style="width: ' . $width . 'px" src="' . DOL_URL_ROOT . $nophoto . '"></div>';
			}
		}
	} elseif ($object->element == 'ticket') {
		$width = 80;
		$cssclass = 'photoref';
		/** @var Ticket $object */
		'@phan-var-force Ticket $object';
		$showimage = $object->is_photo_available($conf->ticket->multidir_output[$entity] . '/' . $object->ref);
		$maxvisiblephotos = getDolGlobalInt('TICKET_MAX_VISIBLE_PHOTO', 2);
		if ($conf->browser->layout == 'phone') {
			$maxvisiblephotos = 1;
		}

		if ($showimage) {
			$showphoto = $object->show_photos('ticket', $conf->ticket->multidir_output[$entity], 'small', $maxvisiblephotos, 0, 0, 0, $width, 0);
			if ($object->nbphoto > 0) {
				$morehtmlleft .= '<div class="floatleft inline-block valignmiddle divphotoref">' . $showphoto . '</div>';
			} else {
				$showimage = 0;
			}
		}
		if (!$showimage) {
			if (getDolGlobalString('TICKET_NODISPLAYIFNOPHOTO')) {
				$nophoto = '';
				$morehtmlleft .= '<div class="floatleft inline-block valignmiddle divphotoref"></div>';
			} else {    // Show no photo link
				$nophoto = img_picto('No photo', 'object_ticket');
				$morehtmlleft .= '<!-- No photo to show -->';
				$morehtmlleft .= '<div class="floatleft inline-block valignmiddle divphotoref"><div class="photoref">';
				$morehtmlleft .= $nophoto;
				$morehtmlleft .= '</div></div>';
			}
		}
	} else {
		// $modulepart may have been set previously if Imagick class exists (see before).
		if ($modulepart != 'unknown' || method_exists($object, 'getDataToShowPhoto')) {
			$phototoshow = '';
			// Check if a preview file is available
			if (in_array($modulepart, array('propal', 'commande', 'facture', 'ficheinter', 'contract', 'supplier_order', 'supplier_proposal', 'supplier_invoice', 'expensereport')) && class_exists("Imagick")) {
				$objectref = dol_sanitizeFileName($object->ref);
				$dir_output = (empty($conf->$modulepart->multidir_output[$entity]) ? $conf->$modulepart->dir_output : $conf->$modulepart->multidir_output[$entity]) . "/";
				if (in_array($modulepart, array('invoice_supplier', 'supplier_invoice'))) {
					$subdir = get_exdir($object->id, 2, 0, 1, $object, $modulepart);
					$subdir .= ((!empty($subdir) && !preg_match('/\/$/', $subdir)) ? '/' : '') . $objectref; // the objectref dir is not included into get_exdir when used with level=2, so we add it at end
				} else {
					$subdir = get_exdir($object->id, 0, 0, 1, $object, $modulepart);
				}
				if (empty($subdir)) {
					$subdir = 'errorgettingsubdirofobject'; // Protection to avoid to return empty path
				}

				$filepath = $dir_output . $subdir . "/";

				$filepdf = $filepath . $objectref . ".pdf";
				$relativepath = $subdir . '/' . $objectref . '.pdf';

				// Define path to preview pdf file (preview precompiled "file.ext" are "file.ext_preview.png")
				$fileimage = $filepdf . '_preview.png';
				$relativepathimage = $relativepath . '_preview.png';

				$pdfexists = file_exists($filepdf);

				// If PDF file exists
				if ($pdfexists) {
					// Conversion du PDF en image png si fichier png non existent
					if (!file_exists($fileimage) || (filemtime($fileimage) < filemtime($filepdf))) {
						if (!getDolGlobalString('MAIN_DISABLE_PDF_THUMBS')) {		// If you experience trouble with pdf thumb generation and imagick, you can disable here.
							include_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
							$ret = dol_convert_file($filepdf, 'png', $fileimage, '0'); // Convert first page of PDF into a file _preview.png
							if ($ret < 0) {
								$error++;
							}
						}
					}
				}

				if ($pdfexists && !$error) {
					$heightforphotref = 80;
					if (!empty($conf->dol_optimize_smallscreen)) {
						$heightforphotref = 60;
					}
					// If the preview file is found
					if (file_exists($fileimage)) {
						$phototoshow = '<div class="photoref">';
						$phototoshow .= '<img height="' . $heightforphotref . '" class="photo photowithborder" src="' . DOL_URL_ROOT . '/viewimage.php?modulepart=apercu' . $modulepart . '&amp;file=' . urlencode($relativepathimage) . '">';
						$phototoshow .= '</div>';
					}
				}
			} elseif (!$phototoshow) { // example if modulepart = 'societe' or 'photo' or 'memberphoto'
				$phototoshow .= $form->showphoto($modulepart, $object, 0, 0, 0, 'photowithmargin photoref', 'small', 1, 0);
			}

			if ($phototoshow) {
				$morehtmlleft .= '<div class="floatleft inline-block valignmiddle divphotoref">';
				$morehtmlleft .= $phototoshow;
				$morehtmlleft .= '</div>';
			}
		}

		if (empty($phototoshow)) {      // Show No photo link (picto of object)
			if ($object->element == 'action') {
				$width = 80;
				$cssclass = 'photorefcenter';
				$nophoto = img_picto('No photo', 'title_agenda');
			} else {
				$width = 14;
				$cssclass = 'photorefcenter';
				$picto = $object->picto;  // @phan-suppress-current-line PhanUndeclaredProperty
				$prefix = 'object_';
				if ($object->element == 'project' && !$object->public) {  // @phan-suppress-current-line PhanUndeclaredProperty
					$picto = 'project'; // instead of projectpub
				}
				if (strpos($picto, 'fontawesome_') !== false) {
					$prefix = '';
				}
				$nophoto = img_picto('No photo', $prefix . $picto);
			}
			$morehtmlleft .= '<!-- No photo to show -->';
			$morehtmlleft .= '<div class="floatleft inline-block valignmiddle divphotoref"><div class="photoref">';
			$morehtmlleft .= $nophoto;
			$morehtmlleft .= '</div></div>';
		}
	}

	if (getDolGlobalString('MAIN_SHOW_TECHNICAL_ID') && (getDolGlobalString('MAIN_SHOW_TECHNICAL_ID') == '1' || preg_match('/' . preg_quote($object->element, '/') . '/i', getDolGlobalString('MAIN_SHOW_TECHNICAL_ID'))) && !empty($object->id)) {
		$morehtmlref .= '<div style="clear: both;"></div>';
		$morehtmlref .= '<div class="smallimp refidno opacitymedium banner-object-technical-id">';
		$morehtmlref .= $langs->trans("TechnicalID") . ': ' . ((int) $object->id);
		$morehtmlref .= '</div>';
	}


	// Show barcode
	if ($showbarcode) {
		$morehtmlleft .= '<div class="floatleft inline-block valignmiddle divphotoref">' . $form->showbarcode($object, 100, 'photoref valignmiddle') . '</div>';
	}

	if ($object->element == 'societe') {
		/** @var Societe $object */
		if (!empty($conf->use_javascript_ajax) && $user->hasRight('societe', 'creer') && getDolGlobalString('MAIN_DIRECT_STATUS_UPDATE')) {
			$morehtmlstatus .= ajax_object_onoff($object, 'status', 'status', 'InActivity', 'ActivityCeased');
		} else {
			$morehtmlstatus .= $object->getLibStatut(6);
		}
	} elseif ($object->element == 'product') {
		/** @var Product $object */
		//$morehtmlstatus.=$langs->trans("Status").' ('.$langs->trans("Sell").') ';
		if (!empty($conf->use_javascript_ajax) && $user->hasRight('produit', 'creer') && getDolGlobalString('MAIN_DIRECT_STATUS_UPDATE')) {
			$morehtmlstatus .= ajax_object_onoff($object, 'status', 'status', 'ProductStatusOnSell', 'ProductStatusNotOnSell');
		} else {
			$morehtmlstatus .= '<span class="statusrefsell">' . $object->getLibStatut(6, 0) . '</span>';
		}
		$morehtmlstatus .= ' &nbsp; ';
		//$morehtmlstatus.=$langs->trans("Status").' ('.$langs->trans("Buy").') ';
		if (!empty($conf->use_javascript_ajax) && $user->hasRight('produit', 'creer') && getDolGlobalString('MAIN_DIRECT_STATUS_UPDATE')) {
			$morehtmlstatus .= ajax_object_onoff($object, 'status_buy', 'status_buy', 'ProductStatusOnBuy', 'ProductStatusNotOnBuy');
		} else {
			$morehtmlstatus .= '<span class="statusrefbuy">' . $object->getLibStatut(6, 1) . '</span>';
		}
	} elseif (in_array($object->element, array('salary'))) {
		/** @var Salary $object */
		'@phan-var-force Salary $object';
		$tmptxt = $object->getLibStatut(6, $object->alreadypaid);
		if (empty($tmptxt) || $tmptxt == $object->getLibStatut(3)) {
			$tmptxt = $object->getLibStatut(5, $object->alreadypaid);
		}
		$morehtmlstatus .= $tmptxt;
	} elseif (in_array($object->element, array('facture', 'invoice', 'invoice_supplier'))) {
		/** @var Facture|FactureFournisseur|CommonInvoice $object */
		'@phan-var-force Facture|FactureFournisseur|CommonInvoice $object';
		if (!isset($object->alreadypaid)) {
			$object->totalpaid = $object->getSommePaiement(0);
			$object->totalcreditnotes = $object->getSumCreditNotesUsed(0);
			$object->totaldeposits = $object->getSumDepositsUsed(0);
			$object->alreadypaid = $object->totalpaid + $object->totalcreditnotes + $object->totaldeposits;
		}
		$tmptxt = $object->getLibStatut(6, (float) $object->alreadypaid);
		if (empty($tmptxt) || $tmptxt == $object->getLibStatut(3)) {
			$tmptxt = $object->getLibStatut(5, (float) $object->alreadypaid);
		}
		$morehtmlstatus .= $tmptxt;
	} elseif (in_array($object->element, array('chargesociales', 'loan', 'tva'))) {	// TODO Move this to use ->alreadypaid like for invoices
		/** @var ChargeSociales|Loan|Tva $object */
		'@phan-var-force ChargeSociales|Loan|Tva $object';
		$tmptxt = $object->getLibStatut(6, $object->totalpaid);
		if (empty($tmptxt) || $tmptxt == $object->getLibStatut(3)) {
			$tmptxt = $object->getLibStatut(5, $object->totalpaid);
		}
		$morehtmlstatus .= $tmptxt;
	} elseif ($object->element == 'contrat' || $object->element == 'contract') {
		/** @var Contrat $object */
		if ($object->status == 0) {
			$morehtmlstatus .= $object->getLibStatut(5);
		} else {
			$morehtmlstatus .= $object->getLibStatut(4);
		}
	} elseif ($object->element == 'facturerec') {
		/** @var FactureRec $object */
		'@phan-var-force FactureRec $object';
		if ($object->frequency == 0) {
			$morehtmlstatus .= $object->getLibStatut(2);
		} else {
			$morehtmlstatus .= $object->getLibStatut(5);
		}
	} elseif ($object->element == 'project_task') {
		/** @var Task $object */
		$tmptxt = $object->getLibStatut(4);
		$morehtmlstatus .= $tmptxt;
	} elseif (method_exists($object, 'getLibStatut')) { // Generic case for status
		$tmptxt = $object->getLibStatut(6);
		if (empty($tmptxt) || $tmptxt == $object->getLibStatut(3)) {
			$tmptxt = $object->getLibStatut(5);
		}
		$morehtmlstatus .= $tmptxt;
	}

	// Say if object was dispatched/transferred "into accountancy"
	if (isModEnabled('accounting') && in_array($object->element, array('bank', 'paiementcharge', 'facture', 'invoice', 'invoice_supplier', 'expensereport', 'payment_various'))) {
		// Note: For 'chargesociales', 'salaries'... this is the payments that are dispatched (so element = 'bank')
		if (method_exists($object, 'getVentilExportCompta')) {
			$accounted = $object->getVentilExportCompta(1);
			$langs->load("accountancy");
			$morehtmlstatus .= '</div><div class="statusref statusrefbis"><span class="opacitymedium">' . ($accounted > 0 ? '<a href="' . DOL_URL_ROOT . '/accountancy/bookkeeping/list.php?search_mvt_num=' . ((int) $accounted) . '">' . $langs->trans("Accounted") . '</a>' : $langs->trans("NotYetAccounted")) . '</span>';
		}
	}

	// Add alias for thirdparty
	if (!empty($object->name_alias)) {
		/** @var Societe $object */
		'@phan-var-force Societe $object';
		$morehtmlref .= '<div class="refidno opacitymedium banner-object-name-alias">' . dol_escape_htmltag($object->name_alias) . '</div>';
	}

	// Add label
	if (in_array($object->element, array('product', 'bank_account', 'project_task'))) {
		/** @var Product|Account|Task $object */
		if (!empty($object->label)) {
			$morehtmlref .= '<div class="refidno banner-object-label">' . $object->label . '</div>';
		}
	}
	// Show address and email
	if (method_exists($object, 'getBannerAddress') && !in_array($object->element, array('product', 'bookmark', 'ecm_directories', 'ecm_files'))) {
		$moreaddress = $object->getBannerAddress('refaddress', $object);	// address, email, url, social networks
		if ($moreaddress) {
			$morehtmlref .= '<div class="refidno refaddress">';
			$morehtmlref .= $moreaddress;
			$morehtmlref .= '</div>';
		}
	}

	$parameters = array('morehtmlref' => &$morehtmlref, 'moreparam' => &$moreparam, 'morehtmlleft' => &$morehtmlleft, 'morehtmlstatus' => &$morehtmlstatus, 'morehtmlright' => &$morehtmlright);
	$reshook = $hookmanager->executeHooks('formDolBanner', $parameters, $object, $action);
	if ($reshook < 0) {
		setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
	} elseif (empty($reshook)) {
		$morehtmlref .= $hookmanager->resPrint;
	} elseif ($reshook > 0) {
		$morehtmlref = $hookmanager->resPrint;
	}

	// $morehtml is the right part (link "Back to list")
	// $morehtmlref is the part after the ref
	// $morehtmlleft is the picto or photo of banner
	// $morehtmlstatus is part under the status
	// $morehtmlright is part of htmlright

	print '<div class="' . ($onlybanner ? 'arearefnobottom ' : 'arearef ') . 'heightref valignmiddle centpercent object-banner-tab-container" data-module-part="'.dolPrintHTMLForAttribute($modulepart).'">';
	print $form->showrefnav($object, $paramid, $morehtml, $shownav, $fieldid, $fieldref, $morehtmlref, $moreparam, $nodbprefix, $morehtmlleft, $morehtmlstatus, $morehtmlright);
	print '</div>';
	print '<div class="underrefbanner clearboth"></div>';
}

/**
 * Show a string with the label tag dedicated to the HTML edit field.
 *
 * @param	string	$langkey		Translation key
 * @param 	string	$fieldkey		Key of the html select field the text refers to
 * @param	int		$fieldrequired	1=Field is mandatory
 * @return string
 * @deprecated Form::editfieldkey
 */
function fieldLabel($langkey, $fieldkey, $fieldrequired = 0)
{
	global $langs;
	$ret = '';
	if ($fieldrequired) {
		$ret .= '<span class="fieldrequired">';
	}
	$ret .= '<label for="' . $fieldkey . '">';
	$ret .= $langs->trans($langkey);
	$ret .= '</label>';
	if ($fieldrequired) {
		$ret .= '</span>';
	}
	return $ret;
}


/**
 * Print decorated date-hour
 *
 * @param	int			$datep			Date
 * @param	int|null	$datef			Second date
 * @param	int			$fullday		Set to 1 for full day (hours are hidden)
 * @param	int			$addseconds		Add also seconds
 * @param	string		$pictotoadd		Picto to add
 * @param	string|bool	$tzoutput		true or 'gmt' => string is for Greenwich location
 * 										false or 'tzserver' => output string is for local PHP server TZ usage
 * 										'tzuser' => output string is for user TZ (current browser TZ with current dst) => In a future, we should have same behaviour than 'tzuserrel'
 *                                 	    'tzuserrel' => output string is for user TZ (current browser TZ with dst or not, depending on date position)
 * @param	int 		$reduceformat	Use 1 to use a reduce format
 * @return	string						Decorated date
 */
function dolOutputDates($datep, $datef = null, $fullday = 0, $addseconds = 0, $pictotoadd = '', $tzoutput = 'tzuserrel', $reduceformat = 0)
{
	$tmpa = dol_getdate($datep);
	if (empty($datef)) {
		$tmpb = $tmpa;
	} else {
		$tmpb = dol_getdate($datef);
	}

	$s = '';

	if ($tmpa['mday'] == $tmpb['mday'] && $tmpa['mon'] == $tmpb['mon'] && $tmpa['year'] == $tmpb['year']) {
		// The same day
		$s .= '<div class="center inline-block">';
		if ($tmpa['hours'] != $tmpb['hours'] || $tmpa['minutes'] != $tmpb['minutes']) {
			// Not the same hour
			$s .=  dol_print_date($datep, 'day'.($reduceformat ? 'reduceformat' : ''), $tzoutput);
			$s .= $pictotoadd;
			if (empty($fullday)) {
				$s .=  '<br><span class="small opacitymedium">';
				$s .=  dol_print_date($datep, 'hour'.($addseconds ? 'sec' : '').'reduceformat', $tzoutput);
				$s .=  '-'.dol_print_date($datef, 'hour'.($addseconds ? 'sec' : '').'reduceformat', $tzoutput);
				$s .=  '</span>';
			}
		} else {
			// The same hour
			$s .=  dol_print_date($datep, 'day'.($reduceformat ? 'reduceformat' : ''), 'tzuserrel');
			$s .= $pictotoadd;
			if (empty($fullday)) {
				$s .=  '<br><span class="small opacitymedium">';
				$s .=  dol_print_date($datep, 'hour'.($addseconds ? 'sec' : '').'reduceformat', $tzoutput);
				$s .=  '</span>';
			}
		}
		$s .=  '</div>';
	} else {
		// Not the same day
		$s .=  '<div class="center inline-block dateborderright">';
		$s .=  dol_print_date($datep, 'day'.($reduceformat ? 'reduceformat' : ''), $tzoutput);
		if (empty($fullday)) {
			$s .=  '<br><span class="small opacitymedium">';
			$s .=  dol_print_date($datep, 'hour'.($addseconds ? 'sec' : '').'reduceformat', $tzoutput);
			$s .=  '</span>';
		}
		$s .=  '</div>';
		$s .=  '<div class="center inline-block dateborderleft">';
		$s .=  dol_print_date($datef, 'day'.($reduceformat ? 'reduceformat' : ''), 'tzuserrel');
		$s .= $pictotoadd;
		if (empty($fullday)) {
			$s .=  '<br><span class="small opacitymedium">';
			$s .=  dol_print_date($datef, 'hour'.($addseconds ? 'sec' : '').'reduceformat', $tzoutput);
			$s .=  '</span>';
		}
		$s .=  '</div>';
	}

	return $s;
}


/**
 * Return the picto for a data type
 *
 * @param 	string		$key		Key
 * @param	string		$morecss	Add more css to the object
 * @return 	string					Picto for the key
 */
function getPictoForType($key, $morecss = '')
{
	// Set array with type -> picto
	$type2picto = array(
		'varchar' => 'font',
		'text' => 'font',
		'html' => 'code',
		'int' => 'sort-numeric-down',
		'double' => 'sort-numeric-down',
		'price' => 'currency',
		'pricecy' => 'multicurrency',
		'password' => 'key',
		'boolean' => 'check-square',
		'date' => 'calendar',
		'datetime' => 'calendar',
		'duration' => 'hourglass',
		'phone' => 'phone',
		'mail' => 'email',
		'url' => 'url',
		'ip' => 'country',
		'select' => 'list',
		'sellist' => 'list',
		'stars' => 'fontawesome_star_fas',
		'radio' => 'check-circle',
		'checkbox' => 'list',
		'chkbxlst' => 'list',
		'link' => 'link',
		'icon' => "question",
		'point' => "country",
		'multipts' => 'country',
		'linestrg' => "country",
		'polygon' => "country",
		'separate' => 'minus'
	);

	if (!empty($type2picto[$key])) {
		return img_picto('', $type2picto[$key], 'class="pictofixedwidth' . ($morecss ? ' ' . $morecss : '') . '"');
	}

	return img_picto('', 'generic', 'class="pictofixedwidth' . ($morecss ? ' ' . $morecss : '') . '"');
}


/**
 *	Show picto whatever it's its name (generic function)
 *
 *	@param      string		$titlealt         		Text on title tag for tooltip. Not used if param notitle is set to 1.
 *	@param      string		$picto       			Name of image file to show ('filenew', ...).
 *													For font awesome icon (example 'user'), you can use picto_nocolor to not have the color of picto forced.
 *													If no extension provided and it is not a font awesome icon, we use '.png'. Image must be stored into theme/xxx/img directory.
 *                                  				Example: picto.png                  if picto.png is stored into htdocs/theme/mytheme/img
 *                                  				Example: picto.png@mymodule         if picto.png is stored into htdocs/mymodule/img
 *                                  				Example: /mydir/mysubdir/picto.png  if picto.png is stored into htdocs/mydir/mysubdir (pictoisfullpath must be set to 1)
 *                                                  Example: fa-value			 		if you want to use fontaweseome icons: fa-<icon-name>
 *                                                  Example: fa-value_fas_color_1em 	if you want to use fontaweseome icons: fa-<icon-name>_<style>_<color>_<size> (only icon-name is mandatory, color can be 'red' or '#FF0000')
 *	@param		string		$moreatt				Add more attribute on img tag (For example 'class="pictofixedwidth"')
 *	@param		int<0,1>    $pictoisfullpath		If true or 1, image path is a full path, 0 if not
 *	@param		int			$srconly				Return only content of the src attribute of img.
 *  @param		int			$notitle				1=Disable tag title. Use it if you add js tooltip, to avoid duplicate tooltip.
 *  @param		string		$alt					Force alt for blind people
 *  @param		string		$morecss				Add more class css on img tag (For example 'myclascss').
 *  @param		int 		$marginleftonlyshort	1 = Add a short left margin on picto, 2 = Add a larger left margin on picto, 0 = No margin left. Works for fontawesome picto only.
 *  @param		string[]	$allowothertags			List of other tags allowed in title and alt attribute
 *  @return     string       				    	Return img tag
 *  @see        img_object(), img_picto_common()
 */
function img_picto($titlealt, $picto, $moreatt = '', $pictoisfullpath = 0, $srconly = 0, $notitle = 0, $alt = '', $morecss = '', $marginleftonlyshort = 2, $allowothertags = array())
{
	global $conf;

	// We forge fullpathpicto for image to $path/img/$picto. By default, we take DOL_URL_ROOT/theme/$conf->theme/img/$picto
	$url = DOL_URL_ROOT;
	$theme = isset($conf->theme) ? $conf->theme : null;
	$path = 'theme/' . $theme;
	if (empty($picto)) {
		$picto = 'generic';
	}

	// Define fullpathpicto to use into src
	if ($pictoisfullpath) {
		// Clean parameters
		if (!preg_match('/(\.png|\.gif|\.svg)$/i', $picto)) {
			$picto .= '.png';
		}
		$fullpathpicto = $picto;
		$reg = array();
		if (preg_match('/class="([^"]+)"/', $moreatt, $reg)) {
			$morecss .= ($morecss ? ' ' : '') . $reg[1];
			$moreatt = str_replace('class="' . $reg[1] . '"', '', $moreatt);
		}
	} else {
		// $picto can not be null since replaced with 'generic' in that case
		// $pictowithouttext = preg_replace('/(\.png|\.gif|\.svg)$/', '', (is_null($picto) ? '' : $picto));
		$pictowithouttext = preg_replace('/(\.png|\.gif|\.svg)$/', '', $picto);
		$pictowithouttext = str_replace('object_', '', $pictowithouttext);
		$pictowithouttext = str_replace('_nocolor', '', $pictowithouttext);

		// Fix some values of $pictowithouttext
		$pictoconvertkey = array(
			'facture' => 'bill',
			'shipping' => 'shipment',
			'fichinter' => 'intervention',
			'agenda' => 'calendar',
			'invoice_supplier' => 'supplier_invoice',
			'order_supplier' => 'supplier_order');
		if (in_array($pictowithouttext, array_keys($pictoconvertkey))) {
			$pictowithouttext = $pictoconvertkey[$pictowithouttext];
		}

		if (strpos($pictowithouttext, 'fontawesome_') === 0 || strpos($pictowithouttext, 'fa-') === 0) {
			// This is a font awesome image 'fontawesome_xxx' or 'fa-xxx'
			$pictowithouttext = str_replace('fontawesome_', '', $pictowithouttext);
			$pictowithouttext = str_replace('fa-', '', $pictowithouttext);

			// Compatibility with old fontawesome versions
			if ($pictowithouttext == 'file-o') {
				$pictowithouttext = 'file';
			}

			$pictowithouttextarray = explode('_', $pictowithouttext);
			$marginleftonlyshort = 0;

			if (!empty($pictowithouttextarray[1])) {
				// Syntax is 'fontawesome_fakey_faprefix_facolor_fasize' or 'fa-fakey_faprefix_facolor_fasize'
				$fakey      = 'fa-' . $pictowithouttextarray[0];
				$faprefix   = empty($pictowithouttextarray[1]) ? 'fas' : $pictowithouttextarray[1];
				$facolor    = empty($pictowithouttextarray[2]) ? '' : $pictowithouttextarray[2];
				$fasize     = empty($pictowithouttextarray[3]) ? '' : $pictowithouttextarray[3];
			} else {
				$fakey      = 'fa-' . $pictowithouttext;
				$faprefix   = 'fas';
				$facolor    = '';
				$fasize     = '';
			}

			// This snippet only needed since function img_edit accepts only one additional parameter: no separate one for css only.
			// class/style need to be extracted to avoid duplicate class/style validation errors when $moreatt is added to the end of the attributes.
			$morestyle = '';
			$reg = array();
			if (preg_match('/class="([^"]+)"/', $moreatt, $reg)) {
				$morecss .= ($morecss ? ' ' : '') . $reg[1];
				$moreatt = str_replace('class="' . $reg[1] . '"', '', $moreatt);
			}
			if (preg_match('/style="([^"]+)"/', $moreatt, $reg)) {
				$morestyle = $reg[1];
				$moreatt = str_replace('style="' . $reg[1] . '"', '', $moreatt);
			}
			$moreatt = trim($moreatt);

			$enabledisablehtml = '<span class="' . $faprefix . ' ' . $fakey;
			$enabledisablehtml .= ($morecss ? ' ' . $morecss : '') . '" style="' . ($fasize ? ('font-size: ' . $fasize . ';') : '') . ($facolor ? (' color: ' . $facolor . ';') : '') . ($morestyle ? ' ' . $morestyle : '') . '"' . (($notitle || empty($titlealt)) ? '' : ' title="' . dol_escape_htmltag($titlealt) . '"') . ($moreatt ? ' ' . $moreatt : '') . '>';
			$enabledisablehtml .= '</span>';

			return $enabledisablehtml;
		}

		if (empty($srconly) && !preg_match('/[\.\/@]/', $picto)) {	// If original picto code does not contains a / and no . inside, it is not a path to an image file on disk
			$fakey = $pictowithouttext;
			$facolor = '';
			$fasize = '';
			$fa = getDolGlobalString('MAIN_FONTAWESOME_ICON_STYLE', 'fas');
			if (in_array($pictowithouttext, array('card', 'bell', 'clock', 'establishment', 'file', 'file-o', 'generic', 'minus-square', 'object_generic', 'pdf', 'plus-square', 'timespent', 'note', 'off', 'on', 'object_bookmark', 'bookmark', 'vcard'))) {
				$fa = 'far';
			}
			if (in_array($pictowithouttext, array('black-tie', 'discord', 'facebook', 'flickr', 'github', 'google', 'google-plus-g', 'instagram', 'linkedin', 'meetup', 'microsoft', 'pinterest', 'skype', 'slack', 'twitter', 'reddit', 'snapchat', 'stripe', 'stripe-s', 'tumblr', 'viadeo', 'whatsapp', 'youtube'))) {
				$fa = 'fab';
			}

			$arrayconvpictotofa = getImgPictoConv('fa');

			if ($pictowithouttext == 'off') {
				$fakey = 'fa-square';
				$fasize = '1.3em';
			} elseif ($pictowithouttext == 'on') {
				$fakey = 'fa-check-square';
				$fasize = '1.3em';
			} elseif ($pictowithouttext == 'listlight') {
				$fakey = 'fa-download';
				$marginleftonlyshort = 1;
			} elseif ($pictowithouttext == 'printer') {
				$fakey = 'fa-print';
				$fasize = '1.2em';
			} elseif ($pictowithouttext == 'note') {
				$fakey = 'fa-sticky-note';
				$marginleftonlyshort = 1;
			} elseif (in_array($pictowithouttext, array('1uparrow', '1downarrow', '1leftarrow', '1rightarrow', '1uparrow_selected', '1downarrow_selected', '1leftarrow_selected', '1rightarrow_selected'))) {
				$convertarray = array('1uparrow' => 'caret-up', '1downarrow' => 'caret-down', '1leftarrow' => 'caret-left', '1rightarrow' => 'caret-right', '1uparrow_selected' => 'caret-up', '1downarrow_selected' => 'caret-down', '1leftarrow_selected' => 'caret-left', '1rightarrow_selected' => 'caret-right');
				$fakey = 'fa-' . $convertarray[$pictowithouttext];
				if (preg_match('/selected/', $pictowithouttext)) {
					$facolor = '#888';
				}
				$marginleftonlyshort = 1;
			} elseif (!empty($arrayconvpictotofa[$pictowithouttext])) {
				$fakey = 'fa-' . $arrayconvpictotofa[$pictowithouttext];
			} else {
				$fakey = 'fa-' . $pictowithouttext;
			}

			if (in_array($pictowithouttext, array('dollyrevert', 'member', 'members', 'contract', 'group', 'resource', 'shipment', 'reception'))) {
				$morecss .= ' em092';
			}
			if (in_array($pictowithouttext, array('conferenceorbooth', 'eventorganization', 'holiday', 'info', 'info_black', 'project', 'workstation'))) {
				$morecss .= ' em088';
			}
			if (in_array($pictowithouttext, array('asset', 'intervention', 'payment', 'loan', 'partnership', 'stock', 'technic'))) {
				$morecss .= ' em080';
			}

			// Define $marginleftonlyshort
			$arrayconvpictotomarginleftonly = array(
				'bank',
				'check',
				'delete',
				'generic',
				'grip',
				'grip_title',
				'jabber',
				'grip_title',
				'grip',
				'listlight',
				'note',
				'on',
				'off',
				'playdisabled',
				'printer',
				'resize',
				'sign-out',
				'stats',
				'switch_on',
				'switch_on_grey',
				'switch_on_red',
				'switch_off',
				'switch_off_grey',
				'switch_off_red',
				'uparrow',
				'1uparrow',
				'1downarrow',
				'1leftarrow',
				'1rightarrow',
				'1uparrow_selected',
				'1downarrow_selected',
				'1leftarrow_selected',
				'1rightarrow_selected'
			);
			if (!array_key_exists($pictowithouttext, $arrayconvpictotomarginleftonly)) {
				$marginleftonlyshort = 0;
			}

			// Add CSS
			$arrayconvpictotomorcess = array(
				'action' => 'infobox-action',
				'account' => 'infobox-bank_account',
				'accounting_account' => 'infobox-bank_account',
				'accountline' => 'infobox-bank_account',
				'accountancy' => 'infobox-bank_account',
				'admin' => 'opacitymedium',
				'asset' => 'infobox-bank_account',
				'bank_account' => 'infobox-bank_account',
				'bill' => 'infobox-commande',
				'billa' => 'infobox-commande',
				'billr' => 'infobox-commande',
				'billd' => 'infobox-commande',
				'bookcal' => 'infobox-portal',
				'margin' => 'infobox-bank_account',
				'conferenceorbooth' => 'infobox-project',
				'cash-register' => 'infobox-portal',
				'contract' => 'infobox-contrat',
				'check' => 'font-status4',
				'conversation' => 'infobox-contrat',
				'donation' => 'infobox-commande',
				'dolly' => 'infobox-commande',
				'dollyrevert' => 'flip infobox-order_supplier',
				'ecm' => 'infobox-action',
				'eventorganization' => 'infobox-project',
				'hrm' => 'infobox-adherent',
				'group' => 'infobox-adherent',
				'intervention' => 'infobox-contrat',
				'incoterm' => 'infobox-supplier_proposal',
				'intracommreport' => 'infobox-bank_account',
				'currency' => 'infobox-bank_account',
				'multicurrency' => 'infobox-bank_account',
				'members' => 'infobox-adherent',
				'member' => 'infobox-adherent',
				'money-bill-alt' => 'infobox-bank_account',
				'order' => 'infobox-commande',
				'user' => 'infobox-adherent',
				'users' => 'infobox-adherent',
				'error' => 'pictoerror',
				'warning' => 'pictowarning',
				'switch_on' => 'font-status4',
				'switch_on_warning' => 'font-status4 warning',
				'switch_on_red' => 'font-status8',
				'switch_off_warning' => 'font-status4 warning',
				'switch_off_red' => 'font-status8',
				'holiday' => 'infobox-holiday',
				'info' => 'opacityhigh',
				'info_black' => 'purple',
				'invoice' => 'infobox-commande',
				'knowledgemanagement' => 'infobox-contrat rotate90',
				'loan' => 'infobox-commande',
				'payment' => 'infobox-bank_account',
				'payment_vat' => 'infobox-bank_account',
				'poll' => 'infobox-portal',
				'pos' => 'infobox-bank_account',
				'project' => 'infobox-project',
				'projecttask' => 'infobox-project',
				'propal' => 'infobox-propal',
				'proposal' => 'infobox-propal',
				'private' => 'infobox-project',
				'reception' => 'flip infobox-order_supplier',
				'recruitmentjobposition' => 'infobox-adherent',
				'recruitmentcandidature' => 'infobox-adherent',
				'resource' => 'infobox-action',
				'salary' => 'infobox-commande',
				'shapes' => 'infobox-adherent',
				'shipment' => 'infobox-commande',
				'store' => 'infobox-portal',
				'stripe' => 'infobox-bank_account',
				'supplier_invoice' => 'infobox-order_supplier',
				'supplier_invoicea' => 'infobox-order_supplier',
				'supplier_invoiced' => 'infobox-order_supplier',
				'supplier_invoicer' => 'infobox-order_supplier',
				'supplier' => 'infobox-order_supplier',
				'supplier_order' => 'infobox-order_supplier',
				'supplier_proposal' => 'infobox-supplier_proposal',
				'ticket' => 'infobox-contrat',
				'title_accountancy' => 'infobox-bank_account',
				'title_hrm' => 'infobox-holiday',
				'expensereport' => 'infobox-expensereport',
				'trip' => 'infobox-expensereport',
				'title_agenda' => 'infobox-action',
				'vat' => 'infobox-bank_account',
				'webportal' => 'infobox-portal',
				'website' => 'infobox-portal',
				//'title_setup'=>'infobox-action', 'tools'=>'infobox-action',
				'list-alt' => 'imgforviewmode',
				'calendar' => 'imgforviewmode',
				'calendarweek' => 'imgforviewmode',
				'calendarmonth' => 'imgforviewmode',
				'calendarday' => 'imgforviewmode',
				'calendarperuser' => 'imgforviewmode',
				'calendarpertype' => 'imgforviewmode'
			);
			if (!empty($arrayconvpictotomorcess[$pictowithouttext]) && strpos($picto, '_nocolor') === false) {
				$morecss .= ($morecss ? ' ' : '') . $arrayconvpictotomorcess[$pictowithouttext];
			}

			// Define $color
			$arrayconvpictotocolor = array(
				'address' => '#6c6aa8',
				'building' => '#6c6aa8',
				'bom' => '#a69944',
				'clone' => '#999',
				'cog' => '#999',
				'companies' => '#6c6aa8',
				'company' => '#6c6aa8',
				'contact' => '#6c6aa8',
				'cron' => '#555',
				'dynamicprice' => '#a69944',
				'edit' => '#444',
				'note' => '#999',
				'error' => '',
				'help' => '#bbb',
				'listlight' => '#999',
				'language' => '#555',
				//'dolly'=>'#a69944', 'dollyrevert'=>'#a69944',
				'lock' => '#ddd',
				'lot' => '#a69944',
				'map-marker-alt' => '#aaa',
				'mrp' => '#a69944',
				'product' => '#a69944',
				'service' => '#a69944',
				'inventory' => '#a69944',
				'stock' => '#a69944',
				'movement' => '#a69944',
				'other' => '#ddd',
				'world' => '#986c6a',
				'partnership' => '#6c6aa8',
				'playdisabled' => '#ccc',
				'printer' => '#444',
				'projectpub' => '#986c6a',
				'resize' => '#444',
				'rss' => '#cba',
				//'shipment'=>'#a69944',
				'search-plus' => '#808080',
				'security' => '#999',
				'square' => '#888',
				'stop-circle' => '#888',
				'stats' => '#444',
				'superadmin' => '#600',
				'switch_off' => '#999',
				'technic' => '#999',
				'tick' => '#282',
				'timespent' => '#555',
				'uncheck' => '#800',
				'uparrow' => '#555',
				'user-cog' => '#999',
				'country' => '#aaa',
				'globe-americas' => '#aaa',
				'region' => '#aaa',
				'state' => '#aaa',
				//'website' => '#304',
				'workstation' => '#a69944'
			);
			if (isset($arrayconvpictotocolor[$pictowithouttext]) && strpos($picto, '_nocolor') === false) {
				$facolor = $arrayconvpictotocolor[$pictowithouttext];
			}

			// This snippet only needed since function img_edit accepts only one additional parameter: no separate one for css only.
			// class/style need to be extracted to avoid duplicate class/style validation errors when $moreatt is added to the end of the attributes.
			$morestyle = '';
			$reg = array();
			if (preg_match('/class="([^"]+)"/', $moreatt, $reg)) {
				$morecss .= ($morecss ? ' ' : '') . $reg[1];
				$moreatt = str_replace('class="' . $reg[1] . '"', '', $moreatt);
			}
			if (preg_match('/style="([^"]+)"/', $moreatt, $reg)) {
				$morestyle = $reg[1];
				$moreatt = str_replace('style="' . $reg[1] . '"', '', $moreatt);
			}
			$moreatt = trim($moreatt);

			$enabledisablehtml = '<span class="' . $fa . ' ' . $fakey . ($marginleftonlyshort ? ($marginleftonlyshort == 1 ? ' marginleftonlyshort' : ' marginleftonly') : '');
			$enabledisablehtml .= ($morecss ? ' ' . $morecss : '') . '" style="' . ($fasize ? ('font-size: ' . $fasize . ';') : '') . ($facolor ? (' color: ' . $facolor . ';') : '') . ($morestyle ? ' ' . $morestyle : '') . '"' . (($notitle || empty($titlealt)) ? '' : ' title="' . dol_escape_htmltag($titlealt) . '"') . ($moreatt ? ' ' . $moreatt : '') . '>';
			$enabledisablehtml .= '</span>';

			return $enabledisablehtml;
		}

		if (getDolGlobalString('MAIN_OVERWRITE_THEME_PATH')) {
			$path = getDolGlobalString('MAIN_OVERWRITE_THEME_PATH') . '/theme/' . $theme; // If the theme does not have the same name as the module
		} elseif (getDolGlobalString('MAIN_OVERWRITE_THEME_RES')) {
			$path = getDolGlobalString('MAIN_OVERWRITE_THEME_RES') . '/theme/' . getDolGlobalString('MAIN_OVERWRITE_THEME_RES'); // To allow an external module to overwrite image resources whatever is activated theme
		} elseif (!empty($conf->modules_parts['theme']) && array_key_exists($theme, $conf->modules_parts['theme'])) {
			$path = $theme . '/theme/' . $theme; // If the theme have the same name as the module
		}

		// If we ask an image into $url/$mymodule/img (instead of default path)
		$regs = array();
		if (preg_match('/^([^@]+)@([^@]+)$/i', $picto, $regs)) {
			$picto = $regs[1];
			$path = $regs[2]; // $path is $mymodule
		}

		// Clean parameters
		if (!preg_match('/(\.png|\.gif|\.svg)$/i', $picto)) {
			$picto .= '.png';
		}
		// If alt path are defined, define url where img file is, according to physical path
		// ex: array(["main"]=>"/home/maindir/htdocs", ["alt0"]=>"/home/moddir0/htdocs", ...)
		foreach ($conf->file->dol_document_root as $type => $dirroot) {
			if ($type == 'main') {
				continue;
			}
			// This consumes a lot of time, that's why enabling alternative dir like "custom" dir should be avoid
			if (file_exists($dirroot . '/' . $path . '/img/' . $picto) && !empty($conf->file->dol_url_root)) {
				$url = DOL_URL_ROOT . $conf->file->dol_url_root[$type];
				break;
			}
		}

		// $url is '' or '/custom', $path is current theme or
		$fullpathpicto = $url . '/' . $path . '/img/' . $picto;
	}

	if ($srconly) {
		return $fullpathpicto;
	}

	// tag title is used for tooltip on <a>, tag alt can be used with very simple text on image for blind people
	return '<img src="' . $fullpathpicto . '"' . ($notitle ? '' : ' alt="' . dolPrintHTMLForAttribute($alt, 0, $allowothertags) . '"') . (($notitle || empty($titlealt)) ? '' : ' title="' . dolPrintHTMLForAttribute($titlealt, 0, $allowothertags) . '"') . ($moreatt ? ' ' . $moreatt . ($morecss ? ' class="' . $morecss . '"' : '') : ' class="inline-block' . ($morecss ? ' ' . $morecss : '') . '"') . '>'; // Alt is used for accessibility, title for popup
}

/**
 * Get array to convert the Dolibarr picto keys into Font awesome keys
 *
 * @param	string		$mode		'fa' to get conversion array for Font-Awesome
 * @return 	string[]				Array of conversion
 * @see img_picto()
 */
function getImgPictoConv($mode = 'fa')
{
	global $conf;

	if (empty($mode) || $mode == 'fa') {
		// Array when the fa picto key is different than the Dolibarr picto key.
		$arrayconvpictotofa = array(
			'account' => 'university',
			'accounting_account' => 'clipboard-list',
			'accountline' => 'receipt',
			'accountancy' => 'search-dollar',
			'action' => 'calendar-alt',
			'add' => 'plus-circle',
			'address' => 'address-book',
			'ai' => 'magic',
			'admin' => 'star',
			'asset' => 'money-check-alt',
			'autofill' => 'fill',
			'back' => 'arrow-left',
			'bank_account' => 'university',
			'bill' => 'file-invoice-dollar',
			'billa' => 'file-excel',
			'billr' => 'file-invoice-dollar',
			'billd' => 'file-medical',
			'blockedlog' => 'file-archive',
			'bookcal' => 'calendar-check',
			'supplier_invoice' => 'file-invoice-dollar',
			'supplier_invoicea' => 'file-excel',
			'supplier_invoicer' => 'file-invoice-dollar',
			'supplier_invoiced' => 'file-medical',
			'bom' => 'shapes',
			'card' => 'address-card',
			'chart' => 'chart-line',
			'company' => 'building',
			'contact' => 'address-book',
			'contract' => 'suitcase',
			'collab' => 'people-arrows',
			'conversation' => 'comments',
			'country' => 'globe-americas',
			'cron' => 'business-time',
			'cross' => 'times',
			'chevron-double-left' => 'angle-double-left',
			'chevron-double-right' => 'angle-double-right',
			'chevron-double-down' => 'angle-double-down',
			'chevron-double-top' => 'angle-double-up',
			'donation' => 'gift',
			'dynamicprice' => 'hand-holding-usd',
			'setup' => 'cog',
			'companies' => 'building',
			'products' => 'cube',
			'commercial' => 'suitcase',
			'invoicing' => 'coins',
			'accounting' => 'search-dollar',
			'category' => 'tag',
			'dollyrevert' => 'dolly',
			'file-o' => 'file',
			'generate' => 'plus-square',
			'hrm' => 'user-tie',
			'incoterm' => 'truck-loading',
			'margin' => 'calculator',
			'members' => 'user-friends',
			'ticket' => 'ticket-alt',
			'globe' => 'external-link-alt',
			'lot' => 'barcode',
			'email' => 'at',
			'establishment' => 'building',
			'edit' => 'pencil-alt',
			'entity' => 'globe',
			'graph' => 'chart-line',
			'grip_title' => 'arrows-alt',
			'grip' => 'arrows-alt',
			'help' => 'question-circle',
			'generic' => 'file',
			'holiday' => 'umbrella-beach',
			'info' => 'info-circle',
			'info_black' => 'info-circle',
			'inventory' => 'boxes',
			'intracommreport' => 'globe-europe',
			'jobprofile' => 'cogs',
			'knowledgemanagement' => 'ticket-alt',
			'label' => 'layer-group',
			'layout' => 'columns',
			'line' => 'bars',
			'loan' => 'money-bill-alt',
			'member' => 'user-alt',
			'meeting' => 'chalkboard-teacher',
			'mrp' => 'cubes',
			'next' => 'arrow-alt-circle-right',
			'trip' => 'wallet',
			'expensereport' => 'wallet',
			'group' => 'users',
			'movement' => 'people-carry',
			'sign-out' => 'sign-out-alt',
			'superadmin' => 'star',
			'switch_off' => 'toggle-off',
			'switch_off_grey' => 'toggle-off',
			'switch_off_warning' => 'toggle-off',
			'switch_off_red' => 'toggle-off',
			'switch_on' => 'toggle-on',
			'switch_on_grey' => 'toggle-on',
			'switch_on_warning' => 'toggle-on',
			'switch_on_red' => 'toggle-on',
			'check' => 'check',
			'bookmark' => 'star',
			'bank' => 'university',
			'close_title' => 'times',
			'delete' => 'trash',
			'filter' => 'filter',
			'list-alt' => 'list-alt',
			'calendarlist' => 'bars',
			'calendar' => 'calendar-alt',
			'calendarmonth' => 'calendar-alt',
			'calendarweek' => 'calendar-week',
			'calendarday' => 'calendar-day',
			'calendarperuser' => 'table',
			'calendarpertype' => 'table',
			'intervention' => 'ambulance',
			'invoice' => 'file-invoice-dollar',
			'order' => 'file-invoice',
			'error' => 'exclamation-triangle',
			'warning' => 'exclamation-triangle',
			'other' => 'square',
			'playdisabled' => 'play',
			'pdf' => 'file-pdf',
			'poll' => 'check-double',
			'pos' => 'cash-register',
			'preview' => 'binoculars',
			'project' => 'project-diagram',
			'projectpub' => 'project-diagram',
			'projecttask' => 'tasks',
			'propal' => 'file-signature',
			'proposal' => 'file-signature',
			'partnership' => 'handshake',
			'payment' => 'money-check-alt',
			'payment_vat' => 'money-check-alt',
			'pictoconfirm' => 'check-square',
			'phoning' => 'phone',
			'phoning_mobile' => 'mobile-alt',
			'phoning_fax' => 'fax',
			'previous' => 'arrow-alt-circle-left',
			'printer' => 'print',
			'product' => 'cube',
			'puce' => 'angle-right',
			'recent' => 'check-square',
			'reception' => 'dolly',
			'recruitmentjobposition' => 'id-card-alt',
			'recruitmentcandidature' => 'id-badge',
			'resize' => 'crop',
			'supplier_order' => 'dol-order_supplier',
			'supplier_proposal' => 'file-signature',
			'refresh' => 'redo',
			'region' => 'map-marked',
			'replacement' => 'exchange-alt',
			'resource' => 'laptop-house',
			'recurring' => 'history',
			'service' => 'concierge-bell',
			'skill' => 'shapes',
			'state' => 'map-marked-alt',
			'security' => 'key',
			'salary' => 'wallet',
			'shipment' => 'dolly',
			'stock' => 'box-open',
			'stats' => 'chart-bar',
			'split' => 'code-branch',
			'status' => 'stop-circle',
			'stripe' => 'stripe-s',
			'supplier' => 'building',
			'technic' => 'cogs',
			'tick' => 'check',
			'timespent' => 'clock',
			'title_setup' => 'tools',
			'title_accountancy' => 'money-check-alt',
			'title_bank' => 'university',
			'title_hrm' => 'umbrella-beach',
			'title_agenda' => 'calendar-alt',
			'uncheck' => 'times',
			'uparrow' => 'share',
			'url' => 'external-link-alt',
			'vat' => 'money-check-alt',
			'vcard' => 'arrow-alt-circle-down',
			'jabber' => 'comment',
			'website' => 'globe-americas',
			'workstation' => 'pallet',
			'webhook' => 'bullseye',
			'world' => 'globe',
			'private' => 'user-lock',
			'conferenceorbooth' => 'chalkboard-teacher',
			'eventorganization' => 'project-diagram',
			'webportal' => 'door-open'
		);

		if ($conf->currency == 'EUR') {
			$arrayconvpictotofa['currency'] = 'euro-sign';
			$arrayconvpictotofa['multicurrency'] = 'dollar-sign';
		} else {
			$arrayconvpictotofa['currency'] = 'dollar-sign';
			$arrayconvpictotofa['multicurrency'] = 'euro-sign';
		}
	} else {
		$arrayconvpictotofa = array();
	}

	return $arrayconvpictotofa;
}


/**
 *	Show a picto called object_picto (generic function)
 *
 *	@param	string		$titlealt			Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *	@param	string		$picto				Name of image to show object_picto (example: user, group, action, bill, contract, propal, product, ...)
 *											For external modules use imagename@mymodule to search into directory "img" of module.
 *	@param	string		$moreatt			Add more attribute on img tag (ie: class="datecallink")
 *	@param	int			$pictoisfullpath	If 1, image path is a full path
 *	@param	int			$srconly			Return only content of the src attribute of img.
 *  @param	int			$notitle			1=Disable tag title. Use it if you add js tooltip, to avoid duplicate tooltip.
 *  @param	string[]	$allowothertags		List of other tags allowed in title attribute
 *	@return	string							Return img tag
 *	@see	img_picto(), img_picto_common()
 */
function img_object($titlealt, $picto, $moreatt = '', $pictoisfullpath = 0, $srconly = 0, $notitle = 0, $allowothertags = array())
{
	if (strpos($picto, '^') === 0) {
		return img_picto($titlealt, str_replace('^', '', $picto), $moreatt, $pictoisfullpath, $srconly, $notitle, '', '', 2, $allowothertags);
	} else {
		return img_picto($titlealt, 'object_' . $picto, $moreatt, $pictoisfullpath, $srconly, $notitle, '', '', 2, $allowothertags);
	}
}

/**
 *	Show weather picto
 *
 *	@param      string		$titlealt         	Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *	@param      string|int	$picto       		Name of image file to show (If no extension provided, we use '.png'). Image must be stored into htdocs/theme/common directory. Or level of meteo image (0-4).
 *	@param		string		$moreatt			Add more attribute on img tag
 *	@param		int			$pictoisfullpath	If 1, image path is a full path
 *  @param      string      $morecss            More CSS
 *	@return     string      					Return img tag
 *  @see        img_object(), img_picto()
 */
function img_weather($titlealt, $picto, $moreatt = '', $pictoisfullpath = 0, $morecss = '')
{
	global $conf;

	if (is_numeric($picto)) {
		//$leveltopicto = array(0=>'weather-clear.png', 1=>'weather-few-clouds.png', 2=>'weather-clouds.png', 3=>'weather-many-clouds.png', 4=>'weather-storm.png');
		//$picto = $leveltopicto[$picto];
		return '<i class="fa fa-weather-level' . $picto . '"></i>';
	} elseif (!preg_match('/(\.png|\.gif)$/i', $picto)) {
		$picto .= '.png';
	}

	$path = DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/weather/' . $picto;

	return img_picto($titlealt, $path, $moreatt, 1, 0, 0, '', $morecss);
}

/**
 *	Show picto (generic function)
 *
 *	@param      string		$titlealt         	Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *	@param      string		$picto       		Name of image file to show (If no extension provided, we use '.png'). Image must be stored into htdocs/theme/common directory.
 *	@param		string		$moreatt			Add more attribute on img tag
 *	@param		int			$pictoisfullpath	If 1, image path is a full path
 *  @param		int			$notitle			1=Disable tag title. Use it if you add js tooltip, to avoid duplicate tooltip.
 *	@return     string      					Return img tag
 *  @see        img_object(), img_picto()
 */
function img_picto_common($titlealt, $picto, $moreatt = '', $pictoisfullpath = 0, $notitle = 0)
{
	global $conf;

	if (!preg_match('/(\.png|\.gif)$/i', $picto)) {
		$picto .= '.png';
	}

	if ($pictoisfullpath) {
		$path = $picto;
	} else {
		$path = DOL_URL_ROOT . '/theme/common/' . $picto;

		if (getDolGlobalInt('MAIN_MODULE_CAN_OVERWRITE_COMMONICONS')) {
			$themepath = DOL_DOCUMENT_ROOT . '/theme/' . $conf->theme . '/img/' . $picto;

			if (file_exists($themepath)) {
				$path = $themepath;
			}
		}
	}

	return img_picto($titlealt, $path, $moreatt, 1, 0, $notitle);
}

/**
 *	Show logo action
 *
 *	@param	string		$titlealt       Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *	@param  string		$numaction   	Action id or code to show
 *	@param 	string		$picto      	Name of image file to show ('filenew', ...)
 *                                      If no extension provided, we use '.png'. Image must be stored into theme/xxx/img directory.
 *                                      Example: picto.png                  if picto.png is stored into htdocs/theme/mytheme/img
 *                                      Example: picto.png@mymodule         if picto.png is stored into htdocs/mymodule/img
 *                                      Example: /mydir/mysubdir/picto.png  if picto.png is stored into htdocs/mydir/mysubdir (pictoisfullpath must be set to 1)
 *  @param	string		$moreatt		More attributes
 *	@return string      				Return an img tag
 */
function img_action($titlealt, $numaction, $picto = '', $moreatt = '')
{
	global $langs;

	if (empty($titlealt) || $titlealt == 'default') {
		if ($numaction == '-1' || $numaction == 'ST_NO') {
			$numaction = -1;
			$titlealt = $langs->transnoentitiesnoconv('ChangeDoNotContact');
		} elseif ($numaction == '0' || $numaction == 'ST_NEVER') {
			$numaction = 0;
			$titlealt = $langs->transnoentitiesnoconv('ChangeNeverContacted');
		} elseif ($numaction == '1' || $numaction == 'ST_TODO') {
			$numaction = 1;
			$titlealt = $langs->transnoentitiesnoconv('ChangeToContact');
		} elseif ($numaction == '2' || $numaction == 'ST_PEND') {
			$numaction = 2;
			$titlealt = $langs->transnoentitiesnoconv('ChangeContactInProcess');
		} elseif ($numaction == '3' || $numaction == 'ST_DONE') {
			$numaction = 3;
			$titlealt = $langs->transnoentitiesnoconv('ChangeContactDone');
		} else {
			$titlealt = $langs->transnoentitiesnoconv('ChangeStatus ' . $numaction);
			$numaction = 0;
		}
	}
	if (!is_numeric($numaction)) {
		$numaction = 0;
	}

	return img_picto($titlealt, (empty($picto) ? 'stcomm' . $numaction . '.png' : $picto), $moreatt);
}

/**
 *	Show logo "+"
 *
 *	@param	string	$titlealt   Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *	@param  string	$other      Add more attributes on img
 *	@return string      		Return tag img
 */
function img_edit_add($titlealt = 'default', $other = '')
{
	global $langs;

	if ($titlealt == 'default') {
		$titlealt = $langs->trans('Add');
	}

	return img_picto($titlealt, 'edit_add.png', $other);
}
/**
 *	Show logo "-"
 *
 *	@param	string	$titlealt	Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *	@param  string	$other      Add more attributes on img
 *	@return string      		Return tag img
 */
function img_edit_remove($titlealt = 'default', $other = '')
{
	global $langs;

	if ($titlealt == 'default') {
		$titlealt = $langs->trans('Remove');
	}

	return img_picto($titlealt, 'edit_remove.png', $other);
}

/**
 *	Show logo edit/modify fiche
 *
 *	@param  string	$titlealt   Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *	@param  integer	$float      If you have to put the style "float: right"
 *	@param  string	$other		Add more attributes on img
 *	@return string      		Return tag img
 */
function img_edit($titlealt = 'default', $float = 0, $other = '')
{
	global $langs;

	if ($titlealt == 'default') {
		$titlealt = $langs->trans('Modify');
	}

	return img_picto($titlealt, 'edit', ($float ? 'style="float: ' . ($langs->tab_translate["DIRECTION"] == 'rtl' ? 'left' : 'right') . '"' : "") . ($other ? ' ' . $other : ''));
}

/**
 *	Show logo view card
 *
 *	@param	string	$titlealt   Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *	@param  integer	$float      If you have to put the style "float: right"
 *	@param  string	$other		Add more attributes on img
 *	@return string      		Return tag img
 */
function img_view($titlealt = 'default', $float = 0, $other = 'class="valignmiddle"')
{
	global $langs;

	if ($titlealt == 'default') {
		$titlealt = $langs->trans('View');
	}

	$moreatt = ($float ? 'style="float: right" ' : '') . $other;

	return img_picto($titlealt, 'eye', $moreatt);
}

/**
 *  Show delete logo
 *
 *  @param	string	$titlealt   Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *	@param  string	$other      Add more attributes on img
 *  @param	string	$morecss	More CSS
 *  @return string      		Retourne tag img
 */
function img_delete($titlealt = 'default', $other = 'class="pictodelete"', $morecss = '')
{
	global $langs;

	if ($titlealt == 'default') {
		$titlealt = $langs->trans('Delete');
	}

	return img_picto($titlealt, 'delete', $other, 0, 0, 0, '', $morecss);
}

/**
 *  Show printer logo
 *
 *  @param  string  $titlealt   Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *  @param  string  $other      Add more attributes on img
 *  @return string              Retourne tag img
 */
function img_printer($titlealt = "default", $other = '')
{
	global $langs;
	if ($titlealt == "default") {
		$titlealt = $langs->trans("Print");
	}
	return img_picto($titlealt, 'printer', $other);
}

/**
 *  Show split logo
 *
 *  @param	string	$titlealt   Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *	@param  string	$other      Add more attributes on img
 *  @return string      		Retourne tag img
 */
function img_split($titlealt = 'default', $other = 'class="pictosplit"')
{
	global $langs;

	if ($titlealt == 'default') {
		$titlealt = $langs->trans('Split');
	}

	return img_picto($titlealt, 'split', $other);
}

/**
 *	Show help logo with cursor "?"
 *
 * 	@param	int              	$usehelpcursor		1=Use help cursor, 2=Use click pointer cursor, 0=No specific cursor
 * 	@param	int|string	        $usealttitle		Text to use as alt title
 * 	@return string            	           			Return tag img
 */
function img_help($usehelpcursor = 1, $usealttitle = 1)
{
	global $langs;

	if ($usealttitle) {
		if (is_string($usealttitle)) {
			$usealttitle = dol_escape_htmltag($usealttitle);
		} else {
			$usealttitle = $langs->trans('Info');
		}
	}

	return img_picto($usealttitle, 'info', 'style="vertical-align: middle;' . ($usehelpcursor == 1 ? ' cursor: help' : ($usehelpcursor == 2 ? ' cursor: pointer' : '')) . '"');
}

/**
 *	Show info logo
 *
 *	@param	string	$titlealt   Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *	@return string      		Return img tag
 */
function img_info($titlealt = 'default')
{
	global $langs;

	if ($titlealt == 'default') {
		$titlealt = $langs->trans('Informations');
	}

	return img_picto($titlealt, 'info', 'style="vertical-align: middle;"');
}

/**
 *	Show warning logo
 *
 *	@param	string	$titlealt   Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *	@param	string	$moreatt	Add more attribute on img tag (For example 'style="float: right"'). If 1, add float: right. Can't be "class" attribute.
 *  @param	string  $morecss	Add more CSS
 *	@return string      		Return img tag
 */
function img_warning($titlealt = 'default', $moreatt = '', $morecss = 'pictowarning')
{
	global $langs;

	if ($titlealt == 'default') {
		$titlealt = $langs->trans('Warning');
	}

	//return '<div class="imglatecoin">'.img_picto($titlealt, 'warning_white.png', 'class="pictowarning valignmiddle"'.($moreatt ? ($moreatt == '1' ? ' style="float: right"' : ' '.$moreatt): '')).'</div>';
	return img_picto($titlealt, 'warning', 'class="' . $morecss . '"' . ($moreatt ? ($moreatt == '1' ? ' style="float: right"' : ' ' . $moreatt) : ''));
}

/**
 *  Show error logo
 *
 *	@param	string	$titlealt   Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *	@return string      		Return img tag
 */
function img_error($titlealt = 'default')
{
	global $langs;

	if ($titlealt == 'default') {
		$titlealt = $langs->trans('Error');
	}

	return img_picto($titlealt, 'error');
}

/**
 *	Show next logo
 *
 *	@param	string	$titlealt   Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *	@param	string	$moreatt	Add more attribute on img tag (For example 'style="float: right"')
 *	@return string      		Return img tag
 */
function img_next($titlealt = 'default', $moreatt = '')
{
	global $langs;

	if ($titlealt == 'default') {
		$titlealt = $langs->trans('Next');
	}

	//return img_picto($titlealt, 'next.png', $moreatt);
	return '<span class="fa fa-chevron-right paddingright paddingleft" title="' . dol_escape_htmltag($titlealt) . '"></span>';
}

/**
 *	Show previous logo
 *
 *	@param	string	$titlealt   Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *	@param	string	$moreatt	Add more attribute on img tag (For example 'style="float: right"')
 *	@return string      		Return img tag
 */
function img_previous($titlealt = 'default', $moreatt = '')
{
	global $langs;

	if ($titlealt == 'default') {
		$titlealt = $langs->trans('Previous');
	}

	//return img_picto($titlealt, 'previous.png', $moreatt);
	return '<span class="fa fa-chevron-left paddingright paddingleft" title="' . dol_escape_htmltag($titlealt) . '"></span>';
}

/**
 *	Show down arrow logo
 *
 *	@param	string	$titlealt   Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *	@param  int		$selected   Selected
 *  @param	string	$moreclass	Add more CSS classes
 *	@return string      		Return img tag
 */
function img_down($titlealt = 'default', $selected = 0, $moreclass = '')
{
	global $langs;

	if ($titlealt == 'default') {
		$titlealt = $langs->trans('Down');
	}

	return img_picto($titlealt, ($selected ? '1downarrow_selected' : '1downarrow'), 'class="imgdown' . ($moreclass ? " " . $moreclass : "") . '"');
}

/**
 *	Show top arrow logo
 *
 *	@param	string	$titlealt   Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *	@param  int		$selected	Selected
 *  @param	string	$moreclass	Add more CSS classes
 *	@return string      		Return img tag
 */
function img_up($titlealt = 'default', $selected = 0, $moreclass = '')
{
	global $langs;

	if ($titlealt == 'default') {
		$titlealt = $langs->trans('Up');
	}

	return img_picto($titlealt, ($selected ? '1uparrow_selected' : '1uparrow'), 'class="imgup' . ($moreclass ? " " . $moreclass : "") . '"');
}

/**
 *	Show left arrow logo
 *
 *	@param	string	$titlealt   Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *	@param  int		$selected	Selected
 *	@param	string	$moreatt	Add more attribute on img tag (For example 'style="float: right"')
 *	@return string      		Return img tag
 */
function img_left($titlealt = 'default', $selected = 0, $moreatt = '')
{
	global $langs;

	if ($titlealt == 'default') {
		$titlealt = $langs->trans('Left');
	}

	return img_picto($titlealt, ($selected ? '1leftarrow_selected' : '1leftarrow'), $moreatt);
}

/**
 *	Show right arrow logo
 *
 *	@param	string	$titlealt   Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *	@param  int		$selected	Selected
 *	@param	string	$moreatt	Add more attribute on img tag (For example 'style="float: right"')
 *	@return string      		Return img tag
 */
function img_right($titlealt = 'default', $selected = 0, $moreatt = '')
{
	global $langs;

	if ($titlealt == 'default') {
		$titlealt = $langs->trans('Right');
	}

	return img_picto($titlealt, ($selected ? '1rightarrow_selected' : '1rightarrow'), $moreatt);
}

/**
 *	Show tick logo if allowed
 *
 *	@param	string	$allow		Allow
 *	@param	string	$titlealt   Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *	@return string      		Return img tag
 */
function img_allow($allow, $titlealt = 'default')
{
	global $langs;

	if ($titlealt == 'default') {
		$titlealt = $langs->trans('Active');
	}

	if ($allow == 1) {
		return img_picto($titlealt, 'tick');
	}

	return '-';
}

/**
 *	Return image of a credit card according to its brand name
 *
 *	@param  string	$brand		Brand name of credit card
 *  @param  string	$morecss	More CSS
 *	@return string     			Return img tag
 */
function img_credit_card($brand, $morecss = 'fa-2x inline-block valignmiddle')
{
	if (is_null($morecss)) {
		$morecss = 'fa-2x';
	}

	if ($brand == 'visa' || $brand == 'Visa') {
		$brand = 'cc-visa';
	} elseif ($brand == 'mastercard' || $brand == 'MasterCard') {
		$brand = 'cc-mastercard';
	} elseif ($brand == 'amex' || $brand == 'American Express') {
		$brand = 'cc-amex';
	} elseif ($brand == 'discover' || $brand == 'Discover') {
		$brand = 'cc-discover';
	} elseif ($brand == 'jcb' || $brand == 'JCB') {
		$brand = 'cc-jcb';
	} elseif ($brand == 'diners' || $brand == 'Diners club') {
		$brand = 'cc-diners-club';
	} elseif (!in_array($brand, array('cc-visa', 'cc-mastercard', 'cc-amex', 'cc-discover', 'cc-jcb', 'cc-diners-club'))) {
		$brand = 'credit-card';
	}

	return '<span class="fa fa-' . $brand . ' fa-fw' . ($morecss ? ' ' . $morecss : '') . '"></span>';
}

/**
 *	Show MIME img of a file
 *
 *	@param	string	$file		Filename
 * 	@param	string	$titlealt	Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *  @param	string	$morecss	More css
 *	@return string     			Return img tag
 */
function img_mime($file, $titlealt = '', $morecss = '')
{
	require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

	$mimetype = dol_mimetype($file, '', 1);
	//$mimeimg = dol_mimetype($file, '', 2);
	$mimefa = dol_mimetype($file, '', 4);

	if (empty($titlealt)) {
		$titlealt = 'Mime type: ' . $mimetype;
	}

	//return img_picto_common($titlealt, 'mime/'.$mimeimg, 'class="'.$morecss.'"');
	return '<i class="fa fa-' . $mimefa . ' ' . (preg_match('/pictofixedwidth/', $morecss) ? '' : 'paddingright ') . ($morecss ? ' ' . $morecss : '') . '"' . ($titlealt ? ' title="' . dolPrintHTMLForAttribute($titlealt) . '"' : '') . '></i>';
}


/**
 *  Show search logo
 *
 *  @param	string	$titlealt   Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *	@param  string	$other      Add more attributes on img
 *  @return string      		Retourne tag img
 */
function img_search($titlealt = 'default', $other = '')
{
	global $langs;

	if ($titlealt == 'default') {
		$titlealt = $langs->trans('Search');
	}

	$img = img_picto($titlealt, 'search', $other, 0, 1);

	$input = '<input type="image" class="liste_titre" name="button_search" src="' . $img . '" ';
	$input .= 'value="' . dol_escape_htmltag($titlealt) . '" title="' . dol_escape_htmltag($titlealt) . '" >';

	return $input;
}

/**
 *  Show search logo
 *
 *  @param	string	$titlealt   Text on alt and title of image. Alt only if param notitle is set to 1. If text is "TextA:TextB", use Text A on alt and Text B on title.
 *	@param  string	$other      Add more attributes on img
 *  @return string      		Retourne tag img
 */
function img_searchclear($titlealt = 'default', $other = '')
{
	global $langs;

	if ($titlealt == 'default') {
		$titlealt = $langs->trans('Search');
	}

	$img = img_picto($titlealt, 'searchclear.png', $other, 0, 1);

	$input = '<input type="image" class="liste_titre" name="button_removefilter" src="' . $img . '" ';
	$input .= 'value="' . dol_escape_htmltag($titlealt) . '" title="' . dol_escape_htmltag($titlealt) . '" >';

	return $input;
}

/**
 *	Show information in HTML for admin users or standard users
 *
 *	@param	string		$text				Text info
 *	@param  integer		$infoonimgalt		Info is shown only on alt of star picto, otherwise it is shown on output after the star picto
 *	@param	int			$nodiv				No div
 *  @param  string|int  $admin      	    '1'=Info for admin users. '0'=Info for standard users (change only the look), 'info', 'error', 'warning', 'xxx'=Other
 *  @param	string		$morecss			More CSS ('', 'warning', 'error')
 *  @param	string		$textfordropdown	Show a text to click to dropdown the info box.
 *  @param	string		$picto				'' or 'warning'
 *  @param  string		$textonpictotooltip	Text to show in tooltip on picto
 *  @param	string		$cssfordropdown		An additional css to avoid conflict to manage the dropdown if $textfordropdown is set
 *	@return	string							String with info text
 */
function info_admin($text, $infoonimgalt = 0, $nodiv = 0, $admin = '1', $morecss = 'hideonsmartphone', $textfordropdown = '', $picto = '', $textonpictotooltip = '', $cssfordropdown = 'info_admin')
{
	global $conf, $langs;

	if ($infoonimgalt) {
		$result = img_picto($text, 'info', 'class="' . ($morecss ? ' ' . $morecss : '') . '"');
	} else {
		if (empty($conf->use_javascript_ajax)) {
			$textfordropdown = '';
		}

		$class = (empty($admin) ? 'undefined' : ((string) $admin == '1' ? 'info' : $admin));
		$fa = 'info-circle';
		if ($picto == 'warning') {
			$fa = 'exclamation-triangle';
		}
		$result = ($nodiv ? '' : '<div class="wordbreak ' . $class . ($cssfordropdown ? ' ' . $cssfordropdown : '') . ($morecss ? ' ' . $morecss : '') . ($textfordropdown ? ' hidden' : '') . '">');
		$result .= img_picto(((string) $admin ? $langs->trans('InfoAdmin') : $langs->trans('Note')).($textonpictotooltip ? ' : '.$textonpictotooltip : ''), $fa);
		$result .= ' ';
		$result .= dol_escape_htmltag($text, 1, 0, 'div,span,b,br,a');
		$result .= ($nodiv ? '' : '</div>');

		if ($textfordropdown) {
			$tmpresult = '<span class="' . $class . ' '. $cssfordropdown.'text opacitymedium cursorpointer">' . $langs->trans($textfordropdown) . ' ' . img_picto($langs->trans($textfordropdown), '1downarrow') . '</span>';
			$tmpresult .= '<script nonce="' . getNonce() . '" type="text/javascript">
				jQuery(document).ready(function() {
					jQuery(".' . $cssfordropdown . 'text").click(function() {
						console.log("toggle text of .'.$cssfordropdown.'");
						jQuery(".' . $cssfordropdown . '").toggle().removeClass("hidden");
					});
				});
				</script>';

			$result = $tmpresult . $result;
		}
	}

	return $result;
}


/**
 *  Displays error message system with all the information to facilitate the diagnosis and the escalation of the bugs.
 *  This function must be called when a blocking technical error is encountered.
 *  However, one must try to call it only within php pages, classes must return their error through their property "error".
 *
 *	@param	 	DoliDB|null     $db      	Database handler
 *	@param  	string|string[] $error		String or array of errors strings to show
 *  @param		string[]|null   $errors		Array of errors
 *	@return 	void
 *  @see    	dol_htmloutput_errors()
 */
function dol_print_error($db = null, $error = '', $errors = null)
{
	global $conf, $langs, $user, $argv;
	global $dolibarr_main_prod;

	$out = '';
	$syslog = '';

	// If error occurs before the $lang object was loaded
	if (!$langs) {
		require_once DOL_DOCUMENT_ROOT . '/core/class/translate.class.php';
		$langs = new Translate('', $conf);
		$langs->load("main");
	}

	// Load translation files required by the error messages
	$langs->loadLangs(array('main', 'errors'));

	if ($_SERVER['DOCUMENT_ROOT']) {    // Mode web
		$out .= $langs->trans("DolibarrHasDetectedError") . ".<br>\n";
		if (getDolGlobalInt('MAIN_FEATURES_LEVEL') > 0) {
			$out .= "You use an experimental or develop level of features, so please do NOT report any bugs or vulnerability, except if problem is confirmed after moving option MAIN_FEATURES_LEVEL back to 0.<br>\n";
		}
		$out .= $langs->trans("InformationToHelpDiagnose") . ":<br>\n";

		$out .= "<b>" . $langs->trans("Date") . ":</b> " . dol_print_date(time(), 'dayhourlog') . "<br>\n";
		$out .= "<b>" . $langs->trans("Dolibarr") . ":</b> " . DOL_VERSION . " - https://www.dolibarr.org<br>\n";
		if (isset($conf->global->MAIN_FEATURES_LEVEL)) {
			$out .= "<b>" . $langs->trans("LevelOfFeature") . ":</b> " . getDolGlobalInt('MAIN_FEATURES_LEVEL') . "<br>\n";
		}
		if ($user instanceof User) {
			$out .= "<b>" . $langs->trans("Login") . ":</b> " . $user->login . "<br>\n";
		}
		if (function_exists("phpversion")) {
			$out .= "<b>" . $langs->trans("PHP") . ":</b> " . phpversion() . "<br>\n";
		}
		$out .= "<b>" . $langs->trans("Server") . ":</b> " . (isset($_SERVER["SERVER_SOFTWARE"]) ? dol_htmlentities($_SERVER["SERVER_SOFTWARE"], ENT_COMPAT) : '') . "<br>\n";
		if (function_exists("php_uname")) {
			$out .= "<b>" . $langs->trans("OS") . ":</b> " . php_uname() . "<br>\n";
		}
		$out .= "<b>" . $langs->trans("UserAgent") . ":</b> " . (isset($_SERVER["HTTP_USER_AGENT"]) ? dol_htmlentities($_SERVER["HTTP_USER_AGENT"], ENT_COMPAT) : '') . "<br>\n";
		$out .= "<br>\n";
		$out .= "<b>" . $langs->trans("RequestedUrl") . ":</b> " . (isset($_SERVER["REQUEST_URI"]) ? dol_htmlentities($_SERVER["REQUEST_URI"], ENT_COMPAT) : '') . "<br>\n";
		$out .= "<b>" . $langs->trans("Referer") . ":</b> " . (isset($_SERVER["HTTP_REFERER"]) ? dol_htmlentities($_SERVER["HTTP_REFERER"], ENT_COMPAT) : '') . "<br>\n";
		$out .= "<b>" . $langs->trans("MenuManager") . ":</b> " . (isset($conf->standard_menu) ? dol_htmlentities($conf->standard_menu, ENT_COMPAT) : '') . "<br>\n";
		$out .= "<br>\n";
		$syslog .= "url=" . (isset($_SERVER["REQUEST_URI"]) ? dol_escape_htmltag($_SERVER["REQUEST_URI"]) : '');
		$syslog .= ", query_string=" . (isset($_SERVER["QUERY_STRING"]) ? dol_escape_htmltag($_SERVER["QUERY_STRING"]) : '');
	} else { // Mode CLI
		$out .= '> ' . $langs->transnoentities("ErrorInternalErrorDetected") . ":\n" . $argv[0] . "\n";
		$syslog .= "pid=" . dol_getmypid();
	}

	if (!empty($conf->modules)) {
		$out .= "<b>" . $langs->trans("Modules") . ":</b> " . implode(', ', $conf->modules) . "<br>\n";
	}

	if (is_object($db)) {
		if ($_SERVER['DOCUMENT_ROOT']) {  // Mode web
			$out .= "<b>" . $langs->trans("DatabaseTypeManager") . ":</b> " . $db->type . "<br>\n";
			$lastqueryerror = $db->lastqueryerror();
			if (!utf8_check($lastqueryerror)) {
				$lastqueryerror = "SQL error string is not a valid UTF8 string. We can't show it.";
			}
			$out .= "<b>" . $langs->trans("RequestLastAccessInError") . ":</b> " . ($lastqueryerror ? dol_escape_htmltag($lastqueryerror) : $langs->trans("ErrorNoRequestInError")) . "<br>\n";
			$out .= "<b>" . $langs->trans("ReturnCodeLastAccessInError") . ":</b> " . ($db->lasterrno() ? dol_escape_htmltag($db->lasterrno()) : $langs->trans("ErrorNoRequestInError")) . "<br>\n";
			$out .= "<b>" . $langs->trans("InformationLastAccessInError") . ":</b> " . ($db->lasterror() ? dol_escape_htmltag($db->lasterror()) : $langs->trans("ErrorNoRequestInError")) . "<br>\n";
			$out .= "<br>\n";
		} else { // Mode CLI
			// No dol_escape_htmltag for output, we are in CLI mode
			$out .= '> ' . $langs->transnoentities("DatabaseTypeManager") . ":\n" . $db->type . "\n";
			$out .= '> ' . $langs->transnoentities("RequestLastAccessInError") . ":\n" . ($db->lastqueryerror() ? $db->lastqueryerror() : $langs->transnoentities("ErrorNoRequestInError")) . "\n";
			$out .= '> ' . $langs->transnoentities("ReturnCodeLastAccessInError") . ":\n" . ($db->lasterrno() ? $db->lasterrno() : $langs->transnoentities("ErrorNoRequestInError")) . "\n";
			$out .= '> ' . $langs->transnoentities("InformationLastAccessInError") . ":\n" . ($db->lasterror() ? $db->lasterror() : $langs->transnoentities("ErrorNoRequestInError")) . "\n";
		}
		$syslog .= ", sql=" . $db->lastquery();
		$syslog .= ", db_error=" . $db->lasterror();
	}

	if ($error || $errors) {
		// Merge all into $errors array
		if (is_array($error) && is_array($errors)) {
			$errors = array_merge($error, $errors);
		} elseif (is_array($error)) {	// deprecated, use second parameters
			$errors = $error;
		} elseif (is_array($errors) && !empty($error)) {
			$errors = array_merge(array($error), $errors);
		} elseif (!empty($error)) {
			$errors = array_merge(array($error), array($errors));
		}

		$langs->load("errors");

		foreach ($errors as $msg) {
			if (empty($msg)) {
				continue;
			}
			if ($_SERVER['DOCUMENT_ROOT']) {  // Mode web
				$out .= "<b>" . $langs->trans("Message") . ":</b> " . dol_escape_htmltag($msg) . "<br>\n";
			} else { // Mode CLI
				$out .= '> ' . $langs->transnoentities("Message") . ":\n" . $msg . "\n";
			}
			$syslog .= ", msg=" . $msg;
		}
	}
	if (empty($dolibarr_main_prod) && $_SERVER['DOCUMENT_ROOT'] && function_exists('xdebug_print_function_stack') && function_exists('xdebug_call_file')) {
		xdebug_print_function_stack();
		$out .= '<b>XDebug information:</b>' . "<br>\n";
		$out .= 'File: ' . xdebug_call_file() . "<br>\n";
		$out .= 'Line: ' . xdebug_call_line() . "<br>\n";
		$out .= 'Function: ' . xdebug_call_function() . "<br>\n";
		$out .= "<br>\n";
	}

	// Return a http header with error code if possible
	if (!headers_sent()) {
		if (function_exists('top_httphead')) {	// In CLI context, the method does not exists
			top_httphead();
		}
		//http_response_code(500);		// If we use 500, message is not output with some command line tools
		http_response_code(202);		// If we use 202, this is not really an error message, but this allow to output message on command line tools
	}

	if (empty($dolibarr_main_prod)) {
		print $out;
	} else {
		if (empty($langs->defaultlang)) {
			$langs->setDefaultLang();
		}
		$langs->loadLangs(array("main", "errors")); // Reload main because language may have been set only on previous line so we have to reload files we need.
		// This should not happen, except if there is a bug somewhere. Enabled and check log in such case.
		print 'This website or feature is currently temporarily not available or failed after a technical error.<br><br>This may be due to a maintenance operation. Current status of operation (' . dol_print_date(dol_now(), 'dayhourrfc') . ') are on next line...<br><br>' . "\n";
		print $langs->trans("DolibarrHasDetectedError") . '. ';
		print $langs->trans("YouCanSetOptionDolibarrMainProdToZero");
		if (!defined("MAIN_CORE_ERROR")) {
			define("MAIN_CORE_ERROR", 1);
		}
	}

	dol_syslog("Error " . $syslog, LOG_ERR);
}

/**
 * Show a public email and error code to contact if technical error
 *
 * @param	string		$prefixcode		Prefix of public error code
 * @param	string  	$errormessage	Complete error message
 * @param	string[]	$errormessages	Array of error messages
 * @param	string		$morecss		More css
 * @param	string		$email			Email
 * @return	void
 */
function dol_print_error_email($prefixcode, $errormessage = '', $errormessages = array(), $morecss = 'error', $email = '')
{
	global $langs;

	if (empty($email)) {
		$email = getDolGlobalString('MAIN_INFO_SOCIETE_MAIL');
	}

	$langs->load("errors");
	$now = dol_now();

	print '<br><div class="center login_main_message"><div class="' . $morecss . '">';
	print $langs->trans("ErrorContactEMail", $email, $prefixcode . '-' . dol_print_date($now, '%Y%m%d%H%M%S'));
	if ($errormessage) {
		print '<br><br>' . $errormessage;
	}
	if (is_array($errormessages) && count($errormessages)) {
		foreach ($errormessages as $mesgtoshow) {
			print '<br><br>' . $mesgtoshow;
		}
	}
	print '</div></div>';
}

/**
 *	Show title line of an array
 *
 *	@param	?string	$name        Label of field
 *	@param	string	$file        Url used when we click on sort picto
 *	@param	string	$field       Field to use for new sorting
 *	@param	string	$begin       ("" by default)
 *	@param	string	$param       Add more parameters on sort url links ("" by default)
 *	@param  string	$moreattrib  Options of attribute td ("" by default)
 *	@param  ?string	$sortfield   Current field used to sort
 *	@param  ?string	$sortorder   Current sort order
 *  @param	string	$prefix		 Prefix for css. Use space after prefix to add your own CSS tag, for example 'mycss '.
 *  @param	?string	$tooltip	 Tooltip
 *  @param	int		$forcenowrapcolumntitle		No need to use 'wrapcolumntitle' css style
 *	@return	void
 */
function print_liste_field_titre($name, $file = "", $field = "", $begin = "", $param = "", $moreattrib = "", $sortfield = "", $sortorder = "", $prefix = "", $tooltip = "", $forcenowrapcolumntitle = 0)
{
	print getTitleFieldOfList($name, 0, $file, $field, $begin, $param, $moreattrib, $sortfield, $sortorder, $prefix, 0, $tooltip, $forcenowrapcolumntitle);
}

/**
 *	Get title line of an array
 *
 *	@param	?string		$name						Translation key of field to show or complete HTML string to show
 *	@param	int<0,2>	$thead	 					0=To use with standard table format, 1=To use inside <thead><tr>, 2=To use with <div>
 *	@param	string		$file						Url used when we click on sort picto
 *	@param	string		$field						Field to use for new sorting. Empty if this field is not sortable. Example "t.abc" or "t.abc,t.def"
 *	@param	string		$begin       				("" by default)
 *	@param	string		$moreparam					Add more parameters on sort url links ("" by default)
 *	@param  string		$moreattrib					Add more attributes on th ("" by default). To add more css class, use param $prefix.
 *	@param  ?string		$sortfield	 				Current field used to sort (Ex: 'd.datep,d.id')
 *	@param  ?string		$sortorder					Current sort order (Ex: 'asc,desc')
 *  @param	string		$prefix	 					Prefix for css. Use space after prefix to add your own CSS tag, for example 'mycss '.
 *  @param	int<0,1>	$disablesortlink			1=Disable sort link
 *  @param	?string		$tooltip 					Text of tooltip with syntax 'Tooltip' or 'Tooltip:[keytoenabledtheonclicktooltip]:[tooltipdirection]'
 *  @param	int<0,1> 	$forcenowrapcolumntitle		No need to use 'wrapcolumntitle' css style
 *	@return	string
 */
function getTitleFieldOfList($name, $thead = 0, $file = "", $field = "", $begin = "", $moreparam = "", $moreattrib = "", $sortfield = "", $sortorder = "", $prefix = "", $disablesortlink = 0, $tooltip = '', $forcenowrapcolumntitle = 0)
{
	global $langs, $form;
	//print "$name, $file, $field, $begin, $options, $moreattrib, $sortfield, $sortorder<br>\n";

	if ($moreattrib == 'class="right"') {
		$prefix .= 'right '; // For backward compatibility
	}

	$tooltip = (string) $tooltip;	// In case $tooltip is null

	$sortorder = strtoupper((string) $sortorder);
	$out = '';
	$sortimg = '';

	$tag = 'th';
	if ($thead == 2) {
		$tag = 'div';
	}

	$tmpsortfield = explode(',', (string) $sortfield);
	$sortfield1 = trim($tmpsortfield[0]); // If $sortfield is 'd.datep,d.id', it becomes 'd.datep'
	$tmpfield = explode(',', $field);
	$field1 = trim($tmpfield[0]); // If $field is 'd.datep,d.id', it becomes 'd.datep'

	if (strpos((string) $tooltip, ':') !== false) {
		$tmptooltip = explode(':', (string) $tooltip);
	} else {
		$tmptooltip = array($tooltip);
	}

	$wrapcolumntitle = (empty($forcenowrapcolumntitle) || (!empty($tmptooltip[2]) && $tmptooltip[2] == '-1'));

	if (!getDolGlobalString('MAIN_DISABLE_WRAPPING_ON_COLUMN_TITLE') && $wrapcolumntitle) {
		$prefix = 'wrapcolumntitle ' . $prefix;
	}

	//var_dump('field='.$field.' field1='.$field1.' sortfield='.$sortfield.' sortfield1='.$sortfield1);
	// If field is used as sort criteria we use a specific css class liste_titre_sel
	// Example if (sortfield,field)=("nom","xxx.nom") or (sortfield,field)=("nom","nom")
	$liste_titre = 'liste_titre';
	if ($field1 && ($sortfield1 == $field1 || $sortfield1 == preg_replace("/^[^\.]+\./", "", $field1))) {
		$liste_titre = 'liste_titre_sel';
	}

	$tagstart = '<' . $tag . ' class="' . $prefix . $liste_titre . '" ' . $moreattrib;
	//$out .= (($field && empty($conf->global->MAIN_DISABLE_WRAPPING_ON_COLUMN_TITLE) && preg_match('/^[a-zA-Z_0-9\s\.\-:&;]*$/', $name)) ? ' title="'.dol_escape_htmltag($langs->trans($name)).'"' : '');
	$tagstart .= ($name && !getDolGlobalString('MAIN_DISABLE_WRAPPING_ON_COLUMN_TITLE') && $wrapcolumntitle && !dol_textishtml($name)) ? ' title="' . dolPrintHTMLForAttribute($langs->trans($name)) . '"' : '';
	$tagstart .= '>';

	if (empty($thead) && $field && empty($disablesortlink)) {    // If this is a sort field
		$options = preg_replace('/sortfield=([a-zA-Z0-9,\s\.]+)/i', '', (is_scalar($moreparam) ? $moreparam : ''));
		$options = preg_replace('/sortorder=([a-zA-Z0-9,\s\.]+)/i', '', $options);
		$options = preg_replace('/&+/i', '&', $options);
		if (!preg_match('/^&/', $options)) {
			$options = '&' . $options;
		}

		$sortordertouseinlink = '';
		if ($field1 != $sortfield1) { // We are on another field than current sorted field
			if (preg_match('/^DESC/i', $sortorder)) {
				$sortordertouseinlink .= str_repeat('desc,', count(explode(',', $field)));
			} else { // We reverse the var $sortordertouseinlink
				$sortordertouseinlink .= str_repeat('asc,', count(explode(',', $field)));
			}
		} else { // We are on field that is the first current sorting criteria
			if (preg_match('/^ASC/i', $sortorder)) {	// We reverse the var $sortordertouseinlink
				$sortordertouseinlink .= str_repeat('desc,', count(explode(',', $field)));
			} else {
				$sortordertouseinlink .= str_repeat('asc,', count(explode(',', $field)));
			}
		}
		$sortordertouseinlink = preg_replace('/,$/', '', $sortordertouseinlink);
		$out .= '<a class="reposition" href="' . dolBuildUrl($file, ['sortfield' => $field, 'sortorder' => $sortordertouseinlink, 'begin' => $begin]) . $options . '"';
		//$out .= (getDolGlobalString('MAIN_DISABLE_WRAPPING_ON_COLUMN_TITLE') ? '' : ' title="'.dol_escape_htmltag($langs->trans($name)).'"');
		$out .= '>';
	}
	if ($tooltip && $tmptooltip[0]) {
		// You can also use 'TranslationString:[keyfortooltiponclick]:[tooltipdirection]' for a tooltip on click or to change tooltip position.
		$out .= $form->textwithpicto($langs->trans((string) $name), $langs->trans((string) $tmptooltip[0]), (empty($tmptooltip[2]) ? '1' : $tmptooltip[2]), 'help', ((!empty($tmptooltip[2]) && $tmptooltip[2] == '-1') ? 'paddingrightonly' : ''), 0, 3, (empty($tmptooltip[1]) ? '' : 'extra_' . str_replace('.', '_', $field) . '_' . $tmptooltip[1]));
	} else {
		$out .= $langs->trans((string) $name);
	}

	if (empty($thead) && $field && empty($disablesortlink)) {    // If this is a sort field
		$out .= '</a>';
	}

	if (empty($thead) && $field) {    // If this is a sort field
		$options = preg_replace('/sortfield=([a-zA-Z0-9,\s\.]+)/i', '', (is_scalar($moreparam) ? $moreparam : ''));
		$options = preg_replace('/sortorder=([a-zA-Z0-9,\s\.]+)/i', '', $options);
		$options = preg_replace('/&+/i', '&', $options);
		if (!preg_match('/^&/', $options)) {
			$options = '&' . $options;
		}

		if (!$sortorder || ($field1 != $sortfield1)) {
			// Nothing
		} else {
			if (preg_match('/^DESC/', $sortorder)) {
				$sortimg .= '<span class="nowrap">' . img_up("Z-A", 0, 'paddingright') . '</span>';
			}
			if (preg_match('/^ASC/', $sortorder)) {
				$sortimg .= '<span class="nowrap">' . img_down("A-Z", 0, 'paddingright') . '</span>';
			}
		}
	}

	$tagend = '</' . $tag . '>';

	$out = $tagstart . $sortimg . $out . $tagend;

	return $out;
}

/**
 *	Show a title.
 *
 *	@param	string	$title			Title to show
 *  @return	void
 *  @deprecated						Use load_fiche_titre instead
 *  @see load_fiche_titre()
 */
function print_titre($title)
{
	dol_syslog(__FUNCTION__ . " is deprecated", LOG_WARNING);

	print '<div class="titre">' . $title . '</div>';
}

/**
 *	Show a title with picto
 *
 *	@param	string	$title				Title to show
 *	@param	string	$mesg				Added message to show on right
 *	@param	string	$picto				Icon to use before title (should be a 32x32 transparent png file)
 *	@param	int		$pictoisfullpath	1=Icon name is a full absolute url of image
 * 	@param	string	$id					To force an id on html objects by example id="name" where name is id
 * 	@return	void
 *  @deprecated Use print load_fiche_titre instead
 */
function print_fiche_titre($title, $mesg = '', $picto = 'generic', $pictoisfullpath = 0, $id = '')
{
	print load_fiche_titre($title, $mesg, $picto, $pictoisfullpath, $id);
}

/**
 *	Load a title with picto
 *
 *	@param	string		$title				Title to show (HTML sanitized content). Can be a string with a <br> as a second string shown under the fmain title.
 *	@param	string		$morehtmlright		Added message to show on right
 *	@param	string		$picto				Icon to use before title (should be a 32x32 transparent png file)
 *	@param	int<0,1>	$pictoisfullpath	1=Icon name is a full absolute url of image
 * 	@param	string		$id					To force an id on html objects
 *  @param  string  	$morecssontable     More css on table
 *	@param	string		$morehtmlcenter		Added message to show on center
 *  @param	string		$morecssonpicto		More css on picto
 * 	@return	string
 *  @see print_barre_liste()
 */
function load_fiche_titre($title, $morehtmlright = '', $picto = 'generic', $pictoisfullpath = 0, $id = '', $morecssontable = '', $morehtmlcenter = '', $morecssonpicto = 'widthpictotitle')
{
	$return = '';

	if ($picto == 'setup') {
		$picto = 'generic';
	}

	$return .= "\n";
	$return .= '<table ' . ($id ? 'id="' . $id . '" ' : '') . 'class="centpercent notopnoleftnoright table-fiche-title' . ($morecssontable ? ' ' . $morecssontable : '') . '">'; // margin bottom must be same than into print_barre_list
	$return .= '<tr class="toptitle">';
	if ($picto) {
		$return .= '<td class="nobordernopadding widthpictotitle valignmiddle col-picto">' . img_picto('', $picto, 'class="valignmiddle pictotitle'.($morecssonpicto ? ' '.$morecssonpicto : '').'"', $pictoisfullpath) . '</td>';
	}
	$return .= '<td class="nobordernopadding valignmiddle col-title">';
	$return .= '<div class="titre inline-block">';
	$return .= '<span class="inline-block valignmiddle print-barre-liste">' . $title . '</span>';	// $title is already HTML sanitized content
	$return .= '</div>';
	$return .= '</td>';
	if (dol_strlen($morehtmlcenter)) {
		$return .= '<td class="nobordernopadding center valignmiddle col-center">' . $morehtmlcenter . '</td>';
	}
	if (dol_strlen($morehtmlright)) {
		$return .= '<td class="nobordernopadding titre_right wordbreakimp right valignmiddle col-right">' . $morehtmlright . '</td>';
	}
	$return .= '</tr></table>' . "\n";

	return $return;
}

/**
 *	Print a title with navigation controls for pagination
 *
 *	@param	string	    $title				Title to show (required). Can be a string with a <br> as a substring.
 *	@param	int|null    $page				Numero of page to show in navigation links (required)
 *	@param	string	    $file				Url of page (required)
 *	@param	string	    $options         	More parameters for links ('' by default, does not include sortfield neither sortorder). Value must be 'urlencoded' before calling function.
 *	@param	?string    	$sortfield       	Field to sort on ('' by default)
 *	@param	?string	    $sortorder       	Order to sort ('' by default)
 *	@param	string	    $morehtmlcenter     String in the middle ('' by default). We often find here string $massaction coming from $form->selectMassAction()
 *	@param	int		    $num				Number of records found by select with limit+1
 *	@param	int|string  $totalnboflines		Total number of records/lines for all pages (if known). Use a negative value of number to not show number. Use '' if unknown. Use a string to show a string.
 *	@param	string	    $picto				Icon to use before title (should be a 32x32 transparent png file)
 *	@param	int		    $pictoisfullpath	1=Icon name is a full absolute url of image
 *  @param	string	    $morehtmlright		More html to show (after arrows)
 *  @param  string      $morecss            More css to the table
 *  @param  int         $limit              Max number of lines (-1 = use default, 0 = no limit, > 0 = limit).
 *  @param  int|string  $selectlimitsuffix    Suffix for limit ID or -1 to hide the select limit combo
 *  @param  int         $hidenavigation     Force to hide the arrows and page for navigation
 *  @param  int			$pagenavastextinput 1=Do not suggest list of pages to navigate but suggest the page number into an input field.
 *  @param	string		$morehtmlrightbeforearrow	More html to show (before arrows)
 *	@return	void
 */
function print_barre_liste($title, $page, $file, $options = '', $sortfield = '', $sortorder = '', $morehtmlcenter = '', $num = -1, $totalnboflines = '', $picto = 'generic', $pictoisfullpath = 0, $morehtmlright = '', $morecss = '', $limit = -1, $selectlimitsuffix = 0, $hidenavigation = 0, $pagenavastextinput = 0, $morehtmlrightbeforearrow = '')
{
	global $conf, $langs;

	$savlimit = $limit;
	$savtotalnboflines = $totalnboflines;
	if (is_numeric($totalnboflines)) {
		$totalnboflines = abs($totalnboflines);
	}

	// Detect if there is a subtitle
	$subtitle = '';
	$tmparray = preg_split('/<br>/i', $title, 2);
	if (!empty($tmparray[1])) {
		$title = $tmparray[0];
		$subtitle = $tmparray[1];
	}

	$page = (int) $page;

	if ($picto == 'setup') {
		$picto = 'title_setup';
	}
	if (($conf->browser->name == 'ie') && $picto == 'generic') {
		$picto = 'title.gif';
	}
	if ($limit < 0) {
		$limit = $conf->liste_limit;
	}

	if ($savlimit != 0 && (($num > $limit) || ($num == -1) || ($limit == 0))) {
		$nextpage = 1;
	} else {
		$nextpage = 0;
	}
	//print 'totalnboflines='.$totalnboflines.'-savlimit='.$savlimit.'-limit='.$limit.'-num='.$num.'-nextpage='.$nextpage.'-selectlimitsuffix='.$selectlimitsuffix.'-hidenavigation='.$hidenavigation;

	print "\n";
	print "<!-- Begin print_barre_liste -->\n";
	print '<table class="centpercent notopnoleftnoright table-fiche-title' . ($morecss ? ' ' . $morecss : '') . '">';
	print '<tr class="toptitle">'; // margin bottom must be same than into load_fiche_tire

	// Left

	if ($picto && $title) {
		print '<td class="nobordernopadding widthpictotitle valignmiddle col-picto">';
		print img_picto('', $picto, 'class="valignmiddle pictotitle widthpictotitle"', $pictoisfullpath);
		print '</td>';
	}

	print '<td class="nobordernopadding valignmiddle col-title">';
	print '<div class="titre inline-block nowrap">';
	print '<span class="inline-block valignmiddle print-barre-liste">' . $title . '</span>';	// $title may contains HTML like a combo list from page consumption.php, so we do not use dolPrintLabel here()
	if (!empty($title) && $savtotalnboflines >= 0 && (string) $savtotalnboflines != '') {
		if (is_numeric($totalnboflines) && (int) $totalnboflines > 0) {
			print '<span class="opacitymedium colorblack marginleftonly totalnboflines valignmiddle" title="' . $langs->trans("NbRecordQualified") . '">(' . $totalnboflines . ')</span>';
		} else {
			print '<span class="opacitymedium colorblack marginleftonly totalnboflines valignmiddle">(' . $totalnboflines . ')</span>';
		}
	}
	print '</div>';
	if (!empty($subtitle)) {
		print '<br><div class="subtitle inline-block hideonsmartphone">' . $subtitle . '</div>';
	}
	print '</td>';

	// Center
	if ($morehtmlcenter && empty($conf->dol_optimize_smallscreen)) {
		print '<td class="nobordernopadding center valignmiddle col-center">' . $morehtmlcenter . '</td>';
	}

	// Right
	print '<td class="nobordernopadding valignmiddle right col-right">';
	print '<input type="hidden" name="pageplusoneold" value="' . ((int) $page + 1) . '">';
	$query = [];
	parse_str($options, $query);
	if ($sortfield) {
		$query += ['sortfield' => $sortfield];
	}
	if ($sortorder) {
		$query += ['sortorder' => $sortorder];
	}

	$options = '&' . http_build_query($query);
	if ($page) {
		$query = array_merge($query, ['page' => $page]);
	}
	// Show navigation bar
	$pagelist = '';
	if ($savlimit != 0 && ($page > 0 || $num > $limit)) {
		if ($totalnboflines) {	// If we know total nb of lines
			// Define nb of extra page links before and after selected page + ... + first or last
			$maxnbofpage = (empty($conf->dol_optimize_smallscreen) ? 4 : 0);

			if ($limit > 0) {
				$nbpages = ceil($totalnboflines / $limit);
			} else {
				$nbpages = 1;
			}
			$cpt = ($page - $maxnbofpage);
			if ($cpt < 0) {
				$cpt = 0;
			}

			if ($cpt >= 1) {
				if (empty($pagenavastextinput)) {
					$query['page'] = 0;
					$pagelist .= '<li class="pagination"><a class="reposition" href="' . dolBuildUrl($file, $query) . '">1</a></li>';
					if ($cpt > 2) {
						$pagelist .= '<li class="pagination"><span class="inactive">...</span></li>';
					} elseif ($cpt == 2) {
						$query['page'] = 0;
						$pagelist .= '<li class="pagination"><a class="reposition" href="' . dolBuildUrl($file, $query) . '">2</a></li>';
					}
				}
			}

			do {
				if ($pagenavastextinput) {
					if ($cpt == $page) {
						$pagelist .= '<li class="pagination pageplusone valignmiddle"><input type="text" class="' . ($totalnboflines > 100 ? 'width40' : 'width25') . ' center pageplusone heightofcombo" name="pageplusone" value="' . ($page + 1) . '"></li>';
						$pagelist .= '/';
					}
				} else {
					if ($cpt == $page) {
						$pagelist .= '<li class="pagination"><span class="active">' . ($page + 1) . '</span></li>';
					} else {
						$query['page'] = $cpt;
						$pagelist .= '<li class="pagination"><a class="reposition" href="' . dolBuildUrl($file, $query) . '">' . ($cpt + 1) . '</a></li>';
					}
				}
				$cpt++;
			} while ($cpt < $nbpages && $cpt <= ($page + $maxnbofpage));

			if (empty($pagenavastextinput)) {
				if ($cpt < $nbpages) {
					if ($cpt < $nbpages - 2) {
						$pagelist .= '<li class="pagination"><span class="inactive">...</span></li>';
					} elseif ($cpt == $nbpages - 2) {
						$query['page'] = ($nbpages - 2);
						$pagelist .= '<li class="pagination"><a class="reposition" href="' . dolBuildUrl($file, $query) . '">' . ($nbpages - 1) . '</a></li>';
					}
					$query['page'] = ($nbpages - 1);
					$pagelist .= '<li class="pagination"><a class="reposition" href="' . dolBuildUrl($file, $query) . '">' . $nbpages . '</a></li>';
				}
			} else {
				$query['page'] = ($nbpages - 1);
				$pagelist .= '<li class="pagination paginationlastpage"><a class="reposition" href="' . dolBuildUrl($file, $query) . '">' . $nbpages . '</a></li>';
			}
		} else {
			$pagelist .= '<li class="pagination"><span class="active">' . ($page + 1) . "</li>";
		}
	}

	if ($savlimit || $morehtmlright || $morehtmlrightbeforearrow) {
		// Show the combolist to select number of record per page and the navigation arrows.
		print_fleche_navigation($page, $file, $options, $nextpage, $pagelist, $morehtmlright, $savlimit, $totalnboflines, $selectlimitsuffix, $morehtmlrightbeforearrow, $hidenavigation); // output the div and ul for previous/last completed with page numbers into $pagelist
	}

	// js to autoselect page field on focus
	if ($pagenavastextinput) {
		print ajax_autoselect('.pageplusone');
	}

	print '</td>';
	print '</tr>';

	print "</table>\n";

	// Center
	if ($morehtmlcenter && !empty($conf->dol_optimize_smallscreen)) {
		print '<div class="nobordernopadding marginbottomonly center valignmiddle col-center centpercent">' . $morehtmlcenter . '</div>';
	}

	print "<!-- End title -->\n\n";
}

/**
 *	Function to show navigation arrows into lists
 *
 *	@param	int				$page				Number of page
 *	@param	string			$file				Page URL (in most cases provided with $_SERVER["PHP_SELF"])
 *	@param	string			$options         	Other url parameters to propagate ("" by default, may include sortfield and sortorder)
 *	@param	integer			$nextpage	    	Do we show a next page button
 *	@param	string			$betweenarrows		HTML content to show between arrows. MUST contains '<li> </li>' tags or '<li><span> </span></li>'.
 *  @param	string			$afterarrows		HTML content to show after arrows. Must NOT contains '<li> </li>' tags.
 *  @param  int             $limit              Max nb of record to show  (-1 = no combo with limit, 0 = no limit, > 0 = limit)
 *	@param	int		        $totalnboflines		Total number of records/lines for all pages (if known)
 *  @param  int|string      $selectlimitsuffix  A suffix for the limit ID, or -1 to hide the select of limit
 *  @param	string			$beforearrows		HTML content to show before arrows. Must NOT contains '<li> </li>' tags.
 *  @param  int        		$hidenavigation     Force to hide the switch mode view and the navigation tool (hide limit section, html in $betweenarrows and $afterarrows but not $beforearrows)
 *	@return	void
 */
function print_fleche_navigation($page, $file, $options = '', $nextpage = 0, $betweenarrows = '', $afterarrows = '', $limit = -1, $totalnboflines = 0, $selectlimitsuffix = '', $beforearrows = '', $hidenavigation = 0)
{
	global $conf, $langs;

	print '<div class="pagination"><ul>';
	if ($beforearrows) {
		print '<li class="paginationbeforearrows">';
		print $beforearrows;
		print '</li>';
	}

	if (empty($hidenavigation)) {
		if ((int) $limit > 0 && (empty($selectlimitsuffix) || !is_numeric($selectlimitsuffix))) {
			$pagesizechoices = '10:10,15:15,20:20,25:25,50:50,100:100,250:250,500:500,1000:1000';
			$pagesizechoices .= ',5000:5000';
			//$pagesizechoices .= ',10000:10000';				// Memory trouble on most browsers
			//$pagesizechoices .= ',20000:20000';				// Memory trouble on most browsers
			//$pagesizechoices .= ',0:'.$langs->trans("All");	// Not yet supported
			//$pagesizechoices .= ',2:2';
			if (getDolGlobalString('MAIN_PAGESIZE_CHOICES')) {
				$pagesizechoices = getDolGlobalString('MAIN_PAGESIZE_CHOICES');
			}

			if (getDolGlobalString('MAIN_USE_HTML5_LIMIT_SELECTOR')) {
				print '<li class="pagination">';
				print '<input onfocus="this.value=null;" onchange="this.blur();" class="flat selectlimit nopadding maxwidth75 right pageplusone" id="limit" name="limit" list="limitlist" title="' . dol_escape_htmltag($langs->trans("MaxNbOfRecordPerPage")) . '" value="' . $limit . '">';
				print '<datalist id="limitlist">';
			} else {
				print '<li class="paginationcombolimit valignmiddle">';
				print '<select id="limit' . (is_numeric($selectlimitsuffix) ? '' : $selectlimitsuffix) . '" name="'.(is_numeric($selectlimitsuffix) ? 'limit' : $selectlimitsuffix).'" class="flat selectlimit nopadding maxwidth75 center' . (is_numeric($selectlimitsuffix) ? '' : ' ' . $selectlimitsuffix) . '" title="' . dol_escape_htmltag($langs->trans("MaxNbOfRecordPerPage")) . '">';
			}
			$tmpchoice = explode(',', $pagesizechoices);
			$tmpkey = $limit . ':' . $limit;
			if (!in_array($tmpkey, $tmpchoice)) {
				$tmpchoice[$tmpkey] = $tmpkey;
			}
			$tmpkey = $conf->liste_limit . ':' . $conf->liste_limit;
			if (!in_array($tmpkey, $tmpchoice)) {
				$tmpchoice[$tmpkey] = $tmpkey;
			}
			asort($tmpchoice, SORT_NUMERIC);
			foreach ($tmpchoice as $val) {
				$selected = '';
				$tmp = explode(':', $val);
				$key = $tmp[0];
				$val = $tmp[1];
				if ($key != '' && $val != '') {
					if ((int) $key == (int) $limit) {
						$selected = ' selected="selected"';
					}
					print '<option name="' . $key . '"' . $selected . '>' . dol_escape_htmltag($val) . '</option>' . "\n";
				}
			}
			if (getDolGlobalString('MAIN_USE_HTML5_LIMIT_SELECTOR')) {
				print '</datalist>';
			} else {
				print '</select>';
				print ajax_combobox("limit" . (is_numeric($selectlimitsuffix) ? '' : $selectlimitsuffix), array(), 0, 0, 'resolve', '-1', 'limit');
				//print ajax_combobox("limit");
			}

			if ($conf->use_javascript_ajax) {
				print '<!-- JS CODE TO ENABLE select limit to launch submit of page -->
						<script>
						jQuery(document).ready(function () {
							jQuery(".selectlimit").change(function() {
								console.log("We change limit so we submit the form");
								$(this).parents(\'form:first\').submit();
							});
						});
						</script>
					';
			}
			print '</li>';
		}
		if ($page > 0) {
			print '<li class="pagination paginationpage paginationpageleft"><a class="paginationprevious reposition" href="' . $file . '?page=' . ($page - 1) . $options . '"><i class="fa fa-chevron-left" title="' . dol_escape_htmltag($langs->trans("Previous")) . '"></i></a></li>';
		}
		if ($betweenarrows) {
			print '<!--<div class="betweenarrows nowraponall inline-block">-->';
			print $betweenarrows;
			print '<!--</div>-->';
		}
		if ($nextpage > 0) {
			print '<li class="pagination paginationpage paginationpageright"><a class="paginationnext reposition" href="' . $file . '?page=' . ($page + 1) . $options . '"><i class="fa fa-chevron-right" title="' . dol_escape_htmltag($langs->trans("Next")) . '"></i></a></li>';
		}
		if ($afterarrows) {
			print '<li class="paginationafterarrows">';
			print $afterarrows;
			print '</li>';
		}
	}
	print '</ul></div>' . "\n";
}


/**
 * Style total amount of an object
 *
 * @param	string|float			$amount			Amount value to format
 * @return  string                      			String to show amount with style of total
 */
function showTotalAmount($amount)
{
	return '<span class="amount">'.$amount.'</span>';
}

/**
 * Output a dimension with best unit
 *
 * @param   float       $dimension      	Dimension
 * @param   int         $unit           	Unit scale of dimension (Example: 0=kg, -3=g, -6=mg, 98=ounce, 99=pound, ...)
 * @param   string      $type           	'weight', 'volume', ...
 * @param   Translate   $outputlangs    	Translate language object
 * @param   int<-1,max> $round          	-1 = non rounding, x = number of decimal
 * @param   string      $forceunitoutput    'no' or numeric (-3, -6, ...) compared to $unit (In most case, this value is value defined into $conf->global->MAIN_WEIGHT_DEFAULT_UNIT)
 * @param	int<0,1>	$use_short_label	1=Use short label ('g' instead of 'gram'). Short labels are not translated.
 * @return  string                      	String to show dimensions
 */
function showDimensionInBestUnit($dimension, $unit, $type, $outputlangs, $round = -1, $forceunitoutput = 'no', $use_short_label = 0)
{
	require_once DOL_DOCUMENT_ROOT . '/core/lib/product.lib.php';

	if (($forceunitoutput == 'no' && $dimension < 1 / 10000 && $unit < 90) || (is_numeric($forceunitoutput) && $forceunitoutput == -6)) {
		$dimension *= 1000000;
		$unit -= 6;
	} elseif (($forceunitoutput == 'no' && $dimension < 1 / 10 && $unit < 90) || (is_numeric($forceunitoutput) && $forceunitoutput == -3)) {
		$dimension *= 1000;
		$unit -= 3;
	} elseif (($forceunitoutput == 'no' && $dimension > 100000000 && $unit < 90) || (is_numeric($forceunitoutput) && $forceunitoutput == 6)) {
		$dimension /= 1000000;
		$unit += 6;
	} elseif (($forceunitoutput == 'no' && $dimension > 100000 && $unit < 90) || (is_numeric($forceunitoutput) && $forceunitoutput == 3)) {
		$dimension /= 1000;
		$unit += 3;
	}
	// Special case when we want output unit into pound or ounce
	/* TODO
	if ($unit < 90 && $type == 'weight' && is_numeric($forceunitoutput) && (($forceunitoutput == 98) || ($forceunitoutput == 99))
	{
		$dimension = // convert dimension from standard unit into ounce or pound
		$unit = $forceunitoutput;
	}
	if ($unit > 90 && $type == 'weight' && is_numeric($forceunitoutput) && $forceunitoutput < 90)
	{
		$dimension = // convert dimension from standard unit into ounce or pound
		$unit = $forceunitoutput;
	}*/

	$ret = price($dimension, 0, $outputlangs, 0, 0, $round);
	// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
	$ret .= ' ' . measuringUnitString(0, $type, $unit, $use_short_label, $outputlangs);

	return $ret;
}



/**
 *	Return yes or no in current language
 *
 *	@param	boolean|int<0, 1>|'yes'|'true'|'no'|'false'	$yesno	Value to test (true, 1, 'yes', 'true' or false, 0, 'no', 'false')
 *	@param	integer|string	$format						1=Yes/No, 0=yes/no, 2=Disabled/enabled checkbox, 3=Disabled/enabled checkbox + Yes/No, 4 or Text=Use picto
 *	@param	int				$color						0=texte only, 1=Text is formatted with a color font style ('ok' or 'error'), 2=Text is formatted with 'ok' color.
 *	@return	string										HTML string
 */
function yn($yesno, $format = 1, $color = 0)
{
	global $langs;

	$result = 'unknown';
	$classname = '';
	if ($yesno === true || (int) $yesno == 1 || (isset($yesno) && (strtolower($yesno) == 'yes' || strtolower($yesno) == 'true'))) { 	// To set to 'no' before the test because of the '== 0'
		$result = $langs->trans('yes');
		if ($format == 1 || $format == 3) {
			$result = $langs->trans("Yes");
		}
		if ($format == 2) {
			$result = '<input type="checkbox" value="1" checked disabled>';
		}
		if ($format == 3) {
			$result = '<input type="checkbox" value="1" checked disabled> ' . $result;
		}
		if ($format == 4 || !is_numeric($format)) {
			$result = img_picto(is_numeric($format) ? '' : $format, 'check');
		}

		$classname = 'ok';
	} else {
		$result = $langs->trans("no");
		if ($format == 1 || $format == 3) {
			$result = $langs->trans("No");
		}
		if ($format == 2) {
			$result = '<input type="checkbox" value="0" disabled>';
		}
		if ($format == 3) {
			$result = '<input type="checkbox" value="0" disabled> ' . $result;
		}
		if ($format == 4 || !is_numeric($format)) {
			$result = img_picto(is_numeric($format) ? '' : $format, 'uncheck');
		}

		if ($color == 2) {
			$classname = 'ok';
		} else {
			$classname = 'error';
		}
	}
	if ($color) {
		return '<span class="' . $classname . '">' . $result . '</span>';
	}
	return $result;
}


/**
 *	Set event message in dol_events session object. Will be output by calling dol_htmloutput_events.
 *  Note: Calling dol_htmloutput_events is done into pages by standard llxFooter() function.
 *  Note: Prefer to use setEventMessages instead.
 *
 *	@param	string|string[]	$mesgs			Message string or array
 *  @param  ''|'mesgs'|'warnings'|'errors'   $style		Which style to use ('mesgs' by default, 'warnings', 'errors')
 *  @param	int				$noduplicate	1 means we do not add the message if already present in session stack
 *  @param	int				$attop			Add the message in the top of the stack (at bottom by default)
 *  @return	void
 *  @see	dol_htmloutput_events()
 */
function setEventMessage($mesgs, $style = 'mesgs', $noduplicate = 0, $attop = 0)
{
	//dol_syslog(__FUNCTION__ . " is deprecated", LOG_WARNING);		This is not deprecated, it is used by setEventMessages function
	if (!is_array($mesgs)) {
		$mesgs = trim((string) $mesgs);
		// If mesgs is a not an empty string
		if ($mesgs) {
			if (!empty($noduplicate) && isset($_SESSION['dol_events'][$style]) && in_array($mesgs, $_SESSION['dol_events'][$style])) {
				return;
			}
			if ($attop) {
				array_unshift($_SESSION['dol_events'][$style], $mesgs);
			} else {
				$_SESSION['dol_events'][$style][] = $mesgs;
			}
		}
	} else {
		// If mesgs is an array
		foreach ($mesgs as $mesg) {
			$mesg = trim((string) $mesg);
			if ($mesg) {
				if (!empty($noduplicate) && isset($_SESSION['dol_events'][$style]) && in_array($mesg, $_SESSION['dol_events'][$style])) {
					return;
				}
				if ($attop) {
					array_unshift($_SESSION['dol_events'][$style], $mesgs);
				} else {
					$_SESSION['dol_events'][$style][] = $mesg;
				}
			}
		}
	}
}

/**
 *	Set event messages in dol_events session object. Will be output by calling dol_htmloutput_events.
 *  Note: Calling dol_htmloutput_events is done into pages by standard llxFooter() function.
 *
 *	@param	string|null		$mesg			Message string
 *	@param	string[]|null	$mesgs			Message array
 *  @param  ''|'mesgs'|'warnings'|'errors'   $style		Which style to use ('mesgs' by default, 'warnings', 'errors')',
 *  @param	string			$messagekey		A key to be used to allow the feature "Never show this message during this session again"
 *  @param	int				$noduplicate	1 means we do not add the message if already present in session stack
 *  @param	int				$attop			Add the message in the top of the stack (at bottom by default)
 *  @return	void
 *  @see	dol_htmloutput_events()
 */
function setEventMessages($mesg, $mesgs, $style = 'mesgs', $messagekey = '', $noduplicate = 0, $attop = 0)
{
	if (empty($mesg) && empty($mesgs)) {
		dol_syslog("Try to add a message in stack, but value to add is empty message" . getCallerInfoString(), LOG_WARNING);
	} else {
		if ($messagekey) {
			// Complete message with a js link to set a cookie "DOLHIDEMESSAGE".$messagekey;
			// TODO
			$mesg .= '';
		}
		if (empty($messagekey) || empty($_COOKIE["DOLUSER_HIDEMESSAGE" . $messagekey])) {
			if (!in_array((string) $style, array('mesgs', 'warnings', 'errors'))) {
				dol_print_error(null, 'Bad parameter style=' . $style . ' for setEventMessages');
			}
			if (empty($mesgs)) {
				setEventMessage((string) $mesg, $style, $noduplicate, $attop);
			} else {
				if (!empty($mesg) && !in_array($mesg, $mesgs)) {
					setEventMessage($mesg, $style, $noduplicate, $attop); // Add message string if not already into array
				}
				setEventMessage($mesgs, $style, $noduplicate, $attop);
			}
		}
	}
}

/**
 *	Print formatted messages to output (Used to show messages on html output).
 *  Note: Calling dol_htmloutput_events is done into pages by standard llxFooter() function, so there is
 *  no need to call it explicitly.
 *
 *  @param	int		$disabledoutputofmessages	Clear all messages stored into session without displaying them
 *  @return	void
 *  @see    									dol_htmloutput_mesg()
 */
function dol_htmloutput_events($disabledoutputofmessages = 0)
{
	// Show mesgs
	if (isset($_SESSION['dol_events']['mesgs'])) {
		if (empty($disabledoutputofmessages)) {
			dol_htmloutput_mesg('', $_SESSION['dol_events']['mesgs']);
		}
		unset($_SESSION['dol_events']['mesgs']);
	}
	// Show errors
	if (isset($_SESSION['dol_events']['errors'])) {
		if (empty($disabledoutputofmessages)) {
			dol_htmloutput_mesg('', $_SESSION['dol_events']['errors'], 'error');
		}
		unset($_SESSION['dol_events']['errors']);
	}

	// Show warnings
	if (isset($_SESSION['dol_events']['warnings'])) {
		if (empty($disabledoutputofmessages)) {
			dol_htmloutput_mesg('', $_SESSION['dol_events']['warnings'], 'warning');
		}
		unset($_SESSION['dol_events']['warnings']);
	}
}

/**
 *	Get formatted messages to output (Used to show messages on html output).
 *  This include also the translation of the message key.
 *
 *	@param	string		$mesgstring		Message string or message key
 *	@param	string[]	$mesgarray      Array of message strings or message keys
 *  @param  string		$style          Style of message output ('ok', 'warning' or 'error')
 *  @param  int			$keepembedded   Set to 1 in error message must be kept embedded into its html place (this disable jnotify)
 *	@return	string						Return html output
 *
 *  @see    dol_print_error()
 *  @see    dol_htmloutput_errors()
 *  @see    setEventMessages()
 */
function get_htmloutput_mesg($mesgstring = '', $mesgarray = [], $style = 'ok', $keepembedded = 0)
{
	global $conf, $langs;

	$ret = 0;
	$return = '';
	$out = '';
	$divstart = $divend = '';

	// If inline message with no format, we add it.
	if ((empty($conf->use_javascript_ajax) || getDolGlobalString('MAIN_DISABLE_JQUERY_JNOTIFY') || $keepembedded) && !preg_match('/<div class=".*">/i', $out)) {
		$divstart = '<div class="' . $style . ' clearboth">';
		$divend = '</div>';
	}

	if ((is_array($mesgarray) && count($mesgarray)) || $mesgstring) {
		$langs->load("errors");
		$out .= $divstart;
		if (is_array($mesgarray) && count($mesgarray)) {
			foreach ($mesgarray as $message) {
				$ret++;
				$out .= $langs->trans($message);
				if ($ret < count($mesgarray)) {
					$out .= "<br>\n";
				}
			}
		}
		if ($mesgstring) {
			$ret++;
			$out .= $langs->trans($mesgstring);
		}
		$out .= $divend;
	}

	if ($out) {
		if (!empty($conf->use_javascript_ajax) && !getDolGlobalString('MAIN_DISABLE_JQUERY_JNOTIFY') && empty($keepembedded)) {
			if ($style == "ok") {
				// For success messages (green), allow manual click to close immediately without fade
				$return = '<script nonce="' . getNonce() . '">
					/* jnotify(message, params) */
					$(document).ready(function() {
						$.jnotify(\'' . dol_escape_js($out) . '\', {
							delay: 3000,
							type: \'' . dol_escape_js($style) . '\',
							sticky: false,
							create: function($note) {
								$note.css("cursor", "pointer").click(function(e) {
									e.stopPropagation();
									$note.remove();
								});
							}
						});
					});
				</script>';
			} else {
				// For error and warning messages, close immediately on click without fade
				$return = '<script nonce="' . getNonce() . '">
					$(document).ready(function() {
						$.jnotify(\'' . dol_escape_js($out) . '\', {
							delay: 3000,
							type: \'' . dol_escape_js($style) . '\',
							sticky: true,
							create: function($note) {
								$note.find("a.jnotify-close").click(function(e) {
									e.stopPropagation();
									$note.remove();
								});
							}
						});
					});
				</script>';
			}
		} else {
			$return = $out;
		}
	}

	return $return;
}

/**
 *  Get formatted error messages to output (Used to show messages on html output).
 *
 *  @param	string		$mesgstring		Error message
 *  @param	string[]	$mesgarray		Error messages array
 *  @param	int			$keepembedded	Set to 1 in error message must be kept embedded into its html place (this disable jnotify)
 *  @return	string                		Return html output
 *
 *  @see    dol_print_error()
 *  @see    dol_htmloutput_mesg()
 */
function get_htmloutput_errors($mesgstring = '', $mesgarray = array(), $keepembedded = 0)
{
	return get_htmloutput_mesg($mesgstring, $mesgarray, 'error', $keepembedded);
}

/**
 *	Print formatted messages to output (Used to show messages on html output).
 *
 *	@param	string		$mesgstring		Message string or message key
 *	@param	string[]	$mesgarray      Array of message strings or message keys
 *	@param  string      $style          Which style to use ('ok', 'warning', 'error')
 *	@param  int         $keepembedded   Set to 1 if message must be kept embedded into its html place (this disable jnotify)
 *	@return	void
 *
 *	@see    dol_print_error()
 *	@see    dol_htmloutput_errors()
 *	@see    setEventMessages()
 */
function dol_htmloutput_mesg($mesgstring = '', $mesgarray = array(), $style = 'ok', $keepembedded = 0)
{
	if (empty($mesgstring) && (!is_array($mesgarray) || count($mesgarray) == 0)) {
		return;
	}

	$iserror = 0;
	$iswarning = 0;
	if (is_array($mesgarray)) {
		foreach ($mesgarray as $val) {
			if ($val && preg_match('/class="error"/i', $val)) {
				$iserror++;
				break;
			}
			if ($val && preg_match('/class="warning"/i', $val)) {
				$iswarning++;
				break;
			}
		}
	} elseif ($mesgstring && preg_match('/class="error"/i', $mesgstring)) {
		$iserror++;
	} elseif ($mesgstring && preg_match('/class="warning"/i', $mesgstring)) {
		$iswarning++;
	}
	if ($style == 'error' || $style == 'errors') {
		$iserror++;
	}
	if ($style == 'warning' || $style == 'warnings') {
		$iswarning++;
	}

	if ($iserror || $iswarning) {
		// Remove div from texts
		$mesgstring = preg_replace('/<\/div><div class="(error|warning)">/', '<br>', $mesgstring);
		$mesgstring = preg_replace('/<div class="(error|warning)">/', '', $mesgstring);
		$mesgstring = preg_replace('/<\/div>/', '', $mesgstring);
		// Remove div from texts array
		if (is_array($mesgarray)) {
			$newmesgarray = array();
			foreach ($mesgarray as $val) {
				if (is_string($val)) {
					$tmpmesgstring = preg_replace('/<\/div><div class="(error|warning)">/', '<br>', $val);
					$tmpmesgstring = preg_replace('/<div class="(error|warning)">/', '', $tmpmesgstring);
					$tmpmesgstring = preg_replace('/<\/div>/', '', $tmpmesgstring);
					$newmesgarray[] = $tmpmesgstring;
				} else {
					dol_syslog("Error call of dol_htmloutput_mesg with an array with a value that is not a string", LOG_WARNING);
				}
			}
			$mesgarray = $newmesgarray;
		}
		print get_htmloutput_mesg($mesgstring, $mesgarray, ($iserror ? 'error' : 'warning'), $keepembedded);
	} else {
		print get_htmloutput_mesg($mesgstring, $mesgarray, 'ok', $keepembedded);
	}
}

/**
 *  Print formatted error messages to output (Used to show messages on html output).
 *
 *  @param	string		$mesgstring		Error message
 *  @param  string[]	$mesgarray		Error messages array
 *  @param  int<0,1>	$keepembedded	Set to 1 in error message must be kept embedded into its html place (this disable jnotify)
 *  @return	void
 *
 *  @see    dol_print_error()
 *  @see    dol_htmloutput_mesg()
 */
function dol_htmloutput_errors($mesgstring = '', $mesgarray = array(), $keepembedded = 0)
{
	dol_htmloutput_mesg($mesgstring, $mesgarray, 'error', $keepembedded);
}



/**
 * 	Return img flag of country for a language code or country code.
 *
 * 	@param	string		$codelang	Language code ('en_IN', 'fr_CA', ...) or ISO Country code on 2 characters in uppercase ('IN', 'FR')
 *  @param	string		$moreatt	Add more attribute on img tag (For example 'style="float: right"' or 'class="saturatemedium"')
 *  @param	int<0,1>	$notitlealt	No title alt
 * 	@return	string				HTML img string with flag.
 */
function picto_from_langcode($codelang, $moreatt = '', $notitlealt = 0)
{
	if (empty($codelang)) {
		return '';
	}

	if ($codelang == 'auto') {
		return '<span class="fa fa-language"></span>';
	}

	$langtocountryflag = array(
		'ar_AR' => '',
		'ca_ES' => 'catalonia',
		'da_DA' => 'dk',
		'fr_CA' => 'mq',
		'sv_SV' => 'se',
		'sw_SW' => 'unknown',
		'AQ' => 'unknown',
		'CW' => 'unknown',
		'IM' => 'unknown',
		'JE' => 'unknown',
		'MF' => 'unknown',
		'BL' => 'unknown',
		'SX' => 'unknown'
	);

	if (isset($langtocountryflag[$codelang])) {
		$flagImage = $langtocountryflag[$codelang];
	} else {
		$tmparray = explode('_', $codelang);
		$flagImage = empty($tmparray[1]) ? $tmparray[0] : $tmparray[1];
	}

	$morecss = '';
	$reg = array();
	if (preg_match('/class="([^"]+)"/', $moreatt, $reg)) {
		$morecss = $reg[1];
		$moreatt = "";
	}

	// return img_picto_common($codelang, 'flags/'.strtolower($flagImage).'.png', $moreatt, 0, $notitlealt);
	return '<span class="flag-sprite ' . strtolower($flagImage) . ($morecss ? ' ' . $morecss : '') . '"' . ($moreatt ? ' ' . $moreatt : '') . (!$notitlealt ? ' title="' . $codelang . '"' : '') . '></span>';
}


/**
 * Print common footer :
 * 		conf->global->MAIN_HTML_FOOTER
 *      js for switch of menu hider
 * 		js for conf->global->MAIN_GOOGLE_AN_ID
 * 		js for conf->global->MAIN_SHOW_TUNING_INFO or $_SERVER["MAIN_SHOW_TUNING_INFO"]
 * 		js for conf->logbuffer
 *
 * @param	string	$zone	'private' (for private pages) or 'public' (for public pages)
 * @return	void
 */
function printCommonFooter($zone = 'private')
{
	global $conf, $hookmanager, $user, $langs;
	global $action;
	global $micro_start_time;

	if ($zone == 'private') {
		print "\n" . '<!-- Common footer for private page -->' . "\n";
	} else {
		print "\n" . '<!-- Common footer for public page -->' . "\n";
	}

	// A div to store page_y POST parameter so we can read it using javascript
	print "\n<!-- A div to store page_y POST parameter -->\n";
	print '<div id="page_y" style="display: none;">' . (GETPOST('page_y') ? GETPOST('page_y') : '') . '</div>' . "\n";

	$parameters = array('zone' => $zone);
	$tmpobject = null;
	// @phan-suppress-next-line PhanPluginConstantVariableNull
	$reshook = $hookmanager->executeHooks('printCommonFooter', $parameters, $tmpobject, $action); // Note that $action and $object may have been modified by some hooks
	if (empty($reshook)) {
		if (getDolGlobalString('MAIN_HTML_FOOTER')) {
			print getDolGlobalString('MAIN_HTML_FOOTER') . "\n";
		}

		print "\n";
		if (!empty($conf->use_javascript_ajax)) {
			print "\n<!-- A script section to add menuhider handler on backoffice, manage focus and mandatory fields, tuning info, ... -->\n";
			print '<script>' . "\n";
			print 'jQuery(document).ready(function() {' . "\n";

			if ($zone == 'private' && empty($conf->dol_use_jmobile)) {
				print "\n";
				print '/* JS CODE TO ENABLE to manage handler to switch left menu page (menuhider) */' . "\n";
				print 'jQuery("li.menuhider").click(function(event) {';
				print '  if (!$( "body" ).hasClass( "sidebar-collapse" )){ event.preventDefault(); }' . "\n";
				print '  console.log("We click on .menuhider");' . "\n";
				print '  $("body").toggleClass("sidebar-collapse")' . "\n";
				print '});' . "\n";
			}

			// Management of focus and mandatory for fields
			if ($action == 'create' || $action == 'add'  || $action == 'edit' || (empty($action) && (preg_match('/new\.php/', $_SERVER["PHP_SELF"]))) || ((empty($action) || $action == 'addline') && (preg_match('/card\.php/', $_SERVER["PHP_SELF"])))) {
				print '/* JS CODE TO ENABLE to manage focus and mandatory form fields */' . "\n";
				$relativepathstring = $_SERVER["PHP_SELF"];
				// Clean $relativepathstring
				if (constant('DOL_URL_ROOT')) {
					$relativepathstring = preg_replace('/^' . preg_quote(constant('DOL_URL_ROOT'), '/') . '/', '', $relativepathstring);
				}
				$relativepathstring = preg_replace('/^\//', '', $relativepathstring);
				$relativepathstring = preg_replace('/^custom\//', '', $relativepathstring);
				//$tmpqueryarraywehave = explode('&', dol_string_nohtmltag($_SERVER['QUERY_STRING']));

				if (!empty($user->default_values[$relativepathstring]['focus'])) {
					foreach ($user->default_values[$relativepathstring]['focus'] as $defkey => $defval) {
						$qualified = 0;
						if ($defkey != '_noquery_') {
							$tmpqueryarraytohave = explode('&', $defkey);
							$foundintru = 0;
							foreach ($tmpqueryarraytohave as $tmpquerytohave) {
								$tmpquerytohaveparam = explode('=', $tmpquerytohave);
								//print "console.log('".$tmpquerytohaveparam[0]." ".$tmpquerytohaveparam[1]." ".GETPOST($tmpquerytohaveparam[0])."');";
								if (!GETPOSTISSET($tmpquerytohaveparam[0]) || ($tmpquerytohaveparam[1] != GETPOST($tmpquerytohaveparam[0]))) {
									$foundintru = 1;
								}
							}
							if (!$foundintru) {
								$qualified = 1;
							}
							//var_dump($defkey.'-'.$qualified);
						} else {
							$qualified = 1;
						}

						if ($qualified) {
							print 'console.log("set the focus by executing jQuery(...).focus();")' . "\n";
							foreach ($defval as $paramkey => $paramval) {
								// Set focus on field
								print 'jQuery("input[name=\'' . $paramkey . '\']").focus();' . "\n";
								print 'jQuery("textarea[name=\'' . $paramkey . '\']").focus();' . "\n";	// TODO KO with ckeditor
								print 'jQuery("select[name=\'' . $paramkey . '\']").focus();' . "\n"; // Not really useful, but we keep it in case of.
							}
						}
					}
				}
				if (!empty($user->default_values[$relativepathstring]['mandatory'])) {
					foreach ($user->default_values[$relativepathstring]['mandatory'] as $defkey => $defval) {
						$qualified = 0;
						if ($defkey != '_noquery_') {
							$tmpqueryarraytohave = explode('&', $defkey);
							$foundintru = 0;
							foreach ($tmpqueryarraytohave as $tmpquerytohave) {
								$tmpquerytohaveparam = explode('=', $tmpquerytohave);
								//print "console.log('".$tmpquerytohaveparam[0]." ".$tmpquerytohaveparam[1]." ".GETPOST($tmpquerytohaveparam[0])."');";
								if (!GETPOSTISSET($tmpquerytohaveparam[0]) || ($tmpquerytohaveparam[1] != GETPOST($tmpquerytohaveparam[0]))) {
									$foundintru = 1;
								}
							}
							if (!$foundintru) {
								$qualified = 1;
							}
							//var_dump($defkey.'-'.$qualified);
						} else {
							$qualified = 1;
						}

						if ($qualified) {
							print 'console.log("set the js code to manage fields that are set as mandatory");' . "\n";

							foreach ($defval as $paramkey => $paramval) {
								// Solution 1: Add handler on submit to check if mandatory fields are empty
								print 'var form = $(\'[name="'.dol_escape_js($paramkey).'"]\').closest("form");'."\n";
								print "form.on('submit', function(event) {
										var submitter = \$(this).find(':submit:focus').get(0);
										var buttonName = submitter ? \$(submitter).attr('name') : 'save';

										if (buttonName == 'cancel') {
											console.log('We click on cancel button so we accept submit with no need to check mandatory fields');
											return true;
										}

										console.log('We did not click on cancel button but on something else, we check that field [name=".dol_escape_js($paramkey)."] is not empty');

										var tmpvalue = jQuery('[name=\"".dol_escape_js($paramkey)."\"]').val();
										let tmptypefield = jQuery('[name=\"".dol_escape_js($paramkey)."\"]').prop('nodeName').toLowerCase(); // Get the tag name (div, section, footer...)

										if (tmptypefield == 'textarea') {
											// We must instead check the content of ckeditor
											var tmpeditor = (typeof CKEDITOR !== 'undefined') ? CKEDITOR.instances['".dol_escape_js($paramkey)."'] : null;
											if (tmpeditor) {
												tmpvalue = tmpeditor.getData();
												console.log('For textarea tmpvalue is '+tmpvalue);
											}
										}

										let tmpvalueisempty = false;
										if (tmpvalue === null || tmpvalue === undefined || tmpvalue === '' || tmpvalue === -1 || tmpvalue === '-1') {
											tmpvalueisempty = true;
										}
										if (tmpvalue === '0' && (tmptypefield == 'select' || tmptypefield == 'input')) {
											tmpvalueisempty = true;
										}
										if (tmpvalueisempty && buttonName !== 'cancel') {
											console.log('field has type '+tmptypefield+' and is empty, we cancel the submit');
											event.preventDefault(); // Stop submission of form to allow custom code to decide.
											event.stopPropagation(); // Stop other handlers.

											alert('".dol_escape_js($langs->transnoentitiesnoconv("ErrorFieldRequired", $paramkey).' ('.$langs->transnoentitiesnoconv("CustomMandatoryFieldRule").')')."');

											return false;
										}
										console.log('field has type '+tmptypefield+' and is defined to '+tmpvalue);
										return true;
									});
								\n";

								// Solution 2: Add property 'required' on input
								// so browser will check value and try to focus on it when submitting the form.
								//print 'setTimeout(function() {';	// If we want to wait that ckeditor beuatifier has finished its job.
								//print 'jQuery("input[name=\''.$paramkey.'\']").prop(\'required\',true);'."\n";
								//print 'jQuery("textarea[id=\''.$paramkey.'\']").prop(\'required\',true);'."\n";
								//print 'jQuery("select[name=\''.$paramkey.'\']").prop(\'required\',true);'."\n";*/
								//print '// required on a select works only if key is "", so we add the required attributes but also we reset the key -1 or 0 to an empty string'."\n";
								//print 'jQuery("select[name=\''.$paramkey.'\'] option[value=\'-1\']").prop(\'value\', \'\');'."\n";
								//print 'jQuery("select[name=\''.$paramkey.'\'] option[value=\'0\']").prop(\'value\', \'\');'."\n";
								// Add 'field required' class on closest td for all input elements : input, textarea and select
								//print '}, 500);'; // 500 milliseconds delay

								// Now set the class "fieldrequired"
								print 'jQuery(\':input[name="' . dol_escape_js($paramkey) . '"]\').closest("tr").find("td:first").addClass("fieldrequired");' . "\n";
							}

							// If we submit using the cancel button, we remove the required attributes
							print 'jQuery("input[name=\'cancel\']").click(function() {
								console.log("We click on cancel button so removed all required attribute");
								jQuery("input, textarea, select").each(function(){this.removeAttribute(\'required\');});
								});' . "\n";
						}
					}
				}
			}

			print '});' . "\n";

			// End of tuning
			if (!empty($_SERVER['MAIN_SHOW_TUNING_INFO']) || getDolGlobalString('MAIN_SHOW_TUNING_INFO')) {
				print "\n";
				print "/* JS CODE TO ENABLE to add memory info */\n";
				print 'window.console && console.log("';
				if (getDolGlobalString('MEMCACHED_SERVER')) {
					print 'MEMCACHED_SERVER=' . getDolGlobalString('MEMCACHED_SERVER') . ' - ';
				}
				print 'MAIN_OPTIMIZE_SPEED=' . getDolGlobalString('MAIN_OPTIMIZE_SPEED', 'off');
				if (!empty($micro_start_time)) {   // Works only if MAIN_SHOW_TUNING_INFO is defined at $_SERVER level. Not in global variable.
					$micro_end_time = microtime(true);
					print ' - Build time: ' . ceil(1000 * ($micro_end_time - $micro_start_time)) . ' ms';
				}

				if (function_exists("memory_get_usage")) {
					print ' - Mem: ' . memory_get_usage(); // Do not use true here, it seems it takes the peak amount
				}
				if (function_exists("memory_get_peak_usage")) {
					print ' - Real mem peak: ' . memory_get_peak_usage(true);
				}
				if (function_exists("zend_loader_file_encoded")) {
					print ' - Zend encoded file: ' . (zend_loader_file_encoded() ? 'yes' : 'no');
				}
				print '");' . "\n";
			}

			print "\n" . '</script>' . "\n";

			// Google Analytics
			// TODO Remove this, can be replaced with the hook printCommonFooter
			if (isModEnabled('google') && getDolGlobalString('MAIN_GOOGLE_AN_ID')) {
				$tmptagarray = explode(',', getDolGlobalString('MAIN_GOOGLE_AN_ID'));
				foreach ($tmptagarray as $tmptag) {
					print "\n";
					print "<!-- JS CODE TO ENABLE for google analtics tag -->\n";
					print '
					<!-- Global site tag (gtag.js) - Google Analytics -->
					<script nonce="' . getNonce() . '" async src="https://www.googletagmanager.com/gtag/js?id=' . trim($tmptag) . '"></script>
					<script>
					window.dataLayer = window.dataLayer || [];
					function gtag(){dataLayer.push(arguments);}
					gtag(\'js\', new Date());

					gtag(\'config\', \'' . trim($tmptag) . '\');
					</script>';
					print "\n";
				}
			}
		}

		// Add Xdebug coverage of code
		if (defined('XDEBUGCOVERAGE')) {
			print_r(xdebug_get_code_coverage());
		}

		// Output string from hooks
		if (!empty($hookmanager->resPrint)) {
			print $hookmanager->resPrint;
		}

		// Add DebugBar data
		if ($user->hasRight('debugbar', 'read')) {
			global $debugbar;
			if ($debugbar instanceof DebugBar\DebugBar) {
				if (isset($debugbar['time'])) {
					// @phan-suppress-next-line PhanPluginUnknownObjectMethodCall
					$debugbar['time']->stopMeasure('pageaftermaster');
				}
				print '<!-- Output debugbar data -->' . "\n";
				$renderer = $debugbar->getJavascriptRenderer();
				print $renderer->render();
			}
		} elseif (count($conf->logbuffer)) {    // If there is some logs in buffer to show
			print "\n";
			print "<!-- Start of log output\n";
			//print '<div class="hidden">'."\n";
			foreach ($conf->logbuffer as $logline) {
				print $logline . "<br>\n";
			}
			//print '</div>'."\n";
			print "End of log output -->\n";
		}
	}
}


/**
 * Set focus onto field with selector (similar behaviour of 'autofocus' HTML5 tag)
 *
 * @param 	string	$selector	Selector ('#id' or 'input[name="ref"]') to use to find the HTML input field that must get the autofocus. You must use a CSS selector, so unique id preceding with the '#' char.
 * @return	void
 */
function dol_set_focus($selector)
{
	print "\n" . '<!-- Set focus onto a specific field -->' . "\n";
	print '<script nonce="' . getNonce() . '">jQuery(document).ready(function() { console.log("Force focus by dol_set_focus"); jQuery("' . dol_escape_js($selector) . '").focus(); });</script>' . "\n";
}



/**
 * Returns simple order table template as string
 *
 * @param	Translate	$outputlangs	Output language
 * @param   Object		$object			Object
 * @return	string						template
 */
function showSimpleHTMLTable($outputlangs, $object)
{
	global $conf;

	$discountIsAvailable = false;
	$orderPositionHasNoPrice = false;

	if (!property_exists($object->lines[0], "remise_percent") ||
		!property_exists($object->lines[0], "fk_unit") ||
		!property_exists($object->lines[0], "multicurrency_total_ttc") ||
		!property_exists($object->lines[0], "description") ||
		!property_exists($object->lines[0], "qty")) {
		return"";
	}

	foreach ($object->lines as $order_position) {
		if (!property_exists($order_position, "price")) {
			$orderPositionHasNoPrice = true;
			break;
		}

		if (!empty($order_position->remise_percent)) {
			$discountIsAvailable = true;
			break;
		}
	};

	if ($orderPositionHasNoPrice) {
		return "";
	}

	$discountHeader = $discountIsAvailable ? '<th style="width:120px">'.$outputlangs->trans("Discount").'</th>' : '';

	$table = '<table border="0" cellpadding="1" cellspacing="1">';
	$table .= '
	<thead>
		<tr>
			<th style="width:50px; text-align:left">#</th>
			<th style="text-align:left">'.$outputlangs->trans("Description").'</th>
			<th style="width:120px; text-align:right;">'.$outputlangs->trans("Price").'</th>
			<th style="width:100px; text-align:right;">'.$outputlangs->trans("Quantity").'</th>
			<th style="width:120px; text-align:right;">'.$outputlangs->trans("Unit").'</th>'.
			$discountHeader.'
			<th style="width:120px; text-align:right;">'.$outputlangs->trans("Sum").'</th>
		</tr>
	</thead>
	<tbody>';

	foreach ($object->lines as $index => $order_position) {
		$position = $index + 1;
		$price = price($order_position->price, 0, $outputlangs, 0, -1, -1, $conf->currency);
		$unit = measuringUnitString($order_position->fk_unit, '', null, 1);
		$total = price($order_position->multicurrency_total_ttc, 0, $outputlangs, 0, -1, -1, $conf->currency);
		$discount = $discountIsAvailable ? '<td style="text-align:center">'.$order_position->remise_percent.'%</td>' : "";

		$table .= '
			<tr>
				<td>'.$position.'</td>
				<td>'.$order_position->description.'</td>
				<td style="text-align:right">'.$price.'</td>
				<td style="text-align:right">'.$order_position->qty.'</td>
				<td style="text-align:right">'.$unit.'</td>'.
				$discount.'
				<td style="text-align:right">'.$total.'</td>
			</tr>';
	}
	$table .= '</tbody></table>';

	return $table;
}

/**
 * Return string with full Url. The file qualified is the one defined by relative path in $object->last_main_doc
 *
 * @param   CommonObject	$object		Object
 * @return	string						Url string
 */
function showDirectDownloadLink($object)
{
	global $langs;

	$out = '';
	$url = $object->getLastMainDocLink($object->element);

	$out .= img_picto($langs->trans("PublicDownloadLinkDesc"), 'globe') . ' <span class="opacitymedium">' . $langs->trans("DirectDownloadLink") . '</span><br>';
	if ($url) {
		$out .= '<div class="urllink"><input type="text" id="directdownloadlink" class="quatrevingtpercent" value="' . $url . '"></div>';
		$out .= ajax_autoselect("directdownloadlink", '');
	} else {
		$out .= '<div class="urllink">' . $langs->trans("FileNotShared") . '</div>';
	}

	return $out;
}



/**
 * Return URL we can use for advanced preview links
 *
 * @param   string    $modulepart     propal, facture, facture_fourn, ...
 * @param   string    $relativepath   Relative path of docs.
 * @param	int<0,1>	  $alldata		  Return array with all components (1 is recommended, then use a simple a href link with the class, target and mime attribute added. 'documentpreview' css class is handled by jquery code into main.inc.php)
 * @param	string	  $param		  More param on http links
 * @return  string|array{}|array{target:string,css:string,url:string,mime:string}	Output string with href link or array with all components of link
 */
function getAdvancedPreviewUrl($modulepart, $relativepath, $alldata = 0, $param = '')
{
	global $conf, $langs;

	if (empty($conf->use_javascript_ajax)) {
		return '';
	}

	$isAllowedForPreview = dolIsAllowedForPreview($relativepath);

	if ($alldata == 1) {
		if ($isAllowedForPreview) {
			return array('target' => '_blank', 'css' => 'documentpreview', 'url' => DOL_URL_ROOT . '/document.php?modulepart=' . urlencode($modulepart) . '&attachment=0&file=' . urlencode($relativepath) . ($param ? '&' . $param : ''), 'mime' => dol_mimetype($relativepath));
		} else {
			return array();
		}
	}

	// old behavior, return a string
	if ($isAllowedForPreview) {
		$tmpurl = DOL_URL_ROOT . '/document.php?modulepart=' . urlencode($modulepart) . '&attachment=0&file=' . urlencode($relativepath) . ($param ? '&' . $param : '');
		$title = $langs->transnoentities("Preview");
		//$title = '%27-alert(document.domain)-%27';							// An example of js injection into a corrupted title string, that should be blocked by the dol_escape_uri().
		//$tmpurl = 'file='.urlencode("'-alert(document.domain)-'_small.jpg");	// An example of tmpurl that should be blocked by the dol_escape_uri()

		// We need to do a dol_escape_uri() on the full string after the javascript: because such parts are the URI and when we click on such links, a RFC3986 decode is done,
		// by the browser, converting the %27 (like when having param file=abc%27def), or when having a corrupted title), into a ', BEFORE interpreting the content that can be a js code.
		// Using the dol_escape_uri guarantee that we encode for URI so decode retrieve original expected value.
		return 'javascript:' . dol_escape_uri('document_preview(\'' . dol_escape_js($tmpurl) . '\', \'' . dol_escape_js(dol_mimetype($relativepath)) . '\', \'' . dol_escape_js($title) . '\')');
	} else {
		return '';
	}
}


/**
 * Make content of an input box selected when we click into input field.
 *
 * @param string	$htmlname		Id of html object ('#idvalue' or '.classvalue')
 * @param string	$addlink		Add a 'link to' after
 * @param string	$textonlink		Text to show on link or 'image'
 * @return string
 * @see showValueWithClipboardCPButton()
 */
function ajax_autoselect($htmlname, $addlink = '', $textonlink = 'Link')
{
	global $langs;
	$out = '<script nonce="' . getNonce() . '">
			   jQuery(document).ready(function () {
					jQuery("' . ((strpos($htmlname, '.') === 0 ? '' : '#') . $htmlname) . '").click(function() { jQuery(this).select(); } );
				});
			</script>';
	if ($addlink) {
		if ($textonlink === 'image') {
			$out .= ' <a href="' . $addlink . '" target="_blank" rel="noopener noreferrer">' . img_picto('', 'globe') . '</a>';
		} else {
			$out .= ' <a href="' . $addlink . '" target="_blank" rel="noopener noreferrer">' . $langs->trans("Link") . '</a>';
		}
	}
	return $out;
}



/**
 * Function dolGetBadge
 *
 * @param   string  			$label      label of badge no html : use in alt attribute for accessibility
 * @param   string  			$html       optional : label of badge with html
 * @param   string  			$type       type of badge : Primary Secondary Success Danger Warning Info Light Dark status0 status1 status2 status3 status4 status5 status6 status7 status8 status9
 * @param   ''|'pill'|'dot'		$mode		Default '' , 'pill', 'dot'
 * @param   string  			$url        the url for link
 * @param   array{attr?:array{class:string,title:string},css?:string}	$params		Various params for future : recommended rather than adding more function arguments. array('attr'=>array('title'=>'abc'))
 * @return  string              			Html badge
 */
function dolGetBadge($label, $html = '', $type = 'primary', $mode = '', $url = '', $params = array())
{
	$csstouse = 'badge';
	$csstouse .= (!empty($mode) ? ' badge-' . $mode : '');
	$csstouse .= (!empty($type) ? ' badge-' . $type : '');
	$csstouse .= (empty($params['css']) ? '' : ' ' . $params['css']);

	$attr = array(
		'class' => $csstouse
	);

	if (empty($html)) {
		$html = $label;
	}

	if (!empty($url)) {
		$attr['href'] = $url;
	}

	if ($mode === 'dot') {
		$attr['class'] .= ' classfortooltip';
		$attr['title'] = $html;
		$attr['aria-label'] = $label;
		$html = '';
	}

	// Override attr
	if (!empty($params['attr']) && is_array($params['attr'])) {
		foreach ($params['attr'] as $key => $value) {
			if ($key == 'class') {
				$attr['class'] .= ' ' . $value;
			} elseif ($key == 'classOverride') {
				$attr['class'] = $value;
			} else {
				$attr[$key] = $value;
			}
		}
	}

	// TODO: add hook

	// escape all attribute
	$attr = array_map('dolPrintHTMLForAttribute', $attr);

	$TCompiledAttr = array();
	foreach ($attr as $key => $value) {
		$TCompiledAttr[] = $key . '="' . $value . '"';
	}

	$compiledAttributes = !empty($TCompiledAttr) ? implode(' ', $TCompiledAttr) : '';

	$tag = !empty($url) ? 'a' : 'span';

	return '<' . $tag . ' ' . $compiledAttributes . '>' . $html . '</' . $tag . '>';
}


/**
 * Output the badge of a status.
 *
 * @param   string  			$statusLabel		Label of badge no html : use in alt attribute for accessibility
 * @param   string  			$statusLabelShort	Short label of badge no html
 * @param   string  			$html				Optional : label of badge with html
 * @param   string  			$statusType			status0 status1 status2 status3 status4 status5 status6 status7 status8 status9 : image name or badge name
 * @param   int<0,6>			$displayMode		0=Long label, 1=Short label, 2=Picto + Short label, 3=Picto, 4=Picto + Long label, 5=Short label + Picto, 6=Long label + Picto
 * @param   string  			$url				The url for link
 * @param   array<string,mixed>	$params				Various params. Example: array('tooltip'=>'no|...', 'badgeParams'=>...)
 * @return  string									Html status string
 */
function dolGetStatus($statusLabel = '', $statusLabelShort = '', $html = '', $statusType = 'status0', $displayMode = 0, $url = '', $params = array())
{
	global $conf;

	$return = '';
	$dolGetBadgeParams = array();

	if (!empty($params['badgeParams'])) {
		$dolGetBadgeParams = $params['badgeParams'];
	}

	// TODO : add a hook
	if ($displayMode == 0) {
		$return = !empty($html) ? $html : (empty($conf->dol_optimize_smallscreen) ? $statusLabel : (empty($statusLabelShort) ? $statusLabel : $statusLabelShort));
	} elseif ($displayMode == 1) {
		$return = !empty($html) ? $html : (empty($statusLabelShort) ? $statusLabel : $statusLabelShort);
	} elseif (getDolGlobalString('MAIN_STATUS_USES_IMAGES')) {
		// Use status with images (for backward compatibility)
		$return = '';
		$htmlLabel      = (in_array($displayMode, array(1, 2, 5)) ? '<span class="hideonsmartphone">' : '') . (!empty($html) ? $html : $statusLabel) . (in_array($displayMode, array(1, 2, 5)) ? '</span>' : '');
		$htmlLabelShort = (in_array($displayMode, array(1, 2, 5)) ? '<span class="hideonsmartphone">' : '') . (!empty($html) ? $html : (!empty($statusLabelShort) ? $statusLabelShort : $statusLabel)) . (in_array($displayMode, array(1, 2, 5)) ? '</span>' : '');

		// For small screen, we always use the short label instead of long label.
		if (!empty($conf->dol_optimize_smallscreen)) {
			if ($displayMode == 0) {
				$displayMode = 1;
			} elseif ($displayMode == 4) {
				$displayMode = 2;
			} elseif ($displayMode == 6) {
				$displayMode = 5;
			}
		}

		// For backward compatibility. Image's filename are still in French, so we use this array to convert
		$statusImg = array(
			'status0' => 'statut0',
			'status1' => 'statut1',
			'status2' => 'statut2',
			'status3' => 'statut3',
			'status4' => 'statut4',
			'status5' => 'statut5',
			'status6' => 'statut6',
			'status7' => 'statut7',
			'status8' => 'statut8',
			'status9' => 'statut9'
		);

		if (!empty($statusImg[$statusType])) {
			$htmlImg = img_picto($statusLabel, $statusImg[$statusType]);
		} else {
			$htmlImg = img_picto($statusLabel, $statusType);
		}

		if ($displayMode === 2) {
			$return = $htmlImg . ' ' . $htmlLabelShort;
		} elseif ($displayMode === 3) {
			$return = $htmlImg;
		} elseif ($displayMode === 4) {
			$return = $htmlImg . ' ' . $htmlLabel;
		} elseif ($displayMode === 5) {
			$return = $htmlLabelShort . ' ' . $htmlImg;
		} else { // $displayMode >= 6
			$return = $htmlLabel . ' ' . $htmlImg;
		}
	} elseif (!empty($displayMode)) {
		// Use new badge (MAIN_STATUS_USES_IMAGES already handled by the previous branch)
		$statusLabelShort = (empty($statusLabelShort) ? $statusLabel : $statusLabelShort);

		$dolGetBadgeParams['attr']['class'] = 'badge-status';
		if (empty($dolGetBadgeParams['attr']['title'])) {
			$dolGetBadgeParams['attr']['title'] = empty($params['tooltip']) ? $statusLabel : ($params['tooltip'] != 'no' ? $params['tooltip'] : '');
		} else {	// If a title was forced from $params['badgeParams']['attr']['title'], we set the class to get it as a tooltip.
			$dolGetBadgeParams['attr']['class'] .= ' classfortooltip';
			// And if we use tooltip, we can output title in HTML  @phan-suppress-next-line PhanTypeInvalidDimOffset
			$dolGetBadgeParams['attr']['title'] = dol_htmlentitiesbr((string) $dolGetBadgeParams['attr']['title'], 1);
		}

		if ($displayMode == 3) {
			$return = dolGetBadge((empty($conf->dol_optimize_smallscreen) ? $statusLabel : (empty($statusLabelShort) ? $statusLabel : $statusLabelShort)), '', $statusType, 'dot', $url, $dolGetBadgeParams);
		} elseif ($displayMode === 5) {
			$return = dolGetBadge($statusLabelShort, $html, $statusType, '', $url, $dolGetBadgeParams);
		} else {
			$return = dolGetBadge(((empty($conf->dol_optimize_smallscreen) && $displayMode != 2) ? $statusLabel : (empty($statusLabelShort) ? $statusLabel : $statusLabelShort)), $html, $statusType, '', $url, $dolGetBadgeParams);
		}
	}

	return $return;
}


/**
 * Function dolGetButtonAction
 *
 * @param string    	$label      	Long label (or tooltip of button if param $text is provided). Also used as tooltip in title attribute. Can be escaped HTML content or full simple text. Used only if $url not defined.
 * @param string    	$text       	Optional : Short label on button. Can be escaped HTML content or full simple text.
 * @param string 		$actionType 	'default', 'edit', 'danger', 'email', 'clone', 'cancel', 'delete', ...
 * @param string|array<int,array{lang:string,enabled:bool,perm:bool|int,label:string,text?:string,url:string,urlroot?:string,isDropDown?:int<0,1>}> 	$url        	Url for link or array of subbutton description
 *                                                                                                                                                                      Example when an array is used:
 *                                                                                                                                                                      $arrayforbutaction = array(
 *                                                                                                                                                                      10 => array('attr' => array('class'=>''), 'lang'=>'propal', 'enabled'=>isModEnabled("propal"), 'perm'=>$user->hasRight('propal', 'creer'), 'label' => 'AddProp', 'url'=>'/comm/propal/card.php?action=create&amp;projectid='.$object->id.'&amp;socid='.$object->socid),
 *                                                                                                                                                                      20 => array('attr' => array('class'=>''), 'lang'=>'mymodule', 'enabled'=>isModEnabled("mymodule"), 'perm'=>$user->hasRight('mymodule', 'write'), 'label' => 'MyModuleAction', 'urlroot'=>dol_build_patch('/mymodule/mypage.php?action=create')),
 *                                                                                                                                                                      30 => array('attr' => array('class'=>''), 'lang'=>'mymodule', 'enabled'=>isModEnabled("mymodule"), 'perm'=>$user->hasRight('mymodule', 'write'), 'label' => 'MyModuleOtherAction', 'urlraw' => '# || external Url || javascript: || tel: || mailto:' ),
 *                                                                                                                                                                      );                                                                                                               );
 * @param string    	$id         	Attribute id of action button. Example 'action-delete'. This can be used for full ajax confirm if this code is reused into the ->formconfirm() method.
 * @param bool|int		$userRight  	User action right. True or 1 of ok. Use 0 if user has no permission, it will add the message "No permission" on tooltip (if no other message explicitly provided). Use -1 to have button not allowed without adding the message (because an explicit label is already set).
 * // phpcs:disable
 * @param array{confirm?:array{url?:string,title?:string,content?:string,use_unsecured_unescapedattr?:bool|string[],action-btn-label?:string,cancel-btn-label?:string,modal?:bool},attr?:array<string,mixed>,areDropdownButtons?:bool,backtopage?:string,lang?:string,enabled?:bool,perm?:int<0,1>,label?:string,url?:string,isDropdown?:int<0,1>,isDropDown?:int<0,1>}	$params = [ // Various params for future : recommended rather than adding more function arguments
 *                                                                                                                                                                                                                                                                                                                                      'attr' => [ // to add or override button attributes
 *                                                                                                                                                                                                                                                                                                                                      	'xxxxx' => '', // your xxxxx attribute you want
 *                                                                                                                                                                                                                                                                                                                                      	'class' => 'reposition', // to add more css class to the button class attribute
 *                                                                                                                                                                                                                                                                                                                                      	'classOverride' => '' // to replace class attribute of the button
 *                                                                                                                                                                                                                                                                                                                                      ],
 *                                                                                                                                                                                                                                                                                                                                      'confirm' => [
 *                                                                                                                                                                                                                                                                                                                                      	'url' => 'http://', // Override Url to go when user click on action btn, if empty default url is $url.?confirm=yes, for no js compatibility use $url for fallback confirm.
 *                                                                                                                                                                                                                                                                                                                                      	'title' => '', // Override title of modal,  if empty default title use "ConfirmBtnCommonTitle" lang key
 *                                                                                                                                                                                                                                                                                                                                      	'action-btn-label' => '', // Override label of action button,  if empty default label use "Confirm" lang key
 *                                                                                                                                                                                                                                                                                                                                     		'cancel-btn-label' => '', // Override label of cancel button,  if empty default label use "CloseDialog" lang key
 *                                                                                                                                                                                                                                                                                                                                     		'content' => '', // Override text of content,  if empty default content use "ConfirmBtnCommonContent" lang key
 *                                                                                                                                                                                                                                                                                                                                      	'modal' => true, // true|false to display dialog as a modal (with dark background)
 *                                                                                                                                                                                                                                                                                                                                      	'isDropDown' => false, // true|false to display dialog as a dropdown list (css dropdown-item with dark background)
 *                                                                                                                                                                                                                                                                                                                                    	  ],
 *                                                                                                                                                                                                                                                                                                                                      ]
 * // phpcs:enable
 * @return string               		html button
 */
function dolGetButtonAction($label, $text = '', $actionType = 'default', $url = '', $id = '', $userRight = 1, $params = array())
{
	global $hookmanager, $action, $object, $langs;

	// If $url is an array, we must build a dropdown button or recursively iterate over each value
	if (is_array($url)) {
		// Loop on $url array to remove entries of disabled modules
		foreach ($url as $key => $subbutton) {
			if (isset($subbutton['enabled']) && empty($subbutton['enabled'])) {
				unset($url[$key]);
			}
		}

		$out = '';

		if (array_key_exists('areDropdownButtons', $params) && $params["areDropdownButtons"] === false) {  // @phan-suppress-current-line PhanTypeInvalidDimOffset
			foreach ($url as $button) {
				if (!empty($button['lang'])) {
					$langs->load($button['lang']);
				}
				$label = $langs->trans($button['label']);
				$text = $button['text'] ?? '';
				$actionType = $button['actionType'] ?? '';
				$tmpUrl = DOL_URL_ROOT . $button['url'] . (empty($params['backtopage']) ? '' : '&amp;backtopage=' . urlencode($params['backtopage']));
				$id = $button['id'] ?? '';
				$userRight = $button['perm'] ?? 1;
				$button['params'] = $button['params'] ?? [];  // @phan-suppress-current-line PhanPluginDuplicateExpressionAssignmentOperation

				$out .= dolGetButtonAction($label, $text, $actionType, $tmpUrl, $id, $userRight, $button['params']);
			}
			return $out;
		}

		if (count($url) > 1) {
			$out .= '<div class="dropdown inline-block dropdown-holder">';
			$out .= '<a style="margin-right: auto;" class="dropdown-toggle classfortooltip butAction' . ($userRight ? '' : 'Refused') . '" title="' . dol_escape_htmltag($label) . '" data-toggle="dropdown">' . ($text ? $text : $label) . '</a>';
			$out .= '<div class="dropdown-content">';
			foreach ($url as $subbutton) {
				if (!empty($subbutton['lang'])) {
					$langs->load($subbutton['lang']);
				}

				if (!empty($subbutton['urlraw'])) {
					$tmpurl = $subbutton['urlraw']; // Use raw url, no url completion, use only what developer send
				} else {
					$tmpurl = !empty($subbutton['urlroot']) ? $subbutton['urlroot'] : $subbutton['url'];
					$tmpurl = dolCompletUrlForDropdownButton($tmpurl, $params, empty($subbutton['urlroot']));
				}

				$subbuttonparam = array();
				if (!empty($subbutton['attr'])) {
					$subbuttonparam['attr'] = $subbutton['attr'];
				}
				$subbuttonparam['isDropDown'] = (empty($params['isDropDown']) ? ($subbutton['isDropDown'] ?? false) : $params['isDropDown']);

				$out .= dolGetButtonAction($subbutton['text'] ?? '', $langs->trans($subbutton['label']), 'default', $tmpurl, $subbutton['id'] ?? '', $subbutton['perm'], $subbuttonparam);
			}
			$out .= "</div>";
			$out .= "</div>";
		} else {
			foreach ($url as $subbutton) {	// Should loop on 1 record only
				if (!empty($subbutton['lang'])) {
					$langs->load($subbutton['lang']);
				}

				if (!empty($subbutton['urlraw'])) {
					$tmpurl = $subbutton['urlraw']; // Use raw url, no url completion, use only what developer send
				} else {
					$tmpurl = !empty($subbutton['urlroot']) ? $subbutton['urlroot'] : $subbutton['url'];
					$tmpurl = dolCompletUrlForDropdownButton($tmpurl, $params, empty($subbutton['urlroot']));
				}

				$label = $langs->trans($subbutton['label']);
				$text = $subbutton['text'] ?? '';
				if (empty($text)) {
					$text = $label;
					$label = '';
				}

				$out .= dolGetButtonAction($label, $text, 'default', $tmpurl, '', $subbutton['perm'], $params);
			}
		}

		return $out;
	}

	// Here, $url is a simple link
	if (!empty($params['isDropdown']) || !empty($params['isDropDown'])) {	// Use the dropdown-item style (not for action button)
		$class = "dropdown-item";
	} else {
		$class = 'butAction';
		if ($actionType == 'edit') {
			$class = 'butAction butActionEdit';
		} elseif ($actionType == 'email') {
			$class = 'butAction butActionEmail';
		} elseif ($actionType == 'clone') {
			$class = 'butAction butActionClone';
		} elseif ($actionType == 'cancel') {
			$class = 'butAction butActionDelete';
		} elseif ($actionType == 'danger' || $actionType == 'delete') {
			$class = 'butAction butActionDelete';
			if (!empty($url) && strpos($url, 'token=') === false) {
				$url .= '&token=' . newToken();
			}
		}
	}
	$attr = array(
		'class' => $class,
		'href' => empty($url) ? '' : $url,
		'title' => $label
	);

	if (empty($text)) {
		$text = $label;
		$attr['title'] = ''; // if html not set, using label on title is redundant
	} else {
		$attr['title'] = $label;
		$attr['aria-label'] = $label;
	}

	if (empty($userRight) || $userRight < 0) {
		$attr['class'] = 'butActionRefused';
		$attr['href'] = '';
		$attr['title'] = (($label && $text && $label != $text) ? $label : '');
		$attr['title'] = ($attr['title'] ? $attr['title'] . (empty($userRight) ? '<br>' : '') : '');
		$attr['title'] .= ((empty($userRight) && empty($label)) ? $langs->trans('NotEnoughPermissions') : '');
	}

	if (!empty($id)) {
		$attr['id'] = $id;
	}

	// Override attr
	if (!empty($params['attr']) && is_array($params['attr'])) {
		foreach ($params['attr'] as $key => $value) {
			if ($key == 'class') {
				$attr['class'] .= ' ' . $value;
			} elseif ($key == 'classOverride') {
				$attr['class'] = $value;
			} else {
				$attr[$key] = $value;
			}
		}
	}

	// automatic add tooltip when title is detected
	if (!empty($attr['title']) && !empty($attr['class']) && strpos($attr['class'], 'classfortooltip') === false) {
		$attr['class'] .= ' classfortooltip';
	}

	// Js Confirm button
	if ($userRight && !empty($params['confirm'])) {
		if (!is_array($params['confirm'])) {
			$params['confirm'] = array();
		}

		if (empty($params['confirm']['url'])) {
			$params['confirm']['url'] = $url . (strpos($url, '?') > 0 ? '&' : '?') . 'confirm=yes';
		}

		// for js disabled compatibility set $url as call to confirm action and $params['confirm']['url'] to confirmed action
		$attr['data-confirm-url'] = $params['confirm']['url'];
		$attr['data-confirm-title'] = !empty($params['confirm']['title']) ? $params['confirm']['title'] : $langs->trans('ConfirmBtnCommonTitle', $label);
		$attr['data-confirm-content'] = !empty($params['confirm']['content']) ? $params['confirm']['content'] : $langs->trans('ConfirmBtnCommonContent', $label);
		$attr['data-confirm-content'] = preg_replace("/\r|\n/", "", $attr['data-confirm-content']);
		$attr['data-confirm-action-btn-label'] = !empty($params['confirm']['action-btn-label']) ? $params['confirm']['action-btn-label'] : $langs->trans('Confirm');
		$attr['data-confirm-cancel-btn-label'] = !empty($params['confirm']['cancel-btn-label']) ? $params['confirm']['cancel-btn-label'] : $langs->trans('CloseDialog');
		$attr['data-confirm-modal'] = !empty($params['confirm']['modal']) ? $params['confirm']['modal'] : true;

		$attr['class'] .= ' butActionConfirm';
	}

	if (isset($attr['href']) && empty($attr['href'])) {
		unset($attr['href']);
	}

	// TODO replace this $TCompiledAttr generation block by commonHtmlAttributeBuilder like line below
	// $TCompiledAttr = commonHtmlAttributeBuilder($attr, $params['use_unsecured_unescapedattr'] ?? []);
	$TCompiledAttr = array();
	foreach ($attr as $key => $value) {
		if (!empty($params['use_unsecured_unescapedattr']) && is_array($params['use_unsecured_unescapedattr']) && in_array($key, $params['use_unsecured_unescapedattr'])) {
			// Deprecated, forbidden.
			$value = dol_htmlentities($value, ENT_QUOTES | ENT_SUBSTITUTE);
		} elseif ($key == 'href') {
			$value = dolPrintHTMLForAttributeUrl($value);
		} else {
			$value = dolPrintHTMLForAttribute($value);
		}

		$TCompiledAttr[] = $key . '="' . $value . '"';	// $value has been escaped by the dolPrintHTMLForAttribute... just before
	}
	$compiledAttributes = empty($TCompiledAttr) ? '' : implode(' ', $TCompiledAttr);

	$tag = !empty($attr['href']) ? 'a' : 'span';

	$parameters = array(
		'TCompiledAttr' => $TCompiledAttr,				// array
		'compiledAttributes' => $compiledAttributes,	// string
		'attr' => $attr,
		'tag' => $tag,
		'label' => $label,
		'html' => $text,
		'actionType' => $actionType,
		'url' => $url,
		'id' => $id,
		'userRight' => $userRight,
		'params' => $params
	);

	$reshook = $hookmanager->executeHooks('dolGetButtonAction', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
	if ($reshook < 0) {
		setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
	}

	if (empty($reshook)) {
		if (dol_textishtml($text)) {	// If content already HTML encoded
			return '<' . $tag . ' ' . $compiledAttributes . '><span class="textbutton">' . $text . '</span></' . $tag . '>';
		} else {
			return '<' . $tag . ' ' . $compiledAttributes . '><span class="textbutton">' . dol_escape_htmltag($text) . '</span></' . $tag . '>';
		}
	} else {
		return $hookmanager->resPrint;
	}
}

/**
 * Builds an array of safe and properly escaped HTML attributes from a key-value pair list.
 *
 * This function ensures that HTML attributes are correctly encoded for safe output.
 * Special handling is applied for attributes such as `href`, which are processed using `dolPrintHTMLForAttributeUrl()`.
 * All other attributes are escaped using `dolPrintHTMLForAttribute()`.
 *
 * Example:
 * ```php
 * $attr = [
 *     'href' => 'https://example.com?a=1&b=2',
 *     'class' => 'btn btn-primary',
 *     'title' => 'View details'
 * ];
 * $result = commonHtmlAttributeBuilder($attr);
 *
 * // Output:
 * // [
 * //   'href' => 'href="https://example.com?a=1&amp;b=2"',
 * //   'class' => 'class="btn btn-primary"',
 * //   'title' => 'title="View details"'
 * // ]
 * ```
 *
 * @param array<string, string|int|float|null|bool> $attr          	Associative array of attribute names and their values.
 * @param string[]                            		$unescapedAttr  Optional list of attribute names that should **not** be escaped.
 *
 * @return array<string, string> An array where each key corresponds to the attribute name
 *                               and each value is a full `key="escaped_value"` string ready for HTML output.
 */
function commonHtmlAttributeBuilder($attr, array $unescapedAttr = [])
{
	$TCompiledAttr = array();
	if (empty($attr)) {
		return [];
	}

	foreach ($attr as $key => $value) {
		// special boolean attributes case
		if (in_array($key, getListOfHtmlBooleanAttributes())) {
			if ($value) {
				$TCompiledAttr[$key] = $key;
			}
			continue;
		}

		if (!empty($unescapedAttr) && in_array($key, $unescapedAttr)) {
			// Not recommended
			$value = dol_htmlentities((string) $value, ENT_QUOTES | ENT_SUBSTITUTE);
		} elseif ($key == 'href') {
			$value = dolPrintHTMLForAttributeUrl((string) $value);
		} else {
			$value = dolPrintHTMLForAttribute((string) $value);
		}

		$TCompiledAttr[$key] = $key . '="' . $value . '"';	// $value has been escaped by the dolPrintHTMLForAttribute... just before
	}

	return 	$TCompiledAttr;
}

/**
 * Returns a list of HTML boolean attributes.
 *
 * Boolean attributes are attributes whose presence on an HTML element
 * represents a true value, and absence represents false. They do not
 * require a value like name="value"; simply including the attribute
 * enables its behavior.
 *
 * Examples of usage:
 * <input type="checkbox" checked>
 * <input type="text" readonly>
 *
 * @return string[] An array of HTML boolean attribute names.
 */
function getListOfHtmlBooleanAttributes(): array
{
	return [
		// Input / Form
		'checked',
		'disabled',
		'readonly',
		'required',
		'autofocus',
		'multiple',

		// Option
		'selected',

		// Form / General
		'novalidate',
		'formnovalidate',

		// Media
		'autoplay',
		'controls',
		'loop',
		'muted',
		'playsinline',

		// Other elements
		'hidden',
		'open',
		'ismap',
		'reversed',
		'allowfullscreen',
		'itemscope',
		'nomodule',
		'defer',
		'async',
		'default',
		'inert',
	];
}


/**
 * An function to complete dropdown url in dolGetButtonAction
 *
 * @param string 				$url 			the Url to complete
 * @param array<string,mixed> 	$params 		params of dolGetButtonAction function
 * @param bool 					$addDolUrlRoot 	to add root url
 * @return string
 */
function dolCompletUrlForDropdownButton(string $url, array $params, bool $addDolUrlRoot = true)
{
	if (empty($url)) {
		return '';
	}

	$parsedUrl = parse_url($url);
	if ((isset($parsedUrl['scheme']) && in_array($parsedUrl['scheme'], ['javascript', 'mailto', 'tel'])) || strpos($url, '#') === 0) {
		return $url;
	}

	if (!empty($parsedUrl['query'])) {
		// Use parse_str() function to parse the string passed via URL
		$urlQuery = '';
		parse_str($parsedUrl['query'], $urlQuery);
		if (!isset($urlQuery['backtopage']) && isset($params['backtopage'])) {
			$url .= '&amp;backtopage=' . urlencode($params['backtopage']);
		}
	}

	if (!isset($parsedUrl['scheme']) && $addDolUrlRoot) {
		$url = DOL_URL_ROOT . $url;
	}

	return $url;
}


/**
 * Add space between dolGetButtonTitle
 *
 * @param  string $moreClass 	more css class label
 * @return string 				html of title separator
 */
function dolGetButtonTitleSeparator($moreClass = "")
{
	return '<span class="button-title-separator ' . $moreClass . '" ></span>';
}

/**
 * get field error icon
 *
 * @param  string  $fieldValidationErrorMsg 	Message to add in tooltip
 * @return string html output
 */
function getFieldErrorIcon($fieldValidationErrorMsg)
{
	$out = '';
	if (!empty($fieldValidationErrorMsg)) {
		$out .= '<span class="field-error-icon classfortooltip" title="' . dol_escape_htmltag($fieldValidationErrorMsg, 1) . '"  role="alert" >'; // role alert is used for accessibility
		$out .= '<span class="fa fa-exclamation-circle" aria-hidden="true" ></span>'; // For accessibility icon is separated and aria-hidden
		$out .= '</span>';
	}

	return $out;
}

/**
 * Function dolGetButtonTitle : this kind of buttons are used in title in list
 *
 * @param string    $label      label of button
 * @param string    $helpText   optional : content for help tooltip
 * @param string    $iconClass  class for icon element (Example: 'fa fa-file')
 * @param string    $url        the url for link
 * @param string    $id         attribute id of button
 * @param int<-2,2>	$status     0 no user rights, 1 active, 2 current action or selected, -1 Feature Disabled (deprecated, use -2 instead), -2 disable Other reason use param $helpText as tooltip help
 * @param array<string,mixed>	$params		various parameters for future : recommended rather than adding more function arguments
 * @return string               html button
 */
function dolGetButtonTitle($label, $helpText = '', $iconClass = 'fa fa-file', $url = '', $id = '', $status = 1, $params = array())
{
	global $langs, $user;

	// Actually this conf is used in css too for external module compatibility and smooth transition to this function
	if (getDolGlobalString('MAIN_BUTTON_HIDE_UNAUTHORIZED') && (!$user->admin) && $status <= 0) {
		return '';
	}
	// Fix old picto fa-th-list to use fa-grid-vertical instead
	if ($iconClass == 'fa fa-th-list imgforviewmode') {
		$iconClass = ' fa fa-grip-horizontal imgforviewmode';
	}

	$class = 'btnTitle';
	if (in_array($iconClass, array('fa fa-plus-circle', 'fa fa-plus-circle size15x', 'fa fa-comment-dots', 'fa fa-paper-plane'))) {
		$class .= ' btnTitlePlus';
	}
	$useclassfortooltip = 1;

	if (!empty($params['morecss'])) {
		$class .= ' ' . $params['morecss'];
	}

	$attr = array(
		'class' => $class,
		'href' => empty($url) ? '' : $url
	);

	if (!empty($helpText)) {
		$attr['title'] = $helpText;
	} elseif ($label) { // empty($attr['title']) &&
		$attr['title'] = $label;
		$useclassfortooltip = 0;
	}

	if ($status == 2) {
		$attr['class'] .= ' btnTitleSelected';
	} elseif ($status <= 0) {
		$attr['class'] .= ' refused';

		$attr['href'] = '';

		if ($status == -1) { // disable
			$attr['title'] = $langs->transnoentitiesnoconv("FeatureDisabled");
		} elseif ($status == 0) { // Not enough permissions
			$attr['title'] = $langs->transnoentitiesnoconv("NotEnoughPermissions");
		}
	}

	if (!empty($attr['title']) && $useclassfortooltip) {
		$attr['class'] .= ' classfortooltip';
	}

	if (!empty($id)) {
		$attr['id'] = $id;
	}

	// Override attr
	if (!empty($params['attr']) && is_array($params['attr'])) {
		foreach ($params['attr'] as $key => $value) {
			if ($key == 'class') {
				$attr['class'] .= ' ' . $value;
			} elseif ($key == 'classOverride') {
				$attr['class'] = $value;
			} else {
				$attr[$key] = $value;
			}
		}
	}

	if (isset($attr['href']) && empty($attr['href'])) {
		unset($attr['href']);
	}

	// TODO : add a hook

	// Generate attributes with escapement
	$TCompiledAttr = array();
	foreach ($attr as $key => $value) {
		$TCompiledAttr[] = $key . '="' . dol_escape_htmltag($value) . '"';	// Do not use dolPrintHTMLForAttribute() here, we must accept "javascript:string"
	}

	$compiledAttributes = (empty($TCompiledAttr) ? '' : implode(' ', $TCompiledAttr));

	$tag = (empty($attr['href']) ? 'span' : 'a');

	$button = '<' . $tag . ' ' . $compiledAttributes . '>';
	$button .= '<span class="' . $iconClass . ' valignmiddle btnTitle-icon"></span>';
	if (!empty($params['forcenohideoftext'])) {
		$button .= '<span class="valignmiddle text-plus-circle btnTitle-label' . (empty($params['forcenohideoftext']) ? ' hideonsmartphone' : '') . '">' . $label . '</span>';
	}
	$button .= '</' . $tag . '>';

	return $button;
}



/**
 * Start a table with headers and a optional clickable number (don't forget to use "finishSimpleTable()" after the last table row)
 *
 * @param string	$header			The first left header of the table (automatic translated)
 * @param string	$link			(optional) The link to a internal dolibarr page, where to go on clicking on the number or the ... (without the first "/")
 * @param string	$arguments		(optional) Additional arguments for the link (e.g. "search_status=0")
 * @param integer	$emptyColumns	(optional) Number of empty columns to add after the first column
 * @param integer	$number			(optional) The number that is shown right after the first header, when -1 the link is shown as '...'
 * @param string	$pictofulllist 	(optional) The picto to use for the full list link
 * @return void
 *
 * @see finishSimpleTable()
 */
function startSimpleTable($header, $link = "", $arguments = "", $emptyColumns = 0, $number = -1, $pictofulllist = '')
{
	global $langs;

	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';

	print ($emptyColumns < 1) ? '<th>' : '<th colspan="' . ($emptyColumns + 1) . '">';

	print '<span class="valignmiddle">' . $langs->trans($header) . '</span>';

	if (!empty($link)) {
		if (!empty($arguments)) {
			print '<a href="' . DOL_URL_ROOT . '/' . $link . '?' . $arguments . '">';
		} else {
			print '<a href="' . DOL_URL_ROOT . '/' . $link . '">';
		}
	}

	if ($number > -1) {
		print '<span class="badge marginleftonlyshort">' . $number . '</span>';
	} elseif (!empty($link)) {
		print '<span class="badge marginleftonlyshort">...</span>';
	}

	if (!empty($link)) {
		print '</a>';
	}

	print '</th>';

	if ($number < 0 && !empty($link)) {
		print '<th class="right">';
		print '</th>';
	}

	print '</tr>';
}

/**
 * Add the correct HTML close tags for "startSimpleTable(...)" (use after the last table line)
 *
 * @param 	bool 	$addLineBreak	(optional) Add a extra line break after the complete table (\<br\>)
 * @return 	void
 *
 * @see startSimpleTable()
 */
function finishSimpleTable($addLineBreak = false)
{
	print '</table>';
	print '</div>';

	if ($addLineBreak) {
		print '<br>';
	}
}

/**
 * Add a summary line to the current open table ("None", "XMoreLines" or "Total xxx")
 *
 * @param integer	$tableColumnCount		The complete count columns of the table
 * @param integer	$num					The count of the rows of the table, when it is zero (0) the "$noneWord" is shown instead
 * @param integer	$nbofloop				(optional)	The maximum count of rows thaht the table show (when it is zero (0) no summary line will show, expect "$noneWord" when $num === 0)
 * @param integer	$total					(optional)	The total value thaht is shown after when the table has minimum of one entire
 * @param string	$noneWord				(optional)	The word that is shown when the table has no entries ($num === 0)
 * @param bool		$extraRightColumn		(optional)	Add a additional column after the summary word and total number
 * @return void
 */
function addSummaryTableLine($tableColumnCount, $num, $nbofloop = 0, $total = 0, $noneWord = "None", $extraRightColumn = false)
{
	global $langs;

	if ($num === 0) {
		print '<tr class="oddeven">';
		print '<td colspan="' . $tableColumnCount . '"><span class="opacitymedium">' . $langs->trans($noneWord) . '</span></td>';
		print '</tr>';
		return;
	}

	if ($nbofloop === 0) {
		// don't show a summary line
		return;
	}

	/* Case already handled above, commented to satisfy phpstan.
	if ($num === 0) {
		$colspan = $tableColumnCount;
	} else
	*/
	if ($num > $nbofloop) {
		$colspan = $tableColumnCount;
	} else {
		$colspan = $tableColumnCount - 1;
	}

	if ($extraRightColumn) {
		$colspan--;
	}

	print '<tr class="liste_total">';

	if ($nbofloop > 0 && $num > $nbofloop) {
		print '<td colspan="' . $colspan . '" class="right">' . $langs->trans("XMoreLines", ($num - $nbofloop)) . '</td>';
	} else {
		print '<td colspan="' . $colspan . '" class="right"> ' . $langs->trans("Total") . '</td>';
		print '<td class="right centpercent">' . price($total) . '</td>';
	}

	if ($extraRightColumn) {
		print '<td></td>';
	}

	print '</tr>';
}



/**
 * Create a button to copy $valuetocopy in the clipboard (for copy and paste feature).
 * Code that handle the click is inside core/js/lib_foot.js.php.
 *
 * @param 	string 		$valuetocopy 		The value to print
 * @param	int<0,1>	$showonlyonhover	Show the copy-paste button only on hover
 * @param	string		$texttoshow			Replace the value to show with this text. Use 'none' to show no text (only the copy-paste picto)
 * @return 	string 							The string to print for the button
 */
function showValueWithClipboardCPButton($valuetocopy, $showonlyonhover = 1, $texttoshow = '')
{
	global $langs;

	$tag = 'span'; 	// Using div (like any style of type 'block') does not work when using the js copy code.

	$result = '<span class="clipboardCP' . ($showonlyonhover ? ' clipboardCPShowOnHover valignmiddle' : '') . '">';
	if ($texttoshow === 'none') {
		$result .= '<' . $tag . ' class="clipboardCPValue hidewithsize">' . dol_escape_htmltag($valuetocopy, 1, 1) . '</' . $tag . '>';
		$result .= '<span class="clipboardCPValueToPrint"></span>';
	} elseif ($texttoshow) {
		$result .= '<' . $tag . ' class="clipboardCPValue hidewithsize">' . dol_escape_htmltag($valuetocopy, 1, 1) . '</' . $tag . '>';
		$result .= '<span class="clipboardCPValueToPrint">' . dol_escape_htmltag($texttoshow, 1, 1) . '</span>';
	} else {
		$result .= '<' . $tag . ' class="clipboardCPValue">' . dol_escape_htmltag($valuetocopy, 1, 1) . '</' . $tag . '>';
	}
	$result .= '<span class="clipboardCPButton far fa-clipboard opacitymedium paddingleft pictomodule" title="' . dolPrintHTML($langs->trans("ClickToCopyToClipboard")) . '"></span>';
	$result .= img_picto('', 'tick', 'class="clipboardCPTick hidden paddingleft pictomodule"');
	$result .= '<span class="clipboardCPText"></span>';
	$result .= '</span>';

	return $result;
}



/**
 *	Show html area with actions in messaging format.
 *	Note: Global parameter $param must be defined.
 *
 *	@param	Conf				$conf		Object conf
 *	@param	Translate			$langs		Object langs
 *	@param	DoliDB				$db			Object db
 *	@param	?CommonObject		$filterobj	Filter on object Adherent|Societe|Project|Product|CommandeFournisseur|Dolresource|Ticket|... to list events linked to an object
 *	@param	?Contact			$objcon		Filter on object contact to filter events on a contact
 *	@param  int					$noprint	Return string but does not output it
 *	@param  string				$actioncode	Filter on actioncode
 *	@param  string				$donetodo	Filter on event 'done' or 'todo' or ''=nofilter (all).
 *	@param  array<string,string>	$filters	Filter on other fields
 *	@param  string				$sortfield	Sort field
 *	@param  string				$sortorder	Sort order
 *	@return	?string							Return html part or void if noprint is 1
 */
function show_actions_messaging($conf, $langs, $db, $filterobj, $objcon = null, $noprint = 0, $actioncode = '', $donetodo = 'done', $filters = array(), $sortfield = 'a.datep,a.id', $sortorder = 'DESC')
{
	dol_syslog('show_actions_messaging::begin', LOG_DEBUG);
	global $user, $conf;
	global $form;

	global $param, $massactionbutton;

	require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';

	// Check parameters
	if (!is_object($filterobj) && !is_object($objcon)) {
		dol_print_error(null, 'BadParameter');
	}

	$histo = array();
	'@phan-var-force array<int,array{type:string,tododone:string,id:string,datestart:int|string,dateend:int|string,fulldayevent:int,note:string,message:string,percent:string,userid:string,login:string,userfirstname:string,userlastname:string,userphoto:string,msg_from?:string,contact_id?:string,socpeopleassigned?:int[],lastname?:string,firstname?:string,fk_element?:int,elementtype?:string,acode:string,alabel?:string,libelle?:string,apicto?:string}> $histo';

	$numaction = 0;
	$now = dol_now();

	$sortfield_list = explode(',', $sortfield);
	$sortfield_label_list = array('a.id' => 'id', 'a.datep' => 'dp', 'a.percent' => 'percent');
	$sanitized_sortfield_new_list = array();
	foreach ($sortfield_list as $sortfield_value) {
		$sanitized_sortfield_new_list[] = $sortfield_label_list[trim($sortfield_value)];  //@phan-suppress-current-line SqlInjection
	}
	$sanitized_sortfield_new = implode(',', $sanitized_sortfield_new_list);

	$sql = null;
	$sql2 = null;

	if (isModEnabled('agenda')) {
		// Search histo on actioncomm
		if (is_object($objcon) && $objcon->id > 0) {
			$sql = "SELECT DISTINCT a.id, a.label as label,";
		} else {
			$sql = "SELECT a.id, a.label as label,";
		}
		$sql .= " a.datep as dp,";
		$sql .= " a.note as message,";
		$sql .= " a.datep2 as dp2,";
		$sql .= " a.percent as percent, 'action' as type,";
		$sql .= " a.fk_element, a.elementtype,";
		$sql .= " a.fk_contact, a.fulldayevent,";
		$sql .= " a.email_from as msg_from,";
		$sql .= " c.code as acode, c.libelle as alabel, c.picto as apicto,";
		$sql .= " u.rowid as user_id, u.login as user_login, u.photo as user_photo, u.firstname as user_firstname, u.lastname as user_lastname";
		if (is_object($filterobj) && get_class($filterobj) == 'Societe') {
			$sql .= ", sp.lastname, sp.firstname";
		} elseif (is_object($filterobj) && get_class($filterobj) == 'Adherent') {
			$sql .= ", m.lastname, m.firstname";
		} elseif (is_object($filterobj) && in_array(get_class($filterobj), array('Commande', 'CommandeFournisseur', 'Product', 'Ticket', 'BOM', 'Contrat', 'Facture', 'FactureFournisseur', 'Propal', 'Expedition'))) {
			$sql .= ", o.ref";
		} else {
			if (is_object($filterobj) && !empty($filterobj->table_element) && !empty($filterobj->element) && !empty($filterobj->id) && array_key_exists('ref', $filterobj->fields)) {
				$sql .= ", o.ref";
			}
		}
		$sql .= " FROM " . MAIN_DB_PREFIX . "actioncomm as a";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "user as u on u.rowid = a.fk_user_action";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_actioncomm as c ON a.fk_action = c.id";

		$force_filter_contact = $filterobj instanceof User;

		if (is_object($objcon) && $objcon->id > 0) {
			$force_filter_contact = true;
			$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "actioncomm_resources as r ON a.id = r.fk_actioncomm";
			$sql .= " AND r.element_type = '" . $db->escape($objcon->table_element) . "' AND r.fk_element = " . ((int) $objcon->id);
		}

		if ((is_object($filterobj) && get_class($filterobj) == 'Societe') || (is_object($filterobj) && get_class($filterobj) == 'Contact')) {
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "socpeople as sp ON a.fk_contact = sp.rowid";
		} elseif (is_object($filterobj) && get_class($filterobj) == 'Dolresource') {
			$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "element_resources as er";
			$sql .= " ON er.resource_type = 'dolresource'";
			$sql .= " AND er.element_id = a.id";
			$sql .= " AND er.resource_id = " . ((int) $filterobj->id);
		} elseif (is_object($filterobj) && get_class($filterobj) == 'Adherent') {
			$sql .= ", " . MAIN_DB_PREFIX . "adherent as m";
		} elseif (is_object($filterobj) && get_class($filterobj) == 'CommandeFournisseur') {
			$sql .= ", " . MAIN_DB_PREFIX . "commande_fournisseur as o";
		} elseif (is_object($filterobj) && get_class($filterobj) == 'Product') {
			$sql .= ", " . MAIN_DB_PREFIX . "product as o";
		} elseif (is_object($filterobj) && get_class($filterobj) == 'Ticket') {
			$sql .= ", " . MAIN_DB_PREFIX . "ticket as o";
		} elseif (is_object($filterobj) && get_class($filterobj) == 'BOM') {
			$sql .= ", " . MAIN_DB_PREFIX . "bom_bom as o";
		} elseif (is_object($filterobj) && get_class($filterobj) == 'Contrat') {
			$sql .= ", " . MAIN_DB_PREFIX . "contrat as o";
		} elseif (is_object($filterobj) && get_class($filterobj) == 'Facture') {
			$sql .= ", " . MAIN_DB_PREFIX . "facture as o";
		} elseif (is_object($filterobj) && get_class($filterobj) == 'FactureFournisseur') {
			$sql .= ", " . MAIN_DB_PREFIX . "facture_fourn as o";
		} elseif (is_object($filterobj) && get_class($filterobj) == 'Commande') {
			$sql .= ", " . MAIN_DB_PREFIX . "commande as o";
		} elseif (is_object($filterobj) && get_class($filterobj) == 'Expedition') {
			$sql .= ", " . MAIN_DB_PREFIX . "expedition as o";
		} elseif (is_object($filterobj) && get_class($filterobj) == 'Propal') {
			$sql .= ", " . MAIN_DB_PREFIX . "propal as o";
		} else {
			if (is_object($filterobj) && !empty($filterobj->table_element) && !empty($filterobj->element) && !empty($filterobj->id) && array_key_exists('ref', $filterobj->fields)) {
				$sql .= ", " . MAIN_DB_PREFIX . $filterobj->table_element . " as o";
			}
		}
		$sql .= " WHERE a.entity IN (" . getEntity('agenda') . ")";
		if (!$force_filter_contact) {
			if (is_object($filterobj) && in_array(get_class($filterobj), array('Societe', 'Client', 'Fournisseur')) && $filterobj->id) {
				$sql .= " AND a.fk_soc = " . ((int) $filterobj->id);
			} elseif (is_object($filterobj) && get_class($filterobj) == 'Project' && $filterobj->id) {
				$sql .= " AND a.fk_project = o.rowid AND a.fk_project = " . ((int) $filterobj->id);
			} elseif (is_object($filterobj) && get_class($filterobj) == 'Adherent') {
				$sql .= " AND a.fk_element = m.rowid AND a.elementtype = 'member'";
				if ($filterobj->id) {
					$sql .= " AND a.fk_element = " . ((int) $filterobj->id);
				}
			} elseif (is_object($filterobj) && get_class($filterobj) == 'Commande') {
				$sql .= " AND a.fk_element = o.rowid AND a.elementtype = 'order'";
				if ($filterobj->id) {
					$sql .= " AND a.fk_element = " . ((int) $filterobj->id);
				}
			} elseif (is_object($filterobj) && get_class($filterobj) == 'Expedition') {
				$sql .= " AND a.fk_element = o.rowid AND a.elementtype = 'shipping'";
				if ($filterobj->id) {
					$sql .= " AND a.fk_element = " . ((int) $filterobj->id);
				}
			} elseif (is_object($filterobj) && get_class($filterobj) == 'Propal') {
				$sql .= " AND a.fk_element = o.rowid AND a.elementtype = 'propal'";
				if ($filterobj->id) {
					$sql .= " AND a.fk_element = " . ((int) $filterobj->id);
				}
			} elseif (is_object($filterobj) && get_class($filterobj) == 'CommandeFournisseur') {
				$sql .= " AND a.fk_element = o.rowid AND a.elementtype = 'order_supplier'";
				if ($filterobj->id) {
					$sql .= " AND a.fk_element = " . ((int) $filterobj->id);
				}
			} elseif (is_object($filterobj) && get_class($filterobj) == 'Product') {
				$sql .= " AND a.fk_element = o.rowid AND a.elementtype = 'product'";
				if ($filterobj->id) {
					$sql .= " AND a.fk_element = " . ((int) $filterobj->id);
				}
			} elseif (is_object($filterobj) && get_class($filterobj) == 'Ticket') {
				$sql .= " AND a.fk_element = o.rowid AND a.elementtype = 'ticket'";
				if ($filterobj->id) {
					$sql .= " AND a.fk_element = " . ((int) $filterobj->id);
				}
			} elseif (is_object($filterobj) && get_class($filterobj) == 'BOM') {
				$sql .= " AND a.fk_element = o.rowid AND a.elementtype = 'bom'";
				if ($filterobj->id) {
					$sql .= " AND a.fk_element = " . ((int) $filterobj->id);
				}
			} elseif (is_object($filterobj) && get_class($filterobj) == 'Contrat') {
				$sql .= " AND a.fk_element = o.rowid AND a.elementtype = 'contract'";
				if ($filterobj->id) {
					$sql .= " AND a.fk_element = " . ((int) $filterobj->id);
				}
			} elseif (is_object($filterobj) && get_class($filterobj) == 'Contact' && $filterobj->id) {
				$sql .= " AND a.fk_contact = sp.rowid";
				$sql .= " AND a.fk_contact = " . ((int) $filterobj->id);
			} elseif (is_object($filterobj) && get_class($filterobj) == 'Facture') {
				$sql .= " AND a.fk_element = o.rowid";
				if ($filterobj->id) {
					$sql .= " AND a.fk_element = " . ((int) $filterobj->id) . " AND a.elementtype = 'invoice'";
				}
			} elseif (is_object($filterobj) && get_class($filterobj) == 'FactureFournisseur') {
				$sql .= " AND a.fk_element = o.rowid";
				if ($filterobj->id) {
					$sql .= " AND a.fk_element = " . ((int) $filterobj->id) . " AND a.elementtype = 'invoice_supplier'";
				}
			} else {
				if (is_object($filterobj) && !empty($filterobj->element) && !empty($filterobj->id) && array_key_exists('ref', $filterobj->fields)) {
					$sql .= " AND a.fk_element = o.rowid";
					$sql .= " AND a.elementtype = '" . $db->escape($filterobj->element) . "'";
					$sql .= " AND a.fk_element = " . ((int) $filterobj->id);
				}
			}
		} else {
			$sql .= " AND u.rowid = " . ((int) $filterobj->id);
		}

		// Condition on actioncode
		if (!empty($actioncode) && $actioncode != '-1') {
			if (!getDolGlobalString('AGENDA_USE_EVENT_TYPE')) {
				if ($actioncode == 'AC_NON_AUTO') {
					$sql .= " AND c.type != 'systemauto'";
				} elseif ($actioncode == 'AC_ALL_AUTO') {
					$sql .= " AND c.type = 'systemauto'";
				} else {
					if ($actioncode == 'AC_OTH') {
						$sql .= " AND c.type != 'systemauto'";
					} elseif ($actioncode == 'AC_OTH_AUTO') {
						$sql .= " AND c.type = 'systemauto'";
					}
				}
			} else {
				if ($actioncode == 'AC_NON_AUTO') {
					$sql .= " AND c.type != 'systemauto'";
				} elseif ($actioncode == 'AC_ALL_AUTO') {
					$sql .= " AND c.type = 'systemauto'";
				} else {
					$sql .= " AND c.code = '" . $db->escape($actioncode) . "'";
				}
			}
		}
		if ($donetodo == 'todo') {
			$sql .= " AND ((a.percent >= 0 AND a.percent < 100) OR (a.percent = -1 AND a.datep > '" . $db->idate($now) . "'))";
		} elseif ($donetodo == 'done') {
			$sql .= " AND (a.percent = 100 OR (a.percent = -1 AND a.datep <= '" . $db->idate($now) . "'))";
		}
		if (is_array($filters) && $filters['search_agenda_label']) {
			$sql .= natural_search('a.label', $filters['search_agenda_label']);
		}
	}

	// Add also event from emailings. TODO This should be replaced by an automatic event ? May be it's too much for very large emailing.
	if (
		isModEnabled('mailing') && !empty($objcon->email)
		&& (empty($actioncode) || $actioncode == 'AC_OTH_AUTO' || $actioncode == 'AC_EMAILING')
	) {
		$langs->load("mails");

		$sql2 = "SELECT m.rowid as id, m.titre as label, mc.date_envoi as dp, mc.date_envoi as dp2, '100' as percent, 'mailing' as type";
		$sql2 .= ", null as fk_element, '' as elementtype, null as contact_id";
		$sql2 .= ", 'AC_EMAILING' as acode, '' as alabel, '' as apicto";
		$sql2 .= ", u.rowid as user_id, u.login as user_login, u.photo as user_photo, u.firstname as user_firstname, u.lastname as user_lastname"; // User that valid action
		if (is_object($filterobj) && get_class($filterobj) == 'Societe') {
			$sql2 .= ", '' as lastname, '' as firstname";
		} elseif (is_object($filterobj) && get_class($filterobj) == 'Adherent') {
			$sql2 .= ", '' as lastname, '' as firstname";
		} elseif (is_object($filterobj) && get_class($filterobj) == 'CommandeFournisseur') {
			$sql2 .= ", '' as ref";
		} elseif (is_object($filterobj) && get_class($filterobj) == 'Product') {
			$sql2 .= ", '' as ref";
		} elseif (is_object($filterobj) && get_class($filterobj) == 'Ticket') {
			$sql2 .= ", '' as ref";
		}
		$sql2 .= " FROM " . MAIN_DB_PREFIX . "mailing as m, " . MAIN_DB_PREFIX . "mailing_cibles as mc, " . MAIN_DB_PREFIX . "user as u";
		$sql2 .= " WHERE mc.email = '" . $db->escape($objcon->email) . "'"; // Search is done on email.
		$sql2 .= " AND mc.statut = 1";
		$sql2 .= " AND u.rowid = m.fk_user_valid";
		$sql2 .= " AND mc.fk_mailing=m.rowid";
	}

	$num = 0;
	$MAXWITHOUTPAGINATION = getDolGlobalInt('AGENDA_MAX_EVENTS_ON_PAGE_WITHOUT_PAGINATION', 100);

	if ($sql || $sql2) {	// May not be defined if module Agenda is not enabled and mailing module disabled too
		if (!empty($sql) && !empty($sql2)) {
			$sql = $sql . " UNION " . $sql2;
		} elseif (empty($sql) && !empty($sql2)) {
			$sql = $sql2;
		}

		//TODO Add navigation with this limits...
		$offset = 0;
		$limit = $MAXWITHOUTPAGINATION;

		// Complete request and execute it with limit
		$sql .= $db->order($sanitized_sortfield_new, $sortorder);
		if ($limit) {
			$sql .= $db->plimit($limit + 1, $offset);
		}

		dol_syslog("function.lib::show_actions_messaging", LOG_DEBUG);

		$resql = $db->query($sql);
		if ($resql) {
			$i = 0;
			$num = $db->num_rows($resql);

			$imaxinloop = ($limit ? min($num, $limit) : $num);
			while ($i < $imaxinloop) {
				$obj = $db->fetch_object($resql);

				if ($obj->type == 'action') {
					$contactaction = new ActionComm($db);
					$contactaction->id = $obj->id;
					$result = $contactaction->fetchResources();
					if ($result < 0) {
						dol_print_error($db);
						setEventMessage("actions.lib::show_actions_messaging Error fetch resource", 'errors');
					}

					//if ($donetodo == 'todo') $sql.= " AND ((a.percent >= 0 AND a.percent < 100) OR (a.percent = -1 AND a.datep > '".$db->idate($now)."'))";
					//elseif ($donetodo == 'done') $sql.= " AND (a.percent = 100 OR (a.percent = -1 AND a.datep <= '".$db->idate($now)."'))";
					$tododone = '';
					if (($obj->percent >= 0 and $obj->percent < 100) || ($obj->percent == -1 && $obj->dp > $now)) {
						$tododone = 'todo';
					}

					$histo[$numaction] = array(
						'type' => $obj->type,
						'tododone' => $tododone,
						'id' => $obj->id,
						'datestart' => $db->jdate($obj->dp),
						'dateend' => $db->jdate($obj->dp2),
						'fulldayevent' => (int) $obj->fulldayevent,
						'note' => $obj->label,
						'message' => $obj->message,
						'percent' => $obj->percent,

						'userid' => $obj->user_id,
						'login' => $obj->user_login,
						'userfirstname' => $obj->user_firstname,
						'userlastname' => $obj->user_lastname,
						'userphoto' => $obj->user_photo,
						'msg_from' => $obj->msg_from,

						'contact_id' => $obj->fk_contact,
						'socpeopleassigned' => $contactaction->socpeopleassigned,
						'lastname' => (empty($obj->lastname) ? '' : $obj->lastname),
						'firstname' => (empty($obj->firstname) ? '' : $obj->firstname),
						'fk_element' => $obj->fk_element,
						'elementtype' => $obj->elementtype,
						// Type of event
						'acode' => $obj->acode,
						'alabel' => $obj->alabel,
						'libelle' => $obj->alabel, // deprecated
						'apicto' => $obj->apicto
					);
				} else {
					$histo[$numaction] = array(
						'type' => $obj->type,
						'tododone' => 'done',
						'id' => $obj->id,
						'datestart' => $db->jdate($obj->dp),
						'dateend' => $db->jdate($obj->dp2),
						'fulldayevent' => (int) $obj->fulldayevent,
						'note' => $obj->label,
						'message' => $obj->message,
						'percent' => $obj->percent,
						'acode' => $obj->acode,

						'userid' => $obj->user_id,
						'login' => $obj->user_login,
						'userfirstname' => $obj->user_firstname,
						'userlastname' => $obj->user_lastname,
						'userphoto' => $obj->user_photo
					);
				}

				$numaction++;
				$i++;
			}
		} else {
			dol_print_error($db);
		}
	}

	// Set $out to show events
	$out = '';

	if (!isModEnabled('agenda')) {
		$langs->loadLangs(array("admin", "errors"));
		$out = info_admin($langs->trans("WarningModuleXDisabledSoYouMayMissEventHere", $langs->transnoentitiesnoconv("Module2400Name")), 0, 0, 'warning');
	}

	if (isModEnabled('agenda') || (isModEnabled('mailing') && !empty($objcon->email))) {
		$delay_warning = getDolGlobalInt('MAIN_DELAY_ACTIONS_TODO') * 24 * 60 * 60;

		require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
		include_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
		require_once DOL_DOCUMENT_ROOT . '/core/class/html.formactions.class.php';
		require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';

		$formactions = new FormActions($db);

		$actionstatic = new ActionComm($db);
		$userstatic = new User($db);
		$contactstatic = new Contact($db);
		$userGetNomUrlCache = array();
		$contactGetNomUrlCache = array();

		$out .= '<div class="filters-container" >';
		$out .= '<form name="listactionsfilter" class="listactionsfilter" action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
		$out .= '<input type="hidden" name="token" value="' . newToken() . '">';

		if (
			$objcon && get_class($objcon) == 'Contact' &&
			(is_null($filterobj) || get_class($filterobj) == 'Societe')
		) {
			$out .= '<input type="hidden" name="id" value="' . $objcon->id . '" />';
		} else {
			$out .= '<input type="hidden" name="id" value="' . $filterobj->id . '" />';
		}
		if (($filterobj && get_class($filterobj) == 'Societe')) {
			$out .= '<input type="hidden" name="socid" value="' . $filterobj->id . '" />';
		} else {
			$out .= '<input type="hidden" name="userid" value="' . $filterobj->id . '" />';
		}

		$out .= "\n";

		$out .= '<div class="div-table-responsive-no-min">';
		$out .= '<table class="noborder borderbottom centpercent">';

		$out .= '<tr class="liste_titre">';

		// Action column
		if ($conf->main_checkbox_left_column) {
			$out .= '<th class="liste_titre width50 middle">';
			$searchpicto = $form->showFilterAndCheckAddButtons($massactionbutton ? 1 : 0, 'checkforselect', 1);
			$out .= $searchpicto;
			$out .= '</th>';
		}

		// Date
		$out .= getTitleFieldOfList('Date', 0, $_SERVER["PHP_SELF"], 'a.datep', '', $param, '', $sortfield, $sortorder, 'nowraponall nopaddingleftimp ') . "\n";

		$out .= '<th class="liste_titre hideonsmartphone"><strong class="hideonsmartphone">' . $langs->trans("Search") . ' : </strong></th>';
		if ($donetodo) {
			$out .= '<th class="liste_titre"></th>';
		}
		// Type of event
		$out .= '<th class="liste_titre">';
		$out .= '<span class="fas fa-square inline-block fawidth30 hideonsmartphone" style="color: #ddd;" title="' . $langs->trans("ActionType") . '"></span>';
		$out .= $formactions->select_type_actions($actioncode, "actioncode", '', getDolGlobalString('AGENDA_USE_EVENT_TYPE') ? -1 : 1, 0, 0, 1, 'selecttype minwidth100', $langs->trans("Type"));
		$out .= '</th>';
		// Label
		$out .= '<th class="liste_titre maxwidth100onsmartphone">';
		$out .= '<input type="text" class="maxwidth100onsmartphone" name="search_agenda_label" value="' . $filters['search_agenda_label'] . '" placeholder="' . $langs->trans("Label") . '">';
		$out .= '</th>';

		// Action column
		if (!$conf->main_checkbox_left_column) {
			$out .= '<th class="liste_titre width50 middle">';
			$searchpicto = $form->showFilterAndCheckAddButtons($massactionbutton ? 1 : 0, 'checkforselect', 1);
			$out .= $searchpicto;
			$out .= '</th>';
		}

		$out .= '</tr>';

		$out .= '</table>';

		$out .= '</form>';
		$out .= '</div>';

		$out .= "\n";

		$out .= '<ul class="timeline">';

		if ($donetodo) {
			$tmp = '';
			if ($filterobj instanceof Societe) {
				$tmp .= '<a href="' . DOL_URL_ROOT . '/comm/action/list.php?mode=show_list&socid=' . $filterobj->id . '&status=done">';
			}
			if ($filterobj instanceof User) {
				$tmp .= '<a href="' . DOL_URL_ROOT . '/comm/action/list.php?mode=show_list&socid=' . $filterobj->id . '&status=done">';
			}
			$tmp .= ($donetodo != 'done' ? $langs->trans("ActionsToDoShort") : '');
			$tmp .= ($donetodo != 'done' && $donetodo != 'todo' ? ' / ' : '');
			$tmp .= ($donetodo != 'todo' ? $langs->trans("ActionsDoneShort") : '');
			//$out.=$langs->trans("ActionsToDoShort").' / '.$langs->trans("ActionsDoneShort");
			if ($filterobj instanceof Societe) {
				$tmp .= '</a>';
			}
			if ($filterobj instanceof User) {
				$tmp .= '</a>';
			}
			$out .= getTitleFieldOfList($tmp);
		}

		require_once DOL_DOCUMENT_ROOT . '/comm/action/class/cactioncomm.class.php';
		$caction = new CActionComm($db);
		$arraylist = $caction->liste_array(1, 'code', '', (!getDolGlobalString('AGENDA_USE_EVENT_TYPE') ? 1 : 0), '', 1);

		$actualCycleDate = false;

		// Loop on each event to show it
		foreach ($histo as $key => $value) {
			$actionstatic->fetch($histo[$key]['id']); // TODO Do we need this, we already have a lot of data of line into $histo

			$actionstatic->type_picto = $histo[$key]['apicto'];
			$actionstatic->type_code = $histo[$key]['acode'];

			$labeltype = $actionstatic->type_code;
			if (!getDolGlobalString('AGENDA_USE_EVENT_TYPE') && empty($arraylist[$labeltype])) {
				$labeltype = 'AC_OTH';
			}
			if (!empty($actionstatic->code) && preg_match('/^TICKET_MSG/', $actionstatic->code)) {
				$labeltype = $langs->trans("Message");
			} else {
				if (!empty($arraylist[$labeltype])) {
					$labeltype = $arraylist[$labeltype];
				}
				if ($actionstatic->type_code == 'AC_OTH_AUTO' && ($actionstatic->type_code != $actionstatic->code) && $labeltype && !empty($arraylist[$actionstatic->code])) {
					$labeltype .= ' - ' . $arraylist[$actionstatic->code]; // Use code in priority on type_code
				}
			}

			$url = DOL_URL_ROOT . '/comm/action/card.php?id=' . $histo[$key]['id'];

			$tmpa = dol_getdate($histo[$key]['datestart'], false);

			if (isset($tmpa['year']) && isset($tmpa['yday']) && $actualCycleDate !== $tmpa['year'] . '-' . $tmpa['yday']) {
				$actualCycleDate = $tmpa['year'] . '-' . $tmpa['yday'];
				$out .= '<!-- timeline time label -->';
				$out .= '<li class="time-label">';
				$out .= '<span class="timeline-badge-date">';
				$out .= dol_print_date($histo[$key]['datestart'], 'daytext', 'tzuserrel', $langs);
				$out .= '</span>';
				$out .= '</li>';
				$out .= '<!-- /.timeline-label -->';
			}


			$out .= '<!-- timeline item -->' . "\n";
			$out .= '<li class="timeline-code-' . (!empty($actionstatic->code) ? strtolower($actionstatic->code) : "none") . '">';

			//$timelineicon = getTimelineIcon($actionstatic, $histo, $key);
			$typeicon = $actionstatic->getTypePicto('pictofixedwidth timeline-icon-not-applicble', $labeltype);
			//$out .= $timelineicon;
			//var_dump($timelineicon);
			$out .= $typeicon;

			$out .= '<div class="timeline-item">' . "\n";

			$out .= '<span class="time timeline-header-action2">';

			if (isset($histo[$key]['type']) && $histo[$key]['type'] == 'mailing') {
				$out .= '<a class="paddingleft paddingright timeline-btn2 editfielda" href="' . DOL_URL_ROOT . '/comm/mailing/card.php?id=' . $histo[$key]['id'] . '">' . img_object($langs->trans("ShowEMailing"), "email") . ' ';
				$out .= $histo[$key]['id'];
				$out .= '</a> ';
			} else {
				$out .= $actionstatic->getNomUrl(1, -1, 'valignmiddle') . ' ';
			}

			if (
				$user->hasRight('agenda', 'allactions', 'create') ||
				(($actionstatic->authorid == $user->id || $actionstatic->userownerid == $user->id) && $user->hasRight('agenda', 'myactions', 'create'))
			) {
				$out .= '<a class="paddingleft paddingright timeline-btn2 editfielda" href="' . DOL_MAIN_URL_ROOT . '/comm/action/card.php?action=edit&token=' . newToken() . '&id=' . $actionstatic->id . '&backtopage=' . urlencode($_SERVER["PHP_SELF"] . '?' . $param) . '">';
				//$out .= '<i class="fa fa-pencil" title="'.$langs->trans("Modify").'" ></i>';
				$out .= img_picto($langs->trans("Modify"), 'edit', 'class="edita"');
				$out .= '</a>';
			}

			$out .= '</span>';

			// Date
			$out .= '<span class="time"><i class="fa fa-clock valignmiddle"></i> ';
			$out .= '<span class="valignmiddle marginrightonly">';
			$out .= dol_print_date($histo[$key]['datestart'], 'day', 'tzuserrel');
			//$out .= '</span>';
			//$out .= '<span class="valignmiddle">'.
			$out .= ' '.dol_print_date($histo[$key]['datestart'], 'hour', 'tzuserrel', null, false, 'opacitymedium');
			//$out .= '</span>';
			if ($histo[$key]['dateend'] && $histo[$key]['dateend'] != $histo[$key]['datestart']) {
				$tmpa = dol_getdate($histo[$key]['datestart'], true);
				$tmpb = dol_getdate($histo[$key]['dateend'], true);
				if ($tmpa['mday'] == $tmpb['mday'] && $tmpa['mon'] == $tmpb['mon'] && $tmpa['year'] == $tmpb['year']) {
					$out .= ' - ' . dol_print_date($histo[$key]['dateend'], 'hour', 'tzuserrel', null, false, 1);
				} else {
					$out .= ' - ' . dol_print_date($histo[$key]['dateend'], 'day', 'tzuserrel');
					//$out .= '<span class="valignmiddle marginrightonly">';
					$out .= ' '.dol_print_date($histo[$key]['dateend'], 'hour', 'tzuserrel', null, false, 'opacitymedium');
					//$out .= '</span>';
				}
			}
			$late = 0;
			if ($histo[$key]['percent'] == 0 && $histo[$key]['datestart'] && $histo[$key]['datestart'] < ($now - $delay_warning)) {
				$late = 1;
			}
			if ($histo[$key]['percent'] == 0 && !$histo[$key]['datestart'] && $histo[$key]['dateend'] && $histo[$key]['datestart'] < ($now - $delay_warning)) {
				$late = 1;
			}
			if ($histo[$key]['percent'] > 0 && $histo[$key]['percent'] < 100 && $histo[$key]['dateend'] && $histo[$key]['dateend'] < ($now - $delay_warning)) {
				$late = 1;
			}
			if ($histo[$key]['percent'] > 0 && $histo[$key]['percent'] < 100 && !$histo[$key]['dateend'] && $histo[$key]['datestart'] && $histo[$key]['datestart'] < ($now - $delay_warning)) {
				$late = 1;
			}
			if ($late) {
				$out .= img_warning($langs->trans("Late")) . ' ';
			}
			$out .= "</span></span>\n";

			$out .= '<span class="time">';
			$out .= $actionstatic->getLibStatut(2);
			$out .= '</span>';

			// Ref
			$out .= '<h3 class="timeline-header">';

			// Author of event
			$out .= '<div class="messaging-author inline-block tdoverflowmax150 valignmiddle marginrightonly">';
			if ($histo[$key]['userid'] > 0) {
				if (!isset($userGetNomUrlCache[$histo[$key]['userid']])) { // is in cache ?
					$userstatic->fetch($histo[$key]['userid']);
					$userGetNomUrlCache[$histo[$key]['userid']] = $userstatic->getNomUrl(-1, '', 0, 0, 16, 0, 'firstelselast', '');
				}
				$out .= $userGetNomUrlCache[$histo[$key]['userid']];
			} elseif (!empty($histo[$key]['msg_from']) && $actionstatic->code == 'TICKET_MSG') {
				if (!isset($contactGetNomUrlCache[$histo[$key]['msg_from']])) {
					if ($contactstatic->fetch(0, null, '', $histo[$key]['msg_from']) > 0) {
						$contactGetNomUrlCache[$histo[$key]['msg_from']] = $contactstatic->getNomUrl(-1, '', 16);
					} else {
						$contactGetNomUrlCache[$histo[$key]['msg_from']] = $histo[$key]['msg_from'];
					}
				}
				$out .= $contactGetNomUrlCache[$histo[$key]['msg_from']];
			} else {
				$out .= '<img class="photomemberphoto userphoto" alt="" src="/public/theme/common/user_anonymous.png">'.$langs->trans("Anonymous");
			}
			$out .= '</div>';

			// Title
			$out .= ' <div class="messaging-title inline-block">';
			//$out .= $actionstatic->getTypePicto();	// The type of event is already into the timeline on left.
			if (empty($conf->dol_optimize_smallscreen) && $actionstatic->type_code != 'AC_OTH_AUTO') {
				$out .= $labeltype . ' - ';
			}

			$tmplabel = '';

			if (!empty($actionstatic->code) && preg_match('/^TICKET_MSG_PRIVATE/', $actionstatic->code)) {
				$out .= $langs->trans('TicketNewMessage').' - <em>'.img_picto($langs->trans('Private'), 'lock', 'class="valignmiddle"').' '.$langs->trans('Private').'</em>';
				$summary = preg_replace('/\[[^\]]*\]\s*/', '', $actionstatic->label);
				//if ($summary != $object->title) {
				$out .= ' - '.dolPrintHTML($summary);
				//}
			} elseif (!empty($actionstatic->code) && preg_match('/^TICKET_MSG/', $actionstatic->code)) {
				$out .= $langs->trans('TicketNewMessage');
			} elseif (isset($histo[$key]['type'])) {
				if ($histo[$key]['type'] == 'action') {
					$transcode = $langs->transnoentitiesnoconv("Action" . $histo[$key]['acode']);
					//$tmplabel = ($transcode != "Action" . $histo[$key]['acode'] ? $transcode : $histo[$key]['alabel']);
					$tmplabel = $histo[$key]['note'];
					$actionstatic->id = $histo[$key]['id'];
					if ($tmplabel != $labeltype) {
						$out .= dol_escape_htmltag(dol_trunc($tmplabel, 120));
					}
				} elseif ($histo[$key]['type'] == 'mailing') {
					$out .= '<a href="' . DOL_URL_ROOT . '/comm/mailing/card.php?id=' . $histo[$key]['id'] . '">' . img_object($langs->trans("ShowEMailing"), "email") . ' ';
					$transcode = $langs->transnoentitiesnoconv("Action" . $histo[$key]['acode']);
					$tmplabel = ($transcode != "Action" . $histo[$key]['acode'] ? $transcode : 'Send mass mailing');
					$out .= dol_escape_htmltag(dol_trunc($tmplabel, 120));
				} else {
					$tmplabel .= $histo[$key]['note'];
					$out .= dol_escape_htmltag(dol_trunc($tmplabel, 120));
				}
			}
			$out = preg_replace('/ - $/', '', $out);	// Remove ending ' - '

			if (isset($histo[$key]['elementtype']) && !empty($histo[$key]['fk_element'])) {
				if (isset($conf->cache['elementlinkcache'][$histo[$key]['elementtype']]) && isset($conf->cache['elementlinkcache'][$histo[$key]['elementtype']][$histo[$key]['fk_element']])) {
					$link = $conf->cache['elementlinkcache'][$histo[$key]['elementtype']][$histo[$key]['fk_element']];
				} else {
					if (!isset($conf->cache['elementlinkcache'][$histo[$key]['elementtype']])) {
						$conf->cache['elementlinkcache'][$histo[$key]['elementtype']] = array();
					}
					$link = dolGetElementUrl($histo[$key]['fk_element'], $histo[$key]['elementtype'], 1);
					$conf->cache['elementlinkcache'][$histo[$key]['elementtype']][$histo[$key]['fk_element']] = $link;
				}

				// We do not show if link if on object we are filtering on (no need to show the link to ticket X when we are on page of events for the ticket X)
				$showlink = 1;
				if (is_object($filterobj) && get_class($filterobj) == 'Ticket') {
					if ($histo[$key]['elementtype'] == 'ticket') {
						$showlink = 0;
					}
				}

				if ($link && $showlink) {
					$out .= ' - ' . $link;
				}
			}

			$out .= '</div>';

			$out .= '</h3>';

			// Message
			if ($actionstatic->code == 'AC_TICKET_CREATE') {
				$newmess = $filterobj->message;
			} else {
				$newmess = $histo[$key]['message'];
			}
			if (
				!empty($newmess && $newmess != $tmplabel)
				&& $actionstatic->code != 'AC_TICKET_MODIFY'
			) {
				$out .= '<div class="timeline-body wordbreak small">';
				$truncateLines = getDolGlobalInt('MAIN_TRUNCATE_TIMELINE_MESSAGE', 3);
				$truncatedText = dolGetFirstLineOfText($newmess, $truncateLines);
				// dolGetFirstLineOfText() cuts on <br> without caring about tag balance, so a message wrapped in
				// a block tag leaves the excerpt with an unclosed tag. The browser then nests the read more link
				// and the full text inside the excerpt, and hiding the excerpt hides the whole message (#39035).
				$truncatedText = dolCloseUnclosedHtmlTags($truncatedText);
				if ($truncateLines > 0 && strlen($newmess) > strlen($truncatedText)) {
					$out .= '<div class="readmore-block --closed" >';
					$out .= '	<div class="readmore-block__excerpt">';
					$out .= 	dolPrintHTML($truncatedText, 0, array('pre', 'code'));
					$out .= ' 	<br><a class="read-more-link" data-read-more-action="open" href="' . DOL_MAIN_URL_ROOT . '/comm/action/card.php?id=' . $actionstatic->id . '&backtopage=' . urlencode($_SERVER["PHP_SELF"] . '?' . $param) . '" >' . $langs->trans("ReadMore") . ' <span class="fa fa-chevron-right" aria-hidden="true"></span></a>';
					$out .= '	</div>';
					$out .= '	<div class="readmore-block__full-text" >';

					$out .=  dolPrintHTML($newmess, 0, array('pre', 'code'));

					$out .= ' 	<a class="read-less-link" data-read-more-action="close" href="#" ><span class="fa fa-chevron-up" aria-hidden="true"></span> ' . $langs->trans("ReadLess") . '</a>';
					$out .= '	</div>';
					$out .= '</div>';
				} else {
					$out .=  dolPrintHTML($newmess, 0, array('pre', 'code'));
				}
				$out .= '</div>';
			}

			// Timeline footer
			$footer = '';

			// Contact for this action
			if (isset($histo[$key]['socpeopleassigned']) && is_array($histo[$key]['socpeopleassigned']) && count($histo[$key]['socpeopleassigned']) > 0) {
				$contactList = '';
				foreach ($histo[$key]['socpeopleassigned'] as $cid => $Tab) {
					if (empty($conf->cache['contact'][$cid])) {
						$contact = new Contact($db);
						$result = $contact->fetch($cid);
						$conf->cache['contact'][$cid] = $contact;
					} else {
						$contact = $conf->cache['contact'][$cid];
						$result = ($contact instanceof Contact) ? $contact->id : 0;
					}

					if ($result > 0) {
						$contactList .= !empty($contactList) ? ', ' : '';
						$contactList .= $contact->getNomUrl(1);
						if (isset($histo[$key]['acode']) && $histo[$key]['acode'] == 'AC_TEL') {
							if (!empty($contact->phone_pro)) {
								$contactList .= '(' . dol_print_phone($contact->phone_pro) . ')';
							}
						}
					}
				}

				$footer .= $langs->trans('ActionOnContact') . ' : ' . $contactList;
			} elseif (empty($objcon->id) && isset($histo[$key]['contact_id']) && $histo[$key]['contact_id'] > 0) {
				if (empty($conf->cache['contact'][$histo[$key]['contact_id']])) {
					$contact = new Contact($db);
					$result = $contact->fetch($histo[$key]['contact_id']);
					$conf->cache['contact'][$histo[$key]['contact_id']] = $contact;
				} else {
					$contact = $conf->cache['contact'][$histo[$key]['contact_id']];
					$result = ($contact instanceof Contact) ? $contact->id : 0;
				}

				if ($result > 0) {
					$footer .= $contact->getNomUrl(1);
					if (isset($histo[$key]['acode']) && $histo[$key]['acode'] == 'AC_TEL') {
						if (!empty($contact->phone_pro)) {
							$footer .= '(' . dol_print_phone($contact->phone_pro) . ')';
						}
					}
				}
			}

			$documents = getActionCommEcmList($actionstatic);
			if (!empty($documents)) {
				$footer .= '<div class="timeline-documents-container">';
				foreach ($documents as $doc) {
					$footer .= '<span id="document_' . $doc->id . '" class="timeline-documents" ';
					$footer .= ' data-id="' . $doc->id . '" ';
					$footer .= ' data-path="' . $doc->filepath . '"';
					$footer .= ' data-filename="' . dol_escape_htmltag($doc->filename) . '" ';
					$footer .= '>';

					$filePath = DOL_DATA_ROOT . '/' . $doc->filepath . '/' . $doc->filename;
					$mime = dol_mimetype($filePath);
					if (empty($doc->agenda_id)) {
						$dir_ref = $actionstatic->id;
						$modulepart = 'actions';
					} else {
						$split_dir = explode('/', $doc->filepath);
						$modulepart = array_shift($split_dir);
						$dir_ref = implode('/', $split_dir);
					}

					$file = $dir_ref . '/' . $doc->filename;
					$thumb = $dir_ref . '/thumbs/' . substr($doc->filename, 0, strrpos($doc->filename, '.')) . '_mini' . substr($doc->filename, strrpos($doc->filename, '.'));
					$doclink = dol_buildpath('document.php', 1) . '?modulepart=' . $modulepart . '&attachment=0&file=' . urlencode($file) . '&entity=' . $conf->entity;
					$viewlink = dol_buildpath('viewimage.php', 1) . '?modulepart=' . $modulepart . '&file=' . urlencode($thumb) . '&entity=' . $conf->entity;



					$mimeAttr = ' mime="' . $mime . '" ';
					$class = '';
					if (in_array($mime, array('image/png', 'image/jpeg', 'application/pdf'))) {
						$class .= ' documentpreview';
					}

					$footer .= '<a href="' . $doclink . '" class="btn-link ' . $class . '" target="_blank" rel="noopener noreferrer" ' . $mimeAttr . ' >';
					$footer .= img_mime($filePath) . ' ' . $doc->filename;
					$footer .= '</a>';

					$footer .= '</span>';
				}
				$footer .= '</div>';
			}

			if (!empty($footer)) {
				$out .= '<div class="timeline-footer">' . $footer . '</div>';
			}

			$out .= '</div>' . "\n"; // end timeline-item

			$out .= '</li>';
			$out .= '<!-- END timeline item -->';
		}

		$out .= "</ul>\n";

		// Code to manage the click on button data-read-more-action to show full description of an event
		$out .= '<script>
				jQuery(document).ready(function () {
				   $(document).on("click", "[data-read-more-action]", function(e){
						console.log("We click on data-read-more-action");
					   let readMoreBloc = $(this).closest(".readmore-block");
					   if(readMoreBloc.length > 0){
							e.preventDefault();
							if($(this).attr("data-read-more-action") == "close"){
								readMoreBloc.addClass("--closed").removeClass("--open");
								 $("html, body").animate({
									scrollTop: readMoreBloc.offset().top - 200
								}, 100);
							}else{
								readMoreBloc.addClass("--open").removeClass("--closed");
							}
					   }
					});
				});
			</script>';


		if (empty($histo)) {
			$out .= '<span class="opacitymedium">' . $langs->trans("NoRecordFound") . '</span>';
		}

		if ($num > $MAXWITHOUTPAGINATION) {
			$langs->load("errors");
			$out .= '<center><span class="opacitymedium">...' . $langs->trans("WarningTooManyDataPleaseUseMoreFilters", $MAXWITHOUTPAGINATION) . '...</span></center>';
		}
	}

	if ($noprint) {
		return $out;
	} else {
		print $out;
		return null;
	}
}
