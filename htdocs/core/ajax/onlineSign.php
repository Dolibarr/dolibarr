<?php
/* Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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
 *    \file       /htdocs/core/ajax/onlineSign.php
 *    \brief      File to make Ajax action to add the signature of a document
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
// Needed to create other objects with workflow
/*if (!defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
}*/
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
$entity = (!empty($_GET['entity']) ? (int) $_GET['entity'] : (!empty($_POST['entity']) ? (int) $_POST['entity'] : 1));	// Keep $_GET and $_POST here. GETPOST not yet defined.
if (is_numeric($entity)) {
	define("DOLENTITY", $entity);
}
include '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

$action = GETPOST('action', 'aZ09');

$signature = GETPOST('signaturebase64');
$ref = GETPOST('ref', 'aZ09');
$mode = GETPOST('mode', 'aZ09');    // 'proposal', ...
$SECUREKEY = GETPOST("securekey"); // Secure key
$online_sign_name = GETPOST("onlinesignname");

$error = 0;
$response = "";

$type = $mode;

// Security check
$securekeyseed = '';
if ($type == 'proposal') {
	$securekeyseed = getDolGlobalString('PROPOSAL_ONLINE_SIGNATURE_SECURITY_TOKEN');
} elseif ($type == 'contract') {
	$securekeyseed = getDolGlobalString('CONTRACT_ONLINE_SIGNATURE_SECURITY_TOKEN');
} elseif ($type == 'fichinter') {
	$securekeyseed = getDolGlobalString('FICHINTER_ONLINE_SIGNATURE_SECURITY_TOKEN');
} else {
	$securekeyseed = getDolGlobalString(strtoupper($type).'_ONLINE_SIGNATURE_SECURITY_TOKEN');
}

if (empty($SECUREKEY) || !dol_verifyHash($securekeyseed . $type . $ref . (!isModEnabled('multicompany') ? '' : $entity), $SECUREKEY, '0')) {
	httponly_accessforbidden('Bad value for securitykey. Value provided ' . dol_escape_htmltag($SECUREKEY) . ' does not match expected value for ref=' . dol_escape_htmltag($ref), 403);
}

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('ajaxonlinesign'));


/*
 * Actions
 */

// None


/*
 * View
 */

top_httphead();

if ($action == "importSignature") {
	$issignatureok = (!empty($signature) && $signature[0] == "image/png;base64");
	if ($issignatureok) {
		$signature = $signature[1];
		$data = base64_decode($signature);

		if ($mode == "propale" || $mode == 'proposal') {
			require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';
			require_once DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php';
			$object = new Propal($db);
			$object->fetch(0, $ref);

			$upload_dir = !empty($conf->propal->multidir_output[$object->entity]) ? $conf->propal->multidir_output[$object->entity] : $conf->propal->dir_output;
			$upload_dir .= '/' . dol_sanitizeFileName($object->ref) . '/';

			$default_font_size = pdf_getPDFFontSize($langs);    // Must be after pdf_getInstance
			$default_font = pdf_getPDFFont($langs);    // Must be after pdf_getInstance
			$langs->loadLangs(array("main", "companies"));

			$date = dol_print_date(dol_now(), "%Y%m%d%H%M%S");
			$filename = "signatures/" . $date . "_signature.png";
			if (!is_dir($upload_dir . "signatures/")) {
				if (!dol_mkdir($upload_dir . "signatures/")) {
					$response = "Error mkdir. Failed to create dir " . $upload_dir . "signatures/";
					$error++;
				}
			}

			if (!$error) {
				$return = file_put_contents($upload_dir . $filename, $data);
				if ($return == false) {
					$error++;
					$response = 'Error file_put_content: failed to create signature file.';
				}
			}

			if (!$error) {
				// Defined modele of doc
				$last_main_doc_file = $object->last_main_doc;
				$directdownloadlink = $object->getLastMainDocLink('proposal');    // url to download the $object->last_main_doc

				if (preg_match('/\.pdf/i', $last_main_doc_file)) {
					// TODO Use the $last_main_doc_file to defined the $newpdffilename and $sourcefile
					$newpdffilename = $upload_dir . $ref . "_signed-" . $date . ".pdf";
					$sourcefile = $upload_dir . $ref . ".pdf";

					if (dol_is_file($sourcefile)) {
						$parameters = array('sourcefile' => $sourcefile, 'newpdffilename' => $newpdffilename);
						$reshook = $hookmanager->executeHooks('AddSignature', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
						if ($reshook < 0) {
							setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
						}

						if (empty($reshook)) {
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
							$pagecount = $pdf->setSourceFile($sourcefile);        // original PDF

							$param = array();
							$param['online_sign_name'] = $online_sign_name;
							$param['pathtoimage'] = $upload_dir . $filename;

							$s = array();    // Array with size of each page. Example array(w'=>210, 'h'=>297);
							for ($i = 1; $i < ($pagecount + 1); $i++) {
								try {
									$tppl = $pdf->importPage($i);
									$s = $pdf->getTemplatesize($tppl);
									$pdf->AddPage($s['h'] > $s['w'] ? 'P' : 'L');
									$pdf->useTemplate($tppl);
									$propalsignonspecificpage = getDolGlobalInt("PROPAL_SIGNATURE_ON_SPECIFIC_PAGE");
									if ($propalsignonspecificpage < 0) {
										$propalsignonspecificpage = $pagecount - abs($propalsignonspecificpage);
									}

									if (getDolGlobalString("PROPAL_SIGNATURE_ON_ALL_PAGES") || $propalsignonspecificpage == $i) {
										// A signature image file is 720 x 180 (ratio 1/4) but we use only the size into PDF
										// TODO Get position of box from PDF template

										if (getDolGlobalString("PROPAL_SIGNATURE_XFORIMGSTART")) {
											$param['xforimgstart'] = getDolGlobalString("PROPAL_SIGNATURE_XFORIMGSTART");
										} else {
											$param['xforimgstart'] = (empty($s['w']) ? 120 : round($s['w'] / 2) + 15);
										}
										if (getDolGlobalString("PROPAL_SIGNATURE_YFORIMGSTART")) {
											$param['yforimgstart'] = getDolGlobalString("PROPAL_SIGNATURE_YFORIMGSTART");
										} else {
											$param['yforimgstart'] = (empty($s['h']) ? 240 : $s['h'] - 60);
										}
										if (getDolGlobalString("PROPAL_SIGNATURE_WFORIMG")) {
											$param['wforimg'] = getDolGlobalString("PROPAL_SIGNATURE_WFORIMG");
										} else {
											$param['wforimg'] = $s['w'] - 20 - $param['xforimgstart'];
										}

										dolPrintSignatureImage($pdf, $langs, $param);
									}
								} catch (Exception $e) {
									dol_syslog("Error when manipulating the PDF " . $sourcefile . " by onlineSign: " . $e->getMessage(), LOG_ERR);
									$response = $e->getMessage();
									$error++;
								}
							}

							if (!getDolGlobalString("PROPAL_SIGNATURE_ON_ALL_PAGES") && !getDolGlobalInt("PROPAL_SIGNATURE_ON_SPECIFIC_PAGE")) {
								// A signature image file is 720 x 180 (ratio 1/4) but we use only the size into PDF
								// TODO Get position of box from PDF template

								if (getDolGlobalString("PROPAL_SIGNATURE_XFORIMGSTART")) {
											$param['xforimgstart'] = getDolGlobalString("PROPAL_SIGNATURE_XFORIMGSTART");
								} else {
									$param['xforimgstart'] = (empty($s['w']) ? 120 : round($s['w'] / 2) + 15);
								}
								if (getDolGlobalString("PROPAL_SIGNATURE_YFORIMGSTART")) {
									$param['yforimgstart'] = getDolGlobalString("PROPAL_SIGNATURE_YFORIMGSTART");
								} else {
									$param['yforimgstart'] = (empty($s['h']) ? 240 : $s['h'] - 60);
								}
								if (getDolGlobalString("PROPAL_SIGNATURE_WFORIMG")) {
									$param['wforimg'] = getDolGlobalString("PROPAL_SIGNATURE_WFORIMG");
								} else {
									$param['wforimg'] = $s['w'] - 20 - $param['xforimgstart'];
								}

								dolPrintSignatureImage($pdf, $langs, $param);
							}

							//$pdf->Close();
							$pdf->Output($newpdffilename, "F");

							// Index the new file and update the last_main_doc property of object.
							$object->indexFile($newpdffilename, 1);
						}
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

				$sql = "UPDATE " . MAIN_DB_PREFIX . "propal";
				$sql .= " SET fk_statut = " . ((int) $object::STATUS_SIGNED) . ", note_private = '" . $db->escape($object->note_private) . "',";
				$sql .= " date_signature = '" . $db->idate(dol_now()) . "',";
				$sql .= " online_sign_ip = '" . $db->escape($online_sign_ip) . "'";
				if ($online_sign_name) {
					$sql .= ", online_sign_name = '" . $db->escape($online_sign_name) . "'";
				}
				$sql .= " WHERE rowid = " . ((int) $object->id);

				dol_syslog(__FILE__, LOG_DEBUG);
				$resql = $db->query($sql);
				if (!$resql) {
					$error++;
				} else {
					$num = $db->affected_rows($resql);
				}

				if (!$error) {
					if (method_exists($object, 'call_trigger')) {
						//customer is not a user !?! so could we use same user as validation ?
						$user = new User($db);
						$user->fetch($object->user_validation_id);
						$object->context = array('closedfromonlinesignature' => 'closedfromonlinesignature');
						$result = $object->call_trigger('PROPAL_CLOSE_SIGNED', $user);
						if ($result < 0) {
							$error++;
							$response = "error in trigger " . $object->error;
						} else {
							$response = "success";
						}
					} else {
						$response = "success";
					}
				} else {
					$error++;
					$response = "error sql";
				}

				if (!$error) {
					$db->commit();
					$response = "success";
					setEventMessages("PropalSigned", null, 'warnings');
				} else {
					$db->rollback();
				}
			}
		} elseif ($mode == 'contract') {
			require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';
			require_once DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php';
			$object = new Contrat($db);
			$object->fetch(0, $ref);

			$upload_dir = !empty($conf->contrat->multidir_output[$object->entity]) ? $conf->contrat->multidir_output[$object->entity] : $conf->contrat->dir_output;
			$upload_dir .= '/' . dol_sanitizeFileName($object->ref) . '/';

			$date = dol_print_date(dol_now(), "%Y%m%d%H%M%S");
			$filename = "signatures/" . $date . "_signature.png";
			if (!is_dir($upload_dir . "signatures/")) {
				if (!dol_mkdir($upload_dir . "signatures/")) {
					$response = "Error mkdir. Failed to create dir " . $upload_dir . "signatures/";
					$error++;
				}
			}

			if (!$error) {
				$return = file_put_contents($upload_dir . $filename, $data);
				if ($return == false) {
					$error++;
					$response = 'Error file_put_content: failed to create signature file.';
				}
			}

			if (!$error) {
				// Defined modele of doc
				$last_main_doc_file = $object->last_main_doc;
				$directdownloadlink = $object->getLastMainDocLink('contrat');    // url to download the $object->last_main_doc

				if (preg_match('/\.pdf/i', $last_main_doc_file)) {
					// TODO Use the $last_main_doc_file to defined the $newpdffilename and $sourcefile
					$newpdffilename = $upload_dir . $ref . "_signed-" . $date . ".pdf";
					$sourcefile = $upload_dir . $ref . ".pdf";

					if (dol_is_file($sourcefile)) {
						$parameters = array('sourcefile' => $sourcefile, 'newpdffilename' => $newpdffilename);
						$reshook = $hookmanager->executeHooks('AddSignature', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
						if ($reshook < 0) {
							setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
						}

						if (empty($reshook)) {
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
							$pagecount = $pdf->setSourceFile($sourcefile);        // original PDF

							$param = array();
							$param['online_sign_name'] = $online_sign_name;
							$param['pathtoimage'] = $upload_dir . $filename;

							$s = array();    // Array with size of each page. Example array(w'=>210, 'h'=>297);
							for ($i = 1; $i < ($pagecount + 1); $i++) {
								try {
									$tppl = $pdf->importPage($i);
									$s = $pdf->getTemplatesize($tppl);
									$pdf->AddPage($s['h'] > $s['w'] ? 'P' : 'L');
									$pdf->useTemplate($tppl);

									if (getDolGlobalString("CONTRACT_SIGNATURE_ON_ALL_PAGES")) {
										// A signature image file is 720 x 180 (ratio 1/4) but we use only the size into PDF
										// TODO Get position of box from PDF template

										if (getDolGlobalString("CONTRACT_SIGNATURE_XFORIMGSTART")) {
											$param['xforimgstart'] = getDolGlobalString("CONTRACT_SIGNATURE_XFORIMGSTART");
										} else {
											$param['xforimgstart'] = 10;
										}
										if (getDolGlobalString("CONTRACT_SIGNATURE_YFORIMGSTART")) {
											$param['yforimgstart'] = getDolGlobalString("CONTRACT_SIGNATURE_YFORIMGSTART");
										} else {
											$param['yforimgstart'] = (empty($s['h']) ? 240 : $s['h'] - 65);
										}
										if (getDolGlobalString("CONTRACT_SIGNATURE_WFORIMG")) {
											$param['wforimg'] = getDolGlobalString("CONTRACT_SIGNATURE_WFORIMG");
										} else {
											$param['wforimg'] = $s['w'] / 2 - $param['xforimgstart'];
										}

										dolPrintSignatureImage($pdf, $langs, $param);
									}
								} catch (Exception $e) {
									dol_syslog("Error when manipulating some PDF by onlineSign: " . $e->getMessage(), LOG_ERR);
									$response = $e->getMessage();
									$error++;
								}
							}

							if (!getDolGlobalString("CONTRACT_SIGNATURE_ON_ALL_PAGES")) {
								// A signature image file is 720 x 180 (ratio 1/4) but we use only the size into PDF
								// TODO Get position of box from PDF template

								$param['xforimgstart'] = 10;
								$param['yforimgstart'] = (empty($s['h']) ? 240 : $s['h'] - 65);
								$param['wforimg'] = $s['w'] / 2 - $param['xforimgstart'];

								dolPrintSignatureImage($pdf, $langs, $param);
							}

							//$pdf->Close();
							$pdf->Output($newpdffilename, "F");

							// Index the new file and update the last_main_doc property of object.
							$object->indexFile($newpdffilename, 1);
						}
					}
					if (!$error) {
						$response = "success";
					}
				} elseif (preg_match('/\.odt/i', $last_main_doc_file)) {
					// Adding signature on .ODT not yet supported
					// TODO
				} else {
					// Document format not supported to insert online signature.
					// We should just create an image file with the signature.
				}
			}
		} elseif ($mode == 'fichinter') {
			require_once DOL_DOCUMENT_ROOT . '/fichinter/class/fichinter.class.php';
			require_once DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php';
			$object = new Fichinter($db);
			$object->fetch(0, $ref);

			$upload_dir = !empty($conf->ficheinter->multidir_output[$object->entity]) ? $conf->ficheinter->multidir_output[$object->entity] : $conf->ficheinter->dir_output;
			$upload_dir .= '/'.dol_sanitizeFileName($object->ref).'/';

			$langs->loadLangs(array("main", "companies"));

			$default_font_size = pdf_getPDFFontSize($langs);	// Must be after pdf_getInstance
			$default_font = pdf_getPDFFont($langs);	// Must be

			$date = dol_print_date(dol_now(), "%Y%m%d%H%M%S");
			$filename = "signatures/" . $date . "_signature.png";
			if (!is_dir($upload_dir . "signatures/")) {
				if (!dol_mkdir($upload_dir . "signatures/")) {
					$response = "Error mkdir. Failed to create dir " . $upload_dir . "signatures/";
					$error++;
				}
			}

			if (!$error) {
				$return = file_put_contents($upload_dir . $filename, $data);
				if ($return == false) {
					$error++;
					$response = 'Error file_put_content: failed to create signature file.';
				}
			}

			if (!$error) {
				// Defined modele of doc
				$last_main_doc_file = $object->last_main_doc;
				$directdownloadlink = $object->getLastMainDocLink('fichinter');    // url to download the $object->last_main_doc

				if (preg_match('/\.pdf/i', $last_main_doc_file)) {
					// TODO Use the $last_main_doc_file to defined the $newpdffilename and $sourcefile
					$newpdffilename = $upload_dir . $ref . "_signed-" . $date . ".pdf";
					$sourcefile = $upload_dir . $ref . ".pdf";

					if (dol_is_file($sourcefile)) {
						$parameters = array('sourcefile' => $sourcefile, 'newpdffilename' => $newpdffilename);
						$reshook = $hookmanager->executeHooks('AddSignature', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
						if ($reshook < 0) {
							setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
						}

						if (empty($reshook)) {
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
							$pagecount = $pdf->setSourceFile($sourcefile);        // original PDF

							$param = array();
							$param['online_sign_name'] = $online_sign_name;
							$param['pathtoimage'] = $upload_dir . $filename;

							$s = array();    // Array with size of each page. Example array(w'=>210, 'h'=>297);
							for ($i = 1; $i < ($pagecount + 1); $i++) {
								try {
									$tppl = $pdf->importPage($i);
									$s = $pdf->getTemplatesize($tppl);
									$pdf->AddPage($s['h'] > $s['w'] ? 'P' : 'L');
									$pdf->useTemplate($tppl);

									if (getDolGlobalString("FICHINTER_SIGNATURE_ON_ALL_PAGES")) {
										// A signature image file is 720 x 180 (ratio 1/4) but we use only the size into PDF
										// TODO Get position of box from PDF template

										if (getDolGlobalString("FICHINTER_SIGNATURE_XFORIMGSTART")) {
											$param['xforimgstart'] = getDolGlobalString("FICHINTER_SIGNATURE_XFORIMGSTART");
										} else {
											$param['xforimgstart'] = 111;
										}
										if (getDolGlobalString("FICHINTER_SIGNATURE_YFORIMGSTART")) {
											$param['yforimgstart'] = getDolGlobalString("FICHINTER_SIGNATURE_YFORIMGSTART");
										} else {
											$param['yforimgstart'] = (empty($s['h']) ? 250 : $s['h'] - 60);
										}
										if (getDolGlobalString("FICHINTER_SIGNATURE_WFORIMG")) {
											$param['wforimg'] = getDolGlobalString("FICHINTER_SIGNATURE_WFORIMG");
										} else {
											$param['wforimg'] = $s['w'] - ($param['xforimgstart'] + 16);
										}

										dolPrintSignatureImage($pdf, $langs, $param);
									}
								} catch (Exception $e) {
									dol_syslog("Error when manipulating some PDF by onlineSign: " . $e->getMessage(), LOG_ERR);
									$response = $e->getMessage();
									$error++;
								}
							}

							if (!getDolGlobalString("FICHINTER_SIGNATURE_ON_ALL_PAGES")) {
								// A signature image file is 720 x 180 (ratio 1/4) but we use only the size into PDF
								// TODO Get position of box from PDF template

								$param['xforimgstart'] = 111;
								$param['yforimgstart'] = (empty($s['h']) ? 250 : $s['h'] - 60);
								$param['wforimg'] = $s['w'] - ($param['xforimgstart'] + 16);

								dolPrintSignatureImage($pdf, $langs, $param);
							}

							//$pdf->Close();
							$pdf->Output($newpdffilename, "F");

							// Index the new file and update the last_main_doc property of object.
							$object->indexFile($newpdffilename, 1);
						}
					}
					if (!$error) {
						$response = "success";
					}
				} elseif (preg_match('/\.odt/i', $last_main_doc_file)) {
					// Adding signature on .ODT not yet supported
					// TODO
				} else {
					// Document format not supported to insert online signature.
					// We should just create an image file with the signature.
				}
				$user = new User($db);
				$object->setSignedStatus($user, $object::SIGNED_STATUSES['STATUS_SIGNED_RECEIVER_ONLINE'], 0, 'FICHINTER_MODIFY');
			}
		} elseif ($mode == "societe_rib") {
			$langs->load('withdrawals');
			require_once DOL_DOCUMENT_ROOT . '/societe/class/companybankaccount.class.php';
			require_once DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php';
			$modelpath = "core/modules/bank/doc/";
			$object = new CompanyBankAccount($db);
			$object->fetch(0, $ref);
			if (!empty($object->id)) {
				$object->fetch_thirdparty();

				$upload_dir = $conf->societe->multidir_output[$object->thirdparty->entity] . '/' . dol_sanitizeFileName($object->thirdparty->id) . '/';

				$default_font_size = pdf_getPDFFontSize($langs);    // Must be after pdf_getInstance
				$default_font = pdf_getPDFFont($langs);    // Must be after pdf_getInstance
				$langs->loadLangs(array("main", "companies"));

				$date = dol_print_date(dol_now(), "%Y%m%d%H%M%S");
				$filename = "signatures/" . $date . "_signature.png";
				if (!dol_is_dir($upload_dir . "signatures/")) {
					if (!dol_mkdir($upload_dir . "signatures/")) {
						$response = "Error mkdir. Failed to create dir " . $upload_dir . "signatures/";
						$error++;
					}
				}
				if (!dol_is_writable($upload_dir . "signatures/")) {
					$response = "Error directory " . $upload_dir . "signatures/ is not writable";
					$error++;
				}
				if (!dol_is_writable(DOL_DATA_ROOT.'/admin/temp/')) {	// This is used by TCPDF as working directory
					$response = "Error directory " . DOL_DATA_ROOT."/admin/temp/ is not writable";
					$error++;
				}

				if (!$error) {
					$return = file_put_contents($upload_dir . $filename, $data);
					if ($return == false) {
						$error++;
						$response = 'Error file_put_content: failed to create signature file.';
					}
				}

				if (!$error) {
					// Defined modele of doc
					$last_main_doc_file = $object->last_main_doc;
					$last_modelpdf = $object->model_pdf;
					$directdownloadlink = $object->getLastMainDocLink('company');    // url to download the $object->last_main_doc

					if (preg_match('/\.pdf/i', $last_main_doc_file)) {
						$sourcefile = '';
						$newpdffilename = '';
						if ($last_modelpdf == 'sepamandate') {
							$newpdffilename = $upload_dir . $langs->transnoentitiesnoconv("SepaMandateShort") . ' ' . dol_sanitizeFileName($object->ref) . "-" . dol_sanitizeFileName($object->rum) . "_signed-" . $date . ".pdf";
							$sourcefile = $upload_dir . $langs->transnoentitiesnoconv("SepaMandateShort") . ' ' . dol_sanitizeFileName($object->ref) . "-" . dol_sanitizeFileName($object->rum) . ".pdf";
						}
						if (dol_is_file($sourcefile)) {
							$parameters = array('sourcefile' => $sourcefile, 'newpdffilename' => $newpdffilename);
							$reshook = $hookmanager->executeHooks('AddSignature', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
							if ($reshook < 0) {
								setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
							}

							if (empty($reshook)) {
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
								$pagecount = $pdf->setSourceFile($sourcefile);        // original PDF

								$s = array();    // Array with size of each page. Example array(w'=>210, 'h'=>297);
								for ($i = 1; $i < ($pagecount + 1); $i++) {
									try {
										$tppl = $pdf->importPage($i);
										$s = $pdf->getTemplatesize($tppl);
										$pdf->AddPage($s['h'] > $s['w'] ? 'P' : 'L');
										$pdf->useTemplate($tppl);
									} catch (Exception $e) {
										dol_syslog("Error when manipulating the PDF " . $sourcefile . " by onlineSign: " . $e->getMessage(), LOG_ERR);
										$response = $e->getMessage();
										$error++;
									}
								}


								// Get position of box from PDF template
								$file = '';
								$classname = '';
								$filefound = '';
								$dirmodels = array('/');
								if (is_array($conf->modules_parts['models'])) {
									$dirmodels = array_merge($dirmodels, $conf->modules_parts['models']);
								}
								foreach ($dirmodels as $reldir) {
									$file = "pdf_" . $last_modelpdf . ".modules.php";
									// On vérifie l'emplacement du modele
									$file = dol_buildpath($reldir . $modelpath . $file, 0);
									if (file_exists($file)) {
										$filefound = $file;
										$classname = 'pdf_' . $last_modelpdf;
										break;
									}
								}

								if ($filefound === '') {
									$response = $langs->trans("Error") . ' Failed to load doc generator with modelpaths=' . $modelpath . ' - modele=' . $last_modelpdf;
									dol_syslog($response, LOG_ERR);
									$error++;
								}

								if (!$error && $classname !== '') {
									// If PDF template class  was found
									require_once $file;

									$objPDF = new $classname($db);

									$pdf->SetFont($default_font, '', $default_font_size - 1);

									$xForDate = $objPDF->marge_gauche;
									$yForDate = $objPDF->page_hauteur - $objPDF->heightforinfotot - $objPDF->heightforfreetext - $objPDF->heightforfooter + 10;
									$pdf->SetXY($xForDate, $yForDate);
									$pdf->MultiCell(100, 4, dol_print_date(dol_now(), "daytext", false, $langs, true), 0, 'L');

									$xforimgstart = $objPDF->xPosSignArea;
									$yforimgstart = $yForDate - 5;
									$wforimg = $s['w'] - 20 - $xforimgstart;

									$param = array();
									$param['online_sign_name'] = $online_sign_name;
									$param['pathtoimage'] = $upload_dir . $filename;

									// A signature image file is 720 x 180 (ratio 1/4) but we use only the size into PDF
									// TODO Get position of box from PDF template

									$param['xforimgstart'] = $xforimgstart;
									$param['yforimgstart'] = $yforimgstart;
									$param['wforimg'] = $wforimg;

									dolPrintSignatureImage($pdf, $langs, $param);
								}
								//$pdf->Close();
								$pdf->Output($newpdffilename, "F");

								// Index the new file and update the last_main_doc property of object.
								$object->indexFile($newpdffilename, 1);
							}
						}
					} elseif (preg_match('/\.odt/i', $last_main_doc_file)) {
						// Adding signature on .ODT not yet supported
						// TODO
					} else {
						// Document format not supported to insert online signature.
						// We should just create an image file with the signature.
					}
				}
			} else {
				$error++;
				$response = "cannot find BAN/RIB";
			}

			if (!$error) {
				$db->begin();

				$online_sign_ip = getUserRemoteIP();

				$sql = "UPDATE " . MAIN_DB_PREFIX . $object->table_element;
				$sql .= " SET ";
				$sql .= " date_signature = '" . $db->idate(dol_now()) . "',";
				$sql .= " online_sign_ip = '" . $db->escape($online_sign_ip) . "'";
				if ($online_sign_name) {
					$sql .= ", online_sign_name = '" . $db->escape($online_sign_name) . "'";
				}
				//$sql .= ", last_main_doc = '" . $db->escape($object->element'..') . "'";

				$sql .= " WHERE rowid = " . ((int) $object->id);

				dol_syslog(__FILE__, LOG_DEBUG);
				$resql = $db->query($sql);
				if (!$resql) {
					$error++;
				} else {
					$num = $db->affected_rows($resql);
				}

				if (!$error) {
					$response = "success";
				} else {
					$error++;
					$response = "error sql";
				}

				if (!$error) {
					$db->commit();
					$response = "success";
					setEventMessages(dol_ucfirst($mode)."Signed", null, 'warnings');
				} else {
					$db->rollback();
				}
			}
		} elseif ($mode == 'expedition') {
			require_once DOL_DOCUMENT_ROOT . '/expedition/class/expedition.class.php';
			require_once DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php';

			$object = new Expedition($db);
			$object->fetch(0, $ref);

			$upload_dir = $conf->expedition->dir_output."/sending/";
			$upload_dir .= '/'.dol_sanitizeFileName($object->ref).'/';

			$langs->loadLangs(array("main", "companies"));

			$default_font_size = pdf_getPDFFontSize($langs);	// Must be after pdf_getInstance
			$default_font = pdf_getPDFFont($langs);	// Must be

			$date = dol_print_date(dol_now(), "%Y%m%d%H%M%S");
			$filename = "signatures/" . $date . "_signature.png";
			if (!is_dir($upload_dir . "signatures/")) {
				if (!dol_mkdir($upload_dir . "signatures/")) {
					$response = "Error mkdir. Failed to create dir " . $upload_dir . "signatures/";
					$error++;
				}
			}

			if (!$error) {
				$return = file_put_contents($upload_dir . $filename, $data);
				if ($return == false) {
					$error++;
					$response = 'Error file_put_content: failed to create signature file.';
				}
			}

			if (!$error) {
				$last_main_doc_file = $object->last_main_doc;
				// Defined modele of doc
				if (empty($last_main_doc_file) || !dol_is_file(DOL_DATA_ROOT.'/'.$object->last_main_doc)) {
					// It seems document has never been generated, or was generated and then deleted.
					// So we try to regenerate it with its default template.
					$defaulttemplate = '';		// We force the use an empty string instead of $object->model_pdf to be sure to use a "main" default template and not the last one used.
					$object->generateDocument($defaulttemplate, $langs);
				}
				$last_main_doc_file = $object->last_main_doc;
				$directdownloadlink = $object->getLastMainDocLink('expedition');    // url to download the $object->last_main_doc

				if (preg_match('/\.pdf/i', $last_main_doc_file)) {
					// TODO Use the $last_main_doc_file to defined the $newpdffilename and $sourcefile
					$newpdffilename = $upload_dir . $ref . "_signed-" . $date . ".pdf";
					$sourcefile = $upload_dir . $ref . ".pdf";

					if (dol_is_file($sourcefile)) {
						$parameters = array('sourcefile' => $sourcefile, 'newpdffilename' => $newpdffilename);
						$reshook = $hookmanager->executeHooks('AddSignature', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
						if ($reshook < 0) {
							setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
						}

						if (empty($reshook)) {
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
							$pagecount = $pdf->setSourceFile($sourcefile);        // original PDF

							$param = array();
							$param['online_sign_name'] = $online_sign_name;
							$param['pathtoimage'] = $upload_dir . $filename;

							$s = array();    // Array with size of each page. Example array(w'=>210, 'h'=>297);
							for ($i = 1; $i < ($pagecount + 1); $i++) {
								try {
									$tppl = $pdf->importPage($i);
									$s = $pdf->getTemplatesize($tppl);
									$pdf->AddPage($s['h'] > $s['w'] ? 'P' : 'L');
									$pdf->useTemplate($tppl);

									if (getDolGlobalString("SHIPMENT_SIGNATURE_ON_ALL_PAGES")) {
										// A signature image file is 720 x 180 (ratio 1/4) but we use only the size into PDF
										// TODO Get position of box from PDF template

										$param['xforimgstart'] = 111;
										$param['yforimgstart'] = (empty($s['h']) ? 250 : $s['h'] - 60);
										$param['wforimg'] = $s['w'] - ($param['xforimgstart'] + 16);

										dolPrintSignatureImage($pdf, $langs, $param);
									}
								} catch (Exception $e) {
									dol_syslog("Error when manipulating some PDF by onlineSign: " . $e->getMessage(), LOG_ERR);
									$response = $e->getMessage();
									$error++;
								}
							}

							if (!getDolGlobalString("SHIPMENT_SIGNATURE_ON_ALL_PAGES")) {
								// A signature image file is 720 x 180 (ratio 1/4) but we use only the size into PDF
								// TODO Get position of box from PDF template

								$param['xforimgstart'] = 111;
								$param['yforimgstart'] = (empty($s['h']) ? 250 : $s['h'] - 60);
								$param['wforimg'] = $s['w'] - ($param['xforimgstart'] + 16);

								dolPrintSignatureImage($pdf, $langs, $param);
							}

							//$pdf->Close();
							$pdf->Output($newpdffilename, "F");

							// Index the new file and update the last_main_doc property of object.
							$object->indexFile($newpdffilename, 1);
						}
					}
					if (!$error) {
						$response = "success";
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

				$sql = "UPDATE " . MAIN_DB_PREFIX . "expedition";
				$sql .= " SET signed_status = " . ((int) $object::STATUS_SIGNED) ;
				$sql .= " WHERE rowid = " . ((int) $object->id);

				dol_syslog(__FILE__, LOG_DEBUG);
				$resql = $db->query($sql);
				if (!$resql) {
					$error++;
				} else {
					$num = $db->affected_rows($resql);
				}

				if (!$error) {
					$db->commit();
					$response = "success";
					setEventMessages("ExpeditionSigned", null, 'warnings');
				} else {
					$db->rollback();
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


/**
 * Output the signature file into the PDF object.
 *
 * @param 	TCPDF 		$pdf		PDF handler
 * @param	Translate	$langs		Language
 * @param	array		$params		Array of params
 * @return	void
 */
function dolPrintSignatureImage(TCPDF $pdf, $langs, $params)
{
	$default_font_size = pdf_getPDFFontSize($langs);	// Must be after pdf_getInstance
	$default_font = pdf_getPDFFont($langs);	// Must be
	$xforimgstart = $params['xforimgstart'];
	$yforimgstart = $params['yforimgstart'];
	$wforimg = $params['wforimg'];

	$pdf->SetXY($xforimgstart, $yforimgstart + round($wforimg / 4) - 4);
	$pdf->SetFont($default_font, '', $default_font_size - 1);
	$pdf->SetTextColor(80, 80, 80);
	$pdf->MultiCell($wforimg, 4, $langs->trans("Signature") . ': ' . dol_print_date(dol_now(), "day", false, $langs, true). ' - '.$params['online_sign_name'], 0, 'L');
	//$pdf->SetXY($xforimgstart, $yforimgstart + round($wforimg / 4));
	//$pdf->MultiCell($wforimg, 4, $langs->trans("Lastname") . ': ' . $online_sign_name, 0, 'L');

	$pdf->Image($params['pathtoimage'], $xforimgstart, $yforimgstart, $wforimg, round($wforimg / 4));

	return;
}
