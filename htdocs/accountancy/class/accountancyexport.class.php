<?php
/*
 * Copyright (C) 2007-2012  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2014       Juanjo Menent       <jmenent@2byte.es>
 * Copyright (C) 2015       Florian Henry       <florian.henry@open-concept.pro>
 * Copyright (C) 2015       Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2016       Pierre-Henry Favre  <phf@atm-consulting.fr>
 * Copyright (C) 2016-2024  Alexandre Spangaro  <aspangaro@open-dsi.fr>
 * Copyright (C) 2022  		Lionel Vessiller    <lvessiller@open-dsi.fr>
 * Copyright (C) 2013-2017  Olivier Geffroy     <jeff@jeffinfo.com>
 * Copyright (C) 2017       Elarifr. Ari Elbaz  <github@accedinfo.com>
 * Copyright (C) 2017-2019  Frédéric France     <frederic.france@netlogic.fr>
 * Copyright (C) 2017       André Schild        <a.schild@aarboard.ch>
 * Copyright (C) 2020       Guillaume Alexandre <guillaume@tag-info.fr>
 * Copyright (C) 2022		Joachim Kueter		<jkueter@gmx.de>
 * Copyright (C) 2022  		Progiseize         	<a.bisotti@progiseize.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file		htdocs/accountancy/class/accountancyexport.class.php
 * \ingroup		Accountancy (Double entries)
 * \brief 		Class accountancy export
 */

require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

/**
 * Manage the different format accountancy export
 */
class AccountancyExport
{
	// Type of export. Used into $conf->global->ACCOUNTING_EXPORT_MODELCSV
	public static $EXPORT_TYPE_CONFIGURABLE = 1; // CSV
	public static $EXPORT_TYPE_AGIRIS = 10;
	public static $EXPORT_TYPE_EBP = 15;
	public static $EXPORT_TYPE_CEGID = 20;
	public static $EXPORT_TYPE_COGILOG = 25;
	public static $EXPORT_TYPE_COALA = 30;
	public static $EXPORT_TYPE_BOB50 = 35;
	public static $EXPORT_TYPE_CIEL = 40;
	public static $EXPORT_TYPE_SAGE50_SWISS = 45;
	public static $EXPORT_TYPE_CHARLEMAGNE = 50;
	public static $EXPORT_TYPE_QUADRATUS = 60;
	public static $EXPORT_TYPE_WINFIC = 70;
	public static $EXPORT_TYPE_OPENCONCERTO = 100;
	public static $EXPORT_TYPE_LDCOMPTA = 110;
	public static $EXPORT_TYPE_LDCOMPTA10 = 120;
	public static $EXPORT_TYPE_GESTIMUMV3 = 130;
	public static $EXPORT_TYPE_GESTIMUMV5 = 135;
	public static $EXPORT_TYPE_ISUITEEXPERT = 200;
	// Generic FEC after that
	public static $EXPORT_TYPE_FEC = 1000;
	public static $EXPORT_TYPE_FEC2 = 1010;

	/**
	 * @var DoliDB	Database handler
	 */
	public $db;

	/**
	 * @var string[] Error codes (or messages)
	 */
	public $errors = array();

	/**
	 *
	 * @var string Separator
	 */
	public $separator = '';

	/**
	 *
	 * @var string End of line
	 */
	public $end_line = '';

	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		global $conf, $hookmanager;

		$this->db = $db;
		$this->separator = $conf->global->ACCOUNTING_EXPORT_SEPARATORCSV;
		$this->end_line = getDolGlobalString('ACCOUNTING_EXPORT_ENDLINE') ? (getDolGlobalInt('ACCOUNTING_EXPORT_ENDLINE') == 1 ? "\n" : "\r\n") : "\n";

		$hookmanager->initHooks(array('accountancyexport'));
	}

	/**
	 * Array with all export type available (key + label)
	 *
	 * @return array of type
	 */
	public function getType()
	{
		global $langs, $hookmanager;

		$listofexporttypes = array(
			self::$EXPORT_TYPE_CONFIGURABLE => $langs->trans('Modelcsv_configurable'),
			self::$EXPORT_TYPE_CEGID => $langs->trans('Modelcsv_CEGID'),
			self::$EXPORT_TYPE_COALA => $langs->trans('Modelcsv_COALA'),
			self::$EXPORT_TYPE_BOB50 => $langs->trans('Modelcsv_bob50'),
			self::$EXPORT_TYPE_CIEL => $langs->trans('Modelcsv_ciel'),
			self::$EXPORT_TYPE_QUADRATUS => $langs->trans('Modelcsv_quadratus'),
			self::$EXPORT_TYPE_WINFIC => $langs->trans('Modelcsv_winfic'),
			self::$EXPORT_TYPE_EBP => $langs->trans('Modelcsv_ebp'),
			self::$EXPORT_TYPE_COGILOG => $langs->trans('Modelcsv_cogilog'),
			self::$EXPORT_TYPE_AGIRIS => $langs->trans('Modelcsv_agiris'),
			self::$EXPORT_TYPE_OPENCONCERTO => $langs->trans('Modelcsv_openconcerto'),
			self::$EXPORT_TYPE_SAGE50_SWISS => $langs->trans('Modelcsv_Sage50_Swiss'),
			self::$EXPORT_TYPE_CHARLEMAGNE => $langs->trans('Modelcsv_charlemagne'),
			self::$EXPORT_TYPE_LDCOMPTA => $langs->trans('Modelcsv_LDCompta'),
			self::$EXPORT_TYPE_LDCOMPTA10 => $langs->trans('Modelcsv_LDCompta10'),
			self::$EXPORT_TYPE_GESTIMUMV3 => $langs->trans('Modelcsv_Gestinumv3'),
			self::$EXPORT_TYPE_GESTIMUMV5 => $langs->trans('Modelcsv_Gestinumv5'),
			self::$EXPORT_TYPE_FEC => $langs->trans('Modelcsv_FEC'),
			self::$EXPORT_TYPE_FEC2 => $langs->trans('Modelcsv_FEC2'),
			self::$EXPORT_TYPE_ISUITEEXPERT => 'Export iSuite Expert',
		);

		// allow modules to define export formats
		$parameters = array();
		$reshook = $hookmanager->executeHooks('getType', $parameters, $listofexporttypes);

		ksort($listofexporttypes, SORT_NUMERIC);

		return $listofexporttypes;
	}

	/**
	 * Return string to summarize the format (Used to generated export filename)
	 *
	 * @param	int		$type		Format id
	 * @return 	string				Format code
	 */
	public static function getFormatCode($type)
	{
		$formatcode = array(
			self::$EXPORT_TYPE_CONFIGURABLE => 'csv',
			self::$EXPORT_TYPE_CEGID => 'cegid',
			self::$EXPORT_TYPE_COALA => 'coala',
			self::$EXPORT_TYPE_BOB50 => 'bob50',
			self::$EXPORT_TYPE_CIEL => 'ciel',
			self::$EXPORT_TYPE_QUADRATUS => 'quadratus',
			self::$EXPORT_TYPE_WINFIC => 'winfic',
			self::$EXPORT_TYPE_EBP => 'ebp',
			self::$EXPORT_TYPE_COGILOG => 'cogilog',
			self::$EXPORT_TYPE_AGIRIS => 'agiris',
			self::$EXPORT_TYPE_OPENCONCERTO => 'openconcerto',
			self::$EXPORT_TYPE_SAGE50_SWISS => 'sage50ch',
			self::$EXPORT_TYPE_CHARLEMAGNE => 'charlemagne',
			self::$EXPORT_TYPE_LDCOMPTA => 'ldcompta',
			self::$EXPORT_TYPE_LDCOMPTA10 => 'ldcompta10',
			self::$EXPORT_TYPE_GESTIMUMV3 => 'gestimumv3',
			self::$EXPORT_TYPE_GESTIMUMV5 => 'gestimumv5',
			self::$EXPORT_TYPE_FEC => 'fec',
			self::$EXPORT_TYPE_FEC2 => 'fec2',
			self::$EXPORT_TYPE_ISUITEEXPERT => 'isuiteexpert',
		);

		global $hookmanager;
		$code = $formatcode[$type];
		$parameters = array('type' => $type);
		$reshook = $hookmanager->executeHooks('getFormatCode', $parameters, $code);

		return $code;
	}

	/**
	 * Array with all export type available (key + label) and parameters for config
	 *
	 * @return array of type
	 */
	public function getTypeConfig()
	{
		global $conf, $langs;

		$exporttypes = array(
			'param' => array(
				self::$EXPORT_TYPE_CONFIGURABLE => array(
					'label' => $langs->trans('Modelcsv_configurable'),
					'ACCOUNTING_EXPORT_FORMAT' => getDolGlobalString('ACCOUNTING_EXPORT_FORMAT', 'txt'),
					'ACCOUNTING_EXPORT_SEPARATORCSV' => getDolGlobalString('ACCOUNTING_EXPORT_SEPARATORCSV', ','),
					'ACCOUNTING_EXPORT_ENDLINE' => getDolGlobalString('ACCOUNTING_EXPORT_ENDLINE', 1),
					'ACCOUNTING_EXPORT_DATE' => getDolGlobalString('ACCOUNTING_EXPORT_DATE', '%Y-%m-%d'),
				),
				self::$EXPORT_TYPE_CEGID => array(
					'label' => $langs->trans('Modelcsv_CEGID'),
				),
				self::$EXPORT_TYPE_COALA => array(
					'label' => $langs->trans('Modelcsv_COALA'),
				),
				self::$EXPORT_TYPE_BOB50 => array(
					'label' => $langs->trans('Modelcsv_bob50'),
				),
				self::$EXPORT_TYPE_CIEL => array(
					'label' => $langs->trans('Modelcsv_ciel'),
					'ACCOUNTING_EXPORT_FORMAT' => 'txt',
				),
				self::$EXPORT_TYPE_QUADRATUS => array(
					'label' => $langs->trans('Modelcsv_quadratus'),
					'ACCOUNTING_EXPORT_FORMAT' => 'txt',
				),
				self::$EXPORT_TYPE_WINFIC => array(
					'label' => $langs->trans('Modelcsv_winfic'),
					'ACCOUNTING_EXPORT_FORMAT' => 'txt',
				),
				self::$EXPORT_TYPE_EBP => array(
					'label' => $langs->trans('Modelcsv_ebp'),
				),
				self::$EXPORT_TYPE_COGILOG => array(
					'label' => $langs->trans('Modelcsv_cogilog'),
				),
				self::$EXPORT_TYPE_AGIRIS => array(
					'label' => $langs->trans('Modelcsv_agiris'),
				),
				self::$EXPORT_TYPE_OPENCONCERTO => array(
					'label' => $langs->trans('Modelcsv_openconcerto'),
				),
				self::$EXPORT_TYPE_SAGE50_SWISS => array(
					'label' => $langs->trans('Modelcsv_Sage50_Swiss'),
				),
				self::$EXPORT_TYPE_CHARLEMAGNE => array(
					'label' => $langs->trans('Modelcsv_charlemagne'),
					'ACCOUNTING_EXPORT_FORMAT' => 'txt',
				),
				self::$EXPORT_TYPE_LDCOMPTA => array(
					'label' => $langs->trans('Modelcsv_LDCompta'),
				),
				self::$EXPORT_TYPE_LDCOMPTA10 => array(
					'label' => $langs->trans('Modelcsv_LDCompta10'),
				),
				self::$EXPORT_TYPE_GESTIMUMV3 => array(
					'label' => $langs->trans('Modelcsv_Gestinumv3'),
					'ACCOUNTING_EXPORT_FORMAT' => 'txt',
				),
				self::$EXPORT_TYPE_GESTIMUMV5 => array(
					'label' => $langs->trans('Modelcsv_Gestinumv5'),
					'ACCOUNTING_EXPORT_FORMAT' => 'txt',
				),
				self::$EXPORT_TYPE_FEC => array(
					'label' => $langs->trans('Modelcsv_FEC'),
					'ACCOUNTING_EXPORT_FORMAT' => 'txt',
				),
				self::$EXPORT_TYPE_FEC2 => array(
					'label' => $langs->trans('Modelcsv_FEC2'),
					'ACCOUNTING_EXPORT_FORMAT' => 'txt',
				),
				self::$EXPORT_TYPE_ISUITEEXPERT => array(
					'label' => 'iSuite Expert',
					'ACCOUNTING_EXPORT_FORMAT' => 'csv',
				),
			),
			'cr'=> array(
				'1' => $langs->trans("Unix"),
				'2' => $langs->trans("Windows")
			),
			'format' => array(
				'csv' => $langs->trans("csv"),
				'txt' => $langs->trans("txt")
			),
		);

		global $hookmanager;
		$parameters = array();
		$reshook = $hookmanager->executeHooks('getTypeConfig', $parameters, $exporttypes);
		return $exporttypes;
	}


	/**
	 * Return the MIME type of a file
	 *
	 * @param	int		$formatexportset	Id of export format
	 * @return 	string						MIME type.
	 */
	public function getMimeType($formatexportset)
	{
		$mime = 'text/csv';

		switch ($formatexportset) {
			case self::$EXPORT_TYPE_FEC:
				$mime = 'text/tab-separated-values';
				break;
			default:
				$mime = 'text/csv';
				break;
		}

		return $mime;
	}

	/**
	 * Function who chose which export to use with the default config, and make the export into a file
	 *
	 * @param 	array	$TData 						Array with data
	 * @param	int		$formatexportset			Id of export format
	 * @param	int		$withAttachment				[=0] Not add files
	 * 												or 1 to have attached in an archive (ex : Quadratus) - Force output mode to write in a file (output mode = 1)
	 * @param	int		$downloadMode				[=0] Direct download
	 * 												or 1 to download after writing files - Forced by default when use withAttachment = 1
	 * 												or -1 not to download files
	 * @param	int		$outputMode					[=0] Print on screen
	 * 												or 1 to write in file and uses a temp directory - Forced by default when use withAttachment = 1
	 * 												or 2 to write in file a default export directory (accounting/export/)
	 * @return 	int		Return integer <0 if KO, >0 OK
	 */
	public function export(&$TData, $formatexportset, $withAttachment = 0, $downloadMode = 0, $outputMode = 0)
	{
		global $conf, $langs;
		global $search_date_end; // Used into /accountancy/tpl/export_journal.tpl.php

		// Define name of file to save
		$filename = 'general_ledger-'.$this->getFormatCode($formatexportset);
		$type_export = 'general_ledger';

		global $db; // The tpl file use $db
		$completefilename = '';
		$exportFile = null;
		$exportFileName = '';
		$exportFilePath = '';
		$exportFileFullName ='';
		$downloadFileMimeType = '';
		$downloadFileFullName = '';
		$downloadFilePath = '';
		$archiveFullName = '';
		$archivePath = '';
		$archiveFileList = array();
		if ($withAttachment == 1) {
			if ($downloadMode == 0) {
				$downloadMode = 1; // force to download after writing all files (can't use direct download)
			}
			if ($outputMode == 0) {
				$outputMode = 1; // force to put files in a temp directory (can't use print on screen)
			}

			// PHP ZIP extension must be enabled
			if (!extension_loaded('zip')) {
				$langs->load('install');
				$this->errors[] = $langs->trans('ErrorPHPDoesNotSupport', 'ZIP');
				return -1;
			}
		}

		$mimetype = $this->getMimeType($formatexportset);
		if ($downloadMode == 0) {
			// begin to print header for direct download
			top_httphead($mimetype, 1);
		}
		include DOL_DOCUMENT_ROOT.'/accountancy/tpl/export_journal.tpl.php';
		if ($outputMode == 1 || $outputMode == 2) {
			if ($outputMode == 1) {
				// uses temp directory by default to write files
				if (!empty($conf->accounting->multidir_temp[$conf->entity])) {
					$outputDir = $conf->accounting->multidir_temp[$conf->entity];
				} else {
					$outputDir = $conf->accounting->dir_temp;
				}
			} else {
				// uses default export directory "accounting/export"
				if (!empty($conf->accounting->multidir_output[$conf->entity])) {
					$outputDir = $conf->accounting->multidir_output[$conf->entity];
				} else {
					$outputDir = $conf->accounting->dir_output;
				}

				// directory already created when module is enabled
				$outputDir .= '/export';
				$outputDir .= '/'.dol_sanitizePathName($formatexportset);
			}

			if (!dol_is_dir($outputDir)) {
				if (dol_mkdir($outputDir) < 0) {
					$this->errors[] = $langs->trans('ErrorCanNotCreateDir', $outputDir);
					return -1;
				}
			}

			if ($outputDir != '') {
				if (!dol_is_dir($outputDir)) {
					$langs->load('errors');
					$this->errors[] = $langs->trans('ErrorDirNotFound', $outputDir);
					return -1;
				}

				if (!empty($completefilename)) {
					// create export file
					$exportFileFullName = $completefilename;
					$exportFileBaseName = basename($exportFileFullName);
					$exportFileName = pathinfo($exportFileBaseName, PATHINFO_FILENAME);
					$exportFilePath = $outputDir . '/' . $exportFileFullName;
					$exportFile = fopen($exportFilePath, 'w');
					if (!$exportFile) {
						$this->errors[] = $langs->trans('ErrorFileNotFound', $exportFilePath);
						return -1;
					}

					if ($withAttachment == 1) {
						$archiveFileList[0] = array(
							'path' => $exportFilePath,
							'name' => $exportFileFullName,
						);

						// archive name and path
						$archiveFullName = $exportFileName . '.zip';
						$archivePath = $outputDir . '/' . $archiveFullName;
					}
				}
			}
		}

		// export file (print on screen or write in a file) and prepare archive list if with attachment is set to 1
		switch ($formatexportset) {
			case self::$EXPORT_TYPE_CONFIGURABLE:
				$this->exportConfigurable($TData, $exportFile);
				break;
			case self::$EXPORT_TYPE_CEGID:
				$this->exportCegid($TData, $exportFile);
				break;
			case self::$EXPORT_TYPE_COALA:
				$this->exportCoala($TData, $exportFile);
				break;
			case self::$EXPORT_TYPE_BOB50:
				$this->exportBob50($TData, $exportFile);
				break;
			case self::$EXPORT_TYPE_CIEL:
				$this->exportCiel($TData, $exportFile);
				break;
			case self::$EXPORT_TYPE_QUADRATUS:
				$archiveFileList = $this->exportQuadratus($TData, $exportFile, $archiveFileList, $withAttachment);
				break;
			case self::$EXPORT_TYPE_WINFIC:
				$this->exportWinfic($TData, $exportFile);
				break;
			case self::$EXPORT_TYPE_EBP:
				$this->exportEbp($TData, $exportFile);
				break;
			case self::$EXPORT_TYPE_COGILOG:
				$this->exportCogilog($TData, $exportFile);
				break;
			case self::$EXPORT_TYPE_AGIRIS:
				$this->exportAgiris($TData, $exportFile);
				break;
			case self::$EXPORT_TYPE_OPENCONCERTO:
				$this->exportOpenConcerto($TData, $exportFile);
				break;
			case self::$EXPORT_TYPE_SAGE50_SWISS:
				$this->exportSAGE50SWISS($TData, $exportFile);
				break;
			case self::$EXPORT_TYPE_CHARLEMAGNE:
				$this->exportCharlemagne($TData, $exportFile);
				break;
			case self::$EXPORT_TYPE_LDCOMPTA:
				$this->exportLDCompta($TData, $exportFile);
				break;
			case self::$EXPORT_TYPE_LDCOMPTA10:
				$this->exportLDCompta10($TData, $exportFile);
				break;
			case self::$EXPORT_TYPE_GESTIMUMV3:
				$this->exportGestimumV3($TData, $exportFile);
				break;
			case self::$EXPORT_TYPE_GESTIMUMV5:
				$this->exportGestimumV5($TData, $exportFile);
				break;
			case self::$EXPORT_TYPE_FEC:
				$archiveFileList = $this->exportFEC($TData, $exportFile, $archiveFileList, $withAttachment);
				break;
			case self::$EXPORT_TYPE_FEC2:
				$archiveFileList = $this->exportFEC2($TData, $exportFile, $archiveFileList, $withAttachment);
				break;
			case self::$EXPORT_TYPE_ISUITEEXPERT:
				$this->exportiSuiteExpert($TData, $exportFile);
				break;
			default:
				global $hookmanager;
				$parameters = array('format' => $formatexportset);
				// file contents will be created in the hooked function via print
				$reshook = $hookmanager->executeHooks('export', $parameters, $TData);
				if ($reshook != 1) {
					$this->errors[] = $langs->trans('accountancy_error_modelnotfound');
				}
				break;
		}

		// create and download export file or archive
		if ($outputMode == 1 || $outputMode == 2) {
			$error = 0;

			// close export file
			if ($exportFile) {
				fclose($exportFile);
			}

			if ($withAttachment == 1) {
				// create archive file
				if (!empty($archiveFullName) && !empty($archivePath) && !empty($archiveFileList)) {
					// archive files
					$downloadFileMimeType = 'application/zip';
					$downloadFileFullName = $archiveFullName;
					$downloadFilePath = $archivePath;

					// create archive
					$archive = new ZipArchive();
					$res = $archive->open($archivePath, ZipArchive::OVERWRITE | ZipArchive::CREATE);
					if ($res !== true) {
						$error++;
						$this->errors[] = $langs->trans('ErrorFileNotFound', $archivePath);
					}
					if (!$error) {
						// add files
						foreach ($archiveFileList as $archiveFileArr) {
							$res = $archive->addFile($archiveFileArr['path'], $archiveFileArr['name']);
							if (!$res) {
								$error++;
								$this->errors[] = $langs->trans('ErrorArchiveAddFile', $archiveFileArr['name']);
								break;
							}
						}
					}
					if (!$error) {
						// close archive
						$archive->close();
					}
				}
			}

			if (!$error) {
				// download after writing files
				if ($downloadMode == 1) {
					if ($withAttachment == 0) {
						// only download exported file
						if (!empty($exportFileFullName) && !empty($exportFilePath)) {
							$downloadFileMimeType = $mimetype;
							$downloadFileFullName = $exportFileFullName;
							$downloadFilePath = $exportFilePath;
						}
					}

					// download export file or archive
					if (!empty($downloadFileMimeType) && !empty($downloadFileFullName) && !empty($downloadFilePath)) {
						header('Content-Type: ' . $downloadFileMimeType);
						header('Content-Disposition: attachment; filename=' . $downloadFileFullName);
						header('Cache-Control: Public, must-revalidate');
						header('Pragma: public');
						header('Content-Length: ' . dol_filesize($downloadFilePath));
						readfileLowMemory($downloadFilePath);
					}
				}
			}

			if ($error) {
				return -1;
			}
		}

		return 1;
	}


	/**
	 * Export format : CEGID
	 *
	 * @param 	array 		$objectLines 			data
	 * @param 	resource	$exportFile				[=null] File resource to export or print if null
	 * @return	void
	 */
	public function exportCegid($objectLines, $exportFile = null)
	{
		$separator = ";";
		$end_line = "\n";

		foreach ($objectLines as $line) {
			$date_document = dol_print_date($line->doc_date, '%d%m%Y');

			$tab = array();

			$tab[] = $date_document;
			$tab[] = $line->code_journal;
			$tab[] = length_accountg($line->numero_compte);
			$tab[] = length_accounta($line->subledger_account);
			$tab[] = $line->sens;
			$tab[] = price2fec(abs($line->debit - $line->credit));
			$tab[] = dol_string_unaccent($line->label_operation);
			$tab[] = dol_string_unaccent($line->doc_ref);

			$output = implode($separator, $tab).$end_line;
			if ($exportFile) {
				fwrite($exportFile, $output);
			} else {
				print $output;
			}
		}
	}

	/**
	 * Export format : COGILOG
	 * Last review for this format : 2022-07-12 Alexandre Spangaro (aspangaro@open-dsi.fr)
	 *
	 * @param 	array 		$objectLines 			data
	 * @param 	resource	$exportFile				[=null] File resource to export or print if null
	 * @return	void
	 */
	public function exportCogilog($objectLines, $exportFile = null)
	{
		$separator = "\t";
		$end_line = "\n";

		foreach ($objectLines as $line) {
			$date_document = dol_print_date($line->doc_date, '%d%m%Y');

			$refInvoice = '';
			if ($line->doc_type == 'customer_invoice') {
				// Customer invoice
				require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
				$invoice = new Facture($this->db);
				$invoice->fetch($line->fk_doc);

				$refInvoice = $invoice->ref;
			} elseif ($line->doc_type == 'supplier_invoice') {
				// Supplier invoice
				require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.facture.class.php';
				$invoice = new FactureFournisseur($this->db);
				$invoice->fetch($line->fk_doc);

				$refInvoice = $invoice->ref_supplier;
			}

			$tab = array();

			$tab[] = $line->code_journal;
			$tab[] = $date_document;
			$tab[] = $refInvoice;
			if (empty($line->subledger_account)) {
				$tab[] = length_accountg($line->numero_compte);
			} else {
				$tab[] = length_accounta($line->subledger_account);
			}
			$tab[] = "";
			$tab[] = $line->label_operation;
			$tab[] = $date_document;
			if ($line->sens == 'D') {
				$tab[] = price($line->debit);
				$tab[] = "";
			} elseif ($line->sens == 'C') {
				$tab[] = "";
				$tab[] = price($line->credit);
			}
			$tab[] = $line->doc_ref;
			$tab[] = $line->label_operation;

			$output = implode($separator, $tab).$end_line;
			if ($exportFile) {
				fwrite($exportFile, $output);
			} else {
				print $output;
			}
		}
	}

	/**
	 * Export format : COALA
	 *
	 * @param 	array 		$objectLines 			data
	 * @param 	resource	$exportFile				[=null] File resource to export or print if null
	 * @return 	void
	 */
	public function exportCoala($objectLines, $exportFile = null)
	{
		// Coala export
		$separator = ";";
		$end_line = "\n";

		foreach ($objectLines as $line) {
			$date_document = dol_print_date($line->doc_date, '%d/%m/%Y');

			$tab = array();

			$tab[] = $date_document;
			$tab[] = $line->code_journal;
			$tab[] = length_accountg($line->numero_compte);
			$tab[] = $line->piece_num;
			$tab[] = $line->doc_ref;
			$tab[] = price($line->debit);
			$tab[] = price($line->credit);
			$tab[] = 'E';
			$tab[] = length_accounta($line->subledger_account);

			$output = implode($separator, $tab).$end_line;
			if ($exportFile) {
				fwrite($exportFile, $output);
			} else {
				print $output;
			}
		}
	}

	/**
	 * Export format : BOB50
	 *
	 * @param 	array 		$objectLines 			data
	 * @param 	resource	$exportFile				[=null] File resource to export or print if null
	 * @return 	void
	 */
	public function exportBob50($objectLines, $exportFile = null)
	{
		// Bob50
		$separator = ";";
		$end_line = "\n";

		foreach ($objectLines as $line) {
			$date_document = dol_print_date($line->doc_date, '%d/%m/%Y');

			$tab = array();

			$tab[] = $line->piece_num;
			$tab[] = $date_document;

			if (empty($line->subledger_account)) {
				$tab[] = 'G';
				$tab[] = length_accountg($line->numero_compte);
			} else {
				if (substr($line->numero_compte, 0, 3) == '411') {
					$tab[] = 'C';
				}
				if (substr($line->numero_compte, 0, 3) == '401') {
					$tab[] = 'F';
				}
				$tab[] = length_accounta($line->subledger_account);
			}

			$tab[] = price($line->debit);
			$tab[] = price($line->credit);
			$tab[] = dol_trunc($line->label_operation, 32);

			$output = implode($separator, $tab).$end_line;
			if ($exportFile) {
				fwrite($exportFile, $output);
			} else {
				print $output;
			}
		}
	}

	/**
	 * Export format : CIEL (Format XIMPORT)
	 * Format since 2003 compatible CIEL version > 2002 / Sage50
	 * Last review for this format : 2021-09-13 Alexandre Spangaro (aspangaro@open-dsi.fr)
	 *
	 * Help : https://sage50c.online-help.sage.fr/aide-technique/
	 * In sage software | Use menu : "Exchange" > "Importing entries..."
	 *
	 * If you want to force filename to "XIMPORT.TXT" for automatically import file present in a directory :
	 * use constant ACCOUNTING_EXPORT_XIMPORT_FORCE_FILENAME
	 *
	 * @param 	array 		$objectLines 			data
	 * @param 	resource	$exportFile				[=null] File resource to export or print if null
	 * @return 	void
	 */
	public function exportCiel($objectLines, $exportFile = null)
	{
		$end_line = "\r\n";

		$i = 1;

		foreach ($objectLines as $line) {
			$code_compta = length_accountg($line->numero_compte);
			if (!empty($line->subledger_account)) {
				$code_compta = length_accounta($line->subledger_account);
			}

			$date_document = dol_print_date($line->doc_date, '%Y%m%d');
			$date_echeance = dol_print_date($line->date_lim_reglement, '%Y%m%d');

			$tab = array();

			$tab[] = str_pad($line->piece_num, 5);
			$tab[] = str_pad(self::trunc($line->code_journal, 2), 2);
			$tab[] = str_pad($date_document, 8, ' ', STR_PAD_LEFT);
			$tab[] = str_pad($date_echeance, 8, ' ', STR_PAD_LEFT);
			$tab[] = str_pad(self::trunc($line->doc_ref, 12), 12);
			$tab[] = str_pad(self::trunc($code_compta, 11), 11);
			$tab[] = str_pad(self::trunc(dol_string_unaccent($line->doc_ref).dol_string_unaccent($line->label_operation), 25), 25);
			$tab[] = str_pad(price2fec(abs($line->debit - $line->credit)), 13, ' ', STR_PAD_LEFT);
			$tab[] = str_pad($line->sens, 1);
			$tab[] = str_repeat(' ', 18); // Analytical accounting - Not managed in Dolibarr
			$tab[] = str_pad(self::trunc(dol_string_unaccent($line->label_operation), 34), 34);
			$tab[] = 'O2003'; // 0 = EUR | 2003 = Format Ciel

			$output = implode($tab).$end_line;
			if ($exportFile) {
				fwrite($exportFile, $output);
			} else {
				print $output;
			}
			$i++;
		}
	}

	/**
	 * Export format : Quadratus (Format ASCII)
	 * Format since 2015 compatible QuadraCOMPTA
	 * Last review for this format : 2023/10/12 Alexandre Spangaro (aspangaro@open-dsi.fr)
	 *
	 * Information on format: https://docplayer.fr/20769649-Fichier-d-entree-ascii-dans-quadracompta.html
	 * Help to import in Quadra: https://wiki.dolibarr.org/index.php?title=Module_Comptabilit%C3%A9_en_Partie_Double#Import_vers_CEGID_Quadra
	 * In QuadraCompta | Use menu : "Outils" > "Suivi des dossiers" > "Import ASCII(Compta)"
	 *
	 * @param 	array 		$objectLines 			data
	 * @param 	resource	$exportFile				[=null] File resource to export or print if null
	 * @param 	array		$archiveFileList		[=array()] Archive file list : array of ['path', 'name']
	 * @param 	int			$withAttachment			[=0] Not add files or 1 to have attached in an archive
	 * @return	array		Archive file list : array of ['path', 'name']
	 */
	public function exportQuadratus($objectLines, $exportFile = null, $archiveFileList = array(), $withAttachment = 0)
	{
		global $conf, $db;

		$end_line = "\r\n";

		// We should use dol_now function not time however this is wrong date to transfert in accounting
		foreach ($objectLines as $line) {
			// Clean some data
			$line->doc_ref = dol_string_unaccent($line->doc_ref);

			$line->label_operation = str_replace(array("\t", "\n", "\r"), " ", $line->label_operation);
			$line->label_operation = str_replace(array("- ", "…", "..."), "", $line->label_operation);
			$line->label_operation = dol_string_unaccent($line->label_operation);

			$line->numero_compte = dol_string_unaccent($line->numero_compte);
			$line->label_compte = dol_string_unaccent($line->label_compte);
			$line->subledger_account = dol_string_unaccent($line->subledger_account);

			$line->subledger_label = str_replace(array("- ", "…", "..."), "", $line->subledger_label);
			$line->subledger_label = dol_string_unaccent($line->subledger_label);

			$code_compta = $line->numero_compte;
			if (!empty($line->subledger_account)) {
				$code_compta = $line->subledger_account;
			}

			$tab = array();

			if (!empty($line->subledger_account)) {
				$tab['type_ligne'] = 'C';
				$tab['num_compte'] = str_pad(self::trunc($line->subledger_account, 8), 8);
				$tab['lib_compte'] = str_pad(self::trunc($line->subledger_label, 30), 30);

				if ($line->doc_type == 'customer_invoice') {
					$tab['lib_alpha'] = strtoupper(str_pad('C'.self::trunc(dol_string_unaccent($line->subledger_label), 6), 7));
					$tab['filler'] = str_repeat(' ', 52);
					$tab['coll_compte'] = str_pad(self::trunc($conf->global->ACCOUNTING_ACCOUNT_CUSTOMER, 8), 8);
				} elseif ($line->doc_type == 'supplier_invoice') {
					$tab['lib_alpha'] = strtoupper(str_pad('F'.self::trunc(dol_string_unaccent($line->subledger_label), 6), 7));
					$tab['filler'] = str_repeat(' ', 52);
					$tab['coll_compte'] = str_pad(self::trunc($conf->global->ACCOUNTING_ACCOUNT_SUPPLIER, 8), 8);
				} else {
					$tab['filler'] = str_repeat(' ', 59);
					$tab['coll_compte'] = str_pad(' ', 8);
				}

				$tab['filler2'] = str_repeat(' ', 110);
				$tab['Maj'] = 2; // Partial update (alpha key, label, address, collectif, RIB)

				if ($line->doc_type == 'customer_invoice') {
					$tab['type_compte'] = 'C';
				} elseif ($line->doc_type == 'supplier_invoice') {
					$tab['type_compte'] = 'F';
				} else {
					$tab['type_compte'] = 'G';
				}

				$tab['filler3'] = str_repeat(' ', 235);

				$tab['end_line'] = $end_line;

				if ($exportFile) {
					fwrite($exportFile, implode($tab));
				} else {
					print implode($tab);
				}
			}

			$tab = array();
			$tab['type_ligne'] = 'M';
			$tab['num_compte'] = str_pad(self::trunc($code_compta, 8), 8);
			$tab['code_journal'] = str_pad(self::trunc($line->code_journal, 2), 2);
			$tab['folio'] = '000';

			// We use invoice date $line->doc_date not $date_ecriture which is the transfert date
			// maybe we should set an option for customer who prefer to keep in accounting software the tranfert date instead of invoice date ?
			//$tab['date_ecriture'] = $date_ecriture;
			$tab['date_ecriture'] = dol_print_date($line->doc_date, '%d%m%y');
			$tab['filler'] = ' ';
			$tab['libelle_ecriture'] = str_pad(self::trunc($line->doc_ref.' '.$line->label_operation, 20), 20);

			// Credit invoice - invert sens
			/*
			if ($line->montant < 0) {
				if ($line->sens == 'C') {
					$tab['sens'] = 'D';
				} else {
					$tab['sens'] = 'C';
				}
				$tab['signe_montant'] = '-';
			} else {
				$tab['sens'] = $line->sens; // C or D
				$tab['signe_montant'] = '+';
			}*/
			$tab['sens'] = $line->sens; // C or D
			$tab['signe_montant'] = '+';

			// The amount must be in centimes without decimal points.
			$tab['montant'] = str_pad(abs(($line->debit - $line->credit) * 100), 12, '0', STR_PAD_LEFT);
			$tab['contrepartie'] = str_repeat(' ', 8);

			// Force date format : %d%m%y
			if (!empty($line->date_lim_reglement)) {
				$tab['date_echeance'] = dol_print_date($line->date_lim_reglement, '%d%m%y'); // Format must be ddmmyy
			} else {
				$tab['date_echeance'] = '000000';
			}

			// Please keep quadra named field lettrage(2) + codestat(3) instead of fake lettrage(5)
			// $tab['lettrage'] = str_repeat(' ', 5);
			$tab['lettrage'] = str_repeat(' ', 2);
			$tab['codestat'] = str_repeat(' ', 3);
			$tab['num_piece'] = str_pad(self::trunc($line->piece_num, 5), 5);

			// Keep correct quadra named field instead of anon filler
			// $tab['filler2'] = str_repeat(' ', 20);
			$tab['affaire'] = str_repeat(' ', 10);
			$tab['quantity1'] = str_repeat(' ', 10);
			$tab['num_piece2'] = str_pad(self::trunc($line->piece_num, 8), 8);
			$tab['devis'] = str_pad($conf->currency, 3);
			$tab['code_journal2'] = str_pad(self::trunc($line->code_journal, 3), 3);
			$tab['filler3'] = str_repeat(' ', 3);

			// Keep correct quadra named field instead of anon filler libelle_ecriture2 is 30 char not 32 !!!!
			// as we use utf8, we must remove accent to have only one ascii char instead of utf8 2 chars for specials that report wrong line size that will exceed import format spec
			// TODO: we should filter more than only accent to avoid wrong line size
			// TODO: remove invoice number doc_ref in label,
			// TODO: we should offer an option for customer to build the label using invoice number / name / date in accounting software
			//$tab['libelle_ecriture2'] = str_pad(self::trunc($line->doc_ref . ' ' . $line->label_operation, 30), 30);
			$tab['libelle_ecriture2'] = str_pad(self::trunc($line->label_operation, 30), 30);
			$tab['codetva'] = str_repeat(' ', 2);

			// We need to keep the 10 lastest number of invoice doc_ref not the beginning part that is the unusefull almost same part
			// $tab['num_piece3'] = str_pad(self::trunc($line->piece_num, 10), 10);
			$tab['num_piece3'] = substr(self::trunc($line->doc_ref, 20), -10);
			$tab['reserved'] = str_repeat(' ', 10); // position 159
			$tab['currency_amount'] = str_repeat(' ', 13); // position 169
			// get document file
			$attachmentFileName = '';
			if ($withAttachment == 1) {
				$attachmentFileKey = trim($line->piece_num);

				if (!isset($archiveFileList[$attachmentFileKey])) {
					$objectDirPath = '';
					$objectFileName = dol_sanitizeFileName($line->doc_ref);
					if ($line->doc_type == 'customer_invoice') {
						$objectDirPath = !empty($conf->invoice->multidir_output[$conf->entity]) ? $conf->invoice->multidir_output[$conf->entity] : $conf->invoice->dir_output;
					} elseif ($line->doc_type == 'expense_report') {
						$objectDirPath = !empty($conf->expensereport->multidir_output[$conf->entity]) ? $conf->expensereport->multidir_output[$conf->entity] : $conf->expensereport->dir_output;
					} elseif ($line->doc_type == 'supplier_invoice') {
						require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
						$invoice = new FactureFournisseur($this->db);
						$invoice->fetch($line->fk_doc);
						$objectDirPath = !empty($conf->fournisseur->facture->multidir_output[$conf->entity]) ? $conf->fournisseur->facture->multidir_output[$conf->entity] : $conf->fournisseur->facture->dir_output;
						$objectDirPath.= '/'.rtrim(get_exdir($invoice->id, 2, 0, 0, $invoice, 'invoice_supplier'), '/');
					}
					$arrayofinclusion = array();
					// If it is a supplier invoice, we want to use last uploaded file
					$arrayofinclusion[] = '^'.preg_quote($objectFileName, '/').(($line->doc_type == 'supplier_invoice') ? '.+' : '').'\.pdf$';
					$fileFoundList = dol_dir_list($objectDirPath.'/'.$objectFileName, 'files', 0, implode('|', $arrayofinclusion), '(\.meta|_preview.*\.png)$', 'date', SORT_DESC, 0, true);
					if (!empty($fileFoundList)) {
						$attachmentFileNameTrunc = str_pad(self::trunc($line->piece_num, 8), 8, '0', STR_PAD_LEFT);
						foreach ($fileFoundList as $fileFound) {
							if (strstr($fileFound['name'], $objectFileName)) {
								// skip native invoice pdfs (canelle)
								// We want to retrieve an attachment representative of the supplier invoice, not a fake document generated by Dolibarr.
								if ($line->doc_type == 'supplier_invoice') {
									if ($fileFound['name'] === $objectFileName.'.pdf') {
										continue;
									}
								} elseif ($fileFound['name'] !== $objectFileName.'.pdf') {
									continue;
								}
								$fileFoundPath = $objectDirPath.'/'.$objectFileName.'/'.$fileFound['name'];
								if (file_exists($fileFoundPath)) {
									$archiveFileList[$attachmentFileKey] = array(
										'path' => $fileFoundPath,
										'name' => $attachmentFileNameTrunc.'.pdf',
									);
									break;
								}
							}
						}
					}
				}

				if (isset($archiveFileList[$attachmentFileKey])) {
					$attachmentFileName = $archiveFileList[$attachmentFileKey]['name'];
				}
			}
			if (dol_strlen($attachmentFileName) == 12) {
				$tab['attachment'] = $attachmentFileName; // position 182
			} else {
				$tab['attachment'] = str_repeat(' ', 12); // position 182
			}
			$tab['filler4'] = str_repeat(' ', 38);
			$tab['end_line'] = $end_line;

			if ($exportFile) {
				fwrite($exportFile, implode($tab));
			} else {
				print implode($tab);
			}
		}

		return $archiveFileList;
	}

	/**
	 * Export format : WinFic - eWinfic - WinSis Compta
	 * Last review for this format : 2022-11-01 Alexandre Spangaro (aspangaro@open-dsi.fr)
	 *
	 * Help : https://wiki.gestan.fr/lib/exe/fetch.php?media=wiki:v15:compta:accountancy-format_winfic-ewinfic-winsiscompta.pdf
	 *
	 * @param 	array 		$objectLines 			data
	 * @param 	resource	$exportFile				[=null] File resource to export or print if null
	 * @return 	void
	 */
	public function exportWinfic($objectLines, $exportFile = null)
	{
		global $conf;

		$end_line = "\r\n";
		$index = 1;

		// Warning ! When truncation is necessary, no dot because 3 dots = three characters. The columns are shifted

		foreach ($objectLines as $line) {
			$code_compta = $line->numero_compte;
			if (!empty($line->subledger_account)) {
				$code_compta = $line->subledger_account;
			}

			$tab = array();
			//$tab['type_ligne'] = 'M';
			$tab['code_journal'] = str_pad(dol_trunc($line->code_journal, 2, 'right', 'UTF-8', 1), 2);

			//We use invoice date $line->doc_date not $date_ecriture which is the transfert date
			//maybe we should set an option for customer who prefer to keep in accounting software the tranfert date instead of invoice date ?
			//$tab['date_ecriture'] = $date_ecriture;
			$tab['date_operation'] = dol_print_date($line->doc_date, '%d%m%Y');

			$tab['folio'] = '     1';

			$tab['num_ecriture'] = str_pad(dol_trunc($index, 6, 'right', 'UTF-8', 1), 6, ' ', STR_PAD_LEFT);

			$tab['jour_ecriture'] = dol_print_date($line->doc_date, '%d%m%y');

			$tab['num_compte'] = str_pad(dol_trunc($code_compta, 6, 'right', 'UTF-8', 1), 6, '0');

			if ($line->sens == 'D') {
				$tab['montant_debit']  = str_pad(number_format($line->debit, 2, ',', ''), 13, ' ', STR_PAD_LEFT);

				$tab['montant_crebit'] = str_pad(number_format(0, 2, ',', ''), 13, ' ', STR_PAD_LEFT);
			} else {
				$tab['montant_debit']  = str_pad(number_format(0, 2, ',', ''), 13, ' ', STR_PAD_LEFT);

				$tab['montant_crebit'] = str_pad(number_format($line->credit, 2, ',', ''), 13, ' ', STR_PAD_LEFT);
			}

			$tab['libelle_ecriture'] = str_pad(dol_trunc(dol_string_unaccent($line->doc_ref).' '.dol_string_unaccent($line->label_operation), 30, 'right', 'UTF-8', 1), 30);

			$tab['lettrage'] = str_repeat(dol_trunc($line->lettering_code, 2, 'left', 'UTF-8', 1), 2);

			$tab['code_piece'] = str_pad(dol_trunc($line->piece_num, 5, 'left', 'UTF-8', 1), 5, ' ', STR_PAD_LEFT);

			$tab['code_stat'] = str_repeat(' ', 4);

			if (!empty($line->date_lim_reglement)) {
				$tab['date_echeance'] = dol_print_date($line->date_lim_reglement, '%d%m%Y');
			} else {
				$tab['date_echeance'] = dol_print_date($line->doc_date, '%d%m%Y');
			}

			$tab['monnaie'] = '1';

			$tab['filler'] = ' ';

			$tab['ind_compteur'] = ' ';

			$tab['quantite'] = '0,000000000';

			$tab['code_pointage'] = str_repeat(' ', 2);

			$tab['end_line'] = $end_line;

			print implode('|', $tab);

			$index++;
		}
	}


	/**
	 * Export format : EBP
	 *
	 * @param 	array 		$objectLines 			data
	 * @param 	resource	$exportFile				[=null] File resource to export or print if null
	 * @return 	void
	 */
	public function exportEbp($objectLines, $exportFile = null)
	{
		$separator = ',';
		$end_line = "\n";

		foreach ($objectLines as $line) {
			$date_document = dol_print_date($line->doc_date, '%d%m%Y');

			$tab = array();

			$tab[] = $line->id;
			$tab[] = $date_document;
			$tab[] = $line->code_journal;
			if (empty($line->subledger_account)) {
				$tab[] = $line->numero_compte;
			} else {
				$tab[] = $line->subledger_account;
			}
			//$tab[] = substr(length_accountg($line->numero_compte), 0, 2) . $separator;
			$tab[] = '"'.dol_trunc($line->label_operation, 40, 'right', 'UTF-8', 1).'"';
			$tab[] = '"'.dol_trunc($line->piece_num, 15, 'right', 'UTF-8', 1).'"';
			$tab[] = price2num(abs($line->debit - $line->credit));
			$tab[] = $line->sens;
			$tab[] = $date_document;
			//print 'EUR';

			$output = implode($separator, $tab).$end_line;
			if ($exportFile) {
				fwrite($exportFile, $output);
			} else {
				print $output;
			}
		}
	}


	/**
	 * Export format : Agiris Isacompta
	 *
	 * @param 	array 		$objectLines 			data
	 * @param 	resource	$exportFile				[=null] File resource to export or print if null
	 * @return 	void
	 */
	public function exportAgiris($objectLines, $exportFile = null)
	{
		$separator = ';';
		$end_line = "\n";

		foreach ($objectLines as $line) {
			$date_document = dol_print_date($line->doc_date, '%d%m%Y');

			$tab = array();

			$tab[] = $line->piece_num;
			$tab[] = self::toAnsi($line->label_operation);
			$tab[] = $date_document;
			$tab[] = self::toAnsi($line->label_operation);

			if (empty($line->subledger_account)) {
				$tab[] = length_accountg($line->numero_compte);
				$tab[] = self::toAnsi($line->label_compte);
			} else {
				$tab[] = length_accounta($line->subledger_account);
				$tab[] = self::toAnsi($line->subledger_label);
			}

			$tab[] = self::toAnsi($line->doc_ref);
			$tab[] = price($line->debit);
			$tab[] = price($line->credit);
			$tab[] = price(abs($line->debit - $line->credit));
			$tab[] = $line->sens;
			$tab[] = $line->lettering_code;
			$tab[] = $line->code_journal;

			$output = implode($separator, $tab).$end_line;
			if ($exportFile) {
				fwrite($exportFile, $output);
			} else {
				print $output;
			}
		}
	}

	/**
	 * Export format : OpenConcerto
	 *
	 * @param 	array 		$objectLines 			data
	 * @param 	resource	$exportFile				[=null] File resource to export or print if null
	 * @return 	void
	 */
	public function exportOpenConcerto($objectLines, $exportFile = null)
	{
		$separator = ';';
		$end_line = "\n";

		foreach ($objectLines as $line) {
			$date_document = dol_print_date($line->doc_date, '%d/%m/%Y');

			$tab = array();

			$tab[] = $date_document;
			$tab[] = $line->code_journal;
			if (empty($line->subledger_account)) {
				$tab[] = length_accountg($line->numero_compte);
			} else {
				$tab[] = length_accounta($line->subledger_account);
			}
			$tab[] = $line->doc_ref;
			$tab[] = $line->label_operation;
			$tab[] = price($line->debit);
			$tab[] = price($line->credit);

			$output = implode($separator, $tab).$end_line;
			if ($exportFile) {
				fwrite($exportFile, $output);
			} else {
				print $output;
			}
		}
	}

	/**
	 * Export format : Configurable CSV
	 *
	 * @param 	array 		$objectLines 			data
	 * @param 	resource	$exportFile				[=null] File resource to export or print if null
	 * @return	void
	 */
	public function exportConfigurable($objectLines, $exportFile = null)
	{
		global $conf;

		$separator = $this->separator;

		foreach ($objectLines as $line) {
			$date_document = dol_print_date($line->doc_date, getDolGlobalString('ACCOUNTING_EXPORT_DATE'));

			$tab = array();
			// export configurable
			$tab[] = $line->piece_num;
			$tab[] = $date_document;
			$tab[] = $line->doc_ref;
			$tab[] = preg_match('/'.$separator.'/', $line->label_operation) ? "'".$line->label_operation."'" : $line->label_operation;
			$tab[] = length_accountg($line->numero_compte);
			$tab[] = length_accounta($line->subledger_account);
			$tab[] = price2num($line->debit);
			$tab[] = price2num($line->credit);
			$tab[] = price2num($line->debit - $line->credit);
			$tab[] = $line->code_journal;

			$output = implode($separator, $tab).$this->end_line;
			if ($exportFile) {
				fwrite($exportFile, $output);
			} else {
				print $output;
			}
		}
	}

	/**
	 * Export format : FEC
	 * Last review for this format : 2023/10/12 Alexandre Spangaro (aspangaro@open-dsi.fr)
	 *
	 * Help to import in your software: https://wiki.dolibarr.org/index.php?title=Module_Comptabilit%C3%A9_en_Partie_Double#Exports_avec_fichiers_sources
	 *
	 * @param 	array 		$objectLines 			data
	 * @param 	resource	$exportFile				[=null] File resource to export or print if null
	 * @param 	array		$archiveFileList		[=array()] Archive file list : array of ['path', 'name']
	 * @param 	int			$withAttachment			[=0] Not add files or 1 to have attached in an archive
	 * @return	array		Archive file list : array of ['path', 'name']
	 */
	public function exportFEC($objectLines, $exportFile = null, $archiveFileList = array(), $withAttachment = 0)
	{
		global $conf, $langs;

		$separator = "\t";
		$end_line = "\r\n";

		$tab = array();
		$tab[] = "JournalCode";
		$tab[] = "JournalLib";
		$tab[] = "EcritureNum";
		$tab[] = "EcritureDate";
		$tab[] = "CompteNum";
		$tab[] = "CompteLib";
		$tab[] = "CompAuxNum";
		$tab[] = "CompAuxLib";
		$tab[] = "PieceRef";
		$tab[] = "PieceDate";
		$tab[] = "EcritureLib";
		$tab[] = "Debit";
		$tab[] = "Credit";
		$tab[] = "EcritureLet";
		$tab[] = "DateLet";
		$tab[] = "ValidDate";
		$tab[] = "Montantdevise";
		$tab[] = "Idevise";
		$tab[] = "DateLimitReglmt";
		$tab[] = "NumFacture";
		$tab[] = "FichierFacture";

		$output = implode($separator, $tab).$end_line;
		if ($exportFile) {
			fwrite($exportFile, $output);
		} else {
			print $output;
		}

		foreach ($objectLines as $line) {
			if ($line->debit == 0 && $line->credit == 0) {
				//unset($array[$line]);
			} else {
				$date_creation = dol_print_date($line->date_creation, '%Y%m%d');
				$date_document = dol_print_date($line->doc_date, '%Y%m%d');
				$date_lettering = dol_print_date($line->date_lettering, '%Y%m%d');
				$date_validation = dol_print_date($line->date_validation, '%Y%m%d');
				$date_limit_payment = dol_print_date($line->date_lim_reglement, '%Y%m%d');

				$refInvoice = '';
				if ($line->doc_type == 'customer_invoice') {
					// Customer invoice
					require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
					$invoice = new Facture($this->db);
					$invoice->fetch($line->fk_doc);

					$refInvoice = $invoice->ref;
				} elseif ($line->doc_type == 'supplier_invoice') {
					// Supplier invoice
					require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
					$invoice = new FactureFournisseur($this->db);
					$invoice->fetch($line->fk_doc);

					$refInvoice = $invoice->ref_supplier;
				}

				$tab = array();

				// FEC:JournalCode
				$tab[] = $line->code_journal;

				// FEC:JournalLib
				$labeljournal = dol_string_unaccent($langs->transnoentities($line->journal_label));
				$labeljournal = dol_string_nospecial($labeljournal, ' ');
				$tab[] = $labeljournal;

				// FEC:EcritureNum
				$tab[] = $line->piece_num;

				// FEC:EcritureDate
				$tab[] = $date_document;

				// FEC:CompteNum
				$tab[] = length_accountg($line->numero_compte);

				// FEC:CompteLib
				$tab[] = dol_string_unaccent($line->label_compte);

				// FEC:CompAuxNum
				$tab[] = length_accounta($line->subledger_account);

				// FEC:CompAuxLib
				$tab[] = dol_string_unaccent($line->subledger_label);

				// FEC:PieceRef
				$tab[] = $line->doc_ref;

				// FEC:PieceDate
				$tab[] = dol_string_unaccent($date_creation);

				// FEC:EcritureLib
				// Clean label operation to prevent problem on export with tab separator & other character
				$line->label_operation = str_replace(array("\t", "\n", "\r"), " ", $line->label_operation);
				$line->label_operation = str_replace(array("..."), "", $line->label_operation);
				$tab[] = dol_string_unaccent($line->label_operation);

				// FEC:Debit
				$tab[] = price2fec($line->debit);

				// FEC:Credit
				$tab[] = price2fec($line->credit);

				// FEC:EcritureLet
				$tab[] = $line->lettering_code;

				// FEC:DateLet
				$tab[] = $date_lettering;

				// FEC:ValidDate
				$tab[] = $date_validation;

				// FEC:Montantdevise
				$tab[] = $line->multicurrency_amount;

				// FEC:Idevise
				$tab[] = $line->multicurrency_code;

				// FEC_suppl:DateLimitReglmt
				$tab[] = $date_limit_payment;

				// FEC_suppl:NumFacture
				// Clean ref invoice to prevent problem on export with tab separator & other character
				$refInvoice = str_replace(array("\t", "\n", "\r"), " ", $refInvoice);
				$tab[] = dol_trunc(self::toAnsi($refInvoice), 17, 'right', 'UTF-8', 1);

				// FEC_suppl:FichierFacture
				// get document file
				$attachmentFileName = '';
				if ($withAttachment == 1) {
					$attachmentFileKey = trim($line->piece_num);

					if (!isset($archiveFileList[$attachmentFileKey])) {
						$objectDirPath = '';
						$objectFileName = dol_sanitizeFileName($line->doc_ref);
						if ($line->doc_type == 'customer_invoice') {
							$objectDirPath = !empty($conf->invoice->multidir_output[$conf->entity]) ? $conf->invoice->multidir_output[$conf->entity] : $conf->invoice->dir_output;
						} elseif ($line->doc_type == 'expense_report') {
							$objectDirPath = !empty($conf->expensereport->multidir_output[$conf->entity]) ? $conf->expensereport->multidir_output[$conf->entity] : $conf->expensereport->dir_output;
						} elseif ($line->doc_type == 'supplier_invoice') {
							$objectDirPath = !empty($conf->fournisseur->facture->multidir_output[$conf->entity]) ? $conf->fournisseur->facture->multidir_output[$conf->entity] : $conf->fournisseur->facture->dir_output;
							$objectDirPath.= '/'.rtrim(get_exdir($invoice->id, 2, 0, 0, $invoice, 'invoice_supplier'), '/');
						}
						$arrayofinclusion = array();
						// If it is a supplier invoice, we want to use last uploaded file
						$arrayofinclusion[] = '^'.preg_quote($objectFileName, '/').(($line->doc_type == 'supplier_invoice') ? '.+' : '').'\.pdf$';
						$fileFoundList = dol_dir_list($objectDirPath.'/'.$objectFileName, 'files', 0, implode('|', $arrayofinclusion), '(\.meta|_preview.*\.png)$', 'date', SORT_DESC, 0, true);
						if (!empty($fileFoundList)) {
							$attachmentFileNameTrunc = $line->doc_ref;
							foreach ($fileFoundList as $fileFound) {
								if (strstr($fileFound['name'], $objectFileName)) {
									// skip native invoice pdfs (canelle)
									// We want to retrieve an attachment representative of the supplier invoice, not a fake document generated by Dolibarr.
									if ($line->doc_type == 'supplier_invoice') {
										if ($fileFound['name'] === $objectFileName.'.pdf') {
											continue;
										}
									} elseif ($fileFound['name'] !== $objectFileName.'.pdf') {
										continue;
									}
									$fileFoundPath = $objectDirPath.'/'.$objectFileName.'/'.$fileFound['name'];
									if (file_exists($fileFoundPath)) {
										$archiveFileList[$attachmentFileKey] = array(
											'path' => $fileFoundPath,
											'name' => $attachmentFileNameTrunc.'.pdf',
										);
										break;
									}
								}
							}
						}
					}

					if (isset($archiveFileList[$attachmentFileKey])) {
						$attachmentFileName = $archiveFileList[$attachmentFileKey]['name'];
					}
				}

				$tab[] = $attachmentFileName;

				$output = implode($separator, $tab).$end_line;
				if ($exportFile) {
					fwrite($exportFile, $output);
				} else {
					print $output;
				}
			}
		}

		return $archiveFileList;
	}

	/**
	 * Export format : FEC2
	 * Last review for this format : 2023/10/12 Alexandre Spangaro (aspangaro@open-dsi.fr)
	 *
	 * Help to import in your software: https://wiki.dolibarr.org/index.php?title=Module_Comptabilit%C3%A9_en_Partie_Double#Exports_avec_fichiers_sources
	 *
	 * @param 	array 		$objectLines 			data
	 * @param 	resource	$exportFile				[=null] File resource to export or print if null
	 * @param 	array		$archiveFileList		[=array()] Archive file list : array of ['path', 'name']
	 * @param 	int			$withAttachment			[=0] Not add files or 1 to have attached in an archive
	 * @return	array		Archive file list : array of ['path', 'name']
	 */
	public function exportFEC2($objectLines, $exportFile = null, $archiveFileList = array(), $withAttachment = 0)
	{
		global $conf, $langs;

		$separator = "\t";
		$end_line = "\r\n";

		$tab = array();
		$tab[] = "JournalCode";
		$tab[] = "JournalLib";
		$tab[] = "EcritureNum";
		$tab[] = "EcritureDate";
		$tab[] = "CompteNum";
		$tab[] = "CompteLib";
		$tab[] = "CompAuxNum";
		$tab[] = "CompAuxLib";
		$tab[] = "PieceRef";
		$tab[] = "PieceDate";
		$tab[] = "EcritureLib";
		$tab[] = "Debit";
		$tab[] = "Credit";
		$tab[] = "EcritureLet";
		$tab[] = "DateLet";
		$tab[] = "ValidDate";
		$tab[] = "Montantdevise";
		$tab[] = "Idevise";
		$tab[] = "DateLimitReglmt";
		$tab[] = "NumFacture";
		$tab[] = "FichierFacture";

		$output = implode($separator, $tab).$end_line;
		if ($exportFile) {
			fwrite($exportFile, $output);
		} else {
			print $output;
		}

		foreach ($objectLines as $line) {
			if ($line->debit == 0 && $line->credit == 0) {
				//unset($array[$line]);
			} else {
				$date_creation = dol_print_date($line->date_creation, '%Y%m%d');
				$date_document = dol_print_date($line->doc_date, '%Y%m%d');
				$date_lettering = dol_print_date($line->date_lettering, '%Y%m%d');
				$date_validation = dol_print_date($line->date_validation, '%Y%m%d');
				$date_limit_payment = dol_print_date($line->date_lim_reglement, '%Y%m%d');

				$refInvoice = '';
				if ($line->doc_type == 'customer_invoice') {
					// Customer invoice
					require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
					$invoice = new Facture($this->db);
					$invoice->fetch($line->fk_doc);

					$refInvoice = $invoice->ref;
				} elseif ($line->doc_type == 'supplier_invoice') {
					// Supplier invoice
					require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
					$invoice = new FactureFournisseur($this->db);
					$invoice->fetch($line->fk_doc);

					$refInvoice = $invoice->ref_supplier;
				}

				$tab = array();

				// FEC:JournalCode
				$tab[] = $line->code_journal;

				// FEC:JournalLib
				$labeljournal = dol_string_unaccent($langs->transnoentities($line->journal_label));
				$labeljournal = dol_string_nospecial($labeljournal, ' ');
				$tab[] = $labeljournal;

				// FEC:EcritureNum
				$tab[] = $line->piece_num;

				// FEC:EcritureDate
				$tab[] = $date_creation;

				// FEC:CompteNum
				$tab[] = length_accountg($line->numero_compte);

				// FEC:CompteLib
				$tab[] = dol_string_unaccent($line->label_compte);

				// FEC:CompAuxNum
				$tab[] = length_accounta($line->subledger_account);

				// FEC:CompAuxLib
				$tab[] = dol_string_unaccent($line->subledger_label);

				// FEC:PieceRef
				$tab[] = $line->doc_ref;

				// FEC:PieceDate
				$tab[] = $date_document;

				// FEC:EcritureLib
				// Clean label operation to prevent problem on export with tab separator & other character
				$line->label_operation = str_replace(array("\t", "\n", "\r"), " ", $line->label_operation);
				$line->label_operation = str_replace(array("..."), "", $line->label_operation);
				$tab[] = dol_string_unaccent($line->label_operation);

				// FEC:Debit
				$tab[] = price2fec($line->debit);

				// FEC:Credit
				$tab[] = price2fec($line->credit);

				// FEC:EcritureLet
				$tab[] = $line->lettering_code;

				// FEC:DateLet
				$tab[] = $date_lettering;

				// FEC:ValidDate
				$tab[] = $date_validation;

				// FEC:Montantdevise
				$tab[] = $line->multicurrency_amount;

				// FEC:Idevise
				$tab[] = $line->multicurrency_code;

				// FEC_suppl:DateLimitReglmt
				$tab[] = $date_limit_payment;

				// FEC_suppl:NumFacture
				// Clean ref invoice to prevent problem on export with tab separator & other character
				$refInvoice = str_replace(array("\t", "\n", "\r"), " ", $refInvoice);
				$tab[] = dol_trunc(self::toAnsi($refInvoice), 17, 'right', 'UTF-8', 1);

				// FEC_suppl:FichierFacture
				// get document file
				$attachmentFileName = '';
				if ($withAttachment == 1) {
					$attachmentFileKey = trim($line->piece_num);

					if (!isset($archiveFileList[$attachmentFileKey])) {
						$objectDirPath = '';
						$objectFileName = dol_sanitizeFileName($line->doc_ref);
						if ($line->doc_type == 'customer_invoice') {
							$objectDirPath = !empty($conf->invoice->multidir_output[$conf->entity]) ? $conf->invoice->multidir_output[$conf->entity] : $conf->invoice->dir_output;
						} elseif ($line->doc_type == 'expense_report') {
							$objectDirPath = !empty($conf->expensereport->multidir_output[$conf->entity]) ? $conf->expensereport->multidir_output[$conf->entity] : $conf->expensereport->dir_output;
						} elseif ($line->doc_type == 'supplier_invoice') {
							$objectDirPath = !empty($conf->fournisseur->facture->multidir_output[$conf->entity]) ? $conf->fournisseur->facture->multidir_output[$conf->entity] : $conf->fournisseur->facture->dir_output;
							$objectDirPath.= '/'.rtrim(get_exdir($invoice->id, 2, 0, 0, $invoice, 'invoice_supplier'), '/');
						}
						$arrayofinclusion = array();
						// If it is a supplier invoice, we want to use last uploaded file
						$arrayofinclusion[] = '^'.preg_quote($objectFileName, '/').(($line->doc_type == 'supplier_invoice') ? '.+' : '').'\.pdf$';
						$fileFoundList = dol_dir_list($objectDirPath.'/'.$objectFileName, 'files', 0, implode('|', $arrayofinclusion), '(\.meta|_preview.*\.png)$', 'date', SORT_DESC, 0, true);
						if (!empty($fileFoundList)) {
							$attachmentFileNameTrunc = $line->doc_ref;
							foreach ($fileFoundList as $fileFound) {
								if (strstr($fileFound['name'], $objectFileName)) {
									// skip native invoice pdfs (canelle)
									// We want to retrieve an attachment representative of the supplier invoice, not a fake document generated by Dolibarr.
									if ($line->doc_type == 'supplier_invoice') {
										if ($fileFound['name'] === $objectFileName.'.pdf') {
											continue;
										}
									} elseif ($fileFound['name'] !== $objectFileName.'.pdf') {
										continue;
									}
									$fileFoundPath = $objectDirPath.'/'.$objectFileName.'/'.$fileFound['name'];
									if (file_exists($fileFoundPath)) {
										$archiveFileList[$attachmentFileKey] = array(
											'path' => $fileFoundPath,
											'name' => $attachmentFileNameTrunc.'.pdf',
										);
										break;
									}
								}
							}
						}
					}

					if (isset($archiveFileList[$attachmentFileKey])) {
						$attachmentFileName = $archiveFileList[$attachmentFileKey]['name'];
					}
				}

				$tab[] = $attachmentFileName;

				$output = implode($separator, $tab).$end_line;
				if ($exportFile) {
					fwrite($exportFile, $output);
				} else {
					print $output;
				}
			}
		}

		return $archiveFileList;
	}

	/**
	 * Export format : SAGE50SWISS
	 *
	 * https://onlinehelp.sageschweiz.ch/default.aspx?tabid=19984
	 * http://media.topal.ch/Public/Schnittstellen/TAF/Specification/Sage50-TAF-format.pdf
	 *
	 * @param 	array 		$objectLines 			data
	 * @param 	resource	$exportFile				[=null] File resource to export or print if null
	 * @return 	void
	 */
	public function exportSAGE50SWISS($objectLines, $exportFile = null)
	{
		// SAGE50SWISS
		$separator = ',';
		$end_line = "\r\n";

		// Print header line
		$tab = array();

		$tab[] = "Blg";
		$tab[] = "Datum";
		$tab[] = "Kto";
		$tab[] = "S/H";
		$tab[] = "Grp";
		$tab[] = "GKto";
		$tab[] = "SId";
		$tab[] = "SIdx";
		$tab[] = "KIdx";
		$tab[] = "BTyp";
		$tab[] = "MTyp";
		$tab[] = "Code";
		$tab[] = "Netto";
		$tab[] = "Steuer";
		$tab[] = "FW-Betrag";
		$tab[] = "Tx1";
		$tab[] = "Tx2";
		$tab[] = "PkKey";
		$tab[] = "OpId";
		$tab[] = "Flag";

		$output = implode($separator, $tab).$end_line;
		if ($exportFile) {
			fwrite($exportFile, $output);
		} else {
			print $output;
		}

		$thisPieceNum = "";
		$thisPieceAccountNr = "";
		$aSize = count($objectLines);
		foreach ($objectLines as $aIndex => $line) {
			$sammelBuchung = false;
			if ($aIndex - 2 >= 0 && $objectLines[$aIndex - 2]->piece_num == $line->piece_num) {
				$sammelBuchung = true;
			} elseif ($aIndex + 2 < $aSize && $objectLines[$aIndex + 2]->piece_num == $line->piece_num) {
				$sammelBuchung = true;
			} elseif ($aIndex + 1 < $aSize
					&& $objectLines[$aIndex + 1]->piece_num == $line->piece_num
					&& $aIndex - 1 < $aSize
					&& $objectLines[$aIndex - 1]->piece_num == $line->piece_num
					) {
				$sammelBuchung = true;
			}

			$tab = array();

			//Blg
			$tab[] = $line->piece_num;

			// Datum
			$date_document = dol_print_date($line->doc_date, '%d.%m.%Y');
			$tab[] = $date_document;

			// Kto
			$tab[] = length_accountg($line->numero_compte);
			// S/H
			if ($line->sens == 'D') {
				$tab[] = 'S';
			} else {
				$tab[] = 'H';
			}
			// Grp
			$tab[] = self::trunc($line->code_journal, 1);
			// GKto
			if (empty($line->code_tiers)) {
				if ($line->piece_num == $thisPieceNum) {
					$tab[] = length_accounta($thisPieceAccountNr);
				} else {
					$tab[] = "div";
				}
			} else {
				$tab[] = length_accounta($line->code_tiers);
			}
			// SId
			$tab[] = $this->separator;
			// SIdx
			$tab[] = "0";
			// KIdx
			$tab[] = "0";
			// BTyp
			$tab[] = "0";

			// MTyp 1=Fibu Einzelbuchung 2=Sammebuchung
			if ($sammelBuchung) {
				$tab[] = "2";
			} else {
				$tab[] = "1";
			}
			// Code
			$tab[] = '""';
			// Netto
			$tab[] = abs($line->debit - $line->credit);
			// Steuer
			$tab[] = "0.00";
			// FW-Betrag
			$tab[] = "0.00";
			// Tx1
			$line1 = self::toAnsi($line->label_compte, 29);
			if ($line1 == "LIQ" || $line1 == "LIQ Beleg ok" || strlen($line1) <= 3) {
				$line1 = "";
			}
			$line2 = self::toAnsi($line->doc_ref, 29);
			if (strlen($line1) == 0) {
				$line1 = $line2;
				$line2 = "";
			}
			if (strlen($line1) > 0 && strlen($line2) > 0 && (strlen($line1) + strlen($line2)) < 27) {
				$line1 = $line1.' / '.$line2;
				$line2 = "";
			}

			$tab[] = '"'.self::toAnsi($line1).'"';
			// Tx2
			$tab[] = '"'.self::toAnsi($line2).'"';
			//PkKey
			$tab[] = "0";
			//OpId
			$tab[] = $this->separator;

			// Flag
			$tab[] = "0";

			$output = implode($separator, $tab).$end_line;
			if ($exportFile) {
				fwrite($exportFile, $output);
			} else {
				print $output;
			}

			if ($line->piece_num !== $thisPieceNum) {
				$thisPieceNum = $line->piece_num;
				$thisPieceAccountNr = $line->numero_compte;
			}
		}
	}

	/**
	 * Export format : LD Compta version 9
	 * http://www.ldsysteme.fr/fileadmin/telechargement/np/ldcompta/Documentation/IntCptW9.pdf
	 *
	 * @param 	array 		$objectLines 			data
	 * @param 	resource	$exportFile				[=null] File resource to export or print if null
	 * @return 	void
	 */
	public function exportLDCompta($objectLines, $exportFile = null)
	{
		$separator = ';';
		$end_line = "\r\n";

		foreach ($objectLines as $line) {
			$date_document = dol_print_date($line->doc_date, '%Y%m%d');
			$date_creation = dol_print_date($line->date_creation, '%Y%m%d');
			$date_lim_reglement = dol_print_date($line->date_lim_reglement, '%Y%m%d');

			$tab = array();

			// TYPE
			$type_enregistrement = 'E'; // For write movement
			$tab[] = $type_enregistrement;
			// JNAL
			$tab[] = substr($line->code_journal, 0, 2);
			// NECR
			$tab[] = $line->id;
			// NPIE
			$tab[] = $line->piece_num;
			// DATP
			$tab[] = $date_document;
			// LIBE
			$tab[] = $line->label_operation;
			// DATH
			$tab[] = $date_lim_reglement;
			// CNPI
			if ($line->doc_type == 'supplier_invoice') {
				if (($line->debit - $line->credit) > 0) {
					$nature_piece = 'AF';
				} else {
					$nature_piece = 'FF';
				}
			} elseif ($line->doc_type == 'customer_invoice') {
				if (($line->debit - $line->credit) < 0) {
					$nature_piece = 'AC';
				} else {
					$nature_piece = 'FC';
				}
			} else {
				$nature_piece = '';
			}
			$tab[] = $nature_piece;
			// RACI
			//			if (!empty($line->subledger_account)) {
			//              if ($line->doc_type == 'supplier_invoice') {
			//                  $racine_subledger_account = '40';
			//              } elseif ($line->doc_type == 'customer_invoice') {
			//                  $racine_subledger_account = '41';
			//              } else {
			//                  $racine_subledger_account = '';
			//              }
			//          } else {
			$racine_subledger_account = ''; // for records of type E leave this field blank
			//          }

			$tab[] = $racine_subledger_account; // deprecated CPTG & CPTA use instead
			// MONT
			$tab[] = price(abs($line->debit - $line->credit), 0, '', 1, 2, 2);
			// CODC
			$tab[] = $line->sens;
			// CPTG
			$tab[] = length_accountg($line->numero_compte);
			// DATE
			$tab[] = $date_creation;
			// CLET
			$tab[] = $line->lettering_code;
			// DATL
			$tab[] = $line->date_lettering;
			// CPTA
			if (!empty($line->subledger_account)) {
				$tab[] = length_accounta($line->subledger_account);
			} else {
				$tab[] = "";
			}
			// CNAT
			if ($line->doc_type == 'supplier_invoice' && !empty($line->subledger_account)) {
				$tab[] = 'F';
			} elseif ($line->doc_type == 'customer_invoice' && !empty($line->subledger_account)) {
				$tab[] = 'C';
			} else {
				$tab[] = "";
			}
			// SECT
			$tab[] = "";
			// CTRE
			$tab[] = "";
			// NORL
			$tab[] = "";
			// DATV
			$tab[] = "";
			// REFD
			$tab[] = $line->doc_ref;
			// CODH
			$tab[] = "";
			// NSEQ
			$tab[] = "";
			// MTDV
			$tab[] = '0';
			// CODV
			$tab[] = "";
			// TXDV
			$tab[] = '0';
			// MOPM
			$tab[] = "";
			// BONP
			$tab[] =  "";
			// BQAF
			$tab[] = "";
			// ECES
			$tab[] = "";
			// TXTL
			$tab[] = "";
			// ECRM
			$tab[] = "";
			// DATK
			$tab[] = "";
			// HEUK
			$tab[] = "";

			$output = implode($separator, $tab).$end_line;
			if ($exportFile) {
				fwrite($exportFile, $output);
			} else {
				print $output;
			}
		}
	}

	/**
	 * Export format : LD Compta version 10 & higher
	 * Last review for this format : 08-15-2021 Alexandre Spangaro (aspangaro@open-dsi.fr)
	 *
	 * Help : http://www.ldsysteme.fr/fileadmin/telechargement/np/ldcompta/Documentation/IntCptW10.pdf
	 *
	 * @param 	array 		$objectLines 			data
	 * @param 	resource	$exportFile				[=null] File resource to export or print if null
	 * @return 	void
	 */
	public function exportLDCompta10($objectLines, $exportFile = null)
	{
		require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';

		$separator = ';';
		$end_line = "\r\n";
		$last_codeinvoice = '';

		foreach ($objectLines as $line) {
			// TYPE C
			if ($last_codeinvoice != $line->doc_ref) {
				//recherche societe en fonction de son code client
				$sql = "SELECT code_client, fk_forme_juridique, nom, address, zip, town, fk_pays, phone, siret FROM ".MAIN_DB_PREFIX."societe";
				$sql .= " WHERE code_client = '".$this->db->escape($line->thirdparty_code)."'";
				$resql = $this->db->query($sql);

				if ($resql && $this->db->num_rows($resql) > 0) {
					$soc = $this->db->fetch_object($resql);

					$address = array('', '', '');
					if (strpos($soc->address, "\n") !== false) {
						$address = explode("\n", $soc->address);
						if (is_array($address) && count($address) > 0) {
							foreach ($address as $key => $data) {
								$address[$key] = str_replace(array("\t", "\n", "\r"), "", $data);
								$address[$key] = dol_trunc($address[$key], 40, 'right', 'UTF-8', 1);
							}
						}
					} else {
						$address[0] = substr(str_replace(array("\t", "\r"), " ", $soc->address), 0, 40);
						$address[1] = substr(str_replace(array("\t", "\r"), " ", $soc->address), 41, 40);
						$address[2] = substr(str_replace(array("\t", "\r"), " ", $soc->address), 82, 40);
					}

					$tab = array();

					$type_enregistrement = 'C';
					//TYPE
					$tab[] = $type_enregistrement;
					//NOCL
					$tab[] = $soc->code_client;
					//NMCM
					$tab[] = "";
					//LIBI
					$tab[] = "";
					//TITR
					$tab[] = "";
					//RSSO
					$tab[] = $soc->nom;
					//CAD1
					$tab[] = $address[0];
					//CAD2
					$tab[] = $address[1];
					//CAD3
					$tab[] = $address[2];
					//COPO
					$tab[] = $soc->zip;
					//BUDI
					$tab[] = substr($soc->town, 0, 40);
					//CPAY
					$tab[] = "";
					//PAYS
					$tab[] = substr(getCountry($soc->fk_pays), 0, 40);
					//NTEL
					$tab[] = $soc->phone;
					//TLEX
					$tab[] = "";
					//TLPO
					$tab[] = "";
					//TLCY
					$tab[] = "";
					//NINT
					$tab[] = "";
					//COMM
					$tab[] = "";
					//SIRE
					$tab[] = str_replace(" ", "", $soc->siret);
					//RIBP
					$tab[] = "";
					//DOBQ
					$tab[] = "";
					//IBBQ
					$tab[] = "";
					//COBQ
					$tab[] = "";
					//GUBQ
					$tab[] = "";
					//CPBQ
					$tab[] = "";
					//CLBQ
					$tab[] = "";
					//BIBQ
					$tab[] = "";
					//MOPM
					$tab[] = "";
					//DJPM
					$tab[] = "";
					//DMPM
					$tab[] = "";
					//REFM
					$tab[] = "";
					//SLVA
					$tab[] = "";
					//PLCR
					$tab[] = "";
					//ECFI
					$tab[] = "";
					//CREP
					$tab[] = "";
					//NREP
					$tab[] = "";
					//TREP
					$tab[] = "";
					//MREP
					$tab[] = "";
					//GRRE
					$tab[] = "";
					//LTTA
					$tab[] = "";
					//CACT
					$tab[] = "";
					//CODV
					$tab[] = "";
					//GRTR
					$tab[] = "";
					//NOFP
					$tab[] = "";
					//BQAF
					$tab[] = "";
					//BONP
					$tab[] = "";
					//CESC
					$tab[] = "";

					$output = implode($separator, $tab).$end_line;
					if ($exportFile) {
						fwrite($exportFile, $output);
					} else {
						print $output;
					}
				}
			}

			$tab = array();

			$date_document = dol_print_date($line->doc_date, '%Y%m%d');
			$date_creation = dol_print_date($line->date_creation, '%Y%m%d');
			$date_lim_reglement = dol_print_date($line->date_lim_reglement, '%Y%m%d');

			// TYPE E
			$type_enregistrement = 'E'; // For write movement
			$tab[] = $type_enregistrement;
			// JNAL
			$tab[] = substr($line->code_journal, 0, 2);
			// NECR
			$tab[] = $line->id;
			// NPIE
			$tab[] = $line->piece_num;
			// DATP
			$tab[] = $date_document;
			// LIBE
			$tab[] = dol_trunc($line->label_operation, 25, 'right', 'UTF-8', 1);
			// DATH
			$tab[] = $date_lim_reglement;
			// CNPI
			if ($line->doc_type == 'supplier_invoice') {
				if (($line->amount) < 0) {		// Currently, only the sign of amount allows to know the type of invoice (standard or credit note). Other solution is to analyse debit/credit/role of account. TODO Add column doc_type_long or make amount mandatory with rule on sign.
					$nature_piece = 'AF';
				} else {
					$nature_piece = 'FF';
				}
			} elseif ($line->doc_type == 'customer_invoice') {
				if (($line->amount) < 0) {
					$nature_piece = 'AC';		// Currently, only the sign of amount allows to know the type of invoice (standard or credit note). Other solution is to analyse debit/credit/role of account. TODO Add column doc_type_long or make amount mandatory with rule on sign.
				} else {
					$nature_piece = 'FC';
				}
			} else {
				$nature_piece = '';
			}
			$tab[] = $nature_piece;
			// RACI
			//			if (!empty($line->subledger_account)) {
			//				if ($line->doc_type == 'supplier_invoice') {
			//					$racine_subledger_account = '40';
			//				} elseif ($line->doc_type == 'customer_invoice') {
			//					$racine_subledger_account = '41';
			//				} else {
			//					$racine_subledger_account = '';
			//				}
			//			} else {
			$racine_subledger_account = ''; // for records of type E leave this field blank
			//			}

			$tab[] = $racine_subledger_account; // deprecated CPTG & CPTA use instead
			// MONT
			$tab[] = price(abs($line->debit - $line->credit), 0, '', 1, 2);
			// CODC
			$tab[] = $line->sens;
			// CPTG
			$tab[] = length_accountg($line->numero_compte);
			// DATE
			$tab[] = $date_document;
			// CLET
			$tab[] = $line->lettering_code;
			// DATL
			$tab[] = $line->date_lettering;
			// CPTA
			if (!empty($line->subledger_account)) {
				$tab[] = length_accounta($line->subledger_account);
			} else {
				$tab[] = "";
			}
			// CNAT
			if ($line->doc_type == 'supplier_invoice' && !empty($line->subledger_account)) {
				$tab[] = 'F';
			} elseif ($line->doc_type == 'customer_invoice' && !empty($line->subledger_account)) {
				$tab[] = 'C';
			} else {
				$tab[] = "";
			}
			// CTRE
			$tab[] = "";
			// NORL
			$tab[] = "";
			// DATV
			$tab[] = "";
			// REFD
			$tab[] = $line->doc_ref;
			// NECA
			$tab[] = '0';
			// CSEC
			$tab[] = "";
			// CAFF
			$tab[] = "";
			// CDES
			$tab[] = "";
			// QTUE
			$tab[] = "";
			// MTDV
			$tab[] = '0';
			// CODV
			$tab[] = "";
			// TXDV
			$tab[] = '0';
			// MOPM
			$tab[] = "";
			// BONP
			$tab[] = "";
			// BQAF
			$tab[] = "";
			// ECES
			$tab[] = "";
			// TXTL
			$tab[] = "";
			// ECRM
			$tab[] = "";
			// DATK
			$tab[] = "";
			// HEUK
			$tab[] = "";

			$output = implode($separator, $tab).$end_line;
			if ($exportFile) {
				fwrite($exportFile, $output);
			} else {
				print $output;
			}

			$last_codeinvoice = $line->doc_ref;
		}
	}

	/**
	 * Export format : Charlemagne
	 *
	 * @param 	array 		$objectLines 			data
	 * @param 	resource	$exportFile				[=null] File resource to export or print if null
	 * @return 	void
	 */
	public function exportCharlemagne($objectLines, $exportFile = null)
	{
		global $langs;
		$langs->load('compta');

		$separator = "\t";
		$end_line = "\n";

		$tab = array();

		$tab[] = $langs->transnoentitiesnoconv('Date');
		$tab[] = self::trunc($langs->transnoentitiesnoconv('Journal'), 6);
		$tab[] = self::trunc($langs->transnoentitiesnoconv('Account'), 15);
		$tab[] = self::trunc($langs->transnoentitiesnoconv('LabelAccount'), 60);
		$tab[] = self::trunc($langs->transnoentitiesnoconv('Piece'), 20);
		$tab[] = self::trunc($langs->transnoentitiesnoconv('LabelOperation'), 60);
		$tab[] = $langs->transnoentitiesnoconv('Amount');
		$tab[] = 'S';
		$tab[] = self::trunc($langs->transnoentitiesnoconv('Analytic').' 1', 15);
		$tab[] = self::trunc($langs->transnoentitiesnoconv('AnalyticLabel').' 1', 60);
		$tab[] = self::trunc($langs->transnoentitiesnoconv('Analytic').' 2', 15);
		$tab[] = self::trunc($langs->transnoentitiesnoconv('AnalyticLabel').' 2', 60);
		$tab[] = self::trunc($langs->transnoentitiesnoconv('Analytic').' 3', 15);
		$tab[] = self::trunc($langs->transnoentitiesnoconv('AnalyticLabel').' 3', 60);

		$output = implode($separator, $tab).$end_line;
		if ($exportFile) {
			fwrite($exportFile, $output);
		} else {
			print $output;
		}

		foreach ($objectLines as $line) {
			$date_document = dol_print_date($line->doc_date, '%Y%m%d');

			$tab = array();

			$tab[] = $date_document; //Date

			$tab[] = self::trunc($line->code_journal, 6); //Journal code

			if (!empty($line->subledger_account)) {
				$account = $line->subledger_account;
			} else {
				$account = $line->numero_compte;
			}
			$tab[] = self::trunc($account, 15); //Account number

			$tab[] = self::trunc($line->label_compte, 60); //Account label
			$tab[] = self::trunc($line->doc_ref, 20); //Piece
			// Clean label operation to prevent problem on export with tab separator & other character
			$line->label_operation = str_replace(array("\t", "\n", "\r"), " ", $line->label_operation);
			$tab[] = self::trunc($line->label_operation, 60); //Operation label
			$tab[] = price(abs($line->debit - $line->credit)); //Amount
			$tab[] = $line->sens; //Direction
			$tab[] = ""; //Analytic
			$tab[] = ""; //Analytic
			$tab[] = ""; //Analytic
			$tab[] = ""; //Analytic
			$tab[] = ""; //Analytic
			$tab[] = ""; //Analytic

			$output = implode($separator, $tab).$end_line;
			if ($exportFile) {
				fwrite($exportFile, $output);
			} else {
				print $output;
			}
		}
	}

	/**
	 * Export format : Gestimum V3
	 *
	 * @param 	array 		$objectLines 			data
	 * @param 	resource	$exportFile				[=null] File resource to export or print if null
	 * @return	void
	 */
	public function exportGestimumV3($objectLines, $exportFile = null)
	{
		global $langs;

		$separator = ',';
		$end_line = "\r\n";

		$invoices_infos = array();
		$supplier_invoices_infos = array();
		foreach ($objectLines as $line) {
			if ($line->debit == 0 && $line->credit == 0) {
				//unset($array[$line]);
			} else {
				$date_document = dol_print_date($line->doc_date, '%d/%m/%Y');
				$date_echeance = dol_print_date($line->date_lim_reglement, '%Y%m%d');

				$invoice_ref = $line->doc_ref;
				$company_name = "";

				if (($line->doc_type == 'customer_invoice' || $line->doc_type == 'supplier_invoice') && $line->fk_doc > 0) {
					if (($line->doc_type == 'customer_invoice' && !isset($invoices_infos[$line->fk_doc])) ||
						($line->doc_type == 'supplier_invoice' && !isset($supplier_invoices_infos[$line->fk_doc]))) {
						if ($line->doc_type == 'customer_invoice') {
							// Get new customer invoice ref and company name
							$sql = 'SELECT f.ref, s.nom FROM ' . MAIN_DB_PREFIX . 'facture as f';
							$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'societe AS s ON f.fk_soc = s.rowid';
							$sql .= ' WHERE f.rowid = '.((int) $line->fk_doc);
							$resql = $this->db->query($sql);
							if ($resql) {
								if ($obj = $this->db->fetch_object($resql)) {
									// Save invoice infos
									$invoices_infos[$line->fk_doc] = array('ref' => $obj->ref, 'company_name' => $obj->nom);
									$invoice_ref = $obj->ref;
									$company_name = $obj->nom;
								}
							}
						} else {
							// Get new supplier invoice ref and company name
							$sql = 'SELECT ff.ref, s.nom FROM ' . MAIN_DB_PREFIX . 'facture_fourn as ff';
							$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'societe AS s ON ff.fk_soc = s.rowid';
							$sql .= ' WHERE ff.rowid = '.((int) $line->fk_doc);
							$resql = $this->db->query($sql);
							if ($resql) {
								if ($obj = $this->db->fetch_object($resql)) {
									// Save invoice infos
									$supplier_invoices_infos[$line->fk_doc] = array('ref' => $obj->ref, 'company_name' => $obj->nom);
									$invoice_ref = $obj->ref;
									$company_name = $obj->nom;
								}
							}
						}
					} elseif ($line->doc_type == 'customer_invoice') {
						// Retrieve invoice infos
						$invoice_ref = $invoices_infos[$line->fk_doc]['ref'];
						$company_name = $invoices_infos[$line->fk_doc]['company_name'];
					} else {
						// Retrieve invoice infos
						$invoice_ref = $supplier_invoices_infos[$line->fk_doc]['ref'];
						$company_name = $supplier_invoices_infos[$line->fk_doc]['company_name'];
					}
				}

				$tab = array();

				$tab[] = $line->id;
				$tab[] = $date_document;
				$tab[] = substr($line->code_journal, 0, 4);

				if ((substr($line->numero_compte, 0, 3) == '411') || (substr($line->numero_compte, 0, 3) == '401')) {
					$tab[] = length_accountg($line->subledger_account);
				} else {
					$tab[] = substr(length_accountg($line->numero_compte), 0, 15);
				}
				//Libellé Auto
				$tab[] = "";
				//print '"'.dol_trunc(str_replace('"', '', $line->label_operation),40,'right','UTF-8',1).'"';
				//Libellé manuel
				$tab[] = dol_trunc(str_replace('"', '', $invoice_ref . (!empty($company_name) ? ' - ' : '') . $company_name), 40, 'right', 'UTF-8', 1);
				//Numéro de pièce
				$tab[] = dol_trunc(str_replace('"', '', $line->piece_num), 10, 'right', 'UTF-8', 1);
				//Devise
				$tab[] = 'EUR';
				//Amount
				$tab[] = price2num(abs($line->debit - $line->credit));
				//Sens
				$tab[] = $line->sens;
				//Code lettrage
				$tab[] = "";
				//Date Echéance
				$tab[] = $date_echeance;

				$output = implode($separator, $tab).$end_line;
				if ($exportFile) {
					fwrite($exportFile, $output);
				} else {
					print $output;
				}
			}
		}
	}

	/**
	 * Export format : Gestimum V5
	 *
	 * @param 	array 		$objectLines 			data
	 * @param 	resource	$exportFile				[=null] File resource to export or print if null
	 * @return 	void
	 */
	public function exportGestimumV5($objectLines, $exportFile = null)
	{
		$separator = ',';
		$end_line = "\r\n";

		foreach ($objectLines as $line) {
			if ($line->debit == 0 && $line->credit == 0) {
				//unset($array[$line]);
			} else {
				$date_document = dol_print_date($line->doc_date, '%d%m%Y');

				$tab = array();

				$tab[] = $line->id;
				$tab[] = $date_document;
				$tab[] = substr($line->code_journal, 0, 4);
				if ((substr($line->numero_compte, 0, 3) == '411') || (substr($line->numero_compte, 0, 3) == '401')) {	// TODO No hard code value
					$tab[] = length_accountg($line->subledger_account);
				} else {
					$tab[] = substr(length_accountg($line->numero_compte), 0, 15);
				}
				$tab[] = "";
				$tab[] = '"'.dol_trunc(str_replace('"', '', $line->label_operation), 40, 'right', 'UTF-8', 1).'"';
				$tab[] = '"' . dol_trunc(str_replace('"', '', $line->doc_ref), 40, 'right', 'UTF-8', 1) . '"';
				$tab[] = '"' . dol_trunc(str_replace('"', '', $line->piece_num), 10, 'right', 'UTF-8', 1) . '"';
				$tab[] = price2num(abs($line->debit - $line->credit));
				$tab[] = $line->sens;
				$tab[] = $date_document;
				$tab[] = "";
				$tab[] = "";
				$tab[] = 'EUR';

				$output = implode($separator, $tab).$end_line;
				if ($exportFile) {
					fwrite($exportFile, $output);
				} else {
					print $output;
				}
			}
		}
	}

	/**
	* Export format : iSuite Expert
	*
	* by OpenSolus [https://opensolus.fr]
	*
	 * @param 	array 		$objectLines 			data
	 * @param 	resource	$exportFile				[=null] File resource to export or print if null
	 * @return 	void
	*/
	public function exportiSuiteExpert($objectLines, $exportFile = null)
	{
		$separator = ';';
		$end_line = "\r\n";


		foreach ($objectLines as $line) {
			$tab = array();

			$date = dol_print_date($line->doc_date, '%d/%m/%Y');

			$tab[] = $line->piece_num;
			$tab[] = $date;
			$tab[] = substr($date, 6, 4);
			$tab[] = substr($date, 3, 2);
			$tab[] = substr($date, 0, 2);
			$tab[] = $line->doc_ref;
			//Conversion de chaine UTF8 en Latin9
			$tab[] = mb_convert_encoding(str_replace(' - Compte auxiliaire', '', $line->label_operation), "Windows-1252", 'UTF-8');

			//Calcul de la longueur des numéros de comptes
			$taille_numero = strlen(length_accountg($line->numero_compte));

			//Création du numéro de client et fournisseur générique
			$numero_cpt_client = '411';
			$numero_cpt_fourn = '401';
			for ($i = 1; $i <= ($taille_numero - 3); $i++) {
				$numero_cpt_client .= '0';
				$numero_cpt_fourn .= '0';
			}

			//Création des comptes auxiliaire des clients et fournisseur
			if (length_accountg($line->numero_compte) == $numero_cpt_client || length_accountg($line->numero_compte) == $numero_cpt_fourn) {
				$tab[] = rtrim(length_accounta($line->subledger_account), "0");
			} else {
				$tab[] = length_accountg($line->numero_compte);
			}
			$nom_client = explode(" - ", $line->label_operation);
			$tab[] = mb_convert_encoding($nom_client[0], "Windows-1252", 'UTF-8');
			$tab[] = price($line->debit);
			$tab[] = price($line->credit);
			$tab[] = price($line->montant);
			$tab[] = $line->code_journal;

			$output = implode($separator, $tab).$end_line;
			if ($exportFile) {
				fwrite($exportFile, $output);
			} else {
				print $output;
			}
		}
	}

	/**
	 * trunc
	 *
	 * @param string	$str 	String
	 * @param integer 	$size 	Data to trunc
	 * @return string
	 */
	public static function trunc($str, $size)
	{
		return dol_trunc($str, $size, 'right', 'UTF-8', 1);
	}

	/**
	 * toAnsi
	 *
	 * @param string	$str 		Original string to encode and optionaly truncate
	 * @param integer 	$size 		Truncate string after $size characters
	 * @return string 				String encoded in Windows-1251 charset
	 */
	public static function toAnsi($str, $size = -1)
	{
		$retVal = dol_string_nohtmltag($str, 1, 'Windows-1251');
		if ($retVal >= 0 && $size >= 0) {
			$retVal = dol_substr($retVal, 0, $size, 'Windows-1251');
		}
		return $retVal;
	}
}
