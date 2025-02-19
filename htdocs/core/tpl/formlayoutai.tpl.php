<?php
/* Copyright (C) 2024  Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2024-2025	MDW						<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024-2025  Frédéric France         <frederic.france@free.fr>
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
 * @var Conf $conf
 * @var ?FormMail 		$formmail
 * @var ?FormWebsite 	$formwebsite
 * @var ?FormAI 		$formai
 * @var string 			$htmlname
 * @var string 			$showlinktolayout		'emailing', 'email', 'websitepage', ...
 * @var string 			$showlinktolayoutlabel	'...'
 * @var string 			$showlinktoai			'' or 'textgeneration', 'textgenerationemail', 'textgenerationwebpage', ...
 * @var string 			$showlinktoailabel		'...'
 * @var	string			$htmlname
 * @var ?string			$out
 */

//Protection to avoid direct call of template
if (empty($conf) || !is_object($conf)) {
	print "Error, template page can't be called as URL";
	exit(1);
}

if (empty($htmlname)) {
	print 'Parameter htmlname not defined.';
	exit(1);
}

?>
<!-- BEGIN PHP TEMPLATE formlayoutai.tpl.php -->
<?php

'
@phan-var-force ?FormWebSite 	$formwebsite
@phan-var-force ?FormMail 		$formmail
@phan-var-force ?FormAI 		$formai
@phan-var-force string 			$showlinktolayout
@phan-var-force string			$showlinktolayoutlabel
@phan-var-force string          $showlinktoai
@phan-var-force string          $showlinktoailabel
@phan-var-force ?string         $out
';

if (!isset($out)) {	// Init to empty string if not defined
	$out = '';
}

// Add link to add layout
if ($showlinktolayout) {	// May be set only if MAIN_EMAIL_USE_LAYOUT is set
	$out .= '<a href="#" id="linkforlayouttemplates" class="notasortlink inline-block alink marginrightonly">';
	$out .= img_picto($showlinktolayoutlabel, 'layout', 'class="paddingrightonly"');
	$out .= $showlinktolayoutlabel.'...';
	$out .= '</a> &nbsp; &nbsp; ';

	$out .= '<script>
						$(document).ready(function() {
  							$("#linkforlayouttemplates").click(function() {
								console.log("We click on linkforlayouttemplates, we toggle .template-selector");
								event.preventDefault();
								jQuery(".template-selector").toggle();
								jQuery(".ai_input'.$htmlname.'").hide();
								jQuery("#pageContent").show();	// May exists for website page only
							});
						});
					</script>
					';
}
// Add link to add AI content
if ($showlinktoai) {
	$out .= '<a href="#" id="linkforaiprompt'.$showlinktoai.'" class="notasortlink inline-block alink marginrightonly">';
	$out .= img_picto($showlinktoailabel, 'ai', 'class="paddingrightonly"');
	$out .= $showlinktoailabel.'...';
	$out .= '</a>';

	$out .= '<script>
						$(document).ready(function() {
  							$("#linkforaiprompt'.$showlinktoai.'").click(function() {
								console.log("formlayoutai.tpl: We click on linkforaiprompt'.$showlinktoai.', we toggle .ai_input'.$showlinktoai.'");
								event.preventDefault();
								jQuery(".ai_input'.$htmlname.'").toggle();
								jQuery(".template-selector").hide();
								jQuery(".email-layout-container").hide();
								if (!jQuery("#ai_input'.$htmlname.'").is(":hidden")) {
									console.log("Set focus on input field #ai_instructions'.$htmlname.'");
									jQuery("#ai_instructions'.$htmlname.'").focus();
									if (!jQuery("pageContent").is(":hidden")) {		// May exists for website page only
										jQuery("#pageContent").show();
									}
								}
							});
						});
					</script>
					';
}
if ($showlinktolayout) {
	if (!empty($formwebsite) && is_object($formwebsite)) {
		$out .= $formwebsite->getContentPageTemplate($htmlname);
	} else {
		$out .= $formmail->getModelEmailTemplate($htmlname, $showlinktolayout);
	}
} else {
	$out .= '<!-- No link to the layout feature, $formmail->withlayout must be set to a string use case, module WYSIWYG must be enabled and MAIN_EMAIL_USE_LAYOUT must be set -->';
}
if ($showlinktoai) {
	$out .= $formai->getSectionForAIPrompt($showlinktoai, $formmail->withaiprompt, $htmlname);
} else {
	$out .= '<!-- No link to the AI feature, $formmail->withaiprompt must be set to the ai feature and module ai must be enabled -->';
}

?>
<!-- END PHP TEMPLATE commonfields_edit.tpl.php -->
