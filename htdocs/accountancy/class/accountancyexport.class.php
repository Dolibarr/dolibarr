<?php
/* Copyright (C) 2007-2012  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2014       Juanjo Menent       <jmenent@2byte.es>
 * Copyright (C) 2015       Florian Henry       <florian.henry@open-concept.pro>
 * Copyright (C) 2015       Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2016		Pierre-Henry Favre	<phf@atm-consulting.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file    htdocs/accountancy/class/accountancyexport.class.php
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
	 * @var Type of export
	 */
	 public static $EXPORT_TYPE_NORMAL		= 1;
	 public static $EXPORT_TYPE_CEGID	 	= 2;
	 public static $EXPORT_TYPE_COALA		= 3;
	 public static $EXPORT_TYPE_BOB50		= 4;
	 public static $EXPORT_TYPE_CIEL		= 5;
	 public static $EXPORT_TYPE_QUADRATUS	= 6;

	/**
	 * @var string[] Error codes (or messages)
	 */
	public $errors = array();

	/**
	 * @var string Separator
	 */
	public $separator = '';

	/**
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
		$this->end_line = "\n";
		return 1;
	}

	/**
	 * Get all export type are available
	 *
	 * @return array of type
	 */
	public static function getType()
	{
		global $langs;

		return array (
			self::$EXPORT_TYPE_NORMAL 		=> $langs->trans('Modelcsv_normal'),
			self::$EXPORT_TYPE_CEGID 		=> $langs->trans('Modelcsv_CEGID'),
			self::$EXPORT_TYPE_COALA 		=> $langs->trans('Modelcsv_COALA'),
			self::$EXPORT_TYPE_BOB50 		=> $langs->trans('Modelcsv_bob50'),
			self::$EXPORT_TYPE_CIEL 		=> $langs->trans('Modelcsv_ciel'),
			self::$EXPORT_TYPE_QUADRATUS 	=> $langs->trans('Modelcsv_quadratus')
		);
	}

	/**
	 * Download the export
	 *
	 * @return void
	 */
	public static function downloadFile()
	{
		global $conf;
		$journal = 'bookkepping';
		include DOL_DOCUMENT_ROOT . '/accountancy/tpl/export_journal.tpl.php';
	}

	/**
	 * Function who chose which export to use with the default config
	 *
	 * @return void
	 */
	public function export(&$TData)
	{
		global $conf, $langs;

		switch ($conf->global->ACCOUNTING_EXPORT_MODELCSV) {
			case self::$EXPORT_TYPE_NORMAL:
				$this->exportNormal($TData);
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
			default:
				$this->errors[] = $langs->trans('accountancy_error_modelnotfound');
				break;
		}

		if (empty($this->errors)) self::downloadFile();
	}

	/**
	 * Export format : Normal
	 *
	 * @return void
	 */
	public function exportNormal(&$TData)
	{

	}

	/**
	 * Export format : CEGID
	 *
	 * @return void
	 */
	public function exportCegid(&$TData)
	{

	}

	/**
	 * Export format : COALA
	 *
	 * @return void
	 */
	public function exportCoala(&$TData)
	{

	}

	/**
	 * Export format : BOB50
	 *
	 * @return void
	 */
	public function exportBob50(&$TData)
	{

	}

	/**
	 * Export format : CIEL
	 *
	 * @return void
	 */
	public function exportCiel(&$TData)
	{
		global $conf;

		$i=1;
		$date_ecriture = dol_print_date(time(), $conf->global->ACCOUNTING_EXPORT_DATE); // format must be yyyymmdd
		foreach ($TData as $data)
		{
			$code_compta = $data->numero_compte;
			if (!empty($data->code_tiers)) $code_compta = $data->code_tiers;

			$Tab = array();
			$Tab['num_ecriture'] = str_pad($i, 5);
			$Tab['code_journal'] = str_pad($data->code_journal, 2);
			$Tab['date_ecriture'] = $date_ecriture;
			$Tab['date_ope'] = dol_print_date($data->doc_date, $conf->global->ACCOUNTING_EXPORT_DATE);
			$Tab['num_piece'] = str_pad(self::trunc($data->piece_num, 12), 12);
			$Tab['num_compte'] = str_pad(self::trunc($code_compta, 11), 11);
			$Tab['libelle_ecriture'] = str_pad(self::trunc($data->doc_ref.$data->label_compte, 25), 25);
			$Tab['montant'] = str_pad(abs($data->montant), 13, ' ', STR_PAD_LEFT);
			$Tab['type_montant'] = str_pad($data->sens, 1);
			$Tab['vide'] = str_repeat(' ', 18);
			$Tab['intitule_compte'] = str_pad(self::trunc($data->label_compte, 34), 34);
			$Tab['end'] = 'O2003';

			$Tab['end_line'] = $this->end_line;

			print implode($Tab);
			$i++;
		}
	}

	/**
	 * Export format : Quadratus
	 *
	 * @return void
	 */
	public function exportQuadratus(&$TData)
	{
		global $conf;

		$date_ecriture = dol_print_date(time(), $conf->global->ACCOUNTING_EXPORT_DATE); // format must be ddmmyy
		foreach ($TData as $data)
		{
			$code_compta = $data->numero_compte;
			if (!empty($data->code_tiers)) $code_compta = $data->code_tiers;

			$Tab = array();
			$Tab['type_ligne'] = 'M';
			$Tab['num_compte'] = str_pad(self::trunc($code_compta, 8), 8);
			$Tab['code_journal'] = str_pad(self::trunc($data->code_journal, 2), 2);
			$Tab['folio'] = '000';
			$Tab['date_ecriture'] = $date_ecriture;
			$Tab['filler'] = ' ';
			$Tab['libelle_ecriture'] = str_pad(self::trunc($data->doc_ref.' '.$data->label_compte, 20), 20);
			$Tab['sens'] = $data->sens; // C or D
			$Tab['signe_montant'] = '+';
			$Tab['montant'] = str_pad(abs($data->montant)*100, 12, '0', STR_PAD_LEFT); // TODO manage negative amount
			$Tab['contrepartie'] = str_repeat(' ', 8);
			if (!empty($data->date_echeance)) $Tab['date_echeance'] = dol_print_date($data->date_echeance, $conf->global->ACCOUNTING_EXPORT_DATE);
			else $Tab['date_echeance'] = '000000';
			$Tab['lettrage'] = str_repeat(' ', 5);
			$Tab['num_piece'] = str_pad(self::trunc($data->piece_num, 5), 5);
			$Tab['filler2'] = str_repeat(' ', 20);
			$Tab['num_piece2'] = str_pad(self::trunc($data->piece_num, 8), 8);
			$Tab['devis'] = str_pad($conf->currency, 3);
			$Tab['code_journal2'] = str_pad(self::trunc($data->code_journal, 3), 3);
			$Tab['filler3'] = str_repeat(' ', 3);
			$Tab['libelle_ecriture2'] = str_pad(self::trunc($data->doc_ref.' '.$data->label_compte, 32), 32);
			$Tab['num_piece3'] = str_pad(self::trunc($data->piece_num, 10), 10);
			$Tab['filler4'] = str_repeat(' ', 73);

			$Tab['end_line'] = $this->end_line;

			print implode($Tab);
		}
	}

	public static function trunc($str, $size)
	{
		return dol_trunc($str, $size, 'right', 'UTF-8', 1);
	}

}
