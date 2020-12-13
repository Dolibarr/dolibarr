<?php
/*
 * Copyright (C) 2007-2012  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2014       Juanjo Menent       <jmenent@2byte.es>
 * Copyright (C) 2015       Florian Henry       <florian.henry@open-concept.pro>
 * Copyright (C) 2015       Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2016       Pierre-Henry Favre  <phf@atm-consulting.fr>
 * Copyright (C) 2016-2019  Alexandre Spangaro  <aspangaro@open-dsi.fr>
 * Copyright (C) 2013-2017  Olivier Geffroy     <jeff@jeffinfo.com>
 * Copyright (C) 2017       Elarifr. Ari Elbaz  <github@accedinfo.com>
 * Copyright (C) 2017-2019  Frédéric France     <frederic.france@netlogic.fr>
 * Copyright (C) 2017       André Schild        <a.schild@aarboard.ch>
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
	public static $EXPORT_TYPE_OPENCONCERTO = 100;
    public static $EXPORT_TYPE_LDCOMPTA = 110;
	public static $EXPORT_TYPE_FEC = 1000;


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
		$this->end_line = empty($conf->global->ACCOUNTING_EXPORT_ENDLINE) ? "\n" : ($conf->global->ACCOUNTING_EXPORT_ENDLINE == 1 ? "\n" : "\r\n");
	}

	/**
	 * Array with all export type available (key + label)
	 *
	 * @return array of type
	 */
	public static function getType()
	{
		global $langs;

		$listofexporttypes = array(
			self::$EXPORT_TYPE_CONFIGURABLE => $langs->trans('Modelcsv_configurable'),
			self::$EXPORT_TYPE_CEGID => $langs->trans('Modelcsv_CEGID'),
			self::$EXPORT_TYPE_COALA => $langs->trans('Modelcsv_COALA'),
			self::$EXPORT_TYPE_BOB50 => $langs->trans('Modelcsv_bob50'),
			self::$EXPORT_TYPE_CIEL => $langs->trans('Modelcsv_ciel'),
			self::$EXPORT_TYPE_QUADRATUS => $langs->trans('Modelcsv_quadratus'),
			self::$EXPORT_TYPE_EBP => $langs->trans('Modelcsv_ebp'),
			self::$EXPORT_TYPE_COGILOG => $langs->trans('Modelcsv_cogilog'),
			self::$EXPORT_TYPE_AGIRIS => $langs->trans('Modelcsv_agiris'),
            self::$EXPORT_TYPE_OPENCONCERTO => $langs->trans('Modelcsv_openconcerto'),
			self::$EXPORT_TYPE_SAGE50_SWISS => $langs->trans('Modelcsv_Sage50_Swiss'),
			self::$EXPORT_TYPE_LDCOMPTA => $langs->trans('Modelcsv_LDCompta'),
			self::$EXPORT_TYPE_FEC => $langs->trans('Modelcsv_FEC'),
				self::$EXPORT_TYPE_CHARLEMAGNE => $langs->trans('Modelcsv_charlemagne'),
		);

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
			self::$EXPORT_TYPE_EBP => 'ebp',
			self::$EXPORT_TYPE_COGILOG => 'cogilog',
			self::$EXPORT_TYPE_AGIRIS => 'agiris',
			self::$EXPORT_TYPE_OPENCONCERTO => 'openconcerto',
            self::$EXPORT_TYPE_SAGE50_SWISS => 'sage50ch',
            self::$EXPORT_TYPE_LDCOMPTA => 'ldcompta',
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

		return array(
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
                    'ACCOUNTING_EXPORT_FORMAT' => 'csv',
                ),
				self::$EXPORT_TYPE_SAGE50_SWISS => array(
					'label' => $langs->trans('Modelcsv_Sage50_Swiss'),
					'ACCOUNTING_EXPORT_FORMAT' => 'csv',
				),
                self::$EXPORT_TYPE_LDCOMPTA => array(
                    'label' => $langs->trans('Modelcsv_LDCompta'),
                    'ACCOUNTING_EXPORT_FORMAT' => 'csv',
                ),
				self::$EXPORT_TYPE_FEC => array(
					'label' => $langs->trans('Modelcsv_FEC'),
					'ACCOUNTING_EXPORT_FORMAT' => 'txt',
				),
				self::$EXPORT_TYPE_CHARLEMAGNE => array(
					'label' => $langs->trans('Modelcsv_charlemagne'),
					'ACCOUNTING_EXPORT_FORMAT' => 'txt',
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

		global $db; 	// The tpl file use $db
		include DOL_DOCUMENT_ROOT.'/accountancy/tpl/export_journal.tpl.php';


		switch ($formatexportset) {
			case self::$EXPORT_TYPE_CONFIGURABLE :
				$this->exportConfigurable($TData);
				break;
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
            case self::$EXPORT_TYPE_OPENCONCERTO :
                $this->exportOpenConcerto($TData);
                break;
			case self::$EXPORT_TYPE_SAGE50_SWISS :
				$this->exportSAGE50SWISS($TData);
				break;
            case self::$EXPORT_TYPE_LDCOMPTA :
                $this->exportLDCompta($TData);
                break;
            case self::$EXPORT_TYPE_FEC :
                $this->exportFEC($TData);
                break;
			case self::$EXPORT_TYPE_CHARLEMAGNE :
				$this->exportCharlemagne($TData);
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
			print price($line->montant).$separator;
			print $line->label_operation.$separator;
			print $line->doc_ref;
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
				print price($line->montant).$separator;
				print ''.$separator;
			}elseif ($line->sens == 'C') {
				print ''.$separator;
				print price($line->montant).$separator;
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
	 * Export format : CIEL
	 *
	 * @param array $TData data
	 * @return void
	 */
	public function exportCiel(&$TData)
	{
		global $conf;

		$end_line = "\r\n";

		$i = 1;
		$date_ecriture = dol_print_date(dol_now(), $conf->global->ACCOUNTING_EXPORT_DATE); // format must be yyyymmdd
		foreach ($TData as $data) {
			$code_compta = $data->numero_compte;
			if (!empty($data->subledger_account))
				$code_compta = $data->subledger_account;

			$Tab = array();
			$Tab['num_ecriture'] = str_pad($i, 5);
			$Tab['code_journal'] = str_pad($data->code_journal, 2);
			$Tab['date_ecriture'] = $date_ecriture;
			$Tab['date_ope'] = dol_print_date($data->doc_date, $conf->global->ACCOUNTING_EXPORT_DATE);
			$Tab['num_piece'] = str_pad(self::trunc($data->piece_num, 12), 12);
			$Tab['num_compte'] = str_pad(self::trunc($code_compta, 11), 11);
			$Tab['libelle_ecriture'] = str_pad(self::trunc(dol_string_unaccent($data->doc_ref).dol_string_unaccent($data->label_operation), 25), 25);
			$Tab['montant'] = str_pad(abs($data->montant), 13, ' ', STR_PAD_LEFT);
			$Tab['type_montant'] = str_pad($data->sens, 1);
			$Tab['vide'] = str_repeat(' ', 18);
			$Tab['intitule_compte'] = str_pad(self::trunc(dol_string_unaccent($data->label_operation), 34), 34);
			$Tab['end'] = 'O2003';

			$Tab['end_line'] = $end_line;

			print implode($Tab);
			$i++;
		}
	}

	/**
	 * Export format : Quadratus
	 *
	 * @param array $TData data
	 * @return void
	 */
	public function exportQuadratus(&$TData)
	{
		global $conf;

		$end_line = "\r\n";

		//We should use dol_now function not time however this is wrong date to transfert in accounting
		//$date_ecriture = dol_print_date(dol_now(), $conf->global->ACCOUNTING_EXPORT_DATE); // format must be ddmmyy
		//$date_ecriture = dol_print_date(time(), $conf->global->ACCOUNTING_EXPORT_DATE); // format must be ddmmyy
		foreach ($TData as $data) {
			$code_compta = $data->numero_compte;
			if (!empty($data->subledger_account))
				$code_compta = $data->subledger_account;

			$Tab = array();
			$Tab['type_ligne'] = 'M';
			$Tab['num_compte'] = str_pad(self::trunc($code_compta, 8), 8);
			$Tab['code_journal'] = str_pad(self::trunc($data->code_journal, 2), 2);
			$Tab['folio'] = '000';

			//We use invoice date $data->doc_date not $date_ecriture which is the transfert date
			//maybe we should set an option for customer who prefer to keep in accounting software the tranfert date instead of invoice date ?
			//$Tab['date_ecriture'] = $date_ecriture;
			$Tab['date_ecriture'] = dol_print_date($data->doc_date, '%d%m%y');
			$Tab['filler'] = ' ';
			$Tab['libelle_ecriture'] = str_pad(self::trunc(dol_string_unaccent($data->doc_ref).' '.dol_string_unaccent($data->label_operation), 20), 20);
			$Tab['sens'] = $data->sens; // C or D
			$Tab['signe_montant'] = '+';

			//elarifr le montant doit etre en centimes sans point decimal !
			$Tab['montant'] = str_pad(abs($data->montant * 100), 12, '0', STR_PAD_LEFT); // TODO manage negative amount
			// $Tab['montant'] = str_pad(abs($data->montant), 12, '0', STR_PAD_LEFT); // TODO manage negative amount
			$Tab['contrepartie'] = str_repeat(' ', 8);

			// elarifr:  date format must be fixed format : 6 char ddmmyy = %d%m%yand not defined by user / dolibarr setting
			if (!empty($data->date_echeance))
				//$Tab['date_echeance'] = dol_print_date($data->date_echeance, $conf->global->ACCOUNTING_EXPORT_DATE);
				$Tab['date_echeance'] = dol_print_date($data->date_echeance, '%d%m%y'); // elarifr:  format must be ddmmyy
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
			print price2num(abs($line->montant)).$separator;
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
			print price($line->montant).$separator;
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
			$tab[] = price2num($line->montant);
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
		print "Idevise";
		print $end_line;

		foreach ($objectLines as $line) {
			if ($line->debit == 0 && $line->credit == 0) {
                unset($array[$line]);
            } else {
				$date_creation = dol_print_date($line->date_creation, '%Y%m%d');
				$date_document = dol_print_date($line->doc_date, '%Y%m%d');
				$date_lettering = dol_print_date($line->date_lettering, '%Y%m%d');
				$date_validation = dol_print_date($line->date_validated, '%Y%m%d');

				// FEC:JournalCode
				print $line->code_journal.$separator;

				// FEC:JournalLib
				print $line->journal_label.$separator;

				// FEC:EcritureNum
				print $line->piece_num.$separator;

				// FEC:EcritureDate
				print $date_document . $separator;

				// FEC:CompteNum
				print $line->numero_compte.$separator;

				// FEC:CompteLib
				print dol_string_unaccent($line->label_compte) . $separator;

				// FEC:CompAuxNum
				print $line->subledger_account.$separator;

				// FEC:CompAuxLib
				print dol_string_unaccent($line->subledger_label).$separator;

				// FEC:PieceRef
				print $line->doc_ref.$separator;

				// FEC:PieceDate
				print $date_creation.$separator;

				// FEC:EcritureLib
				print dol_string_unaccent($line->label_operation).$separator;

				// FEC:Debit
				print price2fec($line->debit).$separator;

				// FEC:Credit
				print price2fec($line->credit).$separator;

				// FEC:EcritureLet
				print $line->lettering_code.$separator;

				// FEC:DateLet
				print $date_lettering.$separator;

				// FEC:ValidDate
				print $date_validation.$separator;

				// FEC:Montantdevise
				print $line->multicurrency_amount.$separator;

				// FEC:Idevise
				print $line->multicurrency_code;

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
        foreach ($objectLines as $aIndex=>$line)
		{
            $sammelBuchung = false;
            if ($aIndex - 2 >= 0 && $objectLines[$aIndex - 2]->piece_num == $line->piece_num)
            {
                $sammelBuchung = true;
            }
            elseif ($aIndex + 2 < $aSize && $objectLines[$aIndex + 2]->piece_num == $line->piece_num)
            {
                $sammelBuchung = true;
            }
            elseif ($aIndex + 1 < $aSize
                    && $objectLines[$aIndex + 1]->piece_num == $line->piece_num
                    && $aIndex - 1 < $aSize
                    && $objectLines[$aIndex - 1]->piece_num == $line->piece_num
                    )
            {
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
            if ($line->sens == 'D')
            {
                print 'S'.$this->separator;
            }
            else
            {
                print 'H'.$this->separator;
            }
            //Grp
            print self::trunc($line->code_journal, 1).$this->separator;
            // GKto
            if (empty($line->code_tiers))
            {
                if ($line->piece_num == $thisPieceNum)
                {
                    print length_accounta($thisPieceAccountNr).$this->separator;
                }
                else
                {
                    print "div".$this->separator;
                }
            }
            else
            {
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
            if ($sammelBuchung)
            {
                print "2".$this->separator;
            }
            else
            {
                print "1".$this->separator;
            }
            // Code
            print '""'.$this->separator;
            // Netto
            if ($line->montant >= 0)
            {
                print $line->montant.$this->separator;
            }
            else
            {
                print ($line->montant * -1).$this->separator;
            }
            // Steuer
            print "0.00".$this->separator;
            // FW-Betrag
            print "0.00".$this->separator;
            // Tx1
            $line1 = self::toAnsi($line->label_compte, 29);
            if ($line1 == "LIQ" || $line1 == "LIQ Beleg ok" || strlen($line1) <= 3)
            {
                $line1 = "";
            }
            $line2 = self::toAnsi($line->doc_ref, 29);
            if (strlen($line1) == 0)
            {
                $line1 = $line2;
                $line2 = "";
            }
            if (strlen($line1) > 0 && strlen($line2) > 0 && (strlen($line1) + strlen($line2)) < 27)
            {
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

            if ($line->piece_num !== $thisPieceNum)
            {
                $thisPieceNum = $line->piece_num;
                $thisPieceAccountNr = $line->numero_compte;
            }
        }
    }

    /**
     * Export format : LD Compta version 9 & higher
     * http://www.ldsysteme.fr/fileadmin/telechargement/np/ldcompta/Documentation/IntCptW10.pdf
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
				if ($line->montant < 0) {
					$nature_piece = 'AF';
				} else {
					$nature_piece = 'FF';
				}
			} elseif ($line->doc_type == 'customer_invoice') {
				if ($line->montant < 0) {
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

			print $racine_subledger_account . $separator; // deprecated CPTG & CPTA use instead
			// MONT
			print price(abs($line->montant), 0, '', 1, 2, 2).$separator;
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

			if (!empty($line->subledger_account)) $account = $line->subledger_account;
			else  $account = $line->numero_compte;
			print self::trunc($account, 15).$separator; //Account number

			print self::trunc($line->label_compte, 60).$separator; //Account label
			print self::trunc($line->doc_ref, 20).$separator; //Piece
			print self::trunc($line->label_operation, 60).$separator; //Operation label
			print price(abs($line->montant)).$separator; //Amount
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
        if ($retVal >= 0 && $size >= 0)
        {
            $retVal = mb_substr($retVal, 0, $size, 'Windows-1251');
        }
        return $retVal;
	}
}
