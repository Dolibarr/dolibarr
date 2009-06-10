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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
			\file       htdocs/includes/modules/dons/html_cerfafr.modules.php
			\ingroup    don
			\brief      Formulaire de don
			\version    $Id$
*/
require_once(DOL_DOCUMENT_ROOT."/includes/modules/dons/modules_don.php");
require_once(DOL_DOCUMENT_ROOT."/don.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");


/**
	    \class      html_cerfafr
		\brief      Classe permettant de g�n�rer les propales au mod�le Azur
*/
class html_cerfafr extends ModeleDon
{
    /**
			\brief      Constructeur
    		\param	    db		Handler acc�s base de donn�e
    */
    function html_cerfafr($db)
    {
        global $conf,$langs;

        $this->db = $db;
        $this->name = "cerfafr";
        $this->description = "Modele de recu de dons";

        // Dimension page pour format A4
        $this->type = 'html';
    }


    /**
    	    \brief      Renvoi derni�re erreur
            \return     string      Derni�re erreur
    */
    function pdferror()
    {
    	return $this->error;
    }


	/**     \brief     	Return if a module can be used or not
	*      	\return		boolean     true if module can be used
	*/
	function isEnabled()
	{
		return true;
	}


    /**
    		\brief      Fonction generant le recu sur le disque
    		\param	    id	        Id du recu a generer
    		\return	    int         >0 si ok, <0 si ko
    */
    function write_file($don,$outputlangs)
    {
		global $user,$conf,$langs,$mysoc;

		$now=gmmktime();

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
	            if (create_exdir($dir) < 0)
	            {
	                $this->error=$langs->trans("ErrorCanNotCreateDir",$dir);
	                return -1;
	            }
	        }

            if (file_exists($dir))
            {
		        // Defini contenu
		        $donmodel=DOL_DOCUMENT_ROOT ."/includes/modules/dons/html_cerfafr.html";
		        $html = implode('', file($donmodel));
		        $html = eregi_replace('__REF__',$id,$html);
		        $html = eregi_replace('__DATE__',dol_print_date($don->date,'day',false,$outputlangs),$html);
		        $html = eregi_replace('__IP__',$user->ip,$html);
		        $html = eregi_replace('__AMOUNT__',$don->amount,$html);
		        $html = eregi_replace('__CURRENCY__',$outputlangs->transnoentitiesnoconv("Currency".$conf->monnaie),$html);
		        $html = eregi_replace('__CURRENCYCODE__',$conf->monnaie,$html);
		        $html = eregi_replace('__MAIN_INFO_SOCIETE_NOM__',$mysoc->nom,$html);
		        $html = eregi_replace('__MAIN_INFO_SOCIETE_ADRESSE__',$mysoc->adresse,$html);
		        $html = eregi_replace('__MAIN_INFO_SOCIETE_CP__',$mysoc->cp,$html);
		        $html = eregi_replace('__MAIN_INFO_SOCIETE_VILLE__',$mysoc->ville,$html);
		        $html = eregi_replace('__DONATOR_NAME__',$don->nom,$html);
		        $html = eregi_replace('__DONATOR_ADDRESS__',$don->adresse,$html);
		        $html = eregi_replace('__DONATOR_ZIP__',$don->cp,$html);
		        $html = eregi_replace('__DONATOR_TOWN__',$don->ville,$html);
		        $html = eregi_replace('__PAYMENTMODE_LIB__ ',$don->modepaiement,$html);
		        $html = eregi_replace('__NOW__',dol_print_date($now,'',false,$outputlangs),$html);

		        // Sauve fichier sur disque
		        dol_syslog("html_cerfafr::write_file $file");
		        $handle=fopen($file,"w");
		        fwrite($handle,$html);
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
