<?PHP
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003 Xavier Dutoit        <doli@sydesy.com> 
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
 * 
 * $Id$
 * $Source$
 *
 * Ce fichier a vocation de disparaitre, la configuration se faisant 
 * dorénavant dans la base de donnée
 *
 */

/*!	\file htdocs/conf/conf.class.php
		\brief      Fichier de la classe de stockage de la config courante
		\version    $Revision$
*/


/*!	\class Conf
		\brief      Classe de stockage de la config courante
		\todo       Deplacer ce fichier dans htdocs/lib
*/

class Conf
{
    /*! \public */
    var $db;         // Objet des caractéristiques de connexions
                    // base db->host, db->name, db->user, db->pass, db->type
    var $langage;    // Langue choisit fr_FR, en_US, ...

    var $externalrss;
    var $commande;
    var $ficheinter;
    var $commercial;
    var $societe;
    var $expedition;
    var $compta;
    var $banque;
    var $don;
    var $caisse;
    var $fournisseur;
    var $adherent;
    var $produit;
    var $service;
    var $stock;
    var $boutique;
    var $projet;
    var $postnuke;
    var $webcal;
    var $propal;
    
}



?>
