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
 * $Source$
 *}
<!-- BEGIN SMARTY TEMPLATE -->
<table width="100%" border="0" class="notopnoleftnoright" style="margin-bottom: 2px;">
<tr>
	<td class="nobordernopadding" width="40" align="left" valign="middle">
		{$title_picto}
	</td>
	<td class="nobordernopadding" valign="middle">
    	<div class="titre">{$title_text}</div>
	</td>
</tr>
</table>

<form id="evolForm" action="fiche.php" method="post">
<input type="hidden" name="action" value="add">
<input type="hidden" name="type" value="0">
<input type="hidden" name="canvas" value="livre">
<input type="hidden" name="price_base_type" value="TTC">

<table class="border" width="100%">
 <tr>
   <td width="15%">Référence</td>
   <td colspan="2">
    <input name="ref" size="20" value="{$prod_ref}"
     class="{$class_normal_ref}" onfocus="this.className='{$class_focus_ref}';" onblur="this.className='{$class_normal_ref}';">
  </td>
 </tr>

 <tr>
  <td width="15%">Titre</td>
  <td width="85%" colspan="3">
   <input name="libelle" size="40" value="{$prod_label}"
    class="normal" onfocus="this.className='focus';" onblur="this.className='normal';">
  </td>
 </tr>

 <tr>
  <td width="15%">ISBN</td>
  <td width="35%">
    <input name="isbna" size="2" maxlength="12" value="{$prod_isbna}"
     class="normal" onfocus="this.className='focus';" onblur="this.className='normal';">-
    <input name="isbnb" size="8" maxlength="7" value="{$prod_isbnb}"
     class="normal" onfocus="this.className='focus';" onblur="this.className='normal';">-
    <input name="isbnc" size="13" maxlength="12" class="normal" value="{$prod_isbnc}"
     onfocus="this.className='focus';" onblur="this.className='normal';" >
    {$prod_isbn}
  </td>
  <td width="15%">ISBN-13</td>
  <td width="35%"><i>sera calculé</i>
  </td>
 </tr>

 <tr>
  <td width="15%">EAN</td>
  <td colspan="3" width="85%"><i>sera calculé</i></td>
 </tr>

 <tr>
  <td>Pages</td>
  <td>
    <input name="pages" size="6" maxlength="5" value="{$prod_pages}"
     class="normal" onfocus="this.className='focus';" onblur="this.className='normal';">
  </td>
  <td>Format</td>
  <td>

   <select class="flat" name="format">
    {html_options values=$livre_available_formats output=$livre_available_formats selected="$prod_format"}
   </select>

</td>
 </tr>
<tr>
 <td>Prix au feuillet</td>
 <td>
  <input name="px_feuillet" type="text" size="7" maxlength="6" value="{$prod_pxfeuil}"
   class="normal" onfocus="this.className='focus';" onblur="this.className='normal';"> HT
 </td>
 <td>Prix couverture</td>
 <td>
  <input name="px_couverture" type="text" size="7" maxlength="6" value="{$prod_pxcouv}"
   class="normal" onfocus="this.className='focus';" onblur="this.className='normal';"> HT
 </td>
</tr>
<tr>
 <td>Prix de revient</td>
 <td><i>sera calculé</i></td>
 <td>Prix reliure</td>
 <td>
  <input name="px_reliure" type="text" size="7" maxlength="6" value="{$prod_pxreliure}"
   class="normal" onfocus="this.className='focus';" onblur="this.className='normal';">
 </td>
</tr>

 <tr>
  <td>Prix de vente</td>
  <td>
   <input name="price" type="text" size="7" maxlength="6" value="{$prod_price}"
    class="normal" onfocus="this.className='focus';" onblur="this.className='normal';"> TTC
  </td>
  <td>Taux TVA</td>
  <td>
   <select class="flat" name="tva_tx">
    {html_options values=$tva_taux_value output=$tva_taux_libelle selected="5.5"}
   </select>
  </td>
 </tr>
</table>

<br />

<table class="border" width="100%">
 <tr>
  <td width="15%">Seuil stock</td>
  <td width="35%">
   <input name="seuil_stock_alerte" size="4" value="{$prod_seuil_stock_alerte}"
     class="normal" onfocus="this.className='focus';" onblur="this.className='normal';">
  </td>
  <td width="15%">Emplacement Stock</td>
  <td width="35%">
   <input name="stock_loc" size="8" value="{$prod_stock_loc}"
     class="normal" onfocus="this.className='focus';" onblur="this.className='normal';">
  </td>
 </tr>
 <tr>
  <td>Statut</td>
  <td>
   <select class="flat" name="statut">
    <option value="1" selected="true">En vente</option>
    <option value="0">Hors vente</option>
   </select>
  </td>
  <td>Poids</td>
  <td>
   <input name="weight" size="5" value="{$prod_weight}"
     class="normal" onfocus="this.className='focus';" onblur="this.className='normal';">g
   <input name="weight_units" type="hidden" value="-3">
  </td>
 </tr>
</table>

<br />

<table class="border" width="100%">
 <tr>
  <td>Auteur / Editeur</td>
  <td>
   <select class="flat" name="auteur">
    {html_options options=$livre_available_auteurs selected=$livre_auteur_id}
   </select>
  </td>
  <td>Saisi par</td>
  <td>
    {$user}
  </td>
 </tr>
<tr>
 <td width="15%">Durée du contrat :</td>
 <td width="35%">
  <input name="contrat_duree" type="text" size="7" maxlength="6" value="{$livre_contrat_duree}"
   class="normal" onfocus="this.className='focus';" onblur="this.className='normal';">
 </td>
 <td width="15%">Date d'application</td>
 <td width="35%">{html_select_date field_order='DMY' start_year='-10' reverse_years=True all_extra='class="flat"'}</td>
</tr>

<tr>
 <td>Taux conclu</td>
 <td>
  <input name="contrat_taux" type="text" size="7" maxlength="6" value="{$livre_contrat_taux}"
   class="normal" onfocus="this.className='focus';" onblur="this.className='normal';">%
 </td>
 <td>Quantité achetée</td>
 <td>
  <input name="contrat_quant" type="text" size="7" maxlength="6" value="{$livre_contrat_quant}"
   class="normal" onfocus="this.className='focus';" onblur="this.className='normal';">
 </td>
</tr>
</table>

<br />

<table class="border" width="100%">
 <tr>
  <td width="15%" valign="top">Description</td>
  <td width="85%" colspan="3">
    <textarea name="desc" rows="6" cols="70"></textarea>
  </td>
 </tr>

 <tr>
  <td width="15%" valign="top">Note (non visible sur les factures, propals...)
  </td>
  <td width="85%" colspan="3">
   <textarea name="note" rows="4" cols="70"></textarea>
  </td>
 </tr>
 <tr>
  <td colspan="4" align="center">
   <input type="submit" class="button" value="Créer">
  </td>
 </tr>

</table>
</form>

<!-- END SMARTY TEMPLATE -->