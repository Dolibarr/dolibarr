<?php
/* Copyright (C) 2024		William Mead		<william.mead@manchenumerique.fr>
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
 *	\file       htdocs/core/class/commonsignedobject.class.php
 *	\ingroup    core
 *	\brief      File of trait for common signed business objects (contracts, interventions, ...)
 */

/**
 *	Trait for common signed business objects
 */
trait CommonSignedObject
{
	/**
	 * @var DoliDB		Database handler (result of a new DoliDB)
	 */
	public $db;

	/**
	 * @var int			The object identifier
	 */
	public $id;

	/**
	 * @var string		Error string
	 */
	public $error;

	/**
	 * @var string 		Name of table without prefix where object is stored
	 */
	public $table_element;

	/**
	 * @var ?static		To store a cloned copy of the object before editing it (to keep track of its former properties)
	 */
	public $oldcopy;

	/**
	 * @var array<string,mixed>		Can be used to pass information when only the object is provided to the method
	 */
	public $context = array();

	/**
	 * Status of the contract (0=NoSignature, 1=SignedBySender, 2=SignedByReceiver, 9=SignedByAll)
	 * @var int
	 */
	public $signed_status = 0;

	/**
	 * Signed statuses dictionary. Label used as key for string localizations.
	 * When min. required PHP is 8.2 this can be updated to a constant
	 * @var array<string,int>
	 */
	public static $SIGNED_STATUSES = [
		'STATUS_NO_SIGNATURE' => 0,
		'STATUS_SIGNED_SENDER' => 1,
		'STATUS_SIGNED_RECEIVER' => 2,
		'STATUS_SIGNED_RECEIVER_ONLINE' => 3,
		'STATUS_SIGNED_ALL' => 9 // To handle future kind of signature (ex: tripartite contract)
	];

	/**
	 *	Returns an array of signed statuses with associated localized labels
	 *
	 *	@return array<string>
	 */
	public function getSignedStatusLocalisedArray(): array
	{
		global $langs;
		$langs->load("commercial");

		$l10n_signed_status_labels = [
			self::$SIGNED_STATUSES['STATUS_NO_SIGNATURE']			=> 'NoSignature',
			self::$SIGNED_STATUSES['STATUS_SIGNED_SENDER']			=> 'SignedSender',
			self::$SIGNED_STATUSES['STATUS_SIGNED_RECEIVER']		=> 'SignedReceiver',
			self::$SIGNED_STATUSES['STATUS_SIGNED_RECEIVER_ONLINE']	=> 'SignedReceiverOnline',
			self::$SIGNED_STATUSES['STATUS_SIGNED_ALL']				=> 'SignedAll'
		];

		$l10n_signed_status = [];
		foreach (self::$SIGNED_STATUSES as $signed_status_code) {
			$l10n_signed_status[$signed_status_code] = $langs->transnoentitiesnoconv($l10n_signed_status_labels[$signed_status_code]);
		}
		return $l10n_signed_status;
	}

	/**
	 * Set signed status & object context. Call sign action trigger.
	 *
	 * @param	User	$user			Object user that modify
	 * @param	int		$status			New signed status to set (often a constant like self::STATUS_XXX)
	 * @param	int		$notrigger		1 = Does not execute triggers, 0 = Execute triggers
	 * @param	string	$triggercode	Trigger code to use
	 * @return	int						0 < if KO, > 0 if OK
	 */
	public function setSignedStatus(User $user, int $status = 0, int $notrigger = 0, string $triggercode = ''): int
	{
		global $langs;
		$langs->loadLangs(array('commercial'));
		$this->signed_status = $status;
		$this->context['signature'] = $status;
		switch ($status) {
			case 0:
				$this->context['actionmsg2'] = $langs->transnoentitiesnoconv('UnsignedInDolibarr');
				break;
			case 1:
				$this->context['actionmsg2'] = $langs->transnoentitiesnoconv('SignedSender');
				break;
			case 2:
				$this->context['actionmsg2'] = $langs->transnoentitiesnoconv('SignedReceiver');
				break;
			case 3:
				$this->context['actionmsg2'] = $langs->transnoentitiesnoconv('SignedReceiverOnline');
				break;
			case 9:
				$this->context['actionmsg2'] = $langs->transnoentitiesnoconv('SignedAll');
				break;
		}
		return $this->setSignedStatusCommon($user, $status, $notrigger, $triggercode);
	}

	/**
	 * Set signed status & call trigger with context message
	 *
	 * @param	User	$user			Object user that modify
	 * @param	int		$status			New signed status to set (often a constant like self::STATUS_XXX)
	 * @param	int		$notrigger		1 = Does not execute triggers, 0 = Execute triggers
	 * @param	string	$triggercode	Trigger code to use
	 * @return	int						0 < if KO, > 0 if OK
	 */
	public function setSignedStatusCommon(User $user, int $status, int $notrigger = 0, string $triggercode = ''): int
	{
		$error = 0;

		$this->db->begin();

		$statusfield = 'signed_status';

		$sql = "UPDATE ".$this->db->prefix().$this->table_element;
		$sql .= " SET ".$statusfield." = ".((int) $status);
		$sql .= " WHERE rowid = ".((int) $this->id);

		if ($this->db->query($sql)) {
			if (!$error) {
				$this->oldcopy = clone $this;
			}

			if (!$error && !$notrigger) {
				// Call trigger
				$result = $this->call_trigger($triggercode, $user);
				if ($result < 0) {
					$error++;
				}
			}

			if (!$error) {
				$this->signed_status = $status;
				$this->db->commit();
				return 1;
			} else {
				$this->db->rollback();
				return -1;
			}
		} else {
			$this->error = $this->db->error();
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *	Returns the label for signed status
	 *
	 *	@param		int		$mode	0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
	 *	@return		string			Label
	 */
	public function getLibSignedStatus(int $mode = 0): string
	{
		global $langs;
		$langs->load("commercial");
		$list_signed_status = $this->getSignedStatusLocalisedArray();
		$signed_status_label = $this->signed_status != '' ? $list_signed_status[$this->signed_status] : '';
		$signed_status_label_short = $this->signed_status != '' ? $list_signed_status[$this->signed_status] : '';
		$signed_status_code = 'status'.$this->signed_status;
		return dolGetStatus($signed_status_label, $signed_status_label_short, '', $signed_status_code, $mode);
	}
}
