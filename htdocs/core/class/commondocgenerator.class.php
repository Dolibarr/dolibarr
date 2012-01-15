<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2007 Regis Houssin        <regis@dolibarr.fr>
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
 * or see http://www.gnu.org/
 */

/**
 *	    \file       htdocs/core/class/commondocgenerator.class.php
 *		\ingroup    core
 *		\brief      File of parent class for documents generators
 */


/**
 *	\class      CommonDocGenerator
 *	\brief      Parent class for documents generators
 */
abstract class CommonDocGenerator
{
	var $error='';


    /**
     * Define array with couple subtitution key => subtitution value
     *
     * @param   $user               User
     * @param   $outputlangs        Language object for output
     */
    function get_substitutionarray_user($user,$outputlangs)
    {
        global $conf;

        return array(
            'myuser_lastname'=>$user->lastname,
            'myuser_firstname'=>$user->firstname,
            'myuser_login'=>$user->login,
            'myuser_phone'=>$user->officephone,
            'myuser_fax'=>$user->officefax,
            'myuser_mobile'=>$user->user_mobile,
            'myuser_email'=>$user->user_email,
            'myuser_web'=>$user->url
        );
    }


    /**
     * Define array with couple subtitution key => subtitution value
     *
     * @param   $mysoc
     * @param   $outputlangs        Language object for output
     */
    function get_substitutionarray_mysoc($mysoc,$outputlangs)
    {
        global $conf;

        if (empty($mysoc->forme_juridique) && ! empty($mysoc->forme_juridique_code))
        {
            $mysoc->forme_juridique=getFormeJuridiqueLabel($mysoc->forme_juridique_code);
        }

        $logotouse=$conf->mycompany->dir_output.'/logos/thumbs/'.$mysoc->logo_small;

        return array(
            'mycompany_logo'=>$logotouse,
            'mycompany_name'=>$mysoc->name,
            'mycompany_email'=>$mysoc->email,
            'mycompany_phone'=>$mysoc->phone,
            'mycompany_fax'=>$mysoc->fax,
            'mycompany_address'=>$mysoc->address,
            'mycompany_zip'=>$mysoc->zip,
            'mycompany_town'=>$mysoc->town,
            'mycompany_country'=>$outputlangs->transnoentitiesnoconv("Country".$mysoc->pays_code),
            'mycompany_country_code'=>$mysoc->pays_code,
            'mycompany_web'=>$mysoc->url,
            'mycompany_juridicalstatus'=>$mysoc->forme_juridique,
            'mycompany_capital'=>$mysoc->capital,
            'mycompany_barcode'=>$mysoc->barcode,
            'mycompany_idprof1'=>$mysoc->idprof1,
            'mycompany_idprof2'=>$mysoc->idprof2,
            'mycompany_idprof3'=>$mysoc->idprof3,
            'mycompany_idprof4'=>$mysoc->idprof4,
            'mycompany_vatnumber'=>$mysoc->tva_intra,
            'mycompany_note'=>$mysoc->note
        );
    }


    /**
     * Define array with couple subtitution key => subtitution value
     *
     * @param   $object
     * @param   $outputlangs        Language object for output
     */
    function get_substitutionarray_thirdparty($object,$outputlangs)
    {
        global $conf;

        return array(
            'company_name'=>$object->name,
            'company_email'=>$object->email,
            'company_phone'=>$object->phone,
            'company_fax'=>$object->fax,
            'company_address'=>$object->address,
            'company_zip'=>$object->zip,
            'company_town'=>$object->town,
            'company_country_code'=>$object->country_code,
            'company_country'=>$outputlangs->transnoentitiesnoconv("Country".$object->country_code),
            'company_web'=>$object->url,
            'company_barcode'=>$object->barcode,
            'company_vatnumber'=>$object->tva_intra,
            'company_customercode'=>$object->code_client,
            'company_suppliercode'=>$object->code_fournisseur,
            'company_customeraccountancycode'=>$object->code_compta,
            'company_supplieraccountancycode'=>$object->code_compta_fournisseur,
            'company_juridicalstatus'=>$object->forme_juridique,
            'company_capital'=>$object->capital,
            'company_idprof1'=>$object->idprof1,
            'company_idprof2'=>$object->idprof2,
            'company_idprof3'=>$object->idprof3,
            'company_idprof4'=>$object->idprof4,
            'company_note'=>$object->note
        );
    }

}

?>
