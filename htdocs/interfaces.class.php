<?php
/* Copyright (C) 2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 */

/**
        \file       htdocs/interfaces.class.php
        \ingroup    core
        \brief      Fichier de la classe de gestion des triggers
*/


/**
        \class      Interfaces
        \brief      Classe de la gestion des triggers
*/

class Interfaces
{
    var $dir;

    /**
     *   \brief      Constructeur.
     *   \param      DB      handler d'accès base
     */
    function Interfaces($DB)
    {
        $this->db = $DB ;
        $this->dir = DOL_DOCUMENT_ROOT . "/includes/triggers";
    }
    
    /**
     *   \brief      Fonction appelée lors du déclenchement d'un évènement Dolibarr.
     *               Cette fonction déclenche tous les triggers trouvés
     *   \param      action      Code de l'evenement
     *   \param      object      Objet concern
     *   \param      user        Objet user
     *   \param      lang        Objet lang
     *   \param      conf        Objet conf
     *   \return     int         Nbre de triggers déclenchés si pas d'erreurs. Nb en erreur sinon.
     */
    function run_triggers($action,$object,$user,$lang,$conf)
    {
    
        $handle=opendir($this->dir);
        $modules = array();
        $nbok = $nbko = 0;
    
        while (($file = readdir($handle))!==false)
        {
            if (is_readable($this->dir."/".$file) && eregi('interface_(.*).class.php',$file,$reg))
            {
                $modName = "Interface".ucfirst($reg[1]);
                //print "file=$file"; print "modName=$modName"; exit;
                if ($modName)
                {
                    include_once($this->dir."/".$file);
                    $objMod = new $modName($db);
                    if ($objMod)
                    {
                        if ($objMod->run_trigger($action,$object,$user,$lang,$conf) > 0)
                        {
                            $nbok++;
                        }
                        else
                        {
                            $nbko++;
                        }
                    }
                }
            }
        }
        if ($nbko) return $nbko;
        return $nbok;
    }

}
?>
