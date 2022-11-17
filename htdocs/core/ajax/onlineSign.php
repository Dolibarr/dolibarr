<?php
/*
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
 *	\file       /htdocs/core/ajax/onlineSign.php
 *	\brief      File to make Ajax action on Knowledge Management
 */

if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1'); // Disables token renewal
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1');
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}
// Needed for create other object with workflow
/*if (!defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
}*/
if (!defined('NOCSRFCHECK')) {
	define('NOCSRFCHECK', '1');
}
// Do not check anti CSRF attack test
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
}
// If there is no need to load and show top and left menu
if (!defined("NOLOGIN")) {
	define("NOLOGIN", '1');
}
if (!defined('NOIPCHECK')) {
	define('NOIPCHECK', '1'); // Do not check IP defined into conf $dolibarr_main_restrict_ip
}
if (!defined('NOBROWSERNOTIF')) {
	define('NOBROWSERNOTIF', '1');
}
$entity = (!empty($_GET['entity']) ? (int) $_GET['entity'] : (!empty($_POST['entity']) ? (int) $_POST['entity'] : 1));
if (is_numeric($entity)) {
	define("DOLENTITY", $entity);
}
include '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

$action = GETPOST('action', 'aZ09');

$signature = GETPOST('signaturebase64');
$ref = GETPOST('ref', 'aZ09');
$mode = GETPOST('mode', 'aZ09');
$SECUREKEY = GETPOST("securekey"); // Secure key

$error = 0;
$response = "";

$type = $mode;

// Check securitykey
$securekeyseed = '';
if ($type == 'proposal') {
	$securekeyseed = getDolGlobalString('PROPOSAL_ONLINE_SIGNATURE_SECURITY_TOKEN');
}

if (empty($SECUREKEY) || !dol_verifyHash($securekeyseed.$type.$ref.(empty($conf->multicompany->enabled) ? '' : $entity), $SECUREKEY, '0')) {
	http_response_code(403);
	print 'Bad value for securitykey. Value provided '.dol_escape_htmltag($SECUREKEY).' does not match expected value for ref='.dol_escape_htmltag($ref);
	exit(-1);
}


/*
 * Actions
 */

// None


/*
 * View
 */

if ($action == "importSignature") {
	if (!empty($signature) && $signature[0] == "image/png;base64") {
		$signature = $signature[1];
		$data = base64_decode($signature);

		if ($mode == "propale" || $mode == 'proposal') {
			require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
			require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
			$object = new Propal($db);
			$object->fetch(0, $ref);

			$upload_dir = !empty($conf->propal->multidir_output[$object->entity])?$conf->propal->multidir_output[$object->entity]:$conf->propal->dir_output;
			$upload_dir .= '/'.dol_sanitizeFileName($object->ref).'/';

			$date = dol_print_date(dol_now(), "%Y%m%d%H%M%S");
			$filename = "signatures/".$date."_signature.png";
			if (!is_dir($upload_dir."signatures/")) {
				if (!dol_mkdir($upload_dir."signatures/")) {
					$response ="Error mkdir. Failed to create dir ".$upload_dir."signatures/";
					$error++;
				}
			}

			if (!$error) {
				$return = file_put_contents($upload_dir.$filename, $data);
				if ($return == false) {
					$error++;
					$response = 'Error file_put_content: failed to create signature file.';
				}
			}

			if (!$error) {
				// Defined modele of doc
				$last_main_doc_file = $object->last_main_doc;
				$directdownloadlink = $object->getLastMainDocLink('proposal');	// url to download the $object->last_main_doc

				if (preg_match('/\.pdf/i', $last_main_doc_file)) {
					// TODO Use the $last_main_doc_file to defined the $newpdffilename and $sourcefile
					$newpdffilename = $upload_dir.$ref."_signed-".$date.".pdf";
					$sourcefile = $upload_dir.$ref.".pdf";

					if (dol_is_file($sourcefile)) {
						// We build the new PDF
						$pdf = pdf_getInstance();
						if (class_exists('TCPDF')) {
							$pdf->setPrintHeader(false);
							$pdf->setPrintFooter(false);
						}
						$pdf->SetFont(pdf_getPDFFont($langs));

						if (getDolGlobalString('MAIN_DISABLE_PDF_COMPRESSION')) {
							$pdf->SetCompression(false);
						}


						//$pdf->Open();
						$pagecount = $pdf->setSourceFile($sourcefile);		// original PDF

						$s = array(); 	// Array with size of each page. Exemple array(w'=>210, 'h'=>297);
						for ($i=1; $i<($pagecount+1); $i++) {
							try {
								$tppl = $pdf->importPage($i);
								$s = $pdf->getTemplatesize($tppl);
								$pdf->AddPage($s['h'] > $s['w'] ? 'P' : 'L');
								$pdf->useTemplate($tppl);
							} catch (Exception $e) {
								dol_syslog("Error when manipulating some PDF by onlineSign: ".$e->getMessage(), LOG_ERR);
								$response = $e->getMessage();
								$error++;
							}
						}

						// A signature image file is 720 x 180 (ratio 1/4) but we use only the size into PDF
						// TODO Get position of box from PDF template
						$xforimgstart = (empty($s['w']) ? 120 : round($s['w'] / 2) + 15);
						$yforimgstart = (empty($s['h']) ? 240 : $s['h'] - 60);
						$wforimg = $s['w'] - 20 - $xforimgstart;

						$pdf->Image($upload_dir.$filename, $xforimgstart, $yforimgstart, $wforimg, round($wforimg / 4));
						//$pdf->Close();
						$pdf->Output($newpdffilename, "F");

						// Index the new file and update the last_main_doc property of object.
						$object->indexFile($newpdffilename, 1);
					}
				} elseif (preg_match('/\.odt/i', $last_main_doc_file)) {
					// Adding signature on .ODT not yet supported
					// TODO
				} else {
					// Document format not supported to insert online signature.
					// We should just create an image file with the signature.
				}
			}

			if (!$error) {
				$db->begin();

				$online_sign_ip = getUserRemoteIP();
				$online_sign_name = '';		// TODO Ask name on form to sign

				$sql  = "UPDATE ".MAIN_DB_PREFIX."propal";
				$sql .= " SET fk_statut = ".((int) $object::STATUS_SIGNED).", note_private = '".$db->escape($object->note_private)."',";
				$sql .= " date_signature = '".$db->idate(dol_now())."',";
				$sql .= " online_sign_ip = '".$db->escape($online_sign_ip)."'";
				if ($online_sign_name) {
					$sql .= ", online_sign_name = '".$db->escape($online_sign_name)."'";
				}
				$sql .= " WHERE rowid = ".((int) $object->id);

				dol_syslog(__METHOD__, LOG_DEBUG);
				$resql = $db->query($sql);
				if (!$resql) {
					$error++;
				} else {
					$num = $db->affected_rows($resql);
				}

				if (!$error) {
					$db->commit();
					$response = "success";
					setEventMessages("PropalSigned", null, 'warnings');
					if (method_exists($object, 'call_trigger')) {
						//customer is not a user !?! so could we use same user as validation ?
						$user = new User($db);
						$user->fetch($object->user_valid_id);
						$result = $object->call_trigger('PROPAL_CLOSE_SIGNED', $user);
						if ($result < 0) {
							$error++;
						}
					}
				} else {
					$db->rollback();
					$error++;
					$response = "error sql";
				}
			}
		}
	} else {
		$error++;
		$response = 'error signature_not_found';
	}
}

if ($error) {
	http_response_code(501);
}

echo $response;
