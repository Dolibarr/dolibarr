<?php
/* Copyright (C) 2005-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**
		\file       htdocs/lib/antispamimage.php
		\brief      Return antispam image
		\version    $Revision$
*/

if (! defined('NOREQUIREMENU')) define('NOREQUIREMENU','1');


// C'est un wrapper, donc header vierge
function llxHeader() { }


require_once("../master.inc.php");
require_once DOL_DOCUMENT_ROOT.'/../external-libs/Artichow/Artichow.cfg.php';
require_once ARTICHOW."/AntiSpam.class.php";

// On créé l'objet anti-spam
$object = new AntiSpam();

// La valeur affichée sur l'image aura 5 lettres
$value=$object->setRand(5);
$object->setSize(128,36);

// Set value in session variable dol_antispam_value
$object->save('dol_antispam_value');

$object->setNoise(0);
$object->setAntiAliasing(false);

$colorbg1=new Color(250,250,250);
$colorbg2=new Color(230,220,210);
$colorfg=new Color(100,100,100);
$colorbr=new Color(220,210,200);
$colorra=new LinearGradient($colorbg1,$colorbg2,90);
//$object->setBackgroundColor($colorbg);
$object->setBackgroundGradient($colorra);
$object->border->setColor($colorbr);

// On affiche l'image à l'écran
$object->draw();
?>