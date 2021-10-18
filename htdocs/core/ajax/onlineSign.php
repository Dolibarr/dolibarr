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
if (!defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
}
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
include '../../main.inc.php';

$action = GETPOST('action', 'aZ09');
$signature = GETPOST('signaturebase64');
$ref = GETPOST('ref', 'aZ09');
$mode = GETPOST('mode', 'aZ09');
$error = 0;
$response = "";
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
		$upload_dir = DOL_DATA_ROOT."/".$mode."/".$ref."/";
		$date = dol_print_date(dol_now(), "%Y%m%d%H%M%S");
		$filename = "signatures/".$date."_signature.png";
		if (!is_dir($upload_dir."signatures/")) {
			if (!mkdir($upload_dir."signatures/")) {
				$response ="error mkdir";
				$error++;
			}
		}
		if (!$error) {
			$return = file_put_contents($upload_dir.$filename, $data);
			if ($return == false) {
				$response = 'error file_put_content';
			} else {
				if ($mode == "propale") {
					require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
					require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
					$object = new Propal($db);
					$object->fetch(0, $ref);

					$pdf = pdf_getInstance();
					$pdf->Open();
					$pdf->AddPage();
					$pagecount = $pdf->setSourceFile($upload_dir.$ref.".pdf");

					$tppl = $pdf->importPage(1);
					$pdf->useTemplate($tppl);
					$pdf->Image($upload_dir.$filename, 129, 239.6, 60, 15);
					$pdf->Close();
					$pdf->Output($upload_dir.$ref."_signed-".$date.".pdf", "F");

					$sql  = "UPDATE ".MAIN_DB_PREFIX."propal";
					$sql .= " SET fk_statut = ".((int) $object::STATUS_SIGNED).", note_private = '".$object->note_private."', date_signature='".$db->idate(dol_now())."'";
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
						setEventMessage("PropalSigned");
					} else {
						$db->rollback();
						$response = "error sql";
					}
				}
			}
		}
	} else {
		$response = 'error signature_not_found';
	}
}
echo $response;
