<?PHP
/* Copyright (C) 2003 Xavier Dutoit <doli@sydesy.com>
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
* Pour se déconnecter
* @package User
*/
if (!empty ($_SERVER["REMOTE_USER"]))
   die("La d&eacute;connection ne fonctionne actuellement que pour l'authentification par pear");
require_once "Auth/Auth.php";
$a = new Auth("DB");
$a->setShowLogin (false);
$a->start();
if ($a->getAuth()) 
  $a->logout();
header("Location: /"); 
?>
