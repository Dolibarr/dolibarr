<?php
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 * $Id$
 * $Source$
 */

/**
			\file       htdocs/includes/dons/html_cerfafr.php
			\ingroup    don
			\brief      Formulaire de don
			\version    $Revision$
*/

require_once(DOL_DOCUMENT_ROOT."/master.inc.php");
require_once(DOL_DOCUMENT_ROOT."/projetdon.class.php");
require_once(DOL_DOCUMENT_ROOT."/don.class.php");



/**
	    \class      html_cerfafr
		\brief      Classe permettant de générer les propales au modèle Azur
*/

class html_cerfafr extends ModeleDon
{
    /**
			\brief      Constructeur
    		\param	    db		Handler accès base de donnée
    */
    function html_cerfafr($db)
    {
        global $conf,$langs;

        $this->db = $db;
        $this->name = "cerfafr";
        $this->description = "Modèle de reçu de dons";
    }


    /**
    	    \brief      Renvoi dernière erreur
            \return     string      Dernière erreur
    */
    function pdferror() 
    {
      return $this->error;
    }


    /**
    		\brief      Fonction générant le recu sur le disque
    		\param	    id	        Id du recu à générer
    		\return	    int         >0 si ok, <0 si ko
    */
    function write_file($id)
    {
        global $conf,$langs,$user;
        
        $don = new Don($this->db);
        $don->fetch($id);

        $filename = sanitize_string($don->id);
		$dir = $conf->don->dir_output . "/" . get_exdir("${filename}");
		$file = $dir . "/" . $filename . ".html";

        if (! is_dir($dir))
        {
            if (create_exdir($dir) < 0)
            {
                $this->error=$langs->trans("ErrorCanNotCreateDir",$dir);
                return -1;
            }
        }

        // Defini contenu
        $donmodel=DOL_DOCUMENT_ROOT ."/includes/modules/dons/html_cerfafr.html";
        $html = implode('', file($donmodel));
        $html = eregi_replace('__REF__',$this->id,$html);
        $html = eregi_replace('__DATE__',strftime("%d/%b/%Y %H:%m:%s",mktime()),$html);
        $html = eregi_replace('__IP__',$user->ip,$html);
        
        // Sauve fichier sur disque
        dolibarr_syslog("html_cerfafr::write_file $file");
        $handle=fopen($file,"w");        
        fwrite($handle,$html);
        fclose($handle);

        return 1;
    }
}

?>
