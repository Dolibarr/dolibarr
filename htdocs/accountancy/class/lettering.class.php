<?php
/* Copyright (C) 2004-2005  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2013       Olivier Geffroy         <jeff@jeffinfo.com>
 * Copyright (C) 2013-2019  Alexandre Spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
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
 * \file      	htdocs/accountancy/class/lettering.class.php
 * \ingroup 	Accountancy (Double entries)
 * \brief 		File of class for lettering
 */

include_once DOL_DOCUMENT_ROOT."/accountancy/class/bookkeeping.class.php";
include_once DOL_DOCUMENT_ROOT."/societe/class/societe.class.php";
include_once DOL_DOCUMENT_ROOT."/core/lib/date.lib.php";

/**
 * Class Lettering
 */
class Lettering extends BookKeeping
{
	/**
	 * @var BookKeeping[] 	Bookkeeping cached
	 */
	public static $bookkeeping_cached = array();


	/**
	 * letteringThirdparty
	 *
	 * @param int $socid Thirdparty id
	 * @return int 1 OK, <0 error
	 */
	public function letteringThirdparty($socid)
	{
		global $conf;

		$error = 0;

		$object = new Societe($this->db);
		$object->id = $socid;
		$object->fetch($socid);


		if ($object->code_compta == '411CUSTCODE') {
			$object->code_compta = '';
		}

		if ($object->code_compta_fournisseur == '401SUPPCODE') {
			$object->code_compta_fournisseur = '';
		}

		/**
		 * Prise en charge des lettering complexe avec prelevment , virement
		 */
		$sql = "SELECT DISTINCT bk.rowid, bk.doc_date, bk.doc_type, bk.doc_ref, bk.subledger_account, ";
		$sql .= " bk.numero_compte , bk.label_compte, bk.debit , bk.credit, bk.montant ";
		$sql .= " , bk.sens , bk.code_journal , bk.piece_num, bk.date_lettering, bu.url_id , bu.type ";
		$sql .= " FROM ".MAIN_DB_PREFIX."accounting_bookkeeping as bk";
		$sql .= " LEFT JOIN  ".MAIN_DB_PREFIX."bank_url as bu ON(bk.fk_doc = bu.fk_bank AND bu.type IN ('payment', 'payment_supplier') ) ";
		$sql .= " WHERE ( ";
		if ($object->code_compta != "") {
			$sql .= " bk.subledger_account = '".$this->db->escape($object->code_compta)."'  ";
		}
		if ($object->code_compta != "" && $object->code_compta_fournisseur != "") {
			$sql .= " OR ";
		}
		if ($object->code_compta_fournisseur != "") {
			$sql .= " bk.subledger_account = '".$this->db->escape($object->code_compta_fournisseur)."' ";
		}

		$sql .= " ) AND (bk.date_lettering ='' OR bk.date_lettering IS NULL) ";
		$sql .= "  AND (bk.lettering_code != '' OR bk.lettering_code IS NULL) ";
		$sql .= ' AND bk.date_validated IS NULL ';
		$sql .= $this->db->order('bk.doc_date', 'DESC');

		// echo $sql;
		//
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);

			while ($obj = $this->db->fetch_object($resql)) {
				$ids = array();
				$ids_fact = array();

				if ($obj->type == 'payment_supplier') {
					$sql = 'SELECT DISTINCT bk.rowid, facf.ref, facf.ref_supplier, payf.fk_bank, facf.rowid as fact_id';
					$sql .= " FROM ".MAIN_DB_PREFIX."facture_fourn facf ";
					$sql .= " INNER JOIN ".MAIN_DB_PREFIX."paiementfourn_facturefourn as payfacf ON  payfacf.fk_facturefourn=facf.rowid";
					$sql .= " INNER JOIN ".MAIN_DB_PREFIX."paiementfourn as payf ON  payfacf.fk_paiementfourn=payf.rowid";
					$sql .= " INNER JOIN ".MAIN_DB_PREFIX."accounting_bookkeeping as bk ON (bk.fk_doc = payf.fk_bank AND bk.code_journal='".$this->db->escape($obj->code_journal)."')";
					$sql .= " WHERE payfacf.fk_paiementfourn = '".$this->db->escape($obj->url_id)."' ";
					$sql .= " AND facf.entity = ".$conf->entity;
					$sql .= " AND code_journal IN (SELECT code FROM ".MAIN_DB_PREFIX."accounting_journal WHERE nature=4 AND entity=".$conf->entity.") ";
					$sql .= " AND ( ";
					if ($object->code_compta != "") {
						$sql .= "  bk.subledger_account = '".$this->db->escape($object->code_compta)."'  ";
					}
					if ($object->code_compta != "" && $object->code_compta_fournisseur != "") {
						$sql .= "  OR  ";
					}
					if ($object->code_compta_fournisseur != "") {
						$sql .= "   bk.subledger_account = '".$this->db->escape($object->code_compta_fournisseur)."' ";
					}
					$sql .= " )  ";

					$resql2 = $this->db->query($sql);
					if ($resql2) {
						while ($obj2 = $this->db->fetch_object($resql2)) {
							$ids[$obj2->rowid] = $obj2->rowid;
							$ids_fact[] = $obj2->fact_id;
						}
						$this->db->free($resql2);
					} else {
						$this->errors[] = $this->db->lasterror;
						return -1;
					}
					if (count($ids_fact)) {
						$sql = 'SELECT bk.rowid, facf.ref, facf.ref_supplier ';
						$sql .= " FROM ".MAIN_DB_PREFIX."facture_fourn facf ";
						$sql .= " INNER JOIN ".MAIN_DB_PREFIX."accounting_bookkeeping as bk ON(  bk.fk_doc = facf.rowid AND facf.rowid IN (".$this->db->sanitize(implode(',', $ids_fact))."))";
						$sql .= " WHERE bk.code_journal IN (SELECT code FROM ".MAIN_DB_PREFIX."accounting_journal WHERE nature=3 AND entity=".$conf->entity.") ";
						$sql .= " AND facf.entity = ".$conf->entity;
						$sql .= " AND ( ";
						if ($object->code_compta != "") {
							$sql .= " bk.subledger_account = '".$this->db->escape($object->code_compta)."'  ";
						}
						if ($object->code_compta != "" && $object->code_compta_fournisseur != "") {
							$sql .= " OR ";
						}
						if ($object->code_compta_fournisseur != "") {
							$sql .= " bk.subledger_account = '".$this->db->escape($object->code_compta_fournisseur)."' ";
						}
						$sql .= ") ";

						$resql2 = $this->db->query($sql);
						if ($resql2) {
							while ($obj2 = $this->db->fetch_object($resql2)) {
								$ids[$obj2->rowid] = $obj2->rowid;
							}
							$this->db->free($resql2);
						} else {
							$this->errors[] = $this->db->lasterror;
							return -1;
						}
					}
				} elseif ($obj->type == 'payment') {
					$sql = 'SELECT DISTINCT bk.rowid, fac.ref, fac.ref, pay.fk_bank, fac.rowid as fact_id';
					$sql .= " FROM ".MAIN_DB_PREFIX."facture fac ";
					$sql .= " INNER JOIN ".MAIN_DB_PREFIX."paiement_facture as payfac ON  payfac.fk_facture=fac.rowid";
					$sql .= " INNER JOIN ".MAIN_DB_PREFIX."paiement as pay ON  payfac.fk_paiement=pay.rowid";
					$sql .= " INNER JOIN ".MAIN_DB_PREFIX."accounting_bookkeeping as bk ON (bk.fk_doc = pay.fk_bank AND bk.code_journal='".$this->db->escape($obj->code_journal)."')";
					$sql .= " WHERE payfac.fk_paiement = '".$this->db->escape($obj->url_id)."' ";
					$sql .= " AND bk.code_journal IN (SELECT code FROM ".MAIN_DB_PREFIX."accounting_journal WHERE nature=4 AND entity=".$conf->entity.") ";
					$sql .= " AND fac.entity IN (".getEntity('invoice', 0).")"; // We don't share object for accountancy
					$sql .= " AND ( ";
					if ($object->code_compta != "") {
						$sql .= "  bk.subledger_account = '".$this->db->escape($object->code_compta)."'  ";
					}
					if ($object->code_compta != "" && $object->code_compta_fournisseur != "") {
						$sql .= "  OR  ";
					}
					if ($object->code_compta_fournisseur != "") {
						$sql .= "   bk.subledger_account = '".$this->db->escape($object->code_compta_fournisseur)."' ";
					}
					$sql .= " )";

					$resql2 = $this->db->query($sql);
					if ($resql2) {
						while ($obj2 = $this->db->fetch_object($resql2)) {
							$ids[$obj2->rowid] = $obj2->rowid;
							$ids_fact[] = $obj2->fact_id;
						}
					} else {
						$this->errors[] = $this->db->lasterror;
						return -1;
					}
					if (count($ids_fact)) {
						$sql = 'SELECT bk.rowid, fac.ref, fac.ref_supplier ';
						$sql .= " FROM ".MAIN_DB_PREFIX."facture fac ";
						$sql .= " INNER JOIN ".MAIN_DB_PREFIX."accounting_bookkeeping as bk ON(  bk.fk_doc = fac.rowid AND fac.rowid IN (".$this->db->sanitize(implode(',', $ids_fact))."))";
						$sql .= " WHERE code_journal IN (SELECT code FROM ".MAIN_DB_PREFIX."accounting_journal WHERE nature=2 AND entity=".$conf->entity.") ";
						$sql .= " AND fac.entity IN (".getEntity('invoice', 0).")"; // We don't share object for accountancy
						$sql .= " AND ( ";
						if ($object->code_compta != "") {
							$sql .= "  bk.subledger_account = '".$this->db->escape($object->code_compta)."'  ";
						}
						if ($object->code_compta != "" && $object->code_compta_fournisseur != "") {
							$sql .= "  OR  ";
						}
						if ($object->code_compta_fournisseur != "") {
							$sql .= "   bk.subledger_account = '".$this->db->escape($object->code_compta_fournisseur)."' ";
						}
						$sql .= " )  ";

						$resql2 = $this->db->query($sql);
						if ($resql2) {
							while ($obj2 = $this->db->fetch_object($resql2)) {
								$ids[$obj2->rowid] = $obj2->rowid;
							}
							$this->db->free($resql2);
						} else {
							$this->errors[] = $this->db->lasterror;
							return -1;
						}
					}
				}

				if (count($ids) > 1) {
					$result = $this->updateLettering($ids);
				}
			}
			$this->db->free($resql);
		}
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(__METHOD__.' '.$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			return -1 * $error;
		} else {
			return 1;
		}
	}

	/**
	 *
	 * @param	array		$ids			ids array
	 * @param	boolean		$notrigger		no trigger
	 * @return	int
	 */
	public function updateLettering($ids = array(), $notrigger = false)
	{
		$error = 0;
		$lettre = 'AAA';

		$sql = "SELECT DISTINCT ab2.lettering_code";
		$sql .=	" FROM " . MAIN_DB_PREFIX . "accounting_bookkeeping As ab";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "bank_url AS bu ON bu.fk_bank = ab.fk_doc";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "bank_url AS bu2 ON bu2.url_id = bu.url_id";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "accounting_bookkeeping AS ab2 ON ab2.fk_doc = bu2.fk_bank";
		$sql .= " WHERE ab.rowid IN (" . $this->db->sanitize(implode(',', $ids)) . ")";
		$sql .= " AND ab.doc_type = 'bank'";
		$sql .= " AND ab2.doc_type = 'bank'";
		$sql .= " AND bu.type = 'company'";
		$sql .= " AND bu2.type = 'company'";
		$sql .= " AND ab.subledger_account != ''";
		$sql .= " AND ab2.subledger_account != ''";
		$sql .= " AND ab.lettering_code IS NULL";
		$sql .= " AND ab2.lettering_code != ''";
		$sql .= " ORDER BY ab2.lettering_code DESC";
		$sql .= " LIMIT 1 ";

		$resqla = $this->db->query($sql);
		if ($resqla) {
			$obj = $this->db->fetch_object($resqla);
			$lettre = (empty($obj->lettering_code) ? 'AAA' : $obj->lettering_code);
			if (!empty($obj->lettering_code)) {
				$lettre++;
			}
			$this->db->free($resqla);
		} else {
			$this->errors[] = 'Error'.$this->db->lasterror();
			$error++;
		}

		$sql = "SELECT SUM(ABS(debit)) as deb, SUM(ABS(credit)) as cred FROM ".MAIN_DB_PREFIX."accounting_bookkeeping WHERE ";
		$sql .= " rowid IN (".$this->db->sanitize(implode(',', $ids)).") AND lettering_code IS NULL AND subledger_account != ''";
		$resqlb = $this->db->query($sql);
		if ($resqlb) {
			$obj = $this->db->fetch_object($resqlb);
			if (!(round(abs($obj->deb), 2) === round(abs($obj->cred), 2))) {
				$this->errors[] = 'Total not exacts '.round(abs($obj->deb), 2).' vs '.round(abs($obj->cred), 2);
				$error++;
			}
			$this->db->free($resqlb);
		} else {
			$this->errors[] = 'Erreur sql'.$this->db->lasterror();
			$error++;
		}

		// Update request

		$now = dol_now();

		if (!$error) {
			$sql = "UPDATE ".MAIN_DB_PREFIX."accounting_bookkeeping SET";
			$sql .= " lettering_code='".$this->db->escape($lettre)."'";
			$sql .= " , date_lettering = '".$this->db->idate($now)."'"; // todo correct date it's false
			$sql .= "  WHERE rowid IN (".$this->db->sanitize(implode(',', $ids)).") AND lettering_code IS NULL AND subledger_account != ''";

			dol_syslog(get_class($this)."::update", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				$error++;
				$this->errors[] = "Error ".$this->db->lasterror();
			}
		}

		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this)."::update ".$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			return -1 * $error;
		} else {
			return 1;
		}
	}

	/**
	 *
	 * @param	array		$ids			ids array
	 * @param	boolean		$notrigger		no trigger
	 * @return	int
	 */
	public function deleteLettering($ids, $notrigger = false)
	{
		$error = 0;

		$sql = "UPDATE ".MAIN_DB_PREFIX."accounting_bookkeeping SET";
		$sql .= " lettering_code = NULL";
		$sql .= " , date_lettering = NULL";
		$sql .= " WHERE rowid IN (".$this->db->sanitize(implode(',', $ids)).")";
		$sql .= " AND subledger_account != ''";

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = "Error ".$this->db->lasterror();
		}

		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this)."::update ".$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			return -1 * $error;
		} else {
			return 1;
		}
	}

	/**
	 * Lettering bookkeeping lines all types
	 *
	 * @param	array		$bookkeeping_ids		Lettering specific list of bookkeeping id
	 * @param	bool		$unlettering			Do unlettering
	 * @return	int									<0 if error (nb lettered = result -1), 0 if noting to lettering, >0 if OK (nb lettered)
	 */
	public function bookkeepingLetteringAll($bookkeeping_ids, $unlettering = false)
	{
		dol_syslog(__METHOD__ . " - ", LOG_DEBUG);

		$error = 0;
		$errors = array();
		$nb_lettering = 0;

		$result = $this->bookkeepingLettering($bookkeeping_ids, 'customer_invoice', $unlettering);
		if ($result < 0) {
			$error++;
			$errors = array_merge($errors, $this->errors);
			$nb_lettering += abs($result) - 2;
		} else {
			$nb_lettering += $result;
		}

		$result = $this->bookkeepingLettering($bookkeeping_ids, 'supplier_invoice', $unlettering);
		if ($result < 0) {
			$error++;
			$errors = array_merge($errors, $this->errors);
			$nb_lettering += abs($result) - 2;
		} else {
			$nb_lettering += $result;
		}

		if ($error) {
			$this->errors = $errors;
			return -2 - $nb_lettering;
		} else {
			return $nb_lettering;
		}
	}

	/**
	 * Lettering bookkeeping lines
	 *
	 * @param	array		$bookkeeping_ids		Lettering specific list of bookkeeping id
	 * @param	string		$type					Type of bookkeeping type to lettering ('customer_invoice' or 'supplier_invoice')
	 * @param	bool		$unlettering			Do unlettering
	 * @return	int									<0 if error (nb lettered = result -1), 0 if noting to lettering, >0 if OK (nb lettered)
	 */
	public function bookkeepingLettering($bookkeeping_ids, $type = 'customer_invoice', $unlettering = false)
	{
		global $langs;

		$this->errors = array();

		// Clean parameters
		$bookkeeping_ids = is_array($bookkeeping_ids) ? $bookkeeping_ids : array();
		$type = trim($type);

		$error = 0;
		$nb_lettering = 0;
		$grouped_lines = $this->getLinkedLines($bookkeeping_ids, $type);
		foreach ($grouped_lines as $lines) {
			$group_error = 0;
			$total = 0;
			$do_it = !$unlettering;
			$lettering_code = null;
			$piece_num_lines = array();
			$bookkeeping_lines = array();
			foreach ($lines as $line_infos) {
				$bookkeeping_lines[$line_infos['id']] = $line_infos['id'];
				$piece_num_lines[$line_infos['piece_num']] = $line_infos['piece_num'];
				$total += ($line_infos['credit'] > 0 ? $line_infos['credit'] : -$line_infos['debit']);

				// Check lettering code
				if ($unlettering) {
					if (isset($lettering_code) && $lettering_code != $line_infos['lettering_code']) {
						$this->errors[] = $langs->trans('AccountancyErrorMismatchLetteringCode');
						$group_error++;
						break;
					}
					if (!isset($lettering_code)) $lettering_code = (string) $line_infos['lettering_code'];
					if (!empty($line_infos['lettering_code'])) $do_it = true;
				} elseif (!empty($line_infos['lettering_code'])) $do_it = false;
			}

			// Check balance amount
			if (!$group_error && !$unlettering && price2num($total) != 0) {
				$this->errors[] = $langs->trans('AccountancyErrorMismatchBalanceAmount', $total);
				$group_error++;
			}

			// Lettering/Unlettering the group of bookkeeping lines
			if (!$group_error && $do_it) {
				if ($unlettering) $result = $this->deleteLettering($bookkeeping_lines);
				else $result = $this->updateLettering($bookkeeping_lines);
				if ($result < 0) {
					$group_error++;
				} else {
					$nb_lettering++;
				}
			}

			if ($group_error) {
				$this->errors[] = $langs->trans('AccountancyErrorLetteringBookkeeping', implode(', ', $piece_num_lines));
				$error++;
			}
		}

		if ($error) {
			return -2 - $nb_lettering;
		} else {
			return $nb_lettering;
		}
	}

	/**
	 * Lettering bookkeeping lines
	 *
	 * @param	array			$bookkeeping_ids		Lettering specific list of bookkeeping id
	 * @param	string			$type					Type of bookkeeping type to lettering ('customer_invoice' or 'supplier_invoice')
	 * @return	array|int								<0 if error otherwise all linked lines by block
	 */
	public function getLinkedLines($bookkeeping_ids, $type = 'customer_invoice')
	{
		global $conf, $langs;
		$this->errors = array();

		// Clean parameters
		$bookkeeping_ids = is_array($bookkeeping_ids) ? $bookkeeping_ids : array();
		$type = trim($type);

		if ($type == 'customer_invoice') {
			$doc_type = 'customer_invoice';
			$bank_url_type = 'payment';
			$payment_element = 'paiement_facture';
			$fk_payment_element = 'fk_paiement';
			$fk_element = 'fk_facture';
			$account_number = $conf->global->ACCOUNTING_ACCOUNT_CUSTOMER;
		} elseif ($type == 'supplier_invoice') {
			$doc_type = 'supplier_invoice';
			$bank_url_type = 'payment_supplier';
			$payment_element = 'paiementfourn_facturefourn';
			$fk_payment_element = 'fk_paiementfourn';
			$fk_element = 'fk_facturefourn';
			$account_number = $conf->global->ACCOUNTING_ACCOUNT_SUPPLIER;
		} else {
			$langs->load('errors');
			$this->errors[] = $langs->trans('ErrorBadParameters');
			return -1;
		}

		$payment_ids = array();

		// Get all payment id from bank lines
		$sql = "SELECT DISTINCT bu.url_id AS payment_id";
		$sql .= " FROM " . MAIN_DB_PREFIX . "accounting_bookkeeping AS ab";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "bank_url AS bu ON bu.fk_bank = ab.fk_doc";
		$sql .= " WHERE ab.doc_type = 'bank'";
		//	$sql .= " AND ab.subledger_account != ''";
		//	$sql .= " AND ab.numero_compte = '" . $this->db->escape($account_number) . "'";
		$sql .= " AND bu.type = '" . $this->db->escape($bank_url_type) . "'";
		if (!empty($bookkeeping_ids)) $sql .= " AND ab.rowid IN (" . $this->db->sanitize(implode(',', $bookkeeping_ids)) . ")";

		dol_syslog(__METHOD__ . " - Get all payment id from bank lines", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->errors[] = "Error " . $this->db->lasterror();
			return -1;
		}

		while ($obj = $this->db->fetch_object($resql)) {
			$payment_ids[$obj->payment_id] = $obj->payment_id;
		}
		$this->db->free($resql);

		// Get all payment id from payment lines
		$sql = "SELECT DISTINCT pe.$fk_payment_element AS payment_id";
		$sql .= " FROM " . MAIN_DB_PREFIX . "accounting_bookkeeping AS ab";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "$payment_element AS pe ON pe.$fk_element = ab.fk_doc";
		$sql .= " WHERE ab.doc_type = '" . $this->db->escape($doc_type) . "'";
		//	$sql .= " AND ab.subledger_account != ''";
		//	$sql .= " AND ab.numero_compte = '" . $this->db->escape($account_number) . "'";
		$sql .= " AND pe.$fk_payment_element IS NOT NULL";
		if (!empty($bookkeeping_ids)) $sql .= " AND ab.rowid IN (" . $this->db->sanitize(implode(',', $bookkeeping_ids)) . ")";

		dol_syslog(__METHOD__ . " - Get all payment id from bank lines", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->errors[] = "Error " . $this->db->lasterror();
			return -1;
		}

		while ($obj = $this->db->fetch_object($resql)) {
			$payment_ids[$obj->payment_id] = $obj->payment_id;
		}
		$this->db->free($resql);

		if (empty($payment_ids)) {
			return array();
		}

		// Get all payments linked by group
		$payment_by_group = $this->getLinkedPaymentByGroup($payment_ids, $type);

		$groups = array();
		foreach ($payment_by_group as $payment_list) {
			$lines = array();

			// Get bank lines
			$sql = "SELECT DISTINCT ab.rowid, ab.piece_num, ab.lettering_code, ab.debit, ab.credit";
			$sql .=	" FROM " . MAIN_DB_PREFIX . "bank_url AS bu";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "accounting_bookkeeping AS ab ON ab.fk_doc = bu.fk_bank";
			$sql .= " WHERE bu.url_id IN (" . $this->db->sanitize(implode(',', $payment_list)) . ")";
			$sql .= " AND bu.type = '" . $this->db->escape($bank_url_type) . "'";
			$sql .= " AND ab.doc_type = 'bank'";
			$sql .= " AND ab.subledger_account != ''";
			$sql .= " AND ab.numero_compte = '" . $this->db->escape($account_number) . "'";

			dol_syslog(__METHOD__ . " - Get bank lines", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				$this->errors[] = "Error " . $this->db->lasterror();
				return -1;
			}

			while ($obj = $this->db->fetch_object($resql)) {
				$lines[$obj->rowid] = array('id' => $obj->rowid, 'piece_num' => $obj->piece_num, 'lettering_code' => $obj->lettering_code, 'debit' => $obj->debit, 'credit' => $obj->credit);
			}
			$this->db->free($resql);

			// Get payment lines
			$sql = "SELECT DISTINCT ab.rowid, ab.piece_num, ab.lettering_code, ab.debit, ab.credit";
			$sql .=	" FROM " . MAIN_DB_PREFIX . "$payment_element AS pe";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "accounting_bookkeeping AS ab ON ab.fk_doc = pe.$fk_element";
			$sql .= " WHERE pe.$fk_payment_element IN (" . $this->db->sanitize(implode(',', $payment_list)) . ")";
			$sql .= " AND ab.doc_type = '" . $this->db->escape($doc_type) . "'";
			$sql .= " AND ab.subledger_account != ''";
			$sql .= " AND ab.numero_compte = '" . $this->db->escape($account_number) . "'";

			dol_syslog(__METHOD__ . " - Get payment lines", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				$this->errors[] = "Error " . $this->db->lasterror();
				return -1;
			}

			while ($obj = $this->db->fetch_object($resql)) {
				$lines[$obj->rowid] = array('id' => $obj->rowid, 'piece_num' => $obj->piece_num, 'lettering_code' => $obj->lettering_code, 'debit' => $obj->debit, 'credit' => $obj->credit);
			}
			$this->db->free($resql);

			if (!empty($lines)) {
				$groups[] = $lines;
			}
		}

		return $groups;
	}

	/**
	 * Linked payment by group
	 *
	 * @param	array			$payment_ids			list of payment id
	 * @param	string			$type					Type of bookkeeping type to lettering ('customer_invoice' or 'supplier_invoice')
	 * @return	array|int								<0 if error otherwise all linked lines by block
	 */
	public function getLinkedPaymentByGroup($payment_ids, $type)
	{
		global $langs;

		// Clean parameters
		$payment_ids = is_array($payment_ids) ? $payment_ids : array();
		$type = trim($type);

		if (empty($payment_ids)) {
			return array();
		}

		if ($type == 'customer_invoice') {
			$payment_element = 'paiement_facture';
			$fk_payment_element = 'fk_paiement';
			$fk_element = 'fk_facture';
		} elseif ($type == 'supplier_invoice') {
			$payment_element = 'paiementfourn_facturefourn';
			$fk_payment_element = 'fk_paiementfourn';
			$fk_element = 'fk_facturefourn';
		} else {
			$langs->load('errors');
			$this->errors[] = $langs->trans('ErrorBadParameters');
			return -1;
		}

		// Get payment lines
		$sql = "SELECT DISTINCT pe2.$fk_payment_element, pe2.$fk_element";
		$sql .=	" FROM " . MAIN_DB_PREFIX . "$payment_element AS pe";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "$payment_element AS pe2 ON pe2.$fk_element = pe.$fk_element";
		$sql .=	" WHERE pe.$fk_payment_element IN (" . $this->db->sanitize(implode(',', $payment_ids)) . ")";

		dol_syslog(__METHOD__ . " - Get payment lines", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->errors[] = "Error " . $this->db->lasterror();
			return -1;
		}

		$current_payment_ids = array();
		$payment_by_element = array();
		$element_by_payment = array();
		while ($obj = $this->db->fetch_object($resql)) {
			$current_payment_ids[$obj->$fk_payment_element] = $obj->$fk_payment_element;
			$element_by_payment[$obj->$fk_payment_element][$obj->$fk_element] = $obj->$fk_element;
			$payment_by_element[$obj->$fk_element][$obj->$fk_payment_element] = $obj->$fk_payment_element;
		}
		$this->db->free($resql);

		if (count(array_diff($payment_ids, $current_payment_ids))) {
			return $this->getLinkedPaymentByGroup($current_payment_ids, $type);
		}

		return $this->getGroupElements($payment_by_element, $element_by_payment);
	}

	/**
	 * Get payment ids grouped by payment id and element id in common
	 *
	 * @param	array	$payment_by_element		List of payment ids by element id
	 * @param	array	$element_by_payment		List of element ids by payment id
	 * @param	int		$element_id				Element Id (used for recursive function)
	 * @param	array	$current_group			Current group (used for recursive function)
	 * @return	array							List of payment ids grouped by payment id and element id in common
	 */
	public function getGroupElements(&$payment_by_element, &$element_by_payment, $element_id = 0, &$current_group = array())
	{
		$grouped_payments = array();
		if ($element_id > 0 && !isset($payment_by_element[$element_id])) {
			// Return if specific element id not found
			return $grouped_payments;
		}

		$save_payment_by_element = null;
		$save_element_by_payment = null;
		if ($element_id == 0) {
			// Save list when is the begin of recursive function
			$save_payment_by_element = $payment_by_element;
			$save_element_by_payment = $element_by_payment;
		}

		do {
			// Get current element id, get this payment id list and delete the entry
			$current_element_id = $element_id > 0 ? $element_id : array_keys($payment_by_element)[0];
			$payment_ids = $payment_by_element[$current_element_id];
			unset($payment_by_element[$current_element_id]);

			foreach ($payment_ids as $payment_id) {
				// Continue if payment id in not found
				if (!isset($element_by_payment[$payment_id])) continue;

				// Set the payment in the current group
				$current_group[$payment_id] = $payment_id;

				// Get current element ids, get this payment id list and delete the entry
				$element_ids = $element_by_payment[$payment_id];
				unset($element_by_payment[$payment_id]);

				// Set payment id on the current group for each element id of the payment
				foreach ($element_ids as $id) {
					$this->getGroupElements($payment_by_element, $element_by_payment, $id, $current_group);
				}
			}

			if ($element_id == 0) {
				// Save current group and reset the current group when is the begin of recursive function
				$grouped_payments[] = $current_group;
				$current_group = array();
			}
		} while (!empty($payment_by_element) && $element_id == 0);

		if ($element_id == 0) {
			// Restore list when is the begin of recursive function
			$payment_by_element = $save_payment_by_element;
			$element_by_payment = $save_element_by_payment;
		}

		return $grouped_payments;
	}
}
