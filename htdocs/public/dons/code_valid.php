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

print '<input type="hidden" name="projetid"    value="'.$HTTP_POST_VARS["projetid"].'">';
print '<input type="hidden" name="prenom"      value="'.$HTTP_POST_VARS["prenom"].'">';
print '<input type="hidden" name="nom"         value="'.$HTTP_POST_VARS["nom"].'">';
print '<input type="hidden" name="societe"     value="'.$HTTP_POST_VARS["societe"].'">';
print '<input type="hidden" name="adresse"     value="'.$HTTP_POST_VARS["adresse"].'">';
print '<input type="hidden" name="cp"          value="'.$HTTP_POST_VARS["cp"].'">';
print '<input type="hidden" name="ville"       value="'.$HTTP_POST_VARS["ville"].'">';
print '<input type="hidden" name="pays"        value="'.$HTTP_POST_VARS["pays"].'">';
print '<input type="hidden" name="date"        value="'.$HTTP_POST_VARS["date"].'">';
print '<input type="hidden" name="public"      value="'.$HTTP_POST_VARS["public"].'">';
print '<input type="hidden" name="email"       value="'.$HTTP_POST_VARS["email"].'">';
print '<input type="hidden" name="montant"     value="'.$HTTP_POST_VARS["montant"].'">';
print '<input type="hidden" name="commentaire" value="'.$HTTP_POST_VARS["commentaire"].'">';


?>
