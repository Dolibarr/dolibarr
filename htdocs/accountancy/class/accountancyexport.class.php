<?php
/*
 * Copyright (C) 2007-2012  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2014       Juanjo Menent       <jmenent@2byte.es>
 * Copyright (C) 2015       Florian Henry       <florian.henry@open-concept.pro>
 * Copyright (C) 2015       Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2016       Pierre-Henry Favre  <phf@atm-consulting.fr>
 * Copyright (C) 2016-2018  Alexandre Spangaro  <aspangaro@open-dsi.fr>
 * Copyright (C) 2013-2017  Olivier Geffroy     <jeff@jeffinfo.com>
 * Copyright (C) 2017       Elarifr. Ari Elbaz  <github@accedinfo.com>
 * Copyright (C) 2017-2019  Frédéric France     <frederic.france@netlogic.fr>

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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file		htdocs/accountancy/class/accountancyexport.class.php
 * \ingroup		Advanced accountancy
 * \brief 		Class accountancy export
 */

/**
 * Class AccountancyExport
 *
 * Manage the different format accountancy export
 */

require_once DOL_DOCUMENT_ROOT . '/core/lib/functions.lib.php';

class AccountancyExport
{
	/**
	 * Type of export. Defined by $conf->global->ACCOUNTING_EXPORT_MODELCSV
	 */
	public static $EXPORT_TYPE_NORMAL = 1;	 			// CSV
	public static $EXPORT_TYPE_CONFIGURABLE = 10;		// CSV
	public static $EXPORT_TYPE_CEGID = 2;
	public static $EXPORT_TYPE_COALA = 3;
	public static $EXPORT_TYPE_BOB50 = 4;
	public static $EXPORT_TYPE_CIEL = 5;
	public static $EXPORT_TYPE_QUADRATUS = 6;
	public static $EXPORT_TYPE_EBP = 7;
	public static $EXPORT_TYPE_COGILOG = 8;
	public static $EXPORT_TYPE_AGIRIS = 9;
	public static $EXPORT_TYPE_FEC = 11;


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
	public function __construct(DoliDB &$db)
	{
		global $conf;

		$this->db = &$db;
		$this->separator = $conf->global->ACCOUNTING_EXPORT_SEPARATORCSV;
		$this->end_line = empty($conf->global->ACCOUNTING_EXPORT_ENDLINE)?"\n":($conf->global->ACCOUNTING_EXPORT_ENDLINE==1?"\n":"\r\n");
	}

	/**
	 * Array with all export type available (key + label)
	 *
	 * @return array of type
	 */
	public static function getType()
	{
		global $langs;

		return array (
			//self::$EXPORT_TYPE_NORMAL => $langs->trans('Modelcsv_normal'),
			self::$EXPORT_TYPE_CONFIGURABLE => $langs->trans('Modelcsv_configurable'),
			self::$EXPORT_TYPE_CEGID => $langs->trans('Modelcsv_CEGID'),
			self::$EXPORT_TYPE_COALA => $langs->trans('Modelcsv_COALA'),
			self::$EXPORT_TYPE_BOB50 => $langs->trans('Modelcsv_bob50'),
			self::$EXPORT_TYPE_CIEL => $langs->trans('Modelcsv_ciel'),
			self::$EXPORT_TYPE_QUADRATUS => $langs->trans('Modelcsv_quadratus'),
			self::$EXPORT_TYPE_EBP => $langs->trans('Modelcsv_ebp'),
			self::$EXPORT_TYPE_COGILOG => $langs->trans('Modelcsv_cogilog'),
			self::$EXPORT_TYPE_AGIRIS => $langs->trans('Modelcsv_agiris'),
			self::$EXPORT_TYPE_FEC => $langs->trans('Modelcsv_FEC'),
		);
	}

	/**
	 * Return string to summarize the format (Used to generated export filename)
	 *
	 * @param	int		$type		Format id
	 * @return 	string				Format code
	 */
	private static function getFormatCode($type)
	{
		$formatcode = array (
			//self::$EXPORT_TYPE_NORMAL => 'csv',
			self::$EXPORT_TYPE_CONFIGURABLE => 'csv',
			self::$EXPORT_TYPE_CEGID => 'cegid',
			self::$EXPORT_TYPE_COALA => 'coala',
			self::$EXPORT_TYPE_BOB50 => 'bob50',
			self::$EXPORT_TYPE_CIEL => 'ciel',
			self::$EXPORT_TYPE_QUADRATUS => 'quadratus',
			self::$EXPORT_TYPE_EBP => 'ebp',
			self::$EXPORT_TYPE_COGILOG => 'cogilog',
			self::$EXPORT_TYPE_AGIRIS => 'agiris',
			self::$EXPORT_TYPE_FEC => 'fec',
		);

		return $formatcode[$type];
	}

	/**
	 * Array with all export type available (key + label) and parameters for config
	 *
	 * @return array of type
	 */
	public static function getTypeConfig()
	{
		global $conf, $langs;

		return array (
			'param' => array(
				/*self::$EXPORT_TYPE_NORMAL => array(
					'label' => $langs->trans('Modelcsv_normal'),
					'ACCOUNTING_EXPORT_FORMAT' => empty($conf->global->ACCOUNTING_EXPORT_FORMAT)?'txt':$conf->global->ACCOUNTING_EXPORT_FORMAT,
					'ACCOUNTING_EXPORT_SEPARATORCSV' => empty($conf->global->ACCOUNTING_EXPORT_SEPARATORCSV)?',':$conf->global->ACCOUNTING_EXPORT_SEPARATORCSV,
					'ACCOUNTING_EXPORT_ENDLINE' => empty($conf->global->ACCOUNTING_EXPORT_ENDLINE)?1:$conf->global->ACCOUNTING_EXPORT_ENDLINE,
					'ACCOUNTING_EXPORT_DATE' => empty($conf->global->ACCOUNTING_EXPORT_DATE)?'%d%m%Y':$conf->global->ACCOUNTING_EXPORT_DATE,
				),*/
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
				self::$EXPORT_TYPE_EBP => array(
					'label' => $langs->trans('Modelcsv_ebp'),
				),
				self::$EXPORT_TYPE_COGILOG => array(
					'label' => $langs->trans('Modelcsv_cogilog'),
				),
				self::$EXPORT_TYPE_AGIRIS => array(
					'label' => $langs->trans('Modelcsv_agiris'),
				),
				self::$EXPORT_TYPE_CONFIGURABLE => array(
					'label' => $langs->trans('Modelcsv_configurable'),
					'ACCOUNTING_EXPORT_FORMAT' => empty($conf->global->ACCOUNTING_EXPORT_FORMAT)?'txt':$conf->global->ACCOUNTING_EXPORT_FORMAT,
					'ACCOUNTING_EXPORT_SEPARATORCSV' => empty($conf->global->ACCOUNTING_EXPORT_SEPARATORCSV)?',':$conf->global->ACCOUNTING_EXPORT_SEPARATORCSV,
					'ACCOUNTING_EXPORT_ENDLINE' => empty($conf->global->ACCOUNTING_EXPORT_ENDLINE)?1:$conf->global->ACCOUNTING_EXPORT_ENDLINE,
					'ACCOUNTING_EXPORT_DATE' => empty($conf->global->ACCOUNTING_EXPORT_DATE)?'%d%m%Y':$conf->global->ACCOUNTING_EXPORT_DATE,
				),
				self::$EXPORT_TYPE_FEC => array(
					'label' => $langs->trans('Modelcsv_FEC'),
					'ACCOUNTING_EXPORT_FORMAT' => 'txt',
				),
			),
			'cr'=> array (
				'1' => $langs->trans("Unix"),
				'2' => $langs->trans("Windows")
			),
			'format' => array (
				'csv' => $langs->trans("csv"),
				'txt' => $langs->trans("txt")
			),
		);
	}


	/**
	 * Function who chose which export to use with the default config, and make the export into a file
	 *
	 * @param array		$TData 		data
	 * @return void
	 */
	public function export(&$TData)
	{
		global $conf, $langs;
		global $search_date_end;	// Used into /accountancy/tpl/export_journal.tpl.php

		// Define name of file to save
		$filename = 'general_ledger-'.$this->getFormatCode($conf->global->ACCOUNTING_EXPORT_MODELCSV);
		$type_export = 'general_ledger';

		include DOL_DOCUMENT_ROOT . '/accountancy/tpl/export_journal.tpl.php';


		switch ($conf->global->ACCOUNTING_EXPORT_MODELCSV) {
			case self::$EXPORT_TYPE_NORMAL :
			case self::$EXPORT_TYPE_CONFIGURABLE :
				$this->exportConfigurable($TData);
				break;
			case self::$EXPORT_TYPE_NORMAL :
			case self::$EXPORT_TYPE_CEGID :
				$this->exportCegid($TData);
				break;
			case self::$EXPORT_TYPE_COALA :
				$this->exportCoala($TData);
				break;
			case self::$EXPORT_TYPE_BOB50 :
				$this->exportBob50($TData);
				break;
			case self::$EXPORT_TYPE_CIEL :
				$this->exportCiel($TData);
				break;
			case self::$EXPORT_TYPE_QUADRATUS :
				$this->exportQuadratus($TData);
				break;
			case self::$EXPORT_TYPE_EBP :
				$this->exportEbp($TData);
				break;
			case self::$EXPORT_TYPE_COGILOG :
				$this->exportCogilog($TData);
				break;
			case self::$EXPORT_TYPE_AGIRIS :
				$this->exportAgiris($TData);
				break;
			case self::$EXPORT_TYPE_FEC :
				$this->exportFEC($TData);
				break;
			default:
				$this->errors[] = $langs->trans('accountancy_error_modelnotfound');
				break;
		}
	}


	/**
	 * Export format : CEGID
	 *
	 * @param array $objectLines data
	 *
	 * @return void
	 */
	public function exportCegid($objectLines)
	{
		foreach ($objectLines as $line) {
			$date = dol_print_date($line->doc_date, '%d%m%Y');
			$separator = ";";
			$end_line = "\n";

			print $date . $separator;
			print $line->code_journal . $separator;
			print length_accountg($line->numero_compte) . $separator;
			print length_accounta($line->subledger_account) . $separator;
			print $line->sens . $separator;
			print price($line->montant) . $separator;
			print $line->label_operation . $separator;
			print $line->doc_ref;
			print $end_line;
		}
	}

	/**
	 * Export format : COGILOG
	 *
	 * @param array $objectLines data
	 *
	 * @return void
	 */
	public function exportCogilog($objectLines)
	{
		foreach ($objectLines as $line) {
			$date = dol_print_date($line->doc_date, '%d%m%Y');
			$separator = ";";
			$end_line = "\n";

			print $line->code_journal . $separator;
			print $date . $separator;
			print $line->piece_num . $separator;
			print length_accountg($line->numero_compte) . $separator;
			print '' . $separator;
			print $line->label_operation . $separator;
			print $date . $separator;
			if ($line->sens=='D') {
				print price($line->montant) . $separator;
				print '' . $separator;
			}elseif ($line->sens=='C') {
				print '' . $separator;
				print price($line->montant) . $separator;
			}
			print $line->doc_ref . $separator;
			print $line->label_operation . $separator;
			print $end_line;
		}
	}

	/**
	 * Export format : COALA
	 *
	 * @param array $objectLines data
	 *
	 * @return void
	 */
	public function exportCoala($objectLines)
	{
		// Coala export
		$separator = ";";
		$end_line = "\n";

		foreach ($objectLines as $line) {
			$date = dol_print_date($line->doc_date, '%d/%m/%Y');
			print $date . $separator;
			print $line->code_journal . $separator;
			print length_accountg($line->numero_compte) . $separator;
			print $line->piece_num . $separator;
			print $line->doc_ref . $separator;
			print price($line->debit) . $separator;
			print price($line->credit) . $separator;
			print 'E' . $separator;
			print length_accountg($line->subledger_account) . $separator;
			print $end_line;
		}
	}

	/**
	 * Export format : BOB50
	 *
	 * @param array $objectLines data
	 *
	 * @return void
	 */
	public function exportBob50($objectLines)
	{

		// Bob50
		$separator = ";";
		$end_line = "\n";

		foreach ($objectLines as $line) {
			print $line->piece_num . $separator;
			$date = dol_print_date($line->doc_date, '%d/%m/%Y');
			print $date . $separator;

			if (empty($line->subledger_account)) {
				print 'G' . $separator;
				print length_accounta($line->numero_compte) . $separator;
			} else {
				if (substr($line->numero_compte, 0, 3) == '411') {
					print 'C' . $separator;
				}
				if (substr($line->numero_compte, 0, 3) == '401') {
					print 'F' . $separator;
				}
				print length_accountg($line->subledger_account) . $separator;
			}

			print price($line->debit) . $separator;
			print price($line->credit) . $separator;
			print dol_trunc($line->label_operation, 32) . $separator;
			print $end_line;
		}
	}

	/**
	 * Export format : CIEL
	 *
	 * @param array $TData data
	 *
	 * @return void
	 */
	public function exportCiel(&$TData)
	{
		global $conf;

		$end_line ="\r\n";

		$i = 1;
		$date_ecriture = dol_print_date(dol_now(), $conf->global->ACCOUNTING_EXPORT_DATE); // format must be yyyymmdd
		foreach ($TData as $data) {
			$code_compta = $data->numero_compte;
			if (! empty($data->subledger_account))
				$code_compta = $data->subledger_account;

			$Tab = array ();
			$Tab['num_ecriture'] = str_pad($i, 5);
			$Tab['code_journal'] = str_pad($data->code_journal, 2);
			$Tab['date_ecriture'] = $date_ecriture;
			$Tab['date_ope'] = dol_print_date($data->doc_date, $conf->global->ACCOUNTING_EXPORT_DATE);
			$Tab['num_piece'] = str_pad(self::trunc($data->piece_num, 12), 12);
			$Tab['num_compte'] = str_pad(self::trunc($code_compta, 11), 11);
			$Tab['libelle_ecriture'] = str_pad(self::trunc(dol_string_unaccent($data->doc_ref) . dol_string_unaccent($data->label_operation), 25), 25);
			$Tab['montant'] = str_pad(abs($data->montant), 13, ' ', STR_PAD_LEFT);
			$Tab['type_montant'] = str_pad($data->sens, 1);
			$Tab['vide'] = str_repeat(' ', 18);
			$Tab['intitule_compte'] = str_pad(self::trunc(dol_string_unaccent($data->label_operation), 34), 34);
			$Tab['end'] = 'O2003';

			$Tab['end_line'] = $end_line;

			print implode($Tab);
			$i ++;
		}
	}

	/**
	 * Export format : Quadratus
	 *
	 * @param array $TData data
	 *
	 * @return void
	 */
	public function exportQuadratus(&$TData)
	{
		global $conf;

		$end_line ="\r\n";

		//We should use dol_now function not time however this is wrong date to transfert in accounting
		//$date_ecriture = dol_print_date(dol_now(), $conf->global->ACCOUNTING_EXPORT_DATE); // format must be ddmmyy
		//$date_ecriture = dol_print_date(time(), $conf->global->ACCOUNTING_EXPORT_DATE); // format must be ddmmyy
		foreach ($TData as $data) {
			$code_compta = $data->numero_compte;
			if (! empty($data->subledger_account))
				$code_compta = $data->subledger_account;

			$Tab = array ();
			$Tab['type_ligne'] = 'M';
			$Tab['num_compte'] = str_pad(self::trunc($code_compta, 8), 8);
			$Tab['code_journal'] = str_pad(self::trunc($data->code_journal, 2), 2);
			$Tab['folio'] = '000';

			//We use invoice date $data->doc_date not $date_ecriture which is the transfert date
			//maybe we should set an option for customer who prefer to keep in accounting software the tranfert date instead of invoice date ?
			//$Tab['date_ecriture'] = $date_ecriture;
			$Tab['date_ecriture'] = dol_print_date($data->doc_date, '%d%m%y');
			$Tab['filler'] = ' ';
			$Tab['libelle_ecriture'] = str_pad(self::trunc(dol_string_unaccent($data->doc_ref) . ' ' . dol_string_unaccent($data->label_operation), 20), 20);
			$Tab['sens'] = $data->sens; // C or D
			$Tab['signe_montant'] = '+';

			//elarifr le montant doit etre en centimes sans point decimal !
			$Tab['montant'] = str_pad(abs($data->montant*100), 12, '0', STR_PAD_LEFT); // TODO manage negative amount
			// $Tab['montant'] = str_pad(abs($data->montant), 12, '0', STR_PAD_LEFT); // TODO manage negative amount
			$Tab['contrepartie'] = str_repeat(' ', 8);

			// elarifr:  date format must be fixed format : 6 char ddmmyy = %d%m%yand not defined by user / dolibarr setting
			if (! empty($data->date_echeance))
				//$Tab['date_echeance'] = dol_print_date($data->date_echeance, $conf->global->ACCOUNTING_EXPORT_DATE);
				$Tab['date_echeance'] = dol_print_date($data->date_echeance, '%d%m%y');	 // elarifr:  format must be ddmmyy
			else
				$Tab['date_echeance'] = '000000';

			//elarifr please keep quadra named field lettrage(2) + codestat(3) instead of fake lettrage(5)
			//$Tab['lettrage'] = str_repeat(' ', 5);
			$Tab['lettrage'] = str_repeat(' ', 2);
			$Tab['codestat'] = str_repeat(' ', 3);
			$Tab['num_piece'] = str_pad(self::trunc($data->piece_num, 5), 5);

			//elarifr keep correct quadra named field instead of anon filler
			//$Tab['filler2'] = str_repeat(' ', 20);
			$Tab['affaire'] = str_repeat(' ', 10);
			$Tab['quantity1'] = str_repeat(' ', 10);
			$Tab['num_piece2'] = str_pad(self::trunc($data->piece_num, 8), 8);
			$Tab['devis'] = str_pad($conf->currency, 3);
			$Tab['code_journal2'] = str_pad(self::trunc($data->code_journal, 3), 3);
			$Tab['filler3'] = str_repeat(' ', 3);

			//elarifr keep correct quadra named field instead of anon filler libelle_ecriture2 is 30 char not 32 !!!!
			//as we use utf8, we must remove accent to have only one ascii char instead of utf8 2 chars for specials that report wrong line size that will exceed import format spec
			//todo we should filter more than only accent to avoid wrong line size
			//TODO: remove invoice number doc_ref in libelle,
			//TODO: we should offer an option for customer to build the libelle using invoice number / name / date in accounting software
			//$Tab['libelle_ecriture2'] = str_pad(self::trunc(dol_string_unaccent($data->doc_ref) . ' ' . dol_string_unaccent($data->label_operation), 30), 30);
			$Tab['libelle_ecriture2'] = str_pad(self::trunc(dol_string_unaccent($data->label_operation), 30), 30);
			$Tab['codetva'] = str_repeat(' ', 2);

			//elarifr we need to keep the 10 lastest number of invoice doc_ref not the beginning part that is the unusefull almost same part
			//$Tab['num_piece3'] = str_pad(self::trunc($data->piece_num, 10), 10);
			$Tab['num_piece3'] = substr(self::trunc($data->doc_ref, 20), -10);
			$Tab['filler4'] = str_repeat(' ', 73);

			$Tab['end_line'] = $end_line;

			print implode($Tab);
		}
	}


	/**
	 * Export format : EBP
	 *
	 * @param array $objectLines data
	 *
	 * @return void
	 */
	public function exportEbp($objectLines)
	{

		$separator = ',';
		$end_line = "\n";

		foreach ($objectLines as $line) {

			$date = dol_print_date($line->doc_date, '%d%m%Y');

			print $line->id . $separator;
			print $date . $separator;
			print $line->code_journal . $separator;
			if (empty($line->subledger_account)) {
                print $line->numero_compte . $separator;
            } else {
                print $line->subledger_account . $separator;
            }
			//print substr(length_accountg($line->numero_compte), 0, 2) . $separator;
			print '"'.dol_trunc($line->label_operation, 40, 'right', 'UTF-8', 1).'"' . $separator;
			print '"'.dol_trunc($line->piece_num, 15, 'right', 'UTF-8', 1).'"'.$separator;
			print price2num($line->montant).$separator;
			print $line->sens.$separator;
			print $date . $separator;
			//print 'EUR';
			print $end_line;
		}
	}


	/**
	 * Export format : Agiris Isacompta
	 *
	 * @param array $objectLines data
	 *
	 * @return void
	 */
	public function exportAgiris($objectLines)
	{

		$separator = ';';
		$end_line = "\n";

		foreach ($objectLines as $line) {

			$date = dol_print_date($line->doc_date, '%d%m%Y');

			print $line->piece_num . $separator;
			print $line->label_operation . $separator;
			print $date . $separator;
			print $line->label_operation . $separator;

			if (empty($line->subledger_account)) {
				print length_accountg($line->numero_compte) . $separator;
				print $line->label_compte . $separator;
			} else {
				print length_accounta($line->subledger_account) . $separator;
				print $line->subledger_label . $separator;
			}

			print $line->doc_ref . $separator;
			print price($line->debit) . $separator;
			print price($line->credit) . $separator;
			print price($line->montant) . $separator;
			print $line->sens . $separator;
			print $line->lettering_code . $separator;
			print $line->code_journal;
			print $end_line;
		}
	}

	/**
	 * Export format : Configurable
	 *
	 * @param array $objectLines data
	 *
	 * @return void
	 */
	public function exportConfigurable($objectLines)
	{
		global $conf;

		foreach ($objectLines as $line) {
			$tab = array();
			// export configurable
			$date = dol_print_date($line->doc_date, $conf->global->ACCOUNTING_EXPORT_DATE);
			$tab[] = $line->piece_num;
			$tab[] = $date;
			$tab[] = $line->doc_ref;
			$tab[] = $line->label_operation;
			$tab[] = length_accountg($line->numero_compte);
			$tab[] = length_accounta($line->subledger_account);
			$tab[] = price($line->debit);
			$tab[] = price($line->credit);
			$tab[] = price($line->montant);
			$tab[] = $line->code_journal;

			$separator = $this->separator;
			print implode($separator, $tab) . $this->end_line;
		}
	}

	/**
	 * Export format : FEC
	 *
	 * @param array $objectLines data
	 *
	 * @return void
	 */
	public function exportFEC($objectLines)
	{
		$separator = "\t";
		$end_line = "\n";

		print "JournalCode" . $separator;
		print "JournalLib" . $separator;
		print "EcritureNum" . $separator;
		print "EcritureDate" . $separator;
		print "CompteNum" . $separator;
		print "CompteLib" . $separator;
		print "CompAuxNum" . $separator;
		print "CompAuxLib" . $separator;
		print "PieceRef" . $separator;
		print "PieceDate" . $separator;
		print "EcritureLib" . $separator;
		print "Debit" . $separator;
		print "Credit" . $separator;
		print "EcritureLet" . $separator;
		print "DateLet" . $separator;
		print "ValidDate" . $separator;
		print "Montantdevise" . $separator;
		print "Idevise";
		print $end_line;

		foreach ($objectLines as $line) {
			$date_creation = dol_print_date($line->date_creation, '%d%m%Y');
			$date_doc = dol_print_date($line->doc_date, '%d%m%Y');
			$date_valid = dol_print_date($line->date_validated, '%d%m%Y');

			// FEC:JournalCode
			print $line->code_journal . $separator;

			// FEC:JournalLib
			print $line->journal_label . $separator;

			// FEC:EcritureNum
			print $line->piece_num . $separator;

			// FEC:EcritureDate
			print $date_creation . $separator;

			// FEC:CompteNum
			print $line->numero_compte . $separator;

			// FEC:CompteLib
			print $line->label_compte . $separator;

			// FEC:CompAuxNum
			print $line->subledger_account . $separator;

			// FEC:CompAuxLib
			print $line->subledger_label . $separator;

			// FEC:PieceRef
			print $line->doc_ref . $separator;

			// FEC:PieceDate
			print $date_doc . $separator;

			// FEC:EcritureLib
			print $line->label_operation . $separator;

			// FEC:Debit
			print price2num($line->debit) . $separator;

			// FEC:Credit
			print price2num($line->credit) . $separator;

			// FEC:EcritureLet
			print $line->lettering_code . $separator;

			// FEC:DateLet
			print $line->date_lettering . $separator;

			// FEC:ValidDate
			print $date_valid . $separator;

			// FEC:Montantdevise
			print $line->multicurrency_amount . $separator;

			// FEC:Idevise
			print $line->multicurrency_code;

			print $end_line;
		}
	}

	/**
	 *
	 * @param string	$str 	data
	 * @param integer 	$size 	data
	 * @return string
	 */
	public static function trunc($str, $size)
	{
		return dol_trunc($str, $size, 'right', 'UTF-8', 1);
	}
}
