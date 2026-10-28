<?php
/* Copyright (C) 2005-2017  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2024-2026  Frédéric France			<frederic.france@free.fr>
 * Copyright (C) 2024-2026	MDW						<mdeweerd@users.noreply.github.com>
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
 *       \brief      Page that analyze a file saved into a temporary directory and suggest final import
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
	8. **Key information:** The invoice number, the invoice date, the due date, the title and the currency must always be inside "document_info" with exactly the key names of the example below. If a value is not found in the document, use null. Never guess or calculate a date that is not written in the document.

	**Example JSON Structure:**

	{
	 "document_info": {
	  "invoice_number": "<invoice ref or number>",
	  "issue_date": "<YYYY-MM-DD>",
	  "due_date": "<YYYY-MM-DD>",
	  "title": "<title>",
	  "currency_code": "<ISO 4217 currency code>"
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
} elseif ($modulepart == 'salaries') {
	$docformat = 'pdf';
	$doctypelabel = 'payslip';

	// Call IA to get information on the payslip. We ask only the data needed to record the salary (no social security number or bank account).
	$prompt = 'Analyze the contents of this '.$docformat.' document of a '.$doctypelabel.' (salary slip) and convert the information into a structured JSON format. Follow these guidelines:

	1. **Structure:** Use exactly the keys of the example JSON structure below. Use null when a value is not found.
	2. **Normalize Dates:** Format dates in ISO format (YYYY-MM-DD). The period is the first and last day of the paid period.
	3. **Normalize Amounts:** Amounts are numbers with a dot as decimal separator, without currency symbol and without thousand separator.
	4. **Net to pay:** "net_to_pay" is the final amount paid to the employee (after all deductions and income tax withholding).
	5. **Privacy:** Do not return the social security number, the bank account or the address of the employee.

	**Example JSON Structure:**

	{
	 "document_info": {
	  "title": "<title of document>",
	  "period_start": "<YYYY-MM-DD>",
	  "period_end": "<YYYY-MM-DD>",
	  "payment_date": "<YYYY-MM-DD>",
	  "currency_code": "<ISO 4217 currency code>"
	 },
	 "employer": {
	  "name": "<name>"
	 },
	 "employee": {
	  "name": "<firstname and lastname>",
	  "job_title": "<job title>"
	 },
	 "summary": {
	  "gross_salary": "<gross salary>",
	  "total_employee_contributions": "<total of employee contributions>",
	  "net_before_income_tax": "<net amount before income tax>",
	  "income_tax": "<income tax withholding>",
	  "net_to_pay": "<net amount paid to the employee>"
	 }
	}
	';
} elseif ($modulepart == 'chargesociales') {
	$docformat = 'pdf';
	$doctypelabel = 'social or fiscal tax notice';

	// Call IA to get information on the social or fiscal tax (social contributions, VAT, property tax, ...)
	$prompt = 'Analyze the contents of this '.$docformat.' document of a '.$doctypelabel.' (a request for payment of social contributions or of a tax) and convert the information into a structured JSON format. Follow these guidelines:

	1. **Structure:** Use exactly the keys of the example JSON structure below. Use null when a value is not found.
	2. **Normalize Dates:** Format dates in ISO format (YYYY-MM-DD). The period is the first and last day of the period the tax is related to. The due date is the last date to pay.
	3. **Normalize Amounts:** Amounts are numbers with a dot as decimal separator, without currency symbol and without thousand separator.
	4. **Amount to pay:** "amount_to_pay" is the total amount that must be paid with this document.
	5. **Privacy:** Do not return bank account numbers or social security numbers.

	**Example JSON Structure:**

	{
	 "document_info": {
	  "title": "<short label of the tax or contribution, for example URSSAF contributions or VAT>",
	  "organization": "<name of the organization asking the payment>",
	  "reference": "<reference of the document or contract>",
	  "period_start": "<YYYY-MM-DD>",
	  "period_end": "<YYYY-MM-DD>",
	  "due_date": "<YYYY-MM-DD>",
	  "currency_code": "<ISO 4217 currency code>"
	 },
	 "employee": {
	  "name": "<firstname and lastname of the employee if the document is related to only one employee, else null>"
	 },
	 "summary": {
	  "amount_to_pay": "<total amount to pay>"
	 }
	}
	';
} elseif ($modulepart == 'expensereport') {
	$docformat = 'pdf';
	$doctypelabel = 'receipt';

	// Call IA to get information on the receipt of an expense (restaurant, hotel, transport, fuel, ...)
	$prompt = 'Analyze the contents of this '.$docformat.' document of a '.$doctypelabel.' (a receipt or an invoice of an expense paid by an employee) and convert the information into a structured JSON format. Follow these guidelines:

	1. **Structure:** Use exactly the keys of the example JSON structure below. Use null when a value is not found.
	2. **Normalize Dates:** Format dates in ISO format (YYYY-MM-DD). The date is the date of the expense.
	3. **Normalize Amounts:** Amounts are numbers with a dot as decimal separator, without currency symbol and without thousand separator.
	4. **VAT:** If there are several VAT rates, set "vat_rate" to null and give the total amounts.
	5. **Privacy:** Do not return card numbers or bank account numbers.

	**Example JSON Structure:**

	{
	 "document_info": {
	  "merchant": "<name of the shop, restaurant, hotel or company>",
	  "title": "<short description of the expense>",
	  "category": "<one of: meal, hotel, transport, fuel, parking, toll, other>",
	  "date": "<YYYY-MM-DD>",
	  "currency_code": "<ISO 4217 currency code>"
	 },
	 "summary": {
	  "total_excluding_tax": "<total amount excluding tax>",
	  "vat_rate": "<VAT rate in percent>",
	  "vat_amount": "<total amount of VAT>",
	  "total_including_tax": "<total amount paid including tax>"
	 }
	}
	';
}


// TODO Move the convert code into an AJAX service and just output the JS code to call the ajax service to start

if ($METHOD == 'converttotext') { // @phpstan-ignore-line
	// Call the tool able to convert the file into text: pdftotext for a PDF file, docling for an image (docling includes an OCR)
	$methodtotext = '';
	if (preg_match('/\.pdf$/i', $fullpathoffile)) {
		$methodtotext = 'pdftotext';
	} elseif (preg_match('/\.(png|jpe?g|gif|bmp|tiff?|webp)$/i', $fullpathoffile)) {
		$methodtotext = 'docling';
	}

	$result = array('error' => 0, 'message' => '', 'keywords' => '', 'content' => '', 'cmd' => '');
	if (!empty($methodtotext)) {
		$result = dolDocToText($fullpathoffile, $methodtotext, 'fulltext');
	}
	if (empty($result['error'])) {
		// Remove the spaces used to align columns of tables, so more useful content is kept in the limit of chars sent to the AI
		$fileContent = preg_replace('/[ \t]{2,}/', ' ', (string) $result['content']);
	}

	if ($fileContent) {
		$prompt = 'This is the content of the document:'."\n\n".dol_substr($fileContent, 0, 12000)."\n\nQuestion: ".$prompt;

		if (getDolGlobalString("AI_DEBUG")) {
			dol_syslog("ajaxuploadpage.php Call AI service for step 'converttotext' (method=".$METHOD.", file=".$file.")", LOG_DEBUG, 0, '_ai');
		}
		$result = $ai->generateContent($prompt, 'auto', 'docparsing', '');		// Call AIservice from a text extracted from file.
		// $result is an array of error messages or a string with answer
		if (is_array($result)) {	// If array, there is an error
			if (getDolGlobalString("AI_DEBUG")) {
				dol_syslog("ajaxuploadpage.php AI service returned an error for step 'converttotext': ".(empty($result['error']) ? '' : $result['error']).(empty($result['curl_error_no']) ? '' : ' curl_error_no='.$result['curl_error_no']), LOG_WARNING, 0, '_ai');
			}
			$error++;
			$errors[] = getAIErrorMessage($result);
		} else {
			$answer = (string) $result;

			$answer = preg_replace('/^```json/', '', $answer);
			$answer = preg_replace('/```$/', '', $answer);
		}
	} else {
		$langs->load("errors");
		$error++;
		$errors[] = $langs->trans("ErrorFailedToConvertFileInText");
		$errors[] = $result['message'];
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

	if (getDolGlobalString("AI_DEBUG")) {
		dol_syslog("ajaxuploadpage.php Call AI service for step 'send file' (method=".$METHOD.", file=".$file.")", LOG_DEBUG, 0, '_ai');
	}
	$result = $ai->generateContent($payload, 'auto', 'file', '');

	if (is_array($result)) {	// If array, there is an error
		if (getDolGlobalString("AI_DEBUG")) {
			dol_syslog("ajaxuploadpage.php AI service returned an error for step 'send file': ".(empty($result['error']) ? '' : $result['error']).(empty($result['curl_error_no']) ? '' : ' curl_error_no='.$result['curl_error_no']), LOG_WARNING, 0, '_ai');
		}
		$error++;
		$errors[] = getAIErrorMessage($result);
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

		if (getDolGlobalString("AI_DEBUG")) {
			dol_syslog("ajaxuploadpage.php Call AI service for step 'create assistant' (method=".$METHOD.", file=".$file.")", LOG_DEBUG, 0, '_ai');
		}
		$result = $ai->generateContent($payload, 'auto', 'assistant', '', array('OpenAI-Beta', 'assistants=v2'));

		if (is_array($result)) {	// If array, there is an error
			if (getDolGlobalString("AI_DEBUG")) {
				dol_syslog("ajaxuploadpage.php AI service returned an error for step 'create assistant': ".(empty($result['error']) ? '' : $result['error']).(empty($result['curl_error_no']) ? '' : ' curl_error_no='.$result['curl_error_no']), LOG_WARNING, 0, '_ai');
			}
			$error++;
			$errors[] = getAIErrorMessage($result);
		} else {
			$assistantId = json_decode($result, true)['id'];
		}
	}


	// Create thread
	if (!$error) {
		$payload = '{}';

		if (getDolGlobalString("AI_DEBUG")) {
			dol_syslog("ajaxuploadpage.php Call AI service for step 'create thread' (method=".$METHOD.", file=".$file.")", LOG_DEBUG, 0, '_ai');
		}
		$result = $ai->generateContent($payload, 'auto', 'thread', '', array('OpenAI-Beta', 'assistants=v2'));

		if (is_array($result)) {	// If array, there is an error
			if (getDolGlobalString("AI_DEBUG")) {
				dol_syslog("ajaxuploadpage.php AI service returned an error for step 'create thread': ".(empty($result['error']) ? '' : $result['error']).(empty($result['curl_error_no']) ? '' : ' curl_error_no='.$result['curl_error_no']), LOG_WARNING, 0, '_ai');
			}
			$error++;
			$errors[] = getAIErrorMessage($result);
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

		if (getDolGlobalString("AI_DEBUG")) {
			dol_syslog("ajaxuploadpage.php Call AI service for step 'add file to thread' (method=".$METHOD.", file=".$file.")", LOG_DEBUG, 0, '_ai');
		}
		$result = $ai->generateContent($payload, 'auto', 'thread', '', array('OpenAI-Beta', 'assistants=v2'), $moreendpoint);

		if (is_array($result)) {	// If array, there is an error
			if (getDolGlobalString("AI_DEBUG")) {
				dol_syslog("ajaxuploadpage.php AI service returned an error for step 'add file to thread': ".(empty($result['error']) ? '' : $result['error']).(empty($result['curl_error_no']) ? '' : ' curl_error_no='.$result['curl_error_no']), LOG_WARNING, 0, '_ai');
			}
			$error++;
			$errors[] = getAIErrorMessage($result);
		}
	}


	// Run the thread
	if (!$error) {
		$payload = array(
			["assistant_id" => $assistantId]
		);
		$moreendpoint = $threadId.'/runs';

		if (getDolGlobalString("AI_DEBUG")) {
			dol_syslog("ajaxuploadpage.php Call AI service for step 'run thread' (method=".$METHOD.", file=".$file.")", LOG_DEBUG, 0, '_ai');
		}
		$result = $ai->generateContent($payload, 'auto', 'thread', '', array('OpenAI-Beta', 'assistants=v2'), $moreendpoint);

		if (is_array($result)) {	// If array, there is an error
			if (getDolGlobalString("AI_DEBUG")) {
				dol_syslog("ajaxuploadpage.php AI service returned an error for step 'run thread': ".(empty($result['error']) ? '' : $result['error']).(empty($result['curl_error_no']) ? '' : ' curl_error_no='.$result['curl_error_no']), LOG_WARNING, 0, '_ai');
			}
			$error++;
			$errors[] = getAIErrorMessage($result);
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

			if (getDolGlobalString("AI_DEBUG")) {
				dol_syslog("ajaxuploadpage.php Call AI service for step 'poll run status' (method=".$METHOD.", file=".$file.")", LOG_DEBUG, 0, '_ai');
			}
			$result = $ai->generateContent($payload, 'auto', 'thread', '', array('OpenAI-Beta', 'assistants=v2'), $moreendpoint);

			if (is_array($result)) {	// If array, there is an error
				if (getDolGlobalString("AI_DEBUG")) {
					dol_syslog("ajaxuploadpage.php AI service returned an error for step 'poll run status': ".(empty($result['error']) ? '' : $result['error']).(empty($result['curl_error_no']) ? '' : ' curl_error_no='.$result['curl_error_no']), LOG_WARNING, 0, '_ai');
				}
				$error++;
				$errors[] = getAIErrorMessage($result);

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

		if (getDolGlobalString("AI_DEBUG")) {
			dol_syslog("ajaxuploadpage.php Call AI service for step 'get answer' (method=".$METHOD.", file=".$file.")", LOG_DEBUG, 0, '_ai');
		}
		$result = $ai->generateContent($prompt, 'auto', 'thread', '', array('OpenAI-Beta', 'assistants=v2'));

		if (is_array($result)) {	// If array, there is an error
			if (getDolGlobalString("AI_DEBUG")) {
				dol_syslog("ajaxuploadpage.php AI service returned an error for step 'get answer': ".(empty($result['error']) ? '' : $result['error']).(empty($result['curl_error_no']) ? '' : ' curl_error_no='.$result['curl_error_no']), LOG_WARNING, 0, '_ai');
			}
			$error++;
			$errors[] = getAIErrorMessage($result);
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
		http_response_code(500);

		if (getDolGlobalString("AI_DEBUG")) {
			dol_syslog("ajaxuploadpage.php Failed to decode the answer of the AI service as JSON (".json_last_error_msg()."): ".dol_trunc((string) $answer, 500), LOG_WARNING, 0, '_ai');
		}

		$langs->load("errors");
		$error++;
		$errors[] = $langs->transnoentitiesnoconv("ErrorAIAnswerIsNotValidJSON", json_last_error_msg());
		print json_encode(array('errors' => $errors));
	} else {
		// Save the JSON answer into a cache file next to the uploaded file (only if file name has no path part)
		if (dol_sanitizeFileName($file) === $file && dol_is_file($fullpathoffile)) {
			$result = file_put_contents($fullpathoffile.'.json', $answer);
			if ($result === false) {
				dol_syslog("Failed to write the json file ".$fullpathoffile.'.json', LOG_WARNING);
				if (getDolGlobalString("AI_DEBUG")) {
					dol_syslog("Failed to write the json file ".$fullpathoffile.'.json', LOG_WARNING, 0, '_ai');
				}
			} else {
				dolChmod($fullpathoffile.'.json');
			}
		}

		print $answer;
	}
}
