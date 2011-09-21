<?php
/* Copyright (C) 2002      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *      \file       htdocs/compta/sociales/class/chargesociales.class.php
 *		\ingroup    facture
 *		\brief      Fichier de la classe des charges sociales
 */

require_once(DOL_DOCUMENT_ROOT."/core/class/commonobject.class.php");


/**     \class      ChargeSociales
 *		\brief      Classe permettant la gestion des paiements des charges
 *                  La tva collectee n'est calculee que sur les factures payees.
 */
class ChargeSociales extends CommonObject
{
	public $element='rowid';
    public $table='chargesociales';
	public $table_element='chargesociales';

	var $id;
	var $date_ech;
	var $lib;
	var $type;
	var $type_libelle;
	var $amount;
	var $paye;
	var $periode;


	function ChargeSociales($DB)
	{
		$this->db = $DB;

		return 1;
	}

	/**
	 *   \brief      Retrouve et charge une charge sociale
	 *   \return     int     1 si trouve, 0 sinon
	 */
	function fetch($id)
	{
		$sql = "SELECT cs.rowid, cs.date_ech,";
		$sql.= " cs.libelle as lib, cs.fk_type, cs.amount, cs.paye, cs.periode,";
		$sql.= " c.libelle";
		$sql.= " FROM ".MAIN_DB_PREFIX."chargesociales as cs, ".MAIN_DB_PREFIX."c_chargesociales as c";
		$sql.= " WHERE cs.fk_type = c.id";
		$sql.= " AND cs.rowid = ".$id;

		dol_syslog("ChargesSociales::fetch sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);

				$this->id             = $obj->rowid;
				$this->ref            = $obj->rowid;
				$this->date_ech       = $this->db->jdate($obj->date_ech);
				$this->lib            = $obj->lib;
				$this->type           = $obj->fk_type;
				$this->type_libelle   = $obj->libelle;
				$this->amount         = $obj->amount;
				$this->paye           = $obj->paye;
				$this->periode        = $this->db->jdate($obj->periode);

				return 1;
			}
			else
			{
				return 0;
			}
			$this->db->free($resql);
		}
		else
		{
			$this->error=$this->db->error();
			return -1;
		}
	}


	/**
	 *      \brief      Create a social contribution in database
	 *      \param      user    User making creation
	 *      \return     int     <0 if KO, id if OK
	 */
	function create($user)
	{
		// Nettoyage parametres
		$newamount=price2num($this->amount,'MT');

		// Validation parametres
		if (! $newamount > 0)
		{
			$this->error="ErrorBadParameter";
			return -2;
		}

		$this->db->begin();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."chargesociales (fk_type, libelle, date_ech, periode, amount)";
		$sql.= " VALUES (".$this->type.",'".$this->db->escape($this->lib)."',";
		$sql.= " '".$this->db->idate($this->date_ech)."','".$this->db->idate($this->periode)."',";
		$sql.= " ".price2num($newamount);
		$sql.= ")";

		dol_syslog("ChargesSociales::create sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$this->id=$this->db->last_insert_id(MAIN_DB_PREFIX."chargesociales");

			//dol_syslog("ChargesSociales::create this->id=".$this->id);
			$this->db->commit();
			return $this->id;
		}
		else
		{
			$this->error=$this->db->error();
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 *      Delete a social contribution
	 *      @param      user    Object user making delete
	 *      @return     int     <0 if KO, >0 if OK
	 */
	function delete($user)
	{
	    $error=0;

	    $this->db->begin();

	    // Get bank transaction lines for this social contributions
	    include_once(DOL_DOCUMENT_ROOT."/compta/bank/class/account.class.php");
	    $account=new Account($this->db);
        $lines_url=$account->get_url('',$this->id,'sc');

        // Delete bank urls
        foreach ($lines_url as $line_url)
        {
            if (! $error)
            {
                $accountline=new AccountLine($this->db);
                $accountline->fetch($line_url['fk_bank']);
                $result=$accountline->delete_urls($user);
                if ($result < 0)
                {
                    $error++;
                }
            }
        }

        // Delete payments
        if (! $error)
        {
    	    $sql = "DELETE FROM ".MAIN_DB_PREFIX."paiementcharge where fk_charge='".$this->id."'";
    		dol_syslog(get_class($this)."::delete sql=".$sql);
    		$resql=$this->db->query($sql);
    		if (! $resql)
    		{
    		    $error++;
    			$this->error=$this->db->lasterror();
    		}
        }

        if (! $error)
        {
            $sql = "DELETE FROM ".MAIN_DB_PREFIX."chargesociales where rowid='".$this->id."'";
    		dol_syslog(get_class($this)."::delete sql=".$sql);
    		$resql=$this->db->query($sql);
    		if (! $resql)
    		{
    		    $error++;
    			$this->error=$this->db->lasterror();
    		}
        }

		if (! $error)
		{
		    $this->db->commit();
			return 1;
		}
		else
		{
		    $this->db->rollback();
			return -1;
		}

	}


	/**
	 *      Met a jour une charge sociale
	 *      @param      user    Utilisateur qui modifie
	 *      @return     int     <0 si erreur, >0 si ok
	 */
	function update($user)
	{
		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."chargesociales";
		$sql.= " SET libelle='".$this->db->escape($this->lib)."',";
		$sql.= " date_ech='".$this->db->idate($this->date_ech)."',";
		$sql.= " periode='".$this->db->idate($this->periode)."'";
		$sql.= " WHERE rowid=".$this->id;

		dol_syslog("ChargesSociales::update sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$this->db->commit();
			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			$this->db->rollback();
			return -1;
		}
	}


	function solde($year = 0)
	{
		$sql = "SELECT sum(f.amount) as amount";
		$sql .= " FROM ".MAIN_DB_PREFIX."chargesociales as f WHERE paye = 0";

		if ($year) {
			$sql .= " AND f.datev >= '$y-01-01' AND f.datev <= '$y-12-31' ";
		}

		$result = $this->db->query($sql);
		if ($result) {
			if ($this->db->num_rows($result)) {
				$obj = $this->db->fetch_object($result);
				return $obj->amount;
			} else {
				return 0;
			}

			$this->db->free();

		} else {
			print $this->db->error();
			return -1;
		}
	}

	/**
	 *    Tag social contribution as payed completely
	 *    @param      user         Object user making change
	 */
	function set_paid($user)
	{
		$sql = "UPDATE ".MAIN_DB_PREFIX."chargesociales";
		$sql.= " set paye=1";
		$sql.= " WHERE rowid = ".$this->id;
		$return = $this->db->query($sql);
		if ($return) return 1;
		else return -1;
	}

	/**
	 *    \brief      Retourne le libelle du statut d'une charge (impaye, payee)
	 *    \param      mode          0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long
	 *    \return     string        Libelle
	 */
	function getLibStatut($mode=0)
	{
		return $this->LibStatut($this->paye,$mode);
	}

	/**
	 *    	\brief      Renvoi le libelle d'un statut donne
	 *    	\param      statut        	Id statut
	 *    	\param      mode          	0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 *    	\return     string        	Libelle du statut
	 */
	function LibStatut($statut,$mode=0)
	{
		global $langs;
		$langs->load('customers');

		if ($mode == 0)
		{
			if ($statut ==  0) return $langs->trans("Unpaid");
			if ($statut ==  1) return $langs->trans("Paid");
		}
		if ($mode == 1)
		{
			if ($statut ==  0) return $langs->trans("Unpaid");
			if ($statut ==  1) return $langs->trans("Paid");
		}
		if ($mode == 2)
		{
			if ($statut ==  0) return img_picto($langs->trans("Unpaid"), 'statut1').' '.$langs->trans("Unpaid");
			if ($statut ==  1) return img_picto($langs->trans("Paid"), 'statut6').' '.$langs->trans("Paid");
		}
		if ($mode == 3)
		{
			if ($statut ==  0) return img_picto($langs->trans("Unpaid"), 'statut1');
			if ($statut ==  1) return img_picto($langs->trans("Paid"), 'statut6');
		}
		if ($mode == 4)
		{
			if ($statut ==  0) return img_picto($langs->trans("Unpaid"), 'statut1').' '.$langs->trans("Unpaid");
			if ($statut ==  1) return img_picto($langs->trans("Paid"), 'statut6').' '.$langs->trans("Paid");
		}
		if ($mode == 5)
		{
			if ($statut ==  0) return $langs->trans("Unpaid").' '.img_picto($langs->trans("Unpaid"), 'statut1');
			if ($statut ==  1) return $langs->trans("Paid").' '.img_picto($langs->trans("Paid"), 'statut6');
		}

		return "Error, mode/status not found";
	}


	/**
	 *    	\brief      Renvoie nom clicable (avec eventuellement le picto)
	 *		\param		withpicto		0=Pas de picto, 1=Inclut le picto dans le lien, 2=Picto seul
	 * 		\param		maxlen			Longueur max libelle
	 *		\return		string			Chaine avec URL
	 */
	function getNomUrl($withpicto=0,$maxlen=0)
	{
		global $langs;

		$result='';

		if (empty($this->ref)) $this->ref=$this->lib;

		$lien = '<a href="'.DOL_URL_ROOT.'/compta/sociales/charges.php?id='.$this->id.'">';
		$lienfin='</a>';

		if ($withpicto) $result.=($lien.img_object($langs->trans("ShowSocialContribution").': '.$this->lib,'bill').$lienfin.' ');
		if ($withpicto && $withpicto != 2) $result.=' ';
		if ($withpicto != 2) $result.=$lien.($maxlen?dol_trunc($this->ref,$maxlen):$this->ref).$lienfin;
		return $result;
	}

	/**
	 * 	Return amount of payments already done
	 *	@return		int		Amount of payment already done, <0 if KO
	 */
	function getSommePaiement()
	{
		$table='paiementcharge';
		$field='fk_charge';

		$sql = 'SELECT sum(amount) as amount';
		$sql.= ' FROM '.MAIN_DB_PREFIX.$table;
		$sql.= ' WHERE '.$field.' = '.$this->id;

		dol_syslog("ChargeSociales::getSommePaiement sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
		    $amount=0;

			$obj = $this->db->fetch_object($resql);
			if ($obj) $amount=$obj->amount?$obj->amount:0;

			$this->db->free($resql);
			return $amount;
		}
		else
		{
			return -1;
		}
	}

    /**
     *  Initialise an instance with random values.
     *  Used to build previews or test instances.
     *	id must be 0 if object instance is a specimen.
     *
     *  @return	void
     */
    function initAsSpecimen()
    {
        global $user,$langs,$conf;

        // Initialize parameters
        $this->id=0;
        $this->ref = 'SPECIMEN';
        $this->specimen=1;
        $this->paye = 0;
        $this->date = time();
        $this->date_ech=$this->date+3600*24*30;
        $this->period=$this->date+3600*24*30;
        $this->amount=100;
        $this->lib = 0;
        $this->type = 1;
        $this->type_libelle = 'Social contribution label';
    }
}

?>
