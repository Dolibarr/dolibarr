<?PHP
/* Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 */

/*!	\file htdocs/includes/modules/facture/modules_facture.php
		\ingroup    facture
		\brief      Fichier contenant la classe mère de generation des factures en PDF
		            et la classe mère de numérotation des factures
		\version    $Revision$
*/

require_once(DOL_DOCUMENT_ROOT."/product.class.php");



/*!	\class ModelePDFFactures
		\brief  Classe mère des modèles de facture
*/

class ModelePDFFactures extends FPDF
{
    var $error='';

    /*!  \brief      Constructeur
     */
    function ModelePDFFactures()
    {
    
    }

   /*! 
        \brief Renvoi le dernier message d'erreur de création de facture
    */
    function pdferror()
    {
        return $this->error;
    }

}


/*!	\class ModeleNumRefFactures
		\brief  Classe mère des modèles de numérotation des références de facture
*/

class ModeleNumRefFactures
{
    var $error='';

    /*!     \brief      Constructeur
     */
    function ModeleNumRefFactures()
    {
    
    }

    /*!     \brief      Renvoi la description par defaut du modele de numérotation
     *      \return     string      Texte descripif
     */
    function getDesc()
    {
        global $langs;
        $langs->load("bills");
        return $langs->trans("NoDescription");
    }

   /*! 
        \brief Renvoi le dernier message d'erreur de création de facture
    */
    function numreferror()
    {
        return $this->error;
    }

}


/*!
		\brief      Crée un facture sur disque en fonction du modèle de FACTURE_ADDON_PDF
		\param	    db  		objet base de donnée
		\param	    facid		id de la facture à créer
*/
function facture_pdf_create($db, $facid)
{
  global $langs;
  $langs->load("bills");
  
  $dir = DOL_DOCUMENT_ROOT . "/includes/modules/facture/";

  if (defined("FACTURE_ADDON_PDF") && FACTURE_ADDON_PDF)
    {

      $file = "pdf_".FACTURE_ADDON_PDF.".modules.php";

      $classname = "pdf_".FACTURE_ADDON_PDF;
      require_once($dir.$file);

      $obj = new $classname($db);

      if ( $obj->write_pdf_file($facid) > 0)
	{
	  return 1;
	}
      else
	{
	  dolibarr_print_error($db,$obj->pdferror());
	  return 0;
	}
    }
  else
    {
      print $langs->trans("Error")." ".$langs->trans("Error_FACTURE_ADDON_PDF_NotDefined");
      return 0;
    }
}


/*!
		\brief      Renvoie la référence de facture suivante non utilisé en fonction du module 
		            de numérotation actif défini dans FACTURE_ADDON
		\param	    soc  		objet societe
		\return     string      reference libre pour la facture
*/
function facture_get_num($soc)
{
  global $db, $langs;
  $langs->load("bills");
  
  $dir = DOL_DOCUMENT_ROOT . "/includes/modules/facture/";

  if (defined("FACTURE_ADDON") && FACTURE_ADDON)
    {

      $file = FACTURE_ADDON."/".FACTURE_ADDON.".modules.php";

      $classname = "NumRefFactures".ucfirst(FACTURE_ADDON);
      require_once($dir.$file);

      $obj = new $classname();

      if ( $obj->getNumRef($soc) != "")
	{
	  return $obj->getNumRef($soc);
	}
      else
	{
	  dolibarr_print_error($db,$obj->numreferror());
	  return "";
	}
    }
  else
    {
      print $langs->trans("Error")." ".$langs->trans("Error_FACTURE_ADDON_NotDefined");
      return "";
    }
}

?>
