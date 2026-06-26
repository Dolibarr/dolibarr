<?php
/* Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024-2026  Frédéric France             <frederic.france@free.fr>
 * Copyright (C) 2024		William Mead				<william.mead@manchenumerique.fr>
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
require_once DOL_DOCUMENT_ROOT . '/core/lib/signature.lib.php';
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

$action = GETPOST('action', 'aZ09');

$signature = GETPOST('signaturebase64');
// Match the filter the producer uses in newonlinesign.php:97 ('alpha').
// Refs such as PR2601-0003 contain a dash, which aZ09 strips. After stripping
// the dash, dol_verifyHash fails because the security key was built from the
// full reference, so onlineSign answered 403 even on valid submissions (#31464).
$ref = GETPOST('ref', 'alpha');
$mode = GETPOST('mode', 'aZ09');    // 'proposal', ...
$SECUREKEY = GETPOST("securekey"); // Secure key
$online_sign_name = GETPOST("onlinesignname");

$error = 0;
$response = "";
$object = null;
$upload_dir = '';
$filename = '';
$newpdffilename = '';

$type = $mode;

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('ajaxonlinesign'));

$sourceDefinition = getOnlineSignatureSourceDefinition($type, $ref, (int) $entity);
if (empty($sourceDefinition)) {
	httponly_accessforbidden($langs->trans('ErrorBadParameters') . " - Bad value for source. Value not supported.", 400);
}

if (!isOnlineSignatureSourceEnabled($sourceDefinition)) {
	httponly_accessforbidden($langs->trans('FeatureOnlineSignDisabled'), 403);
}

if (!empty($sourceDefinition['langfiles']) && is_array($sourceDefinition['langfiles'])) {
	$langs->loadLangs($sourceDefinition['langfiles']);
}

// Security check
if (empty($SECUREKEY) || !verifyOnlineSignatureSecureKey($sourceDefinition, $ref, (int) $entity, $SECUREKEY)) {
	httponly_accessforbidden('Bad value for securitykey. Value provided ' . dol_escape_htmltag($SECUREKEY) . ' does not match expected value for ref=' . dol_escape_htmltag($ref), 403);
}


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

			$upload_dir = !empty($conf->propal->multidir_output[$object->entity ?? $conf->entity]) ? $conf->propal->multidir_output[$object->entity ?? $conf->entity] : $conf->propal->dir_output;
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
				$return = file_put_contents($upload_dir.$filename, $data);
				if ($return === false) {
					$error++;
					$response = 'Error file_put_content: failed to create signature file.';
				} else {
					dolChmod($upload_dir.$filename);
				}
			}

			if (!$error) {
				// Defined modele of doc
				$last_main_doc_file = $object->last_main_doc;
				$directdownloadlink = $object->getLastMainDocLink('proposal');    // url to download the $object->last_main_doc

				if (preg_match('/\.pdf/i', $last_main_doc_file)) {
					$ref_pdf = pathinfo($last_main_doc_file, PATHINFO_FILENAME); // Retrieves the name of external or internal PDF
					$ref_pdf = preg_replace('/_signed-(\d+)/', '', $ref_pdf);

					$newpdffilename = $upload_dir . $ref_pdf . "_signed-" . $date . ".pdf";
					$sourcefile = $upload_dir . $ref_pdf . ".pdf";

					if (dol_is_file($sourcefile)) {
						$parameters = array('sourcefile' => $sourcefile, 'newpdffilename' => $newpdffilename);
						$reshook = $hookmanager->executeHooks('AddSignature', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
						if ($reshook < 0) {
							setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
						}

						if (empty($reshook)) {
							// We build the new PDF
							$formatarray = pdf_getFormat();
							$page_largeur = $formatarray['width'];
							$page_hauteur = $formatarray['height'];
							$format = array($page_largeur, $page_hauteur);

							$pdf = pdf_getInstance($format);
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

							$propalsignonspecificpage = getDolGlobalInt("PROPAL_SIGNATURE_ON_SPECIFIC_PAGE");

							$s = array();    // Array with size of each page. Example array(w'=>210, 'h'=>297);
							for ($i = 1; $i < ($pagecount + 1); $i++) {
								try {
									$tppl = $pdf->importPage($i);
									$s = $pdf->getTemplatesize($tppl);
									$format = array($s['w'], $s['h']);
									$pdf->AddPage($s['h'] > $s['w'] ? 'P' : 'L', $format);
									$pdf->useTemplate($tppl);

									if ($propalsignonspecificpage < 0) {
										$propalsignonspecificpage = $pagecount - abs($propalsignonspecificpage);
									}

									if (empty($propalsignonspecificpage)) {
										// Now we get the metadata keywords from the $sourcefile PDF (by parsing the binary PDF file) and use it to extract
										// the page x in PAGESIGN=x into $propalsignonspecificpage
										$keywords = pdfExtractMetadata($sourcefile, 'Keywords');
										$reg = array();
										if (preg_match('/PAGESIGN=(\d+)/', $keywords, $reg)) {
											$propalsignonspecificpage = (int) $reg[1];
										}
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

							if (!getDolGlobalString("PROPAL_SIGNATURE_ON_ALL_PAGES") && !$propalsignonspecificpage) {
								// We do not found specific instruction or page for the signature, so we add it now we are on the last page.
								// A signature image file is 720 x 180 (ratio 1/4) but we use only the size into PDF
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
				$sql .= " AND fk_statut = ".((int) $object::STATUS_VALIDATED);		// Protection so we can't sign a document that is no more with status validated.

				dol_syslog(__FILE__, LOG_DEBUG);
				$resql = $db->query($sql);
				if (!$resql) {
					$error++;
					$response = "error sql";
				} else {
					$num = $db->affected_rows($resql);
					if ($num <= 0) {
						$error++;
						$langs->load("errors");
						//setEventMessages($langs->trans("ErrorCantSignDocument"), null, 'errors');
						print $langs->transnoentitiesnoconv("ErrorCantSignDocument");	// Must be a print that is shown by ajavascript alert().
					}
				}

				if (!$error) {
					if (method_exists($object, 'call_trigger')) {
						$object->context = array('closedfromonlinesignature' => 'closedfromonlinesignature');

						$result = $object->call_trigger('PROPAL_CLOSE_SIGNED', $user);
						if ($result < 0) {
							$error++;
							$response = "error in trigger " . $object->error;
						} else {
							$soc = new Societe($db);
							$soc->id = $object->socid;
							$result = $soc->setAsCustomer();
							if ($result < 0) {
								$error++;
								$response = $db->lasterror();
							} else {
								$response = "success";
							}
						}
					} else {
						$response = "success";
					}
				}

				if (!$error) {
					$db->commit();
					$response = "success";
					setEventMessages("PropalSigned", null, 'mesgs');
				} else {
					$db->rollback();
				}
			}
		} elseif ($mode == 'contract') {
			require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';
			require_once DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php';
			$object = new Contrat($db);
			$object->fetch(0, $ref);

			$upload_dir = !empty($conf->contract->multidir_output[$object->entity ?? $conf->entity]) ? $conf->contract->multidir_output[$object->entity ?? $conf->entity] : $conf->contrat->dir_output;
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
				if ($return === false) {
					$error++;
					$response = 'Error file_put_content: failed to create signature file.';
				} else {
					dolChmod($upload_dir.$filename);
				}
			}

			if (!$error) {
				// Defined modele of doc
				$last_main_doc_file = $object->last_main_doc;
				$directdownloadlink = $object->getLastMainDocLink('contrat');    // url to download the $object->last_main_doc

				if (preg_match('/\.pdf/i', $last_main_doc_file)) {
					$ref_pdf = pathinfo($last_main_doc_file, PATHINFO_FILENAME); // Retrieves the name of external or internal PDF

					$newpdffilename = $upload_dir . $ref_pdf . "_signed-" . $date . ".pdf";
					$sourcefile = $upload_dir . $ref_pdf . ".pdf";

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
											$param['xforimgstart'] = (empty($s['w']) ? 110 : $s['w'] / 2 - 0);
										}
										if (getDolGlobalString("CONTRACT_SIGNATURE_YFORIMGSTART")) {
											$param['yforimgstart'] = getDolGlobalString("CONTRACT_SIGNATURE_YFORIMGSTART");
										} else {
											$param['yforimgstart'] = (empty($s['h']) ? 250 : $s['h'] - 62);
										}
										if (getDolGlobalString("CONTRACT_SIGNATURE_WFORIMG")) {
											$param['wforimg'] = getDolGlobalString("CONTRACT_SIGNATURE_WFORIMG");
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

							if (!getDolGlobalString("CONTRACT_SIGNATURE_ON_ALL_PAGES")) {
								// A signature image file is 720 x 180 (ratio 1/4) but we use only the size into PDF
								// TODO Get position of box from PDF template

								$param['xforimgstart'] = (empty($s['w']) ? 110 : $s['w'] / 2 - 0);
								$param['yforimgstart'] = (empty($s['h']) ? 250 : $s['h'] - 62);
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
				$object->setSignedStatus($user, Contrat::$SIGNED_STATUSES['STATUS_SIGNED_RECEIVER_ONLINE'], 0, 'CONTRACT_MODIFY');
			}
		} elseif ($mode == 'fichinter') {
			require_once DOL_DOCUMENT_ROOT . '/fichinter/class/fichinter.class.php';
			require_once DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php';
			$object = new Fichinter($db);
			$object->fetch(0, $ref);

			$upload_dir = !empty($conf->ficheinter->multidir_output[$object->entity ?? $conf->entity]) ? $conf->ficheinter->multidir_output[$object->entity ?? $conf->entity] : $conf->ficheinter->dir_output;
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
				if ($return === false) {
					$error++;
					$response = 'Error file_put_content: failed to create signature file.';
				} else {
					dolChmod($upload_dir.$filename);
				}
			}

			if (!$error) {
				// Defined modele of doc
				$last_main_doc_file = $object->last_main_doc;
				$directdownloadlink = $object->getLastMainDocLink('fichinter');    // url to download the $object->last_main_doc

				if (preg_match('/\.pdf/i', $last_main_doc_file)) {
					$ref_pdf = pathinfo($last_main_doc_file, PATHINFO_FILENAME); // Retrieves the name of external or internal PDF

					$newpdffilename = $upload_dir . $ref_pdf . "_signed-" . $date . ".pdf";
					$sourcefile = $upload_dir . $ref_pdf . ".pdf";

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
											$param['xforimgstart'] = (empty($s['w']) ? 110 : round($s['w'] * 0.53));
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

								$param['xforimgstart'] = (empty($s['w']) ? 110 : round($s['w'] * 0.53));
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
				$object->setSignedStatus($user, Fichinter::$SIGNED_STATUSES['STATUS_SIGNED_RECEIVER_ONLINE'], 0, 'FICHINTER_MODIFY');
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

				$upload_dir = $conf->societe->multidir_output[$object->thirdparty->entity] . '/' . dol_sanitizeFileName((string) $object->thirdparty->id) . '/';

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
					if ($return === false) {
						$error++;
						$response = 'Error file_put_content: failed to create signature file.';
					} else {
						dolChmod($upload_dir.$filename);
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
						} else {
							// Fallback for setups using a non-default bank PDF model (eg. "ban"): take the last
							// generated main document as source and append "_signed-<date>" before the extension.
							// Without this the signed PDF is never built and the download link keeps pointing at
							// the unsigned original.
							$sourcefile = DOL_DATA_ROOT . '/' . $last_main_doc_file;
							$newpdffilename = preg_replace('/\.pdf$/i', '_signed-' . $date . '.pdf', $sourcefile);
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
									// Check the template location
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

				$sql = "UPDATE " . MAIN_DB_PREFIX . $db->sanitize($object->table_element);
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
				if ($return === false) {
					$error++;
					$response = 'Error file_put_content: failed to create signature file.';
				} else {
					dolChmod($upload_dir.$filename);
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
					$ref_pdf = pathinfo($last_main_doc_file, PATHINFO_FILENAME); // Retrieves the name of external or internal PDF

					$newpdffilename = $upload_dir . $ref_pdf . "_signed-" . $date . ".pdf";
					$sourcefile = $upload_dir . $ref_pdf . ".pdf";

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

										$param['xforimgstart'] = (empty($s['w']) ? 110 : round($s['w'] * 0.53));
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

								$param['xforimgstart'] = (empty($s['w']) ? 110 : round($s['w'] * 0.53));
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
			$user = new User($db);
			$object->setSignedStatus($user, Expedition::$SIGNED_STATUSES['STATUS_SIGNED_RECEIVER_ONLINE'], 0, 'SHIPPING_MODIFY');
		} else {
			require_once DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php';

			$object = fetchOnlineSignatureObject($sourceDefinition, $ref, (int) $entity);
			if (!is_object($object) || empty($object->id)) {
				$error++;
				$response = "error object_not_found";
			}

			if (!$error) {
				$modulepart = empty($sourceDefinition['modulepart']) ? $type : (string) $sourceDefinition['modulepart'];
				$upload_dir = getMultidirOutput($object, $modulepart, 1);
				if (empty($upload_dir) || preg_match('/^error-/', (string) $upload_dir)) {
					$error++;
					$response = "Error document directory not found for online signature source " . dol_escape_htmltag($type);
				} else {
					$upload_dir = rtrim((string) $upload_dir, '/\\') . '/';
				}
			}

			if (!$error) {
				$date = dol_print_date(dol_now(), "%Y%m%d%H%M%S");
				$filename = "signatures/" . $date . "_signature.png";
				if (!dol_is_dir($upload_dir . "signatures/")) {
					if (!dol_mkdir($upload_dir . "signatures/")) {
						$response = "Error mkdir. Failed to create dir " . $upload_dir . "signatures/";
						$error++;
					}
				}
			}

			if (!$error) {
				$return = file_put_contents($upload_dir . $filename, $data);
				if ($return === false) {
					$error++;
					$response = 'Error file_put_content: failed to create signature file.';
				} else {
					dolChmod($upload_dir . $filename);
				}
			}

			if (!$error) {
				$last_main_doc_file = empty($object->last_main_doc) ? '' : $object->last_main_doc;
				if ((empty($last_main_doc_file) || !dol_is_file(DOL_DATA_ROOT . '/' . $last_main_doc_file)) && method_exists($object, 'generateDocument')) {
					$defaulttemplate = '';
					$object->generateDocument($defaulttemplate, $langs);
					$last_main_doc_file = empty($object->last_main_doc) ? '' : $object->last_main_doc;
				}

				if (empty($last_main_doc_file)) {
					$error++;
					$response = "error document_not_found";
				} elseif (preg_match('/\.pdf$/i', $last_main_doc_file)) {
					$sourcefile = DOL_DATA_ROOT . '/' . $last_main_doc_file;
					$sourcefilewithoutsign = preg_replace('/_signed-(\d+)\.pdf$/i', '.pdf', $sourcefile);
					if (is_string($sourcefilewithoutsign) && dol_is_file($sourcefilewithoutsign)) {
						$sourcefile = $sourcefilewithoutsign;
					} elseif (!dol_is_file($sourcefile)) {
						$sourcefile = $upload_dir . basename($last_main_doc_file);
					}

					if (!dol_is_file($sourcefile)) {
						$error++;
						$response = "error source_pdf_not_found";
					} else {
						$ref_pdf = pathinfo($sourcefile, PATHINFO_FILENAME);
						$ref_pdf = preg_replace('/_signed-(\d+)$/', '', $ref_pdf);
						$newpdffilename = dirname($sourcefile) . '/' . $ref_pdf . "_signed-" . $date . ".pdf";

						$parameters = array(
							'source' => $type,
							'source_definition' => $sourceDefinition,
							'sourcefile' => $sourcefile,
							'newpdffilename' => $newpdffilename,
							'signaturefile' => $upload_dir . $filename,
							'online_sign_name' => $online_sign_name,
						);
						$reshook = $hookmanager->executeHooks('AddSignature', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
						if ($reshook < 0) {
							$error++;
							$response = $hookmanager->error ? $hookmanager->error : "error in AddSignature hook";
							setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
						}

						if (!$error && empty($reshook)) {
							$result = onlineSignatureWriteSignedPdf($sourcefile, $newpdffilename, $upload_dir . $filename, $online_sign_name, $sourceDefinition, $response, $error);
							if ($result < 0) {
								$error = max(1, $error);
							}
						}

						if (!$error && !dol_is_file($newpdffilename)) {
							$error++;
							$response = "error signed_pdf_not_created";
						}

						if (!$error && method_exists($object, 'indexFile')) {
							$object->indexFile($newpdffilename, 1);
						}
					}
				} elseif (preg_match('/\.odt$/i', $last_main_doc_file)) {
					$error++;
					$response = "error document_format_not_supported";
				} else {
					$error++;
					$response = "error document_format_not_supported";
				}
			}

			if (!$error) {
				$result = onlineSignatureFinalizeObject($sourceDefinition, $object, $online_sign_name, $upload_dir . $filename, $newpdffilename, $response);
				if ($result < 0) {
					$error++;
				} else {
					$response = "success";
				}
			}
		}
	} else {
		$error++;
		$response = 'error signature_not_found';
	}
}

if (!$error && $response == 'success' && is_object($object) && !empty($object->id)) {
	$pathoffile = '';
	if (!empty($newpdffilename)) {
		$pathoffile = $newpdffilename;
	} elseif (!empty($upload_dir) && !empty($filename)) {
		$pathoffile = $upload_dir . $filename;
	}
	onlineSignatureRecordTrace($sourceDefinition, $object, $online_sign_name, getUserRemoteIP(), $pathoffile);
}

if ($error) {
	http_response_code(501);
}

echo $response;


/**
 * Build a signed PDF for an online signature source.
 *
 * @param	string				$sourcefile			Full path to the source PDF
 * @param	string				$newpdffilename		Full path to the signed PDF
 * @param	string				$signaturefile		Full path to the signature image
 * @param	string				$online_sign_name	Signer name
 * @param	array<string,mixed>	$sourceDefinition	Online signature source definition
 * @param	string				$response			Response message
 * @param	int					$error				Error counter
 * @return	int										1 if OK, -1 if KO
 */
function onlineSignatureWriteSignedPdf($sourcefile, $newpdffilename, $signaturefile, $online_sign_name, $sourceDefinition, &$response, &$error)
{
	global $langs;

	try {
		$pdf = pdf_getInstance();
		if (class_exists('TCPDF')) {
			$pdf->setPrintHeader(false);
			$pdf->setPrintFooter(false);
		}
		$pdf->SetFont(pdf_getPDFFont($langs));

		if (getDolGlobalString('MAIN_DISABLE_PDF_COMPRESSION')) {
			$pdf->SetCompression(false);
		}

		$pagecount = $pdf->setSourceFile($sourcefile);
		$prefix = empty($sourceDefinition['signature_position_prefix']) ? dol_strtoupper((string) $sourceDefinition['source']) : (string) $sourceDefinition['signature_position_prefix'];
		$allsignpages = getDolGlobalString($prefix . "_SIGNATURE_ON_ALL_PAGES");
		$specificpage = getDolGlobalInt($prefix . "_SIGNATURE_ON_SPECIFIC_PAGE");
		if ($specificpage < 0) {
			$specificpage = max(1, $pagecount + 1 - abs($specificpage));
		}
		if (empty($specificpage)) {
			$keywords = pdfExtractMetadata($sourcefile, 'Keywords');
			$reg = array();
			if (preg_match('/PAGESIGN=(\d+)/', $keywords, $reg)) {
				$specificpage = (int) $reg[1];
			}
		}

		$param = array();
		$param['online_sign_name'] = $online_sign_name;
		$param['pathtoimage'] = $signaturefile;
		$s = array('w' => 0, 'h' => 0);
		$signatureprinted = false;

		for ($i = 1; $i < ($pagecount + 1); $i++) {
			$tppl = $pdf->importPage($i);
			$s = $pdf->getTemplatesize($tppl);
			$pdf->AddPage($s['h'] > $s['w'] ? 'P' : 'L');
			$pdf->useTemplate($tppl);

			if ($allsignpages || (!empty($specificpage) && $specificpage == $i)) {
				onlineSignatureSetImagePosition($param, $prefix, $s);
				dolPrintSignatureImage($pdf, $langs, $param);
				$signatureprinted = true;
			}
		}

		if (!$allsignpages && !$signatureprinted) {
			onlineSignatureSetImagePosition($param, $prefix, $s);
			dolPrintSignatureImage($pdf, $langs, $param);
		}

		$pdf->Output($newpdffilename, "F");

		return 1;
	} catch (Exception $e) {
		dol_syslog("Error when manipulating the PDF " . $sourcefile . " by onlineSign: " . $e->getMessage(), LOG_ERR);
		$response = $e->getMessage();
		$error++;
	}

	return -1;
}

/**
 * Set the signature image position from Dolibarr constants or default values.
 *
 * @param	array<string,int|float|string>			$params		Image parameters
 * @param	string									$prefix		Constant prefix
 * @param	array<string,int|float>					$pagesize	PDF page size
 * @return	void
 */
function onlineSignatureSetImagePosition(&$params, $prefix, $pagesize)
{
	$pagewidth = empty($pagesize['w']) ? 210 : (float) $pagesize['w'];
	$pageheight = empty($pagesize['h']) ? 297 : (float) $pagesize['h'];

	$xforimgstart = getDolGlobalString($prefix . "_SIGNATURE_XFORIMGSTART");
	if ($xforimgstart !== '') {
		$params['xforimgstart'] = (float) $xforimgstart;
	} else {
		$params['xforimgstart'] = $pagewidth / 2;
	}

	$yforimgstart = getDolGlobalString($prefix . "_SIGNATURE_YFORIMGSTART");
	if ($yforimgstart !== '') {
		$params['yforimgstart'] = (float) $yforimgstart;
	} else {
		$params['yforimgstart'] = $pageheight - 62;
	}

	$wforimg = getDolGlobalString($prefix . "_SIGNATURE_WFORIMG");
	if ($wforimg !== '') {
		$params['wforimg'] = (float) $wforimg;
	} else {
		$params['wforimg'] = $pagewidth - ((float) $params['xforimgstart'] + 16);
		if ($params['wforimg'] <= 0) {
			$params['wforimg'] = 80;
		}
	}
}

/**
 * Finalize an external online signature source.
 *
 * @param	array<string,mixed>	$sourceDefinition	Online signature source definition
 * @param	CommonObject		$object				Signed object
 * @param	string				$online_sign_name	Signer name
 * @param	string				$signaturefile		Full path to the signature image
 * @param	string				$signedfile			Full path to the signed PDF
 * @param	string				$response			Response message
 * @return	int										1 if OK, -1 if KO
 */
function onlineSignatureFinalizeObject($sourceDefinition, $object, $online_sign_name, $signaturefile, $signedfile, &$response)
{
	global $db, $hookmanager, $action;

	$online_sign_ip = getUserRemoteIP();
	$parameters = array(
		'source' => empty($sourceDefinition['source']) ? '' : (string) $sourceDefinition['source'],
		'source_definition' => $sourceDefinition,
		'signaturefile' => $signaturefile,
		'signedfile' => $signedfile,
		'online_sign_name' => $online_sign_name,
		'online_sign_ip' => $online_sign_ip,
	);
	$reshook = $hookmanager->executeHooks('completeOnlineSignature', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	if ($reshook < 0) {
		$response = $hookmanager->error ? $hookmanager->error : "error in completeOnlineSignature hook";
		setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
		return -1;
	}
	if ($reshook > 0) {
		return 1;
	}

	if (!empty($sourceDefinition['signed_status_method']) && method_exists($object, (string) $sourceDefinition['signed_status_method']) && array_key_exists('signed_status_value', $sourceDefinition)) {
		$method = (string) $sourceDefinition['signed_status_method'];
		$signedtrigger = empty($sourceDefinition['signed_trigger']) ? '' : (string) $sourceDefinition['signed_trigger'];
		if (empty($object->context) || !is_array($object->context)) {
			$object->context = array();
		}
		$object->context['closedfromonlinesignature'] = 'closedfromonlinesignature';
		$object->context['trigger_reason'] = 'online_signature';
		$object->context['online_sign_name'] = $online_sign_name;
		$object->context['online_sign_ip'] = $online_sign_ip;

		$signatureuser = new User($db);
		$result = $object->$method($signatureuser, $sourceDefinition['signed_status_value'], 0, $signedtrigger);
		if ($result < 0) {
			$response = empty($object->error) ? "error signed_status_method" : $object->error;
			return -1;
		}

		return 1;
	}

	$response = "error online_signature_source_has_no_finalizer";
	return -1;
}

/**
 * Insert the generic online signature trace.
 *
 * @param	array<string,mixed>	$sourceDefinition	Online signature source definition
 * @param	CommonObject		$object				Signed object
 * @param	string				$name				Signer name
 * @param	string				$ip					Signer IP
 * @param	string				$pathoffile			Signed file path
 * @return	int										1 if OK, -1 if KO
 */
function onlineSignatureRecordTrace($sourceDefinition, $object, $name, $ip, $pathoffile)
{
	global $db, $conf;

	$objecttype = empty($sourceDefinition['elementtype']) ? (empty($object->element) ? '' : (string) $object->element) : (string) $sourceDefinition['elementtype'];
	$objecttype = substr($objecttype, 0, 32);
	$entity = empty($object->entity) ? (int) $conf->entity : (int) $object->entity;

	$storedpath = str_replace('\\', '/', $pathoffile);
	$dataroot = str_replace('\\', '/', rtrim(DOL_DATA_ROOT, '/\\')) . '/';
	if (strpos($storedpath, $dataroot) === 0) {
		$storedpath = substr($storedpath, strlen($dataroot));
	}
	$storedpath = substr($storedpath, 0, 255);

	$sql = "INSERT INTO " . MAIN_DB_PREFIX . "onlinesignature";
	$sql .= " (entity, object_type, object_id, datec, name, ip, pathoffile)";
	$sql .= " VALUES (";
	$sql .= ((int) $entity);
	$sql .= ", '" . $db->escape($objecttype) . "'";
	$sql .= ", " . ((int) $object->id);
	$sql .= ", '" . $db->idate(dol_now()) . "'";
	$sql .= ", '" . $db->escape($name) . "'";
	$sql .= ", '" . $db->escape($ip) . "'";
	$sql .= ", '" . $db->escape($storedpath) . "'";
	$sql .= ")";

	$resql = $db->query($sql);
	if (!$resql) {
		dol_syslog("Failed to insert online signature trace: " . $db->lasterror(), LOG_WARNING);
		return -1;
	}

	return 1;
}


/**
 * Output the signature file into the PDF object.
 *
 * @param 	TCPDF 		$pdf		PDF handler
 * @param	Translate	$langs		Language
 * @param	array<string,int|float|string|mixed[]>		$params		Array of params
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
	$pdf->MultiCell($wforimg, 4, $langs->transnoentities("Signature") . ': ' . dol_print_date(dol_now(), "day", false, $langs, true). ' - '.$params['online_sign_name'], 0, 'L');
	//$pdf->SetXY($xforimgstart, $yforimgstart + round($wforimg / 4));
	//$pdf->MultiCell($wforimg, 4, $langs->trans("Lastname") . ': ' . $online_sign_name, 0, 'L');

	$pdf->Image($params['pathtoimage'], $xforimgstart, $yforimgstart, $wforimg, round($wforimg / 4));

	return;
}
