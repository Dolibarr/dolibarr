<?PHP
/* Copyright (C) 2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

print '<input type="hidden" name="projetid"    value="'.$_POST["projetid"].'">';
print '<input type="hidden" name="prenom"      value="'.$_POST["prenom"].'">';
print '<input type="hidden" name="nom"         value="'.$_POST["nom"].'">';
print '<input type="hidden" name="societe"     value="'.$_POST["societe"].'">';
print '<input type="hidden" name="adresse"     value="'.$_POST["adresse"].'">';
print '<input type="hidden" name="cp"          value="'.$_POST["cp"].'">';
print '<input type="hidden" name="ville"       value="'.$_POST["ville"].'">';
print '<input type="hidden" name="pays"        value="'.$_POST["pays"].'">';
print '<input type="hidden" name="date"        value="'.$_POST["date"].'">';
print '<input type="hidden" name="public"      value="'.$_POST["public"].'">';
print '<input type="hidden" name="email"       value="'.$_POST["email"].'">';
print '<input type="hidden" name="montant"     value="'.$_POST["montant"].'">';
print '<input type="hidden" name="commentaire" value="'.$_POST["commentaire"].'">';


?>
