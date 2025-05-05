<?php
/* Copyright (C) 2022 Alice Adminson <aadminson@example.com>
 * Copyright (C) 2024-2025  Frédéric France             <frederic.france@free.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    htdocs/ai/lib/ai.lib.php
 * \ingroup ai
 * \brief   Library files with common functions for Ai
 */

include_once DOL_DOCUMENT_ROOT.'/ai/class/ai.class.php';


/**
 * Prepare admin pages header
 *
 * @return array<string,array<string,string>>
 */
function getListOfAIFeatures()
{
	global $langs;

	$arrayofaifeatures = array(
		'textgenerationemail' => array('label' => $langs->trans('TextGeneration').' ('.$langs->trans("EmailContent").')', 'picto' => '', 'status' => 'dolibarr', 'function' => 'TEXT', 'placeholder' => Ai::AI_DEFAULT_PROMPT_FOR_EMAIL),
		'textgenerationwebpage' => array('label' => $langs->trans('TextGeneration').' ('.$langs->trans("WebsitePage").')', 'picto' => '', 'status' => 'dolibarr', 'function' => 'TEXT', 'placeholder' => Ai::AI_DEFAULT_PROMPT_FOR_WEBPAGE),
		'textgeneration' => array('label' => $langs->trans('TextGeneration').' ('.$langs->trans("Other").')', 'picto' => '', 'status' => 'notused', 'function' => 'TEXT'),

		'texttranslation' => array('label' => $langs->trans('TextTranslation'), 'picto' => '', 'status'=>'dolibarr', 'function' => 'TEXT', 'placeholder' => Ai::AI_DEFAULT_PROMPT_FOR_TEXT_TRANSLATION),
		'textsummarize' => array('label' => $langs->trans('TextSummarize'), 'picto' => '', 'status'=>'dolibarr', 'function' => 'TEXT', 'placeholder' => Ai::AI_DEFAULT_PROMPT_FOR_TEXT_SUMMARIZE),
		'textrephrase' => array('label' => $langs->trans('TextRephraser'), 'picto' => '', 'status'=>'dolibarr', 'function' => 'TEXT', 'placeholder' => Ai::AI_DEFAULT_PROMPT_FOR_TEXT_REPHRASER),

		'textgenerationextrafield' => array('label' => $langs->trans('TextGeneration').' ('.$langs->trans("ExtrafieldFiller").')', 'picto' => '', 'status'=>'dolibarr', 'function' => 'TEXT', 'placeholder' => Ai::AI_DEFAULT_PROMPT_FOR_EXTRAFIELD_FILLER),

		'imagegeneration' => array('label' => 'ImageGeneration', 'picto' => '', 'status' => 'notused', 'function' => 'IMAGE'),
		'videogeneration' => array('label' => 'VideoGeneration', 'picto' => '', 'status' => 'notused', 'function' => 'VIDEO'),
		'audiogeneration' => array('label' => 'AudioGeneration', 'picto' => '', 'status' => 'notused', 'function' => 'AUDIO'),
		'transcription' => array('label' => 'AudioTranscription', 'picto' => '', 'status' => 'notused', 'function' => 'TRANSCRIPT'),
		'translation' => array('label' => 'AudioTranslation', 'picto' => '', 'status' => 'notused', 'function' => 'TRANSLATE')
	);

	return $arrayofaifeatures;
}

/**
 * Get list of available ai services
 *
 * @return array<int|string,mixed>
 */
function getListOfAIServices()
{
	global $langs;

	$arrayofai = array(
		'-1' => array('label' => $langs->trans('SelectAService')),
		'chatgpt' => array(
			'label' => 'ChatGPT',
			'url' => 'https://api.openai.com/v1/',
			'textgeneration' => 'gpt-3.5-turbo',		// a lot of text transformation like: 'textgenerationemail', 'textgenerationwebpage', 'textgeneration', 'texttranslation', 'textsummarize'
			'imagegeneration' => 'dall-e-3',
			'audiogeneration' => 'tts-1',
			'videogeneration' => 'na',
			'transcription' => 'whisper-1',				// audio to text
			'translation' => 'whisper-1',				// audio to text into another language
		),
		'groq' => array(
			'label' => 'Groq',
			'url' => 'https://api.groq.com/openai/',
			'textgeneration' => 'mixtral-8x7b-32768',	// 'llama3-8b-8192', 'gemma-7b-it'
			'imagegeneration' => 'na',
			'audiogeneration' => 'na',
			'videogeneration' => 'na',
			'transcription' => 'na',
			'translation' => 'na',
		),
		'mistral' => array(
			'label' => 'Mistral',
			'url' => 'https://api.mistral.ai/v1/',
			'textgeneration' => 'open-mistral-7b',
			'imagegeneration' => 'na',
			'audiogeneration' => 'na',
			'videogeneration' => 'na',
			'transcription' => 'na',
			'translation' => 'na',
		),
		'custom' => array(
			'label' => 'Custom',
			'url' => 'https://domainofapi.com/v1/',
			'textgeneration' => 'tinyllama-1.1b',
			'imagegeneration' => 'mixtral-8x7b-32768',
			'audiogeneration' => 'mixtral-8x7b-32768',
			'videogeneration' => 'na',
			'transcription' => 'mixtral-8x7b-32768',
			'translation' => 'mixtral-8x7b-32768',
		)
		//'gemini' => array(
		//	'label' => 'Gemini',
		//)
	);

	return $arrayofai;
}




/**
 * Get list for AI summarize
 *
 * @return array<int|string,mixed>
 */
function getListForAISummarize()
{
	$arrayforaisummarize = array(
		//'20_w' => 'SummarizeTwentyWords',
		'50_w' => 'SummarizeFiftyWords',
		'100_w' => 'SummarizeHundredWords',
		'200_w' => 'SummarizeTwoHundredWords',
		'1_p' => 'SummarizeOneParagraphs',
		'2_p' => 'SummarizeTwoParagraphs',
		'25_pc' => 'SummarizeTwentyFivePercent',
		'50_pc' => 'SummarizeFiftyPercent',
		'75_pc' => 'SummarizeSeventyFivePercent'
	);

	return $arrayforaisummarize;
}

/**
 * Get list for AI style of writing
 *
 * @return array<int|string,mixed>
 */
function getListForAIRephraseStyle()
{
	$arrayforaierephrasestyle = array(
		'professional' => 'RephraseStyleProfessional',
		'humouristic' => 'RephraseStyleHumouristic'
	);

	return $arrayforaierephrasestyle;
}

/**
 * Prepare admin pages header
 *
 * @return array<array{0:string,1:string,2:string}>
 */
function aiAdminPrepareHead()
{
	global $langs, $conf;

	$langs->load("agenda");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/ai/admin/setup.php", 1);
	$head[$h][1] = $langs->trans("Settings");
	$head[$h][2] = 'settings';
	$h++;

	$head[$h][0] = dol_buildpath("/ai/admin/custom_prompt.php", 1);
	$head[$h][1] = $langs->trans("CustomPrompt");
	$head[$h][2] = 'custom';
	$h++;

	/*
	$head[$h][0] = dol_buildpath("/ai/admin/myobject_extrafields.php", 1);
	$head[$h][1] = $langs->trans("ExtraFields");
	$head[$h][2] = 'myobject_extrafields';
	$h++;
	*/

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@ai:/ai/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@ai:/ai/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'ai@ai');

	complete_head_from_modules($conf, $langs, null, $head, $h, 'ai@ai', 'remove');

	return $head;
}
