<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005      Regis Houssin        <regis@dolibarr.fr>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 * or see http://www.gnu.org/
 *
 * $Id$
 * $Source$
 */

/**
	    \file       htdocs/includes/modules/societe/modules_societe.php
		\ingroup    societe
		\brief      Fichier contenant la classe mère de module de generation societes
		\version    $Revision$
*/


/**
	    \class      ModeleThirdPartyCode
		\brief  	Classe mère des modèles de numérotation des codes tiers
*/

class ModeleThirdPartyCode
{
    var $error='';

    /**     \brief      Renvoi la description par defaut du modele de numérotation
     *      \return     string      Texte descripif
     */
    function info($langs)
    {
        $langs->load("bills");
        return $langs->trans("NoDescription");
    }

    /**     \brief      Renvoi nom module
     *      \return     string      Nom du module
     */
    function getNom($langs)
    {
        return $this->nom;
    }


    /**     \brief      Renvoi un exemple de numérotation
     *      \return     string      Example
     */
    function getExample($langs)
    {
        $langs->load("bills");
        return $langs->trans("NoExample");
    }

    /**     \brief      Test si les numéros déjà en vigueur dans la base ne provoquent pas de
     *                  de conflits qui empechera cette numérotation de fonctionner.
     *      \return     boolean     false si conflit, true si ok
     */
    function canBeActivated()
    {
        return true;
    }

    /**     \brief      Renvoi prochaine valeur attribuée
     *      \return     string      Valeur
     */
    function getNextValue($langs)
    {
        return $langs->trans("NotAvailable");
    }

    
    /** 
     *      \brief      Renvoi la liste des modèles actifs
     *      \param      db      Handler de base
     */
    function liste_modeles($db)
    {
        $liste=array();
        $sql ="";
        
        $resql = $db->query($sql);
        if ($resql)
        {
            $num = $db->num_rows($resql);
            $i = 0;
            while ($i < $num)
            {
                $row = $db->fetch_row($resql);
                $liste[$row[0]]=$row[1];
                $i++;
            }
        }
        else
        {
            return -1;
        }
        return $liste;
    }

    /** 
     *      \brief      Return description of module parameters
     *      \param      langs      	Output language
	 *		\param		soc			Third party object
	 *		\param		type		-1=Nothing, 0=Customer, 1=Supplier
	 *		\return		string		HTML translated description
     */
    function getToolTip($langs,$soc,$type)
    {
		$s='';
		if ($type == 0)  $s.=$langs->trans("CustomerCodeDesc").'<br>';
		if ($type == 1)  $s.=$langs->trans("SupplierCodeDesc").'<br>';
		if ($type != -1) $s.=$langs->trans("ValidityControledByModule").': <b>'.$this->getNom($langs).'</b><br>';
		if ($type == 0)  $s.=$langs->trans("RequiredIfCustomer").': <b>'.yn(!$this->code_null).'</b><br>';
		if ($type == 1)  $s.=$langs->trans("RequiredIfSupplier").': <b>'.yn(!$this->code_null).'</b><br>';
		if ($type == -1) $s.=$langs->trans("Required").': <b>'.yn(!$this->code_null).'</b><br>';
		$s.=$langs->trans("CanBeModifiedIfOk").': <b>'.yn($this->code_modifiable).'</b><br>';
		$s.=$langs->trans("CanBeModifiedIfKo").': <b>'.yn($this->code_modifiable_invalide).'</b><br>';
		$s.=$langs->trans("AutomaticCode").': <b>'.yn($this->code_auto).'</b><br>';
		if ($type != -1) $s.=$langs->trans("Example").': <b>'.$this->getExample($langs,$soc,1).'</b>';
		return $s;
	}
}


/**
		\class		ModeleAccountancyCode
		\brief  	Classe mère des modèles de numérotation des codes compta
*/

class ModeleAccountancyCode
{
    var $error='';

    /**     \brief      Renvoi la description par defaut du modele de numérotation
     *      \return     string      Texte descripif
     */
    function info($langs)
    {
        $langs->load("bills");
        return $langs->trans("NoDescription");
    }

    /**     \brief      Renvoi un exemple de numérotation
     *      \return     string      Example
     */
    function getExample($langs)
    {
        $langs->load("bills");
        return $langs->trans("NoExample");
    }

    /**     \brief      Test si les numéros déjà en vigueur dans la base ne provoquent pas de
     *                  de conflits qui empechera cette numérotation de fonctionner.
     *      \return     boolean     false si conflit, true si ok
     */
    function canBeActivated()
    {
        return true;
    }

    /**     \brief      Renvoi prochaine valeur attribuée
     *      \return     string      Valeur
     */
    function getNextValue($langs)
    {
        return $langs->trans("NotAvailable");
    }
}

?>
