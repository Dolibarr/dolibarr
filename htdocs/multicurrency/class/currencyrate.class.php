<?php
/* Copyright (C) 2007-2020  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2014       Juanjo Menent       <jmenent@2byte.es>
 * Copyright (C) 2015       Florian Henry       <florian.henry@open-concept.pro>
 * Copyright (C) 2015       Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2016       Pierre-Henry Favre  <phf@atm-consulting.fr>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 * \file    htdocs/multicurrency/class/currencyrate.class.php
 * \ingroup multicurrency
 * \brief   This file is a CRUD class file (Create/Read/Update/Delete) for currencyrate
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobjectline.class.php';

/**
 * Class CurrencyRate
 */
class CurrencyRate extends CommonObjectLine
{
	/**
	 * @var string Id to identify managed objects
	 */
	public $element = 'multicurrency_rate';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'multicurrency_rate';

	/**
	 * @var int ID
	 */
	public $id;

	/**
	 * @var double Rate
	 */
	public $rate;

	/**
	 * @var double Rate Indirect
	 */
	public $rate_indirect;

	/**
	 * @var integer    Date synchronisation
	 */
	public $date_sync;

	/**
	 * @var int Id of currency
	 */
	public $fk_multicurrency;

	/**
	 * @var int Id of entity
	 */
	public $entity;


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
	 * Create object into database
	 *
	 * @param	User	$user				User making the deletion
	 * @param  	int		$fk_multicurrency	Id of currency
	 * @param  	int 	$notrigger 			0=launch triggers after, 1=disable triggers
	 * @return 	int 						Return integer <0 if KO, Id of created object if OK
	 */
	public function create(User $user, int $fk_multicurrency, $notrigger = 0)
	{
		global $conf;

		dol_syslog('CurrencyRate::create', LOG_DEBUG);

		$error = 0;
		$this->rate = (float) price2num($this->rate);
		if (empty($this->entity) || $this->entity <= 0) {
			$this->entity = $conf->entity;
		}
		$now = empty($this->date_sync) ? dol_now() : $this->date_sync;

		// Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX.$this->table_element."(";
		$sql .= ' rate,';
		$sql .= ' rate_indirect,';
		$sql .= ' date_sync,';
		$sql .= ' fk_multicurrency,';
		$sql .= ' entity';
		$sql .= ') VALUES (';
		$sql .= ' '.((float) $this->rate).',';
		$sql .= ' '.((float) $this->rate_indirect).',';
		$sql .= " '".$this->db->idate($now)."',";
		$sql .= " ".((int) $fk_multicurrency).",";
		$sql .= " ".((int) $this->entity);
		$sql .= ')';

		$this->db->begin();

		dol_syslog(__METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog('CurrencyRate::create '.implode(',', $this->errors), LOG_ERR);
		}

		if (!$error) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.$this->table_element);
			$this->fk_multicurrency = $fk_multicurrency;
			$this->date_sync = $now;

			if (empty($notrigger)) {
				$result = $this->call_trigger('CURRENCYRATE_CREATE', $user);
				if ($result < 0) {
					$error++;
				}
			}
		}

		if ($error) {
			$this->db->rollback();

			return -1 * $error;
		} else {
			$this->db->commit();

			return $this->id;
		}
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param 	int    $id  Id object
	 * @return 	int 		Return integer <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id)
	{
		dol_syslog('CurrencyRate::fetch', LOG_DEBUG);

		$sql = "SELECT cr.rowid, cr.rate, cr.rate_indirect, cr.date_sync, cr.fk_multicurrency, cr.entity";
		$sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element." AS cr";
		$sql .= " WHERE cr.rowid = ".((int) $id);

		dol_syslog(__METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$numrows = $this->db->num_rows($resql);
			if ($numrows) {
				$obj = $this->db->fetch_object($resql);

				$this->id = $obj->rowid;
				$this->rate = $obj->rate;
				$this->rate_indirect = $obj->rate_indirect;
				$this->date_sync = $this->db->jdate($obj->date_sync);
				$this->fk_multicurrency = $obj->fk_multicurrency;
				$this->entity = $obj->entity;
			}
			$this->db->free($resql);

			if ($numrows) {
				return 1;
			} else {
				return 0;
			}
		} else {
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog('CurrencyRate::fetch '.implode(',', $this->errors), LOG_ERR);

			return -1;
		}
	}

	/**
	 * Update object into database
	 *
	 * @param	User	$user		User making the deletion
	 * @param  	int 	$notrigger 	0=launch triggers after, 1=disable triggers
	 * @return 	int 				Return integer <0 if KO, >0 if OK
	 */
	public function update(User $user, $notrigger = 0)
	{
		$error = 0;

		dol_syslog('CurrencyRate::update', LOG_DEBUG);

		$this->rate = (float) price2num($this->rate);

		// Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
		$sql .= "SET rate = ".((float) $this->rate);
		if (!empty($this->date_sync)) {
			$sql .= ", date_sync = '".$this->db->idate($this->date_sync)."'";
		}
		if (!empty($this->fk_multicurrency)) {
			$sql .= ', fk_multicurrency = '.((int) $this->fk_multicurrency);
		}
		$sql .= " WHERE rowid =".((int) $this->id);

		$this->db->begin();

		dol_syslog(__METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog('CurrencyRate::update '.implode(',', $this->errors), LOG_ERR);
		}

		if (!$error && empty($notrigger)) {
			$result = $this->call_trigger('CURRENCYRATE_MODIFY', $user);
			if ($result < 0) {
				$error++;
			}
		}

		// Commit or rollback
		if ($error) {
			$this->db->rollback();

			return -1 * $error;
		} else {
			$this->db->commit();

			return 1;
		}
	}

	/**
	 * Delete object in database
	 *
	 * @param	User	$user		User making the deletion
	 * @param  	int 	$notrigger 	0=launch triggers after, 1=disable triggers
	 * @return 	int 				Return integer <0 if KO, >0 if OK
	 */
	public function delete(User $user, $notrigger = 0)
	{
		dol_syslog('CurrencyRate::delete', LOG_DEBUG);

		$error = 0;

		$this->db->begin();

		if (empty($notrigger)) {
			$result = $this->call_trigger('CURRENCYRATE_DELETE', $user);
			if ($result < 0) {
				$error++;
			}
		}

		if (!$error) {
			$sql = 'DELETE FROM '.MAIN_DB_PREFIX.$this->table_element;
			$sql .= ' WHERE rowid='.((int) $this->id);

			dol_syslog(__METHOD__, LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				$error++;
				$this->errors[] = 'Error '.$this->db->lasterror();
				dol_syslog('CurrencyRate::delete '.implode(',', $this->errors), LOG_ERR);
			}
		}

		// Commit or rollback
		if ($error) {
			$this->db->rollback();

			return -1 * $error;
		} else {
			$this->db->commit();

			return 1;
		}
	}
}
