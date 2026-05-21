<?php
/* Copyright (C) 2026  Contributors to Dolibarr project
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
 * \file    htdocs/core/class/datesentwriter.class.php
 * \ingroup core
 * \brief   Service to persist date_sent on supported CommonObjects
 */

/**
 * Records the timestamp of the last successful email send on a supported CommonObject.
 *
 * Return codes: 1 = success, 0 = unsupported element (silent skip), -1 = error.
 * Never throws exceptions. Never calls triggers or UI methods.
 */
class DateSentWriter
{
	/** @var DoliDB */
	private DoliDB $db;

	/**
	 * Map from CommonObject::$element to table name (without prefix).
	 * Only these 12 elements are supported — whitelist protects against arbitrary table writes.
	 *
	 * @var array<string,string>
	 */
	private const TABLE_MAP = array(
		'propal'            => 'propal',
		'commande'          => 'commande',
		'facture'           => 'facture',
		'supplier_proposal' => 'supplier_proposal',
		'order_supplier'    => 'commande_fournisseur',
		'invoice_supplier'  => 'facture_fourn',
		'contrat'           => 'contrat',
		'shipping'          => 'expedition',
		'delivery'          => 'delivery',
		'reception'         => 'reception',
		'fichinter'         => 'fichinter',
		'project'           => 'projet',
	);

	/**
	 * @param DoliDB $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;
	}

	/**
	 * Persist date_sent on the given object (overwrites any existing value).
	 *
	 * Core objects are handled via TABLE_MAP. For custom module objects not in TABLE_MAP,
	 * a hook 'writeDateSent' is fired on context 'datesentwriter'. The hook receives
	 * $parameters['when'] (unix timestamp) and $object by reference. Return > 0 to signal handled.
	 * Declare the hook in the module descriptor: $this->module_parts['hooks'] = array('datesentwriter').
	 *
	 * @param  CommonObject $object Object to update — must have a valid id and a supported element type
	 * @param  int          $when   Unix timestamp of the send event
	 * @return int                  1 on success, 0 if element is not supported (silent skip), -1 on error
	 */
	public function write(CommonObject $object, int $when): int
	{
		if ($object->id <= 0) {
			dol_syslog('DateSentWriter: object without valid id, element='.$object->element, LOG_DEBUG);
			return -1;
		}

		if ($when <= 0) {
			dol_syslog('DateSentWriter: invalid timestamp ('.$when.') for element='.$object->element, LOG_DEBUG);
			return -1;
		}

		if (!isset(self::TABLE_MAP[$object->element])) {
			global $hookmanager;
			if (!empty($hookmanager)) {
				$hookmanager->initHooks(array('datesentwriter'));
				$reshook = $hookmanager->executeHooks('writeDateSent', array('when' => $when), $object);
				if ($reshook > 0) {
					return 1;
				}
			}
			dol_syslog('DateSentWriter: unsupported element '.$object->element, LOG_DEBUG);
			return 0;
		}

		// Defence in depth: the 12 supported classes all declare ?int $date_sent.
		// Guard against future callers passing a class missing the property (PHP 8.2 dynamic-property deprecation).
		if (!property_exists($object, 'date_sent')) {
			dol_syslog('DateSentWriter: object of element='.$object->element.' has no date_sent property', LOG_WARNING);
			return -1;
		}

		$sql  = "UPDATE ".$this->db->sanitize($this->db->prefix().self::TABLE_MAP[$object->element]);
		$sql .= " SET date_sent = '".$this->db->idate($when)."'";
		$sql .= " WHERE rowid = ".intval($object->id);

		$resql = $this->db->query($sql);
		if (!$resql) {
			dol_syslog('DateSentWriter error: '.$this->db->lasterror(), LOG_WARNING);
			return -1;
		}

		$object->date_sent = $when;
		return 1;
	}
}
