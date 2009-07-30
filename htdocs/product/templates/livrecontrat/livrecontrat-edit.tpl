{* Copyright (C) 2006-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2007 Auguria SARL         <info@auguria.org>
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
 *}
 
 <!-- BEGIN SMARTY TEMPLATE -->

<form id="evolForm" action="fiche.php" method="post">
<input type="hidden" name="action" value="update">
<input type="hidden" name="id" value="{$prod_id}">
<input type="hidden" name="ref" value="{$prod_ref}">
<input type="hidden" name="libelle" value="{$prod_label}">
<input type="hidden" name="canvas" value="{$prod_canvas}">
<input type="hidden" name="statut" value="0">

<table class="border" width="100%">
 <tr>
   <td width="15%">Réf.</td>
   <td >{$prod_ref}</td>
 </tr>

 <tr>
  <td width="15%">Titre</td>
  <td width="85%" >{$prod_label}</td>
 </tr>

</table>

<br />

<table class="border" width="100%">
 <tr>
  <td width="15%">Stock</td>
  <td width="35%"><b>{$prod_stock_dispo}</b></td>
  <td width="15%">Seuil d'alerte stock</td>
  <td width="35%">
   <input name="seuil_stock_alerte" size="4" value="{$prod_seuil_stock_alerte}"
     class="normal" onfocus="this.className='focus';" onblur="this.className='normal';">
  </td>
 </tr>
</table>

<br />

<table class="border" width="100%">
<tr>
 <td width="15%">Durée du contrat :</td>
 <td width="35%">
  <input name="contrat_duree" type="text" size="7" maxlength="6" value="{$prod_contrat_duree}"
   class="normal" onfocus="this.className='focus';" onblur="this.className='normal';">
 </td>
 <td width="15%">Date d'application</td>
 <td width="35%">{html_select_date field_order='DMY' time=$prod_contrat_date_app start_year='-10' reverse_years=True}</td>
</tr>

<tr>
 <td>Taux conclu</td>
  <td>
   <input name="contrat_taux" type="text" size="7" maxlength="6" value="{$prod_contrat_taux}"
    class="normal" onfocus="this.className='focus';" onblur="this.className='normal';">%
  </td>
  <td>Quantité achetée</td>
  <td>
   <input name="contrat_quant" type="text" size="7" maxlength="6" value="{$prod_contrat_quant}"
    class="normal" onfocus="this.className='focus';" onblur="this.className='normal';">
  </td>
 </tr>
</table>

<br />

<table class="border" width="100%">
 <tr>
  <td width="15%" valign="top">Description</td>
  <td width="85%">
    <textarea name="desc" rows="6" cols="70">{$prod_description}</textarea>
  </td>
 </tr>

 <tr>
  <td width="15%" valign="top">Note (non visible sur les factures, propals...)
  </td>
  <td width="85%">
   <textarea name="note" rows="4" cols="70">{$prod_note}</textarea>
  </td>
 </tr>
 <tr>
  <td colspan="2" align="center">
   <input type="submit" class="button" value="Enregistrer">&nbsp;
   <input type="submit" class="button" name="cancel" value="Annuler">
  </td>
 </tr>
</table>
</form>
<!-- END SMARTY TEMPLATE -->