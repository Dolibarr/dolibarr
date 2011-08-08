<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *	    \file       htdocs/includes/modules/dons/modules_don.php
 *		\ingroup    don
 *		\brief      File of class to manage donation document generation
 *		\version    $Id: modules_don.php,v 1.21 2011/08/08 16:07:48 eldy Exp $
 */
require_once(DOL_DOCUMENT_ROOT.'/lib/pdf.lib.php');
require_once(DOL_DOCUMENT_ROOT.'/includes/fpdf/fpdfi/fpdi_protection.php');
require_once(DOL_DOCUMENT_ROOT."/compta/dons/class/don.class.php");



/**
 *	    \class      ModeleDon
 *		\brief      Classe mere des modeles de dons
 */
class ModeleDon
{
    var $error='';

    /**
     *      \brief      Return list of active generation modules
     * 		\param		$db		Database handler
     */
    function liste_modeles($db)
    {
        global $conf;

        $type='donation';
        $liste=array();

        include_once(DOL_DOCUMENT_ROOT.'/lib/functions2.lib.php');
        $liste=getListOfModels($db,$type,'');

        return $liste;
    }
}


/**
 *	\class 		ModeleNumRefDons
 *	\brief  	Classe mere des modeles de numerotation des references des dons
 */
class ModeleNumRefDons
{
    var $error='';

    /**     \brief     	Return if a module can be used or not
     *      	\return		boolean     true if module can be used
     */
    function isEnabled()
    {
        return true;
    }

    /**     \brief      Renvoi la description par defaut du modele de num�rotation
     *      \return     string      Texte descripif
     */
    function info()
    {
        global $langs;
        $langs->load("bills");
        return $langs->trans("NoDescription");
    }

    /**     \brief      Renvoi un exemple de num�rotation
     *      \return     string      Example
     */
    function getExample()
    {
        global $langs;
        $langs->load("bills");
        return $langs->trans("NoExample");
    }

    /**     \brief      Test si les num�ros d�j� en vigueur dans la base ne provoquent pas d
     *                  de conflits qui empechera cette num�rotation de fonctionner.
     *      \return     boolean     false si conflit, true si ok
     */
    function canBeActivated()
    {
        return true;
    }

    /**     \brief      Renvoi prochaine valeur attribu�e
     *      \return     string      Valeur
     */
    function getNextValue()
    {
        global $langs;
        return $langs->trans("NotAvailable");
    }

    /**     \brief      Renvoi version du module numerotation
     *      	\return     string      Valeur
     */
    function getVersion()
    {
        global $langs;
        $langs->load("admin");

        if ($this->version == 'development') return $langs->trans("VersionDevelopment");
        if ($this->version == 'experimental') return $langs->trans("VersionExperimental");
        if ($this->version == 'dolibarr') return DOL_VERSION;
        return $langs->trans("NotAvailable");
    }
}


/**
 \brief      Cr�e un don sur disque en fonction du mod�le de DON_ADDON_PDF
 \param	    db  			objet base de donn�e
 \param	    id				id du don � cr�er
 \param	    message			message
 \param	    modele			force le modele � utiliser ('' par defaut)
 \param		outputlangs		objet lang a utiliser pour traduction
 \return     int         	0 si KO, 1 si OK
 */
function don_create($db, $id, $message, $modele, $outputlangs)
{
    global $conf, $langs;
    $langs->load("bills");

    $dir = DOL_DOCUMENT_ROOT . "/includes/modules/dons/";

    // Positionne modele sur le nom du modele � utiliser
    if (! dol_strlen($modele))
    {
        if ($conf->global->DON_ADDON_MODEL)
        {
            $modele = $conf->global->DON_ADDON_MODEL;
        }
        else
        {
            print $langs->trans("Error")." ".$langs->trans("Error_DON_ADDON_MODEL_NotDefined");
            return 0;
        }
    }

    // Charge le modele
    $file = $modele.".modules.php";
    if (file_exists($dir.$file))
    {
        $classname = $modele;

        require_once($dir.$file);

        $obj = new $classname($db);

        $obj->message = $message;

        // We save charset_output to restore it because write_file can change it if needed for
        // output format that does not support UTF8.
        $sav_charset_output=$outputlangs->charset_output;
        if ($obj->write_file($id,$outputlangs) > 0)
        {
            // Succ�s de la cr�ation de la facture. On g�n�re le fichier meta
            don_meta_create($db, $id);
            // et on supprime l'image correspondant au preview
            don_delete_preview($db, $id);

            $outputlangs->charset_output=$sav_charset_output;
            return 1;
        }
        else
        {
            $outputlangs->charset_output=$sav_charset_output;
            dol_syslog("Erreur dans don_create");
            dol_print_error($db,$obj->error);
            return 0;
        }
    }
    else
    {
        print $langs->trans("Error")." ".$langs->trans("ErrorFileDoesNotExists",$dir.$file);
        return 0;
    }
}

/**
 \brief       Cr�� un meta fichier � c�t� de la facture sur le disque pour faciliter les recherches en texte plein. Pourquoi ? tout simplement parcequ'en fin d'exercice quand je suis avec mon comptable je n'ai pas de connexion internet "rapide" pour retrouver en 2 secondes une facture non pay�e ou compliqu�e � g�rer ... avec un rgrep c'est vite fait bien fait [eric seigne
 \param	    db  		Objet base de donn�e
 \param	    donid		Id du don � cr�er
 \param       message     Message
 */
function don_meta_create($db, $donid, $message="")
{
    global $langs,$conf;

    $don = new Don($db);
    $don->id=$donid;
    $don->fetch($donid);
}


/**
 \brief       Supprime l'image de pr�visualitation, pour le cas de r�g�n�ration de facture
 \param	    db  		Objet base de donn�e
 \param	    donid		Id du don
 */
function don_delete_preview($db, $donid)
{
    global $langs,$conf;

    $don = new Don($db);
    $don->id=$donid;
    $don->fetch($donid);
}

?>
