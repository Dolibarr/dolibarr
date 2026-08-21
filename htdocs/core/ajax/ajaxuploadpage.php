<?php
/* Copyright (C) 2005-2017  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2024-2026  Frédéric France			<frederic.france@free.fr>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
 *
 * This file is a modified version of datepicker.php from phpBSM to fix some
 * bugs, to add new features and to dramatically increase speed.
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
 *       \file       htdocs/core/ajax/ajaxuploadpage.php
 *       \brief      Page to show a generic upload file feature
 */

if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', 1); // Disables token renewal
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1');
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}
if (!defined('NOHEADERNOFOOTER')) {
	define('NOHEADERNOFOOTER', '1');
}

require_once '../../main.inc.php';
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/ai/class/ai.class.php';


if (GETPOST('lang', 'aZ09')) {
	$langs->setDefaultLang(GETPOST('lang', 'aZ09')); // If language was forced on URL by the main.inc.php
}

$langs->loadLangs(array("main", "other", "exports"));

$action = GETPOST('action', 'aZ09');
$modulepart = GETPOST('modulepart', 'aZ09');

$upload_dir = $conf->user->dir_temp.'/import';

// Delete the temporary files that are used when uploading files
//dol_delete_file($upload_dir.'/upload_page-by'.$user->id.'-*');

$file = GETPOST('file');

$reg = array();
if (preg_match('/^upload_page-([a-z_]+)-uid(\d+)-/', $file, $reg)) {
	$modulepart = $reg[1];

	if ($reg[2] != $user->id) {
		accessforbidden('User id found in filename to process does not match current user id');
	}
} else {
	accessforbidden('Bad value for file parameter');
}

$error = 0;
$errors = array();

$ai = new Ai($db);


/*
 * Actions
 */

//



/*
 * View
 */

top_httphead('application/json');

$originalfilename = $file;
$uid = $thiid = $pid = $erid = $salid = 0;
if (preg_match('/-uid([\d+])/', $file, $reg)) {
	$uid = $reg[1];
	$originalfilename = preg_replace('/-uid\d+/', '', $originalfilename);
}
if (preg_match('/-thiid([\d+])/', $file, $reg)) {
	$thiid = $reg[1];
	$originalfilename = preg_replace('/-thiid\d+/', '', $originalfilename);
}
if (preg_match('/-pid([\d+])/', $file, $reg)) {
	$pid = $reg[1];
	$originalfilename = preg_replace('/-pid\d+/', '', $originalfilename);
}
if (preg_match('/-erid([\d+])/', $file, $reg)) {
	$erid = $reg[1];
	$originalfilename = preg_replace('/-erid\d+/', '', $originalfilename);
}
if (preg_match('/-salid([\d+])/', $file, $reg)) {
	$salid = $reg[1];
	$originalfilename = preg_replace('/-salid\d+/', '', $originalfilename);
}
$originalfilename = preg_replace('/^upload_page-[a-z_]+-/', '', $originalfilename);




//$METHOD = 'thread';			// For ChatGPT.
$METHOD = 'converttotext';		// For Mistral and most others


$docformat = $doctypelabel = $prompt = '';
$fullpathoffile = $upload_dir.'/'.$file;
$answer = null;
$fileContent = '';

// Set the prompting
if ($modulepart == 'invoice_supplier') {
	$docformat = 'pdf';
	$doctypelabel = 'invoice';

	// Call IA to get information on PDF
	$prompt = 'Analyze the contents of this '.$docformat.' document of an '.$doctypelabel.' and convert all readable text and numerical information into a structured JSON format. Follow these guidelines:

	1. **Hierarchical Structure:** Group related information into logical categories like "document_info", "vendor or issuer", "recipient", "items", "payment_info" and "other" depending on the content.
	2. **Flexible Field Identification:** Identify and organize common fields that are found such as:
	 - "invoice number", "invoice reference"
	 - "date", "issue date", "due date", "transaction or invoice date"
	 - "vendor name", "vendor vat number", "vendor professional id (siret, siren, ...), "vendor address", "vendor phone", "vendor email",
	 - "items", "products", "services", "product or service label", "product or service ref", "product or service ref"
	 - "totals", "summary", "amounts", "balance"
	 - "notes," "comments," "messages," "terms"
	3. **Handle Tables and Lists:** If the document contains tables, represent each row as an object in a list with fields like "no", "ref", "description", "quantity", "vat rate", "unit price", "total excluding tax", "total including tax".
	4. **Normalize Dates:** If dates are present, format them in ISO format (YYYY-MM-DD) whenever possible.
	5. **Ignore Background Noise:** Exclude background noise, decorative elements, and irrelevant symbols that do not contribute to the data content.
	6. **Preserve Context:** If the image contains sections, headings, or grouping indicators, use them to create logical hierarchies in the JSON structure.
	7. **General Usability:** Format the text to be suitable for further processing, analysis, or database import.

	**Example JSON Structure:**

	{
	 "document_info": {
	  "document_ref": "<document ref or number>",
	  "date": "<date>",
	  "title": "<title>"
	 },
	 "vendor": {
	  "name": "<name>",
	  "address": "<address>",
	  "phone": "<phone number>",
	  "email": "<email>",
	  "vatnumber": "<vat number>",
	  "profid": "<professional id>"
	 },
	 "recipient": {
	  "name": "<name>",
	  "address": "<address>",
	  "phone": "<phone number>",
	  "email": "<email>",
	  "vatnumber": "<vat number>",
	  "profid": "<professional id>"
	 },
	 "items": [
	  {
	   "no": "<number>",
	   "ref": "<ref>",
	   "label": "<label>",
	   "description": "<description>",
	   "quantity": "<quantity>",
	   "vatrate": "<vat rate>",
	   "totalinctax": "<total including tax>"
	   "totalexcltax": "<total excluding tax>"
	  }
	 ],
	 "summary": {
	  "subtotal": "<subtotal>",
	  "tax": "<amount of tax>",
	  "total": "<total to pay>"
	 },
	 "payment_methods": [
	  {
       "method": "<check or cash or card or direct_debit or credit_transfer or other>",
       "details": "Detail of the payment mode"
      }
	 ],
	 "payments_done": [
	  {
       "method": "<check or cash or card or direct_debit or credit_transfer or other>",
       "amount": "<detail of the payment>",
	   "note":"<other information on payment done>"
      }
	 ],
	 "notes": "<optional text>"
	}
	';
}


// TODO Move this into an AJAX service and just output the JS code to call the aajax to start

if ($METHOD == 'converttotext') { // @phpstan-ignore-line
	$result = dolDocToText($fullpathoffile, '', 'fulltext');
	if (empty($result['error'])) {
		$fileContent = $result['content'];
	}

	if ($fileContent) {
		$prompt = 'This is the content of the document:'."\n\n".substr($fileContent, 0, 12000)."\n\nQuestion: ".$prompt;

		$result = $ai->generateContent($prompt, 'auto', 'docparsing', '');
		// $result is an array of error messages or a string with answer

		if (is_array($result)) {	// If array, there is an error
			if ($result['error']) {
				$error++;
				$errors[] = $result['error'];
			}
			if ($result['curl_error_no']) {
				$error++;
				$errors[] = $result['curl_error_no'];
			}
		} else {
			$answer = $result;
		}
	} else {
		$errors[] = 'Failed to convert document into TXT';
	}
}


if ($METHOD == 'thread') { // @phpstan-ignore-line
	$prompt = '';

	$fileId = 0;
	$assistantId = 0;
	$threadId = 0;
	$runId = 0;


	// First part is to send the file
	$payload = array(
		"purpose" => "assistants",
		"file" => new CURLFile($fullpathoffile)
	);

	$result = $ai->generateContent($payload, 'auto', 'file', '');

	if (is_array($result)) {	// If array, there is an error
		if ($result['error']) {
			$error++;
			$errors[] = $result['error'];
		}
		if ($result['curl_error_no']) {
			$error++;
			$errors[] = $result['curl_error_no'];
		}
	} else {
		$fileId = json_decode($result, true)['id'];
	}


	// Create assistant
	if (!$error) {
		$payload = [
			"name" => "PDF Analyzer",
			"instructions" => "Analyze PDF and answer precisely",
			"tools" => [
				["type" => "file_search"]
			]
		];

		$result = $ai->generateContent($payload, 'auto', 'assistant', '', array('OpenAI-Beta', 'assistants=v2'));

		if (is_array($result)) {	// If array, there is an error
			if ($result['error']) {
				$error++;
				$errors[] = $result['error'];
			}
			if ($result['curl_error_no']) {
				$error++;
				$errors[] = $result['curl_error_no'];
			}
		} else {
			$assistantId = json_decode($result, true)['id'];
		}
	}


	// Create thread
	if (!$error) {
		$payload = '{}';

		$result = $ai->generateContent($payload, 'auto', 'thread', '', array('OpenAI-Beta', 'assistants=v2'));

		if (is_array($result)) {	// If array, there is an error
			if ($result['error']) {
				$error++;
				$errors[] = $result['error'];
			}
			if ($result['curl_error_no']) {
				$error++;
				$errors[] = $result['curl_error_no'];
			}
		} else {
			$threadId = json_decode($result, true)['id'];
		}
	}


	// Add file to thread
	if (!$error) {
		$payload = array(
			"role" => "user",
			"content" => [
			[
				"type" => "input_text",
				"text" => $prompt
			]
			],
			"attachments" => [
			[
				"file_id" => $fileId,
				"tools" => [["type" => "file_search"]]
			]
			]
		);
		$moreendpoint = $threadId.'/messages';

		$result = $ai->generateContent($payload, 'auto', 'thread', '', array('OpenAI-Beta', 'assistants=v2'), $moreendpoint);

		if (is_array($result)) {	// If array, there is an error
			if ($result['error']) {
				$error++;
				$errors[] = $result['error'];
			}
			if ($result['curl_error_no']) {
				$error++;
				$errors[] = $result['curl_error_no'];
			}
		}
	}


	// Run the thread
	if (!$error) {
		$payload = array(
			["assistant_id" => $assistantId]
		);
		$moreendpoint = $threadId.'/runs';

		$result = $ai->generateContent($payload, 'auto', 'thread', '', array('OpenAI-Beta', 'assistants=v2'), $moreendpoint);

		if (is_array($result)) {	// If array, there is an error
			if ($result['error']) {
				$error++;
				$errors[] = $result['error'];
			}
			if ($result['curl_error_no']) {
				$error++;
				$errors[] = $result['curl_error_no'];
			}
		} else {
			$runId = json_decode($result, true)['id'];
		}
	}


	// Poll until end of thread
	if (!$error) {
		do {
			sleep(1);

			$payload = '';
			$moreendpoint = $threadId.'/runs/'.$runId;

			$result = $ai->generateContent($payload, 'auto', 'thread', '', array('OpenAI-Beta', 'assistants=v2'), $moreendpoint);

			if (is_array($result)) {	// If array, there is an error
				if ($result['error']) {
					$error++;
					$errors[] = $result['error'];
				}
				if ($result['curl_error_no']) {
					$error++;
					$errors[] = $result['curl_error_no'];
				}

				$status = 'completed';
			} else {
				$status = json_decode($result, true)['status'];
			}
		} while ($status !== "completed");
	}


	// Get answer
	if (!$error) {
		$payload = '';
		$moreendpoint = $threadId.'/messages';

		$result = $ai->generateContent($prompt, 'auto', 'thread', '', array('OpenAI-Beta', 'assistants=v2'));

		if (is_array($result)) {	// If array, there is an error
			if ($result['error']) {
				$error++;
				$errors[] = $result['error'];
			}
			if ($result['curl_error_no']) {
				$error++;
				$errors[] = $result['curl_error_no'];
			}
		} else {
			$answer = $result;
		}
	}
}


// End of page

$db->close();


if (!empty($errors)) {
	http_response_code(500);

	print json_encode(array('errors' => $errors));
} else {
	$data = json_decode((string) $answer, true);

	if ($data == null) {
		$error++;
		$errors[] = 'Failed to decode answer';
		print 'Failed to decode answer';
	} else {
		print $answer;
	}
}
