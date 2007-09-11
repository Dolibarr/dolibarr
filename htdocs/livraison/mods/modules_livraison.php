<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2006      Regis Houssin        <regis.houssin@cap-networks.com>
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
            \file       htdocs/livraison/mods/modules_livraison.php
            \ingroup    expedition
            \brief      Fichier contenant la classe mère de generation de bon de livraison en PDF
                            et la classe mère de numérotation des bons de livraisons
            \version    $Revision$
*/

require_once(DOL_DOCUMENT_ROOT.'/includes/fpdf/fpdfi/fpdi_protection.php');


/**
            \class      ModelePDFDeliveryOrder
                \brief      Classe mère des modèles de bon de livraison
*/
class ModelePDFDeliveryOrder extends FPDF
{
    var $error='';

   /** 
        \brief Renvoi le dernier message d'erreur de création de PDF de bon de livraison
    */
    function pdferror()
    {
        return $this->error;
    }

    /** 
     *      \brief      Renvoi la liste des modèles actifs
     */
    function liste_modeles($db)
    {
        $type='delivery';
        $liste=array();
        $sql ="SELECT nom as id, nom as lib";
        $sql.=" FROM ".MAIN_DB_PREFIX."document_model";
        $sql.=" WHERE type = '".$type."'";
        
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
            $this->error=$db->error();
            return -1;
        }
        return $liste;
    }

}



/**
        \class      ModeleNumRefDeliveryOrder
            \brief      Classe mère des modèles de numérotation des références de bon de livraison
*/

class ModeleNumRefDeliveryOrder
{
    var $error='';

    /**     \brief      Renvoi la description par defaut du modele de numérotation
     *      \return     string      Texte descripif
     */
    function info()
    {
        global $langs;
        $langs->load("deliveries");
        return $langs->trans("NoDescription");
    }

    /**     \brief      Renvoi un exemple de numérotation
     *      \return     string      Example
     */
    function getExample()
    {
        global $langs;
        $langs->load("deliveries");
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




function delivery_order_pdf_create($db, $deliveryid, $modele='', $outputlangs='')
{
  global $langs;
  $langs->load("deliveries");
 
  $dir = DOL_DOCUMENT_ROOT."/livraison/mods/pdf/";

  // Positionne modele sur le nom du modele de bon de livraison à utiliser
  if (! strlen($modele))
    {
      if (defined("LIVRAISON_ADDON_PDF") && LIVRAISON_ADDON_PDF)
        {
          $modele = LIVRAISON_ADDON_PDF;
        }
      else
        {
      print $langs->trans("Error")." ".$langs->trans("Error_LIVRAISON_ADDON_PDF_NotDefined");
          return 0;
        }
    }
  // Charge le modele
  $file = "pdf_".$modele.".modules.php";
  if (file_exists($dir.$file))
    {
      $classname = "pdf_".$modele;
      require_once($dir.$file);
  
      $obj = new $classname($db);

      if ($obj->write_pdf_file($deliveryid,$outputlangs) > 0)
        {
          // on supprime l'image correspondant au preview
           delivery_order_delete_preview($db, $deliveryid);
          return 1;
        }
      else
        {
          dolibarr_syslog("Erreur dans delivery_order_pdf_create");
          dolibarr_print_error($db,$obj->pdferror());
          return 0;
        }
    }
  else
    {
      print $langs->trans("Error")." ".$langs->trans("ErrorFileDoesNotExists",$dir.$file);
      return 0;
    }
}


function delivery_order_delete_preview($db, $deliveryid)
{
        global $langs,$conf;

        $delivery = new Livraison($db,"",$deliveryid);
        $delivery->fetch($deliveryid);  
        $client = new Societe($db);
        $client->fetch($delivery->socid);

        if ($conf->livraison->dir_output)
                {
                $deliveryref = sanitize_string($delivery->ref); 
                $dir = $conf->livraison->dir_output . "/" . $deliveryref ; 
                $file = $dir . "/" . $deliveryref . ".pdf.png";

                if ( file_exists( $file ) && is_writable( $file ) )
                        {
                        if ( ! unlink($file) )
                                {
                                $this->error=$langs->trans("ErrorFailedToOpenFile",$file);
                                return 0;
                                }
                        }
                }
}
?>
