<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * \file       htdocs/core/modules/dons/html_cerfafr.modules.php
 * \ingroup    don
 * \brief      Formulaire de don
 */
require_once(DOL_DOCUMENT_ROOT."/core/modules/dons/modules_don.php");
require_once(DOL_DOCUMENT_ROOT."/compta/dons/class/don.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/company.lib.php");


/**
 *	Class to generate document for subscriptions
 */
class html_cerfafr extends ModeleDon
{
    /**
     *  Constructor
     *
     *  @param      DoliDb		$db      Database handler
     */
    function html_cerfafr($db)
    {
        global $conf,$langs;

        $this->db = $db;
        $this->name = "cerfafr";
        $this->description = $langs->trans('DonationsReceiptModel');

        // Dimension page pour format A4
        $this->type = 'html';
    }


	/**
	 * 	Return if a module can be used or not
	 *   
	 *  @return	boolean     true if module can be used
	 */
	function isEnabled()
	{
		return true;
	}


    /**
     *  Write the object to document file to disk
     * 
     *	@param	Don			$don	        Donation object
     *  @param  Translate	$outputlangs    Lang object for output language
     *	@return	int             			>0 if OK, <0 if KO
     */
    function write_file($don,$outputlangs)
    {
		global $user,$conf,$langs,$mysoc;

		$now=dol_now();

		if (! is_object($outputlangs)) $outputlangs=$langs;

		$outputlangs->load("main");
		$outputlangs->load("dict");
		$outputlangs->load("companies");
		$outputlangs->load("bills");
		$outputlangs->load("products");

        if ($conf->don->dir_output)
        {
			// Definition de l'objet $don (pour compatibilite ascendante)
        	if (! is_object($don))
        	{
	            $id = $don;
	            $don = new Don($this->db);
	            $ret=$don->fetch($id);
			}

			// Definition de $dir et $file
			if ($don->specimen)
			{
				$dir = $conf->don->dir_output;
				$file = $dir . "/SPECIMEN.html";
			}
			else
			{
				$donref = dol_sanitizeFileName($don->ref);
				$dir = $conf->don->dir_output . "/" . get_exdir($donref,2);
				$file = $dir . "/" . $donref . ".html";
			}

	        if (! file_exists($dir))
	        {
	            if (dol_mkdir($dir) < 0)
	            {
	                $this->error=$langs->trans("ErrorCanNotCreateDir",$dir);
	                return -1;
	            }
	        }

            if (file_exists($dir))
            {
		        // Defini contenu
		        $donmodel=DOL_DOCUMENT_ROOT ."/core/modules/dons/html_cerfafr.html";
		        $form = implode('', file($donmodel));
		        $form = str_replace('__REF__',$id,$form);
		        $form = str_replace('__DATE__',dol_print_date($don->date,'day',false,$outputlangs),$form);
		        $form = str_replace('__IP__',$user->ip,$form);
		        $form = str_replace('__AMOUNT__',$don->amount,$form);
		        $form = str_replace('__CURRENCY__',$outputlangs->transnoentitiesnoconv("Currency".$conf->currency),$form);
		        $form = str_replace('__CURRENCYCODE__',$conf->currency,$form);
		        $form = str_replace('__MAIN_INFO_SOCIETE_NOM__',$mysoc->name,$form);
		        $form = str_replace('__MAIN_INFO_SOCIETE_ADRESSE__',$mysoc->address,$form);
		        $form = str_replace('__MAIN_INFO_SOCIETE_CP__',$mysoc->zip,$form);
		        $form = str_replace('__MAIN_INFO_SOCIETE_VILLE__',$mysoc->town,$form);
		        $form = str_replace('__DONATOR_NAME__',$don->nom,$form);
		        $form = str_replace('__DONATOR_ADDRESS__',$don->adresse,$form);
		        $form = str_replace('__DONATOR_ZIP__',$don->cp,$form);
		        $form = str_replace('__DONATOR_TOWN__',$don->ville,$form);
		        $form = str_replace('__PAYMENTMODE_LIB__ ',$don->modepaiement,$form);
		        $form = str_replace('__NOW__',dol_print_date($now,'',false,$outputlangs),$form);

		        // Sauve fichier sur disque
		        dol_syslog("html_cerfafr::write_file $file");
		        $handle=fopen($file,"w");
		        fwrite($handle,$form);
		        fclose($handle);
				if (! empty($conf->global->MAIN_UMASK))
					@chmod($file, octdec($conf->global->MAIN_UMASK));

		        return 1;
            }
            else
            {
                $this->error=$langs->trans("ErrorCanNotCreateDir",$dir);
                return 0;
            }
	    }
        else
        {
            $this->error=$langs->trans("ErrorConstantNotDefined","DON_OUTPUTDIR");
            return 0;
		}
        $this->error=$langs->trans("ErrorUnknown");
        return 0;   // Erreur par defaut
    }
}

?>
