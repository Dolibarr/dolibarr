<?PHP
/* Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
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

function deneb_get_num_explain()
{

  $texte = '
Renvoie le numéro de facture sous la forme, PR-03-06-2004-15, où PR est le préfixe commercial de la société, et est suivi de la date (ici le 14 juin 2004) et d\'un compteur général. La constante FACTURE_DENEB_DELTA sert à la correction de plage. FACTURE_DENEB_DELTA ';

  if (defined("FACTURE_DENEB_DELTA"))
    {
      $texte .= "est défini et vaut : ".FACTURE_DENEB_DELTA;
    }
  else
    {
      $texte .= "n'est pas défini";
    }
  return $texte;

}

function venus_get_num_explain()
{

  return '
Renvoie le numéro de facture sous la forme, F-PR-030202, où PR est le préfixe commercial de la société, et est suivi de la date sur un format de 6 digits avec Année, Mois et Jour';

}

function pluton_get_num_explain()
{
  return '
Renvoie le numéro de facture sous une forme numérique simple, la première facture porte le numéro 1, la quinzième facture ayant le numéro 15.';
}

function neptune_get_num_explain()
{
  $texte = '
Identique à pluton, avec un correcteur au moyen de la constante FACTURE_NEPTUNE_DELTA.';
  if (defined("FACTURE_NEPTUNE_DELTA"))
    {
      $texte .= "Défini et vaut : ".FACTURE_NEPTUNE_DELTA;
    }
  else
    {
      $texte .= "N'est pas défini";
    }
  return $texte;
}


function jupiter_get_num_explain()
{
  return '
Système de numérotation mensuel sous la forme F20030715, qui correspond à la 15ème facture du mois de Juillet 2003';
}


/*!
		\brief Crée un facture sur disque en fonction du modèle de FACTURE_ADDON_PDF
		\param	db  		objet base de donnée
		\param	facid		id de la facture à créer
*/
function facture_pdf_create($db, $facid)
{
  
  $dir = DOL_DOCUMENT_ROOT . "/includes/modules/facture/";

  if (defined("FACTURE_ADDON_PDF"))
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
	  print $obj->error();
	  return 0;
	}
    }
  else
    {
      print "Erreur FACTURE_ADDON_PDF non définit !";
      return 0;
    }
}

?>
