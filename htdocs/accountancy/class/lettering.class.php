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
	public static $doc_type_infos = array(
		'customer_invoice' => array(
			'payment_table' => 'paiement',
			'payment_table_fk_bank' => 'fk_bank',
			'doc_payment_table' => 'paiement_facture',
			'doc_payment_table_fk_payment' => 'fk_paiement',
			'doc_payment_table_fk_doc' => 'fk_facture',
			'linked_info' => array(
				array(
					'table' => 'paiement_facture',
					'fk_doc' => 'fk_facture',
					'fk_link' => 'fk_paiement',
					'prefix' => 'p',
				),
				array(
					'table' => 'societe_remise_except',
					'fk_doc' => 'fk_facture_source',
					'fk_link' => 'fk_facture',
					'prefix' => 'a',
					'is_fk_link_is_also_fk_doc' => true,
				),
			),
		),
		'supplier_invoice' => array(
			'payment_table' => 'paiementfourn',
			'payment_table_fk_bank' => 'fk_bank',
			'doc_payment_table' => 'paiementfourn_facturefourn',
			'doc_payment_table_fk_payment' => 'fk_paiementfourn',
			'doc_payment_table_fk_doc' => 'fk_facturefourn',
			'linked_info' => array(
				array(
					'table' => 'paiementfourn_facturefourn',
					'fk_doc' => 'fk_facturefourn',
					'fk_link' => 'fk_paiementfourn',
					'prefix' => 'p',
				),
				array(
					'table' => 'societe_remise_except',
					'fk_doc' => 'fk_invoice_supplier_source',
					'fk_link' => 'fk_invoice_supplier',
					'prefix' => 'a',
					'is_fk_link_is_also_fk_doc' => true,
				),
			),
		),
	);

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
		$sql .=	" FROM " . MAIN_DB_PREFIX . "accounting_bookkeeping AS ab";
		$sql .=	" LEFT JOIN " . MAIN_DB_PREFIX . "accounting_bookkeeping AS ab2 ON ab2.subledger_account = ab.subledger_account";
		$sql .=	" WHERE ab.rowid IN (" . $this->db->sanitize(implode(',', $ids)) . ")";
		$sql .=	" AND ab2.lettering_code != ''";
		$sql .=	" ORDER BY ab2.lettering_code DESC";
		$sql .=	" LIMIT 1 ";

		$resqla = $this->db->query($sql);
		if ($resqla) {
			$obj = $this->db->fetch_object($resqla);
			$lettre = (empty($obj->lettering_code) ? $lettre : $obj->lettering_code);
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
		$affected_rows = 0;

		if (!$error) {
			$sql = "UPDATE ".MAIN_DB_PREFIX."accounting_bookkeeping SET";
			$sql .= " lettering_code='".$this->db->escape($lettre)."'";
			$sql .= ", date_lettering = '".$this->db->idate($now)."'"; // todo correct date it's false
			$sql .= "  WHERE rowid IN (".$this->db->sanitize(implode(',', $ids)).") AND lettering_code IS NULL AND subledger_account != ''";

			dol_syslog(get_class($this)."::update", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				$error++;
				$this->errors[] = "Error ".$this->db->lasterror();
			} else {
				$affected_rows = $this->db->affected_rows($resql);
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
			return $affected_rows;
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
		$sql .= ", date_lettering = NULL";
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
			return $this->db->affected_rows($resql);
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

		$result = $this->bookkeepingLettering($bookkeeping_ids, $unlettering);
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
	 * @param	bool		$unlettering			Do unlettering
	 * @return	int									<0 if error (nb lettered = result -1), 0 if noting to lettering, >0 if OK (nb lettered)
	 */
	public function bookkeepingLettering($bookkeeping_ids, $unlettering = false)
	{
		global $langs;

		$this->errors = array();

		// Clean parameters
		$bookkeeping_ids = is_array($bookkeeping_ids) ? $bookkeeping_ids : array();

		$error = 0;
		$nb_lettering = 0;
		$grouped_lines = $this->getLinkedLines($bookkeeping_ids);
		if (!is_array($grouped_lines)) {
			return -2;
		}

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
				} elseif ($result > 0) {
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
	 * @param	array			$bookkeeping_ids				Lettering specific list of bookkeeping id
	 * @param	bool			$only_has_subledger_account		Get only lines who have subledger account
	 * @return	array|int										<0 if error otherwise all linked lines by block
	 */
	public function getLinkedLines($bookkeeping_ids, $only_has_subledger_account = true)
	{
		global $conf, $langs;
		$this->errors = array();

		// Clean parameters
		$bookkeeping_ids = is_array($bookkeeping_ids) ? $bookkeeping_ids : array();

		// Get all bookkeeping lines
		$sql = "SELECT DISTINCT ab.doc_type, ab.fk_doc";
		$sql .= " FROM " . MAIN_DB_PREFIX . "accounting_bookkeeping AS ab";
		$sql .= " WHERE ab.entity IN (" . getEntity('accountancy') . ")";
		$sql .= " AND ab.fk_doc > 0";
		if (!empty($bookkeeping_ids)) {
			// Get all bookkeeping lines of piece number
			$sql .= " AND EXISTS (";
			$sql .= "  SELECT rowid";
			$sql .= "  FROM " . MAIN_DB_PREFIX . "accounting_bookkeeping AS pn";
			$sql .= "  WHERE pn.entity IN (" . getEntity('accountancy') . ")";
			$sql .= "  AND pn.rowid IN (" . $this->db->sanitize(implode(',', $bookkeeping_ids)) . ")";
			$sql .= "  AND pn.piece_num = ab.piece_num";
			$sql .= " )";
		}
		if ($only_has_subledger_account) $sql .= " AND ab.subledger_account != ''";

		dol_syslog(__METHOD__ . " - Get all bookkeeping lines", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->errors[] = "Error " . $this->db->lasterror();
			return -1;
		}

		$bookkeeping_lines_by_type = array();
		while ($obj = $this->db->fetch_object($resql)) {
			$bookkeeping_lines_by_type[$obj->doc_type][$obj->fk_doc] = $obj->fk_doc;
		}
		$this->db->free($resql);

		if (empty($bookkeeping_lines_by_type)) {
			return array();
		}

		if (!empty($bookkeeping_lines_by_type['bank'])) {
			$new_bookkeeping_lines_by_type = $this->getDocTypeAndFkDocFromBankLines($bookkeeping_lines_by_type['bank']);
			if (!is_array($new_bookkeeping_lines_by_type)) {
				return -1;
			}
			foreach ($new_bookkeeping_lines_by_type as $doc_type => $fk_docs) {
				foreach ($fk_docs as $fk_doc) {
					$bookkeeping_lines_by_type[$doc_type][$fk_doc] = $fk_doc;
				}
			}
		}

		$grouped_lines = array();
		foreach (self::$doc_type_infos as $doc_type => $doc_type_info) {
			if (!is_array($bookkeeping_lines_by_type[$doc_type])) {
				continue;
			}

			// Get all document ids grouped
			$doc_grouped = $this->getLinkedDocumentByGroup($bookkeeping_lines_by_type[$doc_type], $doc_type);
			if (!is_array($doc_grouped)) {
				return -1;
			}

			// Group all lines by document/piece number
			foreach ($doc_grouped as $doc_ids) {
				$bank_ids = $this->getBankLinesFromFkDocAndDocType($doc_ids, $doc_type);
				if (!is_array($bank_ids)) {
					return -1;
				}

				// Get all bookkeeping lines linked
				$sql = "SELECT DISTINCT ab.rowid, ab.piece_num, ab.debit, ab.credit, ab.lettering_code";
				$sql .= " FROM " . MAIN_DB_PREFIX . "accounting_bookkeeping AS ab";
				$sql .= " WHERE ab.entity IN (" . getEntity('accountancy') . ")";
				$sql .= " AND (";
				if (!empty($bank_ids)) {
					$sql .= " EXISTS (";
					$sql .= "  SELECT bpn.rowid";
					$sql .= "  FROM " . MAIN_DB_PREFIX . "accounting_bookkeeping AS bpn";
					$sql .= "  WHERE bpn.entity IN (" . getEntity('accountancy') . ")";
					$sql .= "  AND bpn.doc_type = 'bank'";
					$sql .= "  AND bpn.fk_doc IN (" . $this->db->sanitize(implode(',', $bank_ids)) . ")";
					$sql .= "  AND bpn.piece_num = ab.piece_num";
					$sql .= " ) OR ";
				}
				$sql .= " EXISTS (";
				$sql .= "  SELECT dpn.rowid";
				$sql .= "  FROM " . MAIN_DB_PREFIX . "accounting_bookkeeping AS dpn";
				$sql .= "  WHERE dpn.entity IN (" . getEntity('accountancy') . ")";
				$sql .= "  AND dpn.doc_type = '" . $this->db->escape($doc_type) . "'";
				$sql .= "  AND dpn.fk_doc IN (" . $this->db->sanitize(implode(',', $doc_ids)) . ")";
				$sql .= "  AND dpn.piece_num = ab.piece_num";
				$sql .= " )";
				$sql .= ")";
				if ($only_has_subledger_account) $sql .= " AND ab.subledger_account != ''";

				dol_syslog(__METHOD__ . " - Get all bookkeeping lines linked", LOG_DEBUG);
				$resql = $this->db->query($sql);
				if (!$resql) {
					$this->errors[] = "Error " . $this->db->lasterror();
					return -1;
				}

				$group = array();
				while ($obj = $this->db->fetch_object($resql)) {
					$group[$obj->rowid] = array(
						'id' => $obj->rowid,
						'piece_num' => $obj->piece_num,
						'debit' => $obj->debit,
						'credit' => $obj->credit,
						'lettering_code' => $obj->lettering_code,
					);
				}
				$this->db->free($resql);

				if (!empty($group)) $grouped_lines[] = $group;
			}
		}

		return $grouped_lines;
	}

	/**
	 * Get all fk_doc by doc_type from list of bank ids
	 *
	 * @param	array			$bank_ids		List of bank ids
	 * @return	array|int						<0 if error otherwise all fk_doc by doc_type
	 */
	public function getDocTypeAndFkDocFromBankLines($bank_ids)
	{
		dol_syslog(__METHOD__ . " - bank_ids=".json_encode($bank_ids), LOG_DEBUG);

		// Clean parameters
		$bank_ids = is_array($bank_ids) ? $bank_ids : array();

		if (empty($bank_ids)) {
			return array();
		}

		$bookkeeping_lines_by_type = array();
		foreach (self::$doc_type_infos as $doc_type => $doc_type_info) {
			// Get all fk_doc by doc_type from bank ids
			$sql = "SELECT DISTINCT dp." . $doc_type_info['doc_payment_table_fk_doc'] . " AS fk_doc";
			$sql .= " FROM " . MAIN_DB_PREFIX . $doc_type_info['payment_table'] . " AS p";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . $doc_type_info['doc_payment_table'] . " AS dp ON dp." . $doc_type_info['doc_payment_table_fk_payment'] . " = p.rowid";
			$sql .= " WHERE p." . $doc_type_info['payment_table_fk_bank'] . " IN (" . $this->db->sanitize(implode(',', $bank_ids)) . ")";
			$sql .= " AND dp." . $doc_type_info['doc_payment_table_fk_doc'] . " > 0";

			dol_syslog(__METHOD__ . " - Get all fk_doc by doc_type from list of bank ids for '" . $doc_type . "'", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				$this->errors[] = "Error " . $this->db->lasterror();
				return -1;
			}

			while ($obj = $this->db->fetch_object($resql)) {
				$bookkeeping_lines_by_type[$doc_type][$obj->fk_doc] = $obj->fk_doc;
			}
			$this->db->free($resql);
		}

		return $bookkeeping_lines_by_type;
	}

	/**
	 * Get all bank ids from list of document ids of a type
	 *
	 * @param	array			$document_ids	List of document id
	 * @param	string			$doc_type		Type of document ('customer_invoice' or 'supplier_invoice', ...)
	 * @return	array|int						<0 if error otherwise all all bank ids from list of document ids of a type
	 */
	public function getBankLinesFromFkDocAndDocType($document_ids, $doc_type)
	{
		global $langs;

		dol_syslog(__METHOD__ . " - bank_ids=".json_encode($document_ids) . ", doc_type=$doc_type", LOG_DEBUG);

		// Clean parameters
		$document_ids = is_array($document_ids) ? $document_ids : array();
		//remove empty entries
		$document_ids = array_filter($document_ids);

		$doc_type = trim($doc_type);

		if (empty($document_ids)) {
			return array();
		}
		if (!is_array(self::$doc_type_infos[$doc_type])) {
			$langs->load('errors');
			$this->errors[] = $langs->trans('ErrorBadParameters');
			return -1;
		}

		$doc_type_info = self::$doc_type_infos[$doc_type];
		$bank_ids = array();

		// Get all fk_doc by doc_type from bank ids
		$sql = "SELECT DISTINCT p." . $doc_type_info['payment_table_fk_bank'] . " AS fk_doc";
		$sql .= " FROM " . MAIN_DB_PREFIX . $doc_type_info['payment_table'] . " AS p";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . $doc_type_info['doc_payment_table'] . " AS dp ON dp." . $doc_type_info['doc_payment_table_fk_payment'] . " = p.rowid";
		$sql .= " WHERE dp." . $doc_type_info['doc_payment_table_fk_doc'] . " IN (" . $this->db->sanitize(implode(',', $document_ids)) . ")";
		$sql .= " AND p." . $doc_type_info['payment_table_fk_bank'] . " > 0";

		dol_syslog(__METHOD__ . " - Get all bank ids from list of document ids of a type '" . $doc_type . "'", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->errors[] = "Error " . $this->db->lasterror();
			return -1;
		}

		while ($obj = $this->db->fetch_object($resql)) {
			$bank_ids[$obj->fk_doc] = $obj->fk_doc;
		}
		$this->db->free($resql);

		return $bank_ids;
	}

	/**
	 * Get all linked document ids by group and type
	 *
	 * @param	array			$document_ids	List of document id
	 * @param	string			$doc_type		Type of document ('customer_invoice' or 'supplier_invoice', ...)
	 * @return	array|int						<0 if error otherwise all linked document ids by group and type [ [ 'doc_type' => [ doc_id, ... ], ... ], ... ]
	 */
	public function getLinkedDocumentByGroup($document_ids, $doc_type)
	{
		global $langs;

		// Clean parameters
		$document_ids = is_array($document_ids) ? $document_ids : array();
		$doc_type = trim($doc_type);
		//remove empty entries
		$document_ids = array_filter($document_ids);

		if (empty($document_ids)) {
			return array();
		}

		if (!is_array(self::$doc_type_infos[$doc_type])) {
			$langs->load('errors');
			$this->errors[] = $langs->trans('ErrorBadParameters');
			return -1;
		}

		$doc_type_info = self::$doc_type_infos[$doc_type];

		// Get document lines
		$current_document_ids = array();
		$link_by_element = array();
		$element_by_link = array();
		foreach ($doc_type_info['linked_info'] as $linked_info) {
			$sql = "SELECT DISTINCT tl2." . $linked_info['fk_link'] . " AS fk_link, tl2." . $linked_info['fk_doc'] . " AS fk_doc";
			$sql .= " FROM " . MAIN_DB_PREFIX . $linked_info['table'] . " AS tl";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . $linked_info['table'] . " AS tl2 ON tl2." . $linked_info['fk_link'] . " = tl." . $linked_info['fk_link'];
			$sql .= " WHERE tl." . $linked_info['fk_doc'] . " IN (" . $this->db->sanitize(implode(',', $document_ids)) . ")";

			dol_syslog(__METHOD__ . " - Get document lines", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				$this->errors[] = "Error " . $this->db->lasterror();
				return -1;
			}

			$is_fk_link_is_also_fk_doc = !empty($linked_info['is_fk_link_is_also_fk_doc']);
			while ($obj = $this->db->fetch_object($resql)) {
				$current_document_ids[$obj->fk_doc] = $obj->fk_doc;

				$link_key = $linked_info['prefix'] . $obj->fk_link;
				$element_by_link[$link_key][$obj->fk_doc] = $obj->fk_doc;
				$link_by_element[$obj->fk_doc][$link_key] = $link_key;
				if ($is_fk_link_is_also_fk_doc) {
					$element_by_link[$link_key][$obj->fk_link] = $obj->fk_link;
					$link_by_element[$obj->fk_link][$link_key] = $link_key;
				}
			}
			$this->db->free($resql);
		}

		if (count(array_diff($document_ids, $current_document_ids))) {
			return $this->getLinkedDocumentByGroup($current_document_ids, $doc_type);
		}

		return $this->getGroupElements($link_by_element, $element_by_link);
	}

	/**
	 * Get element ids grouped by link or element in common
	 *
	 * @param	array	$link_by_element	List of payment ids by link key
	 * @param	array	$element_by_link	List of element ids by link key
	 * @param	string	$link_key			Link key (used for recursive function)
	 * @param	array	$current_group		Current group (used for recursive function)
	 * @return	array						List of element ids grouped by link or element in common
	 */
	public function getGroupElements(&$link_by_element, &$element_by_link, $link_key = '', &$current_group = array())
	{
		$grouped_elements = array();
		if (!empty($link_key) && !isset($element_by_link[$link_key])) {
			// Return if specific link key not found
			return $grouped_elements;
		}

		if (empty($link_key)) {
			// Save list when is the begin of recursive function
			$save_link_by_element = $link_by_element;
			$save_element_by_link = $element_by_link;
		}

		do {
			// Get current element id, get this payment id list and delete the entry
			$current_link_key = !empty($link_key) ? $link_key : array_keys($element_by_link)[0];
			$element_ids = $element_by_link[$current_link_key];
			unset($element_by_link[$current_link_key]);

			foreach ($element_ids as $element_id) {
				// Continue if element id in not found
				if (!isset($link_by_element[$element_id])) continue;

				// Set the element in the current group
				$current_group[$element_id] = $element_id;

				// Get current link keys, get this element id list and delete the entry
				$link_keys = $link_by_element[$element_id];
				unset($link_by_element[$element_id]);

				// Set element id on the current group for each link key of the element
				foreach ($link_keys as $key) {
					$this->getGroupElements($link_by_element, $element_by_link, $key, $current_group);
				}
			}

			if (empty($link_key)) {
				// Save current group and reset the current group when is the begin of recursive function
				$grouped_elements[] = $current_group;
				$current_group = array();
			}
		} while (!empty($element_by_link) && empty($link_key));

		if (empty($link_key)) {
			// Restore list when is the begin of recursive function
			$link_by_element = $save_link_by_element;
			$element_by_link = $save_element_by_link;
		}

		return $grouped_elements;
	}
}
