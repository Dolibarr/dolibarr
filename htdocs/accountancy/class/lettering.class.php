<?php
/* Copyright (C) 2004-2005  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2013       Olivier Geffroy         <jeff@jeffinfo.com>
 * Copyright (C) 2013-2019  Alexandre Spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
		if ($object->code_compta != "")
			$sql .= " bk.subledger_account = '".$object->code_compta."'  ";
		if ($object->code_compta != "" && $object->code_compta_fournisseur != "")
			$sql .= " OR ";
		if ($object->code_compta_fournisseur != "")
			$sql .= " bk.subledger_account = '".$object->code_compta_fournisseur."' ";

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

				if ($obj->type == 'payment_supplier')
				{
					$sql = 'SELECT DISTINCT bk.rowid, facf.ref, facf.ref_supplier, payf.fk_bank, facf.rowid as fact_id';
					$sql .= " FROM ".MAIN_DB_PREFIX."facture_fourn facf ";
					$sql .= " INNER JOIN ".MAIN_DB_PREFIX."paiementfourn_facturefourn as payfacf ON  payfacf.fk_facturefourn=facf.rowid";
					$sql .= " INNER JOIN ".MAIN_DB_PREFIX."paiementfourn as payf ON  payfacf.fk_paiementfourn=payf.rowid";
					$sql .= " INNER JOIN ".MAIN_DB_PREFIX."accounting_bookkeeping as bk ON (bk.fk_doc = payf.fk_bank AND bk.code_journal='".$obj->code_journal."')";
					$sql .= " WHERE payfacf.fk_paiementfourn = '".$obj->url_id."' ";
					$sql .= " AND facf.entity = ".$conf->entity;
					$sql .= " AND code_journal IN (SELECT code FROM ".MAIN_DB_PREFIX."accounting_journal WHERE nature=4 AND entity=".$conf->entity.") ";
					$sql .= " AND ( ";
					if ($object->code_compta != "") {
						$sql .= "  bk.subledger_account = '".$object->code_compta."'  ";
					}
					if ($object->code_compta != "" && $object->code_compta_fournisseur != "") {
						$sql .= "  OR  ";
					}
					if ($object->code_compta_fournisseur != "") {
						$sql .= "   bk.subledger_account = '".$object->code_compta_fournisseur."' ";
					}
					$sql .= " )  ";

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
						$sql = 'SELECT bk.rowid, facf.ref, facf.ref_supplier ';
						$sql .= " FROM ".MAIN_DB_PREFIX."facture_fourn facf ";
						$sql .= " INNER JOIN ".MAIN_DB_PREFIX."accounting_bookkeeping as bk ON(  bk.fk_doc = facf.rowid AND facf.rowid IN (".implode(',', $ids_fact)."))";
						$sql .= " WHERE bk.code_journal IN (SELECT code FROM ".MAIN_DB_PREFIX."accounting_journal WHERE nature=3 AND entity=".$conf->entity.") ";
						$sql .= " AND facf.entity = ".$conf->entity;
						$sql .= " AND ( ";
						if ($object->code_compta != "") {
							$sql .= " bk.subledger_account = '".$object->code_compta."'  ";
						}
						if ($object->code_compta != "" && $object->code_compta_fournisseur != "") {
							$sql .= " OR ";
						}
						if ($object->code_compta_fournisseur != "") {
							$sql .= " bk.subledger_account = '".$object->code_compta_fournisseur."' ";
						}
						$sql .= ") ";

						$resql2 = $this->db->query($sql);
						if ($resql2) {
							while ($obj2 = $this->db->fetch_object($resql2)) {
								$ids[$obj2->rowid] = $obj2->rowid;
							}
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
					$sql .= " INNER JOIN ".MAIN_DB_PREFIX."accounting_bookkeeping as bk ON (bk.fk_doc = pay.fk_bank AND bk.code_journal='".$obj->code_journal."')";
					$sql .= " WHERE payfac.fk_paiement = '".$obj->url_id."' ";
					$sql .= " AND bk.code_journal IN (SELECT code FROM ".MAIN_DB_PREFIX."accounting_journal WHERE nature=4 AND entity=".$conf->entity.") ";
					$sql .= " AND fac.entity IN (".getEntity('invoice', 0).")"; // We don't share object for accountancy
					$sql .= " AND ( ";
					if ($object->code_compta != "") {
						$sql .= "  bk.subledger_account = '".$object->code_compta."'  ";
					}
					if ($object->code_compta != "" && $object->code_compta_fournisseur != "") {
						$sql .= "  OR  ";
					}
					if ($object->code_compta_fournisseur != "") {
						$sql .= "   bk.subledger_account = '".$object->code_compta_fournisseur."' ";
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
						$sql .= " INNER JOIN ".MAIN_DB_PREFIX."accounting_bookkeeping as bk ON(  bk.fk_doc = fac.rowid AND fac.rowid IN (".implode(',', $ids_fact)."))";
						$sql .= " WHERE code_journal IN (SELECT code FROM ".MAIN_DB_PREFIX."accounting_journal WHERE nature=2 AND entity=".$conf->entity.") ";
						$sql .= " AND fac.entity IN (".getEntity('invoice', 0).")"; // We don't share object for accountancy
						$sql .= " AND ( ";
						if ($object->code_compta != "") {
							$sql .= "  bk.subledger_account = '".$object->code_compta."'  ";
						}
						if ($object->code_compta != "" && $object->code_compta_fournisseur != "") {
							$sql .= "  OR  ";
						}
						if ($object->code_compta_fournisseur != "") {
							$sql .= "   bk.subledger_account = '".$object->code_compta_fournisseur."' ";
						}
						$sql .= " )  ";

						$resql2 = $this->db->query($sql);
						if ($resql2) {
							while ($obj2 = $this->db->fetch_object($resql2)) {
								$ids[$obj2->rowid] = $obj2->rowid;
							}
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
	 * @param array $ids ids array
	 * @param boolean $notrigger no trigger
	 * @return number
	 */
	public function updateLettering($ids = array(), $notrigger = false)
	{
		$error = 0;
		$lettre = 'AAA';

		$sql = "SELECT DISTINCT lettering_code FROM ".MAIN_DB_PREFIX."accounting_bookkeeping WHERE ";
		$sql .= " lettering_code != '' ORDER BY lettering_code DESC limit 1;  ";

		$result = $this->db->query($sql);
		if ($result) {
			$obj = $this->db->fetch_object($result);
			$lettre = (empty($obj->lettering_code) ? 'AAA' : $obj->lettering_code);
			if (!empty($obj->lettering_code))
				$lettre++;
		} else {
			$this->errors[] = 'Error'.$this->db->lasterror();
			$error++;
		}

		$sql = "SELECT SUM(ABS(debit)) as deb, SUM(ABS(credit)) as cred FROM ".MAIN_DB_PREFIX."accounting_bookkeeping WHERE ";
		$sql .= " rowid IN (".implode(',', $ids).") AND date_validated IS NULL ";
		$result = $this->db->query($sql);
		if ($result) {
			$obj = $this->db->fetch_object($result);
			if (!(round(abs($obj->deb), 2) === round(abs($obj->cred), 2))) {
				$this->errors[] = 'Total not exacts '.round(abs($obj->deb), 2).' vs '.round(abs($obj->cred), 2);
				$error++;
			}
		} else {
			$this->errors[] = 'Erreur sql'.$this->db->lasterror();
			$error++;
		}

		// Update request

		$now = dol_now();

		if (!$error)
		{
			$sql = "UPDATE ".MAIN_DB_PREFIX."accounting_bookkeeping SET";
			$sql .= " lettering_code='".$lettre."'";
			$sql .= " , date_lettering = '".$this->db->idate($now)."'"; // todo correct date it's false
			$sql .= "  WHERE rowid IN (".implode(',', $ids).") AND date_validated IS NULL ";
			$this->db->begin();

			dol_syslog(get_class($this)."::update sql=".$sql, LOG_DEBUG);
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
			$this->db->rollback();
			return -1 * $error;
		} else {
			$this->db->commit();
			return 1;
		}
	}
}
