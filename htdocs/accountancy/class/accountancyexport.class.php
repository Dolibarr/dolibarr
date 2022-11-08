<?php
/*
 * Copyright (C) 2007-2012  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2014       Juanjo Menent       <jmenent@2byte.es>
 * Copyright (C) 2015       Florian Henry       <florian.henry@open-concept.pro>
 * Copyright (C) 2015       Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2016       Pierre-Henry Favre  <phf@atm-consulting.fr>
 * Copyright (C) 2016-2021  Alexandre Spangaro  <aspangaro@open-dsi.fr>
 * Copyright (C) 2013-2017  Olivier Geffroy     <jeff@jeffinfo.com>
 * Copyright (C) 2017       Elarifr. Ari Elbaz  <github@accedinfo.com>
 * Copyright (C) 2017-2019  Frédéric France     <frederic.france@netlogic.fr>
 * Copyright (C) 2017       André Schild        <a.schild@aarboard.ch>
 * Copyright (C) 2020       Guillaume Alexandre <guillaume@tag-info.fr>
 * Copyright (C) 2022		Joachim Kueter		<jkueter@gmx.de>
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
		$this->end_line = empty($conf->global->ACCOUNTING_EXPORT_ENDLINE) ? "\n" : ($conf->global->ACCOUNTING_EXPORT_ENDLINE == 1 ? "\n" : "\r\n");

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
					'ACCOUNTING_EXPORT_FORMAT' => empty($conf->global->ACCOUNTING_EXPORT_FORMAT) ? 'txt' : $conf->global->ACCOUNTING_EXPORT_FORMAT,
					'ACCOUNTING_EXPORT_SEPARATORCSV' => empty($conf->global->ACCOUNTING_EXPORT_SEPARATORCSV) ? ',' : $conf->global->ACCOUNTING_EXPORT_SEPARATORCSV,
					'ACCOUNTING_EXPORT_ENDLINE' => empty($conf->global->ACCOUNTING_EXPORT_ENDLINE) ? 1 : $conf->global->ACCOUNTING_EXPORT_ENDLINE,
					'ACCOUNTING_EXPORT_DATE' => empty($conf->global->ACCOUNTING_EXPORT_DATE) ? '%d%m%Y' : $conf->global->ACCOUNTING_EXPORT_DATE,
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
	 * Function who chose which export to use with the default config, and make the export into a file
	 *
	 * @param 	array	$TData 				Array with data
	 * @param	int		$formatexportset	Id of export format
	 * @return 	void
	 */
	public function export(&$TData, $formatexportset)
	{
		global $conf, $langs;
		global $search_date_end; // Used into /accountancy/tpl/export_journal.tpl.php

		// Define name of file to save
		$filename = 'general_ledger-'.$this->getFormatCode($formatexportset);
		$type_export = 'general_ledger';

		global $db; // The tpl file use $db
		include DOL_DOCUMENT_ROOT.'/accountancy/tpl/export_journal.tpl.php';


		switch ($formatexportset) {
			case self::$EXPORT_TYPE_CONFIGURABLE:
				$this->exportConfigurable($TData);
				break;
			case self::$EXPORT_TYPE_CEGID:
				$this->exportCegid($TData);
				break;
			case self::$EXPORT_TYPE_COALA:
				$this->exportCoala($TData);
				break;
			case self::$EXPORT_TYPE_BOB50:
				$this->exportBob50($TData);
				break;
			case self::$EXPORT_TYPE_CIEL:
				$this->exportCiel($TData);
				break;
			case self::$EXPORT_TYPE_QUADRATUS:
				$this->exportQuadratus($TData);
				break;
			case self::$EXPORT_TYPE_WINFIC:
				$this->exportWinfic($TData);
				break;
			case self::$EXPORT_TYPE_EBP:
				$this->exportEbp($TData);
				break;
			case self::$EXPORT_TYPE_COGILOG:
				$this->exportCogilog($TData);
				break;
			case self::$EXPORT_TYPE_AGIRIS:
				$this->exportAgiris($TData);
				break;
			case self::$EXPORT_TYPE_OPENCONCERTO:
				$this->exportOpenConcerto($TData);
				break;
			case self::$EXPORT_TYPE_SAGE50_SWISS:
				$this->exportSAGE50SWISS($TData);
				break;
			case self::$EXPORT_TYPE_CHARLEMAGNE:
				$this->exportCharlemagne($TData);
				break;
			case self::$EXPORT_TYPE_LDCOMPTA:
				$this->exportLDCompta($TData);
				break;
			case self::$EXPORT_TYPE_LDCOMPTA10:
				$this->exportLDCompta10($TData);
				break;
			case self::$EXPORT_TYPE_GESTIMUMV3:
				$this->exportGestimumV3($TData);
				break;
			case self::$EXPORT_TYPE_GESTIMUMV5:
				$this->exportGestimumV5($TData);
				break;
			case self::$EXPORT_TYPE_FEC:
				$this->exportFEC($TData);
				break;
			case self::$EXPORT_TYPE_FEC2:
				$this->exportFEC2($TData);
				break;
			case self::$EXPORT_TYPE_ISUITEEXPERT :
				$this->exportiSuiteExpert($TData);
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
	}


	/**
	 * Export format : CEGID
	 *
	 * @param array $objectLines data
	 * @return void
	 */
	public function exportCegid($objectLines)
	{
		foreach ($objectLines as $line) {
			$date = dol_print_date($line->doc_date, '%d%m%Y');
			$separator = ";";
			$end_line = "\n";

			print $date.$separator;
			print $line->code_journal.$separator;
			print length_accountg($line->numero_compte).$separator;
			print length_accounta($line->subledger_account).$separator;
			print $line->sens.$separator;
			print price2fec(abs($line->debit - $line->credit)).$separator;
			print dol_string_unaccent($line->label_operation).$separator;
			print dol_string_unaccent($line->doc_ref);
			print $end_line;
		}
	}

	/**
	 * Export format : COGILOG
	 *
	 * @param array $objectLines data
	 * @return void
	 */
	public function exportCogilog($objectLines)
	{
		foreach ($objectLines as $line) {
			$date = dol_print_date($line->doc_date, '%d%m%Y');
			$separator = ";";
			$end_line = "\n";

			print $line->code_journal.$separator;
			print $date.$separator;
			print $line->piece_num.$separator;
			print length_accountg($line->numero_compte).$separator;
			print ''.$separator;
			print $line->label_operation.$separator;
			print $date.$separator;
			if ($line->sens == 'D') {
				print price($line->debit).$separator;
				print ''.$separator;
			} elseif ($line->sens == 'C') {
				print ''.$separator;
				print price($line->credit).$separator;
			}
			print $line->doc_ref.$separator;
			print $line->label_operation.$separator;
			print $end_line;
		}
	}

	/**
	 * Export format : COALA
	 *
	 * @param array $objectLines data
	 * @return void
	 */
	public function exportCoala($objectLines)
	{
		// Coala export
		$separator = ";";
		$end_line = "\n";

		foreach ($objectLines as $line) {
			$date = dol_print_date($line->doc_date, '%d/%m/%Y');

			print $date.$separator;
			print $line->code_journal.$separator;
			print length_accountg($line->numero_compte).$separator;
			print $line->piece_num.$separator;
			print $line->doc_ref.$separator;
			print price($line->debit).$separator;
			print price($line->credit).$separator;
			print 'E'.$separator;
			print length_accounta($line->subledger_account).$separator;
			print $end_line;
		}
	}

	/**
	 * Export format : BOB50
	 *
	 * @param array $objectLines data
	 * @return void
	 */
	public function exportBob50($objectLines)
	{

		// Bob50
		$separator = ";";
		$end_line = "\n";

		foreach ($objectLines as $line) {
			print $line->piece_num.$separator;
			$date = dol_print_date($line->doc_date, '%d/%m/%Y');
			print $date.$separator;

			if (empty($line->subledger_account)) {
				print 'G'.$separator;
				print length_accounta($line->numero_compte).$separator;
			} else {
				if (substr($line->numero_compte, 0, 3) == '411') {
					print 'C'.$separator;
				}
				if (substr($line->numero_compte, 0, 3) == '401') {
					print 'F'.$separator;
				}
				print length_accountg($line->subledger_account).$separator;
			}

			print price($line->debit).$separator;
			print price($line->credit).$separator;
			print dol_trunc($line->label_operation, 32).$separator;
			print $end_line;
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
	 * @param array $TData data
	 * @return void
	 */
	public function exportCiel(&$TData)
	{
		$end_line = "\r\n";

		$i = 1;

		foreach ($TData as $data) {
			$code_compta = length_accountg($data->numero_compte);
			if (!empty($data->subledger_account)) {
				$code_compta = length_accounta($data->subledger_account);
			}

			$date_document = dol_print_date($data->doc_date, '%Y%m%d');
			$date_echeance = dol_print_date($data->date_lim_reglement, '%Y%m%d');

			$Tab = array();
			$Tab['num_ecriture'] = str_pad($data->piece_num, 5);
			$Tab['code_journal'] = str_pad(self::trunc($data->code_journal, 2), 2);
			$Tab['date_ecriture'] = str_pad($date_document, 8, ' ', STR_PAD_LEFT);
			$Tab['date_echeance'] = str_pad($date_echeance, 8, ' ', STR_PAD_LEFT);
			$Tab['num_piece'] = str_pad(self::trunc($data->doc_ref, 12), 12);
			$Tab['num_compte'] = str_pad(self::trunc($code_compta, 11), 11);
			$Tab['libelle_ecriture'] = str_pad(self::trunc(dol_string_unaccent($data->doc_ref).dol_string_unaccent($data->label_operation), 25), 25);
			$Tab['montant'] = str_pad(price2fec(abs($data->debit - $data->credit)), 13, ' ', STR_PAD_LEFT);
			$Tab['type_montant'] = str_pad($data->sens, 1);
			$Tab['vide'] = str_repeat(' ', 18); // Analytical accounting - Not managed in Dolibarr
			$Tab['intitule_compte'] = str_pad(self::trunc(dol_string_unaccent($data->label_operation), 34), 34);
			$Tab['end'] = 'O2003'; // 0 = EUR | 2003 = Format Ciel

			$Tab['end_line'] = $end_line;

			print implode($Tab);
			$i++;
		}
	}

	/**
	 * Export format : Quadratus (Format ASCII)
	 * Format since 2015 compatible QuadraCOMPTA
	 * Last review for this format : 2021/09/13 Alexandre Spangaro (aspangaro@open-dsi.fr)
	 *
	 * Help : https://docplayer.fr/20769649-Fichier-d-entree-ascii-dans-quadracompta.html
	 * In QuadraCompta | Use menu : "Outils" > "Suivi des dossiers" > "Import ASCII(Compta)"
	 *
	 * @param array $TData data
	 * @return void
	 */
	public function exportQuadratus(&$TData)
	{
		global $conf, $db;

		$end_line = "\r\n";

		// We should use dol_now function not time however this is wrong date to transfert in accounting
		// $date_ecriture = dol_print_date(dol_now(), $conf->global->ACCOUNTING_EXPORT_DATE); // format must be ddmmyy
		// $date_ecriture = dol_print_date(time(), $conf->global->ACCOUNTING_EXPORT_DATE); // format must be ddmmyy
		foreach ($TData as $data) {
			$code_compta = $data->numero_compte;
			if (!empty($data->subledger_account)) {
				$code_compta = $data->subledger_account;
			}

			$Tab = array();

			if (!empty($data->subledger_account)) {
				$Tab['type_ligne'] = 'C';
				$Tab['num_compte'] = str_pad(self::trunc($data->subledger_account, 8), 8);
				$Tab['lib_compte'] = str_pad(self::trunc($data->subledger_label, 30), 30);

				if ($data->doc_type == 'customer_invoice') {
					$Tab['lib_alpha'] = strtoupper(str_pad('C'.self::trunc($data->subledger_label, 6), 6));
					$Tab['filler'] = str_repeat(' ', 52);
					$Tab['coll_compte'] = str_pad(self::trunc($conf->global->ACCOUNTING_ACCOUNT_CUSTOMER, 8), 8);
				} elseif ($data->doc_type == 'supplier_invoice') {
					$Tab['lib_alpha'] = strtoupper(str_pad('F'.self::trunc($data->subledger_label, 6), 6));
					$Tab['filler'] = str_repeat(' ', 52);
					$Tab['coll_compte'] = str_pad(self::trunc($conf->global->ACCOUNTING_ACCOUNT_SUPPLIER, 8), 8);
				} else {
					$Tab['filler'] = str_repeat(' ', 59);
					$Tab['coll_compte'] = str_pad(' ', 8);
				}

				$Tab['filler2'] = str_repeat(' ', 110);
				$Tab['Maj'] = 2; // Partial update (alpha key, label, address, collectif, RIB)

				if ($data->doc_type == 'customer_invoice') {
					$Tab['type_compte'] = 'C';
				} elseif ($data->doc_type == 'supplier_invoice') {
					$Tab['coll_compte'] = 'F';
				} else {
					$Tab['coll_compte'] = 'G';
				}

				$Tab['filler3'] = str_repeat(' ', 235);

				$Tab['end_line'] = $end_line;

				print implode($Tab);
			}

			$Tab = array();
			$Tab['type_ligne'] = 'M';
			$Tab['num_compte'] = str_pad(self::trunc($code_compta, 8), 8);
			$Tab['code_journal'] = str_pad(self::trunc($data->code_journal, 2), 2);
			$Tab['folio'] = '000';

			// We use invoice date $data->doc_date not $date_ecriture which is the transfert date
			// maybe we should set an option for customer who prefer to keep in accounting software the tranfert date instead of invoice date ?
			//$Tab['date_ecriture'] = $date_ecriture;
			$Tab['date_ecriture'] = dol_print_date($data->doc_date, '%d%m%y');
			$Tab['filler'] = ' ';
			$Tab['libelle_ecriture'] = str_pad(self::trunc(dol_string_unaccent($data->doc_ref).' '.dol_string_unaccent($data->label_operation), 20), 20);

			// Credit invoice - invert sens
			/*
			if ($data->montant < 0) {
				if ($data->sens == 'C') {
					$Tab['sens'] = 'D';
				} else {
					$Tab['sens'] = 'C';
				}
				$Tab['signe_montant'] = '-';
			} else {
				$Tab['sens'] = $data->sens; // C or D
				$Tab['signe_montant'] = '+';
			}*/
			$Tab['sens'] = $data->sens; // C or D
			$Tab['signe_montant'] = '+';

			// The amount must be in centimes without decimal points.
			$Tab['montant'] = str_pad(abs(($data->debit - $data->credit) * 100), 12, '0', STR_PAD_LEFT);
			$Tab['contrepartie'] = str_repeat(' ', 8);

			// Force date format : %d%m%y
			if (!empty($data->date_lim_reglement)) {
				//$Tab['date_echeance'] = dol_print_date($data->date_lim_reglement, $conf->global->ACCOUNTING_EXPORT_DATE);
				$Tab['date_echeance'] = dol_print_date($data->date_lim_reglement, '%d%m%y'); // Format must be ddmmyy
			} else {
				$Tab['date_echeance'] = '000000';
			}

			// Please keep quadra named field lettrage(2) + codestat(3) instead of fake lettrage(5)
			// $Tab['lettrage'] = str_repeat(' ', 5);
			$Tab['lettrage'] = str_repeat(' ', 2);
			$Tab['codestat'] = str_repeat(' ', 3);
			$Tab['num_piece'] = str_pad(self::trunc($data->piece_num, 5), 5);

			// Keep correct quadra named field instead of anon filler
			// $Tab['filler2'] = str_repeat(' ', 20);
			$Tab['affaire'] = str_repeat(' ', 10);
			$Tab['quantity1'] = str_repeat(' ', 10);
			$Tab['num_piece2'] = str_pad(self::trunc($data->piece_num, 8), 8);
			$Tab['devis'] = str_pad($conf->currency, 3);
			$Tab['code_journal2'] = str_pad(self::trunc($data->code_journal, 3), 3);
			$Tab['filler3'] = str_repeat(' ', 3);

			// Keep correct quadra named field instead of anon filler libelle_ecriture2 is 30 char not 32 !!!!
			// as we use utf8, we must remove accent to have only one ascii char instead of utf8 2 chars for specials that report wrong line size that will exceed import format spec
			// TODO: we should filter more than only accent to avoid wrong line size
			// TODO: remove invoice number doc_ref in libelle,
			// TODO: we should offer an option for customer to build the libelle using invoice number / name / date in accounting software
			//$Tab['libelle_ecriture2'] = str_pad(self::trunc(dol_string_unaccent($data->doc_ref) . ' ' . dol_string_unaccent($data->label_operation), 30), 30);
			$Tab['libelle_ecriture2'] = str_pad(self::trunc(dol_string_unaccent($data->label_operation), 30), 30);
			$Tab['codetva'] = str_repeat(' ', 2);

			// We need to keep the 10 lastest number of invoice doc_ref not the beginning part that is the unusefull almost same part
			// $Tab['num_piece3'] = str_pad(self::trunc($data->piece_num, 10), 10);
			$Tab['num_piece3'] = substr(self::trunc($data->doc_ref, 20), -10);
			$Tab['filler4'] = str_repeat(' ', 73);

			$Tab['end_line'] = $end_line;

			print implode($Tab);
		}
	}

	/**
	 * Export format : WinFic - eWinfic - WinSis Compta
	 * Last review for this format : 2022-11-01 Alexandre Spangaro (aspangaro@open-dsi.fr)
	 *
	 * Help : https://wiki.gestan.fr/lib/exe/fetch.php?media=wiki:v15:compta:accountancy-format_winfic-ewinfic-winsiscompta.pdf
	 *
	 * @param array $TData data
	 *
	 * @return void
	 */
	public function exportWinfic(&$TData)
	{
		global $conf;

		$end_line = "\r\n";
		$index = 1;

		//We should use dol_now function not time however this is wrong date to transfert in accounting
		//$date_ecriture = dol_print_date(dol_now(), $conf->global->ACCOUNTING_EXPORT_DATE); // format must be ddmmyy
		//$date_ecriture = dol_print_date(time(), $conf->global->ACCOUNTING_EXPORT_DATE); // format must be ddmmyy

		// Warning ! When truncation is necessary, no dot because 3 dots = three characters. The columns are shifted

		foreach ($TData as $data) {
			$code_compta = $data->numero_compte;
			if (!empty($data->subledger_account)) {
				$code_compta = $data->subledger_account;
			}

			$Tab = array();
			//$Tab['type_ligne'] = 'M';
			$Tab['code_journal'] = str_pad(dol_trunc($data->code_journal, 2, 'right', 'UTF-8', 1), 2);

			//We use invoice date $data->doc_date not $date_ecriture which is the transfert date
			//maybe we should set an option for customer who prefer to keep in accounting software the tranfert date instead of invoice date ?
			//$Tab['date_ecriture'] = $date_ecriture;
			$Tab['date_operation'] = dol_print_date($data->doc_date, '%d%m%Y');

			$Tab['folio'] = '     1';

			$Tab['num_ecriture'] = str_pad(dol_trunc($index, 6, 'right', 'UTF-8', 1), 6, ' ', STR_PAD_LEFT);

			$Tab['jour_ecriture'] = dol_print_date($data->doc_date, '%d%m%y');

			$Tab['num_compte'] = str_pad(dol_trunc($code_compta, 6, 'right', 'UTF-8', 1), 6, '0');

			if ($data->sens == 'D') {
				$Tab['montant_debit']  = str_pad(number_format($data->debit, 2, ',', ''), 13, ' ', STR_PAD_LEFT);

				$Tab['montant_crebit'] = str_pad(number_format(0, 2, ',', ''), 13, ' ', STR_PAD_LEFT);
			} else {
				$Tab['montant_debit']  = str_pad(number_format(0, 2, ',', ''), 13, ' ', STR_PAD_LEFT);

				$Tab['montant_crebit'] = str_pad(number_format($data->credit, 2, ',', ''), 13, ' ', STR_PAD_LEFT);
			}

			$Tab['libelle_ecriture'] = str_pad(dol_trunc(dol_string_unaccent($data->doc_ref).' '.dol_string_unaccent($data->label_operation), 30, 'right', 'UTF-8', 1), 30);

			$Tab['lettrage'] = str_repeat(dol_trunc($data->lettering_code, 2, 'left', 'UTF-8', 1), 2);

			$Tab['code_piece'] = str_pad(dol_trunc($data->piece_num, 5, 'left', 'UTF-8', 1), 5, ' ', STR_PAD_LEFT);

			$Tab['code_stat'] = str_repeat(' ', 4);

			if (!empty($data->date_lim_reglement)) {
				//$Tab['date_echeance'] = dol_print_date($data->date_lim_reglement, $conf->global->ACCOUNTING_EXPORT_DATE);
				$Tab['date_echeance'] = dol_print_date($data->date_lim_reglement, '%d%m%Y');
			} else {
				$Tab['date_echeance'] = dol_print_date($data->doc_date, '%d%m%Y');
			}

			$Tab['monnaie'] = '1';

			$Tab['filler'] = ' ';

			$Tab['ind_compteur'] = ' ';

			$Tab['quantite'] = '0,000000000';

			$Tab['code_pointage'] = str_repeat(' ', 2);

			$Tab['end_line'] = $end_line;

			print implode('|', $Tab);

			$index++;
		}
	}


	/**
	 * Export format : EBP
	 *
	 * @param array $objectLines data
	 * @return void
	 */
	public function exportEbp($objectLines)
	{

		$separator = ',';
		$end_line = "\n";

		foreach ($objectLines as $line) {
			$date = dol_print_date($line->doc_date, '%d%m%Y');

			print $line->id.$separator;
			print $date.$separator;
			print $line->code_journal.$separator;
			if (empty($line->subledger_account)) {
				print $line->numero_compte.$separator;
			} else {
				print $line->subledger_account.$separator;
			}
			//print substr(length_accountg($line->numero_compte), 0, 2) . $separator;
			print '"'.dol_trunc($line->label_operation, 40, 'right', 'UTF-8', 1).'"'.$separator;
			print '"'.dol_trunc($line->piece_num, 15, 'right', 'UTF-8', 1).'"'.$separator;
			print price2num(abs($line->debit - $line->credit)).$separator;
			print $line->sens.$separator;
			print $date.$separator;
			//print 'EUR';
			print $end_line;
		}
	}


	/**
	 * Export format : Agiris Isacompta
	 *
	 * @param array $objectLines data
	 * @return void
	 */
	public function exportAgiris($objectLines)
	{

		$separator = ';';
		$end_line = "\n";

		foreach ($objectLines as $line) {
			$date = dol_print_date($line->doc_date, '%d%m%Y');

			print $line->piece_num.$separator;
			print self::toAnsi($line->label_operation).$separator;
			print $date.$separator;
			print self::toAnsi($line->label_operation).$separator;

			if (empty($line->subledger_account)) {
				print length_accountg($line->numero_compte).$separator;
				print self::toAnsi($line->label_compte).$separator;
			} else {
				print length_accounta($line->subledger_account).$separator;
				print self::toAnsi($line->subledger_label).$separator;
			}

			print self::toAnsi($line->doc_ref).$separator;
			print price($line->debit).$separator;
			print price($line->credit).$separator;
			print price(abs($line->debit - $line->credit)).$separator;
			print $line->sens.$separator;
			print $line->lettering_code.$separator;
			print $line->code_journal;
			print $end_line;
		}
	}

	/**
	 * Export format : OpenConcerto
	 *
	 * @param array $objectLines data
	 * @return void
	 */
	public function exportOpenConcerto($objectLines)
	{

		$separator = ';';
		$end_line = "\n";

		foreach ($objectLines as $line) {
			$date = dol_print_date($line->doc_date, '%d/%m/%Y');

			print $date.$separator;
			print $line->code_journal.$separator;
			if (empty($line->subledger_account)) {
				print length_accountg($line->numero_compte).$separator;
			} else {
				print length_accounta($line->subledger_account).$separator;
			}
			print $line->doc_ref.$separator;
			print $line->label_operation.$separator;
			print price($line->debit).$separator;
			print price($line->credit).$separator;

			print $end_line;
		}
	}

	/**
	 * Export format : Configurable CSV
	 *
	 * @param array $objectLines data
	 * @return void
	 */
	public function exportConfigurable($objectLines)
	{
		global $conf;

		$separator = $this->separator;

		foreach ($objectLines as $line) {
			$tab = array();
			// export configurable
			$date = dol_print_date($line->doc_date, $conf->global->ACCOUNTING_EXPORT_DATE);
			$tab[] = $line->piece_num;
			$tab[] = $date;
			$tab[] = $line->doc_ref;
			$tab[] = preg_match('/'.$separator.'/', $line->label_operation) ? "'".$line->label_operation."'" : $line->label_operation;
			$tab[] = length_accountg($line->numero_compte);
			$tab[] = length_accounta($line->subledger_account);
			$tab[] = price2num($line->debit);
			$tab[] = price2num($line->credit);
			$tab[] = price2num($line->debit - $line->credit);
			$tab[] = $line->code_journal;

			print implode($separator, $tab).$this->end_line;
		}
	}

	/**
	 * Export format : FEC
	 *
	 * @param array $objectLines data
	 * @return void
	 */
	public function exportFEC($objectLines)
	{
		global $langs;

		$separator = "\t";
		$end_line = "\r\n";

		print "JournalCode".$separator;
		print "JournalLib".$separator;
		print "EcritureNum".$separator;
		print "EcritureDate".$separator;
		print "CompteNum".$separator;
		print "CompteLib".$separator;
		print "CompAuxNum".$separator;
		print "CompAuxLib".$separator;
		print "PieceRef".$separator;
		print "PieceDate".$separator;
		print "EcritureLib".$separator;
		print "Debit".$separator;
		print "Credit".$separator;
		print "EcritureLet".$separator;
		print "DateLet".$separator;
		print "ValidDate".$separator;
		print "Montantdevise".$separator;
		print "Idevise".$separator;
		print "DateLimitReglmt".$separator;
		print "NumFacture";
		print $end_line;

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

				// FEC:JournalCode
				print $line->code_journal . $separator;

				// FEC:JournalLib
				print dol_string_unaccent($langs->transnoentities($line->journal_label)) . $separator;

				// FEC:EcritureNum
				print $line->piece_num . $separator;

				// FEC:EcritureDate
				print $date_document . $separator;

				// FEC:CompteNum
				print $line->numero_compte . $separator;

				// FEC:CompteLib
				print dol_string_unaccent($line->label_compte) . $separator;

				// FEC:CompAuxNum
				print $line->subledger_account . $separator;

				// FEC:CompAuxLib
				print dol_string_unaccent($line->subledger_label) . $separator;

				// FEC:PieceRef
				print $line->doc_ref . $separator;

				// FEC:PieceDate
				print dol_string_unaccent($date_creation) . $separator;

				// FEC:EcritureLib
				// Clean label operation to prevent problem on export with tab separator & other character
				$line->label_operation = str_replace(array("\t", "\n", "\r"), " ", $line->label_operation);
				print dol_string_unaccent($line->label_operation) . $separator;

				// FEC:Debit
				print price2fec($line->debit) . $separator;

				// FEC:Credit
				print price2fec($line->credit) . $separator;

				// FEC:EcritureLet
				print $line->lettering_code . $separator;

				// FEC:DateLet
				print $date_lettering . $separator;

				// FEC:ValidDate
				print $date_validation . $separator;

				// FEC:Montantdevise
				print $line->multicurrency_amount . $separator;

				// FEC:Idevise
				print $line->multicurrency_code . $separator;

				// FEC_suppl:DateLimitReglmt
				print $date_limit_payment . $separator;

				// FEC_suppl:NumFacture
				// Clean ref invoice to prevent problem on export with tab separator & other character
				$refInvoice = str_replace(array("\t", "\n", "\r"), " ", $refInvoice);
				print dol_trunc(self::toAnsi($refInvoice), 17, 'right', 'UTF-8', 1);

				print $end_line;
			}
		}
	}

	/**
	 * Export format : FEC2
	 *
	 * @param array $objectLines data
	 * @return void
	 */
	public function exportFEC2($objectLines)
	{
		global $langs;

		$separator = "\t";
		$end_line = "\r\n";

		print "JournalCode".$separator;
		print "JournalLib".$separator;
		print "EcritureNum".$separator;
		print "EcritureDate".$separator;
		print "CompteNum".$separator;
		print "CompteLib".$separator;
		print "CompAuxNum".$separator;
		print "CompAuxLib".$separator;
		print "PieceRef".$separator;
		print "PieceDate".$separator;
		print "EcritureLib".$separator;
		print "Debit".$separator;
		print "Credit".$separator;
		print "EcritureLet".$separator;
		print "DateLet".$separator;
		print "ValidDate".$separator;
		print "Montantdevise".$separator;
		print "Idevise".$separator;
		print "DateLimitReglmt".$separator;
		print "NumFacture";
		print $end_line;

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

				// FEC:JournalCode
				print $line->code_journal . $separator;

				// FEC:JournalLib
				print dol_string_unaccent($langs->transnoentities($line->journal_label)) . $separator;

				// FEC:EcritureNum
				print $line->piece_num . $separator;

				// FEC:EcritureDate
				print $date_creation . $separator;

				// FEC:CompteNum
				print length_accountg($line->numero_compte) . $separator;

				// FEC:CompteLib
				print dol_string_unaccent($line->label_compte) . $separator;

				// FEC:CompAuxNum
				print length_accounta($line->subledger_account) . $separator;

				// FEC:CompAuxLib
				print dol_string_unaccent($line->subledger_label) . $separator;

				// FEC:PieceRef
				print $line->doc_ref . $separator;

				// FEC:PieceDate
				print $date_document . $separator;

				// FEC:EcritureLib
				// Clean label operation to prevent problem on export with tab separator & other character
				$line->label_operation = str_replace(array("\t", "\n", "\r"), " ", $line->label_operation);
				print dol_string_unaccent($line->label_operation) . $separator;

				// FEC:Debit
				print price2fec($line->debit) . $separator;

				// FEC:Credit
				print price2fec($line->credit) . $separator;

				// FEC:EcritureLet
				print $line->lettering_code . $separator;

				// FEC:DateLet
				print $date_lettering . $separator;

				// FEC:ValidDate
				print $date_validation . $separator;

				// FEC:Montantdevise
				print $line->multicurrency_amount . $separator;

				// FEC:Idevise
				print $line->multicurrency_code . $separator;

				// FEC_suppl:DateLimitReglmt
				print $date_limit_payment . $separator;

				// FEC_suppl:NumFacture
				// Clean ref invoice to prevent problem on export with tab separator & other character
				$refInvoice = str_replace(array("\t", "\n", "\r"), " ", $refInvoice);
				print dol_trunc(self::toAnsi($refInvoice), 17, 'right', 'UTF-8', 1);


				print $end_line;
			}
		}
	}

	/**
	 * Export format : SAGE50SWISS
	 *
	 * https://onlinehelp.sageschweiz.ch/default.aspx?tabid=19984
	 * http://media.topal.ch/Public/Schnittstellen/TAF/Specification/Sage50-TAF-format.pdf
	 *
	 * @param array $objectLines data
	 *
	 * @return void
	 */
	public function exportSAGE50SWISS($objectLines)
	{
		// SAGE50SWISS
		$this->separator = ',';
		$this->end_line = "\r\n";

		// Print header line
		print "Blg,Datum,Kto,S/H,Grp,GKto,SId,SIdx,KIdx,BTyp,MTyp,Code,Netto,Steuer,FW-Betrag,Tx1,Tx2,PkKey,OpId,Flag";
		print $this->end_line;
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

			//Blg
			print $line->piece_num.$this->separator;

			// Datum
			$date = dol_print_date($line->doc_date, '%d.%m.%Y');
			print $date.$this->separator;

			// Kto
			print length_accountg($line->numero_compte).$this->separator;
			// S/H
			if ($line->sens == 'D') {
				print 'S'.$this->separator;
			} else {
				print 'H'.$this->separator;
			}
			//Grp
			print self::trunc($line->code_journal, 1).$this->separator;
			// GKto
			if (empty($line->code_tiers)) {
				if ($line->piece_num == $thisPieceNum) {
					print length_accounta($thisPieceAccountNr).$this->separator;
				} else {
					print "div".$this->separator;
				}
			} else {
				print length_accounta($line->code_tiers).$this->separator;
			}
			//SId
			print $this->separator;
			//SIdx
			print "0".$this->separator;
			//KIdx
			print "0".$this->separator;
			//BTyp
			print "0".$this->separator;

			//MTyp 1=Fibu Einzelbuchung 2=Sammebuchung
			if ($sammelBuchung) {
				print "2".$this->separator;
			} else {
				print "1".$this->separator;
			}
			// Code
			print '""'.$this->separator;
			// Netto
			print abs($line->debit - $line->credit).$this->separator;
			// Steuer
			print "0.00".$this->separator;
			// FW-Betrag
			print "0.00".$this->separator;
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

			print '"'.self::toAnsi($line1).'"'.$this->separator;
			// Tx2
			print '"'.self::toAnsi($line2).'"'.$this->separator;
			//PkKey
			print "0".$this->separator;
			//OpId
			print $this->separator;

			// Flag
			print "0";

			print $this->end_line;

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
	 * @param array $objectLines data
	 *
	 * @return void
	 */
	public function exportLDCompta($objectLines)
	{

		$separator = ';';
		$end_line = "\r\n";

		foreach ($objectLines as $line) {
			$date_document = dol_print_date($line->doc_date, '%Y%m%d');
			$date_creation = dol_print_date($line->date_creation, '%Y%m%d');
			$date_lim_reglement = dol_print_date($line->date_lim_reglement, '%Y%m%d');

			// TYPE
			$type_enregistrement = 'E'; // For write movement
			print $type_enregistrement.$separator;
			// JNAL
			print substr($line->code_journal, 0, 2).$separator;
			// NECR
			print $line->id.$separator;
			// NPIE
			print $line->piece_num.$separator;
			// DATP
			print $date_document.$separator;
			// LIBE
			print $line->label_operation.$separator;
			// DATH
			print $date_lim_reglement.$separator;
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
			print $nature_piece.$separator;
			// RACI
			//			if (! empty($line->subledger_account)) {
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

			print $racine_subledger_account.$separator; // deprecated CPTG & CPTA use instead
			// MONT
			print price(abs($line->debit - $line->credit), 0, '', 1, 2, 2).$separator;
			// CODC
			print $line->sens.$separator;
			// CPTG
			print length_accountg($line->numero_compte).$separator;
			// DATE
			print $date_creation.$separator;
			// CLET
			print $line->lettering_code.$separator;
			// DATL
			print $line->date_lettering.$separator;
			// CPTA
			if (!empty($line->subledger_account)) {
				print length_accounta($line->subledger_account).$separator;
			} else {
				print $separator;
			}
			// CNAT
			if ($line->doc_type == 'supplier_invoice' && !empty($line->subledger_account)) {
				print 'F'.$separator;
			} elseif ($line->doc_type == 'customer_invoice' && !empty($line->subledger_account)) {
				print 'C'.$separator;
			} else {
				print $separator;
			}
			// SECT
			print $separator;
			// CTRE
			print $separator;
			// NORL
			print $separator;
			// DATV
			print $separator;
			// REFD
			print $line->doc_ref.$separator;
			// CODH
			print $separator;
			// NSEQ
			print $separator;
			// MTDV
			print '0'.$separator;
			// CODV
			print $separator;
			// TXDV
			print '0'.$separator;
			// MOPM
			print $separator;
			// BONP
			print $separator;
			// BQAF
			print $separator;
			// ECES
			print $separator;
			// TXTL
			print $separator;
			// ECRM
			print $separator;
			// DATK
			print $separator;
			// HEUK
			print $separator;

			print $end_line;
		}
	}

	/**
	 * Export format : LD Compta version 10 & higher
	 * Last review for this format : 08-15-2021 Alexandre Spangaro (aspangaro@open-dsi.fr)
	 *
	 * Help : http://www.ldsysteme.fr/fileadmin/telechargement/np/ldcompta/Documentation/IntCptW10.pdf
	 *
	 * @param array $objectLines data
	 *
	 * @return void
	 */
	public function exportLDCompta10($objectLines)
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

					$type_enregistrement = 'C';
					//TYPE
					print $type_enregistrement.$separator;
					//NOCL
					print $soc->code_client.$separator;
					//NMCM
					print $separator;
					//LIBI
					print $separator;
					//TITR
					print $separator;
					//RSSO
					print $soc->nom.$separator;
					//CAD1
					print  $address[0].$separator;
					//CAD2
					print  $address[1].$separator;
					//CAD3
					print  $address[2].$separator;
					//COPO
					print  $soc->zip.$separator;
					//BUDI
					print  substr($soc->town, 0, 40).$separator;
					//CPAY
					print  $separator;
					//PAYS
					print  substr(getCountry($soc->fk_pays), 0, 40).$separator;
					//NTEL
					print $soc->phone.$separator;
					//TLEX
					print $separator;
					//TLPO
					print $separator;
					//TLCY
					print $separator;
					//NINT
					print $separator;
					//COMM
					print $separator;
					//SIRE
					print str_replace(" ", "", $soc->siret).$separator;
					//RIBP
					print $separator;
					//DOBQ
					print $separator;
					//IBBQ
					print $separator;
					//COBQ
					print $separator;
					//GUBQ
					print $separator;
					//CPBQ
					print $separator;
					//CLBQ
					print $separator;
					//BIBQ
					print $separator;
					//MOPM
					print $separator;
					//DJPM
					print $separator;
					//DMPM
					print $separator;
					//REFM
					print $separator;
					//SLVA
					print $separator;
					//PLCR
					print $separator;
					//ECFI
					print $separator;
					//CREP
					print $separator;
					//NREP
					print $separator;
					//TREP
					print $separator;
					//MREP
					print $separator;
					//GRRE
					print $separator;
					//LTTA
					print $separator;
					//CACT
					print $separator;
					//CODV
					print $separator;
					//GRTR
					print $separator;
					//NOFP
					print $separator;
					//BQAF
					print $separator;
					//BONP
					print $separator;
					//CESC
					print $separator;

					print $end_line;
				}
			}

			$date_document = dol_print_date($line->doc_date, '%Y%m%d');
			$date_creation = dol_print_date($line->date_creation, '%Y%m%d');
			$date_lim_reglement = dol_print_date($line->date_lim_reglement, '%Y%m%d');

			// TYPE E
			$type_enregistrement = 'E'; // For write movement
			print $type_enregistrement.$separator;
			// JNAL
			print substr($line->code_journal, 0, 2).$separator;
			// NECR
			print $line->id.$separator;
			// NPIE
			print $line->piece_num.$separator;
			// DATP
			print $date_document.$separator;
			// LIBE
			print dol_trunc($line->label_operation, 25, 'right', 'UTF-8', 1).$separator;
			// DATH
			print $date_lim_reglement.$separator;
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
			print $nature_piece.$separator;
			// RACI
			//			if (! empty($line->subledger_account)) {
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

			print $racine_subledger_account.$separator; // deprecated CPTG & CPTA use instead
			// MONT
			print price(abs($line->debit - $line->credit), 0, '', 1, 2).$separator;
			// CODC
			print $line->sens.$separator;
			// CPTG
			print length_accountg($line->numero_compte).$separator;
			// DATE
			print $date_document.$separator;
			// CLET
			print $line->lettering_code.$separator;
			// DATL
			print $line->date_lettering.$separator;
			// CPTA
			if (!empty($line->subledger_account)) {
				print length_accounta($line->subledger_account).$separator;
			} else {
				print $separator;
			}
			// CNAT
			if ($line->doc_type == 'supplier_invoice' && !empty($line->subledger_account)) {
				print 'F'.$separator;
			} elseif ($line->doc_type == 'customer_invoice' && !empty($line->subledger_account)) {
				print 'C'.$separator;
			} else {
				print $separator;
			}
			// CTRE
			print $separator;
			// NORL
			print $separator;
			// DATV
			print $separator;
			// REFD
			print $line->doc_ref.$separator;
			// NECA
			print '0'.$separator;
			// CSEC
			print $separator;
			// CAFF
			print $separator;
			// CDES
			print $separator;
			// QTUE
			print $separator;
			// MTDV
			print '0'.$separator;
			// CODV
			print $separator;
			// TXDV
			print '0'.$separator;
			// MOPM
			print $separator;
			// BONP
			print $separator;
			// BQAF
			print $separator;
			// ECES
			print $separator;
			// TXTL
			print $separator;
			// ECRM
			print $separator;
			// DATK
			print $separator;
			// HEUK
			print $separator;

			print $end_line;

			$last_codeinvoice = $line->doc_ref;
		}
	}

	/**
	 * Export format : Charlemagne
	 *
	 * @param array $objectLines data
	 * @return void
	 */
	public function exportCharlemagne($objectLines)
	{
		global $langs;
		$langs->load('compta');

		$separator = "\t";
		$end_line = "\n";

		/*
		 * Charlemagne export need header
		 */
		print $langs->transnoentitiesnoconv('Date').$separator;
		print self::trunc($langs->transnoentitiesnoconv('Journal'), 6).$separator;
		print self::trunc($langs->transnoentitiesnoconv('Account'), 15).$separator;
		print self::trunc($langs->transnoentitiesnoconv('LabelAccount'), 60).$separator;
		print self::trunc($langs->transnoentitiesnoconv('Piece'), 20).$separator;
		print self::trunc($langs->transnoentitiesnoconv('LabelOperation'), 60).$separator;
		print $langs->transnoentitiesnoconv('Amount').$separator;
		print 'S'.$separator;
		print self::trunc($langs->transnoentitiesnoconv('Analytic').' 1', 15).$separator;
		print self::trunc($langs->transnoentitiesnoconv('AnalyticLabel').' 1', 60).$separator;
		print self::trunc($langs->transnoentitiesnoconv('Analytic').' 2', 15).$separator;
		print self::trunc($langs->transnoentitiesnoconv('AnalyticLabel').' 2', 60).$separator;
		print self::trunc($langs->transnoentitiesnoconv('Analytic').' 3', 15).$separator;
		print self::trunc($langs->transnoentitiesnoconv('AnalyticLabel').' 3', 60).$separator;
		print $end_line;

		foreach ($objectLines as $line) {
			$date = dol_print_date($line->doc_date, '%Y%m%d');
			print $date.$separator; //Date

			print self::trunc($line->code_journal, 6).$separator; //Journal code

			if (!empty($line->subledger_account)) {
				$account = $line->subledger_account;
			} else {
				$account = $line->numero_compte;
			}
			print self::trunc($account, 15).$separator; //Account number

			print self::trunc($line->label_compte, 60).$separator; //Account label
			print self::trunc($line->doc_ref, 20).$separator; //Piece
			// Clean label operation to prevent problem on export with tab separator & other character
			$line->label_operation = str_replace(array("\t", "\n", "\r"), " ", $line->label_operation);
			print self::trunc($line->label_operation, 60).$separator; //Operation label
			print price(abs($line->debit - $line->credit)).$separator; //Amount
			print $line->sens.$separator; //Direction
			print $separator; //Analytic
			print $separator; //Analytic
			print $separator; //Analytic
			print $separator; //Analytic
			print $separator; //Analytic
			print $separator; //Analytic
			print $end_line;
		}
	}

	/**
	 * Export format : Gestimum V3
	 *
	 * @param array $objectLines data
	 *
	 * @return void
	 */
	public function exportGestimumV3($objectLines)
	{
		global $langs;

		$this->separator = ',';

		$invoices_infos = array();
		$supplier_invoices_infos = array();
		foreach ($objectLines as $line) {
			if ($line->debit == 0 && $line->credit == 0) {
				//unset($array[$line]);
			} else {
				$date = dol_print_date($line->doc_date, '%d/%m/%Y');

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

				print $line->id . $this->separator;
				print $date . $this->separator;
				print substr($line->code_journal, 0, 4) . $this->separator;

				if ((substr($line->numero_compte, 0, 3) == '411') || (substr($line->numero_compte, 0, 3) == '401')) {
					print length_accountg($line->subledger_account) . $this->separator;
				} else {
					print substr(length_accountg($line->numero_compte), 0, 15) . $this->separator;
				}
				//Libellé Auto
				print $this->separator;
				//print '"'.dol_trunc(str_replace('"', '', $line->label_operation),40,'right','UTF-8',1).'"' . $this->separator;
				//Libellé manuel
				print dol_trunc(str_replace('"', '', $invoice_ref . (!empty($company_name) ? ' - ' : '') . $company_name), 40, 'right', 'UTF-8', 1) . $this->separator;
				//Numéro de pièce
				print dol_trunc(str_replace('"', '', $line->piece_num), 10, 'right', 'UTF-8', 1) . $this->separator;
				//Devise
				print 'EUR' . $this->separator;
				//Amount
				print price2num(abs($line->debit - $line->credit)) . $this->separator;
				//Sens
				print $line->sens . $this->separator;
				//Code lettrage
				print $this->separator;
				//Date Echéance
				print $date;
				print $this->end_line;
			}
		}
	}

	/**
	 * Export format : Gestimum V5
	 *
	 * @param array $objectLines data
	 *
	 * @return void
	 */
	public function exportGestimumV5($objectLines)
	{

		$this->separator = ',';

		foreach ($objectLines as $line) {
			if ($line->debit == 0 && $line->credit == 0) {
				//unset($array[$line]);
			} else {
				$date = dol_print_date($line->doc_date, '%d%m%Y');

				print $line->id . $this->separator;
				print $date . $this->separator;
				print substr($line->code_journal, 0, 4) . $this->separator;
				if ((substr($line->numero_compte, 0, 3) == '411') || (substr($line->numero_compte, 0, 3) == '401')) {	// TODO No hard code value
					print length_accountg($line->subledger_account) . $this->separator;
				} else {
					print substr(length_accountg($line->numero_compte), 0, 15) . $this->separator;
				}
				print $this->separator;
				//print '"'.dol_trunc(str_replace('"', '', $line->label_operation),40,'right','UTF-8',1).'"' . $this->separator;
				print '"' . dol_trunc(str_replace('"', '', $line->doc_ref), 40, 'right', 'UTF-8', 1) . '"' . $this->separator;
				print '"' . dol_trunc(str_replace('"', '', $line->piece_num), 10, 'right', 'UTF-8', 1) . '"' . $this->separator;
				print price2num(abs($line->debit - $line->credit)) . $this->separator;
				print $line->sens . $this->separator;
				print $date . $this->separator;
				print $this->separator;
				print $this->separator;
				print 'EUR';
				print $this->end_line;
			}
		}
	}

	/**
	* Export format : iSuite Expert
	*
	* by OpenSolus [https://opensolus.fr]
	*
	* @param array $objectLines data
	*
	* @return void
	*/
	public function exportiSuiteExpert($objectLines)
	{
		$this->separator = ';';
		$this->end_line = "\r\n";


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

			//Création du numéro de client générique
			$numero_cpt_client = '411';
			for ($i = 1; $i <= ($taille_numero - 3); $i++) {
				$numero_cpt_client .= '0';
			}

			//Création des comptes auxiliaire des clients
			if (length_accountg($line->numero_compte) == $numero_cpt_client) {
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

			$separator = $this->separator;
			print implode($separator, $tab) . $this->end_line;
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
			$retVal = mb_substr($retVal, 0, $size, 'Windows-1251');
		}
		return $retVal;
	}
}
