<?php
/*
 * Copyright (C) 2007-2012  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2014       Juanjo Menent       <jmenent@2byte.es>
 * Copyright (C) 2015       Florian Henry       <florian.henry@open-concept.pro>
 * Copyright (C) 2015       Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2016       Pierre-Henry Favre  <phf@atm-consulting.fr>
 * Copyright (C) 2016-2020  Alexandre Spangaro  <aspangaro@open-dsi.fr>
 * Copyright (C) 2013-2017  Olivier Geffroy     <jeff@jeffinfo.com>
 * Copyright (C) 2017       Elarifr. Ari Elbaz  <github@accedinfo.com>
 * Copyright (C) 2017-2024  Frédéric France     <frederic.france@free.fr>
 * Copyright (C) 2017       André Schild        <a.schild@aarboard.ch>
 * Copyright (C) 2020       Guillaume Alexandre <guillaume@tag-info.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 * \file		htdocs/accountancy/class/accountancyimport.class.php
 * \ingroup		Accountancy (Double entries)
 * \brief 		Class with methods for accountancy import
 */



/**
 * Manage the different format accountancy import
 */
class AccountancyImport
{
	/**
	 * @var DoliDB	Database handler
	 */
	public $db;

	/**
	 * @var string[]	Array of error strings
	 */
	public $errors = array();

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;
	}

	/**
	 *  Clean amount
	 *
	 * @param   array<array{val:null|int|float|string,type:int<-1,1>}>       $arrayrecord        Array of read values: [fieldpos] => (['val']=>val, ['type']=>-1=null,0=blank,1=string), [fieldpos+1]...
	 * @param   array<string,string>       $listfields         Fields list to add
	 * @param 	int			$record_key         Record key
	 * @return  float							Value
	 */
	public function cleanAmount(&$arrayrecord, $listfields, $record_key)
	{
		$value_trim = trim($arrayrecord[$record_key]['val']);
		return (float) price2num($value_trim);
	}

	/**
	 *  Clean value with trim
	 *
	 * @param   array<array{val:null|int|float|string,type:int<-1,1>}>       $arrayrecord        Array of read values: [fieldpos] => (['val']=>val, ['type']=>-1=null,0=blank,1=string), [fieldpos+1]...
	 * @param   array<string,string>       $listfields         Fields list to add
	 * @param 	int			$record_key         Record key
	 * @return  mixed							Value
	 */
	public function cleanValue(&$arrayrecord, $listfields, $record_key)
	{
		return trim($arrayrecord[$record_key]['val']);
	}

	/**
	 *  Compute amount
	 *
	 * @param   array<array{val:null|int|float|string,type:int<-1,1>}>       $arrayrecord        Array of read values: [fieldpos] => (['val']=>val, ['type']=>-1=null,0=blank,1=string), [fieldpos+1]...
	 * @param   array<string,string>       $listfields         Fields list to add
	 * @param 	int			$record_key         Record key
	 * @return  string							Value
	 */
	public function computeAmount(&$arrayrecord, $listfields, $record_key)
	{
		// get fields indexes
		if (isset($listfields['b.debit']) && isset($listfields['b.credit'])) {
			$debit_index = $listfields['b.debit'];

			$debitFloat = (float) price2num($arrayrecord[$debit_index]['val']);
			if (!empty($debitFloat)) {
				$amount = $debitFloat;
			} else {
				$credit_index = $listfields['b.credit'];
				$amount = (float) price2num($arrayrecord[$credit_index]['val']);
			}

			return "'" . $this->db->escape(abs($amount)) . "'";
		}

		return "''";
	}


	/**
	 *  Compute direction
	 *
	 * @param   array<array{val:null|int|float|string,type:int<-1,1>}>       $arrayrecord        Array of read values: [fieldpos] => (['val']=>val, ['type']=>-1=null,0=blank,1=string), [fieldpos+1]...
	 * @param   array<string,string>       $listfields         Fields list to add
	 * @param 	int			$record_key         Record key
	 * @return  string							Value
	 */
	public function computeDirection(&$arrayrecord, $listfields, $record_key)
	{
		if (isset($listfields['b.debit'])) {
			$debit_index = $listfields['b.debit'];

			$debitFloat = (float) price2num($arrayrecord[$debit_index]['val']);
			if (!empty($debitFloat)) {
				$sens = 'D';
			} else {
				$sens = 'C';
			}

			return $sens;
		}

		return "''";
	}

	/**
	 *  Compute piece number
	 *
	 * @param   array<array{val:null|int|float|string,type:int<-1,1>}>       $arrayrecord        Array of read values: [fieldpos] => (['val']=>val, ['type']=>-1=null,0=blank,1=string), [fieldpos+1]...
	 * @param   array<string,string>       $listfields         Fields list to add
	 * @param 	int			$record_key         Record key
	 * @return  string							Value
	 */
	public function computePieceNum(&$arrayrecord, $listfields, $record_key)
	{
		global $conf;

		$pieceNum = trim($arrayrecord[$record_key]['val']);

		// auto-determine the next value for piece number
		if ($pieceNum == '') {
			if (isset($listfields['b.code_journal']) && isset($listfields['b.doc_date'])) {
				// define memory for last record values and keep next piece number
				if (!isset($conf->cache['accounting'])) {
					$conf->cache['accounting'] = array(
						'lastRecordCompareValues' => array(),
						'nextPieceNum' => 0,
					);
				}
				$codeJournalIndex = $listfields['b.code_journal'];
				$docDateIndex = $listfields['b.doc_date'];
				$atLeastOneLastRecordChanged = false;
				if (empty($conf->cache['accounting']['lastRecordCompareValues'])) {
					$atLeastOneLastRecordChanged = true;
				} else {
					if ($arrayrecord[$codeJournalIndex]['val'] != $conf->cache['accounting']['lastRecordCompareValues']['b.code_journal']
						|| $arrayrecord[$docDateIndex]['val'] != $conf->cache['accounting']['lastRecordCompareValues']['b.doc_date']
					) {
						$atLeastOneLastRecordChanged = true;
					}
				}

				// at least one record value has changed, so we search for the next piece number from database or increment it
				if ($atLeastOneLastRecordChanged) {
					$lastPieceNum = 0;
					if (empty($conf->cache['accounting']['nextPieceNum'])) {
						// get last piece number from database
						$sql = "SELECT MAX(piece_num) as last_piece_num";
						$sql .= " FROM ".$this->db->prefix()."accounting_bookkeeping";
						$sql .= " WHERE entity IN (".getEntity('accountingbookkeeping').")";
						$res = $this->db->query($sql);
						if (!$res) {
							$this->errors[] = $this->db->lasterror();
							return '';
						}
						if ($obj = $this->db->fetch_object($res)) {
							$lastPieceNum = (int) $obj->last_piece_num;
						}
						$this->db->free($res);
					}
					// set next piece number in memory
					if (empty($conf->cache['accounting']['nextPieceNum'])) {
						$conf->cache['accounting']['nextPieceNum'] = $lastPieceNum;
					}
					$conf->cache['accounting']['nextPieceNum']++;

					// set last records values in memory
					$conf->cache['accounting']['lastRecordCompareValues'] = array(
						'b.code_journal' => $arrayrecord[$codeJournalIndex]['val'],
						'b.doc_date' => $arrayrecord[$docDateIndex]['val'],
					);
				}
				$pieceNum = (string) $conf->cache['accounting']['nextPieceNum'];
			}
		}

		return $pieceNum;
	}
}
