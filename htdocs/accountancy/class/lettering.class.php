<?php
/* Copyright (C) 2004-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2013      Olivier Geffroy      <jeff@jeffinfo.com>
 * Copyright (C) 2013      Alexandre Spangaro   <alexandre.spangaro@gmail.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file      accountancy/class/bookkeeping.class.php
 * \ingroup   Advanced accountancy
 * \brief     File of class for lettering
 */

include_once DOL_DOCUMENT_ROOT."/accountancy/class/bookkeeping.class.php";
include_once DOL_DOCUMENT_ROOT."/societe/class/societe.class.php";
include_once DOL_DOCUMENT_ROOT."/core/lib/date.lib.php";


/**
 * Class lettering
 */
class lettering extends BookKeeping
{
    /**
     * lettrageTiers
     *
     * @param   int   $socid      Thirdparty id
     * @return  void
     */
	public function lettrageTiers($socid) {

		$db = $this->db;

		$object = new Societe($this->db);
		$object->id = $socid;
		$object->fetch($socid);


		if( $object->code_compta == '411CUSTCODE')
			$object->code_compta = '';

		if( $object->code_compta_fournisseur == '401SUPPCODE')
			$object->code_compta_fournisseur = '';



		$sql = "SELECT bk.rowid, bk.doc_date, bk.doc_type, bk.lettering_code, bk.code_tiers, bk.numero_compte , bk.label_compte, bk.debit , bk.credit, bk.montant , bk.sens , bk.code_journal , bk.piece_num, bk.date_lettering ";
		$sql .= " FROM " . MAIN_DB_PREFIX . "accounting_bookkeeping as bk";
			$sql .= " WHERE code_journal = 'BQ' AND  ( ";
		if(!empty($object->code_compta)  )
			$sql .= "  bk.code_tiers = '" . $object->code_compta . "'  ";
		if(!empty($object->code_compta) &&  !empty($object->code_compta_fournisseur) )
			$sql .= "  OR  ";
		if(!empty($object->code_compta_fournisseur)  )
		$sql .= "   bk.code_tiers = '" . $object->code_compta_fournisseur . "' ";

		$sql .= " ) AND ( bk.date_lettering ='' OR bk.date_lettering IS NULL ) AND bk.lettering_code  !='' ";

		$sql .= " GROUP BY bk.lettering_code  ";


		$resql = $db->query ( $sql );
		if ($resql) {
			$num = $db->num_rows ( $resql );
			$i = 0;

			while ( $i < $num ) {
				$obj = $db->fetch_object ( $resql );
				$i++;

					$sql = "SELECT  bk.rowid  ";
					$sql .= " FROM " . MAIN_DB_PREFIX . "accounting_bookkeeping as bk";
					$sql .= " WHERE  bk.lettering_code = '".$obj->lettering_code."' ";
										$sql .= " AND ( ";
						if(!empty($object->code_compta)  )
							$sql .= "  bk.code_tiers = '" . $object->code_compta . "'  ";
						if(!empty($object->code_compta) &&  !empty($object->code_compta_fournisseur) )
							$sql .= "  OR  ";
						if(!empty($object->code_compta_fournisseur)  )
							$sql .= "   bk.code_tiers = '" . $object->code_compta_fournisseur . "' ";
						$sql .= " )  ";
// echo $sql;
					$resql2 = $db->query ( $sql );
					if ($resql2) {
						$num2 = $db->num_rows ( $resql2 );
						$i2 = 0;
						$ids = array();
						while ( $i2 < $num2 ) {
							$obj2 = $db->fetch_object ( $resql2 );
							$i2++;
							$ids[] = $obj2->rowid;
						}


						if(count($ids)  > 1 ){
							$result =  $this->updatelettrage($ids);

						// 	var_dump($result);
// 							if( $result < 0 ){
// 								setEventMessages('', $BookKeeping->errors, 'errors' );
// 								$error++;
//
// 							}
						}
					}
			}
		}


		/**
			Prise en charge des lettering complexe avec prelevment , virement
		*/
		$sql = "SELECT bk.rowid, bk.doc_date, bk.doc_type, bk.doc_ref, bk.code_tiers, bk.numero_compte , bk.label_compte, bk.debit , bk.credit, bk.montant , bk.sens , bk.code_journal , bk.piece_num, bk.date_lettering, bu.url_id , bu.type ";
		$sql .= " FROM " . MAIN_DB_PREFIX . "accounting_bookkeeping as bk";
		$sql .= " LEFT JOIN  " . MAIN_DB_PREFIX . "bank_url as bu ON(bk.fk_doc = bu.fk_bank AND bu.type IN ('payment', 'payment_supplier') ) ";
		$sql .= " WHERE code_journal = 'BQ' AND  ( ";
		if(!empty($object->code_compta)  )
			$sql .= "  bk.code_tiers = '" . $object->code_compta . "'  ";
		if(!empty($object->code_compta) &&  !empty($object->code_compta_fournisseur) )
			$sql .= "  OR  ";
		if(!empty($object->code_compta_fournisseur)  )
		$sql .= "   bk.code_tiers = '" . $object->code_compta_fournisseur . "' ";

		$sql .= " ) AND date_lettering ='' ";
		$sql .= " GROUP BY bk.lettering_code  ";

// 		echo $sql;
//
		$resql = $db->query ( $sql );
		if ($resql) {
			$num = $db->num_rows ( $resql );
			$i = 0;

			while ( $i < $num ) {
				$obj = $db->fetch_object ( $resql );
				$ids = array();
				$i++;

	// 			print_r($obj);



					if($obj->type =='payment_supplier' ) {
						$ids[] = $obj->rowid;

					$sql= 'SELECT bk.rowid, facf.ref, facf.ref_supplier, payf.fk_bank ';
					$sql.= " FROM " . MAIN_DB_PREFIX . "facture_fourn facf ";
					$sql.= " INNER JOIN " . MAIN_DB_PREFIX . "paiementfourn_facturefourn as payfacf ON  payfacf.fk_facturefourn=facf.rowid";
					$sql.= " INNER JOIN " . MAIN_DB_PREFIX . "paiementfourn as payf ON  payfacf.fk_paiementfourn=payf.rowid";
					$sql.= " INNER JOIN " .MAIN_DB_PREFIX .  "accounting_bookkeeping as bk ON(  bk.fk_doc = facf.ref) ";
	// 				$sqlmid.= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as soc ON  soc.rowid=facf.fk_soc";
	// 				$sqlmid.= " INNER JOIN " . MAIN_DB_PREFIX . "c_paiement as payc ON  payc.id=payf.fk_paiement";
						$sql .= " WHERE 1   ";
						$sql .= "   AND  fk_paiementfourn = '".$obj->url_id."' ";
	// 					$sql .= " AND (bk.numero_compte = '" . $object->code_compta . "' OR  bk.numero_compte = '" . $object->code_compta_fournisseur . "') ";
						$sql .= " AND ( ";
						if(!empty($object->code_compta)  )
							$sql .= "  bk.code_tiers = '" . $object->code_compta . "'  ";
						if(!empty($object->code_compta) &&  !empty($object->code_compta_fournisseur) )
							$sql .= "  OR  ";
						if(!empty($object->code_compta_fournisseur)  )
							$sql .= "   bk.code_tiers = '" . $object->code_compta_fournisseur . "' ";
						$sql .= " )  ";
	// 					echo $sql;
	// 					exit;
					}
					elseif($obj->type =='payment' ){
						$ids[] = $obj->rowid;

						$sql= 'SELECT bk.rowid,fac.facnumber , pay.fk_bank ';
						$sql.= " FROM " . MAIN_DB_PREFIX . "facture fac ";
						$sql.= " INNER JOIN " . MAIN_DB_PREFIX . "paiement_facture as payfac ON  payfac.fk_facture=fac.rowid";
						$sql.= " INNER JOIN " . MAIN_DB_PREFIX . "paiement as pay ON  payfac.fk_paiement=pay.rowid";
						$sql.= " INNER JOIN " .MAIN_DB_PREFIX .  "accounting_bookkeeping as bk ON(  bk.fk_doc = fac.rowid) ";
						$sql .= " WHERE 1   ";
						$sql .= "   AND   payfac.fk_paiement = '".$obj->url_id."' ";
						$sql .= " AND ( ";
						if(!empty($object->code_compta)  )
							$sql .= "  bk.code_tiers = '" . $object->code_compta . "'  ";
						if(!empty($object->code_compta) &&  !empty($object->code_compta_fournisseur) )
							$sql .= "  OR  ";
						if(!empty($object->code_compta_fournisseur)  )
							$sql .= "   bk.code_tiers = '" . $object->code_compta_fournisseur . "' ";
						$sql .= " )  ";

	// 					echo $sql;
					}



					$resql2 = $db->query ( $sql );
					if ($resql2) {
						$num2 = $db->num_rows ( $resql2 );
						$i2 = 0;

						while ( $i2 < $num2 ) {
							$obj2 = $db->fetch_object ( $resql2 );
							$i2++;
							$ids[] = $obj2->rowid;
						}

	// 					print_r($ids);
	// 					exit;
						if(count($ids)  > 1 ){
							$result =  $this->updatelettrage($ids);

	// 						var_dump($result);
// 							if( $result < 0 ){
// 								setEventMessages('', $BookKeeping->errors, 'errors' );
// 								$error++;
//
// 							}
						}

	// 					exit;
					}
			}
		}


	}


	public function updatelettrage($ids, $notrigger=false){
		$error = 0;

		$sql = "SELECT lettering_code FROM " . MAIN_DB_PREFIX . "accounting_bookkeeping WHERE ";
		$sql .= " lettering_code != '' GROUP BY lettering_code ORDER BY lettering_code DESC limit 1;  ";
// 		echo $sql;
		$result = $this->db->query ( $sql );
		if ($result) {
			$obj = $this->db->fetch_object ( $result );
			$lettre = (empty($obj->lettering_code)? 'AAA' : $obj->lettering_code );
			if(!empty($obj->lettering_code))
				$lettre++;
		}
		else{
			$this->errors[] = 'Error'.$this->db->lasterror();;
			$error++;
		}
// 			var_dump(__line__, $error);

		$sql = "SELECT SUM(ABS(debit)) as deb, SUM(ABS(credit)) as cred   FROM " . MAIN_DB_PREFIX . "accounting_bookkeeping WHERE ";
		$sql .= " rowid IN (".implode(',', $ids).") ";
		$result = $this->db->query ( $sql );
		if ($result) {
			$obj = $this->db->fetch_object ( $result );
// 			print_r($obj);
			if( !(round(abs($obj->deb),2) === round(abs($obj->cred),2)) ){
// 				echo $sql;
// 				print_r($obj);
				$this->errors[] = 'Total not exacts '.round(abs($obj->deb),2).' vs '. round(abs($obj->cred),2);
				$error++;
			}
		}
		else{
			$this->errors[] = 'Erreur sql'.$this->db->lasterror();;
			$error++;
		}


		// Update request

		$now = dol_now();

		$sql = "UPDATE ".MAIN_DB_PREFIX."accounting_bookkeeping SET";
		$sql.= " lettering_code='".$lettre."'";
		$sql.= " , date_lettering = " .$now ;  // todo correct date it's false
		$sql.= "  WHERE rowid IN (".implode(',', $ids).") ";
// 		echo $sql ;
//
// 		var_dump(__line__, $error);
// 		print_r($this->errors);
// 		exit;
		$this->db->begin();

			dol_syslog(get_class($this)."::update sql=".$sql, LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

			if (! $error)
			{
				if (! $notrigger)
				{
					// Uncomment this and change MYOBJECT to your own tag if you
					// want this action calls a trigger.

					//// Call triggers
					//include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
					//$interface=new Interfaces($this->db);
					//$result=$interface->run_triggers('MYOBJECT_MODIFY',$this,$user,$langs,$conf);
					//if ($result < 0) { $error++; $this->errors=$interface->errors; }
					//// End call triggers
				}
			}
// 				var_dump(__line__, $error);
			// Commit or rollback
			if ($error)
			{
// 				foreach($this->errors as $errmsg)
// 				{
// 					dol_syslog(get_class($this)."::update ".$errmsg, LOG_ERR);
// 					$this->error.=($this->error?', '.$errmsg:$errmsg);
// 				}
				$this->db->rollback();
// 				echo $this->error;
// 						var_dump(__line__, $error);
				return -1*$error;
			}
			else
			{
				$this->db->commit();
				return 1;
			}
	}

}

