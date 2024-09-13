<?php
/* Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2011-2020 Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2015      Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2015-2020 Charlene Benke       <charlie@patas-monkey.com>
 * Copyright (C) 2018      Nicolas ZABOURI	    <info@inovea-conseil.com>
 * Copyright (C) 2018-2024  Frédéric France     <frederic.france@free.fr>
 * Copyright (C) 2023-2024  William Mead        <william.mead@manchenumerique.fr>
 * Copyright (C) 2024		MDW					<mdeweerd@users.noreply.github.com>
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
 * 	\file       htdocs/fichinter/class/fichinterligne.class.php
 * 	\ingroup    fichinter
 * 	\brief      File for class to manage intervention lines
 */
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobjectline.class.php';

/**
 *	Class to manage intervention lines
 */
class FichinterLigne extends CommonObjectLine
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 * From llx_fichinterdet
	 * @var int ID
	 */
	public $fk_fichinter;

	/**
	 * @var string Line description
	 */
	public $desc;

	/**
	 * @var int Date of intervention
	 */
	public $date;
	/**
	 * @var int Date of intervention
	 * @deprecated
	 */
	public $datei;

	/**
	 * @var int Duration of intervention
	 */
	public $duration;

	/**
	 * @var int Line rang
	 */
	public $rang = 0;

	/**
	 * @var float Taxe rate
	 */
	public $tva_tx;

	/**
	 * Unit price before taxes
	 * @var float
	 */
	public $subprice;

	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'fichinterdet';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'fichinterdet';

	/**
	 * @var string Field with ID of parent key if this field has a parent
	 */
	public $fk_element = 'fk_fichinter';



	/**
	 *  Constructor
	 *
	 *  @param  DoliDB  $db     Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 *	Retrieve the line of intervention
	 *
	 *	@param  int		$rowid		Line id
	 *	@return	int					Return integer <0 if KO, >0 if OK
	 */
	public function fetch($rowid)
	{
		dol_syslog("FichinterLigne::fetch", LOG_DEBUG);

		$sql = 'SELECT ft.rowid, ft.fk_fichinter, ft.description, ft.duree, ft.rang, ft.date';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'fichinterdet as ft';
		$sql .= ' WHERE ft.rowid = '.((int) $rowid);

		$resql = $this->db->query($sql);
		if ($resql) {
			$objp = $this->db->fetch_object($resql);
			$this->rowid          	= $objp->rowid;
			$this->id               = $objp->rowid;
			$this->fk_fichinter   	= $objp->fk_fichinter;
			$this->date = $this->db->jdate($objp->date);
			$this->datei = $this->db->jdate($objp->date);	// For backward compatibility
			$this->desc           	= $objp->description;
			$this->duration       	= $objp->duree;
			$this->rang           	= $objp->rang;

			$this->db->free($resql);

			$this->fetch_optionals();

			return 1;
		} else {
			$this->error = $this->db->error().' sql='.$sql;
			return -1;
		}
	}

	/**
	 *	Insert the line into database
	 *
	 *	@param		User	$user 		Object user that make creation
	 *	@param		int		$notrigger	Disable all triggers
	 *	@return		int		Return integer <0 if ko, >0 if ok
	 */
	public function insert($user, $notrigger = 0)
	{
		$error = 0;

		dol_syslog("FichinterLigne::insert rang=".$this->rang);

		if (empty($this->date) && !empty($this->datei)) {	// For backward compatibility
			$this->date = $this->datei;
		}

		$this->db->begin();

		$rangToUse = $this->rang;
		if ($rangToUse == -1) {
			// Recupere rang max de la ligne d'intervention dans $rangmax
			$sql = 'SELECT max(rang) as max FROM '.MAIN_DB_PREFIX.'fichinterdet';
			$sql .= ' WHERE fk_fichinter = '.((int) $this->fk_fichinter);
			$resql = $this->db->query($sql);
			if ($resql) {
				$obj = $this->db->fetch_object($resql);
				$rangToUse = $obj->max + 1;
			} else {
				dol_print_error($this->db);
				$this->db->rollback();
				return -1;
			}
		}

		// Insertion dans base de la ligne
		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'fichinterdet';
		$sql .= ' (fk_fichinter, description, date, duree, rang)';
		$sql .= " VALUES (".((int) $this->fk_fichinter).",";
		$sql .= " '".$this->db->escape($this->desc)."',";
		$sql .= " '".$this->db->idate($this->date)."',";
		$sql .= " ".((int) $this->duration).",";
		$sql .= ' '.((int) $rangToUse);
		$sql .= ')';

		dol_syslog("FichinterLigne::insert", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.'fichinterdet');
			$this->rowid = $this->id;

			if (!$error) {
				$result = $this->insertExtraFields();
				if ($result < 0) {
					$error++;
				}
			}


			$result = $this->update_total();

			if ($result > 0) {
				$this->rang = $rangToUse;

				if (!$notrigger) {
					// Call trigger
					$result = $this->call_trigger('LINEFICHINTER_CREATE', $user);
					if ($result < 0) {
						$error++;
					}
					// End call triggers
				}
			}

			if (!$error) {
				$this->db->commit();
				return $result;
			} else {
				$this->db->rollback();
				return -1;
			}
		} else {
			$this->error = $this->db->error()." sql=".$sql;
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 *	Update intervention into database
	 *
	 *	@param		User	$user 		Object user that make creation
	 *	@param		int		$notrigger	Disable all triggers
	 *	@return		int		Return integer <0 if ko, >0 if ok
	 */
	public function update($user, $notrigger = 0)
	{
		$error = 0;

		if (empty($this->date) && !empty($this->datei)) {	// For backward compatibility
			$this->date = $this->datei;
		}

		$this->db->begin();

		// Mise a jour ligne en base
		$sql = "UPDATE ".MAIN_DB_PREFIX."fichinterdet SET";
		$sql .= " description = '".$this->db->escape($this->desc)."',";
		$sql .= " date = '".$this->db->idate($this->date)."',";
		$sql .= " duree = ".((int) $this->duration).",";
		$sql .= " rang = ".((int) $this->rang);
		$sql .= " WHERE rowid = ".((int) $this->id);

		dol_syslog("FichinterLigne::update", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if (!$error) {
				$result = $this->insertExtraFields();
				if ($result < 0) {
					$error++;
				}
			}

			$result = $this->update_total();
			if ($result > 0) {
				if (!$notrigger) {
					// Call trigger
					$result = $this->call_trigger('LINEFICHINTER_MODIFY', $user);
					if ($result < 0) {
						$error++;
					}
					// End call triggers
				}
			}

			if (!$error) {
				$this->db->commit();
				return $result;
			} else {
				$this->error = $this->db->lasterror();
				$this->db->rollback();
				return -1;
			}
		} else {
			$this->error = $this->db->lasterror();
			$this->db->rollback();
			return -1;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Update total duration into llx_fichinter
	 *
	 *	@return		int		Return integer <0 si ko, >0 si ok
	 */
	public function update_total()
	{
		// phpcs:enable
		$sql = "SELECT SUM(duree) as total_duration, min(date) as dateo, max(date) as datee ";
		$sql .= " FROM ".MAIN_DB_PREFIX."fichinterdet";
		$sql .= " WHERE fk_fichinter=".((int) $this->fk_fichinter);

		dol_syslog("FichinterLigne::update_total", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);
			$total_duration = 0;
			if (!empty($obj->total_duration)) {
				$total_duration = $obj->total_duration;
			}
			$this->db->free($resql);

			$error = 0;
			$this->db->begin();

			$sql = "UPDATE ".MAIN_DB_PREFIX."fichinter";
			$sql .= " SET duree = ".((int) $total_duration);
			$sql .= " , dateo = ".(!empty($obj->dateo) ? "'".$this->db->escape($obj->dateo)."'" : "null");
			$sql .= " , datee = ".(!empty($obj->datee) ? "'".$this->db->escape($obj->datee)."'" : "null");
			$sql .= " WHERE rowid = ".((int) $this->fk_fichinter);

			dol_syslog("FichinterLigne::update_total", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				$this->error = $this->db->error();
				$error++;
			}

			if (!$error && isModEnabled('ticket')) {
				// Get linked tickets
				require_once DOL_DOCUMENT_ROOT . '/fichinter/class/fichinter.class.php';
				$intervention = new Fichinter($this->db);
				$intervention->id = $this->fk_fichinter;
				$intervention->fetchObjectLinked(null, "ticket", null, '', 'OR', 1, 'sourcetype', 0);
				if (!empty($intervention->linkedObjectsIds["ticket"])) {
					// Update tickets duration
					$sql = "UPDATE llx_ticket AS t1";
					$sql .= " LEFT JOIN (";
					$sql .= "   SELECT " . $this->db->ifsql("ee.targettype = 'ticket'", "ee.fk_target", "ee.fk_source") . " AS rowid, SUM(fd.duree) as duration";
					$sql .= "   FROM llx_element_element AS ee";
					$sql .= "   LEFT JOIN llx_fichinterdet AS fd ON " . $this->db->ifsql("ee.targettype = 'fichinter'", "ee.fk_target", "ee.fk_source") . " = fd.fk_fichinter";
					$sql .= "   WHERE (ee.sourcetype = 'fichinter' AND ee.targettype = 'ticket') OR (ee.targettype = 'fichinter' AND ee.sourcetype = 'ticket')";
					$sql .= "   AND " . $this->db->ifsql("ee.targettype = 'ticket'", "ee.fk_target", "ee.fk_source") . " IN (" . implode(',', $intervention->linkedObjectsIds["ticket"]) . ")";
					$sql .= "   GROUP BY " . $this->db->ifsql("ee.targettype = 'ticket'", "ee.fk_target", "ee.fk_source");
					$sql .= " ) AS t2 ON t1.rowid = t2.rowid";
					$sql .= " SET t1.duration = t2.duration";
					$sql .= " WHERE t1.rowid IN (" . $this->db->sanitize(implode(',', $intervention->linkedObjectsIds["ticket"])) . ")";

					dol_syslog("FichinterLigne::update_total update ticket duration", LOG_DEBUG);
					$resql = $this->db->query($sql);
					if (!$resql) {
						$this->error = $this->db->error();
						$error++;
					}
				}
			}

			if ($error) {
				$this->db->rollback();
				return -2;
			} else {
				$this->db->commit();
				return 1;
			}
		} else {
			$this->error = $this->db->error();
			return -1;
		}
	}

	/**
	 *	Delete a intervention line
	 *
	 *	@param		User	$user 		Object user that make creation
	 *	@param		int		$notrigger	Disable all triggers
	 *	@return     int		>0 if ok, <0 if ko
	 */
	public function deleteLine($user, $notrigger = 0)
	{
		$error = 0;

		dol_syslog(get_class($this)."::deleteline lineid=".$this->id);

		$this->db->begin();

		$result = $this->deleteExtraFields();
		if ($result < 0) {
			$error++;
			$this->db->rollback();
			return -1;
		}

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."fichinterdet WHERE rowid = ".((int) $this->id);
		$resql = $this->db->query($sql);

		if ($resql) {
			$result = $this->update_total();
			if ($result > 0) {
				if (!$notrigger) {
					// Call trigger
					$result = $this->call_trigger('LINEFICHINTER_DELETE', $user);
					if ($result < 0) {
						$error++;
						$this->db->rollback();
						return -1;
					}
					// End call triggers
				}

				$this->db->commit();
				return $result;
			} else {
				$this->db->rollback();
				return -1;
			}
		} else {
			$this->error = $this->db->error()." sql=".$sql;
			$this->db->rollback();
			return -1;
		}
	}
}
